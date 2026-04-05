<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\OverdueService;
use Exception;

class OverdueController extends Controller
{
    private OverdueService $overdueService;

    public function __construct()
    {
        parent::__construct();
        $this->overdueService = new OverdueService();
    }

    public function index()
    {
        $this->view('superadmin/overdue', [
            'title' => 'Overdue Tracking',
            'currentPage' => 'overdue'
        ]);
    }

    public function getTableData()
    {
        header('Content-Type: application/json');
        try {
            $filters = [
                'search' => $_GET['search'] ?? '',
                'urgency' => $_GET['urgency'] ?? ''
            ];
            
            $campusId = $this->getCampusFilter();
            $stats = $this->overdueService->getOverdueStats($campusId);
            $list = $this->overdueService->fetchOverdueList($filters, $campusId);

            echo json_encode([
                'success' => true,
                'stats' => $stats,
                'list' => $list
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function sendReminder()
    {
        header('Content-Type: application/json');
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $adminId = $_SESSION['user_id'] ?? null;
            if (!$adminId) throw new Exception('Authentication required.');

            $email = $data['email'] ?? '';
            $name = $data['name'] ?? '';
            $itemTitle = $data['book_title'] ?? '';
            $dueDate = $data['due_date'] ?? '';
            $itemId = (int)($data['item_id'] ?? 0);
            $userId = (int)($data['user_id'] ?? 0);

            $sent = $this->overdueService->notifyBorrower($email, $name, $itemTitle, $dueDate);

            if ($sent) {
                $this->overdueService->logNotification($itemId, $userId, $email, $adminId);
                echo json_encode(['success' => true, 'message' => 'Reminder sent successfully!']);
            } else {
                throw new Exception('Failed to send email.');
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
