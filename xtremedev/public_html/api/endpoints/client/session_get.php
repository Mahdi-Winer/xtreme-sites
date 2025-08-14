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

$query = "SELECT id, user_id, device, login_time, logout_time, playtime FROM sessions WHERE user_id = ? ORDER BY login_time DESC LIMIT 1";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($id, $user_id_, $device, $login_time, $logout_time, $playtime);

if ($stmt->fetch()) {
    $row = [
        'id' => $id,
        'user_id' => $user_id_,
        'device' => $device,
        'login_time' => $login_time,
        'logout_time' => $logout_time,
        'playtime' => $playtime
    ];
    echo json_encode(['ok' => true, 'session' => $row]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'not_found']);
}
$stmt->close();