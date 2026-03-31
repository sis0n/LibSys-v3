<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\StudentRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserPermissionModuleRepository;
use App\Repositories\FacultyRepository;
use App\Repositories\StaffRepository;
use App\Repositories\AuditLogRepository;

class UserManagementController extends Controller
{
  private UserRepository $userRepo;
  private StudentRepository $studentRepo;
  private UserPermissionModuleRepository $userPermissionRepo;
  private FacultyRepository $facultyRepo;
  private StaffRepository $staffRepo;
  private AuditLogRepository $auditRepo;

  public function __construct()
  {
    parent::__construct();
    $this->userRepo = new UserRepository();
    $this->studentRepo = new StudentRepository();
    $this->userPermissionRepo = new UserPermissionModuleRepository();
    $this->facultyRepo = new FacultyRepository();
    $this->staffRepo = new StaffRepository();
    $this->auditRepo = new AuditLogRepository();
  }

  public function index()
  {
    $this->view('superadmin/userManagement', [
      'title' => 'User Management',
    ]);
  }

  public function fetchPaginatedUsers()
  {
    header('Content-Type: application/json');
    try {
      $role = strtolower(str_replace([' ', '-'], '_', $_SESSION['role'] ?? ''));
      if ($role === 'librarian') {
          throw new \Exception("Unauthorized: Librarians do not have access to User Management.");
      }

      $limit = (int)($_GET['limit'] ?? 10);
      $offset = (int)($_GET['offset'] ?? 0);
      $search = $_GET['search'] ?? '';
      $filterRole = $_GET['role'] ?? 'All Roles';
      $status = $_GET['status'] ?? 'All Status';

      $currentUserId = $_SESSION['user_id'] ?? null;
      $campusId = $this->getCampusFilter();

      $users = $this->userRepo->getPaginatedUsers($limit, $offset, $search, $filterRole, $status, $currentUserId, $campusId);
      $totalCount = $this->userRepo->countPaginatedUsers($search, $filterRole, $status, $currentUserId, $campusId);

      echo json_encode(['success' => true, 'users' => $users, 'totalCount' => $totalCount]);
    } catch (\Exception $e) {
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
  }

  public function getUserById($id)
  {
    header('Content-Type: application/json');
    $user = $this->userRepo->getUserById($id);
    if (!$user) {
      http_response_code(404);
      echo json_encode(['error' => 'User not found']);
      return;
    }

    $role = strtolower($user['role'] ?? '');
    $extraDetails = null;

    if ($role === 'student') {
      $extraDetails = $this->studentRepo->getStudentByUserId((int)$id);
    } elseif ($role === 'faculty') {
      $extraDetails = $this->facultyRepo->getFacultyByUserId((int)$id);
    } elseif ($role === 'staff') {
      $extraDetails = $this->staffRepo->getStaffByUserId((int)$id);
    }

    $modules = [];
    if (in_array($role, ['admin', 'librarian', 'campus_admin', 'campus admin'])) {
      $modules = $this->userPermissionRepo->getModulesByUserId((int)$id);
    }

    // Fetch campus name if campus_id is available in the user object
    if ($user && isset($user['campus_id'])) {
      $campusRepo = new \App\Repositories\CampusRepository();
      $campuses = $campusRepo->getAllCampuses();
      $campusName = 'Unknown Campus'; // Default value

      foreach ($campuses as $campus) {
        if ($campus['campus_id'] === $user['campus_id']) {
          $campusName = $campus['campus_name'];
          break;
        }
      }
      $user['campus_name'] = $campusName; // Add campus name to the user array
    }

    echo json_encode(['user' => $user, 'extra' => $extraDetails, 'modules' => $modules]);
  }

  public function search()
  {
    header('Content-Type: application/json');
    $query = $_GET['q'] ?? '';
    error_log("Search query: " . $query);

    try {
      if (empty($query)) {
        $users = $this->userRepo->getAllUsers();
      } else {
        $users = $this->userRepo->searchUsers($query);
      }
      echo json_encode(['success' => true, 'users' => $users]);
    } catch (\Exception $e) {
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
  }

  public function addUser()
  {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents("php://input"), true);

    $first_name = trim($data['first_name'] ?? '');
    $middle_name = trim($data['middle_name'] ?? '');
    $last_name = trim($data['last_name'] ?? '');
    $campus_id = $data['campus_id'] ?? null;
    $username = trim($data['username'] ?? '');
    $role = strtolower(trim($data['role'] ?? ''));
    $contact = $data['contact'] ?? 'N/A';

    $campusIdFilter = $this->getCampusFilter();
    if ($campusIdFilter !== null) {
      $campus_id = $campusIdFilter;
    }

    if (!$first_name || !$last_name || !$username || !$role || !$campus_id) {
      echo json_encode([
        'success' => false,
        'message' => 'First Name, Last Name, Campus, Username, and Role are required.'
      ]);
      return;
    }

    $db = $this->userRepo->getDbConnection();

    try {
      $db->beginTransaction();

      if ($this->userRepo->usernameExists($username)) {
        $db->rollBack();
        echo json_encode([
          'success' => false,
          'message' => "The username '$username' is already taken. Please use a different one."
        ]);
        return;
      }
      if ($role === 'student') {
        $studentNumber = $username;
        if ($this->studentRepo->studentNumberExists($studentNumber)) {
          $db->rollBack();
          echo json_encode(['success' => false, 'message' => 'Student Number already exists.']);
          exit;
        }
      }

      $defaultPassword = '12345';
      $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);

      $userData = [
        'username' => $username,
        'password' => $hashedPassword,
        'first_name' => $first_name,
        'middle_name' => $middle_name,
        'last_name' => $last_name,
        'campus_id' => $campus_id,
        'email' => $data['email'] ?? null,
        'role' => str_replace(' ', '_', strtolower($role)),
        'is_active' => 1,
        'created_at' => date('Y-m-d H:i:s')
      ];

      $userId = $this->userRepo->insertUser($userData);

      if (!$userId) {
        throw new \Exception("Failed to insert user record.");
      }

      switch ($role) {
        case 'student':
          $studentNumber = $username;
          $courseId = filter_var($data['course_id'] ?? null, FILTER_VALIDATE_INT);
          if (!$courseId) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Course/Program selection is required']);
            return;
          }

          // Get campus name for student record compatibility if needed
          $campusRepo = new \App\Repositories\CampusRepository();
          $campuses = $campusRepo->getAllCampuses();
          $campusName = 'N/A';
          foreach ($campuses as $cp) {
            if ($cp['campus_id'] == $campus_id) {
              $campusName = $cp['campus_name'];
              break;
            }
          }

          $this->studentRepo->insertStudent(
            $userId,
            $username,
            $courseId,
            $data['year_level'] ?? 1,
            'enrolled',
            $campusName
          );
          break;

        case 'faculty':
          $collegeId = filter_var($data['college_id'] ?? null, FILTER_VALIDATE_INT);

          if (!$collegeId) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Department is required!']);
            return;
          }
          $this->facultyRepo->insertFaculty(
            $userId,
            $collegeId,
            $contact,
            'active'
          );
          break;

        case 'staff':
          $staffRepo = new \App\Repositories\StaffRepository();
          $staffRepo->insertStaff(
            $userId,
            $data['position'] ?? 'N/A',
            $contact,
            'active'
          );

          break;
        case 'admin':
        case 'librarian':
        case 'campus admin':
          if (empty($data['modules']) || !is_array($data['modules'])) {
            $db->rollBack();
            echo json_encode([
              'success' => false,
              'message' => 'Please select at least one module for ' . ucwords($role) . '.',
            ]);
            return;
          }

          $validModules = [
            'book management',
            'equipment management',
            'qr scanner',
            'returning',
            'overdue tracking',
            'borrowing form',
            'attendance logs',
            'reports',
            'transaction history',
            'user management',
            'restore users',
            'student promotion',
            'library policies'
          ];
          $modules = array_filter($data['modules'], fn($m) => in_array($m, $validModules));

          $this->userPermissionRepo->assignModules($userId, $modules);
          break;

        default:
          $db->rollBack();
          echo json_encode(['success' => false, 'message' => 'Invalid role specified.']);
          return;
      }

      $this->auditRepo->log($_SESSION['user_id'], 'CREATE', 'USERS', $username, "Added new user: $first_name $last_name as " . ucfirst($role));

      $db->commit();

      echo json_encode([
        'success' => true,
        'message' => ucfirst($role) . ' user added successfully.',
        'user_id' => $userId,
      ]);
    } catch (\Exception $e) {
      if ($db->inTransaction()) $db->rollBack();
      error_log("[UserManagementController::addUser] " . $e->getMessage());
      echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
      ]);
    }
  }


  public function deleteUser($id)
  {
    header('Content-Type: application/json');

    try {
      $deletedBy = $_SESSION['user_id'] ?? null;
      if (!$deletedBy) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
      }

      if ($this->userRepo->hasBorrowedItems((int)$id)) {
        echo json_encode([
          'success' => false,
          'message' => 'Cannot delete user. The user still has borrowed books or equipment.'
        ]);
        return;
      }

      $user = $this->userRepo->getUserById($id);
      if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        return;
      }

      $campusIdFilter = $this->getCampusFilter();
      if ($campusIdFilter !== null && $user['campus_id'] != $campusIdFilter) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized: User belongs to another campus.']);
        return;
      }

      $deleted = $this->userRepo->deleteUserWithCascade((int)$id, $deletedBy);

      if ($deleted && $user) {
        $this->auditRepo->log($deletedBy, 'DELETE', 'USERS', $user['username'], "Deleted user: {$user['first_name']} {$user['last_name']}");
      }

      echo json_encode([
        'success' => $deleted,
        'message' => $deleted ? 'User deleted successfully.' : 'Failed to delete user.'
      ]);
    } catch (\Exception $e) {
      echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
      ]);
    }
  }

  public function deleteMultipleUsers()
  {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents("php://input"), true);
    $userIds = $data['user_ids'] ?? [];

    $deletedBy = $_SESSION['user_id'] ?? null;
    if (!$deletedBy) {
      echo json_encode(['success' => false, 'message' => 'Unauthorized']);
      return;
    }

    if (empty($userIds) || !is_array($userIds)) {
      echo json_encode(['success' => false, 'message' => 'No user IDs provided.']);
      return;
    }

    $deletedCount = 0;
    $errors = [];

    foreach ($userIds as $id) {
      $id = (int)$id;
      $user = $this->userRepo->getUserById($id);

      if (!$user) {
        $errors[] = "User with ID $id not found.";
        continue;
      }

      if (strtolower($user['role']) === 'superadmin') {
        $errors[] = "Cannot delete Superadmin: {$user['username']}.";
        continue;
      }

      if ($id === $deletedBy) {
        $errors[] = "You cannot delete your own account.";
        continue;
      }

      if ($this->userRepo->hasBorrowedItems($id)) {
        $errors[] = "Cannot delete {$user['username']}: User has borrowed items.";
        continue;
      }

      try {
        if ($this->userRepo->deleteUserWithCascade($id, $deletedBy)) {
          $deletedCount++;
        } else {
          $errors[] = "Failed to delete user: {$user['username']}.";
        }
      } catch (\Exception $e) {
        $errors[] = "Error deleting {$user['username']}: " . $e->getMessage();
      }
    }

    $response = [
      'success' => $deletedCount > 0,
      'message' => "Successfully deleted $deletedCount user(s).",
      'deleted_count' => $deletedCount,
      'errors' => $errors
    ];

    if ($deletedCount === 0 && !empty($errors)) {
      $response['success'] = false;
      $response['message'] = "No users were deleted. See errors for details.";
    } else if ($deletedCount > 0 && !empty($errors)) {
      $response['message'] = "Partially completed: Deleted $deletedCount user(s) with some errors.";
    }

    echo json_encode($response);
  }

  public function allowMultipleEdit()
  {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents("php://input"), true);
    $userIds = $data['user_ids'] ?? [];

    if (empty($userIds) || !is_array($userIds)) {
      echo json_encode(['success' => false, 'message' => 'No user IDs provided.']);
      return;
    }

    $studentProfileRepo = new \App\Repositories\StudentProfileRepository();
    $updatedCount = 0;
    $errors = [];

    foreach ($userIds as $id) {
      $id = (int)$id;
      $user = $this->userRepo->getUserById($id);

      if (!$user) {
        $errors[] = "User with ID $id not found.";
        continue;
      }

      if (strtolower($user['role']) !== 'student') {
        $errors[] = "User {$user['username']} is not a student.";
        continue;
      }

      try {
        if ($studentProfileRepo->setEditAccess($id, true)) {
          $updatedCount++;
        } else {
          $errors[] = "Failed to grant access to {$user['username']}.";
        }
      } catch (\Exception $e) {
        $errors[] = "Error granting access to {$user['username']}: " . $e->getMessage();
      }
    }

    $response = [
      'success' => $updatedCount > 0,
      'message' => "Successfully granted edit access to $updatedCount student(s).",
      'updated_count' => $updatedCount,
      'errors' => $errors
    ];

    if ($updatedCount === 0 && !empty($errors)) {
      $response['success'] = false;
      $response['message'] = "No access was granted. See errors for details.";
    } else if ($updatedCount > 0 && !empty($errors)) {
      $response['message'] = "Partially completed: Granted access to $updatedCount student(s) with some errors.";
    }

    echo json_encode($response);
  }

  public function toggleStatus($id)
  {
    header('Content-Type: application/json');
    try {
      $user = $this->userRepo->getUserById((int)$id);
      if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        return;
      }

      $campusIdFilter = $this->getCampusFilter();
      if ($campusIdFilter !== null && $user['campus_id'] != $campusIdFilter) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized: User belongs to another campus.']);
        return;
      }

      if (strtolower($user['role']) === 'superadmin') {
        echo json_encode(['success' => false, 'message' => 'Superadmin status cannot be changed.']);
        return;
      }

      $this->userRepo->toggleUserStatus((int)$id);

      $updatedUser = $this->userRepo->getUserById((int)$id);
      $newStatus = $updatedUser['is_active'] ? 'Active' : 'Inactive';

      $this->auditRepo->log($_SESSION['user_id'], 'TOGGLE_STATUS', 'USERS', $user['username'], "Set status of {$user['first_name']} {$user['last_name']} to $newStatus");

      echo json_encode([
        'success' => true,
        'message' => 'User status updated successfully.',
        'newStatus' => $newStatus
      ]);
    } catch (\Exception $e) {
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
  }

  public function updateUser($id)
  {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data)) {
      echo json_encode(['success' => false, 'message' => 'Incomplete user data provided.']);
      return;
    }

    $db = $this->userRepo->getDbConnection();

    try {
      $db->beginTransaction();

      $modulesPayload       = $data['modules'] ?? null;
      $modulesKeyWasPresent = array_key_exists('modules', $data);
      unset($data['modules']);

      $currentUser = $this->userRepo->getUserById((int)$id);
      if (!$currentUser) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        return;
      }

      $campusIdFilter = $this->getCampusFilter();
      if ($campusIdFilter !== null && $currentUser['campus_id'] != $campusIdFilter) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized: User belongs to another campus.']);
        return;
      }

      $currentRole = strtolower($currentUser['role'] ?? '');

      $studentData = [];
      if ($currentRole === 'student') {
        if (isset($data['course_id']))  $studentData['course_id']  = $data['course_id'];
        if (isset($data['year_level'])) $studentData['year_level'] = $data['year_level'];
        if (isset($data['section']))    $studentData['section']    = $data['section'];

        unset($data['course_id'], $data['year_level'], $data['section']);
      }

      unset($data['role'], $data['user_id']);

      if (!empty($data['password'])) {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
      } else {
        unset($data['password']);
      }

      $this->userRepo->updateUser((int)$id, $data);

      if (!empty($studentData)) {
        (new \App\Repositories\StudentProfileRepository())->updateStudentProfile((int)$id, $studentData);
      }

      if (in_array($currentRole, ['superadmin', 'admin', 'librarian', 'campus_admin']) && $modulesKeyWasPresent) {
        $modulesToAssign = is_array($modulesPayload) ? $modulesPayload : [];
        $this->userPermissionRepo->assignModules((int)$id, $modulesToAssign);
      }

      $details = "Updated details/permissions for {$currentUser['first_name']} {$currentUser['last_name']}";
      if (!empty($data['password'])) {
        $details .= " (Password was reset by Admin)";
      }
      $this->auditRepo->log($_SESSION['user_id'], 'UPDATE', 'USERS', $currentUser['username'], $details);

      $db->commit();

      echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
    } catch (\Exception $e) {
      if ($db->inTransaction()) $db->rollBack();
      error_log("[UserManagementController::updateUser] " . $e->getMessage());
      echo json_encode(['success' => false, 'message' => 'An internal server error occurred.']);
    }
  }

  public function allowEdit($id)
  {
    header('Content-Type: application/json');
    try {
      $studentRepo = new \App\Repositories\StudentProfileRepository();
      $updated = $studentRepo->setEditAccess((int)$id, true);
      echo json_encode([
        'success' => $updated,
        'message' => $updated
          ? 'Student can now edit their profile again.'
          : 'Failed to grant edit access.'
      ]);
    } catch (\Exception $e) {
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
  }

  public function bulkImport()
  {
    header('Content-Type: application/json');

    if (!isset($_FILES['csv_file'])) {
      echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
      exit;
    }

    $file       = $_FILES['csv_file']['tmp_name'];
    $userRepo   = new \App\Repositories\UserRepository();
    $studentRepo = new \App\Repositories\StudentRepository();
    $courseRepo = new \App\Repositories\CollegeCourseRepository();
    $campusRepo = new \App\Repositories\CampusRepository();
    $db         = $userRepo->getDbConnection();

    $courseMap = [];
    foreach ($courseRepo->getAllCourses() as $c) {
      $courseMap[strtoupper(trim($c['course_code']))] = $c['course_id'];
    }

    $campusMap = [];
    foreach ($campusRepo->getAllCampuses() as $cp) {
      $campusMap[strtoupper(trim($cp['campus_name']))] = $cp['campus_id'];
    }

    $existingUsernames = array_flip($userRepo->getAllUsernamesMap());

    $imported          = 0;
    $errors            = [];
    $batchSize         = 500;
    $usersBuffer       = [];
    $studentDataBuffer = [];
    $defaultPassword   = password_hash('12345', PASSWORD_DEFAULT);
    $timestamp         = date('Y-m-d H:i:s');

    if (($handle = fopen($file, 'r')) === false) {
      echo json_encode(['success' => false, 'message' => 'Failed to open uploaded file.']);
      exit;
    }

    $header    = array_map('strtolower', array_map('trim', fgetcsv($handle)));
    $rowNumber = 2;

    $db->beginTransaction();

    try {
      while (($row = fgetcsv($handle)) !== false) {
        $data = array_combine($header, array_pad($row, count($header), ''));

        $firstName  = trim($data['first_name']     ?? '');
        $lastName   = trim($data['last_name']      ?? '');
        $studentId  = trim($data['student_number'] ?? '');
        $courseCode = strtoupper(trim($data['course_code'] ?? ''));
        $contact    = trim($data['contact']        ?? 'N/A');
        $email      = trim($data['email']          ?? '');
        $campusInput = strtoupper(trim($data['campus'] ?? ''));

        if ($studentId === '' || $firstName === '') {
          $errors[] = "Row $rowNumber: Skip - Missing First Name or Student Number.";
          $rowNumber++;
          continue;
        }

        if (isset($existingUsernames[$studentId])) {
          $errors[] = "Row $rowNumber: Skip - Student ID ($studentId) already exists.";
          $rowNumber++;
          continue;
        }

        $campusId = $campusMap[$campusInput] ?? null;

        $usersBuffer[] = [
          'username'    => $studentId,
          'password'    => $defaultPassword,
          'first_name'  => $firstName,
          'middle_name' => null,
          'last_name'   => $lastName,
          'email'       => !empty($email) ? $email : null,
          'role'        => 'Student',
          'is_active'   => 1,
          'created_at'  => $timestamp,
          'campus_id'   => $campusId,
        ];

        $studentDataBuffer[] = [
          'student_number' => $studentId,
          'course_id'      => $courseMap[$courseCode] ?? null,
          'year_level'     => 1,
          'status'         => 'enrolled',
          'contact'        => $contact,
          'section'        => 'N/A',
        ];

        if (count($usersBuffer) >= $batchSize) {
          $this->processBatch($userRepo, $studentRepo, $usersBuffer, $studentDataBuffer);
          $imported += count($usersBuffer);
          $usersBuffer       = [];
          $studentDataBuffer = [];
        }

        $rowNumber++;
      }

      if (!empty($usersBuffer)) {
        $this->processBatch($userRepo, $studentRepo, $usersBuffer, $studentDataBuffer);
        $imported += count($usersBuffer);
      }

      $db->commit();
    } catch (\Exception $e) {
      if ($db->inTransaction()) $db->rollBack();
      echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
      exit;
    } finally {
      fclose($handle);
    }

    echo json_encode(['success' => $imported > 0, 'imported' => $imported, 'errors' => $errors]);
  }

  private function processBatch($userRepo, $studentRepo, $users, $students)
  {
    try {
      $userRepo->bulkInsertUsers($users);

      $usernames = array_column($users, 'username');
      $placeholders = implode(',', array_fill(0, count($usernames), '?'));
      $db = $userRepo->getDbConnection();
      $stmt = $db->prepare("SELECT username, user_id FROM users WHERE username IN ($placeholders)");
      $stmt->execute($usernames);
      $userMap = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

      $finalStudentsBatch = [];
      foreach ($students as $s) {
        if (isset($userMap[$s['student_number']])) {
          $s['user_id'] = $userMap[$s['student_number']];
          $finalStudentsBatch[] = $s;
        }
      }

      if (!empty($finalStudentsBatch)) {
        $studentRepo->bulkInsertStudentDetails($finalStudentsBatch);
      }
    } catch (\Exception $e) {
      error_log("Bulk Process Error: " . $e->getMessage());
      throw $e;
    }
  }
}
