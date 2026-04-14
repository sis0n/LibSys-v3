<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\UserPermissionModuleRepository;
use App\Repositories\LibraryPolicyRepository;

class ViewController extends Controller
{
  private $userPermissionsRepo;
  private $policyRepo;

  public function __construct()
  {
    parent::__construct();
    $this->userPermissionsRepo = new UserPermissionModuleRepository();
    $this->policyRepo = new LibraryPolicyRepository();
  }

  private function getViewRoleFolder(string $role): string
  {
    $roleMap = [
        'admin' => 'Admin',
        'superadmin' => 'Superadmin',
        'student' => 'Student',
        'faculty' => 'Faculty',
        'librarian' => 'Librarian',
        'staff' => 'staff'
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

        foreach ($privilege_to_page as $privilege => $pageName) {
          if (in_array($privilege, $normalizedPermissions)) {
            $view_path = $viewFolder . '/' . $pageName;
            $current_page = $pageName;
            $title = ucwords(preg_replace('/(?<!^)[A-Z]/', ' $0', $pageName));
            break;
          }
        }

        if (!$view_path) {
            $view_path = $viewFolder . ($role === 'admin' ? '/userManagement' : '/bookManagement');
            $current_page = $role === 'admin' ? 'userManagement' : 'bookManagement';
            $title = $role === 'admin' ? 'User Management' : 'Book Management';
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

    if ($action === 'dashboard' && ($role === 'admin' || $role === 'librarian')) {
        $this->handleDashboard();
        return;
    }

    if (array_key_exists($action, $protectedModules)) {
      if ($role === 'superadmin') {
          // Superadmin has access to everything
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
    // Special handling for consolidated management views
    if ($action === 'userManagement') {
        header('Location: ' . BASE_URL . '/userManagement');
        exit;
    }

    if ($action === 'bookManagement') {
        header('Location: ' . BASE_URL . '/bookManagement');
        exit;
    }

    if ($action === 'equipmentManagement') {
        header('Location: ' . BASE_URL . '/equipmentManagement');
        exit;
    }

    if ($action === 'qrScanner') {
        header('Location: ' . BASE_URL . '/qrScanner');
        exit;
    }

    if ($action === 'returning') {
        header('Location: ' . BASE_URL . '/returning');
        exit;
    }

    if ($action === 'transactionHistory') {
        header('Location: ' . BASE_URL . '/transactionHistory');
        exit;
    }

    if ($action === 'borrowingForm') {
        header('Location: ' . BASE_URL . '/borrowingForm');
        exit;
    }

    if ($action === 'attendanceLogs') {
        header('Location: ' . BASE_URL . '/attendanceLogs');
        exit;
    }

    if (in_array($action, ['bookManagement', 'equipmentManagement', 'libraryPolicies', 'bulkDeleteQueue'])) {      $campusRepo = new \App\Repositories\CampusRepository();
      $allCampuses = $campusRepo->getAllCampuses();
      $data['campuses'] = array_filter($allCampuses, fn($c) => $c['is_active'] == 1);
    }

    if ($action === 'libraryPolicies') {
        $data['selectedCampusId'] = isset($_GET['campus_id']) ? (int)$_GET['campus_id'] : 1;
        $data['policies'] = $this->policyRepo->getPoliciesByCampus($data['selectedCampusId']);
        $data['isViewOnly'] = false;
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
