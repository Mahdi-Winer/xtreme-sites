<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit;

require_once __DIR__ . '/../shared/database-config.php';
require_once __DIR__ . '/../shared/auth-helper.php';

$lang = trim($_GET['lang'] ?? '');
if ($lang === '') $lang = 'fa';

$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$invoice_id) {
    http_response_code(400);
    echo json_encode(['success'=>false, 'error'=>'invoice_id required']);
    exit;
}

$user_id = getUserIdFromBearerToken();
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success'=>false, 'error'=>'unauthorized']);
    exit;
}

// گرفتن اطلاعات اصلی فاکتور، سفارش و محصول
$sql = "SELECT 
    i.id AS invoice_id,
    i.order_id,
    i.amount,
    i.status AS invoice_status,
    i.payment_gateway,
    i.paid_at,
    i.created_at AS invoice_created_at,
    o.product_id,
    o.user_id,
    o.status AS order_status,
    o.created_at AS order_created_at,
    p.project_id,
    p.price AS product_price,
    p.thumbnail,
    p.is_active
FROM invoices i
INNER JOIN orders o ON o.id = i.order_id AND o.user_id = ?
INNER JOIN products p ON p.id = o.product_id AND p.is_active = 1
WHERE i.id = ?
LIMIT 1";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('ii', $user_id, $invoice_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result(
    $invoice_id, $order_id, $amount, $invoice_status, $payment_gateway, $paid_at, $invoice_created_at,
    $product_id, $user_id_f, $order_status, $order_created_at,
    $prod_project_id, $product_price, $thumbnail, $is_active
);

if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['success'=>false, 'error'=>'invoice not found']);
    exit;
}
$stmt->close();

// گرفتن ترجمه محصول
$name = '';
$desc = '';
$stmtT = $mysqli->prepare("SELECT name, description FROM product_translations WHERE product_id=? AND lang=? LIMIT 1");
$stmtT->bind_param('is', $product_id, $lang);
$stmtT->execute();
$stmtT->bind_result($name, $desc);
$stmtT->fetch();
$stmtT->close();

// اگر ترجمه نبود، یک بار دیگر زبان پیش‌فرض را امتحان کن
if ($name == '') {
    $stmtT = $mysqli->prepare("SELECT name, description FROM product_translations WHERE product_id=? LIMIT 1");
    $stmtT->bind_param('i', $product_id);
    $stmtT->execute();
    $stmtT->bind_result($name, $desc);
    $stmtT->fetch();
    $stmtT->close();
}

// خروجی
echo json_encode([
    'success' => true,
    'invoice' => [
        'id'              => $invoice_id,
        'order_id'        => $order_id,
        'amount'          => $amount,
        'status'          => $invoice_status,
        'payment_gateway' => $payment_gateway,
        'paid_at'         => $paid_at,
        'created_at'      => $invoice_created_at,
        'products'        => [[
            'product_id'    => $product_id,
            'project_id'    => $prod_project_id,
            'price'         => $product_price,
            'thumbnail'     => $thumbnail,
            'name'          => $name,
            'desc'          => $desc
        ]],
        'order_status'    => $order_status,
        'order_created'   => $order_created_at,
    ]
], JSON_UNESCAPED_UNICODE);
exit;