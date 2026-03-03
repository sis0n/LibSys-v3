<?php

namespace App\Repositories;

use App\Core\Database;

class FacultyBorrowingHistoryRepository
{
  protected $db;

  public function __construct()
  {
    $this->db = Database::getInstance()->getConnection();
  }

  public function getBorrowingStats(int $userId): array
  {
    $stmt = $this->db->prepare("
            SELECT 
                COUNT(bti.item_id) AS total_borrowed,
                SUM(CASE WHEN bti.status IN ('borrowed', 'overdue') THEN 1 ELSE 0 END) AS currently_borrowed,
                SUM(CASE WHEN bti.status = 'returned' THEN 1 ELSE 0 END) AS total_returned,
                SUM(CASE WHEN bti.status = 'overdue' OR (bti.status = 'borrowed' AND bt.due_date < NOW()) THEN 1 ELSE 0 END) AS total_overdue
            FROM borrow_transactions bt
            JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
            JOIN faculty f ON bt.faculty_id = f.faculty_id
            WHERE f.user_id = :user_id
            AND bt.status NOT IN ('pending', 'expired')
        ");
    $stmt->execute(['user_id' => $userId]);
    $stats = $stmt->fetch(\PDO::FETCH_ASSOC);

    return [
      'total_borrowed' => (int)($stats['total_borrowed'] ?? 0),
      'currently_borrowed' => (int)($stats['currently_borrowed'] ?? 0),
      'total_returned' => (int)($stats['total_returned'] ?? 0),
      'total_overdue' => (int)($stats['total_overdue'] ?? 0)
    ];
  }

  public function getDetailedHistory(int $userId): array
  {
    $stmt = $this->db->prepare("
            SELECT 
                bt.transaction_id, 
                bti.item_id, 
                COALESCE(b.title, e.equipment_name) AS title, 
                COALESCE(b.author, 'N/A') AS author, 
                bt.borrowed_at, 
                bt.due_date, 
                bti.returned_at, 
                bti.status,
                CONCAT(faculty_user.first_name, ' ', faculty_user.last_name) AS faculty_name,
                COALESCE(CONCAT(librarian.first_name, ' ', librarian.last_name), 'N/A') AS librarian_name,
                CASE WHEN bti.book_id IS NOT NULL THEN 'Book' ELSE 'Equipment' END AS item_type
            FROM borrow_transactions bt
            JOIN borrow_transaction_items bti 
                ON bt.transaction_id = bti.transaction_id
            LEFT JOIN books b 
                ON bti.book_id = b.book_id
            LEFT JOIN equipments e
                ON bti.equipment_id = e.equipment_id
            JOIN faculty f 
                ON bt.faculty_id = f.faculty_id
            LEFT JOIN users faculty_user 
                ON f.user_id = faculty_user.user_id
            LEFT JOIN users librarian 
                ON bt.librarian_id = librarian.user_id
            WHERE f.user_id = :uid
            AND bt.status NOT IN ('pending', 'expired')
            ORDER BY bt.borrowed_at DESC
        ");
    $stmt->execute(['uid' => $userId]);
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  // --- Pagination Start ---
  public function getPaginatedBorrowingHistory(int $userId, int $limit, int $offset): array
  {
    $stmt = $this->db->prepare("
        SELECT 
            bt.transaction_id, 
            bti.item_id, 
            COALESCE(b.title, e.equipment_name) AS title, 
            COALESCE(b.author, 'N/A') AS author, 
            bt.borrowed_at, 
            bt.due_date, 
            bti.returned_at, 
            bti.status,
            COALESCE(CONCAT(librarian.first_name, ' ', librarian.last_name), 'N/A') AS librarian_name,
            CASE WHEN bti.book_id IS NOT NULL THEN 'Book' ELSE 'Equipment' END AS item_type
        FROM borrow_transactions bt
        JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
        LEFT JOIN books b ON bti.book_id = b.book_id
        LEFT JOIN equipments e ON bti.equipment_id = e.equipment_id
        JOIN faculty s ON bt.faculty_id = s.faculty_id
        LEFT JOIN users librarian ON bt.librarian_id = librarian.user_id
        WHERE s.user_id = :user_id
        AND bt.status NOT IN ('pending', 'expired')
        ORDER BY bt.borrowed_at DESC
        LIMIT :limit OFFSET :offset
    ");

    $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  public function countBorrowingHistory(int $userId): int
  {
    $stmt = $this->db->prepare("
        SELECT COUNT(bti.item_id)
        FROM borrow_transactions bt
        JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
        JOIN faculty s ON bt.faculty_id = s.faculty_id
        WHERE s.user_id = :user_id AND bt.status NOT IN ('pending', 'expired')
    ");
    $stmt->execute(['user_id' => $userId]);
    return (int)$stmt->fetchColumn();
  }
  // --- Pagination End ---
}
