<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit;
require_once __DIR__ . '/../shared/database-config.php';

$product_id = intval($_GET['product_id'] ?? 0);
if(!$product_id) {
    http_response_code(400);
    echo json_encode(['error' => 'product_id is required']);
    exit;
}

$stmt = $mysqli->prepare("SELECT gift_product_id, max_buyers, active FROM product_gifts WHERE main_product_id=?");
$stmt->bind_param('i', $product_id);
$stmt->execute();
$stmt->bind_result($gift_id, $max_buyers, $active);

$gifts = [];
while($stmt->fetch()) {
    $gifts[] = [
        'gift_product_id' => $gift_id,
        'max_buyers' => $max_buyers,
        'active' => $active
    ];
}
$stmt->close();

echo json_encode($gifts, JSON_UNESCAPED_UNICODE);
exit;