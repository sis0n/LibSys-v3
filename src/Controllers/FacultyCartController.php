<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\CartRepository;

class FacultyCartController extends Controller
{
  private CartRepository $cartRepo;

  public function __construct()
  {
    parent::__construct();
    $this->cartRepo = new CartRepository();
  }

  private function showErrorPage(int $status, string $message = "")
  {
    http_response_code($status);
    $this->view("errors/{$status}", ["message" => $message]);
    exit;
  }

  public function index()
  {
    $userId = $this->ensureFaculty();
    if (!$userId) $this->showErrorPage(401, "Access denied");

    $cartItems = $this->cartRepo->getCartByUser($userId);
    $this->view("faculty/cart", [
      "cartItems" => $cartItems,
      "title" => "My Cart"
    ]);
  }

  public function add($bookId)
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId || $_SESSION['role'] !== 'faculty') {
      http_response_code(401);
      echo json_encode([
        'success' => false,
        'message' => 'Unauthorized.'
      ]);
      return;
    }

    // Add to cart
    $result = $this->cartRepo->addToCart((int)$userId, $bookId);
    echo json_encode(['success' => $result]);
  }

  public function remove($cartId)
  {
    $userId = $this->ensureFaculty();
    $this->cartRepo->removeFromCart($cartId, $userId);

    header('Content-Type: application/json');
    echo json_encode(["success" => true]);
  }

  public function clearCart()
  {
    $userId = $this->ensureFaculty();
    $this->cartRepo->clearCart($userId);

    header('Content-Type: application/json');
    echo json_encode(["success" => true]);
  }

  public function getCartJson()
  {
    $userId = $this->ensureFaculty();
    $cartItems = $this->cartRepo->getCartByUser($userId);

    header('Content-Type: application/json');
    echo json_encode($cartItems);
  }

  public function checkout()
  {
    $userId = $this->ensureFaculty();

    $data = json_decode(file_get_contents("php://input"), true);
    $cartIds = $data['cart_ids'] ?? [];

    if (empty($cartIds)) {
      header('Content-Type: application/json');
      echo json_encode(["success" => false, "message" => "No items selected"]);
      return;
    }

    $ticketId = uniqid("TICKET-");

    foreach ($cartIds as $cid) {
      $this->cartRepo->removeFromCart((int)$cid, $userId);
    }

    header('Content-Type: application/json');
    echo json_encode([
      "success" => true,
      "ticket_id" => $ticketId
    ]);
  }

  private function ensureFaculty()
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
      header('Location: ' . BASE_URL . '/login');
      exit;
    }

    return $_SESSION['user_id'];
  }
}
