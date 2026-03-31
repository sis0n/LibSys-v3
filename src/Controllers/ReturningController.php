<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\ReturningRepository;
use App\Services\MailService;

class ReturningController extends Controller
{
  private $returningRepo;
  private $auditRepo;

  public function __construct()
  {
    parent::__construct();
    $this->returningRepo = new ReturningRepository();
    $this->auditRepo = new \App\Repositories\AuditLogRepository();
  }

  private function sendJson(array $data, int $status = 200): void
  {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
  }

  public function getOverdue()
  {
    $campusId = $this->getCampusFilter();
    $data = $this->returningRepo->getOverdue($campusId);
    if ($data === null) {
      $this->sendJson(['success' => false, 'message' => 'Failed to retrieve data.'], 500);
      return;
    }
    $this->sendJson(['success' => true, 'data' => $data]);
  }

  public function getRecentReturnsJson()
  {
    try {
      $limit = (int)($_GET['limit'] ?? 10);
      $campusId = $this->getCampusFilter();
      $list = $this->returningRepo->getRecentReturns($limit, $campusId);
      $this->sendJson(['success' => true, 'list' => $list]);
    } catch (\Exception $e) {
      $this->sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
  }

  public function checkBookStatus()
  {
    $data = $this->getPostData();
    $identifier = $data['accession_number'] ?? null;
    if (!$identifier) {
      $this->sendJson(['success' => false, 'message' => 'Accession Number or Item ID is required.'], 400);
      return;
    }
    
    $result = $this->returningRepo->findItemByIdentifier($identifier);
    if ($result === null) {
      $this->sendJson(['success' => false, 'message' => 'An error occurred (Null result).'], 500);
      return;
    }

    if (isset($result['status']) && $result['status'] === 'error') {
      $this->sendJson(['success' => false, 'message' => 'An error occurred while processing the item.'], 500);
      return;
    }

    $currentCampusId = $_SESSION['user_data']['campus_id'] ?? null;

    // Logic to detect cross-campus return
    if ($result['status'] === 'borrowed') {
        if (isset($result['matches'])) {
            foreach ($result['matches'] as &$match) {
                $match['is_cross_campus'] = ($match['home_campus_id'] != $currentCampusId);
                $match['current_librarian_campus_id'] = $currentCampusId;
            }
        } else if (isset($result['details'])) {
            $result['details']['is_cross_campus'] = ($result['details']['home_campus_id'] != $currentCampusId);
            $result['details']['current_librarian_campus_id'] = $currentCampusId;
        }
    }

    $this->sendJson(['success' => true, 'data' => $result]);
  }

  public function returnBook()
  {
    $data = $this->getPostData();
    $itemId = $data['borrowing_id'] ?? null;
    $condition = $data['condition'] ?? 'good';
    
    // Get the current campus of the librarian to perform the transfer if needed
    $newCampusId = $_SESSION['user_data']['campus_id'] ?? null;

    if (!$itemId) {
      $this->sendJson(['success' => false, 'message' => 'Borrowing Item ID is required.'], 400);
      return;
    }

    $db = \App\Core\Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT b.title, e.equipment_name, u.first_name, u.last_name
        FROM borrow_transaction_items bti
        JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
        LEFT JOIN books b ON bti.book_id = b.book_id
        LEFT JOIN equipments e ON bti.equipment_id = e.equipment_id
        LEFT JOIN users u ON u.user_id = COALESCE(
            (SELECT user_id FROM students WHERE student_id = bt.student_id),
            (SELECT user_id FROM faculty WHERE faculty_id = bt.faculty_id),
            (SELECT user_id FROM staff WHERE staff_id = bt.staff_id)
        )
        WHERE bti.item_id = ?
    ");
    $stmt->execute([$itemId]);
    $itemInfo = $stmt->fetch(\PDO::FETCH_ASSOC);

    $success = $this->returningRepo->markAsReturned((int)$itemId, $condition, $newCampusId);

    if ($success) {
      $itemName = $itemInfo['title'] ?: $itemInfo['equipment_name'] ?: "Unknown Item";
      $borrower = ($itemInfo['first_name'] . ' ' . $itemInfo['last_name']) ?: "Unknown Borrower";
      
      $actionDetails = "Item '$itemName' returned by $borrower.";
      if ($condition !== 'good') {
          $actionDetails = "Item '$itemName' marked as " . strtoupper($condition) . " during return by $borrower.";
      }

      $this->auditRepo->log($_SESSION['user_id'], 'RETURN', 'TRANSACTIONS', $itemId, $actionDetails);
      
      $recent = $this->returningRepo->getRecentReturns(10);
      $this->sendJson(['success' => true, 'message' => 'Book returned successfully!', 'recent' => $recent]);
    } else {
      $this->sendJson(['success' => false, 'message' => 'Failed to return book.']);
    }
  }

  public function extendDueDate()
  {
    $data = $this->getPostData();
    $itemId = $data['borrowing_id'] ?? null;
    $daysToExtend = $data['days'] ?? null;
    if (!$itemId || !$daysToExtend) {
      $this->sendJson(['success' => false, 'message' => 'Required fields missing.'], 400);
      return;
    }
    $newDueDate = $this->returningRepo->extendDueDate((int)$itemId, (int)$daysToExtend);
    if ($newDueDate) {
      $this->sendJson(['success' => true, 'message' => 'Due date extended successfully!', 'new_due_date' => $newDueDate]);
    } else {
      $this->sendJson(['success' => false, 'message' => 'Failed to extend due date.']);
    }
  }

  public function sendOverdueEmail()
  {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
      $this->sendJson(['success' => false, 'message' => 'Invalid or missing email address.'], 400);
      return;
    }
    $mailService = new MailService();
    $sent = $mailService->sendOverdueNotice($data['email'], $data['name'], $data['book_title'], $data['due_date']);
    if ($sent) {
      $this->sendJson(['success' => true, 'message' => 'Email sent successfully.']);
    } else {
      $this->sendJson(['success' => false, 'message' => 'Failed to send email.'], 500);
    }
  }
}
