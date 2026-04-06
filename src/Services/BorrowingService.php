<?php

namespace App\Services;

use App\Repositories\ManualBorrowingRepository;
use App\Repositories\BorrowingRepository;
use App\Repositories\BookCatalogRepository;
use App\Repositories\AuditLogRepository;
use Exception;

class BorrowingService
{
    private ManualBorrowingRepository $manualRepo;
    private BorrowingRepository $borrowingRepo;
    private BookCatalogRepository $bookRepo;
    private AuditLogRepository $auditRepo;

    public function __construct()
    {
        $this->manualRepo = new ManualBorrowingRepository();
        $this->borrowingRepo = new BorrowingRepository();
        $this->bookRepo = new BookCatalogRepository();
        $this->auditRepo = new AuditLogRepository();
    }

    /**
     * Process a manual borrow transaction
     */
    public function processManualBorrow(array $data, ?int $campusId, int $librarianId): array
    {
        $required = ['first_name', 'last_name', 'role', 'collateral_id', 'equipment_type'];
        if ($data['equipment_type'] === 'Book') {
            $required[] = 'accession_number';
        } else {
            if (empty($data['equipment_id'])) {
                $required[] = 'equipment_name';
            }
        }

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }

        $existingRole = $this->manualRepo->checkIfUserExists($data['input_user_id'], $campusId);
        $borrowerType = null;
        $borrowerId = null;

        if ($existingRole) {
            $userInfo = $this->manualRepo->getUserInfo($data['input_user_id'], $campusId);
            if (empty($userInfo['profile_updated'])) {
                throw new Exception('Profile incomplete. Borrower must update their profile first.');
            }
            $borrowerType = strtolower($existingRole);
            $borrowerId = $data['input_user_id'];
        } else {
            $borrowerType = 'guest';
            $borrowerId = $this->manualRepo->createGuest([
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'email'      => $data['email'] ?? null,
                'contact'    => $data['contact'] ?? null
            ]);
        }

        $itemId = null;
        if ($data['equipment_type'] === 'Book') {
            $book = $this->manualRepo->checkBook($data['accession_number'], $campusId);
            if (!$book['exists']) {
                throw new Exception('Book not found');
            }
            if (!$book['available']) {
                throw new Exception('Book is currently not available');
            }
            $itemId = $book['details']['book_id'];
        } else {
            $itemId = !empty($data['equipment_id']) ? $data['equipment_id'] : $data['equipment_name'];
        }

        $borrowData = [
            'borrower_type' => $borrowerType,
            'borrower_id'   => $borrowerId,
            'collateral_id' => $data['collateral_id'],
            'librarian_id'  => $librarianId,
            'campus_id'     => $campusId
        ];

        if ($data['equipment_type'] === 'Book') {
            $borrowData['book_id'] = $itemId;
        } else {
            $borrowData['equipment_id'] = $itemId;
        }

        $insert = $this->manualRepo->createManualBorrow($borrowData);

        if ($insert['success']) {
            $this->auditRepo->log($librarianId, 'BORROW', 'TRANSACTIONS', $insert['transaction_code'], "Manual borrow processed for {$data['first_name']} {$data['last_name']} ({$data['role']}) - Item: {$data['equipment_type']}");
            return [
                'success' => true,
                'message' => 'Borrow transaction created successfully',
                'transaction_code' => $insert['transaction_code']
            ];
        } else {
            throw new Exception($insert['message']);
        }
    }

    /**
     * Get available equipments for a campus
     */
    public function getAvailableEquipments(?int $campusId): array
    {
        return $this->manualRepo->getEquipments($campusId);
    }

    /**
     * Get available collaterals
     */
    public function getCollaterals(): array
    {
        return $this->manualRepo->getCollaterals();
    }

    /**
     * Check if a user exists and return their info
     */
    public function checkUser(string $inputUserId, ?int $campusId): array
    {
        $role = $this->manualRepo->checkIfUserExists($inputUserId, $campusId);
        if ($role) {
            $userInfo = $this->manualRepo->getUserInfo($inputUserId, $campusId);
            return ['exists' => true, 'data' => $userInfo];
        }
        return ['exists' => false];
    }
}
