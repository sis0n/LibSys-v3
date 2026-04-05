<?php

namespace App\Core;

use App\Repositories\UserRepository;

class Controller
{
    private $UserRepository;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Refresh permissions from DB on every request to avoid "stale" session data
        if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
            $role = strtolower($_SESSION['role']);
            if (in_array($role, ['admin', 'librarian', 'superadmin', 'campus_admin', 'campus admin'])) {
                $userPermissionRepo = new \App\Repositories\UserPermissionModuleRepository();
                $_SESSION['user_permissions'] = $userPermissionRepo->getModulesByUserId($_SESSION['user_id']);
            }
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        if (isset($_SESSION['user_id'])) {
            $this->UserRepository = new UserRepository();
            $currentUser = $this->UserRepository->getUserById($_SESSION['user_id']);

            if (!$currentUser || !$currentUser['is_active']) {
                session_unset();
                session_destroy();
                
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    http_response_code(401);
                    echo json_encode(['success' => false, 'message' => 'Session expired or account deactivated.']);
                    exit;
                }

                header("Location: " . \BASE_URL . "/login?error=deactivated");
                exit;
            }
        }
    }

    protected function getCampusFilter(): ?int
    {
        $role = strtolower(trim(str_replace([' ', '-', '_'], '', $_SESSION['role'] ?? '')));
        
        // Superadmin and Admin have global access (Global/All Campuses)
        if (in_array($role, ['superadmin', 'admin'])) {
            return null;
        }

        // Campus Admin and Librarian are restricted to their own campus
        if (in_array($role, ['campusadmin', 'librarian'])) {
            return $_SESSION['user_data']['campus_id'] ?? null;
        }

        return null;
    }

    protected function validateCsrf(): bool
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return !empty($token) && hash_equals($_SESSION['csrf_token'], $token);
    }

    protected function sanitize($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    protected function getPostData(): array
    {
        return $this->sanitize($_POST);
    }

    public function view(string $view, array $data = [], bool $withLayout = true): void
    {
        extract($data, EXTR_SKIP);

        $basePath = __DIR__ . "/../Views/";

        $head = $basePath . "partials/head.php";
        $sidebar = $basePath . "partials/sidebar.php";
        $header = $basePath . "partials/header.php";
        $footer = $basePath . "partials/footer.php";
        $viewPath = $basePath . $view . ".php";

        $isScannerView = (strpos($view, 'scanner/attendance') !== false);

        if ($withLayout && !$isScannerView) {
            if (file_exists($head)) {
                include $head;
            }
            echo '<body class="bg-gray-50 font-sans min-h-screen flex">';
            echo '<div class="flex min-h-screen w-full">';

            if (file_exists($sidebar)) {
                include $sidebar;
            }

            echo '<div class="flex-1 flex flex-col">';

            if (file_exists($header)) {
                include $header;
            }

            echo '<main class="flex-1 p-6">';
            if (file_exists($viewPath)) {
                include $viewPath;
            } else {
                http_response_code(404);
                include $basePath . "errors/404.php";
            }
            echo '</main>';

            if (file_exists($footer)) {
                echo '<div class="mt-auto">';
                include $footer;
                echo '</div>';
            }

            echo '</div>';
            echo '</div>';
            echo '</body>';
        } else {
            if (file_exists($viewPath)) {
                include $viewPath;
            } else {
                http_response_code(404);
                include $basePath . "errors/404.php";
            }
        }
    }

    protected function json($data, int $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function saveFileLocally($file, $subFolder, $prefix = 'profile')
    {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $uniqueId = uniqid();
        $fileName = "{$prefix}_{$uniqueId}.{$extension}";

        $uploadDir = ROOT_PATH . "/public/storage/uploads/{$subFolder}/";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $destPath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            return "storage/uploads/{$subFolder}/" . $fileName;
        }

        return null;
    }

    protected function validateImageUpload($file)
    {
        $maxSize = 2 * 1024 * 1024; // 2MB
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if ($file['error'] !== UPLOAD_ERR_OK) return "Upload error.";
        if ($file['size'] > $maxSize) return "Image must be less than 2MB.";

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowedTypes)) return "Invalid image type. Only JPG, PNG, GIF, WEBP allowed.";
        return true;
    }

    protected function validatePDFUpload($file)
    {
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['error'] !== UPLOAD_ERR_OK) return "Upload error.";
        if ($file['size'] > $maxSize) return "PDF must be less than 5MB.";
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if ($mime !== 'application/pdf') return "Invalid file type. Only PDF allowed.";
        return true;
    }
}
