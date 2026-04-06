<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\BookService;
use App\Services\StorageService;
use Exception;

class BookManagementController extends Controller
{
    private BookService $bookService;
    private StorageService $storageService;

    public function __construct()
    {
        parent::__construct();
        $this->bookService = new BookService();
        $this->storageService = new StorageService();
    }

    public function fetch()
    {
        header('Content-Type: application/json');
        try {
            $campusFilter = $this->getCampusFilter();
            $result = $this->bookService->getPaginatedBooks($_GET, $campusFilter);
            echo json_encode(['success' => true, 'books' => $result['books'], 'totalCount' => $result['totalCount']]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getDetails($id)
    {
        header('Content-Type: application/json');
        try {
            $book = $this->bookService->getBookDetails((int)$id);
            echo json_encode(['success' => true, 'book' => $book]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    public function store()
    {
        header('Content-Type: application/json');
        try {
            $data = $_POST;
            $adminId = $_SESSION['user_id'] ?? null;
            if (!$adminId) throw new Exception('Authentication required.');

            $campusIdFilter = $this->getCampusFilter();

            if (isset($_FILES['book_image']) && $_FILES['book_image']['error'] == 0) {
                $this->storageService->validateImage($_FILES['book_image']);
                $data['cover'] = $this->storageService->saveFile($_FILES['book_image'], "book_covers", "book");
            }

            $this->bookService->createBook($data, $adminId, $campusIdFilter);
            echo json_encode(['success' => true, 'message' => 'Book added successfully!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function update($id)
    {
        header('Content-Type: application/json');
        try {
            $data = $_POST;
            $adminId = $_SESSION['user_id'] ?? null;
            if (!$adminId) throw new Exception('Authentication required.');

            $campusIdFilter = $this->getCampusFilter();

            if (isset($_FILES['book_image']) && $_FILES['book_image']['error'] == 0) {
                $this->storageService->validateImage($_FILES['book_image']);
                $data['cover'] = $this->storageService->saveFile($_FILES['book_image'], "book_covers", "book");
            } elseif (isset($data['remove_image']) && $data['remove_image'] == "1") {
                $data['cover'] = null;
            }

            $success = $this->bookService->updateBook((int)$id, $data, $adminId, $campusIdFilter);
            echo json_encode(['success' => true, 'message' => $success ? 'Book updated successfully!' : 'No changes made.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function reactivate($id)
    {
        header('Content-Type: application/json');
        try {
            $adminId = $_SESSION['user_id'] ?? null;
            $campusIdFilter = $this->getCampusFilter();
            $this->bookService->reactivateBook((int)$id, $adminId, $campusIdFilter);
            echo json_encode(['success' => true, 'message' => 'Book reactivated successfully!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        header('Content-Type: application/json');
        try {
            $adminId = $_SESSION['user_id'] ?? null;
            $campusIdFilter = $this->getCampusFilter();
            $this->bookService->deactivateBook((int)$id, $adminId, $campusIdFilter);
            echo json_encode(['success' => true, 'message' => 'Book deactivated successfully!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteMultiple()
    {
        header('Content-Type: application/json');
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $adminId = $_SESSION['user_id'] ?? null;
            if (!$adminId) throw new Exception('Authentication required.');

            $result = $this->bookService->deleteMultiple($data['book_ids'] ?? [], $adminId);
            echo json_encode(array_merge(['success' => true], $result));
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function bulkImport()
    {
        header('Content-Type: application/json');
        try {
            if (!isset($_FILES['csv_file'])) throw new Exception('No file uploaded.');

            $adminId = $_SESSION['user_id'] ?? null;
            $campusIdFilter = $this->getCampusFilter();
            
            $result = $this->bookService->bulkImport($_FILES['csv_file']['tmp_name'], $adminId, $campusIdFilter);
            echo json_encode(array_merge(['success' => true], $result));
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getBookBorrowingHistory($id)
    {
        header('Content-Type: application/json');
        try {
            $history = $this->bookService->getBookBorrowingHistory((int)$id);
            echo json_encode(['success' => true, 'history' => $history]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
