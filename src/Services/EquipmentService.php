<?php

namespace App\Services;

use App\Repositories\EquipmentManagementRepository;
use App\Repositories\AuditLogRepository;
use Exception;

class EquipmentService
{
    private EquipmentManagementRepository $equipmentRepo;
    private AuditLogRepository $auditRepo;

    public function __construct()
    {
        $this->equipmentRepo = new EquipmentManagementRepository();
        $this->auditRepo = new AuditLogRepository();
    }

    /**
     * Get paginated equipment with filters
     */
    public function getPaginatedEquipment(array $params, ?int $campusId): array
    {
        $search = $params['search'] ?? '';
        $status = $params['status'] ?? 'All Status';
        $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
        $offset = isset($params['offset']) ? (int)$params['offset'] : 0;

        $equipments = $this->equipmentRepo->fetchEquipments($search, $status, 'default', $limit, $offset, $campusId);
        $totalCount = $this->equipmentRepo->countEquipments($search, $status, $campusId);

        return ['equipments' => $equipments, 'totalCount' => (int)$totalCount];
    }

    /**
     * Get equipment details by ID
     */
    public function getEquipmentDetails(int $id): array
    {
        $equipment = $this->equipmentRepo->getById($id);
        if (!$equipment) {
            throw new Exception('Equipment not found');
        }
        return $equipment;
    }

    /**
     * Create a new equipment
     */
    public function createEquipment(array $data, int $adminId, ?int $campusIdFilter): string
    {
        $campusId = $campusIdFilter ?? ($data['campus_id'] ?? null);
        $name = trim($data['equipment_name'] ?? '');

        if (empty($name)) throw new Exception('Equipment Name is required');
        if (empty($campusId)) throw new Exception('Campus selection is required');

        $assetTag = $this->generateAssetTag();
        $insertData = [
            'equipment_name' => $name,
            'campus_id'      => $campusId,
            'asset_tag'      => $assetTag,
            'status'         => $data['status'] ?? 'available',
            'is_active'      => 1
        ];

        if ($this->equipmentRepo->addEquipment($insertData)) {
            $this->auditRepo->log($adminId, 'ADD', 'EQUIPMENTS', $assetTag, "Added new equipment: $name");
            return $assetTag;
        }

        throw new Exception('Failed to add equipment');
    }

    /**
     * Update existing equipment
     */
    public function updateEquipment(int $id, array $data, int $adminId, ?int $campusIdFilter): bool
    {
        $equipment = $this->equipmentRepo->getById($id);
        if (!$equipment) throw new Exception('Equipment not found');

        if ($campusIdFilter !== null && $equipment['campus_id'] != $campusIdFilter) {
            throw new Exception('Unauthorized: Equipment belongs to another campus.');
        }

        $name = trim($data['equipment_name'] ?? '');
        $campusId = $campusIdFilter ?? ($data['campus_id'] ?? $equipment['campus_id']);

        if (empty($name)) throw new Exception('Equipment Name is required');
        if (empty($campusId)) throw new Exception('Campus selection is required');

        $updateData = [
            'equipment_name' => $name,
            'campus_id'      => $campusId,
            'status'         => $data['status'] ?? $equipment['status']
        ];

        if ($this->equipmentRepo->updateEquipment($id, $updateData)) {
            $this->auditRepo->log($adminId, 'UPDATE', 'EQUIPMENTS', $id, "Updated equipment ID $id: $name");
            return true;
        }

        return false;
    }

    /**
     * Deactivate equipment
     */
    public function deactivateEquipment(int $id, int $adminId, ?int $campusIdFilter): void
    {
        $equipment = $this->equipmentRepo->getById($id);
        if (!$equipment) throw new Exception('Equipment not found');

        if ($campusIdFilter !== null && $equipment['campus_id'] != $campusIdFilter) {
            throw new Exception('Unauthorized: Equipment belongs to another campus.');
        }

        if ($this->equipmentRepo->deactivateEquipment($id)) {
            $this->auditRepo->log($adminId, 'DELETE', 'EQUIPMENTS', $id, "Deactivated equipment: {$equipment['equipment_name']}");
        } else {
            throw new Exception('Failed to deactivate equipment');
        }
    }

    private function generateAssetTag(): string
    {
        $prefix = "EQP-" . date('Y') . "-";
        $isUnique = false;
        $newTag = "";

        while (!$isUnique) {
            $random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $newTag = $prefix . $random;
            if ($this->equipmentRepo->isAssetTagUnique($newTag)) {
                $isUnique = true;
            }
        }
        return $newTag;
    }
}
