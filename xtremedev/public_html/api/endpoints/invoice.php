<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../shared/database-config.php';
require_once __DIR__ . '/../shared/auth-helper.php';

$user_id = getUserIdFromBearerToken();
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

// گرفتن پارامتر invoice_id از GET
$invoice_id = intval($_GET['invoice_id'] ?? 0);
if (!$invoice_id) {
    http_response_code(400);
    echo json_encode(['error' => 'invoice_id required']);
    exit;
}

// فقط فاکتورهایی که متعلق به کاربرند
$stmt = $mysqli->prepare("
    SELECT i.id AS invoice_id, i.amount, i.status, i.created_at, i.paid_at,
           o.id AS order_id, o.status AS order_status,
           p.id AS product_id, pt.name AS product_name, pt.lang
    FROM invoices i
    JOIN orders o ON i.order_id = o.id
    JOIN products p ON o.product_id = p.id
    LEFT JOIN product_translations pt ON pt.product_id = p.id AND pt.lang = 'fa'
    WHERE i.id = ? AND o.user_id = ?
    LIMIT 1
");
$stmt->bind_param('ii', $invoice_id, $user_id);
$stmt->execute();

$stmt->store_result();
$stmt->bind_result($inv_id, $amount, $status, $created_at, $paid_at, $order_id, $order_status, $product_id, $product_name, $product_lang);

if ($stmt->fetch()) {
    $result = [
        'invoice_id' => $inv_id,
        'amount' => $amount,
        'status' => $status,
        'created_at' => $created_at,
        'paid_at' => $paid_at,
        'order_id' => $order_id,
        'order_status' => $order_status,
        'product_id' => $product_id,
        'product_name' => $product_name,
        'product_lang' => $product_lang
    ];
    echo json_encode($result);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'invoice not found']);
}
$stmt->close();
?>