<?php
$userData = $_SESSION['user_data'] ?? [];

$role = $userData['role'] ?? 'guest';
$fullname = $userData['fullname'] ?? 'Guest User';
$username = $userData['username'] ?? '0000';
$profilePic = $userData['profile_picture'] ?? null;

switch ($role) {
    case 'admin':
        $roleTitle = 'Admin Access Module';
        break;
    case 'librarian':
        $roleTitle = 'Librarian Access Module';
        break;
    case 'student':
        $roleTitle = 'Student Access Module';
        break;
    case 'superadmin':
        $roleTitle = 'Super Admin Access Module';
        break;
    case 'staff':
        $roleTitle = 'Staff Access Module';
        break;
    case 'faculty':
        $roleTitle = 'Faculty Access Module';
        break;
    default:
        $roleTitle = 'Guest Access';
        break;
}
?>
<header class="sticky top-0 z-10 bg-white border-b border-orange-200 px-6 py-2 flex justify-between items-center">
    <div class="flex items-center gap-3">
        <button id="hamburgerBtn"
            class="lg:hidden flex items-center justify-center text-orange-700 hover:bg-orange-50 rounded-md h-9 w-9">
            <i class="ph ph-list text-2xl"></i>
        </button>

        <h1 class="text-base sm:text-lg font-semibold text-gray-800 leading-none">
            <?= $roleTitle ?>
        </h1>
    </div>

    <div class="flex items-center gap-4">
        <div class="flex items-center gap-3">

            <div class="w-9 h-9 rounded-full overflow-hidden flex-none border border-orange-300"> <!-- Outer div for shape -->
                <div id="headerAvatarContainer" class="w-full h-full bg-orange-100 flex items-center justify-center text-orange-600 text-lg font-semibold">
                    <?php if (!empty($profilePic)):
                        $cleanPath = ltrim($profilePic, '/');
                    ?>
                        <img id="headerProfilePic"
                            src="<?php echo STORAGE_URL . '/' . $cleanPath; ?>"
                            alt="Profile"
                            class="w-full h-full object-cover">
                    <?php else: ?>
                        <i id="headerProfileIcon" class="ph ph-user"></i>
                    <?php endif; ?>
                </div>
            </div>

            <div class="leading-tight">
                <p id="headerFullname" class="text-xs sm:text-sm font-medium text-gray-700"><?= htmlspecialchars($fullname) ?></p>
                <p id="headerUsername" class="text-[10px] sm:text-xs text-gray-500"><?= htmlspecialchars($username) ?></p>
            </div>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/logout" id="logoutForm">
            <button type="submit" class="p-2 rounded hover:bg-gray-100">
                <i class="ph ph-sign-out"></i>
            </button>
        </form>
    </div>
</header>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const sidebar = document.getElementById("sidebar");
        const btn = document.getElementById("hamburgerBtn");
        const logoutForm = document.getElementById("logoutForm");

        if (logoutForm) {
            logoutForm.addEventListener('submit', (event) => {
                event.preventDefault();
                try {
                    sessionStorage.removeItem('bookCatalogPage');
                    sessionStorage.removeItem('bookManagementPage');
                    sessionStorage.removeItem('bookManagementPage');
                } catch (e) {
                    console.error("Could not remove item from sessionStorage during logout:", e);
                }
                logoutForm.submit();
            });
        } else {
            console.warn("Logout form with id='logoutForm' not found.");
        }

        if (btn && sidebar) {
            function toggleSidebar(forceClose = false) {
                const isHidden = sidebar.classList.contains("-translate-x-full");
                const body = document.body;

                if (forceClose) {
                    sidebar.classList.add("-translate-x-full");
                    sidebar.classList.remove("translate-x-0");
                    body.classList.remove("overflow-hidden");
                } else {
                    sidebar.classList.toggle("-translate-x-full", !isHidden);
                    sidebar.classList.toggle("translate-x-0", isHidden);
                    body.classList.toggle("overflow-hidden", isHidden);
                }
            }

            btn.addEventListener("click", (e) => {
                e.stopPropagation();
                toggleSidebar();
            });

            document.addEventListener("click", (e) => {
                const isMobile = window.innerWidth < 1024;
                const clickedInsideSidebar = sidebar.contains(e.target);
                const clickedButton = btn.contains(e.target);

                if (isMobile && !clickedInsideSidebar && !clickedButton) {
                    toggleSidebar(true);
                }
            });

            window.addEventListener("resize", () => {
                if (window.innerWidth >= 1024) {
                    sidebar.classList.remove("-translate-x-full");
                    sidebar.classList.add("translate-x-0");
                    document.body.classList.remove("overflow-hidden");
                }
            });
        }
    });
</script>