<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\ReportRepository;
use Exception;

class ReportController extends Controller
{
    public function getCirculatedBooksReport()
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Content-Type: application/json');
        try {
            $filter = $_GET['filter'] ?? 'month';
            $campusId = $this->getCampusFilter();
            $repository = new ReportRepository();
            $data = $repository->getCirculatedBooksSummary($filter, $campusId);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error fetching circulated books report: ' . $e->getMessage()]);
        }
    }

    public function getCirculatedEquipmentsReport()
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Content-Type: application/json');
        try {
            $filter = $_GET['filter'] ?? 'month';
            $campusId = $this->getCampusFilter();
            $repository = new ReportRepository();
            $data = $repository->getCirculatedEquipmentsSummary($filter, $campusId);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error fetching circulated equipments report: ' . $e->getMessage()]);
        }
    }

    public function getTopVisitors()
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Content-Type: application/json');
        try {
            $filter = $_GET['filter'] ?? 'month';
            $campusId = $this->getCampusFilter();
            $repository = new ReportRepository();
            $data = $repository->getTopVisitorsFiltered($filter, $campusId);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error fetching top visitors report: ' . $e->getMessage()]);
        }
    }

    public function getTopBorrowers()
    {
        header('Content-Type: application/json');
        try {
            $filter = $_GET['filter'] ?? 'month';
            $campusId = $this->getCampusFilter();
            $repository = new ReportRepository();
            $data = $repository->getTopBorrowers($filter, $campusId);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error fetching top borrowers report: ' . $e->getMessage()]);
        }
    }

    public function getMostBorrowedBooks()
    {
        header('Content-Type: application/json');
        try {
            $filter = $_GET['filter'] ?? 'month';
            $campusId = $this->getCampusFilter();
            $repository = new ReportRepository();
            $data = $repository->getMostBorrowedBooks($filter, $campusId);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error fetching most borrowed books report: ' . $e->getMessage()]);
        }
    }

    public function getLibraryVisitsByDepartment()
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Content-Type: application/json');
        try {
            $filter = $_GET['filter'] ?? 'month';
            $campusId = $this->getCampusFilter();
            $repository = new ReportRepository();
            $data = $repository->getLibraryVisitsByDepartment($filter, $campusId);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error fetching library visits by department report: ' . $e->getMessage()]);
        }
    }

    public function getDeletedBooks()
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Content-Type: application/json');
        try {
            $filter = $_GET['filter'] ?? 'month';
            $campusId = $this->getCampusFilter();
            $repository = new ReportRepository();
            $dbData = $repository->getDeletedBooksReport($filter, $campusId);

            $statsByYear = [];
            foreach ($dbData as $row) {
                $statsByYear[$row['year']] = [
                    'month' => (int)$row['month'],
                    'today' => (int)$row['today'],
                    'filtered_count' => (int)$row['filtered_count']
                ];
            }

            $years = [2025, 2026, 2027];
            $reportData = [];
            $totalCount = 0;
            $totalMonth = 0;
            $totalToday = 0;

            foreach ($years as $year) {
                $stats = $statsByYear[$year] ?? ['month' => 0, 'today' => 0, 'filtered_count' => 0];
                $reportData[] = [
                    "year" => (string)$year,
                    "month" => $stats['month'],
                    "today" => $stats['today'],
                    "count" => $stats['filtered_count']
                ];
                $totalCount += $stats['filtered_count'];
                $totalMonth += $stats['month'];
                $totalToday += $stats['today'];
            }

            $reportData[] = [
                "year" => "TOTAL",
                "month" => $totalMonth,
                "today" => $totalToday,
                "count" => $totalCount
            ];

            echo json_encode(['success' => true, 'data' => $reportData]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error fetching deleted books report: ' . $e->getMessage()]);
        }
    }

    public function getLostDamagedBooksReport()
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Content-Type: application/json');
        try {
            $filter = $_GET['filter'] ?? 'month';
            $campusId = $this->getCampusFilter();
            $repository = new ReportRepository();
            $data = $repository->getLostDamagedBooksSummary($filter, $campusId);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error fetching lost and damaged books report: ' . $e->getMessage()]);
        }
    }

    public function getActivityReport()
    {
        header('Content-Type: application/json');
        try {
            $filter = $_GET['filter'] ?? 'month';
            $repository = new ReportRepository();
            $dashboardRepo = new \App\Repositories\DashboardRepository();
            $data = $repository->getActivityReport($filter);
            $breakdown = $dashboardRepo->getVisitorBreakdown($filter);
            
            echo json_encode([
                'success' => true, 
                'activityData' => $data,
                'visitorBreakdown' => $breakdown
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error fetching activity report: ' . $e->getMessage()]);
        }
    }

    public function getReportGraphData()
    {
        header('Content-Type: application/json');
        try {
            $filter = $_GET['filter'] ?? 'month';
            $repository = new ReportRepository();
            $dashboardRepo = new \App\Repositories\DashboardRepository();
            $response = [
                'success' => true,
                'topVisitors' => $repository->getTopVisitors(),
                'activityData' => $repository->getActivityReport($filter),
                'visitorBreakdown' => $dashboardRepo->getVisitorBreakdown($filter),
            ];
            echo json_encode($response);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to load graph data: ' . $e->getMessage(),
            ]);
        }
    }
}