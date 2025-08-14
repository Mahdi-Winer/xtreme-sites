<?php
session_start();
require_once __DIR__ . '/shared/inc/config.php'; // اینجا ALLOWED_LANGS و DEFAULT_LANG
require_once __DIR__ . '/shared/lang-theme.php';
require_once __DIR__ . '/shared/inc/database-config.php';

// --- زبان فعلی (ورودی یا کوکی یا دیفالت) ---
$lang = $_GET['lang'] ?? ($_COOKIE['site_lang'] ?? (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'en'));
if (!in_array($lang, ALLOWED_LANGS)) $lang = DEFAULT_LANG;

// --- تم فقط از GET (اگر نبود یا نامعتبر بود، 'light')
$theme = isset($_GET['theme']) && in_array($_GET['theme'], ['light','dark']) ? $_GET['theme'] : 'light';

// --- ست‌کردن کوکی زبان (اگر لازم داری)
setcookie('site_lang', $lang, [
    'expires' => time()+60*60*24*30,
    'path' => '/',
    'httponly' => false,
    'samesite' => 'Lax'
]);

// --- بارگذاری ترجمه‌ها (برای پیام خطا)
$tr = [];
$lang_file = __DIR__ . '/shared/assets/languages/' . $lang . '.json';
if (file_exists($lang_file)) $tr = json_decode(file_get_contents($lang_file), true);

// --- دریافت پارامترها
$client_id     = $_GET['client_id']     ?? '';
$redirect_uri  = $_GET['redirect_uri']  ?? '';
$response_type = $_GET['response_type'] ?? '';
$scope         = $_GET['scope']         ?? '';
$state         = $_GET['state']         ?? '';

// --- اگر لاگین نیست ریدایرکت به login.php با همه پارامترها و lang و theme
if (!isset($_SESSION['user_id'])) {
    $q = [
        'client_id' => $client_id,
        'redirect_uri' => $redirect_uri,
        'response_type' => $response_type,
        'scope' => $scope,
        'state' => $state,
        'lang' => $lang,
        'theme' => $theme // اضافه شد
    ];
    $q_str = http_build_query($q);
    header("Location: login.php?redirect=authorize.php&$q_str");
    exit;
}

// --- بررسی client_id معتبر
$stmt = $mysqli->prepare("SELECT id, name, redirect_uris FROM clients WHERE client_id=? LIMIT 1");
$stmt->bind_param('s', $client_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows !== 1) {
    echo "<h2 style='color:#e13a3a'>" . htmlspecialchars($tr['invalid_client_id'] ?? 'Invalid client_id.') . "</h2>";
    exit;
}
$stmt->bind_result($cid, $cname, $c_redirect_uris);
$stmt->fetch();
$stmt->close();

// --- بررسی دقیق redirect_uri
if (!$redirect_uri || !$c_redirect_uris) {
    echo "<h2 style='color:#e13a3a'>" . htmlspecialchars($tr['invalid_redirect_uri'] ?? 'Invalid redirect_uri.') . "</h2>";
    exit;
}
$allowed_uris = explode(',', $c_redirect_uris);
$allowed_uris = array_map('trim', $allowed_uris);
if (!in_array($redirect_uri, $allowed_uris, true)) {
    echo "<h2 style='color:#e13a3a'>" . htmlspecialchars($tr['redirect_uri_not_allowed'] ?? 'redirect_uri not allowed.') . "</h2>";
    exit;
}

// --- بررسی response_type
if ($response_type !== 'code') {
    echo "<h2 style='color:#e13a3a'>" . htmlspecialchars($tr['unsupported_response_type'] ?? 'Unsupported response_type.') . "</h2>";
    exit;
}

// --- صدور کد و ریدایرکت
$code = bin2hex(random_bytes(32));
$user_id = $_SESSION['user_id'];
$expires = date('Y-m-d H:i:s', time() + 300); // 5 دقیقه

$stmt2 = $mysqli->prepare("INSERT INTO oauth_codes (code, user_id, client_id, expires_at, redirect_uri, scope, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
$stmt2->bind_param('siisss', $code, $user_id, $cid, $expires, $redirect_uri, $scope);
$stmt2->execute();
$stmt2->close();

// --- ریدایرکت به redirect_uri با code و state و lang و theme
$redirect = $redirect_uri . "?code=" . urlencode($code);
if ($state) $redirect .= "&state=" . urlencode($state);
if ($lang)  $redirect .= "&lang=" . urlencode($lang);
if ($theme) $redirect .= "&theme=" . urlencode($theme);
header("Location: $redirect");
exit;
?>