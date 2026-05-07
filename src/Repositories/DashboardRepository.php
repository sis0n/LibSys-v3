<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use Exception;

class DashboardRepository
{
  private PDO $db;

  public function __construct()
  {
    $this->db = Database::getInstance()->getConnection();
  }

  public function updateOverdueStatuses(): void
  {
    $this->db->query("UPDATE borrow_transactions SET status = 'overdue' WHERE status = 'borrowed' AND due_date < NOW()");
    $this->db->query("UPDATE borrow_transaction_items bti 
                      INNER JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
                      SET bti.status = 'overdue' 
                      WHERE bt.status = 'overdue' AND bti.status = 'borrowed'");
  }

  public function countUsers(?int $campusId = null): int
  {
    $where = $campusId !== null ? " AND campus_id = " . (int)$campusId : "";
    $stmt = $this->db->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL $where");
    return (int)$stmt->fetchColumn();
  }

  public function countStudents(?int $campusId = null): int
  {
    $where = $campusId !== null ? " AND u.campus_id = " . (int)$campusId : "";
    $stmt = $this->db->query("SELECT COUNT(*) FROM students s JOIN users u ON s.user_id = u.user_id WHERE s.status='enrolled' AND u.deleted_at IS NULL $where");
    return (int)$stmt->fetchColumn();
  }

  public function countFaculty(?int $campusId = null): int
  {
    $where = $campusId !== null ? " AND u.campus_id = " . (int)$campusId : "";
    $stmt = $this->db->query("SELECT COUNT(*) FROM faculty f JOIN users u ON f.user_id = u.user_id WHERE u.deleted_at IS NULL $where");
    return (int)$stmt->fetchColumn();
  }

  public function countStaff(?int $campusId = null): int
  {
    $where = $campusId !== null ? " AND u.campus_id = " . (int)$campusId : "";
    $stmt = $this->db->query("SELECT COUNT(*) FROM staff s JOIN users u ON s.user_id = u.user_id WHERE u.deleted_at IS NULL $where");
    return (int)$stmt->fetchColumn();
  }

  public function countUsersAddedThisMonth(?int $campusId = null): int
  {
    $where = $campusId !== null ? " AND campus_id = " . (int)$campusId : "";
    $stmt = $this->db->query("SELECT COUNT(*) FROM users WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE()) AND deleted_at IS NULL $where");
    return (int)$stmt->fetchColumn();
  }

  public function countTodayAttendance(?int $campusId = null): int
  {
    $where = $campusId !== null ? " AND u.campus_id = " . (int)$campusId : "";
    $stmt = $this->db->query("SELECT COUNT(DISTINCT a.user_id) FROM attendance a JOIN users u ON a.user_id = u.user_id WHERE DATE(a.first_scan_at) = CURDATE() $where");
    return (int)$stmt->fetchColumn();
  }

  public function countBooks(?int $campusId = null): int
  {
    $where = $campusId !== null ? " AND campus_id = " . (int)$campusId : "";
    $stmt = $this->db->query("SELECT COUNT(*) FROM books WHERE is_active = 1 $where");
    return (int)$stmt->fetchColumn();
  }

  public function countAvailableBooks(?int $campusId = null): int
  {
    $where = $campusId !== null ? " AND campus_id = " . (int)$campusId : "";
    $stmt = $this->db->query("SELECT COUNT(*) FROM books WHERE is_active = 1 AND availability = 'available' $where");
    return (int)$stmt->fetchColumn();
  }

  public function countBorrowedItems(?int $campusId = null): int
  {
    $where = $campusId !== null ? " AND u.campus_id = " . (int)$campusId : "";
    $stmt = $this->db->query("
        SELECT COUNT(*) 
        FROM borrow_transaction_items bti
        JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
        LEFT JOIN students s ON bt.student_id = s.student_id
        LEFT JOIN faculty f ON bt.faculty_id = f.faculty_id
        LEFT JOIN staff st ON bt.staff_id = st.staff_id
        LEFT JOIN users u ON u.user_id = COALESCE(s.user_id, f.user_id, st.user_id)
        WHERE bti.status = 'borrowed' $where
    ");
    return (int)$stmt->fetchColumn();
  }

  public function countOverdue(?int $campusId = null): int
  {
    $where = $campusId !== null ? " AND u.campus_id = " . (int)$campusId : "";
    $stmt = $this->db->query("
        SELECT COUNT(*) 
        FROM borrow_transaction_items bti
        JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
        LEFT JOIN students s ON bt.student_id = s.student_id
        LEFT JOIN faculty f ON bt.faculty_id = f.faculty_id
        LEFT JOIN staff st ON bt.staff_id = st.staff_id
        LEFT JOIN users u ON u.user_id = COALESCE(s.user_id, f.user_id, st.user_id)
        WHERE (bti.status = 'overdue' OR bt.status = 'overdue') $where
    ");
    return (int)$stmt->fetchColumn();
  }

  public function getRecentBorrowings(int $limit = 5, ?int $campusId = null): array
  {
    $where = $campusId !== null ? " AND u.campus_id = " . (int)$campusId : "";
    $sql = "
        SELECT bt.transaction_id, bt.transaction_code, bt.borrowed_at, u.first_name, u.last_name, b.title
        FROM borrow_transactions bt
        JOIN borrow_transaction_items bti ON bt.transaction_id = bti.transaction_id
        LEFT JOIN books b ON bti.book_id = b.book_id
        LEFT JOIN students s ON bt.student_id = s.student_id
        LEFT JOIN faculty f ON bt.faculty_id = f.faculty_id
        LEFT JOIN staff st ON bt.staff_id = st.staff_id
        LEFT JOIN users u ON u.user_id = COALESCE(s.user_id, f.user_id, st.user_id)
        WHERE 1=1 $where
        ORDER BY bt.borrowed_at DESC
        LIMIT :limit
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getTrends(string $period, ?int $campusId = null): array
  {
      $data = [];
      if ($period === 'monthly') {
          for ($i = 5; $i >= 0; $i--) {
              $month = date('M', strtotime("-$i months"));
              $date = date('Y-m', strtotime("-$i months"));
              $stmt = $this->db->prepare("SELECT COUNT(*) FROM borrow_transactions WHERE DATE_FORMAT(borrowed_at, '%Y-%m') = :date");
              $stmt->execute(['date' => $date]);
              $data[] = ['label' => $month, 'value' => (int)$stmt->fetchColumn()];
          }
      }
      return $data;
  }

  public function getDashboardStats(?int $campusId = null): array
  {
    try {
      $this->updateOverdueStatuses();

      $userCampusWhere = $campusId !== null ? " AND u.campus_id = " . (int)$campusId : "";
      $bookCampusWhere = $campusId !== null ? " AND b.campus_id = " . (int)$campusId : "";
      
      $campusJoin = " LEFT JOIN campuses c ON u.campus_id = c.campus_id ";
      $bookCampusJoin = " LEFT JOIN campuses c ON b.campus_id = c.campus_id ";

      $stmt = $this->db->query("SELECT COUNT(*) AS total_students FROM students s JOIN users u ON s.user_id = u.user_id $campusJoin WHERE s.status='enrolled' AND u.deleted_at IS NULL $userCampusWhere");
      $totalStudents = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total_students'] ?? 0);

      $stmt = $this->db->query("SELECT COUNT(*) AS total_staff FROM staff s JOIN users u ON s.user_id = u.user_id $campusJoin WHERE u.deleted_at IS NULL $userCampusWhere");
      $totalStaff = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total_staff'] ?? 0);

      $stmt = $this->db->query("SELECT COUNT(*) AS total_faculty FROM faculty f JOIN users u ON f.user_id = u.user_id $campusJoin WHERE u.deleted_at IS NULL $userCampusWhere");
      $totalFaculty = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total_faculty'] ?? 0);

      $totalUsers = $totalStudents + $totalFaculty + $totalStaff;

      $stmt = $this->db->query("
                SELECT COUNT(*) AS added_this_month
                FROM users u
                $campusJoin
                WHERE MONTH(u.created_at)=MONTH(CURDATE()) AND YEAR(u.created_at)=YEAR(CURDATE()) $userCampusWhere
            ");
      $usersAddedThisMonth = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['added_this_month'] ?? 0);

      $stmt = $this->db->prepare("
          SELECT COUNT(DISTINCT a.user_id) AS attendance_today 
          FROM attendance a
          JOIN users u ON a.user_id = u.user_id
          $campusJoin
          WHERE DATE(a.first_scan_at) = CURDATE() $userCampusWhere
      ");
      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      $attendanceToday = (int) ($row['attendance_today'] ?? 0);

      $stmt = $this->db->query("SELECT COUNT(*) AS total_books FROM books b $bookCampusJoin WHERE b.is_active = 1 $bookCampusWhere");
      $totalBooks = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total_books'] ?? 0);

      $stmt = $this->db->query("
          SELECT COUNT(*) AS borrowed_books 
          FROM borrow_transaction_items bti
          JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
          LEFT JOIN students s ON bt.student_id = s.student_id
          LEFT JOIN faculty f ON bt.faculty_id = f.faculty_id
          LEFT JOIN staff st ON bt.staff_id = st.staff_id
          JOIN users u ON u.user_id = COALESCE(s.user_id, f.user_id, st.user_id)
          $campusJoin
          WHERE bti.status = 'borrowed' $userCampusWhere
      ");
      $borrowedBooks = (int) $stmt->fetchColumn();

      $stmt = $this->db->query("
          SELECT COUNT(*) AS overdue_books 
          FROM borrow_transaction_items bti
          JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
          LEFT JOIN students s ON bt.student_id = s.student_id
          LEFT JOIN faculty f ON bt.faculty_id = f.faculty_id
          LEFT JOIN staff st ON bt.staff_id = st.staff_id
          JOIN users u ON u.user_id = COALESCE(s.user_id, f.user_id, st.user_id)
          $campusJoin
          WHERE (bti.status='overdue' OR bt.status='overdue') $userCampusWhere
      ");
      $overdueBooks = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['overdue_books'] ?? 0);

      $availableBooks = $totalBooks - $borrowed_books - $overdueBooks;
      $availableBooksPercent = $totalBooks > 0 ? round($availableBooks / $totalBooks * 100, 1) : 0;
      $borrowedBooksPercent = $totalBooks > 0 ? round(($borrowed_books + $overdueBooks) / $totalBooks * 100, 1) : 0;

      return [
        'success' => true,
        'data' => [
          'students' => $totalStudents,
          'faculty' => $totalFaculty,
          'staff' => $totalStaff,
          'attendance_today' => $attendanceToday,
          'books' => $totalBooks,
          'borrowed_books' => $borrowedBooks + $overdueBooks,
          'totalUsers' => $totalUsers,
          'usersAddedThisMonth' => $usersAddedThisMonth,
          'availableBooks' => $availableBooks,
          'availableBooksPercent' => $availableBooksPercent,
          'borrowedBooksPercent' => $borrowedBooksPercent,
          'overdueBooks' => $overdueBooks
        ]
      ];
    } catch (Exception $e) {
      return [
        'success' => false,
        'message' => 'Failed to load dashboard data: ' . $e->getMessage(),
      ];
    }
  }

  public function getTopVisitors(int $limit = 5, ?int $campusId = null): array
  {
    $where = "WHERE DATE(a.first_scan_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    if ($campusId !== null) {
        $where .= " AND u.campus_id = :campus_id";
    }

    $sql = "
        SELECT u.first_name, u.last_name, s.student_number, s.year_level, s.section, COUNT(a.user_id) AS visits
        FROM attendance a
        JOIN users u ON u.user_id = a.user_id
        LEFT JOIN campuses c ON u.campus_id = c.campus_id
        LEFT JOIN students s ON u.user_id = s.user_id
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
        'student_number' => $r['student_number'] ?? 'N/A',
        'year_level' => $r['year_level'] ?? 'N/A',
        'section' => $r['section'] ?? 'N/A',
        'visits' => (int)$r['visits']
      ];
    }, $rows);
  }

  public function getWeeklyActivity(?int $campusId = null): array
  {
    $data = [];
    for ($i = 6; $i >= 0; $i--) {
      $date = date('Y-m-d', strtotime("-$i days"));
      $day = date('D', strtotime($date));

      $stmt = $this->db->prepare("
          SELECT COUNT(DISTINCT a.user_id) 
          FROM attendance a 
          JOIN users u ON a.user_id = u.user_id
          WHERE DATE(a.first_scan_at) = :date " . ($campusId !== null ? " AND u.campus_id = :campus_id" : "")
      );
      $params = ['date' => $date];
      if ($campusId !== null) $params['campus_id'] = $campusId;
      $stmt->execute($params);
      $visitors = (int) $stmt->fetchColumn();

      $stmt = $this->db->prepare("
          SELECT COUNT(*) 
          FROM borrow_transactions bt
          LEFT JOIN students s ON bt.student_id = s.student_id
          LEFT JOIN faculty f ON bt.faculty_id = f.faculty_id
          LEFT JOIN staff st ON bt.staff_id = st.staff_id
          JOIN users u ON u.user_id = COALESCE(s.user_id, f.user_id, st.user_id)
          WHERE DATE(bt.borrowed_at) = :date " . ($campusId !== null ? " AND u.campus_id = :campus_id" : "")
      );
      $stmt->execute($params);
      $borrows = (int) $stmt->fetchColumn();

      $data[] = ['day' => $day, 'visitors' => $visitors, 'borrows' => $borrows];
    }

    return $data;
  }

  public function getPopularBooks(int $limit = 5, ?int $campusId = null): array
  {
    $where = $campusId !== null ? " WHERE b.campus_id = :campus_id" : "";
    $sql = "
        SELECT b.title, b.author, b.accession_number, COUNT(bti.item_id) AS borrow_count
        FROM borrow_transaction_items bti
        JOIN books b ON bti.book_id = b.book_id
        $where
        GROUP BY b.book_id, b.title, b.author, b.accession_number
        ORDER BY borrow_count DESC
        LIMIT :limit
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    if ($campusId !== null) $stmt->bindValue(':campus_id', $campusId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getRecentActivities(int $limit = 5, ?int $campusId = null): array
  {
    $where = $campusId !== null ? " WHERE u.campus_id = :campus_id" : "";
    $sql = "
        SELECT al.action, al.details, al.created_at, u.username, CONCAT(u.first_name, ' ', u.last_name) as full_name
        FROM audit_logs al
        LEFT JOIN users u ON al.user_id = u.user_id
        $where
        ORDER BY al.created_at DESC
        LIMIT :limit
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    if ($campusId !== null) $stmt->bindValue(':campus_id', $campusId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getOverdueBooks(int $limit = 5, ?int $campusId = null): array
  {
    $campusWhere = $campusId !== null ? " AND u.campus_id = :campus_id" : "";
    $sql = "
        SELECT 
            CONCAT(u.first_name, ' ', u.last_name) AS borrower_name,
            COALESCE(b.title, e.equipment_name) AS title,
            COALESCE(b.accession_number, e.asset_tag) AS accession_number,
            bt.due_date,
            DATEDIFF(CURDATE(), bt.due_date) AS days_overdue
        FROM borrow_transaction_items bti
        JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
        LEFT JOIN books b ON bti.book_id = b.book_id
        LEFT JOIN equipments e ON bti.equipment_id = e.equipment_id
        LEFT JOIN students s ON bt.student_id = s.student_id
        LEFT JOIN faculty f ON bt.faculty_id = f.faculty_id
        LEFT JOIN staff st ON bt.staff_id = st.staff_id
        JOIN users u ON u.user_id = COALESCE(s.user_id, f.user_id, st.user_id)
        WHERE (bti.status = 'overdue' OR bt.status = 'overdue' OR (bti.status = 'borrowed' AND bt.due_date < NOW()))
        $campusWhere
        ORDER BY days_overdue DESC
        LIMIT :limit
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    if ($campusId !== null) $stmt->bindValue(':campus_id', $campusId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getVisitorBreakdown(string $filter = 'month', ?int $campusId = null): array
  {
    $whereClause = "WHERE 1=1";
    if ($filter === 'day') {
        $whereClause .= " AND DATE(a.first_scan_at) = CURDATE()";
    } elseif ($filter === 'month') {
        $whereClause .= " AND MONTH(a.first_scan_at) = MONTH(CURDATE()) AND YEAR(a.first_scan_at) = YEAR(CURDATE())";
    } else { 
        $whereClause .= " AND YEAR(a.first_scan_at) = YEAR(CURDATE())";
    }

    if ($campusId !== null) {
        $whereClause .= " AND u.campus_id = " . (int)$campusId;
    }

    $sqlRole = "
        SELECT u.role, COUNT(a.id) AS count
        FROM attendance a
        JOIN users u ON a.user_id = u.user_id
        $whereClause
        GROUP BY u.role
    ";
    $stmtRole = $this->db->query($sqlRole);
    $roles = $stmtRole->fetchAll(PDO::FETCH_ASSOC);

    $sqlDept = "
        SELECT cl.college_name AS department, COUNT(a.id) AS count
        FROM attendance a
        JOIN users u ON a.user_id = u.user_id
        JOIN students s ON u.user_id = s.user_id
        JOIN courses c_course ON s.course_id = c_course.course_id
        JOIN colleges cl ON c_course.college_id = cl.college_id
        $whereClause
        GROUP BY cl.college_name
        ORDER BY count DESC
        LIMIT 5
    ";
    $stmtDept = $this->db->query($sqlDept);
    $depts = $stmtDept->fetchAll(PDO::FETCH_ASSOC);

    return [
      'byRole' => $roles,
      'byDepartment' => $depts
    ];
  }
}
