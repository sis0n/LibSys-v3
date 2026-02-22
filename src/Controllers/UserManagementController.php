<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\StudentRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserPermissionModuleRepository;
use App\Repositories\FacultyRepository;
use App\Repositories\StaffRepository;

class UserManagementController extends Controller
{
  private UserRepository $userRepo;
  private StudentRepository $studentRepo;
  private UserPermissionModuleRepository $userPermissionRepo;
  private FacultyRepository $facultyRepo;
  private StaffRepository $staffRepo;

  public function __construct()
  {
    $this->userRepo = new UserRepository();
    $this->studentRepo = new StudentRepository();
    $this->userPermissionRepo = new UserPermissionModuleRepository();
    $this->facultyRepo = new FacultyRepository();
    $this->staffRepo = new StaffRepository();
  }

  public function index()
  {
    $this->view('superadmin/userManagement', [
      'title' => 'User Management',
    ]);
  }

  // Pagination Start
  public function fetchPaginatedUsers()
  {
    header('Content-Type: application/json');
    try {
      $limit = (int)($_GET['limit'] ?? 10);
      $offset = (int)($_GET['offset'] ?? 0);
      $search = $_GET['search'] ?? '';
      $role = $_GET['role'] ?? 'All Roles';
      $status = $_GET['status'] ?? 'All Status';

      $users = $this->userRepo->getPaginatedUsers($limit, $offset, $search, $role, $status);
      $totalCount = $this->userRepo->countPaginatedUsers($search, $role, $status);

      echo json_encode(['success' => true, 'users' => $users, 'totalCount' => $totalCount]);
    } catch (\Exception $e) {
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
  }
  // Pagination End

  public function getUserById($id)
  {
    header('Content-Type: application/json');
    $user = $this->userRepo->getUserById($id);
    if (!$user) {
      http_response_code(404);
      echo json_encode(['error' => 'User not found']);
      return;
    }

    $modules = [];
    if (in_array(strtolower($user['role']), ['admin', 'librarian'])) {
      $modules = $this->userPermissionRepo->getModulesByUserId((int)$id);
    }
    echo json_encode(['user' => $user, 'modules' => $modules]);
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
    $middle_name = trim($data['middle_name'] ?? null);
    $last_name = trim($data['last_name'] ?? '');
    $username = trim($data['username'] ?? '');
    $role = strtolower(trim($data['role'] ?? ''));
    $contact = $data['contact'] ?? 'N/A';

    if (!$first_name || !$last_name || !$username || !$role) {
      echo json_encode([
        'success' => false,
        'message' => 'First Name, Last Name, Username, and Role are required.'
      ]);
      return;
    }

    try {

      if ($this->userRepo->usernameExists($username)) {
        echo json_encode([
          'success' => false,
          'message' => "The username '$username' is already taken. Please use a different one."
        ]);
        return;
      }
      if ($role === 'student') {
        $studentNumber = $username;
        if ($this->studentRepo->studentNumberExists($studentNumber)) {
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
        'email' => $data['email'] ?? null,
        'role' => ucfirst($role),
        'is_active' => 1,
        'created_at' => date('Y-m-d H:i:s')
      ];

      $userId = $this->userRepo->insertUser($userData);

      // roles based
      switch ($role) {
        case 'student':
          $studentNumber = $username;
          $courseId = filter_var($data['course_id'] ?? null, FILTER_VALIDATE_INT);
          if (!$courseId) {
            echo json_encode(['success' => false, 'message' => 'Course/Program selection is required']);
            return;
          }
          $this->studentRepo->insertStudent(
            $userId,
            $username,
            $courseId,
            $data['year_level'] ?? 1,
            'enrolled'
          );
          break;

        case 'faculty':
          $collegeId = filter_var($data['college_id'] ?? null, FILTER_VALIDATE_INT);

          if (!$collegeId) {
            echo json_encode(['success' => false, 'message' => 'Department is required!']);
            return;
          }
          $this->facultyRepo->insertFaculty(
            $userId,
            $collegeId,
            $contact,
            $data['contact'] ?? 'N/A',
            'active'
          );
          break;

        case 'staff':
          $staffRepo = new \App\Repositories\StaffRepository();
          $staffRepo->insertStaff(
            $userId,
            $data['employee_id'] ?? 'N/A',
            $data['position'] ?? 'N/A',
            $contact,
            'active'
          );

          break;
        case 'admin':
        case 'librarian':
          if (empty($data['modules']) || !is_array($data['modules'])) {
            echo json_encode([
              'success' => false,
              'message' => 'Please select at least one module for ' . ucfirst($role) . '.',
            ]);
            return;
          }

          $validModules = [
            'book management',
            'qr scanner',
            'returning',
            'borrowing form',
            'attendance logs',
            'reports',
            'transaction history',
            'restore books',
            'user management',
            'restore users'
          ];
          $modules = array_filter($data['modules'], fn($m) => in_array($m, $validModules));

          $this->userPermissionRepo->assignModules($userId, $modules);
          break;

        default:
          echo json_encode(['success' => false, 'message' => 'Invalid role specified.']);
          return;
      }

      echo json_encode([
        'success' => true,
        'message' => ucfirst($role) . ' user added successfully.',
        'user_id' => $userId,
      ]);
    } catch (\Exception $e) {
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

      $deleted = $this->userRepo->deleteUserWithCascade((int)$id, $deletedBy);

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

  // Updated
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
  // end

  public function toggleStatus($id)
  {
    header('Content-Type: application/json');
    try {
      $user = $this->userRepo->getUserById((int)$id);
      if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        return;
      }

      if (strtolower($user['role']) === 'superadmin') {
        echo json_encode(['success' => false, 'message' => 'Superadmin status cannot be changed.']);
        return;
      }

      $this->userRepo->toggleUserStatus((int)$id);

      $updatedUser = $this->userRepo->getUserById((int)$id);
      $newStatus = $updatedUser['is_active'] ? 'Active' : 'Inactive';

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

    try {
      // Itabi ang module data
      $modulesPayload = $data['modules'] ?? null;
      $modulesKeyWasPresent = array_key_exists('modules', $data);
      unset($data['modules']); // Alisin sa $data para 'di ma-save sa 'users' table

      // Kunin ang role ng user mula sa database (dahil hindi ito nagbabago)
      $currentUser = $this->userRepo->getUserById((int)$id);
      $currentRole = strtolower($currentUser['role'] ?? '');

      // Siguraduhin na hindi aksidenteng mapapalitan ang role
      unset($data['role']);
      unset($data['user_id']);

      // Handle password update kung meron
      if (isset($data['password']) && !empty($data['password'])) {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
      } else {
        unset($data['password']); // 'Wag i-update kung empty
      }

      // I-update ang user details (name, email, password, etc.)
      $userUpdated = $this->userRepo->updateUser((int)$id, $data);

      // --- LOGIC PARA LANG SA MODULES ---
      $modulesUpdated = false;

      // Titingnan lang natin kung ang role NIYA TALAGA ay admin o librarian
      if ($currentRole === 'admin' || $currentRole === 'librarian') {

        // At titingnan kung sinadya bang ipadala ang 'modules' key
        if ($modulesKeyWasPresent) {

          // Kung 'null' (uncheck all) or 'di array, gawing '[]'
          $modulesToAssign = is_array($modulesPayload) ? $modulesPayload : [];

          // I-sync ang modules (I-assume na ang assignModules ay nagde-DELETE muna bago mag-INSERT)
          $this->userPermissionRepo->assignModules((int)$id, $modulesToAssign);
          $modulesUpdated = true;
        }
      }

      // --- END NG LOGIC ---

      if ($userUpdated || $modulesUpdated) {
        // Mag-success kung may nagbago sa details O sa modules
        echo json_encode([
          'success' => true,
          'message' => 'User updated successfully.'
        ]);
      } else {
        echo json_encode([
          'success' => false,
          'message' => 'Failed to update user or no changes were made.'
        ]);
      }
    } catch (\Exception $e) {
      error_log("[UserManagementController::updateUser] " . $e->getMessage());
      echo json_encode([
        'success' => false,
        'message' => 'An internal server error occurred.'
      ]);
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

    $file = $_FILES['csv_file']['tmp_name'];
    $userRepo = new \App\Repositories\UserRepository();
    $studentRepo = new \App\Repositories\StudentRepository();
    $db = $userRepo->getDbConnection();

    $imported = 0;
    $errors = [];
    $batchSize = 500;
    $usersBuffer = [];
    $studentDataBuffer = [];

    $defaultPassword = password_hash('12345', PASSWORD_DEFAULT);
    $timestamp = date('Y-m-d H:i:s');

    if (($handle = fopen($file, 'r')) !== false) {
      // 1. Kunin ang header
      $header = array_map('strtolower', array_map('trim', fgetcsv($handle)));

      $rowNumber = 2;
      $db->beginTransaction();

      try {
        while (($row = fgetcsv($handle)) !== false) {
          $data = array_combine($header, array_pad($row, count($header), ''));

          $studentId = trim($data['student_number'] ?? '');
          $firstName = trim($data['first_name'] ?? '');
          $lastName  = trim($data['last_name'] ?? '');

          if ($studentId === '' || $firstName === '' || $lastName === '') {
            $errors[] = "Row $rowNumber: Skip - Missing required fields.";
            $rowNumber++;
            continue;
          }

          if ($userRepo->findByStudentNumber($studentId)) {
            $errors[] = "Row $rowNumber: Skip - Student ID ($studentId) already exists.";
            $rowNumber++;
            continue;
          }

          $usersBuffer[] = [
            'username'    => $studentId,
            'password'    => $defaultPassword,
            'first_name'  => $firstName,
            'middle_name' => null,
            'last_name'   => $lastName,
            'role'        => 'student',
            'is_active'   => 1,
            'created_at'  => $timestamp
          ];

          $studentDataBuffer[] = [
            'student_number' => $studentId,
            'course_id'      => null,
            'year_level'     => 1,
            'status'         => 'enrolled'
          ];

          if (count($usersBuffer) >= $batchSize) {
            $this->processBatch($userRepo, $studentRepo, $usersBuffer, $studentDataBuffer);
            $imported += count($usersBuffer);
            $usersBuffer = [];
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
        if ($db->inTransaction()) {
          $db->rollBack();
        }
        echo json_encode(['success' => false, 'message' => "Database Error: " . $e->getMessage()]);
        exit;
      }
      fclose($handle);
    }
    echo json_encode(['success' => $imported > 0, 'imported' => $imported, 'errors' => $errors]);
  }

  private function processBatch($userRepo, $studentRepo, $users, $students)
  {
    foreach ($users as $index => $userData) {
      try {
        $userId = $userRepo->insertUser($userData);

        if ($userId) {
          $studentRow = $students[$index];
          $studentRow['user_id'] = $userId;
          $studentRepo->insertStudent($studentRow);
        }
      } catch (\Exception $e) {
        continue;
      }
    }
  }
}
