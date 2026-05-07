<?php
namespace App\Repositories;

use App\Core\Database; 
use PDO;
use Exception;

class CollegeCourseRepository
{
  private PDO $db;

  public function __construct()
  {
    $this->db = Database::getInstance()->getConnection();
  }

  public function getCourseById(int $courseId): ?array
  {
    try {
      $stmt = $this->db->prepare("
                SELECT course_id, course_code, course_title 
                FROM courses 
                WHERE course_id = :course_id
            ");
      $stmt->execute([':course_id' => $courseId]);
      $result = $stmt->fetch(PDO::FETCH_ASSOC);

      return $result ?: null;
    } catch (Exception $e) {
      error_log("Error in getCourseById: " . $e->getMessage());
      return null;
    }
  }

  public function getCollegeById(int $collegeId): ?array
  {
    try {
      $stmt = $this->db->prepare("
                SELECT college_id, college_code, college_name 
                FROM colleges 
                WHERE college_id = :college_id
            ");
      $stmt->execute([':college_id' => $collegeId]);
      $result = $stmt->fetch(PDO::FETCH_ASSOC);

      return $result ?: null;
    } catch (Exception $e) {
      error_log("Error in getCollegeById: " . $e->getMessage());
      return null;
    }
  }

  public function getAllColleges(): array
  {
    try {
      $stmt = $this->db->prepare("
                SELECT college_id, college_code, college_name 
                FROM colleges 
                ORDER BY college_code ASC
            ");
      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      error_log("Error in getAllColleges: " . $e->getMessage());
      return [];
    }
  }

  public function getCoursesByCollegeId(int $collegeId): array
  {
    try {
      $stmt = $this->db->prepare("
                SELECT course_id, course_code, course_title 
                FROM courses 
                WHERE college_id = :college_id
                ORDER BY course_code ASC
            ");

      $stmt->execute([':college_id' => $collegeId]);

      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      error_log("Error in getCoursesByCollegeId: " . $e->getMessage());
      return [];
    }
  }

  public function getAllCourses(): array
  {
    try {
      $stmt = $this->db->prepare("
            SELECT course_id, course_code, course_title 
            FROM courses 
            ORDER BY course_code ASC
        ");
      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      error_log("Error in getAllCourses: " . $e->getMessage());
      return [];
    }
  }
}
