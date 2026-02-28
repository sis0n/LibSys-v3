<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold mb-4 text-gray-800 flex items-center gap-3">
            <i class="ph ph-trash-simple text-orange-600"></i>
            Restore Equipment
        </h2>
        <p class="text-gray-700">View and restore previously deleted equipments from the inventory.</p>
    </div>
</div>

<div class="bg-white border border-orange-200 rounded-xl shadow-sm p-6 mt-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">Deleted Items</h3>
            <p class="text-sm text-gray-600">Archived equipment records</p>
        </div>
        <div class="relative w-[300px]">
            <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="text" id="restoreEqSearch" placeholder="Search deleted items..."
                class="bg-orange-50 border border-orange-200 rounded-lg pl-9 pr-3 py-2 outline-none transition text-sm w-full focus:ring-1 focus:ring-orange-300">
        </div>
    </div>

    <div class="overflow-hidden border border-orange-200 rounded-lg shadow-sm">
        <table class="min-w-full text-sm text-gray-700">
            <thead class="bg-orange-100 text-left text-gray-800">
                <tr>
                    <th class="py-3 px-4 font-medium">Equipment Name</th>
                    <th class="py-3 px-4 font-medium">Asset Tag</th>
                    <th class="py-3 px-4 font-medium">Date Deleted</th>
                    <th class="py-3 px-4 font-medium text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="restoreEqTableBody" class="divide-y divide-orange-100">
                <tr>
                    <td colspan="4" class="py-10 text-center text-gray-500">
                        <i class="ph ph-spinner animate-spin text-2xl"></i>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= BASE_URL ?>/js/superadmin/restoreEquipment.js" defer></script>
