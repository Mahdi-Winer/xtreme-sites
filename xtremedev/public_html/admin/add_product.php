<?php
session_start();
require_once __DIR__.'/../shared/inc/config.php';

if (!isset($_SESSION['admin_user_id']) || !isset($_SESSION['admin_access_token'])) {
    header("Location: login.php");
    exit;
}

$lang = 'en';
$is_rtl = false;

// Admin info
$username = $_SESSION['admin_username'] ?? '';
$email    = $_SESSION['admin_email'] ?? '';
$role     = $_SESSION['admin_role'] ?? '';
if (!in_array($role, ['superadmin', 'manager'])) {
    header("Location: access_denied.php");
    exit;
}

// Projects from API
$projects = [];
$api_projects_url = 'https://api.xtremedev.co/endpoints/admin/get_projects.php';
$ch = curl_init($api_projects_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $_SESSION['admin_access_token'],
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 8);
$resp = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($httpcode === 200) {
    $result = json_decode($resp, true);
    if (is_array($result) && isset($result['projects'])) {
        $projects = $result['projects'];
    }
}

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

// Initial values
$success = false;
$errors = [];
$translations = [];
$price   = '';
$is_active = 1;
$project_id = '';
$category_id = '';

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $translations = $_POST['translations'] ?? [];
    $price   = trim($_POST['price'] ?? '0');
    $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;
    $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;

    // Validation
    if ($project_id <= 0) $errors[] = "Project selection is required.";
    if ($category_id <= 0) $errors[] = "Category selection is required.";
    foreach($allowed_langs as $l) {
        if (empty($translations[$l]['name'] ?? '')) $errors[] = "Product name is required for $l";
        if (empty($translations[$l]['description'] ?? '')) $errors[] = "Description is required for $l";
    }
    if (!is_numeric($price) || $price < 0) $errors[] = "Price must be a non-negative number.";
    if (!in_array($is_active, [0, 1])) $errors[] = "Status value is invalid.";

    if (empty($errors)) {
        // Send to API
        $api_url = 'https://api.xtremedev.co/endpoints/admin/add_product.php';
        $postdata = [
            'project_id' => $project_id,
            'category_id' => $category_id,
            'price' => $price,
            'is_active' => $is_active,
            'languages' => $allowed_langs,
            'translations' => []
        ];
        foreach($allowed_langs as $l) {
            $postdata['translations'][$l] = [
                'name' => $translations[$l]['name'],
                'description' => $translations[$l]['description'],
            ];
        }
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer ".$_SESSION['admin_access_token'],
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));
        $resp = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode == 200) {
            $success = true;
            $translations = [];
            $price = '';
            $is_active = 1;
            $project_id = '';
            $category_id = '';
        } else {
            $errors[] = "Unknown error (API)";
        }
    }
}

// Role badge
function role_badge($role) {
    $map = [
        'superadmin' => ['Superadmin', '#38a8ff'],
        'manager'    => ['Manager',    '#00e9c2'],
        'support'    => ['Support',    '#ffb13a'],
        'read_only'  => ['Read Only',  '#6c8cff'],
    ];
    $d = $map[$role] ?? ['Unknown', '#aaa'];
    return '<span class="role-badge" style="background:'.$d[1].';">'.$d[0].'</span>';
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Add Product | XtremeDev Admin</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php include __DIR__.'/../shared/inc/head-assets.php'; ?>
    <?php include 'includes/admin-styles.php'; ?>
    <style>
        html, body { height: 100%; }
        body { background: #181f27 !important; color: #e6e9f2 !important; font-family: Vazirmatn, Tahoma, Arial, sans-serif; min-height: 100vh; margin: 0; display: flex; flex-direction: column; }
        .container-main { max-width: 1200px; margin: 48px auto 0 auto; flex: 1 0 auto; width: 97vw; }
        .page-title { font-weight:900; color:#38a8ff; font-size:2.1rem; letter-spacing:.5px; margin-bottom:1.2rem; display: flex; align-items: center; gap: 14px; }
        .role-badge { display:inline-block; padding: 3px 14px; font-size: 0.95rem; border-radius: 9px; color:#fff; font-weight:700; letter-spacing: .3px; vertical-align:middle; margin-left: 8px; box-shadow:0 1px 6px #22292f1a; }
        .product-form-box { background: #232d3b; border-radius: 23px; box-shadow:0 2px 22px #29364b22; border:2.2px solid #29364b; padding: 2.3rem 3.2rem 1.5rem 3.2rem; margin-bottom: 32px; max-width: 100%; }
        label {font-weight:700;color:#aad3ff;}
        .form-control, select.form-select { background: #232d3b; color: #e6e9f2; border: 1.5px solid #31415a; border-radius: 10px; padding: 12px 17px; font-size: 1.1rem; margin-bottom: 13px; transition: border .15s; }
        .form-control:focus, select.form-select:focus { outline: none; border-color: #38a8ff; background: #253040; color: #fff; }
        .btn-submit { background: linear-gradient(90deg,#38a8ff,#44e1ff 90%); color: #fff; font-weight: 800; border-radius: 11px; padding: 13px 0; width: 210px; font-size: 1.19rem; margin-top: 20px; margin-bottom: 18px; box-shadow: 0 2px 14px #38a8ff33; border: none; transition: background .18s, box-shadow .16s; letter-spacing: .3px;}
        .btn-submit:hover { background: linear-gradient(90deg,#2499fa,#1bc6e8 90%); color: #fff; box-shadow: 0 5px 24px #38a8ff40;}
        .form-link { color:#aad3ff; text-decoration:none; margin-top:14px; display:inline-block; font-size:0.98rem; margin-bottom:4px; }
        .form-link:hover {color:#fff;text-decoration:underline;}
        .alert-success { background: #202e3d; color: #aef3c8; border: 1.7px solid #1db67a; font-weight:700; font-size: 1.05rem; border-radius:9px; }
        .alert-danger { background: #32212a; color: #ffb1d1; border: 1.7px solid #e13a3a; font-weight:700; font-size: 1.05rem; border-radius:9px; }
        .footer-sticky { flex-shrink: 0; margin-top: auto; width: 100%; background: #232d3b; color: #aad3ff; padding: 18px 0 8px 0; text-align: center; border-top: 1.6px solid #31415a; font-size: 0.95rem; letter-spacing: .2px; }
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
        .category-loading { color:#aaa; font-size:0.97rem; padding:0.5rem 0; }
        @media (max-width: 1200px) {
            .container-main { max-width: 98vw; }
            .product-form-box { padding: 2.1rem .7rem 1.1rem .7rem; }
        }
        @media (max-width: 700px) {
            .container-main {padding:0 2px;}
            .page-title {font-size:1.15rem;}
            .product-form-box {padding: 1.1rem 0.2rem;}
            .btn-submit {width:100%;}
        }
    </style>
</head>
<body>
<?php
switch($role) {
    case 'superadmin': include 'includes/superadmin-navbar.php'; break;
    case 'manager':    include 'includes/manager-navbar.php'; break;
    default:           include 'includes/navbar.php';
}
?>
<div class="container-main">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-3">
        <div class="page-title">
            Add Product
            <?=role_badge($role)?>
        </div>
        <div>
      <span style="font-size:1rem;color:#b9d5f6;">
        <b><?=htmlspecialchars($username)?></b> (<span style="color:#38a8ff;"><?=htmlspecialchars($email)?></span>)
      </span>
            <a href="logout.php" class="btn btn-sm btn-danger ms-2">Logout</a>
        </div>
    </div>
    <div class="product-form-box">
        <?php if($success): ?>
            <div class="alert alert-success mb-3">Product successfully added!</div>
        <?php elseif($errors): ?>
            <div class="alert alert-danger mb-3"><?=implode('<br>', array_map('htmlspecialchars', $errors))?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off" id="product-form">
            <label for="project_id">Project <span style="color:#ff8e8e">*</span></label>
            <select class="form-select" id="project_id" name="project_id" required>
                <option value="">Select Project</option>
                <?php foreach($projects as $pr): ?>
                    <option value="<?=$pr['id']?>" <?=$pr['id']==$project_id?'selected':''?>><?=htmlspecialchars($pr['name'])?></option>
                <?php endforeach; ?>
            </select>
            <label for="category_id">Category <span style="color:#ff8e8e">*</span></label>
            <select class="form-select" id="category_id" name="category_id" required>
                <option value="">Select Category</option>
            </select>
            <div id="category_loading" class="category-loading" style="display:none;">
                Loading categories...
            </div>
            <label for="price">Price <span style="color:#ff8e8e">*</span></label>
            <input type="number" class="form-control" id="price" name="price" min="0" step="any" required value="<?=htmlspecialchars($price)?>">
            <label for="is_active">Status <span style="color:#ff8e8e">*</span></label>
            <select class="form-select" id="is_active" name="is_active" required>
                <option value="1" <?=$is_active==1?'selected':''?>>Active</option>
                <option value="0" <?=$is_active==0?'selected':''?>>Inactive</option>
            </select>
            <div style="margin-bottom:0.5rem;"></div>
            <?php foreach($allowed_langs as $l): ?>
                <div class="form-section" style="background:#20253a;padding:22px 22px 16px 22px;border-radius:16px;margin-bottom:26px;box-shadow:0 2px 24px #0002;border:1.7px solid #2b3a4c;">
                    <div class="lang-label">
                        Language: <b><?=htmlspecialchars($lang_names[$l] ?? strtoupper($l))?></b>
                    </div>
                    <label for="name_<?=$l?>">Product Name (<?=$l?>) <span style="color:#ff8e8e">*</span></label>
                    <input type="text" class="form-control" id="name_<?=$l?>" name="translations[<?=$l?>][name]" required value="<?=htmlspecialchars($translations[$l]['name'] ?? '')?>">
                    <label for="desc_<?=$l?>">Description (<?=$l?>) <span style="color:#ff8e8e">*</span></label>
                    <textarea class="form-control" id="desc_<?=$l?>" name="translations[<?=$l?>][description]" rows="2" required><?=htmlspecialchars($translations[$l]['description'] ?? '')?></textarea>
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-submit mt-2">Add Product</button>
        </form>
        <a href="products.php" class="form-link">&larr; Back to products list</a>
    </div>
</div>
<footer class="footer-sticky">
    &copy; <?=date('Y')?> XtremeDev. All rights reserved.
</footer>
<?php include __DIR__.'/../shared/inc/foot-assets.php'; ?>

<script>
function loadCategoriesForProject(project_id, category_id) {
    var $catSelect = document.getElementById('category_id');
    var $catLoading = document.getElementById('category_loading');
    $catSelect.innerHTML = '<option value="">Select Category</option>';
    if (!project_id) return;
    $catLoading.style.display = 'block';
    fetch('https://api.xtremedev.co/endpoints/get_product_categories.php?project_id='+encodeURIComponent(project_id)+'&lang=en')
        .then(res => res.json())
        .then(function(cats){
            $catSelect.innerHTML = '<option value="">Select Category</option>';
            if (cats && Array.isArray(cats)) {
                cats.forEach(function(cat){
                    var selected = (category_id && category_id == cat.id) ? 'selected' : '';
                    $catSelect.innerHTML += '<option value="'+cat.id+'" '+selected+'>'+cat.title+'</option>';
                });
            }
        })
        .finally(function() {
            $catLoading.style.display = 'none';
        });
}
document.addEventListener('DOMContentLoaded', function() {
    var projectSel = document.getElementById('project_id');
    var catSel = document.getElementById('category_id');
    loadCategoriesForProject(projectSel.value, <?=json_encode($category_id)?>);
    projectSel.addEventListener('change', function(){
        loadCategoriesForProject(this.value, '');
    });
});
</script>
</body>
</html>