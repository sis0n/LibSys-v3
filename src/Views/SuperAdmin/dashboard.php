<?php
$fullName = $_SESSION['user_data']['fullname'] ?? $_SESSION['role'] ?? 'Admin';
?>

<main class="min-h-min px-4 sm:px-6 md:px-10 flex flex-col gap-8">

  <section class="py-0">
    <h2 class="text-2xl font-bold text-gray-900">
      Welcome back, <span id="adminName"><?= htmlspecialchars($fullName) ?></span>!
    </h2>
    <p class="text-gray-600 mt-1 text-sm md:text-base">Here's your library system overview.</p>
  </section>

  <section class="border border-orange-100 border-t-4 border-t-green-500 rounded-2xl shadow p-8 bg-white">
    <div class="flex flex-col mb-6">
      <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2 mb-1">
        <i class="ph ph-gear text-orange-500 text-xl"></i>
        Quick Admin Actions
      </h3>
      <p class="text-sm text-gray-500 ml-7">Common administrative tasks</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 justify-items-center gap-4">
      <a href="userManagement" class="flex items-center justify-center bg-white border border-gray-200 text-gray-800 font-semibold py-4 px-6 rounded-lg shadow hover:bg-orange-500 hover:text-white hover:border-orange-500 transition w-full sm:w-56 text-left">
        <i class="ph ph-users text-xl mr-2 align-middle"></i>
        User Management
      </a>
      <a href="bookManagement" class="flex items-center justify-center bg-white border border-gray-200 text-gray-800 font-semibold py-4 px-6 rounded-lg shadow hover:bg-orange-500 hover:text-white hover:border-orange-500 transition w-full sm:w-56 text-left">
        <i class="ph ph-books text-xl mr-2 align-middle"></i>
        Book Management
      </a>
      <a href="returning" class="flex items-center justify-center bg-white border border-gray-200 text-gray-800 font-semibold py-4 px-6 rounded-lg shadow hover:bg-orange-500 hover:text-white hover:border-orange-500 transition w-full sm:w-56 text-left">
        <i class="ph ph-swap text-xl mr-2 align-middle"></i>
        Returning Books
      </a>
      <a href="reports" class="flex items-center justify-center bg-white border border-gray-200 text-gray-800 font-semibold py-4 px-6 rounded-lg shadow hover:bg-orange-500 hover:text-white hover:border-orange-500 transition w-full sm:w-56 text-left">
        <i class="ph ph-activity text-xl mr-2 align-middle"></i>
        Analytics
      </a>
    </div>
  </section>

  <section class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 text-left">
    <?php
    $cards = [
      ['id' => 'totalUsers', 'title' => 'Total Users', 'icon' => 'ph-user', 'color' => 'orange', 'subtitle' => '+0 this month'],
      ['id' => 'dailyVisitors', 'title' => 'Daily Visitors', 'icon' => 'ph-users-three', 'color' => 'green', 'subtitle' => 'Today'],
      ['id' => 'activeBooks', 'title' => 'Available Books', 'icon' => 'ph-book', 'color' => 'orange', 'subtitle' => '0% available'],
      ['id' => 'borrowedBooks', 'title' => 'Borrowed Books', 'icon' => 'ph-books', 'color' => 'green', 'subtitle' => '0% of total books'],
    ];
    foreach ($cards as $c):
    ?>
      <div class="flex flex-col justify-between rounded-lg shadow-sm bg-white border-l-4 border-<?= $c['color'] ?>-500 overflow-hidden h-full">
        <div class="bg-<?= $c['color'] ?>-100 px-6 py-3 flex justify-between items-center">
          <span class="text-sm font-semibold text-gray-600 uppercase tracking-tight"><?= $c['title'] ?></span>
          <i class="ph <?= $c['icon'] ?> text-<?= $c['color'] ?>-500 text-xl"></i>
        </div>
        <div class="p-6 flex flex-col justify-between flex-grow">
          <h4 id="<?= $c['id'] ?>" class="text-3xl font-bold text-gray-900">0</h4>
          <p class="text-sm text-<?= $c['color'] ?>-600 mt-1"><?= $c['subtitle'] ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </section>

  <!-- Role Breakdown -->
  <section class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-left">
    <?php
    $breakdown = [
      ['id' => 'totalStudents', 'title' => 'Students', 'icon' => 'ph-student', 'color' => 'orange'],
      ['id' => 'totalFaculty', 'title' => 'Faculty', 'icon' => 'ph-chalkboard-teacher', 'color' => 'orange'],
      ['id' => 'totalStaff', 'title' => 'Staff', 'icon' => 'ph-identification-card', 'color' => 'orange'],
    ];
    foreach ($breakdown as $b):
    ?>
      <div class="flex items-center gap-4 rounded-xl shadow-sm bg-white border border-<?= $b['color'] ?>-100 p-4 hover:shadow-md transition-shadow">
        <div class="bg-<?= $b['color'] ?>-50 p-3 rounded-lg">
          <i class="ph <?= $b['icon'] ?> text-<?= $b['color'] ?>-500 text-2xl"></i>
        </div>
        <div>
          <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest"><?= $b['title'] ?></p>
          <h4 id="<?= $b['id'] ?>" class="text-xl font-black text-gray-800">0</h4>
        </div>
      </div>
    <?php endforeach; ?>
  </section>

  <section class="grid grid-cols-1 lg:grid-cols-2 gap-6 text-left">
    <!-- Top Visitors -->
    <div class="border border-orange-200 rounded-xl p-6 shadow-sm bg-white h-[450px] flex flex-col">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-black text-gray-800 uppercase tracking-widest flex items-center gap-2">
          <i class="ph ph-crown text-orange-500 text-3xl"></i> Top Visitors
        </h3>
        <span class="text-[10px] bg-orange-100 text-orange-700 font-black uppercase tracking-wider px-3 py-1 rounded-full">This Month</span>
      </div>
      <div class="overflow-hidden rounded-lg border border-orange-100 flex-grow overflow-y-auto custom-scrollbar">
        <table class="w-full text-sm border-collapse">
          <thead class="bg-orange-50 text-orange-700 sticky top-0 z-0">
            <tr>
              <th scope="col" class="px-4 py-3 text-left font-black uppercase w-12">Rank</th>
              <th scope="col" class="px-4 py-3 text-left font-black uppercase">Name</th>
              <th scope="col" class="px-4 py-3 text-left font-black uppercase">ID Number</th>
              <th scope="col" class="px-4 py-3 text-left font-black uppercase">Year & Section</th>
              <th scope="col" class="px-4 py-3 text-right font-black uppercase">Visits</th>
            </tr>
          </thead>
          <tbody id="topVisitorsTableBody" class="divide-y divide-orange-50 bg-white">
            <!-- Rows injected via JS -->
          </tbody>
        </table>
      </div>
    </div>

    <!-- Weekly Activity -->
    <div class="border border-green-200 rounded-xl p-6 shadow-sm bg-white h-[450px] flex flex-col text-left">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-black text-gray-800 uppercase tracking-widest flex items-center gap-2">
          <i class="ph ph-activity text-green-500 text-3xl"></i> Weekly Activity
        </h3>
        <span class="text-[10px] bg-green-100 text-green-700 font-black uppercase tracking-wider px-3 py-1 rounded-full">Visitors and checkouts</span>
      </div>
      <div class="flex-grow">
        <canvas id="weeklyActivityChart"></canvas>
      </div>
    </div>
  </section>

  <section class="grid grid-cols-1 lg:grid-cols-2 gap-6 text-left mb-8">
    <!-- Popular Books -->
    <div class="border border-orange-200 rounded-xl p-6 shadow-sm bg-white h-[450px] flex flex-col">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-black text-gray-800 uppercase tracking-widest flex items-center gap-2">
          <i class="ph ph-fire text-orange-500 text-3xl"></i> Popular Books
        </h3>
        <span class="text-[10px] bg-orange-100 text-orange-700 font-black uppercase tracking-wider px-3 py-1 rounded-full">All Time</span>
      </div>
      <div class="overflow-hidden rounded-lg border border-orange-100 flex-grow overflow-y-auto custom-scrollbar">
        <table class="w-full text-sm border-collapse">
          <thead class="bg-orange-50 text-orange-700 sticky top-0 z-0">
            <tr>
              <th scope="col" class="px-4 py-3 text-left font-black uppercase w-12">Rank</th>
              <th scope="col" class="px-4 py-3 text-left font-black uppercase">Title</th>
              <th scope="col" class="px-4 py-3 text-left font-black uppercase">Accession</th>
              <th scope="col" class="px-4 py-3 text-right font-black uppercase">Borrows</th>
            </tr>
          </thead>
          <tbody id="popularBooksTableBody" class="divide-y divide-orange-50 bg-white">
            <!-- Rows injected via JS -->
          </tbody>
        </table>
      </div>
    </div>

    <!-- Recent Activities -->
    <div class="border border-orange-200 rounded-xl p-6 shadow-sm bg-white h-[450px] flex flex-col">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-black text-gray-800 uppercase tracking-widest flex items-center gap-2">
          <i class="ph ph-clock-counter-clockwise text-orange-500 text-3xl"></i> Recent Activities
        </h3>
        <span class="text-[10px] bg-orange-100 text-orange-700 font-black uppercase tracking-wider px-3 py-1 rounded-full">System Logs</span>
      </div>
      <div class="overflow-hidden rounded-lg border border-orange-100 flex-grow overflow-y-auto custom-scrollbar">
        <table class="w-full text-sm border-collapse">
          <thead class="bg-orange-50 text-orange-700 sticky top-0 z-0">
            <tr>
              <th scope="col" class="px-4 py-3 text-left font-black uppercase w-12">#</th>
              <th scope="col" class="px-4 py-3 text-left font-black uppercase">User</th>
              <th scope="col" class="px-4 py-3 text-left font-black uppercase">Action</th>
              <th scope="col" class="px-4 py-3 text-left font-black uppercase">Details</th>
              <th scope="col" class="px-4 py-3 text-right font-black uppercase">Date</th>
            </tr>
          </thead>
          <tbody id="recentActivitiesTableBody" class="divide-y divide-orange-50 bg-white">
            <!-- Rows injected via JS -->
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- Overdue Books (Feature #2) -->
  <section class="grid grid-cols-1 gap-6 text-left mb-8">
    <div class="border border-green-200 rounded-xl p-6 shadow-sm bg-white h-[450px] flex flex-col">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-black text-gray-800 uppercase tracking-widest flex items-center gap-2">
          <i class="ph ph-warning-circle text-green-500 text-3xl"></i> Overdue Books
        </h3>
        <span class="text-[10px] bg-green-100 text-green-700 font-black uppercase tracking-wider px-3 py-1 rounded-full">Requires Attention</span>
      </div>
      <div class="overflow-hidden rounded-lg border border-green-100 flex-grow overflow-y-auto custom-scrollbar">
        <table class="w-full text-sm border-collapse">
          <thead class="bg-green-50 text-green-700 sticky top-0 z-0">
            <tr>
              <th scope="col" class="px-4 py-3 text-left font-black uppercase w-12">Rank</th>
              <th scope="col" class="px-4 py-3 text-left font-black uppercase">Borrower</th>
              <th scope="col" class="px-4 py-3 text-left font-black uppercase">Book Title</th>
              <th scope="col" class="px-4 py-3 text-left font-black uppercase">Accession</th>
              <th scope="col" class="px-4 py-3 text-right font-black uppercase">Days Overdue</th>
            </tr>
          </thead>
          <tbody id="overdueBooksTableBody" class="divide-y divide-green-50 bg-white">
            <!-- Rows injected via JS -->
          </tbody>
        </table>
      </div>
    </div>
  </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= BASE_URL ?>/js/superadmin/dashboard.js" defer></script>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #fed7aa; border-radius: 10px; }
</style>
