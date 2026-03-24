<?php

namespace App\Controllers;

use App\Repositories\BookManagementRepository;
use App\Core\Controller;

class BookManagementController extends Controller
{
    private $bookRepo;
    private $auditRepo;

    public function __construct()
    {
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
            $limit    = (int)($_GET['limit'] ?? 30);
            $offset   = (int)($_GET['offset'] ?? 0);

            $books = $this->bookRepo->getPaginatedBooks($limit, $offset, $search, $status, $sort);
            $totalCount = $this->bookRepo->countPaginatedBooks($search, $status);

            $this->json(['success' => true, 'books' => $books, 'totalCount' => $totalCount]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getDetails($id)
    {
        try {
            $book = $this->bookRepo->findBookById($id);
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

            if ($book['availability'] === 'borrowed') {
                return $this->json([
                    'success' => false,
                    'message' => 'Cannot delete book. It is currently borrowed.'
                ], 409);
            }

            $result = $this->bookRepo->deleteBook($bookId, $deletedByUserId);

            if ($result['success']) {
                $this->auditRepo->log($deletedByUserId, 'DELETE', 'BOOKS', $book['accession_number'], "Deleted book: {$book['title']}");
            }

            $status = $result['success'] ? 200 : 400;

            if ($result['message'] === 'Book not found.') $status = 404;
            if ($result['message'] === 'Book already deleted.') $status = 409;

            return $this->json($result, $status);
        } catch (\PDOException $e) {
            return $this->json([
                'success' => false,
                'message' => 'Internal server error.'
            ], 500);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
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
    // end

    public function bulkImport()
    {
        header('Content-Type: application/json');
        $imported = 0;
        $errors = [];
        $batchSize = 500;
        $booksToInsert = [];

        $file = $_FILES['csv_file']['tmp_name'];
        $bookRepo = new \App\Repositories\BookManagementRepository();

        if (($handle = fopen($file, 'r')) !== false) {
            fgetcsv($handle); // Skip header
            $rowNumber = 2;

            while (($row = fgetcsv($handle)) !== false) {
                $accessionNumber = trim($row[0] ?? '');
                if (!$accessionNumber) {
                    $errors[] = "Row $rowNumber: Missing accession_number.";
                    $rowNumber++;
                    continue;
                }

                $booksToInsert[] = [
                    'accession_number' => $accessionNumber,
                    'call_number' => trim($row[1] ?? '') ?: null,
                    'title' => trim($row[2] ?? '') ?: null,
                    'author' => trim($row[3] ?? '') ?: null,
                    'book_place' => trim($row[4] ?? '') ?: null,
                    'book_publisher' => trim($row[5] ?? '') ?: null,
                    'year' => trim($row[6] ?? '') ?: null,
                    'book_edition' => trim($row[7] ?? '') ?: null,
                    'description' => trim($row[8] ?? '') ?: null,
                    'book_isbn' => trim($row[9] ?? '') ?: null,
                    'book_supplementary' => trim($row[10] ?? '') ?: null,
                    'subject' => trim($row[11] ?? '') ?: null,
                    'availability' => 'available',
                ];

                if (count($booksToInsert) >= $batchSize) {
                    try {
                        $bookRepo->bulkCreateBooks($booksToInsert);
                        $imported += count($booksToInsert);
                        $booksToInsert = []; // Reset ang collection
                    } catch (\Exception $e) {
                        $errors[] = "Batch error near row $rowNumber: " . $e->getMessage();
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
        }

        echo json_encode(['success' => true, 'imported' => $imported, 'errors' => $errors]);
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
