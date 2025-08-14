<?php
require_once __DIR__ . '/../../shared/database-config.php';
header('Content-Type: application/json');

$headers = getallheaders();
if (!isset($headers['Authorization']) || !preg_match('/Bearer\s+(.+)/', $headers['Authorization'], $m)) {
    http_response_code(401); echo '{"error":"Unauthorized"}'; exit;
}
$token = $m[1];
// TODO: بررسی اعتبار توکن

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'fa';

$stmt = $mysqli->prepare("
    SELECT p.id, p.image, p.is_active, p.created_at,
           t.title, t.description, t.long_description
    FROM public_projects p
    LEFT JOIN project_translations t ON t.project_id = p.id AND t.lang = ?
    WHERE p.id = ?
    LIMIT 1
");
$stmt->bind_param('si', $lang, $id);
$stmt->execute();
$stmt->bind_result($id, $image, $is_active, $created_at, $title, $desc, $long_desc);

if($stmt->fetch()){
    echo json_encode([
        'id' => $id,
        'image' => $image,
        'title' => $title,
        'description' => $desc,
        'long_description' => $long_desc,
        'status' => $is_active ? 'active' : 'inactive',
        'created_at' => $created_at,
    ]);
} else {
    http_response_code(404); echo '{"error":"not found"}';
}
$stmt->close();