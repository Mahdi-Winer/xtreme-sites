<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit;
require_once __DIR__ . '/../shared/database-config.php';

$lang = $_GET['lang'] ?? 'fa';
$project_id = intval($_GET['project_id'] ?? 0);
$parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : null;

if(!$project_id) {
    http_response_code(400);
    echo json_encode(['error'=>'project_id is required']);
    exit;
}

// فقط دسته‌بندی‌های فعال این پروژه
if($parent_id !== null) {
    $stmt = $mysqli->prepare("SELECT id, slug FROM product_categories WHERE is_active=1 AND project_id=? AND parent_id=? ORDER BY order_num ASC, id ASC");
    $stmt->bind_param('ii', $project_id, $parent_id);
} else {
    $stmt = $mysqli->prepare("SELECT id, slug FROM product_categories WHERE is_active=1 AND project_id=? AND (parent_id IS NULL OR parent_id=0) ORDER BY order_num ASC, id ASC");
    $stmt->bind_param('i', $project_id);
}

$stmt->execute();
$stmt->bind_result($id, $slug);

$categories = [];
while($stmt->fetch()) {
    $categories[] = [
        'id' => $id,
        'slug' => $slug
    ];
}
$stmt->close();

// ترجمه عنوان و توضیح دسته
foreach($categories as &$c) {
    $stmt = $mysqli->prepare("SELECT title, description FROM product_categories_translation WHERE category_id=? AND lang=? LIMIT 1");
    $stmt->bind_param('is', $c['id'], $lang);
    $stmt->execute();
    $stmt->bind_result($title, $desc);
    if($stmt->fetch()) {
        $c['title'] = $title;
        $c['description'] = $desc;
    } else {
        $c['title'] = '';
        $c['description'] = '';
    }
    $stmt->close();
}
unset($c);

echo json_encode($categories, JSON_UNESCAPED_UNICODE);
exit;