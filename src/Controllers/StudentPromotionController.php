<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\PromotionService;
use App\Repositories\CollegeCourseRepository;
use Exception;

class StudentPromotionController extends Controller
{
    private PromotionService $promotionService;
    private CollegeCourseRepository $courseRepo;

    public function __construct()
    {
        parent::__construct();
        $this->promotionService = new PromotionService();
        $this->courseRepo = new CollegeCourseRepository();
    }

    public function index()
    {
        $courses = $this->courseRepo->getAllCourses();
        $role = strtolower(str_replace([' ', '-', '_'], '', $_SESSION['role'] ?? ''));
        $viewPath = ($role === 'superadmin' || $role === 'admin') ? 'Superadmin/studentPromotion' : 'campus_admin/studentPromotion';

        $this->view($viewPath, [
            'title' => 'Student Promotion',
            'currentPage' => 'studentPromotion',
            'courses' => $courses
        ]);
    }

    public function fetch()
    {
        try {
            $campusId = $this->getCampusFilter();
            
            $filters = [
                'search' => $_GET['search'] ?? '',
                'course_id' => $_GET['course_id'] ?? '',
                'campus_id' => $campusId ?? $_GET['campus_id'] ?? '',
                'year_level' => $_GET['year_level'] ?? '',
                'status' => $_GET['status'] ?? 1
            ];

            $limit = (int)($_GET['limit'] ?? 100);
            $offset = (int)($_GET['offset'] ?? 0);

            $result = $this->promotionService->getStudentsForPromotion($filters, $limit, $offset);
            $stats = $this->promotionService->getPromotionStats((int)$filters['status'], $filters['campus_id'] ?: null);

            return $this->jsonResponse([
                'students' => $result['students'],
                'totalCount' => $result['totalCount'],
                'totalPages' => $result['totalPages'],
                'stats' => $stats
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function promote()
    {
        try {
            $data = $this->getJsonData();
            $adminId = $_SESSION['user_id'] ?? null;
            if (!$adminId) throw new Exception('Unauthorized.');

            $count = $this->promotionService->processBulkPromotion($data, $adminId);
            return $this->jsonResponse(['message' => "Successfully promoted $count students!"]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function deactivate()
    {
        try {
            $data = $this->getJsonData();
            $adminId = $_SESSION['user_id'] ?? null;
            if (!$adminId) throw new Exception('Unauthorized.');

            $count = $this->promotionService->processBulkDeactivation($data, $adminId);
            return $this->jsonResponse(['message' => "Successfully deactivated $count students!"]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function activate()
    {
        try {
            $data = $this->getJsonData();
            $adminId = $_SESSION['user_id'] ?? null;
            if (!$adminId) throw new Exception('Unauthorized.');

            $count = $this->promotionService->processBulkActivation($data, $adminId);
            return $this->jsonResponse(['message' => "Successfully activated $count students!"]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
