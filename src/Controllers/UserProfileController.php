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

    public function getProfile()
    {
        header('Content-Type: application/json');
        try {
            $userId = $_SESSION['user_id'] ?? $_SESSION['user_data']['user_id'] ?? null;
            $role = $_SESSION['role'] ?? 'guest';
            
            if (!$userId) throw new Exception('Unauthorized', 401);

            $profile = $this->profileService->getProfile((int)$userId, $role);
            echo json_encode(['success' => true, 'profile' => $profile]);
        } catch (Exception $e) {
            http_response_code($e->getCode() ?: 500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function updateProfile()
    {
        header('Content-Type: application/json');
        try {
            $userId = $_SESSION['user_id'] ?? $_SESSION['user_data']['user_id'] ?? null;
            $role = $_SESSION['role'] ?? 'guest';
            
            if (!$userId) throw new Exception('Unauthorized', 401);

            $contentType = $_SERVER["CONTENT_TYPE"] ?? '';
            if (strpos($contentType, "application/json") !== false) {
                $data = json_decode(file_get_contents("php://input"), true);
            } else {
                $data = $_POST;
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

            echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
        } catch (Exception $e) {
            http_response_code($e->getCode() ?: 500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
