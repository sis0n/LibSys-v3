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

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        if (isset($_SESSION['user_id'])) {
            $this->UserRepository = new UserRepository();
            $currentUser = $this->UserRepository->getUserById($_SESSION['user_id']);

            if (!$currentUser || !$currentUser['is_active']) {
                session_unset();
                session_destroy();
                header("Location: " . BASE_URL . "/login?error=deactivated");
                exit;
            }
        }
    }

    protected function getCampusFilter(): ?int
    {
        $role = strtolower($_SESSION['role'] ?? '');
        if (in_array($role, ['campus_admin', 'librarian'])) {
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
}
