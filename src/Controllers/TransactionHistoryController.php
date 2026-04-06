<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\BorrowingHistoryService;
use Exception;

class TransactionHistoryController extends Controller
{
    private BorrowingHistoryService $historyService;

    public function __construct()
    {
        parent::__construct();
        $this->historyService = new BorrowingHistoryService();
    }

    public function getTransactionsJson()
    {
        header('Content-Type: application/json');
        try {
            $status = strtolower($_GET['status'] ?? 'all');
            $date   = $_GET['date'] ?? null;
            $campusId = $this->getCampusFilter();

            if ($status === 'pending') {
                echo json_encode([]);
                return;
            }

            $transactions = $this->historyService->getAdminTransactions($status, $date, $campusId);
            echo json_encode($transactions);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
