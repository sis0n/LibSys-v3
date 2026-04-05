<?php

namespace App\Services;

use App\Repositories\AuthRepository;
use App\Repositories\UserRepository;
use App\Repositories\AuditLogRepository;
use App\Models\User;
use Exception;

class AuthService
{
    private AuthRepository $authRepo;
    private UserRepository $userRepo;
    private AuditLogRepository $auditRepo;

    public function __construct()
    {
        $this->authRepo = new AuthRepository();
        $this->userRepo = new UserRepository();
        $this->auditRepo = new AuditLogRepository();
    }

    /**
     * Attempt to login a user
     */
    public function login(string $username, string $password): array
    {
        $loginResult = $this->authRepo->attemptLogin($username, $password);

        if (!$loginResult) {
            throw new Exception('Invalid username or password.');
        }

        $user = $loginResult['raw_user'];

        if (isset($user['is_active']) && !$user['is_active']) {
            throw new Exception('Your account has been deactivated by the administrator.', 403);
        }

        // Prepare session payload and determine redirect path
        $sessionPayload = $loginResult['session_payload'];
        $userRole = $sessionPayload['role'] ?? '';
        $permissions = $sessionPayload['user_permissions'] ?? [];

        $redirectPath = '';
        if (User::isAdmin($user) || User::isLibrarian($user)) {
            $redirectPath = User::getFirstAccessibleModuleUrl($userRole, $permissions);
        } elseif (User::isScanner($user)) {
            $redirectPath = \BASE_URL . '/attendance';
        } elseif (User::isSuperadmin($user) || User::isCampusAdmin($user) || User::isStudent($user) || User::isFaculty($user) || User::isStaff($user)) {
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
