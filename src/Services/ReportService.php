<?php

namespace App\Services;

use App\Repositories\ReportRepository;
use Exception;

class ReportService
{
    private ReportRepository $reportRepo;

    public function __construct()
    {
        $this->reportRepo = new ReportRepository();
    }

    /**
     * Get aggregate data for the Most Borrowed Books report
     */
    public function getMostBorrowedBooksData(array $filters): array
    {
        return $this->reportRepo->getMostBorrowedBooks(
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null,
            $filters['campus_id'] ?? null,
            $filters['limit'] ?? 10
        );
    }

    /**
     * Get aggregate data for Top Borrowers report
     */
    public function getTopBorrowersData(array $filters): array
    {
        return $this->reportRepo->getTopBorrowers(
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null,
            $filters['campus_id'] ?? null,
            $filters['limit'] ?? 10
        );
    }

    /**
     * Get aggregate data for Attendance report
     */
    public function getAttendanceData(array $filters): array
    {
        return $this->reportRepo->getAttendanceLogs(
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null,
            $filters['campus_id'] ?? null
        );
    }

    /**
     * Get data for Overdue Items report
     */
    public function getOverdueItemsData(?int $campusId): array
    {
        return $this->reportRepo->getOverdueItems($campusId);
    }

    /**
     * Get Lost/Damaged Items report
     */
    public function getLostDamagedItemsData(array $filters): array
    {
        return $this->reportRepo->getLostDamagedItems(
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null,
            $filters['campus_id'] ?? null
        );
    }
}
