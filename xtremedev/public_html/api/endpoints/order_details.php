<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit;

require_once __DIR__ . '/../shared/database-config.php';
require_once __DIR__ . '/../shared/auth-helper.php';

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$lang = trim($_GET['lang'] ?? '');
if ($lang === '') $lang = 'en';

$user_id = getUserIdFromBearerToken();
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error'=>'unauthorized']);
    exit;
}

if (!$order_id) {
    http_response_code(400);
    echo json_encode(['error'=>'missing_order_id']);
    exit;
}

// واکشی اطلاعات سفارش و محصول
$sql = "SELECT 
    o.id AS order_id,
    o.user_id,
    o.product_id,
    o.status AS order_status,
    o.created_at AS order_created_at,
    i.id AS invoice_id,
    i.amount,
    i.status AS invoice_status,
    i.payment_gateway,
    i.paid_at,
    i.created_at AS invoice_created_at,
    p.price AS product_price,
    p.thumbnail,
    p.is_active
FROM orders o
LEFT JOIN invoices i ON i.order_id = o.id
INNER JOIN products p ON p.id = o.product_id
WHERE o.id = ? AND o.user_id = ?
LIMIT 1";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param('ii', $order_id, $user_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result(
    $order_id, $user_id_f, $product_id, $order_status, $order_created_at,
    $invoice_id, $amount, $invoice_status, $payment_gateway, $paid_at, $invoice_created_at,
    $product_price, $thumbnail, $is_active
);

if(!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error'=>'order_not_found']);
    exit;
}
$stmt->close();

// گرفتن ترجمه محصول
$name = '';
$desc = '';
$stmt2 = $mysqli->prepare("SELECT name, description FROM product_translations WHERE product_id=? AND lang=? LIMIT 1");
$stmt2->bind_param('is', $product_id, $lang);
$stmt2->execute();
$stmt2->bind_result($n, $d);
if($stmt2->fetch()) {
    $name = $n;
    $desc = $d;
}
$stmt2->close();

if(!$name) {
    $stmt3 = $mysqli->prepare("SELECT name, description FROM product_translations WHERE product_id=? LIMIT 1");
    $stmt3->bind_param('i', $product_id);
    $stmt3->execute();
    $stmt3->bind_result($n2, $d2);
    if($stmt3->fetch()) {
        $name = $n2;
        $desc = $d2;
    }
    $stmt3->close();
}

$out = [
    'order_id'          => $order_id,
    'order_status'      => $order_status,
    'order_created_at'  => $order_created_at,
    'invoice_id'        => $invoice_id,
    'amount'            => $amount,
    'invoice_status'    => $invoice_status,
    'payment_gateway'   => $payment_gateway,
    'paid_at'           => $paid_at,
    'invoice_created_at'=> $invoice_created_at,
    'product_id'        => $product_id,
    'product_price'     => $product_price,
    'product_thumbnail' => $thumbnail,
    'product_name'      => $name,
    'product_desc'      => $desc,
    'is_active'         => $is_active
];

echo json_encode($out, JSON_UNESCAPED_UNICODE);
exit;