<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\BackupRepository;
use ZipArchive;

class BackupController extends Controller
{
  protected BackupRepository $backupRepo;
  protected \App\Repositories\AuditLogRepository $auditRepo;

  public function __construct()
  {
    parent::__construct();
    $role = strtolower(str_replace([' ', '-'], '_', $_SESSION['role'] ?? ''));
    if ($role !== 'superadmin') {
        http_response_code(403);
        die("Forbidden: Access restricted to Superadmin only.");
    }
    $this->backupRepo = new BackupRepository();
    $this->auditRepo = new \App\Repositories\AuditLogRepository();
  }

  private function getBackupDir(): string
  {
    $dir = __DIR__ . '/../../backups/';
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    return $dir;
  }

  public function initiateBackup()
  {
    set_time_limit(0); // Prevent timeout for large backups

    $dbName = $_ENV['DB_DATABASE'] ?? 'Database';
    $filename = "full_backup_" . date('Ymd_His') . ".sql.gz";
    $path = $this->getBackupDir() . $filename;

    // Use GZIP compression
    $zp = gzopen($path, 'w9');
    if (!$zp) {
      $this->errorResponse("Failed to create compressed backup file.", 500);
    }

    try {
      gzwrite($zp, "-- Full Database Backup: $dbName\n");
      gzwrite($zp, "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n");

      $tables = $this->backupRepo->getAllTableNames();
      foreach ($tables as $table) {
        $this->backupRepo->exportTableSql($table, function ($data) use ($zp) {
          gzwrite($zp, $data);
        });
      }
      gzclose($zp);

      $sizeBytes = filesize($path);
      $size = round($sizeBytes / 1024 / 1024, 2) . ' MB';
      
      $this->backupRepo->logBackup($filename, 'SQL.GZ', $_SESSION['user_id'] ?? 'Admin', $size);
      $this->auditRepo->log($_SESSION['user_id'] ?? 0, 'BACKUP', 'SYSTEM', $filename, "Full database backup compressed ($size)");

      $this->jsonResponse(['filename' => $filename, 'size' => $size]);
    } catch (\Throwable $e) {
      if (isset($zp)) gzclose($zp);
      if (file_exists($path)) unlink($path);
      $this->errorResponse($e->getMessage(), 500);
    }
  }

  public function exportBothFormats(string $tableName)
  {
    set_time_limit(0);

    $allowedTables = [
      'users', 'students', 'staff', 'faculty', 'guests',
      'books', 'deleted_books', 'colleges', 'courses',
      'borrow_transactions', 'borrow_transaction_items', 'borrowings', 'manual_borrowers',
      'attendance', 'attendance_logs', 'audit_logs',
      'equipments', 'collaterals',
      'library_policies', 'reports',
      'user_permissions', 'user_module_permissions'
    ];
    if (!in_array($tableName, $allowedTables)) {
      $this->errorResponse("Invalid table name.", 400);
    }

    $baseName = strtolower($tableName);
    $timestamp = date('Ymd_His');
    $zipFilename = "export_{$baseName}_{$timestamp}.zip";
    $zipPath = $this->getBackupDir() . $zipFilename;

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
      $this->errorResponse("Failed to create ZIP file.", 500);
    }

    try {
      // 1. Export SQL to string (table specific is usually small enough for memory, but let's be safe)
      $sqlBuffer = "";
      $this->backupRepo->exportTableSql($tableName, function($data) use (&$sqlBuffer) {
        $sqlBuffer .= $data;
      });
      $zip->addFromString("{$baseName}.sql", $sqlBuffer);

      // 2. Export CSV
      $csvOutput = fopen('php://temp', 'r+');
      $this->backupRepo->exportTableCsv($tableName, function($row) use ($csvOutput) {
        fputcsv($csvOutput, $row);
      });
      rewind($csvOutput);
      $zip->addFromString("{$baseName}.csv", stream_get_contents($csvOutput));
      fclose($csvOutput);

      $zip->close();

      $this->backupRepo->logBackup($zipFilename, 'ZIP', $_SESSION['user_id'] ?? 'Admin');
      $this->auditRepo->log($_SESSION['user_id'] ?? 0, 'EXPORT', 'SYSTEM', $zipFilename, "Exported table: $tableName");

      $this->jsonResponse(['filename' => $zipFilename]);
    } catch (\Throwable $e) {
      if (file_exists($zipPath)) unlink($zipPath);
      $this->errorResponse("Error: " . $e->getMessage(), 500);
    }
  }

  public function downloadBackup(string $filename)
  {
    // Basic path traversal protection
    $filename = basename($filename);
    $filePath = $this->getBackupDir() . $filename;

    if (!file_exists($filePath) || !is_readable($filePath)) {
      http_response_code(404);
      exit("File not found.");
    }

    // Clear buffer to prevent corrupted downloads
    if (ob_get_level()) ob_end_clean();

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));

    readfile($filePath);
    exit;
  }

  public function listBackupLogs()
  {
    try {
      $logs = $this->backupRepo->getAllBackupLogs();
      $this->jsonResponse(['logs' => $logs]);
    } catch (\Throwable $e) {
      $this->errorResponse($e->getMessage(), 500);
    }
  }

  public function restoreBackup(string $filename)
  {
    set_time_limit(0);

    $filename = basename($filename);
    $filePath = $this->getBackupDir() . $filename;

    if (!file_exists($filePath)) {
      $this->errorResponse("Backup file not found.", 404);
    }

    try {
      $this->backupRepo->restoreDatabase($filePath);
      $this->auditRepo->log($_SESSION['user_id'] ?? 0, 'RESTORE', 'SYSTEM', $filename, "Database restored from local backup: $filename");
      $this->jsonResponse(['message' => "Database restored successfully!"]);
    } catch (\Throwable $e) {
      $this->errorResponse("Restore Error: " . $e->getMessage(), 500);
    }
  }

  public function deleteBackup(string $filename)
  {
    $filename = basename($filename);
    $filePath = $this->getBackupDir() . $filename;

    if (file_exists($filePath)) {
        unlink($filePath);
        $this->auditRepo->log($_SESSION['user_id'] ?? 0, 'DELETE_BACKUP', 'SYSTEM', $filename, "Backup file deleted: $filename");
        $this->jsonResponse(['message' => "Backup file deleted."]);
    } else {
        $this->errorResponse("File not found.", 404);
    }
  }

  public function uploadAndRestore()
  {
    set_time_limit(0);

    if (!isset($_FILES['backup_file'])) {
      $this->errorResponse("No file uploaded.", 400);
    }

    $file = $_FILES['backup_file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $isGzipped = str_ends_with(strtolower($file['name']), '.sql.gz');

    if ($ext !== 'sql' && !$isGzipped) {
      $this->errorResponse("Invalid file type. Only .sql or .sql.gz allowed.", 400);
    }

    $tempPath = $this->getBackupDir() . 'temp_restore_' . time() . '_' . basename($file['name']);
    
    if (move_uploaded_file($file['tmp_name'], $tempPath)) {
      try {
        $this->backupRepo->restoreDatabase($tempPath);
        unlink($tempPath); // Delete temp file after restore
        $this->auditRepo->log($_SESSION['user_id'] ?? 0, 'RESTORE', 'SYSTEM', $file['name'], "Database restored from uploaded file.");
        $this->jsonResponse(['message' => "Database restored successfully from uploaded file!"]);
      } catch (\Throwable $e) {
        if (file_exists($tempPath)) unlink($tempPath);
        $this->errorResponse("Restore Error: " . $e->getMessage(), 500);
      }
    } else {
      $this->errorResponse("Failed to save uploaded file.", 500);
    }
  }
}
