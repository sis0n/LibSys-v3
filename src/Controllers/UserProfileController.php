<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\UserRepository;
use App\Repositories\StudentProfileRepository;
use App\Repositories\FacultyProfileRepository;
use App\Repositories\StaffProfileRepository;
use App\Repositories\CampusRepository;

class UserProfileController extends Controller
{
    private UserRepository $userRepo;
    private $roleRepo;
    private string $role;

    public function __construct()
    {
        parent::__construct();
        $this->userRepo = new UserRepository();
        $this->role = strtolower(str_replace([' ', '-'], '_', $_SESSION['role'] ?? ''));

        // Initialize the appropriate repository based on role
        switch ($this->role) {
            case 'student':
                $this->roleRepo = new StudentProfileRepository();
                break;
            case 'faculty':
                $this->roleRepo = new FacultyProfileRepository();
                break;
            case 'staff':
                $this->roleRepo = new StaffProfileRepository();
                break;
            default:
                // For admin, librarian, superadmin, campus_admin, they only use UserRepository
                $this->roleRepo = null;
                break;
        }
    }

    public function getProfile()
    {
        try {
            $userId = $_SESSION['user_id'] ?? $_SESSION['user_data']['user_id'] ?? null;
            if (!$userId) return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);

            if ($this->roleRepo) {
                $profile = $this->roleRepo->getProfileByUserId($userId);
            } else {
                $profile = $this->userRepo->getUserById($userId);
            }

            if (!$profile) return $this->json(['success' => false, 'message' => 'Profile not found.'], 404);

            // Common campus mapping
            if (isset($profile['campus_id']) && empty($profile['campus_name'])) {
                $campusRepo = new CampusRepository();
                $campus = $campusRepo->getById((int)$profile['campus_id']);
                $profile['campus_name'] = $campus['campus_name'] ?? 'N/A';
            }

            // Role specific flags
            $profile['allow_edit'] = 1;

            return $this->json(['success' => true, 'profile' => $profile]);
        } catch (\Exception $e) {
            error_log("UserProfileController::getProfile Error: " . $e->getMessage());
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateProfile()
    {
        try {
            $userId = $_SESSION['user_id'] ?? $_SESSION['user_data']['user_id'] ?? null;
            if (!$userId) return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);

            // Get Data (Handle both JSON and POST)
            $contentType = $_SERVER["CONTENT_TYPE"] ?? '';
            if (strpos($contentType, "application/json") !== false) {
                $data = json_decode(file_get_contents("php://input"), true);
            } else {
                $data = $_POST;
            }

            if ($this->roleRepo) {
                $profile = $this->roleRepo->getProfileByUserId($userId);
            } else {
                $profile = $this->userRepo->getUserById($userId);
            }

            if (!$profile) return $this->json(['success' => false, 'message' => 'Profile not found.'], 404);

            // Student specific lock check
            if ($this->role === 'student' && ($profile['profile_updated'] ?? 0) == 1 && ($profile['can_edit_profile'] ?? 0) == 0) {
                return $this->json(['success' => false, 'message' => 'Profile is locked.'], 403);
            }

            $requiredFields = ['first_name', 'last_name', 'email'];
            if ($this->roleRepo) $requiredFields[] = 'contact';
            
            // Add role-specific required fields
            if ($this->role === 'student') $requiredFields = array_merge($requiredFields, ['course_id', 'year_level', 'section']);
            if ($this->role === 'faculty') $requiredFields[] = 'college_id';
            if ($this->role === 'staff') $requiredFields[] = 'position';

            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || trim($data[$field]) === '') $missingFields[] = $field;
            }

            if (!empty($missingFields)) {
                return $this->json(['success' => false, 'message' => 'Missing fields: ' . implode(', ', $missingFields)], 400);
            }

            // Validation
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->json(['success' => false, 'message' => 'Invalid email address.'], 400);
            }
            if (isset($data['contact']) && !preg_match('/^\d{11}$/', $data['contact'])) {
                return $this->json(['success' => false, 'message' => 'Contact must be 11 digits.'], 400);
            }

            // Handle Profile Picture
            $finalProfilePic = $profile['profile_picture'] ?? null;
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
                $valid = $this->validateImageUpload($_FILES['profile_image']);
                if ($valid !== true) return $this->json(['success' => false, 'message' => $valid], 400);
                
                $path = $this->saveFileLocally($_FILES['profile_image'], "profile");
                if (!$path) return $this->json(['success' => false, 'message' => 'Upload failed.'], 500);
                $finalProfilePic = $path;
            }

            // User Table Update
            $fullName = trim(implode(' ', array_filter([$data['first_name'], $data['middle_name'] ?? null, $data['last_name'], $data['suffix'] ?? null])));
            $gender = (isset($data['gender']) && $data['gender'] === 'Other') ? trim($data['gender_other'] ?? 'Other') : ($data['gender'] ?? null);

            $userData = [
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'suffix' => $data['suffix'] ?? null,
                'gender' => $gender,
                'email' => $data['email']
            ];

            if ($finalProfilePic) {
                $userData['profile_picture'] = $finalProfilePic;
            }

            // Campus ID for superadmin/admins if provided
            if (isset($data['campus_id'])) {
                $userData['campus_id'] = $data['campus_id'];
            }

            $this->userRepo->updateUser($userId, $userData);

            // Role Table Update (if applicable)
            if ($this->roleRepo) {
                $roleData = ['contact' => $data['contact'], 'profile_updated' => 1];
                
                if ($this->role === 'student') {
                    $finalRegForm = $profile['registration_form'] ?? null;
                    if (isset($_FILES['reg_form']) && $_FILES['reg_form']['error'] === 0) {
                        $valid = $this->validatePDFUpload($_FILES['reg_form']);
                        if ($valid !== true) return $this->json(['success' => false, 'message' => $valid], 400);
                        $path = $this->saveFileLocally($_FILES['reg_form'], "reg_form", "rf");
                        if ($path) $finalRegForm = $path;
                    }
                    $roleData = array_merge($roleData, [
                        'course_id' => $data['course_id'],
                        'year_level' => $data['year_level'],
                        'section' => $data['section'],
                        'registration_form' => $finalRegForm,
                        'can_edit_profile' => 0
                    ]);
                    $this->roleRepo->updateStudentProfile($userId, $roleData);
                } elseif ($this->role === 'faculty') {
                    $roleData['college_id'] = $data['college_id'];
                    $this->roleRepo->updateFacultyProfile($userId, $roleData);
                } elseif ($this->role === 'staff') {
                    $roleData['position'] = $data['position'];
                    $this->roleRepo->updateStaffProfile($userId, $roleData);
                }
            }

            // Sync Session
            if (isset($_SESSION['user_data'])) {
                $_SESSION['user_data']['first_name'] = $userData['first_name'];
                $_SESSION['user_data']['last_name'] = $userData['last_name'];
                if ($finalProfilePic) $_SESSION['user_data']['profile_picture'] = $finalProfilePic;
            }
            $_SESSION['fullname'] = $fullName;

            return $this->json(['success' => true, 'message' => 'Profile updated successfully!']);
        } catch (\Exception $e) {
            error_log("UserProfileController::updateProfile Error: " . $e->getMessage());
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
