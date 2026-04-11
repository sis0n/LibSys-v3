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
    parent::__construct();
    $this->collegeCourseRepo = new CollegeCourseRepository();
    $this->campusRepo = new CampusRepository();
  }

  public function getColleges()
  {
    try {
      $colleges = $this->collegeCourseRepo->getAllColleges();
      $this->jsonResponse(['colleges' => $colleges]);
    } catch (\Exception $e) {
      $this->errorResponse('Server error: ' . $e->getMessage(), 500);
    }
  }

  public function getCoursesByCollege()
  {
    $collegeId = filter_input(INPUT_GET, 'college_id', FILTER_VALIDATE_INT);

    if (!$collegeId) {
      $this->errorResponse('College ID parameter is missing or invalid.', 400);
    }

    try {
      $courses = $this->collegeCourseRepo->getCoursesByCollegeId($collegeId);
      $this->jsonResponse(['courses' => $courses]);
    } catch (\Exception $e) {
      $this->errorResponse('Server error: ' . $e->getMessage(), 500);
    }
  }

  public function getAllCourses()
  {
    try {
      $courses = $this->collegeCourseRepo->getAllCourses();
      $this->jsonResponse(['courses' => $courses]);
    } catch (\Exception $e) {
      $this->errorResponse('Error fetching courses: ' . $e->getMessage(), 500);
    }
  }

  public function getAllCampuses()
  {
    try {
      $campuses = $this->campusRepo->getAllCampuses();
      $this->jsonResponse(['campuses' => $campuses]);
    } catch (\Exception $e) {
      $this->errorResponse('Error fetching campuses: ' . $e->getMessage(), 500);
    }
  }
}
