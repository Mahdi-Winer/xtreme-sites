<?php
// token.php -- OAuth2 Token Endpoint

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'invalid_request', 'error_description' => 'POST method required']);
    exit;
}

require_once __DIR__ . '/shared/inc/database-config.php';
require_once __DIR__ . '/shared/inc/config.php'; // ALLOWED_LANGS و DEFAULT_LANG اینجا

define('LANG_DIR', __DIR__.'/shared/assets/languages/');

// زبان از ورودی POST یا GET یا کوکی یا دیفالت
$allowed_langs = defined('ALLOWED_LANGS') ? ALLOWED_LANGS : ['en'];
$lang = $_POST['lang'] ?? ($_GET['lang'] ?? ($_COOKIE['site_lang'] ?? (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'en')));
if (!in_array($lang, $allowed_langs)) $lang = defined('DEFAULT_LANG') ? DEFAULT_LANG : 'en';

// ست‌کردن کوکی زبان (برای ای پی آی و سایر درخواست‌های بعدی)
setcookie('site_lang', $lang, [
    'expires' => time()+60*60*24*30,
    'path' => '/',
    'httponly' => false,
    'samesite' => 'Lax'
]);

// بارگذاری ترجمه‌ها فقط برای زبان فعال از فایل
$tr = [];
$tr_file = LANG_DIR . $lang . '.json';
if (file_exists($tr_file)) {
    $tr = json_decode(file_get_contents($tr_file), true);
}
if (!$tr) $tr = [];

// دریافت پارامترها
$grant_type    = $_POST['grant_type']    ?? '';
$code          = $_POST['code']          ?? '';
$refresh_token = $_POST['refresh_token'] ?? '';
$redirect_uri  = $_POST['redirect_uri']  ?? '';
$client_id     = $_POST['client_id']     ?? '';
$client_secret = $_POST['client_secret'] ?? '';

// ======================= grant_type: refresh_token =======================
if ($grant_type === 'refresh_token') {
    // چک client_id و client_secret
    $stmt = $mysqli->prepare("SELECT id, client_secret FROM clients WHERE client_id=? LIMIT 1");
    $stmt->bind_param('s', $client_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows !== 1) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_client', 'error_description' => $tr['unknown_client_id'] ?? 'Unknown client_id']);
        exit;
    }
    $stmt->bind_result($cid, $db_secret);
    $stmt->fetch();
    $stmt->close();

    if (!hash_equals($db_secret, $client_secret)) {
        http_response_code(401);
        echo json_encode(['error' => 'invalid_client', 'error_description' => $tr['wrong_client_secret'] ?? 'Wrong client_secret']);
        exit;
    }

    // پیدا کردن refresh_token معتبر و غیرمنقضی
    $stmt = $mysqli->prepare("SELECT user_id, scope FROM oauth_tokens WHERE refresh_token=? AND client_id=? LIMIT 1");
    $stmt->bind_param('ss', $refresh_token, $client_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows !== 1) {
        $stmt->close();
        http_response_code(400);
        echo json_encode(['error' => 'invalid_grant', 'error_description' => $tr['invalid_refresh_token'] ?? 'Invalid refresh_token']);
        exit;
    }
    $stmt->bind_result($user_id, $scope);
    $stmt->fetch();
    $stmt->close();

    // تولید توکن جدید
    $expires_in = 3600; // یک ساعت (ثانیه)
    $new_access_token  = bin2hex(random_bytes(32));
    $new_refresh_token = bin2hex(random_bytes(40));
    $exp = date('Y-m-d H:i:s', time() + $expires_in);

    // ذخیره توکن جدید
    $stmt = $mysqli->prepare("INSERT INTO oauth_tokens (access_token, refresh_token, user_id, client_id, scope, expires_at, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param('ssiiss', $new_access_token, $new_refresh_token, $user_id, $cid, $scope, $exp);
    $stmt->execute();
    $stmt->close();

    // پاک‌کردن توکن قبلی (اختیاری، ولی امن‌تر)
    $stmt = $mysqli->prepare("DELETE FROM oauth_tokens WHERE refresh_token=?");
    $stmt->bind_param('s', $refresh_token);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'access_token'  => $new_access_token,
        'token_type'    => 'bearer',
        'expires_in'    => $expires_in,
        'refresh_token' => $new_refresh_token,
        'scope'         => $scope,
    ]);
    exit;
}

// =================== grant_type: authorization_code =====================
if ($grant_type !== 'authorization_code') {
    http_response_code(400);
    echo json_encode(['error' => 'unsupported_grant_type', 'error_description' => $tr['unsupported_grant_type'] ?? 'Unsupported grant_type']);
    exit;
}

// 1. چک client_id و client_secret
$stmt = $mysqli->prepare("SELECT id, client_secret, redirect_uris FROM clients WHERE client_id=? LIMIT 1");
$stmt->bind_param('s', $client_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows !== 1) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_client', 'error_description' => $tr['unknown_client_id'] ?? 'Unknown client_id']);
    exit;
}
$stmt->bind_result($cid, $db_secret, $c_redirect_uris);
$stmt->fetch();
$stmt->close();

if (!hash_equals($db_secret, $client_secret)) {
    http_response_code(401);
    echo json_encode(['error' => 'invalid_client', 'error_description' => $tr['wrong_client_secret'] ?? 'Wrong client_secret']);
    exit;
}

// 2. چک کد موقت (authorization code)
$stmt = $mysqli->prepare("SELECT id, user_id, client_id, expires_at, redirect_uri, scope, used FROM oauth_codes WHERE code=? LIMIT 1");
$stmt->bind_param('s', $code);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows !== 1) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_grant', 'error_description' => $tr['invalid_code'] ?? 'Invalid code']);
    exit;
}
$stmt->bind_result($row_id, $row_user_id, $row_client_id, $row_expires_at, $row_redirect_uri, $row_scope, $row_used);
$stmt->fetch();
$stmt->close();

$row = [
    'id'         => $row_id,
    'user_id'    => $row_user_id,
    'client_id'  => $row_client_id,
    'expires_at' => $row_expires_at,
    'redirect_uri' => $row_redirect_uri,
    'scope'      => $row_scope,
    'used'       => $row_used,
];

// آیا این کد قبلاً استفاده شده؟
if ($row['used']) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_grant', 'error_description' => $tr['code_already_used'] ?? 'Code already used']);
    exit;
}

// چک منقضی شدن کد
if (strtotime($row['expires_at']) < time()) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_grant', 'error_description' => $tr['code_expired'] ?? 'Code expired']);
    exit;
}

// چک client_id منطبق
if ($row['client_id'] != $cid) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_grant', 'error_description' => $tr['client_id_mismatch'] ?? 'client_id mismatch']);
    exit;
}

// چک redirect_uri منطبق یکی از لیست های مجاز (و همان چیزی که در authorize داده شده)
$allowed_uris = array_map('trim', explode(',', $c_redirect_uris));
if (!in_array($redirect_uri, $allowed_uris, true) || $redirect_uri !== $row['redirect_uri']) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_grant', 'error_description' => $tr['redirect_uri_mismatch'] ?? 'redirect_uri mismatch']);
    exit;
}

// 3. صدور access_token و refresh_token
$user_id    = $row['user_id'];
$scope      = $row['scope'] ?? '';
$expires_in = 3600; // یک ساعت

// تولید access_token و refresh_token
$access_token  = bin2hex(random_bytes(32));
$refresh_token = bin2hex(random_bytes(40));

// ذخیره access_token در جدول
$stmt = $mysqli->prepare("INSERT INTO oauth_tokens (access_token, refresh_token, user_id, client_id, scope, expires_at, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
$exp = date('Y-m-d H:i:s', time() + $expires_in);
$stmt->bind_param('ssiiss', $access_token, $refresh_token, $user_id, $cid, $scope, $exp);
$stmt->execute();
$stmt->close();

// کد موقت را غیرفعال کن
$mysqli->query("UPDATE oauth_codes SET used=1 WHERE id=".(int)$row['id']);

// خروجی استاندارد
echo json_encode([
    'access_token'  => $access_token,
    'token_type'    => 'bearer',
    'expires_in'    => $expires_in,
    'refresh_token' => $refresh_token,
    'scope'         => $scope,
]);
exit;