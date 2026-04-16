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

    public function index()
    {
        $role = strtolower($_SESSION['role'] ?? '');
        $apiBasePath = BASE_URL . '/api/transactionHistory/getTableData';

        $this->view('management/transactionHistory/index', [
            'title' => 'Transaction History',
            'currentPage' => 'transactionHistory',
            'apiBasePath' => $apiBasePath
        ]);
    }

    public function getTableData()
    {
        try {
            $status = strtolower($_GET['status'] ?? 'all');
            $date   = $_GET['date'] ?? null;
            $campusId = $this->getCampusFilter(); // Use getCampusFilter for campus isolation

            if ($status === 'pending') {
                return $this->jsonResponse([]);
            }

            $transactions = $this->historyService->getAdminTransactions($status, $date, $campusId);
            return $this->jsonResponse($transactions);
        } catch (Exception $e) {
            error_log("TransactionHistoryController Error: " . $e->getMessage()); // Log the error
            return $this->errorResponse('Failed to fetch transactions.', 500);
        }
    }
}
