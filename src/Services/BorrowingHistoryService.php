<?php

namespace App\Services;

use App\Repositories\StudentBorrowingHistoryRepository;
use App\Repositories\FacultyBorrowingHistoryRepository;
use App\Repositories\StaffBorrowingHistoryRepository;
use App\Repositories\TransactionHistoryRepository;
use Exception;

class BorrowingHistoryService
{
    private StudentBorrowingHistoryRepository $studentRepo;
    private FacultyBorrowingHistoryRepository $facultyRepo;
    private StaffBorrowingHistoryRepository $staffRepo;
    private TransactionHistoryRepository $transactionRepo;

    public function __construct()
    {
        $this->studentRepo = new StudentBorrowingHistoryRepository();
        $this->facultyRepo = new FacultyBorrowingHistoryRepository();
        $this->staffRepo = new StaffBorrowingHistoryRepository();
        $this->transactionRepo = new TransactionHistoryRepository();
    }

    /**
     * Get paginated borrowing history for a student
     */
    public function getStudentHistory(int $userId, int $limit, int $offset): array
    {
        $history = $this->studentRepo->getPaginatedBorrowingHistory($userId, $limit, $offset);
        $totalRecords = $this->studentRepo->countBorrowingHistory($userId);
        
        return [
            'history' => $this->formatHistoryRecords($history),
            'total' => $totalRecords
        ];
    }

    /**
     * Get student borrowing stats
     */
    public function getStudentStats(int $userId): array
    {
        return $this->studentRepo->getBorrowingStats($userId);
    }

    /**
     * Get faculty/staff borrowing history
     */
    public function getOtherHistory(string $role, int $userId, int $limit, int $offset): array
    {
        if ($role === 'faculty') {
            $history = $this->facultyRepo->getPaginatedBorrowingHistory($userId, $limit, $offset);
            $total = $this->facultyRepo->countBorrowingHistory($userId);
        } else {
            $history = $this->staffRepo->getPaginatedBorrowingHistory($userId, $limit, $offset);
            $total = $this->staffRepo->countBorrowingHistory($userId);
        }

        return [
            'history' => $this->formatHistoryRecords($history),
            'total' => $total
        ];
    }

    /**
     * Get faculty/staff borrowing stats
     */
    public function getOtherStats(string $role, int $userId): array
    {
        if ($role === 'faculty') {
            return $this->facultyRepo->getBorrowingStats($userId);
        } else {
            return $this->staffRepo->getBorrowingStats($userId);
        }
    }

    /**
     * Get global transaction history for admin
     */
    public function getAdminTransactions(string $status, ?string $date, ?int $campusId): array
    {
        if ($status === 'all') {
            return $this->transactionRepo->getAllTransactions($date, $campusId);
        }
        return $this->transactionRepo->getTransactionsByStatus($status, $date, $campusId);
    }

    private function formatHistoryRecords(array $history): array
    {
        return array_map(function ($record) {
            $dueDateStr = $record['due_date'] ?? null;
            $returnedAt = $record['returned_at'] ?? null;
            $dbStatus = !empty($record['status']) ? strtolower(trim($record['status'])) : '';
            
            $now = time();
            $isOverdue = false;
            
            if ($dueDateStr && !$returnedAt) {
                $dueDateTimestamp = strtotime($dueDateStr);
                $dueDateEnd = strtotime(date('Y-m-d 23:59:59', $dueDateTimestamp));
                
                // If it's explicitly marked as overdue OR it's borrowed but past due date
                if ($dbStatus === 'overdue' || ($now > $dueDateEnd)) {
                    $isOverdue = true;
                }
            }

            // Determine final status for display
            $status = $dbStatus;
            if (empty($status)) {
                $status = $isOverdue ? 'overdue' : 'borrowed';
            }

            $statusText = ucfirst($status);
            $statusBgClass = 'bg-gray-100 text-gray-700'; 

            if ($status === 'returned' || !empty($returnedAt)) {
                 $status = 'returned';
                 $statusBgClass = 'bg-green-100 text-green-700';
                 $statusText = 'Returned';
                 $isOverdue = false; // Cannot be overdue if returned
            } elseif ($isOverdue || $status === 'overdue') {
                 $statusBgClass = 'bg-red-100 text-red-700';
                 $statusText = 'Overdue';
                 $isOverdue = true;
            } elseif ($status === 'borrowed') {
                 $statusBgClass = 'bg-amber-100 text-amber-700';
                 $statusText = 'Borrowed';
            } elseif ($status === 'lost') {
                 $statusBgClass = 'bg-red-200 text-red-800';
                 $statusText = 'Lost';
            } elseif ($status === 'damaged') {
                 $statusBgClass = 'bg-orange-100 text-orange-700';
                 $statusText = 'Damaged';
            }

            return [
                'id' => $record['item_id'],
                'title' => $record['title'] ?? 'N/A',
                'author' => $record['author'] ?? 'N/A',
                'item_type' => $record['item_type'] ?? 'Book',
                'borrowedDate' => $record['borrowed_at'] ? date('M d, Y', strtotime($record['borrowed_at'])) : 'N/A',
                'dueDate' => $dueDateStr ? date('M d, Y', strtotime($dueDateStr)) : 'N/A',
                'returnedDate' => $returnedAt ? date('M d, Y', strtotime($returnedAt)) : 'Not returned',
                'librarianName' => $record['librarian_name'] ?? 'N/A',
                'statusText' => $statusText,
                'statusBgClass' => $statusBgClass,
                'isOverdue' => $isOverdue
            ];
        }, $history);
    }
}
