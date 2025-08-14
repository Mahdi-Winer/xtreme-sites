<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit;
require_once __DIR__ . '/../shared/database-config.php';

$product_id = intval($_GET['id'] ?? 0);
$lang = $_GET['lang'] ?? 'fa';

if (!$product_id) {
    http_response_code(400);
    echo json_encode(['error' => 'id is required']);
    exit;
}

// اطلاعات اصلی محصول
$stmt = $mysqli->prepare("SELECT id, project_id, category_id, price, thumbnail, is_active FROM products WHERE id=?");
$stmt->bind_param('i', $product_id);
$stmt->execute();
$stmt->bind_result($id, $project_id, $category_id, $price, $thumbnail, $is_active);

if (!$stmt->fetch() || !$is_active) {
    http_response_code(404);
    echo json_encode(['error' => 'Product not found']);
    exit;
}
$stmt->close();

// ترجمه
$stmt = $mysqli->prepare("SELECT name, description FROM product_translations WHERE product_id=? AND lang=? LIMIT 1");
$stmt->bind_param('is', $product_id, $lang);
$stmt->execute();
$stmt->bind_result($name, $desc);
if ($stmt->fetch()) {
    // ترجمه پیدا شد
    $product_name = $name;
    $product_desc = $desc;
} else {
    // ترجمه نبود، اولین ترجمه را بده
    $stmt2 = $mysqli->prepare("SELECT name, description FROM product_translations WHERE product_id=? LIMIT 1");
    $stmt2->bind_param('i', $product_id);
    $stmt2->execute();
    $stmt2->bind_result($name2, $desc2);
    if ($stmt2->fetch()) {
        $product_name = $name2;
        $product_desc = $desc2;
    } else {
        $product_name = '';
        $product_desc = '';
    }
    $stmt2->close();
}
$stmt->close();

// داده خروجی
echo json_encode([
    'id' => $id,
    'project_id' => $project_id,
    'category_id' => $category_id,
    'name' => $product_name,
    'description' => $product_desc,
    'price' => $price,
    'thumbnail' => $thumbnail,
], JSON_UNESCAPED_UNICODE);
exit;