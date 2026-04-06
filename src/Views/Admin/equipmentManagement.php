<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold tracking-wider text-gray-900 uppercase">Equipment Inventory</h2>
        <p class="text-gray-700">Total registered items in the system</p>
    </div>
    <div class="flex gap-2 text-sm">
        <button
            class="px-4 py-2 border border-orange-200 text-orange-700 font-bold rounded-full hover:bg-orange-50 transition shadow-sm flex items-center gap-2"
            id="multiSelectBtn">
            <i class="ph ph-list-checks"></i>
            Multi-Select
        </button>
        <div id="multiSelectActions" class="hidden flex items-center gap-2 animate-fadeIn">
            <span class="text-xs font-bold text-orange-700 bg-orange-100 px-3 py-2 rounded-lg border border-orange-200 shadow-sm">
                <span id="selectionCount">0</span> selected
            </span>
            <button id="selectAllBtn" class="px-3 py-2 bg-white border border-blue-200 text-blue-600 rounded-lg hover:bg-blue-50 transition font-bold text-xs shadow-sm flex items-center gap-1.5">
                <i class="ph ph-check-square"></i> Select All
            </button>
            <button id="multiDeleteBtn" class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-bold text-xs shadow-sm flex items-center gap-1.5">
                <i class="ph ph-trash"></i> Delete
            </button>
            <button id="cancelSelectionBtn" class="px-3 py-2 bg-white border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 transition font-bold text-xs shadow-sm flex items-center gap-1.5">
                <i class="ph ph-x"></i> Cancel
            </button>
        </div>
        <button
            class="px-4 py-2 bg-orange-500 text-white font-medium rounded-full border border-orange-600 hover:bg-orange-600 gap-2 inline-flex items-center shadow-sm transition-all"
            id="openAddEqBtn">
            <i class="ph ph-plus"></i>
            Add New Equipment
        </button>
    </div>
</div>

<div class="mt-6">
    <div class="flex flex-wrap items-center gap-3 text-sm mb-4">
        <div class="relative w-[330px]">
            <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-orange-500"></i>
            <input type="text" id="eqSearchInput" placeholder="Search by name or asset tag..."
                class="bg-white border border-gray-200 rounded-full pl-10 pr-4 py-2.5 outline-none transition text-sm w-full shadow-sm focus:ring-2 focus:ring-orange-200">
        </div>
        
        <div class="relative inline-block text-left">
            <button id="eqStatusDropdownBtn"
                class="border border-gray-200 bg-white rounded-full px-4 py-2.5 text-sm text-gray-700 flex items-center justify-between gap-2 w-36 hover:bg-orange-50 transition">
                <span>
                    <i class="ph ph-check-circle text-gray-500"></i>
                    <span id="eqStatusDropdownValue">All Status</span>
                </span>
                <i class="ph ph-caret-down text-xs"></i>
            </button>
            <div id="eqStatusDropdownMenu"
                class="absolute mt-2 w-full bg-white border border-orange-200 rounded-xl shadow-md hidden z-20">
                <div class="status-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectEqStatus(this, 'All Status')">All Status</div>
                <div class="status-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectEqStatus(this, 'Active')">Active Only</div>
                <div class="status-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectEqStatus(this, 'Inactive')">Inactive Only</div>
                <hr>
                <div class="status-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectEqStatus(this, 'Available')">Available</div>
                <div class="status-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectEqStatus(this, 'Borrowed')">Borrowed</div>
                <div class="status-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectEqStatus(this, 'Damaged')">Damaged</div>
            </div>
        </div>

        <div class="relative inline-block text-left">
            <button id="eqCampusDropdownBtn"
                class="border border-gray-200 bg-white rounded-full px-4 py-2.5 text-sm text-gray-700 flex items-center justify-between gap-2 w-44 hover:bg-orange-50 transition">
                <span>
                    <i class="ph ph-buildings text-gray-500"></i>
                    <span id="eqCampusDropdownValue">All Campuses</span>
                </span>
                <i class="ph ph-caret-down text-xs"></i>
            </button>
            <div id="eqCampusDropdownMenu"
                class="absolute mt-2 w-full bg-white border border-orange-200 rounded-xl shadow-md hidden z-20">
                <div class="campus-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectEqCampus(this, '', 'All Campuses')">All Campuses</div>
                <?php foreach ($campuses as $campus): ?>
                    <div class="campus-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" 
                         onclick="selectEqCampus(this, '<?= $campus['campus_id'] ?>', '<?= $campus['campus_name'] ?>')">
                        <?= $campus['campus_name'] ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between my-4">
        <h4 id="eqResultsIndicator" class="text-sm text-gray-600 font-medium">Loading...</h4>
    </div>

    <div class="overflow-hidden border border-orange-200 rounded-2xl bg-white shadow-sm">
        <table class="min-w-full text-sm text-gray-700">
            <thead class="bg-orange-100 text-orange-700 uppercase text-xs tracking-wider">
                <tr>
                    <th class="py-3 px-4 font-semibold text-left">Equipment Name</th>
                    <th class="py-3 px-4 font-semibold text-left">Campus</th>
                    <th class="py-3 px-4 font-semibold text-left">Asset Tag</th>
                    <th class="py-3 px-4 font-semibold text-left">Condition</th>
                    <th class="py-3 px-4 font-semibold text-left">Visibility</th>
                    <th class="py-3 px-4 font-semibold text-left">Last Updated</th>
                    <th class="py-3 px-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody id="eqTableBody" class="divide-y divide-orange-100 bg-white">
                <tr>
                    <td colspan="7" class="py-10 text-center text-gray-500">
                        <i class="ph ph-spinner animate-spin text-2xl"></i>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <nav id="eqPaginationControls" class="flex items-center justify-center mt-6 hidden">
        <ul id="eqPaginationList" class="flex items-center h-9 text-sm gap-2"></ul>
    </nav>
</div>

<!-- Add Equipment Modal -->
<div id="addEqModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 w-full max-w-md animate-fadeIn">
        <div class="flex justify-between items-start p-6 border-b border-gray-100">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Add New Equipment</h2>
                <p class="text-sm text-gray-500 mt-1">Register a new physical asset to the system.</p>
            </div>
            <button id="closeAddEqModal" class="text-gray-400 hover:text-red-500 transition">
                <i class="ph ph-x text-2xl"></i>
            </button>
        </div>
        <form id="addEqForm" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Equipment Name <span class="text-red-500">*</span></label>
                <input type="text" name="equipment_name" required placeholder="e.g. Chess Board #1" class="w-full border border-gray-300 rounded-md px-3 py-2 outline-none focus:ring-2 focus:ring-orange-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Campus <span class="text-red-500">*</span></label>
                <select name="campus_id" required class="w-full border border-gray-300 rounded-md px-3 py-2 outline-none focus:ring-2 focus:ring-orange-500 bg-white">
                    <option value="" disabled selected>Select Campus</option>
                    <?php foreach ($campuses as $campus): ?>
                        <option value="<?= $campus['campus_id'] ?>"><?= $campus['campus_name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Asset Tag</label>
                <input type="text" name="asset_tag" readonly placeholder="Auto-generated" class="w-full border border-gray-200 bg-gray-50 text-gray-500 rounded-md px-3 py-2 outline-none cursor-not-allowed">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Initial Status</label>
                <select name="status" class="w-full border border-gray-300 rounded-md px-3 py-2 outline-none focus:ring-2 focus:ring-orange-500 bg-white">
                    <option value="available">Available</option>
                    <option value="damaged">Damaged</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>
            <div class="flex justify-end gap-3 pt-4">
                <button type="button" id="cancelAddEq" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 transition">Add Equipment</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Equipment Modal -->
<div id="editEqModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 w-full max-w-md animate-fadeIn">
        <div class="flex justify-between items-start p-6 border-b border-gray-100">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Edit Equipment</h2>
                <p class="text-sm text-gray-500 mt-1">Update item information or status.</p>
            </div>
            <button id="closeEditEqModal" class="text-gray-400 hover:text-red-500 transition">
                <i class="ph ph-x text-2xl"></i>
            </button>
        </div>
        <form id="editEqForm" class="p-6 space-y-4">
            <input type="hidden" id="edit_equipment_id" name="equipment_id">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Equipment Name <span class="text-red-500">*</span></label>
                <input type="text" id="edit_equipment_name" name="equipment_name" required class="w-full border border-gray-300 rounded-md px-3 py-2 outline-none focus:ring-2 focus:ring-orange-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Campus <span class="text-red-500">*</span></label>
                <select id="edit_campus_id" name="campus_id" required class="w-full border border-gray-300 rounded-md px-3 py-2 outline-none focus:ring-2 focus:ring-orange-500 bg-white">
                    <option value="" disabled>Select Campus</option>
                    <?php foreach ($campuses as $campus): ?>
                        <option value="<?= $campus['campus_id'] ?>"><?= $campus['campus_name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Asset Tag</label>
                <input type="text" id="edit_asset_tag" name="asset_tag" readonly class="w-full border border-gray-200 bg-gray-50 text-gray-500 rounded-md px-3 py-2 outline-none cursor-not-allowed">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="edit_status" name="status" class="w-full border border-gray-300 rounded-md px-3 py-2 outline-none focus:ring-2 focus:ring-orange-500 bg-white">
                    <option value="available">Available</option>
                    <option value="borrowed">Borrowed</option>
                    <option value="damaged">Damaged</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="lost">Lost</option>
                </select>
            </div>
            <div class="flex justify-end gap-3 pt-4">
                <button type="button" id="cancelEditEq" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 transition">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= BASE_URL ?>/js/admin/equipmentManagement.js" defer></script>
