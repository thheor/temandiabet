<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/dates.php';

$user = require_auth();
$userId = $user['id'];

$date = isset($_GET['eaten_date']) ? parse_date($_GET['eaten_date']) : date('Y-m-d');
if ($date === null) {
    json_error('Use eaten_date as YYYY-MM-DD', 422);
}

$stmt = db()->prepare(
    'SELECT id, user_id, food_name, meal_type, estimated_calories, carbohydrates_grams, photo_path, eaten_date, created_at
     FROM food_logs
     WHERE user_id = ? AND eaten_date = ?
     ORDER BY FIELD(meal_type, \'breakfast\', \'lunch\', \'dinner\', \'snack\'), id',
);
$stmt->bind_param('is', $userId, $date);
$stmt->execute();

$meals = [];
$calories = 0;
$carbs = 0.0;
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int) $row['id'];
    $row['user_id'] = (int) $row['user_id'];
    $row['estimated_calories'] = (int) $row['estimated_calories'];
    $row['carbohydrates_grams'] = (float) $row['carbohydrates_grams'];
    $calories += $row['estimated_calories'];
    $carbs += $row['carbohydrates_grams'];
    $meals[] = $row;
}

json_ok([
    'eaten_date' => $date,
    'meals' => $meals,
    'totals' => [
        'estimated_calories' => $calories,
        'carbohydrates_grams' => round($carbs, 2),
    ],
]);
