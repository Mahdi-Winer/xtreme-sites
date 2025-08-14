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

$ticket_id = intval($_GET['ticket_id'] ?? 0);
if (!$ticket_id) {
    http_response_code(400);
    echo json_encode(['error'=>'ticket_id required']);
    exit;
}

// چک کن تیکت متعلق به کاربر باشه
$stmt = $mysqli->prepare("SELECT id, subject, status, created_at FROM tickets WHERE id=? AND user_id=?");
$stmt->bind_param('ii', $ticket_id, $user_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($id, $subject, $status, $created_at);

if ($stmt->fetch()) {
    $ticket = [
        'ticket_id' => $id,
        'subject' => $subject,
        'status' => $status,
        'created_at' => $created_at,
        'messages' => []
    ];
    $stmt->close();

    // پیام‌ها
    $stmt2 = $mysqli->prepare("SELECT sender, message, created_at FROM ticket_messages WHERE ticket_id=? ORDER BY id ASC");
    $stmt2->bind_param('i', $ticket_id);
    $stmt2->execute();
    $stmt2->store_result();
    $stmt2->bind_result($sender, $message, $msg_created_at);

    while ($stmt2->fetch()) {
        $ticket['messages'][] = [
            'sender' => $sender,
            'message' => $message,
            'created_at' => $msg_created_at
        ];
    }
    $stmt2->close();

    echo json_encode($ticket);
} else {
    http_response_code(404);
    echo json_encode(['error'=>'ticket not found']);
    exit;
}
?>