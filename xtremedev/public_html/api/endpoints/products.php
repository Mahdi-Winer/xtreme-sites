<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit;
require_once __DIR__ . '/../shared/database-config.php';

$project_id = intval($_GET['project_id'] ?? 0);
$lang = $_GET['lang'] ?? 'fa';
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

if(!$project_id) {
    http_response_code(400);
    echo json_encode(['error'=>'project_id is required']);
    exit;
}

if($category_id) {
    // محصولات فعال این پروژه و این دسته‌بندی
    $stmt = $mysqli->prepare("SELECT id, price, thumbnail FROM products WHERE project_id=? AND category_id=? AND is_active=1");
    $stmt->bind_param('ii', $project_id, $category_id);
} else {
    // همه محصولات فعال این پروژه
    $stmt = $mysqli->prepare("SELECT id, price, thumbnail FROM products WHERE project_id=? AND is_active=1");
    $stmt->bind_param('i', $project_id);
}

$stmt->execute();
$stmt->bind_result($id, $price, $thumbnail);

$products = [];
while($stmt->fetch()) {
    $products[] = [
        'id' => $id,
        'price' => $price,
        'thumbnail' => $thumbnail
    ];
}
$stmt->close();

// اضافه کردن ترجمه
foreach($products as &$p) {
    $stmt = $mysqli->prepare("SELECT name, description FROM product_translations WHERE product_id=? AND lang=? LIMIT 1");
    $stmt->bind_param('is', $p['id'], $lang);
    $stmt->execute();
    $stmt->bind_result($name, $desc);
    if($stmt->fetch()) {
        $p['name'] = $name;
        $p['description'] = $desc;
    } else {
        // اگر ترجمه نبود، اولین ترجمه را بده
        $stmt2 = $mysqli->prepare("SELECT name, description FROM product_translations WHERE product_id=? LIMIT 1");
        $stmt2->bind_param('i', $p['id']);
        $stmt2->execute();
        $stmt2->bind_result($name2, $desc2);
        if($stmt2->fetch()) {
            $p['name'] = $name2;
            $p['description'] = $desc2;
        } else {
            $p['name'] = '';
            $p['description'] = '';
        }
        $stmt2->close();
    }
    $stmt->close();
}
unset($p);

echo json_encode($products, JSON_UNESCAPED_UNICODE);
exit;