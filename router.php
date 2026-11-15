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

// Real files (API endpoints, uploads, built assets) are executed or served as-is.
if ($path !== '/' && is_file($file)) {
    return false;
}

if (str_starts_with($path, '/backend/')) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Not found']);
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
