document.addEventListener("DOMContentLoaded", function () {
    // --- SweetAlert Helper Functions (Mirrored from User Management) ---
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

    const campusTableBody = document.getElementById("campusTableBody");
    const campusSearchInput = document.getElementById("campusSearchInput");
    const resultsIndicator = document.getElementById("resultsIndicator");
    const addCampusBtn = document.getElementById("addCampusBtn");
    const campusModal = document.getElementById("campusModal");
    const campusForm = document.getElementById("campusForm");
    const modalTitle = document.getElementById("modalTitle");
    const campusIdInput = document.getElementById("campusId");
    const campusNameInput = document.getElementById("campus_name");
    const campusCodeInput = document.getElementById("campus_code");
    const closeButtons = document.querySelectorAll(".close-modal");

    // Multi-select elements
    const multiSelectBtn = document.getElementById("multiSelectBtn");
    const multiSelectActions = document.getElementById("multiSelectActions");
    const selectAllBtn = document.getElementById("selectAllBtn");
    const cancelSelectionBtn = document.getElementById("cancelSelectionBtn");
    const selectionCount = document.getElementById("selectionCount");

    let allCampuses = [];
    let isMultiSelectMode = false;
    let selectedCampuses = new Set();

    // Load Campuses
    async function loadCampuses() {
        try {
            const response = await fetch("api/superadmin/campuses/fetch");
            const data = await response.json();

            if (data.success) {
                allCampuses = data.campuses;
                renderTable(allCampuses);
            } else {
                showErrorToast("Error", data.message || "Failed to load campuses");
            }
        } catch (error) {
            console.error("Error:", error);
            showErrorToast("Error", "An unexpected error occurred");
        }
    }

    function renderTable(campuses) {
        const resultsIndicator = document.getElementById("resultsIndicator");
        if (resultsIndicator) {
            resultsIndicator.innerHTML = `Showing <span class="font-medium text-gray-800">${campuses.length}</span> ${campuses.length === 1 ? 'campus' : 'campuses'}`;
        }

        // Handle multi-select header
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

        if (campuses.length === 0) {
            const colspan = isMultiSelectMode ? 7 : 6;
            campusTableBody.innerHTML = `<tr><td colspan="${colspan}" class="px-4 py-8 text-center text-gray-500">No campuses found.</td></tr>`;
            return;
        }

        campusTableBody.innerHTML = "";
        campuses.forEach((campus, i) => {
            const isSelected = selectedCampuses.has(campus.campus_id);
            const status = campus.is_active == 1 ? 'Active' : 'Inactive';
            const row = document.createElement("tr");
            row.className = `transition-colors ${isSelected ? "bg-orange-100" : (status === 'Inactive' ? "bg-gray-50 text-gray-500" : "hover:bg-orange-50/30")} group`;
            if (isMultiSelectMode) {
                row.classList.add("cursor-pointer");
                row.dataset.campusId = campus.campus_id;
            }

            let checkboxCell = '';
            if (isMultiSelectMode) {
                checkboxCell = `
                    <td class="px-4 py-3">
                        <input type="checkbox" class="campus-checkbox accent-orange-500 pointer-events-none" data-campus-id="${campus.campus_id}" ${isSelected ? "checked" : ""}>
                    </td>
                `;
            }

            let actionsCellHTML = `
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <button onclick="editCampus(${campus.campus_id}, '${campus.campus_name.replace(/'/g, "\\'")}', '${campus.campus_code}')" 
                            class="flex items-center gap-1 border border-orange-200 text-gray-600 px-2 py-1.5 rounded-md text-xs font-medium hover:bg-orange-50 transition" title="Edit">
                            <i class="ph ph-note-pencil text-base"></i><span>Edit</span>
                        </button>
                    </div>
                </td>
            `;

            if (isMultiSelectMode) {
                actionsCellHTML = `<td class="px-4 py-3"></td>`;
            }

            row.innerHTML = `
                ${checkboxCell}
                <td class="px-4 py-3 text-gray-500 font-medium">${i + 1}</td>
                <td class="px-4 py-3 font-semibold text-gray-800">${campus.campus_name}</td>
                <td class="px-4 py-3 font-mono text-orange-600 font-medium">${campus.campus_code}</td>
                <td class="px-4 py-3">
                    <span class="status-badge cursor-pointer" onclick="toggleCampusStatus(${campus.campus_id}, '${campus.campus_name.replace(/'/g, "\\'")}', '${status}')">
                        ${getStatusBadge(status)}
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-600 text-xs italic">${formatDate(campus.created_at)}</td>
                ${actionsCellHTML}
            `;

            campusTableBody.appendChild(row);
        });
    }

    function getStatusBadge(status) {
        const base = "px-2 py-1 text-xs rounded-md font-medium";
        return status.toLowerCase() === "active" ? `<span class="bg-green-500 text-white ${base}">Active</span>` : `<span class="bg-gray-300 text-gray-700 ${base}">Inactive</span>`;
    }

    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Search functionality
    campusSearchInput.addEventListener("input", (e) => {
        const searchTerm = e.target.value.toLowerCase();
        const filtered = allCampuses.filter(c => 
            c.campus_name.toLowerCase().includes(searchTerm) || 
            c.campus_code.toLowerCase().includes(searchTerm) ||
            c.campus_id.toString().includes(searchTerm)
        );
        renderTable(filtered);
    });

    // Multi-select Actions
    function updateMultiSelectButtons() {
        if (isMultiSelectMode) {
            multiSelectBtn.classList.add('hidden');
            multiSelectActions.classList.remove('hidden');
            multiSelectActions.classList.add('inline-flex');
        } else {
            multiSelectBtn.classList.remove('hidden');
            multiSelectActions.classList.add('hidden');
            multiSelectActions.classList.remove('inline-flex');
        }

        if (selectionCount) selectionCount.textContent = selectedCampuses.size;

        const allVisibleIds = allCampuses.map(c => c.campus_id);
        const allSelected = allVisibleIds.length > 0 && allVisibleIds.every(id => selectedCampuses.has(id));

        if (allSelected) {
            selectAllBtn.innerHTML = `<i class="ph ph-check-square-offset text-base"></i> Deselect All`;
        } else {
            selectAllBtn.innerHTML = `<i class="ph ph-check-square-offset text-base"></i> Select All`;
        }
    }

    if (multiSelectBtn) {
        multiSelectBtn.addEventListener('click', () => {
            isMultiSelectMode = true;
            updateMultiSelectButtons();
            renderTable(allCampuses);
        });
    }

    if (cancelSelectionBtn) {
        cancelSelectionBtn.addEventListener('click', () => {
            isMultiSelectMode = false;
            selectedCampuses.clear();
            updateMultiSelectButtons();
            renderTable(allCampuses);
        });
    }

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', () => {
            const allVisibleIds = allCampuses.map(c => c.campus_id);
            const allSelected = allVisibleIds.length > 0 && allVisibleIds.every(id => selectedCampuses.has(id));

            if (allSelected) {
                allVisibleIds.forEach(id => selectedCampuses.delete(id));
            } else {
                allVisibleIds.forEach(id => selectedCampuses.add(id));
            }
            renderTable(allCampuses);
            updateMultiSelectButtons();
        });
    }

    campusTableBody.addEventListener("click", (e) => {
        if (!isMultiSelectMode) return;
        const row = e.target.closest("tr");
        if (!row || e.target.closest('.status-badge')) return;

        const campusId = parseInt(row.dataset.campusId);
        if (selectedCampuses.has(campusId)) {
            selectedCampuses.delete(campusId);
        } else {
            selectedCampuses.add(campusId);
        }
        renderTable(allCampuses);
        updateMultiSelectButtons();
    });

    // Modal helpers
    function openModal(isEdit = false) {
        modalTitle.innerHTML = isEdit ? 
            `<i class="ph ph-pencil-simple text-blue-600 text-xl"></i> Edit Campus` : 
            `<i class="ph ph-buildings text-orange-600 text-xl"></i> Add New Campus`;
        
        campusModal.classList.remove("hidden");
        document.body.classList.add("overflow-hidden");
    }

    function closeModal() {
        campusModal.classList.add("hidden");
        document.body.classList.remove("overflow-hidden");
        campusForm.reset();
        campusIdInput.value = "";
    }

    addCampusBtn.addEventListener("click", () => openModal(false));
    closeButtons.forEach(btn => btn.addEventListener("click", closeModal));

    // Form submission
    campusForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        const id = campusIdInput.value;
        const name = campusNameInput.value.trim();
        const code = campusCodeInput.value.trim().toUpperCase();
        const isEdit = !!id;

        const url = isEdit ? `api/superadmin/campuses/update/${id}` : `api/superadmin/campuses/store`;
        const formData = new FormData();
        formData.append("campus_name", name);
        formData.append("campus_code", code);

        showLoadingModal(isEdit ? "Updating Campus..." : "Adding New Campus...", "Please wait.");

        try {
            const response = await fetch(url, {
                method: "POST",
                body: formData
            });
            const data = await response.json();

            Swal.close();

            if (data.success) {
                showSuccessToast(isEdit ? "Campus Updated" : "Campus Added", data.message);
                closeModal();
                loadCampuses();
            } else {
                showErrorToast("Error", data.message);
            }
        } catch (error) {
            Swal.close();
            console.error("Error:", error);
            showErrorToast("Error", "Failed to save campus");
        }
    });

    // Global functions for Edit/Status
    window.editCampus = (id, name, code) => {
        campusIdInput.value = id;
        campusNameInput.value = name;
        campusCodeInput.value = code;
        openModal(true);
    };

    window.toggleCampusStatus = async (id, name, currentStatus) => {
        const newStatus = currentStatus === 'Active' ? 'Inactive' : 'Active';
        const isConfirmed = await showConfirmationModal(
            "Confirm Status Change",
            `Are you sure you want to change the status of campus "${name}" to **${newStatus}**?`,
            `Yes, ${newStatus}`
        );

        if (isConfirmed) {
            showLoadingModal("Updating Status...", `Setting status to ${newStatus}.`);
            try {
                const response = await fetch(`api/superadmin/campuses/toggleStatus/${id}`, {
                    method: "POST"
                });
                const data = await response.json();

                Swal.close();

                if (data.success) {
                    showSuccessToast("Status Updated", data.message);
                    loadCampuses();
                } else {
                    showErrorToast("Error", data.message);
                }
            } catch (error) {
                Swal.close();
                console.error("Error:", error);
                showErrorToast("Error", "Failed to update campus status");
            }
        }
    };

    loadCampuses();
});
