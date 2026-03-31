<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\LibraryPolicyRepository;

class LibraryPolicyController extends Controller
{
    private LibraryPolicyRepository $policyRepo;
    private \App\Repositories\AuditLogRepository $auditRepo;
    private \App\Repositories\CampusRepository $campusRepo;

    public function __construct()
    {
    parent::__construct();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->policyRepo = new LibraryPolicyRepository();
        $this->auditRepo = new \App\Repositories\AuditLogRepository();
        $this->campusRepo = new \App\Repositories\CampusRepository();
    }

    private function json($data, int $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    private function ensureSuperAdmin()
    {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
            $this->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }
    }

    public function index()
    {
        $this->ensureSuperAdmin();
        $campuses = $this->campusRepo->getAllCampuses();
        $campusId = isset($_GET['campus_id']) ? (int)$_GET['campus_id'] : 1;
        $policies = $this->policyRepo->getPoliciesByCampus($campusId);
        
        $this->view("SuperAdmin/libraryPolicies", [
            "policies" => $policies,
            "campuses" => $campuses,
            "selectedCampusId" => $campusId,
            "title" => "Library Policy Management"
        ]);
    }

    public function getAll()
    {
        $this->ensureSuperAdmin();
        // Gumamit ng !empty para kung empty string ang maipasa, mag-default sa 1
        $campusId = !empty($_GET['campus_id']) ? (int)$_GET['campus_id'] : 1;
        $policies = $this->policyRepo->getPoliciesByCampus($campusId);
        $this->json(['success' => true, 'policies' => $policies]);
    }

    public function update()
    {
        $this->ensureSuperAdmin();

        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['role']) || !isset($data['max_books']) || !isset($data['borrow_duration_days']) || !isset($data['campus_id'])) {
            $this->json(['success' => false, 'message' => 'Invalid data provided'], 400);
        }

        $role = strtolower($data['role']);
        $maxBooks = (int)$data['max_books'];
        $durationDays = (int)$data['borrow_duration_days'];
        $campusId = (int)$data['campus_id'];

        if ($maxBooks < 1) {
            $this->json(['success' => false, 'message' => 'Max items must be at least 1'], 400);
        }

        if ($role !== 'equipment' && $durationDays < 1) {
            $this->json(['success' => false, 'message' => 'Borrow duration must be at least 1 day'], 400);
        }

        if ($role === 'equipment' && $durationDays < 0) {
            $this->json(['success' => false, 'message' => 'Duration cannot be negative'], 400);
        }

        $success = $this->policyRepo->updatePolicy($role, $maxBooks, $durationDays, $campusId);

        if ($success) {
            $campus = $this->campusRepo->getById($campusId);
            $campusName = $campus['campus_name'] ?? "Campus #$campusId";
            $this->auditRepo->log($_SESSION['user_id'], 'UPDATE', 'POLICIES', $role, "Updated policy for $role ($campusName): Max = $maxBooks, Duration = $durationDays days");
            $this->json(['success' => true, 'message' => 'Policy updated successfully']);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to update policy'], 500);
        }
    }
}
