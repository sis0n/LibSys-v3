// --- SweetAlert Helper Functions (from Student Profile) ---

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
    buttonsStyling: false,
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
      // Modal/Popup Design: White BG, Orange Border, Shadow (Matching the screenshot)
      popup:
        "!rounded-xl !shadow-lg !p-6 !bg-white !border-2 !border-orange-400 shadow-[0_0_15px_#ffb34780]",

      // Confirm Button (Orange, Large, Bold)
      confirmButton:
        "!bg-orange-600 !text-white !px-5 !py-2.5 !rounded-lg hover:!bg-orange-700 !mx-2 !font-semibold !text-base",

      // Cancel Button (Gray, Large, Bold)
      cancelButton:
        "!bg-gray-200 !text-gray-800 !px-5 !py-2.5 !rounded-lg hover:!bg-gray-300 !mx-2 !font-semibold !text-base",

      actions: "!mt-4",
    },
  });
  return result.isConfirmed;
}

// ----------------------------------------------------

document.addEventListener("DOMContentLoaded", () => {
  // --- Cropper & Upload Elements (Same IDs as original Staff code) ---
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

  // --- Profile Form Elements (Updated/Modified from Student IDs) ---
  const profileForm = document.getElementById("profileForm");
  const editProfileBtn = document.getElementById("editProfileBtn");
  const cancelProfileBtn = document.getElementById("cancelProfileBtn");
  const formActions = document.getElementById("formActions");
  const profileName = document.getElementById("profileName");
  const profileStaffId = document.getElementById("profileFacultyId"); // Staff ID (from original Staff code)
  const uploadLabel = document.getElementById("uploadLabel");

  // Note: We remove student-specific elements (e.g., regFormUpload, profileStudentId, yearLevel, section, course, profileLockedInfo)

  const allInputs = profileForm.querySelectorAll(
    'input[type="text"], input[type="email"], input[type="tel"], input[type="number"]',
  );
  // Ensure 'facultyId' remains read-only as per original Staff code structure
  const editableInputs = Array.from(allInputs).filter(
    (input) => input.id !== "facultyId",
  );

  const MAX_FILE_SIZE = 1 * 1024 * 1024;
  let originalProfileData = {};
  let isEditing = false;

  // --- Load Staff Profile (Modified endpoint/fields from Student) ---
  async function loadProfile() {
    // 🟠 START LOADING FOR PROFILE
    showLoadingModal(
      "Loading profile data...",
      "Retrieving your latest information.",
    );

    try {
      const res = await fetch("api/staff/myprofile/get"); // Staff Endpoint
      if (!res.ok) {
        const errData = await res.json().catch(() => null);
        throw new Error(
          errData?.message || `Failed to fetch profile. Status: ${res.status}`,
        );
      }

      // Forced delay to ensure loading modal is visible (Optional, for better UX)
      await new Promise((resolve) => setTimeout(resolve, 800));
      if (typeof Swal != "undefined") Swal.close(); // 🟠 CLOSE LOADING (SUCCESS PATH)

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
          "Staff Name";

        profileName.textContent = fullName;
        profileStaffId.textContent = profile.employee_id || "Staff ID"; // Staff ID

        document.getElementById("firstName").value = profile.first_name || "";
        document.getElementById("middleName").value = profile.middle_name || "";
        document.getElementById("lastName").value = profile.last_name || "";
        document.getElementById("suffix").value = profile.suffix || "";
        document.getElementById("position").value = profile.position || ""; // Staff Field
        document.getElementById("email").value = profile.email || "";
        document.getElementById("contact").value = profile.contact || "";
        document.getElementById("facultyId").value = profile.employee_id || ""; // Staff ID Input

        if (data.profile.profile_picture) {
          const cleanPath = data.profile.profile_picture.replace(/^\//, "");
          const finalUrl = window.LARAVEL_URL + cleanPath; // Isang variable para sa consistency

          profilePreview.src = finalUrl;
          profilePreview.classList.remove("hidden");
          profileIcon.classList.add("hidden");

          const headerPic = document.getElementById("headerProfilePic");
          if (headerPic) {
            headerPic.src = finalUrl;
          }
        }

        editProfileBtn.classList.remove("hidden");
        uploadLabel.classList.add("hidden"); // Hide upload label initially in view mode

        // --- Verification Badge Logic ---
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
        // --- End of Logic ---
      } else {
        throw new Error(data.message || "Could not parse profile data.");
      }
    } catch (err) {
      // 🟠 CLOSE LOADING (ERROR PATH)
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

  // --- Toggle Edit Mode (Replaced Student logic with Staff fields) ---
  function toggleEdit(shouldEdit) {
    isEditing = shouldEdit;

    if (shouldEdit) {
      editableInputs.forEach((input) => {
        input.disabled = false;
        input.classList.remove("bg-gray-50", "border-gray-200");
        // Tailwind classes for editing mode
        input.classList.add(
          "bg-white",
          "border-gray-300",
          "focus:border-orange-500",
          "focus:ring-orange-500",
        );
      });
      formActions.classList.remove("hidden");
      editProfileBtn.classList.add("hidden");
      uploadLabel.classList.remove("hidden");
    } else {
      loadProfile(); // Revert changes on cancel

      editableInputs.forEach((input) => {
        input.disabled = true;
        // Tailwind classes for view mode
        input.classList.add("bg-gray-50", "border-gray-200");
        input.classList.remove(
          "bg-white",
          "border-gray-300",
          "focus:border-orange-500",
          "focus:ring-orange-500",
        );
      });

      formActions.classList.add("hidden");
      editProfileBtn.classList.remove("hidden");
      uploadLabel.classList.add("hidden");
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

  // --- Submit Profile Form (Modified endpoint/validation from Student) ---
  profileForm?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const formData = new FormData(profileForm);

    // Validate required fields (Staff specific)
    const requiredFields = [
      "first_name",
      "last_name",
      "position",
      "email",
      "contact",
    ];
    const missingFields = requiredFields.filter(
      (f) => !formData.get(f) || formData.get(f).trim() === "",
    );
    if (missingFields.length) {
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
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
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
    if (
      !hasProfilePic &&
      !croppedBlob &&
      !originalProfileData.profile_picture
    ) {
      showProfileToast(
        "ph-warning",
        "Missing Profile Picture",
        "Profile picture is required.",
        "warning",
      );
      return;
    }

    // --- 🟠 CONFIRM CHANGES MODAL (Using the screenshot design style) ---
    const confirm = await showConfirmationModal(
      "Confirm Changes",
      "Are you sure you want to save your profile changes?",
      "Yes, save it!",
    );

    if (!confirm) return;

    showLoadingModal(
      "Saving profile...",
      "Updating your details. This might take a moment.",
    );

    if (croppedBlob)
      formData.append("profile_image", croppedBlob, "profile.png");

    try {
      const res = await fetch("api/staff/myprofile/update", {
        method: "POST",
        body: formData,
      }); // Staff Endpoint

      if (typeof Swal != "undefined") Swal.close(); // Close loading modal

      const result = await res.json();
      const message = result.message || "Unknown response.";

      if (result.success) {
        showFinalModal(
          true,
          "Saved!",
          "Your profile has been successfully updated.",
        );
        croppedBlob = null;
        uploadInput.value = "";
        // Update header name without full reload
        const newFullName = [
          formData.get("first_name"),
          formData.get("middle_name"),
          formData.get("last_name"),
          formData.get("suffix"),
        ]
          .filter(Boolean)
          .join(" ");
        profileName.textContent = newFullName;

        loadProfile();
        toggleEdit(false);
      } else {
        showFinalModal(false, "Error", message);
      }
    } catch (err) {
      if (typeof Swal != "undefined") Swal.close();
      console.error("Save profile error:", err);
      showFinalModal(
        false,
        "Network Error",
        "Could not save profile due to connection issue.",
      );
    }
  });

  // --- Profile Picture Upload & Crop (Same logic as Student/Original Staff) ---
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
    croppedBlob = null; // Important: Clear the cropped blob if cancelled
  });

  saveCrop?.addEventListener("click", async () => {
    showLoadingModal("Processing Image...", "Cropping profile picture.");

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
      if (typeof Swal != "undefined") Swal.close();
    }, "image/png");

    closeModal(cropModal);
    cropper.destroy();
  });

  // Simple modal functions for cropping modal (from Student code)
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

  loadProfile();
});
