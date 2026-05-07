<?php

use App\Repositories\AttendanceRepository;
use App\Repositories\StudentBorrowingHistoryRepository;
use App\Repositories\FacultyBorrowingHistoryRepository;
use App\Repositories\StaffBorrowingHistoryRepository;
use App\Core\RoleHelper;

$role = $_SESSION['role'] ?? 'student';
$userId = $_SESSION['user_id'];
$isStudent = RoleHelper::isStudent($role);

// Initialize repos based on role
if ($isStudent) {
    $historyRepo = new StudentBorrowingHistoryRepository();
} elseif (RoleHelper::isFaculty($role)) {
    $historyRepo = new FacultyBorrowingHistoryRepository();
} else {
    $historyRepo = new StaffBorrowingHistoryRepository();
}

// Attendance logic - only for students
$daysVisitedThisMonth = 0;
if ($isStudent) {
    $attendanceRepo = new AttendanceRepository();
    $allLogs = $attendanceRepo->getByUserId($userId);
    date_default_timezone_set('Asia/Manila');
    $firstOfMonth = new DateTime('first day of this month 00:00:00');
    $lastOfMonth = new DateTime('last day of this month 23:59:59');
    $visitedDates = [];
    foreach ($allLogs as $log) {
        $logDate = (new DateTime($log['timestamp']))->format('Y-m-d');
        if (!in_array($logDate, $visitedDates)) {
            $logDT = new DateTime($log['timestamp']);
            if ($logDT >= $firstOfMonth && $logDT <= $lastOfMonth) {
                $daysVisitedThisMonth++;
                $visitedDates[] = $logDate;
            }
        }
    }
}

$stats = $historyRepo->getBorrowingStats($userId);
$totalBorrowed = $stats['currently_borrowed'];
$totalOverdue = $stats['total_overdue'];

$allHistory = $historyRepo->getDetailedHistory($userId);
$today = new DateTime('today');
$currentBorrowedBooks = array_filter($allHistory, function ($record) {
    return in_array($record['status'] ?? '', ['borrowed', 'overdue']);
});
?>

<body class="min-h-screen p-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold mb-4">Dashboard</h2>
        <div class="text-gray-700">Here's your library overview for today.</div>
    </div>

    <section class="grid grid-cols-1 <?= $isStudent ? 'md:grid-cols-3' : 'md:grid-cols-2' ?> gap-6 mb-6">

        <div
            class="relative bg-[var(--color-card)] shadow-md rounded-lg border border-[var(--color-border)] p-4 overflow-hidden">
            <div class="absolute top-0 left-0 h-full w-1 bg-[var(--color-orange-500)]"></div>
            <div class="absolute top-3 right-3 text-xl text-[var(--color-orange-500)]"><i class="ph ph-books"></i></div>
            <h3 class="text-sm text-gray-600">Books Borrowed</h3>
            <p class="text-3xl font-bold mt-2"><?= $totalBorrowed ?></p>
            <span class="text-sm text-gray-500">Currently borrowed</span>
        </div>

        <?php if ($isStudent): ?>
        <div
            class="relative bg-[var(--color-card)] shadow-md rounded-lg border border-[var(--color-border)] p-4 overflow-hidden">
            <div class="absolute top-0 left-0 h-full w-1 bg-[var(--color-green-500)]"></div>
            <div class="absolute top-3 right-3 text-xl text-[var(--color-green-500)]"><i
                    class="ph ph-calendar-check"></i></div>
            <h3 class="text-sm text-gray-600">Days Visited</h3>
            <p class="text-3xl font-bold mt-2"><?php echo $daysVisitedThisMonth ?></p>
            <span class="text-sm text-gray-500">This month</span>
        </div>
        <?php endif; ?>

        <div
            class="relative bg-[var(--color-card)] shadow-md rounded-lg border border-[var(--color-border)] p-4 overflow-hidden">
            <div class="absolute top-0 left-0 h-full w-1 bg-[var(--color-destructive)]"></div>
            <div class="absolute top-3 right-3 text-xl text-[var(--color-destructive)]"><i class="ph ph-warning"></i>
            </div>
            <h3 class="text-sm text-gray-600">Overdue Books</h3>
            <p class="text-3xl font-bold mt-2 text-[var(--color-destructive)]"><?= $totalOverdue ?></p>
            <span class="text-sm text-gray-500">Need attention</span>
        </div>
    </section>

    <section class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <div
            class="bg-[var(--color-card)] shadow-md rounded-lg border border-[var(--color-border)] p-4 border-t-4 border-t-[var(--color-orange-500)]">
            <h4 class="text-lg font-semibold mb-2">Currently Borrowed</h4>
            <p class="text-sm text-gray-600 mb-4">Books you need to return</p>

            <?php if (empty($currentBorrowedBooks)): ?>
                <div class="text-center text-gray-500 p-4">
                    <i class="ph ph-book-open text-4xl mb-2"></i>
                    <p>You have no borrowed books.</p>
                </div>
            <?php else: ?>
                <?php foreach (array_slice($currentBorrowedBooks, 0, 3) as $book): ?>
                    <?php
                    $dueDate = new DateTime($book['due_date']);
                    $isOverdue = ($dueDate < $today || ($book['status'] ?? '') === 'overdue');
                    ?>
                    <div
                        class="bg-[var(--color-orange-50)] border border-[var(--color-border)] rounded-md p-3 mb-3 flex justify-between items-center">
                        <div>
                            <p class="font-medium"><?= htmlspecialchars($book['title']) ?></p>
                            <p class="text-sm text-gray-600">by <?= htmlspecialchars($book['author']) ?></p>
                            <p class="text-xs text-gray-500">Due: <?= $dueDate->format('F j, Y') ?></p>
                        </div>

                        <?php if ($isOverdue): ?>
                            <span class="bg-[var(--color-destructive)] text-white px-3 py-1 text-xs rounded-full">Overdue</span>
                        <?php else: ?>
                            <span class="bg-[var(--color-orange-500)] text-white px-3 py-1 text-xs rounded-full">Borrowed</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <?php if (count($currentBorrowedBooks) > 3): ?>
                    <div class="mt-4 text-center">
                        <a href="borrowingHistory" class="text-sm font-medium text-orange-600 hover:underline">
                            View All (<?= count($currentBorrowedBooks) ?>)
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div
            class="bg-[var(--color-card)] shadow-md rounded-lg border border-[var(--color-border)] p-4 border-t-4 border-t-[var(--color-green-500)]">
            <h4 class="text-lg font-semibold mb-2">Quick Actions</h4>
            <p class="text-sm text-gray-600 mb-4">Common tasks</p>
            <div class="space-y-3">
                <a href="bookCatalog"
                    class="flex items-start gap-3 bg-[var(--color-orange-50)] border border-[var(--color-border)] rounded-md p-3 hover:bg-[var(--color-orange-100)] transition">
                    <i class="ph ph-magnifying-glass text-lg mt-0.5"></i>
                    <span>
                        <span class="block font-medium">Search Books</span>
                        <span class="block text-xs text-gray-500">Find books in our catalog</span>
                    </span>
                </a>
                <a href="qrBorrowingTicket"
                    class="flex items-start gap-3 bg-[var(--color-green-50)] border border-[var(--color-border)] rounded-md p-3 hover:bg-[var(--color-green-100)] transition">
                    <i class="ph ph-qr-code text-lg mt-0.5"></i>
                    <span>
                        <span class="block font-medium">Generate QR Ticket</span>
                        <span class="block text-xs text-gray-500">For borrowing books</span>
                    </span>
                </a>
                <a href="borrowingHistory"
                    class="flex items-start gap-3 bg-[var(--color-amber-50)] border border-[var(--color-border)] rounded-md p-3 hover:bg-[var(--color-amber-100)] transition">
                    <i class="ph ph-clock-counter-clockwise text-lg mt-0.5"></i>
                    <span>
                        <span class="block font-medium">View History</span>
                        <span class="block text-xs text-gray-500">Check your borrowing history</span>
                    </span>
                </a>
                <?php if ($isStudent): ?>
                <a href="myAttendance"
                    class="flex items-start gap-3 bg-[var(--color-green-100)] border border-[var(--color-border)] rounded-md p-3 hover:bg-[var(--color-green-200)] transition">
                    <i class="ph ph-user-check text-lg mt-0.5"></i>
                    <span>
                        <span class="block font-medium">My Attendance</span>
                        <span class="block text-xs text-gray-500">Check your attendance history</span>
                    </span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </section>
</body>