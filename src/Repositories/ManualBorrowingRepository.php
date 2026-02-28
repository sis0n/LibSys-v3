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

  public function checkIfUserExists(string $input_user_id): ?string
  {
    $stmt = $this->db->prepare("
            SELECT s.student_id
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            WHERE s.student_number = ? AND s.deleted_at IS NULL
        ");
    $stmt->execute([$input_user_id]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) return 'student';

    $stmt = $this->db->prepare("
            SELECT f.faculty_id
            FROM faculty f
            JOIN users u ON f.user_id = u.user_id
            WHERE f.unique_faculty_id = ? AND f.deleted_at IS NULL
        ");
    $stmt->execute([$input_user_id]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) return 'faculty';

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

  public function getUserInfo(string $input_user_id): ?array
  {
    $stmt = $this->db->prepare("
            SELECT u.first_name, u.middle_name, u.last_name, u.suffix, u.email, s.contact, 'student' AS role
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            WHERE s.student_number = ? AND s.deleted_at IS NULL
        ");
    $stmt->execute([$input_user_id]);
    if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) return $data;

    $stmt = $this->db->prepare("
            SELECT u.first_name, u.middle_name, u.last_name, u.suffix, u.email, f.contact, 'faculty' AS role
            FROM faculty f
            JOIN users u ON f.user_id = u.user_id
            WHERE f.unique_faculty_id = ? AND f.deleted_at IS NULL
        ");
    $stmt->execute([$input_user_id]);
    if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) return $data;

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

  public function getEquipments(): array
  {
    $stmt = $this->db->prepare("SELECT equipment_id, equipment_name, asset_tag FROM equipments WHERE status = 'available' AND is_active = 1 ORDER BY equipment_name ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getCollaterals(): array
  {
    $stmt = $this->db->prepare("SELECT name FROM collaterals ORDER BY name ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
  }

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

  public function createManualBorrow(array $borrowData): array
  {
    try {
      $this->db->beginTransaction();

      $transactionCode = strtoupper(bin2hex(random_bytes(4)));
      $studentId = $facultyId = $staffId = null;

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

        default:
          throw new Exception("Invalid borrower type.");
      }

      $isBook = !empty($borrowData['book_id']);
      $isEquipment = !empty($borrowData['equipment_id']);

      if (!$isBook && !$isEquipment) {
        throw new Exception("Either book_id or equipment_id must be provided.");
      }

      $dueDays = 0;
      if ($isBook) {
          $role = $borrowData['borrower_type'];
          $stmtPolicy = $this->db->prepare("SELECT borrow_duration_days FROM library_policies WHERE role = ? LIMIT 1");
          $stmtPolicy->execute([$role]);
          $policyDays = $stmtPolicy->fetchColumn();
          $dueDays = ($policyDays !== false) ? (int)$policyDays : 7;
      }

      if ($isEquipment) {
          $dueDays = 0;
      }

      $collateralName = trim($borrowData['collateral_id'] ?? '');
      if (!empty($collateralName)) {
          $stmtCheckCollateral = $this->db->prepare("SELECT name FROM collaterals WHERE LOWER(name) = LOWER(?)");
          $stmtCheckCollateral->execute([$collateralName]);
          
          if (!$stmtCheckCollateral->fetch()) {
              $stmtInsertCollateral = $this->db->prepare("INSERT INTO collaterals (name) VALUES (?)");
              $stmtInsertCollateral->execute([$collateralName]);
          }
      }

      $stmt = $this->db->prepare("
            INSERT INTO borrow_transactions
            (student_id, staff_id, faculty_id, transaction_code, borrowed_at, due_date, status, method, collateral_id, librarian_id)
            VALUES
            (:student_id, :staff_id, :faculty_id, :transaction_code, NOW(), DATE_ADD(NOW(), INTERVAL :due_days DAY), 'borrowed', 'manual', :collateral_id, :librarian_id)
        ");
      $stmt->execute([
        ':student_id' => $studentId,
        ':staff_id' => $staffId,
        ':faculty_id' => $facultyId,
        ':transaction_code' => $transactionCode,
        ':due_days' => $dueDays,
        ':collateral_id' => $collateralName,
        ':librarian_id' => $borrowData['librarian_id'] ?? null
      ]);

      $transactionId = $this->db->lastInsertId();

      if ($isBook) {
        $stmt = $this->db->prepare("SELECT book_id FROM books WHERE book_id = ? AND deleted_at IS NULL");
        $stmt->execute([$borrowData['book_id']]);
        if (!$stmt->fetchColumn()) {
          throw new Exception("Book not found.");
        }

        $stmt = $this->db->prepare("
                INSERT INTO borrow_transaction_items (transaction_id, book_id, status)
                VALUES (:transaction_id, :book_id, 'borrowed')
            ");
        $stmt->execute([
          ':transaction_id' => $transactionId,
          ':book_id' => $borrowData['book_id']
        ]);

        $stmt = $this->db->prepare("UPDATE books SET availability = 'borrowed', updated_at = NOW() WHERE book_id = ?");
        $stmt->execute([$borrowData['book_id']]);
      }

      if ($isEquipment) {
        $identifier = $borrowData['equipment_id'];
        $actualEquipmentId = null;

        if (is_numeric($identifier)) {
          $stmtCheck = $this->db->prepare("SELECT equipment_id, status FROM equipments WHERE equipment_id = ? AND is_active = 1");
          $stmtCheck->execute([$identifier]);
          $eq = $stmtCheck->fetch(PDO::FETCH_ASSOC);

          if (!$eq) throw new Exception("Equipment record not found or inactive.");
          if ($eq['status'] !== 'available') throw new Exception("This item is currently {$eq['status']}.");
          
          $actualEquipmentId = $eq['equipment_id'];
        } else {
          $stmtFindEquipment = $this->db->prepare("
            SELECT equipment_id, status FROM equipments 
            WHERE (asset_tag = :identifier OR equipment_name = :identifier)
            AND is_active = 1
            LIMIT 1
          ");
          $stmtFindEquipment->execute([':identifier' => $identifier]);
          $existingEquipment = $stmtFindEquipment->fetch(PDO::FETCH_ASSOC);

          if ($existingEquipment) {
            if ($existingEquipment['status'] !== 'available') {
              throw new Exception("Equipment '{$identifier}' is currently {$existingEquipment['status']}.");
            }
            $actualEquipmentId = $existingEquipment['equipment_id'];
          } else {
            $stmtCreateEquipment = $this->db->prepare("
              INSERT INTO equipments (equipment_name, asset_tag, status, is_active, created_at)
              VALUES (:equipment_name, :asset_tag, 'borrowed', 1, NOW())
            ");
            $stmtCreateEquipment->execute([
              ':equipment_name' => $identifier,
              ':asset_tag' => $identifier,
            ]);
            $actualEquipmentId = $this->db->lastInsertId();
          }
        }

        $stmt = $this->db->prepare("
                INSERT INTO borrow_transaction_items (transaction_id, equipment_id, status)
                VALUES (:transaction_id, :equipment_id, 'borrowed')
            ");
        $stmt->execute([
          ':transaction_id' => $transactionId,
          ':equipment_id' => $actualEquipmentId
        ]);

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
