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

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
if (!$username) {
    http_response_code(400);
    echo json_encode(['error' => 'username_required']);
    exit;
}

// تکراری بودن برای هر کاربر
$stmt = $mysqli->prepare("SELECT id FROM ingame_names WHERE user_id = ? AND username = ?");
$stmt->bind_param('is', $user_id, $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    http_response_code(409);
    echo json_encode(['error' => 'already_exists']);
    exit;
}
$stmt->close();

// ثبت
$stmt = $mysqli->prepare("INSERT INTO ingame_names (user_id, username, used_at) VALUES (?, ?, NOW())");
$stmt->bind_param('is', $user_id, $username);
if ($stmt->execute()) {
    echo json_encode(['ok' => true, 'id' => $stmt->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'db_error']);
}
$stmt->close();