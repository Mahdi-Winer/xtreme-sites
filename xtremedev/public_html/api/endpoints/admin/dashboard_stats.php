<?php
// endpoints/admin/dashboard_stats.php

header('Content-Type: application/json');
require_once __DIR__.'/../../shared/database-config.php';
require_once __DIR__.'/../../shared/auth-helper.php';

// ----------- دریافت lang از GET یا هدر -----------
$lang = 'fa'; // پیشفرض
if (isset($_GET['lang']) && preg_match('/^[a-z]{2}$/', $_GET['lang'])) {
    $lang = $_GET['lang'];
} elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    if (isset($langs[0]) && preg_match('/^[a-z]{2}$/', $langs[0])) {
        $lang = $langs[0];
    }
}
if (!in_array($lang, ['fa','en'])) $lang = 'fa';

// احراز هویت ادمین
$admin_id = getAdminIdFromBearerToken();
if (!$admin_id) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

// استخراج توکن از هدر
$access_token = null;
$header = '';
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $header = $_SERVER['HTTP_AUTHORIZATION'];
} elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
} elseif (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
        $header = $headers['Authorization'];
    }
}
if ($header && preg_match('/Bearer\s+(\S+)/', $header, $matches)) {
    $access_token = $matches[1];
}
if (!$access_token) {
    http_response_code(401);
    echo json_encode(['error' => 'no_token']);
    exit;
}

// دریافت اطلاعات ادمین از auth مرکزی
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://auth.xtremedev.co/admininfo.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_token"
]);
$resp = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($httpcode != 200) {
    http_response_code(401);
    echo json_encode(['error' => 'invalid_admin_token']);
    exit;
}
$admininfo = json_decode($resp, true);
if (!$admininfo || !isset($admininfo['role'])) {
    http_response_code(401);
    echo json_encode(['error' => 'invalid_admin_info']);
    exit;
}
if ($admininfo['role'] !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

// دریافت لیست کاربران از auth مرکزی
$users_api_url = "https://auth.xtremedev.co/api/users.php";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $users_api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_token"
]);
$users_resp = curl_exec($ch);
curl_close($ch);

$users = [];
if ($users_resp) {
    $users = @json_decode($users_resp, true);
    if (!is_array($users)) $users = [];
}
$users_count = count($users);
$users_map = [];
foreach($users as $u) {
    if (isset($u['id'])) $users_map[$u['id']] = $u;
}

// آمار کلی سایر جداول
function get_count($mysqli, $table) {
    $res = $mysqli->query("SELECT COUNT(*) FROM `$table`");
    $cnt = $res ? $res->fetch_row()[0] : 0;
    return $cnt;
}
$orders_count   = get_count($mysqli, 'orders');
$products_count = get_count($mysqli, 'products');
$invoices_count = get_count($mysqli, 'invoices');
$tickets_count  = get_count($mysqli, 'tickets');

// Top Products (بر اساس lang)
$top_products = [];
$stmt = $mysqli->prepare("
    SELECT 
        p.id, 
        p.price, 
        COALESCE(pt.name, '') AS name, 
        COALESCE(pt.description, '') AS description,
        COUNT(o.id) as total_sales
    FROM products p
    LEFT JOIN product_translations pt ON pt.product_id = p.id AND pt.lang = ?
    LEFT JOIN orders o ON o.product_id = p.id AND o.status='paid'
    GROUP BY p.id
    ORDER BY total_sales DESC
    LIMIT 5
");
$stmt->bind_param('s', $lang);
$stmt->execute();
$stmt->bind_result($pid, $price, $name, $description, $total_sales);
while($stmt->fetch()) {
    $top_products[] = [
        'id' => $pid,
        'price' => $price,
        'name' => $name,
        'description' => $description,
        'total_sales' => $total_sales
    ];
}
$stmt->close();

// آخرین سفارشات (بر اساس lang)
$recent_orders = [];
$stmt = $mysqli->prepare("
    SELECT 
        o.id, o.user_id, o.product_id, o.created_at, o.status, 
        p.price, 
        COALESCE(pt.name, '') AS product_name,
        COALESCE(pt.description, '') AS product_description
    FROM orders o
    LEFT JOIN products p ON p.id = o.product_id
    LEFT JOIN product_translations pt ON pt.product_id = p.id AND pt.lang = ?
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$stmt->bind_param('s', $lang);
$stmt->execute();
$stmt->bind_result($oid, $user_id, $product_id, $created_at, $status, $oprice, $product_name, $product_description);
while($stmt->fetch()) {
    $recent_orders[] = [
        'id' => $oid,
        'user_id' => $user_id,
        'product_id' => $product_id,
        'created_at' => $created_at,
        'status' => $status,
        'price' => $oprice,
        'product_name' => $product_name,
        'product_description' => $product_description
    ];
}
$stmt->close();

// آخرین فاکتورها (user_id از طریق orders)
$recent_invoices = [];
$res = $mysqli->query("
    SELECT 
        i.id, i.order_id, i.amount, i.status, i.payment_gateway, i.paid_at, i.created_at,
        o.user_id
    FROM invoices i
    LEFT JOIN orders o ON o.id = i.order_id
    ORDER BY i.created_at DESC LIMIT 5
");
while($row = $res->fetch_assoc()) $recent_invoices[] = $row;

// آخرین تیکت‌ها
$recent_tickets = [];
$res = $mysqli->query("SELECT t.id, t.subject, t.status, t.created_at, t.user_id
    FROM tickets t
    ORDER BY t.created_at DESC LIMIT 5");
while($row = $res->fetch_assoc()) $recent_tickets[] = $row;

// ----------- نمودارها و مجموع درآمد/تعداد -----------

// نمودار ۳۰ روز
$chart_labels = [];
$chart_paid_amount = [];
$chart_paid_count = [];
for ($i = 29; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = $day;
    $q = $mysqli->query("SELECT COUNT(*) as cnt, SUM(amount) as sum FROM invoices WHERE status='paid' AND DATE(paid_at)='$day'");
    $r = $q->fetch_assoc();
    $chart_paid_count[] = intval($r['cnt']);
    $chart_paid_amount[] = intval($r['sum'] ?: 0);
}

// نمودار ۷ روز
$chart7_labels = [];
$chart7_paid_amount = [];
$chart7_paid_count = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $chart7_labels[] = $day;
    $q = $mysqli->query("SELECT COUNT(*) as cnt, SUM(amount) as sum FROM invoices WHERE status='paid' AND DATE(paid_at)='$day'");
    $r = $q->fetch_assoc();
    $chart7_paid_count[] = intval($r['cnt']);
    $chart7_paid_amount[] = intval($r['sum'] ?: 0);
}

// مجموع درآمد و فروش 30 روز اخیر
$res = $mysqli->query("SELECT SUM(amount) as total_amount, COUNT(*) as total_count FROM invoices WHERE status='paid' AND DATE(paid_at) >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)");
$row = $res ? $res->fetch_assoc() : ['total_amount' => 0, 'total_count' => 0];
$sum_amount_30 = intval($row['total_amount']);
$sum_count_30  = intval($row['total_count']);

// مجموع درآمد و فروش 7 روز اخیر
$res = $mysqli->query("SELECT SUM(amount) as total_amount, COUNT(*) as total_count FROM invoices WHERE status='paid' AND DATE(paid_at) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)");
$row = $res ? $res->fetch_assoc() : ['total_amount' => 0, 'total_count' => 0];
$sum_amount_7 = intval($row['total_amount']);
$sum_count_7  = intval($row['total_count']);

// مجموع درآمد و فروش امروز
$todayYmd = date('Y-m-d');
$res = $mysqli->query("SELECT SUM(amount) as total_amount, COUNT(*) as total_count FROM invoices WHERE status='paid' AND DATE(paid_at) = '$todayYmd'");
$row = $res ? $res->fetch_assoc() : ['total_amount' => 0, 'total_count' => 0];
$sum_amount_today = intval($row['total_amount']);
$sum_count_today  = intval($row['total_count']);

// -------------- خروجی نهایی --------------
echo json_encode([
    'admin' => [
        'id'         => $admininfo['id'],
        'username'   => $admininfo['username'],
        'email'      => $admininfo['email'],
        'role'       => $admininfo['role'],
        'status'     => $admininfo['status'] ?? null,
        'created_at' => $admininfo['created_at'] ?? null
    ],
    'stats' => [
        'users_count'    => $users_count,
        'orders_count'   => $orders_count,
        'products_count' => $products_count,
        'invoices_count' => $invoices_count,
        'tickets_count'  => $tickets_count,
    ],
    'users'           => $users,
    'top_products'    => $top_products,
    'recent_orders'   => $recent_orders,
    'recent_invoices' => $recent_invoices,
    'recent_tickets'  => $recent_tickets,
    'chart_30days'    => [
        'labels'      => $chart_labels,
        'paid_amount' => $chart_paid_amount,
        'paid_count'  => $chart_paid_count,
        'sum_amount'  => $sum_amount_30,
        'sum_count'   => $sum_count_30
    ],
    'chart_7days'     => [
        'labels'      => $chart7_labels,
        'paid_amount' => $chart7_paid_amount,
        'paid_count'  => $chart7_paid_count,
        'sum_amount'  => $sum_amount_7,
        'sum_count'   => $sum_count_7
    ],
    'today' => [
        'sum_amount' => $sum_amount_today,
        'sum_count'  => $sum_count_today
    ]
]);
exit;
?>