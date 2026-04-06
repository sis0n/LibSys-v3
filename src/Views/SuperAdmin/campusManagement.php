<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold tracking-wider text-gray-900 uppercase">Campus List</h2>
    </div>
    <div class="flex gap-2 text-sm">
        <button
            class="px-4 py-2 bg-orange-500 text-white font-medium rounded-full border hover:bg-orange-600 gap-2 inline-flex items-center shadow-sm"
            id="addCampusBtn">
            <i class="ph ph-plus"></i>
            Add Campus
        </button>
    </div>
</div>

<div class="mt-6">
    <div class="flex flex-wrap items-center gap-3 text-sm mb-4">
        <div class="relative w-[380px]">
            <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-orange-500"></i>
            <input type="text" id="campusSearchInput" placeholder="Search by campus name or code..."
                class="bg-white border border-gray-200 rounded-full pl-10 pr-4 py-2.5 outline-none transition text-sm w-full shadow-sm focus:ring-2 focus:ring-orange-200">
        </div>
    </div>

    <div class="flex items-center justify-between my-4">
        <h4 id="resultsIndicator" class="text-sm text-gray-600">
            Loading...
        </h4>

        <div class="inline-flex items-center gap-2">
            <div id="multiSelectActions" class="hidden items-center gap-2">
                <div class="h-6 border-l border-gray-300 mx-2"></div>

                <button id="selectAllBtn" title="Select-all"
                    class="inline-flex items-center gap-2 border border-orange-200 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-orange-50 transition">
                    <i class="ph ph-check-square-offset text-base"></i>
                    Select All
                </button>
                <button id="cancelSelectionBtn" title="Cancel multi-select"
                    class="inline-flex items-center gap-2 border border-gray-300 text-gray-700 rounded-lg px-3 py-2 text-sm font-medium hover:bg-gray-100 transition">
                    <i class="ph ph-x text-base"></i>
                    Cancel
                </button>
            </div>

            <button id="multiSelectBtn" title="Multi-select"
                class="inline-flex items-center gap-2 border border-orange-200 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-orange-50 transition">
                <i class="ph ph-list-checks text-base"></i>
                Multiple Select
            </button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-orange-200 bg-white shadow-sm">
        <table class="w-full text-sm border-collapse text-gray-700">
            <thead class="bg-orange-100 text-orange-700 uppercase text-xs tracking-wider border-b border-orange-200">
                <tr>
                    <th class="text-left px-4 py-4 font-semibold w-16">#</th>
                    <th class="text-left px-4 py-4 font-semibold">Campus Name</th>
                    <th class="text-left px-4 py-4 font-semibold">Campus Code</th>
                    <th class="text-left px-4 py-4 font-semibold">Status</th>
                    <th class="text-left px-4 py-4 font-semibold">Date Created</th>
                    <th class="text-left px-4 py-4 font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody id="campusTableBody" class="divide-y divide-orange-100">
                <tr>
                    <td colspan="6" class="text-center text-gray-500 py-10">
                        <i class="ph ph-spinner animate-spin text-2xl"></i>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Campus Modal -->
<div id="campusModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">
    <div class="rounded-xl overflow-hidden shadow-lg border border-[var(--color-border)] bg-[var(--color-card)] w-full max-w-md animate-fadeIn">
        <div class="p-6">
            <div class="flex justify-between items-start mb-4">
                <h2 id="modalTitle" class="text-lg font-semibold flex items-center gap-2">
                    <i class="ph ph-buildings text-orange-500 text-xl"></i>
                    Add New Campus
                </h2>
                <button class="close-modal text-gray-500 hover:text-red-700 transition">
                    <i class="ph ph-x text-2xl"></i>
                </button>
            </div>

            <p class="text-sm text-gray-600 mb-4">
                Configure library campus details.
            </p>

            <form id="campusForm" class="space-y-4">
                <input type="hidden" id="campusId" name="campus_id">
                
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Campus Name <span class="text-red-500">*</span></label>
                        <input type="text" id="campus_name" name="campus_name" placeholder="e.g. South Campus" required
                            class="w-full border border-[var(--color-border)] rounded-md px-3 py-2 text-sm focus-visible:ring-[var(--color-ring)] focus-visible:border-[var(--color-ring)] outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Campus Code <span class="text-red-500">*</span></label>
                        <input type="text" id="campus_code" name="campus_code" placeholder="e.g. SOUTH" required
                            class="w-full border border-[var(--color-border)] rounded-md px-3 py-2 text-sm focus-visible:ring-[var(--color-ring)] focus-visible:border-[var(--color-ring)] outline-none uppercase font-mono">
                        <p class="text-[10px] text-gray-500 mt-1 italic">Maikling unique identifier (e.g., MAIN, SOUTH, BAG)</p>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <button id="saveCampusBtn" type="submit"
                        class="flex-1 bg-orange-600 text-white font-medium px-4 py-2.5 text-sm rounded-md hover:bg-orange-700 transition">
                        Save Campus
                    </button>
                    <button type="button" class="close-modal border border-orange-200 text-gray-800 font-medium px-4 py-2.5 text-sm rounded-md hover:bg-orange-50 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/js/superadmin/campusManagement.js" defer></script>
