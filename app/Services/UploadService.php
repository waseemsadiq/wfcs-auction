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

        // 8. Generate thumbnail (best-effort — don't fail upload if GD unavailable)
        if (extension_loaded('gd')) {
            $this->generateThumbnail($uploadsDir . '/' . $filename, $mime);
        }

        return $filename;
    }

    /**
     * Generate a 200×200-max thumbnail and save to uploads/thumbs/<filename>.
     * Best-effort — silently ignores failures.
     */
    public function generateThumbnail(string $srcPath, string $mime = ''): void
    {
        if ($mime === '') {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = $finfo ? (string)finfo_file($finfo, $srcPath) : '';
            if ($finfo) finfo_close($finfo);
        }

        $src = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($srcPath),
            'image/png'  => @imagecreatefrompng($srcPath),
            'image/webp' => @imagecreatefromwebp($srcPath),
            'image/gif'  => @imagecreatefromgif($srcPath),
            default      => false,
        };

        if ($src === false) {
            return;
        }

        $origW = imagesx($src);
        $origH = imagesy($src);

        $maxDim = 200;
        $ratio  = min($maxDim / $origW, $maxDim / $origH, 1.0);
        $newW   = max(1, (int)round($origW * $ratio));
        $newH   = max(1, (int)round($origH * $ratio));

        $thumb = imagecreatetruecolor($newW, $newH);
        if ($thumb === false) {
            imagedestroy($src);
            return;
        }

        // Preserve transparency for PNG/GIF
        if ($mime === 'image/png' || $mime === 'image/gif') {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
            if ($transparent !== false) {
                imagefilledrectangle($thumb, 0, 0, $newW, $newH, $transparent);
            }
        }

        imagecopyresampled($thumb, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($src);

        $thumbsDir = dirname($srcPath) . '/thumbs';
        if (!is_dir($thumbsDir)) {
            @mkdir($thumbsDir, 0755, true);
        }

        $dest = $thumbsDir . '/' . basename($srcPath);
        match ($mime) {
            'image/jpeg' => imagejpeg($thumb, $dest, 85),
            'image/png'  => imagepng($thumb, $dest, 6),
            'image/webp' => imagewebp($thumb, $dest, 85),
            'image/gif'  => imagegif($thumb, $dest),
            default      => null,
        };

        imagedestroy($thumb);
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
