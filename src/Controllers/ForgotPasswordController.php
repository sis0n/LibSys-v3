<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\UserRepository;
use App\Repositories\PasswordResetRepository;
use App\Services\MailService;

class ForgotPasswordController extends Controller
{
  protected UserRepository $userRepo;
  protected PasswordResetRepository $tokenRepo;
  protected MailService $mailService;

  public function __construct()
  {
    parent::__construct();
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }
    $this->userRepo = new UserRepository();
    $this->tokenRepo = new PasswordResetRepository();
    $this->mailService = new MailService();
  }

  public function index()
  {
    if (empty($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    $viewData = [
      'csrf_token' => $_SESSION['csrf_token']
    ];
    $this->view('auth/forgotPassword', $viewData, false);
  }

  public function resetPasswordPage()
  {
    if (empty($_SESSION['reset_email']) || empty($_SESSION['otp_verified'])) {
      header("Location: " . BASE_URL . "/forgot-password");
      exit;
    }

    $viewData = [
      'csrf_token' => $_SESSION['csrf_token']
    ];

    $this->view('auth/resetPassword', $viewData, false);
  }

  protected function validateCsrf(): bool
  {
      $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
      return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
  }

  public function sendOTP()
  {
    header('Content-Type: application/json');

    if (!$this->validateCsrf()) {
      http_response_code(403);
      echo json_encode(['success' => false, 'message' => 'CSRF token validation failed.']);
      return;
    }

    $identifier = trim($_POST['identifier'] ?? '');
    if (empty($identifier)) {
      http_response_code(400);
      echo json_encode(['success' => false, 'message' => 'Please enter your username or student number.']);
      return;
    }

    $user = $this->userRepo->findByIdentifier($identifier);

    $allowedRoles = ['superadmin', 'student', 'faculty', 'staff'];

    if ($user && !empty($user['email']) && in_array(strtolower($user['role'] ?? ''), $allowedRoles)) {
        $email = $user['email'];
        $_SESSION['reset_user_id'] = $user['user_id'];
        $_SESSION['reset_last_name'] = $user['last_name'];
        
        $result = $this->_sendCode($email, $user['last_name']);

        if ($result) {
          $_SESSION['reset_email'] = $email;
        }
    }

    echo json_encode(['success' => true, 'message' => 'If an account with that username exists, a code has been sent to the registered email.']);
  }

  private function _sendCode(string $email, string $lastName): bool
  {
      try {
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiry = time() + 600; // 10 minutes expiry

        $this->tokenRepo->createToken($email, $otp, $expiry);

        $subject = "Your LibSys Password Reset Code";
        $body = "
              <p>Hello {$lastName},</p>
              <p>You requested to reset your password. Use the code below to verify your identity.</p>
              <h2 style='font-size: 24px; letter-spacing: 2px; font-weight: bold;'>{$otp}</h2>
              <p>This code is valid for 10 minutes.</p>
              <p>Regards,<br>UCC Library Team</p>
          ";

        return $this->mailService->sendEmail($email, $subject, $body);
      } catch (\Throwable $e) {
        error_log("[ForgotPasswordController::_sendCode] " . $e->getMessage());
        return false;
      }
  }

  public function updatePassword()
  {
    header('Content-Type: application/json');

    if (!$this->validateCsrf()) {
      http_response_code(403);
      echo json_encode(['success' => false, 'message' => 'CSRF token validation failed.']);
      return;
    }

    if (empty($_SESSION['reset_user_id']) || empty($_SESSION['otp_verified'])) {
      http_response_code(403);
      echo json_encode(['success' => false, 'message' => 'Session expired. Please start over.']);
      return;
    }

    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirmPassword)) {
      http_response_code(400);
      echo json_encode(['success' => false, 'message' => 'Both passwords are required.']);
      return;
    }

    if ($password !== $confirmPassword) {
      http_response_code(400);
      echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
      return;
    }

    if (strlen($password) < 8) {
      http_response_code(400);
      echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long.']);
      return;
    }

    try {
      $userId = $_SESSION['reset_user_id'];
      $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

      $success = $this->userRepo->updatePassword($userId, $hashedPassword);

      if ($success) {
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_user_id']);
        unset($_SESSION['reset_last_name']);
        unset($_SESSION['otp_verified']);

        echo json_encode(['success' => true, 'message' => 'Password has been reset successfully.']);
      } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update password.']);
      }
    } catch (\Throwable $e) {
      error_log("[ForgotPasswordController::updatePassword] " . $e->getMessage());
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => 'An internal error occurred.']);
    }
  }

  public function verifyOTPPage()
  {
    if (empty($_SESSION['reset_email'])) {
      header("Location: " . BASE_URL . "/forgot-password");
      exit;
    }

    if (empty($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    $viewData = [
      'csrf_token' => $_SESSION['csrf_token'],
      'email' => $_SESSION['reset_email']
    ];
    $this->view('auth/verifyOTP', $viewData, false);
  }

  public function resendOTP()
  {
    header('Content-Type: application/json');

    if (!$this->validateCsrf()) {
      http_response_code(403);
      echo json_encode(['success' => false, 'message' => 'CSRF token validation failed.']);
      return;
    }

    $email = $_SESSION['reset_email'] ?? null;
    $lastName = $_SESSION['reset_last_name'] ?? 'User';

    if (!$email) {
      http_response_code(400);
      echo json_encode(['success' => false, 'message' => 'Session expired. Please start over.']);
      return;
    }

    $this->_sendCode($email, $lastName);

    echo json_encode(['success' => true, 'message' => 'A new code has been sent to your email.']);
  }

  public function checkOTP()
  {
    header('Content-Type: application/json');

    if (!$this->validateCsrf()) {
      http_response_code(403);
      echo json_encode(['success' => false, 'message' => 'CSRF token validation failed.']);
      return;
    }

    $email = $_SESSION['reset_email'] ?? null;
    $otp = trim($_POST['otp'] ?? '');

    if (!$email || empty($otp)) {
      http_response_code(400);
      echo json_encode(['success' => false, 'message' => 'Invalid request. Please try again.']);
      return;
    }

    $token = $this->tokenRepo->findToken($otp);

    if (!$token) {
      echo json_encode(['success' => false, 'message' => 'Invalid code. Please try again.']);
      return;
    }

    if (strtolower($token['email']) !== strtolower($email)) {
      echo json_encode(['success' => false, 'message' => 'Invalid code. Code mismatch.']);
      return;
    }

    if (strtotime($token['expires_at']) < time()) {
      echo json_encode(['success' => false, 'message' => 'This code has expired. Please resend.']);
      return;
    }

    $_SESSION['otp_verified'] = true;

    $this->tokenRepo->deleteToken($email);

    echo json_encode(['success' => true]);
  }
}
