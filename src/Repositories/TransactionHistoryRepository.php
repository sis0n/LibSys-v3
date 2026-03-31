<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

class TransactionHistoryRepository
{
  protected PDO $db;

  public function __construct()
  {
    $this->db = Database::getInstance()->getConnection();
  }

  private function getBaseSelectQuery(): string
  {
    return "
            SELECT 
                bt.transaction_id, bt.transaction_code, bt.borrowed_at, bt.due_date, bt.expires_at,
                bti.returned_at,
                bt.status AS transaction_status,

                librarian.user_id AS librarian_id,
                CONCAT(librarian.first_name, ' ', COALESCE(librarian.middle_name,''), ' ', librarian.last_name) AS librarian_name,

                s.student_id, s.student_number, s.course_id, s.year_level, s.section,
                f.faculty_id, f.unique_faculty_id, f.college_id,
                st.staff_id, st.employee_id, st.position,
                g.guest_id, g.first_name AS guest_first_name, g.last_name AS guest_last_name,

                COALESCE(su.first_name, fu.first_name, stu.first_name, g.first_name) AS first_name,
                COALESCE(su.middle_name, fu.middle_name, stu.middle_name) AS middle_name,
                COALESCE(su.last_name, fu.last_name, stu.last_name, g.last_name) AS last_name,

                -- Item details (Book or Equipment)
                COALESCE(b.title, e.equipment_name) as item_name,
                b.author AS book_author, 
                b.accession_number, 
                b.call_number, 
                b.book_isbn, 
                b.cover,
                e.equipment_id,
                e.equipment_name AS equipment_real_name, -- Added to differentiate from item_name if needed
                e.asset_tag,
                
                CASE 
                    WHEN bti.book_id IS NOT NULL THEN 'Book'
                    WHEN bti.equipment_id IS NOT NULL THEN 'Equipment'
                    ELSE 'Unknown'
                END as item_type,

                c.course_code, c.course_title,
                cl.college_code, cl.college_name
        ";
  }

  private function getBaseFromJoinQuery(): string
  {
    return "
            FROM borrow_transactions bt
            LEFT JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
            
            -- Join both books and equipment, one of them will match
            LEFT JOIN books b ON bti.book_id = b.book_id
            LEFT JOIN equipments e ON bti.equipment_id = e.equipment_id

            LEFT JOIN students s ON bt.student_id = s.student_id
            LEFT JOIN faculty f ON bt.faculty_id = f.faculty_id
            LEFT JOIN staff st ON bt.staff_id = st.staff_id
            LEFT JOIN guests g ON bt.guest_id = g.guest_id
            
            -- User Details for Borrowers
            LEFT JOIN users su ON s.user_id = su.user_id
            LEFT JOIN users fu ON f.user_id = fu.user_id
            LEFT JOIN users stu ON st.user_id = stu.user_id

            -- Course/College Details
            LEFT JOIN courses c ON s.course_id = c.course_id
            LEFT JOIN colleges cl ON f.college_id = cl.college_id

            LEFT JOIN users librarian ON bt.librarian_id = librarian.user_id
            
            -- Joined user table for campus filtering
            JOIN users u ON u.user_id = COALESCE(s.user_id, f.user_id, st.user_id)
        ";
  }

  public function getAllTransactions(?string $date = null, ?int $campusId = null): array
  {
    $whereClause = "bt.status != 'Pending'";
    if ($date) $whereClause .= " AND DATE(bt.borrowed_at) = :date";
    if ($campusId !== null) $whereClause .= " AND u.campus_id = :campus_id";

    $sql = $this->getBaseSelectQuery() . $this->getBaseFromJoinQuery() . "
            WHERE $whereClause
            ORDER BY bt.borrowed_at DESC
        ";

    $stmt = $this->db->prepare($sql);
    if ($date) $stmt->bindParam(':date', $date);
    if ($campusId !== null) $stmt->bindParam(':campus_id', $campusId, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getTransactionsByStatus(string $status, ?string $date = null, ?int $campusId = null): array
  {
    if (strtolower($status) === 'pending') {
      return [];
    }

    $whereClause = "bt.status = :status";
    if ($date) $whereClause .= " AND DATE(bt.borrowed_at) = :date";
    if ($campusId !== null) $whereClause .= " AND u.campus_id = :campus_id";

    $sql = $this->getBaseSelectQuery() . $this->getBaseFromJoinQuery() . "
            WHERE $whereClause
            ORDER BY bt.borrowed_at DESC
        ";

    $stmt = $this->db->prepare($sql);
    $stmt->bindParam(':status', $status);
    if ($date) $stmt->bindParam(':date', $date);
    if ($campusId !== null) $stmt->bindParam(':campus_id', $campusId, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}
