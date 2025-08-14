<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../shared/database-config.php';
require_once __DIR__ . '/../../shared/auth-admin-helper.php';

getAdminInfoOrExit();

$request_id = intval($_GET['id'] ?? 0);
if (!$request_id) {
    http_response_code(400);
    echo json_encode(['error'=>'id is required']);
    exit;
}

$stmt = $mysqli->prepare("SELECT * FROM joinus_requests WHERE id=? LIMIT 1");
$stmt->bind_param('i', $request_id);
$stmt->execute();
$res = $stmt->get_result();
if($row = $res->fetch_assoc()) echo json_encode($row, JSON_UNESCAPED_UNICODE);
else {
    http_response_code(404);
    echo json_encode(['error'=>'not_found']);
}