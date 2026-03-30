<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\StaffTicketRepository;
use App\Repositories\StaffProfileRepository;
use App\Repositories\LibraryPolicyRepository;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;

class StaffTicketController extends Controller
{
  protected StaffTicketRepository $ticketRepo;
  protected StaffProfileRepository $staffProfileRepo;
  protected LibraryPolicyRepository $policyRepo;
  private $auditRepo;

  public function __construct()
  {
    $this->ticketRepo = new StaffTicketRepository();
    $this->staffProfileRepo = new StaffProfileRepository();
    $this->policyRepo = new LibraryPolicyRepository();
    $this->auditRepo = new \App\Repositories\AuditLogRepository();
  }

  private function saveQrLocally(string $transactionCode, string $svgData): bool
  {
    $uploadDir = ROOT_PATH . "/public/storage/uploads/qrcodes/";
    if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0777, true);
    }

    $fileName = $transactionCode . '.svg';
    $destPath = $uploadDir . $fileName;

    return file_put_contents($destPath, $svgData) !== false;
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
    $role = strtolower($_SESSION['role'] ?? 'staff');
    $campusId = $_SESSION['user_data']['campus_id'] ?? 1;

    $policy = $this->policyRepo->getPolicyByRole($role, (int)$campusId);
    $DURATION_DAYS = $policy ? (int)$policy['borrow_duration_days'] : 14;
    $maxAllowed = $policy ? (int)$policy['max_books'] : 7;

    if (!$userId) {
      http_response_code(403);
      echo json_encode(['success' => false, 'message' => 'Unauthorized']);
      exit;
    }

    $staffId = $this->ticketRepo->getStaffIdByUserId((int)$userId);
    if (!$staffId) {
      http_response_code(403);
      echo json_encode(['success' => false, 'message' => 'No staff record found.']);
      exit;
    }

    $profile = $this->staffProfileRepo->getProfileByUserId($userId);

    if (!$profile) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Staff profile details not found. Please complete your profile first.']);
        exit;
    }

    $missingFields = [];
    if (empty($profile['email']) || !filter_var($profile['email'], FILTER_VALIDATE_EMAIL)) {
        $missingFields[] = 'email';
    }
    if (empty($profile['contact'])) { 
        $missingFields[] = 'contact number';
    }
    if (empty($profile['position']) || $profile['position'] === 'N/A') { 
        $missingFields[] = 'position';
    }
    if (empty($profile['profile_picture'])) {
        $missingFields[] = 'profile picture';
    }

    if (!empty($missingFields)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Your profile is incomplete. Please fill in the following required fields: ' . implode(', ', $missingFields) . '.'
        ]);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $selectedIds = $input['cart_ids'] ?? [];

    try {
      $this->ticketRepo->beginTransaction();
      $this->ticketRepo->expireOldPendingTransactionsStaff();

      $cartItems = !empty($selectedIds)
        ? $this->ticketRepo->getStaffCartItemsByIds((int)$userId, $selectedIds)
        : $this->ticketRepo->getStaffCartItems((int)$userId);

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

      if (!$this->saveQrLocally($transactionCode, $svgData)) {
          throw new \Exception("Failed to save QR code locally.");
      }

      $dbPath = "storage/uploads/qrcodes/" . $transactionCode . ".svg";
      $transactionId = $this->ticketRepo->createPendingTransactionForStaff($staffId, $transactionCode, $dueDate, $dbPath, 15);

      $this->ticketRepo->addTransactionItems($transactionId, $cartItems);
      
      $itemTitles = implode(', ', array_column($cartItems, 'title'));
      $this->auditRepo->log($userId, 'TICKET_CREATED', 'TRANSACTIONS', $transactionCode, "Staff generated borrowing ticket for: $itemTitles");

      $this->ticketRepo->removeStaffCartItemsByIds((int)$userId, array_column($cartItems, 'cart_id'));
      
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
    $staffId = $this->ticketRepo->getStaffIdByUserId((int)$userId);
    $this->ticketRepo->expireOldPendingTransactionsStaff();

    $pending = $this->ticketRepo->getPendingTransactionByStaffId($staffId);

    if ($pending) {
      $details = $this->ticketRepo->getStaffFullInfoByStaffId($staffId);
      $books = $this->ticketRepo->getTransactionItems((int)$pending['transaction_id']);

      echo json_encode([
        'success' => true,
        'status' => 'pending',
        'transaction_code' => $pending['transaction_code'],
        'generated_at' => $pending['generated_at'],
        'expires_at' => $pending['expires_at'] ?? null,
        'student' => [
          'student_number' => $details['employee_id'] ?? 'N/A',
          'name' => $this->getFullName($details),
          'year_level' => 'Staff',
          'section' => '',
          'course' => $details['position'] ?? 'N/A'
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
    $staffId = $this->ticketRepo->getStaffIdByUserId((int)$userId);
    $this->ticketRepo->expireOldPendingTransactionsStaff();

    $transactionData = null;
    $items = [];
    $staffInfo = ['staff_id' => 'N/A', 'name' => 'Staff Name', 'department' => 'N/A'];
    $qrPath = null;

    if (empty($transactionCode)) {
      $transactionCode = $_SESSION['last_ticket_code'] ?? null;
    }

    if ($transactionCode) {
      $transaction = $this->ticketRepo->getTransactionByCode($transactionCode);
      if ($transaction && $transaction['staff_id'] == $staffId) {
        $transactionData = $transaction;
        $items = $this->ticketRepo->getTransactionItems($transactionData['transaction_id']);
        $details = $this->ticketRepo->getStaffFullInfoByStaffId($staffId);
        
        if ($details) {
          $staffInfo = [
            'staff_id' => $details['employee_id'],
            'name' => $this->getFullName($details),
            'department' => $details['position']
          ];
        }
        
        if (strtolower($transactionData['status']) === 'pending') {
          $qrPath = STORAGE_URL . "/" . ($transactionData['qrcode'] ?: "storage/uploads/qrcodes/" . $transactionCode . ".svg");
        }
      }
    }

    $this->view("staff/qrBorrowingTicket", [
      "title" => "Staff QR Borrowing Ticket",
      "currentPage" => "qrBorrowingTicket",
      "transaction_code" => $transactionCode,
      "items" => $items,
      "qrPath" => $qrPath,
      "staff" => $staffInfo,
      "generated_at" => $transactionData['generated_at'] ?? null,
      "expires_at" => $transactionData['expires_at'] ?? null,
      "isBorrowed" => ($transactionData && strtolower($transactionData['status']) === 'borrowed'),
      "isExpired" => ($transactionData && strtolower($transactionData['status']) === 'expired')
    ]);
  }
}
