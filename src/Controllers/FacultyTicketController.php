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
use Endroid\QrCode\Writer\PngWriter;

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

  private function generateQr(string $transactionCode): string
  {
    // Relative path patungo sa Laravel folder
    $qrDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'qrcodes';
    $fileName = 'qr_' . $transactionCode . '.svg';
    $qrPath = $qrDir . DIRECTORY_SEPARATOR . $fileName;

    if (!is_dir($qrDir)) {
      mkdir($qrDir, 0777, true);
    }

    try {
      $builder = new Builder(
        writer: new \Endroid\QrCode\Writer\SvgWriter(),
        data: $transactionCode,
        encoding: new Encoding('UTF-8'),
        errorCorrectionLevel: ErrorCorrectionLevel::High,
        size: 300,
        margin: 10,
        roundBlockSizeMode: RoundBlockSizeMode::Margin
      );

      $result = $builder->build();
      $result->saveToFile($qrPath);

      return STORAGE_URL . "/uploads/qrcodes/" . $fileName;
    } catch (\Exception $e) {
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

    $policy = $this->policyRepo->getPolicyByRole('faculty');
    $MAX_BOOKS = $policy ? (int)$policy['max_books'] : 10;
    $DURATION_DAYS = $policy ? (int)$policy['borrow_duration_days'] : 14;

    if (!$userId) {
      http_response_code(403);
      echo json_encode(['success' => false, 'message' => 'Unauthorized']);
      exit;
    }

    $profile = $this->facultyProfileRepo->getProfileByUserId((int)$userId);
    if (!$profile || !$profile['is_qualified']) {
      http_response_code(400);
      echo json_encode([
        "success" => false,
        "message" => "Profile details are incomplete. Please complete your profile before checking out."
      ]);
      exit;
    }

    $facultyId = $this->ticketRepo->getFacultyIdByUserId((int)$userId);
    if (!$facultyId) {
      http_response_code(403);
      echo json_encode(['success' => false, 'message' => 'No faculty record found for this user.']);
      exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $selectedIds = $input['cart_ids'] ?? [];
    if (!is_array($selectedIds)) $selectedIds = [];

    try {
      $this->ticketRepo->beginTransaction();
      $this->ticketRepo->expireOldPendingTransactionsFaculty();

      $cartItems = !empty($selectedIds)
        ? $this->ticketRepo->getFacultyCartItemsByIds($facultyId, $selectedIds)
        : $this->ticketRepo->getFacultyCartItems($facultyId);

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

      $borrowedThisPeriod = $this->ticketRepo->countFacultyBorrowedBooks($facultyId);
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

      $existingTransaction = $this->ticketRepo->getPendingTransactionByFacultyId($facultyId);

      if ($existingTransaction) {
        $transactionId = (int)$existingTransaction['transaction_id'];
        $transactionCode = $existingTransaction['transaction_code'];
        $message = 'Checkout successful! Items added to your pending ticket.';
      } else {
        $transactionCode = strtoupper(uniqid());
        $dueDate = date("Y-m-d H:i:s", strtotime("+{$DURATION_DAYS} days"));
        $transactionId = $this->ticketRepo->createPendingTransactionForFaculty($facultyId, $transactionCode, $dueDate, 15);

        $message = 'Checkout successful! A new Borrowing Ticket has been created.';
      }

      $this->ticketRepo->addTransactionItems($transactionId, $cartItems);

      $cartItemIdsToRemove = array_column($cartItems, 'cart_id');
      if (!empty($cartItemIdsToRemove)) {
        $this->ticketRepo->removeFacultyCartItemsByIds($facultyId, $cartItemIdsToRemove);
      }

      $itemTitles = implode(', ', array_column($cartItems, 'title'));
      $this->auditRepo->log($userId, 'TICKET_CREATED', 'TRANSACTIONS', $transactionCode, "Faculty generated borrowing ticket for: $itemTitles");

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
      error_log("Checkout Error (Faculty): " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
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

    $facultyId = $this->ticketRepo->getFacultyIdByUserId((int)$userId);
    if (!$facultyId) {
      $this->view("errors/no_faculty_record", ["title" => "Error: No Faculty Record"], false);
      exit;
    }

    $this->ticketRepo->expireOldPendingTransactionsFaculty();

    $facultyDetails = $this->ticketRepo->getFacultyInfo($facultyId);

    $facultyInfo = [
      'faculty_id' => $facultyDetails['faculty_id'] ?? null,
      'name' => $this->getFullName($facultyDetails),
      'department' => $facultyDetails['department'] ?? 'N/A'
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
        $latestTransaction = $this->ticketRepo->getLatestTransactionByFacultyId($facultyId);
        if ($latestTransaction) {
          $transactionCode = $latestTransaction['transaction_code'] ?? null;
        }
      }

      if ($transactionCode) {
        $transaction = $this->ticketRepo->getTransactionByCode($transactionCode);

        if ($transaction && isset($transaction['faculty_id']) && $transaction['faculty_id'] == $facultyId) {
          $transactionData = $transaction;
          $items = $this->ticketRepo->getTransactionItems($transactionData['transaction_id']); // FIX 2b: Use $items

          $status = strtolower($transactionData['status']);
          if ($status === 'expired') {
            $isExpired = true;
          } elseif ($status === 'borrowed') {
            $isBorrowed = true;
          }

          if ($status === 'pending' && strtotime($transactionData['expires_at'] ?? '9999-01-01') <= time()) {
            $isExpired = true;
          }

          $facultyDetails = $this->ticketRepo->getFacultyInfo($transactionData['faculty_id']);

          if ($facultyDetails) {
            $facultyInfo['name'] = $this->getFullName($facultyDetails);
            $facultyInfo['department'] = $facultyDetails['department'] ?? 'N/A';
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
      error_log("ERROR in FacultyTicketController show(): " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
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
      "title" => "Faculty QR Borrowing Ticket",
      "currentPage" => "qrBorrowingTicket",
      "transaction_id" => $transactionData['transaction_id'] ?? null,
      "transaction_code" => $transactionData['transaction_code'] ?? null,
      "items" => $items,
      "qrPath" => $qrPath,
      "faculty" => $facultyInfo,
      "generated_at" => $transactionData['generated_at'] ?? null,
      "expires_at" => $transactionData['expires_at'] ?? null,
      "message" => $viewMessage,
      "error_message" => $viewError,
      "isExpired" => $isExpired,
      "isBorrowed" => $isBorrowed,
    ];

    $this->view("faculty/qrBorrowingTicket", $viewData);
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

    $facultyId = $this->ticketRepo->getFacultyIdByUserId((int)$userId);
    if (!$facultyId) {
      echo json_encode(['success' => false, 'message' => 'No faculty record found.']);
      exit;
    }

    $this->ticketRepo->expireOldPendingTransactionsFaculty();

    $facultyDetails = $this->ticketRepo->getFacultyFullInfo($facultyId);

    $facultyInfo = [
      'faculty_id' => $facultyDetails['unique_faculty_id'] ?? null,
      'name' => $this->getFullName($facultyDetails),
      'college' => $facultyDetails['college_name'] ?? 'N/A',
      'contact' => $facultyDetails['contact'] ?? null
    ];

    $pendingTransaction = $this->ticketRepo->getPendingTransactionByFacultyId($facultyId);

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
          'student_number' => $facultyDetails['unique_faculty_id'] ?? 'N/A',
          'name' => $this->getFullName($facultyDetails),
          'year_level' => 'Faculty',
          'section' => '',
          'course' => $facultyDetails['college_name'] ?? 'N/A'
        ]
      ]);
      exit;
    }

    echo json_encode([
      'success' => true,
      'status' => 'none'
    ]);
  }
}
