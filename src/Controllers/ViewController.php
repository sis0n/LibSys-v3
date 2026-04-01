<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\UserPermissionModuleRepository;

class ViewController extends Controller
{
  private $userPermissionsRepo;

  public function __construct()
  {
    parent::__construct();
    $this->userPermissionsRepo = new UserPermissionModuleRepository();
  }

  private function getViewRoleFolder(string $role): string
  {
    $roleMap = [
        'admin' => 'Admin',
        'superadmin' => 'Superadmin',
        'student' => 'Student',
        'faculty' => 'Faculty',
        'librarian' => 'Librarian',
        'staff' => 'staff',
        'campus_admin' => 'campus_admin'
    ];
    return $roleMap[$role] ?? $role;
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

    $viewFolder = $this->getViewRoleFolder($role);

    switch ($role) {
      case 'student':
      case 'faculty':
      case 'staff':
      case 'superadmin':
        $view_path = $viewFolder . '/dashboard';
        $current_page = 'dashboard';
        break;

      case 'campus_admin':
      case 'admin':
      case 'librarian':
        $privilege_to_page = [
          'user management' => 'userManagement',
          'student promotion' => 'studentPromotion',
          'book management' => 'bookManagement',
          'equipment management' => 'equipmentManagement',
          'qr scanner' => 'qrScanner',
          'returning' => 'returning',
          'borrowing form' => 'borrowingForm',
          'attendance logs' => 'attendanceLogs',
          'reports' => 'topVisitor',
          'transaction history' => 'transactionHistory',
        ];

        if ($role === 'campus_admin') {
            $view_path = $viewFolder . '/bookManagement';
            $current_page = 'bookManagement';
            $title = 'Book Management';
            break;
        }

        foreach ($privilege_to_page as $privilege => $pageName) {
          if (in_array($privilege, $normalizedPermissions)) {
            $view_path = $viewFolder . '/' . $pageName;
            $current_page = $pageName;
            $title = ucwords(preg_replace('/(?<!^)[A-Z]/', ' $0', $pageName));
            break;
          }
        }

        // Fallback for Admin/Librarian if no specific permission page matched
        if (!$view_path) {
            if ($role === 'admin') {
                $view_path = $viewFolder . '/userManagement';
                $current_page = 'userManagement';
                $title = 'User Management';
            } else {
                $view_path = $viewFolder . '/bookManagement';
                $current_page = 'bookManagement';
                $title = 'Book Management';
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
      'equipmentManagement' => 'equipment management',
      'qrScanner' => 'qr scanner',
      'returning' => 'returning',
      'borrowingForm' => 'borrowing form',
      'attendanceLogs' => 'attendance logs',
      'topVisitor' => 'reports',
      'transactionHistory' => 'transaction history',
      'backup' => 'backup',
      'restoreBooks' => 'restore books',
      'restoreEquipment' => 'restore equipment',
      'restoreUser' => 'restore users',
      'userManagement' => 'user management',
      'libraryPolicies' => 'superadmin',
      'overdue' => 'overdue tracking',
      'campusManagement' => 'superadmin',
      'auditLogs' => 'superadmin',
      'bulkDeleteQueue' => 'bulk delete queue'
    ];

    $universalPages = [
      'changePassword',
      'myProfile',
      'bookCatalog',
      'myCart',
      'qrBorrowingTicket',
      'borrowingHistory',
      'myAttendance',
      'attendance'
    ];

    if ($action === 'dashboard' && ($role === 'admin' || $role === 'librarian' || $role === 'campus_admin')) {
        $this->handleDashboard();
        return;
    }

    if (array_key_exists($action, $protectedModules)) {

      if ($role === 'superadmin' || $role === 'campus_admin') {
        if ($role === 'campus_admin') {
          // Restricted modules for Campus Admin based on RBAC_POLICY.md
          $restrictedForCampusAdmin = ['campusManagement', 'backup', 'restoreUser', 'auditLogs'];
          if (in_array($action, $restrictedForCampusAdmin)) {
            $this->view("errors/403", ["title" => "Forbidden"], false);
            exit;
          }
        }
      } else if ($role === 'admin' || $role === 'librarian') {
        $permissionName = $protectedModules[$action];

        if ($permissionName !== 'universal' && !$this->userPermissionsRepo->hasAccess($userId, $permissionName)) {
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

    $viewFolder = $this->getViewRoleFolder($role);
    $viewPath = $viewFolder . '/' . $action;
    $data = [
      "title" => ucfirst($action),
      "currentPage" => $action
    ];

    // Inject campuses data for management pages that need it
    if (in_array($action, ['bookManagement', 'equipmentManagement', 'userManagement', 'libraryPolicies', 'bulkDeleteQueue'])) {
      $campusRepo = new \App\Repositories\CampusRepository();
      $allCampuses = $campusRepo->getAllCampuses();
      $data['campuses'] = array_filter($allCampuses, fn($c) => $c['is_active'] == 1);
    }

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
