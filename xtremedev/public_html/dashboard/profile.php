<?php
session_start();
require_once __DIR__.'/../shared/inc/config.php';
require_once __DIR__.'/../shared/notify.php';

$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$is_rtl = ($lang === 'fa');
$darkThemeActive = (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark');

// ترجمه از فایل
$translations = [];
$lang_file = __DIR__ . '/../shared/assets/languages/' . $lang . '.json';
if (file_exists($lang_file)) {
    $json = file_get_contents($lang_file);
    $translations = json_decode($json, true);
}

// ------ SSO ------
if (!isset($_SESSION['user_profile'])) {
    $client_id = 'xtremedev-web';
    $redirect_uri = 'https://xtremedev.co/oauth-callback.php';
    $state = bin2hex(random_bytes(8));

    // theme را هم از کوکی بگیر و اگر نبود پیش‌فرض light
    $theme = isset($_COOKIE['theme']) && in_array($_COOKIE['theme'], ['light','dark']) ? $_COOKIE['theme'] : 'light';

    $login_url = "https://auth.xtremedev.co/authorize.php?client_id=$client_id"
        . "&redirect_uri=" . urlencode($redirect_uri)
        . "&response_type=code"
        . "&scope=basic"
        . "&state=$state"
        . "&lang=" . urlencode($lang)
        . "&theme=" . urlencode($theme); // تم را هم اضافه کردیم

    header("Location: $login_url");
    exit;
}

$user_profile = $_SESSION['user_profile'];
$uid = intval($user_profile['id']);

// ===== تابع رفرش توکن =====
function refresh_access_token() {
    $refresh_token = $_SESSION['refresh_token'] ?? null;
    $client_id     = 'xtremedev-web';
    $client_secret = 'isLD0opYPX1iSU6sLecFBKR22TBlqIsRXNKg8PnaARRVfkaeQD27aqbYsX2R05AnjafbPukMfX4V9YLvhkstmlUI6d2l6WxlWQi8';
    if (!$refresh_token) return false;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://auth.xtremedev.co/token.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    $params = [
        'grant_type'    => 'refresh_token',
        'refresh_token' => $refresh_token,
        'client_id'     => $client_id,
        'client_secret' => $client_secret,
    ];
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    $response = curl_exec($ch);
    curl_close($ch);

    $resp = json_decode($response, true);
    if (!empty($resp['access_token']) && !empty($resp['refresh_token'])) {
        $_SESSION['access_token']  = $resp['access_token'];
        $_SESSION['refresh_token'] = $resp['refresh_token'];
        return true;
    }
    return false;
}

function generate_otp_code() {
    return rand(100000, 999999);
}

$profile_msg = '';
$pass_msg = '';
$otp_error = '';
$otp_stage = '';
$open_change_pass_modal = false;

/* =================== OTP مرحله اول: درخواست ارسال کد ====================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'changepass_request_otp') {
    $otp_code = generate_otp_code();
    $_SESSION['change_pass_otp_code'] = $otp_code;
    $_SESSION['change_pass_otp_time'] = time();
    $_SESSION['change_pass_otp_verified'] = false;
    $user_email = $user_profile['email'] ?? '';
    $user_phone = $user_profile['phone'] ?? '';
    $send_ok = false;
    if (!empty($user_email)) {
        $logo_url = 'https://xtremedev.co/resources/logo-blue.png';
        $body = '
        <div style="background:#f8fafc;padding:32px 0;">
          <div style="max-width:420px;margin:0 auto;background:#fff;border-radius:12px;box-shadow:0 2px 16px #2499fa22;padding:32px 24px 28px 24px;">
            <div style="text-align:center;padding-bottom:16px;">
              <img src="'.htmlspecialchars($logo_url).'" alt="XtremeDev Logo" style="width:220px;height:56px;margin-bottom:8px;">
              <h2 style="color:#2499fa;font-family:tahoma,arial,sans-serif;font-weight:900;letter-spacing:1.2px;margin:0;font-size:1.5rem;">XtremeDev</h2>
            </div>
            <div style="font-family:tahoma,arial,sans-serif;font-size:1.09rem;color:#222;text-align:center;margin-bottom:20px;">
              <b>'.($translations['otp_code_title'] ?? ($lang=='fa'?'کد تایید شما':'Your verification code:')).'</b><br>
              <span style="display:inline-block;font-size:2.1rem;font-weight:900;letter-spacing:0.24em;color:#1e81ce;background:#f0f8ff;padding:8px 24px;border-radius:12px;margin:16px 0 8px 0;border:1.5px dashed #2499fa;">
                '.$otp_code.'
              </span>
              <div style="margin:16px 0 0 0;color:#445;">
                '.($translations['otp_code_instruction'] ?? ($lang=='fa'?'این کد را در سایت وارد کنید.':'Enter this code on the XtremeDev website to verify your account.')).'<br><br>
                '.($translations['otp_code_notice'] ?? ($lang=='fa'?'اگر شما درخواست تغییر رمز عبور نکرده‌اید، این ایمیل را نادیده بگیرید.':'If you did not request this code, you can safely ignore this email.')).'
              </div>
            </div>
            <div style="text-align:center;color:#999;font-size:0.93rem;padding-top:12px;border-top:1px solid #eef2f7;">
              '.($translations['otp_code_footer'] ?? ($lang=='fa'?'با احترام، تیم XtremeDev':'Sincerely,<br>The XtremeDev Team')).'<br>
              <a href="https://xtremedev.co" style="color:#2499fa;text-decoration:none;font-weight:700;">xtremedev.co</a>
            </div>
          </div>
        </div>';
        $subject = $translations['otp_code_subject'] ?? ($lang=='fa' ? 'کد تایید تغییر رمز عبور - XtremeDev' : 'Password Change Verification Code - XtremeDev');
        $send_ok = send_email($user_email, $subject, $body);
    }
    if (!$send_ok && !empty($user_phone)) {
        $msg = ($translations['otp_code_sms'] ?? ($lang=='fa' ? 'کد تایید تغییر رمز عبور شما: ' : 'Your password change verification code: ')) . $otp_code;
        $send_ok = send_sms($user_phone, $msg);
    }
    if ($send_ok === true) {
        $otp_stage = 'sent';
        $otp_error = '<div class="alert alert-success">'.($translations['otp_sent'] ?? '').'</div>';
        $open_change_pass_modal = true;
    } else {
        $otp_stage = '';
        $otp_error = '<div class="alert alert-danger">'.($translations['otp_error_send'] ?? '').($send_ok ? " ($send_ok)" : '').'</div>';
        $open_change_pass_modal = true;
    }
}

/* =================== OTP مرحله دوم: تایید کد ====================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'changepass_verify_otp') {
    $user_otp = trim($_POST['otp_code'] ?? '');
    $real_otp = $_SESSION['change_pass_otp_code'] ?? '';
    $otp_time = $_SESSION['change_pass_otp_time'] ?? 0;
    if (!$user_otp) {
        $otp_stage = 'sent';
        $otp_error = '<div class="alert alert-danger">'.($translations['otp_empty'] ?? '').'</div>';
        $open_change_pass_modal = true;
    } elseif (!$real_otp || !$otp_time || (time() - $otp_time) > 300) {
        $otp_stage = '';
        $otp_error = '<div class="alert alert-danger">'.($translations['otp_expired'] ?? '').'</div>';
        unset($_SESSION['change_pass_otp_code'], $_SESSION['change_pass_otp_time'], $_SESSION['change_pass_otp_verified']);
        $open_change_pass_modal = true;
    } elseif ($user_otp != $real_otp) {
        $otp_stage = 'sent';
        $otp_error = '<div class="alert alert-danger">'.($translations['otp_wrong'] ?? '').'</div>';
        $open_change_pass_modal = true;
    } else {
        $_SESSION['change_pass_otp_verified'] = true;
        $otp_stage = 'verified';
        $open_change_pass_modal = true;
    }
}

/* =================== مرحله سوم: تغییر رمز عبور ====================== */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action'])
    && $_POST['action'] === 'changepass'
) {
    if (!isset($_SESSION['change_pass_otp_verified']) || !$_SESSION['change_pass_otp_verified']) {
        $otp_stage = '';
        $otp_error = '<div class="alert alert-danger">'.($translations['otp_empty'] ?? '').'</div>';
        $open_change_pass_modal = true;
    } else {
        $open_change_pass_modal = true;
        $old_pass = trim($_POST['old_pass']);
        $new_pass = trim($_POST['new_pass']);
        $repeat_pass = trim($_POST['repeat_pass']);

        if (!$old_pass || !$new_pass || !$repeat_pass) {
            $pass_msg = '<div class="alert alert-danger">'.($translations['must_fill'] ?? '').'</div>';
            $otp_stage = 'verified';
        } elseif ($new_pass !== $repeat_pass) {
            $pass_msg = '<div class="alert alert-danger">'.($translations['pass_mismatch'] ?? '').'</div>';
            $otp_stage = 'verified';
        } else {
            $try_count = 0;
            do {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://auth.xtremedev.co/change-password.php');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);

                $post_fields = [
                    'old_password' => $old_pass,
                    'new_password' => $new_pass,
                ];
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
                $headers = ['Accept: application/json'];
                if (!empty($_SESSION['access_token']))
                    $headers[] = 'Authorization: Bearer ' . $_SESSION['access_token'];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $response = curl_exec($ch);
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $resp = json_decode($response, true);

                if ($httpcode == 401 && isset($resp['message']) && $resp['message'] == 'Token expired' && $try_count == 0) {
                    if (refresh_access_token()) {
                        $try_count++;
                        continue;
                    }
                }
                break;
            } while (true);

            if ($httpcode == 200 && isset($resp['success']) && $resp['success']) {
                $profile_msg = '<div class="alert alert-success">'.($translations['pass_success'] ?? '').'</div>';
                $open_change_pass_modal = false;
                $pass_msg = '';
                unset($_SESSION['change_pass_otp_code'], $_SESSION['change_pass_otp_time'], $_SESSION['change_pass_otp_verified']);
                $otp_stage = '';
            } else {
                $msg = isset($resp['message']) ? $resp['message'] : ($translations['pass_fail'] ?? '');
                $pass_msg = '<div class="alert alert-danger">'.$msg.'</div>';
                $otp_stage = 'verified';
            }
        }
    }
}

/* =================== فرم ویرایش پروفایل ====================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'profile') {
    $new_name = trim($_POST['name']);
    $new_email = trim($_POST['email']);
    $new_phone = trim($_POST['phone']);
    $photo_url = $user_profile['photo'] ?? '';

    // آپلود عکس (لوکال)
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $size = $_FILES['photo']['size'];
        if ($size > 1048576) {
            $profile_msg = '<div class="alert alert-danger">'.($translations['file_too_large'] ?? '').'</div>';
        } else {
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed)) {
                $fname = 'uploads/avatar_' . $uid . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $fname)) {
                    $photo_url = $fname;
                }
            }
        }
    }

    if (!$new_name || !$new_email) {
        $profile_msg = '<div class="alert alert-danger">'.($translations['must_fill'] ?? '').'</div>';
    } elseif (empty($profile_msg)) {
        $try_count = 0;
        do {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://auth.xtremedev.co/update-profile.php');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);

            $post_fields = [
                'name' => $new_name,
                'email' => $new_email,
                'phone' => $new_phone,
            ];
            if ($photo_url) $post_fields['photo'] = $photo_url;

            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
            $headers = ['Accept: application/json'];
            if (!empty($_SESSION['access_token']))
                $headers[] = 'Authorization: Bearer ' . $_SESSION['access_token'];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $response = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $resp = json_decode($response, true);

            if ($httpcode == 401 && isset($resp['message']) && $resp['message'] == 'Token expired' && $try_count == 0) {
                if (refresh_access_token()) {
                    $try_count++;
                    continue;
                }
            }
            break;
        } while (true);

        if ($httpcode == 200 && isset($resp['success']) && $resp['success']) {
            $profile_msg = '<div class="alert alert-success">'.($translations['edit_profile'] ?? '').' '.($translations['save_changes'] ?? '').'</div>';
            $_SESSION['user_profile']['name'] = $new_name;
            $_SESSION['user_profile']['email'] = $new_email;
            $_SESSION['user_profile']['phone'] = $new_phone;
            if ($photo_url)
                $_SESSION['user_profile']['photo'] = $photo_url;
            $user_profile = $_SESSION['user_profile'];
        } else {
            $msg = isset($resp['message']) ? $resp['message'] : ($translations['must_fill'] ?? '');
            $profile_msg = '<div class="alert alert-danger">'.$msg.'</div>';
        }
    }
}

$name = $user_profile['name'] ?? '';
$email = $user_profile['email'] ?? '';
$phone = $user_profile['phone'] ?? '';
$photo = $user_profile['photo'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?=$lang?>" dir="<?=$is_rtl?'rtl':'ltr'?>">
<head>
    <meta charset="UTF-8">
    <title><?= $translations['profile_title'] ?? '' ?> | XtremeDev</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php include __DIR__.'/../shared/inc/head-assets.php'; ?>
    <?php include __DIR__.'/includes/dashboard-styles.php'; ?>
    <!-- استایل‌ها همانند قبل... -->
    <style>
        :root {
            --primary: #2499fa;
            --surface: #f4f7fa;
            --surface-alt: #fff;
            --text: #222;
            --shadow-card: #2499fa14;
            --border: #2499fa18;
            --modal-bg: #fff;
            --modal-header: #f8fafc;
        }
        body {
            min-height: 100vh;
            background: var(--surface) !important;
            color: var(--text) !important;
            font-family: Vazirmatn, Tahoma, Arial, sans-serif;
            transition: background 0.3s, color 0.3s;
        }
        body.dark-theme {
            --surface: #181f2a;
            --surface-alt: #222b38;
            --text: #e6e9f2;
            --modal-bg: #222b38;
            --modal-header: #1a253b;
        }
        .profile-card {
            background: var(--surface-alt);
            border-radius: 18px;
            box-shadow: 0 2px 24px var(--shadow-card);
            border: 1.5px solid var(--border);
            max-width: 440px;
            margin: 48px auto 36px auto;
            padding: 2.2rem 1.5rem 1.5rem 1.5rem;
            color: var(--text);
        }
        .profile-title {
            font-weight: 900;
            font-size: 1.35rem;
            color: var(--primary);
            text-align: center;
            margin-bottom: 1.2rem;
            letter-spacing: .3px;
        }
        .profile-avatar {
            width: 90px; height: 90px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 1rem auto;
            display: block;
            border: 3px solid #e6e9f2;
            background: #fff;
        }
        .form-label { color: var(--primary); font-weight: 700;}
        .form-control, .form-select {
            border-radius: 10px;
            font-size: 1rem;
            border: 1.5px solid #dbe6f7;
            margin-bottom: 1rem;
            background: var(--surface);
            color: var(--text);
            transition: border 0.2s, background 0.2s, color 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #38a8ff;
            background: var(--surface-alt);
            color: var(--text);
            box-shadow: 0 0 3px #38a8ff22;
        }
        .profile-section {
            margin-bottom: 2.1rem;
        }
        .profile-section:last-child { margin-bottom: 0;}
        .profile-btn {
            background: var(--primary);
            color: #fff;
            font-weight: 800;
            border-radius: 12px;
            font-size: 1.11rem;
            padding: 0.5rem 2rem;
            border: 0;
            margin: 0 auto 0 auto;
            display: block;
            transition: background 0.2s;
        }
        .profile-btn:hover, .profile-btn:focus {
            background: #38a8ff;
            color: #fff;
        }
        .change-pass-btn {
            width: 100%;
            margin-top: 16px;
            font-size: 1.06rem;
            border-radius: 10px;
            font-weight: 700;
            padding: 0.6rem 1.6rem;
            background: #e4e7ef;
            color: var(--primary);
            border: none;
            transition: background 0.18s, color 0.18s;
        }
        .change-pass-btn:hover, .change-pass-btn:focus {
            background: var(--primary);
            color: #fff;
        }
        body.dark-theme .change-pass-btn {
            background: #1a253b !important;
            color: #38a8ff !important;
        }
        body.dark-theme .change-pass-btn:hover, body.dark-theme .change-pass-btn:focus {
            background: #2499fa !important;
            color: #fff !important;
        }
        .modal-custom {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0; top: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.22);
            align-items: center; justify-content: center;
        }
        .modal-custom.show { display: flex; }
        .modal-dialog-custom {
            background: var(--modal-bg);
            color: var(--text);
            border-radius: 18px;
            max-width: 410px;
            width: 100%;
            box-shadow: 0 8px 48px #111a2e44;
        }
        .modal-header-custom {
            background: var(--modal-header);
            border-radius: 18px 18px 0 0;
            padding: 1.1rem 1.5rem 1.1rem 1.5rem;
            border-bottom: 1px solid #e4e7ef;
        }
        .modal-title-custom { font-weight: 900; color: var(--primary);}
        .modal-body-custom { padding: 1.1rem 1.5rem 1.1rem 1.5rem;}
        .btn-close-custom {
            background: none;
            border: none;
            font-size: 1.4rem;
            color: #aaa;
            position: absolute;
            top: 18px; right: 18px;
            cursor: pointer;
        }
        @media (max-width:600px) {
            .profile-card { padding: 1.2rem 0.5rem 1.1rem 0.5rem; }
            .profile-title { font-size: 1.1rem;}
            .modal-dialog-custom { max-width: 97vw; padding: 0;}
            .modal-body-custom, .modal-header-custom { padding: 1rem 1rem; }
        }
    </style>
</head>
<body<?= $darkThemeActive ? ' class="dark-theme"' : '' ?>>
<?php include 'includes/dashboard-navbar.php'; ?>

<div class="main-content">
    <div class="profile-card shadow">
        <div class="profile-title"><?= $translations['profile_title'] ?? '' ?></div>
        <?php if($profile_msg) echo $profile_msg; ?>
        <form method="POST" enctype="multipart/form-data" class="profile-section" autocomplete="off">
            <input type="hidden" name="action" value="profile">
            <img src="<?=htmlspecialchars($photo ?: 'resources/default-avatar.png')?>" alt="avatar" class="profile-avatar mb-2">
            <label for="photo" class="form-label"><?= $translations['photo'] ?? '' ?></label>
            <input type="file" class="form-control" name="photo" id="photo" accept="image/*">
            <label for="name" class="form-label"><?= $translations['name'] ?? '' ?></label>
            <input type="text" class="form-control" name="name" id="name" required value="<?=htmlspecialchars($name)?>">
            <label for="email" class="form-label"><?= $translations['email'] ?? '' ?></label>
            <input type="email" class="form-control" name="email" id="email" required value="<?=htmlspecialchars($email)?>">
            <label for="phone" class="form-label"><?= $translations['phone'] ?? '' ?></label>
            <input type="text" class="form-control" name="phone" id="phone" value="<?=htmlspecialchars($phone)?>">
            <button type="submit" class="profile-btn mt-2"><?= $translations['save_changes'] ?? '' ?></button>
        </form>
        <button class="change-pass-btn" id="openChangePassModal"><?= $translations['change_pass'] ?? '' ?></button>
    </div>
</div>

<!-- Modal تغییر رمز عبور با OTP -->
<div class="modal-custom<?=($open_change_pass_modal?' show':'')?>" id="changePassModal" tabindex="-1" aria-labelledby="changePassModalLabel" aria-hidden="true">
    <div class="modal-dialog-custom" style="position:relative;">
        <?php if ($otp_stage == ''): ?>
        <form method="POST" id="otpRequestForm" autocomplete="off">
            <input type="hidden" name="action" value="changepass_request_otp">
            <div class="modal-header-custom">
                <h5 class="modal-title-custom"><?= $translations['pass_verify_title'] ?? '' ?></h5>
                <button type="button" class="btn-close-custom" onclick="closeModal()" aria-label="×">&times;</button>
            </div>
            <div class="modal-body-custom">
                <?= $otp_error ?>
                <p><?= $translations['otp_popup_intro'] ?? '' ?></p>
                <button type="submit" class="profile-btn" style="width:100%;margin:0;"><?= $translations['otp_send_btn'] ?? '' ?></button>
            </div>
        </form>
        <?php elseif ($otp_stage == 'sent'): ?>
        <form method="POST" id="otpVerifyForm" autocomplete="off">
            <input type="hidden" name="action" value="changepass_verify_otp">
            <div class="modal-header-custom">
                <h5 class="modal-title-custom"><?= $translations['otp_popup_title'] ?? '' ?></h5>
                <button type="button" class="btn-close-custom" onclick="closeModal()" aria-label="×">&times;</button>
            </div>
            <div class="modal-body-custom">
                <?= $otp_error ?>
                <p><?= $translations['otp_enter_code'] ?? '' ?></p>
                <input type="text" class="form-control" name="otp_code" maxlength="6" required autocomplete="one-time-code">
                <button type="submit" class="profile-btn" style="width:100%;margin:0;"><?= $translations['otp_verify_btn'] ?? '' ?></button>
            </div>
        </form>
        <?php elseif ($otp_stage == 'verified'): ?>
        <form method="POST" id="changePassForm" autocomplete="off">
            <input type="hidden" name="action" value="changepass">
            <div class="modal-header-custom">
                <h5 class="modal-title-custom"><?= $translations['pass_verify_title'] ?? '' ?></h5>
                <button type="button" class="btn-close-custom" onclick="closeModal()" aria-label="×">&times;</button>
            </div>
            <div class="modal-body-custom">
                <?php if($pass_msg) echo $pass_msg; ?>
                <div class="mb-3">
                    <label class="form-label" for="old_pass"><?= $translations['old_pass'] ?? '' ?></label>
                    <input type="password" class="form-control" name="old_pass" id="old_pass" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="new_pass"><?= $translations['new_pass'] ?? '' ?></label>
                    <input type="password" class="form-control" name="new_pass" id="new_pass" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="repeat_pass"><?= $translations['repeat_pass'] ?? '' ?></label>
                    <input type="password" class="form-control" name="repeat_pass" id="repeat_pass" required>
                </div>
                <button type="submit" class="profile-btn" style="width:100%;margin:0;"><?= $translations['change_now'] ?? '' ?></button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/dashboard-footer.php'; ?>
<?php include __DIR__.'/../shared/inc/foot-assets.php'; ?>
<?php if(file_exists('includes/theme-script.php')) include 'includes/theme-script.php'; ?>
<script>
    function closeModal() {
        document.getElementById('changePassModal').classList.remove('show');
    }
    document.getElementById('openChangePassModal').onclick = function() {
        document.getElementById('changePassModal').classList.add('show');
    };
    <?php if($open_change_pass_modal): ?>
    document.addEventListener("DOMContentLoaded", function() {
        document.getElementById('changePassModal').classList.add('show');
    });
    <?php endif; ?>
</script>
</body>
</html>