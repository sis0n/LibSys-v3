<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\DashboardService;
use Exception;

class DashboardController extends Controller
{
    private DashboardService $dashboardService;

    public function __construct()
    {
        parent::__construct();
        $this->dashboardService = new DashboardService();
    }

    public function index()
    {
        $role = strtolower(str_replace([' ', '-'], '_', $_SESSION['role'] ?? 'guest'));
        
        $campusId = $this->getCampusFilter();
        $stats = $this->dashboardService->getStatistics($campusId);

        $viewPath = "Superadmin/dashboard";
        if ($role === 'student' || $role === 'faculty' || $role === 'staff') {
            $viewPath = ucfirst($role) . "/dashboard";
        } elseif ($role === 'campus_admin') {
            $viewPath = "campus_admin/dashboard";
        }

        $this->view($viewPath, [
            "title" => "Dashboard",
            "stats" => $stats
        ]);
    }

    public function getTrends()
    {
        try {
            $period = $_GET['period'] ?? 'monthly';
            $campusId = $this->getCampusFilter();
            $trends = $this->dashboardService->getBorrowingTrends($period, $campusId);
            $this->jsonResponse(['trends' => $trends]);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    public function getData()
    {
        try {
            $campusId = $this->getCampusFilter();
            $data = $this->dashboardService->getDetailedData($campusId);
            $this->jsonResponse($data);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }
}
