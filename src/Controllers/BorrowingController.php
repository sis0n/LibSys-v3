<?php

namespace App\Controllers;

use App\Repositories\BorrowingRepository;
use App\Repositories\BookCatalogRepository;
use App\Core\Controller;

class BorrowingController extends Controller
{
  private $borrowingRepo;
  private $bookRepo;

  public function __construct()
  {
    parent::__construct();
    $this->borrowingRepo = new BorrowingRepository();
    $this->bookRepo = new BookCatalogRepository();
  }

  public function index()
  {
    $borrowings = $this->borrowingRepo->getAll();
    $this->view("borrowings/index", [
      "borrowings" => $borrowings,
      "title" => "Borrowings"
    ]);
  }

  public function returnBook($borrowingId)
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      http_response_code(405);
      $this->view('errors/405');
      exit;
    }

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
      http_response_code(403);
      $this->view('errors/403');
      exit;
    }

    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['librarian', 'admin'])) {
      http_response_code(403);
      $this->view('errors/403');
      exit;
    }

    $borrowing = $this->borrowingRepo->getById($borrowingId);

    if ($borrowing && !$borrowing['returned_at']) {
      $this->borrowingRepo->markAsReturned($borrowingId);
      $this->bookRepo->updateAvailability($borrowing['book_id'], 'available');
    }

    header("Location: placeholder bruh");
  }
}
