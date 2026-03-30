<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class CampusRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAllCampuses()
    {
        // Default behavior: Kunin lahat regardless of status, ordered by name
        $stmt = $this->db->query("SELECT * FROM campuses ORDER BY campus_name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM campuses WHERE campus_id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getCampusByName(string $name): ?array
    {
        $sql = "SELECT * FROM campuses WHERE campus_name = :campus_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':campus_name' => $name]);
        $campus = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $campus ?: null;
    }

    public function getCampusByCode(string $code): ?array
    {
        $sql = "SELECT * FROM campuses WHERE campus_code = :campus_code";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':campus_code' => $code]);
        $campus = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $campus ?: null;
    }

    public function create(string $name, string $code): bool
    {
        $stmt = $this->db->prepare("INSERT INTO campuses (campus_name, campus_code, is_active) VALUES (:name, :code, 1)");
        return $stmt->execute([
            ':name' => $name,
            ':code' => strtoupper(trim($code))
        ]);
    }

    public function update(int $id, string $name, string $code): bool
    {
        $stmt = $this->db->prepare("UPDATE campuses SET campus_name = :name, campus_code = :code WHERE campus_id = :id");
        return $stmt->execute([
            ':name' => $name,
            ':code' => strtoupper(trim($code)),
            ':id' => $id
        ]);
    }

    public function toggleStatus(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE campuses SET is_active = NOT is_active WHERE campus_id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // Delete is kept for record but should not be called from the UI anymore
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM campuses WHERE campus_id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function hasDependencies(int $id): bool
    {
        // Check users
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE campus_id = :id AND deleted_at IS NULL");
        $stmt->execute([':id' => $id]);
        if ((int)$stmt->fetchColumn() > 0) return true;

        // Check books
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM books WHERE campus_id = :id AND is_active = 1");
        $stmt->execute([':id' => $id]);
        if ((int)$stmt->fetchColumn() > 0) return true;

        return false;
    }
}
