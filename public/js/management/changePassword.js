/**
 * Global function to toggle password visibility.
 */
function togglePassword(id, btn) {
    const input = document.getElementById(id);
    if (!input) return;
    const icon = btn.querySelector("i");
    if (!icon) return;

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

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("passwordForm");
    if (!form) return;

    const newPassInput = document.getElementById("new_password");
    const confirmPassInput = document.getElementById("confirm_password");
    const errorMsg = document.getElementById("errorMessage");

    form.addEventListener("submit", async function (event) {
        event.preventDefault();

        if (newPassInput.value !== confirmPassInput.value) {
            errorMsg.textContent = "Passwords do not match!";
            errorMsg.classList.remove("hidden");
            return;
        } else {
            errorMsg.classList.add("hidden");
        }

        const formData = new FormData(form);

        try {
            const res = await fetch(form.action, {
                method: "POST",
                body: formData,
                headers: { 'Accept': 'application/json' }
            });

            const data = await res.json();
            const isSuccess = data.status === "success" || data.success === true;
            
            if (typeof Swal === 'undefined') {
                alert(data.message || (isSuccess ? "Success!" : "Error!"));
                if (isSuccess) form.reset();
                return;
            }

            let timerInterval;
            Swal.fire({
                position: "center",
                showConfirmButton: false,
                backdrop: `rgba(0,0,0,0.3) backdrop-filter: blur(6px)`,
                timer: 2000,
                didOpen: () => {
                    const progressBar = Swal.getHtmlContainer().querySelector("#progress-bar");
                    if (progressBar) {
                        let width = 100;
                        timerInterval = setInterval(() => {
                            width -= 5;
                            progressBar.style.width = width + "%";
                        }, 100);
                    }
                },
                willClose: () => {
                    clearInterval(timerInterval);
                    if (isSuccess) form.reset();
                },
                html: isSuccess ? `
                    <div class="w-full max-w-md bg-orange-50 border-2 border-orange-300 rounded-2xl p-6 sm:p-8 shadow-lg text-center">
                        <div class="flex items-center justify-center w-16 h-16 rounded-full bg-orange-100 mx-auto mb-4">
                            <i class="ph ph-user-check text-orange-600 text-3xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-orange-700">Password Changed</h3>
                        <p class="text-orange-700 mt-3">${data.message || "Your password has been updated."}</p>
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
                        <p class="text-red-700 mt-3">${data.message || "An error occurred."}</p>
                        <div class="w-full bg-red-100 h-2 rounded mt-4 overflow-hidden">
                            <div id="progress-bar" class="bg-red-500 h-2 w-full transition-all duration-100 ease-linear"></div>
                        </div>
                    </div>
                `,
                customClass: { popup: "block !bg-transparent !shadow-none !p-0 !border-0 !m-0 !w-auto !min-w-0 !max-w-none" }
            });
        } catch (error) {
            console.error(error);
        }
    });
});
