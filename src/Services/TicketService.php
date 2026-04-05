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

            $userData = $_SESSION['user_data'] ?? [];
            $fullName = $userData['fullname'] ?? 'User';
            
            $studentData = [
                'student_number' => $userData['username'] ?? 'N/A',
                'name' => $fullName,
                'year_level' => ucfirst($role),
                'section' => '',
                'course' => $userData['program_department'] ?? 'N/A'
            ];

            if ($role === 'student') {
                $studentId = $this->ticketRepo->getStudentIdByUserId($userId);
                if ($studentId) {
                    $details = $this->ticketRepo->getStudentInfo($studentId);
                    $studentData = [
                        'id' => $details['student_number'] ?? 'N/A',
                        'name' => $fullName,
                        'year_level' => $details['year_level'] ?? 'N/A',
                        'section' => $details['section'] ?? '',
                        'course' => $details['course'] ?? 'N/A'
                    ];
                } else {
                    $studentData = [
                        'id' => $userData['username'] ?? 'N/A',
                        'name' => $fullName,
                        'year_level' => 'N/A',
                        'section' => '',
                        'course' => $userData['program_department'] ?? 'N/A'
                    ];
                }
            } elseif ($role === 'faculty') {
                $facultyId = $ticket['faculty_id'] ?? $this->ticketRepo->getFacultyIdByUserId($userId);
                $details = $facultyId ? $this->ticketRepo->getFacultyInfo((int)$facultyId) : null;
                $studentData = [
                    'id' => $details['student_number'] ?? $userData['username'] ?? 'N/A',
                    'name' => $fullName,
                    'department' => $details['course'] ?? $userData['program_department'] ?? 'N/A'
                ];
            } elseif ($role === 'staff') {
                $staffId = $ticket['staff_id'] ?? $this->ticketRepo->getStaffIdByUserId($userId);
                $details = $staffId ? $this->ticketRepo->getStaffInfo((int)$staffId) : null;
                $studentData = [
                    'id' => $details['student_number'] ?? $userData['username'] ?? 'N/A',
                    'name' => $fullName,
                    'position' => $details['course'] ?? $userData['program_department'] ?? 'N/A'
                ];
            }

            return array_merge(['status' => 'pending', 'books' => $books, 'borrower' => $studentData], $ticket);
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
