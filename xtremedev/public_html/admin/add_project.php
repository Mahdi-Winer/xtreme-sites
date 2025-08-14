<?php
session_start();
require_once __DIR__ . '/../shared/inc/config.php';
if (!isset($_SESSION['admin_user_id']) || !isset($_SESSION['admin_access_token'])) {
    header("Location: login.php");
    exit;
}

$lang = 'en';
$is_rtl = false;

// Allowed languages from config
$allowed_langs = (defined('ALLOWED_LANGS') && is_array(ALLOWED_LANGS)) ? ALLOWED_LANGS : ['en'];
$lang_names = [
    'en' => 'English',
    'fa' => 'Persian',
    'ar' => 'Arabic',
    'tr' => 'Turkish',
    'de' => 'German',
    'fr' => 'French',
    'ru' => 'Russian',
    'es' => 'Spanish',
];

$role = $_SESSION['admin_role'] ?? '';
if (!in_array($role, ['superadmin', 'manager'])) {
    header("Location: access_denied.php");
    exit;
}

// مقداردهی اولیه
$image = '';
$status = 'active';
$translations = [];
$msg = '';

// فرم ثبت
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $image = trim($_POST['image'] ?? '');
    $status = ($_POST['status'] ?? 'active') === 'active' ? 'active' : 'inactive';
    $translations = $_POST['translations'] ?? [];

    // اعتبارسنجی عنوان هر زبان
    $errors = [];
    foreach($allowed_langs as $lng) {
        if (empty($translations[$lng]['title'])) {
            $errors[] = "Title is required for " . ($lang_names[$lng] ?? strtoupper($lng));
        }
    }

    if (!$errors) {
        // ارسال به API
        $api_url = 'https://api.xtremedev.co/endpoints/admin/add_project.php';
        $payload = [
            'image' => $image,
            'status' => $status,
            'languages' => $allowed_langs,
            'translations' => []
        ];
        foreach($allowed_langs as $lng) {
            $payload['translations'][$lng] = [
                'title' => $translations[$lng]['title'] ?? '',
                'description' => $translations[$lng]['description'] ?? '',
                'long_description' => $translations[$lng]['long_description'] ?? ''
            ];
        }
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer ".$_SESSION['admin_access_token'],
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        $resp = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode == 200) {
            $msg = '<div class="alert alert-success">Project added successfully.</div>';
            $image = '';
            $status = 'active';
            $translations = [];
        } else {
            $msg = '<div class="alert alert-danger">Could not add project.</div>';
        }
    } else {
        $msg = '<div class="alert alert-danger">'.implode('<br>',$errors).'</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Add New Project | XtremeDev Admin</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php if(file_exists(__DIR__.'/../shared/inc/head-assets.php')) include __DIR__.'/../shared/inc/head-assets.php'; ?>
    <?php if(file_exists('includes/admin-styles.php')) include 'includes/admin-styles.php'; ?>
    <script src="https://cdn.tiny.cloud/1/irnrp1bnwa6iqujrcfr69gp5jzyu5da8dx7xgdtry9d9ppe2/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        body {
            background: #181f27 !important;
            color: #e6e9f2 !important;
            font-family: Vazirmatn, Tahoma, Arial, sans-serif;
        }
        .container-main {
            max-width: 1200px;
            min-width: 320px;
            margin: 48px auto 0 auto;
            padding-bottom: 48px;
            width: 97vw;
        }
        .edit-header {
            font-weight: 900;
            color: #3bbcff;
            font-size: 2.1rem;
            letter-spacing: .5px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-end;
            gap: 14px;
        }
        .alert { margin-bottom: 1.8rem; }
        .project-form {
            background: #232d3b;
            border-radius: 23px;
            box-shadow: 0 4px 34px #2c3b5528, 0 1.2px 0 #38a8ff1a;
            border: 2.2px solid #2b3a4c;
            padding: 2.3rem 3.2rem 1.5rem 3.2rem;
            margin-bottom: 32px;
        }
        .form-section {
            background: #20253a;
            padding: 22px 22px 16px 22px;
            border-radius: 16px;
            margin-bottom: 26px;
            box-shadow: 0 2px 24px #0002;
            border: 1.7px solid #2b3a4c;
        }
        .form-label {
            font-weight:700;
            margin-bottom:5px;
            color:#3bbcff;
            display: block;
            font-size: 1.07rem;
            letter-spacing: .1px;
        }
        .form-control, textarea {
            background: #232d3b !important;
            color: #e6e9f2 !important;
            border: 1.5px solid #31415a;
            border-radius: 10px;
            padding: 12px 17px;
            font-size: 1.1rem;
            margin-bottom: 13px;
            transition: border .15s, background .15s, color .15s;
            caret-color: #38a8ff;
        }
        .form-control:focus, textarea:focus {
            outline: none;
            border-color: #3bbcff;
            background: #253040 !important;
            color: #fff !important;
        }
        .form-control::placeholder, textarea::placeholder { color: #a6b5cf !important; opacity: 1; }
        .form-control:focus::placeholder, textarea:focus::placeholder { color: #78aaff !important; opacity: 1; }
        textarea { min-height: 90px; resize: vertical; }
        .tox-tinymce, .tox-edit-area__iframe { background: #232d3b !important; color: #e6e9f2 !important; border-radius: 10px; }
        .tox .tox-edit-area__iframe { background: #232d3b !important; }
        .tox .tox-statusbar, .tox .tox-toolbar { background: #202942 !important; }
        .tox .tox-toolbar__primary { background: #232d3b !important; }
        .tox .tox-toolbar__overflow { background: #232d3b !important; }
        .tox .tox-statusbar__branding { display: none!important; }
        .tox .tox-statusbar__text-container { color: #b5d1ff !important; }
        .tox .tox-edit-area__iframe { color-scheme: dark; }
        .img-preview {
            max-width: 210px;
            max-height: 150px;
            margin-top:9px;
            border-radius:12px;
            box-shadow:0 2px 18px #0002;
            border: 1.5px solid #3bbcff44;
        }
        label.required:after {
            content: "*";
            color:#e13a3a;
            font-size:1.08em;
            margin-left:3px;
        }
        .lang-label {
            color:#ffd48d;
            font-size:1.05rem;
            font-weight: 600;
            margin-bottom:8px;
            margin-top:0;
            display:inline-block;
            border-left: 4px solid #38a8ff;
            padding-left: 12px;
            margin-left: -12px;
            background: #232d3b;
            border-radius: 6px 0 0 6px;
        }
        .btn-save {
            background: linear-gradient(90deg,#38a8ff,#44e1ff 90%);
            color: #fff;
            font-weight: 800;
            border-radius: 11px;
            padding: 13px 0;
            width: 210px;
            font-size: 1.19rem;
            margin-top: 20px;
            margin-bottom: 18px;
            box-shadow: 0 2px 14px #38a8ff33;
            border: none;
            transition: background .18s, box-shadow .16s;
            letter-spacing: .3px;
        }
        .btn-save:hover {
            background: linear-gradient(90deg,#2499fa,#1bc6e8 90%);
            color: #fff;
            box-shadow: 0 5px 24px #38a8ff40;
        }
        .btn-cancel {
            background: #31415a;
            color: #aad3ff;
            border-radius: 11px;
            padding: 11px 34px;
            font-weight: 600;
            font-size: 1.07rem;
            margin-left: 24px;
            margin-top: 20px;
            border: none;
            transition: background .16s, color .16s;
        }
        .btn-cancel:hover {
            background: #202942;
            color: #fff;
        }
        @media (max-width: 1200px) {
            .container-main { max-width: 98vw; }
            .project-form { padding: 2.1rem .7rem 1.1rem .7rem; }
        }
        @media (max-width: 700px){
            .container-main {padding:0 2px;}
            .edit-header {font-size:1.15rem;}
            .project-form {padding: 1.1rem 0.2rem;}
            .form-section {padding: 11px 5px 10px 10px;}
            .btn-save, .btn-cancel {width:100%; margin-left:0;}
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            <?php foreach($allowed_langs as $lng): ?>
            tinymce.init({
                selector: '#long_<?=$lng?>',
                height: 220,
                menubar: false,
                directionality: 'ltr',
                language: 'en',
                plugins: 'link image lists code directionality table',
                toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image table | code ltr rtl',
                content_style: "body { background-color: #232d3b !important; color: #e6e9f2 !important; font-family: Vazirmatn, Tahoma, Arial, sans-serif; font-size: 16px; } a { color: #38a8ff; }",
                branding: false,
                skin: 'oxide-dark',
                content_css: 'dark',
                browser_spellcheck: true,
                contextmenu: false
            });
            <?php endforeach; ?>
        });
    </script>
</head>
<body>
<?php if(file_exists('includes/superadmin-navbar.php')) include 'includes/superadmin-navbar.php'; ?>
<div class="container-main">
    <div style="margin-bottom:40px;">
        <a href="projects.php" class="btn btn-cancel">Back to Projects</a>
    </div>
    <div class="edit-header">
        Add New Project
    </div>
    <?php if(!empty($msg)) echo $msg; ?>
    <form method="post" autocomplete="off" class="project-form">
        <div class="form-section">
            <label for="image" class="form-label required">Image URL/Path</label>
            <input type="text" class="form-control" name="image" id="image" value="<?=htmlspecialchars($image)?>" required>
            <?php if(!empty($image)): ?>
                <img src="<?=htmlspecialchars($image)?>" class="img-preview" alt="project image">
            <?php endif; ?>
        </div>
        <div class="form-section">
            <label for="status" class="form-label required">Status</label>
            <select class="form-control" id="status" name="status" required>
                <option value="active" <?=$status=='active'?'selected':''?>>Active</option>
                <option value="inactive" <?=$status=='inactive'?'selected':''?>>Inactive</option>
            </select>
        </div>
        <?php foreach($allowed_langs as $lng): ?>
        <div class="form-section">
            <div class="lang-label">Language: <b><?=htmlspecialchars($lang_names[$lng] ?? strtoupper($lng))?></b></div>
            <label class="form-label required" for="title_<?=$lng?>">Title</label>
            <input type="text" class="form-control" name="translations[<?=$lng?>][title]" id="title_<?=$lng?>" maxlength="255" value="<?=htmlspecialchars($translations[$lng]['title'] ?? '')?>" required placeholder="Title (<?=$lng?>)">
            
            <label class="form-label" for="desc_<?=$lng?>">Short Description</label>
            <input type="text" class="form-control" name="translations[<?=$lng?>][description]" id="desc_<?=$lng?>" maxlength="255" value="<?=htmlspecialchars($translations[$lng]['description'] ?? '')?>" placeholder="Short Description (<?=$lng?>)">
            
            <label class="form-label" for="long_<?=$lng?>">Full Description</label>
            <textarea class="form-control" id="long_<?=$lng?>" name="translations[<?=$lng?>][long_description]" placeholder="Full Description (<?=$lng?>)"><?=htmlspecialchars($translations[$lng]['long_description'] ?? '')?></textarea>
        </div>
        <?php endforeach; ?>
        <div style="display:flex;flex-wrap:wrap;gap:0;">
            <button type="submit" class="btn btn-save">Save Project</button>
            <a href="projects.php" class="btn btn-cancel">Cancel</a>
        </div>
    </form>
</div>
<?php if(file_exists(__DIR__.'/../shared/inc/foot-assets.php')) include __DIR__.'/../shared/inc/foot-assets.php'; ?>
</body>
</html>