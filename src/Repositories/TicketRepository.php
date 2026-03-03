<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

class TicketRepository
{
  protected PDO $db;

  public function __construct()
  {
    $this->db = Database::getInstance()->getConnection();
  }

  public function getStudentIdByUserId(int $userId): ?int
  {
    $stmt = $this->db->prepare("SELECT student_id FROM students WHERE user_id = :uid");
    $stmt->execute(['uid' => $userId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    return $student['student_id'] ?? null;
  }

  /**
   * Universal method to count active borrowings (Pending or Borrowed) for any role
   */
  public function countActiveBorrowedItems(int $userId): int
  {
    $sql = "
        SELECT COUNT(bti.item_id) as total
        FROM borrow_transaction_items bti
        JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
        JOIN users u ON (
            bt.student_id = (SELECT student_id FROM students WHERE user_id = u.user_id) OR
            bt.faculty_id = (SELECT faculty_id FROM faculty WHERE user_id = u.user_id) OR
            bt.staff_id   = (SELECT staff_id FROM staff WHERE user_id = u.user_id)
        )
        WHERE u.user_id = :uid 
        AND bti.status IN ('pending', 'borrowed', 'overdue')
        AND bt.status NOT IN ('expired', 'returned')
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute(['uid' => $userId]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    return (int)($res['total'] ?? 0);
  }

  public function getStudentDetailsById($studentId)
  {
    $sql = "
        SELECT 
            u.first_name,
            u.middle_name,
            u.last_name,
            s.student_number,
            s.year_level,
            s.section,
            c.course_title AS course
        FROM students AS s
        INNER JOIN users AS u ON s.user_id = u.user_id
        LEFT JOIN courses AS c ON s.course_id = c.course_id
        WHERE s.student_id = ?
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([$studentId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function getBooksByTransactionCode($transactionCode)
  {
    $sql = "
        SELECT 
            bti.item_id,
            bti.status,
            b.book_id,
            b.title,
            b.author,
            b.accession_number,
            b.call_number,
            b.subject
        FROM borrow_transaction_items AS bti
        INNER JOIN borrow_transactions AS bt ON bt.transaction_id = bti.transaction_id
        INNER JOIN books AS b ON b.book_id = bti.book_id
        WHERE bt.transaction_code = ?
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([$transactionCode]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function checkProfileCompletion(int $studentId): array
  {
    $sql = "SELECT s.profile_updated, u.profile_picture, s.registration_form
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                WHERE s.student_id = ?";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([$studentId]);
    $studentData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$studentData) {
      return ['complete' => false, 'message' => 'No student record found.'];
    }

    $isProfileComplete = (bool)$studentData['profile_updated'];
    $hasPicture = !empty($studentData['profile_picture']);
    $hasForm = !empty($studentData['registration_form']);

    if (!$isProfileComplete || !$hasPicture || !$hasForm) {
      return [
        'complete' => false,
        'message' => 'Profile details are incomplete. Please complete your profile, upload a picture, and submit your registration form in "My Profile" before checking out.'
      ];
    }

    return ['complete' => true, 'message' => 'Profile is complete.'];
  }

  public function getCartItems(int $userId): array
  {
    $stmt = $this->db->prepare("
            SELECT c.cart_id, c.book_id, b.title, b.author, b.accession_number
            FROM carts c
            JOIN books b ON c.book_id = b.book_id
            WHERE c.user_id = :uid 
        ");
    $stmt->execute(['uid' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function addTransactionItems(int $transactionId, array $items): void
  {
    $stmt = $this->db->prepare("
        INSERT INTO borrow_transaction_items (transaction_id, book_id, status)
        VALUES (:tid, :bid, 'pending')
    ");
    foreach ($items as $item) {
      $stmt->execute([
        'tid' => $transactionId,
        'bid' => $item['book_id']
      ]);
    }
  }

  public function clearCart(int $userId): void
  {
    $stmt = $this->db->prepare("DELETE FROM carts WHERE user_id = :uid");
    $stmt->execute(['uid' => $userId]);
  }

  public function getStudentInfo(int $studentId): array
  {
    $stmt = $this->db->prepare("
            SELECT 
                s.student_number, 
                s.course, 
                s.year_level, 
                u.first_name, 
                u.middle_name, 
                u.last_name
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            WHERE s.student_id = :sid
        ");
    $stmt->execute(['sid' => $studentId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
  }


  public function getLatestTransactionByStudentId($studentId)
  {
    $stmt = $this->db->prepare("
            SELECT * FROM borrow_transactions
            WHERE student_id = ?
            ORDER BY borrowed_at DESC
            LIMIT 1
        ");
    $stmt->execute([$studentId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function getTransactionByCode(string $transactionCode): array
  {
    $stmt = $this->db->prepare("SELECT * FROM borrow_transactions WHERE transaction_code = :tcode");
    $stmt->execute(['tcode' => $transactionCode]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
      return [];
    }

    return $transaction;
  }

  public function getCartItemsByIds(int $userId, array $cartIds): array
  {
    $cartIds = array_map('intval', $cartIds);
    if (empty($cartIds)) return [];
    $placeholders = implode(',', array_fill(0, count($cartIds), '?'));

    $stmt = $this->db->prepare("
            SELECT c.cart_id, c.book_id, b.title, b.author, b.accession_number
            FROM carts c
            JOIN books b ON c.book_id = b.book_id
            WHERE c.user_id = ? AND c.cart_id IN ($placeholders)
        ");

    $params = array_merge([$userId], $cartIds);
    $stmt->execute($params);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result;
  }

  public function removeCartItemsByIds(int $userId, array $cartIds): void
  {
    if (empty($cartIds)) return;
    $cartIds = array_map('intval', $cartIds);
    $placeholders = implode(',', array_fill(0, count($cartIds), '?'));

    $stmt = $this->db->prepare(
      "DELETE FROM carts WHERE user_id = ? AND cart_id IN ($placeholders)"
    );

    $stmt->execute(array_merge([$userId], $cartIds));
  }

  public function getTransactionItems(int $transactionId): array
  {
    $stmt = $this->db->prepare("
            SELECT 
                b.book_id, 
                b.title, 
                b.author, 
                b.accession_number, 
                b.call_number, 
                b.subject
            FROM borrow_transaction_items t
            JOIN books b ON t.book_id = b.book_id
            WHERE t.transaction_id = :tid
        ");
    $stmt->execute(['tid' => $transactionId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function createTransaction(int $studentId, string $transactionCode, int $expiryMinutes = 15): int
  {
    $stmt = $this->db->prepare("
        INSERT INTO borrow_transactions (student_id, transaction_code, generated_at, expires_at)
        VALUES (:sid, :tcode, NOW(), DATE_ADD(NOW(), INTERVAL :exp MINUTE))
    ");
    $stmt->execute([
      'sid' => $studentId,
      'tcode' => $transactionCode,
      'exp' => $expiryMinutes
    ]);

    return (int) $this->db->lastInsertId();
  }

  public function getBorrowedBooksByTransaction(int $transactionId): array
  {
    $stmt = $this->db->prepare("
            SELECT b.title, b.accession_number
            FROM borrow_transaction_items bti
            INNER JOIN books b ON bti.book_id = b.book_id
            WHERE bti.transaction_id = :tid
        ");
    $stmt->bindValue(':tid', $transactionId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getPendingTransactionByStudentId(int $studentId)
  {
    $stmt = $this->db->prepare("
        SELECT transaction_id, transaction_code, generated_at, expires_at
        FROM borrow_transactions
        WHERE student_id = :sid
          AND status = 'pending'
        ORDER BY generated_at DESC
        LIMIT 1
    ");
    $stmt->execute(['sid' => $studentId]);
    return $stmt->fetch(\PDO::FETCH_ASSOC);
  }


  public function getBorrowedTransactionByStudentId(int $studentId): ?array
  {
    $stmt = $this->db->prepare("
            SELECT transaction_id, transaction_code, due_date
            FROM borrow_transactions
            WHERE student_id = :sid AND status = 'borrowed'
            LIMIT 1
        ");
    $stmt->execute(['sid' => $studentId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result ?: null;
  }

  public function countItemsInTransaction(int $transactionId, bool $forUpdate = false): int
  {
    $lock = $forUpdate ? ' FOR UPDATE' : '';
    $stmt = $this->db->prepare("
        SELECT COUNT(*) as total
        FROM borrow_transaction_items
        WHERE transaction_id = :tid{$lock}
    ");
    $stmt->execute(['tid' => $transactionId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return isset($row['total']) ? (int)$row['total'] : 0;
  }

  public function countBorrowedBooksThisWeek(int $studentId): int
  {
    $query = "
        SELECT COUNT(*) AS total
        FROM borrow_transaction_items bti
        INNER JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
        WHERE bt.student_id = :student_id
          AND (bt.status = 'pending' OR bt.status = 'borrowed')
          AND bt.borrowed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ";

    $stmt = $this->db->prepare($query);
    $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);
    $stmt->execute();

    return (int) $stmt->fetchColumn();
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

      $updateBooks = $this->db->prepare("
            UPDATE books b
            JOIN borrow_transaction_items bti ON b.book_id = bti.book_id
            SET b.availability = 'available'
            WHERE bti.transaction_id IN ($idsPlaceholders)
        ");
      $updateBooks->execute($expiredTransactions);

      $updateItems = $this->db->prepare("
            UPDATE borrow_transaction_items
            SET status = 'expired'
            WHERE transaction_id IN ($idsPlaceholders)
        ");
      $updateItems->execute($expiredTransactions);
    }
  }

  public function areBooksAvailable(array $bookIds): array
  {
    if (empty($bookIds)) return [];

    $placeholders = implode(',', array_fill(0, count($bookIds), '?'));

    $stmt = $this->db->prepare("
        SELECT bti.book_id, b.title
        FROM borrow_transaction_items bti
        JOIN books b ON bti.book_id = b.book_id
        JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
        WHERE bti.book_id IN ($placeholders)
          AND bt.status IN ('pending','borrowed')
    ");

    $stmt->execute($bookIds);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function setBooksAvailability(array $bookIds, string $availability): void
  {
    if ($availability === 'pending') return;
    if (empty($bookIds)) return;

    $placeholders = implode(',', array_fill(0, count($bookIds), '?'));
    $stmt = $this->db->prepare("
        UPDATE books
        SET availability = ?
        WHERE book_id IN ($placeholders)
    ");
    $stmt->execute(array_merge([$availability], $bookIds));
  }

  public function setTransactionExpiry(int $transactionId, int $minutes = 15): void
  {
    $stmt = $this->db->prepare("
        UPDATE borrow_transactions
        SET expires_at = DATE_ADD(borrowed_at, INTERVAL ? MINUTE)
        WHERE transaction_id = ?
    ");
    $stmt->execute([$minutes, $transactionId]);
  }

  public function createPendingTransaction(int $studentId, string $transactionCode, string $dueDate, string $qrPath, int $expiryMinutes = 15): int
  {
    $stmt = $this->db->prepare("
        INSERT INTO borrow_transactions 
        (student_id, transaction_code, qrcode, due_date, status, generated_at, expires_at)
        VALUES (:sid, :tcode, :qr, :due_date, 'pending', NOW(), DATE_ADD(NOW(), INTERVAL :minutes MINUTE))
    ");
    $stmt->execute([
      'sid' => $studentId,
      'tcode' => $transactionCode,
      'qr' => $qrPath,
      'due_date' => $dueDate,
      'minutes' => $expiryMinutes
    ]);

    return (int) $this->db->lastInsertId();
  }


  public function beginTransaction(): void
  {
    $this->db->beginTransaction();
  }

  public function commit(): void
  {
    $this->db->commit();
  }

  public function rollback(): void
  {
    $this->db->rollBack();
  }
}
