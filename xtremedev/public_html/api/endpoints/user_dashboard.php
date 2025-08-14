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

// تعداد کل سفارشات
$stmt = $mysqli->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($orders_count);
$stmt->fetch();
$stmt->close();

// تعداد سفارشات فعال (مثلاً status = 'active' یا 'paid')
$stmt = $mysqli->prepare("SELECT COUNT(*) FROM orders WHERE user_id=? AND status IN ('active','paid')");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($active_orders_count);
$stmt->fetch();
$stmt->close();

// تعداد فاکتورهای پرداخت نشده
$stmt = $mysqli->prepare("SELECT COUNT(*) FROM invoices i JOIN orders o ON i.order_id = o.id WHERE o.user_id=? AND i.status='unpaid'");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($unpaid_invoices_count);
$stmt->fetch();
$stmt->close();

// تعداد تیکت‌های باز
$stmt = $mysqli->prepare("SELECT COUNT(*) FROM tickets WHERE user_id=? AND status='open'");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($open_tickets_count);
$stmt->fetch();
$stmt->close();

// آخرین ۳ سفارش
$stmt = $mysqli->prepare("SELECT id, status, created_at FROM orders WHERE user_id=? ORDER BY id DESC LIMIT 3");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($order_id, $order_status, $order_created_at);
$last_orders = [];
while ($stmt->fetch()) {
    $last_orders[] = [
        'order_id' => $order_id,
        'status' => $order_status,
        'created_at' => $order_created_at
    ];
}
$stmt->close();

// آخرین ۳ تیکت
$stmt = $mysqli->prepare("SELECT id, subject, status, created_at FROM tickets WHERE user_id=? ORDER BY id DESC LIMIT 3");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($ticket_id, $subject, $t_status, $t_created_at);
$last_tickets = [];
while ($stmt->fetch()) {
    $last_tickets[] = [
        'ticket_id' => $ticket_id,
        'subject' => $subject,
        'status' => $t_status,
        'created_at' => $t_created_at
    ];
}
$stmt->close();

echo json_encode([
    'orders_count' => $orders_count,
    'active_orders_count' => $active_orders_count,
    'unpaid_invoices_count' => $unpaid_invoices_count,
    'open_tickets_count' => $open_tickets_count,
    'last_orders' => $last_orders,
    'last_tickets' => $last_tickets
]);
?>