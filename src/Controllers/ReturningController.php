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
    $this->returningRepo = new ReturningRepository();
    $this->auditRepo = new \App\Repositories\AuditLogRepository();
  }

  private function sendJson(array $data, int $status = 200): void
  {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit; // optional: prevents further HTML output
  }

  public function getOverdue()
  {
    $data = $this->returningRepo->getOverdue();

    if ($data === null) {
      $this->sendJson(['success' => false, 'message' => 'Failed to retrieve data from server.'], 500);
      return;
    }

    $this->sendJson(['success' => true, 'data' => $data]);
  }

  public function checkBookStatus()
  {
    $identifier = $_POST['accession_number'] ?? null;

    if (!$identifier) {
      $this->sendJson(['success' => false, 'message' => 'Accession Number or Item ID is required.'], 400);
      return;
    }

    $result = $this->returningRepo->findItemByIdentifier($identifier);

    if ($result === null) {
      $this->sendJson(['success' => false, 'message' => 'An error occurred while searching for the item.'], 500);
      return;
    }

    $this->sendJson(['success' => true, 'data' => $result]);
  }

  public function returnBook()
  {
    $itemId = $_POST['borrowing_id'] ?? null;

    if (!$itemId) {
      $this->sendJson(['success' => false, 'message' => 'Borrowing Item ID is required.'], 400);
      return;
    }

    $success = $this->returningRepo->markAsReturned((int)$itemId);

    if ($success) {
      $this->auditRepo->log($_SESSION['user_id'], 'RETURN', 'TRANSACTIONS', $itemId, "Item marked as returned.");
      $this->sendJson(['success' => true, 'message' => 'Book returned successfully!']);
    } else {
      $this->sendJson(['success' => false, 'message' => 'Failed to return book. It might be already returned or a database error occurred.']);
    }
  }

  public function extendDueDate()
  {
    $itemId = $_POST['borrowing_id'] ?? null;
    $daysToExtend = $_POST['days'] ?? null;

    if (!$itemId || !$daysToExtend) {
      $this->sendJson(['success' => false, 'message' => 'Borrowing Item ID and extension days are required.'], 400);
      return;
    }

    if (!is_numeric($daysToExtend) || $daysToExtend <= 0) {
      $this->sendJson(['success' => false, 'message' => 'Invalid number of days.'], 400);
      return;
    }

    $newDueDate = $this->returningRepo->extendDueDate((int)$itemId, (int)$daysToExtend);

    if ($newDueDate) {
      $this->sendJson([
        'success' => true,
        'message' => 'Due date extended successfully!',
        'new_due_date' => $newDueDate
      ]);
    } else {
      $this->sendJson(['success' => false, 'message' => 'Failed to extend due date. Transaction not found, already returned, or a database error occurred.']);
    }
  }

  public function sendOverdueEmail()
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->sendJson(['success' => false, 'message' => 'Method not allowed.'], 405);
      return;
    }

    // Get JSON body from JS fetch request
    $data = json_decode(file_get_contents('php://input'), true);

    // Basic validation
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
      $this->sendJson(['success' => false, 'message' => 'Invalid or missing email address.'], 400);
      return;
    }
    if (empty($data['name']) || empty($data['book_title']) || empty($data['due_date'])) {
      $this->sendJson(['success' => false, 'message' => 'Missing required details (name, book, due date).'], 400);
      return;
    }

    // Send using MailService
    $mailService = new MailService();
    $sent = $mailService->sendOverdueNotice(
      $data['email'],
      $data['name'],
      $data['book_title'],
      $data['due_date']
    );

    if ($sent) {
      $this->sendJson(['success' => true, 'message' => 'Email sent successfully.']);
    } else {
      // Check server logs for actual PHPMailer error if this happens
      $this->sendJson(['success' => false, 'message' => 'Failed to send email due to server error.'], 500);
    }
  }
}
