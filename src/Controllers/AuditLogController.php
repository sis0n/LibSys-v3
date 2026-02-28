<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\AuditLogRepository;

class AuditLogController extends Controller
{
    private $auditRepo;

    public function __construct()
    {
        $this->auditRepo = new AuditLogRepository();
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
        header('Content-Type: application/json');
        try {
            $search = $_GET['search'] ?? '';
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            $page = (int)($_GET['page'] ?? 1);
            
            if ($page > 1) {
                $offset = ($page - 1) * $limit;
            }

            $logs = $this->auditRepo->fetchLogs($search, $limit, $offset);
            $totalCount = $this->auditRepo->countLogs($search);

            echo json_encode([
                'success' => true,
                'logs' => $logs,
                'totalCount' => (int)$totalCount,
                'totalPages' => ceil($totalCount / $limit)
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
