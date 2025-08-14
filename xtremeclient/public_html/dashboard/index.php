<?php
session_start();
require_once __DIR__ . '/../shared/inc/config.php';

// زبان فقط از کوکی
$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$is_rtl = $lang === 'fa';

// ترجمه از فایل json
$translations = [];
$lang_file = __DIR__ . '/../shared/assets/languages/' . $lang . '.json';
if (file_exists($lang_file)) {
    $json = file_get_contents($lang_file);
    $translations = json_decode($json, true);
}

// ------ SSO ------
if (!isset($_SESSION['user_profile'])) {
    $client_id = 'xtremedev-web';
    $redirect_uri = 'https://xtremeclient.com/oauth-callback.php';
    $state = bin2hex(random_bytes(8));
    $login_url = "https://auth.xtremedev.co/authorize.php?client_id=$client_id&redirect_uri=" . urlencode($redirect_uri) . "&response_type=code&scope=basic&state=$state";
    header("Location: $login_url");
    exit;
}

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
<html lang="<?= htmlspecialchars($lang) ?>" dir="<?= $is_rtl ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($translations['dashboard'] ?? 'Dashboard') ?> | XtremeDev</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php include __DIR__ . '/../shared/inc/head-assets.php'; ?>
    <style>
        :root {
            --surface: #f4f7fa;
            --surface-alt: #fff;
            --text: #222;
            --primary: #2499fa;
            --shadow-card: #2499fa14;
            --border: #2499fa18;
            --table-border: #c8d0e7;
            --btn-navy: #24325b;
            --btn-navy-hover: #2d45a0;
        }
        body.dark-theme {
            --surface: #181f2a;
            --surface-alt: #202b3b;
            --text: #e6e9f2;
            --shadow-card: #15203222;
            --border: #2499fa28;
            --table-border: #333d56;
            --btn-navy: #24325b;
            --btn-navy-hover: #2d45a0;
        }
        body {
            font-family: Vazirmatn, Tahoma, Arial, sans-serif;
            background: var(--surface);
            color: var(--text);
            min-height: 100vh;
        }
        .dashboard-welcome {
            font-size: 2.1rem;
            font-weight: bold;
            color: var(--primary, #2499fa);
            text-align: center;
            margin-bottom: 2rem;
        }
        .dashboard-welcome span {
            color: #2bc551;
            font-weight: bold;
        }
        .dashboard-cards-row {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            justify-content: center;
            margin-bottom: 2rem;
        }
        .dashboard-card {
            background: var(--surface-alt);
            border-radius: 18px;
            box-shadow: 0 4px 32px var(--shadow-card);
            border: 1.7px solid var(--border);
            padding: 2rem 1rem 1.3rem 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 220px;
            max-width: 330px;
            flex: 1 1 260px;
        }
        .dashboard-card .card-count {
            font-size: 2.2rem;
            font-weight: 900;
        }
        .dashboard-card .card-label {
            font-size: 1.13rem;
            font-weight: 600;
            margin: 0.7rem 0 1rem 0;
            opacity: 0.93;
        }
        .dashboard-wide-card {
            background: var(--surface-alt);
            border-radius: 18px;
            box-shadow: 0 2px 24px var(--shadow-card);
            border: 1.5px solid var(--border);
            padding: 1.5rem 1rem 1.2rem 1rem;
            margin: 0 auto 2rem auto;
            max-width: 1100px;
            width: 100%;
        }
        .dashboard-wide-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .dashboard-wide-title {
            font-weight: 800;
            color: var(--primary,#2499fa);
            font-size: 1.12rem;
        }
        .dashboard-wide-btn {
            min-width: 110px;
        }
        /* جدول‌ها */
        .dashboard-table-wrap {
            width: 100%;
            overflow-x: auto;
            margin-top: 1.3rem;
            margin-bottom: 0.5rem;
            border-radius: 13px;
            background: var(--surface-alt);
            box-shadow: 0 2px 14px var(--shadow-card);
        }
        table.dashboard-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 410px;
            background: transparent;
        }
        table.dashboard-table th,
        table.dashboard-table td {
            padding: 0.82rem .6rem;
            text-align: center;
        }
        table.dashboard-table th {
            background: var(--surface, #f4f7fa);
            color: var(--primary, #2499fa);
            font-weight: 900;
            font-size: 1.04rem;
            border-bottom: 2px solid var(--border, #2499fa18);
        }
        table.dashboard-table td {
            font-size: .99rem;
            color: var(--text);
            border-bottom: 1px solid var(--border, #2499fa18);
            vertical-align: middle;
        }
        table.dashboard-table tbody tr:not(:last-child) {
            border-bottom: 2.5px solid var(--table-border);
        }
        /* دکمه جزئیات */
        .btn-info {
            background: var(--btn-navy) !important;
            color: #fff !important;
            border: 0;
            font-weight: 700;
            box-shadow: 0 2px 8px #1a274a50;
            transition: background 0.18s,box-shadow 0.18s;
            padding: 7px 20px;
            border-radius: 10px;
            font-size: .96rem;
        }
        .btn-info:hover, .btn-info:focus {
            background: var(--btn-navy-hover) !important;
            color: #fff !important;
            box-shadow: 0 2px 14px #2d45a090;
        }
        .order-status-label {
            display: inline-block;
            padding: .32rem .95rem;
            border-radius: 17px;
            font-size: .95rem;
            font-weight: 700;
            color: #fff;
            background: #2499fa;
        }
        .order-status-label.open { background: #ffb100; }
        .order-status-label.answered { background: #2499fa; }
        .order-status-label.closed { background: #aaa; }
        @media (max-width: 991px) {
            .dashboard-cards-row { gap: 1.1rem; }
            .dashboard-card { min-width: 170px; padding: 1.2rem 0.5rem 1rem 0.5rem;}
        }
        @media (max-width: 767px) {
            .dashboard-cards-row { flex-direction: column; align-items: center; }
            .dashboard-card { width: 100%; min-width: 0; max-width: 380px;}
            .dashboard-wide-card { width:100%; min-width:0;}
            table.dashboard-table th, table.dashboard-table td {
                font-size: 0.93rem;
                padding: 0.5rem 0.23rem;
            }
        }
        html[dir="rtl"] .dashboard-card, html[dir="rtl"] .dashboard-wide-card { text-align: right;}
        html[dir="rtl"] .dashboard-cards-row { flex-direction: row-reverse;}
    </style>
</head>
<body<?= $darkThemeActive ? ' class="dark-theme"' : '' ?>>
<?php include 'includes/dashboard-navbar.php'; ?>

<div class="main-content">
    <div class="container" style="max-width:1100px;padding-top:38px;padding-bottom:16px;">
        <div class="dashboard-welcome">
            <?= htmlspecialchars($translations['welcome'] ?? 'Welcome') ?>,
            <span><?= htmlspecialchars($user['name'] ?: $user['email']) ?></span>
        </div>

        <!-- کارت‌های اصلی -->
        <div class="dashboard-cards-row">
            <!-- پلی تایم من -->
            <div class="dashboard-card text-center">
                <div class="card-count" id="dashboard-playtime" style="color:#2b9bff;">-</div>
                <div class="card-label"><?= htmlspecialchars($translations['my_playtime'] ?? '') ?></div>
                <a href="playtime.php" class="btn btn-primary"><?= htmlspecialchars($translations['view_playtime'] ?? '') ?></a>
            </div>
            <!-- سفارشات -->
            <div class="dashboard-card text-center">
                <div class="card-count" id="dashboard-order-count" style="color:#f88b0c;">-</div>
                <div class="card-label"><?= htmlspecialchars($translations['my_orders'] ?? '') ?></div>
                <a href="orders.php" class="btn btn-primary"><?= htmlspecialchars($translations['order_history'] ?? '') ?></a>
            </div>
            <!-- فاکتورها -->
            <div class="dashboard-card text-center">
                <div class="card-count" id="dashboard-invoice-sum" style="color:#46bf61;">- <span style="font-size:1.22rem;"><?= htmlspecialchars($translations['toman'] ?? '') ?></span></div>
                <div class="card-label"><?= htmlspecialchars($translations['total_invoices'] ?? '') ?></div>
                <a href="invoices.php" class="btn btn-primary"><?= htmlspecialchars($translations['invoices'] ?? '') ?></a>
            </div>
        </div>

        <!-- ۵ سشن اخیر -->
        <div class="dashboard-wide-card">
            <div class="dashboard-wide-header">
                <div class="dashboard-wide-title"><?= htmlspecialchars($translations['recent_sessions'] ?? '') ?></div>
            </div>
            <div class="dashboard-table-wrap">
                <table class="dashboard-table" id="game-sessions-table">
                    <thead>
                        <tr>
                            <th><?= htmlspecialchars($translations['session_start'] ?? '') ?></th>
                            <th><?= htmlspecialchars($translations['session_end'] ?? '') ?></th>
                            <th><?= htmlspecialchars($translations['session_duration'] ?? '') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="3"><?= htmlspecialchars($translations['loading'] ?? '') ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- جدول ۳ نام کاربری اخیر -->
        <div class="dashboard-wide-card">
            <div class="dashboard-wide-header">
                <div class="dashboard-wide-title"><?= htmlspecialchars($translations['recent_ingame_names'] ?? '') ?></div>
            </div>
            <div class="dashboard-table-wrap">
                <table class="dashboard-table" id="ingame-names-table">
                    <thead>
                        <tr>
                            <th><?= htmlspecialchars($translations['ingame_username'] ?? 'Username') ?></th>
                            <th><?= htmlspecialchars($translations['used_at'] ?? 'Used At') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="2"><?= htmlspecialchars($translations['loading'] ?? '') ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- تیکت‌ها -->
        <div class="dashboard-wide-card">
            <div class="dashboard-wide-header">
                <div class="dashboard-wide-title"><?= htmlspecialchars($translations['tickets'] ?? '') ?></div>
                <a href="tickets.php" class="btn btn-outline-primary btn-sm dashboard-wide-btn"><?= htmlspecialchars($translations['all_tickets'] ?? '') ?></a>
            </div>
            <div class="dashboard-table-wrap">
                <table class="dashboard-table" id="dashboard-tickets-table">
                    <thead>
                        <tr>
                            <th><?= htmlspecialchars($translations['subject'] ?? '') ?></th>
                            <th><?= htmlspecialchars($translations['status'] ?? '') ?></th>
                            <th><?= htmlspecialchars($translations['date'] ?? '') ?></th>
                            <th><?= htmlspecialchars($translations['details'] ?? '') ?></th>
                        </tr>
                    </thead>
                    <tbody id="dashboard-tickets-body">
                        <tr><td colspan="4"><?= htmlspecialchars($translations['loading'] ?? '') ?></td></tr>
                    </tbody>
                </table>
            </div>
            <div id="dashboard-no-tickets" style="color:#aaa;font-size:1.07rem;display:none;">
                <?= htmlspecialchars($translations['no_tickets'] ?? '') ?>
                <a href="new-ticket.php" class="btn btn-primary btn-sm mt-2"><?= htmlspecialchars($translations['open_ticket'] ?? '') ?></a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/dashboard-footer.php'; ?>
<?php include __DIR__ . '/../shared/inc/foot-assets.php'; ?>
<?php if(file_exists('includes/theme-script.php')) include 'includes/theme-script.php'; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    let token = <?= json_encode($access_token) ?>;
    let tr = <?= json_encode($translations, JSON_UNESCAPED_UNICODE) ?>;

    function logApiResponse(apiName, response) {
        // console.log(`[API] ${apiName}:`, response);
    }

    function formatDurationFa(seconds) {
        seconds = Number(seconds) || 0;
        let h = Math.floor(seconds / 3600);
        let m = Math.floor((seconds % 3600) / 60);
        let s = seconds % 60;
        let parts = [];
        if (h) parts.push(h + ' ' + (tr.playtime_hour || 'ساعت'));
        if (m) parts.push(m + ' ' + (tr.playtime_minute || 'دقیقه'));
        if (s || parts.length === 0) parts.push(s + ' ' + (tr.playtime_second || 'ثانیه'));
        return parts.join(' ' + (tr.and || 'و') + ' ');
    }

    // ---- پلی تایم من ----
    fetch('https://api.xtremedev.co/endpoints/client/playtime_get.php', {
        headers: { 'Authorization': 'Bearer ' + token }
    })
    .then(res => res.json())
    .then(data => {
        let seconds = data && data.playtime && data.playtime.total_playtime !== undefined
            ? data.playtime.total_playtime
            : 0;
        document.getElementById('dashboard-playtime').textContent = formatDurationFa(seconds);
    });

    // ---- سفارشات و فاکتورها و تیکت‌ها ----
    fetch('https://api.xtremedev.co/endpoints/user_dashboard.php', {
        headers: { 'Authorization': 'Bearer ' + token }
    })
    .then(async res => {
        let txt = await res.text();
        let data;
        try { data = JSON.parse(txt); } catch(e){ data = null; }
        document.getElementById('dashboard-order-count').textContent = data && data.orders_count !== undefined ? data.orders_count : '-';
        document.getElementById('dashboard-invoice-sum').innerHTML =
            (data && data.invoice_sum !== undefined ? data.invoice_sum : '0') +
            ' <span style="font-size:1.22rem;"><?= htmlspecialchars($translations['toman'] ?? '') ?></span>';

        let ticketsBody = document.getElementById('dashboard-tickets-body');
        let noTicketsDiv = document.getElementById('dashboard-no-tickets');
        if (data && data.last_tickets && data.last_tickets.length) {
            ticketsBody.innerHTML = '';
            noTicketsDiv.style.display = 'none';
            data.last_tickets.forEach(function(t) {
                let color = t.status=='open' ? '#ffa500' : (t.status=='answered' ? '#2499fa' : '#aaa');
                let label = tr[t.status] || t.status;
                ticketsBody.innerHTML += `
                    <tr>
                        <td><a href="ticket.php?id=${t.ticket_id}" style="font-weight:700;">${t.subject.replace(/</g,"&lt;")}</a></td>
                        <td><span class="order-status-label ${t.status}">${label}</span></td>
                        <td>${t.created_at ? t.created_at.substr(0,10).replace(/-/g, '/') : '-'}</td>
                        <td><a href="ticket.php?id=${t.ticket_id}" class="btn btn-sm btn-info">${tr['details'] || 'Details'}</a></td>
                    </tr>`;
            });
        } else {
            ticketsBody.innerHTML = '';
            noTicketsDiv.style.display = '';
        }
    })
    .catch(function(err) {
        document.getElementById('dashboard-order-count').textContent = '!';
        document.getElementById('dashboard-invoice-sum').textContent = '!';
        document.getElementById('dashboard-tickets-body').innerHTML =
            `<tr><td colspan="4">${tr['no_data'] || '-'}</td></tr>`;
    });

    // ---- ۵ سشن اخیر ----
    fetch('https://api.xtremedev.co/endpoints/client/session_get_all.php?limit=5', {
        headers: { 'Authorization': 'Bearer ' + token }
    })
    .then(res => res.json())
    .then(data => {
        let tbody = document.querySelector('#game-sessions-table tbody');
        if (data && data.sessions && data.sessions.length) {
            tbody.innerHTML = '';
            data.sessions.forEach(function(s) {
                tbody.innerHTML += `
                    <tr>
                        <td>${s.login_time || '-'}</td>
                        <td>${s.logout_time || '-'}</td>
                        <td>${(s.playtime !== undefined && s.playtime !== null) ? formatDurationFa(s.playtime) : '-'}</td>
                    </tr>
                `;
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="3">${tr['no_sessions'] || '-'}</td></tr>`;
        }
    });

    // ---- جدول ۳ نام کاربری اخیر ----
    fetch('https://api.xtremedev.co/endpoints/client/gamename_get_all.php?limit=3', {
        headers: { 'Authorization': 'Bearer ' + token }
    })
    .then(res => res.json())
    .then(data => {
        let tbody = document.querySelector('#ingame-names-table tbody');
        if (data && data.gamenames && data.gamenames.length) {
            tbody.innerHTML = '';
            data.gamenames.forEach(function(g) {
                tbody.innerHTML += `
                    <tr>
                        <td>${g.ingame_username || '-'}</td>
                        <td>${g.used_at ? g.used_at.replace('T', ' ').substring(0, 19) : '-'}</td>
                    </tr>
                `;
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="2">${tr['no_ingame_names'] || '-'}</td></tr>`;
        }
    });
});
</script>
</body>
</html>