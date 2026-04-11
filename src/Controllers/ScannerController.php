<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\UserRepository;
use App\Repositories\AttendanceRepository;
use App\Models\Attendance;
use DateTimeZone;

class ScannerController extends Controller
{
    private $userRepo;
    private $attendanceRepo;

    public function __construct()
    {
    parent::__construct();
        $this->userRepo = new UserRepository();
        $this->attendanceRepo = new AttendanceRepository();
    }

    public function scannerDisplay()
    {
        $this->view("scanner/attendance", ["title" => "Scanner"], false);
    }

    public function attendance()
    {
        try {
            $qrValue = $_POST['qrCodeValue'] ?? null;
            if ($qrValue) {
                $qrValue = strtoupper(trim($qrValue));
            }

            if (!$qrValue) {
                throw new \Exception("No student number provided.");
            }

            $user = $this->userRepo->findByStudentNumberWithDetails($qrValue);
            if (!$user) {
                throw new \Exception("Student not found in records.");
            }

            // Tiyakin na ang required fields ay nandiyan
            $courseId = (int)($user['course_id'] ?? 0);
            $yearLevel = (int)($user['year_level'] ?? 0);
            $section = $user['section'] ?? '';

            if (!$courseId || !$yearLevel || empty($user['profile_updated'])) {
                throw new \Exception("Student profile incomplete. Please update your profile details first.");
            }

            $manila = new DateTimeZone('Asia/Manila');
            $now = new \DateTime('now', $manila);
            $timestamp = $now->format('Y-m-d H:i:s');

            $attendance = new Attendance(
                (int)$user['user_id'],
                $user['student_number'],
                $user['first_name'],
                $user['middle_name'],
                $user['last_name'],
                $yearLevel, // FIXED: Year Level (INT)
                $section,   // FIXED: Section (STRING)
                $courseId,  // FIXED: Course ID (INT)
                'qr',
                $timestamp
            );

            $success = $this->attendanceRepo->logBoth($attendance);
            if (!$success) {
                throw new \Exception("Failed to log attendance. (Repository Error)");
            }

            $fullName = implode(' ', array_filter([$user['first_name'], $user['middle_name'], $user['last_name']]));

            return $this->jsonResponse([
                "status" => "success",
                "full_name" => $fullName,
                "student_number" => $user['student_number'],
                "profile_picture" => $user['profile_picture'],
                "course" => $user['course_title'] ?? $user['course_code'] ?? 'N/A',
                "course_code" => $user['course_code'] ?? 'N/A',
                "year_level" => $user['year_level'],
                "section" => $user['section'] ?? 'N/A',
                "time" => $now->format('g:i A')
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400, ["status" => "error"]);
        }
    }

    public function manual()
    {
        try {
            $studentNumber = $_POST['studentNumber'] ?? null;
            if ($studentNumber) {
                $studentNumber = strtoupper(trim($studentNumber));
            }

            if (!$studentNumber) {
                throw new \Exception("No student number provided.");
            }

            // NOTE: Assuming UserRepository has findByStudentNumberWithDetails method
            $user = $this->userRepo->findByStudentNumberWithDetails($studentNumber);
            if (!$user) {
                throw new \Exception("Student not found in records.");
            }

            // Tiyakin na ang required fields ay nandiyan
            $courseId = (int)($user['course_id'] ?? 0);
            $yearLevel = (int)($user['year_level'] ?? 0);
            $section = $user['section'] ?? '';

            if (!$courseId || !$yearLevel || empty($user['profile_updated'])) {
                throw new \Exception("Student profile incomplete. Please update your profile details first.");
            }


            $now = new \DateTime('now', new DateTimeZone('Asia/Manila'));
            $timestamp = $now->format('Y-m-d H:i:s');

            $attendance = new \App\Models\Attendance(
                (int)$user['user_id'],
                $user['student_number'],
                $user['first_name'],
                $user['middle_name'],
                $user['last_name'],
                $yearLevel, // FIXED: Year Level (INT)
                $section,   // FIXED: Section (STRING)
                $courseId,  // FIXED: Course ID (INT)
                'manual',
                $timestamp
            );

            $success = $this->attendanceRepo->logBoth($attendance);
            if (!$success) {
                throw new \Exception("Failed to log attendance. (Repository Error)");
            }

            $fullName = implode(' ', array_filter([$user['first_name'], $user['middle_name'], $user['last_name']]));

            return $this->jsonResponse([
                "status" => "success",
                "full_name" => $fullName,
                "student_number" => $user['student_number'],
                "profile_picture" => $user['profile_picture'],
                "course" => $user['course_title'] ?? $user['course_code'] ?? 'N/A',
                "course_code" => $user['course_code'] ?? 'N/A',
                "year_level" => $user['year_level'],
                "section" => $user['section'] ?? 'N/A',
                "time" => $now->format('g:i A')
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400, ["status" => "error"]);
        }
    }
}
