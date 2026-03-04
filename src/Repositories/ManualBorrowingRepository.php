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

  public function getUserIdByIdentifier(string $borrowerType, string $identifier): ?int
  {
    $sql = "";
    if ($borrowerType === 'student') {
      $sql = "SELECT user_id FROM students WHERE student_number = ? AND deleted_at IS NULL";
    } elseif ($borrowerType === 'faculty') {
      $sql = "SELECT user_id FROM faculty WHERE unique_faculty_id = ? AND deleted_at IS NULL";
    } elseif ($borrowerType === 'staff') {
      $sql = "SELECT user_id FROM staff WHERE employee_id = ? AND deleted_at IS NULL";
    } else {
      return null;
    }

    $stmt = $this->db->prepare($sql);
    $stmt->execute([$identifier]);
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

  public function createGuest(array $guestData): int
  {
    $stmt = $this->db->prepare("
        INSERT INTO guests (first_name, last_name, email, contact, created_at)
        VALUES (:fn, :ln, :email, :contact, NOW())
    ");
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

      if ($borrowData['borrower_type'] !== 'guest') {
        $userId = $this->getUserIdByIdentifier($borrowData['borrower_type'], $borrowData['borrower_id']);
        if (!$userId) throw new Exception("User record not found.");
      }

      switch ($borrowData['borrower_type']) {
        case 'student':
          $stmt = $this->db->prepare("SELECT student_id FROM students WHERE student_number = ? AND deleted_at IS NULL");
          $stmt->execute([$borrowData['borrower_id']]);
          $studentId = $stmt->fetchColumn();
          break;
        case 'faculty':
          $stmt = $this->db->prepare("SELECT faculty_id FROM faculty WHERE unique_faculty_id = ? AND deleted_at IS NULL");
          $stmt->execute([$borrowData['borrower_id']]);
          $facultyId = $stmt->fetchColumn();
          break;
        case 'staff':
          $stmt = $this->db->prepare("SELECT staff_id FROM staff WHERE employee_id = ? AND deleted_at IS NULL");
          $stmt->execute([$borrowData['borrower_id']]);
          $staffId = $stmt->fetchColumn();
          break;
        case 'guest':
          $guestId = $borrowData['borrower_id'];
          break;
      }

      $isBook = !empty($borrowData['book_id']);
      $isEquipment = !empty($borrowData['equipment_id']);

      if (!$isBook && !$isEquipment) {
        throw new Exception("Either book_id or equipment_id must be provided.");
      }

      $dueDate = null;
      if ($borrowData['borrower_type'] !== 'guest') {
        $stmtPolicy = $this->db->prepare("SELECT max_books, borrow_duration_days FROM library_policies WHERE role = ? LIMIT 1");
        $stmtPolicy->execute([$borrowData['borrower_type']]);
        $policy = $stmtPolicy->fetch(PDO::FETCH_ASSOC);

        if ($policy) {
          $maxAllowed = (int)$policy['max_books'];

          if ($isBook) {
            $currentActive = $this->countActiveBorrowedItems($userId);
            if ($currentActive >= $maxAllowed) {
              throw new Exception("Borrow limit exceeded. User already has $currentActive active items (Limit: $maxAllowed).");
            }
            $duration = (int)$policy['borrow_duration_days'];
            $dueDate = date('Y-m-d H:i:s', strtotime("+{$duration} days"));
          }
        }
      }

      if ($isEquipment) {
        $dueDate = date('Y-m-d 23:59:59');
      }

      if (!$dueDate) {
        $dueDate = date('Y-m-d H:i:s', strtotime("+7 days"));
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
            (student_id, staff_id, faculty_id, guest_id, transaction_code, borrowed_at, due_date, status, method, collateral_id, librarian_id)
            VALUES
            (:student_id, :staff_id, :faculty_id, :guest_id, :transaction_code, NOW(), :due_date, 'borrowed', 'manual', :collateral_id, :librarian_id)
        ");
      $stmt->execute([
        ':student_id' => $studentId,
        ':staff_id' => $staffId,
        ':faculty_id' => $facultyId,
        ':guest_id' => $guestId,
        ':transaction_code' => $transactionCode,
        ':due_date' => $dueDate,
        ':collateral_id' => $collateralName,
        ':librarian_id' => $borrowData['librarian_id'] ?? null
      ]);

      $transactionId = $this->db->lastInsertId();

      if ($isBook) {
        $stmt = $this->db->prepare("INSERT INTO borrow_transaction_items (transaction_id, book_id, status) VALUES (:transaction_id, :book_id, 'borrowed')");
        $stmt->execute([':transaction_id' => $transactionId, ':book_id' => $borrowData['book_id']]);
        $this->db->prepare("UPDATE books SET availability = 'borrowed', updated_at = NOW() WHERE book_id = ?")->execute([$borrowData['book_id']]);
      }

      if ($isEquipment) {
        $identifier = $borrowData['equipment_id'];
        $actualEquipmentId = null;

        if (is_numeric($identifier)) {
          $stmtCheck = $this->db->prepare("SELECT equipment_id, status FROM equipments WHERE equipment_id = ? AND is_active = 1");
          $stmtCheck->execute([$identifier]);
          $eq = $stmtCheck->fetch(PDO::FETCH_ASSOC);
          if (!$eq || $eq['status'] !== 'available') throw new Exception("Equipment is not available.");
          $actualEquipmentId = $eq['equipment_id'];
        } else {
          $stmtFindEquipment = $this->db->prepare("SELECT equipment_id, status FROM equipments WHERE (asset_tag = ? OR equipment_name = ?) AND is_active = 1 LIMIT 1");
          $stmtFindEquipment->execute([$identifier, $identifier]);
          $existingEquipment = $stmtFindEquipment->fetch(PDO::FETCH_ASSOC);

          if ($existingEquipment) {
            if ($existingEquipment['status'] !== 'available') throw new Exception("Equipment is not available.");
            $actualEquipmentId = $existingEquipment['equipment_id'];
          } else {
            $stmtCreateEquipment = $this->db->prepare("INSERT INTO equipments (equipment_name, asset_tag, status, is_active, created_at) VALUES (?, ?, 'borrowed', 1, NOW())");
            $stmtCreateEquipment->execute([$identifier, $identifier]);
            $actualEquipmentId = $this->db->lastInsertId();
          }
        }

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
