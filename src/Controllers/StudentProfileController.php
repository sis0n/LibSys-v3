<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\StudentProfileRepository;
use App\Repositories\UserRepository;

class StudentProfileController extends Controller
{
  private $studentRepo;
  private $userRepo;

  public function __construct()
  {
    parent::__construct();
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }
    $this->studentRepo = new StudentProfileRepository();
    $this->userRepo = new UserRepository();
  }

  private function json($data, $statusCode = 200)
  {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
  }

  private function saveFileLocally($file, $subFolder)
  {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
      return null;
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $uniqueId = uniqid();

    if ($subFolder === 'profile') {
      $fileName = "profile_{$uniqueId}.{$extension}";
    } else {
      $fileName = "rf_{$uniqueId}.{$extension}";
    }

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
    $maxSize = 2 * 1024 * 1024;
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if ($file['error'] !== UPLOAD_ERR_OK) return "Upload error.";
    if ($file['size'] > $maxSize) return "Image must be less than 2MB.";
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowedTypes)) return "Invalid image type. Only JPG, PNG, GIF allowed.";
    return true;
  }

  private function validatePDFUpload($file)
  {
    $maxSize = 5 * 1024 * 1024;
    if ($file['error'] !== UPLOAD_ERR_OK) return "Upload error.";
    if ($file['size'] > $maxSize) return "PDF must be less than 5MB.";
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if ($mime !== 'application/pdf') return "Invalid file type. Only PDF allowed.";
    return true;
  }

  public function getProfile()
  {
    try {
      $currentUserId = $_SESSION['user_id'] ?? null;
      if (!$currentUserId) return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
      $profile = $this->studentRepo->getProfileByUserId($currentUserId);
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

      $this->json(['success' => true, 'profile' => $profile], 200);
    } catch (\Exception $e) {
      $this->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
  }

  public function updateProfile()
  {
    try {
      $currentUserId = $_SESSION['user_id'] ?? null;
      if (!$currentUserId) return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);

      $data = $_POST;
      $profile = $this->studentRepo->getProfileByUserId($currentUserId);

      if ($profile && $profile['profile_updated'] == 1 && $profile['can_edit_profile'] == 0) {
        return $this->json(['success' => false, 'message' => 'Profile is locked.'], 403);
      }

      $isNewProfilePicUploaded = (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0);
      $isNewRegFormUploaded = (isset($_FILES['reg_form']) && $_FILES['reg_form']['error'] === 0);

      $finalProfilePicPath = $profile['profile_picture'];
      if ($isNewProfilePicUploaded) {
        $validation = $this->validateImageUpload($_FILES['profile_image']);
        if ($validation !== true) return $this->json(['success' => false, 'message' => $validation], 400);

        $imagePath = $this->saveFileLocally($_FILES['profile_image'], "profile");
        if (!$imagePath) return $this->json(['success' => false, 'message' => 'Failed to upload picture.'], 500);
        $finalProfilePicPath = $imagePath;
      }

      $finalRegFormPath = $profile['registration_form'];
      if ($isNewRegFormUploaded) {
        $validation = $this->validatePDFUpload($_FILES['reg_form']);
        if ($validation !== true) return $this->json(['success' => false, 'message' => $validation], 400);

        $pdfPath = $this->saveFileLocally($_FILES['reg_form'], "reg_form");
        if (!$pdfPath) return $this->json(['success' => false, 'message' => 'Failed to upload PDF.'], 500);
        $finalRegFormPath = $pdfPath;
      }

      $fullName = trim($data['first_name'] . ' ' . $data['last_name']);

      $gender = $data['gender'] ?? null;
      if ($gender === 'Other') {
        $gender = trim($data['gender_other'] ?? 'Other');
      }
      
      $this->userRepo->updateUser($currentUserId, [
        'first_name' => $data['first_name'],
        'middle_name' => $data['middle_name'] ?? null,
        'last_name' => $data['last_name'],
        'suffix' => $data['suffix'] ?? null,
        'gender' => $gender,
        'email' => $data['email'],
        'profile_picture' => $finalProfilePicPath
      ]);

      $this->studentRepo->updateStudentProfile($currentUserId, [
        'course_id' => $data['course_id'],
        'year_level' => $data['year_level'],
        'section' => $data['section'],
        'contact' => $data['contact'],
        'registration_form' => $finalRegFormPath,
        'profile_updated' => 1,
        'can_edit_profile' => 0 
      ]);

      if (isset($_SESSION['user_data'])) {
        $_SESSION['user_data']['fullname'] = $fullName;
        $_SESSION['user_data']['profile_picture'] = $finalProfilePicPath;
      }

      $this->json(['success' => true, 'message' => 'Profile updated successfully!'], 200);
    } catch (\Exception $e) {
      $this->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
  }
}
