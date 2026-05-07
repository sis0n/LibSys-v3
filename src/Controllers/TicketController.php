<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\TicketService;
use Exception;
use Throwable;

class TicketController extends Controller
{
    private TicketService $ticketService;

    public function __construct()
    {
        parent::__construct();
        $this->ticketService = new TicketService();
    }

    public function index()
    {
        $userId = $_SESSION['user_data']['user_id'] ?? null;
        $role = strtolower($_SESSION['role'] ?? 'guest');
        
        if (!$userId) {
            header('Location: ' . \BASE_URL . '/login');
            exit;
        }

        $ticket = $this->ticketService->getActiveTicket((int)$userId, $role);

        $this->view("user/qrBorrowingTicket", [
            "title" => "QR Borrowing Ticket",
            "ticket" => $ticket
        ]);
    }

    public function checkStatus()
    {
        try {
            $userId = $_SESSION['user_data']['user_id'] ?? null;
            $role = $_SESSION['role'] ?? 'guest';
            
            if (!$userId) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $result = $this->ticketService->checkStatus((int)$userId, $role);
            return $this->jsonResponse($result);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        } catch (Throwable $e) {
            return $this->errorResponse('Fatal Error: ' . $e->getMessage(), 500);
        }
    }

    public function cancel()
    {
        try {
            $data = $this->getPostData();
            $transactionId = $data['transaction_id'] ?? null;
            $userId = $_SESSION['user_id'] ?? $_SESSION['user_data']['user_id'] ?? null;

            if (!$transactionId || !$userId) throw new Exception('Missing information.');

            $this->ticketService->cancelTicket((int)$transactionId, (int)$userId);
            return $this->jsonResponse(['message' => 'Ticket cancelled successfully.']);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
