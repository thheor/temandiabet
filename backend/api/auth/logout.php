<?php
include_once __DIR__ . '/../../config/cors.php';
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../helpers/auth.php';
include_once __DIR__ . '/../../helpers/response.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_error('Method not allowed', 405);
}

start_session();

if (!empty($_SESSION['user_id'])) {
    $id = (int) $_SESSION['user_id'];
    $conn->query("UPDATE users SET remember_token = NULL WHERE id = $id");
} elseif (!empty($_COOKIE[REMEMBER_COOKIE])) {
    $hash = hash('sha256', $_COOKIE[REMEMBER_COOKIE]);
    $conn->query("UPDATE users SET remember_token = NULL WHERE remember_token = '$hash'");
}

$_SESSION = [];
clear_remember_cookie();
setcookie(session_name(), '', cookie_options() + ['expires' => time() - 3600]);
session_destroy();

json_ok();
