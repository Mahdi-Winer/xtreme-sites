<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../shared/database-config.php';
require_once __DIR__ . '/../../shared/auth-admin-helper.php';

getAdminInfoOrExit(['superadmin','manager']); // فقط سوپرادمین و منیجر

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) { http_response_code(400); echo '{"error":"Bad request"}'; exit; }

$title = trim($data['title'] ?? '');
$desc = trim($data['description'] ?? '');
$is_active = isset($data['is_active']) ? (int)$data['is_active'] : 1;

if (!$title) {
    http_response_code(422); echo '{"error":"title required"}'; exit;
}

$stmt = $mysqli->prepare("INSERT INTO joinus_projects (title, description, is_active) VALUES (?, ?, ?)");
$stmt->bind_param('ssi', $title, $desc, $is_active);
if(!$stmt->execute()){
    http_response_code(500); echo '{"error":"DB error"}'; exit;
}
$project_id = $mysqli->insert_id;
$stmt->close();

echo json_encode(['success'=>1, 'id'=>$project_id]);