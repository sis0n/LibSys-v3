<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\CartRepository;

class CartController extends Controller
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
    $userId = $this->ensureStudent();
    if (!$userId) $this->showErrorPage(401, "Unauthorized");

    $cartItems = $this->cartRepo->getCartByUser($userId);
    $this->view("student/cart", [
      "cartItems" => $cartItems,
      "title" => "My Cart"
    ]);
  }

  public function add($bookId)
  {
    $userId = $this->ensureStudent();
    if (!$userId) $this->showErrorPage(401, "Not logged in");

    $success = $this->cartRepo->addToCart($userId, $bookId);

    header('Content-Type: application/json');
    echo json_encode([
      "success" => $success,
      "cart_count" => $this->cartRepo->countCartItems($userId)
    ]);
  }

  public function remove($cartId)
  {
    $userId = $this->ensureStudent();
    if (!$userId) $this->showErrorPage(401, "Not logged in");

    $this->cartRepo->removeFromCart((int)$cartId, $userId);

    header('Content-Type: application/json');
    echo json_encode(["success" => true]);
  }

  public function clearCart()
  {
    $userId = $this->ensureStudent();
    if (!$userId) $this->showErrorPage(401, "Not logged in");

    $this->cartRepo->clearCart($userId);

    header('Content-Type: application/json');
    echo json_encode(["success" => true]);
  }

  public function getCartJson()
  {
    $userId = $this->ensureStudent();
    if (!$userId) $this->showErrorPage(401, "Not logged in");

    $cartItems = $this->cartRepo->getCartByUser($userId);

    header('Content-Type: application/json');
    echo json_encode($cartItems);
  }

  public function checkout()
  {
    $userId = $this->ensureStudent();
    if (!$userId) $this->showErrorPage(401, "Not logged in");

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

  private function ensureStudent()
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
      header('Location: ' . BASE_URL .'/login');
      exit;
    }

    return $_SESSION['user_id'];
  }
}
