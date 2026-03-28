<?php

namespace App\Controllers;

use App\Repositories\QRScannerRepository;
use App\Repositories\LibraryPolicyRepository;
use App\Core\Controller;

class QRScannerController extends Controller
{
  protected $qrScannerRepository;
  protected $policyRepo;
  protected $auditRepo;

  public function __construct()
  {
    $this->qrScannerRepository = new QRScannerRepository();
    $this->policyRepo = new LibraryPolicyRepository();
    $this->auditRepo = new \App\Repositories\AuditLogRepository();
  }

  private function processTicketLookup(string $transactionCode)
  {
    if (empty($transactionCode)) {
      return ['success' => false, 'message' => 'Please enter a ticket code.'];
    }

    $this->qrScannerRepository->expireOldPendingTransactions();

    $transaction = $this->qrScannerRepository->getTransactionDetailsByCode($transactionCode);

    if (!$transaction) {
      return ['success' => false, 'message' => 'Invalid ticket code or transaction not found.'];
    }

    if (in_array(strtolower($transaction['status']), ['expired', 'borrowed', 'returned'])) {
      return ['success' => false, 'message' => 'This ticket is already processed or expired.'];
    }

    if (!empty($transaction['faculty_id'])) {
      $userType = 'faculty';
      $idColumn = 'faculty_id';
      $idValue = (int) $transaction['faculty_id'];
    } elseif (!empty($transaction['staff_id'])) {
      $userType = 'staff';
      $idColumn = 'staff_id';
      $idValue = (int) $transaction['staff_id'];
    } else {
      $userType = 'student';
      $idColumn = 'student_id';
      $idValue = (int) $transaction['student_id'];
    }

    $policy = $this->policyRepo->getPolicyByRole($userType);
    $MAX_LIMIT = $policy ? (int)$policy['max_books'] : 5;

    $currentBorrowed = $this->qrScannerRepository->getBorrowedCount($idColumn, $idValue, $transactionCode);
    $itemsInTicket = count($this->qrScannerRepository->getTransactionItems($transactionCode));
    $projectedTotal = $currentBorrowed + $itemsInTicket;

    if ($projectedTotal > $MAX_LIMIT) {
      return [
        'success' => false,
        'message' => ucfirst($userType) . " borrowing limit exceeded. Has {$currentBorrowed} items. Cannot borrow {$itemsInTicket} more (Limit: {$MAX_LIMIT})."
      ];
    }

    $items = $this->qrScannerRepository->getTransactionItems($transactionCode);

    $middleInitial = !empty($transaction['middle_name']) ? strtoupper(substr($transaction['middle_name'], 0, 1)) . '. ' : '';
    $suffix = !empty($transaction['suffix']) ? ' ' . $transaction['suffix'] : '';
    $fullName = trim("{$transaction['first_name']} {$middleInitial}{$transaction['last_name']}{$suffix}");

    $profilePicPath = $transaction['profile_picture'];
    $profilePicUrl = null;

    if ($profilePicPath) {
      $uploadsPosition = strpos($profilePicPath, 'storage/uploads/');

      if ($uploadsPosition !== false) {
        $finalRelativePath = substr($profilePicPath, $uploadsPosition);
        $profilePicUrl = STORAGE_URL . '/' . $finalRelativePath;
      }
    }

    $userInfo = [
      'type' => $userType,
      'name' => $fullName,
      'profilePicture' => $profilePicUrl
    ];

    if ($userType === 'student') {
      $userInfo['id'] = $transaction['student_number'] ?? 'N/A';
      $userInfo['course'] = $transaction['course_code'] ?? 'N/A';
      $userInfo['yearsection'] = ($transaction['year_level'] ?? '') . '-' . ($transaction['section'] ?? '');

      $regFormPath = $transaction['registration_form'] ?? null;
      $regFormUrl = null;
      if ($regFormPath) {
        $uploadsPosition = strpos($regFormPath, 'storage/uploads/');
        if ($uploadsPosition !== false) {
          $finalRelativePath = substr($regFormPath, $uploadsPosition);
          $regFormUrl = STORAGE_URL . '/' . $finalRelativePath;
        }
      }
      $userInfo['registrationFormUrl'] = $regFormUrl;
    } elseif ($userType === 'faculty') {
      $userInfo['id'] = $transaction['unique_faculty_id'] ?? 'N/A';
      $userInfo['department'] = $transaction['college_code'] ?? 'N/A';
    } else {
      $userInfo['id'] = $transaction['employee_id'] ?? 'N/A';
      $userInfo['position'] = $transaction['position'] ?? 'N/A';
      $userInfo['contact'] = $transaction['contact'] ?? 'N/A';
    }

    $responseData = [
      'user' => $userInfo,
      'ticket' => [
        'id' => $transaction['transaction_code'],
        'generated' => $transaction['borrowed_at'],
        'status' => ucfirst($transaction['status']),
        'dueDate' => $transaction['due_date']
      ],
      'items' => array_map(function ($item) {
        return [
          'title' => $item['title'],
          'author' => $item['author'],
          'accessionNumber' => $item['accession_number'],
          'callNumber' => $item['call_number'],
          'isbn' => $item['book_isbn'],
          'bookId' => $item['book_id']
        ];
      }, $items)
    ];

    return ['success' => true, 'data' => $responseData];
  }

  public function scan()
  {
    header('Content-Type: application/json');
    $transactionCode = trim($_POST['transaction_code'] ?? '');
    $result = $this->processTicketLookup($transactionCode);

    if ($result['success']) {
      $_SESSION['last_scanned_ticket'] = $transactionCode;
    } else {
      unset($_SESSION['last_scanned_ticket']);
    }

    echo json_encode($result);
  }

  public function lookup()
  {
    header('Content-Type: application/json');

    $transactionCode = trim($_SESSION['last_scanned_ticket'] ?? '');

    $result = $this->processTicketLookup($transactionCode);

    echo json_encode($result);
  }

  public function borrowTransaction()
  {
    header('Content-Type: application/json');
    $transactionCode = trim($_POST['transaction_code'] ?? '');
    $staffId = $_SESSION['user_id'] ?? null;

    if (!$staffId) {
      echo json_encode(['success' => false, 'message' => 'Unauthorized. Staff not logged in.']);
      return;
    }

    $transaction = $this->qrScannerRepository->getTransactionDetailsByCode($transactionCode);

    if (!$transaction || strtolower($transaction['status']) !== 'pending') {
      echo json_encode(['success' => false, 'message' => 'Transaction is not in PENDING state.']);
      return;
    }

    if (!empty($transaction['faculty_id'])) {
      $idColumn = 'faculty_id';
      $idValue = $transaction['faculty_id'];
      $userType = 'faculty';
    } elseif (!empty($transaction['staff_id'])) {
      $idColumn = 'staff_id';
      $idValue = $transaction['staff_id'];
      $userType = 'staff';
    } else {
      $idColumn = 'student_id';
      $idValue = $transaction['student_id'];
      $userType = 'student';
    }

    $policy = $this->policyRepo->getPolicyByRole($userType);
    $MAX_LIMIT = $policy ? (int)$policy['max_books'] : 5;

    $currentBorrowed = $this->qrScannerRepository->getBorrowedCount($idColumn, $idValue, $transactionCode);

    if ($currentBorrowed >= $MAX_LIMIT) {
      echo json_encode(['success' => false, 'message' => "Borrowing limit reached for {$userType}."]);
      return;
    }

    $success = $this->qrScannerRepository->processBorrowing($transactionCode, $staffId);

    if ($success) {
      unset($_SESSION['last_scanned_ticket']);
      $this->auditRepo->log($staffId, 'BORROW', 'TRANSACTIONS', $transactionCode, "QR-based borrow processed for {$transaction['first_name']} {$transaction['last_name']} ({$userType})");
      echo json_encode(['success' => true, 'message' => 'Borrow transaction successfully processed.']);
    } else {
      echo json_encode(['success' => false, 'message' => 'Failed to finalize borrow transaction.']);
    }
  }


  public function history()
  {
    header('Content-Type: application/json');

    $search = $_GET['search'] ?? null;
    $status = $_GET['status'] ?? 'All Status';
    $date = $_GET['date'] ?? null;

    $history = $this->qrScannerRepository->getTransactionHistory($search, $status, $date);

    $formattedHistory = array_map(function ($h) {

      $isFaculty = !empty($h['faculty_id']);
      $isStudent = !empty($h['student_id']);

      $middleInitial = !empty($h['middle_name']) ? strtoupper(substr($h['middle_name'], 0, 1)) . '. ' : '';
      $suffix = !empty($h['suffix']) ? ' ' . $h['suffix'] : '';

      $fullName = trim("{$h['first_name']} {$middleInitial}{$h['last_name']}{$suffix}");

      $borrowedDateTime = $h['borrowed_at']
        ? date('M d, Y h:i A', strtotime($h['borrowed_at']))
        : 'N/A';

      $returnedDateTime = $h['returned_at']
        ? date('M d, Y h:i A', strtotime($h['returned_at']))
        : 'Not yet returned';

      $userIdValue = '';
      if ($isStudent) {
        $userIdValue = $h['student_number'] ?? $h['user_identifier'];
      } elseif ($isFaculty) {
        $userIdValue = $h['unique_faculty_id'] ?? $h['user_identifier'];
      } else {
        $userIdValue = $h['user_identifier'];
      }

      return [
        'userName' => $fullName,
        'userId' => $userIdValue,
        'userType' => $isFaculty ? 'Faculty' : ($isStudent ? 'Student' : 'Staff/Guest'),
        'itemsBorrowed' => (int) $h['items_borrowed'],
        'status' => ucfirst($h['status']),
        'borrowedDateTime' => $borrowedDateTime,
        'returnedDateTime' => $returnedDateTime
      ];
    }, $history);

    echo json_encode([
      'success' => true,
      'transactions' => $formattedHistory
    ]);
  }
}
