<?php
session_start();
if (!isset($_SESSION['admin_user_id']) || !isset($_SESSION['admin_access_token'])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__.'/../shared/inc/config.php';

$username = $_SESSION['admin_username'] ?? '';
$email    = $_SESSION['admin_email'] ?? '';
$role     = $_SESSION['admin_role'] ?? '';
$can_edit = ($role === 'superadmin' || $role === 'manager');
$can_view = ($role === 'support' || $role === 'read_only');
if(!$can_edit && !$can_view){
    header("Location: access_denied.php"); exit;
}
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
// Delete project if id is set
$delete_msg = '';
if ($can_edit && isset($_GET['delete']) && intval($_GET['delete']) > 0) {
    $project_id = intval($_GET['delete']);
    $api_url = 'https://api.xtremedev.co/endpoints/admin/delete_project.php';
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer ".$_SESSION['admin_access_token'],
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['project_id' => $project_id]));
    $resp = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode == 200) {
        $delete_msg = '<div class="alert alert-success">Project deleted successfully.</div>';
    } else {
        $delete_msg = '<div class="alert alert-danger">Delete failed or project not found.</div>';
    }
}
// Get projects from API
$api_url = 'https://api.xtremedev.co/endpoints/admin/list_projects.php';
$params = ['lang' => 'en'];
if($search !== '') $params['search'] = $search;
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url . ($params ? ('?' . http_build_query($params)) : ''));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $_SESSION['admin_access_token']
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 12);
$resp = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$projects = [];
if ($httpcode === 200 && $resp) {
    $data = json_decode($resp, true);
    if (is_array($data)) $projects = $data;
} else {
    $projects = [];
}
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
function project_status_badge($status) {
    $status = strtolower($status);
    $map = [
        'active'   => ['Active',   '#38c572'],
        'inactive' => ['Inactive', '#e13a3a'],
        'pending'  => ['Pending',  '#f4be42'],
        'invisible'=> ['Invisible','#6c8cff'],
    ];
    $d = $map[$status] ?? ['Unknown', '#888'];
    return '<span style="display:inline-block;min-width:70px;padding:6px 19px;border-radius:16px;background:'.$d[1].';color:#fff;font-weight:700;font-size:.99rem;text-align:center;">'.$d[0].'</span>';
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Projects | XtremeDev Admin</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php include __DIR__.'/../shared/inc/head-assets.php'; ?>
    <?php include 'includes/admin-styles.php'; ?>
    <style>
        :root {
            --primary: #38a8ff;
            --surface: #181f27;
            --surface-alt: #232d3b;
            --text: #e6e9f2;
            --shadow-card: #38a8ff0c;
            --border: #38a8ff22;
        }
        body {
            background: var(--surface, #181f27) !important;
            color: var(--text, #e6e9f2) !important;
            font-family: Vazirmatn, Tahoma, Arial, sans-serif;
            min-height: 100vh;
            display: flex; flex-direction: column;
        }
        .container-main { max-width: 1200px; margin:48px auto 0 auto; flex: 1 0 auto; width: 100%; }
        .page-title { font-weight:900; color:var(--primary, #38a8ff); font-size:1.7rem; letter-spacing:.5px; margin-bottom:1.2rem; display: flex; align-items: center; gap: 12px; }
        .role-badge { display:inline-block; padding: 3px 14px; font-size: 0.98rem; border-radius: 9px; color:#fff; font-weight:700; letter-spacing: .3px; vertical-align:middle; margin-left: 8px; box-shadow:0 1px 6px #22292f1a; }
        .search-box { margin-bottom: 24px; margin-top: 4px; display: flex; justify-content: flex-start; gap: 10px; align-items: flex-start; }
        .search-input {
            background: var(--surface-alt, #232d3b);
            color: var(--primary, #38a8ff);
            border: 1.4px solid var(--border, #38a8ff22);
            border-radius: 11px;
            padding: 8px 18px;
            font-size: 1.07rem;
            min-width: 180px;
            max-width: 250px;
            transition: border .15s, background .15s, color .15s;
            box-shadow: 0 2px 8px var(--shadow-card);
        }
        .search-input:focus {
            outline: none;
            border-color: var(--primary, #38a8ff);
            background: #1e2836;
            color: #fff;
        }
        .search-btn {
            background: var(--primary, #38a8ff);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 8px 24px;
            font-weight: 700;
            font-size:1.02rem;
            transition: background .15s;
        }
        .search-btn:hover { background: #2499fa; color: #fff; }
        .clear-btn {
            background: #29364b;
            color: #aad3ff;
            border: none;
            border-radius: 10px;
            padding: 8px 16px;
            font-weight: 600;
            font-size:1.02rem;
            transition: background .15s;
            margin-left: 2px;
        }
        .clear-btn:hover { background: #202942; color: #fff; }
        .projects-table-responsive {
            width: 100%;
            overflow-x: auto;
            margin-bottom: 2.5rem;
            margin-top: 2.5rem;
            border-radius: 17px;
            box-shadow: 0 2px 16px var(--shadow-card);
            background: var(--surface-alt, #232d3b);
        }
        table.projects-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 720px;
            background: transparent;
        }
        table.projects-table th,
        table.projects-table td {
            padding: 1.07rem .88rem;
            text-align: center;
        }
        table.projects-table th {
            background: #1e2836;
            color: var(--primary, #38a8ff);
            font-weight: 900;
            font-size: 1.06rem;
            border-bottom: 2px solid var(--border, #38a8ff22);
        }
        table.projects-table td {
            font-size: 1.02rem;
            color: var(--text, #e6e9f2);
            border-bottom: 1px solid var(--border, #38a8ff22);
            vertical-align: middle;
        }
        .action-btns { display: flex; flex-direction: row; gap: 9px; justify-content: center; align-items: center; }
        .btn-edit {
            background: var(--primary, #38a8ff);
            color: #fff;
            border-radius: 8px;
            font-weight: 700;
            border: none;
            padding: 5px 22px;
            font-size:1.01rem;
            transition: background .15s;
        }
        .btn-edit:hover { background: #2499fa; color: #fff; }
        .btn-delete {
            background: #43597a;
            color: #fff;
            border-radius: 8px;
            font-weight: 700;
            border: none;
            padding: 5px 22px;
            font-size:1.01rem;
            transition: background .15s;
        }
        .btn-delete:hover { background: #2a3343; color: #fff; }
        .btn-add-project {
            background: var(--primary, #38a8ff);
            color: #fff;
            font-weight: 700;
            border-radius: 10px;
            padding: 9px 28px;
            margin-bottom: 20px;
            margin-top: 5px;
            transition: background .2s;
            border: none;
            font-size:1.08rem;
        }
        .btn-add-project:hover { background: #2499fa; color: #fff; }
        .footer-sticky {
            flex-shrink: 0;
            margin-top: auto;
            width: 100%;
            background: #232d3b;
            color: #aad3ff;
            padding: 18px 0 8px 0;
            text-align: center;
            border-top: 1.6px solid #31415a;
            font-size: 0.95rem;
            letter-spacing: .2px;
        }
        @media (max-width: 600px) {
            .container-main {padding:0 2px;}
            .page-title {font-size:1.1rem;}
            .role-badge {font-size:0.78rem;padding:2px 8px;}
            .search-box {flex-wrap:wrap;}
        }
    </style>
</head>
<body>
<?php
switch($role) {
    case 'superadmin': include 'includes/superadmin-navbar.php'; break;
    case 'manager':    include 'includes/manager-navbar.php'; break;
    case 'support':    include 'includes/supporter-navbar.php'; break;
    case 'read_only':  include 'includes/readonly-navbar.php'; break;
    default:           include 'includes/navbar.php';
}
?>
<div class="container-main">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-3">
        <div class="page-title">
            Projects
            <?=role_badge($role)?>
        </div>
        <div>
            <span style="font-size:1rem;color:#b9d5f6;">
                <b><?=htmlspecialchars($username)?></b> (<span style="color:#38a8ff;"><?=htmlspecialchars($email)?></span>)
            </span>
            <a href="logout.php" class="btn btn-sm btn-danger ms-2">Logout</a>
        </div>
    </div>
    <?php if($delete_msg): ?>
        <?=$delete_msg?>
    <?php endif; ?>
    <!-- Search -->
    <form method="get" class="search-box" autocomplete="off">
        <input type="text" name="search" class="search-input" placeholder="Search by ID or project title..." value="<?=htmlspecialchars($search)?>">
        <button type="submit" class="search-btn">Search</button>
        <?php if($search): ?>
            <a href="projects.php" class="clear-btn">Clear</a>
        <?php endif; ?>
    </form>
    <?php if($can_edit): ?>
        <a href="add_project.php" class="btn btn-add-project mb-3">Add New Project</a>
    <?php endif; ?>
    <div class="projects-table-responsive">
        <table class="projects-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Status</th>
                <th>Created</th>
                <?php if($can_edit): ?>
                    <th style="width:130px;">Actions</th>
                <?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach($projects as $p): ?>
                <tr>
                    <td><?=htmlspecialchars($p['id'])?></td>
                    <td><?=htmlspecialchars($p['title'])?></td>
                    <td><?=project_status_badge($p['status'] ?? 'active')?></td>
                    <td><?=htmlspecialchars($p['created_at'] ?? '')?></td>
                    <?php if($can_edit): ?>
                        <td class="action-btns">
                            <a href="edit_project.php?id=<?=$p['id']?>" class="btn-edit">Edit</a>
                            <a href="projects.php?delete=<?=$p['id']?>" class="btn-delete" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if(empty($projects)): ?>
                <tr><td colspan="<?=$can_edit?5:4?>" class="text-center text-muted" style="color:#8ba7c7;font-size:1.15rem;">No projects found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<footer class="footer-sticky">
    &copy; <?=date('Y')?> XtremeDev. All rights reserved.
</footer>
<?php include __DIR__.'/../shared/inc/foot-assets.php'; ?>
</body>
</html>