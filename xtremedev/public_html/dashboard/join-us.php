<?php
session_start();
require_once __DIR__.'/../shared/inc/config.php';

// زبان
$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$is_rtl = ($lang === 'fa');
$darkThemeActive = (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark');

// ترجمه از فایل
$translations = [];
$lang_file = __DIR__ . '/../shared/assets/languages/' . $lang . '.json';
if (file_exists($lang_file)) {
    $json = file_get_contents($lang_file);
    $translations = json_decode($json, true);
}

// ------ SSO ------
if (!isset($_SESSION['user_profile'])) {
    $client_id = 'xtremedev-web';
    $redirect_uri = 'https://xtremedev.co/oauth-callback.php';
    $state = bin2hex(random_bytes(8));

    // theme را هم از کوکی بگیر و اگر نبود پیش‌فرض light
    $theme = isset($_COOKIE['theme']) && in_array($_COOKIE['theme'], ['light','dark']) ? $_COOKIE['theme'] : 'light';

    $login_url = "https://auth.xtremedev.co/authorize.php?client_id=$client_id"
        . "&redirect_uri=" . urlencode($redirect_uri)
        . "&response_type=code"
        . "&scope=basic"
        . "&state=$state"
        . "&lang=" . urlencode($lang)
        . "&theme=" . urlencode($theme); // تم را هم اضافه کردیم

    header("Location: $login_url");
    exit;
}

$access_token = $_SESSION['access_token'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?=$lang?>" dir="<?=$is_rtl?'rtl':'ltr'?>">
<head>
    <meta charset="UTF-8">
    <title><?= $translations['joinus_title'] ?? '' ?> | Xtremedev</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include __DIR__.'/../shared/inc/head-assets.php'; ?>
    <?php include __DIR__.'/includes/dashboard-styles.php'; ?>
    <style>
        :root { --primary: #2499fa; --primary-a: rgba(36,153,250,0.97); --surface: #f4f7fa; --surface-alt: #fff; --text: #222; --shadow: #2499fa22; --shadow-card: #2499fa14; --shadow-hover: #2499fa33; --border: #2499fa18; --border-hover: #2499fa44; --footer-bg: #2499fa; --footer-text: #f4f7fa; --footer-text-strong: #fff; --footer-border: #dbe6f7; }
        body.dark-theme { --surface: #181f2a; --surface-alt: #202b3b; --text: #e6e9f2; --shadow: #15203244; --shadow-card: #15203222; --shadow-hover: #2499fa44; --border: #2499fa28; --border-hover: #2499fa66; --footer-bg: #101722; --footer-text: #e9ecf3; --footer-text-strong: #fff; --footer-border: #37425b; }
        body { font-family: 'Vazirmatn', Tahoma, Arial, sans-serif; background: var(--surface); color: var(--text); min-height: 100vh; transition: background 0.4s, color 0.4s; display: flex; flex-direction: column; }
        .main-content { flex: 1 0 auto; }
        .joinus-section { background: var(--surface-alt, #fff); border-radius: 22px; box-shadow: 0 6px 32px var(--shadow-card, #2499fa22); max-width: 700px; margin: 3rem auto 2.5rem auto; padding: 2.5rem 2.2rem 2rem 2.2rem; }
        .joinus-title { font-size: 2.1rem; font-weight: 900; background: linear-gradient(90deg, #2499fa 15%, #3ed2f0 85%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; text-fill-color: transparent; margin-bottom: 1.2rem; letter-spacing: 0.7px; text-align: center !important; }
        .joinus-desc { color: #444; margin-bottom: 1.5rem; font-size: 1.09rem; line-height: 2.2; text-align: justify; }
        .joinus-block h3 { color: #2499fa; font-weight: 800; font-size: 1.13rem; margin-bottom: 0.7rem; }
        .joinus-block ul, .joinus-block ol { padding-right: 1.3rem; padding-left: 0; margin-bottom: 0.7rem; }
        .joinus-block li { margin-bottom: 0.4rem; font-size: 1.01rem; }
        .joinus-form-btn { background: #2499fa; color: #fff; font-weight: 800; border-radius: 12px; min-width: 180px; font-size: 1.13rem; padding: 0.6rem 2.2rem; border: 0; margin: 2.1rem auto 0 auto; display: block; transition: background 0.2s; }
        .joinus-form-btn:hover, .joinus-form-btn:focus { background: #38a8ff; color: #fff; }
        .modal-content { border-radius: 18px; background: var(--surface-alt, #fff); color: var(--text); }
        .form-label { color: #2499fa; font-weight: 700;}
        .form-control, .form-select { border-radius: 10px; font-size: 1rem; border: 1.5px solid #dbe6f7; margin-bottom: 1rem; transition: border 0.2s; }
        .form-control:focus, .form-select:focus { border-color: #38a8ff; box-shadow: 0 0 3px #38a8ff22; }
        .modal-footer { border-top: 0; }
        .role-desc-alert { background: #f4f7fa; border-radius: 10px; padding: 0.85rem 1rem; color: #2086d9; font-size: 0.99rem; margin-bottom: 1.1rem; border: 1.3px dashed #bfe6ff; display: none; }
        body.dark-theme .joinus-section, body.dark-theme .modal-content { background: var(--surface-alt) !important; color: var(--text) !important; box-shadow: 0 6px 32px #0d111c88; }
        body.dark-theme .joinus-title { background: linear-gradient(90deg, #38a8ff 15%, #7ef6d6 85%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; text-fill-color: transparent; }
        body.dark-theme .joinus-block h3, body.dark-theme .form-label { color: #38a8ff; }
        body.dark-theme .form-control, body.dark-theme .form-select { background: #111a27; color: #e6e9f2; border-color: #384c6e; }
        body.dark-theme .form-control:focus, body.dark-theme .form-select:focus { border-color: #38a8ff; background: #161e2e; color: #fff; }
        body.dark-theme .role-desc-alert { background: #1a253b; border-color: #38a8ff33; color: #7ee7ff; }
        body.dark-theme .joinus-desc { color: #bde8ff !important;}
        @media (max-width: 800px) { .joinus-section { max-width: 98vw; padding: 1.3rem 0.5rem;} }
        @media (max-width: 540px) { .joinus-section { padding: 0.6rem 0.1rem;} .joinus-title { font-size: 1.25rem;} }
        .footer-main { background: var(--footer-bg); color: var(--footer-text); text-align: center; padding: 2.2rem 0 1.2rem 0; border-radius: 36px 36px 0 0; font-size: 1.02rem; letter-spacing: 1px; width: 100%; margin-top: auto; }
        html[dir="rtl"] .joinus-title { text-align: right; }
        .skeleton-box { border-radius: 10px; background: linear-gradient(90deg, #f4f7fa 25%, #e6edf5 50%, #f4f7fa 75%); background-size: 200% 100%; animation: skeleton-loading 1.13s infinite linear; margin-bottom: 0.2rem; }
        body.dark-theme .skeleton-box { background: linear-gradient(90deg, #232d3b 25%, #1e2632 50%, #232d3b 75%); }
        @keyframes skeleton-loading { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    </style>
    <script>
        window.PAGE_TRANSLATIONS = <?= json_encode($translations, JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body<?= $darkThemeActive ? ' class="dark-theme"' : '' ?>>
<?php include 'includes/dashboard-navbar.php'; ?>

<div class="main-content">
    <div class="joinus-section shadow" id="joinusContent">
        <div class="text-center py-4" id="joinusLoading">
            <span class="spinner-border text-primary"></span>
            <div style="margin-top:8px;font-size:1.06em;color:#2499fa"><?= $translations['loading'] ?? '' ?></div>
        </div>
    </div>
</div>
<!-- فرم همکاری (پاپ‌آپ دو مرحله‌ای) -->
<div class="modal fade" id="joinusModal" tabindex="-1" aria-labelledby="joinusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="joinusForm" method="post" enctype="multipart/form-data" autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title" id="joinusModalLabel"><?= $translations['modal_title'] ?? '' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= $translations['back'] ?? '' ?>"></button>
                </div>
                <div class="modal-body position-relative" id="modalBody">
                    <div id="joinus-skeleton" style="display:none;">
                        <div style="margin-bottom:1.4rem;">
                            <div class="skeleton-box" style="width: 70%; height: 32px; margin-bottom:17px;"></div>
                            <div class="skeleton-box" style="width: 90%; height: 32px; margin-bottom:17px;"></div>
                            <div class="skeleton-box" style="width: 80%; height: 19px;"></div>
                        </div>
                    </div>
                    <div id="step1" style="display:none;">
                        <div class="mb-3">
                            <label for="projectSelect" class="form-label"><?= $translations['project'] ?? '' ?></label>
                            <select class="form-select" id="projectSelect" name="project" required>
                                <option value=""><?= $translations['choose_project'] ?? '' ?></option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="roleSelect" class="form-label"><?= $translations['role'] ?? '' ?></label>
                            <select class="form-select" id="roleSelect" name="role" disabled required>
                                <option value=""><?= $translations['choose_role'] ?? '' ?></option>
                            </select>
                        </div>
                        <div id="roleDesc" class="role-desc-alert"></div>
                        <div class="text-end">
                            <button type="button" class="btn btn-primary px-4" id="goStep2" disabled><?= $translations['continue'] ?? '' ?></button>
                        </div>
                    </div>
                    <div id="step2" style="display:none;">
                        <input type="hidden" name="step" value="2">
                        <div class="mb-3">
                            <label for="fullname" class="form-label"><?= $translations['fullname'] ?? '' ?></label>
                            <input type="text" class="form-control" id="fullname" name="fullname" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label"><?= $translations['email'] ?? '' ?></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="skills" class="form-label"><?= $translations['skills'] ?? '' ?></label>
                            <textarea class="form-control" id="skills" name="skills" rows="2" placeholder="<?= $translations['skills_ph'] ?? '' ?>" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="desc" class="form-label"><?= $translations['desc'] ?? '' ?></label>
                            <textarea class="form-control" id="desc" name="desc" rows="3" placeholder="<?= $translations['desc'] ?? '' ?>"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="cv" class="form-label"><?= $translations['cv'] ?? '' ?></label>
                            <input type="file" class="form-control" id="cv" name="cv" accept=".pdf,.doc,.docx,.txt,.zip,.rar">
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary" id="backStep1"><?= $translations['back'] ?? '' ?></button>
                            <button type="submit" class="btn btn-primary px-4" id="submitBtn"><?= $translations['submit'] ?? '' ?></button>
                        </div>
                    </div>
                    <div id="formAlert"></div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/dashboard-footer.php'; ?>
<?php include __DIR__.'/../shared/inc/foot-assets.php'; ?>
<?php if(file_exists('includes/theme-script.php')) include 'includes/theme-script.php'; ?>

<script>
const joinusContent = document.getElementById('joinusContent');
const t = window.PAGE_TRANSLATIONS || {};
const lang = <?=json_encode($lang)?>;
const modal = document.getElementById('joinusModal');
let projects = [], allRoles = {}, roleDetail = {};

// ---- لود اطلاعات صفحه (joinus_settings.php) ----
fetch('https://api.xtremedev.co/endpoints/joinus_settings.php?lang='+lang, {
    headers: {'Authorization': 'Bearer <?=htmlspecialchars($access_token)?>'}
})
.then(async resp=>{
    let txt = await resp.text();
    let isJson = false, data = txt;
    try { data = JSON.parse(txt); isJson = true; } catch(e){}
    if(isJson && (data.title || data.desc)) {
        let html = '';
        if (data.logo_url) {
            html += `<div class="text-center mb-3"><img src="${data.logo_url}" alt="logo" style="max-width:180px;max-height:80px;object-fit:contain"></div>`;
        }
        html += `<div class="joinus-title">${data.title || (t['joinus_title']||'')}</div>`;
        html += `<div class="joinus-desc">${data.desc || (t['joinus_desc']||'')}</div>`;
        html += `<div class="row">
            <div class="col-md-6 joinus-block">
                ${data.rules ? `<h3>${t['collab_terms']||''}</h3><div>${data.rules}</div>` : ''}
            </div>
            <div class="col-md-6 joinus-block">
                ${data.benefits ? `<h3>${t['collab_benefits']||''}</h3><div>${data.benefits}</div>` : ''}
            </div>
        </div>
        <button class="joinus-form-btn" data-bs-toggle="modal" data-bs-target="#joinusModal">${t['apply_now']||''}</button>`;
        joinusContent.innerHTML = html;
    } else {
        let code = resp.status;
        joinusContent.innerHTML = `<div class="text-danger text-center mt-3">
            ${(t['error_settings']||'')}
            <br><small style="direction:ltr">HTTP ${code} : <span>${txt}</span></small>
        </div>`;
    }
})
.catch((err)=>{
    let msg = '';
    if (err instanceof TypeError) {
        msg = `[NetworkError] ${err.message}`;
    } else {
        msg = err && err.toString ? err.toString() : '[unknown error]';
    }
    joinusContent.innerHTML = `<div class="text-danger text-center mt-3">
        ${(t['error_settings']||'')}
        <br><small style="direction:ltr">${msg}</small>
    </div>`;
    console.error('Fetch JoinUs Error:', err);
});

// ---- لود پروژه‌ها ----
fetch('https://api.xtremedev.co/endpoints/joinus_projects.php?lang='+lang, {
    headers: {'Authorization': 'Bearer <?=htmlspecialchars($access_token)?>'}
})
.then(resp=>resp.text())
.then(txt=>{
    let data;
    try { data = JSON.parse(txt); } catch(e){ data = txt; }
    if(Array.isArray(data)) {
        projects = data;
        let sel = document.getElementById('projectSelect');
        sel.innerHTML = `<option value="">${t['choose_project']||''}</option>`;
        projects.forEach(pr=>{
            sel.innerHTML += `<option value="${pr.id}">${pr.title}</option>`;
        });
        step1.style.display = 'block';
    }
});

// ---- لود نقش‌های پروژه‌ها ----
fetch('https://api.xtremedev.co/endpoints/joinus_roles.php?lang='+lang, {
    headers: {'Authorization': 'Bearer <?=htmlspecialchars($access_token)?>'}
})
.then(resp=>resp.text())
.then(txt=>{
    let data;
    try { data = JSON.parse(txt); } catch(e){ data = txt; }
    if(Array.isArray(data)) {
        allRoles = {};
        roleDetail = {};
        data.forEach(r=>{
            if(!allRoles[r.project_id]) allRoles[r.project_id]=[];
            allRoles[r.project_id].push({id:r.id, title:r.role_title, desc:r.role_desc});
            roleDetail[r.id] = {title:r.role_title, desc:r.role_desc};
        });
    }
});

// ---- هندل فرم ----
const joinusForm = document.getElementById('joinusForm');
const step1 = document.getElementById('step1');
const step2 = document.getElementById('step2');
const goStep2Btn = document.getElementById('goStep2');
const backStep1Btn = document.getElementById('backStep1');
const projectSelect = document.getElementById('projectSelect');
const roleSelect = document.getElementById('roleSelect');
const roleDesc = document.getElementById('roleDesc');
const formAlert = document.getElementById('formAlert');

projectSelect.addEventListener('change', function() {
    roleSelect.innerHTML = '';
    let pid = this.value;
    if(pid && allRoles[pid]) {
        roleSelect.disabled = false;
        roleSelect.innerHTML = `<option value="">${t['choose_role']||''}</option>`;
        allRoles[pid].forEach(function(r) {
            let opt = document.createElement('option');
            opt.value = r.id;
            opt.textContent = r.title;
            roleSelect.appendChild(opt);
        });
    } else {
        roleSelect.disabled = true;
        roleSelect.innerHTML = `<option value="">${t['choose_role']||''}</option>`;
    }
    roleDesc.style.display = 'none';
    goStep2Btn.disabled = true;
});
roleSelect.addEventListener('change', function() {
    let rid = this.value;
    if(rid && roleDetail[rid] && roleDetail[rid].desc) {
        roleDesc.textContent = roleDetail[rid].desc;
        roleDesc.style.display = 'block';
    } else {
        roleDesc.style.display = 'none';
    }
    goStep2Btn.disabled = !projectSelect.value || !roleSelect.value;
});
projectSelect.addEventListener('change', function() {
    goStep2Btn.disabled = !projectSelect.value || !roleSelect.value;
});
goStep2Btn.addEventListener('click', function() {
    step1.style.display = 'none'; step2.style.display = 'block';
});
backStep1Btn && backStep1Btn.addEventListener('click', function() {
    step2.style.display = 'none'; step1.style.display = 'block';
});

modal.addEventListener('show.bs.modal', function(){
    document.getElementById('joinus-skeleton').style.display = 'block';
    step1.style.display = 'none'; step2.style.display = 'none';
    setTimeout(function(){
        document.getElementById('joinus-skeleton').style.display = 'none';
        step1.style.display = 'block';
        joinusForm.reset();
        projectSelect.selectedIndex = 0;
        roleSelect.innerHTML = `<option value="">${t['choose_role']||''}</option>`;
        roleSelect.disabled = true;
        roleDesc.style.display = 'none';
        goStep2Btn.disabled = true;
        formAlert.innerHTML = '';
    }, 700);
});

joinusForm.addEventListener('submit', function(e) {
    e.preventDefault();
    formAlert.innerHTML = '';
    const formData = new FormData(joinusForm);
    formData.set('project_id', formData.get('project'));
    formData.set('role_id', formData.get('role'));
    formData.delete('project'); formData.delete('role'); formData.delete('step');
    document.getElementById('submitBtn').disabled = true;

    fetch('https://api.xtremedev.co/endpoints/joinus_request_new.php', {
        method: 'POST',
        headers: {'Authorization': 'Bearer <?=htmlspecialchars($access_token)?>'},
        body: formData
    })
    .then(async resp => {
        let isJson = resp.headers.get('content-type')?.includes('application/json');
        let resText = await resp.text();
        let res;
        try { res = JSON.parse(resText); } catch(e) { res = resText; }
        if (res && res.success) {
            step2.style.display = 'none';
            formAlert.innerHTML =
                `<div class="alert alert-success text-center mt-2">${t['success']||''}</div>`;
            setTimeout(()=>{modal.querySelector('.btn-close').click();},1800);
        } else {
            formAlert.innerHTML =
                `<div class="alert alert-danger text-center mt-2">
                    ${(t['fail']||'')}<br>
                    <small style="direction:ltr;display:block;padding-top:6px">${
                        (typeof res === 'object') ? JSON.stringify(res, null, 2) : (res||'-')
                    }</small>
                </div>`;
        }
        document.getElementById('submitBtn').disabled = false;
    })
    .catch((err)=>{
        let msg = '';
        if (err instanceof TypeError) {
            msg = `[NetworkError] ${err.message}`;
        } else {
            msg = err && err.toString ? err.toString() : '[unknown error]';
        }
        formAlert.innerHTML =
            `<div class="alert alert-danger text-center mt-2">${t['fail']||''}<br>
            <small style="direction:ltr;display:block;padding-top:6px">${msg}</small>
            </div>`;
        document.getElementById('submitBtn').disabled = false;
    });
});
</script>
</body>
</html>