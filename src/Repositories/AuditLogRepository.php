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

    public function log($userId, $action, $resource = null, $resourceId = null, $details = null)
    {
        try {
            $sql = "INSERT INTO audit_logs (user_id, action, resource, resource_id, details, created_at) 
                    VALUES (:user_id, :action, :resource, :resource_id, :details, NOW())";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':user_id' => $userId,
                ':action' => $action,
                ':resource' => $resource,
                ':resource_id' => $resourceId,
                ':details' => $details
            ]);
        } catch (Exception $e) {
            error_log("AuditLogRepository error: " . $e->getMessage());
            return false;
        }
    }

    public function fetchLogs($search = '', $limit = 50, $offset = 0)
    {
        try {
            $sql = "SELECT al.*, u.username, u.role, 
                           CONCAT(u.first_name, ' ', u.last_name) as full_name 
                    FROM audit_logs al
                    LEFT JOIN users u ON al.user_id = u.user_id
                    WHERE 1=1";
            
            $params = [];
            if (!empty($search)) {
                $sql .= " AND (u.username LIKE :search OR al.action LIKE :search OR al.resource LIKE :search OR al.details LIKE :search)";
                $params[':search'] = "%$search%";
            }

            $sql .= " ORDER BY al.created_at DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("AuditLogRepository fetch error: " . $e->getMessage());
            return [];
        }
    }

    public function countLogs($search = '')
    {
        $sql = "SELECT COUNT(*) FROM audit_logs al LEFT JOIN users u ON al.user_id = u.user_id WHERE 1=1";
        $params = [];
        if (!empty($search)) {
            $sql .= " AND (u.username LIKE :search OR al.action LIKE :search OR al.resource LIKE :search OR al.details LIKE :search)";
            $params[':search'] = "%$search%";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
}
