<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library | Login</title>
    <!-- Tailwind CSS -->
    <link href="<?= BASE_URL ?>/css/output.css" rel="stylesheet">
    <!-- PHOSPHOR ICONS -->
    <link rel="stylesheet" type="text/css"
        href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2/src/regular/style.css" />
    <!-- SWEETALERT -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-orange-50 to-green-50 font-sans">
    <div class="w-full max-w-md p-6">
        <div class="bg-white shadow-lg rounded-xl p-8 pt-12 relative">
            <a href="<?= BASE_URL ?>/"
                class="absolute top-4 left-4 text-sm font-medium text-gray-600 hover:text-orange-600 transition inline-flex items-center gap-1">
                <i class="ph ph-arrow-left text-lg"></i>
                Back to Library
            </a>

            <div class="flex justify-center mt-2">
                <img src="<?= BASE_URL ?>/assets/library-icons/apple-touch-icon.png" alt="Library Logo" class="w-32">
            </div>

            <h2 class="text-2xl font-semibold text-center text-gray-800">
                Library Online Software
            </h2>
            <p class="text-sm text-center text-gray-600 mb-6">
                Sign in to access your dashboard
            </p>

            <form method="POST" action="<?= BASE_URL ?>/login" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
                <div>
                    <label for="username" class="flex items-center gap-x-1 text-sm font-medium text-gray-700">
                        <i class="ph ph-user"></i>
                        Username
                    </label>
                    <input type="text" id="username" name="username"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"
                        placeholder="Enter your student number" required>
                </div>

                <div>
                    <label for="password" class="flex items-center gap-x-1 block text-sm font-medium text-gray-700">
                        <i class="ph ph-key"></i>
                        Password
                    </label>
                    <div class="relative">
                        <input type="password" id="password" name="password"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-orange-500"
                            placeholder="Enter your password" required>
                        <button type="button" onclick="togglePassword('password', this)"
                            class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center justify-center text-gray-500 hover:text-orange-500 transition-colors duration-200">
                            <i class="ph ph-eye text-lg"></i>
                        </button>
                    </div>
                </div>
                <button type="submit"
                    class="w-full bg-orange-600 text-white py-2 px-4 rounded-lg shadow hover:bg-orange-700 transition">
                    Login
                </button>
            </form>
            <div class="mt-3 text-center space-y-2">
                <a href="<?= BASE_URL ?>/forgotPassword" class="text-sm text-orange-600 hover:underline">
                    Forgot Password?
                </a>
            </div>
        </div>
    </div>

    <script>
    function togglePassword(id, btn) {
        const input = document.getElementById(id);
        const icon = btn.querySelector("i");

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

    document.querySelector("form").addEventListener("submit", async function(e) {
        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);

        try {
            const response = await fetch(form.action, {
                method: "POST",
                body: formData
            });
            const result = await response.json();

            if (result.status === "success") {
                window.location.href = result.redirect;
            } else {
                
                // *** Dito ang Logic para sa Deactivated Account ***
                let alertTitle = "Login Failed";
                let alertMessage = "Invalid username or password. Please try again.";
                let iconClass = "ph ph-x-circle text-red-600 text-3xl";

                // I-check kung deactivated ang account gamit ang 'error_type'
                if (result.error_type === 'deactivated') {
                    alertTitle = "Account Suspended ";
                    alertMessage = "Your account has been suspended by the administrator. Please contact support.";
                    // Maaari ka ring magpalit ng icon kung gusto mo (hal. warning icon)
                    iconClass = "ph ph-warning-circle text-orange-600 text-3xl"; 
                }

                Swal.fire({
                    position: "center",
                    showConfirmButton: false,
                    backdrop: `
                        rgba(0,0,0,0.3)
                        backdrop-filter: blur(6px)
                    `,
                    timer: 3000, // Gawin nating 3 segundo para mabasa ang message
                    didOpen: () => {
                        const progressBar = Swal.getHtmlContainer().querySelector(
                            "#progress-bar");
                        let width = 100;
                        timerInterval = setInterval(() => {
                            width -= 100 / 30; // 3s / 100ms = 30 intervals
                            if (progressBar) {
                                progressBar.style.width = width + "%";
                            }
                        }, 100);
                    },
                    willClose: () => {
                        clearInterval(timerInterval);
                    },
                    html: `
                        <div class="w-[400px] bg-red-50 border-2 border-red-300 rounded-2xl p-8 shadow-lg text-center animate-fade-in">
                            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-red-100 mx-auto mb-4">
                                <i class="${iconClass}"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-red-700">${alertTitle}</h3>
                            <p class="text-base text-red-600 mt-1">${alertMessage}</p>
                            <div class="w-full bg-red-100 h-2 rounded mt-4 overflow-hidden">
                                <div id="progress-bar" class="bg-red-500 h-2 w-full transition-all"></div>
                            </div>
                        </div>
                    `,
                    customClass: {
                        popup: "block !bg-transparent !shadow-none !p-0 !border-0 !m-0 !w-auto !min-w-0 !max-w-none",
                    }
                });
            }
        } catch (error) {
            Swal.fire({
                icon: "error",
                title: "Server Error",
                text: "Something went wrong. Please try again later.",
                confirmButtonColor: "#f97316"
            });
        }
    });
</script>
</body>

</html>