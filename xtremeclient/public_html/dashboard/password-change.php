<?php
session_start();
require_once __DIR__.'/../shared/inc/config.php';
require_once __DIR__.'/../shared/notify.php';

// --- ترجمه ---
$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : 'fa';
$is_rtl = ($lang === 'fa');
$translations = [];
$lang_file = __DIR__ . '/../shared/assets/languages/' . $lang . '.json';
if (file_exists($lang_file)) {
    $json = file_get_contents($lang_file);
    $translations = json_decode($json, true) ?: [];
}
function t($k) {
    global $translations;
    return isset($translations[$k]) ? $translations[$k] : $k;
}

// --- SSO ورود ---
if (!isset($_SESSION['user_profile'])) {
    $client_id = 'xtremedev-web';
    $redirect_uri = 'https://xtremedev.co/oauth-callback.php';
    $state = bin2hex(random_bytes(8));
    $login_url = "https://auth.xtremedev.co/authorize.php?client_id=$client_id&redirect_uri=" . urlencode($redirect_uri) . "&response_type=code&scope=basic&state=$state";
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

$pass_msg = '';
$otp_error = '';
$otp_stage = '';

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
              <b>'.($lang=='fa'?t('otp_code'):'Your verification code:').'</b><br>
              <span style="display:inline-block;font-size:2.1rem;font-weight:900;letter-spacing:0.24em;color:#1e81ce;background:#f0f8ff;padding:8px 24px;border-radius:12px;margin:16px 0 8px 0;border:1.5px dashed #2499fa;">
                '.$otp_code.'
              </span>
              <div style="margin:16px 0 0 0;color:#445;">'.t('otp_email_text').'</div>
            </div>
            <div style="text-align:center;color:#999;font-size:0.93rem;padding-top:12px;border-top:1px solid #eef2f7;">
              '.t('team_xtremedev').'<br>
              <a href="https://xtremedev.co" style="color:#2499fa;text-decoration:none;font-weight:700;">xtremedev.co</a>
            </div>
          </div>
        </div>';
        $subject = t('otp_email_subject');
        $send_ok = send_email($user_email, $subject, $body);
    }
    if (!$send_ok && !empty($user_phone)) {
        $msg = t('otp_sms_text') . $otp_code;
        $send_ok = send_sms($user_phone, $msg);
    }
    if ($send_ok === true) {
        $otp_stage = 'sent';
        $otp_error = '<div class="alert alert-success">'.t('otp_sent').'</div>';
    } else {
        $otp_stage = '';
        $otp_error = '<div class="alert alert-danger">'.t('otp_error_send').($send_ok ? " ($send_ok)" : '').'</div>';
    }
}

/* =================== OTP مرحله دوم: تایید کد ====================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'changepass_verify_otp') {
    $user_otp = trim($_POST['otp_code'] ?? '');
    $real_otp = $_SESSION['change_pass_otp_code'] ?? '';
    $otp_time = $_SESSION['change_pass_otp_time'] ?? 0;
    if (!$user_otp) {
        $otp_stage = 'sent';
        $otp_error = '<div class="alert alert-danger">'.t('otp_empty').'</div>';
    } elseif (!$real_otp || !$otp_time || (time() - $otp_time) > 300) {
        $otp_stage = '';
        $otp_error = '<div class="alert alert-danger">'.t('otp_expired').'</div>';
        unset($_SESSION['change_pass_otp_code'], $_SESSION['change_pass_otp_time'], $_SESSION['change_pass_otp_verified']);
    } elseif ($user_otp != $real_otp) {
        $otp_stage = 'sent';
        $otp_error = '<div class="alert alert-danger">'.t('otp_wrong').'</div>';
    } else {
        $_SESSION['change_pass_otp_verified'] = true;
        $otp_stage = 'verified';
    }
}

/* =================== مرحله سوم: تغییر رمز عبور ====================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'changepass') {
    if (!isset($_SESSION['change_pass_otp_verified']) || !$_SESSION['change_pass_otp_verified']) {
        $otp_stage = '';
        $otp_error = '<div class="alert alert-danger">'.t('otp_empty').'</div>';
    } else {
        $old_pass = trim($_POST['old_pass']);
        $new_pass = trim($_POST['new_pass']);
        $repeat_pass = trim($_POST['repeat_pass']);
        if (!$old_pass || !$new_pass || !$repeat_pass) {
            $pass_msg = '<div class="alert alert-danger">'.t('must_fill').'</div>';
            $otp_stage = 'verified';
        } elseif ($new_pass !== $repeat_pass) {
            $pass_msg = '<div class="alert alert-danger">'.t('pass_mismatch').'</div>';
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
                $pass_msg = '<div class="alert alert-success">'.t('pass_success').'</div>';
                unset($_SESSION['change_pass_otp_code'], $_SESSION['change_pass_otp_time'], $_SESSION['change_pass_otp_verified']);
                $otp_stage = '';
            } else {
                $msg = isset($resp['message']) ? $resp['message'] : t('pass_fail');
                $pass_msg = '<div class="alert alert-danger">'.$msg.'</div>';
                $otp_stage = 'verified';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?=$lang?>" dir="<?=$is_rtl?'rtl':'ltr'?>">
<head>
    <meta charset="UTF-8">
    <title><?=t('pass_verify_title')?> | XtremeDev</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php include __DIR__.'/../shared/inc/head-assets.php'; ?>
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
        .auth-card {
            background: var(--surface-alt);
            border-radius: 22px;
            box-shadow: 0 2px 32px var(--shadow-card);
            border: 1.5px solid var(--border);
            max-width: 410px;
            margin: 56px auto 38px auto;
            padding: 0 0 28px 0;
            color: var(--text);
        }
        .auth-header {
            background: var(--modal-header);
            border-radius: 22px 22px 0 0;
            padding: 1.3rem 2rem 1.25rem 2rem;
            border-bottom: 1.5px solid #e4e7ef;
            text-align: center;
        }
        .auth-title {
            font-weight: 900;
            color: var(--primary);
            font-size: 1.28rem;
            margin-bottom: 0;
            letter-spacing: .3px;
        }
        .auth-body {
            padding: 1.4rem 2rem 0.2rem 2rem;
        }
        .form-label { color: var(--primary); font-weight: 700;}
        .form-control {
            border-radius: 10px;
            font-size: 1rem;
            border: 1.5px solid #dbe6f7;
            margin-bottom: 1rem;
            background: var(--surface);
            color: var(--text);
            transition: border 0.2s, background 0.2s, color 0.2s;
        }
        .form-control:focus {
            border-color: #38a8ff;
            background: var(--surface-alt);
            color: var(--text);
            box-shadow: 0 0 3px #38a8ff22;
        }
        .profile-btn {
            background: var(--primary);
            color: #fff;
            font-weight: 800;
            border-radius: 12px;
            font-size: 1.11rem;
            padding: 0.5rem 2rem;
            border: 0;
            margin: 16px auto 0 auto;
            display: block;
            width: 100%;
            transition: background 0.2s;
        }
        .profile-btn:hover, .profile-btn:focus {
            background: #38a8ff;
            color: #fff;
        }
        .back-btn {
            margin: 32px auto 0 auto !important;
            display: block !important;
            width: 70%;
            max-width: 250px;
            min-width: 160px;
            font-size: 1.08rem;
            border-radius: 10px;
            font-weight: 700;
            padding: 0.6rem 1.6rem;
            background: #e4e7ef;
            color: var(--primary);
            border: none;
            text-align: center;
            box-shadow: 0 2px 8px #2499fa11;
            transition: background 0.18s, color 0.18s;
        }
        .back-btn:hover, .back-btn:focus {
            background: var(--primary);
            color: #fff;
        }
        body.dark-theme .back-btn {
            background: #1a253b !important;
            color: #38a8ff !important;
        }
        body.dark-theme .back-btn:hover, body.dark-theme .back-btn:focus {
            background: #2499fa !important;
            color: #fff !important;
        }
        .alert {
            margin-bottom: 18px;
            border-radius: 10px;
            padding: 10px 18px;
            font-size: .98rem;
        }
        @media (max-width:600px) {
            .auth-card { padding: 0 0 22px 0; max-width: 99vw;}
            .auth-header, .auth-body { padding: 1.1rem 1rem 0.1rem 1rem; }
            .auth-title { font-size: 1.1rem;}
            .back-btn { width: 96%; min-width: 0; }
        }
    </style>
</head>
<body>
<?php include 'includes/dashboard-navbar.php'; ?>

<div class="main-content">
    <div class="auth-card shadow">
        <div class="auth-header">
            <div class="auth-title"><?=t('pass_verify_title')?></div>
        </div>
        <div class="auth-body">
        <?php
        if ($otp_stage == ''): ?>
            <form method="POST" id="otpRequestForm" autocomplete="off">
                <input type="hidden" name="action" value="changepass_request_otp">
                <?=$otp_error?>
                <p style="margin-bottom:26px"><?=t('otp_popup_intro')?></p>
                <button type="submit" class="profile-btn"><?=t('otp_send_btn')?></button>
            </form>
        <?php elseif ($otp_stage == 'sent'): ?>
            <form method="POST" id="otpVerifyForm" autocomplete="off">
                <input type="hidden" name="action" value="changepass_verify_otp">
                <?=$otp_error?>
                <p style="margin-bottom:16px"><?=t('otp_enter_code')?></p>
                <input type="text" class="form-control" name="otp_code" maxlength="6" required autocomplete="one-time-code">
                <button type="submit" class="profile-btn"><?=t('otp_verify_btn')?></button>
            </form>
        <?php elseif ($otp_stage == 'verified'): ?>
            <form method="POST" id="changePassForm" autocomplete="off">
                <input type="hidden" name="action" value="changepass">
                <?php if($pass_msg) echo $pass_msg; ?>
                <div class="mb-3">
                    <label class="form-label" for="old_pass"><?=t('old_pass')?></label>
                    <input type="password" class="form-control" name="old_pass" id="old_pass" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="new_pass"><?=t('new_pass')?></label>
                    <input type="password" class="form-control" name="new_pass" id="new_pass" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="repeat_pass"><?=t('repeat_pass')?></label>
                    <input type="password" class="form-control" name="repeat_pass" id="repeat_pass" required>
                </div>
                <button type="submit" class="profile-btn"><?=t('change_now')?></button>
            </form>
        <?php endif; ?>
        </div>
        <a href="profile.php" class="back-btn"><?=t('profile_title')?></a>
    </div>
</div>

<?php include 'includes/dashboard-footer.php'; ?>
<?php include __DIR__.'/../shared/inc/foot-assets.php'; ?>
<?php if(file_exists('includes/theme-script.php')) include 'includes/theme-script.php'; ?>
</body>
</html>