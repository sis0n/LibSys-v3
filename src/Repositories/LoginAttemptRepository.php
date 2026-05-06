<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use Exception;

class LoginAttemptRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function recordAttempt(string $username): void
    {
        try {
            $ip = $this->getIpAddress();
            $stmt = $this->db->prepare("INSERT INTO login_attempts (username, ip_address, attempted_at) VALUES (:username, :ip, NOW())");
            $stmt->execute([':username' => $username, ':ip' => $ip]);
        } catch (Exception $e) {
            error_log("LoginAttemptRepository::recordAttempt Error: " . $e->getMessage());
        }
    }

    public function countAttempts(string $username): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM login_attempts 
                WHERE username = :username 
                AND attempted_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute([':username' => $username]);
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("LoginAttemptRepository::countAttempts Error: " . $e->getMessage());
            return 0;
        }
    }

    public function countAttemptsByIp(string $ip): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM login_attempts 
                WHERE ip_address = :ip 
                AND attempted_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $stmt->execute([':ip' => $ip]);
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("LoginAttemptRepository::countAttemptsByIp Error: " . $e->getMessage());
            return 0;
        }
    }

    public function getLastAttempt(string $username): ?string
    {
        try {
            $stmt = $this->db->prepare("
                SELECT attempted_at 
                FROM login_attempts 
                WHERE username = :username 
                ORDER BY id DESC 
                LIMIT 1
            ");
            $stmt->execute([':username' => $username]);
            $result = $stmt->fetchColumn();
            return $result ?: null;
        } catch (Exception $e) {
            error_log("LoginAttemptRepository::getLastAttempt Error: " . $e->getMessage());
            return null;
        }
    }

    public function getLastAttemptByIp(string $ip): ?string
    {
        try {
            $stmt = $this->db->prepare("
                SELECT attempted_at 
                FROM login_attempts 
                WHERE ip_address = :ip 
                AND attempted_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                ORDER BY id DESC 
                LIMIT 1
            ");
            $stmt->execute([':ip' => $ip]);
            $result = $stmt->fetchColumn();
            return $result ?: null;
        } catch (Exception $e) {
            error_log("LoginAttemptRepository::getLastAttemptByIp Error: " . $e->getMessage());
            return null;
        }
    }

    public function getIpAddress(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function getCurrentDatabaseTime(): string
    {
        try {
            return $this->db->query("SELECT NOW()")->fetchColumn();
        } catch (Exception $e) {
            return date('Y-m-d H:i:s');
        }
    }

    public function clearAttempts(string $username): void
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM login_attempts WHERE username = :username");
            $stmt->execute([':username' => $username]);
        } catch (Exception $e) {
            error_log("LoginAttemptRepository::clearAttempts Error: " . $e->getMessage());
        }
    }
}
