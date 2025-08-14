<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit;

require_once __DIR__ . '/../../shared/client-database-config.php';
require_once __DIR__ . '/../../shared/auth-helper.php';

$user_id = getUserIdFromBearerToken();
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

// بررسی تکراری نبودن
$stmt = $mysqli->prepare("SELECT id FROM playtime WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    http_response_code(409);
    echo json_encode(['error' => 'already_exists']);
    exit;
}
$stmt->close();

// ایجاد رکورد
$stmt = $mysqli->prepare("INSERT INTO playtime (user_id, total_playtime) VALUES (?, 0)");
$stmt->bind_param('i', $user_id);
if ($stmt->execute()) {
    echo json_encode(['ok' => true, 'id' => $stmt->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'db_error']);
}
$stmt->close();