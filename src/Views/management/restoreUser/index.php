<?php
$currentUserRole = strtolower($_SESSION['role'] ?? 'guest');
?>
<main class="min-h-screen">
    <div class="flex items-center gap-3 mb-6">
        <i class="ph-fill ph-user-plus text-3xl text-gray-700"></i>
        <div>
            <h2 class="text-2xl font-bold mb-1">Restore User</h2>
            <p class="text-gray-500">View and manage users that have been soft-deleted from the system.</p>
        </div>
    </div>

    <div class="bg-white shadow-sm border border-gray-200 rounded-lg p-6 mb-6">
        <div class="flex items-center gap-2 mb-4">
            <i class="ph ph-funnel text-xl text-orange-700"></i>
            <h3 class="text-lg font-semibold text-orange-700">Search & Filter</h3>
        </div>

        <div class="flex flex-col md:flex-row md:items-center gap-4">
            <div class="relative flex-grow">
                <i class="ph ph-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" id="userSearchInput" placeholder="Search by name, username, or email..."
                    class="bg-orange-50 border border-orange-200 rounded-lg pl-11 pr-4 py-2.5 w-full text-sm outline-none transition focus:ring-1 focus:ring-orange-400">
            </div>

            <div class="relative w-full md:w-44">
                <button id="roleFilterDropdownBtn"
                    class="bg-orange-50 border border-orange-200 rounded-lg px-4 py-2.5 text-sm text-gray-700 flex items-center justify-between w-full">
                    <span>All Users</span>
                    <i class="ph ph-caret-down"></i>
                </button>

                <!-- Dropdown menu -->
                <div id="roleFilterDropdownMenu"
                    class="absolute mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg hidden z-10">
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-orange-50"
                        data-value="All Users">All Users</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-orange-50"
                        data-value="student">Student</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-orange-50"
                        data-value="librarian">Librarian</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-orange-50"
                        data-value="admin">Admin</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-orange-50"
                        data-value="superadmin">Super Admin</a>
                </div>
            </div>

            <!-- Calendar Filter -->
            <div class="relative w-full md:w-40">
                <input type="date" id="deletedUserDateFilter"
                    class="bg-orange-50 border border-orange-200 rounded-lg px-3 py-2 outline-none transition text-sm text-gray-700 w-full focus:ring-1 focus:ring-orange-400">
            </div>
        </div>
    </div>

    <!-- Deleted Users Table Section -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg p-6">
        <div class="flex items-center gap-2 mb-4">
            <i class="ph ph-users text-xl text-orange-700"></i>
            <h3 class="text-lg font-semibold text-orange-700">Deleted Users (<span id="deletedUsersCount">0</span>)</h3>
        </div>
        <!-- Inayos ang description -->
        <p class="text-gray-600 mb-6">Users that have been soft-deleted can be restored or archived.</p>

        <div class="overflow-x-auto rounded-lg border border-orange-200">
            <table class="min-w-full divide-y divide-gray-200 text-sm table-fixed">
                <thead class="bg-orange-100">
                    <tr>
                        <th scope="col"
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">
                            User</th>
                        <th scope="col"
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">
                            Username</th>
                        <th scope="col"
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">
                            Role</th>
                        <th scope="col"
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">
                            Deleted Date</th>
                        <th scope="col"
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">
                            Deleted By</th>
                        <th scope="col"
                            class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-auto">
                            Actions</th>
                    </tr>
                </thead>
                <tbody id="deletedUsersTableBody" class="bg-white divide-y divide-gray-200"></tbody>
            </table>
            <p id="noDeletedUsersFound" class="text-gray-500 text-center w-full py-10 hidden">
                <i class="ph ph-user-slash text-4xl block mb-2 text-gray-400"></i>
                No deleted users found matching filters.
            </p>
        </div>

        <!-- Pagination Controls -->
        <div id="pagination-container" class="flex justify-center items-center mt-6 hidden">
            <nav class="bg-white px-8 py-3 rounded-full shadow-md border border-gray-200">
                <ul class="flex items-center gap-4 text-sm">
                    <li><a href="#" id="prev-page"
                            class="flex items-center gap-1 text-gray-400 hover:text-gray-600 transition"><i
                                class="ph ph-caret-left"></i><span>Previous</span></a></li>
                    <div id="pagination-numbers" class="flex items-center gap-3"></div>
                    <li><a href="#" id="next-page"
                            class="flex items-center gap-1 text-gray-400 hover:text-gray-600 transition"><span>Next</span><i
                                class="ph ph-caret-right"></i></a></li>
                </ul>
            </nav>
        </div>
    </div>
</main>

<template id="deleted-user-row-template">
    <tr class="hover:bg-orange-50 cursor-pointer deleted-user-row">
        <td class="px-4 py-3 break-words whitespace-normal">
            <div class="text-sm font-medium text-gray-900 user-fullname"></div>
        </td>
        <td class="px-4 py-3 break-words whitespace-normal text-sm text-gray-500 user-username"></td>
        <td class="px-4 py-3 break-words whitespace-normal text-sm text-gray-500 user-role capitalize"></td> <!-- Added capitalize -->
        <td class="px-4 py-3 break-words whitespace-normal text-sm text-gray-500 user-deleted-date"></td>
        <td class="px-4 py-3 break-words whitespace-normal text-sm text-gray-500 user-deleted-by"></td>
        <td class="px-4 py-3 text-center text-sm font-medium">
            <div class="flex justify-center items-center gap-3 inline-flex">
                <button
                    class="restore-btn inline-flex items-center gap-2 px-3 py-1.5 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition">
                    <i class="ph ph-arrow-counter-clockwise text-base"></i>
                    <span>Restore</span>
                </button>

                <button
                    class="archive-btn inline-flex items-center gap-2 px-3 py-1.5 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition"
                    <?= (isset($isGlobal) && !$isGlobal) ? 'hidden' : '' ?>>
                    <i class="ph ph-archive text-base"></i> 
                    <span>Archive</span>
                </button>
            </div>
        </td>

    </tr>
</template>

<!-- User Details Modal -->
<div id="userDetailsModal"
    class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg flex flex-col">
        <div class="p-6 border-b rounded-t-lg border-gray-200 flex justify-between items-center">
            <div>
                <h3 class="text-xl font-semibold text-orange-600">Deleted User Details</h3>
                <p class="text-sm text-gray-500">Complete information about this deleted user</p>
            </div>
            <button id="closeUserDetailsModalBtn" class="text-gray-400 hover:text-gray-600 transition">
                <i class="ph ph-x text-2xl"></i>
            </button>
        </div>
        <div class="p-8 space-y-6 overflow-y-auto max-h-[70vh]"> <!-- Added max-h for scroll -->
            <div class="border border-gray-200 rounded-lg p-4 shadow-sm">
                <h4 class="text-sm font-semibold text-orange-500 mb-4 uppercase tracking-wider">User Information</h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Full Name:</p>
                        <p id="modalUserFullName" class="font-medium text-gray-800 break-words"></p>
                    </div>
                    <div>
                        <p class="text-gray-500">Username:</p>
                        <p id="modalUsername" class="font-medium text-gray-800 break-words"></p>
                    </div>
                    <div>
                        <p class="text-gray-500">Role:</p>
                        <p id="modalUserRole" class="font-medium text-gray-800 break-words capitalize"></p> <!-- Added capitalize -->
                    </div>
                    <div>
                        <p class="text-gray-500">Email:</p>
                        <p id="modalUserEmail" class="font-medium text-gray-800 break-words"></p>
                    </div>
                    <div>
                        <p class="text-gray-500">Contact:</p>
                        <p id="modalContact" class="font-medium text-gray-800 break-words"></p>
                    </div>
                </div>
            </div>
            <div class="border border-gray-200 rounded-lg p-4 shadow-sm">
                <h4 class="text-sm font-semibold text-orange-500 mb-4 uppercase tracking-wider">Deletion Details</h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Created Date:</p>
                        <p id="modalUserCreatedDate" class="font-medium text-gray-800 break-words"></p>
                    </div>
                    <div>
                        <p class="text-gray-500">Deleted Date:</p>
                        <p id="modalUserDeletedDate" class="font-medium text-gray-800 break-words"></p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-gray-500">Deleted By:</p>
                        <p id="modalUserDeletedBy" class="font-medium text-gray-800 break-words"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

<script src="<?= BASE_URL ?>/js/management/restoreUser.js"></script>
