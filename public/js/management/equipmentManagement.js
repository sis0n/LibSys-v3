/**
 * Unified Equipment Management JS
 */

let eqPage = 1;
let eqSearch = '';
let eqCampus = '';
let eqStatus = '';

const EQ_API_BASE = `${BASE_URL_JS}/api/equipmentManagement`;

document.addEventListener('DOMContentLoaded', () => {
    loadEquipment();
    loadEqCampuses();
    initEqEventListeners();
});

function initEqEventListeners() {
    // Search
    const searchInput = document.getElementById('eqSearchInput');
    if (searchInput) {
        let timeout = null;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                eqSearch = e.target.value;
                eqPage = 1;
                loadEquipment();
            }, 500);
        });
    }

    // Modal Toggles
    setupModal('openAddEqBtn', 'addEqModal', 'closeAddEqModal', 'cancelAddEq');
    setupModal(null, 'editEqModal', 'closeEditEqModal', 'cancelEditEq');

    // Forms
    const addForm = document.getElementById('addEqForm');
    if (addForm) {
        addForm.addEventListener('submit', handleAddEq);
    }

    const editForm = document.getElementById('editEqForm');
    if (editForm) {
        editForm.addEventListener('submit', handleEditEq);
    }

    // Dropdowns
    setupEqDropdown('eqStatusDropdownBtn', 'eqStatusDropdownMenu');
    setupEqDropdown('eqCampusDropdownBtn', 'eqCampusDropdownMenu');

    window.addEventListener('click', (e) => {
        if (!e.target.closest('[id$="DropdownBtn"]')) {
            document.querySelectorAll('[id$="DropdownMenu"]').forEach(m => m.classList.add('hidden'));
        }
    });
}

function setupModal(openBtnId, modalId, closeBtnId, cancelBtnId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    if (openBtnId) {
        const btn = document.getElementById(openBtnId);
        if (btn) btn.onclick = () => modal.classList.remove('hidden');
    }
    const closeBtn = document.getElementById(closeBtnId);
    if (closeBtn) closeBtn.onclick = () => modal.classList.add('hidden');
    const cancelBtn = document.getElementById(cancelBtnId);
    if (cancelBtn) cancelBtn.onclick = () => modal.classList.add('hidden');
}

function setupEqDropdown(btnId, menuId) {
    const btn = document.getElementById(btnId);
    const menu = document.getElementById(menuId);
    if (btn && menu) {
        btn.onclick = (e) => {
            e.stopPropagation();
            const isHidden = menu.classList.contains('hidden');
            document.querySelectorAll('[id$="DropdownMenu"]').forEach(m => m.classList.add('hidden'));
            if (isHidden) menu.classList.remove('hidden');
        };
    }
}

async function loadEquipment() {
    const tbody = document.getElementById('eqTableBody');
    if (!tbody) return;

    tbody.innerHTML = `<tr><td colspan="7" class="py-10 text-center text-gray-500"><i class="ph ph-spinner animate-spin text-2xl"></i></td></tr>`;

    try {
        const params = new URLSearchParams({
            page: eqPage,
            search: eqSearch,
            campus_id: eqCampus,
            status: eqStatus
        });

        const response = await fetch(`${EQ_API_BASE}/fetch?${params}`);
        const data = await response.json();

        if (data.equipments && data.equipments.length > 0) {
            renderEquipment(data.equipments);
            renderEqPagination(data.totalCount);
        } else {
            tbody.innerHTML = `<tr><td colspan="7" class="py-10 text-center text-gray-500">No equipment found.</td></tr>`;
            document.getElementById('eqPaginationControls').classList.add('hidden');
        }
        
        const indicator = document.getElementById('eqResultsIndicator');
        if (indicator) indicator.textContent = `Showing ${data.totalCount || 0} items total`;
    } catch (error) {
        console.error('Error loading equipment:', error);
        tbody.innerHTML = `<tr><td colspan="7" class="py-10 text-center text-red-500">Failed to load data.</td></tr>`;
    }
}

function renderEquipment(items) {
    const tbody = document.getElementById('eqTableBody');
    tbody.innerHTML = '';

    items.forEach(item => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-orange-50/50 transition-colors';
        tr.innerHTML = `
            <td class="py-3 px-4 font-medium text-gray-900">${item.equipment_name}</td>
            <td class="py-3 px-4 text-gray-600">${item.campus_name}</td>
            <td class="py-3 px-4 font-mono text-xs text-orange-600 font-bold">${item.asset_tag}</td>
            <td class="py-3 px-4">
                ${getEqStatusBadge(item.status)}
            </td>
            <td class="py-3 px-4">
                <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase ${item.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                    ${item.is_active ? 'Active' : 'Inactive'}
                </span>
            </td>
            <td class="py-3 px-4 text-xs text-gray-500">${new Date(item.updated_at).toLocaleDateString()}</td>
            <td class="py-3 px-4 text-right">
                <div class="flex items-center justify-end gap-1">
                    <button onclick="editEq(${item.equipment_id})" class="p-2 text-orange-600 hover:bg-orange-50 rounded-lg transition">
                        <i class="ph ph-pencil-simple text-lg"></i>
                    </button>
                    <button onclick="deleteEq(${item.equipment_id}, '${item.equipment_name.replace(/'/g, "\\'")}')" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                        <i class="ph ph-trash text-lg"></i>
                    </button>
                </div>
            </td>
        `;

        tbody.appendChild(tr);
    });
}

function getEqStatusBadge(status) {
    const s = status.toLowerCase();
    const colors = {
        available: 'bg-green-100 text-green-700 border-green-200',
        borrowed: 'bg-blue-100 text-blue-700 border-blue-200',
        damaged: 'bg-yellow-100 text-yellow-700 border-yellow-200',
        maintenance: 'bg-orange-100 text-orange-700 border-orange-200',
        lost: 'bg-red-100 text-red-700 border-red-200'
    };
    const colorClass = colors[s] || 'bg-gray-100 text-gray-700 border-gray-200';
    return `<span class="px-2.5 py-1 rounded-full text-[10px] font-bold border ${colorClass} uppercase tracking-wider">${status}</span>`;
}

function renderEqPagination(total) {
    const totalPages = Math.ceil(total / 10);
    const container = document.getElementById('eqPaginationControls');
    const list = document.getElementById('eqPaginationList');
    
    if (totalPages <= 1) {
        container.classList.add('hidden');
        return;
    }
    container.classList.remove('hidden');
    list.innerHTML = '';

    for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = `w-9 h-9 flex items-center justify-center rounded-lg cursor-pointer transition ${i === eqPage ? 'bg-orange-500 text-white font-bold' : 'hover:bg-orange-100 text-gray-600'}`;
        li.textContent = i;
        li.onclick = () => { eqPage = i; loadEquipment(); };
        list.appendChild(li);
    }
}

async function handleAddEq(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    try {
        const response = await fetch(`${EQ_API_BASE}/store`, { method: 'POST', body: formData });
        const res = await response.json();
        if (response.ok) {
            Swal.fire('Success', res.message, 'success');
            e.target.reset();
            document.getElementById('addEqModal').classList.add('hidden');
            loadEquipment();
        } else { Swal.fire('Error', res.error, 'error'); }
    } catch (e) { Swal.fire('Error', 'Network error', 'error'); }
}

async function editEq(id) {
    try {
        const response = await fetch(`${EQ_API_BASE}/get/${id}`);
        const data = await response.json();
        const eq = data.equipments ? data.equipments[0] : data.equipment;

        if (!eq) throw new Error('Equipment not found');

        const form = document.getElementById('editEqForm');
        form.equipment_id.value = eq.equipment_id;
        form.equipment_name.value = eq.equipment_name;
        form.status.value = eq.status.toLowerCase();
        form.asset_tag.value = eq.asset_tag;
        
        if (form.campus_id && !form.campus_id.disabled) {
            form.campus_id.value = eq.campus_id;
        }

        document.getElementById('editEqModal').classList.remove('hidden');
    } catch (e) { Swal.fire('Error', 'Failed to load details', 'error'); }
}

async function handleEditEq(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    try {
        const response = await fetch(`${EQ_API_BASE}/update`, { method: 'POST', body: formData });
        const res = await response.json();
        if (response.ok) {
            Swal.fire('Success', res.message, 'success');
            document.getElementById('editEqModal').classList.add('hidden');
            loadEquipment();
        } else { Swal.fire('Error', res.error, 'error'); }
    } catch (e) { Swal.fire('Error', 'Network error', 'error'); }
}

async function deleteEq(id, name) {
    const res = await Swal.fire({
        title: 'Deactivate?',
        text: `Are you sure you want to deactivate "${name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, deactivate'
    });
    if (res.isConfirmed) {
        try {
            const response = await fetch(`${EQ_API_BASE}/destroy/${id}`, { method: 'POST' });
            if (response.ok) {
                Swal.fire('Deactivated', 'Item deactivated successfully.', 'success');
                loadEquipment();
            } else { Swal.fire('Error', 'Action failed', 'error'); }
        } catch (e) { Swal.fire('Error', 'Network error', 'error'); }
    }
}

// Campus/Status Selection
window.selectEqCampus = (el, id, name) => {
    eqCampus = id;
    document.getElementById('eqCampusDropdownValue').textContent = name;
    eqPage = 1;
    loadEquipment();
};

window.selectEqStatus = (el, name) => {
    eqStatus = name === 'All Status' ? '' : name;
    document.getElementById('eqStatusDropdownValue').textContent = name;
    eqPage = 1;
    loadEquipment();
};

async function loadEqCampuses() {
    const menu = document.getElementById('eqCampusDropdownMenu');
    if (!menu) return;
    try {
        const response = await fetch(`${BASE_URL_JS}/api/campuses/active`);
        const campuses = await response.json();
        
        const addSelect = document.querySelector('select[name="campus_id"]');
        const editSelect = document.getElementById('edit_campus_id');

        campuses.forEach(c => {
            const div = document.createElement('div');
            div.className = "campus-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm";
            div.textContent = c.campus_name;
            div.onclick = () => selectEqCampus(div, c.campus_id, c.campus_name);
            menu.appendChild(div);

            const opt = `<option value="${c.campus_id}">${c.campus_name}</option>`;
            if (addSelect && !addSelect.disabled) addSelect.innerHTML += opt;
            if (editSelect && !editSelect.disabled) editSelect.innerHTML += opt;
        });
    } catch (e) {}
}
