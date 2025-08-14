<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://xtremedev.co');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit;
require_once __DIR__ . '/../shared/database-config.php';
require_once __DIR__ . '/../shared/auth-helper.php';

$project_id = intval($_GET['project_id'] ?? 0);
$lang = isset($_GET['lang']) && in_array($_GET['lang'], ['fa','en']) ? $_GET['lang'] : 'fa';

$user_id = getUserIdFromBearerToken();
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error'=>'unauthorized']);
    exit;
}
if(!$project_id) {
    http_response_code(400);
    echo json_encode(['error'=>'project_id is required']);
    exit;
}

// فقط محصولات خریداری‌شده همین کاربر و پروژه با پرداخت موفق
$sql = "SELECT 
    p.id, p.price, p.thumbnail
FROM orders o
INNER JOIN invoices i ON i.order_id = o.id AND i.status = 'paid'
INNER JOIN products p ON p.id = o.product_id AND p.project_id = ? AND p.is_active=1
WHERE o.user_id = ? AND o.status = 'paid'
GROUP BY p.id";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param('ii', $project_id, $user_id);
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

// اضافه کردن ترجمه محصول
foreach($products as &$p) {
    $stmt = $mysqli->prepare("SELECT name, description FROM product_translations WHERE product_id=? AND lang=? LIMIT 1");
    $stmt->bind_param('is', $p['id'], $lang);
    $stmt->execute();
    $stmt->bind_result($name, $desc);
    if($stmt->fetch()) {
        $p['name'] = $name;
        $p['description'] = $desc;
    } else {
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