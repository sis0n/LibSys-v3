<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\CampusRepository;
use App\Repositories\AuditLogRepository;

class CampusManagementController extends Controller
{
    private CampusRepository $campusRepo;
    private AuditLogRepository $auditRepo;

    public function __construct()
    {
    parent::__construct();
        $this->campusRepo = new CampusRepository();
        $this->auditRepo = new AuditLogRepository();
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
            $campuses = $this->campusRepo->getAllCampuses();
            echo json_encode(['success' => true, 'campuses' => $campuses]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function store()
    {
        header('Content-Type: application/json');
        try {
            $name = trim($_POST['campus_name'] ?? '');
            $code = strtoupper(trim($_POST['campus_code'] ?? ''));
            
            if (empty($name) || empty($code)) {
                echo json_encode(['success' => false, 'message' => 'Both campus name and code are required.']);
                exit;
            }

            if ($this->campusRepo->getCampusByName($name)) {
                echo json_encode(['success' => false, 'message' => 'Campus name already exists.']);
                exit;
            }

            if ($this->campusRepo->getCampusByCode($code)) {
                echo json_encode(['success' => false, 'message' => 'Campus code already exists.']);
                exit;
            }

            if ($this->campusRepo->create($name, $code)) {
                $newCampus = $this->campusRepo->getCampusByCode($code);
                $this->auditRepo->log(
                    $_SESSION['user_id'],
                    'create',
                    'campus',
                    $newCampus['campus_id'],
                    "Created new campus: $name ($code)"
                );
                echo json_encode(['success' => true, 'message' => 'Campus created successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create campus.']);
            }
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function update($id)
    {
        header('Content-Type: application/json');
        try {
            $name = trim($_POST['campus_name'] ?? '');
            $code = strtoupper(trim($_POST['campus_code'] ?? ''));
            
            if (empty($name) || empty($code)) {
                echo json_encode(['success' => false, 'message' => 'Both campus name and code are required.']);
                exit;
            }

            $existingName = $this->campusRepo->getCampusByName($name);
            if ($existingName && $existingName['campus_id'] != $id) {
                echo json_encode(['success' => false, 'message' => 'Campus name already exists.']);
                exit;
            }

            $existingCode = $this->campusRepo->getCampusByCode($code);
            if ($existingCode && $existingCode['campus_id'] != $id) {
                echo json_encode(['success' => false, 'message' => 'Campus code already exists.']);
                exit;
            }

            $oldCampus = $this->campusRepo->getById($id);
            if ($this->campusRepo->update($id, $name, $code)) {
                $this->auditRepo->log(
                    $_SESSION['user_id'],
                    'update',
                    'campus',
                    $id,
                    "Updated campus '{$oldCampus['campus_name']}' to '$name' and code '{$oldCampus['campus_code']}' to '$code'"
                );
                echo json_encode(['success' => true, 'message' => 'Campus updated successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update campus.']);
            }
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function toggleStatus($id)
    {
        header('Content-Type: application/json');
        try {
            $campus = $this->campusRepo->getById((int)$id);
            if (!$campus) {
                echo json_encode(['success' => false, 'message' => 'Campus not found.']);
                return;
            }

            if ($this->campusRepo->toggleStatus((int)$id)) {
                $updatedCampus = $this->campusRepo->getById((int)$id);
                $newStatus = $updatedCampus['is_active'] ? 'Active' : 'Inactive';
                
                $this->auditRepo->log(
                    $_SESSION['user_id'],
                    'toggle_status',
                    'campus',
                    $id,
                    "Set status of campus '{$campus['campus_name']}' to $newStatus"
                );
                
                echo json_encode([
                    'success' => true, 
                    'message' => "Campus status updated to $newStatus.",
                    'newStatus' => $newStatus
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update campus status.']);
            }
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // Keep destroy but make it unused or protected
    public function destroy($id)
    {
        // Not implemented in the new design (Use toggleStatus instead)
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Direct deletion is no longer allowed. Use deactivation instead.']);
        exit;
    }
}
