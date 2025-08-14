<?php
session_start();
require_once __DIR__.'/shared/inc/config.php';
require_once __DIR__.'/shared/inc/database-config.php';
require_once __DIR__.'/shared/notify.php';

// زبان مجاز و فعال
$allowed_langs = defined('ALLOWED_LANGS') ? ALLOWED_LANGS : ['en'];
$lang = $_GET['lang'] ?? ($_COOKIE['site_lang'] ?? (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'en'));
if (!in_array($lang, $allowed_langs)) $lang = defined('DEFAULT_LANG') ? DEFAULT_LANG : 'en';
setcookie('site_lang', $lang, [
    'expires' => time()+60*60*24*30,
    'path' => '/',
    'httponly' => false,
    'samesite' => 'Lax'
]);

// ترجمه
define('LANG_DIR', __DIR__.'/shared/assets/languages/');
$tr = [];
$tr_file = LANG_DIR . $lang . '.json';
if (file_exists($tr_file)) $tr = json_decode(file_get_contents($tr_file), true);
if (!$tr) $tr = [];

// نام زبان‌ها برای سوییچر
$lang_names = [];
foreach ($allowed_langs as $lng) {
    $data = @json_decode(@file_get_contents(LANG_DIR . $lng . '.json'), true);
    $lang_names[$lng] = $data['lang_name'] ?? strtoupper($lng);
}

// تم از GET یا کوکی یا پیش‌فرض
$theme = $_GET['theme'] ?? ($_COOKIE['theme'] ?? (defined('SITE_THEME') ? SITE_THEME : 'light'));
if (!in_array($theme, ['light', 'dark'])) $theme = 'light';
setcookie('theme', $theme, [
    'expires' => time()+60*60*24*30,
    'path' => '/',
    'httponly' => false,
    'samesite' => 'Lax'
]);

$is_rtl = ($lang === 'fa' || $lang === 'ar');

// لوگو از ترجمه
$logo_url = '';
if ($theme === 'dark') {
    $logo_url = $tr['logo_dark'] ?? $tr['logo_url'] ?? '/shared/assets/logo-default.svg';
} else {
    $logo_url = $tr['logo_colored'] ?? $tr['logo_url'] ?? '/shared/assets/logo-default.svg';
}

$error = '';
$success = '';
$show_verifymodal = false;

// ثبت فرم و ارسال کد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_submit'])) {
    $name = trim($_POST['name'] ?? '');
    $user = trim($_POST['user'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (!$name || !$user || !$password || !$password2) {
        $error = $tr['register_required'] ?? '';
    } elseif ($password !== $password2) {
        $error = $tr['register_pass_not_match'] ?? '';
    } elseif (strlen($password) < 8) {
        $error = $tr['register_pass_short'] ?? '';
    } else {
        if (filter_var($user, FILTER_VALIDATE_EMAIL)) {
            $email = $user; $phone = null; $type = 'email';
        } elseif (preg_match('/^09\d{9}$/', $user)) {
            $email = null; $phone = $user; $type = 'mobile';
        } else {
            $error = $tr['register_invalid_user'] ?? '';
        }

        if (!$error) {
            $stmt = $mysqli->prepare("SELECT id FROM users WHERE email=? OR phone=? LIMIT 1");
            $stmt->bind_param('ss', $email, $phone);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $error = $tr['register_already_registered'] ?? '';
            } else {
                $code = rand(100000, 999999);
                $_SESSION['register_data'] = [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'password' => $password,
                    'type' => $type,
                    'verify_code' => $code,
                    'code_expire' => time() + (8*60)
                ];
                if ($type === 'email') {
                    $subject = $tr['register_signup'] . ' | XtremeDev';
                    $body = '
<div style="background:#f8fafc;padding:32px 0;">
  <div style="max-width:420px;margin:0 auto;background:#fff;border-radius:12px;box-shadow:0 2px 16px #2499fa22;padding:32px 24px 28px 24px;">
    <div style="text-align:center;padding-bottom:16px;">
      <img src="'.htmlspecialchars($logo_url).'" alt="XtremeDev Logo" style="width:220px;height:56px;margin-bottom:8px;">
      <h2 style="color:#2499fa;font-family:tahoma,arial,sans-serif;font-weight:900;letter-spacing:1.2px;margin:0;font-size:1.5rem;">XtremeDev</h2>
    </div>
    <div style="font-family:tahoma,arial,sans-serif;font-size:1.09rem;color:#222;text-align:center;margin-bottom:20px;">
      <b>'.($lang=='fa'?'کد تایید شما':'Your verification code:').'</b><br>
      <span style="display:inline-block;font-size:2.1rem;font-weight:900;letter-spacing:0.24em;color:#1e81ce;background:#f0f8ff;padding:8px 24px;border-radius:12px;margin:16px 0 8px 0;border:1.5px dashed #2499fa;">
        '.$code.'
      </span>
      <div style="margin:16px 0 0 0;color:#445;">
        '.($lang=='fa'?'این کد را در سایت وارد کنید.':'Enter this code on the XtremeDev website to verify your account.').'<br><br>
        '.($lang=='fa'?'اگر شما درخواست ثبت نام نکرده‌اید، این ایمیل را نادیده بگیرید.':'If you did not request this code, you can safely ignore this email.').'
      </div>
    </div>
    <div style="text-align:center;color:#999;font-size:0.93rem;padding-top:12px;border-top:1px solid #eef2f7;">
      '.($lang=='fa'?'با احترام، تیم XtremeDev':'Sincerely,<br>The XtremeDev Team').'<br>
      <a href="https://xtremedev.co" style="color:#2499fa;text-decoration:none;font-weight:700;">xtremedev.co</a>
    </div>
  </div>
</div>';
                    $send_result = send_email($email, $subject, $body);
                    if ($send_result !== true) $error = ($tr['register_send_fail'] ?? '') . " ($send_result)";
                } else {
                    $msg = ($tr['register_verify_code'] ?? '') . ": $code";
                    $send_result = send_sms($phone, $msg);
                    if (!$send_result) $error = $tr['register_send_fail'] ?? '';
                }
                if (!$error) {
                    $show_verifymodal = true;
                    $success = $tr['register_code_sent'] ?? '';
                }
            }
            $stmt->close();
        }
    }
}

// تایید کد و ثبت نهایی و لاگین اتوماتیک و ریدایرکت
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code_submit'])) {
    $input_code = trim($_POST['verify_code'] ?? '');
    $data = $_SESSION['register_data'] ?? [];
    $real_code = $data['verify_code'] ?? '';
    $expire = $data['code_expire'] ?? 0;
    if (!$input_code || !$real_code || !$expire) {
        $error = $tr['register_invalid_code'] ?? '';
        $show_verifymodal = true;
    } elseif (time() > $expire) {
        $error = $tr['register_invalid_code'] ?? '';
        $show_verifymodal = true;
    } elseif ($input_code != $real_code) {
        $error = $tr['register_invalid_code'] ?? '';
        $show_verifymodal = true;
    } else {
        $name = $data['name'];
        $email = $data['email'];
        $phone = $data['phone'];
        $pass_hash = password_hash($data['password'], PASSWORD_DEFAULT);
        if ($data['type'] === 'email') {
            $stmt2 = $mysqli->prepare("INSERT INTO users (fullname, email, password, is_active, created_at) VALUES (?, ?, ?, 1, NOW())");
            $stmt2->bind_param('sss', $name, $email, $pass_hash);
        } else {
            $stmt2 = $mysqli->prepare("INSERT INTO users (fullname, phone, password, is_active, created_at) VALUES (?, ?, ?, 1, NOW())");
            $stmt2->bind_param('sss', $name, $phone, $pass_hash);
        }
        if ($stmt2->execute()) {
            $new_user_id = $stmt2->insert_id;
            $_SESSION['user_id'] = $new_user_id;
            unset($_SESSION['register_data']);

            $redirect_url = 'index.php';
            if (isset($_GET['redirect']) && $_GET['redirect']) {
                $redirect_url = $_GET['redirect'];
                $params = [];
                foreach (['client_id', 'redirect_uri', 'response_type', 'scope', 'state'] as $p) {
                    if (isset($_GET[$p])) $params[$p] = $_GET[$p];
                }
                if (count($params)) {
                    $redirect_url .= (strpos($redirect_url, '?') === false ? '?' : '&') . http_build_query($params);
                }
            }
            $success = $tr['register_success'] ?? '';
            echo '<!DOCTYPE html><html lang="'.$lang.'" dir="'.($is_rtl?'rtl':'ltr').'"><head>
            <meta charset="UTF-8"><title>'.$tr['register_success'].'</title>
            <meta http-equiv="refresh" content="1;url='.htmlspecialchars($redirect_url).'">
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <link href="shared/assets/fonts/Vazirmatn.css" rel="stylesheet">
            <style>body{background:#f4f7fa;display:flex;align-items:center;justify-content:center;height:100vh;font-family:\'Vazirmatn\',tahoma,arial,sans-serif;}
            .box{background:#fff;max-width:400px;margin:2rem auto;padding:2.2rem 1.5rem 1.7rem 1.5rem;border-radius:14px;box-shadow:0 4px 24px #2499fa22;text-align:center;}
            .box .success{color:#086c23;background:#e6f9ee;border-radius:7px;padding:0.7rem;margin-bottom:1rem;}
            </style></head><body><div class="box"><div class="success">'.$success.'</div></div></body></html>';
            exit;
        } else {
            $error = "DB Error!";
            $show_verifymodal = true;
        }
        $stmt2->close();
    }
}
?>
<!DOCTYPE html>
<html lang="<?=$lang?>" dir="<?=$is_rtl?'rtl':'ltr'?>">
<head>
    <meta charset="UTF-8">
    <title><?=htmlspecialchars($tr['register_title'] ?? '')?> | XtremeDev</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="shared/assets/fonts/Vazirmatn.css" rel="stylesheet">
    <?php if(file_exists(__DIR__.'/shared/inc/head-assets.php')) include __DIR__.'/shared/inc/head-assets.php'; ?>
    <style>
        :root {
            --surface: #f4f7fa;
            --surface-alt: #fff;
            --text: #222;
        }
        body {
            font-family: 'Vazirmatn', Tahoma, Arial, sans-serif;
            background: var(--surface, #f4f7fa);
            color: var(--text, #222);
            min-height: 100vh;
            margin: 0;
            transition: background 0.4s, color 0.4s;
            display: flex;
            flex-direction: column;
            height: 100vh;
            direction: <?=$is_rtl ? 'rtl' : 'ltr'?>;
        }
        .login-main {
            flex: 1 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 64px);
            padding-top: 1rem;
            padding-bottom: 1rem;
        }
        .login-box {
            width: 100%;
            max-width: 410px;
            background: var(--surface-alt, #fff);
            border-radius: 16px;
            box-shadow: 0 4px 24px #2499fa22;
            padding: 2.4rem 2rem 2rem 2rem;
            margin: 2rem auto;
        }
        .login-logo { display:block;margin:0 auto 1.5rem auto;max-width:170px;max-height:80px;width:auto;height:auto;}
        .login-box h3 { color: #2499fa; font-weight: 900; letter-spacing: 0.7px;}
        .form-label { color: #2499fa; font-weight: 700;}
        .form-control {
            border-radius: 9px;
            font-size: 1rem;
            border: 1.5px solid #dbe6f7;
            margin-bottom: 1rem;
            background: var(--surface, #f4f7fa);
            color: var(--text, #222);
            min-height: 44px;
            line-height: 1.6;
        }
        .form-control:focus {
            border-color: #38a8ff;
            background: var(--surface, #f4f7fa);
            color: var(--text, #222);
        }
        .btn-primary { background: #2499fa; border:0; font-weight:800; color: #fff; padding: 0.7rem 2.5rem; border-radius: 9px;}
        .btn-primary:hover { background: #38a8ff; }
        .login-link {
            display: block;
            margin: 1rem auto 0 auto;
            text-align: center;
            color: #2499fa;
            font-weight: 700;
            text-decoration: none;
        }
        .login-link:hover { color: #145d99; text-decoration: underline; }
        .alert-danger { color: #b80000; background: #fbeaea; border-radius: 7px; padding: 0.7rem; margin-bottom:1rem; text-align:center;}
        .alert-success { color: #086c23; background: #e6f9ee; border-radius: 7px; padding: 0.7rem; margin-bottom:1rem; text-align:center;}
        .password-group { position: relative; margin-bottom: 1rem;}
        .password-group .form-control { padding-right: 2.8rem !important; margin-bottom: 0;}
        .toggle-password {
            position: absolute;
            top: 0;
            bottom: 0;
            right: 0.75rem;
            margin: auto;
            background: none;
            border: none;
            padding: 0;
            outline: none;
            cursor: pointer;
            color: #888;
            z-index: 3;
            height: 2rem;
            width: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }
        .toggle-password svg { display:block; width:22px; height:22px; }
        .toggle-password:hover, .toggle-password:focus { color: #2499fa; }
        @media (max-width:600px) { .login-box {padding: 1.2rem 0.7rem;} }
        /* --- Lang Switcher --- */
        .lang-switcher {
            margin-bottom: 18px;
            text-align: center !important;
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
        /* Modal */
        .modal-bg {
            background: rgba(44,56,90,0.16); backdrop-filter: blur(1.5px);
            position: fixed; top:0; left:0; right:0; bottom:0;
            display: none; align-items: center; justify-content: center;
            z-index: 2222;
        }
        .modal-bg.active { display: flex; }
        .modal-card {
            background: #fff;
            border-radius: 16px;
            padding: 2.1rem 2rem 1.6rem 2rem;
            box-shadow: 0 8px 32px #2499fa33;
            min-width: 320px;
            max-width: 95vw;
            text-align: center;
            position: relative;
        }
        .modal-card h4 { color: #2499fa; margin-bottom: 1.3rem; }
        .modal-close {
            position: absolute; top: 0.8rem; right: 1.2rem;
            background: none; border: none; font-size: 1.4rem;
            color: #888; cursor: pointer; transition: color 0.2s;
        }
        .modal-close:hover { color: #2499fa; }
        .modal-card input {
            text-align: center; letter-spacing: 6px; font-weight: bold;
        }
        .modal-card .btn-primary { width: 100%; }
        /* === DARK === */
        body.dark-theme { --surface: #0e1016 !important; --surface-alt: #181f2a !important; --text: #e6e9f2 !important; background: #0e1016 !important; color: #e6e9f2 !important; }
        body.dark-theme .login-box, body.dark-theme .modal-card { background: #181f2a !important; color: #e6e9f2 !important; box-shadow: 0 4px 24px #0d111c88; }
        body.dark-theme .form-control { background: #111a27 !important; color: #fff !important; border-color: #384c6e !important; caret-color: #fff !important; }
        body.dark-theme .form-control:focus { border-color: #38a8ff !important; background: #161e2e !important; color: #fff !important; }
        body.dark-theme .form-label { color: #38a8ff !important; }
        body.dark-theme .toggle-password { color: #bfcbe5; }
        body.dark-theme .toggle-password:hover, body.dark-theme .toggle-password:focus { color: #38a8ff; }
        body.dark-theme .login-link { color: #38a8ff !important;}
        body.dark-theme .login-link:hover { color: #2499fa !important;}
        body.dark-theme .modal-close { color: #bfcbe5;}
        body.dark-theme .modal-close:hover { color: #38a8ff;}
        body.dark-theme .lang-dd { background: #1b2536; color: #38a8ff; }
        body.dark-theme .lang-dd:focus, body.dark-theme .lang-dd:hover { background: #232b3a; border-color: #38a8ff;}
        body.dark-theme .lang-switcher svg { fill: #38a8ff; }
    </style>
</head>
<body>
<div class="login-main">
    <div class="login-box shadow">
        <div style="text-align:center;">
            <img src="<?=htmlspecialchars($logo_url)?>" alt="logo" class="login-logo" style="height:56px;max-width:220px;margin-bottom:8px;">
        </div>

        <div class="lang-switcher">
            <form method="get" id="langForm" style="display:inline;">
                <span class="lang-dd-wrap">
                    <select class="lang-dd" name="lang" onchange="document.getElementById('langForm').submit();">
                        <?php foreach($allowed_langs as $lng): ?>
                            <option value="<?=$lng?>"<?=$lng==$lang?' selected':''?>><?=htmlspecialchars($lang_names[$lng])?></option>
                        <?php endforeach; ?>
                    </select>
                    <svg viewBox="0 0 20 20"><path d="M5.8 8.3a1 1 0 0 1 1.4 0L10 11.1l2.8-2.8a1 1 0 1 1 1.4 1.4l-3.5 3.5a1 1 0 0 1-1.4 0l-3.5-3.5a1 1 0 1 1 1.4-1.4z"/></svg>
                </span>
                <?php
                foreach ($_GET as $k=>$v)
                    if($k!='lang') echo '<input type="hidden" name="'.htmlspecialchars($k).'" value="'.htmlspecialchars($v).'">';
                if (!isset($_GET['theme']) && $theme) {
                    echo '<input type="hidden" name="theme" value="'.htmlspecialchars($theme).'">';
                }
                ?>
            </form>
        </div>

        <h3 class="mb-4 text-center"><?=htmlspecialchars($tr['register_signup'] ?? '')?></h3>
        <?php if($error && !$show_verifymodal): ?>
            <div class="alert alert-danger text-center"><?=htmlspecialchars($error)?></div>
        <?php endif; ?>
        <?php if($success && !$show_verifymodal): ?>
            <div class="alert alert-success text-center"><?=htmlspecialchars($success)?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <div class="mb-3">
                <label for="name" class="form-label"><?=htmlspecialchars($tr['register_full_name'] ?? '')?></label>
                <input type="text" class="form-control" id="name" name="name" required maxlength="80"
                       placeholder="<?=htmlspecialchars($tr['register_enter_full_name'] ?? '')?>" value="<?=htmlspecialchars($_POST['name'] ?? '')?>">
            </div>
            <div class="mb-3">
                <label for="user" class="form-label"><?=htmlspecialchars($tr['register_email_or_mobile'] ?? '')?></label>
                <input type="text" class="form-control" id="user" name="user" required
                       placeholder="<?=htmlspecialchars($tr['register_enter_email_mobile'] ?? '')?>" value="<?=htmlspecialchars($_POST['user'] ?? '')?>">
            </div>
            <div class="password-group">
                <label for="password" class="form-label"><?=htmlspecialchars($tr['register_password'] ?? '')?></label>
                <input type="password" class="form-control" id="password" name="password" required
                       placeholder="<?=htmlspecialchars($tr['register_enter_password'] ?? '')?>" autocomplete="new-password">
                <button type="button" class="toggle-password" tabindex="-1" aria-label="Show/hide password" data-toggle="password" data-target="password">
                    <svg id="eye-open-1" xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 22 22">
                        <path stroke="currentColor" stroke-width="1.5" d="M2.68 11c2.06-4.09 6.41-6.27 10.24-5.31 2.06.5 4.06 1.84 5.51 3.92a2.01 2.01 0 0 1 0 2.78c-1.45 2.08-3.45 3.42-5.51 3.92-3.83.96-8.18-1.22-10.24-5.31Z"/>
                        <circle cx="11" cy="11" r="3" stroke="currentColor" stroke-width="1.5"/>
                    </svg>
                    <svg id="eye-closed-1" style="display:none;" xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 22 22">
                        <path stroke="currentColor" stroke-width="1.5" d="M2.68 11c2.06-4.09 6.41-6.27 10.24-5.31 2.06.5 4.06 1.84 5.51 3.92a2.01 2.01 0 0 1 0 2.78c-1.45 2.08-3.45 3.42-5.51 3.92-3.83.96-8.18-1.22-10.24-5.31Z"/>
                        <circle cx="11" cy="11" r="3" stroke="currentColor" stroke-width="1.5"/>
                        <line x1="5" y1="17" x2="17" y2="5" stroke="currentColor" stroke-width="1.5"/>
                    </svg>
                </button>
            </div>
            <div class="password-group">
                <label for="password2" class="form-label"><?=htmlspecialchars($tr['register_confirm_password'] ?? '')?></label>
                <input type="password" class="form-control" id="password2" name="password2" required
                       placeholder="<?=htmlspecialchars($tr['register_reenter_password'] ?? '')?>" autocomplete="new-password">
                <button type="button" class="toggle-password" tabindex="-1" aria-label="Show/hide password" data-toggle="password" data-target="password2">
                    <svg id="eye-open-2" xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 22 22">
                        <path stroke="currentColor" stroke-width="1.5" d="M2.68 11c2.06-4.09 6.41-6.27 10.24-5.31 2.06.5 4.06 1.84 5.51 3.92a2.01 2.01 0 0 1 0 2.78c-1.45 2.08-3.45 3.42-5.51 3.92-3.83.96-8.18-1.22-10.24-5.31Z"/>
                        <circle cx="11" cy="11" r="3" stroke="currentColor" stroke-width="1.5"/>
                    </svg>
                    <svg id="eye-closed-2" style="display:none;" xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 22 22">
                        <path stroke="currentColor" stroke-width="1.5" d="M2.68 11c2.06-4.09 6.41-6.27 10.24-5.31 2.06.5 4.06 1.84 5.51 3.92a2.01 2.01 0 0 1 0 2.78c-1.45 2.08-3.45 3.42-5.51 3.92-3.83.96-8.18-1.22-10.24-5.31Z"/>
                        <circle cx="11" cy="11" r="3" stroke="currentColor" stroke-width="1.5"/>
                        <line x1="5" y1="17" x2="17" y2="5" stroke="currentColor" stroke-width="1.5"/>
                    </svg>
                </button>
            </div>
            <button type="submit" name="register_submit" class="btn btn-primary w-100 mt-3"><?=htmlspecialchars($tr['register_signup_btn'] ?? '')?></button>
        </form>
        <a href="login.php" class="login-link"><?=htmlspecialchars($tr['register_already_account'] ?? '')?></a>
    </div>
</div>

<!-- Modal for verify code -->
<div class="modal-bg" id="verifymodal" <?=($show_verifymodal ? 'style="display:flex;"' : '')?>>
    <div class="modal-card">
        <button class="modal-close" onclick="closeModal()" type="button" aria-label="Close">&times;</button>
        <h4><?=htmlspecialchars($tr['register_verify_code'] ?? '')?></h4>
        <?php if($error && $show_verifymodal): ?>
            <div class="alert alert-danger"><?=htmlspecialchars($error)?></div>
        <?php endif; ?>
        <?php if($success && $show_verifymodal): ?>
            <div class="alert alert-success"><?=htmlspecialchars($success)?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off" style="margin-top:16px;">
            <input type="text" class="form-control" name="verify_code" pattern="\d{6}" required maxlength="6"
                   placeholder="<?=htmlspecialchars($tr['register_code_placeholder'] ?? '')?>">
            <button type="submit" name="verify_code_submit" class="btn btn-primary mt-3"><?=htmlspecialchars($tr['register_submit_code'] ?? '')?></button>
        </form>
    </div>
</div>

<script>
    // Theme دارک/لایت (از کلاس body)
    (() => {
        let theme = '<?=$theme?>';
        if(theme === 'dark') document.body.classList.add('dark-theme');
    })();
    // Password toggle
    document.querySelectorAll('.toggle-password').forEach(function(btn){
        btn.addEventListener('click', function() {
            var target = btn.getAttribute('data-target');
            var pwd = document.getElementById(target);
            var eyeOpen = btn.querySelector('svg[id^=eye-open]');
            var eyeClosed = btn.querySelector('svg[id^=eye-closed]');
            if (pwd.type === "password") {
                pwd.type = "text";
                eyeOpen.style.display = "none";
                eyeClosed.style.display = "";
            } else {
                pwd.type = "password";
                eyeOpen.style.display = "";
                eyeClosed.style.display = "none";
            }
        });
    });
    // Show modal if needed
    <?php if($show_verifymodal): ?>
    document.getElementById('verifymodal').classList.add('active');
    <?php endif; ?>
    function closeModal() {
        document.getElementById('verifymodal').classList.remove('active');
    }
</script>
<?php if(file_exists(__DIR__.'/shared/inc/foot-assets.php')) include __DIR__.'/shared/inc/foot-assets.php'; ?>
</body>
</html>