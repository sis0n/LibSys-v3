<?php

namespace App\Services;

use App\Repositories\ReturningRepository;
use App\Repositories\AuditLogRepository;
use App\Core\Database;
use PDO;
use Exception;

class ReturningService
{
    private ReturningRepository $returningRepo;
    private AuditLogRepository $auditRepo;

    public function __construct()
    {
        $this->returningRepo = new ReturningRepository();
        $this->auditRepo = new AuditLogRepository();
    }

    /**
     * Get overdue items for a campus
     */
    public function getOverdueItems(int $campusId): array
    {
        return $this->returningRepo->getOverdue($campusId) ?? [];
    }

    /**
     * Get recent returns for a campus
     */
    public function getRecentReturns(int $limit, ?int $campusId): array
    {
        return $this->returningRepo->getRecentReturns($limit, $campusId);
    }

    /**
     * Find item by identifier for returning
     */
    public function findItemForReturn(string $identifier, ?int $currentLibrarianCampusId): array
    {
        $result = $this->returningRepo->findItemByIdentifier($identifier);
        
        if (!$result || (isset($result['status']) && $result['status'] === 'error')) {
            throw new Exception("Item not found or an error occurred.");
        }

        // Logic to detect cross-campus return
        if ($result['status'] === 'borrowed') {
            if (isset($result['matches'])) {
                foreach ($result['matches'] as &$match) {
                    $match['is_cross_campus'] = ($currentLibrarianCampusId !== null && $match['home_campus_id'] != $currentLibrarianCampusId);
                    $match['current_librarian_campus_id'] = $currentLibrarianCampusId;
                }
            } else if (isset($result['details'])) {
                $result['details']['is_cross_campus'] = ($currentLibrarianCampusId !== null && $result['details']['home_campus_id'] != $currentLibrarianCampusId);
                $result['details']['current_librarian_campus_id'] = $currentLibrarianCampusId;
            }
        }

        return $result;
    }

    /**
     * Process a return transaction
     */
    public function processReturn(int $borrowingItemId, string $condition, ?int $librarianCampusId, int $librarianId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT b.title, e.equipment_name, u.first_name, u.last_name
            FROM borrow_transaction_items bti
            JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
            LEFT JOIN books b ON bti.book_id = b.book_id
            LEFT JOIN equipments e ON bti.equipment_id = e.equipment_id
            LEFT JOIN users u ON u.user_id = COALESCE(
                (SELECT user_id FROM students WHERE student_id = bt.student_id),
                (SELECT user_id FROM faculty WHERE faculty_id = bt.faculty_id),
                (SELECT user_id FROM staff WHERE staff_id = bt.staff_id)
            )
            WHERE bti.item_id = ?
        ");
        $stmt->execute([$borrowingItemId]);
        $itemInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$itemInfo) {
            throw new Exception("Borrowing record not found.");
        }

        $success = $this->returningRepo->markAsReturned($borrowingItemId, $condition, $librarianCampusId);

        if ($success) {
            $itemName = $itemInfo['title'] ?: $itemInfo['equipment_name'] ?: "Unknown Item";
            $borrower = ($itemInfo['first_name'] . ' ' . $itemInfo['last_name']) ?: "Unknown Borrower";
            
            $actionDetails = "Item '$itemName' returned by $borrower.";
            if ($condition !== 'good') {
                $actionDetails = "Item '$itemName' marked as " . strtoupper($condition) . " during return by $borrower.";
            }

            $this->auditRepo->log($librarianId, 'RETURN', 'TRANSACTIONS', $borrowingItemId, $actionDetails);
            
            return [
                'success' => true,
                'message' => 'Book returned successfully!',
                'recent' => $this->returningRepo->getRecentReturns(10, $librarianCampusId)
            ];
        } else {
            throw new Exception('Failed to return book.');
        }
    }

    /**
     * Extend due date for a borrowing item
     */
    public function extendDueDate(int $borrowingItemId, int $daysToExtend): string
    {
        $newDueDate = $this->returningRepo->extendDueDate($borrowingItemId, $daysToExtend);
        if (!$newDueDate) {
            throw new Exception('Failed to extend due date.');
        }
        return $newDueDate;
    }
}
