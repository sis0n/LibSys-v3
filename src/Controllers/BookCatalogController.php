<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\SearchService;
use Exception;

class BookCatalogController extends Controller
{
    private SearchService $searchService;

    public function __construct()
    {
        parent::__construct();
        $this->searchService = new SearchService();
    }

    public function index()
    {
        $userCampusId = $_SESSION['user_data']['campus_id'] ?? null;
        $campuses = $this->searchService->getFilterCampuses();

        $currentCampusName = "All Campuses";
        if ($userCampusId) {
            foreach ($campuses as $c) {
                if ($c['campus_id'] == $userCampusId) {
                    $currentCampusName = $c['campus_name'];
                    break;
                }
            }
        }

        $result = $this->searchService->searchBooks([], $userCampusId);

        $role = strtolower($_SESSION['role'] ?? '');
        $view_path = "Student/bookCatalog";
        if ($role === 'faculty') {
            $view_path = "Faculty/bookCatalog";
        } elseif ($role === 'staff') {
            $view_path = "staff/bookCatalog";
        }

        $this->view($view_path, [
            "books" => $result['books'],
            "campuses" => $campuses,
            "currentCampusId" => $userCampusId,
            "currentCampusName" => $currentCampusName,
            "title" => "Books Inventory",
            "currentPage" => "bookCatalog"
        ]);
    }

    public function fetch()
    {
        header('Content-Type: application/json');
        try {
            if (!isset($_SESSION['user_id'])) throw new Exception('Unauthorized', 401);

            $userCampusId = $_SESSION['user_data']['campus_id'] ?? null;
            $result = $this->searchService->searchBooks($_GET, $userCampusId);

            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code($e->getCode() ?: 500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
