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
        $stmt = $this->db->query("SELECT * FROM campuses ORDER BY campus_name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a campus by its name.
     *
     * @param string $name The name of the campus.
     * @return array|null Returns the campus data as an associative array or null if not found.
     */
    public function getCampusByName(string $name): ?array
    {
        $sql = "SELECT id, campus_name FROM campuses WHERE campus_name = :campus_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':campus_name' => $name]);
        $campus = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $campus ?: null;
    }
}
