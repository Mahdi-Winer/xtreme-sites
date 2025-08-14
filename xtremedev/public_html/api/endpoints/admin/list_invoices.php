<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../shared/database-config.php';

// مقداردهی client_id و client_secret (در صورت نیاز اینجا مقداردهی کن)
define('AUTH_CLIENT_ID', 'admin-panel');
define('AUTH_CLIENT_SECRET', 'KB1UX!X%9MxPF7^hYqpL*hn}~,kdq>4RVtV~F=uW6u_U2HgvFWi?g9*=zpUp40%i%PP751gP2E+5nCaZk#JEzw9xE=E~6M1qqH9*');

// گرفتن توکن ادمین
function getBearerToken() {
    $header = '';
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) $header = $_SERVER['HTTP_AUTHORIZATION'];
    elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    elseif (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) $header = $headers['Authorization'];
    }
    if ($header && preg_match('/Bearer\s+(\S+)/', $header, $matches)) {
        return $matches[1];
    }
    return null;
}
$access_token = getBearerToken();
if (!$access_token) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

// چک نقش ادمین
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://auth.xtremedev.co/admininfo.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_token"
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 8);
$resp = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$admininfo = @json_decode($resp, true);
$allowed_roles = ['superadmin', 'manager', 'support', 'read_only'];
if ($httpcode != 200 || !$admininfo || empty($admininfo['role']) || !in_array($admininfo['role'], $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['error'=>'forbidden']);
    exit;
}

// پارامتر زبان و سرچ
$lang = (isset($_GET['lang']) && $_GET['lang'] === 'fa') ? 'fa' : 'en';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// مرحله ۱: دریافت فاکتورها و order_id
$invoices = [];
$order_ids = [];
$sql = "SELECT id, order_id, amount, status, payment_gateway, paid_at, created_at FROM invoices ";
if($search !== '') {
    $sql .= "WHERE id LIKE ? OR order_id LIKE ? OR status LIKE ? ";
}
$sql .= "ORDER BY created_at DESC LIMIT 100";

if($search !== '') {
    $search_sql = "%$search%";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('sss', $search_sql, $search_sql, $search_sql);
    $stmt->execute();
    $stmt->bind_result($id, $order_id, $amount, $status, $gateway, $paid_at, $created_at);
    while($stmt->fetch()) {
        $invoices[] = [
            'id' => $id,
            'order_id' => $order_id,
            'amount' => $amount,
            'status' => $status,
            'payment_gateway' => $gateway,
            'paid_at' => $paid_at,
            'created_at' => $created_at,
        ];
        if($order_id) $order_ids[$order_id] = true;
    }
    $stmt->close();
} else {
    $res = $mysqli->query($sql);
    while($row = $res->fetch_assoc()) {
        $invoices[] = $row;
        if($row['order_id']) $order_ids[$row['order_id']] = true;
    }
    $res->free();
}

// مرحله ۲: گرفتن user_id هر سفارش
$user_ids = [];
$order_user_map = [];
if(count($order_ids)) {
    $ids = implode(',', array_map('intval', array_keys($order_ids)));
    $res = $mysqli->query("SELECT id, user_id FROM orders WHERE id IN ($ids)");
    while($row = $res->fetch_assoc()) {
        $order_user_map[$row['id']] = $row['user_id'];
        if($row['user_id']) $user_ids[$row['user_id']] = true;
    }
    $res->free();
}

// مرحله ۳: گرفتن اطلاعات کاربران از API جدید (با POST)
$user_info_cache = [];
foreach(array_keys($user_ids) as $uid) {
    $ch = curl_init("https://auth.xtremedev.co/api/get_user.php");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'client_id' => AUTH_CLIENT_ID,
        'client_secret' => AUTH_CLIENT_SECRET,
        'id' => intval($uid)
    ]);
    $resp = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode == 200 && $resp) {
        $info = json_decode($resp, true);
        $user_info_cache[$uid] = [
            'name' => $info['name'] ?? '',
            'email' => $info['email'] ?? ''
        ];
    } else {
        $user_info_cache[$uid] = ['name'=>'', 'email'=>''];
    }
}

// مرحله ۴: خروجی نهایی
foreach($invoices as &$inv) {
    $oid = $inv['order_id'];
    $uid = $order_user_map[$oid] ?? null;
    $inv['user_name'] = $user_info_cache[$uid]['name'] ?? '';
    $inv['user_email'] = $user_info_cache[$uid]['email'] ?? '';
}
unset($inv);

echo json_encode(['invoices' => $invoices], JSON_UNESCAPED_UNICODE);
exit;
?>