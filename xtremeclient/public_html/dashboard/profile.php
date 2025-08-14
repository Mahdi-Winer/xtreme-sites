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

$profile_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'profile') {
    $new_name = trim($_POST['name']);
    $new_email = isset($_POST['email']) ? trim($_POST['email']) : ($user_profile['email'] ?? '');
    $new_phone = isset($_POST['phone']) ? trim($_POST['phone']) : ($user_profile['phone'] ?? '');
    $photo_url = $user_profile['photo'] ?? '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $size = $_FILES['photo']['size'];
        if ($size > 1048576) {
            $profile_msg = '<div class="alert alert-danger">'.t('file_too_large').'</div>';
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
    if (!$new_name) {
        $profile_msg = '<div class="alert alert-danger">'.t('must_fill').'</div>';
    } elseif (empty($profile_msg)) {
        $_SESSION['user_profile']['name'] = $new_name;
        $_SESSION['user_profile']['email'] = $new_email;
        $_SESSION['user_profile']['phone'] = $new_phone;
        if ($photo_url)
            $_SESSION['user_profile']['photo'] = $photo_url;
        $user_profile = $_SESSION['user_profile'];
        $profile_msg = '<div class="alert alert-success">'.t('profile_saved').'</div>';
    }
}
$name = $user_profile['name'] ?? '';
$email = $user_profile['email'] ?? '';
$phone = $user_profile['phone'] ?? '';
$photo = $user_profile['photo'] ?? '';

$email_set = !empty($email);
$phone_set = !empty($phone);

$email_editable = !$email_set;
$phone_editable = !$phone_set;
?>
<!DOCTYPE html>
<html lang="<?=$lang?>" dir="<?=$is_rtl?'rtl':'ltr'?>">
<head>
    <meta charset="UTF-8">
    <title><?=t('profile_title')?> | XtremeDev</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php include __DIR__.'/../shared/inc/head-assets.php'; ?>
    <?php include __DIR__.'/includes/dashboard-styles.php'; ?>
    <style>
        :root {
            --primary: #2499fa;
            --surface: #f4f7fa;
            --surface-alt: #fff;
            --text: #222;
            --shadow-card: #2499fa14;
            --border: #2499fa18;
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
        /* رفع مشکل نمایش input غیرفعال در تم دارک */
        body.dark-theme input[disabled],
        body.dark-theme input:disabled,
        body.dark-theme input[readonly],
        body.dark-theme input:read-only {
            background: #232c3a !important;
            color: #7e8ca5 !important;
            border-color: #2d3a4b !important;
            opacity: 1 !important;
        }
        input[readonly], input[disabled] {
            opacity: .7;
        }

        /* استایل سفارشی input فایل */
        .file-upload-group {
            display: flex;
            align-items: stretch;
            margin-bottom: 1.2rem;
        }
        .file-upload-group input[type="file"] {
            display: none;
        }
        .file-upload-btn {
            background: var(--primary);
            color: #fff;
            font-weight: 700;
            border: none;
            border-radius: 10px 0 0 10px;
            padding: 8px 24px;
            cursor: pointer;
            font-size: 1rem;
            transition: background .18s;
            outline: none;
        }
        .file-upload-btn:hover, .file-upload-btn:focus {
            background: #38a8ff;
        }
        .file-upload-label {
            display: flex;
            align-items: center;
            background: var(--surface-alt);
            border: 1.5px solid #dbe6f7;
            border-radius: 0 10px 10px 0;
            padding: 0 14px;
            min-width: 0;
            font-size: 0.99rem;
            color: #666;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            flex: 1 1 auto;
            height: 44px;
        }
        body.dark-theme .file-upload-btn {
            background: #1a253b;
            color: #b5dafc;
        }
        body.dark-theme .file-upload-btn:hover, body.dark-theme .file-upload-btn:focus {
            background: #2499fa;
            color: #fff;
        }
        body.dark-theme .file-upload-label {
            background: #232c3a;
            border-color: #2d3a4b;
            color: #b4b8c2;
        }

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
        @media (max-width:600px) {
            .profile-card { padding: 1.2rem 0.5rem 1.1rem 0.5rem; }
            .profile-title { font-size: 1.1rem;}
            .change-pass-btn { width: 96%; min-width: 0; }
            .file-upload-btn { font-size: 0.98rem; padding: 7px 12px;}
            .file-upload-label { font-size: 0.95rem; padding: 0 7px;}
        }
    </style>
</head>
<body>
<?php include 'includes/dashboard-navbar.php'; ?>

<div class="main-content">
    <div class="profile-card shadow">
        <div class="profile-title"><?=t('profile_title')?></div>
        <?php if($profile_msg) echo $profile_msg; ?>
        <form method="POST" enctype="multipart/form-data" class="profile-section" autocomplete="off">
            <input type="hidden" name="action" value="profile">
            <img src="<?=htmlspecialchars($photo ?: 'resources/default-avatar.png')?>" alt="avatar" class="profile-avatar mb-2">

            <label for="photo" class="form-label"><?=t('photo')?></label>
            <div class="file-upload-group">
                <button type="button" class="file-upload-btn" onclick="document.getElementById('photo').click();"><?=t('choose_file')?></button>
                <span class="file-upload-label" id="file-label"><?=t('no_file')?></span>
                <input type="file" name="photo" id="photo" accept="image/*" onchange="updateFileName()">
            </div>

            <label for="name" class="form-label"><?=t('name')?></label>
            <input type="text" class="form-control" name="name" id="name" required value="<?=htmlspecialchars($name)?>">

            <label for="email" class="form-label"><?=t('email')?></label>
            <input
                type="email"
                class="form-control"
                name="email"
                id="email"
                value="<?=htmlspecialchars($email)?>"
                <?= $email_editable ? '' : 'readonly disabled' ?>
                <?= !$email_editable ? 'tabindex="-1"' : '' ?>
                <?= $email_editable ? 'required' : '' ?>
            >

            <label for="phone" class="form-label"><?=t('phone')?></label>
            <input
                type="text"
                class="form-control"
                name="phone"
                id="phone"
                value="<?=htmlspecialchars($phone)?>"
                <?= $phone_editable ? '' : 'readonly disabled' ?>
                <?= !$phone_editable ? 'tabindex="-1"' : '' ?>
            >

            <button type="submit" class="profile-btn mt-2"><?=t('save_changes')?></button>
        </form>
        <a href="password-change.php" class="change-pass-btn"><?=t('change_pass')?></a>
    </div>
</div>

<?php include 'includes/dashboard-footer.php'; ?>
<?php include __DIR__.'/../shared/inc/foot-assets.php'; ?>
<?php if(file_exists('includes/theme-script.php')) include 'includes/theme-script.php'; ?>
<script>
function updateFileName() {
    var fileInput = document.getElementById('photo');
    var label = document.getElementById('file-label');
    if(fileInput.files.length > 0) {
        label.textContent = fileInput.files[0].name;
    } else {
        label.textContent = <?=json_encode(t('no_file'))?>;
    }
}
</script>
</body>
</html>