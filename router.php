<?php

function static_mime($file) {
    switch (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
        case 'html':
            return 'text/html; charset=utf-8';
        case 'js':
            return 'text/javascript; charset=utf-8';
        case 'css':
            return 'text/css; charset=utf-8';
        case 'svg':
            return 'image/svg+xml';
        case 'png':
            return 'image/png';
        case 'jpg':
        case 'jpeg':
            return 'image/jpeg';
        case 'webp':
            return 'image/webp';
        default:
            return mime_content_type($file) ?: 'application/octet-stream';
    }
}

function send_file($file) {
    header('Content-Type: ' . static_mime($file));
    readfile($file);
}

function not_found($body = '', $contentType = null) {
    http_response_code(404);
    if ($contentType !== null) {
        header('Content-Type: ' . $contentType);
    }
    if ($body !== '') {
        echo $body;
    }
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (str_contains($path, '..')) {
    not_found();
    return true;
}

$file = __DIR__ . $path;
if ($path !== '/' && is_file($file)) {
    if (str_ends_with($file, '.php')) {
        include $file;
        return true;
    }
    send_file($file);
    return true;
}

if (str_starts_with($path, '/backend/')) {
    not_found(json_encode(['success' => false, 'message' => 'Not found']), 'application/json; charset=utf-8');
    return true;
}

$distFile = __DIR__ . '/frontend/dist' . $path;
if ($path !== '/' && is_file($distFile)) {
    send_file($distFile);
    return true;
}

if (str_starts_with($path, '/assets/')) {
    not_found();
    return true;
}

$indexFile = __DIR__ . '/frontend/dist/index.html';
if (is_file($indexFile)) {
    send_file($indexFile);
    return true;
}

not_found('Frontend not built. Run: cd frontend && npm run build', 'text/plain; charset=utf-8');
return true;
