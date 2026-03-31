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

    private function checkAccess(array $allowedRoles)
    {
        $role = strtolower(str_replace([' ', '-'], '_', $_SESSION['role'] ?? ''));
        if (!in_array($role, $allowedRoles)) {
            $this->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }
    }

    public function index()
    {
        $this->checkAccess(['superadmin', 'admin', 'campus_admin', 'librarian']);
        
        $allCampuses = $this->campusRepo->getAllCampuses();
        $activeCampuses = array_filter($allCampuses, fn($c) => $c['is_active'] == 1);

        $campusFilter = $this->getCampusFilter();
        $selectedCampusId = isset($_GET['campus_id']) ? (int)$_GET['campus_id'] : ($campusFilter ?? 1);

        // If campus_admin/librarian, they can only see their own campus
        if ($campusFilter && $selectedCampusId != $campusFilter) {
            $selectedCampusId = $campusFilter;
        }

        $policies = $this->policyRepo->getPoliciesByCampus($selectedCampusId);
        
        $this->view("SuperAdmin/libraryPolicies", [
            "policies" => $policies,
            "campuses" => $activeCampuses,
            "selectedCampusId" => $selectedCampusId,
            "title" => "Library Policy Management",
            "isViewOnly" => !in_array(strtolower(str_replace([' ', '-'], '_', $_SESSION['role'] ?? '')), ['superadmin', 'admin'])
        ]);
    }

    public function getAll()
    {
        $this->checkAccess(['superadmin', 'admin', 'campus_admin', 'librarian']);
        
        $campusFilter = $this->getCampusFilter();
        $campusId = !empty($_GET['campus_id']) ? (int)$_GET['campus_id'] : ($campusFilter ?? 1);

        if ($campusFilter && $campusId != $campusFilter) {
            $campusId = $campusFilter;
        }

        $policies = $this->policyRepo->getPoliciesByCampus($campusId);
        $this->json(['success' => true, 'policies' => $policies]);
    }

    public function update()
    {
        $this->checkAccess(['superadmin', 'admin']);

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