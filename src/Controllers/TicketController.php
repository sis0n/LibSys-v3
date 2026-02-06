<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\TicketRepository;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;


class TicketController extends Controller
{
  protected TicketRepository $ticketRepo;

  public function __construct()
  {
    $this->ticketRepo = new TicketRepository();
  }

  private function generateQr(string $transactionCode): string
  {
    $qrDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'qrcodes';
    $qrPath = $qrDir . DIRECTORY_SEPARATOR . $transactionCode . '.png';

    if (!is_dir($qrDir)) {
      mkdir($qrDir, 0777, true);
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
      header('Content-Type: application/json');
      echo json_encode([
        "success" => false,
        "error_from_qr" => $e->getMessage(),
        "checked_path" => $qrPath
      ]);
      exit;
    }
  }

  public function checkout()
  {
    if (session_status() === PHP_SESSION_NONE) session_start();
    header('Content-Type: application/json');

    $userId = $_SESSION['user_id'] ?? null;
    $MAX_BOOKS_PER_WEEK = 5;

    if (!$userId) {
      http_response_code(403);
      echo json_encode(['success' => false, 'message' => 'Unauthorized']);
      exit;
    }

    $studentId = $this->ticketRepo->getStudentIdByUserId((int)$userId);
    if (!$studentId) {
      http_response_code(403);
      echo json_encode(['success' => false, 'message' => 'No student record found for this user.']);
      exit;
    }

    $profileCheck = $this->ticketRepo->checkProfileCompletion($studentId);
    if (!$profileCheck['complete']) {
      http_response_code(400);
      echo json_encode(["success" => false, "message" => $profileCheck['message']]);
      exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $selectedIds = $input['cart_ids'] ?? [];
    if (!is_array($selectedIds)) $selectedIds = [];

    try {
      $this->ticketRepo->beginTransaction();

      // Expire old pending transactions
      $this->ticketRepo->expireOldPendingTransactions();

      // Get cart items
      $cartItems = !empty($selectedIds)
        ? $this->ticketRepo->getCartItemsByIds($studentId, $selectedIds)
        : $this->ticketRepo->getCartItems($studentId);

      if (empty($cartItems)) {
        $this->ticketRepo->rollback();
        echo json_encode(['success' => false, 'message' => 'Cart is empty or selected items not found.']);
        exit;
      }

      // Check availability
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

      // Check weekly borrowing limit
      $borrowedThisWeek = $this->ticketRepo->countBorrowedBooksThisWeek($studentId);
      $newItemsCount = count($cartItems);
      if ($borrowedThisWeek + $newItemsCount > $MAX_BOOKS_PER_WEEK) {
        $this->ticketRepo->rollback();
        http_response_code(400);
        echo json_encode([
          'success' => false,
          'message' => "You can only borrow a maximum of {$MAX_BOOKS_PER_WEEK} books per week. Current: {$borrowedThisWeek}, Trying to add: {$newItemsCount}"
        ]);
        exit;
      }

      // Check if a pending transaction already exists
      $existingTransaction = $this->ticketRepo->getPendingTransactionByStudentId($studentId);

      if ($existingTransaction) {
        $transactionId = (int)$existingTransaction['transaction_id'];
        $transactionCode = $existingTransaction['transaction_code'];
        $message = 'Checkout successful! Items added to your pending ticket.';
      } else {
        // Create new pending transaction
        $transactionCode = strtoupper(uniqid());
        $dueDate = date("Y-m-d H:i:s", strtotime("+7 days")); // default due date
        $transactionId = $this->ticketRepo->createPendingTransaction($studentId, $transactionCode, $dueDate, 15);

        $message = 'Checkout successful! A new Borrowing Ticket has been created.';
      }

      // Add items to transaction
      $this->ticketRepo->addTransactionItems($transactionId, $cartItems);

      // Remove items from cart
      $cartItemIdsToRemove = array_column($cartItems, 'cart_id');
      if (!empty($cartItemIdsToRemove)) {
        $this->ticketRepo->removeCartItemsByIds($studentId, $cartItemIdsToRemove);
      }

      // Generate QR code
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
      error_log("Checkout Error: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
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
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
      $_SESSION['error_message'] = "Please login to view your ticket.";
      header("Location: " . BASE_URL . "/login");
      exit;
    }

    $studentId = $this->ticketRepo->getStudentIdByUserId((int)$userId);
    if (!$studentId) {
      error_log("User ID $userId logged in but has no associated student record.");
      $this->view("errors/no_student_record", ["title" => "Error: No Student Record"], false);
      exit;
    }

    $this->ticketRepo->expireOldPendingTransactions();

    $transactionData = null;
    $books = [];
    $studentInfo = [
      'student_number' => 'N/A',
      'name' => 'Student Name',
      'year_level' => 'N/A',
      'course' => 'N/A'
    ];
    $qrPath = null;
    $viewMessage = null;
    $viewError = null;

    try {
      if (empty($transactionCode)) {
        $transactionCode = $_SESSION['last_ticket_code'] ?? null;
      }
      if (empty($transactionCode)) {
        $latestTransaction = $this->ticketRepo->getLatestTransactionByStudentId($studentId);
        if ($latestTransaction) {
          $transactionCode = $latestTransaction['transaction_code'] ?? null;
        }
      }

      if ($transactionCode) {
        $transaction = $this->ticketRepo->getTransactionByCode($transactionCode);

        if ($transaction && isset($transaction['student_id']) && $transaction['student_id'] == $studentId) {
          $transactionData = $transaction;
          $books = $this->ticketRepo->getTransactionItems($transactionData['transaction_id']);
          $studentDetails = $this->ticketRepo->getStudentInfo($transactionData['student_id']);

          if ($studentDetails) {
            $fullName = implode(' ', array_filter([
              $studentDetails['first_name'] ?? '',
              $studentDetails['middle_name'] ?? '',
              $studentDetails['last_name'] ?? ''
            ]));

            $studentInfo['student_number'] = $studentDetails['student_number'] ?? 'N/A';
            $studentInfo['name'] = !empty(trim($fullName)) ? trim($fullName) : 'Student Name';
            $studentInfo['year_level'] = $studentDetails['year_level'] ?? 'N/A';
            $studentInfo['course'] = $studentDetails['course'] ?? 'N/A';
          } else {
            error_log("Could not retrieve student details for student_id: " . $transactionData['student_id']);
          }

          $qrPath = $this->generateQr($transactionData['transaction_code']);
          if (empty($qrPath)) {
            error_log("Failed to generate QR code image for transaction view: " . $transactionCode);
            $viewError = "Could not generate the QR code image for this ticket.";
          }
        } else {
          error_log("Attempt to access invalid/unauthorized transaction code '$transactionCode' by student ID $studentId (User ID $userId).");
          unset($_SESSION['last_ticket_code']);
          $transactionCode = null;
          $viewError = "The requested borrowing ticket was not found or is invalid.";
        }
      } else {
        $viewMessage = "You do not currently have an active borrowing ticket.";
      }
    } catch (\Throwable $e) {
      error_log("ERROR in TicketController show(): " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
      $viewError = "An unexpected error occurred while loading your ticket details. Please try again later.";
    }

    $isExpired = false;
    if ($transactionData && strtolower($transactionData['status']) === 'expired') {
      $isExpired = true;

      unset($_SESSION['last_ticket_code']);

      $qrFile = __DIR__ . "/../../public/qrcodes/{$transactionCode}.png";
      if (file_exists($qrFile)) {
        unlink($qrFile);
      }

      $qrPath = null;
      $books = [];
      $studentInfo = [
        'student_number' => 'N/A',
        'name' => 'Student Name',
        'year_level' => 'N/A',
        'course' => 'N/A'
      ];
      $transactionData['transaction_code'] = null;
      $transactionData['borrowed_at'] = null;
      $transactionData['due_date'] = null;

      $viewMessage = "Your borrowing ticket has expired.";
    }


    $viewData = [
      "title" => "QR Borrowing Ticket",
      "currentPage" => "qrBorrowingTicket",
      "transaction_id" => $transactionData['transaction_id'] ?? null,
      "transaction_code" => $transactionData['transaction_code'] ?? null,
      "books" => $books,
      "qrPath" => $qrPath,
      "student" => $studentInfo,
      "generated_at" => $transactionData['generated_at'] ?? null,
      "expires_at" => $transactionData['expires_at'] ?? null,
      "message" => $viewMessage,
      "error_message" => $viewError,
      "isExpired" => $isExpired
    ];

    $this->view("student/qrBorrowingTicket", $viewData);
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

    $studentId = $this->ticketRepo->getStudentIdByUserId((int)$userId);
    if (!$studentId) {
      echo json_encode(['success' => false, 'message' => 'No student record found.']);
      exit;
    }

    $this->ticketRepo->expireOldPendingTransactions();

    // ✅ Check Pending Transaction
    $pending = $this->ticketRepo->getPendingTransactionByStudentId($studentId);
    if ($pending) {
      $student = $this->ticketRepo->getStudentDetailsById($studentId);
      $books = $this->ticketRepo->getBooksByTransactionCode($pending['transaction_code']);

      echo json_encode([
        'success' => true,
        'status' => 'pending',
        'transaction_code' => $pending['transaction_code'],
        'generated_at' => $pending['generated_at'],
        'expires_at' => $pending['expires_at'] ?? null,
        'student' => [
          'student_number' => $student['student_number'] ?? 'N/A',
          'name' => trim(($student['first_name'] ?? '') . ' ' . ($student['middle_name'][0] ?? '') . '. ' . ($student['last_name'] ?? '')),
          'year_level' => $student['year_level'] ?? '',
          'section' => $student['section'] ?? '',
          'course' => $student['course'] ?? 'N/A'
        ],
        'books' => $books ?? []
      ]);
      exit;
    }

    // ✅ Check Borrowed Transaction
    $borrowed = $this->ticketRepo->getBorrowedTransactionByStudentId($studentId);
    if ($borrowed) {
      $student = $this->ticketRepo->getStudentDetailsById($studentId);
      $books = $this->ticketRepo->getBooksByTransactionCode($borrowed['transaction_code']);

      echo json_encode([
        'success' => true,
        'status' => 'borrowed',
        'transaction_code' => $borrowed['transaction_code'],
        'due_date' => $borrowed['due_date'],
        'student' => [
          'student_number' => $student['student_number'] ?? 'N/A',
          'name' => trim(($student['first_name'] ?? '') . ' ' . ($student['middle_name'][0] ?? '') . '. ' . ($student['last_name'] ?? '')),
          'year_level' => $student['year_level'] ?? '',
          'section' => $student['section'] ?? '',
          'course' => $student['course'] ?? 'N/A'
        ],
        'books' => $books ?? []
      ]);
      exit;
    }

    // ❌ No Active Transaction
    echo json_encode([
      'success' => true,
      'status' => 'expired'
    ]);
  }
}
