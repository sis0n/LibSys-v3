<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use Exception;

class AuditLogRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function log($userId, $action, $resource, $resourceId, $details)
    {
        try {
            $sql = "INSERT INTO audit_logs (user_id, action, resource, resource_id, details, created_at) 
                    VALUES (:user_id, :action, :resource, :resource_id, :details, NOW())";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':user_id'     => $userId,
                ':action'      => $action,
                ':resource'    => $resource,
                ':resource_id' => $resourceId,
                ':details'     => $details
            ]);
        } catch (Exception $e) {
            error_log("AuditLog error: " . $e->getMessage());
            return false;
        }
    }

    public function fetchLogs($search = '', $limit = 50, $offset = 0, $action = '', $resource = '', ?int $campusId = null)
    {
        try {
            $sql = "SELECT al.*, u.first_name, u.last_name, u.role, u.username
                    FROM audit_logs al
                    LEFT JOIN users u ON al.user_id = u.user_id
                    LEFT JOIN campuses c ON u.campus_id = c.campus_id
                    WHERE (c.is_active = 1 OR u.campus_id IS NULL)";
            $params = [];

            if ($campusId !== null) {
                $sql .= " AND u.campus_id = :campus_id";
                $params[':campus_id'] = $campusId;
            }

            if (!empty($search)) {
                $sql .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR al.action LIKE :search OR al.resource LIKE :search OR al.details LIKE :search)";
                $params[':search'] = "%$search%";
            }

            if (!empty($action)) {
                $sql .= " AND al.action = :action";
                $params[':action'] = $action;
            }

            if (!empty($resource)) {
                $sql .= " AND al.resource = :resource";
                $params[':resource'] = $resource;
            }

            $sql .= " ORDER BY al.created_at DESC, al.log_id DESC LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("fetchLogs error: " . $e->getMessage());
            return [];
        }
    }

    public function countLogs($search = '', $action = '', $resource = '', ?int $campusId = null)
    {
        try {
            $sql = "SELECT COUNT(*) 
                    FROM audit_logs al 
                    LEFT JOIN users u ON al.user_id = u.user_id 
                    LEFT JOIN campuses c ON u.campus_id = c.campus_id
                    WHERE (c.is_active = 1 OR u.campus_id IS NULL)";
            $params = [];

            if ($campusId !== null) {
                $sql .= " AND u.campus_id = :campus_id";
                $params[':campus_id'] = $campusId;
            }

            if (!empty($search)) {
                $sql .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR al.action LIKE :search OR al.resource LIKE :search OR al.details LIKE :search)";
                $params[':search'] = "%$search%";
            }

            if (!empty($action)) {
                $sql .= " AND al.action = :action";
                $params[':action'] = $action;
            }

            if (!empty($resource)) {
                $sql .= " AND al.resource = :resource";
                $params[':resource'] = $resource;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("countLogs error: " . $e->getMessage());
            return 0;
        }
    }
}
