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
$jid = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>
<!DOCTYPE html>
<html lang="<?=$lang?>" dir="<?=$is_rtl?'rtl':'ltr'?>">
<head>
    <meta charset="UTF-8">
    <title><?= $translations['page_title'] ?? '' ?> | XtremeDev</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php include __DIR__.'/../shared/inc/head-assets.php'; ?>
    <?php include __DIR__.'/includes/dashboard-styles.php'; ?>
    <style>
        :root {
            --primary: #2499fa;
            --surface: #f4f7fa;
            --surface-alt: #fff;
            --text: #222;
            --shadow-card: #2499fa14;
            --border: #2499fa18;
        }
        body {
            min-height: 100vh;
            background: var(--surface, #f4f7fa) !important;
            color: var(--text, #222) !important;
            font-family: 'Vazirmatn', Tahoma, Arial, sans-serif;
            display: flex;
            flex-direction: column;
            transition: background 0.3s;
        }
        body.dark-theme {
            --surface: #181f2a;
            --surface-alt: #222b38;
            --text: #e6e9f2;
            background: var(--surface) !important;
            color: var(--text) !important;
        }
        .main-content, .container { background: transparent !important; }
        .joinus-card {
            background: var(--surface-alt, #fff) !important;
            border-radius: 18px;
            box-shadow: 0 2px 24px var(--shadow-card, #2499fa14);
            border: 1.5px solid var(--border, #2499fa18);
            padding: 2rem 1.3rem 1.4rem 1.3rem;
            max-width: 560px;
            margin: 42px auto 30px auto;
            width: 100%;
            color: var(--text, #222);
            min-height: 350px;
        }
        .joinus-title {
            color: var(--primary, #2499fa);
            font-size: 1.21rem;
            font-weight: 900;
            margin-bottom: 1.4rem;
            letter-spacing: .2px;
            text-align: center !important;
        }
        .joinus-meta {
            font-size: 1.05rem;
            color: #888;
            margin-bottom: 1.2rem;
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        .joinus-status {
            font-weight: 800;
            font-size: 1.09rem;
        }
        .joinus-section {
            margin-bottom: 1.12rem;
        }
        .joinus-label {
            font-weight: 700;
            color: #2499fa;
            margin-bottom: .3rem;
            font-size: 1.02rem;
        }
        .joinus-value {
            font-size: 1.08rem;
            color: #223;
            white-space: pre-line;
            word-break: break-word;
        }
        .cv-link {
            font-size: 1rem;
            color: #38a8ff;
            font-weight: 700;
            text-decoration: underline;
        }
        .cv-link:hover { color: #2499fa; }
        .admin-note-block {
            margin-top: 1.1rem;
            padding: 1rem 1rem 0.7rem 1rem;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e4e7ef;
            color: #444;
            font-size: 1.02rem;
        }
        body.dark-theme .joinus-card {
            background: var(--surface-alt) !important;
            color: var(--text) !important;
            box-shadow: 0 2px 24px #0d111c77;
            border-color: #384c6e !important;
        }
        body.dark-theme .joinus-title { color: #38a8ff !important; }
        body.dark-theme .joinus-meta { color: #8da7c7 !important; }
        body.dark-theme .joinus-label { color: #38a8ff !important; }
        body.dark-theme .joinus-value { color: #e6e9f2 !important; }
        body.dark-theme .cv-link { color: #6fc6ff !important; }
        body.dark-theme .cv-link:hover { color: #38a8ff !important; }
        body.dark-theme .admin-note-block {
            background: #181f2a !important;
            border-color: #384c6e !important;
            color: #a3e6fa !important;
        }
        .back-btn-wrap {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 1.5rem;
        }
        .back-btn {
            min-width: 160px;
            font-size: 1.07rem;
            font-weight: 700;
            border-radius: 10px;
            background: #e4e7ef;
            color: #2499fa;
            border: none;
            transition: background 0.18s;
            padding: 0.7rem 1.5rem;
            display: inline-block;
        }
        .back-btn:hover, .back-btn:focus {
            background: #d1e9ff;
            color: #145d99;
        }
        body.dark-theme .back-btn { background: #1a253b !important; color: #38a8ff !important; }
        body.dark-theme .back-btn:hover, body.dark-theme .back-btn:focus { background: #222b38 !important; color: #fff !important;}
        @media (max-width: 600px) {
            .joinus-card { padding: 1.3rem 0.7rem 1.1rem 0.7rem; }
        }
        .skeleton-meta, .skeleton-section {
            border-radius: 10px;
            background: linear-gradient(90deg, #f4f7fa 25%, #e6edf5 50%, #f4f7fa 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.13s infinite linear;
            margin-bottom: 1.1rem;
        }
        .skeleton-meta {
            width: 80%;
            max-width: 320px;
            height: 28px;
            margin: 0 auto 1.1rem auto;
        }
        .skeleton-section {
            width: 100%;
            height: 38px;
            margin-bottom: 1.05rem;
        }
        .dark-theme .skeleton-meta, .dark-theme .skeleton-section {
            background: linear-gradient(90deg, #232d3b 25%, #1e2632 50%, #232d3b 75%);
        }
        @keyframes skeleton-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
    <script>
        window.PAGE_TRANSLATIONS = <?= json_encode($translations, JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body<?= $darkThemeActive ? ' class="dark-theme"' : '' ?>>
<?php include 'includes/dashboard-navbar.php'; ?>

<div class="main-content">
    <div class="container">
        <div class="joinus-card" id="joinus-card">
            <div id="joinus-skeleton">
                <div class="skeleton-meta"></div>
                <div class="skeleton-meta" style="width:60%;"></div>
                <div class="skeleton-section"></div>
                <div class="skeleton-section"></div>
                <div class="skeleton-section"></div>
                <div class="skeleton-section"></div>
                <div class="skeleton-section" style="width:75%;"></div>
            </div>
            <div id="joinus-detail-content" style="display:none;"></div>
        </div>
    </div>
</div>

<?php include 'includes/dashboard-footer.php'; ?>
<?php include __DIR__.'/../shared/inc/foot-assets.php'; ?>
<?php if(file_exists('includes/theme-script.php')) include 'includes/theme-script.php'; ?>

<script>
var t = window.PAGE_TRANSLATIONS || {};
var lang = <?= json_encode($lang) ?>;
var jid = <?= intval($jid) ?>;
var token = <?= json_encode($access_token) ?>;
function escapeHtml(str) {
    return (str || "").replace(/[<>&"]/g, function(m) {
        return {'<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;'}[m];
    });
}
function toJalali(dateStr) {
    if (!dateStr) return '';
    const d = dateStr.split(' ')[0].split('-');
    let gy = parseInt(d[0]), gm = parseInt(d[1]), gd = parseInt(d[2]);
    const g_d_m = [0,31,59,90,120,151,181,212,243,273,304,334];
    let jy = (gy<=1600) ? 0 : 979, gy2 = (gy<=1600) ? gy+621 : gy-1600;
    let days = (365*gy2) + Math.floor((gy2+3)/4) - Math.floor((gy2+99)/100) + Math.floor((gy2+399)/400) - 80 + gd + g_d_m[gm-1];
    if (gm>2 && ((gy%4==0 && gy%100!=0)||gy%400==0)) days++;
    jy += 33*Math.floor(days/12053); days %= 12053;
    jy += 4*Math.floor(days/1461); days %= 1461;
    if (days > 365) { jy += Math.floor((days-1)/365); days = (days-1)%365; }
    let jm, jd;
    if (days < 186) { jm = 1+Math.floor(days/31); jd = 1+(days%31); }
    else { jm = 7+Math.floor((days-186)/30); jd = 1+((days-186)%30); }
    return jy + '/' + (jm<10?'0':'')+jm + '/' + (jd<10?'0':'')+jd;
}

function renderDetail(data) {
    const $skeleton = document.getElementById('joinus-skeleton');
    const $content = document.getElementById('joinus-detail-content');
    $skeleton.style.display = 'none';
    $content.style.display = '';

    if(!data || data.error) {
        $content.innerHTML = `<div class="alert alert-danger text-center mt-2">${t['not_found']||''}</div>`;
        return;
    }
    let status_colors = {
        pending: '#ffa500',
        under_review: '#2499fa',
        accepted: '#2bc551',
        rejected: '#e33'
    };
    let status = data.status;
    let status_label = t[status] || status;
    let status_color = (status_colors[status] || '#555');
    let createdVal = lang === 'fa' ? toJalali(data.created_at) : escapeHtml(data.created_at);
    let updatedVal = data.updated_at ? (lang === 'fa' ? toJalali(data.updated_at) : escapeHtml(data.updated_at)) : '';

    let html = `
        <div class="joinus-title">${t['page_title']||''}</div>
        <div class="joinus-meta">
            <span>
                <span class="joinus-status" style="color:${status_color};">
                    ${(t['status']||'')}: ${escapeHtml(status_label)}
                </span>
            </span>
            <span>${(t['submitted']||'')}: ${createdVal}</span>
            ${updatedVal ? `<span>${(t['updated']||'')}: ${updatedVal}</span>` : ''}
        </div>
        <div class="joinus-section">
            <div class="joinus-label">${t['project']||''}</div>
            <div class="joinus-value">${escapeHtml(data.project_title || '')}</div>
        </div>
        <div class="joinus-section">
            <div class="joinus-label">${t['role']||''}</div>
            <div class="joinus-value">${escapeHtml(data.role_title || '')}</div>
        </div>
        <div class="joinus-section">
            <div class="joinus-label">${t['fullname']||''}</div>
            <div class="joinus-value">${escapeHtml(data.fullname || '')}</div>
        </div>
        <div class="joinus-section">
            <div class="joinus-label">${t['email']||''}</div>
            <div class="joinus-value">${escapeHtml(data.email || '')}</div>
        </div>
        <div class="joinus-section">
            <div class="joinus-label">${t['skills']||''}</div>
            <div class="joinus-value">${escapeHtml(data.skills || '')}</div>
        </div>
        <div class="joinus-section">
            <div class="joinus-label">${t['desc']||''}</div>
            <div class="joinus-value">${escapeHtml(data.desc || '')}</div>
        </div>
        <div class="joinus-section">
            <div class="joinus-label">${t['cv_file']||''}</div>
            <div class="joinus-value">
                ${data.cv_file
                    ? `<a href="${escapeHtml(data.cv_file)}" class="cv-link" target="_blank">${t['download_cv']||''}</a>`
                    : `<span style="color:#aaa;">${t['no_file']||''}</span>`
                }
            </div>
        </div>
        ${(data.admin_note ? `<div class="admin-note-block"><strong>${t['admin_note']||''}:</strong> ${escapeHtml(data.admin_note).replace(/\n/g,'<br>')}</div>` : '')}
        <div class="back-btn-wrap">
            <a href="index.php" class="back-btn">${t['back']||''}</a>
        </div>
    `;
    $content.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('joinus-skeleton').style.display = '';
    document.getElementById('joinus-detail-content').style.display = 'none';

    fetch('https://api.xtremedev.co/endpoints/joinus_request.php?id=' + encodeURIComponent(jid) + '&lang=' + encodeURIComponent(lang), {
        headers: token ? { 'Authorization': 'Bearer ' + token } : {}
    })
    .then(r=>r.json())
    .then(data=>renderDetail(data))
    .catch(()=>renderDetail({error:1}));
});
</script>
</body>
</html>