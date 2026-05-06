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

    /**
     * Attempt to login a user with rate limiting
     */
    public function login(string $username, string $password): array
    {
        // Rate limiting configuration
        $maxAttempts = 5;
        $lockoutMinutes = 15;

        // 1. Check if user is currently locked out
        $failedAttempts = $this->attemptRepo->countAttempts($username, $lockoutMinutes);
        if ($failedAttempts >= $maxAttempts) {
            throw new Exception("Too many failed login attempts. Please try again after $lockoutMinutes minutes.");
        }

        $loginResult = $this->authRepo->attemptLogin($username, $password);

        if (!$loginResult) {
            // 2. Record failed attempt
            $this->attemptRepo->recordAttempt($username);
            throw new Exception("Invalid username or password.");
        }

        $user = $loginResult['raw_user'];

        if (isset($user['is_active']) && !$user['is_active']) {
            throw new Exception('Your account has been deactivated by the administrator.', 403);
        }

        // 3. Login successful - Clear failed attempts
        $this->attemptRepo->clearAttempts($username);

        // Prepare session payload and determine redirect path
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

    /**
     * Logout a user
     */
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
