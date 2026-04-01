window.addEventListener("DOMContentLoaded", () => {
    // --- SweetAlert Helper Functions (Final Shared Design) ---

    // 1. SUCCESS/ADD/UPDATE Toast (Themed Border)
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

    // 2. ERROR/VALIDATION Toast (Red Theme)
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

    // 3. LOADING Modal (Orange Theme)
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

    // 4. CONFIRMATION Modal (Orange Border Final)
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
                // FINAL FIX: Orange Border + White BG + Orange Shadow (Matching the theme)
                popup:
                    "!rounded-xl !shadow-lg !p-6 !bg-white !border-2 !border-orange-400 shadow-[0_0_15px_#ffb34780]",

                // Confirm Button (Orange, Large, Bold)
                confirmButton:
                    "!bg-orange-600 !text-white !px-5 !py-2.5 !rounded-lg hover:!bg-orange-700 !mx-2 !font-semibold !text-base",
                // Cancel Button (Gray, Large, Bold)
                cancelButton:
                    "!bg-gray-200 !text-gray-800 !px-5 !py-2.5 !rounded-lg hover:!bg-gray-300 !mx-2 !font-semibold !text-base",

                actions: "!mt-4"
            },
        });
        return result.isConfirmed;
    }
    // --- End SweetAlert Helper Functions ---

    const programs = {};
    const departments = [];

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
    const addUserUserManagementModuleWrapper = document.getElementById("addUserUserManagementModuleWrapper");

    // BAGONG DAGDAG: ID para sa Edit Modal
    const editUserUserManagementModuleWrapper = document.getElementById("editUserUserManagementModuleWrapper");

    //updated
    const multiSelectBtn = document.getElementById("multiSelectBtn");
    const multiSelectActions = document.getElementById("multiSelectActions");
    const selectAllBtn = document.getElementById("selectAllBtn");
    const cancelSelectionBtn = document.getElementById("cancelSelectionBtn");
    const multiDeleteBtn = document.getElementById("multiDeleteBtn");
    const multiAllowEditBtn = document.getElementById("multiAllowEditBtn");
    const selectionCount = document.getElementById("selectionCount");
    //end
    const userRoleValueEl = document.getElementById("userRoleDropdownValue");

    let allUsers = [];
    let users = [];
    let selectedRole = "All Roles";
    let selectedStatus = "All Status";
    let currentEditingUserId = null;
    //updated
    let isMultiSelectMode = false;
    let selectedUsers = new Set();
    //end
    let currentPage = 1;
    const limit = 10;
    let totalUsers = 0;
    let totalPages = 1;
    let isLoading = false;
    let searchDebounce;

    try {
        const savedPage = sessionStorage.getItem('userManagementPage');
        if (savedPage) {
            const parsedPage = parseInt(savedPage, 10);
            if (!isNaN(parsedPage) && parsedPage > 0) {
                currentPage = parsedPage;
            } else {
                sessionStorage.removeItem('userManagementPage');
            }
        }
    } catch (e) {
        console.error("SessionStorage Error:", e);
        currentPage = 1;
    }

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
                if (isActive) {
                    baseClasses += ` text-white bg-orange-600 rounded-full shadow-sm px-3`;
                } else {
                    baseClasses += ` text-gray-700 hover:text-orange-600 hover:bg-orange-100 rounded-full px-3`;
                }
            }
            a.className = baseClasses;
            li.appendChild(a);
            paginationList.appendChild(li);
        };

        createPageLink("prev", `<i class="flex ph ph-caret-left text-lg"></i> Previous`, page - 1, page === 1);
        const window = 1;
        let pagesToShow = new Set([1, totalPages, page]);
        for (let i = 1; i <= window; i++) {
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

        paginationList.addEventListener('click', async (e) => { //Updated nalag lagay ng async
            e.preventDefault();
            if (isLoading) return;
            const target = e.target.closest('a[data-page]');
            if (!target) return;
            const pageStr = target.dataset.page;
            if (pageStr === '...') return;
            const pageNum = parseInt(pageStr, 10);
            if (!isNaN(pageNum) && pageNum !== currentPage) {
                 // Gumamit ng default behavior (may loading modal) kapag nagpapalit ng page number
                 //updated
                if (isMultiSelectMode && selectedUsers.size > 0) {
                    const isConfirmed = await showConfirmationModal(
                        "Clear Selection?",
                        "Navigating to another page will clear your current selection. Do you want to continue?",
                        "Yes, Continue"
                    );
                    if (isConfirmed) {
                        selectedUsers.clear();
                        updateMultiSelectButtons();
                        loadUsers(pageNum);
                    }
                } else {
                    loadUsers(pageNum);
                }
                //end
            }
        });
    }


    async function loadCampuses(targetSelectId, selectedValue = null) {
        const select = document.getElementById(targetSelectId);
        if (!select) return;

        select.innerHTML = '<option value="">Loading Campuses...</option>';

        try {
            const res = await fetch('api/campuses/all');
            const data = await res.json();

            select.innerHTML = '<option value="">Select Campus</option>';

            if (data.success && Array.isArray(data.campuses) && data.campuses.length > 0) {
                data.campuses.forEach(campus => {
                    const option = new Option(campus.campus_name, campus.campus_id); // Corrected line
                    select.add(option);
                });

                if (selectedValue) {
                    select.value = selectedValue;
                }
            } else {
                select.innerHTML = '<option value="">No Campuses Found</option>';
            }

        } catch (err) {
            console.error("Error loading campuses:", err);
            select.innerHTML = '<option value="">Error loading campuses</option>';
        }
    }

    function updateProgramDepartmentDropdown(role, selectedValue = null) {
        const wrapper = document.getElementById('addUserSingleSelectWrapper');
        const label = document.getElementById('addUserSelectLabel');
        const studentFieldsWrapper = document.getElementById('addUserStudentFieldsWrapper');

        if (!wrapper || !label) return;

        const normalizedRole = (role || "").trim().toLowerCase();

        wrapper.classList.add('hidden');
        if (studentFieldsWrapper) studentFieldsWrapper.classList.add('hidden');

        // Always load campuses for the general field since it replaced gender
        loadCampuses('addCampus');

        if (normalizedRole === 'student') {
            label.innerHTML = 'Course/Program <span class="text-red-500">*</span>';
            wrapper.classList.remove('hidden');
            loadCoursesForStudent(selectedValue);

        } else {
            wrapper.classList.add('hidden');
            const select = document.getElementById('addUserSelectField');
            if (select) select.innerHTML = '';
        }
    }

    function closeModal(modalEl) {
        if (!modalEl) return;
        modalEl.classList.add("hidden");
        document.body.classList.remove("overflow-hidden");
    }

    function buildFullName(firstName, middleName, lastName) {
        return [firstName, middleName, lastName].filter(Boolean).join(' ');
    }

    function toggleModules(container, role, userModules = []) {
        if (!container) return;
        const normalizedRole = (role || "").trim().toLowerCase();
        // console.log("toggleModules:", normalizedRole, userModules); // For debugging

        container.classList.add("hidden");
        const userMgmtWrapper = container.querySelector('#addUserUserManagementModuleWrapper') || document.getElementById('addUserUserManagementModuleWrapper');
        const restoreUserWrapper = container.querySelector('#addUserRestoreUserModuleWrapper') || document.getElementById('addUserRestoreUserModuleWrapper');

        if (userMgmtWrapper) userMgmtWrapper.classList.add('hidden');
        if (restoreUserWrapper) restoreUserWrapper.classList.add('hidden');

        if (normalizedRole === "admin" || normalizedRole === "librarian" || normalizedRole === "campus admin") {
            container.classList.remove("hidden");

            container.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                cb.checked = userModules.some(m => m.toLowerCase().trim() === cb.value.toLowerCase().trim()) || false;
            });

            if (normalizedRole === 'admin') {
                if (userMgmtWrapper) userMgmtWrapper.classList.remove('hidden');
                if (restoreUserWrapper) restoreUserWrapper.classList.remove('hidden');
            } else if (normalizedRole === 'librarian' || normalizedRole === 'campus admin') {
                if (userMgmtWrapper) userMgmtWrapper.classList.remove('hidden');
            }
        } else {
            // Kung hindi admin/librarian, siguraduhing naka-uncheck lahat
            container.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
        }
    }


    async function loadCourses(targetSelectId, selectedValue = null) {
        const select = document.getElementById(targetSelectId);
        if (!select) return;

        select.innerHTML = '<option value="">Loading Courses...</option>';

        try {
            const res = await fetch('api/campus_admin/userManagement/getAllCourses');
            const data = await res.json();

            select.innerHTML = '<option value="">Select Course/Program</option>';

            if (data.success && Array.isArray(data.courses) && data.courses.length > 0) {
                data.courses.forEach(course => {
                    const option = new Option(`${course.course_code} - ${course.course_title}`, course.course_id);
                    select.add(option);
                });

                if (selectedValue) {
                    select.value = selectedValue;
                }
            } else {
                select.innerHTML = '<option value="">No Courses Found</option>';
            }

        } catch (err) {
            console.error("Error loading courses:", err);
            select.innerHTML = '<option value="">Error loading courses</option>';
        }
    }

    async function loadCoursesForStudent(selectedValue = null) {
        await loadCourses('addUserSelectField', selectedValue);
    }

    async function loadDepartments(selectedValue = null) {
        const select = document.getElementById('addUserSelectField');
        if (!select) return;

        select.innerHTML = '<option value="">Loading Colleges...</option>';

        try {
            const res = await fetch('api/campus_admin/userManagement/getColleges');
            const data = await res.json();

            select.innerHTML = '<option value="">Select College/Department</option>';

            if (data.success && Array.isArray(data.colleges) && data.colleges.length > 0) {
                data.colleges.forEach(college => {
                    const option = new Option(`${college.college_code} - ${college.college_name}`, college.college_id);
                    select.add(option);
                });

                if (selectedValue) {
                    select.value = selectedValue;
                }
            } else {
                select.innerHTML = '<option value="">No Colleges Found</option>';
            }

        } catch (err) {
            console.error("Error loading colleges for faculty:", err);
            select.innerHTML = '<option value="">Error loading colleges</option>';
        }
    }

    toggleModules(modulesSection, userRoleValueEl.textContent || "");

    window.selectUserRole = (el, val) => {
        const normalizedRole = (val || "").trim().toLowerCase();

        if (userRoleValueEl) userRoleValueEl.textContent = val;
        setActiveOption("userRoleDropdownMenu", el);

        toggleModules(modulesSection, normalizedRole);

        if (addUserUserManagementModuleWrapper) {
            if (normalizedRole === 'admin') {
                addUserUserManagementModuleWrapper.classList.remove('hidden');
            } else {
                addUserUserManagementModuleWrapper.classList.add('hidden');
            }
        }
        updateProgramDepartmentDropdown(normalizedRole);
    };

    window.selectEditRole = (el, val) => {
        const valueEl = document.getElementById("editRoleDropdownValue");
        if (valueEl) valueEl.textContent = val;
        const editModulesContainer = document.getElementById("editPermissionsContainer");
        const user = users.find(u => u.user_id === currentEditingUserId);
        // We might need to reload user data here if user object is stale, but we rely on `users` array for simplicity
        // For accurate module status on role change in the modal, we'd need more logic, 
        // but sticking to the current structure, we just toggle visibility based on the *new* role selected.
        const normalizedRole = (val || "").trim().toLowerCase();

        if (editModulesContainer) {
            if (normalizedRole === 'admin' || normalizedRole === 'librarian') {
                editModulesContainer.classList.remove("hidden");

                if (editUserUserManagementModuleWrapper) {
                    if (normalizedRole === 'admin') {
                        editUserUserManagementModuleWrapper.classList.remove('hidden');
                    } else {
                        editUserUserManagementModuleWrapper.classList.add('hidden');
                    }
                }
            } else {
                editModulesContainer.classList.add("hidden");
            }
        }

        // This is necessary to visually mark the selected option
        setActiveOption("editRoleDropdownMenu", el);
    };


    if (openBtn) openBtn.addEventListener("click", () => {
        modal?.classList.remove("hidden");
        document.body.classList.add("overflow-hidden");
    });
    if (closeBtn) closeBtn.addEventListener("click", () => closeModal(modal));
    if (cancelBtn) cancelBtn.addEventListener("click", () => closeModal(modal));
    modal?.addEventListener("click", e => {
        if (e.target === modal) closeModal(modal);
    });

    fileInput?.addEventListener("change", () => {
        if (fileInput.files.length) {
            bulkImportForm?.requestSubmit();
        }
    });

    bulkImportForm?.addEventListener("submit", async (e) => {
        e.preventDefault();

        if (!fileInput) return showErrorToast("Import Error", "No file input found for bulk import.");
        if (!fileInput.files.length) return showErrorToast("Import Error", "Please pick a CSV file for bulk import.");

        showLoadingModal("Importing Users...", "Uploading and processing CSV file.");

        const formData = new FormData();
        formData.append("csv_file", fileInput.files[0]);

        try {
            const res = await fetch("api/campus_admin/userManagement/bulkImport", {
                method: "POST",
                body: formData
            });
            const data = await res.json();

            await new Promise(r => setTimeout(r, 1000)); // Minimum delay 1000ms
            Swal.close();

            if (data.success) {
                if (importMessage) {
                    importMessage.textContent = `Imported: ${data.imported} rows successfully!`;
                    importMessage.classList.remove("hidden");
                    setTimeout(() => importMessage.classList.add("hidden"), 5000);
                }
                showSuccessToast("Import Successful", `Successfully imported ${data.imported} rows!`);
                fileInput.value = "";
                closeModal(modal);
                // I-load ang users pagkatapos mag-import, gumagamit ng loading modal
                await loadUsers(1, true);
            } else {
                showErrorToast("Import Failed", data.message || "Failed to import CSV.");
            }
        } catch (err) {
            Swal.close();
            console.error("Error importing CSV:", err);
            showErrorToast("Import Failed", "An error occurred while importing the CSV file.");
        }
    });

    let searchTimeout;
    if (searchInput) {
        searchInput.addEventListener("input", e => {
            const query = e.target.value.trim();
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentPage = 1;
                try {
                    sessionStorage.removeItem('userManagementPage');
                } catch (e) { }
                // Hindi gagamit ng loading modal para sa search
                loadUsers(1, false);
            }, 500);
        });
    }

    function applyFilters() {
        currentPage = 1;
        try {
            sessionStorage.removeItem('userManagementPage');
        } catch (e) { }
        // Hindi gagamit ng loading modal para sa dropdown filters
        loadUsers(1, false);
    }

    function closeAddUserModal() {
        closeModal(addUserModal);
        document.getElementById("addFirstName") && (document.getElementById("addFirstName").value = "");
        document.getElementById("addMiddleName") && (document.getElementById("addMiddleName").value = "");
        document.getElementById("addLastName") && (document.getElementById("addLastName").value = "");
        document.getElementById("addUsername") && (document.getElementById("addUsername").value = "");
        document.getElementById("addUserSelectField") && (document.getElementById("addUserSelectField").value = ""); // Clear select field
        if (userRoleValueEl) userRoleValueEl.textContent = "Select Role";

        if (addUserUserManagementModuleWrapper) {
            addUserUserManagementModuleWrapper.classList.add('hidden');
        }
        if (modulesSection) {
            modulesSection.classList.add('hidden');
            modulesSection.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
        }
    }
    if (openAddUserBtn) openAddUserBtn.addEventListener("click", () => {
        addUserModal?.classList.remove("hidden");
        document.body.classList.add("overflow-hidden");
        // Reset role and fields when opening
        if (userRoleValueEl) userRoleValueEl.textContent = "Select Role";
        updateProgramDepartmentDropdown(""); // Hide program/department dropdown
    });
    [closeAddUserBtn, cancelAddUserBtn].forEach(btn => btn?.addEventListener("click", closeAddUserModal));
    addUserModal?.addEventListener("click", e => {
        if (e.target === addUserModal) closeAddUserModal();
    });

    function closeEditUserModal() {
        closeModal(editUserModal);
        currentEditingUserId = null;
        const changePasswordCheckbox = document.getElementById("togglePassword");
        if (changePasswordCheckbox) changePasswordCheckbox.checked = false;
        document.getElementById('passwordFields')?.classList.add('hidden');
        document.getElementById('editPassword') && (document.getElementById('editPassword').value = '');
        document.getElementById('confirmPassword') && (document.getElementById('confirmPassword').value = '');

        // BAGONG DAGDAG: Siguraduhin na nakatago rin ito pag-close
        if (editUserUserManagementModuleWrapper) {
            editUserUserManagementModuleWrapper.classList.add('hidden');
        }
        const editModulesContainer = document.getElementById("editPermissionsContainer");
        if (editModulesContainer) {
            editModulesContainer.classList.add('hidden');
        }
    }
    [closeEditUserBtn, cancelEditUserBtn].forEach(btn => btn?.addEventListener("click", closeEditUserModal));
    editUserModal?.addEventListener("click", e => {
        if (e.target === editUserModal) closeEditUserModal();
    });

    function setupDropdownToggle(buttonId, menuId) {
        const btn = document.getElementById(buttonId);
        const menu = document.getElementById(menuId);
        if (!btn || !menu) return;
        btn.addEventListener("click", (e) => {
            e.stopPropagation();
            menu.classList.toggle("hidden");
        });
    }

    setupDropdownToggle("roleDropdownBtn", "roleDropdownMenu");
    setupDropdownToggle("statusDropdownBtn", "statusDropdownMenu");
    setupDropdownToggle("userRoleDropdownBtn", "userRoleDropdownMenu");
    setupDropdownToggle("editRoleDropdownBtn", "editRoleDropdownMenu");
    setupDropdownToggle("editStatusDropdownBtn", "editStatusDropdownMenu");

    document.addEventListener("click", () => {
        document.querySelectorAll(".absolute.mt-1, .absolute.mt-1.w-full, .absolute.w-full").forEach(menu => menu.classList.add("hidden"));
    });

    function setActiveOption(containerId, selectedElement) {
        const items = document.querySelectorAll(
            `#${containerId} .dropdown-item, #${containerId} .role-item, #${containerId} .status-item, #${containerId} .user-role-item, #${containerId} .edit-role-item, #${containerId} .edit-status-item`
        );
        items.forEach(item => item.classList.remove("bg-orange-50", "font-semibold", "text-orange-700"));
        if (selectedElement && selectedElement.classList) {
            selectedElement.classList.add("bg-orange-50", "font-semibold", "text-orange-700");
        }
    }

    window.selectRole = (el, val) => {
        const valueEl = document.getElementById("roleDropdownValue");
        if (valueEl) valueEl.textContent = val;
        setActiveOption("roleDropdownMenu", el);
        selectedRole = val;
        applyFilters();
    };

    window.selectStatus = (el, val) => {
        const valueEl = document.getElementById("statusDropdownValue");
        if (valueEl) valueEl.textContent = val;
        setActiveOption("statusDropdownMenu", el);
        selectedStatus = val;
        applyFilters();
    };

    window.selectEditStatus = (el, val) => {
        const valueEl = document.getElementById("editStatusDropdownValue");
        if (valueEl) valueEl.textContent = val;
        setActiveOption("editStatusDropdownMenu", el);
    };

    const allRolesFirst = document.querySelector("#roleDropdownMenu .dropdown-item");
    if (allRolesFirst) {
        setActiveOption("roleDropdownMenu", allRolesFirst);
        const roleVal = allRolesFirst.textContent?.trim();
        if (roleVal) {
            const roleValueEl = document.getElementById("roleDropdownValue");
            if (roleValueEl) roleValueEl.textContent = roleVal;
            selectedRole = roleVal;
        }
    }
    const allStatusFirst = document.querySelector("#statusDropdownMenu .status-item");
    if (allStatusFirst) {
        setActiveOption("statusDropdownMenu", allStatusFirst);
        const statusVal = allStatusFirst.textContent?.trim();
        if (statusVal) {
            const statusValueEl = document.getElementById("statusDropdownValue");
            if (statusValueEl) statusValueEl.textContent = statusVal;
            selectedStatus = statusVal;
        }
    }

    // Binago ang loadUsers function para tanggapin ang isShowLoadingModal flag
    async function loadUsers(page = 1, isShowLoadingModal = true) {
        if (isLoading) return;
        isLoading = true;
        currentPage = page;

        // --- 1. SET START TIME FOR DELAY CHECK ---
        const startTime = Date.now();

        document.getElementById("paginationControls")?.classList.add('hidden');
        // document.getElementById("resultsIndicator").textContent = 'Loading...';
        if (userTableBody) userTableBody.innerHTML = "";

        // --- 2. SHOW SWEETALERT LOADING MODAL (KUNG KAILANGAN) ---
        if (isShowLoadingModal) {
            showLoadingModal("Loading User List...", "Fetching data for page " + page);
        }


        const offset = (page - 1) * limit;
        const search = document.getElementById("userSearchInput").value.trim();

        try {
            const params = new URLSearchParams({
                search: search,
                role: selectedRole === 'All Roles' ? '' : selectedRole,
                status: selectedStatus === 'All Status' ? '' : selectedStatus,
                limit: limit,
                offset: offset
            });

            const res = await fetch(`api/campus_admin/userManagement/pagination?${params.toString()}`);
            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
            const data = await res.json();

            // --- 3. CLOSE LOADING MODAL (SUCCESS PATH) ---
            if (isShowLoadingModal) { // Kung nagpakita ng modal, isara ito
                const elapsed = Date.now() - startTime;
                const minDelay = 500; // Minimum 500ms delay for table load
                if (elapsed < minDelay) await new Promise(r => setTimeout(r, minDelay - elapsed));
                if (typeof Swal != "undefined") Swal.close();
            }


            if (data.success && Array.isArray(data.users)) {
                totalUsers = data.totalCount;
                totalPages = Math.ceil(totalUsers / limit) || 1;

                if (page > totalPages && totalPages > 0) {
                    loadUsers(totalPages, isShowLoadingModal);
                    return;
                }

                users = data.users.map(u => ({
                    user_id: u.user_id,
                    first_name: u.first_name,
                    middle_name: u.middle_name,
                    last_name: u.last_name,
                    name: buildFullName(u.first_name, u.middle_name, u.last_name),
                    username: u.username,
                    email: u.email,
                    role: u.role,
                    status: u.is_active == 1 ? "Active" : "Inactive",
                    joinDate: new Date(u.created_at).toLocaleDateString(),
                    modules: u.modules || []
                }));

                renderTable(users);
                renderPagination(totalPages, currentPage);
                updateUserCounts(users.length, totalUsers, page, limit);
                try {
                    sessionStorage.setItem('userManagementPage', currentPage);
                } catch (e) {
                    console.error("SessionStorage Error:", e);
                }
            } else {
                throw new Error(data.message || "Invalid data format from server.");
            }
        } catch (err) {
            // --- 4. CLOSE LOADING MODAL (ERROR PATH) ---
            if (isShowLoadingModal && typeof Swal != "undefined") Swal.close(); // Kung nagpakita ng modal, isara ito

            console.error("Fetch users error:", err);
            if (userTableBody) userTableBody.innerHTML = `<tr data-placeholder="true"><td colspan="6" class="text-center text-red-500 py-10">Error loading users.</td></tr>`;
            updateUserCounts(0, 0, 1, limit);
            showErrorToast("Loading Failed", "Could not retrieve user list. Check your connection.");

            try {
                sessionStorage.removeItem('userManagementPage');
            } catch (e) { }
        } finally {
            isLoading = false;
        }
    }

    function renderTable(usersToRender) {
        if (!userTableBody) return;
        userTableBody.innerHTML = "";

        //updated
        const headerRow = document.querySelector('thead tr');
        if (headerRow) {
            const firstHeader = headerRow.querySelector('th');
            if (isMultiSelectMode) {
                if (!firstHeader.classList.contains('multi-select-header')) {
                    const th = document.createElement('th');
                    th.className = 'px-4 py-3 font-medium multi-select-header';
                    headerRow.insertBefore(th, firstHeader);
                }
            } else {
                if (firstHeader && firstHeader.classList.contains('multi-select-header')) {
                    firstHeader.remove();
                }
            }
        }
        //end

        if (!usersToRender.length) {
            //updated edit this code: userTableBody.innerHTML = `<tr data-placeholder="true"><td colspan="6" class="text-center text-gray-500 py-10">No users found.</td></tr>`;
            const colspan = document.querySelector('thead tr').children.length;
            userTableBody.innerHTML = `<tr data-placeholder="true"><td colspan="${colspan}" class="text-center text-gray-500 py-10">No users found.</td></tr>`;
            //end
            return;
        }

        usersToRender.forEach((user) => {
            const row = document.createElement("tr");
            
            //Updated edited this code : row.className = user.status === "Inactive" ? "bg-gray-50 text-gray-500" : "bg-white";
            const isSelected = selectedUsers.has(user.user_id);

            row.className = `transition-colors ${isSelected ? "bg-orange-100" : (user.status === "Inactive" ? "bg-gray-50 text-gray-500" : "bg-white")}`;
            if (isMultiSelectMode) {
                row.classList.add("cursor-pointer");
                row.dataset.userId = user.user_id;
            }


            let checkboxCell = '';
            if (isMultiSelectMode) {
                checkboxCell = `
                    <td class="px-4 py-3">
                        <input type="checkbox" class="user-checkbox accent-orange-500 pointer-events-none" data-user-id="${user.user_id}" ${isSelected ? "checked" : ""}>
                    </td>
                `;
            }
            //end

            let actions = `
                <button class="editUserBtn flex items-center gap-1 border border-orange-200 text-gray-600 px-2 py-1.5 rounded-md text-xs font-medium hover:bg-orange-50 transition">
                    <i class="ph ph-note-pencil text-base"></i><span>Edit</span>
                </button>
                <button class="deleteUserBtn flex items-center gap-1 bg-red-600 text-white px-2 py-1.5 rounded-md text-xs font-medium hover:bg-red-700 transition">
                    <i class="ph ph-trash text-base"></i><span>Delete</span>
                </button>
            `;

            if (user.role.toLowerCase() === 'student') {
                actions += `
                    <button class="allow-edit-btn flex items-center gap-1 border border-blue-500 text-blue-600 px-2 py-1.5 rounded-md text-xs font-medium hover:bg-blue-50 transition" data-id="${user.user_id}">
                        Allow Edit
                    </button>
                `;
            }
            // Updated
            let actionsCellHTML = `<td class="px-4 py-3 actions-cell"><div class="flex items-center gap-2">${actions}</div></td>`;
            if (isMultiSelectMode) {
                actionsCellHTML = `<td class="px-4 py-3 actions-cell"></td>`;
            }
            // end

            // Updated 
            row.innerHTML = `
                ${checkboxCell}
                <td class="px-4 py-3"><p class="font-medium text-gray-800">${user.name}</p><p class="text-gray-500 text-xs">${user.username}</p></td>
                <td class="px-4 py-3">${user.email || 'N/A'}</td>
                <td class="px-4 py-3">${getRoleBadge(user.role)}</td>
                <td class="px-4 py-3"><span class="status-badge cursor-pointer toggle-status-btn">${getStatusBadge(user.status)}</span></td>
                <td class="px-4 py-3 text-gray-700">${user.joinDate}</td>
                ${actionsCellHTML}
            `;
            // end
            userTableBody.appendChild(row);
        });
    }

    const confirmAddUserBtn = document.getElementById("confirmAddUser");
    if (confirmAddUserBtn) {
        confirmAddUserBtn.addEventListener("click", async () => {
            const first_name = document.getElementById("addFirstName").value.trim();
            const middle_name = document.getElementById("addMiddleName").value.trim();
            const last_name = document.getElementById("addLastName").value.trim();
            const campus_id = document.getElementById("addCampus").value;
            const username = document.getElementById("addUsername").value.trim();
            const role = document.getElementById("userRoleDropdownValue").textContent.trim();

            const selectWrapper = document.getElementById('addUserSingleSelectWrapper');
            const selectField = document.getElementById('addUserSelectField');

            let payloadData = {};

            if (!first_name || !last_name || !username || !campus_id || role === "Select Role") {
                return showErrorToast("Required Fields Missing", "Please fill in all required fields (First Name, Last Name, Campus, Username, Role).");
            }

            if (selectWrapper && !selectWrapper.classList.contains('hidden')) {
                const selectedValue = selectField.value;

                if (!selectedValue) {
                    const fieldName = role.toLowerCase() === 'student' ? 'Course/Program' : 'College/Department';
                    return showErrorToast("Required Field Missing", `Please select a ${fieldName}.`);
                }

                if (role.toLowerCase() === 'student') {
                    payloadData.course_id = selectedValue;
                } else if (role.toLowerCase() === 'faculty' || role.toLowerCase() === 'staff') {
                    payloadData.college_id = selectedValue;
                }
            }

            const checkedModules = Array.from(document.querySelectorAll('input[name="modules[]"]:checked'))
                .map(cb => cb.value);

            // 1. Show Loading SweetAlert (UPDATED TO USE SWEETALERT)
            showLoadingModal("Adding New User...", `Creating user account for ${first_name} ${last_name}.`);
            const addStartTime = Date.now();

            try {
                const res = await fetch("api/campus_admin/userManagement/add", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        first_name: first_name,
                        middle_name: middle_name || null,
                        last_name: last_name,
                        campus_id: campus_id,
                        username: username,
                        role: role,
                        ...(payloadData.course_id && {
                            course_id: payloadData.course_id
                        }),
                        ...(payloadData.college_id && {
                            college_id: payloadData.college_id
                        }),
                        modules: checkedModules
                    })
                });
                const data = await res.json();

                // MABILIS NA PAGSARA
                const elapsed = Date.now() - addStartTime;
                const minModalDisplay = 500; // Minimum 500ms display time
                if (elapsed < minModalDisplay) await new Promise(r => setTimeout(r, minModalDisplay - elapsed));
                Swal.close();

                if (data.success) {
                    // 2. Show Success Toast (UPDATED TO USE SWEETALERT)
                    showSuccessToast("User Added Successfully!", `${first_name} ${last_name} has been added.`);
                    closeAddUserModal();
                    await loadUsers(currentPage);
                } else {
                    // 3. Show Error Toast (UPDATED TO USE SWEETALERT)
                    showErrorToast("Adding User Failed", data.message || "An unexpected error occurred.");
                }
            } catch (err) {
                console.error("Add user error:", err);
                // Isara ang loading modal at magpakita ng Error Toast
                Swal.close();
                showErrorToast("Adding User Failed", "An error occurred while connecting to the server.");
            }
        });
    }

    if (userTableBody) {
        userTableBody.addEventListener("click", async (e) => {
            const row = e.target.closest("tr");
            if (!row || row.dataset.placeholder) return;

            // Updated
            const userId = row.dataset.userId ? parseInt(row.dataset.userId, 10) : null;

            if (isMultiSelectMode && userId) {
                if (selectedUsers.has(userId)) {
                    selectedUsers.delete(userId);
                } else {
                    selectedUsers.add(userId);
                }
                renderTable(users);
                updateMultiSelectButtons();
                return;
            }

            // end

            const validRows = Array.from(userTableBody.querySelectorAll("tr:not([data-placeholder='true'])"));
            const index = validRows.indexOf(row);
            if (index < 0) return;

            const user = users[index];
            if (!user) return;

            if (e.target.closest(".editUserBtn")) {
                currentEditingUserId = user.user_id;
                const userRole = user.role.toLowerCase();

                // --- 1. SHOW LOADING MODAL ---
                showLoadingModal("Fetching Details...", `Retrieving information for ${user.name}.`);
                const fetchStartTime = Date.now();

                try {
                    // --- 2. FETCH FULL DETAILS FROM API ---
                    const res = await fetch(`api/campus_admin/userManagement/get/${user.user_id}`);
                    const data = await res.json();

                    // Mabilis na pagsara ng modal
                    const elapsed = Date.now() - fetchStartTime;
                    const minModalDisplay = 500;
                    if (elapsed < minModalDisplay) await new Promise(r => setTimeout(r, minModalDisplay - elapsed));
                    Swal.close();

                    if (!data.user) throw new Error("User data not found.");

                    const fullUser = data.user;
                    const extra = data.extra; // Extra info (Student details)

                    // --- 3. POPULATE BASIC FIELDS ---
                    document.getElementById("editFirstName").value = fullUser.first_name || '';
                    document.getElementById("editMiddleName").value = fullUser.middle_name || '';
                    document.getElementById("editLastName").value = fullUser.last_name || '';
                    document.getElementById("editUsername").value = fullUser.username || '';
                    document.getElementById("editEmail").value = fullUser.email || '';
                    
                    await loadCampuses('editCampusField', fullUser.campus_id || null);

                    document.getElementById("editRoleDropdownValue").textContent = fullUser.role;
                    document.getElementById("editStatusDropdownValue").textContent = fullUser.is_active == 1 ? "Active" : "Inactive";
                    document.querySelector("#editUserTitle span").textContent = buildFullName(fullUser.first_name, fullUser.middle_name, fullUser.last_name);

                    // --- 4. HANDLE STUDENT FIELDS ---
                    const studentWrapper = document.getElementById("editStudentFieldsWrapper");
                    if (studentWrapper) {
                        if (userRole === 'student') {
                            studentWrapper.classList.remove("hidden");
                            
                            await loadCourses('editCourseId', extra ? extra.course_id : null);
                            
                            document.getElementById("editYearLevel").value = extra ? (extra.year_level || '1') : '1';
                            document.getElementById("editSection").value = extra ? (extra.section || '') : '';
                        } else {
                            studentWrapper.classList.add("hidden");
                        }
                    }

                    const editModulesContainer = document.getElementById("editPermissionsContainer");
                    if (editModulesContainer) {
                        if (userRole === 'admin' || userRole === 'librarian') {
                            editModulesContainer.classList.remove("hidden");

                            if (editUserUserManagementModuleWrapper) {
                                if (userRole === 'admin') {
                                    editUserUserManagementModuleWrapper.classList.remove('hidden');
                                } else {
                                    editUserUserManagementModuleWrapper.classList.add('hidden');
                                }
                            }

                            editModulesContainer.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                                cb.checked = data.modules?.some(
                                    m => m.toLowerCase().trim() === cb.value.toLowerCase().trim()
                                ) || false;
                            });

                        } else {
                            editModulesContainer.classList.add("hidden");
                            editModulesContainer.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
                        }
                    }

                    editUserModal.classList.remove("hidden");
                    document.body.classList.add("overflow-hidden");

                } catch (err) {
                    console.error("Error fetching user details:", err);
                    Swal.close();
                    showErrorToast("Fetch Failed", "Could not retrieve user details.");
                }
            }

            if (e.target.closest(".deleteUserBtn")) {
                // Use the FINAL CUSTOMIZED confirmation modal
                const isConfirmed = await showConfirmationModal(
                    "Confirm Deletion",
                    `Are you sure you want to delete user: ${user.name} (${user.role})? This action cannot be undone.`,
                    "Yes, Delete!"
                );
                if (!isConfirmed) return;

                // 🟠 START LOADING (Minimal display)
                showLoadingModal("Deleting user...", `Deleting ${user.name} from the system.`);
                const deleteStartTime = Date.now();

                try {
                    const res = await fetch(`api/campus_admin/userManagement/delete/${user.user_id}`, {
                        method: "POST"
                    });
                    const data = await res.json();

                    // CLOSE LOADING (Minimal display: 500ms)
                    const elapsed = Date.now() - deleteStartTime;
                    const minModalDisplay = 500;
                    if (elapsed < minModalDisplay) await new Promise(r => setTimeout(r, minModalDisplay - elapsed));
                    Swal.close();

                    if (data.success) {
                        showSuccessToast("User Deleted!", `User ${user.name} was successfully removed.`);
                        // REFRESH TABLE W/O LOADING MODAL
                        await loadUsers(currentPage, false);
                    } else {
                        showErrorToast("Deletion Failed", data.message || "Failed to delete the user.");
                    }
                } catch (err) {
                    Swal.close();
                    console.error("Delete error:", err);
                    showErrorToast("Deletion Failed", "An error occurred while deleting the user.");
                }
            }

            // TOGGLE STATUS
            if (e.target.closest(".toggle-status-btn")) {
                if (user.role.toLowerCase() === 'superadmin') return showErrorToast("Action Denied", "Superadmin status cannot be changed!");
                if (user.role.toLowerCase() === 'scanner') return showErrorToast("Action Denied", "Scanner status cannot be changed!");

                const newStatus = user.status === 'Active' ? 'Inactive' : 'Active';

                // Use the FINAL CUSTOMIZED confirmation modal
                const isConfirmed = await showConfirmationModal(
                    "Confirm Status Change",
                    `Are you sure you want to change the status of ${user.name} to **${newStatus}**?`,
                    `Yes, ${newStatus}`
                );
                if (!isConfirmed) return;

                // --- BAGONG LOGIC PARA SA MABILIS NA LOADING ---
                showLoadingModal("Updating Status...", `Setting status to ${newStatus}.`);
                const statusStartTime = Date.now();

                try {
                    const res = await fetch(`api/campus_admin/userManagement/toggleStatus/${user.user_id}`, {
                        method: "POST"
                    });
                    const data = await res.json();

                    // Isara agad ang loading modal (minimal delay lang para makita ang animation)
                    const elapsed = Date.now() - statusStartTime;
                    const minModalDisplay = 500; // 500ms minimum display time
                    if (elapsed < minModalDisplay) await new Promise(r => setTimeout(r, minModalDisplay - elapsed));
                    Swal.close();

                    if (data.success) {
                        showSuccessToast("Status Updated", `${user.name} is now ${data.newStatus}.`);
                        // I-reload ang users nang walang loading modal para mas mabilis (false parameter)
                        await loadUsers(currentPage, false);
                    } else {
                        showErrorToast("Status Update Failed", data.message || "Failed to update user status.");
                    }
                } catch (err) {
                    Swal.close(); // Isara ang modal kung may error
                    console.error("Toggle status error:", err);
                    showErrorToast("Status Update Failed", "An error occurred while updating user status.");
                }
            }

            if (e.target.closest(".allow-edit-btn")) {
                const userId = user.user_id;

                // Use the FINAL CUSTOMIZED confirmation modal
                const isConfirmed = await showConfirmationModal(
                    "Allow Profile Edit?",
                    `This will temporarily allow "${user.name}" (Student) to edit their profile details once. Continue?`,
                    "Yes, Allow Edit"
                );
                if (!isConfirmed) return;

                showLoadingModal("Grangting Permission...", "Sending temporary edit token.");

                try {
                    const res = await fetch(`api/campus_admin/userManagement/allowEdit/${userId}`, {
                        method: "POST",
                        headers: { "Content-Type": "application/json" }
                    });
                    const data = await res.json();

                    await new Promise(r => setTimeout(r, 1000)); // Minimum delay 1000ms
                    Swal.close();

                    if (data.success) {
                        showSuccessToast("Permission Granted", data.message || "User can now edit their profile.");

                    } else {
                        showErrorToast("Permission Failed", data.message || "Failed to allow edit for the user.");
                    }
                } catch (err) {
                    Swal.close();
                    console.error("Allow Edit error:", err);
                    showErrorToast("Permission Failed", "An error occurred while updating the user's permission.");
                }
            }
        });
    }

    const saveEditBtn = document.getElementById("saveEditUser");
    if (saveEditBtn) {
        saveEditBtn.addEventListener("click", async () => {
            if (!currentEditingUserId) return;

            const role = document.getElementById("editRoleDropdownValue").textContent.trim();

            const payload = {
                first_name: document.getElementById("editFirstName").value.trim(),
                middle_name: document.getElementById("editMiddleName").value.trim() || null,
                last_name: document.getElementById("editLastName").value.trim(),
                username: document.getElementById("editUsername").value.trim(),
                email: document.getElementById("editEmail").value.trim(),
                campus_id: document.getElementById("editCampusField").value,
                role: role,
                is_active: document.getElementById("editStatusDropdownValue").textContent.trim().toLowerCase() === 'active' ? 1 : 0
            };

            if (role.toLowerCase() === 'student') {
                payload.course_id = document.getElementById("editCourseId").value;
                payload.year_level = document.getElementById("editYearLevel").value;
                payload.section = document.getElementById("editSection").value.trim();
            }

            if (!payload.first_name || !payload.last_name || !payload.email || !payload.username) {
                return showErrorToast("Required Fields Missing", "First Name, Last Name, Username, and Email are required.");
            }

            const changePasswordCheckbox = document.getElementById("togglePassword");
            if (changePasswordCheckbox && changePasswordCheckbox.checked) {
                const newPassword = document.getElementById("editPassword").value;
                const confirmPassword = document.getElementById("confirmPassword").value;
                if (newPassword.length < 8) return showErrorToast("Password Error", "Password must be at least 8 characters long.");
                if (newPassword !== confirmPassword) return showErrorToast("Password Error", "New password and confirmation password do not match.");
                payload.password = newPassword;
            }

            const permContainer = document.getElementById("editPermissionsContainer");
            if (permContainer && !permContainer.classList.contains("hidden")) {
                payload.modules = Array.from(document.querySelectorAll('input[name="editModules[]"]:checked')).map(cb => cb.value);
            }

            showLoadingModal("Saving Changes...", `Updating user details for ${payload.first_name} ${payload.last_name}.`);
            const editStartTime = Date.now();

            try {
                const res = await fetch(`api/campus_admin/userManagement/update/${currentEditingUserId}`, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                await new Promise(r => setTimeout(r, 1000)); // Minimum delay 1000ms
                Swal.close();

                if (data.success) {
                    showSuccessToast("User Updated!", "User information saved successfully.");
                    closeEditUserModal();

                } else {
                    showErrorToast("Update Failed", data.message || "Failed to update user information.");
                }
            } catch (err) {
                Swal.close();
                console.error("Update user error:", err);
                showErrorToast("Update Failed", "An error occurred while updating the user.");
            } finally {
                // closeEditUserModal(); 
            }
        });
    }

    const togglePasswordCheckbox = document.getElementById('togglePassword');
    if (togglePasswordCheckbox) {
        togglePasswordCheckbox.addEventListener('change', () => {
            document.getElementById('passwordFields')?.classList.toggle('hidden', !togglePasswordCheckbox.checked);
        });
    }

    function togglePassword(fieldId, button) {
        const input = document.getElementById(fieldId);
        const icon = button.querySelector('i');
        if (!input || !icon) return;
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove('ph-eye');
            icon.classList.add('ph-eye-slash');
        } else {
            input.type = "password";
            icon.classList.remove('ph-eye-slash');
            icon.classList.add('ph-eye');
        }
    }

    const toggleNewPass = document.getElementById('toggleNewPass');
    if (toggleNewPass) toggleNewPass.addEventListener('click', () => togglePassword('editPassword', toggleNewPass));
    const toggleConfirmPass = document.getElementById('toggleConfirmPass');
    if (toggleConfirmPass) toggleConfirmPass.addEventListener('click', () => togglePassword('confirmPassword', toggleConfirmPass));

    function getRoleBadge(role) {
        const base = "px-2 py-1 text-xs rounded-md font-medium";
        switch (role.toLowerCase()) {
            case "student":
                return `<span class="bg-green-500 text-white ${base}">${role}</span>`;
            case "librarian":
                return `<span class="bg-amber-500 text-white ${base}">${role}</span>`;
            case "admin":
                return `<span class="bg-orange-600 text-white ${base}">${role}</span>`;
            case "campus admin":
            case "campus_admin":
                return `<span class="bg-indigo-600 text-white ${base}">${role}</span>`;
            case "faculty":
                return `<span class="bg-emerald-600 text-white ${base}">${role}</span>`;
            case "staff":
                return `<span class="bg-teal-600 text-white ${base}">${role}</span>`;
            case "superadmin":
                return `<span class="bg-purple-600 text-white ${base}">${role}</span>`;
            default:
                return `<span class="bg-gray-300 text-gray-800 ${base}">${role}</span>`;
        }
    }

    function getStatusBadge(status) {
        const base = "px-2 py-1 text-xs rounded-md font-medium";
        return status.toLowerCase() === "active" ? `<span class="bg-green-500 text-white ${base}">Active</span>` : `<span class="bg-gray-300 text-gray-700 ${base}">Inactive</span>`;
    }

    loadUsers(currentPage);

    // Updated
    function updateMultiSelectButtons() {
        const hasSelection = selectedUsers.size > 0;

        if (isMultiSelectMode) {
            multiSelectBtn.classList.add('hidden');
            multiSelectActions.classList.remove('hidden');
            multiSelectActions.classList.add('inline-flex');
        } else {
            multiSelectBtn.classList.remove('hidden');
            multiSelectActions.classList.add('hidden');
            multiSelectActions.classList.remove('inline-flex');
        }

        multiDeleteBtn.classList.toggle('hidden', !hasSelection);
        multiAllowEditBtn.classList.toggle('hidden', !hasSelection);
        if (selectionCount) selectionCount.textContent = selectedUsers.size;

        const allVisibleUserIds = users.map(u => u.user_id);
        const allSelectedOnPage = allVisibleUserIds.length > 0 && allVisibleUserIds.every(id => selectedUsers.has(id));

        if (allSelectedOnPage) {
            selectAllBtn.innerHTML = `<i class="ph ph-check-square-offset text-base"></i> Deselect All`;
        } else {
            selectAllBtn.innerHTML = `<i class="ph ph-check-square-offset text-base"></i> Select All`;
        }
    }


    if (multiSelectBtn) {
        multiSelectBtn.addEventListener('click', () => {
            isMultiSelectMode = true;
            updateMultiSelectButtons();
            renderTable(users);
        });
    }

    if (cancelSelectionBtn) {
        cancelSelectionBtn.addEventListener('click', () => {
            isMultiSelectMode = false;
            selectedUsers.clear();
            updateMultiSelectButtons();
            renderTable(users);
        });
    }


    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', () => {
            const allVisibleUserIds = users.map(u => u.user_id);
            const allSelectedOnPage = allVisibleUserIds.length > 0 && allVisibleUserIds.every(id => selectedUsers.has(id));

            if (allSelectedOnPage) {
                allVisibleUserIds.forEach(id => selectedUsers.delete(id));
            } else {
                allVisibleUserIds.forEach(id => selectedUsers.add(id));
            }
            renderTable(users);
            updateMultiSelectButtons();
        });
    }

    if (multiDeleteBtn) {
        multiDeleteBtn.addEventListener('click', async () => {
            const userIds = [...selectedUsers];
            if (userIds.length === 0) {
                return showErrorToast("No Users Selected", "Please select users to delete.");
            }

            // --- BULK APPROVAL CHECK (5+ USERS) ---
            let reason = null;
            if (userIds.length >= 5) {
                const { value: inputReason, isConfirmed: isReasonConfirmed } = await Swal.fire({
                    title: 'Bulk Deactivation Reason',
                    text: `You are about to request deactivation for ${userIds.length} users. This requires Superadmin approval. Please provide a reason:`,
                    input: 'textarea',
                    inputPlaceholder: 'Type your reason here...',
                    inputAttributes: {
                        'aria-label': 'Type your reason here'
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Submit Request',
                    cancelButtonText: 'Cancel',
                    inputValidator: (value) => {
                        if (!value) {
                            return 'You need to write something!'
                        }
                    },
                    customClass: {
                        popup: "!rounded-xl !border-2 !border-orange-400 shadow-[0_0_15px_#ffb34780]",
                        confirmButton: "!bg-orange-600 !text-white !px-5 !py-2.5 !rounded-lg hover:!bg-orange-700 !mx-2 !font-semibold",
                        cancelButton: "!bg-gray-200 !text-gray-800 !px-5 !py-2.5 !rounded-lg hover:!bg-gray-300 !mx-2 !font-semibold"
                    },
                    buttonsStyling: false
                });

                if (!isReasonConfirmed) return;
                reason = inputReason;
            } else {
                const isConfirmed = await showConfirmationModal(
                    `Delete ${userIds.length} Users?`,
                    `Are you sure you want to permanently delete the selected ${userIds.length} user(s)? This action cannot be undone.`,
                    "Yes, Delete All"
                );
                if (!isConfirmed) return;
            }

            showLoadingModal("Processing Request...", `Handling ${userIds.length} user(s).`);

            try {
                const res = await fetch('api/campus_admin/userManagement/deleteMultiple', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_ids: userIds,
                        reason: reason
                    })
                });

                const data = await res.json();
                Swal.close();

                if (data.success) {
                    if (data.requires_approval) {
                        showSuccessToast("Request Submitted", data.message);
                    } else {
                        showSuccessToast("Deletion Successful", data.message);
                    }
                } else {
                    let errorMessage = data.message;
                    if (data.errors && data.errors.length > 0) {
                        errorMessage += ` ${data.errors.join(' ')}`;
                    }
                    showErrorToast("Action Failed", errorMessage);
                }

                isMultiSelectMode = false;
                selectedUsers.clear();
                updateMultiSelectButtons();
                loadUsers(1, false);

            } catch (err) {
                Swal.close();
                console.error("Multi-delete error:", err);
                showErrorToast("Network Error", "An error occurred while connecting to the server.");
            }
        });
    }

    if (multiAllowEditBtn) {
        multiAllowEditBtn.addEventListener('click', async () => {
            const selectedIds = [...selectedUsers];
            if (selectedIds.length === 0) {
                return showErrorToast("No Users Selected", "Please select students to allow edit access.");
            }

            const allAreStudents = selectedIds.every(id => {
                const user = users.find(u => u.user_id === id);
                return user && user.role.toLowerCase() === 'student';
            });

            if (!allAreStudents) {
                return showErrorToast("Invalid Selection", "This action can only be applied to students.");
            }

            const isConfirmed = await showConfirmationModal(
                `Allow Edit for ${selectedIds.length} Students?`,
                `This will grant temporary profile edit access to the selected ${selectedIds.length} student(s). Continue?`,
                "Yes, Grant Access"
            );

            if (!isConfirmed) return;

            showLoadingModal("Granting Access...", `Processing ${selectedIds.length} student(s).`);

            try {
                const res = await fetch('api/campus_admin/userManagement/allowMultipleEdit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_ids: selectedIds
                    })
                });

                const data = await res.json();
                Swal.close();

                if (data.success) {
                    showSuccessToast("Access Granted", data.message);
                } else {
                    let errorMessage = data.message;
                    if (data.errors && data.errors.length > 0) {
                        errorMessage += ` ${data.errors.join(' ')}`;
                    }
                    showErrorToast("Action Failed", errorMessage);
                }

                isMultiSelectMode = false;
                selectedUsers.clear();
                updateMultiSelectButtons();
                loadUsers(currentPage, false);

            } catch (err) {
                Swal.close();
                console.error("Multi-allow-edit error:", err);
                showErrorToast("Network Error", "An error occurred while connecting to the server.");
            }
        });
    }
    // end
});
