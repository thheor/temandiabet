<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';

$user = current_user();

if (!$user) {
    json_error('Unauthenticated', 401);
}

json_ok(['user' => $user]);
