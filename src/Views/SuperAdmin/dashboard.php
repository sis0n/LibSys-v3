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
      <a href="topVisitor" class="flex items-center justify-center bg-white border border-gray-200 text-gray-800 font-semibold py-4 px-6 rounded-lg shadow hover:bg-orange-500 hover:text-white hover:border-orange-500 transition w-full sm:w-56 text-left">
        <i class="ph ph-activity text-xl mr-2 align-middle"></i>
        Analytics
      </a>
    </div>
  </section>

  <section class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 text-left">
    <?php
    $cards = [
      ['id' => 'totalUsers', 'title' => 'Total Users', 'icon' => 'ph-user', 'color' => 'orange', 'subtitle' => '+0 this month'],
      ['id' => 'dailyVisitors', 'title' => 'Daily Visitors', 'icon' => 'ph-users-three', 'color' => 'blue', 'subtitle' => 'Today'],
      ['id' => 'activeBooks', 'title' => 'Available Books', 'icon' => 'ph-book', 'color' => 'orange', 'subtitle' => '0% available'],
      ['id' => 'borrowedBooks', 'title' => 'Borrowed Books', 'icon' => 'ph-books', 'color' => 'blue', 'subtitle' => '0% of total books'],
    ];
    foreach ($cards as $c):
    ?>
      <div class="flex flex-col justify-between rounded-lg shadow-sm bg-white border-l-4 border-<?= $c['color'] ?>-500 overflow-hidden h-full">
        <div class="bg-<?= $c['color'] ?>-100 px-6 py-3 flex justify-between items-center">
          <span class="text-sm font-semibold text-gray-600"><?= $c['title'] ?></span>
          <i class="ph <?= $c['icon'] ?> text-<?= $c['color'] ?>-500 text-xl"></i>
        </div>
        <div class="p-6 flex flex-col justify-between flex-grow">
          <h4 id="<?= $c['id'] ?>" class="text-3xl font-bold text-gray-900">0</h4>
          <p class="text-sm text-<?= $c['color'] ?>-600 mt-1"><?= $c['subtitle'] ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </section>

  <section class="grid grid-cols-1 lg:grid-cols-2 gap-6 text-left">
    <!-- Top Visitors (Table Design from Reports) -->
    <div class="border border-orange-200 rounded-xl p-6 shadow-sm bg-white h-[450px] flex flex-col">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest flex items-center gap-2">
          <i class="ph ph-crown text-orange-500 text-xl"></i> Top Visitors
        </h3>
        <span class="text-[10px] bg-orange-100 text-orange-700 font-black uppercase tracking-wider px-3 py-1 rounded-full">This Month</span>
      </div>
      
      <div class="overflow-hidden rounded-lg border border-orange-100 flex-grow overflow-y-auto custom-scrollbar">
        <table class="w-full text-xs border-collapse">
          <thead class="bg-orange-50 text-orange-700 sticky top-0 z-10">
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

    <div class="border border-blue-200 rounded-lg p-6 shadow-sm bg-white h-[450px]">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
          <i class="ph ph-activity text-blue-500 text-lg"></i> Weekly Activity
        </h3>
        <p class="text-xs text-gray-500">Visitors and checkouts</p>
      </div>
      <div class="h-64 sm:h-80">
        <canvas id="weeklyActivityChart"></canvas>
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
