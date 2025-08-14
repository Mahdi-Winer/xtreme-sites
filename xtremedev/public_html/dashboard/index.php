<?php
session_start();
require_once __DIR__ . '/../shared/inc/config.php';

// زبان فقط از کوکی
$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'en');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'en');
$is_rtl = $lang === 'fa';

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

// اطلاعات کاربر از SSO
$profile = $_SESSION['user_profile'];
$user = [
    'name' => $profile['fullname'] ?? '',
    'email' => $profile['email'] ?? '',
    'photo' => !empty($profile['photo']) ? $profile['photo'] : "../resources/default-avatar.png"
];
$access_token = $_SESSION['access_token'] ?? '';
$darkThemeActive = (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark');
?>
<!DOCTYPE html>
<html lang="<?=$lang?>" dir="<?=$is_rtl?'rtl':'ltr'?>">
<head>
    <meta charset="UTF-8">
    <title><?= $translations['dashboard'] ?? '' ?> | XtremeDev</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php include __DIR__ . '/../shared/inc/head-assets.php'; ?>
    <?php include __DIR__ . '/includes/dashboard-styles.php'; ?>
    <style>
        /* ... استایل‌ها عیناً ... */
        :root, body {
            --surface: #f4f7fa;
            --surface-alt: #fff;
            --text: #222;
            --primary: #2499fa;
            --shadow-card: #2499fa14;
            --shadow-hover: #2499fa2a;
            --border: #2499fa18;
            --border-hover: #2499fa44;
        }
        body.dark-theme {
            --surface: #181f2a;
            --surface-alt: #202b3b;
            --text: #e6e9f2;
            --primary: #2499fa;
            --shadow-card: #15203222;
            --shadow-hover: #1a5fc922;
            --border: #2499fa28;
            --border-hover: #2499fa66;
        }
        html, body { height: 100%; }
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: var(--surface) !important;
            color: var(--text) !important;
            font-family: Vazirmatn, Tahoma, Arial, sans-serif;
        }
        .main-content { flex: 1 0 auto; display: flex; flex-direction: column; }
        /* ... سایر استایل‌ها مثل قبل ... */
        .dashboard-welcome {
            font-size: 2.1rem;
            font-weight: 900;
            color: var(--primary);
            margin-bottom: 2.2rem;
            text-align: center;
            letter-spacing: .5px;
        }
        .dashboard-welcome span {
            color: #2bc551;
            font-weight: 900;
            letter-spacing: .6px;
        }
        .dashboard-cards-row {
            display: flex;
            gap: 2.2rem;
            justify-content: center;
            margin-bottom: 2.2rem;
            flex-wrap: wrap;
            max-width: 1100px;
            margin-left: auto;
            margin-right: auto;
        }
        .dashboard-card {
            flex: 1 1 260px;
            max-width: 330px;
            min-width: 220px;
            background: var(--surface-alt);
            border-radius: 18px;
            box-shadow: 0 4px 32px var(--shadow-card);
            border: 1.7px solid var(--border);
            padding: 2.2rem 1rem 1.4rem 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: box-shadow 0.2s, border-color 0.2s, background 0.3s;
            margin-bottom: 0.7rem;
        }
        .dashboard-card:hover {
            box-shadow: 0 8px 48px var(--shadow-hover);
            border-color: var(--border-hover);
            background: var(--surface-alt);
        }
        .dashboard-card .card-count {
            font-size: 2.6rem;
            font-weight: 900;
            margin-bottom: 0.3rem;
            line-height: 1.1;
        }
        .dashboard-card .card-label {
            font-size: 1.13rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 1.2rem;
            margin-top: 0.1rem;
            opacity: 0.93;
        }
        .dashboard-card .btn {
            font-weight: 700;
            width: 100%;
            margin-top: 1.2rem;
            border-radius: 11px;
            font-size: 1rem;
        }
        .dashboard-wide-card {
            background: var(--surface-alt);
            border-radius: 18px;
            box-shadow: 0 2px 24px var(--shadow-card);
            border: 1.5px solid var(--border);
            padding: 1.5rem 1rem 1.2rem 1rem;
            min-height: 320px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            max-width: 1100px;
            width: 100%;
            margin: 0 auto 2.5rem auto;
        }
        .dashboard-wide-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.2rem;
            gap: 1rem;
        }
        .dashboard-wide-title {
            font-weight: 800;
            color: var(--primary);
            font-size: 1.12rem;
            letter-spacing: .2px;
            text-align: left;
            margin-bottom: 0;
        }
        .dashboard-wide-btn {
            margin-bottom:0;
        }
        .joinus-table, .tickets-table {
            background: var(--surface-alt);
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 0.6rem;
        }
        .joinus-table thead th,
        .tickets-table thead th {
            background: var(--surface-alt);
            color: #2499fa;
            font-weight: 800;
            border-bottom: 2px solid var(--border);
        }
        .joinus-table tbody tr,
        .tickets-table tbody tr {
            background: transparent;
        }
        .joinus-table tr td,
        .joinus-table tr th,
        .tickets-table tr td,
        .tickets-table tr th {
            vertical-align: middle;
            background: transparent;
            border-bottom: 1px solid #e8f0fa;
        }
        .details-btn {
            padding: 0.32rem 0.9rem;
            border-radius: 7px;
            font-size: 0.97rem;
            font-weight: 700;
            background: #2499fa;
            color: #fff !important;
            border: none;
            transition: background 0.18s, color 0.18s;
            margin: 0;
            text-decoration: none;
            display: inline-block;
        }
        .details-btn:hover, .details-btn:focus {
            background: #38a8ff;
            color: #fff !important;
            text-decoration: none;
        }
        .dark-theme .joinus-table,
        .dark-theme .tickets-table {
            background: #222b38 !important;
            color: #e6e9f2;
        }
        .dark-theme .joinus-table thead th,
        .dark-theme .tickets-table thead th {
            background: #222b38 !important;
            color: #38a8ff !important;
            border-bottom: 2px solid #384c6e !important;
        }
        .dark-theme .joinus-table tr td,
        .dark-theme .tickets-table tr td {
            background: transparent !important;
            color: #e6e9f2 !important;
            border-bottom: 1px solid #384c6e !important;
        }
        .dark-theme .joinus-table tr th,
        .dark-theme .tickets-table tr th {
            background: transparent !important;
            color: #38a8ff !important;
        }
        .dark-theme .details-btn {
            background: #1a253b !important;
            color: #38a8ff !important;
        }
        .dark-theme .details-btn:hover, .dark-theme .details-btn:focus {
            background: #2499fa !important;
            color: #fff !important;
        }
        @media (max-width: 991px) {
            .dashboard-cards-row { gap: 1.1rem; }
            .dashboard-card { min-width: 170px; padding: 1.2rem 0.5rem 1rem 0.5rem;}
            .main-content { padding: 0 0.7rem;}
        }
        @media (max-width: 767px) {
            .dashboard-welcome { font-size: 1.4rem;}
            .dashboard-cards-row { flex-direction: column; align-items: center; }
            .dashboard-card { width: 100%; min-width: 0; max-width: 380px;}
            .dashboard-wide-card { width:100%; min-width:0;}
            .dashboard-wide-header { flex-direction: column; align-items: flex-start; gap:0.5rem;margin-bottom:1rem;}
            .dashboard-wide-title { text-align:left !important; }
            .dashboard-wide-btn { align-self: flex-end;}
        }
        .dark-theme .dashboard-card,
        .dark-theme .dashboard-wide-card {
            background: #222b38 !important;
            border-color: #384c6e !important;
            color: #e6e9f2 !important;
        }
        .dark-theme .dashboard-card .card-label,
        .dark-theme .dashboard-wide-title {
            color: #e6e9f2 !important;
        }
        .footer-main { margin-top: auto; border-radius: 36px 36px 0 0; width: 100%; background: var(--footer-bg); color: var(--footer-text); transition: background 0.4s, color 0.4s; position: relative; }
        .main-content { min-height: 77vh; }
        html[dir="rtl"] body, body[dir="rtl"] {
            font-family: Vazirmatn, Tahoma, Arial, sans-serif !important;
            direction: rtl;
        }
        html[dir="rtl"] .dashboard-wide-title,
        body[dir="rtl"] .dashboard-wide-title {
            text-align: right !important;
        }
        html[dir="rtl"] .dashboard-cards-row,
        body[dir="rtl"] .dashboard-cards-row {
            flex-direction: row-reverse;
        }
        html[dir="rtl"] .dashboard-card,
        body[dir="rtl"] .dashboard-card {
            text-align: right;
        }
        html[dir="rtl"] .joinus-table th, body[dir="rtl"] .joinus-table th,
        html[dir="rtl"] .joinus-table td, body[dir="rtl"] .joinus-table td {
            text-align: right;
        }
        html[dir="rtl"] .tickets-table th, body[dir="rtl"] .tickets-table th,
        html[dir="rtl"] .tickets-table td, body[dir="rtl"] .tickets-table td {
            text-align: right;
        }

        .dashboard-wide-card .no-joinus {
            color: #aaa;
            font-size: 1.07rem;
            padding: 0.7rem 0.3rem 0.2rem 0.3rem;
        }
        .joinus-requests-table {
            background: var(--surface-alt);
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 0.6rem;
        }
        .joinus-requests-table thead th {
            background: var(--surface-alt);
            color: #2499fa;
            font-weight: 800;
            border-bottom: 2px solid var(--border);
        }
        .joinus-requests-table tbody tr {
            background: transparent;
        }
        .joinus-requests-table tr td, .joinus-requests-table tr th {
            vertical-align: middle;
            background: transparent;
            border-bottom: 1px solid #e8f0fa;
        }
        .dark-theme .joinus-requests-table {
            background: #222b38 !important;
            color: #e6e9f2;
        }
        .dark-theme .joinus-requests-table thead th {
            background: #222b38 !important;
            color: #38a8ff !important;
            border-bottom: 2px solid #384c6e !important;
        }
        .dark-theme .joinus-requests-table tr td {
            background: transparent !important;
            color: #e6e9f2 !important;
            border-bottom: 1px solid #384c6e !important;
        }
        .dark-theme .joinus-requests-table tr th {
            background: transparent !important;
            color: #38a8ff !important;
        }
        .joinus-status-label {
            font-weight: 800;
            font-size: 1em;
        }
        .joinus-project-title, .joinus-role-title {
            font-size: 0.98em;
            font-weight: 600;
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
        <div class="dashboard-welcome mb-3">
            <?= ($translations['welcome'] ?? '') ?>, <span><?= htmlspecialchars($user['name'] ?: $user['email']) ?></span>
        </div>
        <div class="dashboard-cards-row">
            <div class="dashboard-card text-center">
                <div class="card-count" id="dashboard-product-count" style="color:#2b9bff;">-</div>
                <div class="card-label"><?= $translations['my_products'] ?? '' ?></div>
                <a href="my-products.php" class="btn btn-primary"><?= $translations['view_products'] ?? '' ?></a>
            </div>
            <div class="dashboard-card text-center">
                <div class="card-count" id="dashboard-order-count" style="color:#f88b0c;">-</div>
                <div class="card-label"><?= $translations['my_orders'] ?? '' ?></div>
                <a href="orders.php" class="btn btn-primary"><?= $translations['order_history'] ?? '' ?></a>
            </div>
            <div class="dashboard-card text-center">
                <div class="card-count" id="dashboard-invoice-sum" style="color:#46bf61;">- <span style="font-size:1.22rem;"><?= $translations['toman'] ?? '' ?></span></div>
                <div class="card-label"><?= $translations['total_invoices'] ?? '' ?></div>
                <a href="invoices.php" class="btn btn-primary"><?= $translations['invoices'] ?? '' ?></a>
            </div>
        </div>

        <div class="dashboard-wide-card mb-4">
            <div class="dashboard-wide-header">
                <div class="dashboard-wide-title"><?= $translations['tickets'] ?? '' ?></div>
                <a href="tickets.php" class="btn btn-outline-primary btn-sm dashboard-wide-btn"><?= $translations['all_tickets'] ?? '' ?></a>
            </div>
            <div class="table-responsive">
                <table class="table table-borderless align-middle mb-1 tickets-table" style="font-size:0.97rem;">
                    <thead>
                        <tr>
                            <th><?= $translations['subject'] ?? '' ?></th>
                            <th><?= $translations['status'] ?? '' ?></th>
                            <th><?= $translations['date'] ?? '' ?></th>
                            <th><?= $translations['details'] ?? '' ?></th>
                        </tr>
                    </thead>
                    <tbody id="dashboard-tickets-body">
                        <tr><td colspan="4"><?= $translations['loading'] ?? '' ?></td></tr>
                    </tbody>
                </table>
            </div>
            <div id="dashboard-no-tickets" style="color:#aaa;font-size:1.07rem;display:none;">
                <?= $translations['no_tickets'] ?? '' ?>
                <a href="new-ticket.php" class="btn btn-primary btn-sm mt-2"><?= $translations['open_ticket'] ?? '' ?></a>
            </div>
        </div>

        <div class="dashboard-wide-card mb-4" id="dashboard-last-joinus-card">
            <div class="dashboard-wide-header">
                <div class="dashboard-wide-title"><?= $translations['joinus_requests'] ?? '' ?></div>
                <a href="my-applications.php" class="btn btn-outline-primary btn-sm dashboard-wide-btn"><?= $translations['all_requests'] ?? '' ?></a>
            </div>
            <div class="table-responsive">
                <table class="table table-borderless align-middle mb-1 joinus-requests-table" style="font-size:0.97rem;">
                    <thead>
                        <tr>
                            <th><?= $translations['project'] ?? '' ?></th>
                            <th><?= $translations['role'] ?? '' ?></th>
                            <th><?= $translations['fullname'] ?? '' ?></th>
                            <th><?= $translations['skills'] ?? '' ?></th>
                            <th><?= $translations['status'] ?? '' ?></th>
                            <th><?= $translations['date'] ?? '' ?></th>
                            <th><?= $translations['cv'] ?? '' ?></th>
                            <th><?= $translations['details'] ?? '' ?></th>
                        </tr>
                    </thead>
                    <tbody id="dashboard-last-joinus-body">
                        <tr><td colspan="8"><?= $translations['loading'] ?? '' ?></td></tr>
                    </tbody>
                </table>
            </div>
            <div id="dashboard-no-last-joinus" class="no-joinus" style="display:none;">
                <?= $translations['no_joinus'] ?? '' ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/dashboard-footer.php'; ?>
<?php include __DIR__ . '/../shared/inc/foot-assets.php'; ?>
<?php if(file_exists('includes/theme-script.php')) include 'includes/theme-script.php'; ?>

<script>
var t = window.PAGE_TRANSLATIONS || {};
var token = <?= json_encode($access_token) ?>;
var lang = <?= json_encode($lang) ?>;

// تیکت‌ها و کارت‌های اصلی
document.addEventListener("DOMContentLoaded", function() {
    fetch('https://api.xtremedev.co/endpoints/user_dashboard.php', {
        headers: {
            'Authorization': 'Bearer ' + token
        }
    })
    .then(async res => {
        let txt = await res.text();
        let data;
        try { data = JSON.parse(txt); } catch(e){ data = null; }
        document.getElementById('dashboard-product-count').textContent = data && data.products_count !== undefined ? data.products_count : '-';
        document.getElementById('dashboard-order-count').textContent   = data && data.orders_count !== undefined ? data.orders_count : '-';
        document.getElementById('dashboard-invoice-sum').innerHTML    =
            (data && data.invoice_sum !== undefined ? data.invoice_sum : '0') +
            ' <span style="font-size:1.22rem;">' + (t['toman']||'') + '</span>';

        // Tickets
        let ticketsBody = document.getElementById('dashboard-tickets-body');
        let noTicketsDiv = document.getElementById('dashboard-no-tickets');
        if (data && data.last_tickets && data.last_tickets.length) {
            ticketsBody.innerHTML = '';
            noTicketsDiv.style.display = 'none';
            data.last_tickets.forEach(function(tk) {
                let color = tk.status=='open' ? '#ffa500' : (tk.status=='answered' ? '#2499fa' : '#aaa');
                let label = t[tk.status] || tk.status;
                ticketsBody.innerHTML += `
                    <tr>
                        <td><a href="ticket.php?id=${tk.ticket_id}" style="font-weight:700;">${(tk.subject||'').replace(/</g,"&lt;")}</a></td>
                        <td><span style="color:${color};font-weight:800;">${label}</span></td>
                        <td>${tk.created_at ? tk.created_at.substr(0,10).replace(/-/g, '/') : '-'}</td>
                        <td><a href="ticket.php?id=${tk.ticket_id}" class="details-btn">${t['details']||''}</a></td>
                    </tr>`;
            });
        } else {
            ticketsBody.innerHTML = '';
            noTicketsDiv.style.display = '';
        }
    })
    .catch(function() {
        document.getElementById('dashboard-product-count').textContent = '!';
        document.getElementById('dashboard-order-count').textContent = '!';
        document.getElementById('dashboard-invoice-sum').textContent = '!';
        document.getElementById('dashboard-tickets-body').innerHTML =
            `<tr><td colspan="4">${t['no_data']||''}</td></tr>`;
    });

    // JoinUs Requests
    fetch('https://api.xtremedev.co/endpoints/joinus_requests.php?lang=' + encodeURIComponent(lang), {
        headers: {
            'Authorization': 'Bearer ' + token
        }
    })
    .then(async res => {
        let txt = await res.text();
        let data;
        try { data = JSON.parse(txt); } catch(e){ data = null; }

        let joinusBody = document.getElementById('dashboard-last-joinus-body');
        let noJoinusDiv = document.getElementById('dashboard-no-last-joinus');
        joinusBody.innerHTML = '';
        noJoinusDiv.style.display = 'none';

        if (data && Array.isArray(data) && data.length > 0) {
            let maxRows = 5;
            let statusColors = {
                'pending': '#ffa500',
                'under_review': '#2499fa',
                'accepted': '#2bc551',
                'rejected': '#e33',
                '': '#bbb'
            };
            for (let i = 0; i < Math.min(data.length, maxRows); i++) {
                let a = data[i];
                let status = a.status || '';
                let statusLabel = t[status] || status;
                let statusColor = statusColors[status] || '#bbb';
                joinusBody.innerHTML += `
                    <tr>
                        <td>
                          <span class="joinus-project-title">${a.project_title ? a.project_title.replace(/</g,"&lt;") : '-'}</span>
                        </td>
                        <td>
                          <span class="joinus-role-title">${a.role_title ? a.role_title.replace(/</g,"&lt;") : '-'}</span>
                        </td>
                        <td>${a.fullname ? a.fullname.replace(/</g,"&lt;") : '-'}</td>
                        <td>${a.skills ? a.skills.replace(/</g,"&lt;") : '-'}</td>
                        <td><span class="joinus-status-label" style="color:${statusColor};">${statusLabel}</span></td>
                        <td>${a.created_at ? a.created_at.substr(0,10).replace(/-/g,"/") : '-'}</td>
                        <td>
                          ${a.cv_file ? `<a href="${a.cv_file}" target="_blank">${t['download']||''}</a>` : '<span style="color:#aaa;">-</span>'}
                        </td>
                        <td>
                          <a href="joinus-details.php?id=${a.id}" class="details-btn">${t['details']||''}</a>
                        </td>
                    </tr>
                `;
            }
        } else {
            joinusBody.innerHTML = '';
            noJoinusDiv.style.display = '';
        }
    })
    .catch(function() {
        let joinusBody = document.getElementById('dashboard-last-joinus-body');
        let noJoinusDiv = document.getElementById('dashboard-no-last-joinus');
        joinusBody.innerHTML = `<tr><td colspan="8">${t['no_data']||''}</td></tr>`;
        noJoinusDiv.style.display = '';
    });
});
</script>
</body>
</html>