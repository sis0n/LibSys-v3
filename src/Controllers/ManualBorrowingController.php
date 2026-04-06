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

    public function getEquipments(): void
    {
        header('Content-Type: application/json');
        try {
            $campusId = $this->getCampusFilter();
            $equipments = $this->borrowingService->getAvailableEquipments($campusId);
            echo json_encode($equipments);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Failed to fetch equipments'], 500);
        }
    }

    public function getCollaterals(): void
    {
        header('Content-Type: application/json');
        try {
            $collaterals = $this->borrowingService->getCollaterals();
            echo json_encode($collaterals);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Failed to fetch collaterals'], 500);
        }
    }

    public function checkUser(): void
    {
        header('Content-Type: application/json');
        $input_user_id = $_POST['input_user_id'] ?? null;
        if (!$input_user_id) {
            echo json_encode(['success' => false, 'message' => 'No input_user_id provided']);
            return;
        }

        $campusId = $this->getCampusFilter();
        $result = $this->borrowingService->checkUser($input_user_id, $campusId);
        
        echo json_encode(array_merge(['success' => true], $result));
    }

    public function create(): void
    {
        header('Content-Type: application/json');
        try {
            $data = $_POST;
            $campusId = $this->getCampusFilter();
            $librarianId = $_SESSION['user_id'] ?? null;

            if (!$librarianId) throw new Exception('Librarian authentication required.');

            $result = $this->borrowingService->processManualBorrow($data, $campusId, $librarianId);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
