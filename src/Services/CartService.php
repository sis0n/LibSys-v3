<?php

namespace App\Services;

use App\Repositories\CartRepository;
use App\Repositories\TicketRepository;
use Exception;

class CartService
{
    private CartRepository $cartRepo;

    public function __construct()
    {
        $this->cartRepo = new CartRepository();
    }

    /**
     * Get user cart items
     */
    public function getUserCart(int $userId): array
    {
        return $this->cartRepo->getCartByUser($userId);
    }

    /**
     * Add book to cart with validation
     */
    public function addToCart(int $userId, int $bookId): array
    {
        // Add business logic for cart limits or reservation rules here
        $success = $this->cartRepo->addToCart($userId, $bookId);
        
        return [
            "success" => $success,
            "cart_count" => $this->cartRepo->countCartItems($userId)
        ];
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart(int $cartId, int $userId): bool
    {
        return $this->cartRepo->removeFromCart($cartId, $userId);
    }

    /**
     * Clear entire cart
     */
    public function clearCart(int $userId): bool
    {
        return $this->cartRepo->clearCart($userId);
    }

    /**
     * Process checkout
     */
    public function checkout(int $userId, array $cartIds): string
    {
        if (empty($cartIds)) {
            throw new Exception("No items selected for checkout.");
        }

        $ticketRepo = new TicketRepository();
        
        // 1. Get Student ID
        $studentId = $ticketRepo->getStudentIdByUserId($userId);
        if (!$studentId) {
            throw new Exception("Student record not found.");
        }

        // 2. Check Profile Completion
        $profileStatus = $ticketRepo->checkProfileCompletion($studentId);
        if (!$profileStatus['complete']) {
            throw new Exception($profileStatus['message']);
        }

        // 3. Get Cart Items Details
        $items = $ticketRepo->getCartItemsByIds($userId, $cartIds);
        if (empty($items)) {
            throw new Exception("Selected items not found in your cart.");
        }

        $bookIds = array_column($items, 'book_id');

        // 4. Check Books Availability
        $unavailableBooks = $ticketRepo->areBooksAvailable($bookIds);
        if (!empty($unavailableBooks)) {
            $titles = array_column($unavailableBooks, 'title');
            throw new Exception("Some books are no longer available: " . implode(', ', $titles));
        }

        // 5. Generate Transaction Code
        $transactionCode = strtoupper(bin2hex(random_bytes(6)));

        // 6. Generate QR Code
        $qrPath = ROOT_PATH . "/public/storage/uploads/qrcodes/" . $transactionCode . ".svg";
        $qrDir = dirname($qrPath);
        if (!is_dir($qrDir)) {
            mkdir($qrDir, 0777, true);
        }

        try {
            $qrCode = \Endroid\QrCode\QrCode::create($transactionCode);
            $writer = new \Endroid\QrCode\Writer\SvgWriter();
            $result = $writer->write($qrCode);
            $result->saveToFile($qrPath);
        } catch (Exception $e) {
            throw new Exception("Failed to generate QR code: " . $e->getMessage());
        }

        // 7. Create Database Transaction
        try {
            $ticketRepo->beginTransaction();

            // Set due date (7 days from now)
            $dueDate = date('Y-m-d H:i:s', strtotime('+7 days'));

            $transactionId = $ticketRepo->createPendingTransaction(
                $studentId,
                $transactionCode,
                $dueDate,
                $qrPath
            );

            $ticketRepo->addTransactionItems($transactionId, $items);
            
            // Set books to pending availability
            $ticketRepo->setBooksAvailability($bookIds, 'pending');

            // Clear selected items from cart
            $ticketRepo->removeCartItemsByIds($userId, $cartIds);

            $ticketRepo->commit();
        } catch (Exception $e) {
            $ticketRepo->rollback();
            if (file_exists($qrPath)) unlink($qrPath);
            throw $e;
        }

        return $transactionCode;
    }
}
