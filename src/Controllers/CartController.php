<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\CartService;
use Exception;

class CartController extends Controller
{
    private CartService $cartService;

    public function __construct()
    {
        parent::__construct();
        $this->cartService = new CartService();
    }

    private function ensureStudent()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
            header('Location: ' . \BASE_URL .'/login');
            exit;
        }
        return $_SESSION['user_id'];
    }

    public function index()
    {
        $userId = $this->ensureStudent();
        $cartItems = $this->cartService->getUserCart($userId);
        $this->view("student/cart", [
            "cartItems" => $cartItems,
            "title" => "My Cart"
        ]);
    }

    public function add($bookId)
    {
        header('Content-Type: application/json');
        try {
            $userId = $this->ensureStudent();
            $result = $this->cartService->addToCart($userId, (int)$bookId);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function remove($cartId)
    {
        header('Content-Type: application/json');
        try {
            $userId = $this->ensureStudent();
            $this->cartService->removeFromCart((int)$cartId, $userId);
            echo json_encode(["success" => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function clearCart()
    {
        header('Content-Type: application/json');
        try {
            $userId = $this->ensureStudent();
            $this->cartService->clearCart($userId);
            echo json_encode(["success" => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getCartJson()
    {
        header('Content-Type: application/json');
        try {
            $userId = $this->ensureStudent();
            $cartItems = $this->cartService->getUserCart($userId);
            echo json_encode($cartItems);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function checkout()
    {
        header('Content-Type: application/json');
        try {
            $userId = $this->ensureStudent();
            $data = json_decode(file_get_contents("php://input"), true);
            
            $ticketId = $this->cartService->checkout($userId, $data['cart_ids'] ?? []);
            
            echo json_encode([
                "success" => true,
                "ticket_id" => $ticketId
            ]);
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
        }
    }
}
