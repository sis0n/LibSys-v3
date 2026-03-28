<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

class UserPermissionModuleRepository
{
  private $db;

  public function __construct()
  {
    $this->db = Database::getInstance()->getConnection();
  }

  public function getModulesByUserId(int $userId): array
  {
    try {
      $stmt = $this->db->prepare("
                SELECT module_name
                FROM user_module_permissions
                WHERE user_id = :user_id
            ");
      $stmt->execute([':user_id' => $userId]);
      $modules = $stmt->fetchAll(PDO::FETCH_COLUMN);
      return $modules ?: [];
    } catch (PDOException $e) {
      error_log("[UserModulePermissionRepository::getModulesByUserId] " . $e->getMessage());
      return [];
    }
  }

  public function deleteModules(int $userId): bool
  {
    try {
      $stmt = $this->db->prepare("
                DELETE FROM user_module_permissions
                WHERE user_id = :user_id
            ");
      return $stmt->execute([':user_id' => $userId]);
    } catch (PDOException $e) {
      error_log("[UserModulePermissionRepository::deleteModules] " . $e->getMessage());
      return false;
    }
  }

  public function assignModules(int $userId, array $modules): bool
  {
    $transactionStarted = false;
    try {
      if (!$this->db->inTransaction()) {
        $this->db->beginTransaction();
        $transactionStarted = true;
      }

      $this->deleteModules($userId);

      $stmt = $this->db->prepare("
                INSERT INTO user_module_permissions (user_id, module_name)
                VALUES (:user_id, :module_name)
            ");

      foreach ($modules as $module) {
        $stmt->execute([
          ':user_id' => $userId,
          ':module_name' => $module
        ]);
      }

      if ($transactionStarted) {
        $this->db->commit();
      }
      return true;
    } catch (PDOException $e) {
      if ($transactionStarted) {
        $this->db->rollBack();
      }
      error_log("[UserModulePermissionRepository::assignModules] " . $e->getMessage());
      return false;
    }
  }

  public function hasAccess(int $userId, string $moduleName): bool
  {
    try {
      $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM user_module_permissions
                WHERE user_id = :user_id AND module_name = :module_name
            ");
      $stmt->execute([
        ':user_id' => $userId,
        ':module_name' => $moduleName
      ]);
      return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
      error_log("[UserModulePermissionRepository::hasAccess] " . $e->getMessage());
      return false;
    }
  }
}
