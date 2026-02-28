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
    $this->backupRepo = new BackupRepository();
    $this->auditRepo = new \App\Repositories\AuditLogRepository();
  }

  private function getBackupDir(): string
  {
    $dir = __DIR__ . '/../../backups/';
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    return $dir;
  }

  private function exportToCsv(string $tableName): string
  {
    $data = $this->backupRepo->getAllTableData($tableName);
    $output = fopen('php://temp', 'r+');

    fputcsv($output, $data['columns']);
    foreach ($data['data'] as $row) fputcsv($output, $row);

    rewind($output);
    $csvContent = stream_get_contents($output);
    fclose($output);

    return $csvContent;
  }

  private function exportToSql(string $tableName): string
  {
    return $this->backupRepo->exportTableSqlDump($tableName);
  }

  public function initiateBackup()
  {
    if (session_status() === PHP_SESSION_NONE) session_start();

    try {
      $dbName = getenv('DB_DATABASE') ?: 'Unknown Database';
      $tables = $this->backupRepo->getAllTableNames();
      $sqlContent = "-- Full Database Backup: $dbName\n\n";

      foreach ($tables as $table) {
        $sqlContent .= $this->backupRepo->exportTableSqlDump($table);
      }

      $filename = "full_backup_" . date('Ymd_His') . ".sql";
      $path = $this->getBackupDir() . $filename;
      file_put_contents($path, $sqlContent);

      $size = round(filesize($path) / 1024 / 1024, 2) . ' MB';
      $this->backupRepo->logBackup($filename, 'SQL', $_SESSION['user_name'] ?? 'Admin', $size);
      $this->auditRepo->log($_SESSION['user_id'], 'BACKUP', 'SYSTEM', $filename, "Full database backup initiated ($size)");

      echo json_encode(['success' => true, 'filename' => $filename]);
    } catch (\Throwable $e) {
      http_response_code(500);
      exit(json_encode(['success' => false, 'message' => $e->getMessage()]));
    }
  }

  public function exportBothFormats(string $tableName)
  {
    if (session_status() === PHP_SESSION_NONE) session_start();

    $allowedTables = ['users', 'students', 'books', 'borrow_transactions', 'deleted_books', 'deleted_students'];
    if (!in_array($tableName, $allowedTables)) {
      http_response_code(400);
      exit(json_encode(['success' => false, 'message' => "Invalid table name."]));
    }

    $baseName = strtolower($tableName);
    $timestamp = date('Ymd_His');
    $zipFilename = "export_{$baseName}_{$timestamp}.zip";
    $zipPath = $this->getBackupDir() . $zipFilename;

    $zip = new ZipArchive();
    $opened = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    if ($opened !== true) {
      http_response_code(500);
      exit(json_encode(['success' => false, 'message' => "Failed to create zip file. Error code: $opened"]));
    }

    try {
      $zip->addFromString("{$baseName}_data.csv", $this->exportToCsv($tableName));
      $zip->addFromString("{$baseName}_data.sql", $this->exportToSql($tableName));
      $zip->close();

      $this->backupRepo->logBackup($zipFilename, 'ZIP', $_SESSION['user_id']);
      $this->auditRepo->log($_SESSION['user_id'], 'EXPORT', 'SYSTEM', $zipFilename, "Exported table data for: $tableName");

      echo json_encode(['success' => true, 'filename' => $zipFilename]);
    } catch (\Throwable $e) {
      if (file_exists($zipPath)) unlink($zipPath);
      http_response_code(500);
      exit(json_encode(['success' => false, 'message' => "Error during ZIP creation/export: " . $e->getMessage()]));
    }
  }

  public function downloadBackup(string $filename)
  {
    if (session_status() === PHP_SESSION_NONE) session_start();

    $filePath = $this->getBackupDir() . basename($filename);
    if (!file_exists($filePath) || !is_readable($filePath)) {
      http_response_code(404);
      exit("File not found.");
    }

    $mimeType = mime_content_type($filePath);
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Pragma: public');
    header('Cache-Control: must-revalidate');

    readfile($filePath);
    exit;
  }

  public function listBackupLogs()
  {
    if (session_status() === PHP_SESSION_NONE) session_start();
    header('Content-Type: application/json');

    try {
      $logs = $this->backupRepo->getAllBackupLogs();
      echo json_encode(['success' => true, 'logs' => $logs]);
    } catch (\Throwable $e) {
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
  }
}
