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
        'title' => 'Project Roles',
        'add' => '+ Add Role',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'update' => 'Update',
        'create' => 'Create',
        'cancel' => 'Cancel',
        'actions' => 'Actions',
        'role_title' => 'Role Title',
        'role_desc' => 'Role Description',
        'project' => 'Project',
        'select_project' => 'Select Project',
        'role_created' => 'Role created.',
        'role_updated' => 'Role updated.',
        'role_deleted' => 'Role deleted.',
        'delete_failed' => 'Delete failed: ',
        'update_failed' => 'Update failed: ',
        'create_failed' => 'Create failed: ',
        'error_fill' => 'Please fill all fields.',
        'no_roles' => 'No roles found.',
        'are_you_sure' => 'Are you sure?',
        'id' => '#',
    ],
    'fa' => [
        'title' => 'نقش‌های پروژه',
        'add' => '+ افزودن نقش',
        'edit' => 'ویرایش',
        'delete' => 'حذف',
        'update' => 'ویرایش',
        'create' => 'افزودن',
        'cancel' => 'انصراف',
        'actions' => 'عملیات',
        'role_title' => 'عنوان نقش',
        'role_desc' => 'توضیحات نقش',
        'project' => 'پروژه',
        'select_project' => 'انتخاب پروژه',
        'role_created' => 'نقش ثبت شد.',
        'role_updated' => 'نقش ویرایش شد.',
        'role_deleted' => 'نقش حذف شد.',
        'delete_failed' => 'حذف نشد: ',
        'update_failed' => 'ویرایش نشد: ',
        'create_failed' => 'ثبت نشد: ',
        'error_fill' => 'لطفاً همه فیلدها را پر کنید.',
        'no_roles' => 'نقشی ثبت نشده است.',
        'are_you_sure' => 'مطمئن هستید؟',
        'id' => 'شماره',
    ]
];

// فقط سوپرادمین!
if (!isset($_SESSION['admin_user_id'])) { header("Location: login.php"); exit; }
$admin_id = $_SESSION['admin_user_id'];
$stmt = $mysqli->prepare("SELECT is_super_admin FROM admin_users WHERE id=? LIMIT 1");
$stmt->bind_param('i', $admin_id); $stmt->execute(); $stmt->bind_result($is_super_admin);
$stmt->fetch(); $stmt->close();
if (!$is_super_admin) { header("Location: access_denied.php"); exit; }

// لیست پروژه‌ها برای انتخاب در فرم نقش
$projectsArr = [];
$res = $mysqli->query("SELECT id, title FROM joinus_projects ORDER BY id ASC");
while($row = $res->fetch_assoc()) $projectsArr[$row['id']] = $row['title'];

// عملیات CRUD
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // حذف
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $stmt = $mysqli->prepare("DELETE FROM joinus_project_roles WHERE id=?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) $msg = $tr[$lang]['role_deleted'];
        else $msg = $tr[$lang]['delete_failed'].$stmt->error;
        $stmt->close();
    }
    // افزودن یا ویرایش
    if (isset($_POST['action']) && in_array($_POST['action'], ['create', 'edit'])) {
        $id = intval($_POST['id'] ?? 0);
        $project_id = intval($_POST['project_id'] ?? 0);
        $role_title = trim($_POST['role_title'] ?? '');
        $role_desc = trim($_POST['role_desc'] ?? '');
        if(!$project_id || !$role_title || !$role_desc) {
            $msg = $tr[$lang]['error_fill'];
        } else {
            if ($id) {
                $stmt = $mysqli->prepare("UPDATE joinus_project_roles SET project_id=?, role_title=?, role_desc=? WHERE id=?");
                $stmt->bind_param('issi', $project_id, $role_title, $role_desc, $id);
                if ($stmt->execute()) $msg = $tr[$lang]['role_updated'];
                else $msg = $tr[$lang]['update_failed'].$stmt->error;
                $stmt->close();
            } else {
                $stmt = $mysqli->prepare("INSERT INTO joinus_project_roles (project_id, role_title, role_desc) VALUES (?,?,?)");
                $stmt->bind_param('iss', $project_id, $role_title, $role_desc);
                if ($stmt->execute()) $msg = $tr[$lang]['role_created'];
                else $msg = $tr[$lang]['create_failed'].$stmt->error;
                $stmt->close();
            }
        }
    }
}

// دریافت لیست نقش‌ها
$roles = [];
$res = $mysqli->query("SELECT * FROM joinus_project_roles ORDER BY id ASC");
while ($row = $res->fetch_assoc()) $roles[] = $row;

// برای مقداردهی فرم ویرایش
$edit_role = ['id'=>'','project_id'=>'','role_title'=>'','role_desc'=>''];
if (isset($_GET['edit']) && intval($_GET['edit'])) {
    foreach ($roles as $r) {
        if ($r['id'] == intval($_GET['edit'])) {
            $edit_role = $r;
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
        .container-main { max-width: 950px; margin: 40px auto 0 auto; }
        .page-title { color:#38a8ff; font-size:1.32rem; font-weight:900; margin-bottom:1.5rem;}
        .btn-create {
            background:#38c572; color:#fff;
            border-radius:7px; font-weight:700; border:none;
        }
        .btn-create:hover { background:#289e5b; }
        .btn-edit {
            background:#38a8ff; color:#fff;
            border-radius:7px; font-weight:700; border:none;
        }
        .btn-edit:hover { background:#2499fa; }
        .btn-delete {
            background:#e13a3a; color:#fff;
            border-radius:7px; font-weight:700; border:none;
        }
        .btn-delete:hover { background:#c00; }
        .table {
            color: #e6e9f2;
            background: #232d3b;
        }
        .table-striped>tbody>tr:nth-of-type(odd)>* {
            background-color: #202733;
        }
        .table-striped>tbody>tr:nth-of-type(even)>* {
            background-color: #232d3b;
        }
        .table thead tr {
            background: #232d3b !important;
        }
        .table thead th {
            color: #38a8ff !important;
            font-weight: 900;
            border-bottom: 2px solid #31415a !important;
            font-size: 1.05rem;
            background: #232d3b !important;
        }
        .table th, .table td {
            border-color: #2a3547 !important;
        }
        .table td {
            color: #e6e9f2 !important;
            font-weight: 500;
        }
        .form-control, select {
            background:#232d3b;
            color:#e6e9f2;
            border: 1.5px solid #31415a;
            border-radius: 8px;
        }
        .form-control:focus, select:focus {
            border-color:#38a8ff;
            background: #181f27;
            color: #fff;
        }
        .edit-form {background:#232d3b; border-radius:12px; padding:22px 18px; margin-bottom:40px;}
        .edit-form label {font-weight:700; color:#38a8ff;}
        .edit-form .form-group {margin-bottom:18px;}
        @media (max-width: 900px) { .container-main {max-width:99vw;} }
        @media (max-width: 650px) { .container-main {padding:0 2px;} }
    </style>
</head>
<body>
<?php include 'includes/superadmin-navbar.php'; ?>
<div class="container-main">
    <div class="page-title d-flex align-items-center justify-content-between">
        <span><?=$tr[$lang]['title']?></span>
        <button class="btn btn-create" onclick="document.getElementById('createForm').style.display='block';window.scrollTo(0,0);"><?=$tr[$lang]['add']?></button>
    </div>

    <?php if($msg): ?>
        <div class="alert alert-info"><?=$msg?></div>
    <?php endif; ?>

    <!-- فرم افزودن/ویرایش -->
    <form class="edit-form" id="createForm" method="post" style="<?=($edit_role['id']||isset($_GET['add']))?'display:block':'display:none'?>">
        <input type="hidden" name="id" value="<?=htmlspecialchars($edit_role['id'])?>">
        <input type="hidden" name="action" value="<?=($edit_role['id']?'edit':'create')?>">
        <div class="form-group">
            <label><?=$tr[$lang]['project']?></label>
            <select class="form-control" name="project_id" required>
                <option value=""><?=$tr[$lang]['select_project']?></option>
                <?php foreach($projectsArr as $pid => $ptitle): ?>
                    <option value="<?=$pid?>" <?=$edit_role['project_id']==$pid?'selected':''?>><?=$ptitle?></option>
                <?php endforeach;?>
            </select>
        </div>
        <div class="form-group">
            <label><?=$tr[$lang]['role_title']?></label>
            <input type="text" class="form-control" name="role_title" required value="<?=htmlspecialchars($edit_role['role_title'])?>">
        </div>
        <div class="form-group">
            <label><?=$tr[$lang]['role_desc']?></label>
            <input type="text" class="form-control" name="role_desc" required value="<?=htmlspecialchars($edit_role['role_desc'])?>">
        </div>
        <div>
            <button type="submit" class="btn btn-success"><?=($edit_role['id']?$tr[$lang]['update']:$tr[$lang]['create'])?></button>
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('createForm').style.display='none';"><?=$tr[$lang]['cancel']?></button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped" id="rolesTable">
            <thead>
            <tr>
                <th><?=$tr[$lang]['id']?></th>
                <th><?=$tr[$lang]['project']?></th>
                <th><?=$tr[$lang]['role_title']?></th>
                <th><?=$tr[$lang]['role_desc']?></th>
                <th><?=$tr[$lang]['actions']?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($roles as $r): ?>
                <tr>
                    <td><?=htmlspecialchars($r['id'])?></td>
                    <td><?=isset($projectsArr[$r['project_id']]) ? htmlspecialchars($projectsArr[$r['project_id']]) : '<span class="text-danger">?</span>'?></td>
                    <td><?=htmlspecialchars($r['role_title'])?></td>
                    <td><?=htmlspecialchars($r['role_desc'])?></td>
                    <td>
                        <form method="get" style="display:inline;">
                            <input type="hidden" name="edit" value="<?=$r['id']?>">
                            <button class="btn btn-sm btn-edit" type="submit"><?=$tr[$lang]['edit']?></button>
                        </form>
                        <form method="post" style="display:inline;" onsubmit="return confirm('<?=$tr[$lang]['are_you_sure']?>');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?=$r['id']?>">
                            <button class="btn btn-sm btn-delete" type="submit"><?=$tr[$lang]['delete']?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach;?>
            <?php if(empty($roles)): ?>
                <tr><td colspan="5" class="text-center text-muted"><?=$tr[$lang]['no_roles']?></td></tr>
            <?php endif; ?>
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