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

// limit را از GET بگیر، پیشفرض 5
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
if ($limit < 1 || $limit > 100) $limit = 5; // امنیت

// کوئری با mysqli بدون get_result
$query = "SELECT id, user_id, device, login_time, logout_time, playtime FROM sessions WHERE user_id = ? ORDER BY login_time DESC LIMIT ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('ii', $user_id, $limit);
$stmt->execute();

// متغیرها برای bind_result
$stmt->bind_result($id, $user_id_, $device, $login_time, $logout_time, $playtime);

$sessions = [];
while ($stmt->fetch()) {
    $sessions[] = [
        'id' => $id,
        'user_id' => $user_id_,
        'device' => $device,
        'login_time' => $login_time,
        'logout_time' => $logout_time,
        'playtime' => $playtime
    ];
}
$stmt->close();

echo json_encode(['ok' => true, 'sessions' => $sessions]);