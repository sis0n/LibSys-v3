<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

class StaffTicketRepository
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

    public function getStaffFullInfoByStaffId(int $staffId): ?array
    {
        $stmt = $this->db->prepare("
        SELECT 
            s.employee_id,
            s.position,
            u.first_name,
            u.middle_name,
            u.last_name
        FROM staff s
        JOIN users u ON s.user_id = u.user_id
        WHERE s.staff_id = :sid
        LIMIT 1
    ");
        $stmt->execute(['sid' => $staffId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ?: null;
    }


    public function getStaffFullInfoByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare("
        SELECT 
            s.employee_id,
            s.position,
            u.first_name,
            u.middle_name,
            u.last_name
        FROM staff s
        JOIN users u ON s.user_id = u.user_id
        WHERE s.user_id = :uid
        LIMIT 1
    ");
        $stmt->execute(['uid' => $userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ?: null;
    }


    public function getStaffInfoByEmployeeId(int $employeeId): ?array
    {
        $stmt = $this->db->prepare("
        SELECT 
            s.employee_id, 
            s.position, 
            u.first_name, 
            u.middle_name, 
            u.last_name
        FROM staff s
        JOIN users u ON s.user_id = u.user_id
        WHERE s.employee_id = :eid
        LIMIT 1
    ");
        $stmt->execute(['eid' => $employeeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
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

    // Get staff ID from user ID
    public function getStaffIdByUserId(int $userId): ?int
    {
        $stmt = $this->db->prepare("SELECT staff_id, user_id FROM staff WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return isset($row['staff_id']) ? (int)$row['staff_id'] : null;
    }

    public function getStaffInfo(int $staffId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT 
                s.staff_id, 
                s.position, 
                u.first_name, 
                u.last_name,
                u.middle_name
            FROM staff s
            JOIN users u ON s.user_id = u.user_id
            WHERE s.staff_id = :sid
            LIMIT 1
        ");
        $stmt->execute(['sid' => $staffId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function getLatestTransactionByStaffId(int $staffId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM borrow_transactions
            WHERE staff_id = :sid
            ORDER BY generated_at DESC
            LIMIT 1
        ");
        $stmt->execute(['sid' => $staffId]);
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

    public function checkProfileCompletion(int $staffId): array
    {
        $stmt = $this->db->prepare("
            SELECT s.profile_updated, u.profile_picture
            FROM staff s
            JOIN users u ON s.user_id = u.user_id
            WHERE s.staff_id = :sid
        ");
        $stmt->execute(['sid' => $staffId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) return ['complete' => false, 'message' => 'No staff record found.'];

        $complete = (bool)$data['profile_updated'] && !empty($data['profile_picture']);
        return $complete
            ? ['complete' => true, 'message' => 'Profile complete.']
            : ['complete' => false, 'message' => 'Please complete your profile and upload a profile picture.'];
    }

    // Staff cart items
    public function getStaffCartItems(int $staffId): array
    {
        $stmt = $this->db->prepare("
            SELECT c.cart_id, c.book_id, b.title, b.author, b.accession_number
            FROM staff_carts c
            JOIN books b ON c.book_id = b.book_id
            WHERE c.staff_id = :sid
        ");
        $stmt->execute(['sid' => $staffId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStaffCartItemsByIds(int $staffId, array $cartIds): array
    {
        $cartIds = array_map('intval', $cartIds);
        if (empty($cartIds)) return [];

        $placeholders = implode(',', array_fill(0, count($cartIds), '?'));
        $stmt = $this->db->prepare("
            SELECT c.cart_id, c.book_id, b.title, b.author, b.accession_number
            FROM staff_carts c
            JOIN books b ON c.book_id = b.book_id
            WHERE c.staff_id = ? AND c.cart_id IN ($placeholders)
        ");
        $stmt->execute(array_merge([$staffId], $cartIds));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function removeStaffCartItemsByIds(int $staffId, array $cartIds): void
    {
        if (empty($cartIds)) return;
        $cartIds = array_map('intval', $cartIds);
        $placeholders = implode(',', array_fill(0, count($cartIds), '?'));
        $stmt = $this->db->prepare("
            DELETE FROM staff_carts
            WHERE staff_id = ? AND cart_id IN ($placeholders)
        ");
        $stmt->execute(array_merge([$staffId], $cartIds));
    }

    // Transactions
    public function createPendingTransactionForStaff(int $staffId, string $transactionCode, string $dueDate, int $expiryMinutes = 15): int
    {
      $stmt = $this->db->prepare("
              INSERT INTO borrow_transactions 
              (staff_id, transaction_code, due_date, status, generated_at, expires_at)
              VALUES (:sid, :tcode, :due_date, 'pending', NOW(), DATE_ADD(NOW(), INTERVAL :minutes MINUTE))
          ");
      $stmt->execute([
        'sid' => $staffId,
        'tcode' => $transactionCode,
        'due_date' => $dueDate,
        'minutes' => $expiryMinutes
      ]);

      return (int) $this->db->lastInsertId();
    }
    public function getPendingTransactionByStaffId(int $staffId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT transaction_id, transaction_code, due_date, generated_at, expires_at, status
            FROM borrow_transactions
            WHERE staff_id = :sid AND status = 'pending'
            LIMIT 1
        ");
        $stmt->execute(['sid' => $staffId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getBorrowedTransactionByStaffId(int $staffId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT transaction_id, transaction_code, due_date, status
            FROM borrow_transactions
            WHERE staff_id = :sid AND status = 'borrowed'
            LIMIT 1
        ");
        $stmt->execute(['sid' => $staffId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function countStaffBorrowedBooks(int $staffId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM borrow_transaction_items bti
            JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
            WHERE bt.staff_id = :sid
              AND (bt.status = 'pending' OR bt.status = 'borrowed')
        ");
        $stmt->execute(['sid' => $staffId]);
        return (int) $stmt->fetchColumn();
    }

    public function expireOldPendingTransactionsStaff(): void
    {
        $stmt = $this->db->prepare("
            SELECT transaction_id 
            FROM borrow_transactions
            WHERE status = 'pending'
              AND expires_at <= NOW()
              AND staff_id IS NOT NULL
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
