<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\UserPermissionModuleRepository;

class ViewController extends Controller
{
  private $userPermissionsRepo;

  public function __construct()
  {
    $this->userPermissionsRepo = new UserPermissionModuleRepository();
  }

  public function handleDashboard()
  {
    if (!isset($_SESSION['user_id'])) {
      header('Location: ' . BASE_URL . '/login');
      exit;
    }

    $role = strtolower($_SESSION['role'] ?? '');
    $userPermissions = $_SESSION['user_permissions'] ?? [];
    $normalizedPermissions = array_map(fn($p) => trim(strtolower($p)), $userPermissions);

    $view_path = null;
    $current_page = null;
    $title = "Dashboard";

    switch ($role) {
      case 'student':
      case 'faculty':
      case 'staff':
      case 'superadmin':
        $view_path = $role . '/dashboard';
        $current_page = 'dashboard';
        break;

      case 'admin':
      case 'librarian':
        $privilege_to_page = [
          'book management' => 'bookManagement',
          'qr scanner' => 'qrScanner',
          'returning' => 'returning',
          'borrowing form' => 'borrowingForm',
          'attendance logs' => 'attendanceLogs',
          'reports' => 'topVisitor',
          'transaction history' => 'transactionHistory',
          'restore books' => 'restoreBooks',
          'user management' => 'userManagement',
          'restore users' => 'restoreUser'
        ];

        foreach ($privilege_to_page as $privilege => $pageName) {
          if (in_array($privilege, $normalizedPermissions)) {
            $view_path = $role . '/' . $pageName;
            $current_page = $pageName;
            $title = ucwords(preg_replace('/(?<!^)[A-Z]/', ' $0', $pageName));
            break;
          }
        }
        break;
    }

    if ($view_path) {
      $this->view($view_path, [
        "title" => $title,
        "currentPage" => $current_page
      ]);
    } else {
      $this->view("errors/403", ["title" => "Forbidden"], false);
    }
  }

  public function handleGenericPage($action, $id = null)
  {
    if (!isset($_SESSION['user_id'])) {
      header('Location: ' . BASE_URL . '/login');
      exit;
    }

    $userId = (int)$_SESSION['user_id'];
    $role = strtolower($_SESSION['role'] ?? '');

    $protectedModules = [
      'bookManagement' => 'book management',
      'qrScanner' => 'qr scanner',
      'returning' => 'returning',
      'borrowingForm' => 'borrowing form',
      'attendanceLogs' => 'attendance logs',
      'topVisitor' => 'reports',
      'transactionHistory' => 'transaction history',
      'backup' => 'backup',
      'restoreBooks' => 'restore books',
      'restoreUser' => 'restore users',
      'userManagement' => 'user management'
    ];

    $universalPages = [
      'changePassword',
      'myProfile',
      'bookCatalog',
      'myCart',
      'qrBorrowingTicket',
      'borrowingHistory',
      'myAttendance',
      'dashboard',
      'attendance'

    ];

    if (array_key_exists($action, $protectedModules)) {

      if ($role === 'superadmin') {
      } else if ($role === 'admin' || $role === 'librarian') {
        $permissionName = $protectedModules[$action];

        if (!$this->userPermissionsRepo->hasAccess($userId, $permissionName)) {
          $this->view("errors/403", ["title" => "Forbidden"], false);
          exit;
        }
      } else {
        $this->view("errors/403", ["title" => "Forbidden"], false);
        exit;
      }
    } else if (in_array($action, $universalPages)) {
    } else {
      $this->view("errors/404", ["title" => "Not Found"], false);
      exit;
    }

    $viewPath = $role . '/' . $action;
    $data = [
      "title" => ucfirst($action),
      "currentPage" => $action
    ];

    $this->view($viewPath, $data);
  }

  public function handleLandingPage()
  {
    if (isset($_SESSION['user_id'])) {
      header('Location: ' . BASE_URL . '/dashboard');
      exit;
    }

    $this->view("guest/landingPage", [
      "title" => "Book Collections"
    ], false);
  }
}
