<?php
session_start();
header("Content-Type: application/json");
require_once __DIR__ . '/../shared/inc/database-config.php';

if (!isset($_SESSION['admin_user_id'])) {
    http_response_code(401);
    echo json_encode(['status'=>'error', 'message'=>'Not authorized']);
    exit;
}

// فقط سوپرادمین!
$admin_id = $_SESSION['admin_user_id'];
$stmt = $mysqli->prepare("SELECT is_super_admin FROM admin_users WHERE id=? LIMIT 1");
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$stmt->bind_result($is_super_admin);
$stmt->fetch();
$stmt->close();

if (!$is_super_admin) {
    http_response_code(403);
    echo json_encode(['status'=>'error', 'message'=>'Access denied']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
if (!$id) {
    echo json_encode(['status'=>'error', 'message'=>'Invalid member']);
    exit;
}

$stmt = $mysqli->prepare("DELETE FROM team WHERE id=? LIMIT 1");
$stmt->bind_param('i', $id);
if ($stmt->execute()) {
    echo json_encode(['status'=>'ok']);
} else {
    echo json_encode(['status'=>'error', 'message'=>'DB error']);
}
$stmt->close();