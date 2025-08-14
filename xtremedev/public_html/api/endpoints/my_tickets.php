<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit;
require_once __DIR__ . '/../shared/database-config.php';
require_once __DIR__ . '/../shared/auth-helper.php';

$user_id = getUserIdFromBearerToken();
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error'=>'unauthorized']);
    exit;
}

$stmt = $mysqli->prepare("SELECT id, subject, status, created_at FROM tickets WHERE user_id=? ORDER BY id DESC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($id, $subject, $status, $created_at);

$tickets = [];
while ($stmt->fetch()) {
    $tickets[] = [
        'ticket_id' => $id,
        'subject' => $subject,
        'status' => $status,
        'created_at' => $created_at
    ];
}
$stmt->close();

echo json_encode($tickets);
?>