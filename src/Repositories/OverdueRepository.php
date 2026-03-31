<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

class OverdueRepository
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

    public function getOverdueStats(?int $campusId = null): array
    {
        // Refresh statuses first
        $this->db->query("UPDATE borrow_transactions SET status = 'overdue' WHERE status = 'borrowed' AND due_date < NOW()");
        $this->db->query("UPDATE borrow_transaction_items bti 
                          INNER JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
                          SET bti.status = 'overdue' 
                          WHERE bt.status = 'overdue' AND bti.status = 'borrowed'");

        $campusWhere = $campusId !== null ? " AND u.campus_id = " . (int)$campusId : "";
        $borrowerJoin = $this->getBorrowerJoinSql();

        $stats = [
            'total' => 0,
            'critical' => 0,
            'due_today' => 0,
            'notified_today' => 0
        ];

        $stmt = $this->db->query("SELECT COUNT(*) FROM borrow_transaction_items bti JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id $borrowerJoin WHERE bti.status = 'overdue' $campusWhere");
        $stats['total'] = (int)$stmt->fetchColumn();

        $stmt = $this->db->query("
            SELECT COUNT(*) FROM borrow_transaction_items bti
            JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
            $borrowerJoin
            WHERE bti.status = 'overdue' AND DATEDIFF(NOW(), bt.due_date) > 7 $campusWhere
        ");
        $stats['critical'] = (int)$stmt->fetchColumn();

        $stmt = $this->db->query("SELECT COUNT(*) FROM borrow_transactions bt $borrowerJoin WHERE DATE(bt.due_date) = CURDATE() AND bt.status = 'borrowed' $campusWhere");
        $stats['due_today'] = (int)$stmt->fetchColumn();

        $stmt = $this->db->query("SELECT COUNT(*) FROM notification_logs nl JOIN users u ON nl.recipient_user_id = u.user_id WHERE DATE(nl.sent_at) = CURDATE() $campusWhere");
        $stats['notified_today'] = (int)$stmt->fetchColumn();

        return $stats;
    }

    public function fetchOverdueList($filters = [], ?int $campusId = null): array
    {
        $campusWhere = $campusId !== null ? " AND u.campus_id = :campus_id" : "";
        
        $sql = "
            SELECT 
                bti.item_id, bti.status, bti.returned_at,
                bt.due_date, bt.borrowed_at, bt.transaction_id,
                COALESCE(b.title, e.equipment_name) AS item_title, 
                COALESCE(b.accession_number, e.asset_tag) AS accession_number,
                u.first_name, u.last_name, u.email, u.user_id,
                COALESCE(s.student_number, f.unique_faculty_id, st.employee_id) AS student_number,
                s.year_level, s.section,
                COALESCE(c.course_code, cl.college_code) as dept_code,
                DATEDIFF(NOW(), bt.due_date) as days_late,
                (SELECT MAX(sent_at) FROM notification_logs WHERE borrowing_item_id = bti.item_id) as last_notified,
                (SELECT COUNT(*) FROM notification_logs WHERE borrowing_item_id = bti.item_id) as notification_count
            FROM borrow_transaction_items bti
            JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
            LEFT JOIN books b ON bti.book_id = b.book_id
            LEFT JOIN equipments e ON bti.equipment_id = e.equipment_id
            LEFT JOIN students s ON bt.student_id = s.student_id
            LEFT JOIN faculty f ON bt.faculty_id = f.faculty_id
            LEFT JOIN staff st ON bt.staff_id = st.staff_id
            JOIN users u ON u.user_id = COALESCE(s.user_id, f.user_id, st.user_id)
            LEFT JOIN courses c ON s.course_id = c.course_id
            LEFT JOIN colleges cl ON f.college_id = cl.college_id
            WHERE bti.status = 'overdue' $campusWhere
        ";

        $params = [];
        if ($campusId !== null) {
            $params['campus_id'] = $campusId;
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR s.student_number LIKE :search OR b.title LIKE :search OR e.equipment_name LIKE :search)";
            $params['search'] = "%{$filters['search']}%";
        }

        $sql .= " ORDER BY days_late DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function logNotification($borrowingItemId, $recipientUserId, $recipientEmail, $sentBy)
    {
        $sql = "INSERT INTO notification_logs (borrowing_item_id, recipient_user_id, recipient_email, sent_by) 
                VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$borrowingItemId, $recipientUserId, $recipientEmail, $sentBy]);
    }
}
