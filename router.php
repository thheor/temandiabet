<?php
declare(strict_types=1);

// Mirrors XAMPP's /temandiabet mount for `php -S localhost:8000 router.php`.
const PREFIX = '/temandiabet';

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($uri !== PREFIX && !str_starts_with($uri, PREFIX . '/')) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not found. All URLs live under ' . PREFIX . '/';
    return true;
}

$path = substr($uri, strlen(PREFIX)) ?: '/';
$file = __DIR__ . $path;

if (str_contains($path, '..')) {
    http_response_code(404);
    return true;
}

// Real files (API endpoints, uploads) are executed or served as-is.
if ($path !== '/' && is_file($file)) {
    return false;
}

if (str_starts_with($path, '/backend/')) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Not found']);
    return true;
}

// Built SPA assets live in frontend/dist, not at the repo root.
$dist = __DIR__ . '/frontend/dist' . $path;
if ($path !== '/' && is_file($dist)) {
    $type = match (strtolower(pathinfo($dist, PATHINFO_EXTENSION))) {
        'html' => 'text/html; charset=utf-8',
        'js', 'mjs' => 'text/javascript; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'json', 'map' => 'application/json; charset=utf-8',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'woff2' => 'font/woff2',
        default => mime_content_type($dist) ?: 'application/octet-stream',
    };
    header('Content-Type: ' . $type);
    readfile($dist);
    return true;
}

// A missing hashed asset must 404, not fall back to index.html.
if (str_starts_with($path, '/assets/')) {
    http_response_code(404);
    return true;
}

// SPA fallback for the built frontend.
$index = __DIR__ . '/frontend/dist/index.html';
if (is_file($index)) {
    header('Content-Type: text/html; charset=utf-8');
    readfile($index);
    return true;
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo 'Frontend not built. Run: cd frontend && npm run build';
return true;
