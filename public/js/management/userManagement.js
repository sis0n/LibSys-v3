window.addEventListener("DOMContentLoaded", () => {
    // Utility functions (Toasts, Modals)
    function showSuccessToast(title, body = "Successfully processed.") {
        if (typeof Swal == "undefined") return alert(title);
        Swal.fire({
            toast: true,
            position: "bottom-end",
            showConfirmButton: false,
            timer: 3000,
            width: "360px",
            background: "transparent",
            html: `<div class="flex flex-col text-left"><div class="flex items-center gap-3 mb-2"><div class="flex items-center justify-center w-10 h-10 rounded-full bg-green-100 text-green-600"><i class="ph ph-check-circle text-lg"></i></div><div><h3 class="text-[15px] font-semibold text-green-600">${title}</h3><p class="text-[13px] text-gray-700 mt-0.5">${body}</p></div></div></div>`,
            customClass: {
                popup: "!rounded-xl !shadow-md !border-2 !border-green-400 !p-4 !bg-gradient-to-b !from-[#fffdfb] !to-[#f0fff5] shadow-[0_0_8px_#22c55e70]",
            },
        });
    }

    function showErrorToast(title, body = "Please check the input details.") {
        if (typeof Swal == "undefined") return alert(title);
        Swal.fire({
            toast: true,
            position: "bottom-end",
            showConfirmButton: false,
            timer: 4000,
            width: "360px",
            background: "transparent",
            html: `<div class="flex flex-col text-left"><div class="flex items-center gap-3 mb-2"><div class="flex items-center justify-center w-10 h-10 rounded-full bg-red-100 text-red-600"><i class="ph ph-x-circle text-lg"></i></div><div><h3 class="text-[15px] font-semibold text-red-600">${title}</h3><p class="text-[13px] text-gray-700 mt-0.5">${body}</p></div></div></div>`,
            customClass: {
                popup: "!rounded-xl !shadow-md !border-2 !border-red-400 !p-4 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_8px_#ff6b6b70]",
            },
        });
    }

    function showLoadingModal(message = "Processing request...", subMessage = "Please wait.") {
        if (typeof Swal == "undefined") return;
        Swal.fire({
            background: "transparent",
            html: `
                <div class="flex flex-col items-center justify-center gap-2">
                    <div class="animate-spin rounded-full h-10 w-10 border-4 border-orange-200 border-t-orange-600"></div>
                    <p class="text-gray-700 text-[14px]">${message}<br><span class="text-sm text-gray-500">${subMessage}</span></p>
                </div>
            `,
            allowOutsideClick: false,
            showConfirmButton: false,
            customClass: {
                popup: "!rounded-xl !shadow-md !border-2 !border-orange-400 !p-6 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_8px_#ffb34770]",
            },
        });
    }

    async function showConfirmationModal(title, text, confirmText = "Confirm") {
        if (typeof Swal == "undefined") return confirm(title);
        const result = await Swal.fire({
            background: "transparent",
            buttonsStyling: false,
            width: '450px',
            html: `
                <div class="flex flex-col text-center">
                    <div class="flex justify-center mb-3">
                        <div class="flex items-center justify-center w-16 h-16 rounded-full bg-orange-100 text-orange-600">
                            <i class="ph ph-warning-circle text-3xl"></i>
                        </div>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800">${title}</h3>
                    <p class="text-[14px] text-gray-700 mt-1">${text}</p>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: "Cancel",
            customClass: {
                popup: "!rounded-xl !shadow-lg !p-6 !bg-white !border-2 !border-orange-400 shadow-[0_0_15px_#ffb34780]",
                confirmButton: "!bg-orange-600 !text-white !px-5 !py-2.5 !rounded-lg hover:!bg-orange-700 !mx-2 !font-semibold !text-base",
                cancelButton: "!bg-gray-200 !text-gray-800 !px-5 !py-2.5 !rounded-lg hover:!bg-gray-300 !mx-2 !font-semibold !text-base",
                actions: "!mt-4"
            },
        });
        return result.isConfirmed;
    }

    // Constants & Global State
    const API_BASE = API_BASE_PATH;
    const modal = document.getElementById("importModal");
    const openBtn = document.getElementById("bulkImportBtn");
    const closeBtn = document.getElementById("closeImportModal");
    const cancelBtn = document.getElementById("cancelImport");
    const searchInput = document.getElementById("userSearchInput");
    const userTableBody = document.getElementById("userTableBody");
    const addUserModal = document.getElementById("addUserModal");
    const openAddUserBtn = document.getElementById("addUserBtn");
    const closeAddUserBtn = document.getElementById("closeAddUserModal");
    const cancelAddUserBtn = document.getElementById("cancelAddUser");
    const editUserModal = document.getElementById("editUserModal");
    const closeEditUserBtn = document.getElementById("closeEditUserModal");
    const cancelEditUserBtn = document.getElementById("cancelEditUser");
    const bulkImportForm = document.getElementById("bulkImportForm");
    const fileInput = document.getElementById("csvFile");
    const importMessage = document.getElementById("importMessage");
    const modulesSection = document.getElementById("modulesSection");
    const multiSelectBtn = document.getElementById("multiSelectBtn");
    const multiSelectActions = document.getElementById("multiSelectActions");
    const selectAllBtn = document.getElementById("selectAllBtn");
    const cancelSelectionBtn = document.getElementById("cancelSelectionBtn");
    const multiDeleteBtn = document.getElementById("multiDeleteBtn");
    const multiAllowEditBtn = document.getElementById("multiAllowEditBtn");
    const selectionCount = document.getElementById("selectionCount");
    const userRoleValueEl = document.getElementById("userRoleDropdownValue");

    let users = [];
    let selectedRole = "All Roles";
    let selectedStatus = "All Status";
    let selectedCampus = null; // Smart Filter for Superadmin/Global Admin
    let currentEditingUserId = null;
    let isMultiSelectMode = false;
    let selectedUsers = new Set();
    let currentPage = 1;
    const limit = 10;
    let totalUsers = 0;
    let totalPages = 1;
    let isLoading = false;

    // Load state from session storage
    try {
        const savedPage = sessionStorage.getItem('userManagementPage');
        if (savedPage) {
            const parsedPage = parseInt(savedPage, 10);
            if (!isNaN(parsedPage) && parsedPage > 0) currentPage = parsedPage;
        }
    } catch (e) { console.error(e); }

    // Table Rendering & Pagination
    function updateUserCounts(usersLength, totalCountNum, page, perPage) {
        const resultsIndicator = document.getElementById("resultsIndicator");
        if (resultsIndicator) {
            if (totalCountNum === 0) {
                resultsIndicator.innerHTML = `Showing <span class="font-medium text-gray-800">0</span> of <span class="font-medium text-gray-800">0</span> users`;
            } else {
                const startItem = (page - 1) * perPage + 1;
                const endItem = (page - 1) * perPage + usersLength;
                resultsIndicator.innerHTML = `Showing <span class="font-medium text-gray-800">${startItem}-${endItem}</span> of <span class="font-medium text-gray-800">${totalCountNum.toLocaleString()}</span> users`;
            }
        }
    }

    function renderPagination(totalPages, page) {
        const paginationControls = document.getElementById("paginationControls");
        const paginationList = document.getElementById("paginationList");
        if (!paginationControls || !paginationList) return;

        if (totalPages <= 1) {
            paginationControls.classList.add("hidden");
            return;
        }

        paginationControls.classList.remove("hidden");
        paginationList.innerHTML = '';

        const createPageLink = (type, text, pageNum, isDisabled = false, isActive = false) => {
            const li = document.createElement("li");
            const a = document.createElement("a");
            a.href = "#";
            a.setAttribute("data-page", String(pageNum));
            let baseClasses = `flex items-center justify-center min-w-[32px] h-9 text-sm font-medium transition-all duration-200`;
            if (type === "prev" || type === "next") {
                a.innerHTML = text;
                baseClasses += ` text-gray-700 hover:text-orange-600 px-3`;
                if (isDisabled) baseClasses += ` opacity-50 cursor-not-allowed pointer-events-none`;
            } else if (type === "ellipsis") {
                a.textContent = text;
                baseClasses += ` text-gray-400 cursor-default px-2`;
            } else {
                a.textContent = text;
                if (isActive) baseClasses += ` text-white bg-orange-600 rounded-full shadow-sm px-3`;
                else baseClasses += ` text-gray-700 hover:text-orange-600 hover:bg-orange-100 rounded-full px-3`;
            }
            a.className = baseClasses;
            li.appendChild(a);
            paginationList.appendChild(li);
        };

        createPageLink("prev", `<i class="flex ph ph-caret-left text-lg"></i> Previous`, page - 1, page === 1);
        const windowSize = 1;
        let pagesToShow = new Set([1, totalPages, page]);
        for (let i = 1; i <= windowSize; i++) {
            if (page - i > 0) pagesToShow.add(page - i);
            if (page + i <= totalPages) pagesToShow.add(page + i);
        }
        const sortedPages = [...pagesToShow].sort((a, b) => a - b);
        let lastPage = 0;
        for (const p of sortedPages) {
            if (p > lastPage + 1) createPageLink("ellipsis", "…", "...", true);
            createPageLink("number", p, p, false, p === page);
            lastPage = p;
        }
        createPageLink("next", `Next <i class="flex ph ph-caret-right text-lg"></i>`, page + 1, page === totalPages);

        paginationList.onclick = async (e) => {
            e.preventDefault();
            const target = e.target.closest('a[data-page]');
            if (!target || isLoading) return;
            const pageStr = target.dataset.page;
            if (pageStr === '...') return;
            const pageNum = parseInt(pageStr, 10);
            if (pageNum !== currentPage) {
                if (isMultiSelectMode && selectedUsers.size > 0) {
                    const ok = await showConfirmationModal("Clear Selection?", "Navigating will clear your selection. Continue?");
                    if (!ok) return;
                    selectedUsers.clear();
                    updateMultiSelectButtons();
                }
                loadUsers(pageNum);
            }
        };
    }

    // Role-specific field handling
    async function loadCourses(targetSelectId, selectedValue = null) {
        const select = document.getElementById(targetSelectId);
        if (!select) return;
        select.innerHTML = '<option value="">Loading Courses...</option>';
        try {
            const res = await fetch(`${API_BASE}/getAllCourses`);
            const data = await res.json();
            select.innerHTML = '<option value="">Select Course/Program</option>';
            if (data.success && data.courses) {
                data.courses.forEach(c => select.add(new Option(`${c.course_code} - ${c.course_title}`, c.course_id)));
                if (selectedValue) select.value = selectedValue;
            }
        } catch (err) { console.error(err); select.innerHTML = '<option value="">Error</option>'; }
    }

    function updateRoleFields(role, selectFieldId, selectedValue = null) {
        const normalized = role.toLowerCase();
        const wrapper = document.getElementById('addUserSingleSelectWrapper');
        const studentWrapper = document.getElementById('addUserStudentFieldsWrapper');
        if (!wrapper) return;

        wrapper.classList.add('hidden');
        if (studentWrapper) studentWrapper.classList.add('hidden');

        if (normalized === 'student') {
            wrapper.classList.remove('hidden');
            if (studentWrapper) studentWrapper.classList.remove('hidden');
            loadCourses(selectFieldId, selectedValue);
        }
    }

    function togglePermissionsUI(container, role, userModules = []) {
        if (!container) return;
        const normalized = role.toLowerCase();
        const isPrivileged = ['admin', 'librarian'].includes(normalized);
        container.classList.toggle("hidden", !isPrivileged);
        if (!isPrivileged) return;

        // Internal permission wrappers
        const wrappers = {
            'user management': container.querySelector('[id*="UserManagementModuleWrapper"]'),
            'restore users': container.querySelector('[id*="RestoreUserModuleWrapper"]'),
            'bulk delete queue': container.querySelector('[id*="BulkDeleteQueueModuleWrapper"]')
        };

        Object.values(wrappers).forEach(w => w?.classList.add('hidden'));

        if (normalized === 'admin') {
            Object.values(wrappers).forEach(w => w?.classList.remove('hidden'));
        }

        container.querySelectorAll('input[type="checkbox"]').forEach(cb => {
            cb.checked = userModules.some(m => m.toLowerCase().trim() === cb.value.toLowerCase().trim());
        });
    }

    // Modal Events
    function closeModal(el) {
        el?.classList.add("hidden");
        document.body.classList.remove("overflow-hidden");
    }

    window.selectUserRole = (el, val) => {
        if (userRoleValueEl) userRoleValueEl.textContent = val;
        updateRoleFields(val, 'addUserSelectField');
        if (modulesSection) togglePermissionsUI(modulesSection, val);
        setActiveOption("userRoleDropdownMenu", el);
    };

    window.selectEditRole = (el, val) => {
        const valEl = document.getElementById("editRoleDropdownValue");
        if (valEl) valEl.textContent = val;
        const container = document.getElementById("editPermissionsContainer");
        if (container) togglePermissionsUI(container, val);
        setActiveOption("editRoleDropdownMenu", el);
    };

    // Main Actions
    async function loadUsers(page = 1, showSpinner = true) {
        if (isLoading) return;
        isLoading = true;
        currentPage = page;
        if (showSpinner) showLoadingModal("Loading Users...");

        const search = searchInput?.value.trim() || "";
        const params = new URLSearchParams({
            search,
            role: selectedRole === 'All Roles' ? '' : selectedRole,
            status: selectedStatus === 'All Status' ? '' : selectedStatus,
            campus_id: selectedCampus || '',
            limit,
            offset: (page - 1) * limit
        });

        try {
            const res = await fetch(`${API_BASE}/pagination?${params.toString()}`);
            const data = await res.json();
            if (showSpinner) Swal.close();

            if (data.success) {
                totalUsers = data.totalCount;
                totalPages = Math.ceil(totalUsers / limit) || 1;
                users = data.users;
                renderTable(users);
                renderPagination(totalPages, currentPage);
                updateUserCounts(users.length, totalUsers, page, limit);
                sessionStorage.setItem('userManagementPage', currentPage);
            }
        } catch (err) {
            if (showSpinner) Swal.close();
            showErrorToast("Error", "Failed to load users.");
        } finally { isLoading = false; }
    }

    window.selectCampus = (el, name, id) => {
        const valEl = document.getElementById("campusDropdownValue");
        if (valEl) valEl.textContent = name;
        selectedCampus = id;
        setActiveOption("campusDropdownMenu", el);
        currentPage = 1;
        loadUsers(1);
    };

    window.selectRole = (el, val) => {
        const valEl = document.getElementById("roleDropdownValue");
        if (valEl) valEl.textContent = val;
        selectedRole = val;
        setActiveOption("roleDropdownMenu", el);
        currentPage = 1;
        loadUsers(1);
    };

    window.selectStatus = (el, val) => {
        const valEl = document.getElementById("statusDropdownValue");
        if (valEl) valEl.textContent = val;
        selectedStatus = val;
        setActiveOption("statusDropdownMenu", el);
        currentPage = 1;
        loadUsers(1);
    };

    function renderTable(usersToRender) {
        if (!userTableBody) return;
        userTableBody.innerHTML = "";

        // Multi-select header sync
        const headerRow = document.querySelector('thead tr');
        if (headerRow) {
            const existing = headerRow.querySelector('.multi-select-header');
            if (isMultiSelectMode && !existing) {
                const th = document.createElement('th');
                th.className = 'px-4 py-3 multi-select-header';
                headerRow.prepend(th);
            } else if (!isMultiSelectMode && existing) {
                existing.remove();
            }
        }

        if (!usersToRender.length) {
            const cols = headerRow?.children.length || 6;
            userTableBody.innerHTML = `<tr><td colspan="${cols}" class="text-center py-10 text-gray-500">No users found.</td></tr>`;
            return;
        }

        usersToRender.forEach(user => {
            const row = document.createElement("tr");
            const isSelected = selectedUsers.has(user.user_id);
            const isActive = user.is_active == 1;
            
            row.className = `transition-colors ${isSelected ? "bg-orange-100" : (isActive ? "bg-white" : "bg-gray-50 text-gray-500")}`;
            if (isMultiSelectMode) {
                row.classList.add("cursor-pointer");
                row.onclick = () => {
                    if (selectedUsers.has(user.user_id)) selectedUsers.delete(user.user_id);
                    else selectedUsers.add(user.user_id);
                    renderTable(users);
                    updateMultiSelectButtons();
                };
            }

            const name = [user.first_name, user.middle_name, user.last_name].filter(Boolean).join(' ');
            const roleBadge = getRoleBadge(user.role, user.campus_id);
            const statusBadge = getStatusBadge(isActive ? "Active" : "Inactive");

            let actions = `
                <button class="editBtn border border-orange-200 text-gray-600 px-2 py-1.5 rounded-md text-xs hover:bg-orange-50"><i class="ph ph-note-pencil"></i> Edit</button>
                <button class="deleteBtn bg-red-600 text-white px-2 py-1.5 rounded-md text-xs hover:bg-red-700"><i class="ph ph-trash"></i> Delete</button>
            `;
            if (user.role.toLowerCase() === 'student') {
                actions += `<button class="allowEditBtn border border-blue-500 text-blue-600 px-2 py-1.5 rounded-md text-xs hover:bg-blue-50">Allow Edit</button>`;
            }

            row.innerHTML = `
                ${isMultiSelectMode ? `<td class="px-4 py-3"><input type="checkbox" class="accent-orange-500" ${isSelected ? "checked" : ""}></td>` : ""}
                <td class="px-4 py-3"><strong>${name}</strong><br><small>${user.username}</small></td>
                <td class="px-4 py-3">${user.email || 'N/A'}</td>
                <td class="px-4 py-3">${roleBadge}</td>
                <td class="px-4 py-3"><span class="statusToggle cursor-pointer">${statusBadge}</span></td>
                <td class="px-4 py-3">${new Date(user.created_at).toLocaleDateString()}</td>
                <td class="px-4 py-3">${isMultiSelectMode ? "" : `<div class="flex gap-2">${actions}</div>`}</td>
            `;

            // Row actions
            row.querySelector('.editBtn')?.addEventListener('click', (e) => { e.stopPropagation(); openEditModal(user); });
            row.querySelector('.deleteBtn')?.addEventListener('click', (e) => { e.stopPropagation(); deleteUser(user); });
            row.querySelector('.statusToggle')?.addEventListener('click', (e) => { e.stopPropagation(); toggleStatus(user); });
            row.querySelector('.allowEditBtn')?.addEventListener('click', (e) => { e.stopPropagation(); allowEdit(user); });

            userTableBody.appendChild(row);
        });
    }

    async function openEditModal(user) {
        currentEditingUserId = user.user_id;
        showLoadingModal("Fetching Details...");
        try {
            const res = await fetch(`${API_BASE}/get/${user.user_id}`);
            const data = await res.json();
            Swal.close();
            if (!data.user) return;

            const u = data.user;
            document.getElementById("editFirstName").value = u.first_name || "";
            document.getElementById("editMiddleName").value = u.middle_name || "";
            document.getElementById("editLastName").value = u.last_name || "";
            document.getElementById("editEmail").value = u.email || "";
            document.getElementById("editUsername").value = u.username || "";
            document.getElementById("editCampusField").value = u.campus_id || "";
            document.getElementById("editRoleDropdownValue").textContent = u.role;
            document.getElementById("editStatusDropdownValue").textContent = u.is_active == 1 ? "Active" : "Inactive";
            
            updateRoleFields(u.role, 'editCourseId', data.extra?.course_id);
            if (u.role.toLowerCase() === 'student') {
                document.getElementById("editYearLevel").value = data.extra?.year_level || "1";
                document.getElementById("editSection").value = data.extra?.section || "";
            }

            const permContainer = document.getElementById("editPermissionsContainer");
            if (permContainer) togglePermissionsUI(permContainer, u.role, data.modules || []);

            editUserModal.classList.remove("hidden");
            document.body.classList.add("overflow-hidden");
        } catch (e) { Swal.close(); showErrorToast("Error", "Could not load user details."); }
    }

    async function deleteUser(user) {
        const ok = await showConfirmationModal("Delete User?", `Confirm deletion of ${user.first_name} ${user.last_name}.`);
        if (!ok) return;
        showLoadingModal("Deleting...");
        try {
            const res = await fetch(`${API_BASE}/delete/${user.user_id}`, { method: 'POST' });
            const data = await res.json();
            Swal.close();
            if (data.success) { showSuccessToast("Deleted"); loadUsers(currentPage, false); }
            else showErrorToast("Failed", data.message);
        } catch (e) { Swal.close(); }
    }

    async function toggleStatus(user) {
        const newStatus = user.is_active == 1 ? "Inactive" : "Active";
        const ok = await showConfirmationModal("Change Status?", `Set status to ${newStatus}?`);
        if (!ok) return;
        showLoadingModal("Updating...");
        try {
            const res = await fetch(`${API_BASE}/toggleStatus/${user.user_id}`, { method: 'POST' });
            const data = await res.json();
            Swal.close();
            if (data.success) { showSuccessToast("Updated"); loadUsers(currentPage, false); }
        } catch (e) { Swal.close(); }
    }

    async function allowEdit(user) {
        const ok = await showConfirmationModal("Allow Edit?", "Grant temporary profile edit access?");
        if (!ok) return;
        showLoadingModal("Granting...");
        try {
            const res = await fetch(`${API_BASE}/allowEdit/${user.user_id}`, { method: 'POST' });
            const data = await res.json();
            Swal.close();
            if (data.success) showSuccessToast("Granted");
        } catch (e) { Swal.close(); }
    }

    // Event Listeners
    if (openBtn) openBtn.onclick = () => modal?.classList.remove("hidden");
    if (closeBtn) closeBtn.onclick = () => closeModal(modal);
    if (cancelBtn) cancelBtn.onclick = () => closeModal(modal);
    if (openAddUserBtn) openAddUserBtn.onclick = () => addUserModal?.classList.remove("hidden");
    if (closeAddUserBtn) closeAddUserBtn.onclick = () => closeModal(addUserModal);
    if (cancelAddUserBtn) cancelAddUserBtn.onclick = () => closeModal(addUserModal);
    if (closeEditUserBtn) closeEditUserBtn.onclick = () => closeModal(editUserModal);
    if (cancelEditUserBtn) cancelEditUserBtn.onclick = () => closeModal(editUserModal);

    const saveAddBtn = document.getElementById("confirmAddUser");
    if (saveAddBtn) saveAddBtn.onclick = async () => {
        const role = userRoleValueEl.textContent.trim();
        const payload = {
            first_name: document.getElementById("addFirstName").value,
            middle_name: document.getElementById("addMiddleName").value,
            last_name: document.getElementById("addLastName").value,
            username: document.getElementById("addUsername").value,
            campus_id: document.getElementById("addCampus").value,
            role,
            modules: Array.from(document.querySelectorAll('input[name="modules[]"]:checked')).map(cb => cb.value)
        };
        if (role.toLowerCase() === 'student') {
            payload.course_id = document.getElementById("addUserSelectField").value;
            payload.year_level = document.getElementById("addYearLevel").value;
            payload.section = document.getElementById("addSection").value;
        }
        showLoadingModal("Saving...");
        try {
            const res = await fetch(`${API_BASE}/add`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            Swal.close();
            if (data.success) { showSuccessToast("Added"); closeModal(addUserModal); loadUsers(1); }
            else showErrorToast("Failed", data.message);
        } catch (e) { Swal.close(); }
    };

    const saveEditBtn = document.getElementById("saveEditUser");
    if (saveEditBtn) saveEditBtn.onclick = async () => {
        const role = document.getElementById("editRoleDropdownValue").textContent.trim();
        const payload = {
            first_name: document.getElementById("editFirstName").value,
            middle_name: document.getElementById("editMiddleName").value,
            last_name: document.getElementById("editLastName").value,
            email: document.getElementById("editEmail").value,
            username: document.getElementById("editUsername").value,
            campus_id: document.getElementById("editCampusField").value,
            role,
            is_active: document.getElementById("editStatusDropdownValue").textContent.trim().toLowerCase() === 'active' ? 1 : 0,
            modules: Array.from(document.querySelectorAll('input[name="editModules[]"]:checked')).map(cb => cb.value)
        };
        if (role.toLowerCase() === 'student') {
            payload.course_id = document.getElementById("editCourseId").value;
            payload.year_level = document.getElementById("editYearLevel").value;
            payload.section = document.getElementById("editSection").value;
        }
        if (document.getElementById("togglePassword")?.checked) {
            payload.password = document.getElementById("editPassword").value;
        }
        showLoadingModal("Saving...");
        try {
            const res = await fetch(`${API_BASE}/update/${currentEditingUserId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            Swal.close();
            if (data.success) { showSuccessToast("Updated"); closeModal(editUserModal); loadUsers(currentPage, false); }
        } catch (e) { Swal.close(); }
    };

    // Helper UI functions
    function setActiveOption(containerId, el) {
        document.querySelectorAll(`#${containerId} div`).forEach(i => i.classList.remove("bg-orange-50", "font-semibold"));
        el?.classList.add("bg-orange-50", "font-semibold");
    }

    function getRoleBadge(role, campusId = null) {
        const r = role.toLowerCase();
        let color = "bg-gray-500";
        let displayRole = role;

        if (r === 'student') {
            color = "bg-green-500";
        } else if (r === 'admin') {
            color = "bg-orange-600";
            displayRole = campusId ? 'Local Admin' : 'Global Admin';
        } else if (r === 'librarian') {
            color = "bg-amber-500";
        }

        return `<span class="${color} text-white px-2 py-1 rounded text-xs">${displayRole}</span>`;
    }

    function getStatusBadge(status) {
        const color = status.toLowerCase() === 'active' ? 'bg-green-500' : 'bg-gray-400';
        return `<span class="${color} text-white px-2 py-1 rounded text-xs">${status}</span>`;
    }

    function updateMultiSelectButtons() {
        const count = selectedUsers.size;
        if (selectionCount) selectionCount.textContent = count;
        multiSelectBtn?.classList.toggle('hidden', isMultiSelectMode);
        multiSelectActions?.classList.toggle('hidden', !isMultiSelectMode);
        multiDeleteBtn?.classList.toggle('hidden', count === 0);
        multiAllowEditBtn?.classList.toggle('hidden', count === 0);
    }

    if (multiDeleteBtn) multiDeleteBtn.onclick = async () => {
        const ids = [...selectedUsers];
        if (!ids.length) return;
        const ok = await showConfirmationModal(`Delete ${ids.length} Users?`, "This action cannot be undone.");
        if (!ok) return;
        showLoadingModal("Deleting...");
        try {
            const res = await fetch(`${API_BASE}/deleteMultiple`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_ids: ids })
            });
            const data = await res.json();
            Swal.close();
            if (data.success) {
                showSuccessToast("Deleted");
                isMultiSelectMode = false;
                selectedUsers.clear();
                updateMultiSelectButtons();
                loadUsers(1, false);
            }
        } catch (e) { Swal.close(); }
    };

    if (multiAllowEditBtn) multiAllowEditBtn.onclick = async () => {
        const ids = [...selectedUsers];
        if (!ids.length) return;
        const ok = await showConfirmationModal(`Allow Edit for ${ids.length} students?`, "Continue?");
        if (!ok) return;
        showLoadingModal("Processing...");
        try {
            const res = await fetch(`${API_BASE}/allowMultipleEdit`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_ids: ids })
            });
            const data = await res.json();
            Swal.close();
            if (data.success) {
                showSuccessToast("Granted");
                isMultiSelectMode = false;
                selectedUsers.clear();
                updateMultiSelectButtons();
                loadUsers(currentPage, false);
            }
        } catch (e) { Swal.close(); }
    };

    let searchTimeout;
    if (searchInput) {
        searchInput.oninput = () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentPage = 1;
                loadUsers(1, false);
            }, 500);
        };
    }

    if (selectAllBtn) selectAllBtn.onclick = () => {
        const allVisibleIds = users.map(u => u.user_id);
        const allSelected = allVisibleIds.every(id => selectedUsers.has(id));
        if (allSelected) allVisibleIds.forEach(id => selectedUsers.delete(id));
        else allVisibleIds.forEach(id => selectedUsers.add(id));
        renderTable(users);
        updateMultiSelectButtons();
    };

    // Init
    loadUsers(currentPage);
    // Simple dropdown toggle setup
    ["roleDropdownBtn", "statusDropdownBtn", "campusDropdownBtn", "userRoleDropdownBtn", "editRoleDropdownBtn", "editStatusDropdownBtn"].forEach(id => {
        const btn = document.getElementById(id);
        const menu = btn?.nextElementSibling;
        btn?.addEventListener('click', (e) => { e.stopPropagation(); menu?.classList.toggle('hidden'); });
    });
    document.addEventListener('click', () => document.querySelectorAll('[id$="Menu"]').forEach(m => m.classList.add('hidden')));
});