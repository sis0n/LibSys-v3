<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\TicketService;
use Exception;

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
        $role = $_SESSION['role'] ?? 'guest';
        
        if (!$userId) {
            header('Location: ' . \BASE_URL . '/login');
            exit;
        }

        $ticket = $this->ticketService->getActiveTicket((int)$userId, $role);

        // Determine view folder based on role
        $viewFolder = ucfirst($role);
        // Special case for staff (folder is lowercase 'staff' in some parts of the project)
        if ($role === 'staff') $viewFolder = 'staff';

        $this->view("$viewFolder/qrBorrowingTicket", [
            "title" => "QR Borrowing Ticket",
            "ticket" => $ticket
        ]);
    }

    public function checkStatus()
    {
        ob_start(); // Start buffering to catch any accidental output
        
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $userId = $_SESSION['user_data']['user_id'] ?? null;
            $role = $_SESSION['role'] ?? 'guest';
            
            if (!$userId) {
                ob_end_clean(); // Clear buffer before sending JSON
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                exit;
            }

            $result = $this->ticketService->checkStatus((int)$userId, $role);
            
            ob_end_clean(); // Clear buffer before sending JSON
            header('Content-Type: application/json');
            echo json_encode(array_merge(['success' => true], $result));
        } catch (Exception $e) {
            ob_end_clean();
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        } catch (\Error $e) {
            ob_end_clean();
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Fatal Error: ' . $e->getMessage()]);
        }
        exit;
    }

    public function cancel()
    {
        header('Content-Type: application/json');
        try {
            $data = $this->getPostData();
            $transactionId = $data['transaction_id'] ?? null;
            $userId = $_SESSION['user_data']['user_id'] ?? null;

            if (!$transactionId || !$userId) throw new Exception('Missing information.');

            $this->ticketService->cancelTicket((int)$transactionId, (int)$userId);
            echo json_encode(['success' => true, 'message' => 'Ticket cancelled successfully.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
