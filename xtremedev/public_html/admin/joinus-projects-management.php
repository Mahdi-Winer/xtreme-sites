<?php
session_start();
require_once __DIR__ . '/../shared/inc/database-config.php';
require_once __DIR__ . '/../shared/inc/config.php';

// فقط سوپرادمین!
if (!isset($_SESSION['admin_user_id'])) { header("Location: login.php"); exit; }
$admin_id = $_SESSION['admin_user_id'];
$stmt = $mysqli->prepare("SELECT is_super_admin FROM admin_users WHERE id=? LIMIT 1");
$stmt->bind_param('i', $admin_id); $stmt->execute(); $stmt->bind_result($is_super_admin);
$stmt->fetch(); $stmt->close();
if (!$is_super_admin) { header("Location: access_denied.php"); exit; }

// --- دوزبانه
$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'en');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : 'en';
$is_rtl = ($lang === 'fa');
$tr = [
    'en' => [
        'title' => 'Join Us Projects',
        'page_title' => 'Join Us Projects',
        'add' => '+ Add Project',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'update' => 'Update',
        'create' => 'Create',
        'cancel' => 'Cancel',
        'actions' => 'Actions',
        'status' => 'Status',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'project_created' => 'Project created.',
        'project_updated' => 'Project updated.',
        'project_deleted' => 'Project deleted.',
        'delete_failed' => 'Delete failed: ',
        'error_fill' => 'Please fill all fields.',
        'desc' => 'Description',
        'no_projects' => 'No projects found.',
        'are_you_sure' => 'Are you sure?',
        'id' => '#',
        'project_title' => 'Title',
    ],
    'fa' => [
        'title' => 'پروژه‌های همکاری',
        'page_title' => 'پروژه‌های همکاری',
        'add' => '+ افزودن پروژه',
        'edit' => 'ویرایش',
        'delete' => 'حذف',
        'update' => 'ویرایش',
        'create' => 'افزودن',
        'cancel' => 'انصراف',
        'actions' => 'عملیات',
        'status' => 'وضعیت',
        'active' => 'فعال',
        'inactive' => 'غیرفعال',
        'project_created' => 'پروژه ثبت شد.',
        'project_updated' => 'پروژه ویرایش شد.',
        'project_deleted' => 'پروژه حذف شد.',
        'delete_failed' => 'حذف نشد: ',
        'error_fill' => 'لطفاً همه فیلدها را پر کنید.',
        'desc' => 'توضیحات',
        'no_projects' => 'هیچ پروژه‌ای یافت نشد.',
        'are_you_sure' => 'مطمئن هستید؟',
        'id' => 'شماره',
        'project_title' => 'عنوان',
    ]
];

// عملیات CRUD
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // حذف
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $stmt = $mysqli->prepare("DELETE FROM joinus_projects WHERE id=?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) $msg = $tr[$lang]['project_deleted'];
        else $msg = $tr[$lang]['delete_failed'].$stmt->error;
        $stmt->close();
    }
    // افزودن یا ویرایش
    if (isset($_POST['action']) && in_array($_POST['action'], ['create', 'edit'])) {
        $id = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;
        if(!$title || !$desc) {
            $msg = $tr[$lang]['error_fill'];
        } else {
            if ($id) {
                $stmt = $mysqli->prepare("UPDATE joinus_projects SET title=?, description=?, is_active=? WHERE id=?");
                $stmt->bind_param('ssii', $title, $desc, $is_active, $id);
                if ($stmt->execute()) $msg = $tr[$lang]['project_updated'];
                else $msg = $tr[$lang]['delete_failed'].$stmt->error;
                $stmt->close();
            } else {
                $stmt = $mysqli->prepare("INSERT INTO joinus_projects (title, description, is_active) VALUES (?,?,?)");
                $stmt->bind_param('ssi', $title, $desc, $is_active);
                if ($stmt->execute()) $msg = $tr[$lang]['project_created'];
                else $msg = $tr[$lang]['delete_failed'].$stmt->error;
                $stmt->close();
            }
        }
    }
}

// دریافت لیست پروژه‌ها
$projects = [];
$res = $mysqli->query("SELECT * FROM joinus_projects ORDER BY id ASC");
while ($row = $res->fetch_assoc()) $projects[] = $row;

// برای مقداردهی فرم ویرایش
$edit_project = ['id'=>'','title'=>'','description'=>'','is_active'=>1];
if (isset($_GET['edit']) && intval($_GET['edit'])) {
    foreach ($projects as $p) {
        if ($p['id'] == intval($_GET['edit'])) {
            $edit_project = $p;
            break;
        }
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
    <style>
        body { background: #181f27 !important; color: #e6e9f2 !important;<?php if($is_rtl) echo 'direction:rtl;'; ?>}
        .container-main { max-width: 1200px; margin: 48px auto 0 auto; }
        .page-title { color:#38a8ff; font-size:1.7rem; font-weight:900; margin-bottom:2rem; letter-spacing:.4px;}
        .btn-create {
            background:#38c572; color:#fff;
            border-radius:8px; font-weight:700; border:none;
            font-size:1.06rem; padding:8px 28px; transition:background .15s;
        }
        .btn-create:hover { background:#289e5b; }
        .btn-edit {
            background:#38a8ff; color:#fff;
            border-radius:8px; font-weight:700; border:none;
            font-size:1.01rem; padding:6px 20px; margin:0 2px; transition:background .15s;
        }
        .btn-edit:hover { background:#2499fa; }
        .btn-delete {
            background:#e13a3a; color:#fff;
            border-radius:8px; font-weight:700; border:none;
            font-size:1.01rem; padding:6px 20px; margin:0 2px; transition:background .15s;
        }
        .btn-delete:hover { background:#c00; }
        .badge-active { background:#38c572; color:#fff; padding:5px 19px; border-radius:15px; font-weight:700; font-size:1.04rem;}
        .badge-inactive { background:#9baacf; color:#fff; padding:5px 19px; border-radius:15px; font-weight:700; font-size:1.04rem;}
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            margin: 34px 0 50px 0;
            border-radius: 18px;
            background: #232d3b;
            box-shadow:0 2px 16px #38a8ff0c;
        }
        .table {
            width:100%;
            min-width: 900px;
            color: #e6e9f2;
            background: transparent;
            border-collapse:collapse;
        }
        .table-striped>tbody>tr:nth-of-type(odd)>* {
            background-color: #212a3a !important;
        }
        .table-striped>tbody>tr:nth-of-type(even)>* {
            background-color: #232d3b !important;
        }
        .table thead tr {
            background: #232d3b !important;
        }
        .table thead th {
            color: #38a8ff !important;
            font-weight: 900;
            border-bottom: 2px solid #31415a !important;
            font-size: 1.11rem;
            background: #232d3b !important;
            padding-top:18px;padding-bottom:18px;
        }
        .table th, .table td {
            border-color: #2a3547 !important;
            text-align: center;
            padding: 1.15rem .75rem;
        }
        .table td {
            color: #e6e9f2 !important;
            font-weight: 500;
            font-size:1.03rem;
            vertical-align: middle;
        }
        .form-control, select {
            background:#232d3b;
            color:#e6e9f2;
            border: 1.5px solid #31415a;
            border-radius: 8px;
            font-size:1.07rem;
            padding:9px 16px;
        }
        .form-control:focus, select:focus {
            border-color:#38a8ff;
            background: #181f27;
            color: #fff;
        }
        .edit-form {background:#232d3b; border-radius:16px; padding:30px 24px; margin-bottom:40px; box-shadow:0 2px 10px #0003;}
        .edit-form label {font-weight:700; color:#38a8ff;}
        .edit-form .form-group {margin-bottom:22px;}
        .edit-form input, .edit-form select {margin-top:4px;}
        .edit-form button {margin-top:10px;}
        @media (max-width: 1100px) { .container-main {max-width:99vw;} }
        @media (max-width: 900px) { .table {min-width:700px;} .edit-form {padding:18px 6px;} }
        @media (max-width: 650px) { .container-main {padding:0 1vw;} .edit-form {padding:12px 2vw;} .table td, .table th {padding:8px 2vw;} }
    </style>
</head>
<body>
<?php include 'includes/superadmin-navbar.php'; ?>
<div class="container-main">
    <div class="page-title d-flex align-items-center justify-content-between">
        <span><?=$tr[$lang]['page_title']?></span>
        <button class="btn btn-create" onclick="document.getElementById('createForm').style.display='block';window.scrollTo(0,0);"><?=$tr[$lang]['add']?></button>
    </div>

    <?php if($msg): ?>
        <div class="alert alert-info"><?=$msg?></div>
    <?php endif; ?>

    <!-- فرم افزودن/ویرایش -->
    <form class="edit-form" id="createForm" method="post" style="<?=($edit_project['id']||isset($_GET['add']))?'display:block':'display:none'?>">
        <input type="hidden" name="id" value="<?=$edit_project['id']?>">
        <input type="hidden" name="action" value="<?=($edit_project['id']?'edit':'create')?>">
        <div class="form-group">
            <label><?=$tr[$lang]['project_title']?></label>
            <input type="text" class="form-control" name="title" required value="<?=htmlspecialchars($edit_project['title'])?>">
        </div>
        <div class="form-group">
            <label><?=$tr[$lang]['desc']?></label>
            <input type="text" class="form-control" name="description" required value="<?=htmlspecialchars($edit_project['description'])?>">
        </div>
        <div class="form-group">
            <label><?=$tr[$lang]['status']?></label>
            <select class="form-control" name="is_active">
                <option value="1" <?=$edit_project['is_active']?'selected':''?>><?=$tr[$lang]['active']?></option>
                <option value="0" <?=!$edit_project['is_active']?'selected':''?>><?=$tr[$lang]['inactive']?></option>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-success"><?=($edit_project['id']?$tr[$lang]['update']:$tr[$lang]['create'])?></button>
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('createForm').style.display='none';"><?=$tr[$lang]['cancel']?></button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped" id="projTable">
            <thead>
            <tr>
                <th><?=$tr[$lang]['id']?></th>
                <th><?=$tr[$lang]['project_title']?></th>
                <th><?=$tr[$lang]['desc']?></th>
                <th><?=$tr[$lang]['status']?></th>
                <th><?=$tr[$lang]['actions']?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($projects as $p): ?>
                <tr>
                    <td><?=htmlspecialchars($p['id'])?></td>
                    <td><?=htmlspecialchars($p['title'])?></td>
                    <td><?=htmlspecialchars($p['description'])?></td>
                    <td>
                        <?php if($p['is_active']): ?>
                            <span class="badge-active"><?=$tr[$lang]['active']?></span>
                        <?php else: ?>
                            <span class="badge-inactive"><?=$tr[$lang]['inactive']?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="get" style="display:inline;">
                            <input type="hidden" name="edit" value="<?=$p['id']?>">
                            <button class="btn btn-edit" type="submit"><?=$tr[$lang]['edit']?></button>
                        </form>
                        <form method="post" style="display:inline;" onsubmit="return confirm('<?=$tr[$lang]['are_you_sure']?>');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?=$p['id']?>">
                            <button class="btn btn-delete" type="submit"><?=$tr[$lang]['delete']?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach;?>
            <?php if(empty($projects)): ?>
                <tr><td colspan="5" class="text-center text-muted" style="color:#8ba7c7;font-size:1.13rem;"><?=$tr[$lang]['no_projects']?></td></tr>
            <?php endif;?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__.'/../shared/inc/foot-assets.php'; ?>
<script>
    if(window.location.search.indexOf('add') !== -1) {
        document.getElementById('createForm').style.display = 'block';
        window.scrollTo(0,0);
    }
</script>
</body>
</html>