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

  public function checkIfUserExists(string $input_user_id, ?int $campusId = null): ?string
  {
    $campusWhere = $campusId !== null ? " AND u.campus_id = :campus_id" : "";
    
    // Student Check
    $stmt = $this->db->prepare("SELECT s.student_id FROM students s JOIN users u ON s.user_id = u.user_id JOIN campuses cp ON u.campus_id = cp.campus_id WHERE s.student_number = :user_id AND cp.is_active = 1 AND s.deleted_at IS NULL $campusWhere");
    $params = [':user_id' => $input_user_id];
    if ($campusId !== null) $params[':campus_id'] = $campusId;
    $stmt->execute($params);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) return 'student';

    // Faculty Check
    $stmt = $this->db->prepare("SELECT f.faculty_id FROM faculty f JOIN users u ON f.user_id = u.user_id JOIN campuses cp ON u.campus_id = cp.campus_id WHERE f.unique_faculty_id = :user_id AND cp.is_active = 1 AND f.deleted_at IS NULL $campusWhere");
    $stmt->execute($params);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) return 'faculty';

    // Staff Check
    $stmt = $this->db->prepare("SELECT st.staff_id FROM staff st JOIN users u ON st.user_id = u.user_id JOIN campuses cp ON u.campus_id = cp.campus_id WHERE st.employee_id = :user_id AND cp.is_active = 1 AND st.deleted_at IS NULL $campusWhere");
    $stmt->execute($params);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) return 'staff';

    return null;
  }

  public function getUserIdByIdentifier(string $borrowerType, string $identifier, ?int $campusId = null): ?int
  {
    $sql = "";
    $campusWhere = $campusId !== null ? " AND u.campus_id = :campus_id" : "";
    
    if ($borrowerType === 'student') {
      $sql = "SELECT s.user_id FROM students s JOIN users u ON s.user_id = u.user_id WHERE s.student_number = :id AND s.deleted_at IS NULL $campusWhere";
    } elseif ($borrowerType === 'faculty') {
      $sql = "SELECT f.user_id FROM faculty f JOIN users u ON f.user_id = u.user_id WHERE f.unique_faculty_id = :id AND f.deleted_at IS NULL $campusWhere";
    } elseif ($borrowerType === 'staff') {
      $sql = "SELECT st.user_id FROM staff st JOIN users u ON st.user_id = u.user_id WHERE st.employee_id = :id AND st.deleted_at IS NULL $campusWhere";
    } else {
      return null;
    }

    $stmt = $this->db->prepare($sql);
    $params = [':id' => $identifier];
    if ($campusId !== null) $params[':campus_id'] = $campusId;
    $stmt->execute($params);
    return $stmt->fetchColumn() ?: null;
  }

  public function countActiveBorrowedItems(int $userId): int
  {
    $sql = "
        SELECT COUNT(bti.item_id) as total
        FROM borrow_transaction_items bti
        JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
        WHERE bt.status NOT IN ('expired', 'returned')
        AND bti.status IN ('pending', 'borrowed', 'overdue')
        AND (
            bt.student_id = (SELECT student_id FROM students WHERE user_id = :uid) OR
            bt.faculty_id = (SELECT faculty_id FROM faculty WHERE user_id = :uid) OR
            bt.staff_id   = (SELECT staff_id FROM staff WHERE user_id = :uid)
        )
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->execute(['uid' => $userId]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    return (int)($res['total'] ?? 0);
  }

  public function getUserInfo(string $input_user_id, ?int $campusId = null): ?array
  {
    $campusWhere = $campusId !== null ? " AND u.campus_id = :campus_id" : "";
    $params = [':user_id' => $input_user_id];
    if ($campusId !== null) $params[':campus_id'] = $campusId;

    $stmt = $this->db->prepare("SELECT u.first_name, u.middle_name, u.last_name, u.suffix, u.email, s.contact, s.profile_updated, 'student' AS role FROM students s JOIN users u ON s.user_id = u.user_id JOIN campuses cp ON u.campus_id = cp.campus_id WHERE s.student_number = :user_id AND cp.is_active = 1 AND s.deleted_at IS NULL $campusWhere");
    $stmt->execute($params);
    if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) return $data;

    $stmt = $this->db->prepare("SELECT u.first_name, u.middle_name, u.last_name, u.suffix, u.email, f.contact, f.profile_updated, 'faculty' AS role FROM faculty f JOIN users u ON f.user_id = u.user_id JOIN campuses cp ON u.campus_id = cp.campus_id WHERE f.unique_faculty_id = :user_id AND cp.is_active = 1 AND f.deleted_at IS NULL $campusWhere");
    $stmt->execute($params);
    if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) return $data;

    $stmt = $this->db->prepare("SELECT u.first_name, u.middle_name, u.last_name, u.suffix, u.email, st.contact, st.profile_updated, 'staff' AS role FROM staff st JOIN users u ON st.user_id = u.user_id JOIN campuses cp ON u.campus_id = cp.campus_id WHERE st.employee_id = :user_id AND cp.is_active = 1 AND st.deleted_at IS NULL $campusWhere");
    $stmt->execute($params);
    if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) return $data;

    return null;
  }

  public function getEquipments(?int $campusId = null): array
  {
    $where = " WHERE status = 'available' AND is_active = 1 ";
    if ($campusId !== null) {
        $where .= " AND campus_id = :campus_id ";
    }
    $stmt = $this->db->prepare("SELECT equipment_id, equipment_name, asset_tag FROM equipments $where ORDER BY equipment_name ASC");
    if ($campusId !== null) $stmt->bindValue(':campus_id', $campusId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getCollaterals(): array
  {
    $stmt = $this->db->prepare("SELECT collateral_id, name FROM collaterals ORDER BY name ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function checkBook(string $accession_number, ?int $campusId = null): array
  {
    $where = " WHERE accession_number = :acc ";
    if ($campusId !== null) {
        $where .= " AND campus_id = :campus_id ";
    }
    $stmt = $this->db->prepare("SELECT * FROM books $where LIMIT 1");
    $params = ['acc' => $accession_number];
    if ($campusId !== null) $params['campus_id'] = $campusId;
    $stmt->execute($params);
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

  public function createGuest(array $guestData): int
  {
    $stmt = $this->db->prepare("INSERT INTO guests (first_name, last_name, email, contact, created_at) VALUES (:fn, :ln, :email, :contact, NOW())");
    $stmt->execute([
      'fn'      => $guestData['first_name'],
      'ln'      => $guestData['last_name'],
      'email'   => $guestData['email'] ?? null,
      'contact' => $guestData['contact'] ?? null
    ]);
    return (int)$this->db->lastInsertId();
  }

  public function createManualBorrow(array $borrowData): array
  {
    try {
      $this->db->beginTransaction();
      $transactionCode = strtoupper(bin2hex(random_bytes(4)));
      $studentId = $facultyId = $staffId = $guestId = null;
      $userId = null;
      $campusId = $borrowData['campus_id'] ?? null;

      if ($borrowData['borrower_type'] !== 'guest') {
        $userId = $this->getUserIdByIdentifier($borrowData['borrower_type'], $borrowData['borrower_id']);
        if (!$userId) throw new Exception("User record not found.");
      }

      switch ($borrowData['borrower_type']) {
        case 'student':
          $stmt = $this->db->prepare("SELECT s.student_id FROM students s JOIN users u ON s.user_id = u.user_id WHERE s.student_number = :id AND s.deleted_at IS NULL" . ($campusId ? " AND u.campus_id = :campus_id" : ""));
          $params = [':id' => $borrowData['borrower_id']];
          if ($campusId) $params[':campus_id'] = $campusId;
          $stmt->execute($params);
          $studentId = $stmt->fetchColumn();
          break;
        case 'faculty':
          $stmt = $this->db->prepare("SELECT f.faculty_id FROM faculty f JOIN users u ON f.user_id = u.user_id WHERE f.unique_faculty_id = :id AND f.deleted_at IS NULL" . ($campusId ? " AND u.campus_id = :campus_id" : ""));
          $params = [':id' => $borrowData['borrower_id']];
          if ($campusId) $params[':campus_id'] = $campusId;
          $stmt->execute($params);
          $facultyId = $stmt->fetchColumn();
          break;
        case 'staff':
          $stmt = $this->db->prepare("SELECT st.staff_id FROM staff st JOIN users u ON st.user_id = u.user_id WHERE st.employee_id = :id AND st.deleted_at IS NULL" . ($campusId ? " AND u.campus_id = :campus_id" : ""));
          $params = [':id' => $borrowData['borrower_id']];
          if ($campusId) $params[':campus_id'] = $campusId;
          $stmt->execute($params);
          $staffId = $stmt->fetchColumn();
          break;
        case 'guest':
          $guestId = $borrowData['borrower_id'];
          break;
      }

      $isBook = !empty($borrowData['book_id']);
      $isEquipment = !empty($borrowData['equipment_id']);
      if (!$isBook && !$isEquipment) throw new Exception("Either book_id or equipment_id must be provided.");

      $dueDate = null;

      if ($isBook && $borrowData['borrower_type'] !== 'guest') {
        $policyWhere = "role = ?";
        $policyParams = [$borrowData['borrower_type']];
        if ($campusId) {
            $policyWhere .= " AND campus_id = ?";
            $policyParams[] = $campusId;
        }
        $stmtPolicy = $this->db->prepare("SELECT max_books, borrow_duration_days FROM library_policies WHERE $policyWhere LIMIT 1");
        $stmtPolicy->execute($policyParams);
        $policy = $stmtPolicy->fetch(PDO::FETCH_ASSOC);
        if ($policy) {
          $maxAllowed = (int)$policy['max_books'];
          $currentActive = $this->countActiveBorrowedItems($userId);
          if ($currentActive >= $maxAllowed) throw new Exception("Borrow limit exceeded. User already has $currentActive active items.");
          $duration = (int)$policy['borrow_duration_days'];
          $dueDate = date('Y-m-d H:i:s', strtotime("+{$duration} days"));
        }
      }

      if ($isEquipment) {
        $policyWhere = "role = 'equipment'";
        $policyParams = [];
        if ($campusId) {
            $policyWhere .= " AND campus_id = ?";
            $policyParams[] = $campusId;
        }
        $stmtPolicy = $this->db->prepare("SELECT max_books, borrow_duration_days FROM library_policies WHERE $policyWhere LIMIT 1");
        $stmtPolicy->execute($policyParams);
        $policy = $stmtPolicy->fetch(PDO::FETCH_ASSOC);
        if ($policy) {
          $duration = (int)$policy['borrow_duration_days'];
          if ($duration === 0) {
            $dueDate = date('Y-m-d 23:59:59');
          } else {
            $dueDate = date('Y-m-d H:i:s', strtotime("+{$duration} days"));
          }
        }
      }

      if (!$dueDate) $dueDate = date('Y-m-d H:i:s', strtotime("+7 days"));

      $collateralId = !empty($borrowData['collateral_id']) ? (int)$borrowData['collateral_id'] : null;

      $stmt = $this->db->prepare("
            INSERT INTO borrow_transactions (student_id, staff_id, faculty_id, guest_id, transaction_code, borrowed_at, due_date, status, method, collateral_id, librarian_id, campus_id)
            VALUES (:student_id, :staff_id, :faculty_id, :guest_id, :transaction_code, NOW(), :due_date, 'borrowed', 'manual', :collateral_id, :librarian_id, :campus_id)
        ");
      $stmt->execute([
        ':student_id' => $studentId,
        ':staff_id' => $staffId,
        ':faculty_id' => $facultyId,
        ':guest_id' => $guestId,
        ':transaction_code' => $transactionCode,
        ':due_date' => $dueDate,
        ':collateral_id' => $collateralId,
        ':librarian_id' => $borrowData['librarian_id'] ?? null,
        ':campus_id' => $campusId
      ]);

      $transactionId = $this->db->lastInsertId();

      if ($isBook) {
        $stmt = $this->db->prepare("INSERT INTO borrow_transaction_items (transaction_id, book_id, status) VALUES (:transaction_id, :book_id, 'borrowed')");
        $stmt->execute([':transaction_id' => $transactionId, ':book_id' => $borrowData['book_id']]);
        $this->db->prepare("UPDATE books SET availability = 'borrowed', updated_at = NOW() WHERE book_id = ?")->execute([$borrowData['book_id']]);
      }

      if ($isEquipment) {
        $actualEquipmentId = (int)$borrowData['equipment_id'];
        $stmtCheck = $this->db->prepare("SELECT equipment_id, status FROM equipments WHERE equipment_id = ? AND is_active = 1");
        $stmtCheck->execute([$actualEquipmentId]);
        $eq = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        
        if (!$eq) throw new Exception("Equipment not found in inventory.");
        if ($eq['status'] !== 'available') throw new Exception("Equipment is currently not available.");

        $stmt = $this->db->prepare("INSERT INTO borrow_transaction_items (transaction_id, equipment_id, status) VALUES (?, ?, 'borrowed')");
        $stmt->execute([$transactionId, $actualEquipmentId]);
        $this->db->prepare("UPDATE equipments SET status = 'borrowed', updated_at = NOW() WHERE equipment_id = ?")->execute([$actualEquipmentId]);
      }

      $this->db->commit();
      return ['success' => true, 'transaction_id' => $transactionId, 'transaction_code' => $transactionCode];
    } catch (Exception $e) {
      if ($this->db->inTransaction()) $this->db->rollBack();
      return ['success' => false, 'message' => $e->getMessage()];
    }
  }
}