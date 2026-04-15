<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

class RestoreUserRepository
{
  protected PDO $db;

  public function __construct()
  {
    $this->db = Database::getInstance()->getConnection();
  }

  public function getDeletedUsers(?int $campusId = null): array
  {
    $sql = "SELECT 
                    u.user_id as id, 
                    CONCAT(u.first_name, ' ', u.last_name) as fullname, 
                    u.username, 
                    u.role, 
                    u.email,
                    u.campus_id,
                    s.contact, 
                    u.created_at as created_date,
                    u.deleted_at as deleted_date,
                    u.deleted_by as deleted_by_id, 
                    CONCAT(librarian.first_name, ' ', librarian.last_name) as deleted_by_name 
                FROM users u
                LEFT JOIN users librarian ON u.deleted_by = librarian.user_id 
                LEFT JOIN students s ON u.user_id = s.user_id 
                WHERE u.deleted_at IS NOT NULL AND u.is_archived = 0";

    if ($campusId !== null) {
      $sql .= " AND u.campus_id = :campus_id";
    }

    $sql .= " ORDER BY u.deleted_at DESC";

    try {
      $stmt = $this->db->prepare($sql);
      if ($campusId !== null) {
        $stmt->bindValue(':campus_id', $campusId, PDO::PARAM_INT);
      }
      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      error_log("Error fetching soft-deleted users: " . $e->getMessage());
      return [];
    }
  }

  public function getUserById(int $id)
  {
    $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function restoreUser(int $userId): bool
  {
    $this->db->beginTransaction();
    try {
      $sqlUser = "UPDATE users 
                        SET deleted_at = NULL, deleted_by = NULL 
                        WHERE user_id = :id AND deleted_at IS NOT NULL AND is_archived = 0";
      $stmtUser = $this->db->prepare($sqlUser);
      $stmtUser->execute(['id' => $userId]);

      if ($stmtUser->rowCount() === 0) {
        $this->db->rollBack();
        error_log("Restore failed: User $userId not found, not soft-deleted, or already archived.");
        return false;
      }
      $sqlStudent = "UPDATE students 
                           SET deleted_at = NULL, deleted_by = NULL 
                           WHERE user_id = :id AND deleted_at IS NOT NULL";
      $stmtStudent = $this->db->prepare($sqlStudent);
      $stmtStudent->execute(['id' => $userId]);

      $this->db->commit();
      return true;
    } catch (PDOException $e) {
      $this->db->rollBack();
      error_log("Error restoring user $userId: " . $e->getMessage());
      return false;
    }
  }

  public function archiveUser(int $userId, int $librarianId): array
  {
    $this->db->beginTransaction();
    try {
      $stmtGetUser = $this->db->prepare("SELECT user_id, username, first_name, middle_name, last_name, suffix, email, role, deleted_at FROM users WHERE user_id = :id AND deleted_at IS NOT NULL AND is_archived = 0");
      $stmtGetUser->execute(['id' => $userId]);
      $userData = $stmtGetUser->fetch(PDO::FETCH_ASSOC);

      if (!$userData) {
        $this->db->rollBack();
        $stmtCheckExists = $this->db->prepare("SELECT user_id, deleted_at, is_archived FROM users WHERE user_id = :id");
        $stmtCheckExists->execute(['id' => $userId]);
        $existingUser = $stmtCheckExists->fetch(PDO::FETCH_ASSOC);

        $reason = "User $userId not found.";
        if ($existingUser && $existingUser['deleted_at'] === null) {
          $reason = "User $userId exists but is not soft-deleted.";
        } elseif ($existingUser && $existingUser['is_archived'] == 1) {
          $reason = "User $userId is already archived.";
        } elseif ($existingUser) {
          $reason = "User $userId exists but failed archive pre-check.";
        }

        return ['success' => false, 'debug_reason' => $reason, 'debug_data' => $existingUser ?: null];
      }

      $stmtInsertDeletedUser = $this->db->prepare(
        "INSERT INTO deleted_users (user_id, username, first_name, middle_name, last_name, email, role, deleted_at, deleted_by) 
                 VALUES (:user_id, :username, :first_name, :middle_name, :last_name, :email, :role, :deleted_at, :deleted_by)"
      );
      $insertSuccessUser = $stmtInsertDeletedUser->execute([
        ':user_id' => $userData['user_id'],
        ':username' => $userData['username'],
        ':first_name' => $userData['first_name'],
        ':middle_name' => $userData['middle_name'],
        ':last_name' => $userData['last_name'],
        ':email' => $userData['email'],
        ':role' => $userData['role'],
        ':deleted_at' => $userData['deleted_at'],
        ':deleted_by' => $librarianId
      ]);

      if (!$insertSuccessUser) {
        $this->db->rollBack();
        return ['success' => false, 'debug_reason' => 'Failed to insert into deleted_users table.', 'debug_data' => $stmtInsertDeletedUser->errorInfo()];
      }


      if (isset($userData['role']) && strtolower($userData['role']) === 'student') {
        $stmtGetStudent = $this->db->prepare("SELECT student_id, user_id, student_number, course_id, year_level, section, status, deleted_at FROM students WHERE user_id = :id AND deleted_at IS NOT NULL");
        $stmtGetStudent->execute(['id' => $userId]);
        $studentData = $stmtGetStudent->fetch(PDO::FETCH_ASSOC);

        if ($studentData) {
          $stmtInsertDeletedStudent = $this->db->prepare(
            "INSERT INTO deleted_students (student_id, user_id, student_number, course_id, year_level, section, status, deleted_at, deleted_by)
                            VALUES (:student_id, :user_id, :student_number, :course_id, :year_level, :section, :status, :deleted_at, :deleted_by)"
          );
          $insertSuccessStudent = $stmtInsertDeletedStudent->execute([
            ':student_id' => $studentData['student_id'],
            ':user_id' => $studentData['user_id'],
            ':student_number' => $studentData['student_number'],
            ':course_id' => $studentData['course_id'],
            ':year_level' => $studentData['year_level'],
            ':section' => $studentData['section'] ?? null,
            ':status' => $studentData['status'],
            ':deleted_at' => $studentData['deleted_at'],
            ':deleted_by' => $librarianId
          ]);

          if (!$insertSuccessStudent) {
            $this->db->rollBack();
            return ['success' => false, 'debug_reason' => 'Failed to insert into deleted_students table.', 'debug_data' => $stmtInsertDeletedStudent->errorInfo()];
          }
        }
      }

      $stmtMarkArchived = $this->db->prepare("UPDATE users SET is_archived = 1 WHERE user_id = :id");
      $updateSuccess = $stmtMarkArchived->execute(['id' => $userId]);

      if (!$updateSuccess) {
        $this->db->rollBack();
        return ['success' => false, 'debug_reason' => 'Failed to mark user as archived.', 'debug_data' => $stmtMarkArchived->errorInfo()];
      }


      $this->db->commit();
      return ['success' => true];
    } catch (PDOException $e) {
      $this->db->rollBack();
      error_log("Error archiving user $userId (copying and marking archived): " . $e->getMessage());
      return ['success' => false, 'debug_reason' => 'Database error during archive process.', 'debug_data' => $e->getMessage()];
    }
  }
}
