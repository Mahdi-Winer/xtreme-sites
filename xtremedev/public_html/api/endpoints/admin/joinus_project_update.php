<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../shared/database-config.php';
require_once __DIR__ . '/../../shared/auth-admin-helper.php';

getAdminInfoOrExit(['superadmin','manager']);

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) { http_response_code(400); echo '{"error":"Bad request"}'; exit; }

$id = intval($data['id'] ?? 0);
$title = trim($data['title'] ?? '');
$desc = trim($data['description'] ?? '');
$is_active = isset($data['is_active']) ? (int)$data['is_active'] : 1;

if ($id <= 0 || !$title) {
    http_response_code(422); echo '{"error":"Invalid data"}'; exit;
}

$stmt = $mysqli->prepare("UPDATE joinus_projects SET title=?, description=?, is_active=? WHERE id=?");
$stmt->bind_param('ssii', $title, $desc, $is_active, $id);
if(!$stmt->execute()){
    http_response_code(500); echo '{"error":"DB error"}'; exit;
}
$stmt->close();

echo json_encode(['success'=>1]);