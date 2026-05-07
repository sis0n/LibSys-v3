<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\RoleHelper;
use App\Services\UserService;
use Exception;

class UserManagementController extends Controller
{
    private UserService $userService;

    public function __construct()
    {
        parent::__construct();

        $role = strtolower($_SESSION['role'] ?? '');
        $userId = $_SESSION['user_id'] ?? null;

        if ($role === 'librarian' || !RoleHelper::isManagementStaff($role) || $role === 'scanner') {
            $this->view('errors/403', ['title' => 'Access Denied'], false);
            exit;
        }

        if (!RoleHelper::isSuperadmin($role)) {
            $userPermissionsRepo = new \App\Repositories\UserPermissionModuleRepository();
            if (!$userPermissionsRepo->hasAccess($userId, 'user management')) {
                $this->view('errors/403', ['title' => 'Access Denied'], false);
                exit;
            }
        }

        $this->userService = new UserService();
    }

    public function index()
    {
        $role = strtolower($_SESSION['role'] ?? '');
        $campusId = $_SESSION['user_data']['campus_id'] ?? null;

        $campusRepo = new \App\Repositories\CampusRepository();
        $allCampuses = $campusRepo->getAllCampuses();
        $activeCampuses = array_filter($allCampuses, fn($c) => $c['is_active'] == 1);

        $isPrivileged = RoleHelper::isSuperadmin($role) || RoleHelper::isGlobalAdmin($role, $campusId);

        $data = [
            'title' => 'User Management',
            'currentPage' => 'userManagement',
            'campuses' => $activeCampuses,
            'filters' => [
                'campus_locked' => !$isPrivileged,
                'default_campus' => $campusId
            ],
            'user_role' => $role
        ];

        $this->view('management/userManagement/index', $data);
    }

    public function fetchPaginatedUsers()
    {
        try {
            $campusId = $this->getCampusFilter();
            
            if ($campusId === null && isset($_GET['campus_id']) && $_GET['campus_id'] !== '') {
                $campusId = (int)$_GET['campus_id'];
            }

            $result = $this->userService->getPaginatedUsers($_GET, $_SESSION['user_id'] ?? null, $campusId);
            return $this->jsonResponse(['users' => $result['users'], 'totalCount' => $result['totalCount']]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function getUserById($id)
    {
        try {
            $campusId = $this->getCampusFilter();
            $details = $this->userService->getUserDetails((int)$id, $campusId);
            return $this->jsonResponse($details);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    public function search()
    {
        try {
            $query = $_GET['q'] ?? '';
            $campusId = $this->getCampusFilter();

            if ($campusId === null && isset($_GET['campus_id']) && $_GET['campus_id'] !== '') {
                $campusId = (int)$_GET['campus_id'];
            }

            $users = $this->userService->searchUsers($query, $campusId);
            return $this->jsonResponse(['users' => $users]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function addUser()
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $campusIdFilter = $this->getCampusFilter();
            
            $userId = $this->userService->addUser($data, $_SESSION['user_id'], $campusIdFilter);
            
            return $this->jsonResponse([
                'message' => 'User added successfully.',
                'user_id' => $userId,
            ], 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function deleteUser($id)
    {
        try {
            $adminId = $_SESSION['user_id'] ?? null;
            if (!$adminId) throw new Exception('Unauthorized');

            $campusIdFilter = $this->getCampusFilter();
            $this->userService->deleteUser((int)$id, $adminId, $campusIdFilter);

            return $this->jsonResponse(['message' => 'User deleted successfully.']);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function toggleStatus($id)
    {
        try {
            $adminId = $_SESSION['user_id'] ?? null;
            $campusIdFilter = $this->getCampusFilter();
            
            $newStatus = $this->userService->toggleStatus((int)$id, $adminId, $campusIdFilter);

            return $this->jsonResponse([
                'message' => 'User status updated successfully.',
                'newStatus' => $newStatus
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function updateUser($id)
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $adminId = $_SESSION['user_id'] ?? null;
            $campusIdFilter = $this->getCampusFilter();

            $this->userService->updateUser((int)$id, $data, $adminId, $campusIdFilter);

            return $this->jsonResponse(['message' => 'User updated successfully.']);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function allowEdit($id)
    {
        try {
            $updated = $this->userService->setEditAccess((int)$id, true);
            if ($updated) {
                return $this->jsonResponse(['message' => 'Student can now edit their profile again.']);
            } else {
                return $this->errorResponse('Failed to grant edit access.');
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function bulkImport()
    {
        try {
            if (!isset($_FILES['csv_file'])) throw new Exception('No file uploaded.');

            $adminId = $_SESSION['user_id'] ?? null;
            $result = $this->userService->bulkImport($_FILES['csv_file']['tmp_name'], $adminId);

            return $this->jsonResponse($result);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
