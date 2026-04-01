<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\EquipmentManagementRepository;
use App\Repositories\CampusRepository;
use Exception;

class EquipmentManagementController extends Controller
{
    private EquipmentManagementRepository $equipmentRepo;
    private CampusRepository $campusRepo;
    private \App\Repositories\AuditLogRepository $auditRepo;

    public function __construct()
    {
    parent::__construct();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->equipmentRepo = new EquipmentManagementRepository();
        $this->campusRepo = new CampusRepository();
        $this->auditRepo = new \App\Repositories\AuditLogRepository();
    }

    private function json($data, int $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    private function generateAssetTag(): string
    {
        $prefix = "EQP-" . date('Y') . "-";
        $isUnique = false;
        $newTag = "";

        while (!$isUnique) {
            $random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $newTag = $prefix . $random;
            if ($this->equipmentRepo->isAssetTagUnique($newTag)) {
                $isUnique = true;
            }
        }
        return $newTag;
    }

    public function index()
    {
        $role = $_SESSION['role'] ?? 'guest';
        $viewPath = ucfirst($role) . "/equipmentManagement";
        
        // Fetch active campuses for the modal dropdown
        $allCampuses = $this->campusRepo->getAllCampuses();
        $activeCampuses = array_filter($allCampuses, fn($c) => $c['is_active'] == 1);

        $this->view($viewPath, [
            "title" => "Equipment Management",
            "campuses" => $activeCampuses
        ]);
    }

    public function getAll()
    {
        try {
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? 'All Status';
            
            $campusFilter = $this->getCampusFilter();
            $campusId = $campusFilter !== null ? $campusFilter : ($_GET['campus_id'] ?? null);
            
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

            $equipments = $this->equipmentRepo->fetchEquipments($search, $status, 'default', $limit, $offset, $campusId);
            $totalCount = $this->equipmentRepo->countEquipments($search, $status, $campusId);

            $this->json([
                'success' => true,
                'equipments' => $equipments,
                'totalCount' => (int)$totalCount
            ]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function get($id = null)
    {
        try {
            if (!$id) $this->json(['success' => false, 'message' => 'ID required'], 400);
            $equipment = $this->equipmentRepo->getById($id);
            if ($equipment) {
                $this->json(['success' => true, 'equipment' => $equipment]);
            } else {
                $this->json(['success' => false, 'message' => 'Equipment not found'], 404);
            }
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function add()
    {
        try {
            $campusIdFilter = $this->getCampusFilter();
            $campusId = $campusIdFilter !== null ? $campusIdFilter : ($_POST['campus_id'] ?? null);

            $data = [
                'equipment_name' => $_POST['equipment_name'] ?? '',
                'campus_id'      => $campusId,
                'asset_tag'      => $this->generateAssetTag(),
                'status'         => $_POST['status'] ?? 'available',
                'is_active'      => 1
            ];

            if (empty($data['equipment_name'])) {
                $this->json(['success' => false, 'message' => 'Equipment Name is required'], 400);
            }

            if (empty($data['campus_id'])) {
                $this->json(['success' => false, 'message' => 'Campus selection is required'], 400);
            }

            $equipmentId = $this->equipmentRepo->addEquipment($data);

            if ($equipmentId) {
                $this->auditRepo->log($_SESSION['user_id'], 'ADD', 'EQUIPMENTS', $data['asset_tag'], "Added new equipment: {$data['equipment_name']}");
                $this->json(['success' => true, 'message' => 'Equipment added successfully', 'asset_tag' => $data['asset_tag']]);
            } else {
                $this->json(['success' => false, 'message' => 'Failed to add equipment'], 500);
            }
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store() { $this->add(); }

    public function update($id = null)
    {
        try {
            $equipmentId = $id ?? $_POST['equipment_id'] ?? null;
            if (!$equipmentId) $this->json(['success' => false, 'message' => 'Equipment ID required'], 400);

            $equipment = $this->equipmentRepo->getById($equipmentId);
            if (!$equipment) {
                return $this->json(['success' => false, 'message' => 'Equipment not found'], 404);
            }

            $campusIdFilter = $this->getCampusFilter();
            if ($campusIdFilter !== null && $equipment['campus_id'] != $campusIdFilter) {
                return $this->json(['success' => false, 'message' => 'Unauthorized: Equipment belongs to another campus.'], 403);
            }

            $data = [
                'equipment_name' => $_POST['equipment_name'] ?? '',
                'campus_id'      => $campusIdFilter !== null ? $campusIdFilter : ($_POST['campus_id'] ?? null),
                'status'         => $_POST['status'] ?? 'available'
            ];

            if (empty($data['equipment_name'])) {
                $this->json(['success' => false, 'message' => 'Equipment Name is required'], 400);
            }

            if (empty($data['campus_id'])) {
                $this->json(['success' => false, 'message' => 'Campus selection is required'], 400);
            }

            $success = $this->equipmentRepo->updateEquipment((int)$equipmentId, $data);

            if ($success) {
                $this->auditRepo->log($_SESSION['user_id'], 'UPDATE', 'EQUIPMENTS', $equipmentId, "Updated equipment ID $equipmentId: {$data['equipment_name']}");
                $this->json(['success' => true, 'message' => 'Equipment updated successfully']);
            } else {
                $this->json(['success' => false, 'message' => 'Failed to update equipment'], 500);
            }
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function toggleActive($id = null)
    {
        try {
            if (!$id) $this->json(['success' => false, 'message' => 'ID required'], 400);

            $equipment = $this->equipmentRepo->getById($id);
            if (!$equipment) {
                return $this->json(['success' => false, 'message' => 'Equipment not found'], 404);
            }

            $campusIdFilter = $this->getCampusFilter();
            if ($campusIdFilter !== null && $equipment['campus_id'] != $campusIdFilter) {
                return $this->json(['success' => false, 'message' => 'Unauthorized: Equipment belongs to another campus.'], 403);
            }

            $newStatus = $_POST['is_active'] ?? 0;
            
            $success = $this->equipmentRepo->toggleActiveStatus((int)$id, (int)$newStatus);

            if ($success) {
                $action = $newStatus ? 'activated' : 'deactivated';
                $this->auditRepo->log($_SESSION['user_id'], 'TOGGLE_STATUS', 'EQUIPMENTS', $id, "Equipment $id was $action");
                $this->json(['success' => true, 'message' => "Equipment successfully $action"]);
            } else {
                $this->json(['success' => false, 'message' => 'Failed to toggle status'], 500);
            }
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id = null)
    {
        try {
            if (!$id) $this->json(['success' => false, 'message' => 'ID required'], 400);

            $equipment = $this->equipmentRepo->getById($id);
            if (!$equipment) {
                return $this->json(['success' => false, 'message' => 'Equipment not found'], 404);
            }

            $campusIdFilter = $this->getCampusFilter();
            if ($campusIdFilter !== null && $equipment['campus_id'] != $campusIdFilter) {
                return $this->json(['success' => false, 'message' => 'Unauthorized: Equipment belongs to another campus.'], 403);
            }

            $success = $this->equipmentRepo->deactivateEquipment((int)$id);

            if ($success) {
                $this->auditRepo->log($_SESSION['user_id'], 'DELETE', 'EQUIPMENTS', $id, "Deactivated equipment ID $id");
                $this->json(['success' => true, 'message' => 'Equipment deactivated successfully']);
            } else {
                $this->json(['success' => false, 'message' => 'Failed to deactivate equipment'], 500);
            }
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteMultiple()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $equipmentIds = $data['equipment_ids'] ?? [];

        $deletedByUserId = $_SESSION['user_id'] ?? null;
        if ($deletedByUserId === null) {
            return $this->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }

        if (empty($equipmentIds) || !is_array($equipmentIds)) {
            return $this->json(['success' => false, 'message' => 'No equipment IDs provided.'], 400);
        }

        $deletedCount = 0;
        $errors = [];

        foreach ($equipmentIds as $id) {
            try {
                $equipment = $this->equipmentRepo->getById($id);
                if (!$equipment) {
                    $errors[] = "Equipment ID $id not found.";
                    continue;
                }

                $success = $this->equipmentRepo->deactivateEquipment((int)$id);
                if ($success) {
                    $deletedCount++;
                    $this->auditRepo->log($deletedByUserId, 'DELETE', 'EQUIPMENTS', $id, "Deactivated equipment: {$equipment['equipment_name']}");
                } else {
                    $errors[] = "Failed to deactivate '{$equipment['equipment_name']}'.";
                }
            } catch (\Exception $e) {
                $errors[] = "Error deactivating ID $id: " . $e->getMessage();
            }
        }

        $response = [
            'success' => $deletedCount > 0,
            'message' => "Successfully deactivated $deletedCount equipment(s).",
            'deleted_count' => $deletedCount,
            'errors' => $errors
        ];

        return $this->json($response);
    }
}