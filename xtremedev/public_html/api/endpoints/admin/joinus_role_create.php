<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../shared/database-config.php';
require_once __DIR__ . '/../../shared/auth-admin-helper.php';

getAdminInfoOrExit(['superadmin','manager']);

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) { http_response_code(400); echo '{"error":"Bad request"}'; exit; }

$project_id = (int)($data['project_id'] ?? 0);
$role_title = trim($data['role_title'] ?? '');
$role_desc = trim($data['role_desc'] ?? '');

if (!$project_id || !$role_title) {
    http_response_code(422); echo '{"error":"Required fields!"}'; exit;
}

$stmt = $mysqli->prepare("INSERT INTO joinus_project_roles (project_id, role_title, role_desc) VALUES (?, ?, ?)");
$stmt->bind_param('iss', $project_id, $role_title, $role_desc);
if(!$stmt->execute()){
    http_response_code(500); echo '{"error":"DB error"}'; exit;
}
$role_id = $mysqli->insert_id;
$stmt->close();

echo json_encode(['success'=>1, 'id'=>$role_id]);