<?php

namespace App\Services;

use App\Repositories\StudentPromotionRepository;
use App\Repositories\AuditLogRepository;
use Exception;

class PromotionService
{
    private StudentPromotionRepository $promotionRepo;
    private AuditLogRepository $auditRepo;

    public function __construct()
    {
        $this->promotionRepo = new StudentPromotionRepository();
        $this->auditRepo = new AuditLogRepository();
    }

    /**
     * Promote a single student
     */
    public function promoteStudent(int $studentId, int $adminId): bool
    {
        $student = $this->promotionRepo->getStudentById($studentId);
        if (!$student) throw new Exception('Student not found.');

        if ($this->promotionRepo->promote($studentId)) {
            $this->auditRepo->log($adminId, 'PROMOTE', 'STUDENTS', $studentId, "Promoted student {$student['first_name']} {$student['last_name']} to the next year level.");
            return true;
        }

        return false;
    }

    /**
     * Bulk promote students by course and year level
     */
    public function bulkPromote(int $courseId, int $yearLevel, int $adminId): int
    {
        $count = $this->promotionRepo->bulkPromote($courseId, $yearLevel);
        if ($count > 0) {
            $this->auditRepo->log($adminId, 'BULK_PROMOTE', 'STUDENTS', null, "Bulk promoted $count students from Course ID $courseId, Year Level $yearLevel.");
        }
        return $count;
    }

    /**
     * Archive/Graduate students (mark as inactive or specific status)
     */
    public function archiveGraduates(int $courseId, int $adminId): int
    {
        $count = $this->promotionRepo->archiveGraduates($courseId);
        if ($count > 0) {
            $this->auditRepo->log($adminId, 'ARCHIVE_GRADUATES', 'STUDENTS', null, "Archived $count graduating students from Course ID $courseId.");
        }
        return $count;
    }
}
