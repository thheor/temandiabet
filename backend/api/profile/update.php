<?php
include_once __DIR__ . '/../../config/cors.php';
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../helpers/auth.php';
include_once __DIR__ . '/../../helpers/response.php';
include_once __DIR__ . '/../../helpers/request.php';
include_once __DIR__ . '/../../helpers/upload.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_error('Method not allowed', 405);
}

const DIABETES_TYPES = ['type_1', 'type_2', 'prediabetes', 'caregiver'];

$user = require_auth();
$input = json_input();
$userId = (int) $user['id'];

$fullName = trim($input['full_name'] ?? $user['full_name']);
$type = $input['diabetes_type'] ?? $user['diabetes_type'];
$min = (int) ($input['target_glucose_min'] ?? $user['target_glucose_min']);
$max = (int) ($input['target_glucose_max'] ?? $user['target_glucose_max']);

if (strlen($fullName) < 2 || strlen($fullName) > 100) {
    json_error('Full name must be 2-100 characters', 422);
}
if (!in_array($type, DIABETES_TYPES, true)) {
    json_error('Choose a valid diabetes type', 422);
}
if ($min < 20 || $max > 600 || $min >= $max) {
    json_error('Target range must be within 20-600 mg/dL with min below max', 422);
}

$avatarPath = $user['avatar_path'];
if (($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $avatarPath = save_upload($_FILES['avatar'], 'avatars');
    delete_upload($user['avatar_path']);
}

$nameSql = $conn->real_escape_string($fullName);
$typeSql = $conn->real_escape_string($type);
$avatarSql = $avatarPath === null ? 'NULL' : "'" . $conn->real_escape_string($avatarPath) . "'";

$ok = $conn->query(
    "UPDATE users
     SET full_name = '$nameSql', diabetes_type = '$typeSql', target_glucose_min = $min,
         target_glucose_max = $max, avatar_path = $avatarSql
     WHERE id = $userId"
);

if (!$ok) {
    json_error('Could not update the profile', 500);
}

$result = $conn->query("SELECT * FROM users WHERE id = $userId");

json_ok(['user' => public_user($result->fetch_assoc())]);
