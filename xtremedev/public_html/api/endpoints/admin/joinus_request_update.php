<?php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Authorization, Content-Type');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    exit;
}
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
require_once __DIR__ . '/../../shared/database-config.php';
require_once __DIR__ . '/../../shared/auth-admin-helper.php';

getAdminInfoOrExit(['superadmin','manager','support']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo '{"error":"Method not allowed"}'; exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$request_id = intval($data['id'] ?? 0);
$status = $data['status'] ?? '';
$admin_note = trim($data['admin_note'] ?? '');

if (!$request_id || !in_array($status, ['pending','under_review','accepted','rejected'])) {
    http_response_code(400);
    echo json_encode(['error'=>'invalid_input']);
    exit;
}

$stmt = $mysqli->prepare("UPDATE joinus_requests SET status=?, admin_note=?, updated_at=NOW() WHERE id=?");
$stmt->bind_param('ssi', $status, $admin_note, $request_id);
if($stmt->execute()) {
    echo json_encode(['success'=>true]);
} else {
    http_response_code(500);
    echo json_encode(['error'=>'db_error']);
}
$stmt->close();