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

    /**
     * Record a failed login attempt
     */
    public function recordAttempt(string $username): void
    {
        try {
            // Very basic insert. attempted_at will use DEFAULT current_timestamp()
            $stmt = $this->db->prepare("INSERT INTO login_attempts (username) VALUES (:username)");
            $stmt->execute([':username' => $username]);
        } catch (Exception $e) {
            error_log("LoginAttemptRepository::recordAttempt Error: " . $e->getMessage());
        }
    }

    /**
     * Count failed attempts within 15 minutes
     */
    public function countAttempts(string $username, int $minutesWindow = 15): int
    {
        try {
            // Using a simple numeric comparison if possible, or very standard INTERVAL
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM login_attempts 
                WHERE username = :username 
                AND attempted_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            ");
            $stmt->execute([':username' => $username]);
            $count = (int)$stmt->fetchColumn();
            
            return $count;
        } catch (Exception $e) {
            error_log("LoginAttemptRepository::countAttempts Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Clear all failed attempts for a username
     */
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
