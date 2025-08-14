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

// آخرین گیم نیم (جدیدترین used_at)
$stmt = $mysqli->prepare("SELECT id, username, used_at FROM ingame_names WHERE user_id = ? ORDER BY used_at DESC LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($id, $username, $used_at);

if ($stmt->fetch()) {
    $row = [
        'id' => $id,
        'ingame_username' => $username,
        'used_at' => $used_at
    ];
    echo json_encode(['ok' => true, 'ingame_name' => $row]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'not_found']);
}
$stmt->close();