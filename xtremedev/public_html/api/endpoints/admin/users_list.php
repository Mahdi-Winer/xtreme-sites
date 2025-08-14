<?php
// endpoints/admin/users_list.php

header('Content-Type: application/json');
require_once __DIR__.'/../../shared/database-config.php';
require_once __DIR__.'/../../shared/auth-helper.php';

// ========== 1. احراز هویت ادمین ==========
$admin_id = getAdminIdFromBearerToken();
if (!$admin_id) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

// ====== 2. دریافت access_token ======
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

// ========== 3. دریافت اطلاعات ادمین از auth مرکزی ==========
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

// فقط نقش‌های مجاز
$allowed_roles = ['superadmin', 'manager', 'support', 'read_only'];
if (!in_array($admininfo['role'], $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

// ========== 4. درخواست لیست کاربران از سرور auth مرکزی ==========
define('AUTH_API_BASE', 'https://auth.xtremedev.co/api/');
define('AUTH_API_CLIENT_ID', 'admin-panel'); // مقدار واقعی خودت
define('AUTH_API_CLIENT_SECRET', 'KB1UX!X%9MxPF7^hYqpL*hn}~,kdq>4RVtV~F=uW6u_U2HgvFWi?g9*=zpUp40%i%PP751gP2E+5nCaZk#JEzw9xE=E~6M1qqH9*'); // مقدار واقعی خودت

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$api_url = AUTH_API_BASE . "admin-users.php";

// استفاده از POST برای امنیت بیشتر:
$params = [
    'client_id'     => AUTH_API_CLIENT_ID,
    'client_secret' => AUTH_API_CLIENT_SECRET,
];
if($search !== '') $params['search'] = $search;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 12);
$users_resp = curl_exec($ch);
$users_httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($users_httpcode != 200 || !$users_resp) {
    http_response_code(500);
    echo json_encode(['error' => 'users_api_error']);
    exit;
}

$users = json_decode($users_resp, true);
if (!is_array($users)) $users = [];

// ========== 5. خروجی ==========
echo json_encode([
    'users' => $users
]);
exit;
?>