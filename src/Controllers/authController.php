<?php

namespace App\Controllers;

use App\Repositories\AuthRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserPermissionModuleRepository;
use App\Core\Controller;
use App\Models\User;

class AuthController extends Controller
{
    private $AuthRepository;
    private $UserRepository;
    private $UserPermissionRepo;
    private $auditRepo;

    public function __construct()
    {
        $this->AuthRepository = new \App\Repositories\AuthRepository();
        $this->UserRepository = new \App\Repositories\UserRepository();
        $this->UserPermissionRepo = new \App\Repositories\UserPermissionModuleRepository();
        $this->auditRepo = new \App\Repositories\AuditLogRepository();
    }

    public function showLogin()
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');

        if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
            header("Location: " . BASE_URL . "/dashboard");
            exit;
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $this->view("auth/login", [
            "title" => "Login Page",
            "csrf_token" => $_SESSION['csrf_token']
        ], false);
    }

    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid request method.'
            ]);
            return;
        }

        header('Content-Type: application/json');

        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid CSRF token.'
            ]);
            return;
        }

        $username = htmlspecialchars(trim($_POST['username'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Username and password are required.'
            ]);
            return;
        }

        $loginResult = $this->AuthRepository->attemptLogin($username, $password);

        if (!$loginResult) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid username or password.'
            ]);
            return;
        }

        $user = $loginResult['raw_user'];

        if (isset($user['is_active']) && !$user['is_active']) {
            echo json_encode([
                'status' => 'error',
                'error_type' => 'deactivated', // Idagdag ito para makilala
                'message' => 'Your account has been deactivated by the administrator.' // Palitan sa mas pormal na mensahe
            ]);
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);

        foreach ($loginResult['session_payload'] as $key => $value) {
            $_SESSION[$key] = $value;
        }

        $this->auditRepo->log($_SESSION['user_id'], 'LOGIN', 'AUTH', null, 'User logged in successfully.');

        $redirect = '';
        $userRole = $_SESSION['role'] ?? '';
        $finalPath = '';

        if (User::isAdmin($user) || User::isLibrarian($user)) {
            $permissions = $_SESSION['user_permissions'] ?? [];
            $finalPath = User::getFirstAccessibleModuleUrl($userRole, $permissions);
        } elseif (User::isScanner($user)) {
            $finalPath = BASE_URL . '/attendance';
        } elseif (User::isSuperadmin($user) || User::isStudent($user) || User::isFaculty($user) || User::isStaff($user)) {
            $finalPath = BASE_URL . '/dashboard';
        }

        if (empty($finalPath)) {
            echo json_encode(['status' => 'error', 'message' => 'Role not recognized or no accessible module.']);
            return;
        }

        echo json_encode([
            'status' => 'success',
            'redirect' => $finalPath
        ]);
    }

    public function logout()
    {
        session_start();
        if (isset($_SESSION['user_id'])) {
            $this->auditRepo->log($_SESSION['user_id'], 'LOGOUT', 'AUTH', null, 'User logged out.');
        }
        $this->AuthRepository->logout();
        header("Location: " . BASE_URL . "/login");
    }

    public function forgotPassword()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $this->view("auth/forgotPassword", [
            "title" => "Forgot Password",
            "csrf_token" => $_SESSION['csrf_token']
        ], false);
    }

    public function changePassword()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid request method.'
            ]);
            exit;
        }

        if (empty($_SESSION['user_id'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'You must be logged in to change your password.'
            ]);
            exit;
        }

        $userId = $_SESSION['user_id'];
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'All password fields are required.'
            ]);
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            echo json_encode([
                'status' => 'error',
                'message' => 'New passwords do not match.'
            ]);
            exit;
        }

        $user = $this->UserRepository->getUserById($userId);

        if (!$user) {
            echo json_encode([
                'status' => 'error',
                'message' => 'User not found.'
            ]);
            exit;
        }

        $userRepo = new UserRepository();
        $userData = $userRepo->findByIdentifier($user['username']);

        if (!$userData || !password_verify($currentPassword, $userData['password'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Current password is incorrect.'
            ]);
            exit;
        }

        $changed = $this->AuthRepository->changePassword($userId, $newPassword);

        if ($changed) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Your password has been successfully updated.'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Something went wrong while changing your password.'
            ]);
        }
    }

    public function resetPassword()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $this->view("auth/resetPassword", [
            "title" => "Reset Password",
            "csrf_token" => $_SESSION['csrf_token']
        ], false);
    }

    public function verifyOTP()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $this->view("auth/verifyOTP", [
            "title" => "Verify OTP",
            "csrf_token" => $_SESSION['csrf_token']
        ], false);
    }
}
