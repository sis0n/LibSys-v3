<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\StudentRepository;
use App\Repositories\FacultyRepository;
use App\Repositories\StaffRepository;
use App\Repositories\UserPermissionModuleRepository;
use App\Repositories\CampusRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\BulkDeleteRepository;
use App\Repositories\StudentProfileRepository;
use App\Repositories\CollegeCourseRepository;
use Exception;

class UserService
{
    private UserRepository $userRepo;
    private StudentRepository $studentRepo;
    private FacultyRepository $facultyRepo;
    private StaffRepository $staffRepo;
    private UserPermissionModuleRepository $userPermissionRepo;
    private CampusRepository $campusRepo;
    private AuditLogRepository $auditRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
        $this->studentRepo = new StudentRepository();
        $this->facultyRepo = new FacultyRepository();
        $this->staffRepo = new StaffRepository();
        $this->userPermissionRepo = new UserPermissionModuleRepository();
        $this->campusRepo = new CampusRepository();
        $this->auditRepo = new AuditLogRepository();
    }

    /**
     * Fetch paginated users with filters
     */
    public function getPaginatedUsers(array $params, ?int $currentUserId, ?int $campusIdFilter): array
    {
        $limit = (int)($params['limit'] ?? 10);
        $offset = (int)($params['offset'] ?? 0);
        $search = $params['search'] ?? '';
        $filterRole = $params['role'] ?? 'All Roles';
        $status = $params['status'] ?? 'All Status';

        $users = $this->userRepo->getPaginatedUsers($limit, $offset, $search, $filterRole, $status, $currentUserId, $campusIdFilter);
        $totalCount = $this->userRepo->countPaginatedUsers($search, $filterRole, $status, $currentUserId, $campusIdFilter);

        return ['users' => $users, 'totalCount' => $totalCount];
    }

    /**
     * Get comprehensive user details by ID
     */
    public function getUserDetails(int $id, ?int $campusIdFilter = null): array
    {
        $user = $this->userRepo->getUserById($id, $campusIdFilter);
        if (!$user) {
            throw new Exception('User not found or unauthorized access.');
        }

        $role = strtolower($user['role'] ?? '');
        $extraDetails = null;

        if ($role === 'student') {
            $extraDetails = $this->studentRepo->getStudentByUserId($id);
        } elseif ($role === 'faculty') {
            $extraDetails = $this->facultyRepo->getFacultyByUserId($id);
        } elseif ($role === 'staff') {
            $extraDetails = $this->staffRepo->getStaffByUserId($id);
        }

        $modules = [];
        if (in_array($role, ['admin', 'librarian'])) {
            $modules = $this->userPermissionRepo->getModulesByUserId($id);
        }

        if (isset($user['campus_id'])) {
            $campuses = $this->campusRepo->getAllCampuses();
            foreach ($campuses as $campus) {
                if ($campus['campus_id'] === $user['campus_id']) {
                    $user['campus_name'] = $campus['campus_name'];
                    break;
                }
            }
        }

        return ['user' => $user, 'extra' => $extraDetails, 'modules' => $modules];
    }

    /**
     * Add a new user with role-specific details
     */
    public function addUser(array $data, int $adminId, ?int $campusIdFilter): int
    {
        $firstName = trim($data['first_name'] ?? '');
        $lastName = trim($data['last_name'] ?? '');
        $username = trim($data['username'] ?? '');
        $role = strtolower(trim($data['role'] ?? ''));
        $campusId = $campusIdFilter ?? $data['campus_id'] ?? null;

        // Ensure campusId is truly null if it's empty or 0 (for Global Admin)
        if (empty($campusId) || $campusId == 0) {
            $campusId = null;
        }

        $isGlobalEligible = in_array($role, ['superadmin', 'admin']);
        
        if (!$firstName || !$lastName || !$username || !$role || (!$campusId && !$isGlobalEligible)) {
            throw new Exception('First Name, Last Name, Username, and Role are required. Campus is also required for local roles.');
        }

        $db = $this->userRepo->getDbConnection();
        $db->beginTransaction();

        try {
            if ($this->userRepo->usernameExists($username)) {
                throw new Exception("The username '$username' is already taken.");
            }

            if ($role === 'student' && $this->studentRepo->studentNumberExists($username)) {
                throw new Exception('Student Number already exists.');
            }

            $hashedPassword = password_hash($data['password'] ?? '12345', PASSWORD_DEFAULT);

            $userData = [
                'username' => $username,
                'password' => $hashedPassword,
                'first_name' => $firstName,
                'middle_name' => trim($data['middle_name'] ?? ''),
                'last_name' => $lastName,
                'campus_id' => $campusId,
                'email' => $data['email'] ?? null,
                'role' => str_replace(' ', '_', $role),
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $userId = $this->userRepo->insertUser($userData);
            if (!$userId) {
                throw new Exception("Failed to insert user record.");
            }

            $this->handleRoleSpecificInsert($userId, $role, $data, $campusId, $db);

            $this->auditRepo->log($adminId, 'CREATE', 'USERS', $username, "Added new user: $firstName $lastName as " . ucfirst($role));
            $db->commit();

            return $userId;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    private function handleRoleSpecificInsert(int $userId, string $role, array $data, ?int $campusId, $db): void
    {
        switch ($role) {
            case 'student':
                $courseId = filter_var($data['course_id'] ?? null, FILTER_VALIDATE_INT);
                if (!$courseId) throw new Exception('Course/Program selection is required');
                
                $campusName = 'N/A';
                $campuses = $this->campusRepo->getAllCampuses();
                foreach ($campuses as $cp) {
                    if ($cp['campus_id'] == $campusId) {
                        $campusName = $cp['campus_name'];
                        break;
                    }
                }
                $this->studentRepo->insertStudent($userId, $data['username'], $courseId, $data['year_level'] ?? 1, 'enrolled', $campusName);
                break;

            case 'faculty':
                $collegeId = !empty($data['college_id']) ? (int)$data['college_id'] : null;
                $this->facultyRepo->insertFaculty($userId, $collegeId, $data['contact'] ?? 'N/A', 'active');
                break;

            case 'staff':
                $this->staffRepo->insertStaff($userId, $data['position'] ?? 'N/A', $data['contact'] ?? 'N/A', 'active');
                break;

            case 'admin':
            case 'librarian':
                if (empty($data['modules']) || !is_array($data['modules'])) {
                    throw new Exception('Please select at least one module.');
                }
                $this->userPermissionRepo->assignModules($userId, $data['modules']);
                break;

            default:
                throw new Exception('Invalid role specified.');
        }
    }

    /**
     * Delete user with integrity checks
     */
    public function deleteUser(int $id, int $adminId, ?int $campusIdFilter): void
    {
        if ($this->userRepo->hasBorrowedItems($id)) {
            throw new Exception('Cannot delete user. The user still has borrowed books or equipment.');
        }

        $user = $this->userRepo->getUserById($id);
        if (!$user) throw new Exception('User not found.');

        if ($campusIdFilter !== null && $user['campus_id'] != $campusIdFilter) {
            throw new Exception('Unauthorized: User belongs to another campus.');
        }

        if ($this->userRepo->deleteUserWithCascade($id, $adminId)) {
            $this->auditRepo->log($adminId, 'DELETE', 'USERS', $user['username'], "Deleted user: {$user['first_name']} {$user['last_name']}");
        } else {
            throw new Exception('Failed to delete user.');
        }
    }

    /**
     * Toggle user active status
     */
    public function toggleStatus(int $id, int $adminId, ?int $campusIdFilter): string
    {
        $user = $this->userRepo->getUserById($id);
        if (!$user) throw new Exception('User not found.');

        if ($campusIdFilter !== null && $user['campus_id'] != $campusIdFilter) {
            throw new Exception('Unauthorized: User belongs to another campus.');
        }

        if (strtolower($user['role']) === 'superadmin') {
            throw new Exception('Superadmin status cannot be changed.');
        }

        $this->userRepo->toggleUserStatus($id);
        $updatedUser = $this->userRepo->getUserById($id);
        $newStatus = $updatedUser['is_active'] ? 'Active' : 'Inactive';

        $this->auditRepo->log($adminId, 'TOGGLE_STATUS', 'USERS', $user['username'], "Set status of {$user['first_name']} {$user['last_name']} to $newStatus");
        
        return $newStatus;
    }

    /**
     * Bulk import students from CSV
     */
    public function bulkImport(string $filePath, int $adminId): array
    {
        $courseRepo = new CollegeCourseRepository();
        $courseMap = [];
        foreach ($courseRepo->getAllCourses() as $c) {
            $courseMap[strtoupper(trim($c['course_code']))] = $c['course_id'];
        }

        $campusMap = [];
        foreach ($this->campusRepo->getAllCampuses() as $cp) {
            $campusMap[strtoupper(trim($cp['campus_name']))] = $cp['campus_id'];
        }

        $existingUsernames = array_flip($this->userRepo->getAllUsernamesMap());
        $imported = 0;
        $errors = [];
        $batchSize = 500;
        $usersBuffer = [];
        $studentDataBuffer = [];
        $defaultPassword = password_hash('12345', PASSWORD_DEFAULT);
        $timestamp = date('Y-m-d H:i:s');

        if (($handle = fopen($filePath, 'r')) === false) {
            throw new Exception('Failed to open uploaded file.');
        }

        $header = array_map('strtolower', array_map('trim', fgetcsv($handle)));
        $rowNumber = 2;

        $db = $this->userRepo->getDbConnection();
        $db->beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                if (count($header) !== count($row)) {
                    $rowNumber++; continue;
                }
                $data = array_combine($header, array_pad($row, count($header), ''));

                $firstName = trim($data['first_name'] ?? '');
                $lastName = trim($data['last_name'] ?? '');
                $studentId = trim($data['student_number'] ?? '');
                $courseCode = strtoupper(trim($data['course_code'] ?? ''));
                $campusInput = strtoupper(trim($data['campus'] ?? ''));

                if ($studentId === '' || $firstName === '') {
                    $errors[] = "Row $rowNumber: Skip - Missing name or student number.";
                    $rowNumber++; continue;
                }

                if (isset($existingUsernames[$studentId])) {
                    $errors[] = "Row $rowNumber: Skip - Student ID ($studentId) already exists.";
                    $rowNumber++; continue;
                }

                $usersBuffer[] = [
                    'username' => $studentId,
                    'password' => $defaultPassword,
                    'first_name' => $firstName,
                    'middle_name' => null,
                    'last_name' => $lastName,
                    'email' => !empty($data['email']) ? trim($data['email']) : null,
                    'role' => 'Student',
                    'is_active' => 1,
                    'created_at' => $timestamp,
                    'campus_id' => $campusMap[$campusInput] ?? null,
                ];

                $studentDataBuffer[] = [
                    'student_number' => $studentId,
                    'course_id' => $courseMap[$courseCode] ?? null,
                    'year_level' => 1,
                    'status' => 'enrolled',
                    'contact' => trim($data['contact'] ?? 'N/A'),
                    'section' => 'N/A',
                ];

                if (count($usersBuffer) >= $batchSize) {
                    $this->processBatch($usersBuffer, $studentDataBuffer);
                    $imported += count($usersBuffer);
                    $usersBuffer = []; $studentDataBuffer = [];
                }
                $rowNumber++;
            }

            if (!empty($usersBuffer)) {
                $this->processBatch($usersBuffer, $studentDataBuffer);
                $imported += count($usersBuffer);
            }

            $db->commit();
            $this->auditRepo->log($adminId, 'BULK_IMPORT', 'USERS', null, "Bulk imported $imported students.");
            return ['imported' => $imported, 'errors' => $errors];
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        } finally {
            fclose($handle);
        }
    }

    private function processBatch(array $users, array $students): void
    {
        $this->userRepo->bulkInsertUsers($users);
        $usernames = array_column($users, 'username');
        $placeholders = implode(',', array_fill(0, count($usernames), '?'));
        $db = $this->userRepo->getDbConnection();
        $stmt = $db->prepare("SELECT username, user_id FROM users WHERE username IN ($placeholders)");
        $stmt->execute($usernames);
        $userMap = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

        $finalStudentsBatch = [];
        foreach ($students as $s) {
            if (isset($userMap[$s['student_number']])) {
                $s['user_id'] = $userMap[$s['student_number']];
                $finalStudentsBatch[] = $s;
            }
        }

        if (!empty($finalStudentsBatch)) {
            $this->studentRepo->bulkInsertStudentDetails($finalStudentsBatch);
        }
    }

    /**
     * Update user details and role-specific data
     */
    public function updateUser(int $id, array $data, int $adminId, ?int $campusIdFilter): void
    {
        $db = $this->userRepo->getDbConnection();
        $db->beginTransaction();

        try {
            $currentUser = $this->userRepo->getUserById($id);
            if (!$currentUser) throw new Exception('User not found.');

            if ($campusIdFilter !== null && $currentUser['campus_id'] != $campusIdFilter) {
                throw new Exception('Unauthorized: User belongs to another campus.');
            }

            $modulesPayload = $data['modules'] ?? null;
            $modulesKeyWasPresent = array_key_exists('modules', $data);
            unset($data['modules'], $data['role'], $data['user_id']);

            // Normalize campus_id for Global Admins
            if (array_key_exists('campus_id', $data) && (empty($data['campus_id']) || $data['campus_id'] == 0)) {
                $data['campus_id'] = null;
            }

            if (!empty($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            } else {
                unset($data['password']);
            }

            // Handle role-specific profile updates
            $currentRole = strtolower($currentUser['role'] ?? '');
            if ($currentRole === 'student') {
                $studentData = array_intersect_key($data, array_flip(['course_id', 'year_level', 'section']));
                if (!empty($studentData)) {
                    (new StudentProfileRepository())->updateStudentProfile($id, $studentData);
                }
                unset($data['course_id'], $data['year_level'], $data['section']);
            }

            $this->userRepo->updateUser($id, $data);

            if (in_array($currentRole, ['superadmin', 'admin', 'librarian']) && $modulesKeyWasPresent) {
                $this->userPermissionRepo->assignModules($id, (array)$modulesPayload);
            }

            $details = "Updated details/permissions for {$currentUser['first_name']} {$currentUser['last_name']}";
            if (!empty($data['password'])) $details .= " (Password was reset)";
            
            $this->auditRepo->log($adminId, 'UPDATE', 'USERS', $currentUser['username'], $details);
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Grant edit access to student profile
     */
    public function setEditAccess(int $id, bool $allow): bool
    {
        return (new StudentProfileRepository())->setEditAccess($id, $allow);
    }

    /**
     * Handle bulk deletion with approval logic
     */
    public function bulkDelete(array $userIds, ?string $reason, int $adminId, string $adminRole): array
    {
        if (empty($userIds)) throw new Exception('No user IDs provided.');

        if ($adminRole === 'admin' && count($userIds) >= 5) {
            $requestId = (new BulkDeleteRepository())->createRequest($adminId, $userIds, 'users', $reason);
            $this->auditRepo->log($adminId, 'REQUEST_BULK_DELETE', 'USERS', $requestId, "Requested bulk delete for " . count($userIds) . " users. Reason: $reason");
            
            return [
                'requires_approval' => true,
                'message' => 'Bulk delete request has been submitted for Superadmin approval.',
                'request_id' => $requestId
            ];
        }

        $deletedCount = 0;
        $errors = [];

        foreach ($userIds as $id) {
            try {
                $this->deleteUser((int)$id, $adminId, null);
                $deletedCount++;
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        return [
            'requires_approval' => false,
            'deleted_count' => $deletedCount,
            'errors' => $errors
        ];
    }

    /**
     * Search users by keyword
     */
    public function searchUsers(string $query, ?int $campusId = null): array
    {
        if (empty($query)) {
            return $this->userRepo->getPaginatedUsers(100, 0, '', 'All Roles', 'All Status', null, $campusId);
        }
        return $this->userRepo->searchUsers($query, $campusId);
    }

    /**
     * Get all users
     */
    public function getAllUsers(?int $campusId = null): array
    {
        return $this->userRepo->getPaginatedUsers(1000, 0, '', 'All Roles', 'All Status', null, $campusId);
    }
}
