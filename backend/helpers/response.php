<?php
function json_response($status, $body) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($body);
    exit;
}

function json_ok($data = [], $status = 200) {
    json_response($status, ['success' => true] + $data);
}

function json_error($message, $status = 400) {
    json_response($status, ['success' => false, 'message' => $message]);
}
