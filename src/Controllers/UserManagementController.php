<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\UserService;
use Exception;

class UserManagementController extends Controller
{
    private UserService $userService;

    public function __construct()
    {
        parent::__construct();
        
        // RBAC: Restricted roles for User Management
        $role = strtolower(str_replace([' ', '-'], '_', $_SESSION['role'] ?? ''));
        if (in_array($role, ['librarian', 'student', 'faculty', 'staff', 'scanner'])) {
            http_response_code(403);
            die("Forbidden: Access denied.");
        }

        $this->userService = new UserService();
    }

    public function index()
    {
        $this->view('superadmin/userManagement', [
            'title' => 'User Management',
        ]);
    }

    public function fetchPaginatedUsers()
    {
        header('Content-Type: application/json');
        try {
            $campusId = $this->getCampusFilter();
            $result = $this->userService->getPaginatedUsers($_GET, $_SESSION['user_id'] ?? null, $campusId);
            echo json_encode(['success' => true, 'users' => $result['users'], 'totalCount' => $result['totalCount']]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function getUserById($id)
    {
        header('Content-Type: application/json');
        try {
            $details = $this->userService->getUserDetails((int)$id);
            echo json_encode($details);
        } catch (Exception $e) {
            http_response_code(404);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function search()
    {
        header('Content-Type: application/json');
        try {
            $query = $_GET['q'] ?? '';
            $users = $this->userService->searchUsers($query);
            echo json_encode(['success' => true, 'users' => $users]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function addUser()
    {
        header('Content-Type: application/json');
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $campusIdFilter = $this->getCampusFilter();
            
            $userId = $this->userService->addUser($data, $_SESSION['user_id'], $campusIdFilter);
            
            echo json_encode([
                'success' => true,
                'message' => 'User added successfully.',
                'user_id' => $userId,
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteUser($id)
    {
        header('Content-Type: application/json');
        try {
            $adminId = $_SESSION['user_id'] ?? null;
            if (!$adminId) throw new Exception('Unauthorized');

            $campusIdFilter = $this->getCampusFilter();
            $this->userService->deleteUser((int)$id, $adminId, $campusIdFilter);

            echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteMultipleUsers()
    {
        header('Content-Type: application/json');
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $adminId = $_SESSION['user_id'] ?? null;
            $adminRole = strtolower(str_replace([' ', '-'], '_', $_SESSION['role'] ?? ''));

            if (!$adminId) throw new Exception('Unauthorized');

            $result = $this->userService->bulkDelete(
                $data['user_ids'] ?? [], 
                $data['reason'] ?? null, 
                $adminId, 
                $adminRole
            );

            echo json_encode(array_merge(['success' => true], $result));
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function toggleStatus($id)
    {
        header('Content-Type: application/json');
        try {
            $adminId = $_SESSION['user_id'] ?? null;
            $campusIdFilter = $this->getCampusFilter();
            
            $newStatus = $this->userService->toggleStatus((int)$id, $adminId, $campusIdFilter);

            echo json_encode([
                'success' => true,
                'message' => 'User status updated successfully.',
                'newStatus' => $newStatus
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function updateUser($id)
    {
        header('Content-Type: application/json');
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $adminId = $_SESSION['user_id'] ?? null;
            $campusIdFilter = $this->getCampusFilter();

            $this->userService->updateUser((int)$id, $data, $adminId, $campusIdFilter);

            echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function allowEdit($id)
    {
        header('Content-Type: application/json');
        try {
            $updated = $this->userService->setEditAccess((int)$id, true);
            echo json_encode([
                'success' => $updated,
                'message' => $updated ? 'Student can now edit their profile again.' : 'Failed to grant edit access.'
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function bulkImport()
    {
        header('Content-Type: application/json');
        try {
            if (!isset($_FILES['csv_file'])) throw new Exception('No file uploaded.');

            $adminId = $_SESSION['user_id'] ?? null;
            $result = $this->userService->bulkImport($_FILES['csv_file']['tmp_name'], $adminId);

            echo json_encode(array_merge(['success' => true], $result));
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
