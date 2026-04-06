// --- SweetAlert Helper Functions ---

const showProfileToast = (icon, title, text, theme, duration = 3000) => {
  if (typeof Swal == "undefined") return alert(`${title}: ${text}`);

  const themeMap = {
    warning: {
      color: "text-orange-600",
      bg: "bg-orange-100",
      border: "!border-orange-400",
      icon: "ph-warning",
    },
    error: {
      color: "text-red-600",
      bg: "bg-red-100",
      border: "!border-red-400",
      icon: "ph-x-circle",
    },
    success: {
      color: "text-green-600",
      bg: "bg-green-100",
      border: "!border-green-400",
      icon: "ph-check-circle",
    },
  };
  const selectedTheme = themeMap[theme];

  Swal.fire({
    toast: true,
    position: "bottom-end",
    showConfirmButton: false,
    timer: duration,
    width: "360px",
    background: "transparent",
    html: `
            <div class="flex flex-col text-left">
                <div class="flex items-center gap-3 mb-2">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full ${selectedTheme.bg} ${selectedTheme.color}">
                        <i class="ph ${selectedTheme.icon} text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-[15px] font-semibold ${selectedTheme.color}">${title}</h3>
                        <p class="text-[13px] text-gray-700 mt-0.5">${text}</p>
                    </div>
                </div>
            </div>
        `,
    customClass: {
      popup: `!rounded-xl !shadow-md !border-2 !p-4 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] backdrop-blur-sm ${selectedTheme.border} shadow-[0_0_8px_#ffb34770]`,
    },
  });
};

const showLoadingModal = (
  message = "Processing request...",
  subMessage = "Please wait.",
) => {
  if (typeof Swal == "undefined") return;
  Swal.fire({
    background: "transparent",
    html: `
            <div class="flex flex-col items-center justify-center gap-2">
                <div class="animate-spin rounded-full h-10 w-10 border-4 border-orange-200 border-t-orange-600"></div>
                <p class="text-gray-700 text-[14px]">${message}<br><span class="text-sm text-gray-500">${subMessage}</span></p>
            </div>
        `,
    allowOutsideClick: false,
    showConfirmButton: false,
    customClass: {
      popup:
        "!rounded-xl !shadow-md !border-2 !border-orange-400 !p-6 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_8px_#ffb34770]",
    },
  });
};

const showFinalModal = (isSuccess, title, message) => {
  if (typeof Swal == "undefined") return alert(`${title}: ${message}`);

  const duration = 3000;
  let timerInterval;

  const theme = isSuccess
    ? {
        bg: "bg-green-50",
        border: "border-green-300",
        text: "text-green-700",
        iconBg: "bg-green-100",
        iconColor: "text-green-600",
        iconClass: "ph-check-circle",
        progressBarColor: "bg-green-500",
      }
    : {
        bg: "bg-red-50",
        border: "border-red-300",
        text: "text-red-700",
        iconBg: "bg-red-100",
        iconColor: "text-red-600",
        iconClass: "ph-x-circle",
        progressBarColor: "bg-red-500",
      };

  Swal.fire({
    showConfirmButton: false,
    showCancelButton: false,
    buttonsStyling: false,

    width: "450px",

    backdrop: `rgba(0,0,0,0.3) backdrop-filter: blur(6px)`,
    timer: duration,

    didOpen: () => {
      const progressBar =
        Swal.getHtmlContainer().querySelector("#progress-bar");
      let width = 100;
      timerInterval = setInterval(() => {
        width -= 100 / (duration / 100);
        if (progressBar) {
          progressBar.style.width = width + "%";
        }
      }, 100);
    },
    willClose: () => {
      clearInterval(timerInterval);
    },

    html: `
            <div class="w-full ${theme.bg} border-2 ${theme.border} rounded-2xl p-8 shadow-xl text-center">
                <div class="flex items-center justify-center w-16 h-16 rounded-full ${theme.iconBg} mx-auto mb-4">
                    <i class="ph ${theme.iconClass} ${theme.iconColor} text-3xl"></i>
                </div>
                <h3 class="text-2xl font-bold ${theme.text}">${title}</h3>
                <p class="text-base ${theme.text} mt-3 mb-4">
                    ${message}
                </p>
                <div class="w-full bg-gray-200 h-2 rounded mt-4 overflow-hidden">
                    <div id="progress-bar" class="${theme.progressBarColor} h-2 w-full transition-all duration-100 ease-linear"></div>
                </div>
            </div>
        `,
    customClass: {
      popup:
        "!block !bg-transparent !shadow-none !p-0 !border-0 !w-auto !min-w-0 !max-w-none",
    },
  });
};

async function showConfirmationModal(title, text, confirmText = "Confirm") {
  if (typeof Swal == "undefined") return confirm(title);
  const result = await Swal.fire({
    background: "transparent",
    buttonsStyling: false, // Required for custom buttons
    width: "450px",

    html: `
            <div class="flex flex-col text-center">
                <div class="flex justify-center mb-3">
                    <div class="flex items-center justify-center w-16 h-16 rounded-full bg-orange-100 text-orange-600">
                        <i class="ph ph-warning-circle text-3xl"></i>
                    </div>
                </div>
                <h3 class="text-xl font-semibold text-gray-800">${title}</h3>
                <p class="text-[14px] text-gray-700 mt-1">${text}</p>
            </div>
        `,
    showCancelButton: true,
    confirmButtonText: confirmText,
    cancelButtonText: "Cancel",

    customClass: {
      popup:
        "!rounded-xl !shadow-lg !p-6 !bg-white !border-2 !border-orange-400 shadow-[0_0_15px_#ffb34780]",

      confirmButton:
        "!bg-orange-600 !text-white !px-5 !py-2.5 !rounded-lg hover:!bg-orange-700 !mx-2 !font-semibold !text-base",

      cancelButton:
        "!bg-gray-200 !text-gray-800 !px-5 !py-2.5 !rounded-lg hover:!bg-gray-300 !mx-2 !font-semibold !text-base",

      actions: "!mt-4",
    },
  });
  return result.isConfirmed;
}

document.addEventListener("DOMContentLoaded", () => {
  const uploadInput = document.getElementById("uploadProfile");
  const profilePreview = document.getElementById("profilePreview");
  const profileIcon = document.getElementById("profileIcon");
  const cropModal = document.getElementById("cropModal");
  const cropImage = document.getElementById("cropImage");
  const cancelCrop = document.getElementById("cancelCrop");
  const saveCrop = document.getElementById("saveCrop");
  const zoomIn = document.getElementById("zoomIn");
  const zoomOut = document.getElementById("zoomOut");
  const resetCrop = document.getElementById("resetCrop");
  let cropper;
  let croppedBlob = null;

  const regFormUpload = document.getElementById("regFormUpload");
  const viewRegForm = document.getElementById("viewRegForm");
  const uploadBtn = document.getElementById("uploadBtn");
  const removeRegForm = document.getElementById("removeRegForm");

  const profileForm = document.getElementById("profileForm");
  const editProfileBtn = document.getElementById("editProfileBtn");
  const cancelProfileBtn = document.getElementById("cancelProfileBtn");
  const formActions = document.getElementById("formActions");
  const profileName = document.getElementById("profileName");
  const profileStudentId = document.getElementById("profileStudentId");
  const uploadLabel = document.getElementById("uploadLabel");
  const profileLockedInfo = document.getElementById("profileLockedInfo");

  const genderSelect = document.getElementById("gender");
  const genderOtherInput = document.getElementById("genderOther");

  if (genderSelect) {
    genderSelect.addEventListener("change", function () {
      if (this.value === "Other") {
        genderOtherInput.classList.remove("hidden");
        genderOtherInput.disabled = false;
        genderOtherInput.focus();
      } else {
        genderOtherInput.classList.add("hidden");
        genderOtherInput.value = "";
        genderOtherInput.disabled = true;
      }
    });
  }

  const courseSelect = document.getElementById("course");
  const allInputs = profileForm.querySelectorAll(
    'input[type="text"], input[type="email"], input[type="tel"], input[type="number"], select',
  );
  const editableInputs = Array.from(allInputs).filter(
    (input) => input.id !== "studentNumber",
  );

  const MAX_FILE_SIZE = 1 * 1024 * 1024;
  let originalProfileData = {};
  let isEditing = false;


  async function loadCourseOptions(currentCourseId = null) {
    if (!courseSelect) return;

    courseSelect.innerHTML = '<option value="">Loading Courses...</option>';
    courseSelect.disabled = true;

    showLoadingModal("Loading course options...", "Fetching data.");

    try {
      const res = await fetch("api/data/getAllCourses");
      const data = await res.json();

      if (typeof Swal != "undefined") Swal.close();

      courseSelect.innerHTML = "";
      const defaultOption = new Option("Select a Course", "");
      courseSelect.add(defaultOption);

      if (data.success && Array.isArray(data.courses)) {
        data.courses.forEach((course) => {
          const option = new Option(
            `${course.course_code} - ${course.course_title}`,
            String(course.course_id),
          );
          courseSelect.add(option);
        });
      }

      if (currentCourseId) {
        courseSelect.value = String(currentCourseId);
      }
      if (!isEditing) courseSelect.disabled = true;
    } catch (err) {
      if (typeof Swal != "undefined") Swal.close();
      console.error("Error fetching course options:", err);
      courseSelect.innerHTML = `<option value="${currentCourseId || ""}">Error loading courses</option>`;
      courseSelect.disabled = true;
      showProfileToast(
        "ph-x-circle",
        "Error",
        "Failed to load course list.",
        "error",
        3000,
      );
    }
  }

  async function loadProfile() {
    showLoadingModal(
      "Loading profile data...",
      "Retrieving your latest information.",
    );

    try {
      const res = await fetch("api/student/myprofile/get");
      if (!res.ok) {
        const errData = await res.json().catch(() => null);
        throw new Error(
          errData?.message || `Failed to fetch profile. Status: ${res.status}`,
        );
      }
      await new Promise((resolve) => setTimeout(resolve, 2000)); // 2 seconds delay bago close
      Swal.close();

      if (typeof Swal != "undefined") Swal.close();

      const data = await res.json();
      if (data.success && data.profile) {
        const profile = data.profile;
        originalProfileData = profile;

        const fullName =
          profile.full_name ||
          [
            profile.first_name,
            profile.middle_name,
            profile.last_name,
            profile.suffix,
          ]
            .filter(Boolean)
            .join(" ") ||
          "Student Name";
        profileName.textContent = fullName;
        profileStudentId.textContent = profile.student_number || "Student ID";

        document.getElementById("firstName").value = profile.first_name || "";
        document.getElementById("middleName").value = profile.middle_name || "";
        document.getElementById("lastName").value = profile.last_name || "";
        document.getElementById("suffix").value = profile.suffix || "";
        document.getElementById("studentNumber").value =
          profile.student_number || "";
        document.getElementById("yearLevel").value = profile.year_level || "";
        document.getElementById("section").value = profile.section || "";
        document.getElementById("email").value = profile.email || "";
        document.getElementById("contact").value = profile.contact || "";

        // Campus name
        const campusNameInput = document.getElementById("campusName");
        if (campusNameInput) {
            campusNameInput.value = profile.campus_name || "N/A";
            if (!isEditing) {
                campusNameInput.disabled = true;
            }
        }

        if (genderSelect) {
          const standardOptions = ["Male", "Female", "LGBTQIA+", "Prefer not to say", "Other"];
          let genderValue = profile.gender || "";

          // If the value is standard, select it.
          if (standardOptions.includes(genderValue)) {
            genderSelect.value = genderValue;
            genderOtherInput.classList.add("hidden");
            genderOtherInput.value = "";
          } 
          else if (genderValue) {
            genderSelect.value = "Other";
            genderOtherInput.value = genderValue;
            genderOtherInput.classList.remove("hidden");
          } 
          else {
             genderSelect.value = "";
             genderOtherInput.classList.add("hidden");
          }
           
           if (!isEditing) {
             genderSelect.disabled = true;
             genderOtherInput.disabled = true;
           }
        }

        const currentCourseId = String(profile.course_id) || "";
        const courseDisplayName = profile.course_code
          ? `${profile.course_code} - ${profile.course_title}`
          : "Select a Course";

        if (!isEditing && courseSelect) {
          courseSelect.innerHTML = `<option value="${currentCourseId}">${courseDisplayName}</option>`;
          courseSelect.value = currentCourseId;
          courseSelect.disabled = true;
        } else if (isEditing) {
          loadCourseOptions(currentCourseId);
        }

        if (data.profile.profile_picture) {
          const cleanPath = data.profile.profile_picture.replace(/^\//, "");
          const finalUrl = window.STORAGE_URL + '/' + cleanPath;

          profilePreview.src = finalUrl;
          profilePreview.classList.remove("hidden");
          profileIcon.classList.add("hidden");

          const headerPic = document.getElementById("headerProfilePic");
          if (headerPic) {
            headerPic.src = finalUrl;
          }
        }

        if (profile.registration_form) {
          const cleanRegFormPath = profile.registration_form.replace(/^\//, "");
          viewRegForm.href = window.STORAGE_URL + '/' + cleanRegFormPath;
          viewRegForm.classList.remove("hidden");
          uploadBtn.classList.add("hidden");
          if (!isEditing && removeRegForm) removeRegForm.classList.add("hidden");
          else if (isEditing && removeRegForm) removeRegForm.classList.remove("hidden");
        } else {
          viewRegForm.classList.add("hidden");
          if (isEditing) uploadBtn.classList.remove("hidden");
          if (removeRegForm) removeRegForm.classList.add("hidden");
        }

        if (profile.profile_updated == 0 || profile.can_edit_profile == 1) {
          editProfileBtn.classList.remove("hidden");
          profileLockedInfo.classList.add("hidden");
        } else {
          editProfileBtn.classList.add("hidden");
          profileLockedInfo.classList.remove("hidden");
        }

        const verificationBadge = document.getElementById("verificationBadge");
        if (verificationBadge) {
          if (profile.profile_updated == 1 && profile.is_qualified) {
            verificationBadge.classList.remove("hidden");
            verificationBadge.classList.add("inline-flex");
          } else {
            verificationBadge.classList.add("hidden");
            verificationBadge.classList.remove("inline-flex");
          }
        }
      } else {
        throw new Error(data.message || "Could not parse profile data.");
      }
    } catch (err) {
      if (typeof Swal != "undefined") Swal.close();
      console.error("Load profile error:", err);
      showProfileToast(
        "ph-x-circle",
        "Error",
        "Could not load your profile. " + err.message,
        "error",
        5000,
      );
    }
  }

  function toggleEdit(shouldEdit) {
    isEditing = shouldEdit;

    if (shouldEdit) {
      loadCourseOptions(originalProfileData.course_id);

      editableInputs.forEach((input) => {
        if (input.id === 'genderOther' && genderSelect && genderSelect.value !== 'Other') return;

        input.disabled = false;
        input.classList.remove("bg-gray-50", "border-gray-200");
        input.classList.add(
          "bg-white",
          "border-gray-300",
          "focus:border-orange-500",
          "focus:ring-orange-500",
        );
      });

      if (genderSelect) {
        genderSelect.disabled = false;
        genderSelect.classList.remove("bg-gray-50", "border-gray-200");
        genderSelect.classList.add("bg-white", "border-gray-300", "focus:border-orange-500", "focus:ring-orange-500");
      }

      // Ensure campus input is disabled and styled like User ID
      const campusNameInput = document.getElementById("campusName");
      if (campusNameInput) {
          campusNameInput.disabled = true;
          // Explicitly set styles for disabled state to match User ID field
          campusNameInput.classList.remove("bg-white", "border-gray-300", "focus:border-orange-500", "focus:ring-orange-500"); // Remove editing styles
          campusNameInput.classList.add("bg-gray-100", "border-gray-300"); // Apply disabled styles
      }

      formActions.classList.remove("hidden"); // Show Save/Cancel
      editProfileBtn.classList.add("hidden"); // Hide Edit button when editing starts
      uploadLabel.classList.remove("hidden");
      uploadBtn.classList.remove("hidden");
      regFormUpload.disabled = false;
      viewRegForm.classList.remove("hidden");

      if (originalProfileData.registration_form) {
        if (removeRegForm) removeRegForm.classList.remove("hidden");
        uploadBtn.classList.add("hidden");
        const cleanRegFormPath = originalProfileData.registration_form.replace(/^\//, "");
        viewRegForm.href = window.STORAGE_URL + '/' + cleanRegFormPath;
      } else {
        viewRegForm.classList.add("hidden");
        if (removeRegForm) removeRegForm.classList.add("hidden");
        uploadBtn.classList.remove("hidden");
      }

    } else { // !shouldEdit
      loadProfile(); // Re-load profile to set initial states correctly, including editProfileBtn visibility

      editableInputs.forEach((input) => {
        input.disabled = true;
        input.classList.add("bg-gray-50", "border-gray-200");
        input.classList.remove(
          "bg-white",
          "border-gray-300",
          "focus:border-orange-500",
          "focus:ring-orange-500",
        );
      });

      if (genderSelect) {
        genderSelect.disabled = true;
        genderSelect.classList.add("bg-gray-50", "border-gray-200");
        genderSelect.classList.remove("bg-white", "border-gray-300", "focus:border-orange-500", "focus:ring-orange-500");
      }
      if (genderOtherInput) {
        genderOtherInput.disabled = true;
        genderOtherInput.classList.add("bg-gray-50", "border-gray-200");
        genderOtherInput.classList.remove("bg-white", "border-gray-300");
      }

      formActions.classList.add("hidden"); // Hide Save/Cancel
      regFormUpload.disabled = true;
      if (removeRegForm) removeRegForm.classList.add("hidden");

      // Rely on loadProfile() for editProfileBtn visibility when not editing.
      // The redundant visibility logic for editProfileBtn in this else block is removed.

      if (originalProfileData.registration_form) { // Handles viewRegForm visibility
        viewRegForm.classList.remove("hidden");
      }
    }
  }

  editProfileBtn?.addEventListener("click", () => toggleEdit(true));

  // --- 🟠 CANCEL CONFIRMATION (Using the screenshot design style) ---
  cancelProfileBtn?.addEventListener("click", async () => {
    const isConfirmed = await showConfirmationModal(
      "Discard Changes?",
      "Are you sure you want to cancel? All unsaved changes will be lost.",
      "Yes, Discard!",
    );

    if (isConfirmed) {
      toggleEdit(false);
    }
  });

  profileForm?.addEventListener("submit", async (e) => {
    e.preventDefault();

    const formData = new FormData(profileForm);

    const requiredFields = [
      "first_name",
      "last_name",
      "course",
      "year_level",
      "section",
      "email",
      "contact",
    ];
    let missingFields = [];
    for (const field of requiredFields) {
      const value = formData.get(field);
      if (
        !value ||
        value.trim() === "" ||
        (field === "course" && (value === "0" || value === ""))
      ) {
        missingFields.push(field);
      }
    }

    if (missingFields.length > 0) {
      showProfileToast(
        "ph-warning",
        "Missing Info",
        `Please fill in all required fields. Middle Name and Suffix are optional. (Missing: ${missingFields.join(", ")})`,
        "warning",
      );
      return;
    }

    const contact = formData.get("contact");
    if (!/^\d{11}$/.test(contact)) {
      showProfileToast(
        "ph-warning",
        "Invalid Contact",
        "Contact number must be numeric and 11 digits.",
        "warning",
      );
      return;
    }

    const email = formData.get("email");
    if (
      !/^[^--Ÿ -퟿豈-﷏ﷰ-￯]+@[^--Ÿ -퟿豈-﷏ﷰ-￯]+\.[^--Ÿ -퟿豈-﷏ﷰ-￯]+$/.test(email)
    ) {
      showProfileToast(
        "ph-warning",
        "Invalid Email",
        "Please enter a valid email address.",
        "warning",
      );
      return;
    }

    const hasProfilePic =
      profilePreview.src && !profilePreview.classList.contains("hidden");
    if (!hasProfilePic && !croppedBlob) {
      showProfileToast(
        "ph-warning",
        "Missing Profile Picture",
        "Profile picture is required.",
        "warning",
      );
      return;
    }

    const hasRegForm =
      viewRegForm.href &&
      !viewRegForm.classList.contains("hidden") &&
      !viewRegForm.href.includes("blob:");
    if (!hasRegForm && regFormUpload.files.length === 0) {
      showProfileToast(
        "ph-warning",
        "Missing Registration Form",
        "Registration form is required.",
        "warning",
      );
      return;
    }

    // --- 🟠 CONFIRM CHANGES MODAL (Using the screenshot design style) ---
    const confirm = await showConfirmationModal(
      "Confirm Changes",
      "Are you sure? You can only do this once.",
      "Yes, save it!",
    );

    if (!confirm) return;

    showLoadingModal(
      "Saving profile...",
      "Updating your details. This might take a moment.",
    );

    if (croppedBlob) {
      formData.append("profile_image", croppedBlob, "profile.png");
    }

    const selectedCourseValue = formData.get("course");
    formData.delete("course");
    formData.append("course_id", selectedCourseValue);

    try {
      const res = await fetch("api/student/myprofile/update", {
        method: "POST",
        body: formData,
      });

      Swal.close();

      const result = await res.json();
      const message = result.message || "Unknown response.";

      if (result.success) {
        showFinalModal(
          true,
          "Saved!",
          "Your profile has been successfully updated.",
        );

        originalProfileData.profile_updated = 1;
        const headerFullname = document.getElementById("headerFullname");
        if (headerFullname) {
          const newFullName = [
            formData.get("first_name"),
            formData.get("middle_name"),
            formData.get("last_name"),
            formData.get("suffix"),
          ]
            .filter(Boolean)
            .join(" ");
          headerFullname.textContent = newFullName;
        }

        loadProfile();
        toggleEdit(false);
      } else {
        showFinalModal(false, "Error", message);
      }
    } catch (err) {
      Swal.close();
      console.error("Save profile error:", err);
      showFinalModal(
        false,
        "Network Error",
        "Could not save profile due to connection issue.",
      );
    }
  });

  uploadInput?.addEventListener("change", (e) => {
    const file = e.target.files[0];
    if (!file) return;
    if (file.size > MAX_FILE_SIZE) {
      showProfileToast(
        "ph-warning",
        "File Too Large",
        "Image size must be less than 1MB. Please choose a smaller file.",
        "warning",
        5000,
      );
      uploadInput.value = "";
      return;
    }
    const reader = new FileReader();
    reader.onload = () => {
      if (cropImage) cropImage.src = reader.result;
      openModal(cropModal);
      setTimeout(() => {
        if (cropper) cropper.destroy();
        cropper = new Cropper(cropImage, {
          aspectRatio: 1,
          viewMode: 1,
          dragMode: "move",
          background: false,
          autoCropArea: 1,
          responsive: true,
          guides: true,
        });
      }, 100);
    };
    reader.readAsDataURL(file);
  });

  zoomIn?.addEventListener("click", () => cropper && cropper.zoom(0.1));
  zoomOut?.addEventListener("click", () => cropper && cropper.zoom(-0.1));
  resetCrop?.addEventListener("click", () => cropper && cropper.reset());

  cancelCrop?.addEventListener("click", () => {
    closeModal(cropModal);
    if (cropper) cropper.destroy();
    uploadInput.value = "";
  });

  saveCrop?.addEventListener("click", async () => {
    showLoadingModal(
      "Processing Image...",
      "Cropping and uploading profile picture.",
    );

    const canvas = cropper.getCroppedCanvas({ width: 200, height: 200 });
    const circleCanvas = document.createElement("canvas");
    circleCanvas.width = 200;
    circleCanvas.height = 200;
    const circleCtx = circleCanvas.getContext("2d");
    circleCtx.beginPath();
    circleCtx.arc(100, 100, 100, 0, Math.PI * 2);
    circleCtx.closePath();
    circleCtx.clip();
    circleCtx.drawImage(canvas, 0, 0, 200, 200);

    profilePreview.src = circleCanvas.toDataURL("image/png");
    profilePreview.classList.remove("hidden");
    if (profileIcon) profileIcon.style.display = "none";

    circleCanvas.toBlob(async (blob) => {
      croppedBlob = blob;

      showProfileToast(
        "ph-check-circle",
        "Image Cropped",
        "Picture is ready to be saved with the profile changes.",
        "success",
        3000,
      );

      Swal.close();
    }, "image/png");

    closeModal(cropModal);
    cropper.destroy();
  });

  regFormUpload?.addEventListener("change", (e) => {
    const file = e.target.files[0];
    if (!file) return;
    if (file.type !== "application/pdf") {
      showProfileToast(
        "ph-warning",
        "Invalid File Type",
        "Please upload a valid PDF file.",
        "warning",
        5000,
      );
      regFormUpload.value = "";
      return;
    }
    const fileURL = URL.createObjectURL(file);
    viewRegForm.href = fileURL;
    viewRegForm.classList.remove("hidden");
    uploadBtn.classList.add("hidden");
    if (removeRegForm) removeRegForm.classList.remove("hidden");
    showProfileToast(
      "ph-check-circle",
      "Form Ready",
      "Registration form has been attached.",
      "success",
      3000,
    );
  });

  removeRegForm?.addEventListener("click", () => {
    regFormUpload.value = "";
    if (originalProfileData.registration_form && !isEditing) {
      const cleanPath = originalProfileData.registration_form.replace(/^\//, "");
      viewRegForm.href = window.STORAGE_URL + '/' + cleanPath;
      viewRegForm.classList.remove("hidden");
      uploadBtn.classList.add("hidden");
      removeRegForm.classList.add("hidden");
    } else if (isEditing) {
      // Kung nasa edit mode at clinick ang remove, ibalik sa "Upload" state
      viewRegForm.href = "#";
      viewRegForm.classList.add("hidden");
      uploadBtn.classList.remove("hidden");
      removeRegForm.classList.add("hidden");
    } else {
      viewRegForm.classList.add("hidden");
      uploadBtn.classList.remove("hidden");
      removeRegForm.classList.add("hidden");
    }
    showProfileToast(
      "ph-info",
      "Removed",
      "Attached registration form has been removed.",
      "warning",
      2000,
    );
  });

  function openModal(modal) {
    if (modal) {
      modal.classList.remove("hidden");
      document.body.classList.add("overflow-hidden");
    }
  }
  function closeModal(modal) {
    if (modal) {
      modal.classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    }
  }

  isEditing = false;
  loadProfile();
});
