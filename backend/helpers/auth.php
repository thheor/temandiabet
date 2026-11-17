<?php
include_once __DIR__ . '/../config/db.php';
include_once __DIR__ . '/response.php';

const REMEMBER_COOKIE = 'remember_token';
const REMEMBER_DAYS = 7;

function cookie_options() {
    return [
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']),
    ];
}

function start_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params(cookie_options() + ['lifetime' => 0]);
    session_start();
}

function public_user($row) {
    return [
        'id' => (int) $row['id'],
        'full_name' => $row['full_name'],
        'email' => $row['email'],
        'diabetes_type' => $row['diabetes_type'],
        'target_glucose_min' => (int) $row['target_glucose_min'],
        'target_glucose_max' => (int) $row['target_glucose_max'],
        'avatar_path' => $row['avatar_path'],
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'],
    ];
}

function set_remember_cookie($token) {
    setcookie(
        REMEMBER_COOKIE,
        $token,
        cookie_options() + ['expires' => time() + REMEMBER_DAYS * 86400]
    );
}

function clear_remember_cookie() {
    setcookie(REMEMBER_COOKIE, '', cookie_options() + ['expires' => time() - 3600]);
}

function current_user() {
    global $conn;

    start_session();

    if (!empty($_SESSION['user_id'])) {
        $id = (int) $_SESSION['user_id'];
        $result = $conn->query("SELECT * FROM users WHERE id = $id");
        $row = $result->fetch_assoc();

        if ($row) {
            return public_user($row);
        }

        unset($_SESSION['user_id']);
    }

    $token = $_COOKIE[REMEMBER_COOKIE] ?? '';
    if ($token === '') {
        return null;
    }

    $hash = hash('sha256', $token);
    $result = $conn->query("SELECT * FROM users WHERE remember_token = '$hash'");
    $row = $result->fetch_assoc();

    if (!$row) {
        return null;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $row['id'];

    return public_user($row);
}

function require_auth() {
    $user = current_user();

    if (!$user) {
        json_error('Unauthenticated', 401);
    }

    return $user;
}
