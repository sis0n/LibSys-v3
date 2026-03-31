<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

class ReturningRepository
{
  private $db;

  public function __construct()
  {
    $this->db = Database::getInstance()->getConnection();
  }

  public function getOverdue(?int $campusId = null): ?array
  {
    try {
      $this->db->query("UPDATE borrow_transactions SET status = 'overdue' WHERE status = 'borrowed' AND due_date < NOW()");
      $this->db->query("UPDATE borrow_transaction_items bti 
                              INNER JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
                              SET bti.status = 'overdue' 
                              WHERE bt.status = 'overdue' AND bti.status = 'borrowed'");

      $whereClause = "(bti.status = 'borrowed' OR bti.status = 'overdue') AND bt.due_date < NOW()";
      if ($campusId !== null) {
          $whereClause .= " AND bt.campus_id = " . (int)$campusId;
      }

      $queryOverdue = "
                SELECT 
                    bti.status,
                    bt.due_date,
                    bt.borrowed_at,
                    b.title AS item_borrowed,
                    u.first_name, u.last_name,
                    u.email, 
                    s.student_number AS id_number, s.year_level, s.section, s.contact AS contact_number,
                    f.faculty_id AS id_number_f, f.contact AS contact_number_f,
                    st.staff_id AS id_number_st, st.position AS department_st, st.contact AS contact_number_st,
                    g.guest_id AS id_number_g, g.contact AS contact_number_g, g.first_name AS g_first_name, g.last_name AS g_last_name,
                    s.course_id, 
                    f.college_id, 
                    COALESCE(c.course_code, cl.college_code, st.position) AS department_course_code,
                    COALESCE(c.course_title, cl.college_name, st.position) AS department_course_name
                FROM borrow_transaction_items bti
                JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
                JOIN books b ON bti.book_id = b.book_id
                LEFT JOIN students s ON bt.student_id = s.student_id
                LEFT JOIN faculty f ON bt.faculty_id = f.faculty_id
                LEFT JOIN staff st ON bt.staff_id = st.staff_id
                LEFT JOIN guests g ON bt.guest_id = g.guest_id
                LEFT JOIN users u ON u.user_id = COALESCE(s.user_id, f.user_id, st.user_id)
                LEFT JOIN courses c ON s.course_id = c.course_id
                LEFT JOIN colleges cl ON f.college_id = cl.college_id
                WHERE $whereClause
                ORDER BY bt.due_date ASC
            ";

      $stmtOverdue = $this->db->prepare($queryOverdue);
      $stmtOverdue->execute();
      $overdue = $stmtOverdue->fetchAll(PDO::FETCH_ASSOC);

      return [
        'overdue' => array_map([$this, 'formatTableData'], $overdue)
      ];
    } catch (PDOException $e) {
      http_response_code(500);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['success' => false, 'error' => $e->getMessage()]);
      exit;
    }
  }

  public function getRecentReturns(int $limit = 5, ?int $campusId = null): array
  {
    $whereClause = "bti.status = 'returned'";
    if ($campusId !== null) {
        $whereClause .= " AND bt.campus_id = " . (int)$campusId;
    }

    $sql = "
        SELECT 
            COALESCE(b.title, e.equipment_name) as item_title,
            COALESCE(b.accession_number, e.asset_tag) as accession_number,
            bti.returned_at,
            u.first_name, u.last_name,
            COALESCE(s.student_number, f.unique_faculty_id, st.employee_id) as identifier,
            COALESCE(CONCAT(s.year_level, ' ', s.section), cl.college_code, st.position) as year_section
        FROM borrow_transaction_items bti
        JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
        LEFT JOIN books b ON bti.book_id = b.book_id
        LEFT JOIN equipments e ON bti.equipment_id = e.equipment_id
        LEFT JOIN students s ON bt.student_id = s.student_id
        LEFT JOIN faculty f ON bt.faculty_id = f.faculty_id
        LEFT JOIN staff st ON bt.staff_id = st.staff_id
        LEFT JOIN users u ON u.user_id = COALESCE(s.user_id, f.user_id, st.user_id)
        LEFT JOIN colleges cl ON f.college_id = cl.college_id
        WHERE $whereClause
        ORDER BY bti.returned_at DESC
        LIMIT :limit
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function findItemByIdentifier($identifier): ?array
  {
    try {
      // 1. First, look for ACTIVE BORROWINGS (borrowed or overdue) for this book
      $stmt = $this->db->prepare("
          SELECT 
              bti.item_id as borrowing_id, bti.transaction_id, bti.book_id, bti.status as bti_status,
              bt.borrowed_at, bt.due_date,
              u.first_name, u.last_name, u.email,
              s.student_number, s.year_level, s.section,
              f.unique_faculty_id,
              st.employee_id,
              c.course_code, c.course_title,
              cl.college_code,
              b.title as book_title, b.author, b.accession_number, b.call_number, b.book_isbn, b.campus_id as book_campus_id,
              camp.campus_name as home_campus_name
          FROM borrow_transaction_items bti
          JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
          JOIN books b ON bti.book_id = b.book_id
          LEFT JOIN students s ON bt.student_id = s.student_id
          LEFT JOIN faculty f ON bt.faculty_id = f.faculty_id
          LEFT JOIN staff st ON bt.staff_id = st.staff_id
          LEFT JOIN users u ON u.user_id = COALESCE(s.user_id, f.user_id, st.user_id)
          LEFT JOIN courses c ON s.course_id = c.course_id
          LEFT JOIN colleges cl ON f.college_id = cl.college_id
          LEFT JOIN campuses camp ON camp.campus_id = b.campus_id
          WHERE b.accession_number = ?
          AND bti.status IN ('borrowed', 'overdue')
      ");
      $stmt->execute([$identifier]);
      $activeBorrowings = $stmt->fetchAll(PDO::FETCH_ASSOC);

      if (count($activeBorrowings) > 0) {
        $matches = [];
        foreach ($activeBorrowings as $row) {
          $borrowerName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
          
          $idNumber = $row['student_number'] ?? $row['unique_faculty_id'] ?? $row['employee_id'] ?? 'N/A';
          $dept = $row['course_code'] ?? $row['college_code'] ?? 'N/A';
          $yearSec = isset($row['year_level']) ? ($row['year_level'] . ' ' . $row['section']) : 'N/A';

          $matches[] = [
            'status' => 'borrowed',
            'item_type' => 'Book',
            'title' => $row['book_title'],
            'author' => $row['author'] ?? null,
            'accession_number' => $row['accession_number'],
            'borrower_name' => $borrowerName,
            'id_number' => $idNumber,
            'course_or_department' => $dept,
            'student_year_section' => $yearSec,
            'due_date' => $row['due_date'],
            'borrowing_id' => $row['borrowing_id'],
            'availability' => $row['bti_status'],
            'home_campus_id' => $row['book_campus_id'],
            'home_campus_name' => $row['home_campus_name'],
            'call_number' => $row['call_number'] ?? null,
            'book_isbn' => $row['book_isbn'] ?? null
          ];
        }
        
        return [
          'status' => 'borrowed',
          'matches' => $matches,
          'count' => count($matches)
        ];
      }

      // 2. If no active borrowings, check if the book exists at all (Available)
      $stmt = $this->db->prepare("
          SELECT b.*, camp.campus_name as home_campus_name 
          FROM books b 
          LEFT JOIN campuses camp ON b.campus_id = camp.id 
          WHERE b.accession_number = ?
      ");
      $stmt->execute([$identifier]);
      $book = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($book) {
        return [
          'status' => 'available',
          'details' => array_merge($book, [
            'item_type' => 'Book',
            'home_campus_name' => $book['home_campus_name']
          ])
        ];
      }

      return ['status' => 'not_found'];
    } catch (PDOException $e) {
      error_log('[ReturningRepository::findItemByIdentifier] ' . $e->getMessage());
      return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
    }
  }

  public function markAsReturned($itemId, $condition = 'good', $newCampusId = null): ?array
  {
    try {
      $this->db->beginTransaction();

      $stmtGetItem = $this->db->prepare("
                SELECT bti.book_id, bti.equipment_id, bti.transaction_id
                FROM borrow_transaction_items bti
                WHERE bti.item_id = ? AND (bti.status = 'borrowed' OR bti.status = 'overdue')
            ");
      $stmtGetItem->execute([$itemId]);
      $itemInfo = $stmtGetItem->fetch(PDO::FETCH_ASSOC);

      if (!$itemInfo) {
        $this->db->rollBack();
        return null;
      }

      $bookId = $itemInfo['book_id'];
      $equipmentId = $itemInfo['equipment_id'];
      $transactionId = $itemInfo['transaction_id'];

      $itemStatus = ($condition === 'good') ? 'returned' : $condition;
      $availabilityStatus = ($condition === 'good') ? 'available' : $condition;

      $stmtUpdateItem = $this->db->prepare("
                UPDATE borrow_transaction_items
                SET status = ?, returned_at = NOW()
                WHERE item_id = ?
            ");
      $stmtUpdateItem->execute([$itemStatus, $itemId]);

      if ($bookId !== null) {
        if ($newCampusId) {
          $stmtUpdateBook = $this->db->prepare("UPDATE books SET availability = ?, campus_id = ? WHERE book_id = ?");
          $stmtUpdateBook->execute([$availabilityStatus, $newCampusId, $bookId]);
        } else {
          $stmtUpdateBook = $this->db->prepare("UPDATE books SET availability = ? WHERE book_id = ?");
          $stmtUpdateBook->execute([$availabilityStatus, $bookId]);
        }
      } elseif ($equipmentId !== null) {
        if ($newCampusId) {
          $stmtUpdateEquipment = $this->db->prepare("UPDATE equipments SET status = ?, campus_id = ? WHERE equipment_id = ?");
          $stmtUpdateEquipment->execute([$availabilityStatus, $newCampusId, $equipmentId]);
        } else {
          $stmtUpdateEquipment = $this->db->prepare("UPDATE equipments SET status = ? WHERE equipment_id = ?");
          $stmtUpdateEquipment->execute([$availabilityStatus, $equipmentId]);
        }
      } else {
        $this->db->rollBack();
        return null;
      }

      $stmtCheckAll = $this->db->prepare("
                SELECT COUNT(*) as remaining
                FROM borrow_transaction_items
                WHERE transaction_id = ? AND (status = 'borrowed' OR status = 'overdue')
            ");
      $stmtCheckAll->execute([$transactionId]);
      $remaining = $stmtCheckAll->fetch(PDO::FETCH_ASSOC)['remaining'];

      if ($remaining == 0) {
        $stmtUpdateTrans = $this->db->prepare("UPDATE borrow_transactions SET status = 'returned' WHERE transaction_id = ?");
        $stmtUpdateTrans->execute([$transactionId]);
      }

      $this->db->commit();
      return ['success' => true];
    } catch (PDOException $e) {
      $this->db->rollBack();
      error_log('[ReturningRepository::markAsReturned] ' . $e->getMessage());
      return null;
    }
  }

  public function extendDueDate($itemId, $days): ?string
  {
    try {
      $this->db->beginTransaction();
      $stmtGetTrans = $this->db->prepare("SELECT transaction_id FROM borrow_transaction_items WHERE item_id = ?");
      $stmtGetTrans->execute([$itemId]);
      $item = $stmtGetTrans->fetch(PDO::FETCH_ASSOC);

      if (!$item) {
        $this->db->rollBack();
        return null;
      }
      $transactionId = $item['transaction_id'];

      $stmtUpdate = $this->db->prepare("
                UPDATE borrow_transactions 
                SET due_date = DATE_ADD(due_date, INTERVAL ? DAY), status = 'borrowed'
                WHERE transaction_id = ? AND (status = 'borrowed' OR status = 'overdue')
            ");
      $stmtUpdate->execute([$days, $transactionId]);

      if ($stmtUpdate->rowCount() == 0) {
        $this->db->rollBack();
        return null;
      }

      $this->db->prepare("UPDATE borrow_transaction_items SET status = 'borrowed' WHERE transaction_id = ? AND status = 'overdue'")
        ->execute([$transactionId]);

      $stmtSelect = $this->db->prepare("SELECT due_date FROM borrow_transactions WHERE transaction_id = ?");
      $stmtSelect->execute([$transactionId]);
      $result = $stmtSelect->fetch(PDO::FETCH_ASSOC);

      $this->db->commit();
      return (new \DateTime($result['due_date']))->format('Y-m-d');
    } catch (PDOException $e) {
      $this->db->rollBack();
      error_log('[ReturningRepository::extendDueDate] ' . $e->getMessage());
      return null;
    }
  }

  private function formatTableData($row)
  {
    $type = 'student';
    $id = $row['id_number'] ?? 'N/A';
    $contact = $row['contact_number'] ?? 'N/A';

    if (!empty($row['id_number_f'])) {
      $type = 'faculty';
      $id = $row['id_number_f'];
      $contact = $row['contact_number_f'];
    } elseif (!empty($row['id_number_st'])) {
      $type = 'staff';
      $id = $row['id_number_st'];
      $contact = $row['contact_number_st'];
    } elseif (!empty($row['id_number_g'])) {
      $type = 'guest';
      $id = $row['id_number_g'];
      $contact = $row['contact_number_g'];
    }

    $courseOrDept = match ($type) {
      'student' => ($row['department_course_code'] ?? 'N/A') . ' - ' . ($row['department_course_name'] ?? 'N/A') . ' ' . ($row['year_level'] ?? 'N/A') . ' ' . ($row['section'] ?? ''),
      'faculty' => $row['department_course_name'] ?? 'N/A',
      'staff' => $row['department_st'] ?? 'N/A',
      'guest' => 'N/A',
      default => 'N/A'
    };

    return [
      'borrower_type' => $type,
      'user_name' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
      'user_id' => $id,
      'department_or_course' => $courseOrDept,
      'item_borrowed' => $row['item_borrowed'],
      'date_borrowed' => (new \DateTime($row['borrowed_at']))->format('Y-m-d'),
      'due_date' => (new \DateTime($row['due_date']))->format('Y-m-d'),
      'contact' => $contact,
      'email' => $row['email'] ?? null,
    ];
  }
}
