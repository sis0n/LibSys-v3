<?php
// Ilagay ang session_start sa pinaka-unahan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// [1] I-define ang ROOT_PATH
define('ROOT_PATH', dirname(__DIR__)); 

// [2] I-load ang Autoloader
require ROOT_PATH . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Config\RouteConfig;

date_default_timezone_set('Asia/Manila');

// [3] I-load ang .env
$dotenv = Dotenv::createImmutable(ROOT_PATH);
$dotenv->load();

// [4] I-define ang BASE_URL
if (!defined('BASE_URL')) {
    $appUrl = $_ENV['APP_URL'] ?? 'http://localhost';
    define('BASE_URL', rtrim($appUrl, '/')); 
}

// ---------------------------------------------------------------------
// --- FRONT CONTROLLER LOGIC: Mas Matibay na URI Calculation ---
// ---------------------------------------------------------------------

// 5. I-parse ang Full URI na galing sa server
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); // Hal: /libsys/public/api/attendance/logs/ajax

// 6. I-calculate ang BASE PATH na kailangan nating tanggalin
$baseUrlPath = parse_url(BASE_URL, PHP_URL_PATH); // Hal: /libsys/public
// Idagdag ang trailing slash para maging: /libsys/public/
$basePathToRemove = rtrim($baseUrlPath, '/') . '/'; 

// 7. [ANG AYOS AY DITO] Tanggalin ang BASE PATH mula sa URI
// Hal: /libsys/public/api/attendance/logs/ajax -> api/attendance/logs/ajax
$route = str_replace($basePathToRemove, '', $uri); 

// 8. Final Route Normalization
$route = trim($route, '/');
$route = $route === '' ? 'dashboard' : $route; 

// 9. I-resolve ang Router
$method = $_SERVER['REQUEST_METHOD'];
$router = RouteConfig::register();
$router->resolve($route, $method);