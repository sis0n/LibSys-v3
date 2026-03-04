<div class="min-h-screen">
    <div class="mb-6">
        <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
            <i class="ph ph-scroll text-orange-600"></i>
            Library Policy Management
        </h2>
        <p class="text-gray-700">Configure borrowing limits and durations for different user roles.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="policyCardsContainer">
        <!-- Policies will be loaded here via JS -->
        <div class="col-span-full py-20 flex flex-col items-center justify-center bg-white rounded-xl border border-dashed border-gray-300">
            <div class="animate-spin rounded-full h-10 w-10 border-4 border-orange-200 border-t-orange-600 mb-4"></div>
            <p class="text-gray-500">Loading library policies...</p>
        </div>
    </div>
</div>

<!-- Edit Policy Modal -->
<div id="editPolicyModal" class="fixed inset-0 bg-black/60 flex items-center justify-center hidden z-50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden animate-fadeIn">
        <div class="bg-orange-600 px-6 py-4 flex items-center justify-between">
            <h3 class="text-white font-semibold text-lg" id="modalTitle">Edit Policy</h3>
            <button type="button" class="text-white/80 hover:text-white transition" onclick="closePolicyModal()">
                <i class="ph ph-x text-2xl"></i>
            </button>
        </div>

        <form id="editPolicyForm" class="p-6 space-y-4">
            <input type="hidden" id="policyRole" name="role">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <input type="text" id="displayRole" class="w-full bg-gray-100 border border-gray-200 rounded-lg px-4 py-2 text-gray-600 capitalize" disabled>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Max Books to Borrow</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                        <i class="ph ph-books"></i>
                    </span>
                    <input type="number" id="maxBooks" name="max_books" min="1" max="50" required
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition">
                </div>
                <p class="text-xs text-gray-500 mt-1">Maximum number of items a user can have at once.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Borrow Duration (Days)</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                        <i class="ph ph-calendar"></i>
                    </span>
                    <input type="number" id="durationDays" name="borrow_duration_days" min="0" max="365" required
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition">
                </div>
                <p class="text-xs text-gray-500 mt-1">Number of days before an item is marked as overdue.</p>
            </div>

            <div class="pt-4 flex gap-3">
                <button type="button" onclick="closePolicyModal()"
                    class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium">
                    Cancel
                </button>
                <button type="submit"
                    class="flex-1 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition font-medium">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script src="<?= BASE_URL ?>/js/superadmin/libraryPolicies.js" defer></script>