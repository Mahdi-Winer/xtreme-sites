<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../shared/database-config.php';
require_once __DIR__ . '/../../shared/auth-admin-helper.php';

getAdminInfoOrExit(['superadmin','manager']);

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) { http_response_code(400); echo '{"error":"Bad request"}'; exit; }

$id = intval($data['id'] ?? 0);
$role_title = trim($data['role_title'] ?? '');
$role_desc = trim($data['role_desc'] ?? '');

if ($id <= 0 || !$role_title) {
    http_response_code(422); echo '{"error":"Invalid data"}'; exit;
}

$stmt = $mysqli->prepare("UPDATE joinus_project_roles SET role_title=?, role_desc=? WHERE id=?");
$stmt->bind_param('ssi', $role_title, $role_desc, $id);
if(!$stmt->execute()){
    http_response_code(500); echo '{"error":"DB error"}'; exit;
}
$stmt->close();

echo json_encode(['success'=>1]);