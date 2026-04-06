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
     * Get faculty/staff borrowing history (can be expanded to use specific repos)
     */
    public function getOtherHistory(string $role, int $userId, int $limit, int $offset): array
    {
        // Simple logic here, can be more specific if repositories differ significantly
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
            $dueDate = strtotime($record['due_date']);
            $returnedDate = $record['returned_at'] ? strtotime($record['returned_at']) : null;
            $now = time();

            $status = $record['status'];
            $isOverdue = ($status === 'overdue') || ($status === 'borrowed' && ($dueDate < $now));
            
            $statusText = ucfirst($status);
            $statusBgClass = 'bg-gray-100 text-gray-700'; 

            if ($status === 'returned') {
                 $statusBgClass = 'bg-green-100 text-green-700';
            } elseif ($isOverdue) {
                $statusText = ($status === 'overdue') ? 'Overdue' : 'Borrowed'; 
                 $statusBgClass = 'bg-red-100 text-red-700';
            } elseif ($status === 'borrowed') {
                $statusBgClass = 'bg-amber-100 text-amber-700';
            }

            return [
                'id' => $record['item_id'],
                'title' => $record['title'] ?? 'N/A',
                'author' => $record['author'] ?? 'N/A',
                'item_type' => $record['item_type'] ?? 'Book',
                'borrowedDate' => $record['borrowed_at'] ? date('M d, Y', strtotime($record['borrowed_at'])) : 'N/A',
                'dueDate' => $record['due_date'] ? date('M d, Y', $dueDate) : 'N/A',
                'returnedDate' => $returnedDate ? date('M d, Y', $returnedDate) : 'Not returned',
                'librarianName' => $record['librarian_name'] ?? 'N/A',
                'statusText' => $statusText,
                'statusBgClass' => $statusBgClass,
                'isOverdue' => $isOverdue
            ];
        }, $history);
    }
}
