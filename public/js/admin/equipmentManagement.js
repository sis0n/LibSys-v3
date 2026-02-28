document.addEventListener("DOMContentLoaded", () => {
    // --- SweetAlert Helper Functions (Identical to User Management) ---

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

    // --- State ---
    let equipments = [];
    let currentPage = 1;
    let currentSearch = "";
    let currentStatus = "All Status";
    const limit = 10;

    // --- Elements ---
    const tableBody = document.getElementById("eqTableBody");
    const resultsIndicator = document.getElementById("eqResultsIndicator");
    const searchInput = document.getElementById("eqSearchInput");
    const addModal = document.getElementById("addEqModal");
    const editModal = document.getElementById("editEqModal");
    const addForm = document.getElementById("addEqForm");
    const editForm = document.getElementById("editEqForm");

    // --- Core Functions ---

    function getStatusBadge(isActive) {
        const base = "px-2 py-1 text-[10px] rounded-md font-bold uppercase tracking-wider transition-all";
        return isActive 
            ? `<span class="bg-green-500 text-white hover:bg-green-600 ${base}">Active</span>` 
            : `<span class="bg-gray-300 text-gray-700 hover:bg-gray-400 ${base}">Inactive</span>`;
    }

    function getConditionClass(status) {
        switch(status.toLowerCase()) {
            case 'available': return 'bg-green-100 text-green-700 border-green-200';
            case 'borrowed':  return 'bg-orange-100 text-orange-700 border-orange-200';
            case 'damaged':   return 'bg-red-100 text-red-700 border-red-200';
            case 'maintenance': return 'bg-blue-100 text-blue-700 border-blue-200';
            default: return 'bg-gray-100 text-gray-700 border-gray-200';
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
            limit: limit,
            offset: offset
        });

        try {
            const res = await fetch(`api/admin/equipmentManagement/fetch?${params}`);
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
            tableBody.innerHTML = `<tr><td colspan="6" class="py-12 text-center text-gray-500">No equipment found matching filters.</td></tr>`;
            return;
        }

        equipments.forEach(eq => {
            const row = document.createElement("tr");
            row.className = `hover:bg-orange-50/50 transition-colors ${!eq.is_active ? 'bg-gray-50/50 opacity-70' : ''}`;
            
            row.innerHTML = `
                <td class="px-6 py-4 font-medium text-gray-800">${eq.equipment_name}</td>
                <td class="px-6 py-4 text-gray-600 font-mono text-xs">${eq.asset_tag || 'N/A'}</td>
                <td class="px-6 py-4">
                    <span class="w-fit px-2.5 py-0.5 rounded-full text-[10px] font-bold border ${getConditionClass(eq.status)}">
                        ${eq.status.toUpperCase()}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <div class="toggle-status-btn cursor-pointer w-fit" onclick="toggleActive(${eq.equipment_id}, ${eq.is_active}, '${eq.equipment_name}')">
                        ${getStatusBadge(eq.is_active)}
                    </div>
                </td>
                <td class="px-6 py-4 text-gray-500 text-xs">${new Date(eq.updated_at).toLocaleDateString(undefined, {year:'numeric', month:'short', day:'numeric'})}</td>
                <td class="px-6 py-4 text-center">
                    <button onclick="editEq(${eq.equipment_id})" class="inline-flex items-center gap-1.5 border border-orange-200 text-orange-700 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-orange-100 transition shadow-sm">
                        <i class="ph ph-note-pencil text-base"></i> Edit
                    </button>
                </td>
            `;
            tableBody.appendChild(row);
        });
    }

    // --- Action Handlers ---

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
        const startTime = Date.now();

        try {
            const fd = new FormData();
            fd.append('is_active', newStatus);
            
            const res = await fetch(`api/admin/equipmentManagement/toggleActive/${id}`, { method: "POST", body: fd });
            const data = await res.json();

            const elapsed = Date.now() - startTime;
            if (elapsed < 500) await new Promise(r => setTimeout(r, 500 - elapsed));
            Swal.close();

            if (data.success) {
                showSuccessToast("Status Updated", `${name} is now ${newStatusText}.`);
                loadEquipments(currentPage, false);
            } else {
                showErrorToast("Update Failed", data.message);
            }
        } catch (err) {
            Swal.close();
            showErrorToast("Connection Error", "An error occurred while updating status.");
        }
    };

    window.editEq = async (id) => {
        showLoadingModal("Fetching Data...", "Getting item details.");
        try {
            const res = await fetch(`api/admin/equipmentManagement/get/${id}`);
            const data = await res.json();
            Swal.close();

            if (data.success) {
                document.getElementById("edit_equipment_id").value = data.equipment.equipment_id;
                document.getElementById("edit_equipment_name").value = data.equipment.equipment_name;
                document.getElementById("edit_asset_tag").value = data.equipment.asset_tag || "";
                document.getElementById("edit_status").value = data.equipment.status;
                editModal.classList.remove("hidden");
                document.body.classList.add("overflow-hidden");
            }
        } catch (err) {
            Swal.close();
            showErrorToast("Error", "Could not fetch item details.");
        }
    };

    addForm.onsubmit = async (e) => {
        e.preventDefault();
        const fd = new FormData(addForm);
        showLoadingModal("Adding Equipment...", "Saving to inventory.");
        
        try {
            const res = await fetch(`api/admin/equipmentManagement/store`, { method: "POST", body: fd });
            const data = await res.json();
            await new Promise(r => setTimeout(r, 500));
            Swal.close();

            if (data.success) {
                showSuccessToast("Success", data.message);
                addModal.classList.add("hidden");
                document.body.classList.remove("overflow-hidden");
                addForm.reset();
                loadEquipments(1, false);
            } else showErrorToast("Error", data.message);
        } catch (err) {
            Swal.close();
            showErrorToast("Error", "Server connection failed.");
        }
    };

    editForm.onsubmit = async (e) => {
        e.preventDefault();
        const id = document.getElementById("edit_equipment_id").value;
        const fd = new FormData(editForm);
        showLoadingModal("Saving Changes...", "Updating record.");

        try {
            const res = await fetch(`api/admin/equipmentManagement/update/${id}`, { method: "POST", body: fd });
            const data = await res.json();
            await new Promise(r => setTimeout(r, 500));
            Swal.close();

            if (data.success) {
                showSuccessToast("Updated", data.message);
                editModal.classList.add("hidden");
                document.body.classList.remove("overflow-hidden");
                loadEquipments(currentPage, false);
            } else showErrorToast("Error", data.message);
        } catch (err) {
            Swal.close();
            showErrorToast("Error", "Server connection failed.");
        }
    };

    // --- Utility Functions ---

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

    // --- Search & Filter ---
    let debounceTimer;
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

    document.getElementById("eqStatusDropdownBtn").onclick = (e) => {
        e.stopPropagation();
        document.getElementById("eqStatusDropdownMenu").classList.toggle("hidden");
    };

    // Modal Controls
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
    });

    loadEquipments(1);
});
