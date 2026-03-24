<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class StaffProfileRepository
{
  private $db;

  public function __construct()
  {
    $this->db = Database::getInstance()->getConnection();
  }

  public function getProfileByUserId(int $userId): ?array
  {
    $stmt = $this->db->prepare("
            SELECT 
                u.user_id,
                u.username,
                u.first_name,
                u.middle_name,
                u.last_name,
                u.suffix,
                u.gender,
                u.email,
                u.profile_picture,
                u.role,
                u.is_active,
                s.employee_id,
                s.position,
                s.contact,
                s.status,
                s.profile_updated
            FROM users u
            LEFT JOIN staff s ON u.user_id = s.user_id
            WHERE u.user_id = :userId AND u.deleted_at IS NULL
        ");
    $stmt->execute([':userId' => $userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // --- BORROWING QUALIFICATION CHECK ---
        $requiredFields = [
            'first_name', 'last_name', 'email', 'profile_picture',
            'position', 'contact'
        ];
        
        $isQualified = true;
        foreach ($requiredFields as $field) {
            if (empty($result[$field])) {
                $isQualified = false;
                break;
            }
        }
        $result['is_qualified'] = $isQualified;
        // --- END OF CHECK ---
    }

    return $result ?: null;
  }

  public function updateStaffProfile(int $userId, array $data): bool
  {
    $allowedFields = [
      'position',
      'contact',
      'profile_updated',
      'status'
    ];

    $sqlParts = [];
    $params = [':user_id' => $userId];

    foreach ($allowedFields as $field) {
      if (isset($data[$field])) {
        $sqlParts[] = "$field = :$field";
        $params[":$field"] = $data[$field];
      }
    }

    if (empty($sqlParts)) return true;

    $sql = "UPDATE staff SET " . implode(", ", $sqlParts) . " WHERE user_id = :user_id";
    $stmt = $this->db->prepare($sql);
    return $stmt->execute($params);
  }

  public function setEditAccess(int $userId, bool $allow = true): bool
  {
    $stmt = $this->db->prepare("
            UPDATE staff 
            SET profile_updated = :allow
            WHERE user_id = :user_id
        ");
    return $stmt->execute([
      ':allow' => $allow ? 0 : 1, 
      ':user_id' => $userId
    ]);
  }
}
