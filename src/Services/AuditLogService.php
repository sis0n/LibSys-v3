<?php

namespace App\Services;

use App\Repositories\AuditLogRepository;

class AuditLogService
{
    private AuditLogRepository $auditRepo;

    public function __construct()
    {
        $this->auditRepo = new AuditLogRepository();
    }

    /**
     * Log an activity
     */
    public function log(int $userId, string $action, string $module, ?string $itemId, string $details): void
    {
        $this->auditRepo->log($userId, strtoupper($action), strtoupper($module), $itemId, $details);
    }

    /**
     * Get paginated logs
     */
    public function getLogs(int $limit, int $offset, string $search = '', ?int $campusId = null): array
    {
        return $this->auditRepo->fetchLogs($search, $limit, $offset, '', '', $campusId);
    }

    /**
     * Count total logs for pagination
     */
    public function countLogs(string $search = '', ?int $campusId = null): int
    {
        return (int) $this->auditRepo->countLogs($search, '', '', $campusId);
    }
}
