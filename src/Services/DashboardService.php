<?php

namespace App\Services;

use App\Repositories\DashboardRepository;

class DashboardService
{
    private DashboardRepository $dashboardRepo;

    public function __construct()
    {
        $this->dashboardRepo = new DashboardRepository();
    }

    /**
     * Get aggregate statistics for the dashboard
     */
    public function getStatistics(?int $campusId): array
    {
        return [
            'total_books' => $this->dashboardRepo->countBooks($campusId),
            'total_borrowed' => $this->dashboardRepo->countBorrowedItems($campusId),
            'total_users' => $this->dashboardRepo->countUsers($campusId),
            'overdue_count' => $this->dashboardRepo->countOverdue($campusId),
            'recent_borrowings' => $this->dashboardRepo->getRecentBorrowings(5, $campusId),
            'most_borrowed' => $this->dashboardRepo->getPopularBooks(5, $campusId),
            'attendance_today' => $this->dashboardRepo->countTodayAttendance($campusId)
        ];
    }

    /**
     * Get borrowing trends for a specific period
     */
    public function getBorrowingTrends(string $period, ?int $campusId): array
    {
        return $this->dashboardRepo->getTrends($period, $campusId);
    }

    /**
     * Get detailed data for the dashboard (cards, tables, charts)
     */
    public function getDetailedData(?int $campusId): array
    {
        // Summary stats
        $totalBooks = $this->dashboardRepo->countBooks($campusId);
        $availableBooks = $this->dashboardRepo->countAvailableBooks($campusId);
        $borrowedBooks = $this->dashboardRepo->countBorrowedItems($campusId);
        
        $availablePercent = ($totalBooks > 0) ? round(($availableBooks / $totalBooks) * 100) : 0;
        $borrowedPercent = ($totalBooks > 0) ? round(($borrowedBooks / $totalBooks) * 100) : 0;

        $data = [
            'totalUsers' => $this->dashboardRepo->countUsers($campusId),
            'attendance_today' => $this->dashboardRepo->countTodayAttendance($campusId),
            'availableBooks' => $availableBooks,
            'borrowed_books' => $borrowedBooks,
            'students' => $this->dashboardRepo->countStudents($campusId),
            'faculty' => $this->dashboardRepo->countFaculty($campusId),
            'staff' => $this->dashboardRepo->countStaff($campusId),
            'usersAddedThisMonth' => $this->dashboardRepo->countUsersAddedThisMonth($campusId),
            'availableBooksPercent' => $availablePercent,
            'borrowedBooksPercent' => $borrowedPercent,
        ];

        return [
            'success' => true,
            'data' => $data,
            'topVisitors' => $this->dashboardRepo->getTopVisitors(5, $campusId),
            'weeklyActivity' => $this->dashboardRepo->getWeeklyActivity($campusId),
            'popularBooks' => $this->dashboardRepo->getPopularBooks(5, $campusId),
            'recentActivities' => $this->dashboardRepo->getRecentActivities(10, $campusId),
            'overdueBooks' => $this->dashboardRepo->getOverdueBooks(5, $campusId)
        ];
    }
}
