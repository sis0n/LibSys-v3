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

    public function index()
    {
        $apiBasePath = BASE_URL . '/api/returning';

        $this->view('management/returning/index', [
            'title' => 'Returning',
            'currentPage' => 'returning',
            'apiBasePath' => $apiBasePath
        ]);
    }

    public function getOverdue()
    {
        try {
            $campusId = $this->getCampusFilter();
            $data = $this->returningService->getOverdueItems($campusId);
            return $this->jsonResponse(['data' => $data]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function getRecentReturnsJson()
    {
        try {
            $limit = (int)($_GET['limit'] ?? 10);
            $campusId = $this->getCampusFilter();
            $list = $this->returningService->getRecentReturns($limit, $campusId);
            return $this->jsonResponse(['list' => $list]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function checkBookStatus()
    {
        try {
            $data = $this->getPostData();
            $identifier = $data['accession_number'] ?? null;
            if (!$identifier) throw new Exception('Accession Number or Item ID is required.');
            
            $currentCampusId = $_SESSION['user_data']['campus_id'] ?? null;
            $result = $this->returningService->findItemForReturn($identifier, $currentCampusId);
            
            return $this->jsonResponse(['data' => $result]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function returnBook()
    {
        try {
            $data = $this->getPostData();
            $itemId = $data['borrowing_id'] ?? null;
            $condition = $data['condition'] ?? 'good';
            $librarianId = $_SESSION['user_id'] ?? null;
            $librarianCampusId = $_SESSION['user_data']['campus_id'] ?? null;

            if (!$itemId) throw new Exception('Borrowing Item ID is required.');
            if (!$librarianId) throw new Exception('Unauthorized.');

            $result = $this->returningService->processReturn((int)$itemId, $condition, $librarianCampusId, $librarianId);
            return $this->jsonResponse($result);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function extendDueDate()
    {
        try {
            $data = $this->getPostData();
            $itemId = $data['borrowing_id'] ?? null;
            $daysToExtend = $data['days'] ?? null;

            if (!$itemId || !$daysToExtend) throw new Exception('Required fields missing.');

            $newDueDate = $this->returningService->extendDueDate((int)$itemId, (int)$daysToExtend);
            return $this->jsonResponse(['message' => 'Due date extended successfully!', 'new_due_date' => $newDueDate]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function sendOverdueEmail()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $sent = $this->notificationService->sendOverdueNotice(
                $data['email'] ?? '',
                $data['name'] ?? '',
                $data['book_title'] ?? '',
                $data['due_date'] ?? ''
            );

            if ($sent) {
                return $this->jsonResponse(['message' => 'Email sent successfully.']);
            } else {
                throw new Exception('Failed to send email.');
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
