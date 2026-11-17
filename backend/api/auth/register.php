<?php
include_once __DIR__ . '/../../config/cors.php';
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../helpers/response.php';
include_once __DIR__ . '/../../helpers/request.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_error('Method not allowed', 405);
}

const DIABETES_TYPES = ['type_1', 'type_2', 'prediabetes', 'caregiver'];

$input = json_input();
$fullName = trim($input['full_name'] ?? '');
$email = strtolower(trim($input['email'] ?? ''));
$password = (string) ($input['password'] ?? '');
$type = $input['diabetes_type'] ?? '';

if (strlen($fullName) < 2 || strlen($fullName) > 100) {
    json_error('Full name must be 2-100 characters', 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120) {
    json_error('Enter a valid email address', 422);
}
if (strlen($password) < 8) {
    json_error('Password must be at least 8 characters', 422);
}
if (!in_array($type, DIABETES_TYPES, true)) {
    json_error('Choose a valid diabetes type', 422);
}

$emailSql = $conn->real_escape_string($email);
$result = $conn->query("SELECT id FROM users WHERE email = '$emailSql'");
if ($result->fetch_assoc()) {
    json_error('Email is already registered', 409);
}

$nameSql = $conn->real_escape_string($fullName);
$typeSql = $conn->real_escape_string($type);
$hash = password_hash($password, PASSWORD_BCRYPT);
$hashSql = $conn->real_escape_string($hash);

$ok = $conn->query(
    "INSERT INTO users (full_name, email, password_hash, diabetes_type)
     VALUES ('$nameSql', '$emailSql', '$hashSql', '$typeSql')"
);

if (!$ok) {
    if ($conn->errno === 1062) {
        json_error('Email is already registered', 409);
    }
    json_error('Could not create the account', 500);
}

json_ok(['id' => $conn->insert_id], 201);
