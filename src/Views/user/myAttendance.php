<?php
date_default_timezone_set('Asia/Manila');

use App\Repositories\AttendanceRepository;

$attendanceRepo = new AttendanceRepository();
$userId = $_SESSION['user_id'];
$allLogs = [];
$fetchError = null;

try {
  $allLogs = $attendanceRepo->getByUserId($userId);
} catch (\Exception $e) {
  error_log("Error fetching attendance logs for user {$userId}: " . $e->getMessage());
  $fetchError = "Could not load attendance data due to a server error.";
}

$attendanceJSData = json_encode($allLogs, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

?>

<div class="min-h-screen">
    <div class="mb-6">
        <h2 class="text-2xl font-bold mb-4 text-gray-800">My Attendance</h2>
        <p class="text-gray-700">Track your library visits and check-in times.</p>
    </div>

    <section class="bg-white shadow-md rounded-lg border border-gray-200 p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <div>
                <h4 class="text-base font-semibold text-gray-800">Attendance History</h4>
                <p class="text-sm text-gray-600">View your library check-ins by date</p>
            </div>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 text-sm">
                <div class="flex flex-col items-start gap-1 sm:flex-row sm:items-center sm:gap-2">
                    <label for="attendanceDate" class="text-sm font-medium text-gray-700">Select
                        Date:</label>
                    <input type="date" id="attendanceDate" name="attendanceDate" value="<?= date('Y-m-d') ?>"
                        class="bg-orange-50 border border-orange-200 rounded-lg px-3 py-2 outline-none transition text-sm text-gray-700 w-full sm:w-40 focus:ring-1 focus:ring-orange-400 align-middle">
                </div>
                <div class="flex flex-col items-start gap-1 sm:flex-row sm:items-center sm:gap-2">
                    <label for="attendanceMethod" class="text-sm font-medium text-gray-700">Select
                        Method:</label>
                    <div class="relative w-full sm:w-40">
                        <select id="attendanceMethod" name="attendanceMethod"
                            class="bg-orange-50 border border-orange-200 rounded-lg px-3 py-2 pr-8 outline-none transition text-sm text-gray-700 w-full focus:ring-1 focus:ring-orange-400 appearance-none">
                            <option value="all">All Methods</option>
                            <option value="qr">QR Code</option>
                            <option value="manual">Manual</option>
                        </select>
                        <i
                            class="ph ph-caret-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 pointer-events-none"></i>
                    </div>

                </div>
            </div>
        </div>

        <!-- Desktop Header -->
        <div class="hidden md:grid md:grid-cols-4 gap-4 px-4 py-2 bg-orange-50 text-gray-700 font-medium text-sm rounded-t-lg border border-orange-200 mt-4">
            <div>Date</div>
            <div>Day</div>
            <div>Check-in Time</div>
            <div>Method</div>
        </div>

        <!-- Attendance List Container -->
        <div id="attendanceListContainer" class="bg-white rounded-b-lg md:border-x md:border-b border-orange-200">
            <!-- Loading and No Records Placeholders -->
            <div data-placeholder="true" id="tableLoadingRow" class="text-center text-gray-500 py-10">
                <i class="ph ph-spinner animate-spin text-2xl"></i> Loading...
            </div>
            <div data-placeholder="true" id="tableNoRecordsRow" class="hidden text-center text-gray-500 py-10">
                <i class="ph ph-clipboard text-4xl block mb-2"></i>
                No attendance records found for the selected criteria.
            </div>
            <!-- JavaScript will inject attendance records here -->
        </div>

        <div id="attendance-error"
            class="text-red-600 text-center <?= $fetchError ? '' : 'hidden' ?> p-4 mt-4 bg-red-50 border border-red-200 rounded-lg">
            <?= $fetchError ?? '' ?>
        </div>

    </section>

    <script>
    const allAttendanceLogs = <?= $attendanceJSData ?>;
    </script>

    <script src="<?= BASE_URL ?>/js/user/myAttendance.js" defer></script>

</div>