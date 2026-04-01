<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use Exception;

class BulkDeleteRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create a new bulk delete request
     */
    public function createRequest(int $requesterId, array $items, string $entityType, ?string $reason = null): int
    {
        try {
            $this->db->beginTransaction();

            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $stmt = $this->db->prepare("
                INSERT INTO bulk_delete_requests (requester_id, entity_type, status, reason, expires_at)
                VALUES (?, ?, 'pending', ?, ?)
            ");
            $stmt->execute([$requesterId, $entityType, $reason, $expiresAt]);
            $requestId = (int)$this->db->lastInsertId();

            $stmtItem = $this->db->prepare("
                INSERT INTO bulk_delete_request_items (request_id, entity_id)
                VALUES (?, ?)
            ");

            foreach ($items as $itemId) {
                $stmtItem->execute([$requestId, $itemId]);
            }

            $this->db->commit();
            return $requestId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Get pending requests for approval
     */
    public function getPendingRequests(?int $campusId = null): array
    {
        // Auto-expire requests first
        $this->db->query("UPDATE bulk_delete_requests SET status = 'expired' WHERE status = 'pending' AND expires_at < NOW()");

        $sql = "
            SELECT 
                r.*, 
                u.first_name, u.last_name, u.role as requester_role, u.campus_id,
                cp.campus_name,
                (SELECT COUNT(*) FROM bulk_delete_request_items WHERE request_id = r.request_id) as total_items
            FROM bulk_delete_requests r
            JOIN users u ON r.requester_id = u.user_id
            JOIN campuses cp ON u.campus_id = cp.campus_id
            WHERE r.status = 'pending'
        ";

        if ($campusId !== null) {
            $sql .= " AND u.campus_id = :campus_id";
        }

        $sql .= " ORDER BY r.requested_at ASC";

        $stmt = $this->db->prepare($sql);
        if ($campusId !== null) {
            $stmt->bindParam(':campus_id', $campusId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get details of a specific request
     */
    public function getRequestDetails(int $requestId): array
    {
        $stmt = $this->db->prepare("
            SELECT r.*, u.first_name, u.last_name, u.role as requester_role, cp.campus_name,
            (SELECT COUNT(*) FROM bulk_delete_request_items WHERE request_id = r.request_id) as total_items
            FROM bulk_delete_requests r
            JOIN users u ON r.requester_id = u.user_id
            JOIN campuses cp ON u.campus_id = cp.campus_id
            WHERE r.request_id = ?
        ");
        $stmt->execute([$requestId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) return [];

        $stmtItems = $this->db->prepare("
            SELECT ri.*, 
                   CONCAT(u_target.first_name, ' ', u_target.last_name) as item_name,
                   u_target.username as identifier,
                   cp.campus_name as target_campus,
                   u_target.deleted_at as target_deleted_at
            FROM bulk_delete_request_items ri
            JOIN bulk_delete_requests r ON ri.request_id = r.request_id
            LEFT JOIN users u_target ON ri.entity_id = u_target.user_id
            LEFT JOIN campuses cp ON u_target.campus_id = cp.campus_id
            WHERE ri.request_id = ?
        ");
        $stmtItems->execute([$requestId]);
        $request['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        return $request;
    }

    /**
     * Update request status
     */
    public function updateStatus(int $requestId, string $status, ?int $approverId = null): bool
    {
        $stmt = $this->db->prepare("
            UPDATE bulk_delete_requests 
            SET status = ?, approver_id = ?, decided_at = NOW() 
            WHERE request_id = ?
        ");
        return $stmt->execute([$status, $approverId, $requestId]);
    }
}
