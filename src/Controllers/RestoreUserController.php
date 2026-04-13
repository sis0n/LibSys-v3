<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\RoleHelper;
use App\Repositories\RestoreUserRepository;
use Exception;

class RestoreUserController extends Controller
{
  protected RestoreUserRepository $restoreUserRepo;
  protected \App\Repositories\AuditLogRepository $auditRepo;

  public function __construct()
  {
    parent::__construct();
    
    $role = $_SESSION['role'] ?? '';
    $campusId = $_SESSION['user_data']['campus_id'] ?? null;

    $isSuper = RoleHelper::isSuperadmin($role);
    $isGlobalAdmin = RoleHelper::isGlobalAdmin($role, $campusId);

    if (!$isSuper && !$isGlobalAdmin) {
      $this->view('errors/403', ['title' => 'Access Denied']);
      exit;
    }
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
    try {
      $users = $this->restoreUserRepo->getDeletedUsers();
      return $this->jsonResponse(['users' => $users]);
    } catch (\Throwable $e) {
      error_log("Error in getDeletedUsersJson: " . $e->getMessage());
      return $this->errorResponse('Failed to fetch deleted users.', 500);
    }
  }

  public function restore()
  {
    if (session_status() === PHP_SESSION_NONE) session_start();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      return $this->errorResponse('Method Not Allowed', 405);
    }

    if (!$this->validateCsrf()) {
      return $this->errorResponse('CSRF token validation failed.', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? null;

    if (!$userId) {
      return $this->errorResponse('User ID is required.', 400);
    }

    try {
      $user = $this->restoreUserRepo->getUserById((int)$userId);
      $restored = $this->restoreUserRepo->restoreUser((int)$userId);
      if ($restored) {
        $adminId = $_SESSION['user_id'] ?? null;
        $userIdentifier = $user ? "{$user['first_name']} {$user['last_name']} (@{$user['username']})" : "ID: $userId";
        $this->auditRepo->log($adminId, 'RESTORE', 'USERS', $userId, "Restored user account: $userIdentifier");
        return $this->jsonResponse(['message' => 'User restored successfully.']);
      } else {
        return $this->errorResponse('Failed to restore user. User might not exist or already restored.', 400);
      }
    } catch (\Throwable $e) {
      error_log("Error restoring user $userId: " . $e->getMessage());
      return $this->errorResponse('An internal error occurred during restoration.', 500);
    }
  }

  public function archive($id)
  {
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (!$this->validateCsrf()) {
      return $this->errorResponse('CSRF token validation failed.', 403);
    }

    $userId = filter_var($id, FILTER_VALIDATE_INT);
    $librarianId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    if (!$userId) {
      return $this->errorResponse('Invalid User ID provided in URL.', 400);
    }
    if (!$librarianId) {
      return $this->errorResponse('Unauthorized action. Admin ID not found.', 403);
    }

    try {
      $user = $this->restoreUserRepo->getUserById((int)$userId);
      $result = $this->restoreUserRepo->archiveUser($userId, $librarianId);
      if ($result['success']) {
        $userIdentifier = $user ? "{$user['first_name']} {$user['last_name']} (@{$user['username']})" : "ID: $userId";
        $this->auditRepo->log($librarianId, 'ARCHIVE', 'USERS', $userId, "Permanently archived user account: $userIdentifier");
        return $this->jsonResponse(['message' => 'User data successfully archived.']);
      } else {
          return $this->errorResponse('Failed to archive user. Check debug info.', 404, [
            'debug_reason' => $result['debug_reason'] ?? 'Unknown reason.',
            'debug_data' => $result['debug_data'] ?? null
          ]);
        }
    } catch (\Exception $e) {
      error_log("Error archiving user $userId: " . $e->getMessage());
      return $this->errorResponse('An internal error occurred during archiving.', 500);
    }
  }
}
