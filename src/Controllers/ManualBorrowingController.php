<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\BorrowingService;
use Exception;

class ManualBorrowingController extends Controller
{
    private BorrowingService $borrowingService;

    public function __construct()
    {
        parent::__construct();
        $this->borrowingService = new BorrowingService();
    }

    public function index()
    {
        $role = strtolower($_SESSION['role'] ?? '');
        $apiBasePath = BASE_URL . '/api/' . ($role === 'superadmin' ? 'superadmin' : ($role === 'admin' ? 'admin' : 'librarian')) . '/borrowingForm';

        $this->view('management/borrowingForm/index', [
            'title' => 'Borrowing Form',
            'currentPage' => 'borrowingForm',
            'apiBasePath' => $apiBasePath
        ]);
    }

    public function getEquipments(): void
    {
        try {
            $campusId = $this->getCampusFilter();
            $equipments = $this->borrowingService->getAvailableEquipments($campusId);
            $this->jsonResponse(['list' => $equipments]);
        } catch (Exception $e) {
            $this->errorResponse('Failed to fetch equipments', 500);
        }
    }

    public function getCollaterals(): void
    {
        try {
            $collaterals = $this->borrowingService->getCollaterals();
            $this->jsonResponse(['list' => $collaterals]);
        } catch (Exception $e) {
            $this->errorResponse('Failed to fetch collaterals', 500);
        }
    }

    public function checkUser(): void
    {
        try {
            $input_user_id = $_POST['input_user_id'] ?? null;
            if (!$input_user_id) {
                $this->errorResponse('No input_user_id provided', 400);
                return;
            }

            $campusId = $this->getCampusFilter();
            $result = $this->borrowingService->checkUser($input_user_id, $campusId);
            
            $this->jsonResponse($result);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    public function create(): void
    {
        try {
            $data = $_POST;
            $campusId = $this->getCampusFilter();
            $librarianId = $_SESSION['user_id'] ?? null;

            if (!$librarianId) throw new Exception('Librarian authentication required.');

            $result = $this->borrowingService->processManualBorrow($data, $campusId, $librarianId);
            $this->jsonResponse($result);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }
}
