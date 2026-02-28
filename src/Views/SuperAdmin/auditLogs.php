<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold mb-4 text-gray-800 flex items-center gap-2">
            <i class="ph ph-shield-check text-orange-600"></i>
            Audit Trail
        </h2>
        <p class="text-gray-700">Track all administrative actions and system events for security and accountability.</p>
    </div>
</div>

<div class="bg-white border border-orange-200 rounded-xl shadow-sm p-6 mt-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">System Logs</h3>
            <p class="text-sm text-gray-600">Complete record of system activities</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="relative w-[350px]">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" id="logSearchInput" placeholder="Search by user, action, or details..."
                    class="bg-orange-50 border border-orange-200 rounded-lg pl-9 pr-3 py-2 outline-none transition text-sm w-full focus:ring-1 focus:ring-orange-300 shadow-sm">
            </div>
            <button id="refreshLogsBtn" class="p-2 text-orange-600 hover:bg-orange-50 rounded-lg border border-orange-200 transition shadow-sm" title="Refresh Logs">
                <i class="ph ph-arrows-clockwise text-xl"></i>
            </button>
        </div>
    </div>

    <div class="my-4">
        <h4 id="resultsIndicator" class="text-sm text-gray-600 font-medium">Loading logs...</h4>
    </div>

    <div class="overflow-hidden border border-orange-200 rounded-lg shadow-sm">
        <table class="min-w-full text-sm text-gray-700">
            <thead class="bg-orange-100 text-left text-gray-800">
                <tr>
                    <th class="py-3 px-6 font-bold uppercase tracking-wider">Timestamp</th>
                    <th class="py-3 px-6 font-bold uppercase tracking-wider">User</th>
                    <th class="py-3 px-6 font-bold uppercase tracking-wider">Action</th>
                    <th class="py-3 px-6 font-bold uppercase tracking-wider">Resource</th>
                    <th class="py-3 px-6 font-bold uppercase tracking-wider">Details</th>
                </tr>
            </thead>
            <tbody id="logTableBody" class="divide-y divide-orange-100 bg-white">
                <tr>
                    <td colspan="5" class="py-16 text-center text-gray-500">
                        <div class="flex flex-col items-center gap-2">
                            <i class="ph ph-spinner animate-spin text-3xl"></i>
                            <span>Fetching activity records...</span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="mt-6 flex flex-col items-center gap-4">
        <nav id="paginationControls" class="flex items-center justify-center hidden">
            <ul id="paginationList" class="flex items-center h-9 text-sm gap-2"></ul>
        </nav>
        <p id="pageIndicator" class="text-xs text-gray-500 font-medium"></p>
    </div>
</div>

<script src="<?= BASE_URL ?>/js/superadmin/auditLogs.js" defer></script>
