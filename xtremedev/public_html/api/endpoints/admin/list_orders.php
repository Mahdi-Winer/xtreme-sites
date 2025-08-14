<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../shared/database-config.php';

// ------ مقادیر client_id و client_secret (اینجا مقداردهی کن یا از کانفیگ بخوان) ------
define('AUTH_CLIENT_ID', 'admin-panel');
define('AUTH_CLIENT_SECRET', 'KB1UX!X%9MxPF7^hYqpL*hn}~,kdq>4RVtV~F=uW6u_U2HgvFWi?g9*=zpUp40%i%PP751gP2E+5nCaZk#JEzw9xE=E~6M1qqH9*');

// --------- Bearer Token ----------
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

// --------- چک نقش ادمین ---------
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

// --------- پارامترها ---------
$lang = (isset($_GET['lang']) && $_GET['lang'] === 'fa') ? 'fa' : 'en';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// --------- مرحله 1: خواندن سفارش‌ها ---------
$orders = [];
$user_ids = [];
$product_ids = [];
$sql = "SELECT id, user_id, product_id, project_id, status, created_at FROM orders ";
if($search !== '') {
    $sql .= "WHERE id LIKE ? OR product_id LIKE ? OR status LIKE ? ";
}
$sql .= "ORDER BY created_at DESC LIMIT 100";

if($search !== '') {
    $search_sql = "%$search%";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('sss', $search_sql, $search_sql, $search_sql);
    $stmt->execute();
    $stmt->bind_result($id, $user_id, $product_id, $project_id, $status, $created_at);
    while($stmt->fetch()) {
        $orders[] = [
            'id'         => $id,
            'user_id'    => $user_id,
            'product_id' => $product_id,
            'project_id' => $project_id,
            'status'     => $status,
            'created_at' => $created_at
        ];
        if($user_id) $user_ids[$user_id] = true;
        if($product_id) $product_ids[$product_id] = true;
    }
    $stmt->close();
} else {
    $res = $mysqli->query($sql);
    while($row = $res->fetch_assoc()) {
        $orders[] = $row;
        if($row['user_id']) $user_ids[$row['user_id']] = true;
        if($row['product_id']) $product_ids[$row['product_id']] = true;
    }
    $res->free();
}

// --------- مرحله 2: گرفتن ترجمه محصولات ---------
$product_titles = [];
if (count($product_ids)) {
    $ids = implode(',', array_map('intval', array_keys($product_ids)));
    $stmt = $mysqli->prepare("SELECT product_id, lang, name FROM product_translations WHERE product_id IN ($ids)");
    $stmt->execute();
    $stmt->bind_result($pid, $plang, $pname);
    while($stmt->fetch()) {
        if (!isset($product_titles[$pid])) $product_titles[$pid] = [];
        $product_titles[$pid][$plang] = $pname;
    }
    $stmt->close();
}

// --------- مرحله 3: گرفتن اطلاعات کاربران از API get_user.php ---------
$user_info_cache = [];
foreach(array_keys($user_ids) as $uid) {
    $ch = curl_init("https://auth.xtremedev.co/api/get_user.php");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'client_id'     => AUTH_CLIENT_ID,
        'client_secret' => AUTH_CLIENT_SECRET,
        'id'            => intval($uid)
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

// --------- مرحله 4: تکمیل خروجی ---------
foreach($orders as &$ord) {
    $uid = $ord['user_id'];
    $pid = $ord['product_id'];
    $ord['user_name'] = $user_info_cache[$uid]['name'] ?? '';
    $ord['user_email'] = $user_info_cache[$uid]['email'] ?? '';
    $ord['product_title'] = isset($product_titles[$pid][$lang]) ? $product_titles[$pid][$lang] : '';
    unset($ord['user_id'], $ord['product_id']);
}
unset($ord);

echo json_encode(['orders' => $orders], JSON_UNESCAPED_UNICODE);
exit;
?>