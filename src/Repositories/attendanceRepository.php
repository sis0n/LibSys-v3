<?php

namespace App\Repositories;

use App\Models\Attendance;
use App\Core\Database;
use PDO;
use PDOException;

class AttendanceRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getByUserId(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    al.student_number, 
                    al.full_name, 
                    c.course_code, 
                    c.course_title, 
                    al.year_level, 
                    al.section, 
                    al.method, 
                    al.timestamp
                FROM attendance_logs al
                LEFT JOIN courses c ON al.course_id = c.course_id
                WHERE al.user_id = :user_id
                ORDER BY al.timestamp DESC
            ");
            $stmt->execute(['user_id' => $userId]);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_map(function ($row) {
                $row['year_level_section'] = trim(($row['year_level'] ?? '') . ' ' . ($row['section'] ?? ''));

                $course_code = $row['course_code'] ?? null;
                $course_title = $row['course_title'] ?? null;

                $row['course'] = ($course_code && $course_title)
                    ? $course_code . ' - ' . $course_title
                    : 'N/A';

                return $row;
            }, $results);
        } catch (PDOException $e) {
            error_log("Fetch attendance failed: " . $e->getMessage());
            return [];
        }
    }

    public function getByUserAndDate(int $userId, string $date)
    {
        $stmt = $this->db->prepare("SELECT * FROM attendance WHERE user_id = ? AND date = ?");
        $stmt->execute([$userId, $date]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllLogs(): array
    {
        $sql = "SELECT al.id, al.user_id, al.student_number, al.full_name, 
                       c.course_title AS course, al.year_level, al.method, al.timestamp
                FROM attendance_logs al
                LEFT JOIN courses c ON al.course_id = c.course_id
                ORDER BY al.timestamp DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateLastScan(int $attendanceId, string $time): bool
    {
        $stmt = $this->db->prepare("UPDATE attendance SET last_scan_at = ? WHERE id = ?");
        return $stmt->execute([$time, $attendanceId]);
    }

    public function logBoth(\App\Models\Attendance $attendance): bool
    {
        try {
            $this->db->beginTransaction();

            $this->db->query("UPDATE borrow_transactions SET status = 'overdue' WHERE status = 'borrowed' AND due_date < NOW()");
            $this->db->query("UPDATE borrow_transaction_items bti 
                              INNER JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
                              SET bti.status = 'overdue' 
                              WHERE bt.status = 'overdue' AND bti.status = 'borrowed'");

            $stmtCourse = $this->db->prepare("SELECT course_id FROM students WHERE student_number = :student_number");
            $stmtCourse->execute([':student_number' => $attendance->getStudentNumber()]);
            $studentData = $stmtCourse->fetch(PDO::FETCH_ASSOC);
            $courseId = $studentData ? $studentData['course_id'] : null;

            $parts = [
                $attendance->getFirstName(),
                $attendance->getMiddleName(),
                $attendance->getLastName()
            ];
            $fullName = implode(' ', array_filter($parts));

            $sqlLogs = "
            INSERT INTO attendance_logs 
                (user_id, student_number, full_name, year_level, course_id, method, timestamp) 
            VALUES 
                (:user_id, :student_number, :full_name, :year_level, :course_id, :method, :timestamp)
            ";
            $stmtLogs = $this->db->prepare($sqlLogs);

            $ok1 = $stmtLogs->execute([
                ':user_id' => $attendance->getUserId(),
                ':student_number' => $attendance->getStudentNumber(),
                ':full_name'      => $fullName,
                ':year_level'     => $attendance->getYearLevel(),
                ':course_id'      => $courseId,
                ':method'         => $attendance->getSource(),
                ':timestamp'      => $attendance->getTimestamp()
            ]);

            if (!$ok1) {
                $errorInfo = $stmtLogs->errorInfo();
                $this->db->rollBack();
                error_log("SQL Error (Logs): " . implode(" - ", $errorInfo));
                throw new \Exception("SQL Error (Logs): " . $errorInfo[2]);
            }

            $dt   = new \DateTime($attendance->getTimestamp(), new \DateTimeZone('Asia/Manila'));
            $date = $dt->format('Y-m-d');
            $ts   = $dt->format('Y-m-d H:i:s');

            $sqlSummary = "
            INSERT INTO attendance 
                (user_id, date, first_scan_at, last_scan_at, created_at)
            VALUES 
                (:user_id, :date, :first_scan_at, :last_scan_at, :created_at)
            ON DUPLICATE KEY UPDATE 
                last_scan_at = :last_scan_at_update
            ";
            $stmtSummary = $this->db->prepare($sqlSummary);

            $ok2 = $stmtSummary->execute([
                ':user_id' => $attendance->getUserId(),
                ':date' => $date,
                ':first_scan_at' => $ts,
                ':last_scan_at' => $ts,
                ':created_at' => $ts,
                ':last_scan_at_update' => $ts
            ]);

            if (!$ok2) {
                $errorInfo = $stmtSummary->errorInfo();
                $this->db->rollBack();
                error_log("SQL Error (Summary): " . implode(" - ", $errorInfo));
                throw new \Exception("SQL Error (Summary): " . $errorInfo[2]);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("EXCEPTION: " . $e->getMessage());
            throw $e;
        }
    }

    public function getLogsByPeriod(?string $start = null, ?string $end = null, string $search = '', ?string $courseName = null): array
    {
        $query = "
            SELECT al.full_name, al.student_number, al.timestamp, c.course_title AS course 
            FROM attendance_logs al
            LEFT JOIN courses c ON al.course_id = c.course_id
            WHERE 1=1
        ";
        $params = [];

        if ($start && $end) {
            $endOfDay = $end . ' 23:59:59';
            $query .= " AND al.timestamp BETWEEN :start AND :end";
            $params[':start'] = $start;
            $params[':end'] = $endOfDay;
        }

        if ($search) {
            $query .= " AND (al.full_name LIKE :search OR al.student_number LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        if ($courseName) {
            $query .= " AND c.course_title = :courseName";
            $params[':courseName'] = $courseName;
        }

        $query .= " ORDER BY al.timestamp DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
