<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

const REMEMBER_COOKIE = 'remember_token';
const REMEMBER_DAYS = 7;

// Session and remember-me cookies must share the exact same path, otherwise
// logging out leaves a live cookie behind.
function cookie_options(): array
{
    return [
        'path' => BASE_PATH ?: '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']),
    ];
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params(cookie_options() + ['lifetime' => 0]);
    session_start();
}

function public_user(array $row): array
{
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

function remember_token_hash(string $token): string
{
    return hash('sha256', $token);
}

function set_remember_cookie(string $token): void
{
    setcookie(
        REMEMBER_COOKIE,
        $token,
        cookie_options() + ['expires' => time() + REMEMBER_DAYS * 86400],
    );
}

function clear_remember_cookie(): void
{
    setcookie(REMEMBER_COOKIE, '', cookie_options() + ['expires' => time() - 3600]);
}

// Session first, then the remember-me cookie for a fresh browser.
function current_user(): ?array
{
    start_session();

    if (!empty($_SESSION['user_id'])) {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ($row) {
            return public_user($row);
        }

        unset($_SESSION['user_id']);
    }

    $token = $_COOKIE[REMEMBER_COOKIE] ?? '';
    if ($token === '') {
        return null;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE remember_token = ?');
    $hash = remember_token_hash($token);
    $stmt->bind_param('s', $hash);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        return null;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $row['id'];

    return public_user($row);
}

function require_auth(): array
{
    $user = current_user();

    if (!$user) {
        json_error('Unauthenticated', 401);
    }

    return $user;
}
