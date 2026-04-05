<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\BorrowingHistoryService;
use Exception;

class StaffBorrowingHistoryController extends Controller
{
    private BorrowingHistoryService $historyService;

    public function __construct()
    {
        parent::__construct();
        $this->historyService = new BorrowingHistoryService();
    }

    public function fetchPaginatedBorrowingHistory()
    {
        header('Content-Type: application/json');
        try {
            $userId = $_SESSION['user_data']['user_id'] ?? null;
            if (!$userId) throw new Exception('Unauthorized', 401);

            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 5);
            $offset = ($page - 1) * $limit;

            $result = $this->historyService->getOtherHistory('staff', $userId, $limit, $offset);
            $totalPages = ceil($result['total'] / $limit);

            echo json_encode([
                'success' => true,
                'borrowingHistory' => $result['history'],
                'totalPages' => $totalPages,
                'currentPage' => $page,
                'totalRecords' => $result['total']
            ]);
        } catch (Exception $e) {
            http_response_code($e->getCode() ?: 500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
