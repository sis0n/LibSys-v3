<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\CampusService;
use Exception;

class CampusManagementController extends Controller
{
    private CampusService $campusService;

    public function __construct()
    {
        parent::__construct();
        // RBAC: Only Superadmin can manage campuses
        $role = strtolower(str_replace([' ', '-'], '_', $_SESSION['role'] ?? ''));
        if ($role !== 'superadmin') {
            http_response_code(403);
            die("Forbidden: Access denied. Only Superadmin can manage campuses.");
        }

        $this->campusService = new CampusService();
    }

    public function index()
    {
        $this->view('superadmin/campusManagement', [
            'title' => 'Campus Management',
            'currentPage' => 'campusManagement'
        ]);
    }

    public function fetch()
    {
        header('Content-Type: application/json');
        try {
            $campuses = $this->campusService->getAllCampuses();
            echo json_encode(['success' => true, 'campuses' => $campuses]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function store()
    {
        header('Content-Type: application/json');
        try {
            $result = $this->campusService->createCampus(
                $_POST['campus_name'] ?? '', 
                $_POST['campus_code'] ?? '', 
                $_SESSION['user_id']
            );
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function update($id)
    {
        header('Content-Type: application/json');
        try {
            $this->campusService->updateCampus(
                (int)$id, 
                $_POST['campus_name'] ?? '', 
                $_POST['campus_code'] ?? '', 
                $_SESSION['user_id']
            );
            echo json_encode(['success' => true, 'message' => 'Campus updated successfully!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function toggleStatus($id)
    {
        header('Content-Type: application/json');
        try {
            $newStatus = $this->campusService->toggleStatus((int)$id, $_SESSION['user_id']);
            echo json_encode([
                'success' => true, 
                'message' => "Campus status updated to $newStatus.",
                'newStatus' => $newStatus
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function destroy($id)
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Direct deletion is no longer allowed. Use deactivation instead.']);
        exit;
    }
}
