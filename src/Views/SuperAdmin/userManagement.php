<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold mb-4">User Management</h2>
        <p class="text-gray-700">Manage students, librarians, and system access.</p>
    </div>
    <div class="flex gap-2 text-sm">
        <button
            class="inline-flex items-center bg-white font-medium border border-orange-200 justify-center px-4 py-2 rounded-lg hover:bg-gray-100 px-4 gap-2"
            id="bulkImportBtn">
            <i class="ph ph-upload-simple"></i>
            Bulk Import
        </button>
        <button
            class="px-4 py-2 bg-orange-500 text-white font-medium rounded-lg border hover:bg-orange-600 gap-2 inline-flex items-center"
            id="addUserBtn">
            <i class="ph ph-plus"></i>
            Add User
        </button>
    </div>
</div>

<div class="bg-[var(--color-card)] border border-orange-200 rounded-xl shadow-sm p-6 mt-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">User Management</h3>
            <p class="text-sm text-gray-600">Registered users in the system</p>
        </div>
        <div class="flex items-center gap-2 text-sm">
            <div class="relative">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input type="text" id="userSearchInput" placeholder="Search"
                    class="bg-orange-50 border border-orange-200 rounded-lg pl-9 pr-3 py-2 outline-none transition text-sm">
            </div>
            <div class="relative inline-block text-left">
                <button id="roleDropdownBtn"
                    class="border border-orange-200 rounded-lg px-3 py-2 text-sm text-gray-700 flex items-center justify-between gap-2 w-36 hover:bg-orange-50 transition">
                    <span id="roleDropdownValue">All Roles</span>
                    <i class="ph ph-caret-down text-xs"></i>
                </button>
                <div id="roleDropdownMenu"
                    class="absolute mt-1 w-full bg-white border border-orange-200 rounded-lg shadow-md hidden z-20">
                    <div class="dropdown-item px-3 py-2 hover:bg-orange-100 cursor-pointer"
                        onclick="selectRole(this, 'All Roles')">All Roles</div>
                    <div class="dropdown-item px-3 py-2 hover:bg-orange-100 cursor-pointer"
                        onclick="selectRole(this, 'Student')">Student</div>
                    <div class="dropdown-item px-3 py-2 hover:bg-orange-100 cursor-pointer"
                        onclick="selectRole(this, 'Librarian')">Librarian</div>
                    <div class="dropdown-item px-3 py-2 hover:bg-orange-100 cursor-pointer"
                        onclick="selectRole(this, 'Admin')">Admin</div>
                    <div class="dropdown-item px-3 py-2 hover:bg-orange-100 cursor-pointer"
                        onclick="selectRole(this, 'Faculty')">Faculty</div>
                    <div class="dropdown-item px-3 py-2 hover:bg-orange-100 cursor-pointer"
                        onclick="selectRole(this, 'Staff')">Staff</div>
                </div>
            </div>

            <div class="relative inline-block text-left ml-3">
                <button id="statusDropdownBtn"
                    class="border border-orange-200 rounded-lg px-3 py-2 text-sm text-gray-700 flex items-center justify-between gap-2 w-36 hover:bg-orange-50 transition">
                    <span id="statusDropdownValue">All Status</span>
                    <i class="ph ph-caret-down text-xs"></i>
                </button>
                <div id="statusDropdownMenu"
                    class="absolute mt-1 w-full bg-white border border-orange-200 rounded-lg shadow-md hidden z-20">
                    <div class="status-item px-3 py-2 hover:bg-orange-100 cursor-pointer"
                        onclick="selectStatus(this, 'All Status')">All Status</div>
                    <div class="status-item px-3 py-2 hover:bg-orange-100 cursor-pointer"
                        onclick="selectStatus(this, 'Active')">Active</div>
                    <div class="status-item px-3 py-2 hover:bg-orange-100 cursor-pointer"
                        onclick="selectStatus(this, 'Inactive')">Inactive</div>
                </div>
            </div>
        </div>
    </div>
    <!-- Updated -->
    <div class="flex items-center justify-between my-4">

        <h4 id="resultsIndicator" class="text-sm text-gray-600">
            Loading...
        </h4>

        <div class="inline-flex items-center gap-2">
            <div id="multiSelectActions" class="hidden items-center gap-2">
                <button id="multiDeleteBtn" title="Delete selected users"
                    class=" hidden inline-flex items-center gap-2 bg-red-600 text-white rounded-lg px-3 py-2 text-sm font-medium hover:bg-red-700 transition">
                    <i class="ph ph-trash text-base"></i>
                    Delete (<span id="selectionCount">0</span>)
                </button>
                <button id="multiAllowEditBtn" title="Allow edit for selected students"
                    class="hidden inline-flex items-center gap-2 bg-blue-600 text-white rounded-lg px-3 py-2 text-sm font-medium hover:bg-blue-700 transition">
                    <i class="ph ph-user-plus text-base"></i>
                    Allow Edit
                </button>

                <div class="h-6 border-l border-gray-300 mx-2"></div>

                <button id="selectAllBtn" title="Select-all"
                    class="inline-flex items-center gap-2 border border-orange-200 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-orange-50 transition">
                    <i class="ph ph-check-square-offset text-base"></i>
                    Select All
                </button>
                <button id="cancelSelectionBtn" title="Cancel multi-select"
                    class="inline-flex items-center gap-2 border border-gray-300 text-gray-700 rounded-lg px-3 py-2 text-sm font-medium hover:bg-gray-100 transition">
                    <i class="ph ph-x text-base"></i>
                    Cancel
                </button>
            </div>

            <button id="multiSelectBtn" title="Multi-select"
                class="inline-flex items-center gap-2 border border-orange-200 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-orange-50 transition">
                <i class="ph ph-list-checks text-base"></i>
                Multiple Select
            </button>
        </div>

    </div>
    <!-- end -->
    <div class="overflow-x-auto rounded-lg border border-orange-200">
        <table class="w-full text-sm border-collapse">
            <thead class="bg-orange-50 text-gray-700 border border-orange-100">
                <tr>
                    <th class="text-left px-4 py-3 font-medium">User</th>
                    <th class="text-left px-4 py-3 font-medium">Email</th>
                    <th class="text-left px-4 py-3 font-medium">Role</th>
                    <th class="text-left px-4 py-3 font-medium">Status</th>
                    <th class="text-left px-4 py-3 font-medium">Date Registered</th>
                    <th class="text-left px-4 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody id="userTableBody" class="divide-y divide-orange-100">
                <tr data-placeholder="true">
                    <td colspan="6" class="text-center text-gray-500 py-10">
                        <i class="ph ph-spinner animate-spin text-2xl"></i>
                    </td>
                </tr>
            </tbody>

        </table>
    </div>

    <nav id="paginationControls" aria-label="Page navigation"
        class="flex items-center justify-center bg-white border border-gray-200 rounded-full shadow-md px-4 py-2 mt-6 w-fit mx-auto gap-3 hidden">
        <ul id="paginationList" class="flex items-center h-9 text-sm gap-3">
        </ul>
    </nav>
</div>

<div id="importModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">
    <div
        class="bg-[var(--color-card)] rounded-xl shadow-lg border border-[var(--color-border)] w-full max-w-md p-6 animate-fadeIn">
        <div class="flex justify-between items-start mb-4">
            <h2 class="text-lg font-semibold">Bulk Import Users</h2>
            <button id="closeImportModal" class="text-gray-500 hover:text-red-700 transition">
                <i class="ph ph-x text-2xl"></i>
            </button>
        </div>

        <p class="text-sm text-gray-600 mb-4">
            Import multiple users from a CSV file or use sample data.
        </p>
        <div id="importMessage" class="text-green-600 font-medium mb-2 hidden"></div>
        <form id="bulkImportForm" enctype="multipart/form-data">
            <label for="csvFile"
                class="block border-2 border-dashed border-[var(--color-border)] rounded-lg p-8 text-center cursor-pointer hover:border-[var(--color-ring)]/60 transition">
                <i class="ph ph-upload text-[var(--color-ring)] text-3xl mb-2 block"></i>
                <p class="font-medium text-[var(--color-ring)]">Drop CSV file here or click to browse</p>
                <p class="text-xs text-gray-500 mt-1">Expected format: Name, Username, Role</p>
                <input type="file" id="csvFile" name="csv_file" accept=".csv" class="hidden" />
            </label>
        </form>
        <div class="text-center mt-4">
            <button id="cancelImport"
                class="mt-2 border border-[var(--color-border)] px-4 py-2 rounded-md text-gray-700 hover:bg-gray-100 transition">
                Cancel
            </button>
        </div>
    </div>
</div>

<div id="addUserModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">
    <div
        class="rounded-xl overflow-hidden shadow-lg border border-[var(--color-border)] bg-[var(--color-card)] w-full max-w-lg animate-fadeIn">
        <div class="flex flex-col min-h-[60vh] max-h-[80vh]">

            <div class="flex-1 overflow-y-auto p-6">
                <div class="flex justify-between items-start mb-4">
                    <h2 class="text-lg font-semibold flex items-center gap-2">
                        <i class="ph ph-user-plus text-[var(--color-ring)] text-xl"></i>
                        Add New User
                    </h2>
                    <button id="closeAddUserModal" class="text-gray-500 hover:text-red-700 transition">
                        <i class="ph ph-x text-2xl"></i>
                    </button>
                </div>

                <p class="text-sm text-gray-600 mb-4">
                    Create a new user account with specific permissions.
                </p>

                <div class="space-y-4 mb-6">
                    <h3 class="font-medium text-[var(--color-ring)]">Basic Information</h3>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">First Name <span
                                    class="text-red-500">*</span></label>
                            <input type="text" id="addFirstName" placeholder="Juan"
                                class="w-full border border-[var(--color-border)] rounded-md px-3 py-2 text-sm focus-visible:ring-[var(--color-ring)] focus-visible:border-[var(--color-ring)] outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                            <input type="text" id="addMiddleName" placeholder="Ponce"
                                class="w-full border border-[var(--color-border)] rounded-md px-3 py-2 text-sm focus-visible:ring-[var(--color-ring)] focus-visible:border-[var(--color-ring)] outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name <span
                                    class="text-red-500">*</span></label>
                            <input type="text" id="addLastName" placeholder="Dela Cruz"
                                class="w-full border border-[var(--color-border)] rounded-md px-3 py-2 text-sm focus-visible:ring-[var(--color-ring)] focus-visible:border-[var(--color-ring)] outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Username <span
                                    class="text-red-500">*</span></label>
                            <input type="text" id="addUsername" placeholder="username"
                                class="w-full border border-[var(--color-border)] rounded-md px-3 py-2 text-sm focus-visible:ring-[var(--color-ring)] focus-visible:border-[var(--color-ring)] outline-none">
                        </div>
                        <div class="relative w-full">
                            <label class="block text-sm text-gray-700 mb-1">Role</label>
                            <button id="userRoleDropdownBtn"
                                class="w-full border border-orange-200 rounded-lg px-3 py-2 text-sm text-gray-700 flex items-center justify-between hover:bg-orange-50 transition">
                                <span id="userRoleDropdownValue">Select Role</span>
                                <i class="ph ph-caret-down text-xs"></i>
                            </button>
                            <div id="userRoleDropdownMenu"
                                class="absolute mt-1 w-full bg-white border border-orange-200 rounded-lg shadow-md hidden z-20">
                                <div class="user-role-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm"
                                    onclick="selectUserRole(this, 'Student')">Student</div>
                                <div class="user-role-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm"
                                    onclick="selectUserRole(this, 'Librarian')">Librarian</div>
                                <div class="user-role-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm"
                                    onclick="selectUserRole(this, 'Admin')">Admin</div>
                                <div class="user-role-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm"
                                    onclick="selectUserRole(this, 'Faculty')">Faculty</div>
                                <div class="user-role-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm"
                                    onclick="selectUserRole(this, 'Staff')">Staff</div>
                            </div>
                        </div>
                    </div>

                    <div id="addUserSingleSelectWrapper" class="hidden">
                        <label id="addUserSelectLabel"
                            class="block text-sm font-medium text-gray-700 mb-1">Course/Department <span
                                class="text-red-500">*</span></label>
                        <select id="addUserSelectField" required
                            class="w-full border border-[var(--color-border)] rounded-md px-3 py-2 text-sm focus-visible:ring-[var(--color-ring)] focus-visible:border-[var(--color-ring)] outline-none">
                        </select>
                    </div>
                </div>

                <div id="addUserStudentFieldsWrapper" class="hidden space-y-4">
                    <h3 class="font-medium text-[var(--color-ring)]">Student Information</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label id="addCollegeLabel" class="block text-sm font-medium text-gray-700 mb-1">College
                                <span class="text-red-500">*</span></label>
                            <select id="addCollegeDropdown" required
                                class="w-full border border-[var(--color-border)] rounded-md px-3 py-2 text-sm focus-visible:ring-[var(--color-ring)] focus-visible:border-[var(--color-ring)] outline-none">
                                <option value="">Select College</option>
                            </select>
                        </div>
                        <div>
                            <label id="addCourseLabel"
                                class="block text-sm font-medium text-gray-700 mb-1">Course/Program <span
                                    class="text-red-500">*</span></label>
                            <select id="addCourseDropdown" name="course_id" required disabled
                                class="w-full border border-[var(--color-border)] rounded-md px-3 py-2 text-sm bg-gray-100 focus-visible:ring-[var(--color-ring)] focus-visible:border-[var(--color-ring)] outline-none">
                                <option value="">Select College First</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="addUserDepartmentWrapper" class="hidden">
                    <label id="addUserDepartmentLabel" class="block text-sm font-medium text-gray-700 mb-1">Department
                        <span class="text-red-500">*</span></label>
                    <select id="addUserDepartment" required
                        class="w-full border border-[var(--color-border)] rounded-md px-3 py-2 text-sm focus-visible:ring-[var(--color-ring)] focus-visible:border-[var(--color-ring)] outline-none">
                        <option value="">Select Department</option>
                    </select>
                </div>

                <div id="modulesSection" class="hidden">
                    <h3 class="font-medium text-[var(--color-ring)] mb-2 flex items-center gap-2">
                        <i class="ph ph-shield-check text-[var(--color-ring)]"></i> Permissions
                    </h3>
                    <p class="text-sm text-gray-600 mb-3">
                        Select the actions this user should have access to per module.
                    </p>

                    <div class="grid grid-cols-2 gap-3 max-h-[70vh] overflow-y-auto rounded-xl p-2">
                        <?php
                        $modules = [
                            'book management',
                            'equipment management',
                            'qr scanner',
                            'returning',
                            'borrowing form',
                            'attendance logs',
                            'reports',
                            'transaction history',
                            'restore books',
                            'restore equipment',
                            'user management',
                            'restore users'
                        ];

                        foreach ($modules as $module):
                            $isUserManagement = ($module === 'user management');
                            $isRestoreUser = ($module === 'restore users');
                            $wrapperId = $isUserManagement ? 'id="addUserUserManagementModuleWrapper"' : '';
                            $restoreId = $isRestoreUser ? 'id="addUserRestoreUserModuleWrapper"' : '';
                        ?>
                        <div <?= $wrapperId ?> <?= $restoreId ?>
                            class="border rounded-md p-3 bg-orange-50/50 border-orange-200">
                            <label class="inline-flex items-center text-sm text-gray-700">
                                <input type="checkbox" class="mr-2 accent-orange-500" name="modules[]"
                                    value="<?= $module ?>">
                                <?= ucwords($module) ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 p-4 bg-white sticky bottom-0">
                <button id="confirmAddUser"
                    class="flex-1 bg-orange-600 text-white font-medium px-4 py-2.5 text-sm rounded-md hover:bg-orange-700 transition">
                    Add User
                </button>
                <button id="cancelAddUser"
                    class="border border-orange-200 text-gray-800 font-medium px-4 py-2.5 text-sm rounded-md hover:bg-orange-50 transition">
                    Cancel
                </button>
            </div>

        </div>

    </div>
</div>

<div id="editUserModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">
    <div
        class="rounded-xl overflow-hidden shadow-lg border border-[var(--color-border)] bg-[var(--color-card)] w-full max-w-lg animate-fadeIn">
        <div class="p-6 max-h-[90vh] overflow-y-auto">

            <div class="flex justify-between items-start mb-4">
                <div>
                    <h2 id="editUserTitle" class="text-lg font-semibold flex items-center gap-2">
                        <i class="ph ph-user-gear text-orange-500 text-xl"></i>
                        Edit User: <span class="font-semibold text-gray-800">Full Name</span>
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Update user information and permissions.
                    </p>
                </div>
                <button id="closeEditUserModal" class="text-gray-500 hover:text-red-600 transition">
                    <i class="ph ph-x text-2xl"></i>
                </button>
            </div>

            <div class="space-y-4 mb-6">
                <h3 class="font-medium text-orange-600">Basic Information</h3>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm text-gray-700 mb-1 font-medium">First Name <span
                                class="text-red-500">*</span></label>
                        <input id="editFirstName" type="text"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-orange-400 outline-none"
                            placeholder="Juan">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 mb-1 font-medium">Middle Name</label>
                        <input id="editMiddleName" type="text"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-orange-400 outline-none"
                            placeholder="Ponce">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 mb-1 font-medium">Last Name <span
                                class="text-red-500">*</span></label>
                        <input id="editLastName" type="text"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-orange-400 outline-none"
                            placeholder="Dela Cruz">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-700 mb-1 font-medium">Email <span
                                class="text-red-500">*</span></label>
                        <input id="editEmail" type="email"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-orange-400 outline-none"
                            placeholder="user@university.edu">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 mb-1 font-medium">Username</label>
                        <input id="editUsername" type="text"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-orange-400 outline-none"
                            placeholder="username">
                    </div>
                </div>


                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-700 mb-1 font-medium">Role</label>
                        <div class="relative w-full">
                            <button id="editRoleDropdownBtn"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-500 flex items-center justify-between bg-gray-50 cursor-not-allowed"
                                disabled>
                                <span id="editRoleDropdownValue"></span>
                                <i class="ph ph-caret-down text-xs"></i>
                            </button>
                            <div id="editRoleDropdownMenu"
                                class="absolute mt-1 w-full bg-white border border-orange-200 rounded-lg shadow-md hidden z-20">
                                <div class="edit-role-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm"
                                    onclick="selectEditRole(this, 'Student',selected)">Student</div>
                                <div class="edit-role-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm"
                                    onclick="selectEditRole(this, 'Faculty')">Faculty</div>
                                <div class="edit-role-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm"
                                    onclick="selectEditRole(this, 'Staff')">Staff</div>
                                <div class="edit-role-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm"
                                    onclick="selectEditRole(this, 'Librarian')">Librarian</div>
                                <div class="edit-role-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm"
                                    onclick="selectEditRole(this, 'Admin')">Admin</div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 mb-1 font-medium">Status</label>
                        <div class="relative w-full">
                            <button id="editStatusDropdownBtn"
                                class="w-full border border-orange-200 rounded-lg px-3 py-2 text-sm text-gray-700 flex items-center justify-between hover:bg-orange-50 transition">
                                <span id="editStatusDropdownValue">Select Status</span>
                                <i class="ph ph-caret-down text-xs"></i>
                            </button>
                            <div id="editStatusDropdownMenu"
                                class="absolute mt-1 w-full bg-white border border-orange-200 rounded-lg shadow-md hidden z-20">
                                <div class="edit-status-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm"
                                    onclick="selectEditStatus(this, 'Active')">Active</div>
                                <div class="edit-status-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm"
                                    onclick="selectEditStatus(this, 'Inactive')">Inactive</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="border-t pt-4 mb-6 border-orange-200">
                <h3 class="font-medium text-orange-600 mb-2 flex items-center gap-2">
                    <i class="ph ph-lock-key text-orange-500"></i> Change Password
                </h3>

                <label class="flex items-center gap-2 text-sm text-gray-700 mb-3">
                    <input type="checkbox" id="togglePassword" class="accent-orange-500">
                    Change this user's password
                </label>

                <div id="passwordFields" class="hidden bg-orange-50/30 border border-orange-200 rounded-lg p-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-700 mb-1 font-medium">New Password <span
                                    class="text-red-500">*</span></label>
                            <div class="relative">
                                <input id="editPassword" type="password"
                                    class="w-full bg-orange-50 border border-orange-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-orange-400 outline-none pr-10"
                                    placeholder="Enter new password" minlength="8">
                                <button type="button" id="toggleNewPass"
                                    class="absolute right-3 top-2.5 text-gray-500 hover:text-orange-500">
                                    <i class="ph ph-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-700 mb-1 font-medium">Confirm Password <span
                                    class="text-red-500">*</span></label>
                            <div class="relative">
                                <input id="confirmPassword" type="password"
                                    class="w-full bg-orange-50 border border-orange-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-orange-400 outline-none pr-10"
                                    placeholder="Confirm new password">
                                <button type="button" id="toggleConfirmPass"
                                    class="absolute right-3 top-2.5 text-gray-500 hover:text-orange-500">
                                    <i class="ph ph-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Password must be at least 8 characters long</p>
                    <div class="mt-4 bg-amber-50 border border-orange-200 rounded-md p-3 text-sm text-amber-700">
                        <strong>Note:</strong> Changing the password will require the user to sign in with the new
                        password.
                        They will be notified via email about this change.
                    </div>
                </div>
            </div>

            <div id="editPermissionsContainer" class="hidden">
                <h3 class="font-medium text-[var(--color-ring)] mb-2 flex items-center gap-2">
                    <i class="ph ph-shield-check text-[var(--color-ring)]"></i> Permissions
                </h3>
                <p class="text-sm text-gray-600 mb-3">
                    Select the functions this user should have access to.
                </p>

                <div id="editModulesSection" class="grid grid-cols-2 gap-3 max-h-[70vh] overflow-y-auto rounded-xl p-2">
                    <?php
                    $modules = [
                        'book management',
                        'equipment management',
                        'qr scanner',
                        'returning',
                        'borrowing form',
                        'attendance logs',
                        'reports',
                        'transaction history',
                        'restore books',
                        'restore equipment',
                        'user management',
                        'restore users'
                    ];

                    foreach ($modules as $module):
                        $isUserManagement = ($module === 'user management');
                        $isRestoreUser = ($module === 'restore users');
                        $wrapperId = $isUserManagement ? 'id="editUserUserManagementModuleWrapper"' : '';
                        $restoreId = $isRestoreUser ? 'id="editUserRestoreUserModuleWrapper"' : '';
                    ?>
                    <div <?= $wrapperId ?> <?= $restoreId ?>
                        class="border rounded-md p-3 bg-orange-50/50 border-orange-200">
                        <label class="inline-flex items-center text-sm text-gray-700">
                            <input type="checkbox" class="mr-2 accent-orange-500" name="editModules[]"
                                value="<?= $module ?>">
                            <?= ucwords($module) ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button id="saveEditUser"
                    class="flex-1 bg-orange-600 text-white font-medium text-sm px-6 py-2.5 rounded-md hover:bg-orange-700 transition">
                    Save Changes
                </button>
                <button id="cancelEditUser"
                    class="border border-orange-200 text-gray-800 font-medium text-sm px-6 py-2.5 rounded-md hover:bg-orange-50 transition">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/js/superadmin/userManagement.js" defer></script>