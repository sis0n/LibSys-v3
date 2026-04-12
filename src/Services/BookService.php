<?php

namespace App\Services;

use App\Repositories\BookManagementRepository;
use App\Repositories\AuditLogRepository;
use Exception;

class BookService
{
    private BookManagementRepository $bookRepo;
    private AuditLogRepository $auditRepo;

    public function __construct()
    {
        $this->bookRepo = new BookManagementRepository();
        $this->auditRepo = new AuditLogRepository();
    }

    /**
     * Get paginated books with filters
     */
    public function getPaginatedBooks(array $params, ?int $campusIdRestriction): array
    {
        $limit = (int)($params['limit'] ?? 30);
        $offset = (int)($params['offset'] ?? 0);
        $search = $params['search'] ?? '';
        $status = $params['status'] ?? 'All Status';
        $sort = $params['sort'] ?? 'default';

        // Use restricted campusId if set, otherwise use campus_id from request params
        $finalCampusId = $campusIdRestriction ?? (!empty($params['campus_id']) ? (int)$params['campus_id'] : null);

        $books = $this->bookRepo->getPaginatedBooks($limit, $offset, $search, $status, $sort, $finalCampusId);
        $totalCount = $this->bookRepo->countPaginatedBooks($search, $status, $finalCampusId);

        return ['books' => $books, 'totalCount' => $totalCount];
    }

    /**
     * Get book details by ID
     */
    public function getBookDetails(int $id): array
    {
        $book = $this->bookRepo->findBookByIdAll($id);
        if (!$book) {
            throw new Exception('Book not found.');
        }

        if (!empty($book['cover'])) {
            $book['cover'] = \STORAGE_URL . '/' . ltrim($book['cover'], '/');
        }

        return $book;
    }

    /**
     * Create a new book
     */
    public function createBook(array $data, int $adminId, ?int $campusIdFilter): bool
    {
        if (empty($data['title']) || empty($data['author']) || empty($data['accession_number']) || empty($data['call_number'])) {
            throw new Exception('Required fields are missing.');
        }

        $data['campus_id'] = $campusIdFilter ?? (!empty($data['campus_id']) ? (int)$data['campus_id'] : null);
        $data['borrowing_duration_override'] = (isset($data['borrowing_duration_override']) && $data['borrowing_duration_override'] !== '') ? (int)$data['borrowing_duration_override'] : null;

        if ($this->bookRepo->createBook($data)) {
            $this->auditRepo->log($adminId, 'CREATE', 'BOOKS', $data['accession_number'], "Added new book: {$data['title']}");
            return true;
        }

        throw new Exception('Failed to create book.');
    }

    /**
     * Update an existing book
     */
    public function updateBook(int $id, array $data, int $adminId, ?int $campusIdFilter): bool
    {
        if (empty($data['title']) || empty($data['author']) || empty($data['accession_number']) || empty($data['call_number'])) {
            throw new Exception('Required fields are missing.');
        }

        $book = $this->bookRepo->findBookById($id);
        if (!$book) throw new Exception('Book not found.');

        if ($campusIdFilter !== null && $book['campus_id'] != $campusIdFilter) {
            throw new Exception('Unauthorized: Book belongs to another campus.');
        }

        $data['campus_id'] = !empty($data['campus_id']) ? (int)$data['campus_id'] : $book['campus_id'];
        $data['borrowing_duration_override'] = (isset($data['borrowing_duration_override']) && $data['borrowing_duration_override'] !== '') ? (int)$data['borrowing_duration_override'] : null;

        if (!isset($data['availability'])) {
            $data['availability'] = $book['availability'];
        }

        if ($this->bookRepo->updateBook($id, $data, $adminId)) {
            $this->auditRepo->log($adminId, 'UPDATE', 'BOOKS', $data['accession_number'], "Updated book: {$data['title']}");
            return true;
        }

        return false; // No changes made
    }

    /**
     * Deactivate a book
     */
    public function deactivateBook(int $id, int $adminId, ?int $campusIdFilter): void
    {
        $book = $this->bookRepo->findBookById($id);
        if (!$book) throw new Exception('Book not found.');

        if ($campusIdFilter !== null && $book['campus_id'] != $campusIdFilter) {
            throw new Exception('Unauthorized: Book belongs to another campus.');
        }

        if ($book['availability'] === 'borrowed') {
            throw new Exception("Cannot deactivate '{$book['title']}': It is currently borrowed.");
        }

        $result = $this->bookRepo->deleteBook($id, $adminId);
        if ($result['success']) {
            $this->auditRepo->log($adminId, 'DEACTIVATE', 'BOOKS', $book['accession_number'], "Deactivated book: {$book['title']}");
        } else {
            throw new Exception($result['message'] ?? 'Failed to deactivate book.');
        }
    }

    /**
     * Reactivate a book
     */
    public function reactivateBook(int $id, int $adminId, ?int $campusIdFilter): void
    {
        $book = $this->bookRepo->findBookByIdAll($id);
        if (!$book) throw new Exception('Book not found.');

        if ($campusIdFilter !== null && $book['campus_id'] != $campusIdFilter) {
            throw new Exception('Unauthorized: Book belongs to another campus.');
        }

        if ($this->bookRepo->toggleActiveStatus($id, 1, $adminId)) {
            $this->auditRepo->log($adminId, 'REACTIVATE', 'BOOKS', $book['accession_number'], "Reactivated book: {$book['title']}");
        } else {
            throw new Exception('Failed to reactivate book.');
        }
    }

    /**
     * Delete multiple books
     */
    public function deleteMultiple(array $bookIds, int $adminId): array
    {
        $deletedCount = 0;
        $errors = [];

        foreach ($bookIds as $bookId) {
            try {
                $this->deactivateBook((int)$bookId, $adminId, null);
                $deletedCount++;
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        return [
            'deleted_count' => $deletedCount,
            'errors' => $errors
        ];
    }

    /**
     * Bulk import books from CSV
     */
    public function bulkImport(string $filePath, int $adminId, ?int $campusIdFilter): array
    {
        $campusRepo = new \App\Repositories\CampusRepository();
        $campusMap = [];
        foreach ($campusRepo->getAllCampuses() as $cp) {
            $campusMap[strtoupper(trim($cp['campus_name']))] = $cp['campus_id'];
        }

        $existingAccessions = [];
        foreach ($this->bookRepo->getAllAccessionNumbers() as $acc) {
            $existingAccessions[strtoupper(trim($acc['accession_number'])) . '|' . $acc['campus_id']] = true;
        }

        if (($handle = fopen($filePath, 'r')) === false) {
            throw new Exception('Failed to open CSV file.');
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            throw new Exception('Could not read CSV header.');
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        $headerMap = [];
        foreach ($header as $index => $headerName) {
            $headerMap[trim(strtolower($headerName))] = $index;
        }

        $columnMapping = [
            'accession_number'  => $headerMap['accession_number']  ?? null,
            'call_number'       => $headerMap['call_number']       ?? null,
            'title'             => $headerMap['title']             ?? null,
            'author'            => $headerMap['author']            ?? null,
            'book_place'        => $headerMap['place']             ?? null,
            'book_publisher'    => $headerMap['publisher']         ?? null,
            'year'              => $headerMap['year']              ?? null,
            'book_edition'      => $headerMap['edition']           ?? null,
            'description'       => $headerMap['desc']              ?? null,
            'book_isbn'         => $headerMap['isbn']              ?? null,
            'book_supplementary'=> $headerMap['supp']              ?? null,
            'subject'           => $headerMap['subj']              ?? null,
            'campus'            => $headerMap['campus']            ?? null,
        ];

        // Basic validation of mapping
        foreach (['accession_number', 'title', 'campus'] as $required) {
            if ($columnMapping[$required] === null) throw new Exception("Missing required CSV header: $required");
        }

        $imported = 0;
        $booksToInsert = [];
        $batchSize = 250;
        $rowNumber = 2;

        while (($row = fgetcsv($handle)) !== false) {
            // Helper to get value safely
            $getV = function($key) use ($row, $columnMapping) {
                $idx = $columnMapping[$key] ?? null;
                return ($idx !== null && isset($row[$idx])) ? trim($row[$idx]) : null;
            };

            $accessionNumber = trim($getV('accession_number') ?? '');
            $campusInput = strtoupper(trim($getV('campus') ?? ''));
            
            if ($accessionNumber === '') continue;
            
            $campusId = $campusIdFilter ?? ($campusMap[$campusInput] ?? null);
            if ($campusId === null) continue;

            $booksToInsert[] = [
                'accession_number'  => $accessionNumber,
                'call_number'       => $getV('call_number'),
                'title'             => $getV('title') ?? 'Untitled',
                'author'            => $getV('author') ?? 'Unknown',
                'book_place'        => $getV('book_place'),
                'book_publisher'    => $getV('book_publisher'),
                'campus_id'         => $campusId,
                'year'              => $getV('year'),
                'book_edition'      => $getV('book_edition'),
                'description'       => $getV('description'),
                'book_isbn'         => $getV('book_isbn'),
                'book_supplementary'=> $getV('book_supplementary'),
                'subject'           => $getV('subject'),
                'availability'      => 'available',
                'borrowing_duration_override' => 0
            ];

            if (count($booksToInsert) >= $batchSize) {
                $this->bookRepo->bulkCreateBooks($booksToInsert);
                $imported += count($booksToInsert);
                $booksToInsert = [];
            }
            $rowNumber++;
        }

        if (!empty($booksToInsert)) {
            $this->bookRepo->bulkCreateBooks($booksToInsert);
            $imported += count($booksToInsert);
        }

        fclose($handle);
        $this->auditRepo->log($adminId, 'BULK_IMPORT', 'BOOKS', null, "Bulk imported $imported books.");
        
        return ['imported' => $imported];
    }

    /**
     * Get borrowing history for a specific book
     */
    public function getBookBorrowingHistory(int $id): array
    {
        return $this->bookRepo->getBookBorrowingHistory($id);
    }
}
