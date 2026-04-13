<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\BulkDeleteRepository;
use App\Repositories\AuditLogRepository;
use Exception;

class BulkDeleteController extends Controller
{
    private $bulkRepo;
    private $auditRepo;

    public function __construct()
    {
    parent::__construct();
        $this->bulkRepo = new BulkDeleteRepository();
        $this->auditRepo = new AuditLogRepository();
    }

    public function index()
    {
        $role = strtolower($_SESSION['role'] ?? 'guest');
        if (!in_array($role, ['superadmin', 'admin'])) {
            $this->view("errors/403", ["title" => "Unauthorized"]);
            return;
        }

        $this->view($role . "/bulkDeleteQueue", [
            "title" => "Bulk Delete Approval Queue",
            "currentPage" => "bulkDeleteQueue"
        ]);
    }

    public function fetchPending()
    {
        try {
            $campusId = $this->getCampusFilter();
            $userRole = strtolower($_SESSION['role'] ?? '');
            
            // Get all pending requests for the campus (or all if Superadmin)
            $requests = $this->bulkRepo->getPendingRequests($userRole === 'superadmin' ? null : $campusId);
            
            // --- HIERARCHY FILTERING ---
            // Campus Admin nag-request -> Admin o Superadmin ang mag-a-approve
            // Admin nag-request -> Superadmin ang mag-a-approve
            $filteredRequests = array_filter($requests, function($req) use ($userRole) {
                $reqRole = strtolower($req['requester_role'] ?? '');
                
                if ($userRole === 'superadmin') {
                    return true; // Superadmin can see/approve everything
                }
                
                if ($userRole === 'admin') {
                    // Admin can ONLY see/approve requests from Librarians
                    return in_array($reqRole, ['librarian']);
                }
                
                return false;
            });

            $this->json(['success' => true, 'requests' => array_values($filteredRequests)]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getDetails($id)
    {
        try {
            $request = $this->bulkRepo->getRequestDetails((int)$id);
            if (!$request) {
                return $this->json(['success' => false, 'message' => 'Request not found.'], 404);
            }
            $this->json(['success' => true, 'request' => $request]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function approve()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $requestId = (int)($data['request_id'] ?? 0);
        $approverId = $_SESSION['user_id'];
        $userRole = strtolower($_SESSION['role'] ?? '');

        if (!$requestId) {
            return $this->json(['success' => false, 'message' => 'Request ID is required.'], 400);
        }

        try {
            $request = $this->bulkRepo->getRequestDetails($requestId);
            if (!$request || $request['status'] !== 'pending') {
                return $this->json(['success' => false, 'message' => 'Valid pending request not found.'], 404);
            }

            // --- HIERARCHY AUTHORIZATION ---
            $reqRole = strtolower($request['requester_role'] ?? '');
            if ($userRole === 'admin') {
                if (!in_array($reqRole, ['librarian'])) {
                    return $this->json(['success' => false, 'message' => 'Unauthorized: Admins can only approve requests from Librarians.'], 403);
                }
            } elseif ($userRole !== 'superadmin') {
                return $this->json(['success' => false, 'message' => 'Unauthorized: Only Admins and Superadmins can approve requests.'], 403);
            }

            // Perform Actual Deactivation
            $successCount = 0;
            $skippedCount = 0;
            $errors = [];

            if ($request['entity_type'] === 'users') {
                $userRepo = new \App\Repositories\UserRepository();
                foreach ($request['items'] as $item) {
                    // deleteUserWithCascade returns false if user is already deleted
                    $res = $userRepo->deleteUserWithCascade($item['entity_id'], $approverId);
                    if ($res) {
                        $successCount++;
                    } else {
                        // Check if it's already deleted or an actual error
                        $skippedCount++;
                    }
                }
            } else {
                return $this->json(['success' => false, 'message' => 'Invalid entity type for bulk delete.'], 400);
            }

            // Update Request Status
            $this->bulkRepo->updateStatus($requestId, 'executed', $approverId);
            
            $summary = "Processed $successCount users";
            if ($skippedCount > 0) $summary .= " ($skippedCount skipped/already processed)";
            
            $this->auditRepo->log($approverId, 'APPROVE_BULK_DELETE', strtoupper($request['entity_type']), $requestId, "Approved and executed batch #$requestId. $summary.");

            $this->json([
                'success' => true, 
                'message' => "Bulk deactivation complete. $summary.",
                'skipped' => $skippedCount
            ]);

        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function reject()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $requestId = (int)($data['request_id'] ?? 0);
        $approverId = $_SESSION['user_id'];
        $userRole = strtolower($_SESSION['role'] ?? '');

        if (!$requestId) {
            return $this->json(['success' => false, 'message' => 'Request ID is required.'], 400);
        }

        try {
            $request = $this->bulkRepo->getRequestDetails($requestId);
            if (!$request || $request['status'] !== 'pending') {
                return $this->json(['success' => false, 'message' => 'Valid pending request not found.'], 404);
            }

            // --- HIERARCHY AUTHORIZATION ---
            $reqRole = strtolower($request['requester_role'] ?? '');
            if ($userRole === 'admin') {
                if (!in_array($reqRole, ['librarian'])) {
                    return $this->json(['success' => false, 'message' => 'Unauthorized: Admins can only reject requests from Librarians.'], 403);
                }
            } elseif ($userRole !== 'superadmin') {
                return $this->json(['success' => false, 'message' => 'Unauthorized: Only Admins and Superadmins can reject requests.'], 403);
            }

            $this->bulkRepo->updateStatus($requestId, 'rejected', $approverId);
            $this->auditRepo->log($approverId, 'REJECT_BULK_DELETE', strtoupper($request['entity_type']), $requestId, "Rejected bulk deactivation request #$requestId");

            $this->json(['success' => true, 'message' => 'Bulk deactivation request rejected.']);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
