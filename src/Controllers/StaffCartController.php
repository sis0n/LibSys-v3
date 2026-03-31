<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\CartRepository;

class StaffCartController extends Controller
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
    $userId = $this->ensureStaff();
    if (!$userId) $this->showErrorPage(401, "Access denied");

    $cartItems = $this->cartRepo->getCartByUser($userId);
    $this->view("staff/cart", [
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
    if (!$userId || $_SESSION['role'] !== 'staff') {
      http_response_code(401);
      echo json_encode([
        'success' => false,
        'message' => 'Unauthorized.'
      ]);
      return;
    }

    $result = $this->cartRepo->addToCart((int)$userId, $bookId);
    echo json_encode(['success' => $result]);
  }

  public function remove($cartId)
  {
    $userId = $this->ensureStaff();
    $this->cartRepo->removeFromCart($cartId, $userId);

    header('Content-Type: application/json');
    echo json_encode(["success" => true]);
  }

  public function clearCart()
  {
    $userId = $this->ensureStaff();
    $this->cartRepo->clearCart($userId);

    header('Content-Type: application/json');
    echo json_encode(["success" => true]);
  }

  public function getCartJson()
  {
    $userId = $this->ensureStaff();
    $cartItems = $this->cartRepo->getCartByUser($userId);

    header('Content-Type: application/json');
    echo json_encode($cartItems);
  }

  public function checkout()
  {
    $userId = $this->ensureStaff();

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

  private function ensureStaff()
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
      header('Location: '. BASE_URL . '/login');
      exit;
    }

    return $_SESSION['user_id'];
  }
}
