<?php

namespace App\Services;

use App\Repositories\QRScannerRepository;
use App\Repositories\AuditLogRepository;
use Exception;

class QRScannerService
{
    private QRScannerRepository $qrRepo;
    private AuditLogRepository $auditRepo;

    public function __construct()
    {
        $this->qrRepo = new QRScannerRepository();
        $this->auditRepo = new AuditLogRepository();
    }

    /**
     * Process scanned QR ticket
     */
    public function scanTicket(string $transactionCode, int $currentLibrarianCampusId): array
    {
        $ticket = $this->qrRepo->getTicketByCode($transactionCode);
        if (!$ticket) throw new Exception('Ticket not found.');

        if ($ticket['status'] === 'completed') throw new Exception('This ticket has already been processed.');
        if ($ticket['status'] === 'cancelled') throw new Exception('This ticket has been cancelled.');

        $items = $this->qrRepo->getTicketItems($ticket['transaction_id']);
        if (empty($items)) throw new Exception('No items found in this ticket.');

        // Cross-campus detection
        $isCrossCampus = ($ticket['campus_id'] != $currentLibrarianCampusId);
        
        foreach ($items as &$item) {
            $item['is_cross_campus'] = ($item['item_campus_id'] != $currentLibrarianCampusId);
        }

        return [
            'ticket' => $ticket,
            'items' => $items,
            'is_cross_campus' => $isCrossCampus
        ];
    }

    /**
     * Complete the transaction using ID
     */
    public function completeTransaction(int $transactionId, int $librarianId, int $librarianCampusId): bool
    {
        $success = $this->qrRepo->completeTransaction($transactionId, $librarianId, $librarianCampusId);
        if ($success) {
            $this->auditRepo->log($librarianId, 'PROCESS_TICKET', 'TRANSACTIONS', $transactionId, "Processed borrowing ticket ID: $transactionId");
            return true;
        }
        return false;
    }

    /**
     * Process borrowing using transaction code
     */
    public function borrowTransaction(string $transactionCode, int $librarianId): bool
    {
        $success = $this->qrRepo->processBorrowing($transactionCode, $librarianId);
        if ($success) {
            // Log it
            $this->auditRepo->log($librarianId, 'BORROW_BOOKS', 'TRANSACTIONS', 0, "Processed borrowing for ticket: $transactionCode");
            return true;
        }
        return false;
    }

    /**
     * Get transaction history with filters
     */
    public function getTransactionHistory(?string $search = null, ?string $status = null, ?string $date = null): array
    {
        return $this->qrRepo->getTransactionHistory($search, $status, $date);
    }
}
