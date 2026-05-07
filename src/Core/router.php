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
    $uri = trim($uri, '/');

    if (!isset($this->routes[$method])) {
      http_response_code(404);
      include ROOT_PATH . '/src/Views/errors/404.php';
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

        if (!empty($allowedAccess)) {
          $userRole = $_SESSION['role'] ?? 'guest';
          $userPermissions = $_SESSION['user_permissions'] ?? [];
          $userId = $_SESSION['user_id'] ?? null;

          if (!$userId) {
            http_response_code(403);
            include ROOT_PATH . '/src/Views/errors/403.php';
            return;
          }

          try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT campus_id, is_active FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            $currentData = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($currentData) {
              if (isset($_SESSION['user_data']) && $_SESSION['user_data']['campus_id'] != $currentData['campus_id']) {
                $_SESSION['user_data']['campus_id'] = $currentData['campus_id'];
              }
              if ($currentData['is_active'] == 0 && !RoleHelper::isSuperadmin($userRole)) {
                session_destroy();
                header('Location: /login');
                exit;
              }
            }
          } catch (\Exception $e) {
            error_log("Session Sync Error: " . $e->getMessage());
          }

          if (!RoleHelper::hasAccess($userRole, $userPermissions, $allowedAccess)) {
            http_response_code(403);
            include ROOT_PATH . '/src/Views/errors/403.php';
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

          return $controllerInstance->$methodName(...$matches);
        } catch (\Throwable $e) {
          error_log("Router error: " . $e->getMessage());

          if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
            die("<h1>Application Error (Debug Mode)</h1><p>" . $e->getMessage() . "</p><pre>" . $e->getTraceAsString() . "</pre>");
          }

          http_response_code(500);
          $error_view = ROOT_PATH . '/src/Views/errors/500.php';
          if (file_exists($error_view)) {
            include $error_view;
          } else {
            echo "<h1>500 Internal Server Error</h1><p>Something went wrong on our end.</p>";
          }
          exit;
        }
      }
    }

    http_response_code(404);
    include ROOT_PATH . '/src/Views/errors/404.php';
  }
}