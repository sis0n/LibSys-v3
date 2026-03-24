<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class StudentProfileRepository
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
            s.student_id,
            s.student_number,
            s.course_id, 
            s.year_level,
            s.section,
            s.contact,
            s.registration_form,
            s.profile_updated,
            s.can_edit_profile,
            c.course_code,
            c.course_title
        FROM users u
        LEFT JOIN students s ON u.user_id = s.user_id
        LEFT JOIN courses c ON s.course_id = c.course_id
        WHERE u.user_id = :userId AND u.deleted_at IS NULL
    ");
    $stmt->execute([':userId' => $userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        if ($result['course_code'] && $result['course_title']) {
            $result['course_full_name'] = $result['course_code'] . ' - ' . $result['course_title'];
        }

        $requiredFields = [
            'first_name', 'last_name', 'email', 'profile_picture',
            'course_id', 'year_level', 'section', 'contact', 'registration_form'
        ];
        
        $isQualified = true;
        foreach ($requiredFields as $field) {
            if (empty($result[$field])) {
                $isQualified = false;
                break;
            }
        }
        $result['is_qualified'] = $isQualified;
    }

    return $result ?: null;
  }

  public function updateStudentProfile(int $userId, array $data): bool
  {
    $fields = [];
    $params = [':userId' => $userId];

    $allowedFields = [
      'course_id',
      'year_level',
      'section',
      'contact',
      'profile_updated',
      'can_edit_profile',
      'registration_form' 
    ];

    foreach ($data as $key => $value) {
      if (in_array($key, $allowedFields)) {
        $fields[] = "{$key} = :{$key}";
        $params[":{$key}"] = $value;
      }
    }

    if (empty($fields)) {
      return true; 
    }

    $sql = "UPDATE students SET " . implode(', ', $fields) . " WHERE user_id = :userId";

    $stmt = $this->db->prepare($sql);

    return $stmt->execute($params);
  }

  public function setEditAccess(int $userId, bool $allow = true): bool
  {
    $stmt = $this->db->prepare("
            UPDATE students 
            SET can_edit_profile = :allow 
            WHERE user_id = :user_id
        ");
    return $stmt->execute([
      ':allow' => $allow ? 1 : 0,
      ':user_id' => $userId
    ]);
  }
}
