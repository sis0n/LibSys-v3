<?php

namespace App\Core;

class Router
{
  protected array $routes = [];

  public function get(string $uri, string $controller, array $roles = []): void
  {
    $this->routes['GET'][$uri] = [
      'controller' => $controller,
      'roles' => $roles
    ];
  }

  public function post(string $uri, string $controller, array $roles = []): void
  {
    $this->routes['POST'][$uri] = [
      'controller' => $controller,
      'roles' => $roles
    ];
  }

  public function resolve(string $uri, string $method)
  {
    // Tiyakin na walang leading/trailing slash
    $uri = trim($uri, '/');

    if (!isset($this->routes[$method])) {
      http_response_code(404);
      include __DIR__ . '/../Views/errors/404.php';
      return;
    }

    foreach ($this->routes[$method] as $route => $info) {

      $pattern = preg_quote($route, '#');

      $pattern = preg_replace('/\\\{[^}]+\\\}/', '([^/]+)', $pattern);

      $pattern = "#^" . trim($pattern, '/') . "$#";

      if (preg_match($pattern, $uri, $matches)) {
        array_shift($matches);

        $controller = $info['controller'];
        $allowedAccess = $info['roles'];

        // --- HYBRID AUTHORIZATION CHECK ---
        if (!empty($allowedAccess)) {
          $userRole = strtolower(str_replace([' ', '-'], '_', $_SESSION['role'] ?? ''));
          $userId = $_SESSION['user_id'] ?? null;

          if (!$userId) {
            http_response_code(403);
            include __DIR__ . '/../Views/errors/403.php';
            return;
          }

          // --- SESSION SYNC CHECK ---
          // Ensure that if a Superadmin changes a user's campus, the session reflects it immediately.
          try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT campus_id, is_active FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            $currentData = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($currentData) {
              // Update campus_id if it changed
              if (isset($_SESSION['user_data']) && $_SESSION['user_data']['campus_id'] != $currentData['campus_id']) {
                $_SESSION['user_data']['campus_id'] = $currentData['campus_id'];
              }
              
              // Logout if deactivated
              if ($currentData['is_active'] == 0 && $userRole !== 'superadmin') {
                session_destroy();
                header('Location: /login');
                exit;
              }
            }
          } catch (\Exception $e) {
            error_log("Session Sync Error: " . $e->getMessage());
          }

          $hasAccess = false;
          $allowedAccessNormalized = array_map(function($role) {
              return strtolower(str_replace([' ', '-'], '_', $role));
          }, $allowedAccess);

          // Superadmin has access to everything
          if ($userRole === 'superadmin') {
            $hasAccess = true;
          } 
          // Check if direct role matches
          elseif (in_array($userRole, $allowedAccessNormalized)) {
            $hasAccess = true;
          } 
          // Check module permissions for specific roles
          elseif (in_array($userRole, ['admin', 'librarian', 'campus_admin'])) {
            $userPermissions = $_SESSION['user_permissions'] ?? [];
            $normalizedUserPermissions = array_map('strtolower', $userPermissions);

            $matches_permission = array_intersect($normalizedUserPermissions, $allowedAccessNormalized);
            if (count($matches_permission) > 0) {
              $hasAccess = true;
            }
          }

          if (!$hasAccess) {
            http_response_code(403);
            include __DIR__ . '/../Views/errors/403.php';
            return;
          }
        }

        [$controllerName, $methodName] = explode('@', $controller);
        $controllerClass = "App\\Controllers\\$controllerName";

        try {
          if (!class_exists($controllerClass)) {
            throw new \Exception("Controller $controllerClass not found");
          }

          $controllerInstance = new $controllerClass();

          if (!method_exists($controllerInstance, $methodName)) {
            throw new \Exception("Method $methodName not found in $controllerClass");
          }

          // Pass dynamic params (like $id)
          return $controllerInstance->$methodName(...$matches);
        } catch (\Throwable $e) {
          error_log("Router error: " . $e->getMessage());

          if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
            die("<h1>Application Error (Debug Mode)</h1><p>" . $e->getMessage() . "</p><pre>" . $e->getTraceAsString() . "</pre>");
          }

          http_response_code(500);
          $error_view = __DIR__ . '/../Views/errors/500.php';
          if (file_exists($error_view)) {
            include $error_view;
          } else {
            echo "<h1>500 Internal Server Error</h1><p>Something went wrong on our end.</p>";
          }
          exit;
        }
      }
    }

    // No route matched
    http_response_code(404);
    include __DIR__ . '/../Views/errors/404.php';
  }
}