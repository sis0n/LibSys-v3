<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\QRScannerService;
use Exception;

class QRScannerController extends Controller
{
    private QRScannerService $qrService;

    public function __construct()
    {
        parent::__construct();
        $this->qrService = new QRScannerService();
    }

    public function index()
    {
        $role = $_SESSION['role'] ?? 'guest';
        $viewFolder = $role === 'staff' ? 'staff' : ucfirst($role);
        $this->view("$viewFolder/qrScanner", ["title" => "QR Scanner"]);
    }

    public function scan()
    {
        // Start buffering to catch any accidental output/warnings
        if (ob_get_level()) ob_end_clean();
        ob_start();
        
        header('Content-Type: application/json');
        
        try {
            $data = $this->getPostData();
            $transactionCode = $data['transaction_code'] ?? null;
            $currentLibrarianCampusId = $_SESSION['user_data']['campus_id'] ?? $_SESSION['campus_id'] ?? 0;

            if (!$transactionCode) throw new Exception('Transaction code is required.');

            // Fetch data from service
            $result = $this->qrService->scanTicket($transactionCode, (int)$currentLibrarianCampusId);
            
            $ticketData = $result['ticket'];
            $itemsData = $result['items'];

            // Smart URL Builder to prevent double paths
            $baseUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost/LibSys/public', '/');
            $formatUrl = function($path) use ($baseUrl) {
                if (empty($path)) return null;
                if (str_starts_with($path, 'http')) return $path;
                
                $cleanPath = ltrim($path, '/');
                // If path already contains storage/uploads, just append to baseUrl
                if (str_contains($cleanPath, 'storage/uploads')) {
                    return $baseUrl . '/' . $cleanPath;
                }
                // Otherwise, add the storage/uploads prefix
                return $baseUrl . '/storage/uploads/' . $cleanPath;
            };

            // Map data for Frontend JS
            $formattedUser = [
                'id' => $ticketData['student_number'] ?? $ticketData['unique_faculty_id'] ?? $ticketData['employee_id'] ?? 'N/A',
                'name' => ($ticketData['first_name'] ?? '') . ' ' . ($ticketData['last_name'] ?? ''),
                'type' => !empty($ticketData['student_id']) ? 'student' : (!empty($ticketData['faculty_id']) ? 'faculty' : 'staff'),
                'profilePicture' => $formatUrl($ticketData['profile_picture'] ?? null),
                'course' => $ticketData['course_title'] ?? $ticketData['course_code'] ?? 'N/A',
                'yearsection' => ($ticketData['year_level'] ?? '') . ' ' . ($ticketData['section'] ?? ''),
                'department' => $ticketData['college_name'] ?? $ticketData['college_code'] ?? 'N/A',
                'position' => $ticketData['position'] ?? 'N/A',
                'contact' => $ticketData['contact'] ?? 'N/A',
                'registrationFormUrl' => $formatUrl($ticketData['registration_form'] ?? null)
            ];

            $formattedItems = array_map(function($item) {
                return [
                    'title' => $item['title'] ?? 'Unknown Title',
                    'author' => $item['author'] ?? 'Unknown Author',
                    'accessionNumber' => $item['accession_number'] ?? 'N/A',
                    'callNumber' => $item['call_number'] ?? 'N/A',
                    'isbn' => $item['book_isbn'] ?? 'N/A'
                ];
            }, $itemsData);

            $response = [
                'isValid' => true,
                'user' => $formattedUser,
                'ticket' => [
                    'id' => $ticketData['transaction_code'],
                    'status' => $ticketData['status'],
                    'generated' => date('M d, Y h:i A', strtotime($ticketData['generated_at'] ?? $ticketData['borrowed_at'] ?? 'now')),
                    'dueDate' => !empty($ticketData['due_date']) ? date('M d, Y', strtotime($ticketData['due_date'])) : 'N/A'
                ],
                'items' => $formattedItems
            ];

            // Clear any buffered output/warnings before sending JSON
            ob_end_clean();
            echo json_encode(['success' => true, 'data' => $response]);
            exit;

        } catch (Exception $e) {
            if (ob_get_level()) ob_end_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    public function borrowTransaction()
    {
        if (ob_get_level()) ob_end_clean();
        ob_start();
        header('Content-Type: application/json');

        try {
            $data = $this->getPostData();
            $transactionCode = $data['transaction_code'] ?? null;
            $librarianId = $_SESSION['user_id'] ?? null;

            if (!$transactionCode || !$librarianId) {
                throw new Exception('Missing required information.');
            }

            $success = $this->qrService->borrowTransaction($transactionCode, (int)$librarianId);
            
            ob_end_clean();
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Transaction completed successfully!']);
            } else {
                throw new Exception('Failed to process transaction.');
            }
            exit;

        } catch (Exception $e) {
            if (ob_get_level()) ob_end_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    public function history()
    {
        if (ob_get_level()) ob_end_clean();
        ob_start();
        header('Content-Type: application/json');

        try {
            $search = $_GET['search'] ?? null;
            $status = $_GET['status'] ?? null;
            $date = $_GET['date'] ?? null;

            $transactions = $this->qrService->getTransactionHistory($search, $status, $date);
            
            $formatted = array_map(function($t) {
                return [
                    'studentName' => ($t['first_name'] ?? '') . ' ' . ($t['last_name'] ?? ''),
                    'studentNumber' => $t['user_identifier'] ?? 'N/A',
                    'itemsBorrowed' => $t['items_borrowed'] ?? 0,
                    'borrowedDateTime' => !empty($t['borrowed_at']) ? date('M d, Y h:i A', strtotime($t['borrowed_at'])) : '---',
                    'returnedDateTime' => !empty($t['returned_at']) ? date('M d, Y h:i A', strtotime($t['returned_at'])) : '---',
                    'status' => ucfirst($t['status'] ?? 'unknown')
                ];
            }, $transactions);

            ob_end_clean();
            echo json_encode($formatted);
            exit;

        } catch (Exception $e) {
            if (ob_get_level()) ob_end_clean();
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }
}
