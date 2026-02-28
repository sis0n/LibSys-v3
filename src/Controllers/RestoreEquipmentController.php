<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\RestoreEquipmentRepository;
use Exception;

class RestoreEquipmentController extends Controller
{
    private $repo;

    public function __construct()
    {
        $this->repo = new RestoreEquipmentRepository();
    }

    public function fetchDeleted()
    {
        header('Content-Type: application/json');
        try {
            $equipments = $this->repo->getDeletedEquipments();
            echo json_encode([
                'success' => true,
                'equipments' => $equipments
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function restore($id)
    {
        header('Content-Type: application/json');
        try {
            if ($this->repo->restoreEquipment($id)) {
                echo json_encode(['success' => true, 'message' => 'Equipment restored successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to restore equipment.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deletePermanently($id)
    {
        header('Content-Type: application/json');
        try {
            if ($this->repo->permanentDelete($id)) {
                echo json_encode(['success' => true, 'message' => 'Equipment permanently deleted.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete equipment permanently.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
