<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuditLogService;
use Exception;

class AuditLogController extends Controller
{
    private AuditLogService $auditService;

    public function __construct()
    {
        parent::__construct();
        // RBAC: Only Superadmin can view audit logs
        if (($_SESSION['role'] ?? '') !== 'superadmin') {
            http_response_code(403);
            die("Forbidden");
        }
        $this->auditService = new AuditLogService();
    }

    public function index()
    {
        $this->view('superadmin/auditLogs', [
            'title' => 'Audit Trail',
            'currentPage' => 'auditLogs'
        ]);
    }

    public function fetch()
    {
        try {
            $limit = (int)($_GET['limit'] ?? 10);
            $offset = (int)($_GET['offset'] ?? 0);
            $search = $_GET['search'] ?? '';
            $campusId = $this->getCampusFilter();

            $logs = $this->auditService->getLogs($limit, $offset, $search, $campusId);
            $totalCount = $this->auditService->countLogs($search, $campusId);

            $this->jsonResponse([
                'logs' => $logs,
                'totalCount' => $totalCount
            ]);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }}
