// Unified Profile JS for all roles
const showProfileToast = (icon, title, text, theme, duration = 3000) => {
    if (typeof Swal == "undefined") return alert(`${title}: ${text}`);
    const themeMap = {
        warning: { color: "text-orange-600", bg: "bg-orange-100", border: "!border-orange-400", icon: "ph-warning" },
        error: { color: "text-red-600", bg: "bg-red-100", border: "!border-red-400", icon: "ph-x-circle" },
        success: { color: "text-green-600", bg: "bg-green-100", border: "!border-green-400", icon: "ph-check-circle" },
    };
    const selectedTheme = themeMap[theme];
    Swal.fire({
        toast: true, position: "bottom-end", showConfirmButton: false, timer: duration, width: "360px", background: "transparent",
        html: `<div class="flex flex-col text-left"><div class="flex items-center gap-3 mb-2"><div class="flex items-center justify-center w-10 h-10 rounded-full ${selectedTheme.bg} ${selectedTheme.color}"><i class="ph ${selectedTheme.icon} text-lg"></i></div><div><h3 class="text-[15px] font-semibold ${selectedTheme.color}">${title}</h3><p class="text-[13px] text-gray-700 mt-0.5">${text}</p></div></div></div>`,
        customClass: { popup: `!rounded-xl !shadow-md !border-2 !p-4 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] backdrop-blur-sm ${selectedTheme.border} shadow-[0_0_8px_#ffb34770]` },
    });
};

const showLoadingModal = (message = "Processing request...", subMessage = "Please wait.") => {
    if (typeof Swal == "undefined") return;
    Swal.fire({
        background: "transparent",
        html: `<div class="flex flex-col items-center justify-center gap-2"><div class="animate-spin rounded-full h-10 w-10 border-4 border-orange-200 border-t-orange-600"></div><p class="text-gray-700 text-[14px]">${message}<br><span class="text-sm text-gray-500">${subMessage}</span></p></div>`,
        allowOutsideClick: false, showConfirmButton: false,
        customClass: { popup: "!rounded-xl !shadow-md !border-2 !border-orange-400 !p-6 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_8px_#ffb34770]" },
    });
};

document.addEventListener("DOMContentLoaded", () => {
    const role = window.USER_ROLE.toLowerCase();
    const API_BASE = `${window.BASE_URL}/api/profile`;
    
    // Elements
    const profileForm = document.getElementById("profileForm");
    const editProfileBtn = document.getElementById("editProfileBtn");
    const cancelProfileBtn = document.getElementById("cancelProfileBtn");
    const formActions = document.getElementById("formActions");
    const profilePreview = document.getElementById("profilePreview");
    const profileIcon = document.getElementById("profileIcon");
    const uploadOverlay = document.getElementById("uploadOverlay");
    const uploadLabel = document.getElementById("uploadLabel");
    const uploadInput = document.getElementById("uploadProfile");
    
    const cropModal = document.getElementById("cropModal");
    const cropImage = document.getElementById("cropImage");
    let cropper, croppedBlob = null;

    let originalData = {};
    let isEditing = false;

    // Sections
    const studentSection = document.getElementById("studentDetailsSection");
    const employmentSection = document.getElementById("employmentDetailsSection");

    async function loadProfile() {
        showLoadingModal("Loading profile...");
        try {
            const res = await fetch(`${API_BASE}/get`);
            const data = await res.json();
            if (typeof Swal != "undefined") Swal.close();

            if (data.success && data.profile) {
                const p = data.profile;
                originalData = p;
                renderProfile(p);
            }
        } catch (err) {
            if (typeof Swal != "undefined") Swal.close();
            showProfileToast("ph-x-circle", "Error", "Failed to load profile.", "error");
        }
    }

    function renderProfile(p) {
        // Common Headers
        const nameEl = document.getElementById("profileName");
        const identEl = document.getElementById("profileIdentifier");
        const fullName = [p.first_name, p.middle_name, p.last_name, p.suffix].filter(Boolean).join(" ");
        if (nameEl) nameEl.textContent = fullName || "User";
        if (identEl) identEl.textContent = p.student_number || p.employee_id || p.unique_faculty_id || p.user_id;

        // Basic Info
        const setVal = (id, val) => { const el = document.getElementById(id); if (el) el.value = val || ""; };
        setVal("firstName", p.first_name);
        setVal("middleName", p.middle_name);
        setVal("lastName", p.last_name);
        setVal("suffix", p.suffix);
        setVal("email", p.email);
        setVal("contact", p.contact);
        setVal("campusName", p.campus_name);

        const genderSelect = document.getElementById("gender");
        const genderOther = document.getElementById("genderOther");
        if (genderSelect) {
            const standard = ["Male", "Female", "LGBTQIA+", "Prefer not to say", "Other"];
            if (standard.includes(p.gender)) {
                genderSelect.value = p.gender;
                if (genderOther) genderOther.classList.add("hidden");
            } else if (p.gender) {
                genderSelect.value = "Other";
                if (genderOther) {
                    genderOther.value = p.gender;
                    genderOther.classList.remove("hidden");
                }
            }
        }

        // Profile Picture
        if (p.profile_picture) {
            const cleanPath = p.profile_picture.replace(/^\//, "");
            profilePreview.src = `${window.STORAGE_URL}/${cleanPath}`;
            profilePreview.classList.remove("hidden");
            profileIcon.classList.add("hidden");
        }

        // Role Specific Logic
        if (role === 'student') {
            studentSection.classList.remove("hidden");
            setVal("studentNumber", p.student_number);
            setVal("yearLevel", p.year_level);
            setVal("section", p.section);
            loadCourseOptions(p.course_id);
            
            if (p.registration_form) {
                const viewBtn = document.getElementById("viewRegForm");
                if (viewBtn) {
                    viewBtn.href = `${window.STORAGE_URL}/${p.registration_form.replace(/^\//, "")}`;
                    viewBtn.classList.remove("hidden");
                }
            }
            
            const badge = document.getElementById("verificationBadge");
            if (badge && p.profile_updated == 1 && p.is_qualified) badge.classList.remove("hidden");
        } else if (['staff', 'faculty'].includes(role)) {
            employmentSection.classList.remove("hidden");
            setVal("employeeId", p.employee_id || p.unique_faculty_id);
            setVal("department", p.department || p.college_code);
            if (role === 'staff') {
                document.getElementById("positionWrapper")?.classList.remove("hidden");
                setVal("position", p.position);
            }
        }

        // Edit Button Visibility
        const lockedInfo = document.getElementById("profileLockedInfo");
        if (role === 'student' && p.profile_updated == 1 && p.can_edit_profile == 0) {
            editProfileBtn.classList.add("hidden");
            lockedInfo?.classList.remove("hidden");
        } else {
            editProfileBtn.classList.remove("hidden");
            lockedInfo?.classList.add("hidden");
        }
    }

    async function loadCourseOptions(currentId) {
        const select = document.getElementById("course");
        if (!select) return;
        try {
            const res = await fetch(`${window.BASE_URL}/api/data/getAllCourses`);
            const data = await res.json();
            select.innerHTML = '<option value="">Select Course</option>';
            if (data.success) {
                data.courses.forEach(c => {
                    const opt = new Option(`${c.course_code} - ${c.course_title}`, c.course_id);
                    select.add(opt);
                });
                if (currentId) select.value = currentId;
            }
        } catch (e) {}
    }

    function toggleEdit(editing) {
        isEditing = editing;
        const inputs = profileForm.querySelectorAll('input:not([disabled]), select:not([disabled])');
        const allToggleable = profileForm.querySelectorAll('input, select');
        
        allToggleable.forEach(input => {
            if (['studentNumber', 'employeeId', 'campusName'].includes(input.id)) return;
            input.disabled = !editing;
            input.classList.toggle("bg-white", editing);
            input.classList.toggle("bg-gray-50", !editing);
            input.classList.toggle("border-orange-500", editing);
        });

        formActions.classList.toggle("hidden", !editing);
        editProfileBtn.classList.toggle("hidden", editing);
        uploadOverlay.classList.toggle("hidden", !editing);
        uploadLabel.classList.toggle("hidden", !editing);
        
        if (role === 'student') {
            document.getElementById("uploadBtn")?.classList.toggle("hidden", !editing || originalData.registration_form);
            document.getElementById("removeRegForm")?.classList.toggle("hidden", !editing || !originalData.registration_form);
        }

        if (!editing) loadProfile();
    }

    editProfileBtn?.addEventListener("click", () => toggleEdit(true));
    cancelProfileBtn?.addEventListener("click", async () => {
        if (await showConfirmationModal("Discard changes?", "Unsaved changes will be lost.")) toggleEdit(false);
    });

    profileForm.onsubmit = async (e) => {
        e.preventDefault();
        if (!await showConfirmationModal("Save changes?", "Confirm profile update.")) return;

        showLoadingModal("Saving...");
        const formData = new FormData(profileForm);
        if (croppedBlob) formData.append("profile_image", croppedBlob, "profile.png");

        try {
            const res = await fetch(`${API_BASE}/update`, { method: "POST", body: formData });
            const data = await res.json();
            if (data.success) {
                Swal.close();
                showProfileToast("ph-check-circle", "Success", "Profile updated.", "success");
                toggleEdit(false);
            } else {
                showProfileToast("ph-x-circle", "Error", data.message || "Update failed.", "error");
            }
        } catch (e) { Swal.close(); }
    };

    // Image Upload & Crop
    uploadInput?.addEventListener("change", (e) => {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = () => {
            cropImage.src = reader.result;
            cropModal.classList.remove("hidden");
            if (cropper) cropper.destroy();
            cropper = new Cropper(cropImage, { aspectRatio: 1, viewMode: 1 });
        };
        reader.readAsDataURL(file);
    });

    document.getElementById("saveCrop")?.addEventListener("click", () => {
        const canvas = cropper.getCroppedCanvas({ width: 200, height: 200 });
        canvas.toBlob(blob => {
            croppedBlob = blob;
            profilePreview.src = canvas.toDataURL();
            profilePreview.classList.remove("hidden");
            profileIcon.classList.add("hidden");
            cropModal.classList.add("hidden");
        });
    });

    loadProfile();
});
