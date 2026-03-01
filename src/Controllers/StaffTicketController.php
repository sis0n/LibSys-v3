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
use Endroid\QrCode\Writer\PngWriter;

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

  private function generateQr(string $transactionCode): string
  {
    $qrDir = __DIR__ . "/../../public/qrcodes";
    $qrPath = $qrDir . "/{$transactionCode}.png";

    if (!is_dir($qrDir) && !mkdir($qrDir, 0777, true) && !is_dir($qrDir)) {
      error_log("Failed to create directory: " . $qrDir);
      return '';
    }

    try {
      $builder = new Builder(
        writer: new PngWriter(),
        data: $transactionCode,
        encoding: new Encoding('UTF-8'),
        errorCorrectionLevel: ErrorCorrectionLevel::High,
        size: 300,
        margin: 10,
        roundBlockSizeMode: RoundBlockSizeMode::Margin
      );

      $result = $builder->build();
      $result->saveToFile($qrPath);

      return BASE_URL . "/qrcodes/{$transactionCode}.png";
    } catch (\Exception $e) {
      error_log("QR Code Generation Error for code '$transactionCode': " . $e->getMessage());
      return '';
    }
  }

  private function getFullName(array $details): string
  {
    $firstName = $details['first_name'] ?? '';
    $lastName = $details['last_name'] ?? '';
    $middleName = $details['middle_name'] ?? '';

    $middleInitial = !empty($middleName) ? substr($middleName, 0, 1) . '.' : '';

    return trim("{$firstName} {$middleInitial} {$lastName}");
  }

  public function checkout()
  {
    if (session_status() === PHP_SESSION_NONE) session_start();
    header('Content-Type: application/json');

    $userId = $_SESSION['user_id'] ?? null;

    $policy = $this->policyRepo->getPolicyByRole('staff');
    $MAX_BOOKS = $policy ? (int)$policy['max_books'] : 7;
    $DURATION_DAYS = $policy ? (int)$policy['borrow_duration_days'] : 14;

    if (!$userId) {
      http_response_code(403);
      echo json_encode(['success' => false, 'message' => 'Unauthorized']);
      exit;
    }

    $profile = $this->staffProfileRepo->getProfileByUserId((int)$userId);
    if (!$profile || !$profile['is_qualified']) {
      http_response_code(400);
      echo json_encode([
        "success" => false,
        "message" => "Profile details are incomplete. Please complete your profile before checking out."
      ]);
      exit;
    }

    $staffId = $this->ticketRepo->getStaffIdByUserId((int)$userId);
    if (!$staffId) {
      http_response_code(403);
      echo json_encode(['success' => false, 'message' => 'No staff record found for this user.']);
      exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $selectedIds = $input['cart_ids'] ?? [];
    if (!is_array($selectedIds)) $selectedIds = [];

    try {
      $this->ticketRepo->beginTransaction();
      $this->ticketRepo->expireOldPendingTransactionsStaff();

      $cartItems = !empty($selectedIds)
        ? $this->ticketRepo->getStaffCartItemsByIds($staffId, $selectedIds)
        : $this->ticketRepo->getStaffCartItems($staffId);

      if (empty($cartItems)) {
        $this->ticketRepo->rollback();
        echo json_encode(['success' => false, 'message' => 'Cart is empty or selected items not found.']);
        exit;
      }

      $bookIds = array_column($cartItems, 'book_id');
      $unavailableBooks = $this->ticketRepo->areBooksAvailable($bookIds);
      if (!empty($unavailableBooks)) {
        $titles = implode(', ', array_column($unavailableBooks, 'title'));
        $this->ticketRepo->rollback();
        http_response_code(400);
        echo json_encode([
          'success' => false,
          'message' => "The following book(s) are already checked out or pending: $titles"
        ]);
        exit;
      }

      $borrowedThisPeriod = $this->ticketRepo->countStaffBorrowedBooks($staffId);
      $newItemsCount = count($cartItems);
      if ($borrowedThisPeriod + $newItemsCount > $MAX_BOOKS) {
        $this->ticketRepo->rollback();
        http_response_code(400);
        echo json_encode([
          'success' => false,
          'message' => "You can only borrow a maximum of {$MAX_BOOKS} books. Current: {$borrowedThisPeriod}, Trying to add: {$newItemsCount}"
        ]);
        exit;
      }

      $existingTransaction = $this->ticketRepo->getPendingTransactionByStaffId($staffId);

      if ($existingTransaction) {
        $transactionId = (int)$existingTransaction['transaction_id'];
        $transactionCode = $existingTransaction['transaction_code'];
        $message = 'Checkout successful! Items added to your pending ticket.';
      } else {
        $transactionCode = strtoupper(uniqid());
        $dueDate = date("Y-m-d H:i:s", strtotime("+{$DURATION_DAYS} days"));
        $transactionId = $this->ticketRepo->createPendingTransactionForStaff($staffId, $transactionCode, $dueDate, 15);

        $message = 'Checkout successful! A new Borrowing Ticket has been created.';
      }

      $this->ticketRepo->addTransactionItems($transactionId, $cartItems);

      $cartItemIdsToRemove = array_column($cartItems, 'cart_id');
      if (!empty($cartItemIdsToRemove)) {
        $this->ticketRepo->removeStaffCartItemsByIds($staffId, $cartItemIdsToRemove);
      }

      $itemTitles = implode(', ', array_column($cartItems, 'title'));
      $this->auditRepo->log($userId, 'TICKET_CREATED', 'TRANSACTIONS', $transactionCode, "Staff generated borrowing ticket for: $itemTitles");

      $_SESSION['last_ticket_code'] = $transactionCode;
      $qrPath = $this->generateQr($transactionCode);

      $this->ticketRepo->commit();

      echo json_encode([
        'success' => true,
        'message' => $message,
        'ticket_code' => $transactionCode,
        'qrPath' => $qrPath
      ]);
      exit;
    } catch (\Throwable $e) {
      $this->ticketRepo->rollback();
      error_log("Checkout Error (Staff): " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
      http_response_code(500);
      echo json_encode([
        'success' => false,
        'message' => 'Checkout failed: ' . $e->getMessage()
      ]);
      exit;
    }
  }

  public function show(string $transactionCode = null)
  {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
      header("Location: " . BASE_URL . "/login");
      exit;
    }

    $staffId = $this->ticketRepo->getStaffIdByUserId((int)$userId);
    if (!$staffId) {
      $this->view("errors/no_staff_record", ["title" => "Error: No Staff Record"], false);
      exit;
    }

    $this->ticketRepo->expireOldPendingTransactionsStaff();

    $staffDetails = $this->ticketRepo->getStaffInfo($staffId);

    $staffInfo = [
      'staff_id' => $staffDetails['staff_id'] ?? null,
      'name' => $this->getFullName($staffDetails),
      'department' => $staffDetails['department'] ?? 'N/A'
    ];

    $transactionData = null;
    $items = [];
    $qrPath = null;
    $viewMessage = null;
    $viewError = null;
    $isExpired = false;
    $isBorrowed = false;

    try {
      if (empty($transactionCode)) {
        $transactionCode = $_SESSION['last_ticket_code'] ?? null;
      }

      if (empty($transactionCode)) {
        $latestTransaction = $this->ticketRepo->getLatestTransactionByStaffId($staffId);
        if ($latestTransaction) {
          $transactionCode = $latestTransaction['transaction_code'] ?? null;
        }
      }

      if ($transactionCode) {
        $transaction = $this->ticketRepo->getTransactionByCode($transactionCode);

        if ($transaction && isset($transaction['staff_id']) && $transaction['staff_id'] == $staffId) {
          $transactionData = $transaction;
          $items = $this->ticketRepo->getTransactionItems($transactionData['transaction_id']);

          $status = strtolower($transactionData['status']);
          if ($status === 'expired') {
            $isExpired = true;
          } elseif ($status === 'borrowed') {
            $isBorrowed = true;
          }

          if ($status === 'pending' && strtotime($transactionData['expires_at'] ?? '9999-01-01') <= time()) {
            $isExpired = true;
          }

          $staffDetails = $this->ticketRepo->getStaffInfo($transactionData['staff_id']);

          if ($staffDetails) {
            $staffInfo['name'] = $this->getFullName($staffDetails);
            $staffInfo['department'] = $staffDetails['department'] ?? 'N/A';
          }

          if (!$isExpired && !$isBorrowed) {
            $qrPath = $this->generateQr($transactionData['transaction_code']);
            if (empty($qrPath)) {
              $viewError = "Could not generate the QR code image for this ticket.";
            }
          }

          if ($isExpired || $isBorrowed) {
            unset($_SESSION['last_ticket_code']);
            $qrFile = __DIR__ . "/../../public/qrcodes/{$transactionCode}.png";
            if (file_exists($qrFile)) unlink($qrFile);
            $qrPath = null;
          }
        } else {
          unset($_SESSION['last_ticket_code']);
          $viewError = "The requested borrowing ticket was not found or is invalid.";
        }
      } else {
        $viewMessage = "You do not currently have an active borrowing ticket.";
      }
    } catch (\Throwable $e) {
      error_log("ERROR in StaffTicketController show(): " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
      $viewError = "An unexpected error occurred while loading your ticket details.";
    }

    if ($isExpired) {
      $viewMessage = "Your borrowing ticket has expired.";
    } elseif ($isBorrowed) {
      $viewMessage = "This ticket has been successfully processed.";
    }

    if ($isExpired || $isBorrowed) {
      $transactionData['transaction_code'] = null;
    }

    $viewData = [
      "title" => "Staff QR Borrowing Ticket",
      "currentPage" => "qrBorrowingTicket",
      "transaction_id" => $transactionData['transaction_id'] ?? null,
      "transaction_code" => $transactionData['transaction_code'] ?? null,
      "items" => $items,
      "qrPath" => $qrPath,
      "staff" => $staffInfo,
      "generated_at" => $transactionData['generated_at'] ?? null,
      "expires_at" => $transactionData['expires_at'] ?? null,
      "message" => $viewMessage,
      "error_message" => $viewError,
      "isExpired" => $isExpired,
      "isBorrowed" => $isBorrowed,
    ];

    $this->view("staff/qrBorrowingTicket", $viewData);
  }

  public function checkStatus()
  {
    if (session_status() === PHP_SESSION_NONE) session_start();
    header('Content-Type: application/json');

    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
      echo json_encode(['success' => false, 'message' => 'Unauthorized']);
      exit;
    }

    $staffId = $this->ticketRepo->getStaffIdByUserId((int)$userId);
    if (!$staffId) {
      echo json_encode(['success' => false, 'message' => 'No staff record found.']);
      exit;
    }

    $this->ticketRepo->expireOldPendingTransactionsStaff();

    $staffDetails = $this->ticketRepo->getStaffFullInfoByStaffId($staffId);
    $staffData = [
      'employee_id' => $staffDetails['employee_id'] ?? 'N/A',
      'staff_name' => trim(
        ($staffDetails['first_name'] ?? '') . ' ' .
          ($staffDetails['middle_name'] ?? '') . ' ' .
          ($staffDetails['last_name'] ?? '')
      ),
      'position' => $staffDetails['position'] ?? 'N/A'
    ];

    // Default response
    $response = [
      'success' => true,
      'status' => 'none',
      'transaction_code' => null,
      'generated_at' => null,
      'expires_at' => null,
      'items' => [],
      'employee_id' => $staffData['employee_id'],
      'staff_name' => $staffData['staff_name'],
      'position' => $staffData['position'],
      'books_count' => 0
    ];

    $pendingTransaction = $this->ticketRepo->getPendingTransactionByStaffId($staffId);
    if ($pendingTransaction) {
      $status = $pendingTransaction['status'];
      if ($status === 'pending' && strtotime($pendingTransaction['expires_at'] ?? '9999-01-01') <= time()) {
        $status = 'expired';
      }

      $items = $this->ticketRepo->getTransactionItems((int)$pendingTransaction['transaction_id']);

      echo json_encode([
        'success' => true,
        'status' => $status,
        'transaction_code' => $pendingTransaction['transaction_code'],
        'generated_at' => $pendingTransaction['generated_at'],
        'expires_at' => $pendingTransaction['expires_at'],
        'books' => $items,
        'student' => [
          'student_number' => $staffData['employee_id'] ?? 'N/A',
          'name' => $staffData['staff_name'] ?? 'N/A',
          'year_level' => 'Staff',
          'section' => '',
          'course' => $staffData['position'] ?? 'N/A'
        ]
      ]);
      exit;
    }

    echo json_encode([
      'success' => true,
      'status' => 'none'
    ]);
    exit;
  }
}
