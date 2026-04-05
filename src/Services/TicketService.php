<?php

namespace App\Services;

use App\Repositories\TicketRepository;
use Exception;

class TicketService
{
    private TicketRepository $ticketRepo;

    public function __construct()
    {
        $this->ticketRepo = new TicketRepository();
    }

    /**
     * Get ticket details by transaction code
     */
    public function getTicketDetails(string $transactionCode): array
    {
        $ticket = $this->ticketRepo->getTicketByCode($transactionCode);
        if (!$ticket) {
            throw new Exception('Ticket not found.');
        }
        return $ticket;
    }

    /**
     * Get active ticket for a user
     */
    public function getActiveTicket(int $userId, string $role): ?array
    {
        return $this->ticketRepo->getActiveTicket($userId, $role);
    }

    /**
     * Check status of a ticket
     */
    public function checkStatus(int $userId, string $role): array
    {
        // Trigger expiration cleanup before checking
        $this->ticketRepo->expireOldPendingTransactions();
        
        $ticket = $this->ticketRepo->getActiveTicket($userId, $role);
        
        if (!$ticket) {
            return ['status' => 'none'];
        }

        // If the repository returned a ticket but it's expired, it will have status 'expired'
        if ($ticket['status'] === 'expired') {
            return ['status' => 'expired', 'transaction_code' => $ticket['transaction_code']];
        }

        if ($ticket['status'] === 'pending') {
            $ticketId = (int)$ticket['transaction_id'];
            $books = $this->ticketRepo->getTransactionItems($ticketId);

            $studentData = [
                'student_number' => 'N/A',
                'name' => ($_SESSION['user_data']['first_name'] ?? '') . ' ' . ($_SESSION['user_data']['last_name'] ?? ''),
                'year_level' => 'N/A',
                'section' => '',
                'course' => 'N/A'
            ];

            if ($role === 'student') {
                $studentId = $this->ticketRepo->getStudentIdByUserId($userId);
                if ($studentId) {
                    $details = $this->ticketRepo->getStudentInfo($studentId);
                    $studentData = [
                        'student_number' => $details['student_number'] ?? 'N/A',
                        'name' => ($_SESSION['user_data']['first_name'] ?? '') . ' ' . ($_SESSION['user_data']['last_name'] ?? ''),
                        'year_level' => $details['year_level'] ?? 'N/A',
                        'section' => $details['section'] ?? '',
                        'course' => $details['course'] ?? 'N/A'
                    ];
                }
            } elseif ($role === 'faculty') {
                $facultyId = $ticket['faculty_id'] ?? $this->ticketRepo->getFacultyIdByUserId($userId);
                if ($facultyId) {
                    $details = $this->ticketRepo->getFacultyInfo((int)$facultyId);
                    $studentData['student_number'] = $details['student_number'] ?? 'N/A';
                    $studentData['year_level'] = 'Faculty';
                    $studentData['course'] = $details['course'] ?? 'N/A';
                }
            } elseif ($role === 'staff') {
                $staffId = $ticket['staff_id'] ?? $this->ticketRepo->getStaffIdByUserId($userId);
                if ($staffId) {
                    $details = $this->ticketRepo->getStaffInfo((int)$staffId);
                    $studentData['student_number'] = $details['student_number'] ?? 'N/A';
                    $studentData['year_level'] = 'Staff';
                    $studentData['course'] = $details['course'] ?? 'N/A';
                }
            }

            return array_merge(['status' => 'pending', 'books' => $books, 'student' => $studentData], $ticket);
        }

        return ['status' => $ticket['status'], 'transaction_code' => $ticket['transaction_code']];
    }

    /**
     * Cancel an active ticket
     */
    public function cancelTicket(int $transactionId, int $userId): bool
    {
        return $this->ticketRepo->updateStatus($transactionId, 'cancelled');
    }
}
