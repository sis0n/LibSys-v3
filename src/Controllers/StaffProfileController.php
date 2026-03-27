<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\StaffProfileRepository;
use App\Repositories\UserRepository;

class StaffProfileController extends Controller
{
  private StaffProfileRepository $staffRepo;
  private UserRepository $userRepo;

  public function __construct()
  {
    if (session_status() === PHP_SESSION_NONE) session_start();

    $this->staffRepo = new StaffProfileRepository();
    $this->userRepo = new UserRepository();
  }

  private function json($data, int $statusCode = 200)
  {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
  }

  /**
   * Save file locally to public/storage/uploads/
   */
  private function saveFileLocally($file, $subFolder)
  {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
      return null;
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $uniqueId = uniqid();
    $fileName = "profile_{$uniqueId}.{$extension}";

    $uploadDir = ROOT_PATH . "/public/storage/uploads/{$subFolder}/";
    
    if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0777, true);
    }

    $destPath = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $destPath)) {
      return "storage/uploads/{$subFolder}/" . $fileName;
    }

    return null;
  }

  private function validateImageUpload($file)
  {
    $maxSize = 2 * 1024 * 1024; // 2MB
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

    if ($file['error'] !== UPLOAD_ERR_OK) return "Upload error.";
    if ($file['size'] > $maxSize) return "Image must be less than 2MB.";

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedTypes)) return "Invalid image type. Only JPG, PNG, GIF allowed.";
    return true;
  }

  public function getProfile()
  {
    try {
      $currentUserId = $_SESSION['user_id'] ?? null;
      if (!$currentUserId) return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);

      $profile = $this->staffRepo->getProfileByUserId($currentUserId);
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

      $profile['allow_edit'] = 1; // Staff can always edit

      $this->json(['success' => true, 'profile' => $profile]);
    } catch (\Exception $e) {
      error_log("StaffProfileController::getProfile Error: " . $e->getMessage());
      $this->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
  }

  public function updateProfile()
  {
    try {
      $currentUserId = $_SESSION['user_id'] ?? null;
      if (!$currentUserId) return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);

      $data = $_POST;
      $profile = $this->staffRepo->getProfileByUserId($currentUserId);
      if (!$profile) return $this->json(['success' => false, 'message' => 'Profile not found.'], 404);

      $requiredFields = ['first_name', 'last_name', 'email', 'position', 'contact'];
      $missingFields = [];
      foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') $missingFields[] = $field;
      }
      if (!empty($missingFields)) {
        return $this->json(['success' => false, 'message' => 'Missing required fields: ' . implode(', ', $missingFields)], 400);
      }

      $isNewFileUploaded = (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0);
      if (empty($profile['profile_picture']) && !$isNewFileUploaded) {
        return $this->json(['success' => false, 'message' => 'Profile picture is required.'], 400);
      }

      if (!preg_match('/^\d{11}$/', $data['contact'])) {
        return $this->json(['success' => false, 'message' => 'Contact number must be 11 digits.'], 400);
      }
      if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return $this->json(['success' => false, 'message' => 'Invalid email address.'], 400);
      }

      $fullName = trim(implode(' ', array_filter([$data['first_name'], $data['middle_name'] ?? null, $data['last_name'], $data['suffix'] ?? null])));
      
      $gender = $data['gender'] ?? null;
      if ($gender === 'Other') {
        $gender = trim($data['gender_other'] ?? 'Other');
      }

      $userData = [
        'first_name' => $data['first_name'],
        'middle_name' => $data['middle_name'] ?? null,
        'last_name' => $data['last_name'],
        'suffix' => $data['suffix'] ?? null,
        'gender' => $gender,
        'full_name' => $fullName,
        'email' => $data['email']
      ];

      $finalProfilePicPath = $profile['profile_picture'];
      if ($isNewFileUploaded) {
        $validation = $this->validateImageUpload($_FILES['profile_image']);
        if ($validation !== true) return $this->json(['success' => false, 'message' => $validation], 400);
        
        $imagePath = $this->saveFileLocally($_FILES['profile_image'], "profile");
        if ($imagePath === null) {
          return $this->json(['success' => false, 'message' => 'Failed to upload profile picture locally.'], 500);
        }
        $finalProfilePicPath = $imagePath;
      }
      $userData['profile_picture'] = $finalProfilePicPath;

      $this->userRepo->updateUser($currentUserId, $userData);

      $staffData = [
        'position' => $data['position'],
        'contact' => $data['contact'],
        'profile_updated' => 1
      ];
      $this->staffRepo->updateStaffProfile($currentUserId, $staffData);

      if (isset($_SESSION['user_data'])) {
        $_SESSION['user_data']['fullname'] = $fullName;
        if ($finalProfilePicPath) $_SESSION['user_data']['profile_picture'] = $finalProfilePicPath;
      }

      $this->json(['success' => true, 'message' => 'Profile updated successfully!']);
    } catch (\Exception $e) {
      error_log("StaffProfileController::updateProfile Error: " . $e->getMessage());
      $this->json(['success' => false, 'message' => 'Error updating profile: ' . $e->getMessage()], 500);
    }
  }
}
