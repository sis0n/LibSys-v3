<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

class BackupRepository
{
  protected PDO $db;

  public function __construct()
  {
    $this->db = Database::getInstance()->getConnection();
  }

  /**
   * Streams table data as CSV rows using a callback.
   */
  public function exportTableCsv(string $tableName, callable $callback): void
  {
    try {
      $stmt = $this->db->query("DESCRIBE `$tableName`");
      $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
      $callback($columns);

      $this->db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
      $stmt = $this->db->query("SELECT * FROM `$tableName`");
      
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $callback($row);
      }
      $this->db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    } catch (PDOException $e) {
      throw new \Exception("CSV Export failed for $tableName: " . $e->getMessage());
    }
  }

  public function getAllTableNames(): array
  {
    try {
      $stmt = $this->db->query("SHOW TABLES");
      return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
      throw new \Exception("Failed to retrieve table names: " . $e->getMessage());
    }
  }

  /**
   * Streams table SQL dump (structure + data) using a callback.
   */
  public function exportTableSql(string $tableName, callable $callback): void
  {
    $db = $this->db;

    $callback("\n-- Table structure for table `$tableName`\n");
    $callback("DROP TABLE IF EXISTS `$tableName`;\n");
    $createStmt = $db->query("SHOW CREATE TABLE `$tableName` ");
    $row = $createStmt->fetch(PDO::FETCH_ASSOC);
    $callback($row['Create Table'] . ";\n\n");

    $callback("-- Dumping data for table `$tableName`\n");
    $db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
    $stmt = $db->query("SELECT * FROM `$tableName` ");
    
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
      $values = array_map(function ($val) use ($db) {
        return ($val === null) ? 'NULL' : $db->quote($val);
      }, $row);
      $callback("INSERT INTO `$tableName` VALUES (" . implode(', ', $values) . ");\n");
    }
    $db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    $callback("\n");
  }

  public function logBackup(string $fileName, string $fileType, string $createdBy, ?string $size = null)
  {
    $stmt = $this->db->prepare(
      "INSERT INTO backup_log (file_name, file_type, created_by, size, created_at) VALUES (:file_name, :file_type, :created_by, :size, NOW())"
    );
    $stmt->execute([
      ':file_name' => $fileName,
      ':file_type' => $fileType,
      ':created_by' => $createdBy,
      ':size' => $size
    ]);
  }

  public function getAllBackupLogs(): array
  {
    $stmt = $this->db->query("
            SELECT 
                bl.*, 
                IFNULL(CONCAT(u.first_name, ' ', u.last_name), 'Unknown User') AS created_by_name 
            FROM backup_log bl
            LEFT JOIN users u ON bl.created_by = u.user_id 
            ORDER BY bl.created_at DESC
        ");
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * Restores the database from a SQL file (supports .sql and .sql.gz)
   */
  public function restoreDatabase(string $filePath): void
  {
    $db = $this->db;
    $isGzipped = str_ends_with($filePath, '.gz');

    $fp = $isGzipped ? gzopen($filePath, 'rb') : fopen($filePath, 'r');
    if (!$fp) throw new \Exception("Cannot open backup file for restoration.");

    try {
      $db->exec("SET FOREIGN_KEY_CHECKS = 0;");

      $query = "";
      while (!($isGzipped ? gzeof($fp) : feof($fp))) {
        $line = $isGzipped ? gzgets($fp, 4096) : fgets($fp, 4096);
        
        if (trim($line) == "" || str_starts_with(trim($line), "--") || str_starts_with(trim($line), "/*")) {
          continue;
        }

        $query .= $line;

        if (str_ends_with(trim($line), ";")) {
          $db->exec($query);
          $query = "";
        }
      }

      $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
      $isGzipped ? gzclose($fp) : fclose($fp);
    } catch (\PDOException $e) {
      $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
      if ($fp) $isGzipped ? gzclose($fp) : fclose($fp);
      throw new \Exception("Restore failed: " . $e->getMessage());
    }
  }
}
