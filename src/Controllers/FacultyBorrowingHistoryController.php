<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\BorrowingHistoryService;
use Exception;

class FacultyBorrowingHistoryController extends Controller
{
    private BorrowingHistoryService $historyService;

    public function __construct()
    {
        parent::__construct();
        $this->historyService = new BorrowingHistoryService();
    }

    public function fetchPaginatedBorrowingHistory()
    {
        try {
            $userId = $_SESSION['user_data']['user_id'] ?? null;
            if (!$userId) throw new Exception('Unauthorized', 401);

            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 5);
            $offset = ($page - 1) * $limit;

            $result = $this->historyService->getOtherHistory('faculty', $userId, $limit, $offset);
            $totalPages = ceil($result['total'] / $limit);

            return $this->jsonResponse([
                'borrowingHistory' => $result['history'],
                'totalPages' => $totalPages,
                'currentPage' => $page,
                'totalRecords' => $result['total']
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function fetchStats()
    {
        try {
            $userId = $_SESSION['user_data']['user_id'] ?? null;
            if (!$userId) throw new Exception('Unauthorized', 401);

            $stats = $this->historyService->getOtherStats('faculty', $userId);
            return $this->jsonResponse(['stats' => $stats]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
    }
}
