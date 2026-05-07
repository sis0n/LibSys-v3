<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\BookCatalogRepository;
use Exception;

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

        $this->view("guest/landingPage", [
            "title" => "LOSUCC",
            "books" => $books
        ], false);
    }

    public function fetchGuestBooks()
    {
        try {
            $search = $_GET['search'] ?? '';
            $offset = (int)($_GET['offset'] ?? 0);
            $limit  = (int)($_GET['limit'] ?? 30); 
            $category = $_GET['category'] ?? '';
            $status   = $_GET['status'] ?? '';
            $sort     = $_GET['sort'] ?? 'default';

            $books = $this->BookCatalogRepository->getPaginatedFiltered($limit, $offset, $search, $category, $status, $sort);
            $totalCount = $this->BookCatalogRepository->countPaginatedFiltered($search, $category, $status);

            return $this->jsonResponse(['books' => $books, 'totalCount' => $totalCount]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}