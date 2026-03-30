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

    public function getCirculatedBooksSummary(string $filter = 'month')
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

            $sql = "
                SELECT
                    'Student' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = CURDATE() THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(CURDATE(), 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) AND MONTH(bt.borrowed_at) = MONTH(CURDATE()) THEN bti.item_id END) AS month,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) THEN bti.item_id END) AS year,
                    COUNT(bti.item_id) AS filtered_count
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                WHERE bt.student_id IS NOT NULL AND bti.status IN ('borrowed', 'returned', 'overdue') AND bti.book_id IS NOT NULL $whereClause
                UNION ALL
                SELECT
                    'Faculty' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = CURDATE() THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(CURDATE(), 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) AND MONTH(bt.borrowed_at) = MONTH(CURDATE()) THEN bti.item_id END) AS month,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) THEN bti.item_id END) AS year,
                    COUNT(bti.item_id) AS filtered_count
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                WHERE bt.faculty_id IS NOT NULL AND bti.status IN ('borrowed', 'returned', 'overdue') AND bti.book_id IS NOT NULL $whereClause
                UNION ALL
                SELECT
                    'Staff' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = CURDATE() THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(CURDATE(), 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) AND MONTH(bt.borrowed_at) = MONTH(CURDATE()) THEN bti.item_id END) AS month,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) THEN bti.item_id END) AS year,
                    COUNT(bti.item_id) AS filtered_count
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                WHERE bt.staff_id IS NOT NULL AND bti.status IN ('borrowed', 'returned', 'overdue') AND bti.book_id IS NOT NULL $whereClause
                UNION ALL
                SELECT
                    'TOTAL' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = CURDATE() THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(CURDATE(), 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) AND MONTH(bt.borrowed_at) = MONTH(CURDATE()) THEN bti.item_id END) AS month,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) THEN bti.item_id END) AS year,
                    COUNT(bti.item_id) AS filtered_count
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                WHERE bti.status IN ('borrowed', 'returned', 'overdue') AND bti.book_id IS NOT NULL $whereClause;
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
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

            if ($campusId !== null) {
                $whereClause .= " AND bt.campus_id = " . (int)$campusId;
            }

            $sql = "
                SELECT
                    'Student' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = CURDATE() THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(CURDATE(), 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) AND MONTH(bt.borrowed_at) = MONTH(CURDATE()) THEN bti.item_id END) AS month,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) THEN bti.item_id END) AS year,
                    COUNT(bti.item_id) AS filtered_count
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                WHERE bt.student_id IS NOT NULL AND bti.status IN ('borrowed', 'returned', 'overdue') AND bti.equipment_id IS NOT NULL $whereClause
                UNION ALL
                SELECT
                    'Faculty' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = CURDATE() THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(CURDATE(), 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) AND MONTH(bt.borrowed_at) = MONTH(CURDATE()) THEN bti.item_id END) AS month,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) THEN bti.item_id END) AS year,
                    COUNT(bti.item_id) AS filtered_count
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                WHERE bt.faculty_id IS NOT NULL AND bti.status IN ('borrowed', 'returned', 'overdue') AND bti.equipment_id IS NOT NULL $whereClause
                UNION ALL
                SELECT
                    'Staff' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = CURDATE() THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(CURDATE(), 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) AND MONTH(bt.borrowed_at) = MONTH(CURDATE()) THEN bti.item_id END) AS month,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) THEN bti.item_id END) AS year,
                    COUNT(bti.item_id) AS filtered_count
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                WHERE bt.staff_id IS NOT NULL AND bti.status IN ('borrowed', 'returned', 'overdue') AND bti.equipment_id IS NOT NULL $whereClause
                UNION ALL
                SELECT
                    'TOTAL' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = CURDATE() THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(CURDATE(), 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) AND MONTH(bt.borrowed_at) = MONTH(CURDATE()) THEN bti.item_id END) AS month,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(CURDATE()) THEN bti.item_id END) AS year,
                    COUNT(bti.item_id) AS filtered_count
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                WHERE bti.status IN ('borrowed', 'returned', 'overdue') AND bti.equipment_id IS NOT NULL $whereClause;
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
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
                $whereClause = "DATE(a.date) = CURDATE()";
            } elseif ($filter === 'month') {
                $whereClause = "MONTH(a.date) = MONTH(CURDATE()) AND YEAR(a.date) = YEAR(CURDATE())";
            } else { // year
                $whereClause = "YEAR(a.date) = YEAR(CURDATE())";
            }

            if ($campusId !== null) {
                $whereClause .= " AND u.campus_id = " . (int)$campusId;
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
            $stmt->execute();
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

            if ($campusId !== null) {
                $whereClause .= " AND u.campus_id = " . (int)$campusId;
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
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getTopBorrowers: " . $e->getMessage());
            return [];
        }
    }

    public function getMostBorrowedBooks(string $filter = 'month')
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

            $sql = "
                SELECT 
                    b.title,
                    b.author,
                    b.accession_number,
                    COUNT(bti.item_id) AS borrow_count
                FROM borrow_transaction_items bti
                JOIN books b ON bti.book_id = b.book_id
                JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
                $whereClause
                GROUP BY b.book_id, b.title, b.author, b.accession_number
                ORDER BY borrow_count DESC
                LIMIT 10;
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getMostBorrowedBooks: " . $e->getMessage());
            return [];
        }
    }

    public function getLibraryVisitsByDepartment(string $filter = 'month')
    {
        try {
            $sql = "
                WITH DepartmentVisits AS (
                    SELECT
                        cl.college_name AS department,
                        COUNT(CASE WHEN DATE(a.date) = CURDATE() THEN a.id END) AS today,
                        COUNT(CASE WHEN YEARWEEK(a.date, 1) = YEARWEEK(CURDATE(), 1) THEN a.id END) AS week,
                        COUNT(CASE WHEN MONTH(a.date) = MONTH(CURDATE()) AND YEAR(a.date) = YEAR(CURDATE()) THEN a.id END) AS month,
                        COUNT(CASE WHEN YEAR(a.date) = YEAR(CURDATE()) THEN a.id END) AS year,
                        COUNT(a.id) AS filtered_count
                    FROM colleges cl
                    LEFT JOIN (
                        -- Combine Students and Faculty attendance
                        SELECT s.user_id, s.course_id, NULL as college_id FROM students s
                        UNION ALL
                        SELECT f.user_id, NULL as course_id, f.college_id FROM faculty f
                    ) u_combined ON (
                        (u_combined.college_id = cl.college_id) OR 
                        (u_combined.course_id IN (SELECT course_id FROM courses WHERE college_id = cl.college_id))
                    )
                    LEFT JOIN attendance a ON u_combined.user_id = a.user_id
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
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getLibraryVisitsByDepartment: " . $e->getMessage());
            return [];
        }
    }

    public function getDeletedBooksReport(string $filter = 'month')
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
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getDeletedBooksReport: " . $e->getMessage());
            return [];
        }
    }

    public function getLostDamagedBooksSummary(string $filter = 'month')
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

            $sql = "
                SELECT
                    'Lost' AS category,
                    COUNT(CASE WHEN DATE(bti.returned_at) = CURDATE() THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bti.returned_at, 1) = YEARWEEK(CURDATE(), 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bti.returned_at) = YEAR(CURDATE()) AND MONTH(bti.returned_at) = MONTH(CURDATE()) THEN bti.item_id END) AS month,
                    COUNT(CASE WHEN YEAR(bti.returned_at) = YEAR(CURDATE()) THEN bti.item_id END) AS year,
                    COUNT(bti.item_id) AS filtered_count
                FROM borrow_transaction_items bti
                WHERE bti.status = 'lost' AND bti.book_id IS NOT NULL $whereClause
                UNION ALL
                SELECT
                    'Damaged' AS category,
                    COUNT(CASE WHEN DATE(bti.returned_at) = CURDATE() THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bti.returned_at, 1) = YEARWEEK(CURDATE(), 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bti.returned_at) = YEAR(CURDATE()) AND MONTH(bti.returned_at) = MONTH(CURDATE()) THEN bti.item_id END) AS month,
                    COUNT(CASE WHEN YEAR(bti.returned_at) = YEAR(CURDATE()) THEN bti.item_id END) AS year,
                    COUNT(bti.item_id) AS filtered_count
                FROM borrow_transaction_items bti
                WHERE bti.status = 'damaged' AND bti.book_id IS NOT NULL $whereClause
                UNION ALL
                SELECT
                    'TOTAL' AS category,
                    COUNT(CASE WHEN DATE(bti.returned_at) = CURDATE() THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bti.returned_at, 1) = YEARWEEK(CURDATE(), 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bti.returned_at) = YEAR(CURDATE()) AND MONTH(bti.returned_at) = MONTH(CURDATE()) THEN bti.item_id END) AS month,
                    COUNT(CASE WHEN YEAR(bti.returned_at) = YEAR(CURDATE()) THEN bti.item_id END) AS year,
                    COUNT(bti.item_id) AS filtered_count
                FROM borrow_transaction_items bti
                WHERE bti.status IN ('lost', 'damaged') AND bti.book_id IS NOT NULL $whereClause;
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getLostDamagedBooksSummary: " . $e->getMessage());
            return [];
        }
    }

    public function getMostBorrowedBooksData($startDate, $endDate)
    {
        try {
            $sql = "
                SELECT 
                    b.title,
                    b.author,
                    b.accession_number,
                    COUNT(bti.item_id) AS range_total
                FROM borrow_transaction_items bti
                JOIN books b ON bti.book_id = b.book_id
                JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
                WHERE bti.book_id IS NOT NULL AND DATE(bt.borrowed_at) BETWEEN :startDate AND :endDate
                GROUP BY b.book_id, b.title, b.author, b.accession_number
                ORDER BY range_total DESC
                LIMIT 10;
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['startDate' => $startDate, 'endDate' => $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getMostBorrowedBooksData: " . $e->getMessage());
            return [];
        }
    }

    public function getTopBorrowersData($startDate, $endDate)
    {
        try {
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
                WHERE DATE(bt.borrowed_at) BETWEEN :startDate AND :endDate
                GROUP BY u.user_id, u.first_name, u.last_name, u.username, u.role
                ORDER BY range_total DESC
                LIMIT 10;
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['startDate' => $startDate, 'endDate' => $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getTopBorrowersData: " . $e->getMessage());
            return [];
        }
    }

    public function getOverdueSummaryData($startDate, $endDate)
    {
        try {
            // Count items that became overdue within the date range or are currently overdue and were borrowed in the range
            // For simplicity, we count items where the due_date falls within the range and status is 'overdue' or 'returned' (if it was returned late)
            $sql = "
                SELECT
                    'Overdue Books' as category,
                    COUNT(item_id) as range_total
                FROM borrow_transaction_items
                WHERE book_id IS NOT NULL 
                AND status = 'overdue'
                AND DATE(due_date) BETWEEN :startDate AND :endDate
                UNION ALL
                SELECT
                    'Overdue Equipments' as category,
                    COUNT(item_id) as range_total
                FROM borrow_transaction_items
                WHERE equipment_id IS NOT NULL 
                AND status = 'overdue'
                AND DATE(due_date) BETWEEN :startDate AND :endDate
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['startDate' => $startDate, 'endDate' => $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getOverdueSummaryData: " . $e->getMessage());
            return [];
        }
    }

    public function getLostDamagedBooksData($startDate, $endDate)
    {
        try {
            $sql = "
                SELECT
                    'Lost' AS category,
                    COUNT(CASE WHEN DATE(bti.returned_at) = :endDate THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bti.returned_at, 1) = YEARWEEK(:endDate, 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bti.returned_at) = YEAR(:endDate) AND MONTH(bti.returned_at) = MONTH(:endDate) THEN bti.item_id END) AS month,
                    COUNT(bti.item_id) AS range_total
                FROM borrow_transaction_items bti
                WHERE bti.status = 'lost' AND bti.book_id IS NOT NULL AND DATE(bti.returned_at) BETWEEN :startDate AND :endDate
                UNION ALL
                SELECT
                    'Damaged' AS category,
                    COUNT(CASE WHEN DATE(bti.returned_at) = :endDate THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bti.returned_at, 1) = YEARWEEK(:endDate, 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bti.returned_at) = YEAR(:endDate) AND MONTH(bti.returned_at) = MONTH(:endDate) THEN bti.item_id END) AS month,
                    COUNT(bti.item_id) AS range_total
                FROM borrow_transaction_items bti
                WHERE bti.status = 'damaged' AND bti.book_id IS NOT NULL AND DATE(bti.returned_at) BETWEEN :startDate AND :endDate
                UNION ALL
                SELECT
                    'TOTAL' AS category,
                    COUNT(CASE WHEN DATE(bti.returned_at) = :endDate THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bti.returned_at, 1) = YEARWEEK(:endDate, 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bti.returned_at) = YEAR(:endDate) AND MONTH(bti.returned_at) = MONTH(:endDate) THEN bti.item_id END) AS month,
                    COUNT(bti.item_id) AS range_total
                FROM borrow_transaction_items bti
                WHERE bti.status IN ('lost', 'damaged') AND bti.book_id IS NOT NULL AND DATE(bti.returned_at) BETWEEN :startDate AND :endDate;
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['startDate' => $startDate, 'endDate' => $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getLostDamagedBooksData: " . $e->getMessage());
            return [];
        }
    }

    public function getLibraryResourcesData()
    {
        try {
            // Count active books (available or borrowed, not deactivated)
            $stmtBooks = $this->db->query("SELECT COUNT(*) FROM books WHERE is_active = 1");
            $totalBooks = $stmtBooks->fetchColumn();

            // Count available books specifically
            $stmtAvail = $this->db->query("SELECT COUNT(*) FROM books WHERE is_active = 1 AND availability = 'available'");
            $availableBooks = $stmtAvail->fetchColumn();

            // Count active equipments (not deactivated)
            $stmtEquip = $this->db->query("SELECT COUNT(*) FROM equipments WHERE is_active = 1");
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

    public function getDeletedBooksData($startDate, $endDate)
    {
        try {
            $sql = "
                SELECT
                    SUM(CASE WHEN DATE(deleted_at) = :endDate THEN 1 ELSE 0 END) as today,
                    SUM(CASE WHEN YEARWEEK(deleted_at, 1) = YEARWEEK(:endDate, 1) THEN 1 ELSE 0 END) as week,
                    SUM(CASE WHEN MONTH(deleted_at) = MONTH(:endDate) AND YEAR(deleted_at) = YEAR(:endDate) THEN 1 ELSE 0 END) as month,
                    COUNT(*) as range_total
                FROM books
                WHERE deleted_at BETWEEN :startDate AND :endDate;
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['startDate' => $startDate, 'endDate' => $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getDeletedBooksData: " . $e->getMessage());
            return [];
        }
    }

    public function getCirculatedBooksData($startDate, $endDate)
    {
        try {
            $sql = "
                SELECT
                    'Student' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = :endDate THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(:endDate, 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(:endDate) AND MONTH(bt.borrowed_at) = MONTH(:endDate) THEN bti.item_id END) AS month,
                    COUNT(bti.item_id) AS range_total
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                WHERE bt.student_id IS NOT NULL AND bti.status IN ('borrowed', 'returned', 'overdue') AND bti.book_id IS NOT NULL AND DATE(bt.borrowed_at) BETWEEN :startDate AND :endDate
                UNION ALL
                SELECT
                    'Faculty' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = :endDate THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(:endDate, 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(:endDate) AND MONTH(bt.borrowed_at) = MONTH(:endDate) THEN bti.item_id END) AS month,
                    COUNT(bti.item_id) AS range_total
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                WHERE bt.faculty_id IS NOT NULL AND bti.status IN ('borrowed', 'returned', 'overdue') AND bti.book_id IS NOT NULL AND DATE(bt.borrowed_at) BETWEEN :startDate AND :endDate
                UNION ALL
                SELECT
                    'Staff' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = :endDate THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(:endDate, 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(:endDate) AND MONTH(bt.borrowed_at) = MONTH(:endDate) THEN bti.item_id END) AS month,
                    COUNT(bti.item_id) AS range_total
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                WHERE bt.staff_id IS NOT NULL AND bti.status IN ('borrowed', 'returned', 'overdue') AND bti.book_id IS NOT NULL AND DATE(bt.borrowed_at) BETWEEN :startDate AND :endDate
                UNION ALL
                SELECT
                    'TOTAL' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = :endDate THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(:endDate, 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(:endDate) AND MONTH(bt.borrowed_at) = MONTH(:endDate) THEN bti.item_id END) AS month,
                    COUNT(bti.item_id) AS range_total
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                WHERE bti.status IN ('borrowed', 'returned', 'overdue') AND bti.book_id IS NOT NULL AND DATE(bt.borrowed_at) BETWEEN :startDate AND :endDate;
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['startDate' => $startDate, 'endDate' => $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getCirculatedBooksData: " . $e->getMessage());
            return [];
        }
    }

    public function getCirculatedEquipmentsData($startDate, $endDate)
    {
        try {
            $sql = "
                SELECT
                    'Student' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = :endDate THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(:endDate, 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(:endDate) AND MONTH(bt.borrowed_at) = MONTH(:endDate) THEN bti.item_id END) AS month,
                    COUNT(bti.item_id) AS range_total
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                WHERE bt.student_id IS NOT NULL AND bti.status IN ('borrowed', 'returned', 'overdue') AND bti.equipment_id IS NOT NULL AND DATE(bt.borrowed_at) BETWEEN :startDate AND :endDate
                UNION ALL
                SELECT
                    'Faculty' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = :endDate THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(:endDate, 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(:endDate) AND MONTH(bt.borrowed_at) = MONTH(:endDate) THEN bti.item_id END) AS month,
                    COUNT(bti.item_id) AS range_total
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                WHERE bt.faculty_id IS NOT NULL AND bti.status IN ('borrowed', 'returned', 'overdue') AND bti.equipment_id IS NOT NULL AND DATE(bt.borrowed_at) BETWEEN :startDate AND :endDate
                UNION ALL
                SELECT
                    'Staff' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = :endDate THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(:endDate, 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(:endDate) AND MONTH(bt.borrowed_at) = MONTH(:endDate) THEN bti.item_id END) AS month,
                    COUNT(bti.item_id) AS range_total
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                WHERE bt.staff_id IS NOT NULL AND bti.status IN ('borrowed', 'returned', 'overdue') AND bti.equipment_id IS NOT NULL AND DATE(bt.borrowed_at) BETWEEN :startDate AND :endDate
                UNION ALL
                SELECT
                    'TOTAL' AS category,
                    COUNT(CASE WHEN DATE(bt.borrowed_at) = :endDate THEN bti.item_id END) AS today,
                    COUNT(CASE WHEN YEARWEEK(bt.borrowed_at, 1) = YEARWEEK(:endDate, 1) THEN bti.item_id END) AS week,
                    COUNT(CASE WHEN YEAR(bt.borrowed_at) = YEAR(:endDate) AND MONTH(bt.borrowed_at) = MONTH(:endDate) THEN bti.item_id END) AS month,
                    COUNT(bti.item_id) AS range_total
                FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
                WHERE bti.status IN ('borrowed', 'returned', 'overdue') AND bti.equipment_id IS NOT NULL AND DATE(bt.borrowed_at) BETWEEN :startDate AND :endDate;
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['startDate' => $startDate, 'endDate' => $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getCirculatedEquipmentsData: " . $e->getMessage());
            return [];
        }
    }

    public function getTopVisitorsData($startDate, $endDate)
    {
        try {
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
                WHERE a.date BETWEEN :startDate AND :endDate
                GROUP BY a.user_id, u.first_name, u.last_name, s.student_id, c.course_code
                ORDER BY visits DESC
                LIMIT 10;
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['startDate' => $startDate, 'endDate' => $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getTopVisitorsData: " . $e->getMessage());
            return [];
        }
    }

    public function getLibraryVisitsData($startDate, $endDate)
    {
        try {
            $sql = "
                WITH DepartmentVisits AS (
                    SELECT
                        cl.college_name AS department,
                        COUNT(CASE WHEN DATE(a.date) = :endDate THEN a.id END) AS today,
                        COUNT(CASE WHEN a.date BETWEEN DATE_SUB(:endDate, INTERVAL 6 DAY) AND :endDate THEN a.id END) AS week,
                        COUNT(CASE WHEN MONTH(a.date) = MONTH(:endDate) AND YEAR(a.date) = YEAR(:endDate) THEN a.id END) AS month,
                        COUNT(a.id) AS range_total
                    FROM colleges cl
                    LEFT JOIN courses c ON cl.college_id = c.college_id
                    LEFT JOIN students s ON c.course_id = s.course_id
                    LEFT JOIN attendance a ON s.user_id = a.user_id AND a.date BETWEEN :startDate AND :endDate
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
            $stmt->execute(['startDate' => $startDate, 'endDate' => $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ReportRepository error in getLibraryVisitsData: " . $e->getMessage());
            return [];
        }
    }

    public function getTopVisitors(int $limit = 5): array
    {
        $sql = "
            SELECT u.first_name, u.last_name, COUNT(a.user_id) AS visits
            FROM attendance a
            JOIN users u ON u.user_id = a.user_id
            WHERE DATE(a.first_scan_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY a.user_id
            ORDER BY visits DESC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($r) {
        return [
            'user_name' => trim($r['first_name'] . ' ' . $r['last_name']),
            'visits' => (int)$r['visits']
        ];
        }, $rows);
    }

    public function getActivityReport(string $filter = 'month'): array
    {
        $data = [];
        if ($filter === 'day') {
            // Group by hour for today
            for ($i = 0; $i < 24; $i++) {
                $hour = str_pad($i, 2, '0', STR_PAD_LEFT);
                $label = $hour . ":00";
                
                $stmt = $this->db->prepare("SELECT COUNT(DISTINCT user_id) FROM attendance WHERE DATE(first_scan_at) = CURDATE() AND HOUR(first_scan_at) = :hour");
                $stmt->execute(['hour' => $i]);
                $visitors = (int) $stmt->fetchColumn();

                $stmt = $this->db->prepare("SELECT COUNT(*) FROM borrow_transactions WHERE DATE(borrowed_at) = CURDATE() AND HOUR(borrowed_at) = :hour");
                $stmt->execute(['hour' => $i]);
                $borrows = (int) $stmt->fetchColumn();

                $data[] = ['label' => $label, 'visitors' => $visitors, 'borrows' => $borrows];
            }
        } elseif ($filter === 'year') {
            // Group by month for this year
            for ($i = 1; $i <= 12; $i++) {
                $label = date('M', mktime(0, 0, 0, $i, 1));
                
                $stmt = $this->db->prepare("SELECT COUNT(DISTINCT user_id) FROM attendance WHERE YEAR(first_scan_at) = YEAR(CURDATE()) AND MONTH(first_scan_at) = :month");
                $stmt->execute(['month' => $i]);
                $visitors = (int) $stmt->fetchColumn();

                $stmt = $this->db->prepare("SELECT COUNT(*) FROM borrow_transactions WHERE YEAR(borrowed_at) = YEAR(CURDATE()) AND MONTH(borrowed_at) = :month");
                $stmt->execute(['month' => $i]);
                $borrows = (int) $stmt->fetchColumn();

                $data[] = ['label' => $label, 'visitors' => $visitors, 'borrows' => $borrows];
            }
        } else {
            // Default: Group by day for this month (last 30 days)
            for ($i = 29; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $label = date('M d', strtotime($date));

                $stmt = $this->db->prepare("SELECT COUNT(DISTINCT user_id) FROM attendance WHERE DATE(first_scan_at) = :date");
                $stmt->execute(['date' => $date]);
                $visitors = (int) $stmt->fetchColumn();

                $stmt = $this->db->prepare("SELECT COUNT(*) FROM borrow_transactions WHERE DATE(borrowed_at) = :date");
                $stmt->execute(['date' => $date]);
                $borrows = (int) $stmt->fetchColumn();

                $data[] = ['label' => $label, 'visitors' => $visitors, 'borrows' => $borrows];
            }
        }

        return $data;
    }
}