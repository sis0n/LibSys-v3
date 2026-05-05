<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\StudentProfileRepository;
use App\Repositories\FacultyProfileRepository;
use App\Repositories\StaffProfileRepository;
use App\Repositories\CampusRepository;
use App\Core\RoleHelper;
use Exception;

class UserProfileService
{
    private UserRepository $userRepo;
    private CampusRepository $campusRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
        $this->campusRepo = new CampusRepository();
    }

    /**
     * Get profile data based on role
     */
    public function getProfile(int $userId, string $role): array
    {
        $role = RoleHelper::compareNormalize($role);
        $repo = $this->getRoleRepo($role);

        if ($repo) {
            $profile = $repo->getProfileByUserId($userId);
        } else {
            $profile = $this->userRepo->getUserById($userId);
        }

        if (!$profile) throw new Exception('Profile not found.');

        if (isset($profile['campus_id']) && empty($profile['campus_name'])) {
            $campus = $this->campusRepo->getById((int)$profile['campus_id']);
            $profile['campus_name'] = $campus['campus_name'] ?? 'N/A';
        }

        $profile['allow_edit'] = 1;
        return $profile;
    }

    /**
     * Update user profile
     */
    public function updateProfile(int $userId, string $role, array $data, ?array $files = []): array
    {
        $role = RoleHelper::compareNormalize($role);
        $repo = $this->getRoleRepo($role);

        if ($repo) {
            $currentProfile = $repo->getProfileByUserId($userId);
        } else {
            $currentProfile = $this->userRepo->getUserById($userId);
        }

        if (!$currentProfile) throw new Exception('Profile not found.');

        // Student lock check
        if ($role === RoleHelper::STUDENT && ($currentProfile['profile_updated'] ?? 0) == 1 && ($currentProfile['can_edit_profile'] ?? 0) == 0) {
            throw new Exception('Profile is locked.', 403);
        }

        $this->validateFields($role, $data, (bool)$repo);

        $storageService = new StorageService();
        $finalProfilePic = $currentProfile['profile_picture'] ?? null;

        if (isset($files['profile_image']) && $files['profile_image']['error'] === 0) {
            $storageService->validateImage($files['profile_image']);
            $finalProfilePic = $storageService->saveFile($files['profile_image'], "profile", "user");
        }

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

        if (isset($data['campus_id'])) {
            $userData['campus_id'] = $data['campus_id'];
        }

        $this->userRepo->updateUser($userId, $userData);

        if ($repo) {
            $roleData = ['contact' => $data['contact'], 'profile_updated' => 1];
            
            if ($role === 'student') {
                $finalRegForm = $currentProfile['registration_form'] ?? null;
                
                // Explicit removal check
                if (isset($data['remove_rf']) && $data['remove_rf'] == '1') {
                    $finalRegForm = null;
                }

                if (isset($files['reg_form']) && $files['reg_form']['error'] === 0) {
                    $storageService->validatePDF($files['reg_form']);
                    $finalRegForm = $storageService->saveFile($files['reg_form'], "reg_form", "rf");
                }
                
                $roleData = array_merge($roleData, [
                    'course_id' => $data['course_id'],
                    'year_level' => $data['year_level'],
                    'section' => $data['section'],
                    'registration_form' => $finalRegForm,
                    'can_edit_profile' => 0
                ]);
                $repo->updateStudentProfile($userId, $roleData);
            } elseif ($role === 'faculty') {
                $roleData['college_id'] = $data['college_id'];
                $repo->updateFacultyProfile($userId, $roleData);
            } elseif ($role === 'staff') {
                $roleData['position'] = $data['position'];
                $repo->updateStaffProfile($userId, $roleData);
            }
        }

        $fullName = trim(implode(' ', array_filter([$userData['first_name'], $userData['middle_name'], $userData['last_name'], $userData['suffix']])));

        return [
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name'],
            'profile_picture' => $finalProfilePic,
            'fullname' => $fullName
        ];
    }

    private function validateFields(string $role, array $data, bool $hasRoleTable): void
    {
        $requiredFields = ['first_name', 'last_name', 'email'];
        if ($hasRoleTable) $requiredFields[] = 'contact';
        
        if ($role === 'student') $requiredFields = array_merge($requiredFields, ['course_id', 'year_level', 'section']);
        if ($role === 'faculty') $requiredFields[] = 'college_id';
        if ($role === 'staff') $requiredFields[] = 'position';

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') throw new Exception("Missing field: $field");
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) throw new Exception('Invalid email address.');
        if (isset($data['contact']) && !preg_match('/^\d{11}$/', $data['contact'])) throw new Exception('Contact must be 11 digits.');
    }

    private function getRoleRepo(string $role)
    {
        switch ($role) {
            case 'student': return new StudentProfileRepository();
            case 'faculty': return new FacultyProfileRepository();
            case 'staff':   return new StaffProfileRepository();
            default:        return null;
        }
    }
}
