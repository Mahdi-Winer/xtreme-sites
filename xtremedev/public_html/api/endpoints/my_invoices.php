<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit;

require_once __DIR__ . '/../shared/database-config.php';
require_once __DIR__ . '/../shared/auth-helper.php';

$lang = trim($_GET['lang'] ?? '');
if ($lang === '') $lang = 'en';

$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

$user_id = getUserIdFromBearerToken();
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error'=>'unauthorized']);
    exit;
}

$filter = "";
$params = [];
$types = "i";
$params[] = $user_id;
if($project_id) {
    $filter = "AND p.project_id = ? ";
    $types .= "i";
    $params[] = $project_id;
}

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
{$filter}
ORDER BY i.created_at DESC";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$stmt->store_result(); // مهم: نتایج را بافر کن
$stmt->bind_result(
    $invoice_id, $order_id, $amount, $invoice_status, $payment_gateway, $paid_at, $invoice_created_at,
    $product_id, $user_id_f, $order_status, $order_created_at,
    $prod_project_id, $product_price, $thumbnail, $is_active
);

$product_ids = [];
while($stmt->fetch()) {
    $product_ids[$product_id] = true;
}
$stmt->data_seek(0); // بازگشت به اول رزلت

// گرفتن همه ترجمه‌ها یکجا
$translations = [];
if (!empty($product_ids)) {
    $ids = implode(',', array_keys($product_ids));
    $sqlT = "SELECT product_id, name, description FROM product_translations WHERE product_id IN ($ids) AND lang = ?";
    $stmtT = $mysqli->prepare($sqlT);
    $stmtT->bind_param('s', $lang);
    $stmtT->execute();
    $stmtT->bind_result($pid, $name, $desc);
    while($stmtT->fetch()) {
        $translations[$pid] = ['name' => $name, 'desc' => $desc];
    }
    $stmtT->close();

    foreach ($product_ids as $pid => $_) {
        if (!isset($translations[$pid])) {
            $sqlT2 = "SELECT name, description FROM product_translations WHERE product_id=? LIMIT 1";
            $stmtT2 = $mysqli->prepare($sqlT2);
            $stmtT2->bind_param('i', $pid);
            $stmtT2->execute();
            $stmtT2->bind_result($name, $desc);
            if($stmtT2->fetch()) {
                $translations[$pid] = ['name' => $name, 'desc' => $desc];
            } else {
                $translations[$pid] = ['name' => '', 'desc' => ''];
            }
            $stmtT2->close();
        }
    }
}

$out = [];
$stmt->data_seek(0);
while($stmt->fetch()) {
    $name = $translations[$product_id]['name'] ?? '';
    $desc = $translations[$product_id]['desc'] ?? '';

    $out[] = [
        'invoice_id'        => $invoice_id,
        'order_id'          => $order_id,
        'amount'            => $amount,
        'status'            => $invoice_status, // فیلد status
        'payment_gateway'   => $payment_gateway,
        'paid_at'           => $paid_at,
        'created_at'        => $invoice_created_at,
        'product_id'        => $product_id,
        'project_id'        => $prod_project_id,
        'product_price'     => $product_price,
        'product_thumbnail' => $thumbnail,
        'product_name'      => $name,
        'product_desc'      => $desc,
        'order_status'      => $order_status,
        'order_created'     => $order_created_at,
    ];
}
$stmt->close();

echo json_encode($out, JSON_UNESCAPED_UNICODE);
exit;