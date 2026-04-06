<?php

namespace App\Services;

use Exception;

class StorageService
{
    private string $uploadPath;
    private string $storageUrl;

    public function __construct()
    {
        $this->uploadPath = \PUBLIC_PATH . '/storage/uploads';
        $this->storageUrl = \STORAGE_URL;
    }

    /**
     * Save a file locally
     */
    public function saveFile(array $file, string $folder, string $prefix = ''): string
    {
        $targetDir = $this->uploadPath . '/' . ltrim($folder, '/');
        
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = $prefix . '_' . uniqid() . '.' . $extension;
        $targetPath = $targetDir . '/' . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return 'storage/uploads/' . ltrim($folder, '/') . '/' . $fileName;
        }

        throw new Exception('Failed to save file to storage.');
    }

    /**
     * Delete a file from storage
     */
    public function deleteFile(string $filePath): bool
    {
        $fullPath = \PUBLIC_PATH . '/' . ltrim($filePath, '/');
        if (file_exists($fullPath) && is_file($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }

    /**
     * Get public URL of a stored file
     */
    public function getUrl(string $filePath): string
    {
        return $this->storageUrl . '/' . ltrim($filePath, 'storage/uploads/');
    }

    /**
     * Validate image upload
     */
    public function validateImage(array $file, int $maxSizeMB = 2): void
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPEG, PNG, GIF, and WEBP are allowed.');
        }

        $maxSize = $maxSizeMB * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            throw new Exception("File size too large. Maximum allowed is {$maxSizeMB}MB.");
        }
    }

    /**
     * Validate PDF upload
     */
    public function validatePDF(array $file, int $maxSizeMB = 5): void
    {
        if ($file['type'] !== 'application/pdf') {
            throw new Exception('Invalid file type. Only PDF documents are allowed.');
        }

        $maxSize = $maxSizeMB * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            throw new Exception("File size too large. Maximum allowed is {$maxSizeMB}MB.");
        }
    }
}
