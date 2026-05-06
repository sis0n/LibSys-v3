<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\UserRepository;
use App\Repositories\PasswordResetRepository;
use App\Repositories\LoginAttemptRepository;
use App\Services\MailService;

class ForgotPasswordController extends Controller
{
  protected UserRepository $userRepo;
  protected PasswordResetRepository $tokenRepo;
  protected MailService $mailService;
  protected LoginAttemptRepository $attemptRepo;

  public function __construct()
  {
    parent::__construct();
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }
    $this->userRepo = new UserRepository();
    $this->tokenRepo = new PasswordResetRepository();
    $this->mailService = new MailService();
    $this->attemptRepo = new LoginAttemptRepository();
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

  public function sendOTP()
  {
    if (!$this->validateCsrf()) {
      return $this->errorResponse('CSRF token validation failed.', 403);
    }

    $identifier = trim($_POST['identifier'] ?? '');
    if (empty($identifier)) {
      return $this->errorResponse('Please enter your username or student number.', 400);
    }

    $user = $this->userRepo->findByIdentifier($identifier);

    // Rate limiting for Sending OTP
    $limitIdentifier = "SEND_OTP_" . $identifier;
    $maxSends = 3;
    $windowMinutes = 30;

    $sendCount = $this->attemptRepo->countAttempts($limitIdentifier, $windowMinutes);
    if ($sendCount >= $maxSends) {
        return $this->errorResponse("Too many requests. Please try again after $windowMinutes minutes.", 429);
    }

    $allowedRoles = ['superadmin', 'student', 'faculty', 'staff'];

    if ($user && !empty($user['email']) && in_array(strtolower($user['role'] ?? ''), $allowedRoles)) {
        $email = $user['email'];
        $_SESSION['reset_user_id'] = $user['user_id'];
        $_SESSION['reset_last_name'] = $user['last_name'];
        $_SESSION['reset_identifier'] = $identifier;
        
        $result = $this->_sendCode($email, $user['last_name']);

        if ($result) {
          $_SESSION['reset_email'] = $email;
          $this->attemptRepo->recordAttempt($limitIdentifier);
        }
    }

    return $this->jsonResponse(['message' => 'If an account with that username exists, a code has been sent to the registered email.']);
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
    if (!$this->validateCsrf()) {
      return $this->errorResponse('CSRF token validation failed.', 403);
    }

    if (empty($_SESSION['reset_user_id']) || empty($_SESSION['otp_verified'])) {
      return $this->errorResponse('Session expired. Please start over.', 403);
    }

    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirmPassword)) {
      return $this->errorResponse('Both passwords are required.', 400);
    }

    if ($password !== $confirmPassword) {
      return $this->errorResponse('Passwords do not match.', 400);
    }

    if (strlen($password) < 8) {
      return $this->errorResponse('Password must be at least 8 characters long.', 400);
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

        return $this->jsonResponse(['message' => 'Password has been reset successfully.']);
      } else {
        return $this->errorResponse('Failed to update password.', 500);
      }
    } catch (\Throwable $e) {
      error_log("[ForgotPasswordController::updatePassword] " . $e->getMessage());
      return $this->errorResponse('An internal error occurred.', 500);
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
    if (!$this->validateCsrf()) {
      return $this->errorResponse('CSRF token validation failed.', 403);
    }

    $email = $_SESSION['reset_email'] ?? null;
    $lastName = $_SESSION['reset_last_name'] ?? 'User';
    $identifier = $_SESSION['reset_identifier'] ?? $email;

    if (!$email) {
      return $this->errorResponse('Session expired. Please start over.', 400);
    }

    // Rate limiting for Resending OTP
    $limitIdentifier = "SEND_OTP_" . $identifier;
    $maxSends = 3;
    $windowMinutes = 30;

    $sendCount = $this->attemptRepo->countAttempts($limitIdentifier, $windowMinutes);
    if ($sendCount >= $maxSends) {
        return $this->errorResponse("Too many requests. Please try again after $windowMinutes minutes.", 429);
    }

    $success = $this->_sendCode($email, $lastName);
    if ($success) {
        $this->attemptRepo->recordAttempt($limitIdentifier);
        return $this->jsonResponse(['message' => 'A new code has been sent to your email.']);
    }

    return $this->errorResponse('Failed to send code. Please try again later.');
  }

  public function checkOTP()
  {
    if (!$this->validateCsrf()) {
      return $this->errorResponse('CSRF token validation failed.', 403);
    }

    $email = $_SESSION['reset_email'] ?? null;
    $otp = trim($_POST['otp'] ?? '');

    if (!$email || empty($otp)) {
      return $this->errorResponse('Invalid request. Please try again.', 400);
    }

    // Rate limiting for OTP verification
    $limitIdentifier = "OTP_VERIFY_" . $email;
    $maxAttempts = 5;
    $lockoutMinutes = 15;

    $failedAttempts = $this->attemptRepo->countAttempts($limitIdentifier, $lockoutMinutes);
    if ($failedAttempts >= $maxAttempts) {
        return $this->errorResponse("Too many failed attempts. Please try again after $lockoutMinutes minutes.", 429);
    }

    $token = $this->tokenRepo->findToken($otp);

    if (!$token || strtolower($token['email']) !== strtolower($email)) {
      $this->attemptRepo->recordAttempt($limitIdentifier);
      $remaining = $maxAttempts - ($failedAttempts + 1);
      $msg = "Invalid code. Please try again.";
      if ($remaining > 0) $msg .= " $remaining attempts remaining.";
      else $msg = "Too many failed attempts. Your account is locked for $lockoutMinutes minutes.";
      
      return $this->errorResponse($msg);
    }

    if (strtotime($token['expires_at']) < time()) {
      return $this->errorResponse('This code has expired. Please resend.');
    }

    $_SESSION['otp_verified'] = true;

    $this->tokenRepo->deleteToken($email);
    $this->attemptRepo->clearAttempts($limitIdentifier);

    return $this->jsonResponse();
  }
}
