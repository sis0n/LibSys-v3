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
     * Add book to cart with validation (Library Policies)
     */
    public function addToCart(int $userId, int $bookId, string $role = 'student'): array
    {
        $ticketRepo = new \App\Repositories\TicketRepository();
        $policyRepo = new \App\Repositories\LibraryPolicyRepository();
        $userRepo = new \App\Repositories\UserRepository();

        // 1. Get User Data (specifically campus_id)
        $user = $userRepo->findById($userId);
        if (!$user) throw new Exception("User not found.");
        $campusId = $user['campus_id'] ?? null;
        if (!$campusId) throw new Exception("User campus not identified.");

        // 2. Get Library Policy for this role and campus
        $policy = $policyRepo->getPolicyByRole($role, $campusId);
        if (!$policy) {
            // Fallback or default if no policy is set yet
            $maxBooks = 5; 
        } else {
            $maxBooks = (int)$policy['max_books'];
        }

        // 3. Count Active Borrowed Items (Pending, Borrowed, Overdue)
        $activeBorrowedCount = $ticketRepo->countActiveBorrowedItems($userId);

        // 4. Count Current Cart Items
        $currentCartCount = $this->cartRepo->countCartItems($userId);

        // 5. Total potential borrowings
        $totalPotential = $activeBorrowedCount + $currentCartCount;

        if ($totalPotential >= $maxBooks) {
            throw new Exception("Borrowing limit reached. Your limit is {$maxBooks} books (including current borrowings and cart items).");
        }

        // 6. Check if already in cart
        if ($this->cartRepo->isBookInCart($userId, $bookId)) {
            return [
                "success" => false,
                "message" => "This book is already in your cart.",
                "cart_count" => $currentCartCount
            ];
        }

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
    public function checkout(int $userId, array $cartIds, string $role = 'student'): string
    {
        if (empty($cartIds)) {
            throw new Exception("No items selected for checkout.");
        }

        $ticketRepo = new TicketRepository();
        
        $roleId = null;
        $roleColumn = '';

        if ($role === 'student') {
            $roleId = $ticketRepo->getStudentIdByUserId($userId);
            $roleColumn = 'student_id';
            if (!$roleId) throw new Exception("Student record not found.");
            
            // Check Profile Completion (Student only for now as per requirement or keep it for all)
            $profileStatus = $ticketRepo->checkProfileCompletion($roleId);
            if (!$profileStatus['complete']) {
                throw new Exception($profileStatus['message']);
            }
        } elseif ($role === 'faculty') {
            $roleId = $ticketRepo->getFacultyIdByUserId($userId);
            $roleColumn = 'faculty_id';
            if (!$roleId) throw new Exception("Faculty record not found.");
        } elseif ($role === 'staff') {
            $roleId = $ticketRepo->getStaffIdByUserId($userId);
            $roleColumn = 'staff_id';
            if (!$roleId) throw new Exception("Staff record not found.");
        } else {
            throw new Exception("Invalid role for checkout.");
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
            $qrCode = new \Endroid\QrCode\QrCode($transactionCode);
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
                $roleId,
                $transactionCode,
                $dueDate,
                $qrPath,
                $roleColumn
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
