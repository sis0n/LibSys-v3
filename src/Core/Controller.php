<?php

namespace App\Core;

use App\Repositories\UserRepository;

class Controller
{
    private $UserRepository;



    public function __construct()
    {
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
    /**
     * Render a view with optional header/footer layout
     *
     * @param string $view  View file path (relative to Views folder, without .php)
     * @param array  $data  Data to extract into the view
     * @param void   $withLayout Include header/footer (default: true)
     */
    // File: App/Core/Controller.php (Updated view method)

    public function view(string $view, array $data = [], bool $withLayout = true): void
    {
        extract($data, EXTR_SKIP);

        $basePath = __DIR__ . "/../Views/";

        $head = $basePath . "partials/head.php";
        $sidebar = $basePath . "partials/sidebar.php";
        $header = $basePath . "partials/header.php";
        $footer = $basePath . "partials/footer.php";
        $viewPath = $basePath . $view . ".php";


        // Check if the current view is the scanner view
        $isScannerView = (strpos($view, 'scanner/attendance') !== false);

        if ($withLayout && !$isScannerView) {
            // --- STANDARD LAYOUT (With Sidebar) ---
            if (file_exists($head)) {
                include $head;
            }
            echo '<body class="bg-gray-50 font-sans min-h-screen flex">';
            echo '<div class="flex min-h-screen w-full">';

            // Sidebar
            if (file_exists($sidebar)) {
                include $sidebar;
            }

            // Right side (header + content + footer)
            echo '<div class="flex-1 flex flex-col">';

            // Header
            if (file_exists($header)) {
                include $header;
            }

            // Main Content (expandable)
            echo '<main class="flex-1 p-6">';
            if (file_exists($viewPath)) {
                include $viewPath;
            } else {
                http_response_code(404);
                include $basePath . "errors/404.php";
            }
            echo '</main>';

            // Footer (push to bottom)
            if (file_exists($footer)) {
                echo '<div class="mt-auto">';
                include $footer;
                echo '</div>';
            }

            echo '</div>'; // close right side
            echo '</div>'; // close flex wrapper
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