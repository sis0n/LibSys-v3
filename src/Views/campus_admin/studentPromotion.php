<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold mb-4 text-gray-800 flex items-center gap-2">
            <i class="ph ph-student text-orange-600"></i>
            Student Promotion Tool
        </h2>
        <p class="text-gray-700">Batch update student year levels or deactivate/activate graduating batches.</p>
    </div>
</div>

<!-- Stats Cards -->
<div id="statsContainer" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
    <!-- Will be populated by JS -->
</div>

<div class="bg-white border border-orange-200 rounded-xl shadow-sm p-6 mt-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative w-[250px]">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" id="promoSearchInput" placeholder="Search name or student ID..."
                    class="bg-orange-50 border border-orange-200 rounded-lg pl-9 pr-3 py-2 outline-none transition text-sm w-full focus:ring-1 focus:ring-orange-300 shadow-sm">
            </div>
            
            <select id="courseFilter" class="bg-white border border-orange-200 rounded-lg px-3 py-2 text-sm text-gray-700 outline-none focus:ring-1 focus:ring-orange-300">
                <option value="">All Courses</option>
            </select>

            <select id="campusFilter" class="bg-white border border-orange-200 rounded-lg px-3 py-2 text-sm text-gray-700 outline-none focus:ring-1 focus:ring-orange-300">
                <option value="">All Campuses</option>
                <?php foreach ($campuses as $campus): ?>
                    <option value="<?= $campus['campus_id'] ?>"><?= $campus['campus_name'] ?></option>
                <?php endforeach; ?>
            </select>

            <select id="yearFilter" class="bg-white border border-orange-200 rounded-lg px-3 py-2 text-sm text-gray-700 outline-none focus:ring-1 focus:ring-orange-300">
                <option value="">All Year Levels</option>
                <option value="1">1st Year</option>
                <option value="2">2nd Year</option>
                <option value="3">3rd Year</option>
                <option value="4">4th Year</option>
                <option value="5">5th Year</option>
            </select>

            <select id="statusFilter" class="bg-white border border-orange-200 rounded-lg px-3 py-2 text-sm text-gray-700 outline-none focus:ring-1 focus:ring-orange-300 font-bold">
                <option value="1" class="text-green-600">Active Students</option>
                <option value="0" class="text-red-600">Inactive/Graduated</option>
            </select>
        </div>

        <div class="flex items-center gap-2">
            <!-- For Active Students -->
            <button id="promoteBtn" class="hidden px-4 py-2 bg-orange-600 text-white font-bold rounded-lg hover:bg-orange-700 gap-2 items-center transition shadow-md">
                <i class="ph ph-arrow-circle-up text-lg"></i>
                Promote Selected (<span class="selected-count">0</span>)
            </button>
            <button id="deactivateBtn" class="hidden px-4 py-2 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 gap-2 items-center transition shadow-md">
                <i class="ph ph-user-minus text-lg"></i>
                Deactivate Selected
            </button>

            <!-- For Inactive Students -->
            <button id="activateBtn" class="hidden px-4 py-2 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 gap-2 items-center transition shadow-md">
                <i class="ph ph-user-plus text-lg"></i>
                Activate Selected (<span class="selected-count">0</span>)
            </button>
        </div>
    </div>

    <div id="selectionBanner" class="hidden bg-orange-100 border-l-4 border-orange-500 p-4 mb-4 animate-fadeIn">
        <div class="flex items-center justify-between">
            <p class="text-sm text-orange-800">
                All <span class="page-count font-bold">0</span> students on this page are selected. 
                <button id="selectEveryMatching" class="ml-2 underline font-bold hover:text-orange-950">Select all <span class="total-matching">0</span> students matching current filters</button>
            </p>
            <p id="globalSelectedMsg" class="hidden text-sm text-orange-800 font-bold">
                <i class="ph ph-check-circle mr-1"></i> All students matching filters are selected.
                <button id="clearGlobalSelect" class="ml-4 underline font-bold hover:text-orange-950">Clear selection</button>
            </p>
        </div>
    </div>

    <div class="my-4 flex items-center justify-between">
        <h4 id="resultsIndicator" class="text-sm text-gray-600 font-medium">Loading students...</h4>
        <label class="flex items-center gap-2 text-sm text-orange-700 font-bold cursor-pointer hover:bg-orange-50 px-2 py-1 rounded">
            <input type="checkbox" id="selectAllStudents" class="w-4 h-4 accent-orange-600">
            Select All on Page
        </label>
    </div>

    <div class="overflow-hidden border border-orange-200 rounded-lg shadow-sm">
        <table class="min-w-full text-sm text-gray-700">
            <thead class="bg-orange-100 text-left text-gray-800">
                <tr>
                    <th class="py-3 px-6 w-10"></th>
                    <th class="py-3 px-6 font-bold uppercase tracking-wider">Student Info</th>
                    <th class="py-3 px-6 font-bold uppercase tracking-wider">Course</th>
                    <th class="py-3 px-6 font-bold uppercase tracking-wider text-center">Current Year</th>
                    <th class="py-3 px-6 font-bold uppercase tracking-wider text-center">Next Year</th>
                </tr>
            </thead>
            <tbody id="promoTableBody" class="divide-y divide-orange-100 bg-white">
                <tr>
                    <td colspan="5" class="py-16 text-center text-gray-500">
                        <i class="ph ph-spinner animate-spin text-3xl mb-2"></i>
                        <p>Preparing student list...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <nav id="paginationControls" aria-label="Page navigation"
        class="flex items-center justify-center bg-white border border-gray-200 rounded-full shadow-md px-4 py-2 mt-6 w-fit mx-auto gap-3 hidden">
        <ul id="paginationList" class="flex items-center h-9 text-sm gap-3">
        </ul>
    </nav>
</div>

<script src="<?= BASE_URL ?>/js/campus_admin/studentPromotion.js" defer></script>
