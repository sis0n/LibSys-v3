<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class BookManagementRepository
{
    private $db;
    private $baseQuery = "SELECT b.*, c.campus_name FROM books b LEFT JOIN campuses c ON b.campus_id = c.campus_id WHERE 1=1";
    private $countQuery = "SELECT COUNT(*) FROM books WHERE 1=1";

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getPaginatedBooks(int $limit, int $offset, string $search, string $status, string $sort, int $campus_id = null): array
    {
        $limit = max(1, min($limit, 1000));
        $offset = max(0, $offset);

        $query = $this->baseQuery;
        $params = [];

        if (strtolower($status) === 'inactive') {
            $query .= " AND b.is_active = 0";
        } else {
            $query .= " AND b.is_active = 1";
            if ($status !== '' && strtolower($status) !== 'all status') {
                $query .= " AND b.availability = ?";
                $params[] = strtolower($status);
            }
        }

        if ($search !== '') {
            $query .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.book_isbn LIKE ? OR b.accession_number LIKE ? OR b.call_number LIKE ? OR b.subject LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if ($campus_id !== null && $campus_id > 0) {
            $query .= " AND b.campus_id = ?";
            $params[] = $campus_id;
        }

        $orderBy = "ORDER BY b.created_at DESC";
        switch ($sort) {
            case 'title_asc':
                $orderBy = "ORDER BY b.title ASC";
                break;
            case 'title_desc':
                $orderBy = "ORDER BY b.title DESC";
                break;
            case 'year_asc':
                $orderBy = "ORDER BY b.year ASC, b.title ASC";
                break;
            case 'year_desc':
                $orderBy = "ORDER BY b.year DESC, b.title ASC";
                break;
        }

        $query .= " " . $orderBy . " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countPaginatedBooks(string $search, string $status, int $campus_id = null): int
    {
        $query = $this->countQuery;
        $params = [];

        if (strtolower($status) === 'inactive') {
            $query .= " AND is_active = 0";
        } else {
            $query .= " AND is_active = 1";
            if ($status !== '' && strtolower($status) !== 'all status') {
                $query .= " AND availability = ?";
                $params[] = strtolower($status);
            }
        }

        if ($search !== '') {
            $query .= " AND (title LIKE ? OR author LIKE ? OR book_isbn LIKE ? OR accession_number LIKE ? OR call_number LIKE ? OR subject LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        if ($campus_id !== null && $campus_id > 0) {
            $query .= " AND campus_id = ?";
            $params[] = $campus_id;
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function findBookById($id)
    {
        $stmt = $this->db->prepare("SELECT b.*, c.campus_name FROM books b LEFT JOIN campuses c ON b.campus_id = c.campus_id WHERE b.book_id = ? AND b.is_active = 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findBookByIdAll($id)
    {
        $stmt = $this->db->prepare("SELECT b.*, c.campus_name FROM books b LEFT JOIN campuses c ON b.campus_id = c.campus_id WHERE b.book_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function toggleActiveStatus(int $bookId, int $status, int $updatedByUserId): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE books 
                SET is_active = :status,
                    updated_by = :updated_by
                WHERE book_id = :book_id
            ");
            return $stmt->execute([
                ':status' => $status,
                ':updated_by' => $updatedByUserId,
                ':book_id' => $bookId
            ]);
        } catch (\PDOException $e) {
            error_log("Error in toggleActiveStatus: " . $e->getMessage());
            return false;
        }
    }

    public function bulkCreateBooks(array $allBooksData)
    {
        if (empty($allBooksData)) return false;

        $fields = [
            'accession_number',
            'call_number',
            'title',
            'author',
            'book_place',
            'book_publisher',
            'campus_id',
            'year',
            'book_edition',
            'description',
            'book_isbn',
            'book_supplementary',
            'subject',
            'availability'
        ];

        $columns = implode(", ", $fields);

        $rowPlaceholders = "(" . implode(", ", array_fill(0, count($fields), "?")) . ")";
        $allPlaceholders = implode(", ", array_fill(0, count($allBooksData), $rowPlaceholders));

        $sql = "INSERT INTO books ($columns) VALUES $allPlaceholders";
        $stmt = $this->db->prepare($sql);

        $flatParams = [];
        foreach ($allBooksData as $data) {
            foreach ($fields as $field) {
                $flatParams[] = $data[$field] ?? null;
            }
        }

        return $stmt->execute($flatParams);
    }

    public function createBook($data)
    {
        $fields = [
            'accession_number',
            'call_number',
            'title',
            'author',
            'book_place',
            'book_publisher',
            'campus_id',
            'year',
            'book_edition',
            'description',
            'book_isbn',
            'book_supplementary',
            'subject',
            'availability'
        ];

        if (!empty($data['cover'])) {
            $fields[] = 'cover';
        }

        $columns = implode(", ", $fields);
        $placeholders = ":" . implode(", :", $fields);

        $sql = "INSERT INTO books ($columns) VALUES ($placeholders)";
        $stmt = $this->db->prepare($sql);

        $params = [];
        foreach ($fields as $field) {
            $params[":$field"] = $data[$field] ?? null;
        }

        return $stmt->execute($params);
    }

    public function updateBook($id, $data, $updated_by_user_id)
    {
        $sqlParts = [];
        $params = [
            ':book_id' => $id,
            ':updated_by' => $updated_by_user_id
        ];

        $allowedFields = ['accession_number', 'call_number', 'title', 'author', 'book_place', 'book_publisher', 'campus_id', 'year', 'book_edition', 'description', 'book_isbn', 'book_supplementary', 'subject', 'availability', 'cover'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $sqlParts[] = "$field = :$field";
                $params[":$field"] = ($field === 'year' && empty($data[$field])) ? null : $data[$field];
            }
        }

        if (empty($sqlParts)) return true;

        $sqlParts[] = "updated_by = :updated_by";

        $sql = "UPDATE books SET " . implode(", ", $sqlParts) . " WHERE book_id = :book_id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteBook(int $bookId, int $deletedByUserId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT is_active FROM books WHERE book_id = :book_id");
            $stmt->execute([':book_id' => $bookId]);
            $book = $stmt->fetch();

            if (!$book) {
                return ['success' => false, 'message' => 'Book not found.'];
            }

            if ($book['is_active'] == 0) {
                return ['success' => false, 'message' => 'Book is already inactive.'];
            }

            $stmt = $this->db->prepare("
                UPDATE books 
                SET is_active = 0,
                    updated_by = :updated_by,
                    is_archived = 0 
                WHERE book_id = :book_id
            ");
            $stmt->execute([
                ':updated_by' => $deletedByUserId,
                ':book_id' => $bookId
            ]);

            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Book deactivated successfully!'];
            }

            return ['success' => false, 'message' => 'Failed to deactivate book.'];
        } catch (\PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function getBookBorrowingHistory(int $bookId): array
    {
        $sql = "
            SELECT 
                bt.transaction_id, bt.borrowed_at, bt.due_date, bti.returned_at, bti.status,
                COALESCE(u.first_name, g.first_name) AS first_name,
                COALESCE(u.last_name, g.last_name) AS last_name,
                COALESCE(u.role, 'Guest') AS role,
                COALESCE(s.student_number, f.unique_faculty_id, st.employee_id, 'N/A') AS identifier
            FROM borrow_transaction_items bti
            JOIN borrow_transactions bt ON bti.transaction_id = bt.transaction_id
            
            LEFT JOIN students s ON bt.student_id = s.student_id
            LEFT JOIN faculty f ON bt.faculty_id = f.faculty_id
            LEFT JOIN staff st ON bt.staff_id = st.staff_id
            LEFT JOIN guests g ON bt.guest_id = g.guest_id
            
            LEFT JOIN users u ON (s.user_id = u.user_id OR f.user_id = u.user_id OR st.user_id = u.user_id)
            
            WHERE bti.book_id = :book_id AND bti.status IN ('borrowed', 'returned', 'damaged', 'lost', 'overdue')
            ORDER BY bt.borrowed_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':book_id' => $bookId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllAccessionNumbers(): array
    {
        $stmt = $this->db->query("SELECT accession_number, campus_id FROM books");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
