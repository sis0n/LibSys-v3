<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\TicketRepository;
use App\Repositories\LibraryPolicyRepository;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;

class TicketController extends Controller
{
  protected TicketRepository $ticketRepo;
  protected LibraryPolicyRepository $policyRepo;
  private $auditRepo;

  public function __construct()
  {
    $this->ticketRepo = new TicketRepository();
    $this->policyRepo = new LibraryPolicyRepository();
    $this->auditRepo = new \App\Repositories\AuditLogRepository();
  }

  private function uploadQrToBackend(string $transactionCode, string $svgData): bool
  {
    $apiUrl = str_replace('/storage', '/api/upload-qr', STORAGE_URL);
    $fileName = $transactionCode . '.svg';

    try {
      $ch = curl_init($apiUrl);
      $postData = json_encode([
        'filename' => $fileName,
        'image' => base64_encode($svgData)
      ]);

      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
      ]);

      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
      curl_setopt($ch, CURLOPT_TIMEOUT, 10);

      $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);
      return ($httpCode === 200);
    } catch (\Exception $e) {
      error_log("Upload failed: " . $e->getMessage());
      return false;
    }
  }

  public function checkout()
  {
    if (session_status() === PHP_SESSION_NONE) session_start();
    header('Content-Type: application/json');

    $userId = $_SESSION['user_id'] ?? null;
    $role = strtolower($_SESSION['role'] ?? 'student');

    $policy = $this->policyRepo->getPolicyByRole($role);
    $DURATION_DAYS = $policy ? (int)$policy['borrow_duration_days'] : 7;
    $maxAllowed = $policy ? (int)$policy['max_books'] : 5;

    if (!$userId) {
      echo json_encode(['success' => false, 'message' => 'Unauthorized']);
      exit;
    }

    $studentId = $this->ticketRepo->getStudentIdByUserId((int)$userId);
    if (!$studentId) {
      echo json_encode(['success' => false, 'message' => 'No student record found.']);
      exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $selectedIds = $input['cart_ids'] ?? [];

    try {
      $this->ticketRepo->beginTransaction();
      $this->ticketRepo->expireOldPendingTransactions();

      $cartItems = !empty($selectedIds)
        ? $this->ticketRepo->getCartItemsByIds((int)$userId, $selectedIds)
        : $this->ticketRepo->getCartItems((int)$userId);

      if (empty($cartItems)) {
        $this->ticketRepo->rollback();
        echo json_encode(['success' => false, 'message' => 'Cart is empty.']);
        exit;
      }

      $currentActiveCount = $this->ticketRepo->countActiveBorrowedItems((int)$userId);
      $newItemsCount = count($cartItems);

      if (($currentActiveCount + $newItemsCount) > $maxAllowed) {
        $this->ticketRepo->rollback();
        echo json_encode([
          'success' => false, 
          'message' => "Borrow limit exceeded. You already have $currentActiveCount active items and you're trying to add $newItemsCount more. Your limit is $maxAllowed."
        ]);
        exit;
      }

      $transactionCode = strtoupper(uniqid());
      $dueDate = date("Y-m-d H:i:s", strtotime("+{$DURATION_DAYS} days"));
      
      $builder = new Builder(
        writer: new SvgWriter(),
        data: $transactionCode,
        encoding: new Encoding('UTF-8'),
        errorCorrectionLevel: ErrorCorrectionLevel::High,
        size: 300,
        margin: 10,
        roundBlockSizeMode: RoundBlockSizeMode::Margin
      );
      $result = $builder->build();
      $svgData = $result->getString();

      if (!$this->uploadQrToBackend($transactionCode, $svgData)) {
          throw new \Exception("Failed to bridge QR code to mobile storage.");
      }

      $dbPath = "uploads/qrcodes/" . $transactionCode . ".svg";
      $transactionId = $this->ticketRepo->createPendingTransaction($studentId, $transactionCode, $dueDate, $dbPath, 15);

      $this->ticketRepo->addTransactionItems($transactionId, $cartItems);
      
      $itemTitles = implode(', ', array_column($cartItems, 'title'));
      $this->auditRepo->log($userId, 'TICKET_CREATED', 'TRANSACTIONS', $transactionCode, "Student generated borrowing ticket for: $itemTitles");

      $this->ticketRepo->removeCartItemsByIds((int)$userId, array_column($cartItems, 'cart_id'));
      
      $_SESSION['last_ticket_code'] = $transactionCode;
      $this->ticketRepo->commit();

      echo json_encode([
        'success' => true,
        'message' => 'Checkout successful!',
        'ticket_code' => $transactionCode,
        'qrPath' => STORAGE_URL . '/' . $dbPath
      ]);
    } catch (\Throwable $e) {
      if (isset($this->ticketRepo)) $this->ticketRepo->rollback();
      error_log("Checkout Error: " . $e->getMessage());
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
  }

  public function checkStatus()
  {
    if (session_status() === PHP_SESSION_NONE) session_start();
    header('Content-Type: application/json');

    $userId = $_SESSION['user_id'] ?? null;
    $studentId = $this->ticketRepo->getStudentIdByUserId((int)$userId);
    $this->ticketRepo->expireOldPendingTransactions();

    $pending = $this->ticketRepo->getPendingTransactionByStudentId($studentId);

    if ($pending) {
      $details = $this->ticketRepo->getStudentDetailsById($studentId);
      $books = $this->ticketRepo->getBooksByTransactionCode($pending['transaction_code']);
      $fullName = trim(($details['first_name'] ?? '') . ' ' . ($details['last_name'] ?? ''));

      echo json_encode([
        'success' => true,
        'status' => 'pending',
        'transaction_code' => $pending['transaction_code'],
        'generated_at' => $pending['generated_at'],
        'expires_at' => $pending['expires_at'] ?? null,
        'student' => [
          'student_number' => $details['student_number'] ?? 'N/A',
          'name' => $fullName,
          'year_level' => $details['year_level'] ?? '',
          'section' => $details['section'] ?? '',
          'course' => $details['course'] ?? 'N/A'
        ],
        'books' => $books ?? []
      ]);
      exit;
    }

    echo json_encode(['success' => true, 'status' => 'none']);
  }

  public function show(string $transactionCode = null)
  {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $userId = $_SESSION['user_id'] ?? null;
    $studentId = $this->ticketRepo->getStudentIdByUserId((int)$userId);
    $this->ticketRepo->expireOldPendingTransactions();

    $transactionData = null;
    $books = [];
    $studentInfo = ['student_number' => 'N/A', 'name' => 'Student Name', 'year_level' => 'N/A', 'course' => 'N/A'];
    $qrPath = null;

    if (empty($transactionCode)) {
      $transactionCode = $_SESSION['last_ticket_code'] ?? null;
    }

    if ($transactionCode) {
      $transaction = $this->ticketRepo->getTransactionByCode($transactionCode);
      if ($transaction && $transaction['student_id'] == $studentId) {
        $transactionData = $transaction;
        $books = $this->ticketRepo->getTransactionItems($transactionData['transaction_id']);
        $studentDetails = $this->ticketRepo->getStudentInfo($transactionData['student_id']);
        
        if ($studentDetails) {
          $fullName = trim(($studentDetails['first_name'] ?? '') . ' ' . ($studentDetails['last_name'] ?? ''));
          $studentInfo = [
            'student_number' => $studentDetails['student_number'],
            'name' => $fullName,
            'year_level' => $studentDetails['year_level'],
            'section' => $studentDetails['section'] ?? '',
            'course' => $studentDetails['course']
          ];
        }
        
        if (strtolower($transactionData['status']) === 'pending') {
          $qrPath = STORAGE_URL . "/" . ($transactionData['qrcode'] ?: "uploads/qrcodes/" . $transactionCode . ".svg");
        }
      }
    }

    $this->view("student/qrBorrowingTicket", [
      "title" => "QR Borrowing Ticket",
      "currentPage" => "qrBorrowingTicket",
      "transaction_code" => $transactionCode,
      "books" => $books,
      "qrPath" => $qrPath,
      "student" => $studentInfo,
      "generated_at" => $transactionData['generated_at'] ?? null,
      "expires_at" => $transactionData['expires_at'] ?? null,
      "isBorrowed" => ($transactionData && strtolower($transactionData['status'] ?? '') === 'borrowed'),
      "isExpired" => ($transactionData && strtolower($transactionData['status'] ?? '') === 'expired')
    ]);
  }
}
