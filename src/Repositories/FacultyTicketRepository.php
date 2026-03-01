<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

class FacultyTicketRepository
{
  protected PDO $db;

  public function __construct()
  {
    $this->db = Database::getInstance()->getConnection();
  }

  public function getTransactionByCode(string $transactionCode): ?array
  {
    $stmt = $this->db->prepare("SELECT * FROM borrow_transactions WHERE transaction_code = :tcode");
    $stmt->execute(['tcode' => $transactionCode]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
  }

  public function getFacultyFullInfo(int $facultyId): ?array
{
    $stmt = $this->db->prepare("
        SELECT 
            f.faculty_id,
            f.unique_faculty_id,
            f.college_id,
            f.contact,
            u.first_name,
            u.last_name,
            u.middle_name,
            c.college_name
        FROM faculty f
        JOIN users u ON f.user_id = u.user_id
        LEFT JOIN colleges c ON f.college_id = c.college_id
        WHERE f.faculty_id = :fid
        LIMIT 1
    ");
    $stmt->execute(['fid' => $facultyId]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    return $data ?: null;
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

  // Get faculty ID from user ID
  public function getFacultyIdByUserId(int $userId): ?int
  {
    $stmt = $this->db->prepare("SELECT faculty_id, user_id FROM faculty WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return isset($row['faculty_id']) ? (int)$row['faculty_id'] : null;
  }


  public function getFacultyInfo(int $facultyId): ?array
  {
    $stmt = $this->db->prepare("
        SELECT 
            f.faculty_id, 
            f.department, 
            u.first_name, 
            u.last_name,
            u.middle_name   
        FROM faculty f
        JOIN users u ON f.user_id = u.user_id  
        WHERE f.faculty_id = :fid
        LIMIT 1
    ");
    // Siguraduhin na 'faculty' ang tamang table name, at 'department' ang tamang column.
    $stmt->execute(['fid' => $facultyId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result ?: null;
  }

  public function getLatestTransactionByFacultyId(int $facultyId): ?array
  {
    $stmt = $this->db->prepare("
            SELECT * FROM borrow_transactions
            WHERE faculty_id = :fid
            ORDER BY generated_at DESC
            LIMIT 1
        ");
    $stmt->execute(['fid' => $facultyId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
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

  public function checkProfileCompletion(int $facultyId): array
  {
    $stmt = $this->db->prepare("
            SELECT f.profile_updated, u.profile_picture
            FROM faculty f
            JOIN users u ON f.user_id = u.user_id
            WHERE f.faculty_id = :fid
        ");
    $stmt->execute(['fid' => $facultyId]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) return ['complete' => false, 'message' => 'No faculty record found.'];

    $complete = (bool)$data['profile_updated'] && !empty($data['profile_picture']);
    return $complete
      ? ['complete' => true, 'message' => 'Profile complete.']
      : ['complete' => false, 'message' => 'Please complete your profile and upload a profile picture.'];
  }

  // Faculty cart items
  public function getFacultyCartItems(int $facultyId): array
  {
    $stmt = $this->db->prepare("
            SELECT c.cart_id, c.book_id, b.title, b.author, b.accession_number
            FROM faculty_carts c
            JOIN books b ON c.book_id = b.book_id
            WHERE c.faculty_id = :fid
        ");
    $stmt->execute(['fid' => $facultyId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getFacultyCartItemsByIds(int $facultyId, array $cartIds): array
  {
    $cartIds = array_map('intval', $cartIds);
    if (empty($cartIds)) return [];

    $placeholders = implode(',', array_fill(0, count($cartIds), '?'));
    $stmt = $this->db->prepare("
            SELECT c.cart_id, c.book_id, b.title, b.author, b.accession_number
            FROM faculty_carts c
            JOIN books b ON c.book_id = b.book_id
            WHERE c.faculty_id = ? AND c.cart_id IN ($placeholders)
        ");
    $stmt->execute(array_merge([$facultyId], $cartIds));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function removeFacultyCartItemsByIds(int $facultyId, array $cartIds): void
  {
    if (empty($cartIds)) return;
    $cartIds = array_map('intval', $cartIds);
    $placeholders = implode(',', array_fill(0, count($cartIds), '?'));
    $stmt = $this->db->prepare("
            DELETE FROM faculty_carts
            WHERE faculty_id = ? AND cart_id IN ($placeholders)
        ");
    $stmt->execute(array_merge([$facultyId], $cartIds));
  }

  // Transactions
  public function createPendingTransactionForFaculty(int $facultyId, string $transactionCode, string $dueDate, string $qrPath, int $expiryMinutes = 15): int
  {
    $stmt = $this->db->prepare("
            INSERT INTO borrow_transactions 
            (faculty_id, transaction_code, qrcode, due_date, status, generated_at, expires_at)
            VALUES (:fid, :tcode, :qr, :due_date, 'pending', NOW(), DATE_ADD(NOW(), INTERVAL :minutes MINUTE))
        ");
    $stmt->execute([
      'fid' => $facultyId,
      'tcode' => $transactionCode,
      'qr' => $qrPath,
      'due_date' => $dueDate,
      'minutes' => $expiryMinutes
    ]);

    return (int) $this->db->lastInsertId();
  }

  public function getPendingTransactionByFacultyId(int $facultyId): ?array
  {
    $stmt = $this->db->prepare("
        SELECT transaction_id, transaction_code, due_date, generated_at, expires_at, status
        FROM borrow_transactions
        WHERE faculty_id = :fid AND status = 'pending'
        LIMIT 1
    ");
    $stmt->execute(['fid' => $facultyId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
  }

  public function getBorrowedTransactionByFacultyId(int $facultyId): ?array
  {
    $stmt = $this->db->prepare("
        SELECT transaction_id, transaction_code, due_date, status
        FROM borrow_transactions
        WHERE faculty_id = :fid AND status = 'borrowed'
        LIMIT 1
    ");
    $stmt->execute(['fid' => $facultyId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
  }

  public function countFacultyBorrowedBooks(int $facultyId): int
  {
    $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM borrow_transaction_items bti
            JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
            WHERE bt.faculty_id = :fid
              AND (bt.status = 'pending' OR bt.status = 'borrowed')
        ");
    $stmt->execute(['fid' => $facultyId]);
    return (int) $stmt->fetchColumn();
  }

  public function expireOldPendingTransactionsFaculty(): void
  {
    $stmt = $this->db->prepare("
            SELECT transaction_id 
            FROM borrow_transactions
            WHERE status = 'pending'
              AND expires_at <= NOW()
              AND faculty_id IS NOT NULL
        ");
    $stmt->execute();
    $expiredTransactions = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($expiredTransactions)) {
      $placeholders = implode(',', array_fill(0, count($expiredTransactions), '?'));

      $updateTrans = $this->db->prepare("
                UPDATE borrow_transactions
                SET status = 'expired'
                WHERE transaction_id IN ($placeholders)
            ");
      $updateTrans->execute($expiredTransactions);

      $updateBooks = $this->db->prepare("
                UPDATE books b
                JOIN borrow_transaction_items bti ON b.book_id = bti.book_id
                SET b.availability = 'available'
                WHERE bti.transaction_id IN ($placeholders)
            ");
      $updateBooks->execute($expiredTransactions);

      $updateItems = $this->db->prepare("
                UPDATE borrow_transaction_items
                SET status = 'expired'
                WHERE transaction_id IN ($placeholders)
            ");
      $updateItems->execute($expiredTransactions);
    }
  }

  // Transaction helpers
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
