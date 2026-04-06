<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\BookCatalogRepository;
use App\Repositories\CampusRepository;
use PDO;

class FacultyBookCatalogController extends Controller
{
  private $bookRepo;
  private $campusRepo;

  public function __construct()
  {
    parent::__construct();
    $this->bookRepo = new BookCatalogRepository();
    $this->campusRepo = new CampusRepository();
  }

  public function index()
  {
    $campusId = $_SESSION['user_data']['campus_id'] ?? null;
    $books = $this->bookRepo->getAllBooks($campusId);
    $campuses = $this->campusRepo->getAllCampuses();

    // Get current campus name for default display
    $currentCampusName = "All Campuses";
    if ($campusId) {
        foreach ($campuses as $c) {
            if ($c['campus_id'] == $campusId) {
                $currentCampusName = $c['campus_name'];
                break;
            }
        }
    }

    // Transform paths para isama ang BASE_URL
    $books = array_map(function($book) {
      if (!empty($book['cover'])) {
        $book['cover'] = BASE_URL . '/' . ltrim($book['cover'], '/');
      }
      return $book;
    }, $books);

    $this->view("Faculty/bookCatalog", [
      "books" => $books,
      "campuses" => $campuses,
      "currentCampusId" => $campusId,
      "currentCampusName" => $currentCampusName,
      "title" => "Books Inventory",
      "currentPage" => "bookCatalog"
    ]);
  }

  public function search()
  {
    $keyword = $_GET['pl'] ?? '';
    $books = $this->bookRepo->searchBooks($keyword);
    $this->view("books/index", [
      "books" => $books,
      "title" => "Search Results"
    ]);
  }

  public function create()
  {
    $this->view("books/create", [
      "title" => "Add New Book"
    ]);
  }

  public function store()
  {
    if (!isset($_SESSION['user_id'])) {
      http_response_code(401);
      echo json_encode(['error' => 'Unauthorized']);
      exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        exit('Invalid CSRF token');
      }
    }

    $data = $_POST;
    $this->bookRepo->addBook($data);
    header("Location: /books");
  }

  public function edit($id)
  {
    $book = $this->bookRepo->getBookById($id);
    $this->view("books/edit", [
      "book" => $book,
      "title" => "Edit Book"
    ]);
  }

  public function update($id)
  {

    if (!isset($_SESSION['user_id'])) {
      http_response_code(401);
      echo json_encode(['error' => 'Unauthorized']);
      exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        exit('Invalid CSRF token');
      }
    }

    $data = $_POST;
    $this->bookRepo->updateBook($id, $data);
    header("Location: /books");
  }

  public function destroy($id)
  {
    if (!isset($_SESSION['user_id'])) {
      http_response_code(401);
      echo json_encode(['error' => 'Unauthorized']);
      exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        exit('Invalid CSRF token');
      }
    }
    $this->bookRepo->deleteBook($id);
    header("Location: /books");
  }

  public function filter()
  {
    $filters = $_GET;
    $books = $this->bookRepo->filterBooks($filters);
    $this->view("books/index", [
      "books" => $books,
      "title" => "Filtered Books"
    ]);
  }

  public function fetch()
  {
    if (!isset($_SESSION['user_id'])) {
      http_response_code(401);
      echo json_encode(['error' => 'Unauthorized']);
      exit;
    }

    $search   = $_GET['search'] ?? '';
    $offset   = (int)($_GET['offset'] ?? 0);
    $limit    = (int)($_GET['limit'] ?? 30);
    $category = $_GET['category'] ?? '';
    $status   = $_GET['status'] ?? '';
    $sort     = $_GET['sort'] ?? 'default';

    $campusIdParam = $_GET['campus_id'] ?? null;
    $campusId = null;

    if ($campusIdParam === 'all') {
        $campusId = null;
    } elseif ($campusIdParam !== null) {
        $campusId = (int)$campusIdParam;
    } else {
        $campusId = $_SESSION['user_data']['campus_id'] ?? null;
    }

    $books = $this->bookRepo->getPaginatedFiltered(
      $limit,
      $offset,
      $search,
      $category,
      $status,
      $sort,
      $campusId
    );

    // Transform paths para isama ang BASE_URL
    $books = array_map(function($book) {
      if (!empty($book['cover'])) {
        $book['cover'] = BASE_URL . '/' . ltrim($book['cover'], '/');
      }
      return $book;
    }, $books);

    $totalCount = $this->bookRepo->countPaginatedFiltered($search, $category, $status, $campusId);

    $response = [
      'books' => $books,
      'totalCount' => $totalCount
    ];

    header('Content-Type: application/json');
    echo json_encode($response);
  }

  public function getAvailableCount()
  {
    $campusIdParam = $_GET['campus_id'] ?? null;
    $campusId = null;

    if ($campusIdParam === 'all') {
        $campusId = null;
    } elseif ($campusIdParam !== null) {
        $campusId = (int)$campusIdParam;
    } else {
        $campusId = $_SESSION['user_data']['campus_id'] ?? null;
    }

    $count = $this->bookRepo->countAvailableBooks($campusId);
    header('Content-Type: application/json');
    echo json_encode(['available' => $count]);
  }
}