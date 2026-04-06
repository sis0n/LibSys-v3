<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class FacultyProfileRepository
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
            u.campus_id,
            u.profile_picture,
            u.role,
            u.is_active,
            f.faculty_id,
            f.unique_faculty_id,
            f.college_id,         
            f.contact,
            f.profile_updated,
            c.college_code,         
            c.college_name,
            cp.campus_name
        FROM users u
        LEFT JOIN faculty f ON u.user_id = f.user_id
        LEFT JOIN colleges c ON f.college_id = c.college_id /* JOIN sa colleges para sa pangalan */
        LEFT JOIN campuses cp ON u.campus_id = cp.campus_id
        WHERE u.user_id = :userId AND u.deleted_at IS NULL
    ");
    $stmt->execute([':userId' => $userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        if ($result['college_code'] && $result['college_name']) {
            $result['college_full_name'] = $result['college_code'] . ' - ' . $result['college_name'];
        }

        // --- BORROWING QUALIFICATION CHECK ---
        $requiredFields = [
            'first_name', 'last_name', 'email', 'profile_picture',
            'college_id', 'contact'
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

  public function updateFacultyProfile(int $userId, array $data): bool
  {
    $allowedFields = [
      'college_id',
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

    $sql = "UPDATE faculty SET " . implode(", ", $sqlParts) . " WHERE user_id = :user_id";
    $stmt = $this->db->prepare($sql);
    return $stmt->execute($params);
  }

  public function setEditAccess(int $userId, bool $allow = true): bool
  {
    $stmt = $this->db->prepare("
            UPDATE faculty 
            SET profile_updated = :allow
            WHERE user_id = :user_id
        ");
    return $stmt->execute([
      ':allow' => $allow ? 0 : 1,
      ':user_id' => $userId
    ]);
  }
}
