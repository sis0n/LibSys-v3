<?php

namespace App\Services;

use App\Repositories\OverdueRepository;
use App\Services\NotificationService;
use Exception;

class OverdueService
{
    private OverdueRepository $overdueRepo;
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->overdueRepo = new OverdueRepository();
        $this->notificationService = new NotificationService();
    }

    /**
     * Get paginated overdue items
     */
    public function getOverdueItems(array $params, ?int $campusId): array
    {
        $limit = (int)($params['limit'] ?? 10);
        $offset = (int)($params['offset'] ?? 0);
        $search = $params['search'] ?? '';

        $items = $this->overdueRepo->getOverduePaginated($limit, $offset, $search, $campusId);
        $totalCount = $this->overdueRepo->countOverdue($search, $campusId);

        return ['items' => $items, 'totalCount' => $totalCount];
    }

    /**
     * Send overdue notice to a borrower
     */
    public function notifyBorrower(string $email, string $name, string $itemTitle, string $dueDate): bool
    {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid or missing email address.');
        }

        return $this->notificationService->sendOverdueNotice($email, $name, $itemTitle, $dueDate);
    }

    /**
     * Mark an item as lost
     */
    /**
     * Get overdue stats (total, high priority, etc)
     */
    public function getOverdueStats(?int $campusId): array
    {
        return $this->overdueRepo->getOverdueStats($campusId);
    }

    /**
     * Fetch overdue list with filters
     */
    public function fetchOverdueList(array $filters, ?int $campusId): array
    {
        return $this->overdueRepo->fetchOverdueList($filters, $campusId);
    }

    /**
     * Log a notification sent to a user
     */
    public function logNotification(?int $itemId, ?int $userId, string $email, int $adminId): void
    {
        $this->overdueRepo->logNotification($itemId, $userId, $email, $adminId);
    }
}
