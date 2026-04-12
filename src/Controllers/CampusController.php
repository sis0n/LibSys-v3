<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\CampusService;
use Exception;

class CampusController extends Controller
{
    private CampusService $campusService;

    public function __construct()
    {
        parent::__construct();
        $this->campusService = new CampusService();
    }

    /**
     * Get all active campuses (Public/Any Authenticated Role)
     */
    public function getActive()
    {
        try {
            $campuses = $this->campusService->getActiveCampuses();
            $this->json($campuses);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }
}
