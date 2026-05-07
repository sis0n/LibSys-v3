<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\BorrowingHistoryService;
use Exception;

class BorrowingHistoryController extends Controller
{
    private BorrowingHistoryService $historyService;

    public function __construct()
    {
        parent::__construct();
        $this->historyService = new BorrowingHistoryService();
    }

    public function index()
    {
        $this->view('user/borrowingHistory', [
            'title' => 'My Borrowing History',
            'currentPage' => 'borrowingHistory'
        ]);
    }

    public function fetchPaginatedBorrowingHistory()
    {
        try {
            $userId = $_SESSION['user_data']['user_id'] ?? null;
            if (!$userId) throw new Exception('Unauthorized', 401);
            
            $role = strtolower($_SESSION['role'] ?? 'guest');

            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 5);
            $offset = ($page - 1) * $limit;

            if ($role === 'student') {
                $result = $this->historyService->getStudentHistory($userId, $limit, $offset);
            } else {
                $result = $this->historyService->getOtherHistory($role, $userId, $limit, $offset);
            }

            $totalPages = ceil($result['total'] / $limit);

            return $this->jsonResponse([
                'success' => true,
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
            
            $role = strtolower($_SESSION['role'] ?? 'guest');

            if ($role === 'student') {
                $stats = $this->historyService->getStudentStats($userId);
            } else {
                $stats = $this->historyService->getOtherStats($role, $userId);
            }

            return $this->jsonResponse([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
    }
}
