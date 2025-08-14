<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit;

require_once __DIR__ . '/../shared/database-config.php';
require_once __DIR__ . '/../shared/auth-helper.php';

$user_id = getUserIdFromBearerToken();
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST method required']);
    exit;
}

$invoice_id = intval($_POST['invoice_id'] ?? 0);
$gateway = trim($_POST['gateway'] ?? '');
$callbackurl = trim($_POST['callbackurl'] ?? '');

if (!$invoice_id || !$gateway) {
    http_response_code(400);
    echo json_encode(['error' => 'invoice_id and gateway required']);
    exit;
}

// فقط آی‌دی درگاه‌هایی که واقعاً داری اینجا بذار
$allowed_gateways = ['mellat', 'sep', 'zarinpal', 'sadad'];
if (!in_array($gateway, $allowed_gateways, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid gateway']);
    exit;
}

// بررسی و دریافت اطلاعات فاکتور
$stmt = $mysqli->prepare("SELECT i.amount, i.status FROM invoices i JOIN orders o ON i.order_id = o.id WHERE i.id=? AND o.user_id=? LIMIT 1");
$stmt->bind_param('ii', $invoice_id, $user_id);
$stmt->execute();
$stmt->bind_result($amount, $status);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'invoice not found']);
    exit;
}
$stmt->close();

if ($status !== 'unpaid') {
    http_response_code(400);
    echo json_encode(['error' => 'invoice already paid or cancelled']);
    exit;
}

// آپدیت درگاه انتخابی در جدول invoice
$stmt = $mysqli->prepare("UPDATE invoices SET payment_gateway=? WHERE id=?");
$stmt->bind_param('si', $gateway, $invoice_id);
$stmt->execute();
$stmt->close();

// ساخت لینک پرداخت و ارسال callbackurl به عنوان پارامتر
$payment_link = "https://pay.xtremedev.co/$gateway/pay.php?invoice_id=$invoice_id";
if ($callbackurl) {
    // امن‌سازی callbackurl
    $payment_link .= '&callbackurl=' . urlencode($callbackurl);
}

echo json_encode([
    'success' => true,
    'gateway' => $gateway,
    'payment_link' => $payment_link,
    'amount' => $amount
]);
?>