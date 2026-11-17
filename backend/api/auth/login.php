<?php
include_once __DIR__ . '/../../config/cors.php';
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../helpers/auth.php';
include_once __DIR__ . '/../../helpers/response.php';
include_once __DIR__ . '/../../helpers/request.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_error('Method not allowed', 405);
}

$input = json_input();
$email = strtolower(trim($input['email'] ?? ''));
$password = (string) ($input['password'] ?? '');
$remember = !empty($input['remember']);

$emailSql = $conn->real_escape_string($email);
$result = $conn->query("SELECT * FROM users WHERE email = '$emailSql'");
$row = $result->fetch_assoc();

if (!$row || !password_verify($password, $row['password_hash'])) {
    json_error('Email or password is incorrect', 401);
}

start_session();
session_regenerate_id(true);
$_SESSION['user_id'] = (int) $row['id'];

if ($remember) {
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $userId = (int) $row['id'];
    $conn->query("UPDATE users SET remember_token = '$hash' WHERE id = $userId");
    set_remember_cookie($token);
}

json_ok(['user' => public_user($row)]);
