<?php
declare(strict_types=1);

namespace App\Services;

class UploadService
{
    private const MAX_SIZE    = 5 * 1024 * 1024; // 5 MB
    private const ALLOWED_MIME = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    /**
     * Handle an item image upload.
     *
     * Validates: file size (<= 5 MB), MIME type (via finfo), valid image (getimagesize).
     * Generates a secure random filename.
     * Moves the uploaded file to the uploads/ directory.
     * Returns just the filename (not the full path).
     *
     * @param array $file  Element from $_FILES (keys: tmp_name, size, name, error)
     * @return string      Generated filename
     * @throws \RuntimeException on any validation or move failure
     */
    public function uploadItemImage(array $file): string
    {
        // 1. Check for upload errors
        if (!empty($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('File upload error (code ' . $file['error'] . ').');
        }

        // 2. Validate file size
        if ((int)$file['size'] > self::MAX_SIZE) {
            throw new \RuntimeException('Image must be 5 MB or smaller.');
        }

        // 3. Validate MIME type via finfo (not $_FILES['type'] which is user-supplied)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            throw new \RuntimeException('Could not open finfo resource.');
        }
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            throw new \RuntimeException('Only JPEG, PNG, WebP, and GIF images are allowed.');
        }

        // 4. Validate image content
        if (!getimagesize($file['tmp_name'])) {
            throw new \RuntimeException('Uploaded file is not a valid image.');
        }

        // 5. Generate a secure random filename, keeping the original extension
        $ext      = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        $filename = bin2hex(random_bytes(16)) . ($ext !== '' ? '.' . $ext : '');

        // 6. Resolve uploads directory relative to the app root
        $uploadsDir = $this->uploadsDir();
        if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0755, true)) {
            throw new \RuntimeException('Could not create uploads directory.');
        }

        // 7. Move file
        if (!move_uploaded_file($file['tmp_name'], $uploadsDir . '/' . $filename)) {
            throw new \RuntimeException('Failed to move uploaded file.');
        }

        return $filename;
    }

    /**
     * Delete an upload by filename.
     * Silently ignores files that do not exist.
     *
     * @param string $filename  Just the filename, not a full path
     */
    public function delete(string $filename): void
    {
        if ($filename === '') {
            return;
        }

        // Prevent path traversal
        $filename = basename($filename);
        $path     = $this->uploadsDir() . '/' . $filename;

        if (file_exists($path)) {
            unlink($path);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve the absolute path to the uploads/ directory.
     * Works from both Galvani web context (cwd = app subfolder) and CLI.
     */
    private function uploadsDir(): string
    {
        // __DIR__ is app/Services — go up two levels to reach the app root
        return dirname(__DIR__, 2) . '/uploads';
    }
}
