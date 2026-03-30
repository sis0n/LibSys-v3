/**
 * Global function to toggle password visibility.
 * Kailangan itong maging global dahil tinatawag ito ng "onclick" attribute sa HTML.
 */
function togglePassword(id, btn) {
  const input = document.getElementById(id);
  if (!input) return; // Guard clause

  const icon = btn.querySelector("i");
  if (!icon) return; // Guard clause

  if (input.type === "password") {
    input.type = "text";
    icon.classList.remove("ph-eye");
    icon.classList.add("ph-eye-slash");
  } else {
    input.type = "password";
    icon.classList.remove("ph-eye-slash");
    icon.classList.add("ph-eye");
  }
}

/**
 * Main script execution after the DOM is loaded.
 */
document.addEventListener("DOMContentLoaded", () => {

  const form = document.getElementById("passwordForm");
  if (!form) {
    console.error("ChangePassword Error: Form '#passwordForm' not found.");
    return; // Stop if form doesn't exist
  }

  const newPassInput = document.getElementById("new_password");
  const confirmPassInput = document.getElementById("confirm_password");
  const errorMsg = document.getElementById("errorMessage");

  // Check if all elements are found
  if (!newPassInput || !confirmPassInput || !errorMsg) {
    console.error("ChangePassword Error: Missing required input fields or error message element.");
    return;
  }

  /**
   * Single submit event listener for the password form.
   * Handles both validation and AJAX submission with SweetAlert.
   */
  form.addEventListener("submit", async function (event) {
    event.preventDefault(); // Always prevent default submission

    // --- 1. Validation Logic (Mula sa luma mong script 1) ---
    const newPass = newPassInput.value;
    const confirmPass = confirmPassInput.value;

    if (newPass !== confirmPass) {
      errorMsg.textContent = "Passwords do not match!"; // Set text just in case
      errorMsg.classList.remove("hidden");
      return; // Stop execution if passwords don't match
    } else {
      errorMsg.classList.add("hidden");
    }

    // --- 2. Fetch & SweetAlert Logic (Mula sa luma mong script 2) ---
    const formData = new FormData(form);

    try {
      const res = await fetch(form.action, {
        method: "POST",
        body: formData,
        headers: {
          // Send header to indicate we expect JSON
          'Accept': 'application/json'
        }
      });

      if (!res.ok) {
        throw new Error(`HTTP error! status: ${res.status}`);
      }

      const data = await res.json();
      let timerInterval;

      const showAlert = (isSuccess) => {
        if (typeof Swal === 'undefined') {
          alert(data.message || (isSuccess ? "Success!" : "Error!"));
          if (isSuccess) form.reset();
          return;
        }

        Swal.fire({
          position: "center",
          showConfirmButton: false,
          backdrop: `
                        rgba(0,0,0,0.3)
                        backdrop-filter: blur(6px)
                    `,
          timer: 2000,
          didOpen: () => {
            const progressBar = Swal.getHtmlContainer().querySelector("#progress-bar");
            // Check if progressBar exists before manipulating
            if (progressBar) {
              let width = 100;
              timerInterval = setInterval(() => {
                width -= 100 / 20; // 100% / (2000ms / 100ms)
                progressBar.style.width = width + "%";
              }, 100);
            }
          },
          willClose: () => {
            clearInterval(timerInterval);
            if (isSuccess) { // Only reset form on success
              form.reset();
            }
          },
          html: isSuccess ? `
                        <div class="w-full max-w-md bg-orange-50 border-2 border-orange-300 rounded-2xl p-6 sm:p-8 shadow-lg text-center">
                            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-orange-100 mx-auto mb-4">
                                <i class="ph ph-user-check text-orange-600 text-3xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-orange-700">Password Changed</h3>
                            <div class="text-base text-orange-700 mt-3 space-y-1">
                                <p>${data.message || "Your password has been updated."}</p>
                            </div>
                            <div class="w-full bg-orange-100 h-2 rounded mt-4 overflow-hidden">
                                <div id="progress-bar" class="bg-orange-500 h-2 w-full transition-all duration-100 ease-linear"></div>
                            </div>
                        </div>
                    ` : `
                        <div class="w-full max-w-md bg-red-50 border-2 border-red-300 rounded-2xl p-6 sm:p-8 shadow-lg text-center">
                            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-red-100 mx-auto mb-4">
                                <i class="ph ph-x-circle text-red-600 text-3xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-red-700">Update Failed</h3>
                            <div class="text-base text-red-700 mt-3 space-y-1">
                                <p>${data.message || "An error occurred."}</p>
                            </div>
                            <div class="w-full bg-red-100 h-2 rounded mt-4 overflow-hidden">
                                <div id="progress-bar" class="bg-red-500 h-2 w-full transition-all duration-100 ease-linear"></div>
                            </div>
                        </div>
                    `,
          customClass: {
            popup: "block !bg-transparent !shadow-none !p-0 !border-0 !m-0 !w-auto !min-w-0 !max-w-none",
          }
        });
      };

      if (data.status === "success") {
        showAlert(true);
      } else {
        showAlert(false);
      }

    } catch (error) {
      console.error("Change Password Fetch Error:", error);
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: "error",
          title: "Oops...",
          text: "Something went wrong! Could not connect or parse response.",
          confirmButtonColor: "#dc3545",
        });
      } else {
        alert("Something went wrong! Could not connect.");
      }
    }
  });
});
