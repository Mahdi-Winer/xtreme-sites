<?php
session_start();
if (!isset($_SESSION['admin_user_id']) || !isset($_SESSION['admin_access_token'])) {
    header("Location: login.php");
    exit;
}
$role = $_SESSION['admin_role'] ?? '';
$can_edit = ($role === 'superadmin' || $role === 'manager');
if(!$can_edit) { header("Location: access_denied.php"); exit; }
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Team Roles | XtremeDev Admin</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php include __DIR__.'/../shared/inc/head-assets.php'; ?>
    <?php include 'includes/admin-styles.php'; ?>
    <style>
        body { background: #181f27 !important; color: #e6e9f2 !important; font-family: Vazirmatn, Tahoma, Arial, sans-serif; min-height: 100vh; margin: 0; }
        .container-main { max-width: 1100px; margin:48px auto 0 auto; width: 100%; }
        .card { background: #222b3b; border-radius: 18px; box-shadow: 0 2px 22px #0003; padding: 42px 44px 32px 44px; margin-bottom: 48px; }
        .card-title { font-weight:900; color:#38a8ff; font-size:2rem; letter-spacing:.4px; margin-bottom:1.7rem; }
        .role-table-responsive {
            width: 100%;
            overflow-x: auto;
            margin: 0 auto 24px auto;
            border-radius: 15px;
            background: #232d3b;
            box-shadow:0 2px 16px #38a8ff0c;
        }
        .role-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 760px;
            background: transparent;
        }
        .role-table th, .role-table td {
            padding: 1.13rem .93rem;
            border-bottom: 1px solid #31415a;
            text-align: center;
        }
        .role-table th {
            color:#38a8ff;
            font-weight:900;
            background:#1e2836;
            font-size:1.09rem;
            border-bottom:2px solid #38a8ff22;
        }
        .role-table td {
            color:#e6e9f2;
            font-size:1.03rem;
        }
        .btn-main {
            background: #38a8ff;
            color: #fff;
            border-radius: 8px;
            font-weight: 700;
            padding: 9px 20px;
            border: none;
            font-size:1.01rem;
            margin-left:4px;
            transition: background .13s;
        }
        .btn-main:hover { background: #2499fa; color: #fff; }
        .btn-xs { font-size: .93rem; padding: 6px 15px; }
        .btn-danger { background: #e13a3a; color: #fff; }
        .btn-danger:hover { background: #c81313; }
        .modal { display:none; position:fixed; z-index:99; left:0;top:0;width:100vw;height:100vh;background:#000a;align-items:center;justify-content:center; }
        .modal-content { background:#232d3b;padding:38px 22px 22px 22px;border-radius:15px;min-width:340px;max-width:96vw;box-shadow:0 2px 32px #000c;}
        .modal-title { font-weight:700;color:#38a8ff;margin-bottom:13px;font-size:1.24rem;}
        .form-label { font-weight:700; margin-bottom:4px; color:#aad3ff; }
        .form-control { background: #232d3b !important; color: #e6e9f2 !important; border: 1.2px solid #31415a; border-radius: 8px; padding: 9px 16px; font-size: 1.09rem; transition: border .15s; }
        .form-control:focus { outline: none; border-color: #38a8ff; background: #253040; }
        .alert { margin-top: 8px; }
        @media (max-width: 900px) {
            .container-main {max-width:99vw;}
            .card {padding:18px 4vw 18px 4vw;}
            .role-table {min-width:620px;}
        }
        @media (max-width: 650px) {
            .container-main {padding:0 1vw;}
            .card {padding:10px 1vw;}
            .modal-content {padding:12px 2vw;}
            .role-table th,.role-table td {padding:11px 2vw;}
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
    <div class="card">
        <div class="card-title">Team Roles</div>
        <div class="mb-3">
            <button class="btn btn-main" onclick="showAddModal()">Add Role</button>
        </div>
        <div class="role-table-responsive">
            <div id="roles-table-block"></div>
        </div>
        <div id="form-msg"></div>
    </div>
</div>

<!-- Modal for add/edit -->
<div id="role-modal" class="modal" tabindex="-1">
    <div class="modal-content">
        <div class="modal-title" id="modal-title"></div>
        <form id="role-form" autocomplete="off">
            <input type="hidden" id="role_id" name="role_id">
            <div class="form-row">
                <label class="form-label" for="name_fa">Role Name (Persian) <span style="color:#e13a3a;">*</span></label>
                <input type="text" class="form-control" id="name_fa" name="name_fa" required>
            </div>
            <div class="form-row">
                <label class="form-label" for="name_en">Role Name (English) <span style="color:#e13a3a;">*</span></label>
                <input type="text" class="form-control" id="name_en" name="name_en" required>
            </div>
            <div class="form-row">
                <label class="form-label" for="role_sort">Sort Order</label>
                <input type="number" class="form-control" id="role_sort" name="role_sort" min="0" value="1">
            </div>
            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-main" id="role-save-btn">Save</button>
                <button type="button" class="btn btn-cancel" onclick="hideModal()">Cancel</button>
            </div>
            <div id="modal-form-msg"></div>
        </form>
    </div>
</div>

<footer class="footer-sticky" style="width:100%;background:#232d3b;color:#aad3ff;padding:18px 0 8px 0;text-align:center;border-top:1.6px solid #31415a;font-size:0.95rem;letter-spacing:.2px;">
    &copy; <?=date('Y')?> XtremeDev. All rights reserved.
</footer>
<?php include __DIR__.'/../shared/inc/foot-assets.php'; ?>
<script>
var access_token = <?=json_encode($_SESSION['admin_access_token'])?>;
let rolesList = [];

function showAddModal() {
    document.getElementById('modal-title').textContent = "Add Role";
    document.getElementById('role_id').value = '';
    document.getElementById('name_fa').value = '';
    document.getElementById('name_en').value = '';
    document.getElementById('role_sort').value = 1;
    document.getElementById('modal-form-msg').innerHTML = '';
    document.getElementById('role-modal').style.display = 'flex';
}
function showEditModal(role) {
    document.getElementById('modal-title').textContent = "Edit Role";
    document.getElementById('role_id').value = role.id;
    document.getElementById('name_fa').value = role.name_fa;
    document.getElementById('name_en').value = role.name_en;
    document.getElementById('role_sort').value = role.sort_order || 1;
    document.getElementById('modal-form-msg').innerHTML = '';
    document.getElementById('role-modal').style.display = 'flex';
}
function hideModal() {
    document.getElementById('role-modal').style.display = 'none';
}

async function loadRoles() {
    document.getElementById('roles-table-block').innerHTML = '<div style="margin:2rem 0;text-align:center;color:#8af">Loading...</div>';
    try {
        let resp = await fetch('/api/endpoints/roles_list.php?lang=fa', {
            headers: { 'Authorization': 'Bearer '+access_token }
        });
        let roles_fa = await resp.json();

        let resp2 = await fetch('/api/endpoints/roles_list.php?lang=en', {
            headers: { 'Authorization': 'Bearer '+access_token }
        });
        let roles_en = await resp2.json();

        rolesList = roles_fa.map(rfa => {
            let en = roles_en.find(re => re.id == rfa.id) || {};
            return {
                id: rfa.id,
                name_fa: rfa.name,
                name_en: en.name || '',
                sort_order: rfa.sort_order !== undefined ? rfa.sort_order : 1
            };
        });

        if(!rolesList.length) {
            document.getElementById('roles-table-block').innerHTML = '<div style="text-align:center;color:#8af;">No roles found.</div>';
            return;
        }

        let html = `<table class="role-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Role Name (Persian)</th>
                    <th>Role Name (English)</th>
                    <th>Sort Order</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
        `;
        rolesList.forEach((role, idx) => {
            html += `<tr>
                <td>${idx+1}</td>
                <td>${role.name_fa}</td>
                <td>${role.name_en}</td>
                <td>${role.sort_order||1}</td>
                <td>
                    <button class="btn btn-main btn-xs" onclick='showEditModal(${JSON.stringify(role)})'>Edit Role</button>
                    <button class="btn btn-danger btn-xs" onclick='deleteRole(${role.id})'>Delete Role</button>
                </td>
            </tr>`;
        });
        html += '</tbody></table>';
        document.getElementById('roles-table-block').innerHTML = html;
    } catch(e) {
        document.getElementById('roles-table-block').innerHTML = '<div style="color:#e13a3a;text-align:center;margin:2rem 0">An error occurred. Please try again.</div>';
    }
}
loadRoles();

document.getElementById('role-form').onsubmit = async function(e){
    e.preventDefault();
    let id_raw = document.getElementById('role_id').value;
    let id = id_raw ? parseInt(id_raw, 10) : null;
    let name_fa = document.getElementById('name_fa').value.trim();
    let name_en = document.getElementById('name_en').value.trim();
    let sort_order = document.getElementById('role_sort').value ? parseInt(document.getElementById('role_sort').value, 10) : 1;
    if (!name_fa || !name_en) {
        document.getElementById('modal-form-msg').innerHTML = '<div class="alert alert-danger">Required fields!</div>';
        return;
    }
    let api, sendData;
    if(id) {
        api = '/api/endpoints/admin/roles_edit.php';
        sendData = {
            id: id,
            sort_order: sort_order,
            translations: {
                fa: { name: name_fa },
                en: { name: name_en }
            }
        };
    } else {
        api = '/api/endpoints/admin/roles_add.php';
        sendData = {
            sort_order: sort_order,
            translations: {
                fa: { name: name_fa },
                en: { name: name_en }
            }
        };
    }

    try {
        let resp = await fetch(api, {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer '+access_token,
                'Content-Type':'application/json'
            },
            body: JSON.stringify(sendData)
        });
        let text = await resp.text();
        let json;
        try { json = JSON.parse(text); } catch(e){ json = null; }
        if(resp.status===200 && json && json.success){
            document.getElementById('modal-form-msg').innerHTML = '<div class="alert alert-success">Operation successful.</div>';
            setTimeout(()=>{
                hideModal();
                loadRoles();
            }, 1200);
        } else {
            document.getElementById('modal-form-msg').innerHTML = '<div class="alert alert-danger">'+(json && json.error ? json.error : "An error occurred. Please try again.")+'</div>';
        }
    } catch(e){
        document.getElementById('modal-form-msg').innerHTML = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
    }
}

// Delete role
async function deleteRole(id) {
    if(!confirm("Are you sure you want to delete this role?")) return;
    try {
        let resp = await fetch('/api/endpoints/admin/roles_delete.php', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer '+access_token,
                'Content-Type':'application/json'
            },
            body: JSON.stringify({id:id})
        });
        let text = await resp.text();
        let json;
        try { json = JSON.parse(text); } catch(e){ json = null; }
        if(resp.status===200 && json && json.success){
            loadRoles();
        } else {
            alert(json && json.error ? json.error : "An error occurred. Please try again.");
        }
    } catch(e){
        alert("An error occurred. Please try again.");
    }
}
window.onclick = function(e){
    if(e.target && e.target.id==='role-modal') hideModal();
}
</script>
</body>
</html>