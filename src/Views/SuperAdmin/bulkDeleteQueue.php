<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold mb-4">Bulk Delete Approval Queue</h2>
        <p class="text-gray-700">Review and approve bulk deletion requests from Librarians and Campus Admins.</p>
    </div>
</div>

<div class="bg-[var(--color-card)] border border-orange-200 rounded-xl shadow-sm p-6 mt-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">Pending Requests</h3>
            <p class="text-sm text-gray-600">Requests waiting for your decision</p>
        </div>
        <div class="flex items-center gap-2">
            <button id="refreshQueueBtn" class="p-2 text-orange-600 hover:bg-orange-50 rounded-lg transition shadow-sm border border-orange-100" title="Refresh Queue">
                <i class="ph ph-arrows-clockwise text-xl"></i>
            </button>
        </div>
    </div>

    <div class="overflow-hidden border border-orange-200 rounded-lg shadow-sm bg-white">
        <table class="min-w-full text-sm text-gray-700">
            <thead class="bg-orange-100 text-left text-gray-800 sticky top-0 z-0 border-b border-orange-200">
                <tr>
                    <th class="py-3 px-4 font-medium">Request ID</th>
                    <th class="py-3 px-4 font-medium">Campus</th>
                    <th class="py-3 px-4 font-medium">Requester</th>
                    <th class="py-3 px-4 font-medium text-center">Batch Size</th>
                    <th class="py-3 px-4 font-medium text-center">Requested At</th>
                    <th class="py-3 px-4 font-medium text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="queueTableBody" class="divide-y divide-orange-100 bg-white">
                <tr data-placeholder="true">
                    <td colspan="6" class="py-10 text-center text-gray-500">
                        <i class="ph ph-spinner animate-spin text-2xl"></i>
                        <p class="mt-2 text-xs">Loading pending requests...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Request Details Modal -->
<div id="requestDetailsModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300 ease-out p-4">
    <div id="requestDetailsModalContent" class="bg-[var(--color-card)] w-full max-w-2xl rounded-2xl shadow-lg overflow-hidden transform scale-95 transition-transform duration-300 ease-out max-h-[85vh] flex flex-col border border-orange-200">
        <!-- Header -->
        <div class="bg-gradient-to-r from-orange-500 to-amber-500 p-5 text-white flex-shrink-0 flex justify-between items-center rounded-t-xl">
            <div>
                <h2 class="text-xl font-bold flex items-center gap-2 text-white">
                    <i class="ph ph-list-magnifying-glass text-2xl"></i>
                    Request Details #<span id="detailRequestId"></span>
                </h2>
                <p class="text-sm text-orange-50 font-medium italic" id="detailRequesterInfo"></p>
            </div>
            <button id="closeRequestDetails" class="text-white text-3xl hover:text-red-200 transition-colors duration-200">
                <i class="ph ph-x-circle"></i>
            </button>
        </div>
        
        <div class="p-6 space-y-6 overflow-y-auto">
            <!-- Summary Info -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-orange-50 p-3 rounded-xl border border-orange-100 flex items-center gap-3">
                    <div class="w-10 h-10 bg-orange-100 text-orange-600 rounded-lg flex items-center justify-center">
                        <i class="ph ph-users text-xl font-bold"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-orange-600 font-black uppercase tracking-widest mb-0.5">Total Users</p>
                        <p id="detailItemCount" class="text-lg font-bold text-gray-800"></p>
                    </div>
                </div>
                <div class="bg-blue-50 p-3 rounded-xl border border-blue-100 flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center">
                        <i class="ph ph-identification-card text-xl font-bold"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-blue-600 font-black uppercase tracking-widest mb-0.5">Entity Type</p>
                        <p class="text-lg font-bold text-gray-800">Users</p>
                    </div>
                </div>
            </div>

            <!-- Reason Section -->
            <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4 relative overflow-hidden">
                <h3 class="font-bold text-gray-700 text-xs mb-2 flex items-center gap-2 uppercase tracking-widest">
                    <i class="ph ph-chat-centered-text text-orange-500 text-base"></i>
                    Reason for Deletion
                </h3>
                <p id="detailReason" class="text-gray-600 text-sm italic leading-relaxed whitespace-pre-wrap pl-6 border-l-2 border-orange-200"></p>
            </div>

            <!-- Items Table -->
            <div class="space-y-3">
                <h3 class="font-bold text-gray-700 text-xs flex items-center gap-2 uppercase tracking-widest pl-1">
                    <i class="ph ph-list-checks text-orange-500 text-base"></i>
                    Users to be Deactivated
                </h3>
                <div class="overflow-hidden border border-gray-200 rounded-2xl shadow-sm bg-white max-h-[300px] overflow-y-auto">
                    <table class="min-w-full text-sm text-gray-700">
                        <thead class="bg-gray-50 sticky top-0 z-10 border-b border-gray-200">
                            <tr>
                                <th class="py-3 px-4 text-left font-bold text-gray-500 text-xs uppercase tracking-tighter">ID/Username</th>
                                <th class="py-3 px-4 text-left font-bold text-gray-500 text-xs uppercase tracking-tighter">Full Name</th>
                                <th class="py-3 px-4 text-left font-bold text-gray-500 text-xs uppercase tracking-tighter text-center">Campus</th>
                                <th class="py-3 px-4 text-left font-bold text-gray-500 text-xs uppercase tracking-tighter text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="detailItemsTableBody" class="divide-y divide-gray-50">
                            <!-- Injected by JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="text-xs text-red-600 bg-red-50 p-4 rounded-2xl border border-red-100 flex gap-3 items-start shadow-sm">
                <i class="ph ph-warning-octagon text-xl flex-shrink-0"></i>
                <p class="font-medium leading-tight mt-0.5"><b>Security Notice:</b> Approving this request will immediately deactivate these user accounts. This action is irreversible and will be logged in the audit trail.</p>
            </div>
        </div>

        <div class="p-6 bg-white border-t border-gray-100 flex justify-end gap-3 rounded-b-2xl">
            <button id="rejectBtn" class="px-6 py-2.5 border border-gray-200 text-gray-500 rounded-xl hover:bg-gray-50 transition font-bold text-sm shadow-sm flex items-center gap-2 bg-white">
                <i class="ph ph-x-circle text-lg"></i> Reject Request
            </button>
            <button id="approveBtn" class="px-8 py-2.5 bg-orange-600 text-white rounded-xl hover:bg-orange-700 transition font-bold text-sm shadow-md flex items-center gap-2">
                <i class="ph ph-check-circle text-lg"></i> Approve & Execute
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= BASE_URL ?>/js/admin/bulkDeleteQueue.js" defer></script>
