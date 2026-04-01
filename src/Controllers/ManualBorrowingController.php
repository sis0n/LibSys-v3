<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\ManualBorrowingRepository;
use Exception;

class ManualBorrowingController extends Controller
{
  private ManualBorrowingRepository $manualRepo;
  private \App\Repositories\AuditLogRepository $auditRepo;

  public function __construct()
  {
    parent::__construct();
    $this->manualRepo = new ManualBorrowingRepository();
    $this->auditRepo = new \App\Repositories\AuditLogRepository();
  }

  public function getEquipments(): void
  {
    try {
      $campusId = $this->getCampusFilter();
      $equipments = $this->manualRepo->getEquipments($campusId);
      $this->sendJson($equipments);
    } catch (Exception $e) {
      $this->sendJson(['error' => 'Failed to fetch equipments'], 500);
    }
  }

  public function getCollaterals(): void
  {
    try {
      $collaterals = $this->manualRepo->getCollaterals();
      $this->sendJson($collaterals);
    } catch (Exception $e) {
      $this->sendJson(['error' => 'Failed to fetch collaterals'], 500);
    }
  }

  private function sendJson(array $data, int $statusCode = 200): void
  {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
  }

  public function checkUser(): void
  {
    $input_user_id = $_POST['input_user_id'] ?? null;
    if (!$input_user_id) {
      $this->sendJson(['success' => false, 'message' => 'No input_user_id provided']);
    }

    $campusId = $this->getCampusFilter();
    $role = $this->manualRepo->checkIfUserExists($input_user_id, $campusId);
    if ($role) {
      $userInfo = $this->manualRepo->getUserInfo($input_user_id, $campusId);
      $this->sendJson(['success' => true, 'exists' => true, 'data' => $userInfo]);
    } else {
      $this->sendJson(['success' => true, 'exists' => false]);
    }
  }

  public function create(): void
  {
    try {
      $data = [
        'input_user_id'    => $_POST['input_user_id'] ?? null,
        'first_name'       => $_POST['first_name'] ?? null,
        'middle_name'      => $_POST['middle_name'] ?? null,
        'last_name'        => $_POST['last_name'] ?? null,
        'suffix'           => $_POST['suffix'] ?? null,
        'role'             => $_POST['role'] ?? null,
        'email'            => $_POST['email'] ?? null,
        'contact'          => $_POST['contact'] ?? null,
        'collateral_id'    => $_POST['collateral_id_hidden'] ?? null,
        'equipment_type'   => $_POST['equipment_type'] ?? null,
        'accession_number' => $_POST['accession_number'] ?? null,
        'equipment_name'   => $_POST['equipment_name'] ?? null,
        'equipment_id'     => $_POST['equipment_id'] ?? null
      ];

      $required = ['first_name', 'last_name', 'role', 'collateral_id', 'equipment_type'];
      if ($data['equipment_type'] === 'Book') {
        $required[] = 'accession_number';
      } else {
        if (empty($data['equipment_id'])) {
          $required[] = 'equipment_name';
        }
      }

      foreach ($required as $field) {
        if (empty($data[$field])) {
          $this->sendJson(['success' => false, 'message' => "Missing required field: {$field}"]);
        }
      }

      $campusId = $this->getCampusFilter();
      $existingRole = $this->manualRepo->checkIfUserExists($data['input_user_id'], $campusId);
      $borrowerType = null;
      $borrowerId = null;

      if ($existingRole) {
        $userInfo = $this->manualRepo->getUserInfo($data['input_user_id'], $campusId);
        if (empty($userInfo['profile_updated'])) {
          $this->sendJson(['success' => false, 'message' => 'Profile incomplete. Borrower must update their profile first.']);
        }
        $borrowerType = strtolower($existingRole);
        $borrowerId = $data['input_user_id'];
      } else {
        $borrowerType = 'guest';
        $borrowerId = $this->manualRepo->createGuest([
          'first_name' => $data['first_name'],
          'last_name'  => $data['last_name'],
          'email'      => $data['email'] ?? null,
          'contact'    => $data['contact'] ?? null
        ]);
      }

      $itemId = null; 
      if ($data['equipment_type'] === 'Book') {
        $book = $this->manualRepo->checkBook($data['accession_number'], $campusId);
        if (!$book['exists']) {
          $this->sendJson(['success' => false, 'message' => 'Book not found']);
        }
        if (!$book['available']) {
          $this->sendJson(['success' => false, 'message' => 'Book is currently not available']);
        }
        $itemId = $book['details']['book_id'];
      } else {
        $itemId = !empty($data['equipment_id']) ? $data['equipment_id'] : $data['equipment_name']; 
      }

      $borrowData = [
        'borrower_type' => $borrowerType,
        'borrower_id'   => $borrowerId,
        'collateral_id' => $data['collateral_id'],
        'librarian_id'  => $_SESSION['user_id'] ?? null,
        'campus_id'     => $campusId
      ];

      if ($data['equipment_type'] === 'Book') {
        $borrowData['book_id'] = $itemId;
      } else {
        $borrowData['equipment_id'] = $itemId; 
      }

      $insert = $this->manualRepo->createManualBorrow($borrowData);

      if ($insert['success']) {
        $this->auditRepo->log($_SESSION['user_id'], 'BORROW', 'TRANSACTIONS', $insert['transaction_code'], "Manual borrow processed for {$data['first_name']} {$data['last_name']} ({$data['role']}) - Item: {$data['equipment_type']}");
        $this->sendJson([
          'success' => true,
          'message' => 'Borrow transaction created successfully',
          'transaction_code' => $insert['transaction_code']
        ]);
      } else {
        $this->sendJson(['success' => false, 'message' => $insert['message']]);
      }
    } catch (Exception $e) {
      $this->sendJson(['success' => false, 'message' => $e->getMessage()]);
    }
  }
}