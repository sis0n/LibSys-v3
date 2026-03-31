<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\OverdueRepository;
use App\Repositories\AuditLogRepository;
use App\Services\MailService;

class OverdueController extends Controller
{
    private OverdueRepository $overdueRepo;
    private AuditLogRepository $auditRepo;
    private MailService $mailService;

    public function __construct()
    {
    parent::__construct();
        $this->overdueRepo = new OverdueRepository();
        $this->auditRepo = new AuditLogRepository();
        $this->mailService = new MailService();
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

            $stats = $this->overdueRepo->getOverdueStats($campusId);
            $list = $this->overdueRepo->fetchOverdueList($filters, $campusId);

            echo json_encode([
                'success' => true,
                'stats' => $stats,
                'list' => $list
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function sendReminder()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents("php://input"), true);

        $email = $data['email'] ?? '';
        $name = $data['name'] ?? '';
        $bookTitle = $data['book_title'] ?? '';
        $dueDate = $data['due_date'] ?? '';
        $itemId = $data['item_id'] ?? null;
        $userId = $data['user_id'] ?? null;

        if (empty($email) || $email === 'N/A') {
            echo json_encode(['success' => false, 'message' => 'No valid email address found.']);
            return;
        }

        try {
            $subject = "URGENT: Overdue Library Item Reminder";
            $body = "
                <div style='font-family: sans-serif; color: #333;'>
                    <h2 style='color: #dc2626;'>Overdue Notice</h2>
                    <p>Dear <strong>$name</strong>,</p>
                    <p>This is a reminder that the following item is now overdue:</p>
                    <div style='background: #fef2f2; border: 1px solid #fee2e2; padding: 15px; border-radius: 8px;'>
                        <p style='margin: 0;'><strong>Item:</strong> $bookTitle</p>
                        <p style='margin: 5px 0 0;'><strong>Due Date:</strong> $dueDate</p>
                    </div>
                    <p>Please return the item to the library immediately to avoid further penalties.</p>
                    <p style='font-size: 0.8em; color: #666; border-top: 1px solid #eee; margin-top: 20px; padding-top: 10px;'>
                        This is an automated system message.
                    </p>
                </div>
            ";

            $sent = $this->mailService->sendEmail($email, $subject, $body);

            if ($sent) {
                $this->overdueRepo->logNotification($itemId, $userId, $email, $_SESSION['user_id']);
                
                $this->auditRepo->log($_SESSION['user_id'], 'NOTIFY', 'OVERDUE', $name, "Sent overdue reminder for: $bookTitle");

                echo json_encode(['success' => true, 'message' => 'Reminder sent successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to send email.']);
            }
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
