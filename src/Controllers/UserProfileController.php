<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\UserProfileService;
use Exception;

class UserProfileController extends Controller
{
    private UserProfileService $profileService;

    public function __construct()
    {
        parent::__construct();
        $this->profileService = new UserProfileService();
    }

    public function index()
    {
        $this->view('management/profile/index', [
            'title' => 'My Profile',
            'currentPage' => 'myProfile'
        ]);
    }

    public function changePasswordPage()
    {
        $this->view('management/profile/changePassword', [
            'title' => 'Change Password',
            'currentPage' => 'changePassword'
        ]);
    }

    public function getProfile()
    {
        try {
            $userId = $_SESSION['user_id'] ?? $_SESSION['user_data']['user_id'] ?? null;
            $role = $_SESSION['role'] ?? 'guest';
            
            if (!$userId) throw new Exception('Unauthorized', 401);

            $profile = $this->profileService->getProfile((int)$userId, $role);
            return $this->jsonResponse(['profile' => $profile]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function updateProfile()
    {
        try {
            $userId = $_SESSION['user_id'] ?? $_SESSION['user_data']['user_id'] ?? null;
            $role = $_SESSION['role'] ?? 'guest';
            
            if (!$userId) throw new Exception('Unauthorized', 401);

            $contentType = $_SERVER["CONTENT_TYPE"] ?? '';
            if (strpos($contentType, "application/json") !== false) {
                $data = $this->getJsonData();
            } else {
                $data = $this->getPostData();
            }

            $result = $this->profileService->updateProfile((int)$userId, $role, $data, $_FILES);

            // Sync Session
            if (isset($_SESSION['user_data'])) {
                $_SESSION['user_data']['first_name'] = $result['first_name'];
                $_SESSION['user_data']['last_name'] = $result['last_name'];
                if ($result['profile_picture']) {
                    $_SESSION['user_data']['profile_picture'] = $result['profile_picture'];
                }
            }
            $_SESSION['fullname'] = $result['fullname'];

            return $this->jsonResponse(['message' => 'Profile updated successfully!']);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500);
        }
    }
}
