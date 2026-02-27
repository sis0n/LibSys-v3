<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use Exception;

class ManualBorrowingRepository
{
  private $db;

  public function __construct()
  {
    $this->db = Database::getInstance()->getConnection();
  }

  // --- Check if user exists and return type ---
  public function checkIfUserExists(string $input_user_id): ?string
  {
    // Students
    $stmt = $this->db->prepare("
            SELECT s.student_id
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            WHERE s.student_number = ? AND s.deleted_at IS NULL
        ");
    $stmt->execute([$input_user_id]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) return 'student';

    // Faculty
    $stmt = $this->db->prepare("
            SELECT f.faculty_id
            FROM faculty f
            JOIN users u ON f.user_id = u.user_id
            WHERE f.unique_faculty_id = ? AND f.deleted_at IS NULL
        ");
    $stmt->execute([$input_user_id]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) return 'faculty';

    // Staff
    $stmt = $this->db->prepare("
            SELECT st.staff_id
            FROM staff st
            JOIN users u ON st.user_id = u.user_id
            WHERE st.employee_id = ? AND st.deleted_at IS NULL
        ");
    $stmt->execute([$input_user_id]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) return 'staff';

    return null;
  }

  // --- Get user info (includes email & contact for autofill) ---
  public function getUserInfo(string $input_user_id): ?array
  {
    // Students
    $stmt = $this->db->prepare("
            SELECT u.first_name, u.middle_name, u.last_name, u.suffix, u.email, s.contact, 'student' AS role
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            WHERE s.student_number = ? AND s.deleted_at IS NULL
        ");
    $stmt->execute([$input_user_id]);
    if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) return $data;

    // Faculty
    $stmt = $this->db->prepare("
            SELECT u.first_name, u.middle_name, u.last_name, u.suffix, u.email, f.contact, 'faculty' AS role
            FROM faculty f
            JOIN users u ON f.user_id = u.user_id
            WHERE f.unique_faculty_id = ? AND f.deleted_at IS NULL
        ");
    $stmt->execute([$input_user_id]);
    if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) return $data;

    // Staff
    $stmt = $this->db->prepare("
            SELECT u.first_name, u.middle_name, u.last_name, u.suffix, u.email, st.contact, 'staff' AS role
            FROM staff st
            JOIN users u ON st.user_id = u.user_id
            WHERE st.employee_id = ? AND st.deleted_at IS NULL
        ");
    $stmt->execute([$input_user_id]);
    if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) return $data;

    return null;
  }

  // --- Create guest record ---
  public function createGuest(array $guestData): int
  {
    $stmt = $this->db->prepare("
            INSERT INTO guests (first_name, last_name, email, contact, created_at)
            VALUES (:first_name, :last_name, :email, :contact, NOW())
        ");
    $stmt->execute([
      ':first_name' => $guestData['first_name'],
      ':last_name'  => $guestData['last_name'],
      ':email'      => $guestData['email'] ?? null,
      ':contact'    => $guestData['contact'] ?? null,
    ]);

    return $this->db->lastInsertId();
  }

  // --- Get all equipment names ---
  public function getEquipments(): array
  {
    $stmt = $this->db->prepare("SELECT equipment_id, equipment_name, asset_tag FROM equipments WHERE status = 'available' ORDER BY equipment_name ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch as associative array
  }

  // --- Get all collateral names ---
  public function getCollaterals(): array
  {
    $stmt = $this->db->prepare("SELECT name FROM collaterals ORDER BY name ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
  }



  // --- Check book availability ---
  public function checkBook(string $accession_number): array
  {
    $stmt = $this->db->prepare("SELECT * FROM books WHERE accession_number = :acc LIMIT 1");
    $stmt->execute(['acc' => $accession_number]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($book) {
      return [
        'exists' => true,
        'available' => strtolower(trim($book['availability'])) === 'available',
        'details' => $book
      ];
    }

    return ['exists' => false];
  }

  // --- Mark book as borrowed ---
  public function markBookAsBorrowed(string $accessionNumber): bool
  {
    try {
      $stmt = $this->db->prepare("
                UPDATE books SET availability = 'borrowed', updated_at = NOW() WHERE accession_number = :acc
            ");
      return $stmt->execute(['acc' => $accessionNumber]);
    } catch (\PDOException $e) {
      error_log("Failed to mark book as borrowed: " . $e->getMessage());
      return false;
    }
  }

  // --- Create manual borrowing (transaction + item) ---
  public function createManualBorrow(array $borrowData): array
  {
    try {
      $this->db->beginTransaction();

      $transactionCode = strtoupper(bin2hex(random_bytes(4)));

      $studentId = $facultyId = $staffId = $guestId = null;

      // --- Resolve borrower ID based on type ---
      switch ($borrowData['borrower_type']) {
        case 'student':
          $stmt = $this->db->prepare("SELECT student_id FROM students WHERE student_number = ? AND deleted_at IS NULL");
          $stmt->execute([$borrowData['borrower_id']]);
          $studentId = $stmt->fetchColumn();
          if (!$studentId) throw new Exception("Student not found.");
          break;

        case 'faculty':
          $stmt = $this->db->prepare("SELECT faculty_id FROM faculty WHERE unique_faculty_id = ? AND deleted_at IS NULL");
          $stmt->execute([$borrowData['borrower_id']]);
          $facultyId = $stmt->fetchColumn();
          if (!$facultyId) throw new Exception("Faculty not found.");
          break;

        case 'staff':
          $stmt = $this->db->prepare("SELECT staff_id FROM staff WHERE employee_id = ? AND deleted_at IS NULL");
          $stmt->execute([$borrowData['borrower_id']]);
          $staffId = $stmt->fetchColumn();
          if (!$staffId) throw new Exception("Staff not found.");
          break;

        case 'guest':
          $guestId = $borrowData['borrower_id'];
          break;

        default:
          throw new Exception("Invalid borrower type.");
      }

      // --- Insert into borrow_transactions ---
      $stmt = $this->db->prepare("
            INSERT INTO borrow_transactions
            (student_id, staff_id, faculty_id, guest_id, transaction_code, borrowed_at, due_date, status, method, collateral_id, librarian_id)
            VALUES
            (:student_id, :staff_id, :faculty_id, :guest_id, :transaction_code, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), 'borrowed', 'manual', :collateral_id, :librarian_id)
        ");
      $stmt->execute([
        ':student_id' => $studentId,
        ':staff_id' => $staffId,
        ':faculty_id' => $facultyId,
        ':guest_id' => $guestId,
        ':transaction_code' => $transactionCode,
        ':collateral_id' => $borrowData['collateral_id'] ?? null,
        ':librarian_id' => $borrowData['librarian_id'] ?? null
      ]);

      $transactionId = $this->db->lastInsertId();

      // --- Handle book and/or equipment ---
      $isBook = !empty($borrowData['book_id']);
      $isEquipment = !empty($borrowData['equipment_id']);

      if (!$isBook && !$isEquipment) {
        throw new Exception("Either book_id or equipment_id must be provided.");
      }

      if ($isBook) {
        // Validate book exists
        $stmt = $this->db->prepare("SELECT book_id FROM books WHERE book_id = ? AND deleted_at IS NULL");
        $stmt->execute([$borrowData['book_id']]);
        if (!$stmt->fetchColumn()) {
          throw new Exception("Book not found.");
        }

        // Insert into borrow_transaction_items
        $stmt = $this->db->prepare("
                INSERT INTO borrow_transaction_items (transaction_id, book_id, status)
                VALUES (:transaction_id, :book_id, 'borrowed')
            ");
        $stmt->execute([
          ':transaction_id' => $transactionId,
          ':book_id' => $borrowData['book_id']
        ]);

        // Update book availability
        $stmt = $this->db->prepare("UPDATE books SET availability = 'borrowed', updated_at = NOW() WHERE book_id = ?");
        $stmt->execute([$borrowData['book_id']]);
      }

      if ($isEquipment) {
        $equipmentId = $borrowData['equipment_id']; // This is actually the name/asset_tag from frontend

        // Try to find existing equipment by asset_tag or equipment_name
        $stmtFindEquipment = $this->db->prepare("
          SELECT equipment_id, status FROM equipments 
          WHERE asset_tag = :identifier OR equipment_name = :identifier
          LIMIT 1
        ");
        $stmtFindEquipment->execute([':identifier' => $equipmentId]);
        $existingEquipment = $stmtFindEquipment->fetch(PDO::FETCH_ASSOC);

        if ($existingEquipment) {
          if ($existingEquipment['status'] !== 'available') {
            throw new Exception("Equipment '{$equipmentId}' is currently not available.");
          }
          $actualEquipmentId = $existingEquipment['equipment_id'];
        } else {
          // If not found, create a new equipment entry
          $stmtCreateEquipment = $this->db->prepare("
            INSERT INTO equipments (equipment_name, asset_tag, status, created_at)
            VALUES (:equipment_name, :asset_tag, 'borrowed', NOW())
          ");
          $stmtCreateEquipment->execute([
            ':equipment_name' => $equipmentId, // Using the identifier as name for new entry
            ':asset_tag' => $equipmentId, // Using the identifier as asset tag for new entry
          ]);
          $actualEquipmentId = $this->db->lastInsertId();
        }

        // Insert into borrow_transaction_items
        $stmt = $this->db->prepare("
                INSERT INTO borrow_transaction_items (transaction_id, equipment_id, status)
                VALUES (:transaction_id, :equipment_id, 'borrowed')
            ");
        $stmt->execute([
          ':transaction_id' => $transactionId,
          ':equipment_id' => $actualEquipmentId // Use the actual equipment_id (INT)
        ]);

        // Update equipment status in the equipments table
        $stmtUpdateEquipment = $this->db->prepare("
          UPDATE equipments SET status = 'borrowed', updated_at = NOW() 
          WHERE equipment_id = :equipment_id
        ");
        $stmtUpdateEquipment->execute([':equipment_id' => $actualEquipmentId]);
      }

      $this->db->commit();

      return [
        'success' => true,
        'transaction_id' => $transactionId,
        'transaction_code' => $transactionCode
      ];
    } catch (Exception $e) {
      $this->db->rollBack();
      return ['success' => false, 'message' => $e->getMessage()];
    }
  }
}
