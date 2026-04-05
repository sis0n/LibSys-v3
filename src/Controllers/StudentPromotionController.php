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
        $this->view('superadmin/studentPromotion', [
            'title' => 'Student Promotion',
            'currentPage' => 'studentPromotion',
            'courses' => $courses
        ]);
    }

    public function promote()
    {
        header('Content-Type: application/json');
        try {
            $studentId = $_POST['student_id'] ?? null;
            $adminId = $_SESSION['user_id'] ?? null;

            if (!$studentId || !$adminId) throw new Exception('Missing information.');

            $this->promotionService->promoteStudent((int)$studentId, (int)$adminId);
            echo json_encode(['success' => true, 'message' => 'Student promoted successfully!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function bulkPromote()
    {
        header('Content-Type: application/json');
        try {
            $courseId = $_POST['course_id'] ?? null;
            $yearLevel = $_POST['year_level'] ?? null;
            $adminId = $_SESSION['user_id'] ?? null;

            if (!$courseId || !$yearLevel || !$adminId) throw new Exception('Missing information.');

            $count = $this->promotionService->bulkPromote((int)$courseId, (int)$yearLevel, (int)$adminId);
            echo json_encode(['success' => true, 'message' => "Successfully promoted $count students!"]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function archiveGraduates()
    {
        header('Content-Type: application/json');
        try {
            $courseId = $_POST['course_id'] ?? null;
            $adminId = $_SESSION['user_id'] ?? null;

            if (!$courseId || !$adminId) throw new Exception('Missing information.');

            $count = $this->promotionService->archiveGraduates((int)$courseId, (int)$adminId);
            echo json_encode(['success' => true, 'message' => "Successfully archived $count graduates!"]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
