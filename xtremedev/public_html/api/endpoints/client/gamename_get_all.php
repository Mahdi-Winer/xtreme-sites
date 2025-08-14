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

$stmt = $mysqli->prepare("SELECT id, username, used_at FROM ingame_names WHERE user_id = ? ORDER BY used_at DESC");
$stmt->bind_param('i', $user_id);
$stmt->execute();

// استفاده از bind_result و fetch
$stmt->bind_result($id, $username, $used_at);
$names = [];
while ($stmt->fetch()) {
    $names[] = [
        'id' => $id,
        'ingame_username' => $username,
        'used_at' => $used_at
    ];
}

echo json_encode(['ok' => true, 'gamenames' => $names]);
$stmt->close();