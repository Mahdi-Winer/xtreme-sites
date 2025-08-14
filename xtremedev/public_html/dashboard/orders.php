<?php
session_start();
require_once __DIR__.'/../shared/inc/config.php';

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
    <title><?= $translations['my_orders'] ?? '' ?> | XtremeDev</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php include __DIR__ . '/../shared/inc/head-assets.php'; ?>
    <?php include __DIR__ . '/includes/dashboard-styles.php'; ?>
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
            background: var(--surface) !important;
            color: var(--text) !important;
            font-family: 'Vazirmatn', Tahoma, Arial, sans-serif;
            display: flex; flex-direction: column;
        }
        .main-content { flex: 1 0 auto; }
        .orders-section {
            max-width: 1000px;
            margin: 48px auto 30px auto;
            width: 100%;
            background: var(--surface-alt, #fff);
            border-radius: 18px;
            box-shadow: 0 2px 24px var(--shadow-card, #2499fa14);
            padding: 2.5rem 1.5rem 2rem 1.5rem;
            transition: background 0.25s;
        }
        .orders-title {
            color: var(--primary, #2499fa);
            font-size: 1.6rem;
            font-weight: 900;
            margin-bottom: 1.7rem;
            letter-spacing: .2px;
            text-align: <?= $is_rtl ? 'right' : 'left' ?>;
        }
        .order-table thead th {
            background: var(--surface-alt, #fff);
            color: #2499fa;
            font-weight: 800;
            border-bottom: 2px solid var(--border, #2499fa18);
        }
        .order-table tr td,
        .order-table tr th {
            vertical-align: middle;
            background: transparent;
            border-bottom: 1px solid #e8f0fa;
        }
        .order-status-badge {
            border-radius: 8px;
            font-size: 0.97rem;
            font-weight: 700;
            padding: 0.22rem 1.1rem;
            display: inline-block;
            color: #fff;
        }
        .badge-pending { background: #f8ae36; }
        .badge-paid { background: #35c452; }
        .badge-failed { background: #e13a3a; }
        .badge-canceled { background: #888; }
        .dark-theme .orders-section,
        .dark-theme .order-table,
        .dark-theme .order-table thead,
        .dark-theme .order-table tbody,
        .dark-theme .order-table tfoot,
        .dark-theme .order-table th,
        .dark-theme .order-table td,
        .dark-theme .order-table tr {
            background-color: #181f27 !important;
            color: #e6e9f2 !important;
        }
        .dark-theme .orders-section {
            box-shadow: 0 2px 24px #0d111c77;
        }
        .dark-theme .order-table thead th {
            color: #38a8ff !important;
            border-bottom: 2px solid #384c6e !important;
        }
        .dark-theme .order-table tr {
            border-bottom: 1px solid #384c6e !important;
        }
        @media (max-width: 991px) {
            .orders-section { padding: 1.2rem 0.3rem 1.2rem 0.3rem;}
        }
        html[dir="rtl"] .orders-title {text-align:right;}
        .skeleton-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
            margin-top: .7rem;
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
    <div class="container">
        <div class="orders-section">
            <div class="orders-title"><?= $translations['my_orders'] ?? '' ?></div>
            <div id="orders-skeleton">
                <div class="table-responsive mb-4">
                    <table class="skeleton-table" style="width:100%;">
                        <thead>
                        <tr>
                            <?php for($i=0;$i<5;$i++): ?><th></th><?php endfor; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php for($i=0;$i<4;$i++): ?>
                        <tr class="skeleton-row">
                            <?php for($j=0;$j<5;$j++): ?>
                                <td><div class="skeleton-cell"></div></td>
                            <?php endfor; ?>
                        </tr>
                        <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div id="orders-table-wrap" style="display:none;"></div>
            <div id="orders-none" style="color:#aaa;font-size:1.07rem;display:none;"><?= $translations['no_orders'] ?? '' ?></div>
        </div>
    </div>
</div>

<?php include 'includes/dashboard-footer.php'; ?>
<?php include __DIR__ . '/../shared/inc/foot-assets.php'; ?>
<?php if(file_exists('includes/theme-script.php')) include 'includes/theme-script.php'; ?>

<script>
var t = window.PAGE_TRANSLATIONS || {};
var lang = <?= json_encode($lang) ?>;

function escapeHtml(str) {
    return (str || "").replace(/[<>&"]/g, function(m) {
        return {'<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;'}[m];
    });
}

function renderOrders(orders) {
    const tableWrap = document.getElementById('orders-table-wrap');
    const ordersNone = document.getElementById('orders-none');
    if (!orders || !orders.length) {
        tableWrap.innerHTML = '';
        tableWrap.style.display = 'none';
        ordersNone.style.display = '';
        return;
    }
    ordersNone.style.display = 'none';
    tableWrap.style.display = '';
    let html = `
    <div class="table-responsive mb-4">
        <table class="table table-borderless align-middle order-table" style="font-size:1.06rem;">
            <thead>
            <tr>
                <th>${t['order_no']||''}</th>
                <th>${t['product']||''}</th>
                <th>${t['amount']||''}</th>
                <th>${t['status']||''}</th>
                <th>${t['order_date']||''}</th>
            </tr>
            </thead>
            <tbody>
    `;
    orders.forEach(o => {
        let badge = 'badge-pending';
        let statusKey = (o.status || '').toLowerCase();
        let status_label = t[statusKey] || o.status || '-';
        if (statusKey === 'paid') badge = 'badge-paid';
        else if (statusKey === 'failed') badge = 'badge-failed';
        else if (statusKey === 'canceled') badge = 'badge-canceled';

        let orderNo = o.order_id ? o.order_id.toString().padStart(6, '0') : '-';
        let productTitle = o.product_name || '-';
        let amount = o.invoice_amount !== null && o.invoice_amount !== undefined ? Number(o.invoice_amount).toLocaleString() + ' ' + (t['toman']||'') : '-';

        html += `
            <tr>
                <td>${escapeHtml(orderNo)}</td>
                <td>${escapeHtml(productTitle)}</td>
                <td>${amount}</td>
                <td>
                    <span class="order-status-badge ${badge}">${escapeHtml(status_label)}</span>
                </td>
                <td>${escapeHtml(o.created_at || '-')}</td>
            </tr>
        `;
    });

    html += `</tbody></table></div>`;
    tableWrap.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', function() {
    const skeleton = document.getElementById('orders-skeleton');
    const tableWrap = document.getElementById('orders-table-wrap');
    const ordersNone = document.getElementById('orders-none');
    tableWrap.style.display = 'none';
    ordersNone.style.display = 'none';
    skeleton.style.display = '';

    fetch('https://api.xtremedev.co/endpoints/my_orders.php?lang=' + encodeURIComponent(lang), {
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
            renderOrders(data);
        } else {
            tableWrap.innerHTML = '';
            tableWrap.style.display = 'none';
            ordersNone.style.display = '';
        }
    })
    .catch(err => {
        skeleton.style.display = 'none';
        tableWrap.innerHTML = '';
        tableWrap.style.display = 'none';
        ordersNone.style.display = '';
    });
});
</script>
</body>
</html>