<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use Exception;

class ReportRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    private function getBorrowerJoinSql()
    {
        return "
            LEFT JOIN students s ON bt.student_id = s.student_id
            LEFT JOIN faculty f ON bt.faculty_id = f.faculty_id
            LEFT JOIN staff st ON bt.staff_id = st.staff_id
            JOIN users u ON u.user_id = COALESCE(s.user_id, f.user_id, st.user_id)
        ";
    }

    public function getCirculatedBooksSummary(string $filter = 'month', ?int $campusId = null)
    {
        try {
            $whereClause = "";
            if ($filter === 'day') {
                $whereClause = "AND DATE(bt.borrowed_at) = CURDATE()";
            } elseif ($filter === 'month') {
                $whereClause = "AND MONTH(bt.borrowed_at) = MONTH(CURDATE()) AND YEAR(bt.borrowed_at) = YEAR(CURDATE())";
            } else { // year
                $whereClause = "AND YEAR(bt.borrowed_at) = YEAR(CURDATE())";
            }

            $campusWhere = $campusId !== null ? " AND u.campus_id = :campus_id" : "";
            $params = $campusId !== null ? ['campus_id' => $campusId] : [];

            $borrowerJoin = $this->getBorrowerJoinSql();

            $sql = "
                SELECT
                    'Student' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = CURDATE() THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(CURDATE(), 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) AND MONTH(bt.borrowed_at) = MONTH(CURDATE()) THEN bti.item_id END) AS month,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) THEN bti.item_id END) AS year,
                    COUNT(bti.item_id) AS filtered_count
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                $borrowerJoin
                WHERE bt.student_id IS NOT NULL AND bti.status IN ('borrowed', 'returned', 'overdue') AND bti.book_id IS NOT NULL $whereClause $campusWhere
                UNION ALL
                SELECT
                    'Faculty' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = CURDATE() THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(CURDATE(), 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) AND MONTH(bt.borrowed_at) = MONTH(CURDATE()) THEN bti.item_id END) AS month,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) THEN bti.item_id END) AS year,
                    COUNT(bti.item_id) AS filtered_count
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                $borrowerJoin
                WHERE bt.faculty_id IS NOT NULL AND bti.status IN ('borrowed', 'returned', 'overdue') AND bti.book_id IS NOT NULL $whereClause $campusWhere
                UNION ALL
                SELECT
                    'Staff' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = CURDATE() THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(CURDATE(), 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) AND MONTH(bt.borrowed_at) = MONTH(CURDATE()) THEN bti.item_id END) AS month,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) THEN bti.item_id END) AS year,
                    COUNT(bti.item_id) AS filtered_count
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                $borrowerJoin
                WHERE bt.staff_id IS NOT NULL AND bti.status IN ('borrowed', 'returned', 'overdue') AND bti.book_id IS NOT NULL $whereClause $campusWhere
                UNION ALL
                SELECT
                    'TOTAL' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = CURDATE() THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(CURDATE(), 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) AND MONTH(bt.borrowed_at) = MONTH(CURDATE()) THEN bti.item_id END) AS month,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) THEN bti.item_id END) AS year,
                    COUNT(bti.item_id) AS filtered_count
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                $borrowerJoin
                WHERE bti.status IN ('borrowed', 'returned', 'overdue') AND bti.book_id IS NOT NULL $whereClause $campusWhere;
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getCirculatedBooksSummary: " . $e->getMessage());
            return [];
        }
    }

    public function getCirculatedEquipmentsSummary(string $filter = 'month', ?int $campusId = null)
    {
        try {
            $whereClause = "";
            if ($filter === 'day') {
                $whereClause = "AND DATE(bt.borrowed_at) = CURDATE()";
            } elseif ($filter === 'month') {
                $whereClause = "AND MONTH(bt.borrowed_at) = MONTH(CURDATE()) AND YEAR(bt.borrowed_at) = YEAR(CURDATE())";
            } else { // year
                $whereClause = "AND YEAR(bt.borrowed_at) = YEAR(CURDATE())";
            }

            $campusWhere = $campusId !== null ? " AND u.campus_id = :campus_id" : "";
            $params = $campusId !== null ? ['campus_id' => $campusId] : [];
            $borrowerJoin = $this->getBorrowerJoinSql();

            $sql = "
                SELECT
                    'Student' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = CURDATE() THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(CURDATE(), 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) AND MONTH(bt.borrowed_at) = MONTH(CURDATE()) THEN bti.item_id END) AS month,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) THEN bti.item_id END) AS year,
                    COUNT(bti.item_id) AS filtered_count
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                $borrowerJoin
                WHERE bt.student_id IS NOT NULL AND bti.status IN ('borrowed', 'returned', 'overdue') AND bti.equipment_id IS NOT NULL $whereClause $campusWhere
                UNION ALL
                SELECT
                    'Faculty' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = CURDATE() THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(CURDATE(), 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) AND MONTH(bt.borrowed_at) = MONTH(CURDATE()) THEN bti.item_id END) AS month,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) THEN bti.item_id END) AS year,
                    COUNT(bti.item_id) AS filtered_count
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                $borrowerJoin
                WHERE bt.faculty_id IS NOT NULL AND bti.status IN ('borrowed', 'returned', 'overdue') AND bti.equipment_id IS NOT NULL $whereClause $campusWhere
                UNION ALL
                SELECT
                    'Staff' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = CURDATE() THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(CURDATE(), 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) AND MONTH(bt.borrowed_at) = MONTH(CURDATE()) THEN bti.item_id END) AS month,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) THEN bti.item_id END) AS year,
                    COUNT(bti.item_id) AS filtered_count
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                $borrowerJoin
                WHERE bt.staff_id IS NOT NULL AND bti.status IN ('borrowed', 'returned', 'overdue') AND bti.equipment_id IS NOT NULL $whereClause $campusWhere
                UNION ALL
                SELECT
                    'TOTAL' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = CURDATE() THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(CURDATE(), 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) AND MONTH(bt.borrowed_at) = MONTH(CURDATE()) THEN bti.item_id END) AS month,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) THEN bti.item_id END) AS year,
                    COUNT(bti.item_id) AS filtered_count
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                $borrowerJoin
                WHERE bti.status IN ('borrowed', 'returned', 'overdue') AND bti.equipment_id IS NOT NULL $whereClause $campusWhere;
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getCirculatedEquipmentsSummary: " . $e->getMessage());
            return [];
        }
    }

    public function getTopVisitorsFiltered(string $filter = 'month', ?int $campusId = null)
    {
        try {
            $whereClause = "";
            if ($filter === 'day') {
                $whereClause = "DATE(a.first_scan_at) = CURDATE()";
            } elseif ($filter === 'month') {
                $whereClause = "MONTH(a.first_scan_at) = MONTH(CURDATE()) AND YEAR(a.first_scan_at) = YEAR(CURDATE())";
            } else { // year
                $whereClause = "YEAR(a.first_scan_at) = YEAR(CURDATE())";
            }

            $params = [];
            if ($campusId !== null) {
                $whereClause .= " AND u.campus_id = :campus_id";
                $params['campus_id'] = $campusId;
            }

            $sql = "
                SELECT
                    CONCAT(u.first_name, ' ', u.last_name) AS full_name,
                    s.student_number AS student_number,
                    COALESCE(c.course_code, 'N/A') AS course,
                    COUNT(a.user_id) AS visits
                FROM attendance a
                JOIN students s ON a.user_id = s.user_id
                JOIN users u ON s.user_id = u.user_id
                LEFT JOIN courses c ON s.course_id = c.course_id
                WHERE $whereClause
                GROUP BY a.user_id, u.first_name, u.last_name, s.student_id, c.course_code
                ORDER BY visits DESC
                LIMIT 10;
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getTopVisitorsFiltered: " . $e->getMessage());
            return [];
        }
    }

    public function getTopBorrowers(string $filter = 'month', ?int $campusId = null)
    {
        try {
            $whereClause = "";
            if ($filter === 'day') {
                $whereClause = "DATE(bt.borrowed_at) = CURDATE()";
            } elseif ($filter === 'month') {
                $whereClause = "MONTH(bt.borrowed_at) = MONTH(CURDATE()) AND YEAR(bt.borrowed_at) = YEAR(CURDATE())";
            } else { // year
                $whereClause = "YEAR(bt.borrowed_at) = YEAR(CURDATE())";
            }

            $params = [];
            if ($campusId !== null) {
                $whereClause .= " AND u.campus_id = :campus_id";
                $params['campus_id'] = $campusId;
            }

            $sql = "
                SELECT 
                    CONCAT(u.first_name, ' ', u.last_name) AS full_name,
                    u.username AS identifier,
                    u.role,
                    COUNT(bt.transaction_id) AS borrow_count
                FROM borrow_transactions bt
                LEFT JOIN students s ON bt.student_id = s.student_id
                LEFT JOIN faculty f ON bt.faculty_id = f.faculty_id
                LEFT JOIN staff st ON bt.staff_id = st.staff_id
                JOIN users u ON u.user_id = COALESCE(s.user_id, f.user_id, st.user_id)
                WHERE $whereClause
                GROUP BY u.user_id, u.first_name, u.last_name, u.username, u.role
                ORDER BY borrow_count DESC
                LIMIT 10;
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getTopBorrowers: " . $e->getMessage());
            return [];
        }
    }

    public function getMostBorrowedBooks(string $filter = 'month', ?int $campusId = null)
    {
        try {
            $whereClause = "WHERE bti.book_id IS NOT NULL";
            if ($filter === 'day') {
                $whereClause .= " AND DATE(bt.borrowed_at) = CURDATE()";
            } elseif ($filter === 'month') {
                $whereClause .= " AND MONTH(bt.borrowed_at) = MONTH(CURDATE()) AND YEAR(bt.borrowed_at) = YEAR(CURDATE())";
            } else { // year
                $whereClause .= " AND YEAR(bt.borrowed_at) = YEAR(CURDATE())";
            }

            $params = [];
            if ($campusId !== null) {
                $whereClause .= " AND u.campus_id = :campus_id";
                $params['campus_id'] = $campusId;
            }

            $borrowerJoin = $this->getBorrowerJoinSql();

            $sql = "
                SELECT 
                    b.title,
                    b.author,
                    b.accession_number,
                    COUNT(bti.item_id) AS borrow_count
                FROM borrow_transaction_items bti
                JOIN books b ON bti.book_id = b.book_id
                JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
                $borrowerJoin
                $whereClause
                GROUP BY b.book_id, b.title, b.author, b.accession_number
                ORDER BY borrow_count DESC
                LIMIT 10;
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getMostBorrowedBooks: " . $e->getMessage());
            return [];
        }
    }

    public function getLibraryVisitsByDepartment(string $filter = 'month', ?int $campusId = null)
    {
        try {
            $campusWhere = $campusId !== null ? " AND u.campus_id = :campus_id" : "";
            $params = $campusId !== null ? ['campus_id' => $campusId] : [];

            $sql = "
                WITH DepartmentVisits AS (
                    SELECT
                        cl.college_name AS department,
                        COUNT(CASE WHEN DATE(a.first_scan_at) = CURDATE() THEN a.id END) AS today,
                        COUNT(CASE WHEN YEARWEEK(a.first_scan_at, 1) = YEARWEEK(CURDATE(), 1) THEN a.id END) AS week,
                        COUNT(CASE WHEN MONTH(a.first_scan_at) = MONTH(CURDATE()) AND YEAR(a.first_scan_at) = YEAR(CURDATE()) THEN a.id END) AS month,
                        COUNT(CASE WHEN YEAR(a.first_scan_at) = YEAR(CURDATE()) THEN a.id END) AS year,
                        COUNT(a.id) AS filtered_count
                    FROM colleges cl
                    LEFT JOIN (
                        -- Combine Students and Faculty
                        SELECT user_id, course_id, NULL as college_id FROM students
                        UNION ALL
                        SELECT user_id, NULL as course_id, college_id FROM faculty
                    ) u_combined ON (
                        (u_combined.college_id = cl.college_id) OR 
                        (u_combined.course_id IN (SELECT course_id FROM courses WHERE college_id = cl.college_id))
                    )
                    LEFT JOIN attendance a ON u_combined.user_id = a.user_id
                    JOIN users u ON a.user_id = u.user_id
                    WHERE 1=1 $campusWhere
                    GROUP BY cl.college_name
                )
                SELECT * FROM DepartmentVisits
                UNION ALL
                SELECT
                    'TOTAL' AS department,
                    SUM(today) AS today,
                    SUM(week) AS week,
                    SUM(month) AS month,
                    SUM(year) AS year,
                    SUM(filtered_count) AS filtered_count
                FROM DepartmentVisits;
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getLibraryVisitsByDepartment: " . $e->getMessage());
            return [];
        }
    }

    public function getDeletedBooksReport(string $filter = 'month', ?int $campusId = null)
    {
        try {
            $whereClause = "";
            if ($filter === 'day') {
                $whereClause = "AND DATE(deleted_at) = CURDATE()";
            } elseif ($filter === 'month') {
                $whereClause = "AND MONTH(deleted_at) = MONTH(CURDATE()) AND YEAR(deleted_at) = YEAR(CURDATE())";
            } else { // year
                $whereClause = "AND YEAR(deleted_at) = YEAR(CURDATE())";
            }

            $params = [];
            if ($campusId !== null) {
                $whereClause .= " AND campus_id = :campus_id";
                $params['campus_id'] = $campusId;
            }

            $sql = "
                SELECT
                    YEAR(deleted_at) as year,
                    SUM(CASE WHEN MONTH(deleted_at) = MONTH(CURDATE()) AND YEAR(deleted_at) = YEAR(CURDATE()) THEN 1 ELSE 0 END) as month,
                    SUM(CASE WHEN DATE(deleted_at) = CURDATE() THEN 1 ELSE 0 END) as today,
                    COUNT(*) as filtered_count
                FROM books
                WHERE is_active = 0 $whereClause
                GROUP BY YEAR(deleted_at)
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getDeletedBooksReport: " . $e->getMessage());
            return [];
        }
    }

    public function getLostDamagedBooksSummary(string $filter = 'month', ?int $campusId = null)
    {
        try {
            $whereClause = "";
            if ($filter === 'day') {
                $whereClause = "AND DATE(bti.returned_at) = CURDATE()";
            } elseif ($filter === 'month') {
                $whereClause = "AND MONTH(bti.returned_at) = MONTH(CURDATE()) AND YEAR(bti.returned_at) = YEAR(CURDATE())";
            } else { // year
                $whereClause = "AND YEAR(bti.returned_at) = YEAR(CURDATE())";
            }

            $campusWhere = $campusId !== null ? " AND u.campus_id = :campus_id" : "";
            $params = $campusId !== null ? ['campus_id' => $campusId] : [];
            $borrowerJoin = $this->getBorrowerJoinSql();

            $sql = "
                SELECT
                    'Lost' AS category,
                    COUNT(CASE WHEN DATE(bti.returned_at) = CURDATE() THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bti.returned_at, 1) = YEARWEEK(CURDATE(), 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bti.returned_at) = YEAR(CURDATE()) AND MONTH(bti.returned_at) = MONTH(CURDATE()) THEN bti.item_id END) AS month,
                    COUNT(CASE WHEN YEAR(bti.returned_at) = YEAR(CURDATE()) THEN bti.item_id END) AS year,
                    COUNT(bti.item_id) AS filtered_count
                FROM borrow_transaction_items bti
                JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
                $borrowerJoin
                WHERE bti.status = 'lost' AND bti.book_id IS NOT NULL $whereClause $campusWhere
                UNION ALL
                SELECT
                    'Damaged' AS category,
                    COUNT(CASE WHEN DATE(bti.returned_at) = CURDATE() THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bti.returned_at, 1) = YEARWEEK(CURDATE(), 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bti.returned_at) = YEAR(CURDATE()) AND MONTH(bti.returned_at) = MONTH(CURDATE()) THEN bti.item_id END) AS month,
                    COUNT(CASE WHEN YEAR(bti.returned_at) = YEAR(CURDATE()) THEN bti.item_id END) AS year,
                    COUNT(bti.item_id) AS filtered_count
                FROM borrow_transaction_items bti
                JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
                $borrowerJoin
                WHERE bti.status = 'damaged' AND bti.book_id IS NOT NULL $whereClause $campusWhere
                UNION ALL
                SELECT
                    'TOTAL' AS category,
                    COUNT(CASE WHEN DATE(bti.returned_at) = CURDATE() THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bti.returned_at, 1) = YEARWEEK(CURDATE(), 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bti.returned_at) = YEAR(CURDATE()) AND MONTH(bti.returned_at) = MONTH(CURDATE()) THEN bti.item_id END) AS month,
                    COUNT(CASE WHEN YEAR(bti.returned_at) = YEAR(CURDATE()) THEN bti.item_id END) AS year,
                    COUNT(bti.item_id) AS filtered_count
                FROM borrow_transaction_items bti
                JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
                $borrowerJoin
                WHERE bti.status IN ('lost', 'damaged') AND bti.book_id IS NOT NULL $whereClause $campusWhere;
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getLostDamagedBooksSummary: " . $e->getMessage());
            return [];
        }
    }

    public function getMostBorrowedBooksData($startDate, $endDate, ?int $campusId = null)
    {
        try {
            $campusWhere = $campusId !== null ? " AND u.campus_id = :campus_id" : "";
            $borrowerJoin = $this->getBorrowerJoinSql();

            $sql = "
                SELECT 
                    b.title,
                    b.author,
                    b.accession_number,
                    COUNT(bti.item_id) AS range_total
                FROM borrow_transaction_items bti
                JOIN books b ON bti.book_id = b.book_id
                JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
                $borrowerJoin
                WHERE bti.book_id IS NOT NULL AND DATE(bt.borrowed_at) BETWEEN :startDate AND :endDate $campusWhere
                GROUP BY b.book_id, b.title, b.author, b.accession_number
                ORDER BY range_total DESC
                LIMIT 10;
            ";
            $stmt = $this->db->prepare($sql);
            $params = ['startDate' => $startDate, 'endDate' => $endDate];
            if ($campusId !== null) $params['campus_id'] = $campusId;
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getMostBorrowedBooksData: " . $e->getMessage());
            return [];
        }
    }

    public function getTopBorrowersData($startDate, $endDate, ?int $campusId = null)
    {
        try {
            $campusWhere = $campusId !== null ? " AND u.campus_id = :campus_id" : "";

            $sql = "
                SELECT 
                    CONCAT(u.first_name, ' ', u.last_name) AS full_name,
                    u.username AS identifier,
                    u.role,
                    COUNT(bt.transaction_id) AS range_total
                FROM borrow_transactions bt
                LEFT JOIN students s ON bt.student_id = s.student_id
                LEFT JOIN faculty f ON bt.faculty_id = f.faculty_id
                LEFT JOIN staff st ON bt.staff_id = st.staff_id
                JOIN users u ON u.user_id = COALESCE(s.user_id, f.user_id, st.user_id)
                WHERE DATE(bt.borrowed_at) BETWEEN :startDate AND :endDate $campusWhere
                GROUP BY u.user_id, u.first_name, u.last_name, u.username, u.role
                ORDER BY range_total DESC
                LIMIT 10;
            ";
            $stmt = $this->db->prepare($sql);
            $params = ['startDate' => $startDate, 'endDate' => $endDate];
            if ($campusId !== null) $params['campus_id'] = $campusId;
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getTopBorrowersData: " . $e->getMessage());
            return [];
        }
    }

    public function getOverdueSummaryData($startDate, $endDate, ?int $campusId = null)
    {
        try {
            $campusWhere = $campusId !== null ? " AND u.campus_id = :campus_id" : "";
            $borrowerJoin = "
                JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
                LEFT JOIN students s ON bt.student_id = s.student_id
                LEFT JOIN faculty f ON bt.faculty_id = f.faculty_id
                LEFT JOIN staff st ON bt.staff_id = st.staff_id
                JOIN users u ON u.user_id = COALESCE(s.user_id, f.user_id, st.user_id)
            ";

            $sql = "
                SELECT
                    'Overdue Books' as category,
                    COUNT(bti.item_id) as range_total
                FROM borrow_transaction_items bti
                $borrowerJoin
                WHERE bti.book_id IS NOT NULL 
                AND bti.status = 'overdue'
                AND DATE(bt.due_date) BETWEEN :startDate AND :endDate $campusWhere
                UNION ALL
                SELECT
                    'Overdue Equipments' as category,
                    COUNT(bti.item_id) as range_total
                FROM borrow_transaction_items bti
                $borrowerJoin
                WHERE bti.equipment_id IS NOT NULL 
                AND bti.status = 'overdue'
                AND DATE(bt.due_date) BETWEEN :startDate AND :endDate $campusWhere
            ";
            $stmt = $this->db->prepare($sql);
            $params = ['startDate' => $startDate, 'endDate' => $endDate];
            if ($campusId !== null) $params['campus_id'] = $campusId;
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getOverdueSummaryData: " . $e->getMessage());
            return [];
        }
    }

    public function getLostDamagedBooksData($startDate, $endDate, ?int $campusId = null)
    {
        try {
            $campusWhere = $campusId !== null ? " AND u.campus_id = :campus_id" : "";
            $borrowerJoin = $this->getBorrowerJoinSql();

            $sql = "
                SELECT
                    'Lost' AS category,
                    COUNT(CASE WHEN DATE(bti.returned_at) = :endDate THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bti.returned_at, 1) = YEARWEEK(:endDate, 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bti.returned_at) = YEAR(:endDate) AND MONTH(bti.returned_at) = MONTH(:endDate) THEN bti.item_id END) AS month,
                    COUNT(bti.item_id) AS range_total
                FROM borrow_transaction_items bti
                JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
                $borrowerJoin
                WHERE bti.status = 'lost' AND bti.book_id IS NOT NULL AND DATE(bti.returned_at) BETWEEN :startDate AND :endDate $campusWhere
                UNION ALL
                SELECT
                    'Damaged' AS category,
                    COUNT(CASE WHEN DATE(bti.returned_at) = :endDate THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bti.returned_at, 1) = YEARWEEK(:endDate, 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bti.returned_at) = YEAR(:endDate) AND MONTH(bti.returned_at) = MONTH(:endDate) THEN bti.item_id END) AS month,
                    COUNT(bti.item_id) AS range_total
                FROM borrow_transaction_items bti
                JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
                $borrowerJoin
                WHERE bti.status = 'damaged' AND bti.book_id IS NOT NULL AND DATE(bti.returned_at) BETWEEN :startDate AND :endDate $campusWhere
                UNION ALL
                SELECT
                    'TOTAL' AS category,
                    COUNT(CASE WHEN DATE(bti.returned_at) = :endDate THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bti.returned_at, 1) = YEARWEEK(:endDate, 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bti.returned_at) = YEAR(:endDate) AND MONTH(bti.returned_at) = MONTH(:endDate) THEN bti.item_id END) AS month,
                    COUNT(bti.item_id) AS range_total
                FROM borrow_transaction_items bti
                JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
                $borrowerJoin
                WHERE bti.status IN ('lost', 'damaged') AND bti.book_id IS NOT NULL AND DATE(bti.returned_at) BETWEEN :startDate AND :endDate $campusWhere;
            ";
            $stmt = $this->db->prepare($sql);
            $params = ['startDate' => $startDate, 'endDate' => $endDate];
            if ($campusId !== null) $params['campus_id'] = $campusId;
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getLostDamagedBooksData: " . $e->getMessage());
            return [];
        }
    }

    public function getLibraryResourcesData(?int $campusId = null)
    {
        try {
            $campusWhere = $campusId !== null ? " AND campus_id = :campus_id" : "";
            $params = $campusId !== null ? ['campus_id' => $campusId] : [];

            // Count active books (available or borrowed, not deactivated)
            $stmtBooks = $this->db->prepare("SELECT COUNT(*) FROM books WHERE is_active = 1 $campusWhere");
            $stmtBooks->execute($params);
            $totalBooks = $stmtBooks->fetchColumn();

            // Count available books specifically
            $stmtAvail = $this->db->prepare("SELECT COUNT(*) FROM books WHERE is_active = 1 AND availability = 'available' $campusWhere");
            $stmtAvail->execute($params);
            $availableBooks = $stmtAvail->fetchColumn();

            // Count active equipments (not deactivated)
            $stmtEquip = $this->db->prepare("SELECT COUNT(*) FROM equipments WHERE is_active = 1 $campusWhere");
            $stmtEquip->execute($params);
            $totalEquip = $stmtEquip->fetchColumn();

            return [
                'total_collection' => $totalBooks + $totalEquip,
                'available_books'  => $availableBooks,
                'total_equipments' => $totalEquip
            ];
        } catch (Exception $e) {
            error_log("ReportRepository error in getLibraryResourcesData: " . $e->getMessage());
            return [
                'total_collection' => 0,
                'available_books'  => 0,
                'total_equipments' => 0
            ];
        }
    }

    public function getDeletedBooksData($startDate, $endDate, ?int $campusId = null)
    {
        try {
            $campusWhere = $campusId !== null ? " AND campus_id = :campus_id" : "";

            $sql = "
                SELECT
                    SUM(CASE WHEN DATE(deleted_at) = :endDate THEN 1 ELSE 0 END) as today,
                    SUM(CASE WHEN YEARWEEK(deleted_at, 1) = YEARWEEK(:endDate, 1) THEN 1 ELSE 0 END) as week,
                    SUM(CASE WHEN MONTH(deleted_at) = MONTH(:endDate) AND YEAR(deleted_at) = YEAR(:endDate) THEN 1 ELSE 0 END) as month,
                    COUNT(*) as range_total
                FROM books
                WHERE deleted_at BETWEEN :startDate AND :endDate $campusWhere;
            ";
            $stmt = $this->db->prepare($sql);
            $params = ['startDate' => $startDate, 'endDate' => $endDate];
            if ($campusId !== null) $params['campus_id'] = $campusId;
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getDeletedBooksData: " . $e->getMessage());
            return [];
        }
    }

    public function getCirculatedBooksData($startDate, $endDate, ?int $campusId = null)
    {
        try {
            $campusWhere = $campusId !== null ? " AND u.campus_id = :campus_id" : "";
            $borrowerJoin = $this->getBorrowerJoinSql();

            $sql = "
                SELECT
                    'Student' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = :endDate THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(:endDate, 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(:endDate) AND MONTH(bt.borrowed_at) = MONTH(:endDate) THEN bti.item_id END) AS month,
                    COUNT(bti.item_id) AS range_total
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                $borrowerJoin
                WHERE bt.student_id IS NOT NULL AND bti.status IN ('borrowed', 'returned', 'overdue') AND bti.book_id IS NOT NULL AND DATE(bt.borrowed_at) BETWEEN :startDate AND :endDate $campusWhere
                UNION ALL
                SELECT
                    'Faculty' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = :endDate THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(:endDate, 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(:endDate) AND MONTH(bt.borrowed_at) = MONTH(:endDate) THEN bti.item_id END) AS month,
                    COUNT(bti.item_id) AS range_total
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                $borrowerJoin
                WHERE bt.faculty_id IS NOT NULL AND bti.status IN ('borrowed', 'returned', 'overdue') AND bti.book_id IS NOT NULL AND DATE(bt.borrowed_at) BETWEEN :startDate AND :endDate $campusWhere
                UNION ALL
                SELECT
                    'Staff' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = :endDate THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(:endDate, 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(:endDate) AND MONTH(bt.borrowed_at) = MONTH(:endDate) THEN bti.item_id END) AS month,
                    COUNT(bti.item_id) AS range_total
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                $borrowerJoin
                WHERE bt.staff_id IS NOT NULL AND bti.status IN ('borrowed', 'returned', 'overdue') AND bti.book_id IS NOT NULL AND DATE(bt.borrowed_at) BETWEEN :startDate AND :endDate $campusWhere
                UNION ALL
                SELECT
                    'TOTAL' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = :endDate THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(:endDate, 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(:endDate) AND MONTH(bt.borrowed_at) = MONTH(:endDate) THEN bti.item_id END) AS month,
                    COUNT(bti.item_id) AS range_total
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                $borrowerJoin
                WHERE bti.status IN ('borrowed', 'returned', 'overdue') AND bti.book_id IS NOT NULL AND DATE(bt.borrowed_at) BETWEEN :startDate AND :endDate $campusWhere;
            ";
            $stmt = $this->db->prepare($sql);
            $params = ['startDate' => $startDate, 'endDate' => $endDate];
            if ($campusId !== null) $params['campus_id'] = $campusId;
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getCirculatedBooksData: " . $e->getMessage());
            return [];
        }
    }

    public function getCirculatedEquipmentsData($startDate, $endDate, ?int $campusId = null)
    {
        try {
            $campusWhere = $campusId !== null ? " AND u.campus_id = :campus_id" : "";
            $borrowerJoin = $this->getBorrowerJoinSql();

            $sql = "
                SELECT
                    'Student' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = :endDate THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(:endDate, 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(:endDate) AND MONTH(bt.borrowed_at) = MONTH(:endDate) THEN bti.item_id END) AS month,
                    COUNT(bti.item_id) AS range_total
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                $borrowerJoin
                WHERE bt.student_id IS NOT NULL AND bti.status IN ('borrowed', 'returned', 'overdue') AND bti.equipment_id IS NOT NULL AND DATE(bt.borrowed_at) BETWEEN :startDate AND :endDate $campusWhere
                UNION ALL
                SELECT
                    'Faculty' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = :endDate THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(:endDate, 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(:endDate) AND MONTH(bt.borrowed_at) = MONTH(:endDate) THEN bti.item_id END) AS month,
                    COUNT(bti.item_id) AS range_total
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                $borrowerJoin
                WHERE bt.faculty_id IS NOT NULL AND bti.status IN ('borrowed', 'returned', 'overdue') AND bti.equipment_id IS NOT NULL AND DATE(bt.borrowed_at) BETWEEN :startDate AND :endDate $campusWhere
                UNION ALL
                SELECT
                    'Staff' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = :endDate THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(:endDate, 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(:endDate) AND MONTH(bt.borrowed_at) = MONTH(:endDate) THEN bti.item_id END) AS month,
                    COUNT(bti.item_id) AS range_total
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                $borrowerJoin
                WHERE bt.staff_id IS NOT NULL AND bti.status IN ('borrowed', 'returned', 'overdue') AND bti.equipment_id IS NOT NULL AND DATE(bt.borrowed_at) BETWEEN :startDate AND :endDate $campusWhere
                UNION ALL
                SELECT
                    'TOTAL' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = :endDate THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(:endDate, 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(:endDate) AND MONTH(bt.borrowed_at) = MONTH(:endDate) THEN bti.item_id END) AS month,
                    COUNT(bti.item_id) AS range_total
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                $borrowerJoin
                WHERE bti.status IN ('borrowed', 'returned', 'overdue') AND bti.equipment_id IS NOT NULL AND DATE(bt.borrowed_at) BETWEEN :startDate AND :endDate $campusWhere;
            ";
            $stmt = $this->db->prepare($sql);
            $params = ['startDate' => $startDate, 'endDate' => $endDate];
            if ($campusId !== null) $params['campus_id'] = $campusId;
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getCirculatedEquipmentsData: " . $e->getMessage());
            return [];
        }
    }

    public function getTopVisitorsData($startDate, $endDate, ?int $campusId = null)
    {
        try {
            $campusWhere = $campusId !== null ? " AND u.campus_id = :campus_id" : "";

            $sql = "
                SELECT
                    CONCAT(u.first_name, ' ', u.last_name) AS full_name,
                    s.student_number AS student_number,
                    COALESCE(c.course_code, 'N/A') AS course,
                    COUNT(a.user_id) AS visits
                FROM attendance a
                JOIN students s ON a.user_id = s.user_id
                JOIN users u ON s.user_id = u.user_id
                LEFT JOIN courses c ON s.course_id = c.course_id
                WHERE a.first_scan_at BETWEEN :startDate AND :endDate $campusWhere
                GROUP BY a.user_id, u.first_name, u.last_name, s.student_id, c.course_code
                ORDER BY visits DESC
                LIMIT 10;
            ";
            $stmt = $this->db->prepare($sql);
            $params = ['startDate' => $startDate, 'endDate' => $endDate];
            if ($campusId !== null) $params['campus_id'] = $campusId;
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getTopVisitorsData: " . $e->getMessage());
            return [];
        }
    }

    public function getLibraryVisitsData($startDate, $endDate, ?int $campusId = null)
    {
        try {
            $campusWhere = $campusId !== null ? " AND u.campus_id = :campus_id" : "";
            $params = ['startDate' => $startDate, 'endDate' => $endDate];
            if ($campusId !== null) $params['campus_id'] = $campusId;

            $sql = "
                WITH DepartmentVisits AS (
                    SELECT
                        cl.college_name AS department,
                        COUNT(CASE WHEN DATE(a.first_scan_at) = :endDate THEN a.id END) AS today,
                        COUNT(CASE WHEN a.first_scan_at BETWEEN DATE_SUB(:endDate, INTERVAL 6 DAY) AND :endDate THEN a.id END) AS week,
                        COUNT(CASE WHEN MONTH(a.first_scan_at) = MONTH(:endDate) AND YEAR(a.first_scan_at) = YEAR(:endDate) THEN a.id END) AS month,
                        COUNT(a.id) AS range_total
                    FROM colleges cl
                    LEFT JOIN courses c ON cl.college_id = c.college_id
                    LEFT JOIN students s ON c.course_id = s.course_id
                    LEFT JOIN attendance a ON s.user_id = a.user_id AND a.first_scan_at BETWEEN :startDate AND :endDate
                    LEFT JOIN users u ON a.user_id = u.user_id
                    WHERE 1=1 $campusWhere
                    GROUP BY cl.college_name
                )
                SELECT * FROM DepartmentVisits
                UNION ALL
                SELECT
                    'TOTAL' AS department,
                    SUM(today) AS today,
                    SUM(week) AS week,
                    SUM(month) AS month,
                    SUM(range_total) AS range_total
                FROM DepartmentVisits;
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getLibraryVisitsData: " . $e->getMessage());
            return [];
        }
    }

    public function getTopVisitors(int $limit = 5, ?int $campusId = null): array
    {
        $where = "WHERE DATE(a.first_scan_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        $params = ['limit' => $limit];
        if ($campusId !== null) {
            $where .= " AND u.campus_id = :campus_id";
            $params['campus_id'] = $campusId;
        }

        $sql = "
            SELECT u.first_name, u.last_name, COUNT(a.user_id) AS visits
            FROM attendance a
            JOIN users u ON u.user_id = a.user_id
            $where
            GROUP BY a.user_id
            ORDER BY visits DESC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        if ($campusId !== null) $stmt->bindValue(':campus_id', $campusId, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($r) {
        return [
            'user_name' => trim($r['first_name'] . ' ' . $r['last_name']),
            'visits' => (int)$r['visits']
        ];
        }, $rows);
    }

    public function getActivityReport(string $filter = 'month', ?int $campusId = null): array
    {
        $data = [];
        $campusWhere = $campusId !== null ? " AND u.campus_id = :campus_id" : "";
        $params = [];
        if ($campusId !== null) $params['campus_id'] = $campusId;

        if ($filter === 'day') {
            for ($i = 0; $i < 24; $i++) {
                $hour = str_pad($i, 2, '0', STR_PAD_LEFT);
                $label = $hour . ":00";
                
                $currParams = $params;
                $currParams['hour'] = $i;

                $stmt = $this->db->prepare("SELECT COUNT(DISTINCT a.user_id) FROM attendance a JOIN users u ON a.user_id = u.user_id WHERE DATE(a.first_scan_at) = CURDATE() AND HOUR(a.first_scan_at) = :hour $campusWhere");
                $stmt->execute($currParams);
                $visitors = (int) $stmt->fetchColumn();

                $stmt = $this->db->prepare("
                    SELECT COUNT(*) 
                    FROM borrow_transactions bt 
                    LEFT JOIN students s ON bt.student_id = s.student_id
                    LEFT JOIN faculty f ON bt.faculty_id = f.faculty_id
                    LEFT JOIN staff st ON bt.staff_id = st.staff_id
                    JOIN users u ON u.user_id = COALESCE(s.user_id, f.user_id, st.user_id)
                    WHERE DATE(bt.borrowed_at) = CURDATE() AND HOUR(bt.borrowed_at) = :hour $campusWhere
                ");
                $stmt->execute($currParams);
                $borrows = (int) $stmt->fetchColumn();

                $data[] = ['label' => $label, 'visitors' => $visitors, 'borrows' => $borrows];
            }
        } elseif ($filter === 'year') {
            for ($i = 1; $i <= 12; $i++) {
                $label = date('M', mktime(0, 0, 0, $i, 1));
                
                $currParams = $params;
                $currParams['month'] = $i;

                $stmt = $this->db->prepare("SELECT COUNT(DISTINCT a.user_id) FROM attendance a JOIN users u ON a.user_id = u.user_id WHERE YEAR(a.first_scan_at) = YEAR(CURDATE()) AND MONTH(a.first_scan_at) = :month $campusWhere");
                $stmt->execute($currParams);
                $visitors = (int) $stmt->fetchColumn();

                $stmt = $this->db->prepare("
                    SELECT COUNT(*) 
                    FROM borrow_transactions bt 
                    LEFT JOIN students s ON bt.student_id = s.student_id
                    LEFT JOIN faculty f ON bt.faculty_id = f.faculty_id
                    LEFT JOIN staff st ON bt.staff_id = st.staff_id
                    JOIN users u ON u.user_id = COALESCE(s.user_id, f.user_id, st.user_id)
                    WHERE YEAR(bt.borrowed_at) = YEAR(CURDATE()) AND MONTH(bt.borrowed_at) = :month $campusWhere
                ");
                $stmt->execute($currParams);
                $borrows = (int) $stmt->fetchColumn();

                $data[] = ['label' => $label, 'visitors' => $visitors, 'borrows' => $borrows];
            }
        } else {
            for ($i = 29; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $label = date('M d', strtotime($date));

                $currParams = $params;
                $currParams['date'] = $date;

                $stmt = $this->db->prepare("SELECT COUNT(DISTINCT a.user_id) FROM attendance a JOIN users u ON a.user_id = u.user_id WHERE DATE(a.first_scan_at) = :date $campusWhere");
                $stmt->execute($currParams);
                $visitors = (int) $stmt->fetchColumn();

                $stmt = $this->db->prepare("
                    SELECT COUNT(*) 
                    FROM borrow_transactions bt 
                    LEFT JOIN students s ON bt.student_id = s.student_id
                    LEFT JOIN faculty f ON bt.faculty_id = f.faculty_id
                    LEFT JOIN staff st ON bt.staff_id = st.staff_id
                    JOIN users u ON u.user_id = COALESCE(s.user_id, f.user_id, st.user_id)
                    WHERE DATE(bt.borrowed_at) = :date $campusWhere
                ");
                $stmt->execute($currParams);
                $borrows = (int) $stmt->fetchColumn();

                $data[] = ['label' => $label, 'visitors' => $visitors, 'borrows' => $borrows];
            }
        }

        return $data;
    }
}
