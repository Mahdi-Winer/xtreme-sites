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
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
    echo json_encode(['error'=>'POST method required']);
    exit;
}

$ticket_id = intval($_POST['ticket_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

if (!$ticket_id || !$message) {
    http_response_code(400);
    echo json_encode(['error'=>'ticket_id and message required']);
    exit;
}

// بررسی این که تیکت متعلق به کاربر باشه و باز باشه
$stmt = $mysqli->prepare("SELECT status FROM tickets WHERE id=? AND user_id=?");
$stmt->bind_param('ii', $ticket_id, $user_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($status);

if ($stmt->fetch() && $status === 'open') {
    $stmt->close();

    // ثبت پیام
    $stmt2 = $mysqli->prepare("INSERT INTO ticket_messages (ticket_id, sender, message) VALUES (?, 'user', ?)");
    $stmt2->bind_param('is', $ticket_id, $message);
    $stmt2->execute();
    $stmt2->close();

    echo json_encode(['success' => true]);
} else {
    $stmt->close();
    http_response_code(400);
    echo json_encode(['error'=>'ticket not found or closed']);
    exit;
}
?>