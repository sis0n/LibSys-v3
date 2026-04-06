<?php

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

class User
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByIdentifier(string $identifier): ?array
    {
        try {
            $sql = "
                SELECT u.*, s.student_number 
                FROM users u
                LEFT JOIN students s ON u.user_id = s.user_id
                WHERE (u.username = :identifier OR s.student_number = :identifier)
                  AND u.is_active = 1
                LIMIT 1
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':identifier', $identifier, PDO::PARAM_STR);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public static function getFirstAccessibleModuleUrl(string $role, array $permissions): string
    {
        $normalizedPermissions = array_map('strtolower', $permissions);

        $orderedModules = [
            'user management' => 'userManagement',
            'book management' => 'bookManagement',
            'qr scanner' => 'qrScanner',
            'returning' => 'returning',
            'borrowing form' => 'borrowingForm',
            'attendance logs' => 'attendanceLogs',
            'reports' => 'topVisitor',
            'transaction history' => 'transactionHistory',
            'backup' => 'backup',
            'restore books' => 'restoreBooks',
            'restore users' => 'restoreUser',

        ];

        foreach ($orderedModules as $permission => $urlSegment) {
            if (in_array($permission, $normalizedPermissions)) {
                return $urlSegment;
            }
        }

        return "changePassword";
    }
    // static role checks – secure role methods
    public static function isAdmin(array $user): bool
    {
        $role = strtolower(str_replace(' ', '_', $user['role'] ?? ''));
        return $role === 'admin';
    }
    public static function isLibrarian(array $user): bool
    {
        $role = strtolower(str_replace(' ', '_', $user['role'] ?? ''));
        return $role === 'librarian';
    }
    public static function isStudent(array $user): bool
    {
        $role = strtolower(str_replace(' ', '_', $user['role'] ?? ''));
        return $role === 'student';
    }
    public static function isSuperadmin(array $user): bool
    {
        $role = strtolower(str_replace(' ', '_', $user['role'] ?? ''));
        return $role === 'superadmin';
    }
    public static function isCampusAdmin(array $user): bool
    {
        $role = strtolower(str_replace(' ', '_', $user['role'] ?? ''));
        return $role === 'campus_admin' || $role === 'campus_admin'; // handles both
    }
    public static function isScanner(array $user): bool
    {
        $role = strtolower(str_replace(' ', '_', $user['role'] ?? ''));
        return $role === 'scanner';
    }
    public static function isFaculty(array $user): bool
    {
        $role = strtolower(str_replace(' ', '_', $user['role'] ?? ''));
        return $role === 'faculty';
    }
    public static function isStaff(array $user): bool
    {
        $role = strtolower(str_replace(' ', '_', $user['role'] ?? ''));
        return $role === 'staff';
    }
}
