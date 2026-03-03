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
            f.faculty_id, f.unique_faculty_id, f.college_id, f.contact,
            u.first_name, u.last_name, u.middle_name,
            c.college_name
        FROM faculty f
        JOIN users u ON f.user_id = u.user_id
        LEFT JOIN colleges c ON f.college_id = c.college_id
        WHERE f.faculty_id = :fid
        LIMIT 1
    ");
    $stmt->execute(['fid' => $facultyId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
  }

  public function getFacultyIdByUserId(int $userId): ?int
  {
    $stmt = $this->db->prepare("SELECT faculty_id FROM faculty WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return isset($row['faculty_id']) ? (int)$row['faculty_id'] : null;
  }

  // --- CARTS LOGIC (FIXED TO USE 'carts' TABLE) ---
  public function getFacultyCartItems(int $userId): array
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

  public function getFacultyCartItemsByIds(int $userId, array $cartIds): array
  {
    if (empty($cartIds)) return [];
    $placeholders = implode(',', array_fill(0, count($cartIds), '?'));
    $stmt = $this->db->prepare("
            SELECT c.cart_id, c.book_id, b.title, b.author, b.accession_number
            FROM carts c
            JOIN books b ON c.book_id = b.book_id
            WHERE c.user_id = ? AND c.cart_id IN ($placeholders)
        ");
    $stmt->execute(array_merge([$userId], array_map('intval', $cartIds)));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function removeFacultyCartItemsByIds(int $userId, array $cartIds): void
  {
    if (empty($cartIds)) return;
    $placeholders = implode(',', array_fill(0, count($cartIds), '?'));
    $stmt = $this->db->prepare("DELETE FROM carts WHERE user_id = ? AND cart_id IN ($placeholders)");
    $stmt->execute(array_merge([$userId], array_map('intval', $cartIds)));
  }

  // --- TRANSACTION LOGIC ---
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

  public function addTransactionItems(int $transactionId, array $items): void
  {
    $stmt = $this->db->prepare("INSERT INTO borrow_transaction_items (transaction_id, book_id, status) VALUES (:tid, :bid, 'pending')");
    foreach ($items as $item) {
      $stmt->execute(['tid' => $transactionId, 'bid' => $item['book_id']]);
    }
  }

  public function getPendingTransactionByFacultyId(int $facultyId): ?array
  {
    $stmt = $this->db->prepare("SELECT * FROM borrow_transactions WHERE faculty_id = :fid AND status = 'pending' LIMIT 1");
    $stmt->execute(['fid' => $facultyId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
  }

  public function getTransactionItems(int $transactionId): array
  {
    $stmt = $this->db->prepare("
            SELECT b.book_id, b.title, b.author, b.accession_number, b.call_number, b.subject
            FROM borrow_transaction_items t
            JOIN books b ON t.book_id = b.book_id
            WHERE t.transaction_id = :tid
        ");
    $stmt->execute(['tid' => $transactionId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function expireOldPendingTransactionsFaculty(): void
  {
    $stmt = $this->db->prepare("SELECT transaction_id FROM borrow_transactions WHERE status = 'pending' AND expires_at <= NOW() AND faculty_id IS NOT NULL");
    $stmt->execute();
    $expired = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($expired)) {
      $ids = implode(',', array_fill(0, count($expired), '?'));
      $this->db->prepare("UPDATE borrow_transactions SET status = 'expired' WHERE transaction_id IN ($ids)")->execute($expired);
      $this->db->prepare("UPDATE borrow_transaction_items SET status = 'expired' WHERE transaction_id IN ($ids)")->execute($expired);
    }
  }

  public function beginTransaction(): void { $this->db->beginTransaction(); }
  public function commit(): void { $this->db->commit(); }
  public function rollback(): void { $this->db->rollBack(); }
}
