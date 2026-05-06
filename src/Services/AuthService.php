<?php

namespace App\Services;

use App\Repositories\AuthRepository;
use App\Repositories\UserRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\LoginAttemptRepository;
use App\Models\User;
use Exception;

class AuthService
{
    private AuthRepository $authRepo;
    private UserRepository $userRepo;
    private AuditLogRepository $auditRepo;
    private LoginAttemptRepository $attemptRepo;

    public function __construct()
    {
        $this->authRepo = new AuthRepository();
        $this->userRepo = new UserRepository();
        $this->auditRepo = new AuditLogRepository();
        $this->attemptRepo = new LoginAttemptRepository();
    }

    public function login(string $username, string $password): array
    {
        $this->checkLockout($username);

        $loginResult = $this->authRepo->attemptLogin($username, $password);

        if (!$loginResult) {
            $this->attemptRepo->recordAttempt($username);
            $ip = $this->attemptRepo->getIpAddress();
            $this->auditRepo->log(null, 'LOGIN_FAILED', 'AUTH', null, "Failed login attempt for username: $username from IP: $ip");
            $this->checkLockout($username);
            throw new Exception("Invalid username or password.");
        }

        $user = $loginResult['raw_user'];

        if (isset($user['is_active']) && !$user['is_active']) {
            $this->auditRepo->log($user['user_id'], 'LOGIN_DEACTIVATED', 'AUTH', null, "Attempted login to deactivated account.");
            throw new Exception('Your account has been deactivated by the administrator.', 403);
        }

        $this->attemptRepo->clearAttempts($username);

        $sessionPayload = $loginResult['session_payload'];
        $userRole = $sessionPayload['role'] ?? '';
        $permissions = $sessionPayload['user_permissions'] ?? [];

        $redirectPath = '';
        if (User::isAdmin($user) || User::isLibrarian($user)) {
            $redirectPath = User::getFirstAccessibleModuleUrl($userRole, $permissions);
        } elseif (User::isScanner($user)) {
            $redirectPath = \BASE_URL . '/attendance';
        } elseif (User::isSuperadmin($user) || User::isAdmin($user) || User::isStudent($user) || User::isFaculty($user) || User::isStaff($user)) {
            $redirectPath = \BASE_URL . '/dashboard';
        }

        if (empty($redirectPath)) {
            throw new Exception('Role not recognized or no accessible module.');
        }

        return [
            'user_id' => $user['user_id'],
            'session_payload' => $sessionPayload,
            'redirect' => $redirectPath
        ];
    }

    private function checkLockout(string $username): void
    {
        $ip = $this->attemptRepo->getIpAddress();
        $ipAttempts = $this->attemptRepo->countAttemptsByIp($ip);

        if ($ipAttempts >= 50) {
            $lastIpAttempt = $this->attemptRepo->getLastAttemptByIp($ip);
            if ($lastIpAttempt) {
                $dbTime = $this->attemptRepo->getCurrentDatabaseTime();
                $passed = strtotime($dbTime) - strtotime($lastIpAttempt);
                $waitTime = 3600;
                
                if ($passed < $waitTime) {
                    $remaining = $waitTime - $passed;
                    $this->auditRepo->log(null, 'IP_LOCKOUT', 'AUTH', null, "IP address $ip has been locked out for 1 hour due to 50+ failures.");
                    throw new Exception(json_encode([
                        'message' => "Too many failed attempts from this connection. Please try again after " . $this->formatTime($remaining) . ".",
                        'remaining' => $remaining
                    ]), 429);
                }
            }
        }

        $failedAttempts = $this->attemptRepo->countAttempts($username);

        if ($failedAttempts >= 4) {
            $lastAttempt = $this->attemptRepo->getLastAttempt($username);
            $waitTime = $this->getWaitTime($failedAttempts);
            
            if ($lastAttempt) {
                $dbTime = $this->attemptRepo->getCurrentDatabaseTime();
                $secondsPassed = strtotime($dbTime) - strtotime($lastAttempt);
                
                if ($secondsPassed < $waitTime) {
                    $remaining = $waitTime - $secondsPassed;
                    $msg = ($failedAttempts === 4) 
                        ? "Too many failed login attempts. Please wait a moment before trying again."
                        : "Too many failed login attempts. Please try again after " . $this->formatTime($remaining) . ".";
                    
                    $this->auditRepo->log(null, 'ACCOUNT_LOCKOUT', 'AUTH', null, "Account $username is locked out. Level: $failedAttempts attempts.");
                    throw new Exception(json_encode(['message' => $msg, 'remaining' => $remaining]), 429);
                }
            }
        }
    }

    private function getWaitTime(int $failCount): int
    {
        return match (true) {
            $failCount === 4 => 30,
            $failCount === 5 => 300,
            $failCount === 6 => 900,
            $failCount === 7 => 1800,
            $failCount === 8 => 3600,
            $failCount === 9 => 10800,
            $failCount >= 10 => 86400,
            default => 0,
        };
    }

    private function formatTime(int $seconds): string
    {
        if ($seconds < 60) {
            return "$seconds seconds";
        }
        
        if ($seconds < 3600) {
            $mins = ceil($seconds / 60);
            return "$mins " . ($mins === 1 ? "minute" : "minutes");
        }
        
        $hours = ceil($seconds / 3600);
        return "$hours " . ($hours === 1 ? "hour" : "hours");
    }

    public function logout(int $userId): void
    {
        $this->auditRepo->log($userId, 'LOGOUT', 'AUTH', null, 'User logged out.');
        $this->authRepo->logout();
    }

    /**
     * Change user password
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword, string $confirmPassword): bool
    {
        if ($newPassword !== $confirmPassword) {
            throw new Exception('New passwords do not match.');
        }

        $user = $this->userRepo->getUserById($userId);
        if (!$user) {
            throw new Exception('User not found.');
        }

        $userData = $this->userRepo->findByIdentifier($user['username']);
        if (!$userData || !password_verify($currentPassword, $userData['password'])) {
            throw new Exception('Current password is incorrect.');
        }

        $changed = $this->authRepo->changePassword($userId, $newPassword);
        if ($changed) {
            $this->auditRepo->log($userId, 'CHANGE_PASSWORD', 'AUTH', null, 'User successfully changed their own password.');
            return true;
        }

        throw new Exception('Something went wrong while changing your password.');
    }
}
