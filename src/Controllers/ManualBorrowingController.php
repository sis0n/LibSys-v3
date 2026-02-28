<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\ManualBorrowingRepository;
use Exception;

class ManualBorrowingController extends Controller
{
  private ManualBorrowingRepository $manualRepo;

  public function __construct()
  {
    $this->manualRepo = new ManualBorrowingRepository();
  }

  public function getEquipments(): void
  {
    try {
      $equipments = $this->manualRepo->getEquipments();
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

  // --- Check if user exists ---
  public function checkUser(): void
  {
    $input_user_id = $_POST['input_user_id'] ?? null;
    if (!$input_user_id) {
      $this->sendJson(['success' => false, 'message' => 'No input_user_id provided']);
    }

    $role = $this->manualRepo->checkIfUserExists($input_user_id);
    if ($role) {
      $userInfo = $this->manualRepo->getUserInfo($input_user_id);
      $this->sendJson(['success' => true, 'exists' => true, 'data' => $userInfo]);
    } else {
      $this->sendJson(['success' => true, 'exists' => false]);
    }
  }

  // --- Create manual borrow ---
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
        'collateral_id'    => $_POST['collateral_id'] ?? null,
        'equipment_type'   => $_POST['equipment_type'] ?? null,
        'accession_number' => $_POST['accession_number'] ?? null,
        'equipment_name'   => $_POST['equipment_name'] ?? null,
        'equipment_id'     => $_POST['equipment_id'] ?? null // Hidden ID from inventory
      ];

      // --- Required field validation ---
      $required = ['first_name', 'last_name', 'role', 'collateral_id', 'equipment_type'];
      if ($data['equipment_type'] === 'Book') {
        $required[] = 'accession_number';
      } else {
        $required[] = 'equipment_name'; 
      }

      foreach ($required as $field) {
        if (empty($data[$field])) {
          $this->sendJson(['success' => false, 'message' => "Missing required field: {$field}"]);
        }
      }

      // --- Determine Item ID for repository ---
      $itemId = null; 

      if ($data['equipment_type'] === 'Book') {
        $book = $this->manualRepo->checkBook($data['accession_number']);
        if (!$book['exists']) {
          $this->sendJson(['success' => false, 'message' => 'Book not found']);
        }
        if (!$book['available']) {
          $this->sendJson(['success' => false, 'message' => 'Book is currently not available']);
        }
        $itemId = $book['details']['book_id'];
      } else {
        // PRIORITIZE: If an equipment_id was selected from inventory, use it.
        // Otherwise, use the text name (which might trigger auto-create).
        $itemId = !empty($data['equipment_id']) ? $data['equipment_id'] : $data['equipment_name']; 
      }

      // --- Check if user exists ---
      $existingRole = $this->manualRepo->checkIfUserExists($data['input_user_id']);

      $borrowerType = null;
      $borrowerId = null;

      if ($existingRole) {
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

      // --- Create borrowing transaction ---
      $borrowData = [
        'borrower_type' => $borrowerType,
        'borrower_id'   => $borrowerId,
        'collateral_id' => $data['collateral_id'],
        'librarian_id'  => $_SESSION['user_id'] ?? null
      ];

      if ($data['equipment_type'] === 'Book') {
        $borrowData['book_id'] = $itemId;
      } else {
        // For equipment, the itemId is the equipment_name, which the repository will use to find/create an equipment_id
        $borrowData['equipment_id'] = $itemId; 
      }

      $insert = $this->manualRepo->createManualBorrow($borrowData);

      if ($insert['success']) {
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
