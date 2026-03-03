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

  public function getDashboardStats(): array
  {
    try {
      $this->db->query("UPDATE borrow_transactions SET status = 'overdue' WHERE status = 'borrowed' AND due_date < NOW()");
      $this->db->query("UPDATE borrow_transaction_items bti 
                        INNER JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
                        SET bti.status = 'overdue' 
                        WHERE bt.status = 'overdue' AND bti.status = 'borrowed'");

      $stmt = $this->db->query("SELECT COUNT(*) AS total_students FROM students WHERE status='enrolled' AND deleted_at IS NULL");
      $totalStudents = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total_students'] ?? 0);

      $stmt = $this->db->query("SELECT COUNT(*) AS total_staff FROM staff WHERE status='active' AND deleted_at IS NULL");
      $totalStaff = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total_staff'] ?? 0);

      $stmt = $this->db->query("SELECT COUNT(*) AS total_faculty FROM faculty WHERE status='active' AND deleted_at IS NULL");
      $totalFaculty = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total_faculty'] ?? 0);

      $totalUsers = $totalStudents + $totalFaculty + $totalStaff;

      $stmt = $this->db->query("
                SELECT COUNT(*) AS added_this_month
                FROM users
                WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())
            ");
      $usersAddedThisMonth = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['added_this_month'] ?? 0);

      $stmt = $this->db->prepare("
          SELECT COUNT(DISTINCT user_id) AS attendance_today 
          FROM attendance 
          WHERE DATE(first_scan_at) = CURDATE()
      ");
      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      $attendanceToday = (int) ($row['attendance_today'] ?? 0);

      $stmt = $this->db->query("SELECT COUNT(*) AS total_books FROM books WHERE deleted_at IS NULL");
      $totalBooks = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total_books'] ?? 0);

      $stmt = $this->db->query("
          SELECT COUNT(*) AS borrowed_books 
          FROM borrow_transaction_items 
          WHERE status = 'borrowed'
      ");
      $borrowedBooks = (int) $stmt->fetchColumn();

      $stmt = $this->db->query("SELECT COUNT(*) AS overdue_books FROM borrow_transaction_items WHERE status='overdue'");
      $overdueBooks = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['overdue_books'] ?? 0);

      $availableBooks = $totalBooks - $borrowedBooks - $overdueBooks;
      $availableBooksPercent = $totalBooks > 0 ? round($availableBooks / $totalBooks * 100, 1) : 0;
      $borrowedBooksPercent = $totalBooks > 0 ? round(($borrowedBooks + $overdueBooks) / $totalBooks * 100, 1) : 0;

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

  public function getWeeklyActivity(): array
  {
    $data = [];
    for ($i = 6; $i >= 0; $i--) {
      $date = date('Y-m-d', strtotime("-$i days"));
      $day = date('D', strtotime($date));

      $stmt = $this->db->prepare("SELECT COUNT(DISTINCT user_id) FROM attendance WHERE DATE(first_scan_at) = :date");
      $stmt->execute(['date' => $date]);
      $visitors = (int) $stmt->fetchColumn();

      $stmt = $this->db->prepare("SELECT COUNT(*) FROM borrow_transactions WHERE DATE(borrowed_at) = :date");
      $stmt->execute(['date' => $date]);
      $borrows = (int) $stmt->fetchColumn();

      $data[] = ['day' => $day, 'visitors' => $visitors, 'borrows' => $borrows];
    }

    return $data;
  }

  public function getPopularBooks(int $limit = 5): array
  {
    $sql = "
        SELECT b.title, COUNT(bti.item_id) AS borrow_count
        FROM borrow_transaction_items bti
        JOIN books b ON bti.book_id = b.book_id
        GROUP BY b.book_id, b.title
        ORDER BY borrow_count DESC
        LIMIT :limit
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getVisitorBreakdown(): array
  {
    $sqlRole = "
        SELECT u.role, COUNT(a.id) AS count
        FROM attendance a
        JOIN users u ON a.user_id = u.user_id
        WHERE DATE(a.first_scan_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY u.role
    ";
    $stmtRole = $this->db->query($sqlRole);
    $roles = $stmtRole->fetchAll(PDO::FETCH_ASSOC);

    $sqlDept = "
        SELECT cl.college_name AS department, COUNT(a.id) AS count
        FROM attendance a
        JOIN students s ON a.user_id = s.user_id
        JOIN courses c ON s.course_id = c.course_id
        JOIN colleges cl ON c.college_id = cl.college_id
        WHERE DATE(a.first_scan_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
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
