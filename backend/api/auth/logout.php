<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_error('Method not allowed', 405);
}

start_session();

// Clear the stored token for whoever this request belongs to, session or cookie.
if (!empty($_SESSION['user_id'])) {
    $stmt = db()->prepare('UPDATE users SET remember_token = NULL WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
} elseif (!empty($_COOKIE[REMEMBER_COOKIE])) {
    $stmt = db()->prepare('UPDATE users SET remember_token = NULL WHERE remember_token = ?');
    $hash = remember_token_hash($_COOKIE[REMEMBER_COOKIE]);
    $stmt->bind_param('s', $hash);
    $stmt->execute();
}

$_SESSION = [];
clear_remember_cookie();
setcookie(session_name(), '', cookie_options() + ['expires' => time() - 3600]);
session_destroy();

json_ok();
