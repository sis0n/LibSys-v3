<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class BookManagementRepository
{
    private $db;
    private $baseQuery = "SELECT * FROM books WHERE deleted_at IS NULL";
    private $countQuery = "SELECT COUNT(*) FROM books WHERE deleted_at IS NULL";

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getPaginatedBooks(int $limit, int $offset, string $search, string $status, string $sort): array
    {
        $limit = max(1, min($limit, 1000));
        $offset = max(0, $offset);

        $query = $this->baseQuery;
        $params = [];

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

        if ($status !== '' && strtolower($status) !== 'all status') {
            $query .= " AND availability = ?";
            $params[] = strtolower($status);
        }

        $orderBy = "ORDER BY created_at DESC";
        switch ($sort) {
            case 'title_asc':
                $orderBy = "ORDER BY title ASC";
                break;
            case 'title_desc':
                $orderBy = "ORDER BY title DESC";
                break;
            case 'year_asc':
                $orderBy = "ORDER BY year ASC, title ASC";
                break;
            case 'year_desc':
                $orderBy = "ORDER BY year DESC, title ASC";
                break;
        }

        $query .= " " . $orderBy . " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countPaginatedBooks(string $search, string $status): int
    {
        $query = $this->countQuery;
        $params = [];

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
        if ($status !== '' && strtolower($status) !== 'all status') {
            $query .= " AND availability = ?";
            $params[] = strtolower($status);
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function findBookById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM books WHERE book_id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
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

        $allowedFields = ['accession_number', 'call_number', 'title', 'author', 'book_place', 'book_publisher', 'year', 'book_edition', 'description', 'book_isbn', 'book_supplementary', 'subject', 'availability', 'cover'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
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
            // 1️⃣ Check if book exists
            $stmt = $this->db->prepare("SELECT deleted_at FROM books WHERE book_id = :book_id");
            $stmt->execute([':book_id' => $bookId]);
            $book = $stmt->fetch();

            if (!$book) {
                return ['success' => false, 'message' => 'Book not found.'];
            }

            // 2️⃣ Check if already deleted
            if ($book['deleted_at'] !== null) {
                return ['success' => false, 'message' => 'Book already deleted.'];
            }

            // 3️⃣ Soft delete
            $stmt = $this->db->prepare("
            UPDATE books 
            SET deleted_at = CURRENT_TIMESTAMP, 
                deleted_by = :deleted_by,
                is_archived = 0 
            WHERE book_id = :book_id AND deleted_at IS NULL
        ");
            $stmt->execute([
                ':deleted_by' => $deletedByUserId,
                ':book_id' => $bookId
            ]);

            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Book deleted successfully!'];
            }

            return ['success' => false, 'message' => 'Failed to delete book.'];
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
            
            WHERE bti.book_id = :book_id AND bti.status IN ('borrowed', 'returned')
            ORDER BY bt.borrowed_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':book_id' => $bookId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
