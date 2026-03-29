<?php

namespace App\Views\partials;

$currentPage = $currentPage ?? '';
$role = $_SESSION['role'] ?? 'guest';

$userPermissions = $_SESSION['user_permissions'] ?? [];
$isSuperAdmin = $role === 'superadmin';

$normalizedPermissions = array_map(function ($p) {
    return trim(strtolower($p));
}, $userPermissions);

$hasPermission = function ($code) use ($normalizedPermissions, $isSuperAdmin) {
    if ($isSuperAdmin) {
        return true;
    }
    return in_array(trim(strtolower($code)), $normalizedPermissions);
};

$baseUrl = BASE_URL;
?>

<aside id="sidebar" class="fixed lg:sticky lg:top-0 left-0 top-0 h-screen w-64 
        bg-orange-50 border-r border-orange-200 flex flex-col 
        transform -translate-x-full lg:translate-x-0 
        transition-transform duration-300 ease-in-out 
        z-40 overflow-hidden hover:overflow-y-auto">

    <a href="<?= $baseUrl ?>/dashboard"
        class="flex items-center gap-4 px-6 py-4 border-b border-orange-200 cursor-pointer">
        <img src="<?= $baseUrl ?>/assets/library-icons/apple-touch-icon.png" alt="Logo" class="h-18">
        <span class="font-semibold text-lg text-orange-700">
            Library Management System
        </span>
    </a>
    <div class="flex-1 space-y-1 overflow-hidden hover:overflow-y-auto scroll-smooth ...">

        <nav class="flex-1 px-4 py-6 space-y-2">

            <?php if ($role === 'student'): ?>

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

                <div class="sidebar-dropdown" data-pages='["myCart", "qrBorrowingTicket"]'>
                    <button
                        class="sidebar-dropdown-toggle flex items-center justify-between w-full gap-x-3 px-3 py-2 rounded-lg transition <?= (in_array($currentPage, ["myCart", "qrBorrowingTicket"])) ? 'bg-orange-100 text-orange-900' : 'hover:bg-orange-100 text-orange-900' ?>">
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

                <div class="sidebar-dropdown" data-pages='["borrowingHistory", "myAttendance"]'>
                    <button
                        class="sidebar-dropdown-toggle flex items-center justify-between w-full gap-x-3 px-3 py-2 rounded-lg transition <?= (in_array($currentPage, ["borrowingHistory", "myAttendance"])) ? 'bg-orange-100 text-orange-900' : 'hover:bg-orange-100 text-orange-900' ?>">
                        <span class="flex items-center gap-x-3">
                            <i class="ph ph-clock-counter-clockwise text-2xl"></i>
                            <span class="text-base">History</span>
                        </span>
                        <i class="ph ph-caret-down text-xl dropdown-icon transition-transform"></i>
                    </button>
                    <div class="pl-5 pt-1 space-y-1 hidden">
                        <a href="<?= $baseUrl ?>/borrowingHistory"
                            class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= ($currentPage === 'borrowingHistory') ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-bookmarks text-xl"></i>
                            <span class="text-base text-sm">Borrowing History</span>
                        </a>

                        <a href="<?= $baseUrl ?>/myAttendance"
                            class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= ($currentPage === 'myAttendance') ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-calendar-check text-xl"></i>
                            <span class="text-base text-sm">Attendance</span>
                        </a>
                    </div>
                </div>

                <div class="sidebar-dropdown" data-pages='["myProfile", "changePassword"]'>
                    <button
                        class="sidebar-dropdown-toggle flex items-center justify-between w-full gap-x-3 px-3 py-2 rounded-lg transition <?= (in_array($currentPage, ["myProfile", "changePassword"])) ? 'bg-orange-100 text-orange-900' : 'hover:bg-orange-100 text-orange-900' ?>">
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


            <?php elseif ($role === 'faculty'): ?>

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

                <div class="sidebar-dropdown" data-pages='["borrowingHistory"]'>
                    <button class="sidebar-dropdown-toggle flex items-center justify-between w-full gap-x-3 px-3 py-2 rounded-lg transition <?= (in_array($currentPage, ["borrowingHistory"])) ? 'bg-orange-100 text-orange-900' : 'hover:bg-orange-100 text-orange-900' ?>">
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
                    </div>
                </div>

                <div class="sidebar-dropdown" data-pages='["myProfile", "changePassword"]'>
                    <button class="sidebar-dropdown-toggle flex items-center justify-between w-full gap-x-3 px-3 py-2 rounded-lg transition <?= (in_array($currentPage, ["myProfile", "changePassword"])) ? 'bg-orange-100 text-orange-900' : 'hover:bg-orange-100 text-orange-900' ?>">
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

            <?php elseif ($role === 'staff'): ?>

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
                        <a href="<?= BASE_URL ?>/qrBorrowingTicket" class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= ($currentPage === 'qrBorrowingTicket') ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-qr-code text-xl"></i>
                            <span class="text-base text-sm">QR Borrowing Ticket</span>
                        </a>
                    </div>
                </div>

                <div class="sidebar-dropdown" data-pages='["borrowingHistory"]'>
                    <button class="sidebar-dropdown-toggle flex items-center justify-between w-full gap-x-3 px-3 py-2 rounded-lg transition <?= (in_array($currentPage, ["borrowingHistory"])) ? 'bg-orange-100 text-orange-900' : 'hover:bg-orange-100 text-orange-900' ?>">
                        <span class="flex items-center gap-x-3">
                            <i class="ph ph-clock-counter-clockwise text-2xl"></i>
                            <span class="text-base">History</span>
                        </span>
                        <i class="ph ph-caret-down text-xl dropdown-icon transition-transform"></i>
                    </button>
                    <div class="pl-5 pt-1 space-y-1 hidden">
                        <a href="<?= BASE_URL ?>/borrowingHistory" class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= ($currentPage === 'borrowingHistory') ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-bookmarks text-xl"></i>
                            <span class="text-base text-sm">Borrowing History</span>
                        </a>
                    </div>
                </div>

                <div class="sidebar-dropdown" data-pages='["myProfile", "changePassword"]'>
                    <button class="sidebar-dropdown-toggle flex items-center justify-between w-full gap-x-3 px-3 py-2 rounded-lg transition <?= (in_array($currentPage, ["myProfile", "changePassword"])) ? 'bg-orange-100 text-orange-900' : 'hover:bg-orange-100 text-orange-900' ?>">
                        <span class="flex items-center gap-x-3">
                            <i class="ph ph-user-gear text-2xl"></i>
                            <span class="text-base">Account Settings</span>
                        </span>
                        <i class="ph ph-caret-down text-xl dropdown-icon transition-transform"></i>
                    </button>
                    <div class="pl-5 pt-1 space-y-1 hidden">
                        <a href="<?= BASE_URL ?>/myProfile" class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= ($currentPage === 'myProfile') ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-user-circle text-xl"></i>
                            <span class="text-base text-sm">Profile</span>
                        </a>
                        <a href="<?= BASE_URL ?>/changePassword" class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= ($currentPage === 'changePassword') ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-key text-xl"></i>
                            <span class="text-base text-sm">Change Password</span>
                        </a>
                    </div>
                </div>

            <?php elseif ($role === 'superadmin'): ?>
                <a href="<?= $baseUrl ?>/dashboard"
                    class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'dashboard' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                    <i class="ph ph-house text-2xl"></i>
                    <span>Dashboard</span>
                </a>

                <div class="sidebar-dropdown" data-pages='["userManagement", "campusManagement", "bookManagement", "equipmentManagement", "studentPromotion"]'>
                    <button
                        class="sidebar-dropdown-toggle flex items-center justify-between w-full gap-x-3 px-3 py-2 rounded-lg transition <?= (in_array($currentPage, ["userManagement", "campusManagement", "bookManagement", "equipmentManagement", "studentPromotion"])) ? 'bg-orange-100 text-orange-900 font-semibold' : 'hover:bg-orange-100 text-orange-900' ?>">
                        <span class="flex items-center gap-x-3">
                            <i class="ph ph-folders text-2xl"></i>
                            <span class="text-base">Management</span>
                        </span>
                        <i class="ph ph-caret-down text-xl dropdown-icon transition-transform"></i>
                    </button>
                    <div class="pl-5 pt-1 space-y-1 hidden ml-4">
                        <a href="<?= $baseUrl ?>/userManagement"
                            class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'userManagement' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-users text-xl"></i>
                            <span class="text-base text-sm">User Management</span>
                        </a>
                        <a href="<?= $baseUrl ?>/campusManagement"
                            class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'campusManagement' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-buildings text-xl"></i>
                            <span class="text-base text-sm">Campus Management</span>
                        </a>
                        <a href="<?= $baseUrl ?>/studentPromotion"
                            class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'studentPromotion' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-student text-xl"></i>
                            <span class="text-base text-sm">Student Promotion</span>
                        </a>
                        <a href="<?= $baseUrl ?>/bookManagement"
                            class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'bookManagement' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-book-open text-xl"></i>
                            <span class="text-base text-sm">Book Management</span>
                        </a>
                        <a href="<?= $baseUrl ?>/equipmentManagement"
                            class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'equipmentManagement' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-desktop text-xl"></i>
                            <span class="text-base text-sm">Equipment Management</span>
                        </a>
                    </div>
                </div>

                <div class="sidebar-dropdown" data-pages='["qrScanner", "returning", "borrowingForm"]'>
                    <button
                        class="sidebar-dropdown-toggle flex items-center justify-between w-full gap-x-3 px-3 py-2 rounded-lg transition <?= (in_array($currentPage, ["qrScanner", "returning", "borrowingForm"])) ? 'bg-orange-100 text-orange-900 font-semibold' : 'hover:bg-orange-100 text-orange-900' ?>">
                        <span class="flex items-center gap-x-3">
                            <i class="ph ph-swap text-2xl"></i>
                            <span class="text-base">QR & Returning</span>
                        </span>
                        <i class="ph ph-caret-down text-xl dropdown-icon transition-transform"></i>
                    </button>
                    <div class="pl-5 pt-1 space-y-1 hidden ml-4">
                        <a href="<?= $baseUrl ?>/qrScanner"
                            class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'qrScanner' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-qr-code text-xl"></i>
                            <span class="text-base text-sm">QR Scanner</span>
                        </a>
                        <a href="<?= $baseUrl ?>/returning"
                            class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'returning' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-arrow-counter-clockwise text-xl"></i>
                            <span class="text-base text-sm">Returning</span>
                        </a>
                        <a href="<?= $baseUrl ?>/borrowingForm"
                            class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'borrowingForm' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-receipt text-xl"></i>
                            <span class="text-base text-sm">Borrowing Form</span>
                        </a>
                    </div>
                </div>

                <div class="sidebar-dropdown" data-pages='["attendanceLogs", "topVisitor", "transactionHistory", "auditLogs", "overdue"]'>
                    <button
                        class="sidebar-dropdown-toggle flex items-center justify-between w-full gap-x-3 px-3 py-2 rounded-lg transition <?= (in_array($currentPage, ["attendanceLogs", "topVisitor", "transactionHistory", "auditLogs", "overdue"])) ? 'bg-orange-100 text-orange-900 font-semibold' : 'hover:bg-orange-100 text-orange-900' ?>">
                        <span class="flex items-center gap-x-3">
                            <i class="ph ph-activity text-2xl"></i>
                            <span class="text-base">Activity & Logs</span>
                        </span>
                        <i class="ph ph-caret-down text-xl dropdown-icon transition-transform"></i>
                    </button>
                    <div class="pl-5 pt-1 space-y-1 hidden ml-4">
                        <a href="<?= $baseUrl ?>/attendanceLogs"
                            class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'attendanceLogs' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-calendar-check text-xl"></i>
                            <span class="text-base text-sm">Attendance Logs</span>
                        </a>
                        <a href="<?= $baseUrl ?>/overdue"
                            class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'overdue' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-warning text-xl"></i>
                            <span class="text-base text-sm">Overdue Tracking</span>
                        </a>
                        <a href="<?= $baseUrl ?>/topVisitor"
                            class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'topVisitor' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-chart-bar text-xl"></i>
                            <span class="text-base text-sm">Reports</span>
                        </a>
                        <a href="<?= $baseUrl ?>/transactionHistory"
                            class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'transactionHistory' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-arrows-left-right text-xl"></i>
                            <span class="text-base text-sm">Transaction History</span>
                        </a>
                        <a href="<?= $baseUrl ?>/auditLogs"
                            class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'auditLogs' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-shield-check text-xl"></i>
                            <span class="text-base text-sm">Audit Trail</span>
                        </a>
                    </div>
                </div>

                <div class="sidebar-dropdown" data-pages='["backup", "restoreBooks", "restoreUser"]'>
                    <button
                        class="sidebar-dropdown-toggle flex items-center justify-between w-full gap-x-3 px-3 py-2 rounded-lg transition <?= (in_array($currentPage, ["backup", "restoreBooks", "restoreUser"])) ? 'bg-orange-100 text-orange-900 font-semibold' : 'hover:bg-orange-100 text-orange-900' ?>">
                        <span class="flex items-center gap-x-3">
                            <i class="ph ph-database text-2xl"></i>
                            <span class="text-base">Backup & Restore</span>
                        </span>
                        <i class="ph ph-caret-down text-xl dropdown-icon transition-transform"></i>
                    </button>
                    <div class="pl-5 pt-1 space-y-1 hidden">
                        <a href="<?= $baseUrl ?>/backup"
                            class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'backup' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-floppy-disk text-xl"></i>
                            <span class="text-base text-sm">Backup</span>
                        </a>
                        <a href="<?= $baseUrl ?>/restoreBooks"
                            class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'restoreBooks' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-book text-xl"></i>
                            <span class="text-base text-sm">Restore Books</span>
                        </a>
                        <a href="<?= $baseUrl ?>/restoreUser"
                            class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'restoreUser' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-user-gear text-xl"></i>
                            <span class="text-base text-sm">Restore User</span>
                        </a>
                    </div>
                </div>

                <a href="<?= $baseUrl ?>/libraryPolicies"
                    class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'libraryPolicies' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                    <i class="ph ph-scroll text-2xl"></i>
                    <span>Library Policies</span>
                </a>

                <div class="sidebar-dropdown" data-pages='["myProfile", "changePassword"]'>
                    <button class="sidebar-dropdown-toggle flex items-center justify-between w-full gap-x-3 px-3 py-2 rounded-lg transition <?= (in_array($currentPage, ["myProfile", "changePassword"])) ? 'bg-orange-100 text-orange-900 font-semibold' : 'hover:bg-orange-100 text-orange-900' ?>">
                        <span class="flex items-center gap-x-3">
                            <i class="ph ph-user-gear text-2xl"></i>
                            <span class="text-base">Account Settings</span>
                        </span>
                        <i class="ph ph-caret-down text-xl dropdown-icon transition-transform"></i>
                    </button>
                    <div class="pl-5 pt-1 space-y-1 hidden">
                        <a href="<?= BASE_URL ?>/myProfile" class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= ($currentPage === 'myProfile') ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-user-circle text-xl"></i>
                            <span class="text-base text-sm">Profile</span>
                        </a>
                        <a href="<?= BASE_URL ?>/changePassword" class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= ($currentPage === 'changePassword') ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                            <i class="ph ph-key text-xl"></i>
                            <span class="text-base text-sm">Change Password</span>
                        </a>
                    </div>
                </div>

            <?php elseif ($role === 'admin' || $role === 'librarian'): ?>
                <?php if ($hasPermission('user management')): ?>
                    <a href="<?= $baseUrl ?>/userManagement"
                        class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'userManagement' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                        <i class="ph ph-users text-2xl"></i>
                        <span class="text-base">User Management</span>
                    </a>
                <?php endif; ?>

                <?php if ($hasPermission('book management')): ?>
                    <a href="<?= $baseUrl ?>/bookManagement"
                        class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'bookManagement' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                        <i class="ph ph-book-open text-2xl"></i>
                        <span class="text-base">Book Management</span>
                    </a>
                <?php endif; ?>

                <?php if ($hasPermission('equipment management')): ?>
                    <a href="<?= $baseUrl ?>/equipmentManagement"
                        class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'equipmentManagement' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                        <i class="ph ph-desktop text-2xl"></i>
                        <span class="text-base">Equipment Management</span>
                    </a>
                <?php endif; ?>

                <?php if ($hasPermission('qr scanner')): ?>
                    <a href="<?= $baseUrl ?>/qrScanner"
                        class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'qrScanner' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                        <i class="ph ph-qr-code text-2xl"></i>
                        <span class="text-base">QR Scanner</span>
                    </a>
                <?php endif; ?>

                <?php if ($hasPermission('returning')): ?>
                    <a href="<?= $baseUrl ?>/returning"
                        class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'returning' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                        <i class="ph ph-arrow-counter-clockwise text-2xl"></i>
                        <span class="text-base">Returning</span>
                    </a>
                <?php endif; ?>

                <?php if ($hasPermission('borrowing form')): ?>
                    <a href="<?= $baseUrl ?>/borrowingForm"
                        class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'borrowingForm' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                        <i class="ph ph-receipt text-2xl"></i>
                        <span class="text-base">Borrowing Form</span>
                    </a>
                <?php endif; ?>

                <?php if ($hasPermission('attendance logs')): ?>
                    <a href="<?= $baseUrl ?>/attendanceLogs"
                        class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'attendanceLogs' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                        <i class="ph ph-calendar-check text-2xl"></i>
                        <span class="text-base">Attendance Logs</span>
                    </a>
                <?php endif; ?>

                <?php if ($hasPermission('reports')): ?>
                    <a href="<?= $baseUrl ?>/topVisitor"
                        class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'topVisitor' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                        <i class="ph ph-chart-bar text-2xl"></i>
                        <span class="text-base">Reports</span>
                    </a>
                <?php endif; ?>

                <?php if ($hasPermission('transaction history')): ?>
                    <a href="<?= $baseUrl ?>/transactionHistory"
                        class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'transactionHistory' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                        <i class="ph ph-arrows-left-right text-2xl"></i>
                        <span class="text-base">Transaction History</span>
                    </a>
                <?php endif; ?>

                <?php if ($hasPermission('restore books')): ?>
                    <a href="<?= $baseUrl ?>/restoreBooks"
                        class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'restoreBooks' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                        <i class="ph ph-book text-2xl"></i>
                        <span class="text-base">Restore Books</span>
                    </a>
                <?php endif; ?>

                <?php if ($hasPermission('restore users')): ?>
                    <a href="<?= $baseUrl ?>/restoreUser"
                        class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= $currentPage === 'restoreUser' ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                        <i class="ph ph-user-gear text-2xl"></i>
                        <span class="text-base">Restore User</span>
                    </a>
                <?php endif; ?>

                <a href="<?= $baseUrl ?>/changePassword"
                    class="flex items-center gap-x-3 px-3 py-2 rounded-lg transition <?= ($currentPage === 'changePassword') ? 'bg-green-600 text-white font-medium' : 'hover:bg-orange-100 text-orange-900' ?>">
                    <i class="ph ph-key text-2xl"></i>
                    <span class="text-base">Change Password</span>
                </a>

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

                const isManagedRole = ['admin', 'librarian'].includes('<?= $role ?>');


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