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

        // RBAC: Restricted roles for User Management
        $role = $_SESSION['role'] ?? '';
        if (RoleHelper::isLibrarian($role) || !RoleHelper::isStaff($role) || RoleHelper::compareNormalize($role) === RoleHelper::compareNormalize(RoleHelper::SCANNER)) {
            // Check if user has explicit 'user management' permission
            $userId = $_SESSION['user_id'] ?? null;
            if ($userId) {
                $userPermissionsRepo = new \App\Repositories\UserPermissionModuleRepository();
                if (!$userPermissionsRepo->hasAccess($userId, 'user management')) {
                    http_response_code(403);
                    die("Forbidden: Access denied.");
                }
            } else {
                http_response_code(403);
                die("Forbidden: Access denied.");
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

        $data = [
            'title' => 'User Management',
            'currentPage' => 'userManagement',
            'campuses' => $activeCampuses,
            'permissions' => [
                'add' => true,
                'edit' => true,
                'delete' => RoleHelper::isSuperadmin($role) || RoleHelper::isAdmin($role),
                'bulk_import' => RoleHelper::isSuperadmin($role) || RoleHelper::isAdmin($role),
                'multi_select' => true,
                'allow_edit' => true,
                'manage_permissions' => RoleHelper::isSuperadmin($role) || RoleHelper::isAdmin($role),
            ],
            'filters' => [
                'campus_locked' => !RoleHelper::isSuperadmin($role),
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
            $result = $this->userService->getPaginatedUsers($_GET, $_SESSION['user_id'] ?? null, $campusId);
            return $this->jsonResponse(['users' => $result['users'], 'totalCount' => $result['totalCount']]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function getUserById($id)
    {
        try {
            $details = $this->userService->getUserDetails((int)$id);
            return $this->jsonResponse($details);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    public function search()
    {
        try {
            $query = $_GET['q'] ?? '';
            $users = $this->userService->searchUsers($query);
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

    public function deleteMultipleUsers()
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $adminId = $_SESSION['user_id'] ?? null;
            $adminRole = RoleHelper::compareNormalize($_SESSION['role'] ?? '');

            if (!$adminId) throw new Exception('Unauthorized');

            $result = $this->userService->bulkDelete(
                $data['user_ids'] ?? [], 
                $data['reason'] ?? null, 
                $adminId, 
                $adminRole
            );

            return $this->jsonResponse($result);
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
