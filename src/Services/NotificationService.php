<?php

namespace App\Services;

use App\Services\MailService;

class NotificationService
{
    private MailService $mailService;

    public function __construct()
    {
        $this->mailService = new MailService();
    }

    /**
     * Send an email notification
     */
    public function sendEmail(string $to, string $subject, string $body): bool
    {
        return $this->mailService->sendEmail($to, $subject, $body);
    }

    /**
     * Send overdue notice via email
     */
    public function sendOverdueNotice(string $to, string $name, string $bookTitle, string $dueDate): bool
    {
        return $this->mailService->sendOverdueNotice($to, $name, $bookTitle, $dueDate);
    }

    /**
     * Placeholder for SMS notification
     */
    public function sendSMS(string $phone, string $message): bool
    {
        // Integration with SMS gateway would go here
        error_log("SMS to $phone: $message");
        return true; 
    }

    /**
     * Placeholder for In-App notification
     */
    public function sendInApp(int $userId, string $title, string $message): bool
    {
        // Logic to insert into a notifications table would go here
        error_log("In-App Notification to User ID $userId: $title - $message");
        return true;
    }
}
