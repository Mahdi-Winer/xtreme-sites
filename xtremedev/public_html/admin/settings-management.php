<?php
session_start();
require_once __DIR__ . '/../shared/inc/database-config.php';
require_once __DIR__ . '/../shared/inc/config.php';

// زبان جاری
$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'en');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : 'en';
$is_rtl = ($lang === 'fa');

// ترجمه‌ها
$tr = [
    'en' => [
        'title' => 'Site Settings',
        'site_title_en' => 'Site Title (EN)',
        'site_title_fa' => 'Site Title (FA)',
        'hero_image' => 'Hero Image URL',
        'logo_url' => 'Logo URL',
        'site_intro_en' => 'Site Intro (EN)',
        'site_intro_fa' => 'Site Intro (FA)',
        'joinus_title_en' => 'Join Us Title (EN)',
        'joinus_title_fa' => 'Join Us Title (FA)',
        'joinus_desc_en' => 'Join Us Description (EN)',
        'joinus_desc_fa' => 'Join Us Description (FA)',
        'joinus_rules_en' => 'Join Us Rules (EN)',
        'joinus_rules_fa' => 'Join Us Rules (FA)',
        'joinus_benefits_en' => 'Join Us Benefits (EN)',
        'joinus_benefits_fa' => 'Join Us Benefits (FA)',
        'save' => 'Save Settings',
        'success' => 'Settings updated successfully.',
        'error' => 'Database error: ',
    ],
    'fa' => [
        'title' => 'تنظیمات سایت',
        'site_title_en' => 'عنوان سایت (انگلیسی)',
        'site_title_fa' => 'عنوان سایت (فارسی)',
        'hero_image' => 'آدرس تصویر اصلی',
        'logo_url' => 'آدرس لوگو',
        'site_intro_en' => 'مقدمه سایت (انگلیسی)',
        'site_intro_fa' => 'مقدمه سایت (فارسی)',
        'joinus_title_en' => 'عنوان همکاری (انگلیسی)',
        'joinus_title_fa' => 'عنوان همکاری (فارسی)',
        'joinus_desc_en' => 'توضیحات همکاری (انگلیسی)',
        'joinus_desc_fa' => 'توضیحات همکاری (فارسی)',
        'joinus_rules_en' => 'قوانین همکاری (انگلیسی)',
        'joinus_rules_fa' => 'قوانین همکاری (فارسی)',
        'joinus_benefits_en' => 'مزایای همکاری (انگلیسی)',
        'joinus_benefits_fa' => 'مزایای همکاری (فارسی)',
        'save' => 'ذخیره تنظیمات',
        'success' => 'تنظیمات با موفقیت ذخیره شد.',
        'error' => 'خطای پایگاه داده: ',
    ]
];

// فقط سوپرادمین!
if (!isset($_SESSION['admin_user_id'])) {
    header("Location: login.php");
    exit;
}
$admin_id = $_SESSION['admin_user_id'];
$stmt = $mysqli->prepare("SELECT is_super_admin FROM admin_users WHERE id=? LIMIT 1");
if (!$stmt) die('Prepare failed (admin check): ' . $mysqli->error);
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$stmt->bind_result($is_super_admin);
$stmt->fetch();
$stmt->close();
if (!$is_super_admin) {
    header("Location: access_denied.php");
    exit;
}

// گرفتن تنظیمات فعلی
$stmt = $mysqli->prepare("SELECT * FROM settings LIMIT 1");
if (!$stmt) die('Prepare failed (settings select): ' . $mysqli->error);
$stmt->execute();
$res = $stmt->get_result();
if (!$res) die('Get result failed: ' . $stmt->error);
$settings = $res->fetch_assoc();
$stmt->close();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'site_title_en', 'site_title_fa',
        'site_intro_en', 'site_intro_fa',
        'hero_image', 'logo_url',
        'joinus_title_en', 'joinus_title_fa',
        'joinus_desc_en', 'joinus_desc_fa',
        'joinus_rules_en', 'joinus_rules_fa',
        'joinus_benefits_en', 'joinus_benefits_fa',
    ];
    $data = [];
    foreach ($fields as $f) $data[$f] = trim($_POST[$f] ?? '');

    $stmt = $mysqli->prepare("UPDATE settings SET
        site_title_en=?, site_title_fa=?, site_intro_en=?, site_intro_fa=?,
        hero_image=?, logo_url=?,
        joinus_title_en=?, joinus_title_fa=?,
        joinus_desc_en=?, joinus_desc_fa=?,
        joinus_rules_en=?, joinus_rules_fa=?,
        joinus_benefits_en=?, joinus_benefits_fa=?
        WHERE id=1
    ");
    if (!$stmt) die('Prepare failed (settings update): ' . $mysqli->error);
    $stmt->bind_param(
        'ssssssssssssss',
        $data['site_title_en'], $data['site_title_fa'],
        $data['site_intro_en'], $data['site_intro_fa'],
        $data['hero_image'], $data['logo_url'],
        $data['joinus_title_en'], $data['joinus_title_fa'],
        $data['joinus_desc_en'], $data['joinus_desc_fa'],
        $data['joinus_rules_en'], $data['joinus_rules_fa'],
        $data['joinus_benefits_en'], $data['joinus_benefits_fa']
    );
    if ($stmt->execute()) {
        $success = $tr[$lang]['success'];
        $settings = $data;
    } else {
        $error = $tr[$lang]['error'] . $stmt->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="<?=$lang?>">
<head>
    <meta charset="UTF-8">
    <title><?=$tr[$lang]['title']?> | Admin Panel</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php include __DIR__.'/includes/admin-styles.php'; ?>
    <?php include __DIR__.'/../shared/inc/head-assets.php'; ?>
    <script src="https://cdn.tiny.cloud/1/irnrp1bnwa6iqujrcfr69gp5jzyu5da8dx7xgdtry9d9ppe2/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        body { background: #181f27 !important; color: #e6e9f2 !important; font-family: Vazirmatn, Tahoma, Arial, sans-serif; <?php if($is_rtl) echo "direction:rtl;"; ?> }
        .container-main { max-width: 900px; margin: 40px auto 0 auto; }
        .page-title { color:#38a8ff; font-size:1.45rem; font-weight:900; margin-bottom:1.7rem; }
        .form-label { color:#aad3ff; font-weight:700; }
        .form-control, textarea {
            background: #181f27;
            color: #e6e9f2;
            border: 1.5px solid #31415a;
            border-radius: 8px;
            font-size: 1.06rem;
        }
        .form-control:focus, textarea:focus {
            border-color:#38a8ff;
            background: #232d3b;
            color: #fff;
        }
        .tox-tinymce { border-radius: 10px !important; border:1.5px solid #31415a !important; }
        .btn-submit { background:#38c572; color:#fff; border-radius:7px; font-weight:700; }
        .btn-submit:hover { background:#289e5b; }
        .alert-success { background:#232d3b;border:1px solid #38c572;color:#38c572; }
        .alert-danger { background:#232d3b;border:1px solid #e13a3a;color:#e13a3a; }
        @media (max-width: 900px) { .container-main {max-width:99vw;} }
        @media (max-width: 650px) { .container-main {padding:0 2px;} .page-title {font-size:1.08rem;} }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            tinymce.init({
                selector: 'textarea.rich',
                directionality: '<?=( $is_rtl ? "rtl" : "ltr" )?>',
                skin: 'oxide-dark',
                content_css: 'dark',
                height: 210,
                menubar: false,
                plugins: 'lists advlist link autolink code table colorpicker textcolor',
                toolbar: 'undo redo | formatselect | bold italic underline forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | outdent indent | h1 h2 h3 | link table | removeformat code',
                toolbar_mode: 'wrap',
                branding: false,
                setup: function(editor) {
                    editor.on('change', function () { editor.save(); });
                }
            });
        });
    </script>
</head>
<body>
<?php include 'includes/superadmin-navbar.php'; ?>
<div class="container-main">
    <div class="page-title"><?=$tr[$lang]['title']?></div>
    <?php if($success): ?>
        <div class="alert alert-success"><?=$success?></div>
    <?php elseif($error): ?>
        <div class="alert alert-danger"><?=$error?></div>
    <?php endif; ?>
    <form method="post" autocomplete="off">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label"><?=$tr[$lang]['site_title_en']?></label>
                <input type="text" name="site_title_en" class="form-control" required value="<?=htmlspecialchars($settings['site_title_en'] ?? '')?>">
            </div>
            <div class="col-md-6">
                <label class="form-label"><?=$tr[$lang]['site_title_fa']?></label>
                <input type="text" name="site_title_fa" class="form-control" required value="<?=htmlspecialchars($settings['site_title_fa'] ?? '')?>">
            </div>
            <div class="col-md-6">
                <label class="form-label"><?=$tr[$lang]['hero_image']?></label>
                <input type="text" name="hero_image" class="form-control" value="<?=htmlspecialchars($settings['hero_image'] ?? '')?>">
            </div>
            <div class="col-md-6">
                <label class="form-label"><?=$tr[$lang]['logo_url']?></label>
                <input type="text" name="logo_url" class="form-control" value="<?=htmlspecialchars($settings['logo_url'] ?? '')?>">
            </div>
            <div class="col-12">
                <label class="form-label"><?=$tr[$lang]['site_intro_en']?></label>
                <textarea name="site_intro_en" class="form-control" rows="2"><?=htmlspecialchars($settings['site_intro_en'] ?? '')?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label"><?=$tr[$lang]['site_intro_fa']?></label>
                <textarea name="site_intro_fa" class="form-control" rows="2"><?=htmlspecialchars($settings['site_intro_fa'] ?? '')?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label"><?=$tr[$lang]['joinus_title_en']?></label>
                <input type="text" name="joinus_title_en" class="form-control" value="<?=htmlspecialchars($settings['joinus_title_en'] ?? '')?>">
            </div>
            <div class="col-md-6">
                <label class="form-label"><?=$tr[$lang]['joinus_title_fa']?></label>
                <input type="text" name="joinus_title_fa" class="form-control" value="<?=htmlspecialchars($settings['joinus_title_fa'] ?? '')?>">
            </div>
            <div class="col-12">
                <label class="form-label"><?=$tr[$lang]['joinus_desc_en']?></label>
                <textarea name="joinus_desc_en" class="form-control rich" rows="3"><?=htmlspecialchars($settings['joinus_desc_en'] ?? '')?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label"><?=$tr[$lang]['joinus_desc_fa']?></label>
                <textarea name="joinus_desc_fa" class="form-control rich" rows="3"><?=htmlspecialchars($settings['joinus_desc_fa'] ?? '')?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label"><?=$tr[$lang]['joinus_rules_en']?></label>
                <textarea name="joinus_rules_en" class="form-control rich" rows="3"><?=htmlspecialchars($settings['joinus_rules_en'] ?? '')?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label"><?=$tr[$lang]['joinus_rules_fa']?></label>
                <textarea name="joinus_rules_fa" class="form-control rich" rows="3"><?=htmlspecialchars($settings['joinus_rules_fa'] ?? '')?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label"><?=$tr[$lang]['joinus_benefits_en']?></label>
                <textarea name="joinus_benefits_en" class="form-control rich" rows="3"><?=htmlspecialchars($settings['joinus_benefits_en'] ?? '')?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label"><?=$tr[$lang]['joinus_benefits_fa']?></label>
                <textarea name="joinus_benefits_fa" class="form-control rich" rows="3"><?=htmlspecialchars($settings['joinus_benefits_fa'] ?? '')?></textarea>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-submit"><?=$tr[$lang]['save']?></button>
            </div>
        </div>
    </form>
</div>
</body>
</html>