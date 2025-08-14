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
        'title' => 'Team Management',
        'create' => '+ Create Member',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'delete_confirm' => 'Are you sure to delete this member?',
        'delete_failed' => 'Delete failed!',
        'server_error' => 'Server error!',
        'search_placeholder' => 'Search by name or skill ...',
        'no_members' => 'No team members found.',
        'col_id' => '#',
        'col_name_en' => 'Name (EN)',
        'col_name_fa' => 'Name (FA)',
        'col_role' => 'Role',
        'col_skills' => 'Skills',
        'col_priority' => 'Priority',
        'col_sub_role' => 'Sub Role',
        'col_actions' => 'Actions'
    ],
    'fa' => [
        'title' => 'مدیریت اعضای تیم',
        'create' => '+ افزودن عضو',
        'edit' => 'ویرایش',
        'delete' => 'حذف',
        'delete_confirm' => 'آیا از حذف این عضو مطمئن هستید؟',
        'delete_failed' => 'حذف انجام نشد!',
        'server_error' => 'خطای سرور!',
        'search_placeholder' => 'جستجو بر اساس نام یا مهارت ...',
        'no_members' => 'عضوی یافت نشد.',
        'col_id' => 'شماره',
        'col_name_en' => 'نام (انگلیسی)',
        'col_name_fa' => 'نام (فارسی)',
        'col_role' => 'نقش',
        'col_skills' => 'مهارت‌ها',
        'col_priority' => 'اولویت',
        'col_sub_role' => 'نقش تکمیلی',
        'col_actions' => 'عملیات'
    ]
];

// فقط سوپرادمین!
if (!isset($_SESSION['admin_user_id'])) {
    header("Location: login.php");
    exit;
}
$admin_id = $_SESSION['admin_user_id'];
$stmt = $mysqli->prepare("SELECT is_super_admin FROM admin_users WHERE id=? LIMIT 1");
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$stmt->bind_result($is_super_admin);
$stmt->fetch();
$stmt->close();

if (!$is_super_admin) {
    header("Location: access_denied.php");
    exit;
}

// دریافت لیست اعضا
$members = [];
$res = $mysqli->query("SELECT * FROM team ORDER BY priority, id");
while ($row = $res->fetch_assoc()) {
    $members[] = $row;
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
    <style>
        body { background: #181f27 !important; color: #e6e9f2 !important; font-family: Vazirmatn, Tahoma, Arial, sans-serif;<?php if($is_rtl) echo 'direction:rtl;'; ?> }
        .container-main { max-width: 1100px; margin: 40px auto 0 auto; }
        .page-title { color:#38a8ff; font-size:1.4rem; font-weight:900; margin-bottom:1.7rem; }
        .search-box,
        .search-box:focus {
            background: #181f27 !important;
            color: #e6e9f2 !important;
            border: 1.5px solid #31415a !important;
            border-radius: 8px;
            font-size: 1.03rem;
            box-shadow: none;
            transition: border-color .18s, background .18s;
            max-width: 320px;
            margin-bottom: 15px;
        }
        .search-box::placeholder {
            color: #aad3ff !important;
            opacity: .85;
        }
        .table, .table thead th, .table tbody td {
            background: #232d3b !important;
            color: #e6e9f2 !important;
            border-color: #31415a !important;
        }
        .table thead th {
            background: #1a2232 !important;
            color: #38a8ff !important;
            font-weight: 900;
            border-bottom-width: 2px;
        }
        .table tbody tr {
            background: #232d3b !important;
            transition: background .14s;
        }
        .table tbody tr:hover {
            background: #273143 !important;
            color: #fff !important;
        }
        .table tr td {
            vertical-align: middle;
            border-color: #31415a !important;
        }
        .btn-create { background:#38c572; color:#fff; border-radius:7px; font-weight:700; margin-bottom:13px;}
        .btn-create:hover { background:#289e5b; }
        .btn-edit { background:#38a8ff; color:#fff; border-radius:7px; font-weight:700; }
        .btn-edit:hover { background:#2499fa; }
        .btn-delete { background:#e13a3a; color:#fff; border-radius:7px; font-weight:700; }
        .btn-delete:hover { background:#c00; }
        @media (max-width: 900px) { .container-main {max-width:99vw;} }
        @media (max-width: 600px) {
            .container-main {padding:0 2px;}
            .page-title {font-size:1.1rem;}
            .table-responsive {font-size:0.93rem;}
        }
    </style>
</head>
<body>
<?php include 'includes/superadmin-navbar.php'; ?>
<div class="container-main">
    <div class="page-title d-flex align-items-center justify-content-between">
        <span><?=$tr[$lang]['title']?></span>
        <a href="team-create.php" class="btn btn-create"><?=$tr[$lang]['create']?></a>
    </div>
    <input type="text" class="form-control search-box" id="searchInput" placeholder="<?=$tr[$lang]['search_placeholder']?>">
    <div class="table-responsive">
        <table class="table mt-2" id="teamTable">
            <thead>
            <tr>
                <th><?=$tr[$lang]['col_id']?></th>
                <th><?=$tr[$lang]['col_name_en']?></th>
                <th><?=$tr[$lang]['col_name_fa']?></th>
                <th><?=$tr[$lang]['col_role']?></th>
                <th><?=$tr[$lang]['col_skills']?></th>
                <th><?=$tr[$lang]['col_priority']?></th>
                <th><?=$tr[$lang]['col_sub_role']?></th>
                <th><?=$tr[$lang]['col_actions']?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($members as $row): ?>
                <tr>
                    <td><?=htmlspecialchars($row['id'])?></td>
                    <td><?=htmlspecialchars($row['name_en'])?></td>
                    <td><?=htmlspecialchars($row['name_fa'])?></td>
                    <td><?=htmlspecialchars($row['role_id'])?></td>
                    <td>
                        <div style="color:#aad3ff;font-size:.99em;">
                            <?=htmlspecialchars($row['skill_en'])?><br>
                            <span style="color:#f4be42"><?=htmlspecialchars($row['skill_fa'])?></span>
                        </div>
                    </td>
                    <td><?=htmlspecialchars($row['priority'])?></td>
                    <td>
                        <?=htmlspecialchars($row['sub_role_en'])?><br>
                        <span style="color:#f4be42"><?=htmlspecialchars($row['sub_role_fa'])?></span>
                    </td>
                    <td>
                        <a href="edit-team.php?id=<?=intval($row['id'])?>" class="btn btn-sm btn-edit"><?=$tr[$lang]['edit']?></a>
                        <button class="btn btn-sm btn-delete" onclick="deleteMember(this)" data-id="<?=intval($row['id'])?>"><?=$tr[$lang]['delete']?></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if(empty($members)): ?>
                <tr><td colspan="8" class="text-center text-muted"><?=$tr[$lang]['no_members']?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // حذف عضو تیم با تایید و Ajax
    function deleteMember(btn) {
        var id = btn.getAttribute('data-id');
        if(!id) return;
        if(confirm(<?=json_encode($tr[$lang]['delete_confirm'])?>)) {
            btn.disabled = true;
            fetch('delete-team.php', {
                method: 'POST',
                body: new URLSearchParams('id='+id)
            })
                .then(r=>r.json())
                .then(res=>{
                    if(res.status === 'ok') {
                        let tr = btn.closest('tr');
                        if(tr) tr.remove();
                    } else {
                        alert(<?=json_encode($tr[$lang]['delete_failed'])?>);
                        btn.disabled = false;
                    }
                })
                .catch(()=>{
                    alert(<?=json_encode($tr[$lang]['server_error'])?>);
                    btn.disabled = false;
                });
        }
    }
    // سرچ زنده
    document.getElementById('searchInput').addEventListener('input', function() {
        let val = this.value.toLowerCase();
        document.querySelectorAll('#teamTable tbody tr').forEach(function(row) {
            let txt = row.innerText.toLowerCase();
            row.style.display = (!val || txt.includes(val)) ? '' : 'none';
        });
    });
</script>
</body>
</html>