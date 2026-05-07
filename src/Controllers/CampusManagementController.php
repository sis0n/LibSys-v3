<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\RoleHelper;
use App\Services\CampusService;
use Exception;

class CampusManagementController extends Controller
{
    private CampusService $campusService;

    public function __construct()
    {
        parent::__construct();
        if (!RoleHelper::isSuperadmin($_SESSION['role'] ?? '')) {
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
        try {
            $campuses = $this->campusService->getAllCampuses();
            $this->jsonResponse(['campuses' => $campuses]);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    public function store()
    {
        try {
            $result = $this->campusService->createCampus(
                $_POST['campus_name'] ?? '', 
                $_POST['campus_code'] ?? '', 
                $_SESSION['user_id']
            );
            $this->jsonResponse($result);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    public function update($id)
    {
        try {
            $this->campusService->updateCampus(
                (int)$id, 
                $_POST['campus_name'] ?? '', 
                $_POST['campus_code'] ?? '', 
                $_SESSION['user_id']
            );
            $this->jsonResponse(['message' => 'Campus updated successfully!']);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    public function toggleStatus($id)
    {
        try {
            $newStatus = $this->campusService->toggleStatus((int)$id, $_SESSION['user_id']);
            $this->jsonResponse([
                'message' => "Campus status updated to $newStatus.",
                'newStatus' => $newStatus
            ]);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    public function destroy($id)
    {
        $this->errorResponse('Direct deletion is no longer allowed. Use deactivation instead.');
    }
}
