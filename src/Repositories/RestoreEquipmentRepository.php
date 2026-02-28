<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use Exception;

class RestoreEquipmentRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getDeletedEquipments()
    {
        $stmt = $this->db->prepare("SELECT * FROM equipments WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function restoreEquipment($id)
    {
        $stmt = $this->db->prepare("UPDATE equipments SET deleted_at = NULL WHERE equipment_id = ?");
        return $stmt->execute([$id]);
    }

    public function permanentDelete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM equipments WHERE equipment_id = ?");
        return $stmt->execute([$id]);
    }
}
