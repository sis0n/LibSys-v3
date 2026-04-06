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

    public function getStudentsForPromotion(array $filters, int $limit, int $offset): array
    {
        $students = $this->promotionRepo->fetchStudents($filters, $limit, $offset);
        $totalCount = $this->promotionRepo->countStudents($filters);
        
        return [
            'students' => $students,
            'totalCount' => (int)$totalCount,
            'totalPages' => ceil($totalCount / $limit)
        ];
    }

    public function getPromotionStats(int $status = 1, ?int $campusId = null): array
    {
        return $this->promotionRepo->getYearLevelStats($status, $campusId);
    }

    public function processBulkPromotion(array $data, int $adminId): int
    {
        $count = 0;
        if ($data['is_all']) {
            $count = $this->promotionRepo->bulkPromoteByFilter($data['filters']);
        } else {
            $count = $this->promotionRepo->bulkPromote($data['student_ids']);
        }

        if ($count > 0) {
            $this->auditRepo->log($adminId, 'BULK_PROMOTE', 'STUDENTS', null, "Bulk promoted $count students.");
        }
        return (int)$count;
    }

    public function processBulkDeactivation(array $data, int $adminId): int
    {
        $count = 0;
        if ($data['is_all']) {
            $count = $this->promotionRepo->bulkDeactivateByFilter($data['filters']);
        } else {
            $count = $this->promotionRepo->bulkDeactivate($data['student_ids']);
        }

        if ($count > 0) {
            $this->auditRepo->log($adminId, 'BULK_DEACTIVATE', 'STUDENTS', null, "Bulk deactivated $count students.");
        }
        return (int)$count;
    }

    public function processBulkActivation(array $data, int $adminId): int
    {
        $count = 0;
        if ($data['is_all']) {
            $count = $this->promotionRepo->bulkActivateByFilter($data['filters']);
        } else {
            $count = $this->promotionRepo->bulkActivate($data['student_ids']);
        }

        if ($count > 0) {
            $this->auditRepo->log($adminId, 'BULK_ACTIVATE', 'STUDENTS', null, "Bulk activated $count students.");
        }
        return (int)$count;
    }

    /**
     * Promote a single student
     */
    public function promoteStudent(int $studentId, int $adminId): bool
    {
        if ($this->promotionRepo->bulkPromote([$studentId])) {
            $this->auditRepo->log($adminId, 'PROMOTE', 'STUDENTS', $studentId, "Promoted student ID $studentId to the next year level.");
            return true;
        }

        return false;
    }
}
