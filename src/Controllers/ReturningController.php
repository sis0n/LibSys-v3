<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ReturningService;
use App\Services\NotificationService;
use Exception;

class ReturningController extends Controller
{
    private ReturningService $returningService;
    private NotificationService $notificationService;

    public function __construct()
    {
        parent::__construct();
        $this->returningService = new ReturningService();
        $this->notificationService = new NotificationService();
    }

    public function getOverdue()
    {
        header('Content-Type: application/json');
        try {
            $campusId = $this->getCampusFilter();
            $data = $this->returningService->getOverdueItems($campusId);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getRecentReturnsJson()
    {
        header('Content-Type: application/json');
        try {
            $limit = (int)($_GET['limit'] ?? 10);
            $campusId = $this->getCampusFilter();
            $list = $this->returningService->getRecentReturns($limit, $campusId);
            echo json_encode(['success' => true, 'list' => $list]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function checkBookStatus()
    {
        header('Content-Type: application/json');
        try {
            $data = $this->getPostData();
            $identifier = $data['accession_number'] ?? null;
            if (!$identifier) throw new Exception('Accession Number or Item ID is required.');
            
            $currentCampusId = $_SESSION['user_data']['campus_id'] ?? null;
            $result = $this->returningService->findItemForReturn($identifier, $currentCampusId);
            
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function returnBook()
    {
        header('Content-Type: application/json');
        try {
            $data = $this->getPostData();
            $itemId = $data['borrowing_id'] ?? null;
            $condition = $data['condition'] ?? 'good';
            $librarianId = $_SESSION['user_id'] ?? null;
            $librarianCampusId = $_SESSION['user_data']['campus_id'] ?? null;

            if (!$itemId) throw new Exception('Borrowing Item ID is required.');
            if (!$librarianId) throw new Exception('Unauthorized.');

            $result = $this->returningService->processReturn((int)$itemId, $condition, $librarianCampusId, $librarianId);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function extendDueDate()
    {
        header('Content-Type: application/json');
        try {
            $data = $this->getPostData();
            $itemId = $data['borrowing_id'] ?? null;
            $daysToExtend = $data['days'] ?? null;

            if (!$itemId || !$daysToExtend) throw new Exception('Required fields missing.');

            $newDueDate = $this->returningService->extendDueDate((int)$itemId, (int)$daysToExtend);
            echo json_encode(['success' => true, 'message' => 'Due date extended successfully!', 'new_due_date' => $newDueDate]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function sendOverdueEmail()
    {
        header('Content-Type: application/json');
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $sent = $this->notificationService->sendOverdueNotice(
                $data['email'] ?? '',
                $data['name'] ?? '',
                $data['book_title'] ?? '',
                $data['due_date'] ?? ''
            );

            if ($sent) {
                echo json_encode(['success' => true, 'message' => 'Email sent successfully.']);
            } else {
                throw new Exception('Failed to send email.');
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
