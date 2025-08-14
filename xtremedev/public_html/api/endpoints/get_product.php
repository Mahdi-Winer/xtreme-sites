<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit;
require_once __DIR__ . '/../shared/database-config.php';

$product_id = intval($_GET['product_id'] ?? 0);
$lang = $_GET['lang'] ?? 'fa';
if(!$product_id) {
    http_response_code(400);
    echo json_encode(['error' => 'product_id is required']);
    exit;
}

$stmt = $mysqli->prepare("SELECT id, price, thumbnail, category_id, is_active FROM products WHERE id=? LIMIT 1");
$stmt->bind_param('i', $product_id);
$stmt->execute();
$stmt->bind_result($id, $price, $thumbnail, $category_id, $is_active);
if(!$stmt->fetch() || !$is_active) {
    http_response_code(404);
    echo json_encode(['error' => 'Product not found']);
    exit;
}
$stmt->close();

$product = [
    'id' => $id,
    'price' => $price,
    'thumbnail' => $thumbnail,
    'category_id' => $category_id
];

// ترجمه محصول
$stmt = $mysqli->prepare("SELECT name, description FROM product_translations WHERE product_id=? AND lang=? LIMIT 1");
$stmt->bind_param('is', $id, $lang);
$stmt->execute();
$stmt->bind_result($name, $desc);
if($stmt->fetch()) {
    $product['name'] = $name;
    $product['description'] = $desc;
} else {
    $product['name'] = '';
    $product['description'] = '';
}
$stmt->close();

// تصاویر بیشتر محصول
$stmt = $mysqli->prepare("SELECT image_path FROM product_images WHERE product_id=? ORDER BY order_num ASC, id ASC");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($image_path);
$images = [];
while($stmt->fetch()) {
    $images[] = $image_path;
}
$product['images'] = $images;
$stmt->close();

echo json_encode($product, JSON_UNESCAPED_UNICODE);
exit;