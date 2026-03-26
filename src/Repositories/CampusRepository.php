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
}
