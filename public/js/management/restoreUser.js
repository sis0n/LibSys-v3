// --- SweetAlert Helper Functions (Global Declarations) ---

function showSuccessToast(title, body = "") {
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

function showErrorToast(title, body = "An error occurred during processing.") {
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

// 🟠 LOADING MODAL (ORANGE THEME)
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

// 🟠 CONFIRMATION MODAL (ORANGE THEME)
async function showConfirmationModal(title, text, confirmText = "Confirm", icon = "ph-warning-circle") {
    if (typeof Swal == "undefined") return confirm(title);
    const result = await Swal.fire({
        background: "transparent",
        html: `
            <div class="flex flex-col text-center">
                <div class="flex justify-center mb-3">
                    <div class="flex items-center justify-center w-14 h-14 rounded-full bg-orange-100 text-orange-600">
                        <i class="ph ${icon} text-2xl"></i>
                    </div>
                </div>
                <h3 class="text-[17px] font-semibold text-orange-700">${title}</h3>
                <p class="text-[14px] text-gray-700 mt-1">${text}</p>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: "Cancel",
        customClass: {
            popup:
                "!rounded-xl !shadow-md !p-6 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] !border-2 !border-orange-400 shadow-[0_0_8px_#ffb34770]",
            confirmButton:
                "!bg-orange-600 !text-white !px-5 !py-2.5 !rounded-lg hover:!bg-orange-700",
            cancelButton:
                "!bg-gray-200 !text-gray-800 !px-5 !py-2.5 !rounded-lg hover:!bg-gray-300",
        },
    });
    return result.isConfirmed;
}
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

const API_BASE = `${BASE_URL_JS}/api/restoreUser`;

document.addEventListener('DOMContentLoaded', () => {
    const userSearchInput = document.getElementById('userSearchInput');
    const roleFilterDropdownBtn = document.getElementById('roleFilterDropdownBtn');
    const roleFilterDropdownMenu = document.getElementById('roleFilterDropdownMenu');
    const roleFilterDropdownSpan = roleFilterDropdownBtn.querySelector('span');
    const deletedUserDateFilter = document.getElementById('deletedUserDateFilter');

    const deletedUsersTableBody = document.getElementById('deletedUsersTableBody');
    const deletedUserRowTemplate = document.getElementById('deleted-user-row-template').content;
    const deletedUsersCount = document.getElementById('deletedUsersCount');
    const noDeletedUsersFound = document.getElementById('noDeletedUsersFound');

    const paginationContainer = document.getElementById('pagination-container');
    const paginationNumbersDiv = document.getElementById('pagination-numbers');
    const prevPageBtn = document.getElementById('prev-page');
    const nextPageBtn = document.getElementById('next-page');
    const csrfTokenInput = document.getElementById('csrf_token');

    let allDeletedUsers = [];

    let currentSearchTerm = '';
    let currentRoleFilter = 'All Users';
    let currentDateFilter = '';
    let filteredUsers = [];
    const itemsPerPage = 10;
    let currentPage = 1;

    // --- Page Memory ---
    try {
        const savedPage = sessionStorage.getItem('restoreUserPage');
        if (savedPage) {
            const parsedPage = parseInt(savedPage, 10);
            if (!isNaN(parsedPage) && parsedPage > 0) {
                currentPage = parsedPage;
            } else {
                sessionStorage.removeItem('restoreUserPage');
            }
        }
    } catch (e) {
        console.error("SessionStorage Error:", e);
        currentPage = 1;
    }

    const userDetailsModal = document.getElementById('userDetailsModal');
    const closeUserDetailsModalBtn = document.getElementById('closeUserDetailsModalBtn');
    const modalUserFullName = document.getElementById('modalUserFullName');
    const modalUsername = document.getElementById('modalUsername');
    const modalUserRole = document.getElementById('modalUserRole');
    const modalUserEmail = document.getElementById('modalUserEmail');
    const modalContact = document.getElementById('modalContact');
    const modalUserCreatedDate = document.getElementById('modalUserCreatedDate');
    const modalUserDeletedDate = document.getElementById('modalUserDeletedDate');
    const modalUserDeletedBy = document.getElementById('modalUserDeletedBy');

    // =========================================================================
    // MODIFIED: fetchDeletedUsers (Initial load, retains loading modal)
    // =========================================================================
    async function fetchDeletedUsers() {
        // 1. 🟠 START LOADING SWEETALERT MODAL
        const startTime = Date.now();
        if (typeof showLoadingModal !== 'undefined') {
             showLoadingModal("Loading Deleted Users...", "Retrieving archive data.");
        }
        showLoadingState(); // Keep default table loading temporarily

        try {
            const response = await fetch(`${API_BASE}/fetch`);
            if (!response.ok) throw new Error("Failed to fetch data.");
            
            const data = await response.json();

            // 2. CLOSE LOADING with minimum delay
            const elapsed = Date.now() - startTime;
            const minDelay = 500;
            if (elapsed < minDelay) await new Promise(r => setTimeout(r, minDelay - elapsed));
            if (typeof Swal != 'undefined') Swal.close();
            
            
            if (data.success && Array.isArray(data.users)) {
                allDeletedUsers = data.users;
                applyFiltersAndRender(true); // Pass true for initial load
            } else {
                console.error('Error fetching deleted users:', data.message);
                showErrorState('Failed to load deleted users.');
                allDeletedUsers = [];
                applyFiltersAndRender(true); // Pass true for initial load even on error
            }
        } catch (error) {
            // 3. CLOSE LOADING and SHOW ERROR
            if (typeof Swal != 'undefined') Swal.close();
            console.error('Network error fetching deleted users:', error);
            showErrorState('Network error. Could not load deleted users.');
            allDeletedUsers = [];
            applyFiltersAndRender();
        }
    }
    // =========================================================================

    function showLoadingState() {
        // Updated to show a spinning icon for better UX during loading
        deletedUsersTableBody.innerHTML = '<tr><td colspan="6" class="text-center py-10 text-gray-500"><i class="ph ph-spinner animate-spin text-2xl"></i> Loading...</td></tr>';
        noDeletedUsersFound.classList.add('hidden');
        paginationContainer.classList.add('hidden');
    }

    function showErrorState(message) {
        // Tiyakin na magsara ang SweetAlert2 bago mag-display ng error
        if (typeof Swal != 'undefined') Swal.close();
        
        deletedUsersTableBody.innerHTML = `<tr><td colspan="6" class="text-center py-10 text-red-500">${message}</td></tr>`;
        noDeletedUsersFound.classList.add('hidden');
        paginationContainer.classList.add('hidden');
        deletedUsersCount.textContent = 0;
        showErrorToast('Load Failed', message);
    }

    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        try {
            return new Date(dateString).toLocaleDateString('en-US', {
                year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
            });
        } catch (e) {
            return dateString;
        }
    }

    function renderDeletedUsers(usersToRender) {
        deletedUsersTableBody.innerHTML = '';
        deletedUsersCount.textContent = filteredUsers.length;

        if (usersToRender.length === 0) {
            if (filteredUsers.length === 0 && !currentSearchTerm && currentRoleFilter === 'All Users' && !currentDateFilter) {
                noDeletedUsersFound.textContent = 'No deleted users found.';
                noDeletedUsersFound.classList.remove('hidden');
            } else if (filteredUsers.length === 0) {
                noDeletedUsersFound.textContent = 'No deleted users found matching filters.';
                noDeletedUsersFound.classList.remove('hidden');
            } else {
                deletedUsersTableBody.innerHTML = '<tr><td colspan="6" class="text-center py-10 text-gray-500">No users found on this page.</td></tr>';
                noDeletedUsersFound.classList.add('hidden');
            }
            paginationContainer.classList.add('hidden');
            return;
        }
        noDeletedUsersFound.classList.add('hidden');
        paginationContainer.classList.remove('hidden');

        usersToRender.forEach(user => {
            const newRow = deletedUserRowTemplate.cloneNode(true);
            const tr = newRow.querySelector('tr');
            tr.dataset.userId = user.id;
            tr.classList.add('deleted-user-row');

            newRow.querySelector('.user-fullname').textContent = user.fullname || 'N/A';
            newRow.querySelector('.user-username').textContent = user.username || 'N/A';
            newRow.querySelector('.user-role').textContent = user.role || 'N/A';
            newRow.querySelector('.user-deleted-date').textContent = formatDate(user.deleted_date);
            newRow.querySelector('.user-deleted-by').textContent = user.deleted_by_name || 'Unknown';

            const restoreBtn = newRow.querySelector('.restore-btn');
            restoreBtn.addEventListener('click', async (e) => {
                e.stopPropagation();
                
                const isConfirmed = await showConfirmationModal(
                    "Confirm Restoration",
                    `Are you sure you want to restore ${user.fullname || user.username}? This user will regain access.`,
                    "Yes, Restore"
                );
                if (!isConfirmed) return;
                
                // 🟠 START LOADING FOR RESTORE (MINIMAL DELAY)
                showLoadingModal("Restoring User...", `Restoring ${user.fullname || user.username} account.`);
                const restoreStartTime = Date.now();
                
                restoreBtn.disabled = true;
                restoreBtn.classList.add('opacity-50');
                
                try {
                    const response = await fetch(`${API_BASE}/restore`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfTokenInput ? csrfTokenInput.value : ''
                        },
                        body: JSON.stringify({ user_id: user.id })
                    });
                    const result = await response.json();
                    
                    // CLOSE LOADING
                    const restoreElapsed = Date.now() - restoreStartTime;
                    const minModalDisplay = 500; // 500ms minimal processing time
                    if (restoreElapsed < minModalDisplay) await new Promise(r => setTimeout(r, minModalDisplay - restoreElapsed));
                    if (typeof Swal != 'undefined') Swal.close();
                    
                    if (result.success) {
                        showSuccessToast('Restored Successfully', `User ${user.fullname || user.username} has been restored.`);
                        fetchDeletedUsers();
                    } else {
                        showErrorToast('Restore Failed', result.message);
                        restoreBtn.disabled = false;
                        restoreBtn.classList.remove('opacity-50');
                    }
                } catch (error) {
                    if (typeof Swal != 'undefined') Swal.close();
                    console.error('Error restoring user:', error);
                    showErrorToast('Restoration Error', 'An error occurred during restoration.');
                    restoreBtn.disabled = false;
                    restoreBtn.classList.remove('opacity-50');
                }
            });

            const archiveBtn = newRow.querySelector('.archive-btn');
            if (archiveBtn) {
                archiveBtn.addEventListener('click', async (e) => {
                    e.stopPropagation();
                    
                    const isConfirmed = await showConfirmationModal(
                        "Confirm Archiving",
                        `ARCHIVE ${user.fullname || user.username}? This will permanently remove the deletion record from this list.`,
                        "Yes, Archive"
                    );
                    if (!isConfirmed) return;
                    
                    // 🟠 START LOADING FOR ARCHIVE (MINIMAL DELAY - 500ms)
                    showLoadingModal("Archiving Record...", `Permanently archiving deletion record.`);
                    const archiveStartTime = Date.now();
                    
                    archiveBtn.disabled = true;
                    archiveBtn.classList.add('opacity-50');
                    
                    try {
                        const response = await fetch(`${API_BASE}/delete/${user.id}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfTokenInput ? csrfTokenInput.value : ''
                            }
                        });
                        const result = await response.json();
                        
                        // CLOSE LOADING
                        const archiveElapsed = Date.now() - archiveStartTime;
                        const minModalDisplay = 500; // 500ms minimal processing time
                        if (archiveElapsed < minModalDisplay) await new Promise(r => setTimeout(r, minModalDisplay - archiveElapsed));
                        if (typeof Swal != 'undefined') Swal.close();
                        
                        if (result.success) {
                            showSuccessToast('Archived Successfully', result.message);
                            fetchDeletedUsers();
                        } else {
                            showErrorToast('Archiving Failed', result.message);
                            archiveBtn.disabled = false;
                            archiveBtn.classList.remove('opacity-50');
                        }
                    } catch (error) {
                        if (typeof Swal != 'undefined') Swal.close();
                        console.error('Error archiving user:', error);
                        showErrorToast('Archiving Error', 'An error occurred during archiving.');
                        archiveBtn.disabled = false;
                        archiveBtn.classList.remove('opacity-50');
                    }
                });
            }

            deletedUsersTableBody.appendChild(newRow);
        });
    }

    function renderPagination(totalItems, itemsPerPage, currentPage) {
        paginationNumbersDiv.innerHTML = '';
        const totalPages = Math.ceil(totalItems / itemsPerPage);

        if (totalItems <= itemsPerPage || totalPages <= 1) {
            paginationContainer.classList.add('hidden');
            return;
        }
        paginationContainer.classList.remove('hidden');

        prevPageBtn.classList.toggle('opacity-50', currentPage === 1);
        prevPageBtn.classList.toggle('cursor-not-allowed', currentPage === 1);
        nextPageBtn.classList.toggle('opacity-50', currentPage === totalPages);
        nextPageBtn.classList.toggle('cursor-not-allowed', currentPage === totalPages);

        const maxPagesToShow = 3;
        let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
        let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

        if (endPage - startPage + 1 < maxPagesToShow) {
            startPage = Math.max(1, endPage - maxPagesToShow + 1);
        }

        if (startPage > 1) {
            createPageLink(1);
            if (startPage > 2) createEllipsis();
        }

        for (let i = startPage; i <= endPage; i++) {
            createPageLink(i, i === currentPage);
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) createEllipsis();
            createPageLink(totalPages);
        }
    }

    function createPageLink(pageNumber, isActive = false) {
        const link = document.createElement('a');
        link.href = '#';
        link.textContent = pageNumber;
        link.classList.add(
            'flex', 'items-center', 'justify-center',
            'w-8', 'h-8', 'rounded-full', 'text-gray-700',
            'hover:bg-orange-50', 'hover:text-orange-600', 'transition'
        );
        if (isActive) {
            link.classList.add('bg-orange-500', 'text-white', 'font-semibold');
            link.classList.remove('hover:bg-orange-50', 'hover:text-orange-600', 'text-gray-700');
        }
        link.addEventListener('click', (e) => {
            e.preventDefault();
            goToPage(pageNumber);
        });
        paginationNumbersDiv.appendChild(link);
    }

    function createEllipsis() {
        const ellipsisSpan = document.createElement('span');
        ellipsisSpan.textContent = '...';
        ellipsisSpan.classList.add(
            'flex', 'items-center', 'justify-center',
            'w-8', 'h-8', 'text-gray-500'
        );
        paginationNumbersDiv.appendChild(ellipsisSpan);
    }

    function goToPage(page) {
        const totalPages = Math.ceil(filteredUsers.length / itemsPerPage);
        currentPage = Math.max(1, Math.min(page, totalPages));

        try {
            sessionStorage.setItem('restoreUserPage', currentPage);
        } catch (e) {
            console.error("SessionStorage Error:", e);
        }

        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const usersToRender = filteredUsers.slice(startIndex, endIndex);
        renderDeletedUsers(usersToRender);
        renderPagination(filteredUsers.length, itemsPerPage, currentPage);
    }

    function applyFiltersAndRender(isInitialLoad = false) {
        let tempFiltered = allDeletedUsers.filter(user => {
            const searchLower = currentSearchTerm.toLowerCase();
            const matchesSearch = !currentSearchTerm ||
                (user.fullname && user.fullname.toLowerCase().includes(searchLower)) ||
                (user.username && user.username.toLowerCase().includes(searchLower)) ||
                (user.email && user.email.toLowerCase().includes(searchLower));

            const matchesRole = currentRoleFilter === 'All Users' || (user.role && user.role.toLowerCase() === currentRoleFilter.toLowerCase());

            let matchesDate = true;
            if (currentDateFilter && user.deleted_date) {
                try {
                    const deletedDate = new Date(user.deleted_date);
                    const filterDate = new Date(currentDateFilter + "T00:00:00");
                    matchesDate = deletedDate.getFullYear() === filterDate.getFullYear() &&
                        deletedDate.getMonth() === filterDate.getMonth() &&
                        deletedDate.getDate() === filterDate.getDate();
                } catch (e) {
                    matchesDate = false;
                    console.error("Date comparison error:", e);
                }
            } else if (currentDateFilter && !user.deleted_date) {
                matchesDate = false;
            }

            return matchesSearch && matchesRole && matchesDate;
        });
        filteredUsers = tempFiltered;
        
        if (!isInitialLoad) { // Only reset page if not initial load
            currentPage = 1;
            try {
                sessionStorage.removeItem('restoreUserPage');
            } catch (e) {}
        }

        goToPage(isInitialLoad ? currentPage : 1);
    }

    function openUserDetailsModal(user) {
        modalUserFullName.textContent = user.fullname || 'N/A';
        modalUsername.textContent = user.username || 'N/A';
        modalUserRole.textContent = user.role || 'N/A';
        modalUserEmail.textContent = user.email || 'N/A';
        modalContact.textContent = user.contact || 'N/A';
        modalUserCreatedDate.textContent = formatDate(user.created_date);
        modalUserDeletedDate.textContent = formatDate(user.deleted_date);
        modalUserDeletedBy.textContent = user.deleted_by_name || 'Unknown';
        userDetailsModal.classList.remove('hidden');
    }

    function closeUserDetailsModal() {
        userDetailsModal.classList.add('hidden');
    }

    // Walang loading modal sa filters/search
    userSearchInput.addEventListener('input', (e) => {
        currentSearchTerm = e.target.value;
        applyFiltersAndRender();
    });

    roleFilterDropdownBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        roleFilterDropdownMenu.classList.toggle('hidden');
    });

    roleFilterDropdownMenu.querySelectorAll('a').forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            currentRoleFilter = item.dataset.value;
            roleFilterDropdownSpan.textContent = currentRoleFilter;
            roleFilterDropdownMenu.classList.add('hidden');
            applyFiltersAndRender();
        });
    });

    deletedUserDateFilter.addEventListener('change', (e) => {
        currentDateFilter = e.target.value;
        applyFiltersAndRender();
    });
    // End Walang loading modal sa filters/search

    document.addEventListener('click', (e) => {
        if (!roleFilterDropdownBtn.contains(e.target) && !roleFilterDropdownMenu.contains(e.target)) {
            roleFilterDropdownMenu.classList.add('hidden');
        }
    });

    prevPageBtn.addEventListener('click', (e) => {
        e.preventDefault();
        if (currentPage > 1) {
            goToPage(currentPage - 1);
        }
    });

    nextPageBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const totalPages = Math.ceil(filteredUsers.length / itemsPerPage);
        if (currentPage < totalPages) {
            goToPage(currentPage + 1);
        }
    });

    deletedUsersTableBody.addEventListener('click', (e) => {
        const row = e.target.closest('.deleted-user-row');
        if (!row) return;

        if (e.target.closest('.restore-btn') || e.target.closest('.archive-btn')) {
            return;
        }

        const userId = parseInt(row.dataset.userId, 10);
        const user = allDeletedUsers.find(u => u.id === userId);
        if (user) {
            openUserDetailsModal(user);
        } else {
            console.error("Could not find user data for ID:", userId);
        }
    });

    closeUserDetailsModalBtn.addEventListener('click', closeUserDetailsModal);
    userDetailsModal.addEventListener('click', (e) => {
        if (e.target === userDetailsModal) {
            closeUserDetailsModal();
        }
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !userDetailsModal.classList.contains('hidden')) {
            closeUserDetailsModal();
        }
    });

    fetchDeletedUsers();
});
