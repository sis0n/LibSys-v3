<?php

namespace App\Repositories;

use App\Repositories\UserRepository;
use App\Repositories\UserPermissionModuleRepository;
use App\Repositories\CollegeCourseRepository;

class AuthRepository
{
  private UserRepository $userRepo;
  private UserPermissionModuleRepository $userModuleRepo;
  private CollegeCourseRepository $collegeCourseRepo;

  public function __construct()
  {
    $this->userRepo = new UserRepository();
    $this->userModuleRepo = new UserPermissionModuleRepository();
    $this->collegeCourseRepo = new CollegeCourseRepository();
  }

  public function attemptLogin(string $username, string $password): ?array
  {
    $user = $this->userRepo->findByIdentifier($username);

    // Add debug logs here
    error_log('AuthRepository::attemptLogin - User data retrieved: ' . print_r($user, true));
    error_log('AuthRepository::attemptLogin - Submitted password: ' . $password); // Password from the login form
    error_log('AuthRepository::attemptLogin - Stored hash: ' . ($user['password'] ?? 'not set')); // Password hash from DB

    if ($user && isset($user['password']) && password_verify($password, $user['password'])) {
      $firstName = $user['first_name'] ?? '';
      $middleName = $user['middle_name'] ?? '';
      $lastName = $user['last_name'] ?? '';
      $suffix = $user['suffix'] ?? '';
      $fullName = implode(' ', array_filter([$firstName, $middleName, $lastName, $suffix]));

      $modules = [];
      $role = strtolower(trim($user['role'] ?? 'guest'));

      $departmentOrCourse = null;

      if ($role === 'student' && isset($user['course_id'])) {
        $course = $this->collegeCourseRepo->getCourseById((int)$user['course_id']);
        $departmentOrCourse = $course['course_code'] ?? 'N/A';
      } elseif (in_array($role, ['faculty', 'staff']) && isset($user['college_id'])) {
        $college = $this->collegeCourseRepo->getCollegeById((int)$user['college_id']);
        $departmentOrCourse = $college['college_code'] ?? 'N/A';
      }

      if (in_array($role, ['admin', 'librarian', 'superadmin'])) {
        $modules = $this->userModuleRepo->getModulesByUserId($user['user_id']);
      }

      $finalUsername = $user['username'] ?? $user['student_number'] ?? $username;
      $finalFullname = !empty(trim($fullName)) ? $fullName : 'User';

      return [
        'raw_user' => $user,
        'session_payload' => [
          'user_data' => [
            'user_id' => $user['user_id'],
            'student_id' => $user['student_id'] ?? null,
            'faculty_id' => $user['faculty_id'] ?? null,
            'staff_id' => $user['staff_id'] ?? null,
            'program_department' => $departmentOrCourse,
            'username' => $finalUsername,
            'role' => $role,
            'fullname' => $finalFullname,
            'profile_picture' => $user['profile_picture'] ?? null,
            'is_active' => $user['is_active'] ?? 0,
            'modules' => $modules,
          ],
          'user_id' => $user['user_id'],
          'username' => $finalUsername,
          'role' => $role,
          'user_permissions' => $modules
        ]
      ];
    } else {
        // Log if password verification failed or user data was incomplete
        error_log('AuthRepository::attemptLogin - Password verification failed or user data incomplete.');
        return null;
    }
  }

  public function logout(): void
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
      $params = session_get_cookie_params();
      setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
      );
    }

    session_destroy();
  }

  public function changePassword(int $userId, string $newPassword): bool
  {
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    return $this->userRepo->updatePassword($userId, $hashedPassword);
  }
}
