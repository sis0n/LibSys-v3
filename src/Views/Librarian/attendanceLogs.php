<?php

use App\Repositories\AttendanceRepository;

$attendanceRepo = new AttendanceRepository();
$campusId = $_SESSION['user_data']['campus_id'] ?? null;
$logs = $attendanceRepo->getAllLogs($campusId);

date_default_timezone_set('Asia/Manila');

$formattedLogs = [];
foreach ($logs as $log) {
    try{
    $logTime = new DateTime($log['timestamp']);
    $formattedLogs[] = [
        'date' => $logTime->format("Y-m-d"),
        'day' => $logTime->format("l"),
        'studentName' => $log['full_name'],
        'studentNumber' => $log['student_number'],
        'time' => $logTime->format("H:i:s"),
        'status' => "Present"
    ];
     } catch (Exception $e) {
        error_log("Invalid timestamp in attendanceLogs: " . $log['timestamp']);
    }
}
?>

<main class="min-h-screen ">
        <div>
            <h2 class="text-2xl font-bold flex items-center gap-2 text-gray-800 mb-4">
                Attendance Logs
            </h2>
            <p class="text-gray-700 text-md">
                Monitor student visits and library usage patterns.
            </p>
        </div>
        
    <div class="bg-[var(--color-card)] border border-orange-200 rounded-xl shadow-sm p-6 mt-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Attendance Records</h3>
                <p class="text-sm text-gray-600">Browse student check-in history</p>
            </div>

            <div class="flex items-center gap-2 text-sm">

                <div class="relative">
                    <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" id="attendanceSearchInput" placeholder="Search by student..."
                        class="bg-orange-50 border border-orange-200 rounded-lg pl-9 pr-3 py-2 outline-none transition text-sm w-48 focus:ring-1 focus:ring-orange-400">
                </div>

                <div class="relative">
                    <input type="date" id="datePickerInput"
                        class="bg-orange-50 border border-orange-200 rounded-lg px-3 py-2 outline-none transition text-sm text-gray-700 w-36 focus:ring-1 focus:ring-orange-400">
                </div>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-orange-200">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-orange-50 text-gray-700 border border-orange-100">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium">Student</th>
                        <th class="text-left px-4 py-3 font-medium">Date</th>
                        <th class="text-left px-4 py-3 font-medium">First Check-in</th>
                        <th class="text-left px-4 py-3 font-medium">Total Check-ins</th>
                        <th class="text-left px-4 py-3 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody id="attendanceTableBody" class="divide-y divide-orange-100">
                    <tr id="noRecordsRow" class="bg-white">
                        <td colspan="5" class="text-center text-gray-500 py-10">
                            <i class="ph ph-clipboard text-4xl block mb-2"></i>
                            Currently no records are logs
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div id="pagination-container" class="flex justify-center items-center mt-6 hidden">
            <nav class="bg-white px-8 py-3 rounded-full shadow-md border border-gray-200">
                <ul class="flex items-center gap-4 text-sm">
                    <li>
                        <a id="prev-page" href="#" class="flex items-center text-sm font-medium gap-1 text-gray-400 hover:text-orange-700 transition">
                            <i class="ph ph-caret-left"></i>
                            <span>Previous</span>
                        </a>
                    </li>
                    <div id="pagination-numbers" class="flex items-center gap-3">
                    </div>
                    <li>
                        <a id="next-page" href="#" class="flex items-center text-sm font-medium gap-1 text-gray-400 hover:text-orange-700 transition">
                            <span>Next</span>
                            <i class="ph ph-caret-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    <div id="viewCheckinsModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">
        <div
            class="bg-[var(--color-card)] rounded-xl shadow-lg border border-[var(--color-border)] w-full max-w-sm p-4 animate-fadeIn">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h2 id="checkinsModalTitle" class="text-lg font-semibold text-gray-800">Viewing Check-ins</h2>
                    <p id="checkinsModalSubtitle" class="text-sm text-gray-600 mt-1">Student / Date</p>
                </div>
                <button id="closeCheckinsModal" class="text-gray-500 hover:text-red-700 transition">
                    <i class="ph ph-x text-2xl"></i> </button>
            </div>

            <div class="max-h-60 overflow-y-auto space-y-2" id="checkinsList">
            </div>

            <div class="text-right mt-4">
                <button id="closeCheckinsModalBtn"
                    class="mt-2 border border-gray-300 px-4 py-2 rounded-md text-gray-700 hover:bg-gray-100 transition text-sm font-medium">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>/js/librarian/attendanceLogs.js" defer></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof initializeAttendanceLogs === 'function') {
                initializeAttendanceLogs();
            } else {
                console.error("AttendanceLogs Error: Initialization function not found. Check if attendanceLogs.js loaded correctly.");
                const tableBody = document.getElementById("attendanceTableBody");
                if (tableBody) {
                    const errorRow = tableBody.querySelector('#noRecordsRow') || tableBody.insertRow();
                    errorRow.innerHTML = `<td colspan="5" class="text-center text-red-600 py-10">Error initializing page. Please refresh.</td>`;
                    errorRow.classList.remove('hidden');
                }
            }
        });
    </script>
</main>