<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../helpers/response.php';

/**
 * Shared mysqli connection. Defaults match docker-compose.yml; XAMPP users
 * override them in the gitignored local.php (see local.example.php).
 */
function db(): mysqli
{
    static $conn = null;
    if ($conn instanceof mysqli) {
        return $conn;
    }

    $local = is_file(__DIR__ . '/local.php') ? require __DIR__ . '/local.php' : [];

    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = mysqli_connect(
        $local['host'] ?? '127.0.0.1',
        $local['user'] ?? 'temandiabet',
        $local['pass'] ?? 'temandiabet',
        $local['name'] ?? 'temandiabet',
        $local['port'] ?? 3308,
    );

    if (!$conn) {
        json_error('Database connection failed', 500);
    }

    mysqli_set_charset($conn, 'utf8mb4');

    return $conn;
}
