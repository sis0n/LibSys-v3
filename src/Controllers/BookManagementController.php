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

    public function index()
    {
        $role = $_SESSION['role'] ?? 'guest';
        
        $data = [
            'title' => 'Book Management',
            'currentPage' => 'bookManagement',
            'permissions' => [
                'add' => true, // Librarians and above can add
                'edit' => true,
                'delete' => $role === 'superadmin' || $role === 'admin' || $role === 'campus_admin',
                'bulk_import' => $role === 'superadmin' || $role === 'admin' || $role === 'campus_admin',
                'multi_delete' => $role === 'superadmin' || $role === 'admin' || $role === 'campus_admin'
            ],
            'filters' => [
                'campus_locked' => !in_array($role, ['superadmin', 'admin']),
                'default_campus' => $_SESSION['user_data']['campus_id'] ?? null
            ]
        ];

        $this->view("management/bookManagement/index", $data);
    }

    public function fetch()
    {
        try {
            $campusFilter = $this->getCampusFilter();
            $result = $this->bookService->getPaginatedBooks($_GET, $campusFilter);
            return $this->jsonResponse(['books' => $result['books'], 'totalCount' => $result['totalCount']]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function getDetails($id)
    {
        try {
            $book = $this->bookService->getBookDetails((int)$id);
            return $this->jsonResponse(['book' => $book]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    public function store()
    {
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
            return $this->jsonResponse(['message' => 'Book added successfully!'], 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function update($id)
    {
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
            return $this->jsonResponse(['message' => $success ? 'Book updated successfully!' : 'No changes made.']);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function reactivate($id)
    {
        try {
            $adminId = $_SESSION['user_id'] ?? null;
            $campusIdFilter = $this->getCampusFilter();
            $this->bookService->reactivateBook((int)$id, $adminId, $campusIdFilter);
            return $this->jsonResponse(['message' => 'Book reactivated successfully!']);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $adminId = $_SESSION['user_id'] ?? null;
            $campusIdFilter = $this->getCampusFilter();
            $this->bookService->deactivateBook((int)$id, $adminId, $campusIdFilter);
            return $this->jsonResponse(['message' => 'Book deactivated successfully!']);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function deleteMultiple()
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $adminId = $_SESSION['user_id'] ?? null;
            if (!$adminId) throw new Exception('Authentication required.');

            $result = $this->bookService->deleteMultiple($data['book_ids'] ?? [], $adminId);
            return $this->jsonResponse($result);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function bulkImport()
    {
        try {
            if (!isset($_FILES['csv_file'])) throw new Exception('No file uploaded.');

            $adminId = $_SESSION['user_id'] ?? null;
            $campusIdFilter = $this->getCampusFilter();
            
            $result = $this->bookService->bulkImport($_FILES['csv_file']['tmp_name'], $adminId, $campusIdFilter);
            return $this->jsonResponse($result);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function getBookBorrowingHistory($id)
    {
        try {
            $history = $this->bookService->getBookBorrowingHistory((int)$id);
            return $this->jsonResponse(['history' => $history]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
