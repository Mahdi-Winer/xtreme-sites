<?php
session_start();
require_once __DIR__ . '/../shared/inc/database-config.php';
require_once __DIR__ . '/../shared/inc/config.php';

// --- دوزبانه
$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'en');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : 'en';
$is_rtl = ($lang === 'fa');
$tr = [
    'en' => [
        'title' => 'Create Team Member',
        'back' => 'Back',
        'success' => 'Team member created successfully.',
        'error_fill' => 'Please fill all required fields.',
        'error_db' => 'Database error: ',
        'name_en' => 'Name (EN)',
        'name_fa' => 'Name (FA)',
        'role_id' => 'Role ID',
        'priority' => 'Priority',
        'skill_en' => 'Skill (EN)',
        'skill_fa' => 'Skill (FA)',
        'sub_role_en' => 'Sub Role (EN)',
        'sub_role_fa' => 'Sub Role (FA)',
        'long_bio_en' => 'Long Bio (EN)',
        'long_bio_fa' => 'Long Bio (FA)',
        'create' => 'Create',
    ],
    'fa' => [
        'title' => 'افزودن عضو جدید تیم',
        'back' => 'بازگشت',
        'success' => 'عضو تیم با موفقیت ثبت شد.',
        'error_fill' => 'لطفاً همه فیلدهای ضروری را پر کنید.',
        'error_db' => 'خطای دیتابیس: ',
        'name_en' => 'نام (انگلیسی)',
        'name_fa' => 'نام (فارسی)',
        'role_id' => 'شناسه نقش',
        'priority' => 'اولویت',
        'skill_en' => 'مهارت (انگلیسی)',
        'skill_fa' => 'مهارت (فارسی)',
        'sub_role_en' => 'زیرنقش (انگلیسی)',
        'sub_role_fa' => 'زیرنقش (فارسی)',
        'long_bio_en' => 'توضیح کامل (انگلیسی)',
        'long_bio_fa' => 'توضیح کامل (فارسی)',
        'create' => 'افزودن',
    ]
];

if (!isset($_SESSION['admin_user_id'])) {
    header("Location: login.php"); exit;
}
$admin_id = $_SESSION['admin_user_id'];
$stmt = $mysqli->prepare("SELECT is_super_admin FROM admin_users WHERE id=? LIMIT 1");
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$stmt->bind_result($is_super_admin);
$stmt->fetch();
$stmt->close();
if (!$is_super_admin) {
    header("Location: access_denied.php"); exit;
}

$error = '';
$success = '';
$data = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'name_en', 'name_fa', 'role_id', 'skill_en', 'skill_fa', 'priority',
        'sub_role_en', 'sub_role_fa', 'long_bio_en', 'long_bio_fa'
    ];
    foreach ($fields as $f) $data[$f] = trim($_POST[$f] ?? '');
    foreach (['name_en','name_fa','role_id','skill_en','skill_fa','priority','sub_role_en','sub_role_fa'] as $k) {
        if ($data[$k] === '' || ($k === 'role_id' && !is_numeric($data[$k])) || ($k === 'priority' && !is_numeric($data[$k]))) {
            $error = $tr[$lang]['error_fill'];
            break;
        }
    }
    if (!$error) {
        $stmt = $mysqli->prepare(
            "INSERT INTO team
            (name_en, name_fa, role_id, skill_en, skill_fa, priority, sub_role_en, sub_role_fa, long_bio_en, long_bio_fa)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'ssississss',
            $data['name_en'], $data['name_fa'], $data['role_id'], $data['skill_en'], $data['skill_fa'],
            $data['priority'], $data['sub_role_en'], $data['sub_role_fa'],
            $data['long_bio_en'], $data['long_bio_fa']
        );
        if ($stmt->execute()) {
            $success = $tr[$lang]['success'];
            foreach ($fields as $f) $data[$f] = '';
        } else {
            $error = $tr[$lang]['error_db'] . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="<?=$lang?>" dir="<?=$is_rtl?'rtl':'ltr'?>">
<head>
    <meta charset="UTF-8">
    <title><?=$tr[$lang]['title']?> | Admin Panel</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php include __DIR__.'/includes/admin-styles.php'; ?>
    <?php include __DIR__.'/../shared/inc/head-assets.php'; ?>
    <script src="https://cdn.tiny.cloud/1/irnrp1bnwa6iqujrcfr69gp5jzyu5da8dx7xgdtry9d9ppe2/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        body { background: #181f27 !important; color: #e6e9f2 !important; font-family: Vazirmatn, Tahoma, Arial, sans-serif; }
        .container-main { max-width: 650px; margin: 40px auto 0 auto; }
        .page-title { color:#38a8ff; font-size:1.3rem; font-weight:900; margin-bottom:1.4rem; }
        .form-label { color:#aad3ff; font-weight:700; }
        .form-control, textarea {
            background: #181f27;
            color: #e6e9f2;
            border: 1.5px solid #31415a;
            border-radius: 8px;
            font-size: 1.05rem;
        }
        .form-control:focus, textarea:focus {
            border-color:#38a8ff;
            background: #232d3b;
            color: #fff;
        }
        .tox-tinymce { border-radius: 10px !important; border:1.5px solid #31415a !important; }
        .btn-submit { background:#38c572; color:#fff; border-radius:7px; font-weight:700; }
        .btn-submit:hover { background:#289e5b; }
        .btn-back { background:#232d3b; border:1.5px solid #38a8ff; color:#38a8ff; border-radius:7px; font-weight:700; margin-<?=$is_rtl?'left':'right'?>:6px;}
        .btn-back:hover { background:#38a8ff; color:#fff; }
        .alert-success { background:#232d3b;border:1px solid #38c572;color:#38c572; }
        .alert-danger { background:#232d3b;border:1px solid #e13a3a;color:#e13a3a; }
        @media (max-width: 650px) { .container-main {padding:0 2px;} .page-title {font-size:1.08rem;} }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            tinymce.init({
                selector: 'textarea.rich',
                directionality: '<?=$is_rtl ? 'rtl' : 'ltr'?>',
                skin: 'oxide-dark',
                content_css: 'dark',
                height: 220,
                menubar: false,
                plugins: 'lists advlist link autolink code table colorpicker textcolor',
                toolbar: 'undo redo | formatselect | bold italic underline forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | outdent indent | h1 h2 h3 | link table | removeformat code',
                toolbar_mode: 'wrap',
                branding: false,
                language: '<?=$lang == "fa" ? "fa_IR" : "en"?>',
                setup: function(editor) {
                    editor.on('change', function () { editor.save(); });
                }
            });
        });
    </script>
    <?php if($lang=='fa'): ?>
        <script src="https://cdn.tiny.cloud/1/irnrp1bnwa6iqujrcfr69gp5jzyu5da8dx7xgdtry9d9ppe2/tinymce/7/langs/fa_IR.js"></script>
    <?php endif; ?>
</head>
<body>
<?php include 'includes/superadmin-navbar.php'; ?>
<div class="container-main">
    <div class="page-title d-flex align-items-center justify-content-between">
        <span><?=$tr[$lang]['title']?></span>
        <a href="team-management.php" class="btn btn-back">&larr; <?=$tr[$lang]['back']?></a>
    </div>
    <?php if($success): ?>
        <div class="alert alert-success"><?=$success?></div>
    <?php elseif($error): ?>
        <div class="alert alert-danger"><?=$error?></div>
    <?php endif; ?>
    <form method="post" autocomplete="off">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label"><?=$tr[$lang]['name_en']?></label>
                <input type="text" name="name_en" value="<?=htmlspecialchars($data['name_en'] ?? '')?>" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label"><?=$tr[$lang]['name_fa']?></label>
                <input type="text" name="name_fa" value="<?=htmlspecialchars($data['name_fa'] ?? '')?>" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label"><?=$tr[$lang]['role_id']?></label>
                <input type="number" name="role_id" value="<?=htmlspecialchars($data['role_id'] ?? '')?>" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label"><?=$tr[$lang]['priority']?></label>
                <input type="number" name="priority" value="<?=htmlspecialchars($data['priority'] ?? '1')?>" class="form-control" required min="1">
            </div>
            <div class="col-md-6">
                <label class="form-label"><?=$tr[$lang]['skill_en']?></label>
                <input type="text" name="skill_en" value="<?=htmlspecialchars($data['skill_en'] ?? '')?>" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label"><?=$tr[$lang]['skill_fa']?></label>
                <input type="text" name="skill_fa" value="<?=htmlspecialchars($data['skill_fa'] ?? '')?>" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label"><?=$tr[$lang]['sub_role_en']?></label>
                <input type="text" name="sub_role_en" value="<?=htmlspecialchars($data['sub_role_en'] ?? '')?>" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label"><?=$tr[$lang]['sub_role_fa']?></label>
                <input type="text" name="sub_role_fa" value="<?=htmlspecialchars($data['sub_role_fa'] ?? '')?>" class="form-control" required>
            </div>
            <div class="col-12">
                <label class="form-label"><?=$tr[$lang]['long_bio_en']?></label>
                <textarea name="long_bio_en" class="form-control rich" rows="4"><?=htmlspecialchars($data['long_bio_en'] ?? '')?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label"><?=$tr[$lang]['long_bio_fa']?></label>
                <textarea name="long_bio_fa" class="form-control rich" rows="4"><?=htmlspecialchars($data['long_bio_fa'] ?? '')?></textarea>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-submit"><?=$tr[$lang]['create']?></button>
            </div>
        </div>
    </form>
</div>
</body>
</html>