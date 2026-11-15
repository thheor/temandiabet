<?php
declare(strict_types=1);

function json_response(int $status, array $body): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_ok(array $data = [], int $status = 200): void
{
    json_response($status, ['success' => true] + $data);
}

function json_error(string $message, int $status = 400): void
{
    json_response($status, ['success' => false, 'message' => $message]);
}
