document.addEventListener("DOMContentLoaded", () => {
    const tableBody = document.getElementById("restoreEqTableBody");
    const searchInput = document.getElementById("restoreEqSearch");
    let deletedItems = [];

    async function loadDeleted() {
        tableBody.innerHTML = `<tr><td colspan="4" class="py-10 text-center"><i class="ph ph-spinner animate-spin text-2xl text-orange-500"></i></td></tr>`;
        try {
            const res = await fetch(`api/campus_admin/restoreEquipment/fetch`);
            const data = await res.json();
            if (data.success) {
                deletedItems = data.equipments;
                renderTable(deletedItems);
            }
        } catch (err) {
            console.error(err);
        }
    }

    function renderTable(items) {
        tableBody.innerHTML = "";
        if (items.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="4" class="py-12 text-center text-gray-500">No deleted items found.</td></tr>`;
            return;
        }

        items.forEach(item => {
            const row = `
                <tr class="hover:bg-orange-50 transition-colors">
                    <td class="px-4 py-4 font-medium text-gray-800">${item.equipment_name}</td>
                    <td class="px-4 py-4 text-gray-600">${item.asset_tag || 'N/A'}</td>
                    <td class="px-4 py-4 text-gray-500">${new Date(item.deleted_at).toLocaleString()}</td>
                    <td class="px-4 py-4 text-center">
                        <div class="flex justify-center gap-2">
                            <button onclick="restoreEq(${item.equipment_id}, '${item.equipment_name}')" class="flex items-center gap-1 bg-green-100 text-green-700 px-3 py-1.5 rounded-md hover:bg-green-200 transition text-xs font-semibold">
                                <i class="ph ph-arrows-counter-clockwise"></i> Restore
                            </button>
                            <button onclick="permanentDeleteEq(${item.equipment_id}, '${item.equipment_name}')" class="flex items-center gap-1 bg-red-100 text-red-700 px-3 py-1.5 rounded-md hover:bg-red-200 transition text-xs font-semibold">
                                <i class="ph ph-trash"></i> Delete
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            tableBody.insertAdjacentHTML("beforeend", row);
        });
    }

    window.restoreEq = async (id, name) => {
        const confirm = await Swal.fire({
            title: "Restore Equipment?",
            text: `Bring "${name}" back to the active inventory?`,
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#10b981",
            confirmButtonText: "Yes, restore it"
        });

        if (confirm.isConfirmed) {
            const res = await fetch(`api/campus_admin/restoreEquipment/restore/${id}`, { method: "POST" });
            const data = await res.json();
            if (data.success) {
                Swal.fire("Restored!", data.message, "success");
                loadDeleted();
            }
        }
    };

    window.permanentDeleteEq = async (id, name) => {
        const confirm = await Swal.fire({
            title: "Permanent Delete?",
            text: `Warning: This will permanently delete "${name}". This action cannot be undone!`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ef4444",
            confirmButtonText: "Yes, delete forever"
        });

        if (confirm.isConfirmed) {
            const res = await fetch(`api/campus_admin/restoreEquipment/deletePermanently/${id}`, { method: "POST" });
            const data = await res.json();
            if (data.success) {
                Swal.fire("Deleted!", data.message, "success");
                loadDeleted();
            }
        }
    };

    searchInput.addEventListener("input", (e) => {
        const term = e.target.value.toLowerCase();
        const filtered = deletedItems.filter(i => 
            i.equipment_name.toLowerCase().includes(term) || 
            (i.asset_tag && i.asset_tag.toLowerCase().includes(term))
        );
        renderTable(filtered);
    });

    loadDeleted();
});
