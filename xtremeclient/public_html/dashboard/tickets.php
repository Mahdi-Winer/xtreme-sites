<?php
session_start();
require_once __DIR__.'/../shared/inc/config.php';

// تعیین زبان
$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : 'fa';
$is_rtl = ($lang === 'fa');

// ترجمه‌ها از فایل json
$translations = [];
$lang_file = __DIR__ . '/../shared/assets/languages/' . $lang . '.json';
if (file_exists($lang_file)) {
    $json = file_get_contents($lang_file);
    $translations = json_decode($json, true);
}

// چک لاگین
if(!isset($_SESSION['user_profile'])) {
    $client_id = 'xtremedev-web';
    $redirect_uri = 'https://xtremedev.co/oauth-callback.php';
    $state = bin2hex(random_bytes(8));
    $login_url = "https://auth.xtremedev.co/authorize.php?client_id=$client_id&redirect_uri=" . urlencode($redirect_uri) . "&response_type=code&scope=basic&state=$state";
    header("Location: $login_url");
    exit;
}
$access_token = $_SESSION['access_token'] ?? '';
$darkThemeActive = (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark');
?>
<!DOCTYPE html>
<html lang="<?=$lang?>" dir="<?=$is_rtl?'rtl':'ltr'?>">
<head>
    <meta charset="UTF-8">
    <title><?=htmlspecialchars($translations['tickets'] ?? 'Tickets')?> | XtremeDev</title>
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
        body.dark-theme {
            --surface: #181f2a;
            --surface-alt: #202b3b;
            --text: #e6e9f2;
            --primary: #2499fa;
            --shadow-card: #15203222;
            --border: #2499fa28;
        }
        body {
            min-height: 100vh;
            background: var(--surface, #f4f7fa) !important;
            color: var(--text, #222) !important;
            font-family: 'Vazirmatn', Tahoma, Arial, sans-serif;
            display: flex;
            flex-direction: column;
        }
        .main-content { flex: 1 0 auto; }
        .tickets-section {
            max-width: 1100px;
            margin: 48px auto 30px auto;
            width: 100%;
        }
        .tickets-title {
            color: var(--primary, #2499fa);
            font-size: 1.7rem;
            font-weight: 900;
            margin-bottom: 2.1rem;
            letter-spacing: .2px;
            text-align: <?= $is_rtl ? 'right' : 'left' ?>;
        }
        .tickets-card {
            background: var(--surface-alt, #fff);
            border-radius: 18px;
            box-shadow: 0 2px 24px var(--shadow-card, #2499fa14);
            border: 1.5px solid var(--border, #2499fa18);
            padding: 1.7rem 1rem 1.2rem 1rem;
            min-height: 340px;
            display: flex;
            flex-direction: column;
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
        .tickets-table-responsive {
            width: 100%;
            overflow-x: auto;
            margin-bottom: 2rem;
            margin-top: 2.5rem;
            border-radius: 17px;
            box-shadow: 0 2px 16px var(--shadow-card);
            background: var(--surface-alt, #fff);
        }
        table.tickets-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
            background: transparent;
        }
        table.tickets-table th,
        table.tickets-table td {
            padding: 1.05rem .9rem;
            text-align: center;
        }
        table.tickets-table th {
            background: var(--surface, #f4f7fa);
            color: var(--primary, #2499fa);
            font-weight: 900;
            font-size: 1.04rem;
            border-bottom: 2px solid var(--border, #2499fa18);
        }
        table.tickets-table td {
            font-size: .99rem;
            color: var(--text);
            border-bottom: 1px solid var(--border, #2499fa18);
            vertical-align: middle;
        }
        .status-label {
            display: inline-block;
            padding: .23rem .95rem;
            border-radius: 17px;
            font-size: .96rem;
            font-weight: 700;
            color: #fff;
        }
        .status-label.open { background: #ffb100; }
        .status-label.answered { background: #2499fa; }
        .status-label.closed { background: #7b7b7b; }
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
        body.dark-theme .tickets-table-responsive {
            background: var(--surface-alt) !important;
            color: var(--text) !important;
            box-shadow: 0 2px 24px #0d111c77;
            border-color: #384c6e !important;
        }
        body.dark-theme .tickets-table th {
            background: #181f2a; color: #38a8ff;
        }
        body.dark-theme .tickets-table td {color: #e6e9f2;}
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
            .tickets-title { font-size: 1.3rem;}
            .tickets-card { padding: 1.1rem 0.4rem 1.2rem 0.4rem; }
            .tickets-header-row { flex-direction: column; align-items: stretch; gap: 0.5rem;}
            .tickets-card h2 { margin-bottom: 0.3rem; }
            .create-ticket-btn { width: 100%; }
        }
        html[dir="rtl"] .tickets-title {text-align:right;}
        html[dir="rtl"] .tickets-card h2 {text-align:right;}
        /* Skeleton Loader */
        .skeleton-table {margin-top:2.5rem;width:100%;}
        .skeleton-row {
            display: flex;
            gap: 1.2rem;
            margin-bottom: 1.07rem;
            align-items: center;
            width: 100%;
        }
        .skeleton-cell {
            flex: 1 1 0;
            height: 28px;
            border-radius: 8px;
            background: linear-gradient(90deg, #f4f7fa 25%, #e6edf5 50%, #f4f7fa 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.13s infinite linear;
        }
        .dark-theme .skeleton-cell {
            background: linear-gradient(90deg, #232d3b 25%, #1e2632 50%, #232d3b 75%);
        }
        @keyframes skeleton-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
</head>
<body<?= $darkThemeActive ? ' class="dark-theme"' : '' ?>>
<?php include 'includes/dashboard-navbar.php'; ?>
<div class="main-content">
    <div class="container">
        <div class="tickets-section">
            <div class="tickets-title"><?=htmlspecialchars($translations['tickets'] ?? 'Tickets')?></div>
            <div class="tickets-card">
                <div class="tickets-header-row">
                    <h2><?=htmlspecialchars($translations['all_tickets'] ?? $translations['tickets'] ?? 'All Tickets')?></h2>
                    <a href="new-ticket.php" class="create-ticket-btn"><?=htmlspecialchars($translations['open_ticket'] ?? 'Open New Ticket')?></a>
                </div>
                <!-- Skeleton Loader -->
                <div id="tickets-skeleton" class="skeleton-table">
                    <?php for($i=0;$i<4;$i++): ?>
                        <div class="skeleton-row">
                            <div class="skeleton-cell" style="max-width:260px"></div>
                            <div class="skeleton-cell" style="max-width:110px"></div>
                            <div class="skeleton-cell" style="max-width:120px"></div>
                            <div class="skeleton-cell" style="max-width:60px"></div>
                        </div>
                    <?php endfor; ?>
                </div>
                <div id="tickets-table-wrap" style="display:none;"></div>
                <div id="tickets-none" style="color:#aaa;font-size:1.07rem;display:none;"><?=htmlspecialchars($translations['no_tickets'] ?? 'No tickets found.')?></div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/dashboard-footer.php'; ?>
<?php include __DIR__ . '/../shared/inc/foot-assets.php'; ?>
<?php if(file_exists('includes/theme-script.php')) include 'includes/theme-script.php'; ?>

<script>
const t = <?= json_encode($translations, JSON_UNESCAPED_UNICODE) ?>;
const lang = <?= json_encode($lang) ?>;
function escapeHtml(str) {
    return (str || "").replace(/[<>&"]/g, function(m) {
        return {'<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;'}[m];
    });
}
function formatDate(iso) {
    if (!iso) return "-";
    const d = new Date(iso.replace(" ","T"));
    if (isNaN(d.getTime())) return "-";
    return d.toLocaleDateString(lang, {year:'numeric',month:'short',day:'numeric'}) +
        " " + d.toLocaleTimeString(lang, {hour:'2-digit',minute:'2-digit'});
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
    <div class="tickets-table-responsive">
        <table class="tickets-table">
            <thead>
            <tr>
                <th>${t.subject||'Subject'}</th>
                <th>${t.status||'Status'}</th>
                <th>${t.date||'Date'}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
    `;
    tickets.forEach(tk => {
        let status = (tk.status||"").toLowerCase();
        let label = t[status] || escapeHtml(tk.status||'-');
        html += `<tr>
            <td>${escapeHtml(tk.subject)}</td>
            <td><span class="status-label ${status}">${escapeHtml(label)}</span></td>
            <td>${formatDate(tk.created_at)}</td>
            <td>
                <a href="ticket.php?id=${encodeURIComponent(tk.ticket_id)}" class="details-btn">${t.details||'Details'}</a>
            </td>
        </tr>`;
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
        console.log("Server response:", text); // نمایش ریسپانس سرور در کنسول
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