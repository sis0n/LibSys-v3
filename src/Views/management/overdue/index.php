<div class="min-h-screen">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-800">Overdue Tracking</h2>
        <p class="text-gray-500 mt-4">Manage and monitor books that are past their due date.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-orange-500 flex items-center gap-4">
            <div class="bg-orange-100 p-3 rounded-full text-orange-600">
                <i class="ph ph-books text-2xl"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase">Total Overdue</p>
                <h4 class="text-2xl font-bold text-gray-800" id="stat-total">0</h4>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-red-500 flex items-center gap-4">
            <div class="bg-red-100 p-3 rounded-full text-red-600">
                <i class="ph ph-warning-octagon text-2xl"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase">Critical (>7d)</p>
                <h4 class="text-2xl font-bold text-gray-800" id="stat-critical">0</h4>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-amber-500 flex items-center gap-4">
            <div class="bg-amber-100 p-3 rounded-full text-amber-600">
                <i class="ph ph-calendar-check text-2xl"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase">Due Today</p>
                <h4 class="text-2xl font-bold text-gray-800" id="stat-due-today">0</h4>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-blue-500 flex items-center gap-4">
            <div class="bg-blue-100 p-3 rounded-full text-blue-600">
                <i class="ph ph-envelope-simple-open text-2xl"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase">Notified Today</p>
                <h4 class="text-2xl font-bold text-gray-800" id="stat-notified">0</h4>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-3 w-full max-w-2xl">
                <div class="relative flex-grow">
                    <input type="text" id="overdue-search" placeholder="Search borrower, student ID, or book title..." 
                        class="w-full p-3 pl-10 border border-orange-200/80 rounded-md bg-orange-50/40 placeholder-orange-800/40 text-gray-800 focus:outline-none focus:ring-2 focus:ring-orange-400">
                    <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
                <select id="urgency-filter" class="p-3 border border-orange-200/80 rounded-md bg-orange-50/40 text-gray-700 outline-none focus:ring-2 focus:ring-orange-400">
                    <option value="">All Urgency</option>
                    <option value="critical">Critical (7d+)</option>
                    <option value="warning">Warning (4-7d)</option>
                    <option value="minor">New (1-3d)</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button id="refresh-btn" class="p-3 bg-white border border-gray-300 rounded-md hover:bg-gray-50 text-gray-600 shadow-sm transition">
                    <i class="ph ph-arrows-clockwise text-xl"></i>
                </button>
                <button id="bulk-notify-btn" class="bg-orange-500 text-white px-6 py-3 rounded-md font-semibold flex items-center gap-2 hover:bg-orange-600 transition border border-orange-500 shadow-sm">
                    <i class="ph ph-paper-plane-tilt"></i>
                    <span>Notify All Eligible</span>
                </button>
            </div>
        </div>

        <div class="overflow-x-auto border border-gray-200 rounded-lg">
            <table class="w-full text-left table-fixed">
                <thead>
                    <tr class="bg-stone-50 border-b border-gray-200">
                        <th class="w-1/4 px-6 py-4 text-sm font-semibold text-gray-600">Borrower Details</th>
                        <th class="w-1/3 px-6 py-4 text-sm font-semibold text-gray-600">Item Information</th>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 text-center">Due Status</th>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 text-center">Last Notified</th>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 text-center">Attempts</th>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="overdue-table-body" class="divide-y divide-gray-200">
                </tbody>
            </table>
        </div>

        <div id="empty-state" class="hidden py-20 flex flex-col items-center justify-center text-center">
            <i class="ph ph-tray text-6xl text-gray-200 mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-800">No Overdue Records</h3>
            <p class="text-gray-500 text-sm">Everything seems to be in order.</p>
        </div>
    </div>
</div>

<script>
    // Define the unified API base path for this module
    const OVERDUE_API_BASE = `${BASE_URL_JS}/api/overdue`;
</script>
<script src="<?= BASE_URL ?>/js/management/overdue.js" defer></script>
