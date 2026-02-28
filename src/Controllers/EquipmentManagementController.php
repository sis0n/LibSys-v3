<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\EquipmentManagementRepository;
use Exception;

class EquipmentManagementController extends Controller
{
    private $repo;

    public function __construct()
    {
        $this->repo = new EquipmentManagementRepository();
    }

    public function fetch()
    {
        header('Content-Type: application/json');
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? 'All Status';
        $sort   = $_GET['sort'] ?? 'default';
        $limit  = $_GET['limit'] ?? 30;
        $offset = $_GET['offset'] ?? 0;

        try {
            $equipments = $this->repo->fetchEquipments($search, $status, $sort, $limit, $offset);
            $totalCount = $this->repo->countEquipments($search, $status);

            echo json_encode([
                'success' => true,
                'equipments' => $equipments,
                'totalCount' => (int)$totalCount
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function store()
    {
        header('Content-Type: application/json');
        $data = [
            'equipment_name' => trim($_POST['equipment_name'] ?? ''),
            'asset_tag'      => trim($_POST['asset_tag'] ?? ''),
            'status'         => $_POST['status'] ?? 'available'
        ];

        if (empty($data['equipment_name'])) {
            echo json_encode(['success' => false, 'message' => 'Equipment name is required.']);
            return;
        }

        try {
            if ($this->repo->store($data)) {
                echo json_encode(['success' => true, 'message' => 'Equipment added successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add equipment.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function get($id)
    {
        header('Content-Type: application/json');
        try {
            $equipment = $this->repo->getById($id);
            if ($equipment) {
                echo json_encode(['success' => true, 'equipment' => $equipment]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Equipment not found.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function update($id)
    {
        header('Content-Type: application/json');
        $data = [
            'equipment_name' => trim($_POST['equipment_name'] ?? ''),
            'asset_tag'      => trim($_POST['asset_tag'] ?? ''),
            'status'         => $_POST['status'] ?? 'available'
        ];

        if (empty($data['equipment_name'])) {
            echo json_encode(['success' => false, 'message' => 'Equipment name is required.']);
            return;
        }

        try {
            if ($this->repo->update($id, $data)) {
                echo json_encode(['success' => true, 'message' => 'Equipment updated successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update equipment.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function toggleActive($id)
    {
        header('Content-Type: application/json');
        $newStatus = (int)($_POST['is_active'] ?? 0);
        try {
            if ($this->repo->toggleActiveStatus($id, $newStatus)) {
                $msg = $newStatus ? "Equipment activated." : "Equipment deactivated.";
                echo json_encode(['success' => true, 'message' => $msg]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to change status.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
