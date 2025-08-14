<?php
error_reporting(0);
ini_set('display_errors', 0);

ini_set('session.gc_maxlifetime', 60*60*24*7);
session_set_cookie_params(['lifetime' => 60*60*24*7, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__.'/shared/inc/database-config.php';
require_once __DIR__.'/shared/inc/config.php';

define('LANG_DIR', __DIR__.'/shared/assets/languages/');

// --- لیست زبان‌های مجاز از کانفیگ ---
$allowed_langs = defined('ALLOWED_LANGS') ? ALLOWED_LANGS : ['en'];

// --- زبان فعلی (ورودی، کوکی یا دیفالت) فقط از لیست مجاز ---
$lang = $_GET['lang'] ?? ($_COOKIE['site_lang'] ?? (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'en'));
if (!in_array($lang, $allowed_langs)) $lang = defined('DEFAULT_LANG') ? DEFAULT_LANG : 'en';

// --- ست‌کردن کوکی زبان ---
setcookie('site_lang', $lang, [
    'expires' => time()+60*60*24*30,
    'path' => '/',
    'httponly' => false,
    'samesite' => 'Lax'
]);

// --- بارگذاری ترجمه‌ها فقط برای زبان فعال از فایل ---
$tr = [];
$tr_file = LANG_DIR . $lang . '.json';
if (file_exists($tr_file)) {
    $tr = json_decode(file_get_contents($tr_file), true);
}
if (!$tr) $tr = [];

$is_rtl = ($lang === 'fa' || $lang === 'ar');

// --- ساخت لیست نام زبان‌ها (از کلید "lang_name" هر فایل ترجمه یا خود کد) ---
$lang_names = [];
foreach ($allowed_langs as $lng) {
    $data = @json_decode(@file_get_contents(LANG_DIR . $lng . '.json'), true);
    $lang_names[$lng] = $data['lang_name'] ?? strtoupper($lng);
}

// --- تم (theme) از GET یا کوکی یا پیش‌فرض ---
$theme = $_GET['theme'] ?? ($_COOKIE['theme'] ?? 'light');
if (!in_array($theme, ['light','dark'])) $theme = 'light';
// --- ست‌کردن کوکی تم ---
setcookie('theme', $theme, [
    'expires' => time()+60*60*24*30,
    'path' => '/',
    'httponly' => false,
    'samesite' => 'Lax'
]);

// --- برای کلاس بدنه ---
$body_class = ($theme === 'dark') ? 'dark-theme' : '';

// --- پارامترهای لازم برای لینک‌ها (theme و lang و پارامترهای SSO) ---
$link_params = [
    'lang' => $lang,
    'theme' => $theme,
];
foreach (['redirect','client_id','redirect_uri','response_type','scope','state'] as $p)
    if (isset($_GET[$p])) $link_params[$p] = $_GET[$p];

$signup_url = 'register.php' . (count($link_params) ? ('?' . http_build_query($link_params)) : '');
$otp_url    = 'login-otp.php' . (count($link_params) ? ('?' . http_build_query($link_params)) : '');
$back_url   = 'index.php' . (count($link_params) ? ('?' . http_build_query($link_params)) : '');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['user'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$user || !$password) {
        $error = $tr['required'] ?? 'Required fields!';
    } else {
        $stmt = $mysqli->prepare("SELECT id, password, email, phone FROM users WHERE (email=? OR phone=?) AND is_active=1 LIMIT 1");
        $stmt->bind_param('ss', $user, $user);
        $stmt->execute(); $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($uid, $hashed_pass, $email, $phone);
            $stmt->fetch();
            if (password_verify($password, $hashed_pass)) {
                $_SESSION['user_id'] = $uid;
                $mysqli->query("UPDATE users SET last_login=NOW() WHERE id=".$uid);
                if (isset($_GET['redirect']) && $_GET['redirect']) {
                    $redirect_url = $_GET['redirect'];
                    $params = [];
                    foreach (['client_id','redirect_uri','response_type','scope','state','lang','theme'] as $p)
                        if (isset($_GET[$p])) $params[$p] = $_GET[$p];
                    if (count($params)) {
                        $redirect_url .= (strpos($redirect_url, '?') === false ? '?' : '&') . http_build_query($params);
                    }
                    header("Location: $redirect_url"); exit;
                } else {
                    header("Location: index.php?lang=$lang&theme=$theme"); exit;
                }
            }
        }
        $error = $tr['incorrect'] ?? 'Login information is incorrect.';
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="<?=$lang?>" dir="<?=$is_rtl?'rtl':'ltr'?>">
<head>
    <meta charset="UTF-8">
    <title><?=htmlspecialchars($tr['signin']??'Sign in')?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php include __DIR__.'/../shared/inc/head-assets.php'; ?>
    <link href="../shared/assets/fonts/Vazirmatn.css" rel="stylesheet">
    <style>
        body { font-family: 'Vazirmatn', Tahoma, Arial, sans-serif; background: #f4f7fa;color:#222;min-height:100vh;display:flex;align-items:center;justify-content:center;margin:0;direction:<?= $is_rtl ? 'rtl' : 'ltr' ?>;}
        .login-box { max-width:410px; background:#fff;border-radius:16px;box-shadow:0 4px 24px #2499fa22;padding:2.4rem 2rem 2rem 2rem;margin:2rem auto;width:100%;}
        .login-logo { display:block;margin:0 auto 1.5rem auto;max-width:170px;max-height:80px;width:auto;height:auto;}
        .login-box h3 { color: #2499fa; font-weight: 900; letter-spacing: 0.7px;}
        .form-label { color: #2499fa; font-weight: 700;}
        .form-control { border-radius:9px;font-size:1rem;border:1.5px solid #dbe6f7;margin-bottom:1rem;background:#f4f7fa;color:#222;min-height:44px;line-height:1.6;}
        .form-control:focus { border-color:#38a8ff; }
        .btn-primary {background:#2499fa;border:0;font-weight:800;}
        .btn-primary:hover {background:#38a8ff;}
        .alert-danger {font-size:.98rem;}
        /* ---------- Lang Dropdown ========== */
        .lang-switcher {
            margin-bottom:18px;
            text-align:center !important;
            position: relative;
            z-index: 20;
        }
        .lang-dd-wrap {
            display: inline-block;
            position: relative;
            min-width: 110px;
        }
        .lang-dd {
            width: 100%;
            padding: 7px 36px 7px 16px;
            font-size: 1.06rem;
            border-radius: 10px;
            border: 1.5px solid #dbe6f7;
            background: #f5fafd;
            color: #2499fa;
            font-weight: 800;
            box-shadow: 0 2px 12px #2499fa08;
            transition: border 0.18s, box-shadow 0.18s, background 0.18s;
            appearance: none;
            outline: none;
            cursor: pointer;
            margin: 0;
        }
        .lang-dd:focus { border-color: #38a8ff; background: #eaf6ff; }
        .lang-dd:hover { border-color: #38a8ff; background: #eaf6ff; }
        .lang-dd-wrap svg {
            position: absolute;
            <?= $is_rtl ? "left:12px;" : "right:12px;" ?>
            top: 50%;
            transform: translateY(-50%)<?= $is_rtl ? " rotate(180deg)" : "" ?>;
            pointer-events: none;
            width: 18px; height: 18px;
            fill: #38a8ff;
        }
        @media (max-width:600px) {
            .login-box {padding:1.2rem 0.7rem;}
            .lang-dd { font-size:0.97rem;}
        }
        /* تم دارک */
        body.dark-theme { background:#191f29 !important; color:#eaf0fa !important; }
        body.dark-theme .login-box { background:#232b3a !important; color:#eaf0fa !important;}
        body.dark-theme .form-control { background:#202b35 !important; color:#eaf0fa !important; border-color:#384c6e;}
        body.dark-theme .form-control:focus { border-color:#38a8ff !important; background:#232b3a !important;}
        body.dark-theme .form-label { color:#38a8ff !important;}
        body.dark-theme .btn-primary { background:#145d99 !important;}
        body.dark-theme .btn-primary:hover { background:#38a8ff !important;}
        body.dark-theme .lang-dd { background:#1b2536; color:#38a8ff;}
        body.dark-theme .lang-dd:focus,
        body.dark-theme .lang-dd:hover { background:#232b3a; border-color:#38a8ff;}
        body.dark-theme .lang-switcher svg { fill:#38a8ff; }
    </style>
</head>
<body class="<?=$body_class?>">
<div class="login-box shadow">
    <?php
    // لوگو را از فایل ترجمه بگیر
    $logo_url = '';
    if ($theme === 'dark') {
        $logo_url = $tr['logo_dark'] ?? $tr['logo_url'] ?? '/shared/assets/logo-default.svg';
    } else {
        $logo_url = $tr['logo_colored'] ?? $tr['logo_url'] ?? '/shared/assets/logo-default.svg';
    }
    ?>
    <img src="<?=htmlspecialchars($logo_url)?>" class="login-logo" alt="logo">

    <div class="lang-switcher">
        <form method="get" id="langForm" style="display:inline;">
            <span class="lang-dd-wrap">
                <select class="lang-dd" name="lang" onchange="document.getElementById('langForm').submit();">
                    <?php foreach($allowed_langs as $lng): ?>
                        <option value="<?=$lng?>"<?=$lng==$lang?' selected':''?>><?=htmlspecialchars($lang_names[$lng])?></option>
                    <?php endforeach; ?>
                </select>
                <!-- فلش SVG -->
                <svg viewBox="0 0 20 20"><path d="M5.8 8.3a1 1 0 0 1 1.4 0L10 11.1l2.8-2.8a1 1 0 1 1 1.4 1.4l-3.5 3.5a1 1 0 0 1-1.4 0l-3.5-3.5a1 1 0 1 1 1.4-1.4z"/></svg>
            </span>
            <?php
            // پارامترهای دیگر GET را حفظ کن (theme و ...)
            foreach ($_GET as $k=>$v)
                if($k!='lang') echo '<input type="hidden" name="'.htmlspecialchars($k).'" value="'.htmlspecialchars($v).'">';
            // اگر theme فعلی در GET نبود، theme رو هم ست کن
            if (!isset($_GET['theme']) && $theme) {
                echo '<input type="hidden" name="theme" value="'.htmlspecialchars($theme).'">';
            }
            ?>
        </form>
    </div>
    <h3 class="mb-4 text-center"><?=htmlspecialchars($tr['signin']??'Sign in')?></h3>
    <?php if($error): ?>
        <div class="alert alert-danger text-center"><?=htmlspecialchars($error)?></div>
    <?php endif; ?>
    <form method="post" autocomplete="off">
        <div class="mb-3">
            <label for="user" class="form-label"><?=htmlspecialchars($tr['user']??'Username')?></label>
            <input type="text" class="form-control" id="user" name="user" required placeholder="<?=htmlspecialchars($tr['user_ph']??'')?>">
        </div>
        <div class="mb-3">
            <label for="password" class="form-label"><?=htmlspecialchars($tr['password']??'Password')?></label>
            <input type="password" class="form-control" id="password" name="password" required placeholder="<?=htmlspecialchars($tr['password_ph']??'')?>">
        </div>
        <a href="forgot.php?lang=<?=htmlspecialchars($lang)?>&theme=<?=htmlspecialchars($theme)?>" style="float:<?=$is_rtl?'left':'right'?>;font-size:.99rem;color:#2499fa;"><?=htmlspecialchars($tr['forgot']??'Forgot password?')?></a>
        <div style="clear:both"></div>
        <button type="submit" class="btn btn-primary w-100 mt-1"><?=htmlspecialchars($tr['submit']??'Sign in')?></button>
    </form>
    <div class="extra-links mt-3" style="display:flex;justify-content:space-between;gap:10px;">
        <a href="<?=htmlspecialchars($signup_url)?>"><?=htmlspecialchars($tr['signup']??'Sign up')?></a>
        <a href="<?=htmlspecialchars($otp_url)?>"><?=htmlspecialchars($tr['otp']??'Sign in with OTP')?></a>
    </div>
    <a href="<?=htmlspecialchars($back_url)?>" style="margin:2.2rem auto 0 auto;display:block;min-width:120px;font-size:1.07rem;font-weight:700;border-radius:10px;background:#e4e7ef;color:#2499fa;text-align:center;padding:0.7rem 1.5rem;"><?= $is_rtl ? '&#8592; ' : '' ?><?=htmlspecialchars($tr['back']??'Back')?><?= $is_rtl ? '' : ' &#8592;' ?></a>
</div>
<?php include __DIR__.'/../shared/inc/foot-assets.php'; ?>
</body>
</html>