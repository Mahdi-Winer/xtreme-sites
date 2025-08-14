<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../shared/database-config.php';
require_once __DIR__ . '/../shared/auth-helper.php';

// ۱. احراز هویت با access_token
$user_id = getUserIdFromBearerToken();
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

// ۲. فقط POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST method required']);
    exit;
}

// ۳. پارامترها
$product_id = intval($_POST['product_id'] ?? 0);
$project_id = intval($_POST['project_id'] ?? 0);

if (!$product_id || !$project_id) {
    http_response_code(400);
    echo json_encode(['error' => 'product_id and project_id required']);
    exit;
}

// ۴. بررسی صحت محصول و پروژه
$stmt = $mysqli->prepare("SELECT price FROM products WHERE id=? AND project_id=? AND is_active=1");
$stmt->bind_param('ii', $product_id, $project_id);
$stmt->execute();
$stmt->bind_result($price);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'product not found']);
    exit;
}
$stmt->close();

// ۵. ثبت سفارش
$stmt = $mysqli->prepare("INSERT INTO orders (user_id, product_id, project_id, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
$stmt->bind_param('iii', $user_id, $product_id, $project_id);
if ($stmt->execute()) {
    $order_id = $stmt->insert_id;
    $stmt->close();

    // ۶. ساخت فاکتور
    $stmt2 = $mysqli->prepare("INSERT INTO invoices (order_id, amount, status, created_at) VALUES (?, ?, 'unpaid', NOW())");
    $stmt2->bind_param('ii', $order_id, $price);
    if ($stmt2->execute()) {
        $invoice_id = $stmt2->insert_id;
        $stmt2->close();

        echo json_encode([
            'success'    => true,
            'order_id'   => $order_id,
            'invoice_id' => $invoice_id,
            'amount'     => $price
        ]);
        exit;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'failed to create invoice']);
        exit;
    }
} else {
    http_response_code(500);
    echo json_encode(['error' => 'failed to create order']);
    exit;
}
?>