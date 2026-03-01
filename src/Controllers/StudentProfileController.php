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

  /**
   * BRIDGE: Nag-uupload ng file sa Laravel Backend via Universal API
   */
  private function uploadToBackendAPI($file, $folder, $lastName = 'file')
  {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
      return null;
    }

    $apiUrl = str_replace('/storage', '/api/upload-file', STORAGE_URL);
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Gagamit ng Random Unique ID para sa security at privacy
    $uniqueId = uniqid();

    if (strpos($folder, 'profile') !== false) {
        $fileName = "profile_{$uniqueId}.{$extension}";
    } else {
        $fileName = "rf_{$uniqueId}.{$extension}";
    }

    $fileData = base64_encode(file_get_contents($file['tmp_name']));

    try {
      $ch = curl_init($apiUrl);
      $postData = json_encode([
        'filename' => $fileName,
        'folder' => $folder,
        'file' => $fileData
      ]);

      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
      ]);

      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
      curl_setopt($ch, CURLOPT_TIMEOUT, 20);

      $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);

      if ($httpCode === 200) {
          return "uploads/" . trim($folder, '/') . "/" . $fileName;
      }
      return null;
    } catch (\Exception $e) {
      error_log("API Upload Error: " . $e->getMessage());
      return null;
    }
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
      $submittedLastName = $data['last_name'] ?? 'file';

      if ($profile && $profile['profile_updated'] == 1 && $profile['can_edit_profile'] == 0) {
        return $this->json(['success' => false, 'message' => 'Profile is locked.'], 403);
      }

      $isNewProfilePicUploaded = (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0);
      $isNewRegFormUploaded = (isset($_FILES['reg_form']) && $_FILES['reg_form']['error'] === 0);

      // 1. Profile Picture
      $finalProfilePicPath = $profile['profile_picture'];
      if ($isNewProfilePicUploaded) {
        $validation = $this->validateImageUpload($_FILES['profile_image']);
        if ($validation !== true) return $this->json(['success' => false, 'message' => $validation], 400);

        $imagePath = $this->uploadToBackendAPI($_FILES['profile_image'], "profile_images", $submittedLastName);
        if (!$imagePath) return $this->json(['success' => false, 'message' => 'Failed to upload picture.'], 500);
        $finalProfilePicPath = $imagePath;
      }

      // 2. Reg Form
      $finalRegFormPath = $profile['registration_form'];
      if ($isNewRegFormUploaded) {
        $validation = $this->validatePDFUpload($_FILES['reg_form']);
        if ($validation !== true) return $this->json(['success' => false, 'message' => $validation], 400);

        $pdfPath = $this->uploadToBackendAPI($_FILES['reg_form'], "reg_forms", $submittedLastName);
        if (!$pdfPath) return $this->json(['success' => false, 'message' => 'Failed to upload PDF.'], 500);
        $finalRegFormPath = $pdfPath;
      }

      // 3. Update DB
      $fullName = trim($data['first_name'] . ' ' . $data['last_name']);
      
      $this->userRepo->updateUser($currentUserId, [
        'first_name' => $data['first_name'],
        'middle_name' => $data['middle_name'] ?? null,
        'last_name' => $data['last_name'],
        'suffix' => $data['suffix'] ?? null,
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
