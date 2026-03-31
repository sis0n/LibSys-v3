<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\DashboardRepository;

class DashboardController extends Controller
{
  protected DashboardRepository $dashboardRepo;

  public function __construct()
  {
    parent::__construct();
    $this->dashboardRepo = new DashboardRepository();
  }

  public function getData()
  {
    try {
      $filter = $_GET['filter'] ?? 'month';
      $campusId = $this->getCampusFilter();
      $stats = $this->dashboardRepo->getDashboardStats($campusId)['data'] ?? [];

      $response = [
        'success' => true,
        'data' => [
          'students' => $stats['students'] ?? 0,
          'faculty' => $stats['faculty'] ?? 0,
          'staff' => $stats['staff'] ?? 0,
          'attendance_today' => $stats['attendance_today'] ?? 0,
          'books' => $stats['books'] ?? 0,
          'borrowed_books' => $stats['borrowed_books'] ?? 0,
          'totalUsers' => $stats['totalUsers'] ?? 0,
          'usersAddedThisMonth' => $stats['usersAddedThisMonth'] ?? 0,
          'availableBooks' => $stats['availableBooks'] ?? 0,
          'availableBooksPercent' => $stats['availableBooksPercent'] ?? 0,
          'borrowedBooksPercent' => $stats['borrowedBooksPercent'] ?? 0,
        ],
        'topVisitors' => $this->dashboardRepo->getTopVisitors(5, $campusId),
        'weeklyActivity' => $this->dashboardRepo->getWeeklyActivity($campusId),
        'visitorBreakdown' => $this->dashboardRepo->getVisitorBreakdown($filter, $campusId),
        'popularBooks' => $this->dashboardRepo->getPopularBooks(5, $campusId),
        'recentActivities' => $this->dashboardRepo->getRecentActivities(5, $campusId),
        'overdueBooks' => $this->dashboardRepo->getOverdueBooks(5, $campusId),
      ];

      echo json_encode($response);
    } catch (\Exception $e) {
      echo json_encode([
        'success' => false,
        'message' => 'Failed to load dashboard data: ' . $e->getMessage(),
      ]);
    }
  }
}
