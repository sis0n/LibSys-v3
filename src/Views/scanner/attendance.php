<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library | Scanner</title>
    <!-- Tailwind CSS -->
    <link href="<?= BASE_URL ?>/css/output.css" rel="stylesheet">
    <!-- PHOSPHOR ICONS -->
    <link rel="stylesheet" type="text/css"
        href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2/src/regular/style.css" />
    <!-- SWEETALERT2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-orange-50 font-sans flex flex-col min-h-screen">

    <!-- Header -->
    <header
        class="sticky top-0 z-10 bg-white border-b border-orange-200 px-4 sm:px-6 flex flex-wrap justify-between items-center">
        <div class="flex items-center gap-3 sm:gap-4">
            <img src="<?= BASE_URL ?>/assets/library-icons/apple-touch-icon.png" alt="Logo" class="h-12">
            <span class="font-semibold text-lg sm:text-xl text-orange-700 whitespace-nowrap">
                Library Online Software
            </span>
        </div>

        <div class="mt-2 sm:mt-0 flex items-center gap-4">
            <!-- Logout -->
            <form method="POST" action="<?= BASE_URL ?>/logout">
                <button type="submit" class="p-2 rounded hover:bg-gray-100">
                    <i class="ph ph-sign-out"></i>
                </button>
            </form>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex flex-col justify-center items-center px-2 sm:px-2 py-4 sm:py-4 space-y-6 overflow-auto">
        <div class="w-full max-w-3xl mx-auto space-y-6">
            <!-- QR Code Scanner Form -->
            <form action="<?= BASE_URL ?>/scanner/scan" method="POST"
                class="relative bg-white shadow rounded-lg border border-orange-200 p-4 sm:p-6 text-center">
                
            <div class="border border-orange-200 bg-orange-50 rounded-lg p-4 mb-4">
                <div id="DateTime" class="flex flex-col sm:flex-row items-center justify-center sm:gap-4 text-orange-700">
                    <div class="flex items-center gap-2">
                        <i class="ph ph-calendar text-2xl sm:text-3xl"></i>
                        <p id="date" class="text-base sm:text-lg font-semibold"></p>
                    </div>
                    <div class="hidden sm:block h-8 border-l-2 border-orange-200"></div>
                    <div class="flex items-center gap-2 mt-2 sm:mt-0">
                        <i class="ph ph-clock text-2xl sm:text-3xl"></i>
                        <p id="time" class="text-xl sm:text-2xl font-bold"></p>
                    </div>
                </div>
            </div>

                <h2 class="text-xl sm:text-2xl font-semibold text-orange-700 mb-2">
                    Attendance Scanner
                </h2>
                <p class="text-sm sm:text-md text-gray-600 mb-6">
                    Students can scan their QR in student ID, for automated attendance
                </p>

                <!-- QR Box -->
                <div id="qrBox"
                    class="w-32 h-32 sm:w-40 sm:h-40 mx-auto border-2 border-dashed border-orange-300 rounded-lg flex items-center justify-center bg-orange-50 cursor-pointer">
                    <i class="ph ph-qr-code text-[var(--color-primary)] text-7xl sm:text-9xl"></i>
                </div>

                <!-- Hidden QR input -->
                <input type="text" id="qrCodeValue" name="qrCodeValue" class="absolute opacity-0 pointer-events-none"
                    tabindex="-1" autocomplete="off">
            </form>

            <!-- Manual Attendance Entry -->
            <section class="bg-white shadow rounded-lg border border-orange-200 p-4 sm:p-6">
                <h2 class="text-lg sm:text-xl font-semibold text-orange-700">
                    Manual Attendance Entry
                </h2>
                <p class="text-sm text-gray-600 mb-6">
                    Manually record student attendance when you don't have ID or QR scanning
                </p>

                <form action="<?= BASE_URL ?>/scanner/manual" method="POST"
                    class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label for="studentNumber" class="block text-md font-medium text-gray-700 mb-1">
                            Student Number
                        </label>
                        <input type="text" name="studentNumber" id="studentNumber" placeholder="Enter student number"
                            class="text-md text-gray-700 w-full px-3 py-2 border border-orange-200 rounded-md 
                                focus:ring-2 focus:ring-orange-400 focus:outline-none bg-orange-50" required>
                    </div>

                    <div class="md:col-span-2 flex justify-center mt-4">
                        <button type="submit"
                            class="px-4 sm:px-5 py-2 bg-orange-500 text-white text-sm sm:text-base font-medium rounded-md hover:bg-orange-600 transition">
                            Record Attendance
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="mt-auto p-4 sm:p-6 border-t border-green-200 bg-green-50 shadow-inner">
        <div class="max-w-4xl mx-auto px-9">
            <h3 class="font-semibold mb-3 text-lg sm:mb-4 flex items-center gap-2 ml-6">
                <i class="ph ph-info text-green-600"></i>
                Instructions
            </h3>

            <div class="px-9 grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 text-sm">
                <!-- QR Code Scanning -->
                <div>
                    <h4 class="font-medium flex items-center gap-2 text-orange-600">
                        <i class="ph ph-qr-code"></i> QR Code Scanning:
                    </h4>
                    <ul class="list-disc list-inside mt-2 space-y-1 text-gray-700">
                        <li>Students should use the "Scan QR Code" button</li>
                        <li>System will automatically record attendance</li>
                        <li>Fast and efficient for regular use</li>
                    </ul>
                </div>

                <!-- Manual Entry -->
                <div class="ml-10">
                    <h4 class="font-medium flex items-center gap-2 text-green-600">
                        <i class="ph ph-pencil-simple"></i> Manual Entry:
                    </h4>
                    <ul class="list-disc list-inside mt-2 space-y-1 text-gray-700">
                        <li>Use when QR scanning is unavailable</li>
                        <li>Enter student number</li>
                        <li>Date and time filled automatically</li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script>
    // === DATE & TIME ===
    function updateDateTime() {
        const dateEl = document.getElementById('date');
        const timeEl = document.getElementById('time');
        if (!dateEl || !timeEl) return;

        const now = new Date();

        // Format date
        const dateFormatOptions = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        };
        dateEl.textContent = now.toLocaleDateString('en-US', dateFormatOptions);

        // Format time
        const timeFormatOptions = {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        };
        timeEl.textContent = now.toLocaleTimeString('en-US', timeFormatOptions);
    }

    // Update the date and time every second
    setInterval(updateDateTime, 1000);

    // Initial call to display immediately
    updateDateTime();


    // === QR SCANNER ===
    const qrInput = document.getElementById("qrCodeValue");
    const qrBox = document.getElementById("qrBox");
    const qrForm = qrInput.form;
    const manualForm = document.querySelector("form[action='<?= BASE_URL ?>/scanner/manual']");

    // Kapag na-click QR box, mag-focus sa QR input
    qrBox.addEventListener("click", () => {
        qrInput.focus();
    });

    // Auto-focus QR input kapag page load
    window.addEventListener("load", () => {
        qrInput.focus();
    });

    // Kapag nag-click sa manual input, i-unfocus QR input
    const manualInputs = document.querySelectorAll("#studentNumber");
    manualInputs.forEach(input => {
        input.addEventListener("focus", () => qrInput.blur());
    });

    // === QR FORM SUBMIT (QR Scan) ===
    if (qrForm) {
        qrForm.addEventListener("submit", async (e) => {
            e.preventDefault();

            const formData = new FormData(qrForm);
            try {
                // FIXED: Use relative path /scanner/scan
                const res = await fetch("api/scanner/scan", {
                    method: "POST",
                    body: formData
                });

                const data = await res.json();
                let timerInterval;

                const showAlert = (isSuccess) => {
                    Swal.fire({
                        position: "center",
                        showConfirmButton: false,
                        backdrop: `
                        rgba(0,0,0,0.3)
                        backdrop-filter: blur(6px)
                        `,
                        timer: 2000,
                        didOpen: () => {
                            const progressBar = Swal.getHtmlContainer().querySelector(
                                "#progress-bar");
                            let width = 100;
                            timerInterval = setInterval(() => {
                                width -= 100 / 20;
                                if (progressBar) {
                                    progressBar.style.width = width + "%";
                                }
                            }, 100);
                        },
                        willClose: () => {
                            clearInterval(timerInterval);
                            qrForm.reset();
                            qrInput.value = "";
                            qrInput.focus();
                        },
                        html: isSuccess ? `
                        <div class="w-[450px] bg-orange-50 border-2 border-orange-300 rounded-2xl p-8 shadow-lg text-center">
                            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-orange-100 mx-auto mb-4">
                                <i class="ph ph-user-check text-orange-600 text-3xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-orange-700">Attendance Recorded</h3>
                            <div class="text-base text-orange-700 mt-3 space-y-1">
                                <p><strong>Name:</strong> ${data.full_name}</p>
                                <p><strong>Student Number:</strong> ${data.student_number}</p>
                                <p><strong>Time:</strong> ${data.time}</p>
                            </div>
                            <div class="w-full bg-orange-100 h-2 rounded mt-4 overflow-hidden">
                                <div id="progress-bar" class="bg-orange-500 h-2 w-full transition-all"></div>
                            </div>
                        </div>
                        ` : `
                        <div class="w-[450px] bg-red-50 border-2 border-red-300 rounded-2xl p-8 shadow-lg text-center">
                            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-red-100 mx-auto mb-4">
                                <i class="ph ph-x-circle text-red-600 text-3xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-red-700">Attendance Failed</h3>
                            <p class="text-base text-red-600 mt-1">${data.message || 'Invalid QR. Please try again.'}</p>
                            <div class="w-full bg-red-100 h-2 rounded mt-4 overflow-hidden">
                                <div id="progress-bar" class="bg-red-500 h-2 w-full transition-all"></div>
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
            } catch (err) {
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: "Something went wrong while scanning QR.",
                });

                qrForm.reset();
                qrInput.value = "";
                qrInput.focus();
            }
        });
    }


    // === MANUAL FORM ===
    if (manualForm) {
        manualForm.addEventListener("submit", async (e) => {
            e.preventDefault();

            const formData = new FormData(manualForm);
            try {
                // FIXED: Use relative path /api/scanner/manual
                const res = await fetch("api/scanner/manual", {
                    method: "POST",
                    body: formData
                });

                const data = await res.json();
                let timerInterval;

                const showAlert = (isSuccess) => {
                    Swal.fire({
                        position: "center",
                        showConfirmButton: false,
                        backdrop: `
                        rgba(0,0,0,0.3)
                        backdrop-filter: blur(6px)
                        `,
                        timer: 2000,
                        didOpen: () => {
                            const progressBar = Swal.getHtmlContainer().querySelector(
                                "#progress-bar");
                            let width = 100;
                            timerInterval = setInterval(() => {
                                width -= 100 / 20;
                                if (progressBar) {
                                    progressBar.style.width = width + "%";
                                }
                            }, 100);
                        },
                        willClose: () => {
                            clearInterval(timerInterval);
                            manualForm.reset();
                        },
                        html: isSuccess ? `
                        <div class="w-[450px] bg-orange-50 border-2 border-orange-300 rounded-2xl p-8 shadow-lg text-center">
                            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-orange-100 mx-auto mb-4">
                                <i class="ph ph-user-check text-orange-600 text-3xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-orange-700">Attendance Recorded</h3>
                            <div class="text-base text-orange-700 mt-3 space-y-1">
                                <p><strong>Name:</strong> ${data.full_name}</p>
                                <p><strong>Student Number:</strong> ${data.student_number}</p>
                                <p><strong>Time:</strong> ${data.time}</p>
                            </div>
                            <div class="w-full bg-orange-100 h-2 rounded mt-4 overflow-hidden">
                                <div id="progress-bar" class="bg-orange-500 h-2 w-full transition-all"></div>
                            </div>
                        </div>
                        ` : `
                        <div class="w-[450px] bg-red-50 border-2 border-red-300 rounded-2xl p-8 shadow-lg text-center">
                            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-red-100 mx-auto mb-4">
                                <i class="ph ph-x-circle text-red-600 text-3xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-red-700">Attendance Failed</h3>
                            <p class="text-base text-red-600 mt-1">${data.message || 'Invalid Student Number. Please try again.'}</p>
                            <div class="w-full bg-red-100 h-2 rounded mt-4 overflow-hidden">
                                <div id="progress-bar" class="bg-red-500 h-2 w-full transition-all"></div>
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
            } catch (err) {
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: "Something went wrong while submitting manual attendance.",
                });
                manualForm.reset();
            }
        });
    }
    </script>


</body>

</html>