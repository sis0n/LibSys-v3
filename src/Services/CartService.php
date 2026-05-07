<?php

namespace App\Services;

use App\Repositories\CartRepository;
use App\Repositories\TicketRepository;
use App\Repositories\LibraryPolicyRepository;
use App\Repositories\UserRepository;
use Exception;

class CartService
{
    private CartRepository $cartRepo;
    private TicketRepository $ticketRepo;
    private LibraryPolicyRepository $policyRepo;
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->cartRepo = new CartRepository();
        $this->ticketRepo = new TicketRepository();
        $this->policyRepo = new LibraryPolicyRepository();
        $this->userRepo = new UserRepository();
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
        $user = $this->userRepo->findById($userId);
        if (!$user) throw new Exception("User not found.");
        $campusId = $user['campus_id'] ?? null;
        if (!$campusId) throw new Exception("User campus not identified.");

        $policy = $this->policyRepo->getPolicyByRole($role, $campusId);
        if (!$policy) {
            $maxBooks = 5; 
        } else {
            $maxBooks = (int)$policy['max_books'];
        }

        $activeBorrowedCount = $this->ticketRepo->countActiveBorrowedItems($userId);

        $currentCartCount = $this->cartRepo->countCartItems($userId);

        $totalPotential = $activeBorrowedCount + $currentCartCount;

        if ($totalPotential >= $maxBooks) {
            throw new Exception("Borrowing limit reached. Your limit is {$maxBooks} books (including current borrowings and cart items).");
        }

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

        $roleId = null;
        $roleColumn = '';

        if ($role === 'student') {
            $roleId = $this->ticketRepo->getStudentIdByUserId($userId);
            $roleColumn = 'student_id';
            if (!$roleId) throw new Exception("Student record not found.");
            
            $profileStatus = $this->ticketRepo->checkProfileCompletion($roleId);
            if (!$profileStatus['complete']) {
                throw new Exception($profileStatus['message']);
            }
        } elseif ($role === 'faculty') {
            $roleId = $this->ticketRepo->getFacultyIdByUserId($userId);
            $roleColumn = 'faculty_id';
            if (!$roleId) throw new Exception("Faculty record not found.");

            $profileStatus = $this->ticketRepo->checkFacultyProfileCompletion($roleId);
            if (!$profileStatus['complete']) {
                throw new Exception($profileStatus['message']);
            }
        } elseif ($role === 'staff') {
            $roleId = $this->ticketRepo->getStaffIdByUserId($userId);
            $roleColumn = 'staff_id';
            if (!$roleId) throw new Exception("Staff record not found.");

            $profileStatus = $this->ticketRepo->checkStaffProfileCompletion($roleId);
            if (!$profileStatus['complete']) {
                throw new Exception($profileStatus['message']);
            }
        } else {
            throw new Exception("Invalid role for checkout.");
        }

        $items = $this->ticketRepo->getCartItemsByIds($userId, $cartIds);
        if (empty($items)) {
            throw new Exception("Selected items not found in your cart.");
        }

        $bookIds = array_column($items, 'book_id');

        $unavailableBooks = $this->ticketRepo->areBooksAvailable($bookIds);
        if (!empty($unavailableBooks)) {
            $titles = array_column($unavailableBooks, 'title');
            throw new Exception("Some books are no longer available: " . implode(', ', $titles));
        }

        $transactionCode = strtoupper(bin2hex(random_bytes(6)));

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

        try {
            $this->ticketRepo->beginTransaction();

            $user = $this->userRepo->findById($userId);
            $campusId = $user['campus_id'] ?? 1;
            $policy = $this->policyRepo->getPolicyByRole($role, $campusId);
            $durationDays = $policy ? (int)$policy['borrow_duration_days'] : 7;

            $dueDate = date('Y-m-d H:i:s', strtotime("+{$durationDays} days"));

            $transactionId = $this->ticketRepo->createPendingTransaction(
                $roleId,
                $transactionCode,
                $dueDate,
                $qrPath,
                $roleColumn
            );

            $this->ticketRepo->addTransactionItems($transactionId, $items);
            
            $this->ticketRepo->setBooksAvailability($bookIds, 'pending');

            $this->ticketRepo->removeCartItemsByIds($userId, $cartIds);

            $this->ticketRepo->commit();
        } catch (Exception $e) {
            $this->ticketRepo->rollback();
            if (file_exists($qrPath)) unlink($qrPath);
            throw $e;
        }

        return $transactionCode;
    }
}
