<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\FacultyProfileRepository;
use App\Repositories\UserRepository;

class FacultyProfileController extends Controller
{
  private FacultyProfileRepository $facultyRepo;
  private UserRepository $userRepo;

  public function __construct()
  {
    if (session_status() === PHP_SESSION_NONE) session_start();

    $this->facultyRepo = new FacultyProfileRepository();
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
   * BRIDGE: Nag-uupload ng file sa Laravel Backend via Universal API
   */
  private function uploadToBackendAPI($file, $folder)
  {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
      return null;
    }

    $apiUrl = str_replace('/storage', '/api/upload-file', STORAGE_URL);
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Gagamit ng Random Unique ID para sa security at privacy
    $uniqueId = uniqid();
    $prefix = (strpos($folder, 'profile') !== false) ? 'profile_' : 'file_';
    $fileName = "{$prefix}{$uniqueId}.{$extension}";

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
      error_log("API Upload Error (Faculty): " . $e->getMessage());
      return null;
    }
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

      $profile = $this->facultyRepo->getProfileByUserId($currentUserId);
      if (!$profile) return $this->json(['success' => false, 'message' => 'Profile not found.'], 404);

      $profile['allow_edit'] = 1;
      $this->json(['success' => true, 'profile' => $profile]);
    } catch (\Exception $e) {
      error_log("FacultyProfileController::getProfile Error: " . $e->getMessage());
      $this->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
  }

  public function updateProfile()
  {
    try {
      $currentUserId = $_SESSION['user_id'] ?? null;
      if (!$currentUserId) return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);

      $data = $_POST;
      $profile = $this->facultyRepo->getProfileByUserId($currentUserId);
      if (!$profile) return $this->json(['success' => false, 'message' => 'Profile not found.'], 404);

      $requiredFields = ['first_name', 'last_name', 'email', 'contact', 'college_id'];
      $missingFields = [];

      foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '' || ($field === 'college_id' && (!is_numeric($data[$field]) || (int)$data[$field] === 0))) {
          $missingFields[] = $field;
        }
      }
      if (!empty($missingFields)) {
        return $this->json([
          'success' => false,
          'message' => 'Missing required fields: ' . implode(', ', $missingFields)
        ], 400);
      }

      $isNewFileUploaded = (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0);
      if (empty($profile['profile_picture']) && !$isNewFileUploaded) {
        return $this->json(['success' => false, 'message' => 'Profile picture is required.'], 400);
      }

      $collegeId = filter_var($data['college_id'], FILTER_VALIDATE_INT);
      if (!$collegeId) {
        return $this->json(['success' => false, 'message' => 'Invalid college selection.'], 400);
      }

      if (!preg_match('/^\d{11}$/', $data['contact'])) {
        return $this->json(['success' => false, 'message' => 'Contact number must be 11 digits.'], 400);
      }

      if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return $this->json(['success' => false, 'message' => 'Invalid email address.'], 400);
      }

      $fullName = trim(implode(' ', array_filter([$data['first_name'], $data['middle_name'] ?? null, $data['last_name'], $data['suffix'] ?? null])));
      $userData = [
        'first_name' => $data['first_name'],
        'middle_name' => $data['middle_name'] ?? null,
        'last_name' => $data['last_name'],
        'suffix' => $data['suffix'] ?? null,
        'full_name' => $fullName,
        'email' => $data['email']
      ];

      $finalProfilePicPath = $profile['profile_picture'];
      if ($isNewFileUploaded) {
        $validation = $this->validateImageUpload($_FILES['profile_image']);
        if ($validation !== true) return $this->json(['success' => false, 'message' => $validation], 400);
        
        $imagePath = $this->uploadToBackendAPI($_FILES['profile_image'], "profile_images");
        if (!$imagePath) {
          return $this->json(['success' => false, 'message' => 'Failed to upload profile picture to mobile storage.'], 500);
        }
        $finalProfilePicPath = $imagePath;
      }
      $userData['profile_picture'] = $finalProfilePicPath;

      $this->userRepo->updateUser($currentUserId, $userData);

      $facultyData = [
        'college_id' => $collegeId,
        'contact' => $data['contact'],
        'profile_updated' => 1
      ];

      $this->facultyRepo->updateFacultyProfile($currentUserId, $facultyData);

      if (isset($_SESSION['user_data'])) {
        $_SESSION['user_data']['fullname'] = $fullName;
        if ($finalProfilePicPath) $_SESSION['user_data']['profile_picture'] = $finalProfilePicPath;
      }

      $this->json(['success' => true, 'message' => 'Profile updated successfully!']);
    } catch (\Exception $e) {
      error_log("FacultyProfileController::updateProfile Error: " . $e->getMessage());
      $this->json(['success' => false, 'message' => 'Error updating profile: ' . $e->getMessage()], 500);
    }
  }
}
