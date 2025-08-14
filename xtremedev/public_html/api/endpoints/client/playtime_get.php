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

$stmt = $mysqli->prepare("SELECT id, user_id, total_playtime FROM playtime WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($id, $uid, $total_playtime);

if ($stmt->fetch()) {
    $row = [
        'id' => $id,
        'user_id' => $uid,
        'total_playtime' => $total_playtime
    ];
    echo json_encode(['ok' => true, 'playtime' => $row]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'not_found']);
}
$stmt->close();