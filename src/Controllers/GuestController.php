<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\BookCatalogRepository;

class GuestController extends Controller
{
    private $BookCatalogRepository;

    public function __construct()
    {
    parent::__construct();
        $this->BookCatalogRepository = new BookCatalogRepository();
    }

    public function guestDisplay()
    {
        $books = $this->BookCatalogRepository->getAllBooks();

        $this->view("Guest/landingPage", [
            "title" => "LOSUCC",
            "books" => $books
        ], false);
    }

    public function fetchGuestBooks()
    {
        $search = $_GET['search'] ?? '';
        $offset = (int)($_GET['offset'] ?? 0);
        $limit  = (int)($_GET['limit'] ?? 30); 
        $category = $_GET['category'] ?? '';
        $status   = $_GET['status'] ?? '';
        $sort     = $_GET['sort'] ?? 'default';

        $books = $this->BookCatalogRepository->getPaginatedFiltered($limit, $offset, $search, $category, $status, $sort);
        $totalCount = $this->BookCatalogRepository->countPaginatedFiltered($search, $category, $status);

        header('Content-Type: application/json');
        echo json_encode(['books' => $books, 'totalCount' => $totalCount]);
    }
}