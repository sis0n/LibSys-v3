<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class LibraryPolicyRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getPoliciesByCampus(int $campusId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM library_policies WHERE campus_id = :campus_id ORDER BY role ASC");
        $stmt->execute(['campus_id' => $campusId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPolicyByRole(string $role, int $campusId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM library_policies WHERE role = :role AND campus_id = :campus_id LIMIT 1");
        $stmt->execute(['role' => $role, 'campus_id' => $campusId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function updatePolicy(string $role, int $maxBooks, int $durationDays, int $campusId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE library_policies 
            SET max_books = :max_books, 
                borrow_duration_days = :duration 
            WHERE role = :role AND campus_id = :campus_id
        ");
        return $stmt->execute([
            'role' => $role,
            'max_books' => $maxBooks,
            'duration' => $durationDays,
            'campus_id' => $campusId
        ]);
    }

    public function syncActiveTransactionsDueDate(string $role, int $durationDays): bool
    {
        $idColumn = '';
        $roleValue = strtolower($role);
        
        if ($roleValue === 'student') $idColumn = 'student_id';
        elseif ($roleValue === 'faculty') $idColumn = 'faculty_id';
        elseif ($roleValue === 'staff') $idColumn = 'staff_id';
        else return false;

        $sql = "UPDATE borrow_transactions 
                SET due_date = DATE_ADD(borrowed_at, INTERVAL :duration DAY) 
                WHERE status IN ('borrowed', 'overdue') 
                AND $idColumn IS NOT NULL";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['duration' => $durationDays]);
    }
}
