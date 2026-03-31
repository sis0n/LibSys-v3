<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\UserRepository;

class CampusAdminProfileController extends Controller
{
  private UserRepository $userRepo;

  public function __construct()
  {
    parent::__construct();
    $this->userRepo = new UserRepository();
  }

  private function json($data, int $statusCode = 200)
  {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
  }

  public function getProfile()
  {
    $currentUserId = $_SESSION['user_data']['user_id'] ?? null;
    if (!$currentUserId) return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);

    $profile = $this->userRepo->getUserById($currentUserId);
    if (!$profile) return $this->json(['success' => false, 'message' => 'Profile not found.'], 404);

    // Map campus name if campus_id is available
    if (isset($profile['campus_id']) && empty($profile['campus_name'])) {
      $campusRepo = new \App\Repositories\CampusRepository();
      $campuses = $campusRepo->getAllCampuses();
      $campusName = 'N/A';
      foreach ($campuses as $campus) {
        if ($campus['campus_id'] == $profile['campus_id']) {
          $campusName = $campus['campus_name'];
          break;
        }
      }
      $profile['campus_name'] = $campusName;
    }

    $profile['allow_edit'] = 1;

    $this->json(['success' => true, 'profile' => $profile]);
  }

  public function updateProfile()
  {
    $currentUserId = $_SESSION['user_data']['user_id'] ?? null;
    if (!$currentUserId) return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);

    $data = json_decode(file_get_contents("php://input"), true);

    $gender = $data['gender'] ?? null;
    if ($gender === 'Other') {
      $gender = trim($data['gender_other'] ?? 'Other');
    }

    $userData = [
      'first_name' => trim($data['first_name'] ?? ''),
      'last_name' => trim($data['last_name'] ?? ''),
      'middle_name' => trim($data['middle_name'] ?? null),
      'suffix' => trim($data['suffix'] ?? null),
      'gender' => $gender,
      'email' => trim($data['email'] ?? ''),
    ];

    $this->userRepo->updateUser($currentUserId, $userData);

    $_SESSION['user_data']['first_name'] = $userData['first_name'];
    $_SESSION['user_data']['last_name'] = $userData['last_name'];
    $_SESSION['fullname'] = trim($userData['first_name'] . ' ' . $userData['last_name']);

    $this->json([
      'success' => true,
      'message' => 'Profile updated successfully!',
    ]);
  }
}
