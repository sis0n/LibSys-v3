<?php

namespace App\Controllers;

use App\Repositories\StaffBorrowingHistoryRepository;
use App\Core\Controller;

class StaffBorrowingHistoryController extends Controller
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new StaffBorrowingHistoryRepository();
    }

    public function fetchPaginatedBorrowingHistory()
    {
        header('Content-Type: application/json');

        $userId = $_SESSION['user_data']['user_id'] ?? 0;
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
        $offset = ($page - 1) * $limit;

        $history = $this->repo->getPaginatedBorrowingHistory($userId, $limit, $offset);
        $totalRecords = $this->repo->countBorrowingHistory($userId);
        $totalPages = ceil($totalRecords / $limit);

        $formattedHistory = $this->formatHistoryRecords($history);

        echo json_encode([
            'success' => true,
            'borrowingHistory' => $formattedHistory,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'totalRecords' => $totalRecords
        ]);
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

    public function fetchStats()
    {
        header('Content-Type: application/json');

        $userId = $_SESSION['user_data']['user_id'] ?? 0;
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $stats = $this->repo->getBorrowingStats($userId);

        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
    }
}
