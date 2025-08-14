<?php
session_start();
require_once __DIR__.'/../shared/inc/config.php';

// تابع تبدیل میلادی به شمسی (در JS هم داریم)
function to_jalali($date_gregorian) {
    if(!$date_gregorian) return '';
    $parts = explode(' ', $date_gregorian);
    $date = $parts[0];
    list($gy,$gm,$gd) = explode('-', $date);
    return gregorian_to_jalali_simple($gy, $gm, $gd);
}
function gregorian_to_jalali_simple($gy, $gm, $gd) {
    $g_days_in_month = [31,28,31,30,31,30,31,31,30,31,30,31];
    $j_days_in_month = [31,31,31,31,31,31,30,30,30,30,30,29];
    $gy = intval($gy); $gm = intval($gm); $gd = intval($gd);
    $gy2 = ($gm > 2)? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + intval(($gy2 + 3) / 4) - intval(($gy2 + 99) / 100)
        + intval(($gy2 + 399) / 400) + $gd;
    for ($i=0; $i < $gm - 1; ++$i)
        $days += $g_days_in_month[$i];
    $jy = -1595 + (33 * intval($days / 12053));
    $days %= 12053;
    $jy += 4 * intval($days/1461);
    $days %= 1461;
    if ($days > 365) {
        $jy += intval(($days - 1) / 365);
        $days = ($days - 1) % 365;
    }
    for ($j=0; $j < 11 && $days >= $j_days_in_month[$j]; ++$j)
        $days -= $j_days_in_month[$j];
    $jm = $j + 1;
    $jd = $days + 1;
    return sprintf("%04d/%02d/%02d", $jy, $jm, $jd);
}

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
    <title><?= $translations['page_title'] ?? '' ?> | XtremeDev</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php include __DIR__.'/../shared/inc/head-assets.php'; ?>
    <?php include __DIR__.'/includes/dashboard-styles.php'; ?>
    <style>
        html, body { min-height: 100vh; }
        body {
            background: var(--surface, #f4f7fa) !important;
            color: var(--text, #222) !important;
            font-family: 'Vazirmatn', Tahoma, Arial, sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            transition: background 0.3s;
        }
        body.dark-theme {
            --surface: #181f2a;
            --surface-alt: #222b38;
            --text: #e6e9f2;
            background: var(--surface) !important;
            color: var(--text) !important;
        }
        .main-content { flex: 1 0 auto; background: transparent !important;}
        .container { background: transparent !important; }
        .page-title {
            font-size: 2rem;
            font-weight: 900;
            color: var(--primary, #2499fa);
            margin-bottom: 2.2rem;
            text-align: center !important;
            letter-spacing: .6px;
        }
        .applications-card {
            background: var(--surface-alt, #fff);
            border-radius: 18px;
            box-shadow: 0 2px 24px var(--shadow-card, #2499fa14);
            border: 1.5px solid var(--border, #2499fa18);
            padding: 1.7rem 1rem 1.2rem 1rem;
            max-width: 1100px;
            margin: 0 auto 2.5rem auto;
            min-height: 340px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            width: 100%;
        }
        .applications-card h2 {
            color: var(--primary, #2499fa);
            font-size: 1.19rem;
            font-weight: 800;
            margin-bottom: 1.4rem;
            letter-spacing: .3px;
            text-align: center !important;
        }
        .joinus-table {
            background: var(--surface-alt, #fff);
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 0.6rem;
        }
        .joinus-table thead th {
            background: var(--surface-alt, #fff);
            color: #2499fa;
            font-weight: 800;
            border-bottom: 2px solid var(--border, #2499fa18);
        }
        .joinus-table tbody tr {
            background: transparent;
        }
        .joinus-table tr td,
        .joinus-table tr th {
            vertical-align: middle;
            background: transparent;
            border-bottom: 1px solid #e8f0fa;
        }
        .details-btn {
            padding: 0.35rem 1rem;
            border-radius: 8px;
            font-size: 0.97rem;
            font-weight: 700;
            background: #e4e7ef;
            color: #2499fa;
            border: none;
            transition: background 0.18s, color 0.18s;
            margin-left: 0.4rem;
            margin-right: 0.4rem;
            text-decoration: none;
            display: inline-block;
        }
        .details-btn:hover, .details-btn:focus {
            background: #2499fa;
            color: #fff;
            text-decoration: none;
        }
        body.dark-theme .applications-card,
        body.dark-theme .joinus-table {
            background: var(--surface-alt) !important;
            color: var(--text) !important;
            box-shadow: 0 2px 24px #0d111c77;
            border-color: #384c6e !important;
        }
        body.dark-theme .joinus-table thead th {
            background: var(--surface-alt) !important;
            color: #38a8ff !important;
            border-bottom: 2px solid #384c6e !important;
        }
        body.dark-theme .joinus-table tr td {
            background: transparent !important;
            color: var(--text) !important;
            border-bottom: 1px solid #384c6e !important;
        }
        body.dark-theme .joinus-table tr th {
            background: transparent !important;
            color: #38a8ff !important;
        }
        body.dark-theme .details-btn {
            background: #1a253b !important;
            color: #38a8ff !important;
        }
        body.dark-theme .details-btn:hover, body.dark-theme .details-btn:focus {
            background: #2499fa !important;
            color: #fff !important;
        }
        .skeleton-table { width: 100%; border-collapse: collapse; margin-bottom: 2rem; margin-top: .7rem; }
        .skeleton-table th, .skeleton-table td { padding: 0.8rem 0.5rem; border-bottom: 1px solid #e0e7f0; }
        .skeleton-row .skeleton-cell {
            height: 22px; border-radius: 7px;
            background: linear-gradient(90deg, #f4f7fa 25%, #e6edf5 50%, #f4f7fa 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.1s infinite linear;
        }
        .dark-theme .skeleton-row .skeleton-cell {
            background: linear-gradient(90deg, #232d3b 25%, #1e2632 50%, #232d3b 75%);
        }
        .skeleton-cell { width: 100%; display:block; }
        @keyframes skeleton-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        @media (max-width: 767px) {
            .page-title { font-size: 1.3rem;}
            .applications-card { padding: 1.1rem 0.4rem 1.2rem 0.4rem; }
        }
        html[dir="rtl"] .applications-card h2,
        html[dir="rtl"] .page-title { text-align: center !important; }
    </style>
    <script>
        window.PAGE_TRANSLATIONS = <?= json_encode($translations, JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body<?= $darkThemeActive ? ' class="dark-theme"' : '' ?>>
<?php include 'includes/dashboard-navbar.php'; ?>

<div class="main-content">
    <div class="container" style="max-width:1100px;padding-top:38px;padding-bottom:16px;">
        <div class="page-title"><?= $translations['page_title'] ?? '' ?></div>
        <div class="applications-card">
            <h2><?= $translations['list_title'] ?? '' ?></h2>
            <div id="applies-skeleton">
                <div class="table-responsive mb-4">
                    <table class="skeleton-table" style="width:100%;">
                        <thead>
                        <tr>
                            <?php for($j=0;$j<8;$j++): ?>
                                <th></th>
                            <?php endfor; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php for($i=0;$i<4;$i++): ?>
                        <tr class="skeleton-row">
                            <?php for($j=0;$j<8;$j++): ?>
                                <td><div class="skeleton-cell"></div></td>
                            <?php endfor; ?>
                        </tr>
                        <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div id="applies-table-wrap" style="display:none;"></div>
            <div id="applies-none" style="color:#aaa;font-size:1.07rem;display:none;"><?= $translations['no_applies'] ?? '' ?></div>
            <div id="applies-submit-btn" style="display:none;">
                <a href="joinus.php" class="btn btn-primary btn-sm mt-2"><?= $translations['submit_request'] ?? '' ?></a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/dashboard-footer.php'; ?>
<?php include __DIR__.'/../shared/inc/foot-assets.php'; ?>
<?php if(file_exists('includes/theme-script.php')) include 'includes/theme-script.php'; ?>
<script>
var t = window.PAGE_TRANSLATIONS || {};
var lang = <?= json_encode($lang) ?>;
var accessToken = <?= json_encode($access_token) ?>;

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
function escapeHtml(str) {
    return (str || "").replace(/[<>&"]/g, function(m) {
        return {'<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;'}[m];
    });
}

function renderApplies(applies) {
    const wrap = document.getElementById('applies-table-wrap');
    const none = document.getElementById('applies-none');
    const submitBtn = document.getElementById('applies-submit-btn');
    if (!applies || !applies.length) {
        wrap.innerHTML = '';
        wrap.style.display = 'none';
        none.style.display = '';
        submitBtn.style.display = '';
        return;
    }
    none.style.display = 'none';
    submitBtn.style.display = 'none';
    wrap.style.display = '';
    let html = `
    <div class="table-responsive">
        <table class="table table-borderless align-middle mb-1 joinus-table" style="font-size:0.97rem;">
            <thead>
            <tr>
                <th>${t['th_project']||''}</th>
                <th>${t['th_role']||''}</th>
                <th>${t['th_fullname']||''}</th>
                <th>${t['th_skills']||''}</th>
                <th>${t['th_status']||''}</th>
                <th>${t['th_date']||''}</th>
                <th>${t['th_cv']||''}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
    `;
    applies.forEach(function(a){
        let status = a.status;
        let color = (status=='pending' ? '#ffa500' : (status=='under_review' ? '#2499fa' : (status=='accepted' ? '#2bc551' : '#e33')));
        let label = t[status] || status;
        let dateVal = lang==='fa' ? toJalali(a.created_at) : (a.created_at || '').substr(0,10);
        html += `
            <tr>
                <td>${escapeHtml(a.project_title)}</td>
                <td>${escapeHtml(a.role_title)}</td>
                <td>${escapeHtml(a.fullname)}</td>
                <td>${escapeHtml(a.skills)}</td>
                <td><span style="color:${color};font-weight:800;">${escapeHtml(label)}</span></td>
                <td>${escapeHtml(dateVal)}</td>
                <td>
                    ${a.cv_file
                        ? `<a href="${escapeHtml(a.cv_file)}" target="_blank">${t['download']||''}</a>`
                        : `<span style="color:#aaa;">-</span>`
                    }
                </td>
                <td>
                    <a href="joinus-details.php?id=${encodeURIComponent(a.id)}" class="details-btn">${t['th_details']||''}</a>
                </td>
            </tr>
        `;
    });
    html += `</tbody></table></div>`;
    wrap.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', function() {
    const skeleton = document.getElementById('applies-skeleton');
    const wrap = document.getElementById('applies-table-wrap');
    const none = document.getElementById('applies-none');
    const submitBtn = document.getElementById('applies-submit-btn');
    wrap.style.display = 'none';
    none.style.display = 'none';
    submitBtn.style.display = 'none';
    skeleton.style.display = '';

    let apiUrl = 'https://api.xtremedev.co/endpoints/joinus_requests.php?lang=' + encodeURIComponent(lang);

    fetch(apiUrl, {
        headers: accessToken ? { 'Authorization': 'Bearer ' + accessToken } : {}
    })
    .then(r=>r.json())
    .then(data=>{
        skeleton.style.display = 'none';
        if(Array.isArray(data) && data.length) {
            renderApplies(data);
        } else {
            wrap.innerHTML = '';
            wrap.style.display = 'none';
            none.style.display = '';
            submitBtn.style.display = '';
        }
    })
    .catch(()=>{
        skeleton.style.display = 'none';
        wrap.innerHTML = '';
        wrap.style.display = 'none';
        none.style.display = '';
        submitBtn.style.display = '';
    });
});
</script>
</body>
</html>