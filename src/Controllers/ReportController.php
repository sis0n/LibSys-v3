<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\ReportRepository;
use Exception;

class ReportController extends Controller
{
    public function getCirculatedBooksReport()
    {
        try {
            $filter = $_GET['filter'] ?? 'month';
            $campusId = $this->getCampusFilter();
            $repository = new ReportRepository();
            $data = $repository->getCirculatedBooksSummary($filter, $campusId);
            return $this->jsonResponse(['data' => $data]);
        } catch (Exception $e) {
            return $this->errorResponse('Error fetching circulated books report: ' . $e->getMessage(), 500);
        }
    }

    public function getCirculatedEquipmentsReport()
    {
        try {
            $filter = $_GET['filter'] ?? 'month';
            $campusId = $this->getCampusFilter();
            $repository = new ReportRepository();
            $data = $repository->getCirculatedEquipmentsSummary($filter, $campusId);
            return $this->jsonResponse(['data' => $data]);
        } catch (Exception $e) {
            return $this->errorResponse('Error fetching circulated equipments report: ' . $e->getMessage(), 500);
        }
    }

    public function getTopVisitors()
    {
        try {
            $filter = $_GET['filter'] ?? 'month';
            $campusId = $this->getCampusFilter();
            $repository = new ReportRepository();
            $data = $repository->getTopVisitorsFiltered($filter, $campusId);
            return $this->jsonResponse(['data' => $data]);
        } catch (Exception $e) {
            return $this->errorResponse('Error fetching top visitors report: ' . $e->getMessage(), 500);
        }
    }

    public function getTopBorrowers()
    {
        try {
            $filter = $_GET['filter'] ?? 'month';
            $campusId = $this->getCampusFilter();
            $repository = new ReportRepository();
            $data = $repository->getTopBorrowers($filter, $campusId);
            return $this->jsonResponse(['data' => $data]);
        } catch (Exception $e) {
            return $this->errorResponse('Error fetching top borrowers report: ' . $e->getMessage(), 500);
        }
    }

    public function getMostBorrowedBooks()
    {
        try {
            $filter = $_GET['filter'] ?? 'month';
            $campusId = $this->getCampusFilter();
            $repository = new ReportRepository();
            $data = $repository->getMostBorrowedBooks($filter, $campusId);
            return $this->jsonResponse(['data' => $data]);
        } catch (Exception $e) {
            return $this->errorResponse('Error fetching most borrowed books report: ' . $e->getMessage(), 500);
        }
    }

    public function getLibraryVisitsByDepartment()
    {
        try {
            $filter = $_GET['filter'] ?? 'month';
            $campusId = $this->getCampusFilter();
            $repository = new ReportRepository();
            $data = $repository->getLibraryVisitsByDepartment($filter, $campusId);
            return $this->jsonResponse(['data' => $data]);
        } catch (Exception $e) {
            return $this->errorResponse('Error fetching library visits by department report: ' . $e->getMessage(), 500);
        }
    }

    public function getDeletedBooks()
    {
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

            return $this->jsonResponse(['data' => $reportData]);
        } catch (Exception $e) {
            return $this->errorResponse('Error fetching deleted books report: ' . $e->getMessage(), 500);
        }
    }

    public function getLostDamagedBooksReport()
    {
        try {
            $filter = $_GET['filter'] ?? 'month';
            $campusId = $this->getCampusFilter();
            $repository = new ReportRepository();
            $data = $repository->getLostDamagedBooksSummary($filter, $campusId);
            return $this->jsonResponse(['data' => $data]);
        } catch (Exception $e) {
            return $this->errorResponse('Error fetching lost and damaged books report: ' . $e->getMessage(), 500);
        }
    }

    public function getActivityReport()
    {
        try {
            $filter = $_GET['filter'] ?? 'month';
            $repository = new ReportRepository();
            $dashboardRepo = new \App\Repositories\DashboardRepository();
            $data = $repository->getActivityReport($filter);
            $breakdown = $dashboardRepo->getVisitorBreakdown($filter);
            
            return $this->jsonResponse([
                'activityData' => $data,
                'visitorBreakdown' => $breakdown
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('Error fetching activity report: ' . $e->getMessage(), 500);
        }
    }

    public function getReportGraphData()
    {
        try {
            $filter = $_GET['filter'] ?? 'month';
            $repository = new ReportRepository();
            $dashboardRepo = new \App\Repositories\DashboardRepository();
            $response = [
                'topVisitors' => $repository->getTopVisitors(),
                'activityData' => $repository->getActivityReport($filter),
                'visitorBreakdown' => $dashboardRepo->getVisitorBreakdown($filter),
            ];
            return $this->jsonResponse($response);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to load graph data: ' . $e->getMessage(), 500);
        }
    }
}
