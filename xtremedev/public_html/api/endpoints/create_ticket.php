<?php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Authorization, Content-Type');
    exit(0);
}
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
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

$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if (!$subject || !$message) {
    http_response_code(400);
    echo json_encode(['error'=>'subject and message required']);
    exit;
}

// ثبت تیکت
$stmt = $mysqli->prepare("INSERT INTO tickets (user_id, subject) VALUES (?, ?)");
$stmt->bind_param('is', $user_id, $subject);
if ($stmt->execute()) {
    $ticket_id = $stmt->insert_id;
    $stmt->close();

    // ثبت پیام اول تیکت
    $stmt2 = $mysqli->prepare("INSERT INTO ticket_messages (ticket_id, sender, message) VALUES (?, 'user', ?)");
    $stmt2->bind_param('is', $ticket_id, $message);
    $stmt2->execute();
    $stmt2->close();

    echo json_encode([
        'success' => true,
        'ticket_id' => $ticket_id
    ]);
    exit;
} else {
    http_response_code(500);
    echo json_encode(['error'=>'failed to create ticket']);
    exit;
}
?>