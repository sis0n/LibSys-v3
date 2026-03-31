<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use Exception;

class EquipmentManagementRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function isAssetTagUnique(string $tag): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM equipments WHERE asset_tag = ?");
        $stmt->execute([$tag]);
        return (int)$stmt->fetchColumn() === 0;
    }

    public function fetchEquipments($search = '', $status = 'All Status', $sort = 'default', $limit = 30, $offset = 0, $campusId = null)
    {
        $query = "SELECT e.*, c.campus_name 
                  FROM equipments e 
                  INNER JOIN campuses c ON e.campus_id = c.campus_id 
                  WHERE c.is_active = 1"; 
        $params = [];

        if ($status === 'All Status' || empty($status)) {
            $query .= " AND e.is_active = 1";
        } elseif ($status === 'Active') {
            $query .= " AND e.is_active = 1";
        } elseif ($status === 'Inactive') {
            $query .= " AND e.is_active = 0";
        } else {
            $query .= " AND e.status = :status AND e.is_active = 1";
            $params[':status'] = strtolower($status);
        }

        if (!empty($campusId)) {
            $query .= " AND e.campus_id = :campus_id";
            $params[':campus_id'] = $campusId;
        }

        if (!empty($search)) {
            $query .= " AND (e.equipment_name LIKE :search OR e.asset_tag LIKE :search)";
            $params[':search'] = "%$search%";
        }

        switch ($sort) {
            case 'name_asc':  $query .= " ORDER BY e.equipment_name ASC"; break;
            case 'name_desc': $query .= " ORDER BY e.equipment_name DESC"; break;
            case 'newest':    $query .= " ORDER BY e.created_at DESC"; break;
            case 'oldest':    $query .= " ORDER BY e.created_at ASC"; break;
            default:          $query .= " ORDER BY e.equipment_name ASC"; break;
        }

        $query .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countEquipments($search = '', $status = 'All Status', $campusId = null)
    {
        $query = "SELECT COUNT(*) FROM equipments e INNER JOIN campuses c ON e.campus_id = c.campus_id WHERE c.is_active = 1";
        $params = [];

        if ($status === 'All Status' || empty($status)) {
            $query .= " AND e.is_active = 1";
        } elseif ($status === 'Active') {
            $query .= " AND e.is_active = 1";
        } elseif ($status === 'Inactive') {
            $query .= " AND e.is_active = 0";
        } else {
            $query .= " AND e.status = :status AND e.is_active = 1";
            $params[':status'] = strtolower($status);
        }

        if (!empty($search)) {
            $query .= " AND (e.equipment_name LIKE :search OR e.asset_tag LIKE :search)";
            $params[':search'] = "%$search%";
        }

        if (!empty($campusId)) {
            $query .= " AND e.campus_id = :campus_id";
            $params[':campus_id'] = $campusId;
        }

        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function deactivateEquipment($id)
    {
        $stmt = $this->db->prepare("UPDATE equipments SET is_active = 0, updated_at = NOW() WHERE equipment_id = ?");
        return $stmt->execute([$id]);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT e.*, c.campus_name FROM equipments e INNER JOIN campuses c ON e.campus_id = c.campus_id WHERE e.equipment_id = ? AND c.is_active = 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function toggleActiveStatus($id, $newStatus)
    {
        $stmt = $this->db->prepare("UPDATE equipments SET is_active = ?, updated_at = NOW() WHERE equipment_id = ?");
        return $stmt->execute([$newStatus, $id]);
    }
}
