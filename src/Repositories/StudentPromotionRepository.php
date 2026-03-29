<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use Exception;

class StudentPromotionRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getYearLevelStats($status = 1, $campusId = null)
    {
        $sql = "SELECT year_level, COUNT(*) as count 
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                WHERE u.is_active = :status AND u.deleted_at IS NULL";
        
        $params = [':status' => $status];
        if (!empty($campusId)) {
            $sql .= " AND u.campus_id = :campus_id";
            $params[':campus_id'] = $campusId;
        }

        $sql .= " GROUP BY year_level ORDER BY year_level ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchStudents($filters = [], $limit = 100, $offset = 0)
    {
        $status = isset($filters['status']) ? (int)$filters['status'] : 1;
        
        $sql = "SELECT s.*, u.first_name, u.last_name, u.username, u.email, u.is_active, c.course_code, c.course_title, cp.campus_name
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                LEFT JOIN courses c ON s.course_id = c.course_id
                LEFT JOIN campuses cp ON u.campus_id = cp.campus_id
                WHERE u.is_active = :status AND u.deleted_at IS NULL";
        
        $params = [':status' => $status];
        if (!empty($filters['course_id'])) {
            $sql .= " AND s.course_id = :course_id";
            $params[':course_id'] = $filters['course_id'];
        }
        if (!empty($filters['campus_id'])) {
            $sql .= " AND u.campus_id = :campus_id";
            $params[':campus_id'] = $filters['campus_id'];
        }
        if (!empty($filters['year_level'])) {
            $sql .= " AND s.year_level = :year_level";
            $params[':year_level'] = $filters['year_level'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR s.student_number LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }

        $sql .= " ORDER BY s.year_level ASC, u.last_name ASC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countStudents($filters = [])
    {
        $status = isset($filters['status']) ? (int)$filters['status'] : 1;
        $sql = "SELECT COUNT(*) FROM students s JOIN users u ON s.user_id = u.user_id WHERE u.is_active = :status AND u.deleted_at IS NULL";
        $params = [':status' => $status];
        if (!empty($filters['course_id'])) {
            $sql .= " AND s.course_id = :course_id";
            $params[':course_id'] = $filters['course_id'];
        }
        if (!empty($filters['campus_id'])) {
            $sql .= " AND u.campus_id = :campus_id";
            $params[':campus_id'] = $filters['campus_id'];
        }
        if (!empty($filters['year_level'])) {
            $sql .= " AND s.year_level = :year_level";
            $params[':year_level'] = $filters['year_level'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR s.student_number LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function bulkPromote($studentIds)
    {
        if (empty($studentIds)) return false;
        $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
        $sql = "UPDATE students SET year_level = year_level + 1 WHERE student_id IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($studentIds);
    }

    public function bulkPromoteByFilter($filters)
    {
        $status = isset($filters['status']) ? (int)$filters['status'] : 1;
        $sql = "UPDATE students s
                JOIN users u ON s.user_id = u.user_id
                SET s.year_level = s.year_level + 1
                WHERE u.is_active = :status AND u.deleted_at IS NULL";
        
        $params = [':status' => $status];
        if (!empty($filters['course_id'])) {
            $sql .= " AND s.course_id = :course_id";
            $params[':course_id'] = $filters['course_id'];
        }
        if (!empty($filters['campus_id'])) {
            $sql .= " AND u.campus_id = :campus_id";
            $params[':campus_id'] = $filters['campus_id'];
        }
        if (!empty($filters['year_level'])) {
            $sql .= " AND s.year_level = :year_level";
            $params[':year_level'] = $filters['year_level'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR s.student_number LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function bulkDeactivate($studentIds)
    {
        if (empty($studentIds)) return false;
        $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
        $sql = "UPDATE users u 
                JOIN students s ON u.user_id = s.user_id 
                SET u.is_active = 0 
                WHERE s.student_id IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($studentIds);
    }

    public function bulkDeactivateByFilter($filters)
    {
        $sql = "UPDATE users u 
                JOIN students s ON u.user_id = s.user_id 
                SET u.is_active = 0 
                WHERE u.is_active = 1 AND u.deleted_at IS NULL";
        
        $params = [];
        if (!empty($filters['course_id'])) {
            $sql .= " AND s.course_id = :course_id";
            $params[':course_id'] = $filters['course_id'];
        }
        if (!empty($filters['campus_id'])) {
            $sql .= " AND u.campus_id = :campus_id";
            $params[':campus_id'] = $filters['campus_id'];
        }
        if (!empty($filters['year_level'])) {
            $sql .= " AND s.year_level = :year_level";
            $params[':year_level'] = $filters['year_level'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR s.student_number LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function bulkActivate($studentIds)
    {
        if (empty($studentIds)) return false;
        $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
        $sql = "UPDATE users u 
                JOIN students s ON u.user_id = s.user_id 
                SET u.is_active = 1 
                WHERE s.student_id IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($studentIds);
    }

    public function bulkActivateByFilter($filters)
    {
        $sql = "UPDATE users u 
                JOIN students s ON u.user_id = s.user_id 
                SET u.is_active = 1 
                WHERE u.is_active = 0 AND u.deleted_at IS NULL";
        
        $params = [];
        if (!empty($filters['course_id'])) {
            $sql .= " AND s.course_id = :course_id";
            $params[':course_id'] = $filters['course_id'];
        }
        if (!empty($filters['campus_id'])) {
            $sql .= " AND u.campus_id = :campus_id";
            $params[':campus_id'] = $filters['campus_id'];
        }
        if (!empty($filters['year_level'])) {
            $sql .= " AND s.year_level = :year_level";
            $params[':year_level'] = $filters['year_level'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR s.student_number LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}
