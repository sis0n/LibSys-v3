<?php
// File: App/Controllers/DataController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\CollegeCourseRepository;
use App\Repositories\CampusRepository;

class DataController extends Controller
{
  private CollegeCourseRepository $collegeCourseRepo;
  private CampusRepository $campusRepo;

  public function __construct()
  {
    $this->collegeCourseRepo = new CollegeCourseRepository();
    $this->campusRepo = new CampusRepository();
  }

  public function getColleges()
  {
    header('Content-Type: application/json');
    try {
      $colleges = $this->collegeCourseRepo->getAllColleges();
      echo json_encode(['success' => true, 'colleges' => $colleges]);
    } catch (\Exception $e) {
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
    exit;
  }

  public function getCoursesByCollege()
  {
    header('Content-Type: application/json');

    $collegeId = filter_input(INPUT_GET, 'college_id', FILTER_VALIDATE_INT);

    if (!$collegeId) {
      http_response_code(400);
      echo json_encode(['success' => false, 'message' => 'College ID parameter is missing or invalid.']);
      exit;
    }

    try {
      $courses = $this->collegeCourseRepo->getCoursesByCollegeId($collegeId);
      echo json_encode(['success' => true, 'courses' => $courses]);
    } catch (\Exception $e) {
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
    exit;
  }

  public function getAllCourses()
  {
    header('Content-Type: application/json');
    try {
      $courses = $this->collegeCourseRepo->getAllCourses();
      echo json_encode(['success' => true, 'courses' => $courses]);
    } catch (\Exception $e) {
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => 'Error fetching courses: ' . $e->getMessage()]);
    }
    exit;
  }

  public function getAllCampuses()
  {
    header('Content-Type: application/json');
    try {
      $campuses = $this->campusRepo->getAllCampuses();
      echo json_encode(['success' => true, 'campuses' => $campuses]);
    } catch (\Exception $e) {
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => 'Error fetching campuses: ' . $e->getMessage()]);
    }
    exit;
  }
}
