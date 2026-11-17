<?php
include_once __DIR__ . '/../../config/cors.php';
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../helpers/auth.php';
include_once __DIR__ . '/../../helpers/response.php';
include_once __DIR__ . '/../../helpers/request.php';
include_once __DIR__ . '/../../helpers/dates.php';
include_once __DIR__ . '/../../helpers/upload.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_error('Method not allowed', 405);
}

const MEAL_TYPES = ['breakfast', 'lunch', 'dinner', 'snack'];

$user = require_auth();
$input = json_input();
$userId = (int) $user['id'];

$name = trim($input['food_name'] ?? '');
$mealType = $input['meal_type'] ?? '';
$calories = (int) ($input['estimated_calories'] ?? 0);
$carbs = (float) ($input['carbohydrates_grams'] ?? 0);
$rawDate = (string) ($input['eaten_date'] ?? '');
$date = $rawDate === '' ? date('Y-m-d') : parse_date($rawDate);

if ($name === '' || strlen($name) > 150) {
    json_error('Food name must be 1-150 characters', 422);
}
if (!in_array($mealType, MEAL_TYPES, true)) {
    json_error('Choose a meal type', 422);
}
if ($calories < 0 || $calories > 10000) {
    json_error('Calories must be a whole number between 0 and 10000', 422);
}
if ($carbs < 0 || $carbs > 1000) {
    json_error('Carbohydrates must be between 0 and 1000 g', 422);
}
if ($date === null) {
    json_error('Use eaten_date as YYYY-MM-DD', 422);
}

$photoPath = null;
if (($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $photoPath = save_upload($_FILES['photo'], 'foods');
}

$nameSql = $conn->real_escape_string($name);
$mealSql = $conn->real_escape_string($mealType);
$dateSql = $conn->real_escape_string($date);
$photoSql = $photoPath === null ? 'NULL' : "'" . $conn->real_escape_string($photoPath) . "'";

$ok = $conn->query(
    "INSERT INTO food_logs
        (user_id, food_name, meal_type, estimated_calories, carbohydrates_grams, photo_path, eaten_date)
     VALUES ($userId, '$nameSql', '$mealSql', $calories, $carbs, $photoSql, '$dateSql')"
);

if (!$ok) {
    delete_upload($photoPath);
    json_error('Could not save the meal', 500);
}

json_ok([
    'meal' => [
        'id' => $conn->insert_id,
        'user_id' => $userId,
        'food_name' => $name,
        'meal_type' => $mealType,
        'estimated_calories' => $calories,
        'carbohydrates_grams' => round($carbs, 2),
        'photo_path' => $photoPath,
        'eaten_date' => $date,
    ],
], 201);
