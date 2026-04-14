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
        try {
            $auth = $this->ensureAuthenticated();
            $result = $this->cartService->addToCart($auth['user_id'], (int)$bookId);
            $this->jsonResponse($result);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    public function remove($cartId)
    {
        try {
            $auth = $this->ensureAuthenticated();
            $this->cartService->removeFromCart((int)$cartId, $auth['user_id']);
            $this->jsonResponse();
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    public function clearCart()
    {
        try {
            $auth = $this->ensureAuthenticated();
            $this->cartService->clearCart($auth['user_id']);
            $this->jsonResponse();
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    public function getCartJson()
    {
        try {
            $auth = $this->ensureAuthenticated();
            $cartItems = $this->cartService->getUserCart($auth['user_id']);
            $this->json($cartItems); // Return array directly
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    public function checkout()
    {
        try {
            $auth = $this->ensureAuthenticated();
            $data = $this->getJsonData();
            
            $ticketId = $this->cartService->checkout($auth['user_id'], $data['cart_ids'] ?? [], $auth['role']);
            
            $this->jsonResponse([
                "ticket_id" => $ticketId
            ]);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }
}
