<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\AttendanceRepository;
use DateTime;
use DateTimeZone;
use Exception;

class AttendanceController extends Controller
{
    private AttendanceRepository $attendanceRepo;

    public function __construct()
    {
        parent::__construct();
        $this->attendanceRepo = new AttendanceRepository();
    }

    public function fetchAttendance()
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $userId = $_SESSION['user_id'];
            $allLogs = $this->attendanceRepo->getByUserId($userId);

            $data = [
                'day' => [],
                'week' => [],
                'month' => [],
                'year' => []
            ];

            $now = new DateTime('now', new DateTimeZone('Asia/Manila'));

            foreach ($allLogs as $log) {
                $logTime = new DateTime($log['timestamp'], new DateTimeZone('Asia/Manila'));
                $diff = $now->diff($logTime);

                $entry = [
                    'date' => $logTime->format('D, M d, Y'),
                    'time' => $logTime->format('g:i A'),
                    'status' => 'Checked In'
                ];

                if ($diff->days === 0) $data['day'][] = $entry;
                if ($diff->days <= 7) $data['week'][] = $entry;
                if ($diff->m === 0 && $diff->y === 0) $data['month'][] = $entry;
                if ($diff->y === 0) $data['year'][] = $entry;
            }

            return $this->jsonResponse($data);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function fetchLogsAjax()
    {
        try {
            $period = $_GET['period'] ?? 'Today';
            $search = $_GET['search'] ?? '';
            $courseName = isset($_GET['course']) && $_GET['course'] !== 'All Courses' ? $_GET['course'] : null;
            date_default_timezone_set('Asia/Manila');

            switch ($period) {
                case 'Today':
                    $start = (new DateTime('today'))->format('Y-m-d 00:00:00');
                    $end   = (new DateTime('today'))->format('Y-m-d 23:59:59');
                    break;
                case 'Yesterday':
                    $start = (new DateTime('yesterday'))->format('Y-m-d 00:00:00');
                    $end   = (new DateTime('yesterday'))->format('Y-m-d 23:59:59');
                    break;
                case 'All dates':
                default:
                    $start = null;
                    $end   = null;
                    break;
            }

            $campusId = $this->getCampusFilter();
            $logs = $this->attendanceRepo->getLogsByPeriod($start, $end, $search, $courseName, $campusId);

            $formattedLogs = [];
            foreach ($logs as $log) {
                $logTime = new DateTime($log['timestamp'], new DateTimeZone('Asia/Manila'));

                $courseDisplay = $log['course'] ?? 'N/A';
                $yearLevelSectionDisplay = $log['year_level_section'] ?? 'N/A';

                $formattedLogs[] = [
                    'date' => $logTime->format("Y-m-d"),
                    'day' => $logTime->format("l"),
                    'studentName' => $log['full_name'],
                    'studentNumber' => $log['student_number'],
                    'time' => $logTime->format("H:i:s"),
                    'status' => "Present",
                    'course' => $courseDisplay,
                    'year_level_section' => $yearLevelSectionDisplay
                ];
            }

            return $this->jsonResponse($formattedLogs);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
