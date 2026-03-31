<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\RestoreUserRepository;

class RestoreUserController extends Controller
{
  protected RestoreUserRepository $restoreUserRepo;
  protected \App\Repositories\AuditLogRepository $auditRepo;

  public function __construct()
  {
    parent::__construct();
    $this->restoreUserRepo = new RestoreUserRepository();
    $this->auditRepo = new \App\Repositories\AuditLogRepository();
  }

  public function index()
  {
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (empty($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    $viewData = [
      "title" => "Restore User",
      "currentPage" => "restoreUser",
      'csrf_token' => $_SESSION['csrf_token']
    ];
    $this->view('superadmin/restoreUser', $viewData);
  }

  public function getDeletedUsersJson()
  {
    header('Content-Type: application/json');
    try {
      $users = $this->restoreUserRepo->getDeletedUsers();
      echo json_encode(['success' => true, 'users' => $users]);
    } catch (\Throwable $e) {
      error_log("Error in getDeletedUsersJson: " . $e->getMessage());
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => 'Failed to fetch deleted users.']);
    }
  }

  public function restore()
  {
    header('Content-Type: application/json');
    if (session_status() === PHP_SESSION_NONE) session_start();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      http_response_code(405);
      echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
      return;
    }

    if (!$this->validateCsrf()) {
      http_response_code(403);
      echo json_encode(['success' => false, 'message' => 'CSRF token validation failed.']);
      return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? null;

    if (!$userId) {
      http_response_code(400);
      echo json_encode(['success' => false, 'message' => 'User ID is required.']);
      return;
    }

    try {
      $user = $this->restoreUserRepo->getUserById((int)$userId);
      $restored = $this->restoreUserRepo->restoreUser((int)$userId);
      if ($restored) {
        $adminId = $_SESSION['user_id'] ?? null;
        $userIdentifier = $user ? "{$user['first_name']} {$user['last_name']} (@{$user['username']})" : "ID: $userId";
        $this->auditRepo->log($adminId, 'RESTORE', 'USERS', $userId, "Restored user account: $userIdentifier");
        echo json_encode(['success' => true, 'message' => 'User restored successfully.']);
      } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Failed to restore user. User might not exist or already restored.']);
      }
    } catch (\Throwable $e) {
      error_log("Error restoring user $userId: " . $e->getMessage());
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => 'An internal error occurred during restoration.']);
    }
  }

  public function archive($id)
  {
    header('Content-Type: application/json');
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (!$this->validateCsrf()) {
      http_response_code(403);
      echo json_encode(['success' => false, 'message' => 'CSRF token validation failed.']);
      return;
    }

    $userId = filter_var($id, FILTER_VALIDATE_INT);
    $librarianId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    if (!$userId) {
      http_response_code(400);
      echo json_encode(['success' => false, 'message' => 'Invalid User ID provided in URL.']);
      return;
    }
    if (!$librarianId) {
      http_response_code(403);
      echo json_encode(['success' => false, 'message' => 'Unauthorized action. Admin ID not found.']);
      return;
    }

    try {
      $user = $this->restoreUserRepo->getUserById((int)$userId);
      $result = $this->restoreUserRepo->archiveUser($userId, $librarianId);
      if ($result['success']) {
        $userIdentifier = $user ? "{$user['first_name']} {$user['last_name']} (@{$user['username']})" : "ID: $userId";
        $this->auditRepo->log($librarianId, 'ARCHIVE', 'USERS', $userId, "Permanently archived user account: $userIdentifier");
        echo json_encode(['success' => true, 'message' => 'User data successfully archived.']);
      } else {
          http_response_code(404);
          echo json_encode([
            'success' => false,
            'message' => 'Failed to archive user. Check debug info.',
            'debug_reason' => $result['debug_reason'] ?? 'Unknown reason.',
            'debug_data' => $result['debug_data'] ?? null
          ]);
        }
    } catch (\Exception $e) {
      http_response_code(500);
      error_log("Error archiving user $userId: " . $e->getMessage());
      echo json_encode(['success' => false, 'message' => 'An internal error occurred during archiving.']);
    }
  }
}
