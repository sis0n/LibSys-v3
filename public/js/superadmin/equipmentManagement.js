document.addEventListener("DOMContentLoaded", () => {
    // --- SweetAlert Helper Functions ---

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
            customClass: { popup: "!rounded-xl !shadow-md !border-2 !border-green-400 !p-4 !bg-gradient-to-b !from-[#fffdfb] !to-[#f0fff5] shadow-[0_0_8px_#22c55e70]" },
        });
    }

    function showErrorToast(title, body = "Please check the details.") {
        if (typeof Swal == "undefined") return alert(title);
        Swal.fire({
            toast: true,
            position: "bottom-end",
            showConfirmButton: false,
            timer: 4000,
            width: "360px",
            background: "transparent",
            html: `<div class="flex flex-col text-left"><div class="flex items-center gap-3 mb-2"><div class="flex items-center justify-center w-10 h-10 rounded-full bg-red-100 text-red-600"><i class="ph ph-x-circle text-lg"></i></div><div><h3 class="text-[15px] font-semibold text-red-600">${title}</h3><p class="text-[13px] text-gray-700 mt-0.5">${body}</p></div></div></div>`,
            customClass: { popup: "!rounded-xl !shadow-md !border-2 !border-red-400 !p-4 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_8px_#ff6b6b70]" },
        });
    }

    function showLoadingModal(message = "Processing request...", subMessage = "Please wait.") {
        if (typeof Swal == "undefined") return;
        Swal.fire({
            background: "transparent",
            html: `<div class="flex flex-col items-center justify-center gap-2"><div class="animate-spin rounded-full h-10 w-10 border-4 border-orange-200 border-t-orange-600"></div><p class="text-gray-700 text-[14px]">${message}<br><span class="text-sm text-gray-500">${subMessage}</span></p></div>`,
            allowOutsideClick: false,
            showConfirmButton: false,
            customClass: { popup: "!rounded-xl !shadow-md !border-2 !border-orange-400 !p-6 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_8px_#ffb34770]" },
        });
    }

    async function showConfirmationModal(title, text, confirmText = "Confirm", isDelete = false) {
        if (typeof Swal == "undefined") return confirm(title);
        const result = await Swal.fire({
            width: '400px',
            html: `
                <div class="flex flex-col text-center">
                    <div class="flex justify-center mb-3">
                        <div class="flex items-center justify-center w-16 h-16 rounded-full ${isDelete ? 'bg-red-100 text-red-600' : 'bg-orange-100 text-orange-600'}">
                            <i class="ph ${isDelete ? 'ph-trash' : 'ph-warning-circle'} text-3xl"></i>
                        </div>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">${title}</h3>
                    <p class="text-[13px] text-gray-700 mt-1">${text}</p>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: "Cancel",
            confirmButtonColor: isDelete ? '#ef4444' : '#f97316',
            cancelButtonColor: '#e5e7eb',
            customClass: {
                popup: "!rounded-xl !border-2 " + (isDelete ? "!border-red-400" : "!border-orange-400"),
                confirmButton: "!px-5 !py-2 !text-sm !font-bold !rounded-lg !text-white",
                cancelButton: "!px-5 !py-2 !text-sm !font-bold !rounded-lg !text-gray-700"
            },
        });
        return result.isConfirmed;
    }

    // --- State ---
    let equipments = [];
    let currentPage = 1;
    let currentSearch = "";
    let currentStatus = "All Status";
    let currentCampus = "";
    let debounceTimer;
    let isMultiSelectMode = false;
    let selectedEqIds = new Set();
    const limit = 10;

    // --- Elements ---
    const tableBody = document.getElementById("eqTableBody");
    const resultsIndicator = document.getElementById("eqResultsIndicator");
    const searchInput = document.getElementById("eqSearchInput");
    const addModal = document.getElementById("addEqModal");
    const editModal = document.getElementById("editEqModal");
    const addForm = document.getElementById("addEqForm");
    const editForm = document.getElementById("editEqForm");

    const multiSelectBtn = document.getElementById("multiSelectBtn");
    const multiSelectActions = document.getElementById("multiSelectActions");
    const selectAllBtn = document.getElementById("selectAllBtn");
    const cancelSelectionBtn = document.getElementById("cancelSelectionBtn");
    const multiDeleteBtn = document.getElementById("multiDeleteBtn");
    const selectionCount = document.getElementById("selectionCount");

    // --- Core Functions ---

    function getStatusBadge(isActive) {
        const base = "px-3 py-1 text-xs rounded-full font-semibold transition-all";
        return isActive
            ? `<span class="bg-emerald-100 text-emerald-700 ${base}">Active</span>`
            : `<span class="bg-rose-100 text-rose-700 ${base}">Inactive</span>`;
    }

    function getConditionClass(status) {
        switch(status.toLowerCase()) {
            case 'available': return 'bg-emerald-100 text-emerald-700';
            case 'borrowed':  return 'bg-blue-100 text-blue-700';
            case 'damaged':   return 'bg-rose-100 text-rose-700';
            case 'lost':      return 'bg-rose-100 text-rose-700';
            case 'maintenance': return 'bg-amber-100 text-amber-700';
            default: return 'bg-gray-100 text-gray-700';
        }
    }

    async function loadEquipments(page = 1, showModal = true) {
        if (showModal) showLoadingModal("Loading Inventory...", "Fetching equipment records.");
        const startTime = Date.now();
        currentPage = page;

        const offset = (page - 1) * limit;
        const params = new URLSearchParams({
            search: currentSearch,
            status: currentStatus === "All Status" ? "" : currentStatus,
            campus_id: currentCampus,
            limit: limit,
            offset: offset
        });

        try {
            const res = await fetch(`api/superadmin/equipmentManagement/fetch?${params}`);
            const data = await res.json();

            if (showModal) {
                const elapsed = Date.now() - startTime;
                if (elapsed < 500) await new Promise(r => setTimeout(r, 500 - elapsed));
                Swal.close();
            }

            if (data.success) {
                equipments = data.equipments;
                renderTable();
                updateIndicator(data.totalCount);
                renderPagination(data.totalCount);
            }
        } catch (err) {
            if (showModal) Swal.close();
            showErrorToast("Load Failed", "Could not retrieve equipment data.");
        }
    }

    function renderTable() {
        tableBody.innerHTML = "";
        if (equipments.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="${isMultiSelectMode ? '8' : '7'}" class="py-12 text-center text-gray-500">No equipment found matching filters.</td></tr>`;
            return;
        }

        // Update header for multi-select
        const headerRow = document.querySelector("thead tr");
        if (isMultiSelectMode && !headerRow.querySelector(".selection-header")) {
            const th = document.createElement("th");
            th.className = "py-3 px-4 font-semibold text-left selection-header";
            th.innerHTML = '<i class="ph ph-check-square"></i>';
            headerRow.prepend(th);
        } else if (!isMultiSelectMode && headerRow.querySelector(".selection-header")) {
            headerRow.querySelector(".selection-header").remove();
        }

        equipments.forEach(eq => {
            const isSelected = selectedEqIds.has(eq.equipment_id);
            const row = document.createElement("tr");
            row.className = `hover:bg-orange-50/40 transition-colors ${isSelected ? 'bg-orange-100' : ''} ${!eq.is_active ? 'bg-gray-50/50 opacity-70' : ''}`;
            
            let rowHtml = "";
            if (isMultiSelectMode) {
                rowHtml += `
                    <td class="px-6 py-4">
                        <input type="checkbox" class="accent-orange-500" ${isSelected ? "checked" : ""} onchange="toggleEqSelection(${eq.equipment_id})">
                    </td>
                `;
            }

            rowHtml += `
                <td class="px-6 py-4 font-medium text-gray-800">${eq.equipment_name}</td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-orange-700 bg-orange-50 border border-orange-200 px-3 py-1 rounded-full">
                        <i class="ph ph-map-pin"></i>
                        ${eq.campus_name || 'N/A'}
                    </span>
                </td>
                <td class="px-6 py-4 text-gray-600 font-mono text-xs">${eq.asset_tag || 'N/A'}</td>
                <td class="px-6 py-4">
                    <span class="w-fit px-3 py-1 rounded-full text-xs font-semibold ${getConditionClass(eq.status)}">
                        ${eq.status.toUpperCase()}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <div class="toggle-status-btn cursor-pointer w-fit" onclick="toggleActive(${eq.equipment_id}, ${eq.is_active}, '${eq.equipment_name}')">
                        ${getStatusBadge(eq.is_active)}
                    </div>
                </td>
                <td class="px-6 py-4 text-gray-500 text-xs">${new Date(eq.updated_at).toLocaleDateString(undefined, {year:'numeric', month:'short', day:'numeric'})}</td>
                <td class="px-6 py-4 text-right">
                    <div class="flex justify-end gap-2">
                        <button onclick="editEq(${eq.equipment_id})" class="inline-flex items-center gap-1.5 border border-orange-200 text-orange-700 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-orange-100 transition shadow-sm">
                            <i class="ph ph-note-pencil text-base"></i> Edit
                        </button>
                        <button onclick="deleteEq(${eq.equipment_id}, '${eq.equipment_name}')" class="inline-flex items-center gap-1.5 border border-red-200 text-red-600 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-red-50 transition shadow-sm">
                            <i class="ph ph-trash text-base"></i> Delete
                        </button>
                    </div>
                </td>
            `;
            row.innerHTML = rowHtml;
            tableBody.appendChild(row);
        });
    }

    window.toggleEqSelection = (id) => {
        if (selectedEqIds.has(id)) selectedEqIds.delete(id);
        else selectedEqIds.add(id);
        selectionCount.textContent = selectedEqIds.size;
        renderTable();
    };

    multiSelectBtn.addEventListener("click", () => {
        isMultiSelectMode = true;
        multiSelectBtn.classList.add("hidden");
        multiSelectActions.classList.remove("hidden");
        renderTable();
    });

    cancelSelectionBtn.addEventListener("click", () => {
        isMultiSelectMode = false;
        selectedEqIds.clear();
        selectionCount.textContent = "0";
        multiSelectBtn.classList.remove("hidden");
        multiSelectActions.classList.add("hidden");
        renderTable();
    });

    selectAllBtn.addEventListener("click", () => {
        if (selectedEqIds.size === equipments.length) selectedEqIds.clear();
        else equipments.forEach(eq => selectedEqIds.add(eq.equipment_id));
        selectionCount.textContent = selectedEqIds.size;
        renderTable();
    });

    multiDeleteBtn.addEventListener("click", async () => {
        const count = selectedEqIds.size;
        if (count === 0) return;

        const confirmed = await showConfirmationModal(
            "Bulk Delete Equipment",
            `Are you sure you want to delete ${count} equipment items?`,
            "Yes, Delete Them!",
            true
        );
        if (!confirmed) return;

        try {
            showLoadingModal("Processing Request...", "Please wait.");
            const response = await fetch("api/superadmin/equipmentManagement/deleteMultiple", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ 
                    equipment_ids: Array.from(selectedEqIds)
                }),
            });
            const data = await response.json();
            Swal.close();

            if (data.success) {
                showSuccessToast("Success", `Successfully deleted ${data.deleted_count} item(s)!`);
                selectedEqIds.clear();
                cancelSelectionBtn.click();
                loadEquipments(currentPage, false);
            } else {
                showErrorToast("Error", data.message);
            }
        } catch (error) {
            Swal.close();
            console.error("Error bulk deleting equipment:", error);
            showErrorToast("Error", "A server error occurred.");
        }
    });

    window.toggleActive = async (id, currentIsActive, name) => {
        const newStatus = currentIsActive ? 0 : 1;
        const newStatusText = newStatus ? 'Active' : 'Inactive';

        const isConfirmed = await showConfirmationModal(
            "Confirm Status Change",
            `Are you sure you want to set <b>${name}</b> to <b class="${newStatus ? 'text-green-600' : 'text-red-600'}">${newStatusText}</b>?`,
            `Yes, ${newStatusText}`
        );

        if (!isConfirmed) return;

        showLoadingModal("Updating Status...", `Setting status to ${newStatusText}.`);
        try {
            const fd = new FormData();
            fd.append('is_active', newStatus);
            const res = await fetch(`api/superadmin/equipmentManagement/toggleActive/${id}`, { method: "POST", body: fd });
            const data = await res.json();
            Swal.close();
            if (data.success) {
                showSuccessToast("Status Updated", `${name} is now ${newStatusText}.`);
                loadEquipments(currentPage, false);
            } else showErrorToast("Update Failed", data.message);
        } catch (err) { Swal.close(); showErrorToast("Connection Error", "An error occurred."); }
    };

    window.editEq = async (id) => {
        showLoadingModal("Fetching Data...", "Getting item details.");
        try {
            const res = await fetch(`api/superadmin/equipmentManagement/get/${id}`);
            const data = await res.json();
            Swal.close();
            if (data.success) {
                document.getElementById("edit_equipment_id").value = data.equipment.equipment_id;
                document.getElementById("edit_equipment_name").value = data.equipment.equipment_name;
                document.getElementById("edit_campus_id").value = data.equipment.campus_id || "";
                document.getElementById("edit_asset_tag").value = data.equipment.asset_tag || "";
                document.getElementById("edit_status").value = data.equipment.status;
                editModal.classList.remove("hidden");
                document.body.classList.add("overflow-hidden");
            }
        } catch (err) { Swal.close(); showErrorToast("Error", "Could not fetch details."); }
    };

    window.deleteEq = async (id, name) => {
        const isConfirmed = await showConfirmationModal(
            "Delete Equipment",
            `Are you sure you want to delete <b>${name}</b>? This action will move the item to the archive.`,
            "Yes, Delete",
            true
        );

        if (!isConfirmed) return;

        showLoadingModal("Deleting...", "Moving equipment to archive.");
        try {
            const res = await fetch(`api/superadmin/equipmentManagement/delete/${id}`, { method: "POST" });
            const data = await res.json();
            Swal.close();
            if (data.success) {
                showSuccessToast("Deleted", "Equipment moved to archive.");
                loadEquipments(currentPage, false);
            } else showErrorToast("Error", data.message);
        } catch (err) { Swal.close(); showErrorToast("Error", "Server connection failed."); }
    };

    addForm.onsubmit = async (e) => {
        e.preventDefault();
        const fd = new FormData(addForm);
        showLoadingModal("Adding Equipment...", "Saving to inventory.");
        try {
            const res = await fetch(`api/superadmin/equipmentManagement/store`, { method: "POST", body: fd });
            const data = await res.json();
            Swal.close();
            if (data.success) {
                showSuccessToast("Success", data.message);
                addModal.classList.add("hidden");
                document.body.classList.remove("overflow-hidden");
                addForm.reset();
                loadEquipments(1, false);
            } else showErrorToast("Error", data.message);
        } catch (err) { Swal.close(); showErrorToast("Error", "Server connection failed."); }
    };

    editForm.onsubmit = async (e) => {
        e.preventDefault();
        const id = document.getElementById("edit_equipment_id").value;
        const fd = new FormData(editForm);
        showLoadingModal("Saving Changes...", "Updating record.");
        try {
            const res = await fetch(`api/superadmin/equipmentManagement/update/${id}`, { method: "POST", body: fd });
            const data = await res.json();
            Swal.close();
            if (data.success) {
                showSuccessToast("Updated", data.message);
                editModal.classList.add("hidden");
                document.body.classList.remove("overflow-hidden");
                loadEquipments(currentPage, false);
            } else showErrorToast("Error", data.message);
        } catch (err) { Swal.close(); showErrorToast("Error", "Server connection failed."); }
    };

    function updateIndicator(total) {
        const start = equipments.length ? (currentPage - 1) * limit + 1 : 0;
        const end = (currentPage - 1) * limit + equipments.length;
        resultsIndicator.innerHTML = `Showing <span class="font-bold text-gray-800">${start}-${end}</span> of <span class="font-bold text-gray-800">${total}</span> items`;
    }

    function renderPagination(total) {
        const list = document.getElementById("eqPaginationList");
        const totalPages = Math.ceil(total / limit);
        list.innerHTML = "";
        if (totalPages <= 1) {
            document.getElementById("eqPaginationControls").classList.add("hidden");
            return;
        }
        document.getElementById("eqPaginationControls").classList.remove("hidden");
        for (let i = 1; i <= totalPages; i++) {
            const li = document.createElement("li");
            const btn = document.createElement("button");
            btn.className = `w-8 h-8 rounded-full text-xs font-bold transition ${i === currentPage ? 'bg-orange-600 text-white shadow-md' : 'text-gray-600 hover:bg-orange-100'}`;
            btn.textContent = i;
            btn.onclick = () => loadEquipments(i);
            li.appendChild(btn);
            list.appendChild(li);
        }
    }

    searchInput.addEventListener("input", (e) => {
        currentSearch = e.target.value;
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => loadEquipments(1, false), 500);
    });

    window.selectEqStatus = (el, status) => {
        currentStatus = status;
        document.getElementById("eqStatusDropdownValue").textContent = status;
        document.getElementById("eqStatusDropdownMenu").classList.add("hidden");
        loadEquipments(1, false);
    };

    window.selectEqCampus = (el, campusId, campusName) => {
        currentCampus = campusId;
        document.getElementById("eqCampusDropdownValue").textContent = campusName;
        document.getElementById("eqCampusDropdownMenu").classList.add("hidden");
        loadEquipments(1, false);
    };

    document.getElementById("eqStatusDropdownBtn").onclick = (e) => {
        e.stopPropagation();
        document.getElementById("eqCampusDropdownMenu").classList.add("hidden");
        document.getElementById("eqStatusDropdownMenu").classList.toggle("hidden");
    };

    document.getElementById("eqCampusDropdownBtn").onclick = (e) => {
        e.stopPropagation();
        document.getElementById("eqStatusDropdownMenu").classList.add("hidden");
        document.getElementById("eqCampusDropdownMenu").classList.toggle("hidden");
    };

    document.getElementById("openAddEqBtn").onclick = () => {
        addModal.classList.remove("hidden");
        document.body.classList.add("overflow-hidden");
    };
    document.getElementById("closeAddEqModal").onclick = () => {
        addModal.classList.add("hidden");
        document.body.classList.remove("overflow-hidden");
    };
    document.getElementById("cancelAddEq").onclick = () => {
        addModal.classList.add("hidden");
        document.body.classList.remove("overflow-hidden");
    };
    document.getElementById("closeEditEqModal").onclick = () => {
        editModal.classList.add("hidden");
        document.body.classList.remove("overflow-hidden");
    };
    document.getElementById("cancelEditEq").onclick = () => {
        editModal.classList.add("hidden");
        document.body.classList.remove("overflow-hidden");
    };

    document.addEventListener("click", () => {
        document.getElementById("eqStatusDropdownMenu").classList.add("hidden");
        document.getElementById("eqCampusDropdownMenu").classList.add("hidden");
    });

    loadEquipments(1);
});
