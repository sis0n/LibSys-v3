<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

class UserRepository
{
  private $db;

  public function __construct()
  {
    $this->db = Database::getInstance()->getConnection();
  }

  public function getDbConnection()
  {
    return $this->db;
  }

  public function findByIdentifier(string $identifier)
  {
    try {
      $stmt = $this->db->prepare("
            SELECT 
                u.user_id, 
                u.username, 
                u.password, 
                u.first_name, 
                u.middle_name, 
                u.last_name, 
                u.suffix, 
                u.gender,
                u.profile_picture, 
                u.is_active, 
                u.role,
                u.email,
                u.campus_id,
                s.student_id, 
                s.student_number, 
                s.year_level, 
                s.course_id,
                s.section
            FROM students s
            LEFT JOIN users u ON u.user_id = s.user_id
            WHERE UPPER(s.student_number) = UPPER(:identifier)
            AND u.deleted_at IS NULL
            LIMIT 1
        ");
      $stmt->execute(['identifier' => $identifier]);
      $student = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($student) {
        return $student;
      }

      $stmt = $this->db->prepare("
            SELECT 
                u.user_id, 
                u.username, 
                u.password, 
                u.first_name, 
                u.middle_name, 
                u.last_name, 
                u.suffix, 
                u.gender,
                u.profile_picture, 
                u.is_active, 
                u.role,
                u.email,
                u.campus_id,
                s.student_id, 
                s.student_number, 
                s.year_level, 
                s.course_id,
                s.section
            FROM users u
            LEFT JOIN students s ON u.user_id = s.user_id
            WHERE LOWER(u.username) = LOWER(:identifier)
            AND u.deleted_at IS NULL
            LIMIT 1
        ");
      $stmt->execute(['identifier' => $identifier]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($user) {
        return $user;
      }

      $stmt = $this->db->prepare("
            SELECT 
                u.user_id,
                u.username,
                u.password,
                u.first_name,
                u.middle_name,
                u.last_name,
                u.suffix,
                u.profile_picture,
                u.is_active,
                u.role,
                u.email,
                u.campus_id,
                f.faculty_id,
                f.college_id
            FROM faculty f
            LEFT JOIN users u ON u.user_id = f.user_id
            WHERE LOWER(u.username) = LOWER(:identifier)
            AND u.deleted_at IS NULL
            LIMIT 1
        ");
      $stmt->execute(['identifier' => $identifier]);
      $faculty = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($faculty) {
        $faculty['role'] = 'faculty';
        return $faculty;
      }

      $stmt = $this->db->prepare("
            SELECT 
                user_id, username, password, first_name, middle_name, last_name, suffix, profile_picture, is_active, role, email
            FROM users
            WHERE LOWER(username) = LOWER(:identifier)
            AND deleted_at IS NULL
            LIMIT 1
        ");
      $stmt->execute(['identifier' => $identifier]);
      $genericUser = $stmt->fetch(\PDO::FETCH_ASSOC);

      return $genericUser ?: null;
    } catch (\PDOException $e) {
      error_log("[UserRepository::findByIdentifier] " . $e->getMessage());
      return null;
    }
  }

  public function bulkInsertUsers(array $usersBatch)
  {
    if (empty($usersBatch)) return [];

    $columns = ['username', 'password', 'first_name', 'middle_name', 'last_name', 'email', 'role', 'is_active', 'created_at', 'campus_id'];
    $colString = implode(',', $columns);

    $placeholders = [];
    $flatValues = [];

    foreach ($usersBatch as $user) {
      $placeholders[] = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';
      foreach ($columns as $col) {
        $flatValues[] = isset($user[$col]) && $user[$col] !== '' ? $user[$col] : null;
      }
    }

    $sql = "INSERT IGNORE INTO users ($colString) VALUES " . implode(',', $placeholders);

    $stmt = $this->db->prepare($sql);
    $stmt->execute($flatValues);

    return true;
  }

  public function insertUser(array $data): int
  {
    $stmt = $this->db->prepare("
            INSERT INTO users (username, password, first_name, middle_name, last_name, suffix, campus_id, email, role, is_active, created_at)
            VALUES (:username, :password, :first_name, :middle_name, :last_name, :suffix, :campus_id, :email, :role, :is_active, :created_at)
        ");

    $stmt->execute([
      ':username' => $data['username'],
      ':password' => $data['password'],
      ':first_name' => $data['first_name'],
      ':middle_name' => $data['middle_name'] ?? null,
      ':last_name' => $data['last_name'],
      ':suffix' => $data['suffix'] ?? null,
      ':campus_id' => $data['campus_id'] ?? null,
      ':email' => $data['email'] ?? null,
      ':role' => $data['role'],
      ':is_active' => $data['is_active'] ?? 1,
      ':created_at' => $data['created_at'] ?? date('Y-m-d H:i:s')
    ]);

    return (int)$this->db->lastInsertId();
  }

  public function updateUser(int $id, array $data): bool
  {
    $fields = [];
    $params = [':id' => $id];

    if (isset($data['first_name'])) {
      $fields[] = "first_name = :first_name";
      $params[':first_name'] = $data['first_name'];
    }
    if (isset($data['middle_name'])) {
      $fields[] = "middle_name = :middle_name";
      $params[':middle_name'] = $data['middle_name'];
    }
    if (isset($data['last_name'])) {
      $fields[] = "last_name = :last_name";
      $params[':last_name'] = $data['last_name'];
    }
    if (isset($data['suffix'])) {
      $fields[] = "suffix = :suffix";
      $params[':suffix'] = $data['suffix'];
    }
    if (isset($data['gender'])) {
      $fields[] = "gender = :gender";
      $params[':gender'] = $data['gender'];
    }
    if (isset($data['username'])) {
      $fields[] = "username = :username";
      $params[':username'] = $data['username'];
    }
    if (isset($data['email'])) {
      $fields[] = "email = :email";
      $params[':email'] = $data['email'];
    }
    if (isset($data['password']) && !empty($data['password'])) {
      $fields[] = "password = :password";
      $params[':password'] = $data['password'];
    }
    if (isset($data['role'])) {
      $fields[] = "role = :role";
      $params[':role'] = $data['role'];
    }
    if (isset($data['is_active'])) {
      $fields[] = "is_active = :is_active";
      $params[':is_active'] = $data['is_active'];
    }
    if (isset($data['profile_picture'])) {
      $fields[] = "profile_picture = :profile_picture";
      $params[':profile_picture'] = $data['profile_picture'];
    }
    if (isset($data['campus_id'])) {
      $fields[] = "campus_id = :campus_id";
      $params[':campus_id'] = $data['campus_id'];
    }

    if (empty($fields)) {
      return false;
    }

    $fields[] = "updated_at = NOW()";

    $query = "UPDATE users SET " . implode(', ', $fields) . " WHERE user_id = :id";
    try {
      $stmt = $this->db->prepare($query);
      $stmt->execute($params);
      return true;
    } catch (\PDOException $e) {
      error_log("[UserRepository::updateUser] " . $e->getMessage());
      return false;
    }
  }

  public function getAllUsers(): array
  {
    try {
      $stmt = $this->db->query("
            SELECT 
                u.user_id,
                u.username,
                u.first_name,
                u.middle_name,
                u.last_name,
                u.suffix,
                u.email,
                u.role,
                u.is_active,
                u.created_at,
                GROUP_CONCAT(um.module_name) AS modules
            FROM users u
            LEFT JOIN user_module_permissions um ON um.user_id = u.user_id
            WHERE u.deleted_at IS NULL
            GROUP BY u.user_id
            ORDER BY u.user_id DESC
        ");
      $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

      foreach ($users as &$user) {
        $user['modules'] = $user['modules'] ? explode(',', $user['modules']) : [];
      }

      return $users;
    } catch (PDOException $e) {
      error_log('[UserRepository::getAllUsers] ' . $e->getMessage());
      return [];
    }
  }


  public function findByStudentNumber(string $studentNumber)
  {
    try {
      $stmt = $this->db->prepare("
                SELECT 
                    u.user_id, 
                    u.username, 
                    u.first_name, 
                    u.middle_name, 
                    u.last_name, 
                    u.suffix,
                    u.profile_picture,
                    u.is_active, 
                    u.role,
                    s.student_id, 
                    s.student_number, 
                    s.year_level, 
                    s.course,
                    s.section,
                    s.campus
                FROM users u
                JOIN students s ON u.user_id = s.user_id
                WHERE UPPER(s.student_number) = UPPER(:student_number)
                AND u.deleted_at IS NULL
                LIMIT 1
            ");
      $stmt->execute(['student_number' => $studentNumber]);

      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      return $result;
    } catch (PDOException $e) {
      error_log("[UserRepository::findByStudentNumber] " . $e->getMessage());
      return false;
    }
  }

  public function getUserById($id)
  {
    $stmt = $this->db->prepare("
            SELECT 
                user_id, 
                first_name, 
                middle_name, 
                last_name, 
                suffix,
                gender,
                username, 
                email,
                role, 
                is_active,
                campus_id
            FROM users 
            WHERE user_id = :id AND deleted_at IS NULL
        ");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function searchUsers(string $query): array
  {
    $searchQuery = "%" . strtolower($query) . "%";
    $stmt = $this->db->prepare("
            SELECT 
                user_id, 
                username, 
                first_name, 
                middle_name, 
                last_name, 
                suffix,
                email, 
                role, 
                is_active, 
                created_at
            FROM users
            WHERE (LOWER(first_name) LIKE :query
                OR LOWER(last_name) LIKE :query
                OR LOWER(username) LIKE :query
                OR LOWER(email) LIKE :query)
            AND deleted_at IS NULL
            ORDER BY user_id DESC
        ");
    $stmt->execute(['query' => $searchQuery]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function hasBorrowedItems(int $userId): bool
  {
    $stmt = $this->db->prepare("
        SELECT 
            (SELECT student_id FROM students WHERE user_id = :user_id) AS student_id,
            (SELECT staff_id FROM staff WHERE user_id = :user_id) AS staff_id,
            (SELECT faculty_id FROM faculty WHERE user_id = :user_id) AS faculty_id
    ");
    $stmt->execute([':user_id' => $userId]);
    $ids = $stmt->fetch(\PDO::FETCH_ASSOC);

    $studentId = $ids['student_id'] ?? 0;
    $staffId = $ids['staff_id'] ?? 0;
    $facultyId = $ids['faculty_id'] ?? 0;

    $stmt = $this->db->prepare("
        SELECT COUNT(*) 
        FROM borrow_transactions bt
        JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
        WHERE 
            (bt.student_id = :student_id OR bt.staff_id = :staff_id OR bt.faculty_id = :faculty_id)
            AND TRIM(LOWER(bti.status)) = 'borrowed'
            AND (bti.book_id IS NOT NULL OR bti.equipment_id IS NOT NULL)
    ");

    $stmt->execute([
      ':student_id' => $studentId,
      ':staff_id' => $staffId,
      ':faculty_id' => $facultyId
    ]);

    return (int)$stmt->fetchColumn() > 0;
  }

  public function deleteUserWithCascade(int $userId, int $deletedBy): bool
  {
    try {
      $this->db->beginTransaction();

      $stmtCheck = $this->db->prepare("SELECT role FROM users WHERE user_id = :id AND deleted_at IS NULL");
      $stmtCheck->execute([':id' => $userId]);
      $user = $stmtCheck->fetch(PDO::FETCH_ASSOC);

      if (!$user) {
        $this->db->rollBack();
        return false;
      }

      $stmtUpdateUser = $this->db->prepare("
            UPDATE users 
            SET deleted_at = NOW(), deleted_by = :deleted_by 
            WHERE user_id = :uid AND deleted_at IS NULL
        ");
      $stmtUpdateUser->execute([
        ':deleted_by' => $deletedBy,
        ':uid' => $userId
      ]);

      if ($stmtUpdateUser->rowCount() === 0) {
        $this->db->rollBack();
        return false;
      }

      if (isset($user['role']) && strtolower($user['role']) === 'student') {
        $stmtUpdateStudent = $this->db->prepare("
                UPDATE students 
                SET deleted_at = NOW(), deleted_by = :deleted_by 
                WHERE user_id = :uid AND deleted_at IS NULL
            ");
        $stmtUpdateStudent->execute([
          ':deleted_by' => $deletedBy,
          ':uid' => $userId
        ]);
      }

      $this->db->commit();
      return true;
    } catch (\Exception $e) {
      $this->db->rollBack();
      return false;
    }
  }

  public function toggleUserStatus(int $userId): bool
  {
    $stmt = $this->db->prepare("
            UPDATE users 
            SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END, updated_at = NOW()
            WHERE user_id = :id
        ");
    return $stmt->execute([':id' => $userId]);
  }

  public function updatePassword(int $userId, string $hashedPassword): bool
  {
    try {
      $stmt = $this->db->prepare("
                UPDATE users
                SET password = :password, updated_at = NOW()
                WHERE user_id = :user_id AND deleted_at IS NULL
            ");
      return $stmt->execute([
        ':password' => $hashedPassword,
        ':user_id' => $userId
      ]);
    } catch (\PDOException $e) {
      error_log("[UserRepository::updatePassword]" . $e->getMessage());
      return false;
    }
  }

  public function findFacultyByIdentifier(string $identifier): ?array
  {
    $stmt = $this->db->prepare("
        SELECT * FROM faculty
        WHERE (username = :identifier OR email = :identifier)
        AND deleted_at IS NULL
        LIMIT 1
    ");
    $stmt->execute(['identifier' => $identifier]);
    return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
  }

  public function insertStudent(array $data): int
  {
    $userId = $this->insertUser([
      'first_name' => $data['first_name'],
      'middle_name' => $data['middle_name'] ?? null,
      'last_name' => $data['last_name'],
      'username' => $data['username'],
      'role' => 'student',
      'password' => $data['password'] ?? password_hash('defaultpassword', PASSWORD_DEFAULT),
      'is_active' => $data['is_active'] ?? 1,
      'created_at' => $data['created_at'] ?? date('Y-m-d H:i:s')
    ]);

    $stmt = $this->db->prepare("
        INSERT INTO students (user_id, student_number, year_level, course, section, campus)
        VALUES (:user_id, :student_number, :year_level, :course, :section, :campus)
    ");

    $stmt->execute([
      ':user_id' => $userId,
      ':student_number' => $data['username'],
      ':year_level' => $data['year_level'] ?? 1,
      ':course' => $data['course'] ?? 'N/A',
      ':section' => $data['section'] ?? 'N/A',
      ':campus' => $data['campus'] ?? 'N/A'
    ]);

    return $userId;
  }

  public function findByStudentNumberWithDetails(string $studentNumber): ?array
  {
    try {
      $stmt = $this->db->prepare("
            SELECT 
                u.user_id, u.username, u.password, u.first_name, u.middle_name, u.last_name, u.suffix, 
                u.profile_picture, s.student_number, s.course_id, s.year_level, s.section, s.campus,
                s.profile_updated, c.course_title, c.course_code
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            LEFT JOIN courses c ON s.course_id = c.course_id
            WHERE UPPER(s.student_number) = UPPER(:studentNumber)
            AND u.deleted_at IS NULL
            LIMIT 1
        ");
      $stmt->execute(['studentNumber' => $studentNumber]);
      $result = $stmt->fetch(\PDO::FETCH_ASSOC);

      return $result ?: null;
    } catch (\PDOException $e) {
      error_log("[UserRepository::findByStudentNumberWithDetails] " . $e->getMessage());
      return null;
    }
  }

  public function findByEmail(string $email)
  {
    try {
      $stmt = $this->db->prepare("
                SELECT 
                    u.user_id, 
                    u.username, 
                    u.password, 
                    u.first_name, 
                    u.middle_name, 
                    u.last_name, 
                    u.suffix, 
                    u.profile_picture, 
                    u.is_active, 
                    u.role,
                    u.email,
                    s.student_id, 
                    s.student_number, 
                    s.year_level, 
                    s.course_id,
                    s.section,
                    s.campus,
                    f.faculty_id,
                    f.college_id
                FROM users u
                LEFT JOIN students s ON u.user_id = s.user_id AND s.deleted_at IS NULL
                LEFT JOIN faculty f ON u.user_id = f.user_id
                WHERE LOWER(u.email) = LOWER(:email)
                AND u.deleted_at IS NULL
                LIMIT 1
            ");

      $stmt->execute(['email' => strtolower($email)]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      return $user ?: null;
    } catch (\PDOException $e) {
      error_log("[UserRepository::findByEmail] " . $e->getMessage());
      return null;
    }
  }

  public function getPaginatedUsers(int $limit, int $offset, string $search, string $role, string $status, ?int $excludeUserId = null, ?int $campusId = null): array
  {
    $baseQuery = "
        FROM users u
        LEFT JOIN user_module_permissions um ON um.user_id = u.user_id
        WHERE u.deleted_at IS NULL AND u.role NOT IN ('superadmin')
    ";
    $params = [];

    if ($excludeUserId !== null) {
      $baseQuery .= " AND u.user_id != ?";
      $params[] = $excludeUserId;
    }

    if ($campusId !== null) {
      $baseQuery .= " AND u.campus_id = ?";
      $params[] = $campusId;
    }

    if ($search !== '') {
      $baseQuery .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
      $searchTerm = "%$search%";
      array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    }

    if ($role !== '' && strtolower($role) !== 'all roles') {
      $baseQuery .= " AND u.role = ?";
      $params[] = $role;
    }

    if ($status !== '' && strtolower($status) !== 'all status') {
      $baseQuery .= " AND u.is_active = ?";
      $params[] = ($status === 'Active' ? 1 : 0);
    }

    $orderBy = " ORDER BY u.created_at DESC";
    $limitOffset = " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

    $query = "
        SELECT 
            u.user_id, u.username, u.first_name, u.middle_name, u.last_name, u.suffix,
            u.email, u.role, u.is_active, u.created_at,
            GROUP_CONCAT(um.module_name) AS modules
        " . $baseQuery . " GROUP BY u.user_id" . $orderBy . $limitOffset;

    try {
      $stmt = $this->db->prepare($query);
      $stmt->execute($params);
      $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

      foreach ($users as &$user) {
        $user['modules'] = $user['modules'] ? explode(',', $user['modules']) : [];
      }
      return $users;
    } catch (PDOException $e) {
      error_log('[UserRepository::getPaginatedUsers] ' . $e->getMessage());
      return [];
    }
  }

  public function countPaginatedUsers(string $search, string $role, string $status, ?int $excludeUserId = null, ?int $campusId = null): int
  {
    $query = "SELECT COUNT(DISTINCT u.user_id) FROM users u WHERE u.deleted_at IS NULL AND u.role NOT IN ('superadmin')";
    $params = [];

    if ($excludeUserId !== null) {
      $query .= " AND u.user_id != ?";
      $params[] = $excludeUserId;
    }

    if ($campusId !== null) {
      $query .= " AND u.campus_id = ?";
      $params[] = $campusId;
    }

    if ($search !== '') {
      $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
      $searchTerm = "%$search%";
      array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    }

    if ($role !== '' && strtolower($role) !== 'all roles') {
      $query .= " AND u.role = ?";
      $params[] = $role;
    }

    if ($status !== '' && strtolower($status) !== 'all status') {
      $query .= " AND u.is_active = ?";
      $params[] = ($status === 'Active' ? 1 : 0);
    }

    try {
      $stmt = $this->db->prepare($query);
      $stmt->execute($params);
      return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
      error_log('[UserRepository::countPaginatedUsers] ' . $e->getMessage());
      return 0;
    }
  }

  public function usernameExists($username)
  {
    $sql = "SELECT COUNT(*) FROM users WHERE username = :username";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':username' => $username]);
    return $stmt->fetchColumn() > 0;
  }

  public function getAllUsernamesMap(): array
  {
    $stmt = $this->db->query("SELECT username FROM users");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
  }
}
