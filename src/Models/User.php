<?php

namespace App\Models;

use App\Core\Database;
use App\Core\RoleHelper;
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

    // static role checks
    public static function isSuperadmin(array $user): bool
    {
        return RoleHelper::isSuperadmin($user['role'] ?? '');
    }

    public static function isAdmin(array $user): bool
    {
        return RoleHelper::isAdmin($user['role'] ?? '');
    }

    public static function isGlobalAdmin(array $user): bool
    {
        return RoleHelper::isGlobalAdmin($user['role'] ?? '', $user['campus_id'] ?? null);
    }

    public static function isLocalAdmin(array $user): bool
    {
        return RoleHelper::isLocalAdmin($user['role'] ?? '', $user['campus_id'] ?? null);
    }

    public static function isLibrarian(array $user): bool
    {
        return RoleHelper::isLibrarian($user['role'] ?? '');
    }

    public static function isStudent(array $user): bool
    {
        return RoleHelper::compareNormalize($user['role'] ?? '') === RoleHelper::compareNormalize(RoleHelper::STUDENT);
    }

    public static function isScanner(array $user): bool
    {
        return RoleHelper::compareNormalize($user['role'] ?? '') === RoleHelper::compareNormalize(RoleHelper::SCANNER);
    }

    public static function isFaculty(array $user): bool
    {
        return RoleHelper::compareNormalize($user['role'] ?? '') === RoleHelper::compareNormalize(RoleHelper::FACULTY);
    }

    public static function isStaff(array $user): bool
    {
        return RoleHelper::compareNormalize($user['role'] ?? '') === RoleHelper::compareNormalize(RoleHelper::STAFF);
    }
}
