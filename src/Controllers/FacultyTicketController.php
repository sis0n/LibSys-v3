<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\FacultyTicketRepository;
use App\Repositories\FacultyProfileRepository;
use App\Repositories\LibraryPolicyRepository;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;

class FacultyTicketController extends Controller
{
  protected FacultyTicketRepository $ticketRepo;
  protected FacultyProfileRepository $facultyProfileRepo;
  protected LibraryPolicyRepository $policyRepo;
  private $auditRepo;

  public function __construct()
  {
    $this->ticketRepo = new FacultyTicketRepository();
    $this->facultyProfileRepo = new FacultyProfileRepository();
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
      error_log("Faculty Upload failed: " . $e->getMessage());
      return false;
    }
  }

  private function getFullName(array $details): string
  {
    $firstName = $details['first_name'] ?? '';
    $lastName = $details['last_name'] ?? '';
    $middleName = $details['middle_name'] ?? '';
    return trim("{$firstName} {$middleName} {$lastName}");
  }

  public function checkout()
  {
    if (session_status() === PHP_SESSION_NONE) session_start();
    header('Content-Type: application/json');

    $userId = $_SESSION['user_id'] ?? null;
    $role = strtolower($_SESSION['role'] ?? 'faculty');

    $policy = $this->policyRepo->getPolicyByRole($role);
    $DURATION_DAYS = $policy ? (int)$policy['borrow_duration_days'] : 14;
    $maxAllowed = $policy ? (int)$policy['max_books'] : 10;

    if (!$userId) {
      http_response_code(403);
      echo json_encode(['success' => false, 'message' => 'Unauthorized']);
      exit;
    }

    $facultyId = $this->ticketRepo->getFacultyIdByUserId((int)$userId);
    if (!$facultyId) {
      http_response_code(403);
      echo json_encode(['success' => false, 'message' => 'No faculty record found.']);
      exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $selectedIds = $input['cart_ids'] ?? [];

    try {
      $this->ticketRepo->beginTransaction();
      $this->ticketRepo->expireOldPendingTransactionsFaculty();

      $cartItems = !empty($selectedIds)
        ? $this->ticketRepo->getFacultyCartItemsByIds((int)$userId, $selectedIds)
        : $this->ticketRepo->getFacultyCartItems((int)$userId);

      if (empty($cartItems)) {
        $this->ticketRepo->rollback();
        echo json_encode(['success' => false, 'message' => 'Cart is empty.']);
        exit;
      }

      $ticketRepoGeneral = new \App\Repositories\TicketRepository();
      $currentActiveCount = $ticketRepoGeneral->countActiveBorrowedItems((int)$userId);
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
      $transactionId = $this->ticketRepo->createPendingTransactionForFaculty($facultyId, $transactionCode, $dueDate, $dbPath, 15);

      $this->ticketRepo->addTransactionItems($transactionId, $cartItems);
      
      $itemTitles = implode(', ', array_column($cartItems, 'title'));
      $this->auditRepo->log($userId, 'TICKET_CREATED', 'TRANSACTIONS', $transactionCode, "Faculty generated borrowing ticket for: $itemTitles");

      $this->ticketRepo->removeFacultyCartItemsByIds((int)$userId, array_column($cartItems, 'cart_id'));
      
      $_SESSION['last_ticket_code'] = $transactionCode;
      $this->ticketRepo->commit();

      echo json_encode([
        'success' => true,
        'message' => 'Checkout successful!',
        'ticket_code' => $transactionCode,
        'qrPath' => STORAGE_URL . '/' . $dbPath
      ]);
    } catch (\Throwable $e) {
      $this->ticketRepo->rollback();
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
  }

  public function checkStatus()
  {
    if (session_status() === PHP_SESSION_NONE) session_start();
    header('Content-Type: application/json');

    $userId = $_SESSION['user_id'] ?? null;
    $facultyId = $this->ticketRepo->getFacultyIdByUserId((int)$userId);
    $this->ticketRepo->expireOldPendingTransactionsFaculty();

    $pending = $this->ticketRepo->getPendingTransactionByFacultyId($facultyId);

    if ($pending) {
      $details = $this->ticketRepo->getFacultyFullInfo($facultyId);
      $books = $this->ticketRepo->getTransactionItems((int)$pending['transaction_id']);

      echo json_encode([
        'success' => true,
        'status' => 'pending',
        'transaction_code' => $pending['transaction_code'],
        'generated_at' => $pending['generated_at'],
        'expires_at' => $pending['expires_at'] ?? null,
        'student' => [
          'student_number' => $details['unique_faculty_id'] ?? 'N/A',
          'name' => $this->getFullName($details),
          'year_level' => 'Faculty',
          'section' => '',
          'course' => $details['college_name'] ?? 'N/A'
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
    $facultyId = $this->ticketRepo->getFacultyIdByUserId((int)$userId);
    $this->ticketRepo->expireOldPendingTransactionsFaculty();

    $transactionData = null;
    $items = [];
    $facultyInfo = ['faculty_id' => 'N/A', 'name' => 'Faculty Name', 'college' => 'N/A'];
    $qrPath = null;

    if (empty($transactionCode)) {
      $transactionCode = $_SESSION['last_ticket_code'] ?? null;
    }

    if ($transactionCode) {
      $transaction = $this->ticketRepo->getTransactionByCode($transactionCode);
      if ($transaction && $transaction['faculty_id'] == $facultyId) {
        $transactionData = $transaction;
        $items = $this->ticketRepo->getTransactionItems($transactionData['transaction_id']);
        $details = $this->ticketRepo->getFacultyFullInfo($facultyId);
        
        if ($details) {
          $facultyInfo = [
            'faculty_id' => $details['unique_faculty_id'],
            'name' => $this->getFullName($details),
            'college' => $details['college_name']
          ];
        }
        
        if (strtolower($transactionData['status']) === 'pending') {
          $qrPath = STORAGE_URL . "/" . ($transactionData['qrcode'] ?: "uploads/qrcodes/" . $transactionCode . ".svg");
        }
      }
    }

    $this->view("faculty/qrBorrowingTicket", [
      "title" => "Faculty QR Borrowing Ticket",
      "currentPage" => "qrBorrowingTicket",
      "transaction_code" => $transactionCode,
      "items" => $items,
      "qrPath" => $qrPath,
      "faculty" => $facultyInfo,
      "generated_at" => $transactionData['generated_at'] ?? null,
      "expires_at" => $transactionData['expires_at'] ?? null,
      "isBorrowed" => ($transactionData && strtolower($transactionData['status']) === 'borrowed'),
      "isExpired" => ($transactionData && strtolower($transactionData['status']) === 'expired')
    ]);
  }
}
