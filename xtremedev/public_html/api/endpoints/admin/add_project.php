<?php
require_once __DIR__ . '/../../shared/database-config.php';
header('Content-Type: application/json');

$headers = getallheaders();
if (!isset($headers['Authorization']) || !preg_match('/Bearer\s+(.+)/', $headers['Authorization'], $m)) {
    http_response_code(401); echo '{"error":"Unauthorized"}'; exit;
}
// TODO: بررسی اعتبار توکن و نقش ادمین

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) { http_response_code(400); echo '{"error":"Bad request"}'; exit; }

$image = $data['image'] ?? '';
$is_active = isset($data['status']) && $data['status'] === 'active' ? 1 : 0;

// درج پروژه
$stmt = $mysqli->prepare("INSERT INTO public_projects (image, is_active, created_at) VALUES (?, ?, NOW())");
$stmt->bind_param('si', $image, $is_active);
if(!$stmt->execute()){
    http_response_code(500); echo '{"error":"DB error"}'; exit;
}
$project_id = $mysqli->insert_id;
$stmt->close();

// درج ترجمه‌ها
if (isset($data['translations']) && is_array($data['translations'])) {
    foreach ($data['translations'] as $lang => $t) {
        $title = $t['title'] ?? '';
        $desc = $t['description'] ?? '';
        $long_desc = $t['long_description'] ?? '';
        $stmt2 = $mysqli->prepare("INSERT INTO project_translations (project_id, lang, title, description, long_description) VALUES (?, ?, ?, ?, ?)");
        $stmt2->bind_param('issss', $project_id, $lang, $title, $desc, $long_desc);
        $stmt2->execute();
        $stmt2->close();
    }
}
echo json_encode(['success'=>1, 'id'=>$project_id]);