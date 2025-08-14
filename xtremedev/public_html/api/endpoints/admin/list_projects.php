<?php
require_once __DIR__ . '/../../shared/database-config.php';
header('Content-Type: application/json');

// بررسی توکن (نمونه ساده)
$headers = getallheaders();
if (!isset($headers['Authorization']) || !preg_match('/Bearer\s+(.+)/', $headers['Authorization'], $m)) {
    http_response_code(401); echo '{"error":"Unauthorized"}'; exit;
}
$token = $m[1];
// TODO: بررسی اعتبار توکن و نقش ادمین

$lang = isset($_GET['lang']) ? $_GET['lang'] : 'fa';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = [];
$params = [];
$types = '';

$sql = "
    SELECT p.id, p.image, p.is_active, p.created_at,
           t.title, t.description, t.long_description
    FROM public_projects p
    LEFT JOIN project_translations t ON t.project_id = p.id AND t.lang = ?
";

$types .= 's';
$params[] = $lang;

if ($search !== '') {
    $sql .= " WHERE (p.id = ? OR t.title LIKE ?)";
    $types .= 'is';
    $params[] = intval($search);
    $params[] = '%'.$search.'%';
}

$sql .= " ORDER BY p.id DESC";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$stmt->bind_result($id, $image, $is_active, $created_at, $title, $desc, $long_desc);

$list = [];
while($stmt->fetch()){
    $list[] = [
        'id' => $id,
        'image' => $image,
        'title' => $title,
        'description' => $desc,
        'long_description' => $long_desc,
        'status' => $is_active ? 'active' : 'inactive',
        'created_at' => $created_at,
    ];
}
$stmt->close();
echo json_encode($list);