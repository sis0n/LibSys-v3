<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library | 401 Unauthorized</title>
    <link href="<?= BASE_URL ?>/css/output.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2/src/regular/style.css" />
</head>

<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-orange-50 to-green-50 font-sans p-6 overflow-hidden">

    <div class="w-full max-w-md text-center relative z-10">

        <div class="flex justify-center mb-10">
            <img src="<?= BASE_URL ?>/assets/library-icons/apple-touch-icon.png" alt="Library Logo" class="w-24 drop-shadow-sm opacity-90">
        </div>

        <div class="mb-10">
            <h1 class="text-8xl font-black text-gray-900 tracking-tighter leading-none">401</h1>
            <h2 class="text-xl font-bold text-gray-700 mt-4 uppercase tracking-[0.3em]">Unauthorized</h2>
            <div class="w-16 h-1.5 bg-orange-600 mx-auto mt-6 rounded-full"></div>
        </div>

        <p class="text-base text-gray-600 mb-12 leading-relaxed font-medium">
            Authentication is required to access this resource. Please log in to continue.
        </p>

        <div class="flex justify-center mt-6">
            <a href="<?= BASE_URL ?>/login"
                class="inline-flex items-center justify-center gap-4 bg-orange-600 text-white py-4 px-8 rounded-xl shadow-lg shadow-orange-200 hover:bg-orange-700 hover:-translate-y-1 hover:shadow-orange-300 transition-all duration-300 font-semibold text-lg tracking-normal">
                <i class="ph ph-sign-in text-2xl"></i>
                <span class="leading-none">Log In</span>
            </a>
        </div>

        <div class="mt-24 pb-12 text-center">
            <p class="text-[10px] uppercase tracking-[0.3em] text-gray-400 font-black opacity-60">
                &copy; <?= date('Y') ?> UCC Library Management System
            </p>
        </div>
    </div>

</body>

</html>
