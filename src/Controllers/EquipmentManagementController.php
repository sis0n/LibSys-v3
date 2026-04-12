<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\EquipmentService;
use App\Services\CampusService;
use Exception;

class EquipmentManagementController extends Controller
{
    private EquipmentService $equipmentService;
    private CampusService $campusService;

    public function __construct()
    {
        parent::__construct();
        $this->equipmentService = new EquipmentService();
        $this->campusService = new CampusService();
    }

    public function index()
    {
        $role = $_SESSION['role'] ?? 'guest';
        
        $data = [
            'title' => 'Equipment Management',
            'currentPage' => 'equipmentManagement',
            'permissions' => [
                'add' => true,
                'edit' => true,
                'delete' => $role === 'superadmin' || $role === 'admin' || $role === 'campus_admin',
                'multi_delete' => $role === 'superadmin' || $role === 'admin' || $role === 'campus_admin'
            ],
            'filters' => [
                'campus_locked' => !in_array($role, ['superadmin', 'admin']),
                'default_campus' => $_SESSION['user_data']['campus_id'] ?? null
            ]
        ];

        $this->view("management/equipmentManagement/index", $data);
    }

    public function fetch()
    {
        try {
            $campusFilter = $this->getCampusFilter();
            $result = $this->equipmentService->getPaginatedEquipment($_GET, $campusFilter);
            return $this->jsonResponse($result);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function get($id = null)
    {
        try {
            if (!$id) throw new Exception('ID required');
            $equipment = $this->equipmentService->getEquipmentDetails((int)$id);
            return $this->jsonResponse(['equipment' => $equipment]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    public function store()
    {
        try {
            $adminId = $_SESSION['user_id'] ?? null;
            if (!$adminId) throw new Exception('Authentication required.');

            $campusIdFilter = $this->getCampusFilter();
            $assetTag = $this->equipmentService->createEquipment($_POST, $adminId, $campusIdFilter);

            return $this->jsonResponse(['message' => 'Equipment added successfully', 'asset_tag' => $assetTag], 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function update($id = null)
    {
        try {
            $equipmentId = $id ?? $_POST['equipment_id'] ?? null;
            $adminId = $_SESSION['user_id'] ?? null;
            if (!$equipmentId) throw new Exception('Equipment ID required');
            if (!$adminId) throw new Exception('Authentication required.');

            $campusIdFilter = $this->getCampusFilter();
            $this->equipmentService->updateEquipment((int)$equipmentId, $_POST, $adminId, $campusIdFilter);

            return $this->jsonResponse(['message' => 'Equipment updated successfully']);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function destroy($id = null)
    {
        try {
            if (!$id) throw new Exception('ID required');
            $adminId = $_SESSION['user_id'] ?? null;
            $campusIdFilter = $this->getCampusFilter();
            
            $this->equipmentService->deactivateEquipment((int)$id, $adminId, $campusIdFilter);
            return $this->jsonResponse(['message' => 'Equipment deactivated successfully']);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function deleteMultiple()
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $adminId = $_SESSION['user_id'] ?? null;
            $campusIdFilter = $this->getCampusFilter();

            if (!$adminId) throw new Exception('Authentication required.');

            $deletedCount = 0;
            $errors = [];
            foreach ($data['equipment_ids'] ?? [] as $id) {
                try {
                    $this->equipmentService->deactivateEquipment((int)$id, $adminId, $campusIdFilter);
                    $deletedCount++;
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }

            return $this->jsonResponse([
                'success' => $deletedCount > 0,
                'message' => "Successfully deactivated $deletedCount equipment(s).",
                'deleted_count' => $deletedCount,
                'errors' => $errors
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
