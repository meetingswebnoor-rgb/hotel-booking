<?php

declare(strict_types=1);

namespace App\Core;

use finfo;

/**
 * Validated image upload to public/assets/uploads/{subdir}/. Content
 * type is sniffed from the actual bytes (finfo), not the client-sent
 * mime or filename, and a fresh UUID name is always generated — the
 * original filename is never trusted or reused.
 */
final class FileUpload
{
    private const ALLOWED_IMAGE_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private const MAX_BYTES = 5 * 1024 * 1024;

    /**
     * @param array<string, mixed>|null $file a single $_FILES entry
     */
    public static function storeImage(?array $file, string $subdir): ?string
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK || (int) $file['size'] > self::MAX_BYTES || (int) $file['size'] < 1) {
            return null;
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            return null;
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $ext = self::ALLOWED_IMAGE_MIMES[$mime] ?? null;

        if ($ext === null) {
            return null;
        }

        $dir = BASE_PATH . '/public/assets/uploads/' . trim($subdir, '/');

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return null;
        }

        $filename = uuid() . '.' . $ext;

        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
            return null;
        }

        return 'uploads/' . trim($subdir, '/') . '/' . $filename;
    }

    /**
     * Handles PHP's array-of-arrays $_FILES shape for name="field[]"
     * multi-file inputs, storing each valid image and skipping the rest.
     *
     * @param array<string, mixed>|null $files a single $_FILES entry for a multi-file input
     * @return array<int, string>
     */
    public static function storeImages(?array $files, string $subdir): array
    {
        if ($files === null || !isset($files['name']) || !is_array($files['name'])) {
            return [];
        }

        $stored = [];

        foreach ($files['name'] as $i => $name) {
            if ($name === '' || ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $single = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i] ?? '',
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i] ?? 0,
            ];

            $path = self::storeImage($single, $subdir);

            if ($path !== null) {
                $stored[] = $path;
            }
        }

        return $stored;
    }
}
