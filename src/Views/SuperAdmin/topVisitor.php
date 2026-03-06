<main class="min-h-screen">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold mb-4 text-gray-800 flex items-center gap-2">
                <i class="ph ph-books text-orange-600"></i>
                Library Reports
            </h2>
            <p class="text-gray-700">Comprehensive library statistics and analytics dashboard</p>
        </div>
        <div class="flex gap-2 text-sm">
            <select id="global-report-filter" class="bg-white font-bold border border-orange-200 px-4 py-2 rounded-lg hover:bg-gray-50 transition-all cursor-pointer outline-none text-orange-700 shadow-sm">
                <option value="day">Today</option>
                <option value="month" selected>This Month</option>
                <option value="year">This Year</option>
            </select>
            <button
                class="inline-flex items-center bg-white font-medium border border-orange-200 justify-center px-4 py-2 rounded-lg hover:bg-gray-100 px-4 gap-2 shadow-sm transition-all"
                id="download-report-btn">
                <i class="ph ph-download-simple"></i>
                Download Report
            </button>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
        <!-- Visitor Breakdown Table -->
        <div class="bg-white border border-orange-200 rounded-xl shadow-sm p-6 transition-all hover:shadow-md h-80 flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest flex items-center gap-2">
                    <i class="ph ph-buildings text-orange-500 text-xl"></i>
                    Top Departments by Visits
                </h3>
                <span class="timeframe-badge text-[10px] bg-orange-100 text-orange-700 font-black uppercase tracking-wider px-3 py-1 rounded-full">This Month</span>
            </div>
            <div class="overflow-y-auto custom-scrollbar flex-grow border border-orange-100 rounded-lg">
                <table class="w-full text-xs border-collapse">
                    <thead class="bg-orange-50 text-orange-700 sticky top-0">
                        <tr>
                            <th scope="col" class="px-4 py-2.5 text-left font-black uppercase w-12">Rank</th>
                            <th scope="col" class="px-4 py-2.5 text-left font-black uppercase">Department</th>
                            <th scope="col" class="px-4 py-2.5 text-right font-black uppercase">Visits</th>
                        </tr>
                    </thead>
                    <tbody id="department-breakdown-tbody" class="divide-y divide-orange-50">
                        <!-- Rows injected via JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Weekly Activity Chart -->
        <div class="bg-white border border-orange-200 rounded-xl shadow-sm p-6 transition-all hover:shadow-md">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <i class="ph ph-chart-line text-blue-500 text-xl"></i>
                    Weekly Activity
                </h3>
                <span class="timeframe-badge text-[10px] bg-blue-100 text-blue-700 font-black uppercase tracking-wider px-3 py-1 rounded-full">This Month</span>
            </div>
            <div class="h-64">
                <canvas id="weeklyActivityChart"></canvas>
            </div>
        </div>
    </div>

   <!-- First Row of Tables -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">

        <!-- Circulated Books Table -->
        <div class="bg-white border border-orange-200 rounded-xl shadow-sm p-5 transition-all hover:shadow-md">
            <div class="flex justify-between items-center mb-4 border-b border-orange-50 pb-3">
                <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest">Circulated Books</h3>
                <span class="timeframe-badge text-[10px] bg-orange-100 text-orange-700 font-black uppercase tracking-wider px-3 py-1 rounded-full">This Month</span>
            </div>
            <div class="overflow-x-auto rounded-lg border border-orange-100">
                <table class="w-full text-xs border-collapse">
                    <thead class="bg-orange-50 text-orange-700">
                        <tr>
                            <th scope="col" class="px-3 py-2.5 text-left font-black uppercase">Category</th>
                            <th scope="col" class="px-3 py-2.5 text-center font-black uppercase">Today</th>
                            <th scope="col" class="px-3 py-2.5 text-center font-black uppercase">Week</th>
                            <th scope="col" class="px-3 py-2.5 text-center font-black uppercase">Month</th>
                            <th scope="col" class="px-3 py-2.5 text-center font-black uppercase">Year</th>
                        </tr>
                    </thead>
                    <tbody id="circulated-books-tbody" class="divide-y divide-orange-50"></tbody>
                </table>
            </div>
        </div>

        <!-- Circulated Equipments Table -->
        <div class="bg-white border border-orange-200 rounded-xl shadow-sm p-5 transition-all hover:shadow-md">
            <div class="flex justify-between items-center mb-4 border-b border-orange-50 pb-3">
                <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest">Circulated Equipments</h3>
                <span class="timeframe-badge text-[10px] bg-orange-100 text-orange-700 font-black uppercase tracking-wider px-3 py-1 rounded-full">This Month</span>
            </div>
            <div class="overflow-x-auto rounded-lg border border-orange-100">
                <table class="w-full text-xs border-collapse">
                    <thead class="bg-orange-50 text-orange-700">
                        <tr>
                            <th scope="col" class="px-3 py-2.5 text-left font-black uppercase">Category</th>
                            <th scope="col" class="px-3 py-2.5 text-center font-black uppercase">Today</th>
                            <th scope="col" class="px-3 py-2.5 text-center font-black uppercase">Week</th>
                            <th scope="col" class="px-3 py-2.5 text-center font-black uppercase">Month</th>
                            <th scope="col" class="px-3 py-2.5 text-center font-black uppercase">Year</th>
                        </tr>
                    </thead>
                    <tbody id="circulated-equipments-tbody" class="divide-y divide-orange-50"></tbody>
                </table>
            </div>
        </div>

        <!-- Deleted Books Table -->
        <div class="bg-white border border-orange-200 rounded-xl shadow-sm p-5 transition-all hover:shadow-md">
            <div class="flex justify-between items-center mb-4 border-b border-orange-50 pb-3">
                <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest">Deleted Books</h3>
                <span class="timeframe-badge text-[10px] bg-orange-100 text-orange-700 font-black uppercase tracking-wider px-3 py-1 rounded-full">This Month</span>
            </div>
            <div class="overflow-x-auto rounded-lg border border-orange-100">
                <table class="w-full text-xs border-collapse">
                    <thead class="bg-orange-50 text-orange-700">
                        <tr>
                            <th scope="col" class="px-3 py-2.5 text-left font-black uppercase">Year</th>
                            <th scope="col" class="px-3 py-2.5 text-center font-black uppercase">Month</th>
                            <th scope="col" class="px-3 py-2.5 text-center font-black uppercase">Today</th>
                            <th scope="col" class="px-3 py-2.5 text-center font-black uppercase">Count</th>
                        </tr>
                    </thead>
                    <tbody id="deleted-books-tbody" class="divide-y divide-orange-50"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Second Row of Tables -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
        <!-- Library Visit (by Department) Table -->
        <div class="bg-white border border-orange-200 rounded-xl shadow-sm p-5 transition-all hover:shadow-md">
            <div class="flex justify-between items-center mb-4 border-b border-orange-50 pb-3">
                <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest">Library Visit (by Department)</h3>
            </div>
            <div class="overflow-x-auto rounded-lg border border-orange-100">
                <table class="w-full text-sm border-collapse">
                    <thead class="bg-orange-50 text-orange-700 border-b border-orange-100">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left font-black uppercase">Department</th>
                            <th scope="col" class="px-4 py-3 text-center font-black uppercase">Today</th>
                            <th scope="col" class="px-4 py-3 text-center font-black uppercase">Week</th>
                            <th scope="col" class="px-4 py-3 text-center font-black uppercase">Month</th>
                            <th scope="col" class="px-4 py-3 text-center font-black uppercase">Year</th>
                        </tr>
                    </thead>
                    <tbody id="library-visit-tbody" class="divide-y divide-orange-50"></tbody>
                </table>
            </div>
        </div>

        <!-- Top 10 Visitors Table -->
        <div class="bg-white border border-orange-200 rounded-xl shadow-sm p-5 transition-all hover:shadow-md">
            <div class="flex justify-between items-center mb-4 border-b border-orange-50 pb-3">
                <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest">Top 10 Visitors</h3>
                <span class="timeframe-badge text-[10px] bg-orange-100 text-orange-700 font-black uppercase tracking-wider px-3 py-1 rounded-full">This Month</span>
            </div>
            <div class="overflow-x-auto rounded-lg border border-orange-100">
                <table class="w-full text-xs border-collapse">
                    <thead class="bg-orange-50 text-orange-700">
                        <tr>
                            <th scope="col" class="px-4 py-2.5 text-left font-black uppercase w-12">Rank</th>
                            <th scope="col" class="px-4 py-2.5 text-left font-black uppercase">Name</th>
                            <th scope="col" class="px-4 py-2.5 text-center font-black uppercase">ID</th>
                            <th scope="col" class="px-4 py-2.5 text-center font-black uppercase">Course</th>
                            <th scope="col" class="px-4 py-2.5 text-center font-black uppercase">Visits</th>
                        </tr>
                    </thead>
                    <tbody id="top-visitors-tbody" class="divide-y divide-orange-50"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Third Row of Tables (New) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
        <!-- Top 10 Borrowers Table -->
        <div class="bg-white border border-orange-200 rounded-xl shadow-sm p-5 transition-all hover:shadow-md">
            <div class="flex justify-between items-center mb-4 border-b border-orange-50 pb-3">
                <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest">Top 10 Borrowers</h3>
                <span class="timeframe-badge text-[10px] bg-orange-100 text-orange-700 font-black uppercase tracking-wider px-3 py-1 rounded-full">This Month</span>
            </div>
            <div class="overflow-x-auto rounded-lg border border-orange-100">
                <table class="w-full text-xs border-collapse">
                    <thead class="bg-orange-50 text-orange-700">
                        <tr>
                            <th scope="col" class="px-4 py-2.5 text-left font-black uppercase w-12">Rank</th>
                            <th scope="col" class="px-4 py-2.5 text-left font-black uppercase">Name</th>
                            <th scope="col" class="px-4 py-2.5 text-center font-black uppercase">ID</th>
                            <th scope="col" class="px-4 py-2.5 text-center font-black uppercase">Role</th>
                            <th scope="col" class="px-4 py-2.5 text-center font-black uppercase">Borrows</th>
                        </tr>
                    </thead>
                    <tbody id="top-borrowers-tbody" class="divide-y divide-orange-50"></tbody>
                </table>
            </div>
        </div>

        <!-- Most Borrowed Books Table -->
        <div class="bg-white border border-orange-200 rounded-xl shadow-sm p-5 transition-all hover:shadow-md">
            <div class="flex justify-between items-center mb-4 border-b border-orange-50 pb-3">
                <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest">Most Borrowed Books</h3>
                <span class="timeframe-badge text-[10px] bg-orange-100 text-orange-700 font-black uppercase tracking-wider px-3 py-1 rounded-full">This Month</span>
            </div>
            <div class="overflow-x-auto rounded-lg border border-orange-100">
                <table class="w-full text-xs border-collapse">
                    <thead class="bg-orange-50 text-orange-700">
                        <tr>
                            <th scope="col" class="px-4 py-2.5 text-left font-black uppercase w-12">Rank</th>
                            <th scope="col" class="px-4 py-2.5 text-left font-black uppercase">Book Title</th>
                            <th scope="col" class="px-4 py-2.5 text-center font-black uppercase">Accession</th>
                            <th scope="col" class="px-4 py-2.5 text-center font-black uppercase">Borrows</th>
                        </tr>
                    </thead>
                    <tbody id="most-borrowed-books-tbody" class="divide-y divide-orange-50"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Custom Date Modal -->
    <div id="customDateModal"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center h-full w-full hidden z-50">
        <div class="relative bg-white rounded-2xl shadow-xl w-[420px] p-8 border border-orange-100 animate-fadeIn">
            <h3 class="text-lg font-bold text-gray-800 text-center mb-6">
                Pick a date range for the data you want to download.
            </h3>

            <div class="space-y-5">
                <div>
                    <label for="startDate" class="block text-xs font-black uppercase tracking-widest text-gray-500 mb-2">Start Date</label>
                    <input type="date" id="startDate" name="startDate"
                        class="w-full border border-orange-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-orange-400 transition-all bg-orange-50/30">
                </div>

                <div>
                    <label for="endDate" class="block text-xs font-black uppercase tracking-widest text-gray-500 mb-2">End Date</label>
                    <input type="date" id="endDate" name="endDate"
                        class="w-full border border-orange-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-orange-400 transition-all bg-orange-50/30">
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 mt-8">
                <button id="confirmDateRange"
                    class="flex-1 py-3 bg-orange-500 text-white rounded-xl font-bold text-sm shadow-lg shadow-orange-200 hover:bg-orange-600 active:scale-95 transition-all">
                    Generate
                </button>
                <button id="cancelDateRange"
                    class="px-6 py-3 text-gray-500 font-bold text-sm hover:text-gray-700 transition-all">
                    Cancel
                </button>
            </div>
        </div>
    </div>

</main>
<script>
    const BASE_URL = "<?= BASE_URL ?>";
</script>
<script src="<?= BASE_URL ?>/js/superadmin/reports.js"></script>