<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit;
require_once __DIR__ . '/../shared/database-config.php';

$category_id = intval($_GET['category_id'] ?? 0);
$lang = $_GET['lang'] ?? 'fa';
if(!$category_id) {
    http_response_code(400);
    echo json_encode(['error' => 'category_id is required']);
    exit;
}

$stmt = $mysqli->prepare("SELECT id, price, thumbnail FROM products WHERE category_id=? AND is_active=1");
$stmt->bind_param('i', $category_id);
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

foreach($products as &$p) {
    $stmt = $mysqli->prepare("SELECT name, description FROM product_translations WHERE product_id=? AND lang=? LIMIT 1");
    $stmt->bind_param('is', $p['id'], $lang);
    $stmt->execute();
    $stmt->bind_result($name, $desc);
    if($stmt->fetch()) {
        $p['name'] = $name;
        $p['description'] = $desc;
    } else {
        $p['name'] = '';
        $p['description'] = '';
    }
    $stmt->close();
}
unset($p);

echo json_encode($products, JSON_UNESCAPED_UNICODE);
exit;