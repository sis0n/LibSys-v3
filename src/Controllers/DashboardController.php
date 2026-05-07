<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\RoleHelper;
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
        $role = RoleHelper::compareNormalize($_SESSION['role'] ?? 'guest');
        
        $campusId = $this->getCampusFilter();
        $stats = $this->dashboardService->getStatistics($campusId);

        if ($role === 'superadmin') {
            $viewPath = "superadmin/dashboard";
        } elseif (RoleHelper::isBorrower($role)) { 
            $viewPath = "user/dashboard";
        } elseif (RoleHelper::isAdmin($role) || RoleHelper::isLibrarian($role)) {
            $permissions = $_SESSION['user_permissions'] ?? [];
            $redirectPath = \App\Models\User::getFirstAccessibleModuleUrl($role, $permissions);
            header('Location: ' . \BASE_URL . '/' . $redirectPath);
            exit;
        } else {
            header('Location: ' . \BASE_URL . '/login');
            exit;
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
