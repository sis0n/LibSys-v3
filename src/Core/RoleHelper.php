<?php

namespace App\Core;

class RoleHelper
{
    /**
     * Standardized Roles
     */
    const SUPERADMIN = 'superadmin';
    const ADMIN = 'admin';
    const CAMPUS_ADMIN = 'campus_admin';
    const LIBRARIAN = 'librarian';
    const SCANNER = 'scanner';
    const STUDENT = 'student';
    const FACULTY = 'faculty';
    const STAFF = 'staff';

    /**
     * Standardized Modules (Permissions)
     * These should match what is stored in the database `user_permission_modules` table
     */
    const MOD_USER_MANAGEMENT      = 'user management';
    const MOD_CAMPUS_MANAGEMENT    = 'campus management';
    const MOD_STUDENT_PROMOTION    = 'student promotion';
    const MOD_BOOK_MANAGEMENT      = 'book management';
    const MOD_EQUIPMENT_MANAGEMENT = 'equipment management';
    const MOD_QR_SCANNER           = 'qr scanner';
    const MOD_RETURNING            = 'returning';
    const MOD_BORROWING_FORM       = 'borrowing form';
    const MOD_ATTENDANCE_LOGS      = 'attendance logs';
    const MOD_OVERDUE_TRACKING     = 'overdue tracking';
    const MOD_REPORTS              = 'reports';
    const MOD_TRANSACTION_HISTORY  = 'transaction history';
    const MOD_AUDIT_TRAIL          = 'audit trail';
    const MOD_BACKUP               = 'backup';
    const MOD_RESTORE_USER         = 'restore users';
    const MOD_BULK_DELETE_QUEUE    = 'bulk delete queue';
    const MOD_LIBRARY_POLICIES     = 'library policies';

    /**
     * Normalize a string for strict comparison (lowercase, no spaces/dashes/underscores)
     */
    public static function compareNormalize(?string $str): string
    {
        if ($str === null) return '';
        return strtolower(trim(str_replace([' ', '-', '_'], '', $str)));
    }

    /**
     * Check if a user role matches any of the allowed roles/permissions
     */
    public static function hasAccess(string $userRole, array $userPermissions, array $allowedAccess): bool
    {
        $normalizedUserRole = self::compareNormalize($userRole);
        
        // Superadmin has absolute access to everything
        if ($normalizedUserRole === self::compareNormalize(self::SUPERADMIN)) {
            return true;
        }

        $normalizedAllowed = array_map([self::class, 'compareNormalize'], $allowedAccess);

        // 1. Check if direct role matches (e.g., 'student' allowed for a route)
        if (in_array($normalizedUserRole, $normalizedAllowed)) {
            return true;
        }

        // 2. Check if any of the user's specific module permissions match
        $normalizedUserPermissions = array_map([self::class, 'compareNormalize'], $userPermissions);
        foreach ($normalizedUserPermissions as $perm) {
            if (in_array($perm, $normalizedAllowed)) {
                return true;
            }
        }

        return false;
    }

    public static function isSuperadmin(string $role): bool {
        return self::compareNormalize($role) === self::compareNormalize(self::SUPERADMIN);
    }

    public static function isAdmin(string $role): bool {
        return self::compareNormalize($role) === self::compareNormalize(self::ADMIN);
    }

    public static function isCampusAdmin(string $role): bool {
        return self::compareNormalize($role) === self::compareNormalize(self::CAMPUS_ADMIN);
    }

    public static function isLibrarian(string $role): bool {
        return self::compareNormalize($role) === self::compareNormalize(self::LIBRARIAN);
    }

    /**
     * High-level check for management staff (Superadmin, Admin, Campus Admin, Librarian)
     */
    public static function isStaff(string $role): bool {
        $normalized = self::compareNormalize($role);
        return in_array($normalized, [
            self::compareNormalize(self::SUPERADMIN),
            self::compareNormalize(self::ADMIN),
            self::compareNormalize(self::CAMPUS_ADMIN),
            self::compareNormalize(self::LIBRARIAN)
        ]);
    }

    /**
     * Check if the user has global access (Superadmin or Admin)
     */
    public static function hasGlobalAccess(string $role): bool {
        $normalized = self::compareNormalize($role);
        return in_array($normalized, [
            self::compareNormalize(self::SUPERADMIN),
            self::compareNormalize(self::ADMIN)
        ]);
    }
}
