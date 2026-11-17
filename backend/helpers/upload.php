<?php
include_once __DIR__ . '/response.php';

const UPLOAD_MAX_BYTES = 2 * 1024 * 1024;

const UPLOAD_MIME_EXT = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

function save_upload($file, $folder) {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        json_error('Upload failed', 400);
    }

    if ($file['size'] > UPLOAD_MAX_BYTES) {
        json_error('Image must be 2 MB or smaller', 400);
    }

    $ext = UPLOAD_MIME_EXT[(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: ''] ?? null;
    if ($ext === null) {
        json_error('Only JPG, PNG or WebP images are allowed', 400);
    }

    $dir = __DIR__ . '/../uploads/' . $folder;
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        json_error('Could not prepare the upload folder', 500);
    }

    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        json_error('Could not save the image', 500);
    }

    return 'uploads/' . $folder . '/' . $name;
}

function delete_upload($relativePath) {
    if ($relativePath === null || !str_starts_with($relativePath, 'uploads/') || str_contains($relativePath, '..')) {
        return;
    }

    $file = __DIR__ . '/../' . $relativePath;
    if (is_file($file)) {
        unlink($file);
    }
}
