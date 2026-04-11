<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Core\Controller;
use App\Models\User;

class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct()
    {
        parent::__construct();
        $this->authService = new AuthService();
    }

    public function showLogin()
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');

        if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
            header("Location: " . \BASE_URL . "/dashboard");
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
            $this->errorResponse('Invalid request method.', 400, ['status' => 'error']);
        }

        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $this->errorResponse('Invalid CSRF token.', 403, ['status' => 'error']);
        }

        $username = htmlspecialchars(trim($_POST['username'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $this->errorResponse('Username and password are required.', 400, ['status' => 'error']);
        }

        try {
            $result = $this->authService->login($username, $password);

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            session_regenerate_id(true);

            foreach ($result['session_payload'] as $key => $value) {
                $_SESSION[$key] = $value;
            }

            // Log the success via AuditLog directly or via Service if preferred
            (new \App\Services\AuditLogService())->log($_SESSION['user_id'], 'LOGIN', 'AUTH', null, 'User logged in successfully.');

            $this->jsonResponse([
                'status' => 'success',
                'redirect' => $result['redirect']
            ]);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() === 403 ? 403 : 200; // Keep 200 for normal error messages in AJAX
            $this->errorResponse($e->getMessage(), $statusCode, [
                'status' => 'error',
                'error_type' => ($e->getCode() === 403) ? 'deactivated' : 'auth_failed'
            ]);
        }
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (isset($_SESSION['user_id'])) {
            $this->authService->logout($_SESSION['user_id']);
        }
        
        header("Location: " . \BASE_URL . "/login");
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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->errorResponse('Invalid request method.', 400, ['status' => 'error']);
        }

        if (empty($_SESSION['user_id'])) {
            $this->errorResponse('You must be logged in to change your password.', 401, ['status' => 'error']);
        }

        try {
            $this->authService->changePassword(
                $_SESSION['user_id'],
                $_POST['current_password'] ?? '',
                $_POST['new_password'] ?? '',
                $_POST['confirm_password'] ?? ''
            );

            $this->jsonResponse([
                'status' => 'success',
                'message' => 'Your password has been successfully updated.'
            ]);
        } catch (\Exception $e) {
            $this->errorResponse($e->getMessage(), 400, ['status' => 'error']);
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
