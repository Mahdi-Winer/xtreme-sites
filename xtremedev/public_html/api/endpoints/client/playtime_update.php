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

$total_playtime = isset($_POST['total_playtime']) ? intval($_POST['total_playtime']) : null;
if ($total_playtime === null) {
    http_response_code(400);
    echo json_encode(['error' => 'total_playtime_required']);
    exit;
}

$stmt = $mysqli->prepare("UPDATE playtime SET total_playtime = ? WHERE user_id = ?");
$stmt->bind_param('ii', $total_playtime, $user_id);
if ($stmt->execute()) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'db_error']);
}
$stmt->close();