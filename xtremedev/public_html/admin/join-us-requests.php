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
$delete_msg = '';
if ($can_edit && isset($_GET['delete']) && intval($_GET['delete']) > 0) {
    $request_id = intval($_GET['delete']);
    $api_url = 'https://api.xtremedev.co/endpoints/admin/joinus_requests.php';
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer ".$_SESSION['admin_access_token'],
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['action' => 'delete', 'id' => $request_id]));
    $resp = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode == 200) {
        $delete_msg = '<div class="alert alert-success">Request deleted successfully.</div>';
    } else {
        $delete_msg = '<div class="alert alert-danger">Delete failed or request not found.</div>';
    }
}

$api_url = 'https://api.xtremedev.co/endpoints/admin/joinus_requests.php';
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

$requests = [];
if ($httpcode === 200 && $resp) {
    $data = json_decode($resp, true);
    if (is_array($data)) $requests = $data;
} else {
    $requests = [];
}

function joinus_status_badge($status) {
    $status = strtolower($status);
    $map = [
        'pending'      => ['Pending',      '#ffa500'],
        'under_review' => ['Under Review', '#2499fa'],
        'accepted'     => ['Accepted',     '#2bc551'],
        'rejected'     => ['Rejected',     '#e33'],
    ];
    $d = $map[$status] ?? ['Unknown', '#888'];
    return '<span style="display:inline-block;min-width:70px;padding:6px 19px;border-radius:16px;background:'.$d[1].';color:#fff;font-weight:700;font-size:.99rem;text-align:center;">'.$d[0].'</span>';
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
$status_enum = ['pending','under_review','accepted','rejected'];
$status_labels = [
    'pending'      => 'Pending',
    'under_review' => 'Under Review',
    'accepted'     => 'Accepted',
    'rejected'     => 'Rejected',
];
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>JoinUs Requests | XtremeDev Admin</title>
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
        .joinus-table-responsive {
            width: 100%;
            overflow-x: auto;
            margin-bottom: 2.5rem;
            margin-top: 2.5rem;
            border-radius: 17px;
            box-shadow: 0 2px 16px var(--shadow-card);
            background: var(--surface-alt, #232d3b);
        }
        table.joinus-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1100px;
            background: transparent;
        }
        table.joinus-table th,
        table.joinus-table td {
            padding: 1.07rem .88rem;
            text-align: center;
        }
        table.joinus-table th {
            background: #1e2836;
            color: var(--primary, #38a8ff);
            font-weight: 900;
            font-size: 1.06rem;
            border-bottom: 2px solid var(--border, #38a8ff22);
        }
        table.joinus-table td {
            font-size: 1.02rem;
            color: var(--text, #e6e9f2);
            border-bottom: 1px solid var(--border, #38a8ff22);
            vertical-align: middle;
        }
        .action-btns { display: flex; flex-direction: row; gap: 9px; justify-content: center; align-items: center; }
        .btn-view {
            background: var(--primary, #38a8ff);
            color: #fff;
            border-radius: 8px;
            font-weight: 700;
            border: none;
            padding: 5px 22px;
            font-size:1.01rem;
            transition: background .15s;
            text-decoration: none;
        }
        .btn-view:hover { background: #2499fa; color: #fff; text-decoration: none; }
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
        .btn-manage {
            background:#6c8cff; color:#fff; border-radius:8px; font-weight:700; border:none; padding:5px 18px; transition:background .15s;
        }
        .btn-manage:hover { background:#384bff; }
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
        #manageJoinusModal { display:none; position:fixed; left:0; top:0; width:100vw; height:100vh; background:#111a; z-index:9999; justify-content:center; align-items:center; }
        #manageJoinusModal .modal-inner { background:#232d3b; border-radius:13px; padding:30px 20px 16px 20px; min-width:320px; max-width:95vw; box-shadow:0 8px 32px #0004; position:relative; }
        #manageJoinusModal label { font-weight:700; color:#aad3ff; margin-bottom:6px; display:block; }
        #manageJoinusModal select, #manageJoinusModal textarea { width:100%; margin-bottom:12px; border-radius:7px; background:#181f27; color:#fff; border:1px solid #31415a; padding:7px 10px; }
        #manageJoinusModal textarea { min-height:60px; resize:vertical; }
        #manageJoinusModal .close-btn { position:absolute; right:12px; top:12px; font-size:1.4rem; background:none; color:#fff; border:none; cursor:pointer; }
        #manageJoinusModal .modal-save-btn { background:#2bc551; color:#fff; border:none; border-radius:9px; font-weight:700; padding:7px 22px; margin-top:10px; }
        #manageJoinusModal .modal-save-btn:disabled { background:#666; }
        #manageJoinusModal .modal-msg { margin:7px 0 0 0; font-size:.99rem; }
        @media (max-width: 600px) { .container-main {padding:0 2px;} .page-title {font-size:1.1rem;} .role-badge {font-size:0.78rem;padding:2px 8px;} .search-box {flex-wrap:wrap;} }
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
            JoinUs Requests
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
    <form method="get" class="search-box" autocomplete="off">
        <input type="text" name="search" class="search-input" placeholder="Search by ID, name or email..." value="<?=htmlspecialchars($search)?>">
        <button type="submit" class="search-btn">Search</button>
        <?php if($search): ?>
            <a href="admin_joinus.php" class="clear-btn">Clear</a>
        <?php endif; ?>
    </form>
    <div class="joinus-table-responsive">
        <table class="joinus-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Project</th>
                <th>Role</th>
                <th>Skills</th>
                <th>Status</th>
                <th>Created</th>
                <th style="width:150px;">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($requests as $r): ?>
                <tr>
                    <td><?=htmlspecialchars($r['id'])?></td>
                    <td><?=htmlspecialchars($r['fullname'])?></td>
                    <td><?=htmlspecialchars($r['email'])?></td>
                    <td><?=htmlspecialchars($r['project_title'])?></td>
                    <td><?=htmlspecialchars($r['role_title'])?></td>
                    <td><?=htmlspecialchars($r['skills'])?></td>
                    <td><?=joinus_status_badge($r['status'])?></td>
                    <td><?=htmlspecialchars($r['created_at'])?></td>
                    <td class="action-btns">
                        <button type="button" class="btn-manage" data-id="<?=htmlspecialchars($r['id'])?>">Manage</button>
                        <a href="admin_joinus_view.php?id=<?=$r['id']?>" class="btn-view">View</a>
                        <?php if($can_edit): ?>
                            <a href="admin_joinus.php?delete=<?=$r['id']?>" class="btn-delete" onclick="return confirm('Are you sure?')">Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if(empty($requests)): ?>
                <tr><td colspan="9" class="text-center text-muted" style="color:#8ba7c7;font-size:1.15rem;">No JoinUs requests found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<footer class="footer-sticky">
    &copy; <?=date('Y')?> XtremeDev. All rights reserved.
</footer>
<?php include __DIR__.'/../shared/inc/foot-assets.php'; ?>

<!-- Manage Modal -->
<div id="manageJoinusModal">
    <div class="modal-inner">
        <button type="button" class="close-btn" id="manageCloseBtn">&times;</button>
        <div id="manageJoinusContent" style="min-width:260px;max-width:400px;"></div>
    </div>
</div>
<script>
const apiUrlGet = "https://api.xtremedev.co/endpoints/admin/joinus_requests.php";
const apiUrlPost = "https://api.xtremedev.co/endpoints/admin/joinus_request_update.php";
const modal = document.getElementById('manageJoinusModal');
const modalContent = document.getElementById('manageJoinusContent');
const statusEnum = <?=json_encode($status_enum)?>;
const statusLabels = <?=json_encode($status_labels)?>;

document.querySelectorAll('.btn-manage').forEach(el => {
    el.onclick = function() {
        modalContent.innerHTML = `<div style="text-align:center;padding:45px 0;color:#6c8cff">Loading ...</div>`;
        modal.style.display = 'flex';
        fetch(apiUrlGet + '?id=' + encodeURIComponent(this.dataset.id) + '&lang=en', {
            headers: {'Authorization':'Bearer <?=addslashes($_SESSION['admin_access_token'])?>'}
        })
        .then(res => res.text().then(text => ({status: res.status, text})))
        .then(({status, text}) => {
            let row = null;
            try { row = JSON.parse(text); } catch(e) {}
            if (row && row.id) {
                let statusOptions = '';
                for(const st of statusEnum) statusOptions += `<option value="${st}">${statusLabels[st]}</option>`;
                modalContent.innerHTML = `
                    <div style="font-size:1.17rem;color:#38a8ff;font-weight:900;margin-bottom:11px;">
                        Manage #${row.id}
                    </div>
                    <div style="margin-bottom:6px"><b>Full Name:</b> ${row.fullname}</div>
                    <div style="margin-bottom:6px"><b>Email:</b> ${row.email}</div>
                    <div style="margin-bottom:6px"><b>Project:</b> ${(row.project_title||'')}</div>
                    <div style="margin-bottom:6px"><b>Role:</b> ${(row.role_title||'')}</div>
                    <div style="margin-bottom:6px"><b>Skills:</b> ${(row.skills||'')}</div>
                    <div style="margin-bottom:6px"><b>Description:</b> ${(row.desc||'')}</div>
                    <div style="margin-bottom:6px"><b>Resume:</b> ${row.cv_file ? `<a href="${row.cv_file}" target="_blank" style="color:#2bc551">Download</a>` : '<span style="color:#666">Not found</span>'}</div>
                    <form id="manageJoinusForm">
                        <label for="statusSel">Status:</label>
                        <select id="statusSel" name="status" required>${statusOptions}</select>
                        <label for="adminNote">Admin Note:</label>
                        <textarea id="adminNote" name="admin_note" maxlength="500" placeholder="Admin Note ...">${row.admin_note ? row.admin_note : ''}</textarea>
                        <button type="submit" class="modal-save-btn">Save</button>
                        <div class="modal-msg" id="modalMsg"></div>
                    </form>
                `;
                document.getElementById('statusSel').value = row.status;
                document.getElementById('manageJoinusForm').onsubmit = function(e){
                    e.preventDefault();
                    document.querySelector('.modal-save-btn').disabled = true;
                    const status = document.getElementById('statusSel').value;
                    const note = document.getElementById('adminNote').value;
                    fetch(apiUrlPost, {
                        method: 'POST',
                        headers: {
                            'Authorization':'Bearer <?=addslashes($_SESSION['admin_access_token'])?>',
                            'Content-Type':'application/json'
                        },
                        body: JSON.stringify({
                            id: row.id,
                            status: status,
                            admin_note: note
                        })
                    })
                    .then(r=>r.text())
                    .then(raw=>{
                        let d;
                        try{ d=JSON.parse(raw); }catch(e){ d=null; }
                        if(d && d.success){
                            document.getElementById('modalMsg').innerHTML = '<span style="color:#2bc551">Update successful.</span>';
                            setTimeout(()=>{ modal.style.display='none'; location.reload(); },1000);
                        } else {
                            document.getElementById('modalMsg').innerHTML = '<span style="color:#e33">Failed to save changes!</span>';
                        }
                        document.querySelector('.modal-save-btn').disabled = false;
                    })
                    .catch((e)=>{
                        document.getElementById('modalMsg').innerHTML = '<span style="color:#e33">Server connection error!</span>';
                        document.querySelector('.modal-save-btn').disabled = false;
                    });
                };
            } else {
                modalContent.innerHTML = '<div style="color:#e33">Not found</div>';
            }
        }).catch((e)=>{
            modalContent.innerHTML = `<div style="color:#e33">Server connection error!</div>`;
        });
    }
});
document.getElementById('manageCloseBtn').onclick = ()=>{ modal.style.display='none'; };
window.onclick = function(ev) { if(ev.target===modal) modal.style.display='none'; }
</script>
</body>
</html>