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

    private function ensureAuthenticated()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . \BASE_URL . '/login');
            exit;
        }
        return [
            'user_id' => $_SESSION['user_id'],
            'role' => $_SESSION['role'] ?? 'guest'
        ];
    }

    public function index()
    {
        $auth = $this->ensureAuthenticated();
        $cartItems = $this->cartService->getUserCart($auth['user_id']);
        
        $role = $auth['role'];
        $viewFolder = ucfirst($role);
        if ($role === 'staff') $viewFolder = 'staff';
        
        if (!($role === 'student' || $role === 'faculty' || $role === 'staff')) {
            header('Location: ' . \BASE_URL . '/dashboard');
            exit;
        }

        $this->view("$viewFolder/myCart", [
            "cartItems" => $cartItems,
            "title" => "My Cart"
        ]);
    }

    public function add($bookId)
    {
        header('Content-Type: application/json');
        try {
            $auth = $this->ensureAuthenticated();
            $result = $this->cartService->addToCart($auth['user_id'], (int)$bookId);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function remove($cartId)
    {
        header('Content-Type: application/json');
        try {
            $auth = $this->ensureAuthenticated();
            $this->cartService->removeFromCart((int)$cartId, $auth['user_id']);
            echo json_encode(["success" => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function clearCart()
    {
        header('Content-Type: application/json');
        try {
            $auth = $this->ensureAuthenticated();
            $this->cartService->clearCart($auth['user_id']);
            echo json_encode(["success" => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getCartJson()
    {
        header('Content-Type: application/json');
        try {
            $auth = $this->ensureAuthenticated();
            $cartItems = $this->cartService->getUserCart($auth['user_id']);
            echo json_encode($cartItems);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function checkout()
    {
        header('Content-Type: application/json');
        try {
            $auth = $this->ensureAuthenticated();
            $data = json_decode(file_get_contents("php://input"), true);
            
            $ticketId = $this->cartService->checkout($auth['user_id'], $data['cart_ids'] ?? [], $auth['role']);
            
            echo json_encode([
                "success" => true,
                "ticket_id" => $ticketId
            ]);
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
        }
    }
}
