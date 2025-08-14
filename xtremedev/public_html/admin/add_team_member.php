<?php
session_start();
require_once __DIR__ . '/../shared/inc/config.php';
if (!isset($_SESSION['admin_user_id']) || !isset($_SESSION['admin_access_token'])) {
    header("Location: login.php");
    exit;
}
$lang = 'en';
$is_rtl = false;
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
if(!in_array($role, ['superadmin', 'manager'])) { header("Location: access_denied.php"); exit; }
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Add New Team Member | XtremeDev Admin</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php include __DIR__.'/../shared/inc/head-assets.php'; ?>
    <?php include 'includes/admin-styles.php'; ?>
    <script src="https://cdn.tiny.cloud/1/irnrp1bnwa6iqujrcfr69gp5jzyu5da8dx7xgdtry9d9ppe2/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        body { background: #181f27 !important; color: #e6e9f2 !important; font-family: Vazirmatn, Tahoma, Arial, sans-serif; min-height: 100vh; margin: 0; }
        .container-main { max-width: 1000px; margin:40px auto 0 auto; width: 97vw; }
        .form-card { background: #232d3b; border-radius: 22px; box-shadow: 0 2px 34px #0002; padding: 2.7rem 3.2rem 1.8rem 3.2rem; margin-bottom: 40px; max-width: 820px; margin-left:auto; margin-right:auto;}
        .form-title { font-weight:900; color:#38a8ff; font-size:1.7rem; letter-spacing:.4px; margin-bottom:1.2rem; }
        .form-label { font-weight:700; margin-bottom:4px; color:#3bbcff; display:block; }
        .form-control { background: #232d3b !important; color: #e6e9f2 !important; border: 1.5px solid #31415a; border-radius: 10px; padding: 12px 17px; font-size: 1.09rem; margin-bottom: 13px; transition: border .15s; }
        .form-control:focus { outline: none; border-color: #38a8ff; background: #253040; color: #fff; }
        .form-select { background: #232d3b; color: #e6e9f2; border: 1.5px solid #31415a; border-radius: 10px; padding: 12px 17px; font-size: 1.09rem; }
        .form-select:focus { border-color: #38a8ff; }
        .form-section {
            background: #20253a;
            padding: 22px 22px 16px 22px;
            border-radius: 16px;
            margin-bottom: 26px;
            box-shadow: 0 2px 24px #0002;
            border: 1.7px solid #2b3a4c;
        }
        .btn-main { background: linear-gradient(90deg,#38a8ff,#44e1ff 90%); color: #fff; border-radius: 11px; font-weight: 800; padding: 13px 0; width: 210px; font-size:1.2rem; border:none; margin-top: 18px; margin-bottom: 12px; box-shadow: 0 2px 14px #38a8ff33; transition: background .16s, box-shadow .16s; }
        .btn-main:hover { background: linear-gradient(90deg,#2499fa,#1bc6e8 90%); color: #fff; box-shadow: 0 5px 24px #38a8ff40;}
        .btn-cancel { background:#31415a; color:#aad3ff; border-radius:11px; padding:13px 34px; font-weight:600; font-size:1.07rem; margin-left:24px;margin-top:18px;border:none;transition:background .16s,color .16s;}
        .btn-cancel:hover {background:#202942;color:#fff;}
        .img-preview { display: block; max-width: 110px; max-height: 110px; border-radius: 10px; margin: 12px 0 4px 0; box-shadow: 0 2px 8px #2222; }
        .alert { margin-top: 8px; }
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
        .server-response-block { background:#1c2533;color:#a1e3b6;direction:ltr;font-family:monospace;font-size:.99rem;padding:10px 14px;border-radius:8px;margin:10px 0 0 0;white-space:pre-wrap;overflow-x:auto; }
        .server-response-title {color:#98e7ff;font-weight:700;font-size:1.07rem;margin:18px 0 2px 0;}
        @media (max-width: 1100px) { .container-main{max-width:98vw;} .form-card{padding:2.1rem .7rem 1.1rem .7rem;} }
        @media (max-width: 700px) { .container-main {padding:0 2px;} .form-title {font-size:1.15rem;} .form-card {padding: 1.1rem 0.2rem;} .btn-main,.btn-cancel{width:100%;margin-left:0;} }
        .footer-sticky {
            flex-shrink: 0;
            margin-top: 50px;
            width: 100%;
            background: linear-gradient(90deg, #232d3b 40%, #273c54 100%);
            color: #aad3ff;
            padding: 20px 0 9px 0;
            text-align: center;
            border-top: 2.5px solid #31415a;
            font-size: 1.08rem;
            letter-spacing: .18px;
            box-shadow: 0 -2px 24px #38a8ff11;
            position: relative;
        }
        .footer-sticky .footer-inner {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
        }
        .footer-sticky svg {
            display: inline-block;
            width: 22px;
            height: 22px;
            fill: #38a8ff;
            margin-bottom: -4px;
            margin-right: 2px;
        }
        .footer-sticky a {
            color: #aad3ff;
            text-decoration: underline dotted;
            transition: color .13s;
        }
        .footer-sticky a:hover {
            color: #fff;
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
    <div class="form-card">
        <div class="form-title">Add New Team Member</div>
        <form id="add-member-form" autocomplete="off">
            <div id="form-fields"></div>
            <div style="display:flex;flex-wrap:wrap;gap:0;">
                <button type="submit" id="save-btn" class="btn-main">Add Member</button>
                <a href="team.php" class="btn-cancel">Cancel</a>
                <span id="form-loader" style="display:none;margin-left:22px;"><span class="spinner-border spinner-border-sm"></span> Loading...</span>
            </div>
            <div id="form-msg"></div>
        </form>
        <div id="server-response-block"></div>
    </div>
</div>
<footer class="footer-sticky">
    <div class="footer-inner">
        <svg viewBox="0 0 24 24">
            <path d="M12 2C6.477 2 2 6.477 2 12c0 5.523 4.477 10 10 10s10-4.477 10-10c0-5.523-4.477-10-10-10zm-.25 4.7c.67 0 1.25.58 1.25 1.3s-.58 1.3-1.25 1.3-1.25-.58-1.25-1.3.58-1.3 1.25-1.3zm2.46 12.29c-.19.07-.4.11-.61.11-.23 0-.45-.04-.65-.13l-1.21-.54c-.2-.09-.32-.31-.32-.58V10.6c0-.33.26-.6.59-.6h.01c.32 0 .59.27.59.6v5.53l.82.36c.31.14.44.5.29.8-.07.15-.19.26-.32.31z"/>
        </svg>
        <span>
            &copy; <?=date('Y')?> <a href="https://xtremedev.co" target="_blank" rel="noopener">XtremeDev</a>. All rights reserved.
        </span>
    </div>
</footer>
<?php include __DIR__.'/../shared/inc/foot-assets.php'; ?>
<script>
const allowedLangs = <?=json_encode($allowed_langs)?>;
const langNames = <?=json_encode($lang_names)?>;
const access_token = <?=json_encode($_SESSION['admin_access_token'])?>;

async function loadRoles() {
    document.getElementById('form-fields').innerHTML = '<div style="margin:2rem 0;text-align:center;color:#8af">Loading...</div>';
    let roles = [];
    try {
        let rresp = await fetch('/api/endpoints/roles_list.php?lang=en', {
            headers: { 'Authorization': 'Bearer '+access_token }
        });
        roles = await rresp.json();
        if (!Array.isArray(roles)) roles = [];
    } catch(e) {
        document.getElementById('form-fields').innerHTML = '<div style="color:#e13a3a;text-align:center;margin:2rem 0">An error occurred. Please try again.</div>';
        return;
    }
    let html = `
    <div class="form-section">
        <label class="form-label required" for="photo">Photo URL</label>
        <input type="url" class="form-control" id="photo" name="photo" placeholder="https://...">
        <div id="img-preview-wrap"></div>
    </div>
    <div class="form-section">
        <label class="form-label required" for="role_id">Role</label>
        <select class="form-control" id="role_id" name="role_id" required>
            <option value="">---</option>
            ${roles.map(r => `<option value="${r.id}">${r.name}</option>`).join('')}
        </select>
    </div>
    <div class="form-section">
        <label class="form-label" for="priority">Priority</label>
        <input type="number" class="form-control" id="priority" name="priority" value="1" min="0">
    </div>
    `;
    for (let lng of allowedLangs) {
        html += `
        <div class="form-section">
            <div class="lang-label">Language: <b>${langNames[lng]||lng.toUpperCase()}</b></div>
            <label class="form-label required" for="name_${lng}">Name</label>
            <input type="text" class="form-control" name="translations[${lng}][name]" id="name_${lng}" maxlength="128" required placeholder="Name (${lng})">
            
            <label class="form-label" for="skill_${lng}">Skill</label>
            <input type="text" class="form-control" name="translations[${lng}][skill]" id="skill_${lng}" maxlength="128" placeholder="Skill (${lng})">
            
            <label class="form-label" for="subrole_${lng}">Sub-role</label>
            <input type="text" class="form-control" name="translations[${lng}][sub_role]" id="subrole_${lng}" maxlength="128" placeholder="Sub-role (${lng})">
            
            <label class="form-label" for="bio_${lng}">Biography</label>
            <textarea class="form-control" id="bio_${lng}" name="translations[${lng}][bio]" placeholder="Biography (${lng})"></textarea>
        </div>
        `;
    }
    document.getElementById('form-fields').innerHTML = html;

    document.getElementById('photo').addEventListener('input',function(){
        let url = this.value.trim();
        let wrap = document.getElementById('img-preview-wrap');
        if(url) wrap.innerHTML = '<img class="img-preview" src="'+url.replace(/"/g,'&quot;')+'">';
        else wrap.innerHTML = '';
    });

    // TinyMCE for all bio fields
    for (let lng of allowedLangs) {
        tinymce.init({
            selector: `#bio_${lng}`,
            height: 210,
            directionality: ['ar','fa'].includes(lng)?'rtl':'ltr',
            menubar: false,
            plugins: 'lists link autolink table code emoticons directionality',
            toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist | link emoticons | code',
            skin: 'oxide-dark',
            content_css: 'dark',
            language: (lng=='fa'||lng=='ar'?lng:'en'),
            branding: false
        });
    }
}
loadRoles();

document.getElementById('add-member-form').onsubmit = async function(e){
    e.preventDefault();
    document.getElementById('form-loader').style.display = '';
    document.getElementById('form-msg').innerHTML = '';
    document.getElementById('server-response-block').innerHTML = '';
    if (window.tinymce) tinymce.triggerSave();

    let fd = new FormData(this);
    let obj = {};
    fd.forEach((v,k)=>{
        if(k.endsWith(']')) {
            let match = k.match(/^translations\[(\w+)\]\[(\w+)\]$/);
            if(match){
                let [_, lng, key] = match;
                obj.translations = obj.translations || {};
                obj.translations[lng] = obj.translations[lng] || {};
                obj.translations[lng][key] = v;
            }
        } else {
            obj[k] = v;
        }
    });

    // Validation
    let errorMsg = '';
    for(let lng of allowedLangs) {
        if(!obj.translations?.[lng]?.name) {
            errorMsg = `Name is required for ${langNames[lng]||lng.toUpperCase()}`;
            break;
        }
    }
    if(!obj.role_id) errorMsg = "Role is required.";
    if(errorMsg) {
        document.getElementById('form-loader').style.display = 'none';
        document.getElementById('form-msg').innerHTML = `<div class="alert alert-danger">${errorMsg}</div>`;
        return;
    }

    // Prepare payload
    let sendData = {
        role_id: obj.role_id,
        priority: obj.priority||1,
        photo: obj.photo||"",
        allowed_langs: allowedLangs,
        translations: obj.translations || {}
    };

    try {
        let resp = await fetch('/api/endpoints/admin/team_add.php', {
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
        document.getElementById('form-loader').style.display = 'none';

        if(resp.status===200 && json && json.success){
            document.getElementById('form-msg').innerHTML = '<div class="alert alert-success">Member added successfully.</div>';
            setTimeout(()=>window.location.href='team.php',1800);
        } else {
            document.getElementById('form-msg').innerHTML = '<div class="alert alert-danger">'+(json && json.error ? json.error : "An error occurred. Please try again.")+'</div>';
        }
        document.getElementById('server-response-block').innerHTML =
            '<div class="server-response-title">Server Response:</div>' +
            '<div class="server-response-block">'+
            (text ? text.replace(/</g,'&lt;') : '-') +
            '</div>';
    } catch(e){
        document.getElementById('form-loader').style.display = 'none';
        document.getElementById('form-msg').innerHTML = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
        document.getElementById('server-response-block').innerHTML =
            '<div class="server-response-title">Server Response:</div>' +
            '<div class="server-response-block">'+(e && e.toString ? e.toString() : "ERROR")+'</div>';
    }
}
</script>
</body>
</html>