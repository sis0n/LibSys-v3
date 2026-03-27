<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class StudentRepository
{
  private PDO $db;

  public function __construct()
  {
    $this->db = Database::getInstance()->getConnection();
  }

  public function studentNumberExists(string $studentNumber): bool
  {
    $stmt = $this->db->prepare("
            SELECT s.student_id 
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            WHERE s.student_number = :student_number 
            AND u.is_archived = 0
            LIMIT 1
        ");
    $stmt->execute(['student_number' => $studentNumber]);
    return (bool) $stmt->fetch();
  }

  public function insertStudent(int $userId, string $studentNumber, ?int $courseId, int $yearLevel, string $status, string $campus = 'N/A', string $contact = 'N/A', string $section = 'N/A'): int
  {
    $stmt = $this->db->prepare("
    INSERT INTO students (user_id, student_number, course_id, year_level, status, campus, contact, section, can_edit_profile)
    VALUES (:user_id, :student_number, :course_id, :year_level, :status, :campus, :contact, :section, 1)
  ");

    $stmt->execute([
      ':user_id' => $userId,
      ':student_number' => $studentNumber,
      ':course_id' => $courseId,
      ':year_level' => $yearLevel,
      ':status' => $status,
      ':campus' => $campus,
      ':contact' => $contact,
      ':section' => $section
    ]);

    return (int)$this->db->lastInsertId();
  }

  public function bulkInsertStudentDetails(array $studentsBatch)
  {
    if (empty($studentsBatch)) return;

    $placeholders = [];
    $values = [];

    foreach ($studentsBatch as $s) {
      $placeholders[] = '(?, ?, ?, ?, ?, ?, ?, 1)'; // Removed 'campus' placeholder
      $values[] = $s['user_id'];
      $values[] = $s['student_number'];
      $values[] = $s['course_id'];
      $values[] = $s['year_level'];
      $values[] = $s['status'];
      // $values[] = $s['campus'] ?? 'N/A'; // Removed campus value
      $values[] = $s['contact'] ?? 'N/A';
      $values[] = $s['section'] ?? 'N/A';
    }

    // Removed 'campus' from the SQL columns list
    $sql = "INSERT IGNORE INTO students (user_id, student_number, course_id, year_level, status, contact, section, can_edit_profile) VALUES " . implode(',', $placeholders);
    $this->db->prepare($sql)->execute($values);
  }

  public function getStudentByUserId(int $userId)
  {
    $stmt = $this->db->prepare("SELECT * FROM students WHERE user_id = :user_id LIMIT 1");
    $stmt->execute([':user_id' => $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }
}
