<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\StudentPromotionRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\CampusRepository;

class StudentPromotionController extends Controller
{
    private $promoRepo;
    private $auditRepo;
    private $campusRepo;

    public function __construct()
    {
    parent::__construct();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $role = strtolower(str_replace([' ', '-'], '_', $_SESSION['role'] ?? ''));
        if (in_array($role, ['librarian', 'student', 'faculty', 'staff', 'scanner'])) {
            http_response_code(403);
            die("Forbidden: Access denied.");
        }

        $this->promoRepo = new StudentPromotionRepository();
        $this->auditRepo = new \App\Repositories\AuditLogRepository();
        $this->campusRepo = new CampusRepository();
    }

    public function index()
    {
        $allCampuses = $this->campusRepo->getAllCampuses();
        $activeCampuses = array_filter($allCampuses, fn($c) => $c['is_active'] == 1);

        $campusId = $this->getCampusFilter();
        if ($campusId) {
            $activeCampuses = array_filter($activeCampuses, fn($c) => $c['campus_id'] == $campusId);
        }

        $this->view('superadmin/studentPromotion', [
            'title' => 'Student Promotion',
            'currentPage' => 'studentPromotion',
            'campuses' => $activeCampuses
        ]);
    }

    public function fetch()
    {
        header('Content-Type: application/json');
        try {
            $campusIdFilter = $this->getCampusFilter();
            $requestedCampusId = $_GET['campus_id'] ?? null;
            
            // Enforce campus filter
            $campusId = $campusIdFilter ?? $requestedCampusId;

            $filters = [
                'course_id' => $_GET['course_id'] ?? null,
                'campus_id' => $campusId,
                'year_level' => $_GET['year_level'] ?? null,
                'search' => $_GET['search'] ?? '',
                'status' => $_GET['status'] ?? 1
            ];
            $limit = (int)($_GET['limit'] ?? 100);
            $offset = (int)($_GET['offset'] ?? 0);
            $page = (int)($_GET['page'] ?? 1);
            
            if ($page > 1) {
                $offset = ($page - 1) * $limit;
            }

            $students = $this->promoRepo->fetchStudents($filters, $limit, $offset);
            $totalCount = $this->promoRepo->countStudents($filters);
            $stats = $this->promoRepo->getYearLevelStats($filters['status'], $filters['campus_id']);

            echo json_encode([
                'success' => true,
                'students' => $students,
                'totalCount' => (int)$totalCount,
                'totalPages' => ceil($totalCount / $limit),
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function promote()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents("php://input"), true);
        $isAll = $data['is_all'] ?? false;
        $ids = $data['student_ids'] ?? [];
        $filters = $data['filters'] ?? [];

        try {
            if ($isAll) {
                $count = $this->promoRepo->countStudents($filters);
                if ($this->promoRepo->bulkPromoteByFilter($filters)) {
                    $this->auditRepo->log($_SESSION['user_id'], 'PROMOTE_ALL', 'STUDENTS', null, "Global promotion: Promoted all $count matching students.");
                    echo json_encode(['success' => true, 'message' => "Successfully promoted all $count matching students!"]);
                }
            } else {
                if (empty($ids)) {
                    echo json_encode(['success' => false, 'message' => 'No students selected.']);
                    return;
                }
                if ($this->promoRepo->bulkPromote($ids)) {
                    $this->auditRepo->log($_SESSION['user_id'], 'PROMOTE', 'STUDENTS', count($ids) . ' items', "Bulk promoted " . count($ids) . " selected students.");
                    echo json_encode(['success' => true, 'message' => 'Selected students promoted successfully!']);
                }
            }
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deactivate()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents("php://input"), true);
        $isAll = $data['is_all'] ?? false;
        $ids = $data['student_ids'] ?? [];
        $filters = $data['filters'] ?? [];

        try {
            if ($isAll) {
                $count = $this->promoRepo->countStudents($filters);
                if ($this->promoRepo->bulkDeactivateByFilter($filters)) {
                    $this->auditRepo->log($_SESSION['user_id'], 'DEACTIVATE_ALL', 'STUDENTS', null, "Global deactivation: Deactivated all $count matching students.");
                    echo json_encode(['success' => true, 'message' => "Successfully deactivated all $count matching students!"]);
                }
            } else {
                if (empty($ids)) {
                    echo json_encode(['success' => false, 'message' => 'No students selected.']);
                    return;
                }
                if ($this->promoRepo->bulkDeactivate($ids)) {
                    $this->auditRepo->log($_SESSION['user_id'], 'DEACTIVATE', 'STUDENTS', count($ids) . ' items', "Bulk deactivated " . count($ids) . " selected students.");
                    echo json_encode(['success' => true, 'message' => 'Selected students deactivated successfully!']);
                }
            }
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function activate()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents("php://input"), true);
        $isAll = $data['is_all'] ?? false;
        $ids = $data['student_ids'] ?? [];
        $filters = $data['filters'] ?? [];

        try {
            if ($isAll) {
                $count = $this->promoRepo->countStudents($filters);
                if ($this->promoRepo->bulkActivateByFilter($filters)) {
                    $this->auditRepo->log($_SESSION['user_id'], 'ACTIVATE_ALL', 'STUDENTS', null, "Global activation: Activated all $count matching students.");
                    echo json_encode(['success' => true, 'message' => "Successfully activated all $count matching students!"]);
                }
            } else {
                if (empty($ids)) {
                    echo json_encode(['success' => false, 'message' => 'No students selected.']);
                    return;
                }
                if ($this->promoRepo->bulkActivate($ids)) {
                    $this->auditRepo->log($_SESSION['user_id'], 'ACTIVATE', 'STUDENTS', count($ids) . ' items', "Bulk activated " . count($ids) . " selected students.");
                    echo json_encode(['success' => true, 'message' => 'Selected students activated successfully!']);
                }
            }
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
