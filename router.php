<?php
declare(strict_types=1);

// Mirrors XAMPP's /temandiabet mount for `php -S localhost:8000 router.php`.
const PREFIX = '/temandiabet';

// fileinfo reports .css as text/plain, which browsers refuse, so web types are explicit.
function static_mime(string $file): string
{
    return match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
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
        default => mime_content_type($file) ?: 'application/octet-stream',
    };
}

// Router locals are prefixed so they cannot collide with variables in the
// endpoint files this script requires.
$routerUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($routerUri !== PREFIX && !str_starts_with($routerUri, PREFIX . '/')) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not found. All URLs live under ' . PREFIX . '/';
    return true;
}

$routerPath = substr($routerUri, strlen(PREFIX)) ?: '/';

if (str_contains($routerPath, '..')) {
    http_response_code(404);
    return true;
}

// The built-in server would resolve the prefixed URI against the docroot and
// miss the file, so repo files are executed or streamed from here.
$routerFile = __DIR__ . $routerPath;
if ($routerPath !== '/' && is_file($routerFile)) {
    if (str_ends_with($routerFile, '.php')) {
        require $routerFile;
        return true;
    }

    header('Content-Type: ' . static_mime($routerFile));
    readfile($routerFile);
    return true;
}

if (str_starts_with($routerPath, '/backend/')) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Not found']);
    return true;
}

// Built SPA assets live in frontend/dist, not at the repo root.
$routerDist = __DIR__ . '/frontend/dist' . $routerPath;
if ($routerPath !== '/' && is_file($routerDist)) {
    header('Content-Type: ' . static_mime($routerDist));
    readfile($routerDist);
    return true;
}

// A missing hashed asset must 404, not fall back to index.html.
if (str_starts_with($routerPath, '/assets/')) {
    http_response_code(404);
    return true;
}

// SPA fallback for the built frontend.
$routerIndex = __DIR__ . '/frontend/dist/index.html';
if (is_file($routerIndex)) {
    header('Content-Type: text/html; charset=utf-8');
    readfile($routerIndex);
    return true;
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo 'Frontend not built. Run: cd frontend && npm run build';
return true;
