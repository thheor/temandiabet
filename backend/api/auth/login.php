<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/request.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_error('Method not allowed', 405);
}

$input = json_input();
$email = strtolower(trim((string) ($input['email'] ?? '')));
$password = (string) ($input['password'] ?? '');
$remember = filter_var($input['remember'] ?? false, FILTER_VALIDATE_BOOLEAN);

$stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row || !password_verify($password, $row['password_hash'])) {
    json_error('Email or password is incorrect', 401);
}

start_session();
session_regenerate_id(true);
$_SESSION['user_id'] = (int) $row['id'];

if ($remember) {
    $token = bin2hex(random_bytes(32));
    $hash = remember_token_hash($token);
    $stmt = db()->prepare('UPDATE users SET remember_token = ? WHERE id = ?');
    $stmt->bind_param('si', $hash, $row['id']);
    $stmt->execute();
    set_remember_cookie($token);
}

json_ok(['user' => public_user($row)]);
