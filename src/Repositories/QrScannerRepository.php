<?php

namespace App\Repositories;

use App\Core\Database;
use Exception;
use PDO;

class QRScannerRepository
{
  protected $db;

  public function __construct()
  {
    $this->db = Database::getInstance()->getConnection();
  }

  public function getTransactionDetailsByCode(string $code)
  {
    $stmt = $this->db->prepare("
        SELECT 
            bt.transaction_id, bt.transaction_code, bt.borrowed_at, bt.due_date, bt.status,
            bt.student_id, bt.faculty_id, bt.staff_id,

            s.student_number, s.year_level, s.section, 
            
            f.unique_faculty_id, /* <<< FIXED: Walang alias, direkta sa field name */
            f.college_id,
            
            st.staff_id, st.employee_id, st.position, st.contact,
            u.profile_picture, u.first_name, u.last_name, u.middle_name, u.suffix,
            
            s.course_id,
            c.course_code, c.course_title,
            cl.college_code, cl.college_name

        FROM borrow_transactions bt
        LEFT JOIN students s ON bt.student_id = s.student_id
        LEFT JOIN faculty f ON bt.faculty_id = f.faculty_id
        LEFT JOIN staff st ON bt.staff_id = st.staff_id
        LEFT JOIN users u ON u.user_id = COALESCE(s.user_id, f.user_id, st.user_id)
        LEFT JOIN courses c ON s.course_id = c.course_id
        LEFT JOIN colleges cl ON f.college_id = cl.college_id
        
        WHERE LOWER(TRIM(bt.transaction_code)) = LOWER(:code)
        LIMIT 1
    ");
    $stmt->execute(['code' => trim($code)]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function getTransactionItems(string $code)
  {
    $stmt = $this->db->prepare("
            SELECT b.title, b.author, b.accession_number, b.call_number, b.book_isbn, b.book_id
            FROM borrow_transaction_items bti
            JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
            JOIN books b ON bti.book_id = b.book_id
            WHERE LOWER(TRIM(bt.transaction_code)) = LOWER(:code)
        ");
    $stmt->execute(['code' => trim($code)]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getBorrowedCount(string $idColumn, int $idValue, string $currentTransactionCode): int
  {
    if (!in_array($idColumn, ['student_id', 'faculty_id', 'staff_id'])) {
      return 99999;
    }

    $query = "
            SELECT COUNT(bti.item_id)
            FROM borrow_transactions bt
            JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
            WHERE bt.{$idColumn} = :id_value
              AND bti.status IN ('pending','borrowed')
              AND bt.transaction_code != :current_code
        ";
    $stmt = $this->db->prepare($query);
    $stmt->execute([
      'id_value' => $idValue,
      'current_code' => $currentTransactionCode
    ]);
    return (int)$stmt->fetchColumn();
  }

  public function saveBorrowing(array $data)
  {
    try {
      $this->db->beginTransaction();

      $stmt = $this->db->prepare("
                INSERT INTO borrow_transactions (student_id, transaction_code, borrowed_at, due_date, status)
                VALUES (:student_id, :transaction_code, NOW(), :due_date, 'pending')
            ");
      $stmt->execute([
        'student_id' => $data['student_id'],
        'transaction_code' => $data['transaction_code'],
        'due_date' => $data['due_date']
      ]);

      $transactionId = $this->db->lastInsertId();

      $stmtItem = $this->db->prepare("
                INSERT INTO borrow_transaction_items (transaction_id, book_id, status)
                VALUES (:transaction_id, :book_id, 'pending')
            ");

      foreach ($data['book_ids'] as $bookId) {
        $stmtItem->execute([
          'transaction_id' => $transactionId,
          'book_id' => $bookId
        ]);
      }

      $this->db->commit();
      return $transactionId;
    } catch (Exception $e) {
      error_log("Save Borrowing Error: " . $e->getMessage());
      $this->db->rollBack();
      return 0;
    }
  }

  public function processBorrowing(string $transactionCode, ?int $staffId = null)
  {
    try {
      $this->db->beginTransaction();

      $dueDate = date('Y-m-d H:i:s', strtotime('+7 days'));

      $stmt = $this->db->prepare("
                UPDATE borrow_transactions
                SET status = 'borrowed',
                    borrowed_at = NOW(),
                    due_date = :due_date,
                    librarian_id = :librarian_id
                WHERE transaction_code = :code AND status = 'pending'
            ");
      $stmt->execute([
        'code' => $transactionCode,
        'due_date' => $dueDate,
        'librarian_id' => $staffId
      ]);

      $transactionIdStmt = $this->db->prepare("SELECT transaction_id FROM borrow_transactions WHERE transaction_code = :code");
      $transactionIdStmt->execute(['code' => $transactionCode]);
      $transactionId = $transactionIdStmt->fetchColumn();

      $stmtItemStatus = $this->db->prepare("
                UPDATE borrow_transaction_items
                SET status = 'borrowed'
                WHERE transaction_id = :transaction_id AND status = 'pending'
            ");
      $stmtItemStatus->execute(['transaction_id' => $transactionId]);

      $items = $this->getTransactionItems($transactionCode);
      $stmtBook = $this->db->prepare("UPDATE books SET availability = 'borrowed' WHERE book_id = :book_id");
      foreach ($items as $item) {
        $stmtBook->execute(['book_id' => $item['book_id']]);
      }

      $this->db->commit();
      return true;
    } catch (Exception $e) {
      error_log("Process Borrowing Error: " . $e->getMessage());
      $this->db->rollBack();
      return false;
    }
  }


  public function getTransactionHistory(?string $search = null, ?string $status = null, ?string $date = null)
  {
    $query = "
            SELECT 
                COALESCE(s.student_number, f.unique_faculty_id) AS user_identifier,
                s.student_id, 
                f.faculty_id,
                
                bt.transaction_code, 
                bt.borrowed_at, 
                bt.status,
                MAX(bti.returned_at) AS returned_at,
                COUNT(bti.book_id) AS items_borrowed,
                u.first_name, u.last_name, u.middle_name, u.suffix,
                
                c.course_code, cl.college_code 
                
            FROM borrow_transactions bt
            LEFT JOIN students s ON bt.student_id = s.student_id
            LEFT JOIN faculty f ON bt.faculty_id = f.faculty_id
            LEFT JOIN users u ON u.user_id = COALESCE(s.user_id, f.user_id)
            LEFT JOIN courses c ON s.course_id = c.course_id
            LEFT JOIN colleges cl ON f.college_id = cl.college_id
            
            JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
            WHERE 1=1
            AND bt.status IN ('borrowed', 'returned', 'pending')
        ";

    $params = [];

    if ($search) {
      $query .= " AND (s.student_number LIKE :search OR f.unique_faculty_id LIKE :search OR bt.transaction_code LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search)";
      $params['search'] = "%$search%";
    }

    if ($status && $status !== 'All Status') {
      $query .= " AND bt.status = :status";
      $params['status'] = strtolower($status);
    }

    if ($date) {
      $query .= " AND DATE(bt.borrowed_at) = :date";
      $params['date'] = $date;
    }

    $query .= " GROUP BY 
            bt.transaction_id, bt.transaction_code, bt.borrowed_at, bt.status, 
            s.student_number, s.student_id, c.course_code, 
            f.faculty_id, f.unique_faculty_id, cl.college_code,
            u.first_name, u.last_name, u.middle_name, u.suffix
            ORDER BY bt.borrowed_at DESC";

    $stmt = $this->db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getAllTransactions()
  {
    $stmt = $this->db->query("SELECT transaction_code, status FROM borrow_transactions");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function expireOldPendingTransactions(): void
  {
    $stmt = $this->db->prepare("
            SELECT transaction_id 
            FROM borrow_transactions
            WHERE status = 'pending'
            AND expires_at <= NOW()
        ");
    $stmt->execute();
    $expiredTransactions = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($expiredTransactions)) {
      $idsPlaceholders = implode(',', array_fill(0, count($expiredTransactions), '?'));

      $updateTrans = $this->db->prepare("
                UPDATE borrow_transactions
                SET status = 'expired'
                WHERE transaction_id IN ($idsPlaceholders)
            ");
      $updateTrans->execute($expiredTransactions);

      $updateItems = $this->db->prepare("
                UPDATE borrow_transaction_items
                SET status = 'expired'
                WHERE transaction_id IN ($idsPlaceholders)
            ");
      $updateItems->execute($expiredTransactions);

      $updateBooks = $this->db->prepare("
                UPDATE books b
                JOIN borrow_transaction_items bti ON b.book_id = bti.book_id
                SET b.availability = 'available'
                WHERE bti.transaction_id IN ($idsPlaceholders)
            ");
      $updateBooks->execute($expiredTransactions);
    }
  }
}
