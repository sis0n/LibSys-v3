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
        $viewPath = ucfirst($role) . "/equipmentManagement";
        
        $campuses = $this->campusService->getAllCampuses();
        $activeCampuses = array_filter($campuses, fn($c) => $c['is_active'] == 1);

        $this->view($viewPath, [
            "title" => "Equipment Management",
            "campuses" => $activeCampuses
        ]);
    }

    public function getAll()
    {
        header('Content-Type: application/json');
        try {
            $campusFilter = $this->getCampusFilter();
            $result = $this->equipmentService->getPaginatedEquipment($_GET, $campusFilter);
            echo json_encode(array_merge(['success' => true], $result));
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function get($id = null)
    {
        header('Content-Type: application/json');
        try {
            if (!$id) throw new Exception('ID required');
            $equipment = $this->equipmentService->getEquipmentDetails((int)$id);
            echo json_encode(['success' => true, 'equipment' => $equipment]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    public function store()
    {
        header('Content-Type: application/json');
        try {
            $adminId = $_SESSION['user_id'] ?? null;
            if (!$adminId) throw new Exception('Authentication required.');

            $campusIdFilter = $this->getCampusFilter();
            $assetTag = $this->equipmentService->createEquipment($_POST, $adminId, $campusIdFilter);

            echo json_encode(['success' => true, 'message' => 'Equipment added successfully', 'asset_tag' => $assetTag]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function update($id = null)
    {
        header('Content-Type: application/json');
        try {
            $equipmentId = $id ?? $_POST['equipment_id'] ?? null;
            $adminId = $_SESSION['user_id'] ?? null;
            if (!$equipmentId) throw new Exception('Equipment ID required');
            if (!$adminId) throw new Exception('Authentication required.');

            $campusIdFilter = $this->getCampusFilter();
            $this->equipmentService->updateEquipment((int)$equipmentId, $_POST, $adminId, $campusIdFilter);

            echo json_encode(['success' => true, 'message' => 'Equipment updated successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function destroy($id = null)
    {
        header('Content-Type: application/json');
        try {
            if (!$id) throw new Exception('ID required');
            $adminId = $_SESSION['user_id'] ?? null;
            $campusIdFilter = $this->getCampusFilter();
            
            $this->equipmentService->deactivateEquipment((int)$id, $adminId, $campusIdFilter);
            echo json_encode(['success' => true, 'message' => 'Equipment deactivated successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteMultiple()
    {
        header('Content-Type: application/json');
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

            echo json_encode([
                'success' => $deletedCount > 0,
                'message' => "Successfully deactivated $deletedCount equipment(s).",
                'deleted_count' => $deletedCount,
                'errors' => $errors
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
