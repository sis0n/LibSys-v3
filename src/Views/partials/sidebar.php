<?php

use App\Core\RoleHelper;

$currentPage = $currentPage ?? '';
$role = $_SESSION['role'] ?? 'guest';
$userPermissions = $_SESSION['user_permissions'] ?? [];
$isSuperAdmin = RoleHelper::isSuperadmin($role);
$isAdmin = RoleHelper::isAdmin($role);
$isLibrarian = RoleHelper::isLibrarian($role);

$normalizedPermissions = array_map(function ($p) {
    return RoleHelper::compareNormalize($p);
}, $userPermissions);

$hasPermission = function ($module) use ($normalizedPermissions, $isSuperAdmin) {
    if ($isSuperAdmin) return true;
    return in_array(RoleHelper::compareNormalize($module), $normalizedPermissions);
};

$baseUrl = \BASE_URL;
?>

<aside id="sidebar" class="fixed lg:sticky lg:top-0 left-0 top-0 h-screen w-64 
        bg-orange-50 border-r border-orange-200 flex flex-col 
        transform -translate-x-full lg:translate-x-0 
        transition-transform duration-300 ease-in-out 
        z-40 overflow-hidden hover:overflow-y-auto">

    <a href="<?= RoleHelper::isStaff($role) ? $baseUrl . '/dashboard' : '#' ?>"
        class="flex items-center gap-4 px-6 py-4 border-b border-orange-200 cursor-pointer">
        <img src="<?= $baseUrl ?>/assets/library-icons/apple-touch-icon.png" alt="Logo" class="h-18">
        <span class="font-semibold text-lg text-orange-700">
            Library Management System
        </span>
    </a>
    
    <div class="flex-1 space-y-1 overflow-hidden hover:overflow-y-auto scroll-smooth">
        <nav class="flex-1 px-4 py-6 space-y-2">

            <?php if (in_array($role, [RoleHelper::STUDENT, RoleHelper::FACULTY, RoleHelper::STAFF])): ?>
                <!-- Common User Navigation -->
                <a href="<?= $baseUrl ?>/dashboard"
                    class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= ($currentPage === 'dashboard') ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                    <i class="ph ph-house text-2xl"></i>
                    <span class="text-base">Dashboard</span>
                </a>

                <a href="<?= $baseUrl ?>/bookCatalog"
                    class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= ($currentPage === 'bookCatalog') ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                    <i class="ph ph-book text-2xl"></i>
                    <span class="text-base">Book Catalog</span>
                </a>

                <!-- Borrowings Dropdown -->
                <div class="sidebar-dropdown" data-pages='["myCart", "qrBorrowingTicket"]'>
                    <button class="sidebar-dropdown-toggle flex items-center justify-between w-full gap-x-3 px-3 py-2 rounded-lg transition <?= (in_array($currentPage, ["myCart", "qrBorrowingTicket"])) ? 'bg-orange-100 text-orange-900' : 'hover:bg-orange-100 text-orange-900' ?>">
                        <span class="flex items-center gap-x-3">
                            <i class="ph ph-handbag text-2xl"></i>
                            <span class="text-base">Borrowings</span>
                        </span>
                        <i class="ph ph-caret-down text-xl dropdown-icon transition-transform"></i>
                    </button>
                    <div class="pl-5 pt-1 space-y-1 hidden">
                        <a href="<?= $baseUrl ?>/myCart" class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= ($currentPage === 'myCart') ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-shopping-cart text-xl"></i>
                            <span class="text-base text-sm">Bookbag</span>
                        </a>
                        <a href="<?= $baseUrl ?>/qrBorrowingTicket" class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= ($currentPage === 'qrBorrowingTicket') ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-qr-code text-xl"></i>
                            <span class="text-base text-sm">QR Borrowing Ticket</span>
                        </a>
                    </div>
                </div>

                <!-- History Dropdown -->
                <div class="sidebar-dropdown" data-pages='["borrowingHistory", "myAttendance"]'>
                    <button class="sidebar-dropdown-toggle flex items-center justify-between w-full gap-x-3 px-3 py-2 rounded-lg transition <?= (in_array($currentPage, ["borrowingHistory", "myAttendance"])) ? 'bg-orange-100 text-orange-900' : 'hover:bg-orange-100 text-orange-900' ?>">
                        <span class="flex items-center gap-x-3">
                            <i class="ph ph-clock-counter-clockwise text-2xl"></i>
                            <span class="text-base">History</span>
                        </span>
                        <i class="ph ph-caret-down text-xl dropdown-icon transition-transform"></i>
                    </button>
                    <div class="pl-5 pt-1 space-y-1 hidden">
                        <a href="<?= $baseUrl ?>/borrowingHistory" class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= ($currentPage === 'borrowingHistory') ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-bookmarks text-xl"></i>
                            <span class="text-base text-sm">Borrowing History</span>
                        </a>
                        <?php if ($role === RoleHelper::STUDENT): ?>
                        <a href="<?= $baseUrl ?>/myAttendance" class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= ($currentPage === 'myAttendance') ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-calendar-check text-xl"></i>
                            <span class="text-base text-sm">Attendance</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Shared Account Settings for all roles -->
                <div class="sidebar-dropdown" data-pages='["myProfile", "changePassword"]'>
                    <button class="sidebar-dropdown-toggle flex items-center justify-between w-full gap-x-3 px-3 py-2 rounded-lg transition <?= (in_array($currentPage, ["myProfile", "changePassword"])) ? 'bg-orange-100 text-orange-900 font-semibold' : 'hover:bg-orange-100 text-orange-900' ?>">
                        <span class="flex items-center gap-x-3">
                            <i class="ph ph-user-gear text-2xl"></i>
                            <span class="text-base">Account Settings</span>
                        </span>
                        <i class="ph ph-caret-down text-xl dropdown-icon transition-transform"></i>
                    </button>
                    <div class="pl-5 pt-1 space-y-1 hidden">
                        <a href="<?= $baseUrl ?>/myProfile" class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= ($currentPage === 'myProfile') ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-user-circle text-xl"></i>
                            <span class="text-base text-sm">Profile</span>
                        </a>
                        <a href="<?= $baseUrl ?>/changePassword" class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= ($currentPage === 'changePassword') ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-key text-xl"></i>
                            <span class="text-base text-sm">Change Password</span>
                        </a>
                    </div>
                </div>

            <?php elseif (RoleHelper::isStaff($role)): ?>
                <!-- Staff (Superadmin, Admin, Librarian) Navigation -->
                <a href="<?= $baseUrl ?>/dashboard"
                    class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= ($currentPage === 'dashboard') ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                    <i class="ph ph-house text-2xl"></i>
                    <span class="text-base">Dashboard</span>
                </a>

                <!-- Management Dropdown -->
                <?php 
                    $mgmtItems = [];
                    if ($hasPermission(RoleHelper::MOD_USER_MANAGEMENT)) $mgmtItems[] = ["url" => "userManagement", "icon" => "ph ph-users", "label" => "User Management"];
                    if ($isSuperAdmin) $mgmtItems[] = ["url" => "campusManagement", "icon" => "ph ph-buildings", "label" => "Campus Management"];
                    if ($hasPermission(RoleHelper::MOD_STUDENT_PROMOTION)) $mgmtItems[] = ["url" => "studentPromotion", "icon" => "ph ph-student", "label" => "Student Promotion"];
                    if ($hasPermission(RoleHelper::MOD_BOOK_MANAGEMENT)) $mgmtItems[] = ["url" => "bookManagement", "icon" => "ph ph-book-open", "label" => "Book Management"];
                    if ($hasPermission(RoleHelper::MOD_EQUIPMENT_MANAGEMENT)) $mgmtItems[] = ["url" => "equipmentManagement", "icon" => "ph ph-desktop", "label" => "Equipment Management"];
                    
                    if (!empty($mgmtItems)):
                        $mgmtPages = array_column($mgmtItems, 'url');
                ?>
                <div class="sidebar-dropdown" data-pages='<?= json_encode($mgmtPages) ?>'>
                    <button class="sidebar-dropdown-toggle flex items-center justify-between w-full gap-x-3 px-3 py-2 rounded-lg transition <?= (in_array($currentPage, $mgmtPages)) ? 'bg-orange-100 text-orange-900 font-semibold' : 'hover:bg-orange-100 text-orange-900' ?>">
                        <span class="flex items-center gap-x-3">
                            <i class="ph ph-folders text-2xl"></i>
                            <span class="text-base">Management</span>
                        </span>
                        <i class="ph ph-caret-down text-xl dropdown-icon transition-transform"></i>
                    </button>
                    <div class="pl-5 pt-1 space-y-1 hidden ml-4">
                        <?php foreach ($mgmtItems as $item): ?>
                        <a href="<?= $baseUrl ?>/<?= $item['url'] ?>" class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === $item['url'] ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="<?= $item['icon'] ?> text-xl"></i>
                            <span class="text-base text-sm"><?= $item['label'] ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- QR & Transactions Dropdown -->
                <?php 
                    $transItems = [];
                    if ($hasPermission(RoleHelper::MOD_QR_SCANNER)) $transItems[] = ["url" => "qrScanner", "icon" => "ph ph-qr-code", "label" => "QR Scanner"];
                    if ($hasPermission(RoleHelper::MOD_RETURNING)) $transItems[] = ["url" => "returning", "icon" => "ph ph-arrow-counter-clockwise", "label" => "Returning"];
                    if ($hasPermission(RoleHelper::MOD_BORROWING_FORM)) $transItems[] = ["url" => "borrowingForm", "icon" => "ph ph-receipt", "label" => "Borrowing Form"];
                    
                    if (!empty($transItems)):
                        $transPages = array_column($transItems, 'url');
                ?>
                <div class="sidebar-dropdown" data-pages='<?= json_encode($transPages) ?>'>
                    <button class="sidebar-dropdown-toggle flex items-center justify-between w-full gap-x-3 px-3 py-2 rounded-lg transition <?= (in_array($currentPage, $transPages)) ? 'bg-orange-100 text-orange-900 font-semibold' : 'hover:bg-orange-100 text-orange-900' ?>">
                        <span class="flex items-center gap-x-3">
                            <i class="ph ph-swap text-2xl"></i>
                            <span class="text-base">QR & Returning</span>
                        </span>
                        <i class="ph ph-caret-down text-xl dropdown-icon transition-transform"></i>
                    </button>
                    <div class="pl-5 pt-1 space-y-1 hidden ml-4">
                        <?php foreach ($transItems as $item): ?>
                        <a href="<?= $baseUrl ?>/<?= $item['url'] ?>" class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === $item['url'] ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="<?= $item['icon'] ?> text-xl"></i>
                            <span class="text-base text-sm"><?= $item['label'] ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Activity & Logs Dropdown -->
                <?php 
                    $logItems = [];
                    if ($hasPermission(RoleHelper::MOD_ATTENDANCE_LOGS)) $logItems[] = ["url" => "attendanceLogs", "icon" => "ph ph-calendar-check", "label" => "Attendance Logs"];
                    if ($hasPermission(RoleHelper::MOD_OVERDUE_TRACKING)) $logItems[] = ["url" => "overdue", "icon" => "ph ph-warning", "label" => "Overdue Tracking"];
                    if ($hasPermission(RoleHelper::MOD_REPORTS)) $logItems[] = ["url" => "reports", "icon" => "ph ph-chart-bar", "label" => "Reports"];
                    if ($hasPermission(RoleHelper::MOD_TRANSACTION_HISTORY)) $logItems[] = ["url" => "transactionHistory", "icon" => "ph ph-arrows-left-right", "label" => "Transaction History"];
                    if ($isSuperAdmin) $logItems[] = ["url" => "auditLogs", "icon" => "ph ph-shield-check", "label" => "Audit Trail"];
                    
                    if (!empty($logItems)):
                        $logPages = array_column($logItems, 'url');
                ?>
                <div class="sidebar-dropdown" data-pages='<?= json_encode($logPages) ?>'>
                    <button class="sidebar-dropdown-toggle flex items-center justify-between w-full gap-x-3 px-3 py-2 rounded-lg transition <?= (in_array($currentPage, $logPages)) ? 'bg-orange-100 text-orange-900 font-semibold' : 'hover:bg-orange-100 text-orange-900' ?>">
                        <span class="flex items-center gap-x-3">
                            <i class="ph ph-activity text-2xl"></i>
                            <span class="text-base">Activity & Logs</span>
                        </span>
                        <i class="ph ph-caret-down text-xl dropdown-icon transition-transform"></i>
                    </button>
                    <div class="pl-5 pt-1 space-y-1 hidden ml-4">
                        <?php foreach ($logItems as $item): ?>
                        <a href="<?= $baseUrl ?>/<?= $item['url'] ?>" class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === $item['url'] ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="<?= $item['icon'] ?> text-xl"></i>
                            <span class="text-base text-sm"><?= $item['label'] ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Backup & Policies -->
                <?php 
                    $backupItems = [];
                    if ($isSuperAdmin) $backupItems[] = ["url" => "backup", "icon" => "ph ph-floppy-disk", "label" => "Backup"];
                    if ($hasPermission(RoleHelper::MOD_RESTORE_USER)) $backupItems[] = ["url" => "restoreUser", "icon" => "ph ph-user-gear", "label" => "Restore User"];
                    
                    if (!empty($backupItems)):
                        $backupPages = array_column($backupItems, 'url');
                ?>
                <div class="sidebar-dropdown" data-pages='<?= json_encode($backupPages) ?>'>
                    <button class="sidebar-dropdown-toggle flex items-center justify-between w-full gap-x-3 px-3 py-2 rounded-lg transition <?= (in_array($currentPage, $backupPages)) ? 'bg-orange-100 text-orange-900 font-semibold' : 'hover:bg-orange-100 text-orange-900' ?>">
                        <span class="flex items-center gap-x-3">
                            <i class="ph ph-database text-2xl"></i>
                            <span class="text-base">Backup & Restore</span>
                        </span>
                        <i class="ph ph-caret-down text-xl dropdown-icon transition-transform"></i>
                    </button>
                    <div class="pl-5 pt-1 space-y-1 hidden ml-4">
                        <?php foreach ($backupItems as $item): ?>
                        <a href="<?= $baseUrl ?>/<?= $item['url'] ?>" class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === $item['url'] ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="<?= $item['icon'] ?> text-xl"></i>
                            <span class="text-base text-sm"><?= $item['label'] ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($isSuperAdmin): ?>
                <a href="<?= $baseUrl ?>/libraryPolicies"
                    class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'libraryPolicies' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                    <i class="ph ph-scroll text-2xl"></i>
                    <span>Library Policies</span>
                </a>
                <?php endif; ?>

                <!-- Account Settings -->
                <div class="sidebar-dropdown" data-pages='["myProfile", "changePassword"]'>
                    <button class="sidebar-dropdown-toggle flex items-center justify-between w-full gap-x-3 px-3 py-2 rounded-lg transition <?= (in_array($currentPage, ["myProfile", "changePassword"])) ? 'bg-orange-100 text-orange-900 font-semibold' : 'hover:bg-orange-100 text-orange-900' ?>">
                        <span class="flex items-center gap-x-3">
                            <i class="ph ph-user-gear text-2xl"></i>
                            <span class="text-base">Account Settings</span>
                        </span>
                        <i class="ph ph-caret-down text-xl dropdown-icon transition-transform"></i>
                    </button>
                    <div class="pl-5 pt-1 space-y-1 hidden">
                        <a href="<?= $baseUrl ?>/myProfile" class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= ($currentPage === 'myProfile') ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-user-circle text-xl"></i>
                            <span class="text-base text-sm">Profile</span>
                        </a>
                        <a href="<?= $baseUrl ?>/changePassword" class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= ($currentPage === 'changePassword') ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-key text-xl"></i>
                            <span class="text-base text-sm">Change Password</span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

        </nav>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = '<?= $currentPage ?>';
            const dropdowns = document.querySelectorAll('.sidebar-dropdown');

            dropdowns.forEach(dropdown => {
                const toggle = dropdown.querySelector('.sidebar-dropdown-toggle');
                if (!toggle) return;

                const menu = toggle.nextElementSibling;
                if (!menu) return;

                const icon = toggle.querySelector('.dropdown-icon');
                const pages = JSON.parse(dropdown.dataset.pages || '[]');

                if (pages.includes(currentPage)) {
                    menu.style.display = 'block';
                    if (icon) icon.classList.add('rotate-180');
                    toggle.classList.add('text-orange-900', 'font-semibold');
                }

                toggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    const isVisible = menu.style.display === 'block';
                    menu.style.display = isVisible ? 'none' : 'block';
                    if (icon) icon.classList.toggle('rotate-180', !isVisible);
                });
            });
        });
    </script>
</aside>