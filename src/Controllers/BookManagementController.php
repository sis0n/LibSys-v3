<?php

namespace App\Controllers;

use App\Repositories\BookManagementRepository;
use App\Core\Controller;
use App\Repositories\CampusRepository; // Added use statement

class BookManagementController extends Controller
{
    private $bookRepo;
    private $auditRepo;

    public function __construct()
    {
    parent::__construct();
        $this->bookRepo = new BookManagementRepository();
        $this->auditRepo = new \App\Repositories\AuditLogRepository();
    }

    private function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Save file locally to public/storage/uploads/
     */
    private function saveFileLocally($file, $subFolder)
    {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $uniqueId = uniqid();
        $fileName = "book_{$uniqueId}.{$extension}";

        $uploadDir = ROOT_PATH . "/public/storage/uploads/{$subFolder}/";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $destPath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            return "storage/uploads/{$subFolder}/" . $fileName;
        }

        return null;
    }

    private function validateImageUpload($file)
    {
        $maxSize = 2 * 1024 * 1024;
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if ($file['error'] !== UPLOAD_ERR_OK) return "Upload error.";
        if ($file['size'] > $maxSize) return "Image must be less than 2MB.";
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, $allowedTypes)) return "Invalid image type. Only JPG, PNG, GIF, WEBP allowed.";
        return true;
    }

    private function handleImageUpload($file)
    {
        $validation = $this->validateImageUpload($file);
        if ($validation !== true) {
            return null;
        }

        return $this->saveFileLocally($file, "book_covers");
    }

    public function fetch()
    {
        try {
            $search   = $_GET['search'] ?? '';
            $status   = $_GET['status'] ?? 'All Status';
            $sort     = $_GET['sort'] ?? 'default';

            $campusFilter = $this->getCampusFilter();
            $campusId = $campusFilter !== null ? $campusFilter : (isset($_GET['campus_id']) ? (int)$_GET['campus_id'] : null);
            
            $limit    = (int)($_GET['limit'] ?? 30);
            $offset   = (int)($_GET['offset'] ?? 0);

            $books = $this->bookRepo->getPaginatedBooks($limit, $offset, $search, $status, $sort, $campusId);
            $totalCount = $this->bookRepo->countPaginatedBooks($search, $status, $campusId);

            $this->json(['success' => true, 'books' => $books, 'totalCount' => $totalCount]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getDetails($id)
    {
        try {
            $book = $this->bookRepo->findBookByIdAll($id);
            if (!$book) {
                return $this->json(['success' => false, 'message' => 'Book not found.'], 404);
            }

            // Transform path para isama ang STORAGE_URL
            if (!empty($book['cover'])) {
                $book['cover'] = STORAGE_URL . '/' . ltrim($book['cover'], '/');
            }

            $this->json(['success' => true, 'book' => $book]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store()
    {
        $data = $_POST;
        if (empty($data['title']) || empty($data['author']) || empty($data['accession_number']) || empty($data['call_number'])) {
            return $this->json(['success' => false, 'message' => 'Required fields are missing.'], 400);
        }

        // Ensure campus_id is handled
        $campusIdFilter = $this->getCampusFilter();
        if ($campusIdFilter !== null) {
            $data['campus_id'] = $campusIdFilter;
        } else {
            $data['campus_id'] = !empty($data['campus_id']) ? (int)$data['campus_id'] : null;
        }

        $data['borrowing_duration_override'] = (!empty($data['borrowing_duration_override']) || $data['borrowing_duration_override'] === '0') ? (int)$data['borrowing_duration_override'] : null;

        if (isset($_FILES['book_image']) && $_FILES['book_image']['error'] == 0) {
            $imagePath = $this->handleImageUpload($_FILES['book_image']);
            if ($imagePath) {
                $data['cover'] = $imagePath;
            } else {
                return $this->json(['success' => false, 'message' => 'Error uploading file.'], 500);
            }
        }
        try {
            $success = $this->bookRepo->createBook($data);
            if ($success) {
                $this->auditRepo->log($_SESSION['user_id'], 'CREATE', 'BOOKS', $data['accession_number'], "Added new book: {$data['title']}");
                $this->json(['success' => true, 'message' => 'Book added successfully!']);
            } else {
                $this->json(['success' => false, 'message' => 'Database error.'], 500);
            }
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                return $this->json(['success' => false, 'message' => 'Accession number or ISBN might already exist.'], 409);
            }
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update($id)
    {
        $data = $_POST;
        if (empty($data['title']) || empty($data['author']) || empty($data['accession_number']) || empty($data['call_number'])) {
            return $this->json(['success' => false, 'message' => 'Required fields are missing.'], 400);
        }

        $currentUserId = $_SESSION['user_id'] ?? null;
        if ($currentUserId === null) {
            return $this->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }

        // Ensure campus_id is handled
        $data['campus_id'] = !empty($data['campus_id']) ? (int)$data['campus_id'] : null;

        $data['borrowing_duration_override'] = (!empty($data['borrowing_duration_override']) || $data['borrowing_duration_override'] === '0') ? (int)$data['borrowing_duration_override'] : null;

        if (isset($_FILES['book_image']) && $_FILES['book_image']['error'] == 0) {
            $imagePath = $this->handleImageUpload($_FILES['book_image']);
            if ($imagePath) {
                $data['cover'] = $imagePath;
            } else {
                return $this->json(['success' => false, 'message' => 'Error uploading new file.'], 500);
            }
        } elseif (isset($data['remove_image']) && $data['remove_image'] == "1") {
            $data['cover'] = null;
        }

        try {
            $book = $this->bookRepo->findBookById($id);
            if (!$book) {
                return $this->json(['success' => false, 'message' => 'Book not found.'], 404);
            }

            $campusIdFilter = $this->getCampusFilter();
            if ($campusIdFilter !== null && $book['campus_id'] != $campusIdFilter) {
                return $this->json(['success' => false, 'message' => 'Unauthorized: Book belongs to another campus.'], 403);
            }

            if (!isset($data['availability'])) {
                $data['availability'] = $book['availability'];
            }

            $success = $this->bookRepo->updateBook($id, $data, $currentUserId);

            if ($success) {
                $this->auditRepo->log($currentUserId, 'UPDATE', 'BOOKS', $data['accession_number'], "Updated book: {$data['title']}");
                $this->json(['success' => true, 'message' => 'Book updated successfully!']);
            } else {
                $this->json(['success' => false, 'message' => 'Failed to update or no changes made.'], 500);
            }
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function reactivate($id)
    {
        $bookId = (int)$id;
        $updatedByUserId = $_SESSION['user_id'] ?? null;

        if ($updatedByUserId === null) {
            return $this->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }

        try {
            $book = $this->bookRepo->findBookByIdAll($bookId); // Need a new repo method or update existing

            if (!$book) {
                return $this->json(['success' => false, 'message' => 'Book not found.'], 404);
            }

            $campusIdFilter = $this->getCampusFilter();
            if ($campusIdFilter !== null && $book['campus_id'] != $campusIdFilter) {
                return $this->json(['success' => false, 'message' => 'Unauthorized: Book belongs to another campus.'], 403);
            }

            $success = $this->bookRepo->toggleActiveStatus($bookId, 1, $updatedByUserId);

            if ($success) {
                $this->auditRepo->log($updatedByUserId, 'REACTIVATE', 'BOOKS', $book['accession_number'], "Reactivated book: {$book['title']}");
                return $this->json(['success' => true, 'message' => 'Book reactivated successfully!']);
            }

            return $this->json(['success' => false, 'message' => 'Failed to reactivate book.'], 500);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $bookId = (int)$id;
        $deletedByUserId = $_SESSION['user_id'] ?? null;

        if ($deletedByUserId === null) {
            return $this->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }

        try {
            $book = $this->bookRepo->findBookById($bookId);
            if (!$book) {
                return $this->json(['success' => false, 'message' => 'Book not found.'], 404);
            }

            $campusIdFilter = $this->getCampusFilter();
            if ($campusIdFilter !== null && $book['campus_id'] != $campusIdFilter) {
                return $this->json(['success' => false, 'message' => 'Unauthorized: Book belongs to another campus.'], 403);
            }

            if ($book['availability'] === 'borrowed') {
                return $this->json(['success' => false, 'message' => "Cannot deactivate '{$book['title']}': It is currently borrowed."], 400);
            }

            $result = $this->bookRepo->deleteBook($bookId, $deletedByUserId);
            if ($result['success']) {
                $this->auditRepo->log($deletedByUserId, 'DEACTIVATE', 'BOOKS', $book['accession_number'], "Deactivated book: {$book['title']}");
                return $this->json(['success' => true, 'message' => 'Book deactivated successfully!']);
            }

            return $this->json(['success' => false, 'message' => $result['message'] ?? 'Failed to deactivate book.'], 500);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Updated
    public function deleteMultiple()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $bookIds = $data['book_ids'] ?? [];

        $deletedByUserId = $_SESSION['user_id'] ?? null;
        if ($deletedByUserId === null) {
            return $this->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }

        if (empty($bookIds) || !is_array($bookIds)) {
            return $this->json(['success' => false, 'message' => 'No book IDs provided.'], 400);
        }

        $deletedCount = 0;
        $errors = [];

        foreach ($bookIds as $bookId) {
            $bookId = (int)$bookId;
            $book = $this->bookRepo->findBookById($bookId);

            if (!$book) {
                $errors[] = "Book with ID $bookId not found.";
                continue;
            }

            if ($book['availability'] === 'borrowed') {
                $errors[] = "Cannot delete '{$book['title']}': It is currently borrowed.";
                continue;
            }

            try {
                $result = $this->bookRepo->deleteBook($bookId, $deletedByUserId);
                if ($result['success']) {
                    $deletedCount++;
                } else {
                    $errors[] = "Failed to delete '{$book['title']}': " . ($result['message'] ?? 'Unknown error');
                }
            } catch (\Exception $e) {
                $errors[] = "Error deleting '{$book['title']}': " . $e->getMessage();
            }
        }

        $response = [
            'success' => $deletedCount > 0,
            'message' => "Successfully deleted $deletedCount book(s).",
            'deleted_count' => $deletedCount,
            'errors' => $errors
        ];

        if ($deletedCount === 0 && !empty($errors)) {
            $response['success'] = false;
            $response['message'] = "No books were deleted. See errors for details.";
        } else if ($deletedCount > 0 && !empty($errors)) {
            $response['message'] = "Partially completed: Deleted $deletedCount book(s) with some errors.";
        }

        return $this->json($response);
    }

    public function bulkImport()
    {
        header('Content-Type: application/json');

        if (!isset($_FILES['csv_file'])) {
            echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
            return;
        }

        $imported     = 0;
        $errors       = [];
        $batchSize    = 500;
        $booksToInsert = [];

        $file      = $_FILES['csv_file']['tmp_name'];
        $bookRepo  = new \App\Repositories\BookManagementRepository();
        $campusRepo = new \App\Repositories\CampusRepository();

        $campusMap = [];
        foreach ($campusRepo->getAllCampuses() as $cp) {
            $campusMap[strtoupper(trim($cp['campus_name']))] = $cp['campus_id'];
        }

        $existingAccessions = [];
        foreach ($bookRepo->getAllAccessionNumbers() as $acc) {
            $existingAccessions[strtoupper(trim($acc['accession_number'])) . '|' . $acc['campus_id']] = true;
        }

        $seenAccessions = [];

        if (($handle = fopen($file, 'r')) === false) {
            echo json_encode(['success' => false, 'message' => 'Failed to open CSV file.']);
            return;
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            echo json_encode(['success' => false, 'message' => 'Could not read CSV header.']);
            return;
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        foreach ($header as $index => $headerName) {
            $headerMap[trim(strtolower($headerName))] = $index;
        }

        // echo json_encode(['debug_headers' => array_keys($headerMap)]);
        // exit;

        $columnMapping = [
            'accession_number' => $headerMap['accession_number'] ?? null,
            'call_number'      => $headerMap['call_number']      ?? null,
            'title'            => $headerMap['title']            ?? null,
            'author'           => $headerMap['author']           ?? null,
            'place'            => $headerMap['book_place']       ?? null,
            'publisher'        => $headerMap['book_publisher']   ?? null,
            'year'             => $headerMap['year']             ?? null,
            'edition'          => $headerMap['book_edition']     ?? null,
            'desc'             => $headerMap['description']      ?? null,
            'isbn'             => $headerMap['book_isbn']        ?? null,
            'supp'             => $headerMap['book_supplementary'] ?? null,
            'subj'             => $headerMap['subject']          ?? null,
            'campus'           => $headerMap['campus']           ?? null,
            'borrowing_duration_override' => $headerMap['borrowing_duration_override'] ?? null,
        ];

        $missingHeaders = [];
        foreach (['accession_number', 'title', 'author', 'campus'] as $required) {
            if ($columnMapping[$required] === null) {
                $missingHeaders[] = $required;
            }
        }

        if (!empty($missingHeaders)) {
            fclose($handle);
            echo json_encode([
                'success' => false,
                'message' => 'Missing required CSV headers: ' . implode(', ', $missingHeaders) . '.'
            ]);
            return;
        }

        $rowNumber = 2;

        while (($row = fgetcsv($handle)) !== false) {
            // 1. Missing accession number — PANATILIHIN
            $accessionNumber = trim($row[$columnMapping['accession_number']] ?? '');
            if ($accessionNumber === '') {
                fclose($handle);
                echo json_encode([
                    'success' => false,
                    'message' => "Import aborted: Row $rowNumber has a missing or empty accession number. Please check your CSV and try again.",
                    'imported' => 0,
                    'errors'   => []
                ]);
                return;
            }

            // 2. Missing campus check
            $campusInput = strtoupper(trim($row[$columnMapping['campus']] ?? ''));
            if ($campusInput === '') {
                fclose($handle);
                echo json_encode([
                    'success' => false,
                    'message' => "Import aborted: Row $rowNumber (Accession No: $accessionNumber) has a missing campus name. Please fix your CSV and try again.",
                    'imported' => 0,
                    'errors'   => []
                ]);
                return;
            }

            // 3. Invalid campus check
            $campusIdFilter = $this->getCampusFilter();
            if ($campusIdFilter !== null) {
                $campusId = $campusIdFilter;
            } else {
                $campusId = $campusMap[$campusInput] ?? null;
            }

            if ($campusId === null) {
                fclose($handle);
                echo json_encode([
                    'success' => false,
                    'message' => "Import aborted: Row $rowNumber (Accession No: $accessionNumber) has an invalid campus '$campusInput'. Please fix your CSV and try again.",
                    'imported' => 0,
                    'errors'   => []
                ]);
                return;
            }

            // 4. Duplicate check sa database — BAGO: may campus_id na
            $accessionKey = strtoupper($accessionNumber) . '|' . $campusId;
            if (isset($existingAccessions[$accessionKey])) {
                fclose($handle);
                echo json_encode([
                    'success' => false,
                    'message' => "Import aborted: Row $rowNumber (Accession No: $accessionNumber) already exists in the same campus. Please fix your CSV and try again.",
                    'imported' => 0,
                    'errors'   => []
                ]);
                return;
            }

            // 5. Duplicate check sa CSV — BAGO: may campus_id na
            if (isset($seenAccessions[$accessionKey])) {
                fclose($handle);
                echo json_encode([
                    'success' => false,
                    'message' => "Import aborted: Row $rowNumber (Accession No: $accessionNumber) is a duplicate within the CSV file for the same campus. Please fix your CSV and try again.",
                    'imported' => 0,
                    'errors'   => []
                ]);
                return;
            }

            $seenAccessions[$accessionKey] = true;

            $campusInput = strtoupper(trim($row[$columnMapping['campus']] ?? ''));
            if ($campusInput === '') {
                fclose($handle);
                echo json_encode([
                    'success' => false,
                    'message' => "Import aborted: Row $rowNumber (Accession No: $accessionNumber) has a missing campus name. Please fix your CSV and try again.",
                    'imported' => 0,
                    'errors'   => []
                ]);
                return;
            }

            $campusId = $campusMap[$campusInput] ?? null;
            if ($campusId === null) {
                fclose($handle);
                echo json_encode([
                    'success' => false,
                    'message' => "Import aborted: Row $rowNumber (Accession No: $accessionNumber) has an invalid campus '$campusInput'. Please fix your CSV and try again.",
                    'imported' => 0,
                    'errors'   => []
                ]);
                return;
            }

            $overrideValue = null;
            $overrideIdx = $columnMapping['borrowing_duration_override'] ?? null;
            if ($overrideIdx !== null && isset($row[$overrideIdx])) {
                $val = trim($row[$overrideIdx]);
                if ($val !== '') {
                    $overrideValue = (int)$val;
                }
            }

            $booksToInsert[] = [
                'accession_number'    => $accessionNumber,
                'call_number'         => trim($row[$columnMapping['call_number']] ?? '') ?: null,
                'title'               => trim($row[$columnMapping['title']]       ?? '') ?: null,
                'author'              => trim($row[$columnMapping['author']]      ?? '') ?: null,
                'book_place'          => trim($row[$columnMapping['place']]       ?? '') ?: null,
                'book_publisher'      => trim($row[$columnMapping['publisher']]   ?? '') ?: null,
                'year'                => trim($row[$columnMapping['year']]        ?? '') ?: null,
                'book_edition'        => trim($row[$columnMapping['edition']]     ?? '') ?: null,
                'description'         => trim($row[$columnMapping['desc']]        ?? '') ?: null,
                'book_isbn'           => trim($row[$columnMapping['isbn']]        ?? '') ?: null,
                'book_supplementary'  => trim($row[$columnMapping['supp']]        ?? '') ?: null,
                'subject'             => trim($row[$columnMapping['subj']]        ?? '') ?: null,
                'campus_id'           => $campusId,
                'availability'        => 'available',
                'borrowing_duration_override' => $overrideValue,
            ];

            if (count($booksToInsert) >= $batchSize) {
                try {
                    $bookRepo->bulkCreateBooks($booksToInsert);
                    $imported += count($booksToInsert);
                    $booksToInsert = [];
                } catch (\Exception $e) {
                    $errors[] = "Batch error near row $rowNumber: " . $e->getMessage();
                    $booksToInsert = [];
                }
            }

            $rowNumber++;
        }

        if (!empty($booksToInsert)) {
            try {
                $bookRepo->bulkCreateBooks($booksToInsert);
                $imported += count($booksToInsert);
            } catch (\Exception $e) {
                $errors[] = "Final batch error: " . $e->getMessage();
            }
        }

        fclose($handle);

        if ($imported === 0 && !empty($errors)) {
            echo json_encode(['success' => false, 'message' => 'Import failed.', 'errors' => $errors]);
        } else {
            $message = "Successfully imported $imported book(s).";
            if (!empty($errors)) {
                $message = "Partially imported: " . $message . " Errors encountered.";
            }
            echo json_encode(['success' => true, 'message' => $message, 'imported' => $imported, 'errors' => $errors]);
        }
    }

    public function getBorrowingHistory($id)
    {
        try {
            $history = $this->bookRepo->getBookBorrowingHistory((int)$id);
            $this->json(['success' => true, 'history' => $history]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
