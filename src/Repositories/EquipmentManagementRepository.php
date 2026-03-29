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
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM equipments WHERE asset_tag = ? AND deleted_at IS NULL");
        $stmt->execute([$tag]);
        return (int)$stmt->fetchColumn() === 0;
    }

    public function fetchEquipments($search = '', $status = 'All Status', $sort = 'default', $limit = 30, $offset = 0, $campusId = null)
    {
        $query = "SELECT e.*, c.campus_name 
                  FROM equipments e 
                  INNER JOIN campuses c ON e.campus_id = c.campus_id 
                  WHERE e.deleted_at IS NULL AND c.is_active = 1"; 
        $params = [];

        if (!empty($search)) {
            $query .= " AND (e.equipment_name LIKE :search OR e.asset_tag LIKE :search)";
            $params[':search'] = "%$search%";
        }

        if ($status !== 'All Status' && !empty($status)) {
            if ($status === 'Active') {
                $query .= " AND e.is_active = 1";
            } elseif ($status === 'Inactive') {
                $query .= " AND e.is_active = 0";
            } else {
                $query .= " AND e.status = :status";
                $params[':status'] = strtolower($status);
            }
        }

        if (!empty($campusId)) {
            $query .= " AND e.campus_id = :campus_id";
            $params[':campus_id'] = $campusId;
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
        $query = "SELECT COUNT(*) FROM equipments e INNER JOIN campuses c ON e.campus_id = c.campus_id WHERE e.deleted_at IS NULL AND c.is_active = 1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (e.equipment_name LIKE :search OR e.asset_tag LIKE :search)";
            $params[':search'] = "%$search%";
        }

        if ($status !== 'All Status' && !empty($status)) {
            if ($status === 'Active') {
                $query .= " AND e.is_active = 1";
            } elseif ($status === 'Inactive') {
                $query .= " AND e.is_active = 0";
            } else {
                $query .= " AND e.status = :status";
                $params[':status'] = strtolower($status);
            }
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

    public function addEquipment($data)
    {
        $stmt = $this->db->prepare("INSERT INTO equipments (equipment_name, campus_id, asset_tag, status, is_active, created_at) VALUES (:name, :campus_id, :asset_tag, :status, :is_active, NOW())");
        $stmt->execute([
            ':name'      => $data['equipment_name'],
            ':campus_id' => $data['campus_id'],
            ':asset_tag' => $data['asset_tag'],
            ':status'    => $data['status'],
            ':is_active' => $data['is_active']
        ]);
        return $this->db->lastInsertId();
    }

    public function updateEquipment($id, $data)
    {
        $stmt = $this->db->prepare("UPDATE equipments SET equipment_name = :name, campus_id = :campus_id, status = :status, updated_at = NOW() WHERE equipment_id = :id");
        return $stmt->execute([
            ':id'        => $id,
            ':name'      => $data['equipment_name'],
            ':campus_id' => $data['campus_id'],
            ':status'    => $data['status']
        ]);
    }

    public function softDeleteEquipment($id)
    {
        $stmt = $this->db->prepare("UPDATE equipments SET deleted_at = NOW(), is_active = 0 WHERE equipment_id = ?");
        return $stmt->execute([$id]);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT e.*, c.campus_name FROM equipments e INNER JOIN campuses c ON e.campus_id = c.campus_id WHERE e.equipment_id = ? AND e.deleted_at IS NULL AND c.is_active = 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function toggleActiveStatus($id, $newStatus)
    {
        $stmt = $this->db->prepare("UPDATE equipments SET is_active = ?, updated_at = NOW() WHERE equipment_id = ?");
        return $stmt->execute([$newStatus, $id]);
    }
}