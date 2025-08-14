<?php
session_start();
require_once __DIR__.'/../shared/inc/config.php';

// زبان و ترجمه
$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'en');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'en');
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
    <title><?= $translations['support_tickets'] ?? '' ?> | XtremeDev</title>
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
            background: var(--surface) !important;
            color: var(--text) !important;
            font-family: 'Vazirmatn', Tahoma, Arial, sans-serif;
            display: flex;
            flex-direction: column;
        }
        body.dark-theme {
            --surface: #181f2a;
            --surface-alt: #222b38;
            --text: #e6e9f2;
            background: var(--surface) !important;
            color: var(--text) !important;
        }
        .main-content { flex: 1 0 auto; }
        .page-title {
            font-size: 2rem;
            font-weight: 900;
            color: var(--primary, #2499fa);
            margin-bottom: 2.2rem;
            text-align: center;
            letter-spacing: .6px;
        }
        .tickets-card {
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
        .tickets-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.4rem;
            flex-wrap: wrap;
            gap: 0.8rem;
        }
        .tickets-card h2 {
            color: var(--primary, #2499fa);
            font-size: 1.18rem;
            font-weight: 800;
            margin: 0;
            letter-spacing: .3px;
            text-align: left;
            flex: 1 1;
        }
        .create-ticket-btn {
            display: inline-block;
            font-size: 1.02rem;
            font-weight: 700;
            padding: 0.46rem 1.1rem 0.43rem 1.1rem;
            border-radius: 8px;
            background: #2499fa;
            color: #fff !important;
            border: none;
            transition: background 0.18s, color 0.18s;
            box-shadow: 0 1px 6px #2499fa18;
            text-decoration: none;
        }
        .create-ticket-btn:hover, .create-ticket-btn:focus {
            background: #38a8ff;
            color: #fff !important;
            text-decoration: none;
        }
        .tickets-table {
            background: var(--surface-alt, #fff);
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 0.6rem;
        }
        .tickets-table thead th {
            background: var(--surface-alt, #fff);
            color: #2499fa;
            font-weight: 800;
            border-bottom: 2px solid var(--border, #2499fa18);
        }
        .tickets-table tbody tr {
            background: transparent;
        }
        .tickets-table tr td,
        .tickets-table tr th {
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
        body.dark-theme .tickets-card,
        body.dark-theme .tickets-table {
            background: var(--surface-alt) !important;
            color: var(--text) !important;
            box-shadow: 0 2px 24px #0d111c77;
            border-color: #384c6e !important;
        }
        body.dark-theme .tickets-table thead th {
            background: var(--surface-alt) !important;
            color: #38a8ff !important;
            border-bottom: 2px solid #384c6e !important;
        }
        body.dark-theme .tickets-table tr td {
            background: transparent !important;
            color: var(--text) !important;
            border-bottom: 1px solid #384c6e !important;
        }
        body.dark-theme .tickets-table tr th {
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
        body.dark-theme .create-ticket-btn {
            background: #38a8ff !important;
            color: #fff !important;
        }
        body.dark-theme .create-ticket-btn:hover, body.dark-theme .create-ticket-btn:focus {
            background: #2499fa !important;
            color: #fff !important;
        }
        @media (max-width: 767px) {
            .page-title { font-size: 1.3rem;}
            .tickets-card { padding: 1.1rem 0.4rem 1.2rem 0.4rem; }
            .tickets-header-row { flex-direction: column; align-items: stretch; gap: 0.5rem;}
            .tickets-card h2 { margin-bottom: 0.3rem; }
            .create-ticket-btn { width: 100%; }
        }
        html[dir="rtl"] .page-title {text-align:right;}
        html[dir="rtl"] .tickets-card h2 {text-align:right;}
        /* === Skeleton Loader === */
        .skeleton-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
            margin-top: .5rem;
        }
        .skeleton-table th, .skeleton-table td {
            padding: 0.8rem 0.5rem;
            border-bottom: 1px solid #e0e7f0;
        }
        .skeleton-row .skeleton-cell {
            height: 22px;
            border-radius: 7px;
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
    </style>
    <script>
        window.PAGE_TRANSLATIONS = <?= json_encode($translations, JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body<?= $darkThemeActive ? ' class="dark-theme"' : '' ?>>
<?php include 'includes/dashboard-navbar.php'; ?>

<div class="main-content">
    <div class="container" style="max-width:1100px;padding-top:38px;padding-bottom:16px;">
        <div class="page-title"><?= $translations['support_tickets'] ?? '' ?></div>
        <div class="tickets-card">
            <div class="tickets-header-row">
                <h2><?= $translations['your_tickets'] ?? '' ?></h2>
                <a href="new-ticket.php" class="create-ticket-btn"><?= $translations['create_ticket'] ?? '' ?></a>
            </div>
            <!-- اسکلتون جدول -->
            <div id="tickets-skeleton">
                <div class="table-responsive">
                    <table class="skeleton-table" style="width:100%;">
                        <thead>
                        <tr>
                            <th style="width:43%"></th>
                            <th style="width:17%"></th>
                            <th style="width:25%"></th>
                            <th style="width:15%"></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php for($i=0;$i<4;$i++): ?>
                        <tr class="skeleton-row">
                            <?php for($j=0;$j<4;$j++): ?>
                                <td><div class="skeleton-cell"></div></td>
                            <?php endfor; ?>
                        </tr>
                        <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div id="tickets-table-wrap" style="display:none;"></div>
            <div id="tickets-none" style="color:#aaa;font-size:1.07rem;display:none;"><?= $translations['no_tickets'] ?? '' ?></div>
        </div>
    </div>
</div>

<?php include 'includes/dashboard-footer.php'; ?>
<?php include __DIR__.'/../shared/inc/foot-assets.php'; ?>
<?php if(file_exists('includes/theme-script.php')) include 'includes/theme-script.php'; ?>

<script>
// تبدیل تاریخ میلادی به جلالی (نمونه ساده)
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

const t = window.PAGE_TRANSLATIONS || {};
const lang = <?= json_encode($lang) ?>;

function escapeHtml(str) {
    return (str || "").replace(/[<>&"]/g, function(m) {
        return {'<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;'}[m];
    });
}

function renderTickets(tickets) {
    const $wrap = document.getElementById('tickets-table-wrap');
    const $none = document.getElementById('tickets-none');
    if (!tickets || !tickets.length) {
        $wrap.innerHTML = '';
        $wrap.style.display = 'none';
        $none.style.display = '';
        return;
    }
    $none.style.display = 'none';
    $wrap.style.display = '';
    let html = `
    <div class="table-responsive">
        <table class="table table-borderless align-middle mb-1 tickets-table" style="font-size:0.97rem;">
            <thead>
            <tr>
                <th>${t.subject}</th>
                <th>${t.status}</th>
                <th>${t.date}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
    `;
    tickets.forEach(tk => {
        let statusKey = (tk.status || '').toLowerCase();
        let color = statusKey === 'open' ? '#ffa500' : (statusKey === 'answered' ? '#2499fa' : '#aaa');
        let label = t[statusKey] || tk.status;
        let dateVal = (lang === 'fa') ? toJalali(tk.created_at) : (tk.created_at || '').substr(0,10);
        html += `
            <tr>
                <td>${escapeHtml(tk.subject)}</td>
                <td><span style="color:${color};font-weight:800;">${escapeHtml(label)}</span></td>
                <td>${escapeHtml(dateVal)}</td>
                <td>
                    <a href="ticket.php?id=${encodeURIComponent(tk.ticket_id)}" class="details-btn">${t.details}</a>
                </td>
            </tr>
        `;
    });
    html += `</tbody></table></div>`;
    $wrap.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', function() {
    const skeleton = document.getElementById('tickets-skeleton');
    const tableWrap = document.getElementById('tickets-table-wrap');
    const ticketsNone = document.getElementById('tickets-none');
    tableWrap.style.display = 'none';
    ticketsNone.style.display = 'none';
    skeleton.style.display = '';

    fetch('https://api.xtremedev.co/endpoints/my_tickets.php', {
        headers: {
            'Authorization': 'Bearer <?=$access_token?>'
        }
    })
    .then(async res => {
        let text = await res.text();
        let data;
        try { data = JSON.parse(text); }
        catch (e) { data = text; }
        skeleton.style.display = 'none';
        if (Array.isArray(data) && data.length > 0) {
            renderTickets(data);
        } else {
            tableWrap.innerHTML = '';
            tableWrap.style.display = 'none';
            ticketsNone.style.display = '';
        }
    })
    .catch(err => {
        skeleton.style.display = 'none';
        tableWrap.innerHTML = '';
        tableWrap.style.display = 'none';
        ticketsNone.style.display = '';
    });
});
</script>
</body>
</html>