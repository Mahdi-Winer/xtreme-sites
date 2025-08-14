<?php
require_once __DIR__ . '/../../shared/database-config.php';
header('Content-Type: application/json');

$headers = getallheaders();
if (!isset($headers['Authorization']) || !preg_match('/Bearer\s+(.+)/', $headers['Authorization'], $m)) {
    http_response_code(401); echo '{"error":"Unauthorized"}'; exit;
}
// TODO: بررسی اعتبار توکن و نقش ادمین

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['project_id'])) { http_response_code(400); echo '{"error":"Bad request"}'; exit; }

$project_id = intval($data['project_id']);

// حذف ترجمه‌ها
$stmt = $mysqli->prepare("DELETE FROM project_translations WHERE project_id=?");
$stmt->bind_param('i', $project_id);
$stmt->execute();
$stmt->close();

// حذف پروژه
$stmt = $mysqli->prepare("DELETE FROM public_projects WHERE id=?");
$stmt->bind_param('i', $project_id);
$stmt->execute();
if ($stmt->affected_rows > 0) {
    echo '{"success":1}';
} else {
    http_response_code(404); echo '{"error":"not found"}';
}
$stmt->close();