<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class=" bg-gradient-to-br from-orange-50 to-green-50 font-sans flex items-center justify-center min-h-screen">


  <div class="bg-white/95 backdrop-blur-sm border border-orange-200/60 shadow-xl shadow-orange-200/40 rounded-2xl p-8 w-full max-w-md">
    <div class="flex justify-center">
      <img src="<?= BASE_URL ?>/assets/library-icons/apple-touch-icon.png" alt="Library Logo" class="w-32">
    </div>

    <div class="text-center mb-6 mt-3">
      <h1 class="text-2xl font-bold text-gray-900">Reset your password</h1>
      <p class="text-sm text-gray-600 mt-1">We'll send a reset link to your email</p>
    </div>

    <form id="forgotForm" class="space-y-5">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">

      <div class="text-left">
        <label for="identifier" class="block text-sm font-medium text-gray-700 mb-1">Username or Student Number</label>
        <input
          type="text"
          id="identifier"
          name="identifier"
          placeholder="Enter your username or ID number"
          required
          class="mt-1 block w-full rounded-lg border border-orange-300 bg-orange-50 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-orange-400 transition">
      </div>

      <button
        type="submit"
        id="sendBtn"
        class="w-full bg-orange-600 text-white py-2.5 rounded-lg font-medium hover:bg-orange-700 disabled:bg-orange-300 disabled:text-orange-800 active:scale-[.98] transition-transform">
        Continue
      </button>
    </form>

    <!-- ORANGE ALERT BOX -->
    <div id="alertBox" class="hidden mt-5 bg-orange-100 border border-orange-300 text-orange-800 text-sm rounded-lg p-3 space-y-1">
      <p id="message" class="font-medium"></p>
      <p class="text-xs opacity-80">Didn’t receive anything? Check Spam/Promotions folder.</p>
    </div>

    <div class="mt-7 text-center">
      <a href="<?= BASE_URL ?>/login" class="text-sm text-orange-700 hover:underline font-medium">Back to Login</a>
    </div>
  </div>

  <script>
    const form = document.getElementById("forgotForm");
    const alertBox = document.getElementById("alertBox");
    const message = document.getElementById("message");
    const sendBtn = document.getElementById("sendBtn");

    const csrfToken = document.querySelector('input[name="csrf_token"]').value;

    const BASE_URL = "<?= BASE_URL ?>";

    form.addEventListener("submit", (e) => {
      e.preventDefault();

      sendBtn.disabled = true;
      sendBtn.textContent = "Processing...";
      alertBox.classList.add("hidden");

      const formData = new FormData(form);

      fetch(`forgot-password/send-otp`, {
          method: "POST",
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          },
          body: formData,
        })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            alertBox.classList.remove("hidden");
            message.textContent = data.message; // Gagamitin 'yung message galing sa server

            setTimeout(() => {
              window.location.href = `${BASE_URL}/verifyOTP`;
            }, 1500);

          } else {
            alertBox.classList.remove("hidden");
            message.textContent = data.message || "An error occurred. Please try again.";
            sendBtn.disabled = false;
            sendBtn.textContent = "Continue";
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alertBox.classList.remove("hidden");
          message.textContent = "Could not connect to the server. Please check your connection.";
          sendBtn.disabled = false;
          sendBtn.textContent = "Continue";
        });
    });
  </script>


</body>

</html>