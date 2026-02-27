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

  public function getOverdue(): ?array
  {
    try {
      // AUTO-UPDATE STATUSES
      $this->db->query("UPDATE borrow_transactions SET status = 'overdue' WHERE status = 'borrowed' AND due_date < NOW()");
      $this->db->query("UPDATE borrow_transaction_items bti 
                              INNER JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
                              SET bti.status = 'overdue' 
                              WHERE bt.status = 'overdue' AND bti.status = 'borrowed'");

      $baseSelect = "
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
            ";

      $baseFrom = "
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
            ";

      $queryOverdue = $baseSelect . $baseFrom . "
                WHERE (bti.status = 'borrowed' OR bti.status = 'overdue')
                AND bt.due_date < NOW()
            ";

      $stmtOverdue = $this->db->prepare($queryOverdue . " ORDER BY bt.due_date ASC");
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

  public function findItemByIdentifier($identifier): ?array
  {
    try {
      // 1. Subukang hanapin kung LIBRO (via accession number)
      $stmt = $this->db->prepare("SELECT * FROM books WHERE accession_number = ?");
      $stmt->execute([$identifier]);
      $book = $stmt->fetch(PDO::FETCH_ASSOC);

      $itemIdForQuery = null;
      $itemType = null;
      $itemBasicDetails = null;

      if ($book) {
        $itemIdForQuery = $book['book_id'];
        $itemType = 'Book';
        $itemBasicDetails = $book;
      } else {
        // 2. Kung hindi libro, subukang hanapin kung EQUIPMENT (via name o asset tag)
        $stmt = $this->db->prepare("SELECT * FROM equipments WHERE equipment_name = ? OR asset_tag = ?");
        $stmt->execute([$identifier, $identifier]);
        $equipment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($equipment) {
          $itemIdForQuery = $equipment['equipment_id'];
          $itemType = 'Equipment';
          $itemBasicDetails = $equipment;
        } else {
          return ['status' => 'not_found'];
        }
      }

      // 3. I-check ang availability/status
      $availability = strtolower(trim(($itemType === 'Book' ? $itemBasicDetails['availability'] : $itemBasicDetails['status']) ?? ''));

      if ($availability === 'borrowed' || $availability === 'overdue') {
        $idColumn = ($itemType === 'Book') ? 'bti.book_id' : 'bti.equipment_id';

        $stmt = $this->db->prepare("
                    SELECT 
                        bti.item_id, bti.transaction_id, bti.book_id, bti.equipment_id,
                        bt.borrowed_at AS date_borrowed, bt.due_date,
                        bt.student_id, bt.faculty_id, bt.staff_id, bt.guest_id,
                        s.student_number, s.year_level, s.section, s.contact AS student_contact,
                        u_student.first_name AS student_first_name, u_student.last_name AS student_last_name, u_student.email AS student_email, 
                        c.course_code, c.course_title, 
                        f.unique_faculty_id, f.college_id, f.contact AS faculty_contact, 
                        u_faculty.first_name AS faculty_first_name, u_faculty.last_name AS faculty_last_name, u_faculty.email AS faculty_email, 
                        cl.college_code, cl.college_name, 
                        st.staff_id AS staff_id_num, st.employee_id, st.position AS staff_position, st.contact AS staff_contact,
                        u_staff.first_name AS staff_first_name, u_staff.last_name AS staff_last_name, u_staff.email AS staff_email,
                        g.guest_id AS guest_id_num, g.first_name AS guest_first_name, g.last_name AS guest_last_name, g.contact AS guest_contact,
                        bti.status, bti.item_id AS borrowing_id,
                        COALESCE(b.title, e.equipment_name) AS item_title,
                        b.author, b.book_isbn, b.accession_number, b.call_number,
                        e.asset_tag
                    FROM borrow_transaction_items bti
                    JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
                    LEFT JOIN students s ON bt.student_id = s.student_id
                    LEFT JOIN users u_student ON s.user_id = u_student.user_id
                    LEFT JOIN courses c ON s.course_id = c.course_id 
                    LEFT JOIN faculty f ON bt.faculty_id = f.faculty_id
                    LEFT JOIN users u_faculty ON f.user_id = u_faculty.user_id
                    LEFT JOIN colleges cl ON f.college_id = cl.college_id 
                    LEFT JOIN staff st ON bt.staff_id = st.staff_id
                    LEFT JOIN users u_staff ON st.user_id = u_staff.user_id
                    LEFT JOIN guests g ON bt.guest_id = g.guest_id
                    LEFT JOIN books b ON bti.book_id = b.book_id
                    LEFT JOIN equipments e ON bti.equipment_id = e.equipment_id
                    WHERE {$idColumn} = ? AND (bti.status = 'borrowed' OR bti.status = 'overdue')
                    ORDER BY bt.borrowed_at DESC
                    LIMIT 1
                ");
        $stmt->execute([$itemIdForQuery]);
        $borrowInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($borrowInfo) {
          $borrowerName = $borrowerType = $contact = $email = $idNumber = $courseOrDept = $yearSectionDisplay = 'N/A';

          if (!empty($borrowInfo['student_id'])) {
            $borrowerName = trim(($borrowInfo['student_first_name'] ?? '') . ' ' . ($borrowInfo['student_last_name'] ?? ''));
            $borrowerType = 'student';
            $contact = $borrowInfo['student_contact'] ?? 'N/A';
            $email = $borrowInfo['student_email'] ?? 'N/A';
            $idNumber = $borrowInfo['student_number'] ?? 'N/A';
            $courseOrDept = trim(($borrowInfo['course_code'] ?? 'N/A') . ' - ' . ($borrowInfo['course_title'] ?? 'N/A'));
            $yearSectionDisplay = trim(($borrowInfo['year_level'] ?? 'N/A') . ' ' . ($borrowInfo['section'] ?? 'N/A'));
          } elseif (!empty($borrowInfo['faculty_id'])) {
            $borrowerName = trim(($borrowInfo['faculty_first_name'] ?? '') . ' ' . ($borrowInfo['faculty_last_name'] ?? ''));
            $borrowerType = 'faculty';
            $contact = $borrowInfo['faculty_contact'] ?? 'N/A';
            $email = $borrowInfo['faculty_email'] ?? 'N/A';
            $idNumber = $borrowInfo['unique_faculty_id'] ?? $borrowInfo['faculty_id'] ?? 'N/A';
            $courseOrDept = trim(($borrowInfo['college_code'] ?? 'N/A') . ' - ' . ($borrowInfo['college_name'] ?? 'N/A'));
            $yearSectionDisplay = $borrowInfo['college_name'] ?? 'N/A';
          } elseif (!empty($borrowInfo['staff_id'])) {
            $borrowerName = trim(($borrowInfo['staff_first_name'] ?? '') . ' ' . ($borrowInfo['staff_last_name'] ?? ''));
            $borrowerType = 'staff';
            $contact = $borrowInfo['staff_contact'] ?? 'N/A';
            $email = $borrowInfo['staff_email'] ?? 'N/A';
            $idNumber = $borrowInfo['employee_id'] ?? $borrowInfo['staff_id_num'] ?? 'N/A';
            $courseOrDept = $borrowInfo['staff_position'] ?? 'N/A';
            $yearSectionDisplay = $borrowerType;
          } elseif (!empty($borrowInfo['guest_id'])) {
            $borrowerName = trim(($borrowInfo['guest_first_name'] ?? '') . ' ' . ($borrowInfo['guest_last_name'] ?? ''));
            $borrowerType = 'guest';
            $contact = $borrowInfo['guest_contact'] ?? 'N/A';
            $email = 'N/A';
            $idNumber = $borrowInfo['guest_id_num'] ?? 'N/A';
            $courseOrDept = 'N/A';
            $yearSectionDisplay = $borrowerType;
          }

          return [
            'status' => 'borrowed',
            'details' => [
              'item_type' => $itemType,
              'title' => $borrowInfo['item_title'],
              'author' => $borrowInfo['author'] ?? null,
              'book_isbn' => $borrowInfo['book_isbn'] ?? null,
              'accession_number' => $borrowInfo['accession_number'] ?? null,
              'call_number' => $borrowInfo['call_number'] ?? null,
              'asset_tag' => $borrowInfo['asset_tag'] ?? null,
              'borrower_type' => $borrowerType,
              'borrower_name' => $borrowerName,
              'id_number' => $idNumber,
              'course_or_department' => $courseOrDept,
              'student_year_section' => $yearSectionDisplay,
              'contact' => $contact,
              'email' => $email,
              'date_borrowed' => $borrowInfo['date_borrowed'],
              'due_date' => $borrowInfo['due_date'],
              'borrowing_id' => $borrowInfo['borrowing_id'],
              'availability' => $borrowInfo['status']
            ]
          ];
        }
        return ['status' => 'available', 'details' => ['item_type' => $itemType, 'title' => ($itemType === 'Book' ? $itemBasicDetails['title'] : $itemBasicDetails['equipment_name'])]];
      }

      return ['status' => 'available', 'details' => ['item_type' => $itemType, 'title' => ($itemType === 'Book' ? $itemBasicDetails['title'] : $itemBasicDetails['equipment_name'])]];
    } catch (PDOException $e) {
      error_log('[ReturningRepository::findItemByIdentifier] ' . $e->getMessage());
      return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
    }
  }

  public function markAsReturned($itemId): ?array
  {
    try {
      $this->db->beginTransaction();

      // Allow returning if status is 'borrowed' OR 'overdue'
      // Fetch both book_id and equipment_id
      $stmtGetItem = $this->db->prepare("
                SELECT bti.book_id, bti.equipment_id, bti.transaction_id
                FROM borrow_transaction_items bti
                WHERE bti.item_id = ? AND (bti.status = 'borrowed' OR bti.status = 'overdue')
            ");
      $stmtGetItem->execute([$itemId]);
      $itemInfo = $stmtGetItem->fetch(PDO::FETCH_ASSOC);

      if (!$itemInfo) {
        $this->db->rollBack();
        return null; // Item not found or already returned
      }

      $bookId = $itemInfo['book_id'];
      $equipmentId = $itemInfo['equipment_id']; // Get equipment_id
      $transactionId = $itemInfo['transaction_id'];

      // Update borrow_transaction_items status
      $stmtUpdateItem = $this->db->prepare("
                UPDATE borrow_transaction_items
                SET status = 'returned', returned_at = NOW()
                WHERE item_id = ?
            ");
      $stmtUpdateItem->execute([$itemId]);

      // Update specific item's availability/status
      if ($bookId !== null) {
        // It's a book
        $stmtUpdateBook = $this->db->prepare("UPDATE books SET availability = 'available' WHERE book_id = ?");
        $stmtUpdateBook->execute([$bookId]);
      } elseif ($equipmentId !== null) {
        // It's an equipment (using its ID/Name)
        $stmtUpdateEquipment = $this->db->prepare("UPDATE equipments SET status = 'available' WHERE equipment_id = ?");
        $stmtUpdateEquipment->execute([$equipmentId]);
      } else {
        // Neither book nor equipment ID found (error state)
        $this->db->rollBack();
        return null;
      }

      // Check if all items in this transaction are returned
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

      // Allow extension for borrowed OR overdue
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

      // Also update items back to 'borrowed' if they were overdue
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
    // Determine borrower type based on which ID field is present
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
      'email' => $row['email'] ?? null, // <-- ADDED EMAIL HERE
    ];
  }
}
