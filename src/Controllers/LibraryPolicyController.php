<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\LibraryPolicyRepository;

class LibraryPolicyController extends Controller
{
    private LibraryPolicyRepository $policyRepo;
    private \App\Repositories\AuditLogRepository $auditRepo;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->policyRepo = new LibraryPolicyRepository();
        $this->auditRepo = new \App\Repositories\AuditLogRepository();
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
        $policies = $this->policyRepo->getAllPolicies();
        $this->view("SuperAdmin/libraryPolicies", [
            "policies" => $policies,
            "title" => "Library Policy Management"
        ]);
    }

    public function getAll()
    {
        $this->ensureSuperAdmin();
        $policies = $this->policyRepo->getAllPolicies();
        $this->json(['success' => true, 'policies' => $policies]);
    }

    public function update()
    {
        $this->ensureSuperAdmin();

        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['role']) || !isset($data['max_books']) || !isset($data['borrow_duration_days'])) {
            $this->json(['success' => false, 'message' => 'Invalid data provided'], 400);
        }

        $role = strtolower($data['role']);
        $maxBooks = (int)$data['max_books'];
        $durationDays = (int)$data['borrow_duration_days'];

        if ($maxBooks < 1) {
            $this->json(['success' => false, 'message' => 'Max items must be at least 1'], 400);
        }

        if ($role !== 'equipment' && $durationDays < 1) {
            $this->json(['success' => false, 'message' => 'Borrow duration must be at least 1 day'], 400);
        }

        if ($role === 'equipment' && $durationDays < 0) {
            $this->json(['success' => false, 'message' => 'Duration cannot be negative'], 400);
        }

        $success = $this->policyRepo->updatePolicy($role, $maxBooks, $durationDays);

        if ($success) {
            $this->auditRepo->log($_SESSION['user_id'], 'UPDATE', 'POLICIES', $role, "Updated policy for $role: Max = $maxBooks, Duration = $durationDays days");
            $this->json(['success' => true, 'message' => 'Policy updated successfully']);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to update policy'], 500);
        }
    }
}
