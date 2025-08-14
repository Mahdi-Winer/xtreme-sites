<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit;
require_once __DIR__ . '/../shared/database-config.php';
require_once __DIR__ . '/../shared/auth-helper.php';

// احراز هویت
$user_id = getUserIdFromBearerToken();
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$product_id = intval($_POST['product_id'] ?? 0);
if (!$product_id) {
    http_response_code(400);
    echo json_encode(['error' => 'product_id is required']);
    exit;
}

// قیمت و وضعیت محصول + project_id
$stmt = $mysqli->prepare("SELECT price, project_id FROM products WHERE id=? AND is_active=1 LIMIT 1");
$stmt->bind_param('i', $product_id);
$stmt->execute();
$stmt->bind_result($price, $project_id);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'product not found']);
    exit;
}
$stmt->close();

// پیدا کردن سفارش باز (پرداخت‌نشده) این کاربر و این محصول
$stmt = $mysqli->prepare("SELECT id FROM orders WHERE user_id=? AND product_id=? AND status='pending' LIMIT 1");
$stmt->bind_param('ii', $user_id, $product_id);
$stmt->execute();
$stmt->bind_result($order_id);
$order_exists = $stmt->fetch();
$stmt->close();

if ($order_exists) {
    // اگه سفارش باز داری، دنبال فاکتور unpaid همین order بگرد
    $stmt = $mysqli->prepare("SELECT id FROM invoices WHERE order_id=? AND status='unpaid' LIMIT 1");
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $stmt->bind_result($invoice_id);
    if ($stmt->fetch()) {
        // همین اینویس رو برگردون
        echo json_encode(['success' => true, 'order_id' => $order_id, 'invoice_id' => $invoice_id]);
        exit;
    }
    $stmt->close();
} else {
    // سفارش باز نداری، سفارش جدید بساز (حتماً project_id را هم درج کن)
    $stmt = $mysqli->prepare("INSERT INTO orders (user_id, product_id, project_id, status, is_gift, created_at) VALUES (?, ?, ?, 'pending', 0, NOW())");
    $stmt->bind_param('iii', $user_id, $product_id, $project_id);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error' => 'failed to create order']);
        exit;
    }
    $order_id = $stmt->insert_id;
    $stmt->close();
}

// حالا برای این order یک اینویس unpaid جدید بساز
$stmt = $mysqli->prepare("INSERT INTO invoices (order_id, amount, status, created_at) VALUES (?, ?, 'unpaid', NOW())");
$stmt->bind_param('id', $order_id, $price);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'failed to create invoice']);
    exit;
}
$invoice_id = $stmt->insert_id;
$stmt->close();

echo json_encode([
    'success' => true,
    'order_id' => $order_id,
    'invoice_id' => $invoice_id
]);
exit;