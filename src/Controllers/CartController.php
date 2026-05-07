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
        
        $this->view("user/myCart", [
            "cartItems" => $cartItems,
            "title" => "My Cart"
        ]);
    }

    public function add($bookId)
    {
        try {
            $auth = $this->ensureAuthenticated();
            $result = $this->cartService->addToCart($auth['user_id'], (int)$bookId, $auth['role']);
            $cart = $this->cartService->getUserCart($auth['user_id']);
            
            $this->jsonResponse([
                "success" => $result['success'],
                "message" => $result['message'] ?? ($result['success'] ? "Book added to cart!" : "Could not add book."),
                "cart" => $cart,
                "cart_count" => $result['cart_count']
            ]);
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
            $this->json($cartItems); 
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
