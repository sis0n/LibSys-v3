<?php

namespace App\Services;

use App\Repositories\CampusRepository;
use App\Repositories\AuditLogRepository;
use Exception;

class CampusService
{
    private CampusRepository $campusRepo;
    private AuditLogRepository $auditRepo;

    public function __construct()
    {
        $this->campusRepo = new CampusRepository();
        $this->auditRepo = new AuditLogRepository();
    }

    /**
     * Get all campuses
     */
    public function getAllCampuses(): array
    {
        return $this->campusRepo->getAllCampuses();
    }

    /**
     * Create a new campus
     */
    public function createCampus(string $name, string $code, int $adminId): array
    {
        $name = trim($name);
        $code = strtoupper(trim($code));

        if (empty($name) || empty($code)) {
            throw new Exception('Both campus name and code are required.');
        }

        if ($this->campusRepo->getCampusByName($name)) {
            throw new Exception('Campus name already exists.');
        }

        if ($this->campusRepo->getCampusByCode($code)) {
            throw new Exception('Campus code already exists.');
        }

        if ($this->campusRepo->create($name, $code)) {
            $newCampus = $this->campusRepo->getCampusByCode($code);
            $this->auditRepo->log($adminId, 'CREATE', 'CAMPUS', $newCampus['campus_id'], "Created new campus: $name ($code)");
            return ['success' => true, 'message' => 'Campus created successfully!', 'campus' => $newCampus];
        }

        throw new Exception('Failed to create campus.');
    }

    /**
     * Update an existing campus
     */
    public function updateCampus(int $id, string $name, string $code, int $adminId): bool
    {
        $name = trim($name);
        $code = strtoupper(trim($code));

        if (empty($name) || empty($code)) {
            throw new Exception('Both campus name and code are required.');
        }

        $existingName = $this->campusRepo->getCampusByName($name);
        if ($existingName && $existingName['campus_id'] != $id) {
            throw new Exception('Campus name already exists.');
        }

        $existingCode = $this->campusRepo->getCampusByCode($code);
        if ($existingCode && $existingCode['campus_id'] != $id) {
            throw new Exception('Campus code already exists.');
        }

        $oldCampus = $this->campusRepo->getById($id);
        if (!$oldCampus) throw new Exception('Campus not found.');

        if ($this->campusRepo->update($id, $name, $code)) {
            $this->auditRepo->log($adminId, 'UPDATE', 'CAMPUS', $id, "Updated campus '{$oldCampus['campus_name']}' to '$name' and code '{$oldCampus['campus_code']}' to '$code'");
            return true;
        }

        throw new Exception('Failed to update campus.');
    }

    /**
     * Toggle campus active status
     */
    public function toggleStatus(int $id, int $adminId): string
    {
        $campus = $this->campusRepo->getById($id);
        if (!$campus) throw new Exception('Campus not found.');

        if ($this->campusRepo->toggleStatus($id)) {
            $updatedCampus = $this->campusRepo->getById($id);
            $newStatus = $updatedCampus['is_active'] ? 'Active' : 'Inactive';
            $this->auditRepo->log($adminId, 'TOGGLE_STATUS', 'CAMPUS', $id, "Set status of campus '{$campus['campus_name']}' to $newStatus");
            return $newStatus;
        }

        throw new Exception('Failed to update campus status.');
    }

    /**
     * Get campus by ID
     */
    public function getCampusById(int $id): ?array
    {
        return $this->campusRepo->getById($id);
    }
}
