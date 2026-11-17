<?php
$host = '127.0.0.1';
$port = 3308;
$user = 'temandiabet';
$password = 'temandiabet';
$database = 'temandiabet';

mysqli_report(MYSQLI_REPORT_OFF);

$conn = mysqli_connect($host, $user, $password, $database, $port);

if (!$conn) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');
