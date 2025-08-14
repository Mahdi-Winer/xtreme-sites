<?php
session_start();
require_once __DIR__.'/../shared/inc/config.php';

// زبان
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
    <title><?=htmlspecialchars($translations['my_invoices'] ?? $translations['invoices'] ?? 'My Invoices')?> | XtremeDev</title>
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
            background: var(--surface, #f4f7fa) !important;
            color: var(--text, #222) !important;
            font-family: 'Vazirmatn', Tahoma, Arial, sans-serif;
            display: flex; flex-direction: column;
        }
        .main-content { flex: 1 0 auto; }
        .invoices-section {
            max-width: 1200px;
            margin: 48px auto 30px auto;
            width: 100%;
        }
        .invoices-title {
            color: var(--primary, #2499fa);
            font-size: 1.7rem;
            font-weight: 900;
            margin-bottom: 2.1rem;
            letter-spacing: .2px;
            text-align: <?= $is_rtl ? 'right' : 'left' ?>;
        }
        .invoices-table-responsive {
            width: 100%;
            overflow-x: auto;
            margin-bottom: 2rem;
            margin-top: 2.5rem;
            border-radius: 17px;
            box-shadow: 0 2px 16px var(--shadow-card);
            background: var(--surface-alt, #fff);
        }
        table.invoices-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
            background: transparent;
        }
        table.invoices-table th,
        table.invoices-table td {
            padding: 1.05rem .9rem;
            text-align: center;
        }
        table.invoices-table th {
            background: var(--surface, #f4f7fa);
            color: var(--primary, #2499fa);
            font-weight: 900;
            font-size: 1.04rem;
            border-bottom: 2px solid var(--border, #2499fa18);
        }
        table.invoices-table td {
            font-size: .99rem;
            color: var(--text);
            border-bottom: 1px solid var(--border, #2499fa18);
            vertical-align: middle;
        }
        .invoice-product-thumb {
            width: 46px;
            height: 46px;
            border-radius: 11px;
            object-fit: cover;
            box-shadow: 0 4px 16px #2499fa12;
            border: 1.2px solid #e3eefb;
            background: #e3eefb;
        }
        .invoice-status-label {
            display: inline-block;
            padding: .32rem .95rem;
            border-radius: 17px;
            font-size: .95rem;
            font-weight: 700;
            color: #fff;
            background: #2499fa;
        }
        .invoice-status-label.paid { background: #2bc551; }
        .invoice-status-label.unpaid { background: #ffb100; }
        .invoice-status-label.cancelled,.invoice-status-label.failed { background: #fa3d3d; }
        .invoice-status-label.refunded { background: #7b7b7b; }
        .invoice-status-label.processing { background: #2499fa; }
        .invoice-status-label.expired { background: #9b9b9b; }
        .pay-invoice-btn {
            display: inline-block;
            padding: .32rem 1.1rem;
            border-radius: 17px;
            font-size: .97rem;
            font-weight: 800;
            color: #fff !important;
            background: #2bc551;
            text-decoration: none;
            transition: background .2s;
            box-shadow: 0 1px 8px #2bc55122;
        }
        .pay-invoice-btn:hover { background: #23a044; }
        .dark-theme .pay-invoice-btn { background: #23a044; }
        .dark-theme .pay-invoice-btn:hover { background: #32e56c; color:#fff; }
        .dark-theme .invoices-table-responsive {background: #202b3b;}
        .dark-theme table.invoices-table th {background: #181f2a; color: #38a8ff;}
        .dark-theme table.invoices-table td {color: #e6e9f2;}
        .dark-theme .invoice-product-thumb {background: #1d2635; border-color: #293a59;}
        /* Skeleton Loader */
        .skeleton-table {margin-top:2.5rem;}
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
        @media (max-width: 800px) {
            .invoices-table-responsive { min-width: 0; }
            table.invoices-table { min-width: 520px;}
            .invoices-section {padding:0 2vw;}
        }
        @media (max-width: 600px) {
            .invoices-table-responsive {min-width:0;}
            table.invoices-table { min-width: 410px; font-size: .92rem;}
            .invoices-section {padding:0 1vw;}
            .invoices-title {font-size:1.33rem;}
        }
        html[dir="rtl"] .invoices-title {text-align: right;}
    </style>
</head>
<body<?= $darkThemeActive ? ' class="dark-theme"' : '' ?>>
<?php include 'includes/dashboard-navbar.php'; ?>
<div class="main-content">
    <div class="container">
        <div class="invoices-section">
            <div class="invoices-title"><?=htmlspecialchars($translations['my_invoices'] ?? $translations['invoices'] ?? 'My Invoices')?></div>
            <!-- Skeleton Loader -->
            <div id="invoices-skeleton" class="skeleton-table">
                <?php for($i=0;$i<3;$i++): ?>
                <div class="skeleton-row">
                    <div class="skeleton-cell" style="max-width:60px;"></div>
                    <div class="skeleton-cell" style="max-width:120px;"></div>
                    <div class="skeleton-cell" style="max-width:180px;"></div>
                    <div class="skeleton-cell" style="max-width:90px;"></div>
                    <div class="skeleton-cell" style="max-width:110px;"></div>
                    <div class="skeleton-cell" style="max-width:100px;"></div>
                    <div class="skeleton-cell" style="max-width:80px;"></div>
                </div>
                <?php endfor; ?>
            </div>
            <div id="invoices-table-wrap" style="display:none;"></div>
            <div id="invoices-none" style="color:#aaa;font-size:1.07rem;margin-bottom:2rem;display:none;">
                <?=htmlspecialchars($translations['no_invoices'] ?? 'No invoices found.')?>
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
const statusMap = {
    "paid": t.paid || "Paid",
    "unpaid": t.unpaid || "Unpaid",
    "cancelled": t.cancelled || "Cancelled",
    "failed": t.failed || "Failed",
    "refunded": t.refunded || "Refunded",
    "processing": t.processing || "Processing",
    "expired": t.expired || "Expired"
};
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
function renderInvoices(invoices) {
    let html = '';
    if (invoices && invoices.length) {
        html += '<div class="invoices-table-responsive"><table class="invoices-table">';
        html += '<thead><tr>';
        html += '<th>#</th>';
        html += `<th>${t.product||"Product"}</th>`;
        html += `<th>${t.amount||"Amount"}</th>`;
        html += `<th>${t.status||"Status"}</th>`;
        html += `<th>${t.payment_gateway||"Gateway"}</th>`;
        html += `<th>${t.paid_at||"Paid At"}</th>`;
        html += `<th>${t.date||"Date"}</th>`;
        html += `<th>${t.action||"Action"}</th>`;
        html += '</tr></thead><tbody>';
        invoices.forEach((inv, idx) => {
            let payBtn = '';
            if((inv.status||"").toLowerCase() === 'unpaid') {
                payBtn = `<a class="pay-invoice-btn" href="/dashboard/pay-invoice.php?id=${encodeURIComponent(inv.invoice_id)}">${t.pay||"Pay"}</a>`;
            }
            html += `<tr>
                <td>${invoices.length-idx}</td>
                <td>
                    <div style="display:flex;align-items:center;gap:9px;justify-content:center;">
                        <img class="invoice-product-thumb" src="${inv.product_thumbnail ? escapeHtml(inv.product_thumbnail) : '/shared/assets/images/default-product.png'}" loading="lazy" alt="">
                        <span style="font-weight:700;">${escapeHtml(inv.product_name||'-')}</span>
                    </div>
                </td>
                <td>${inv.amount ? (Number(inv.amount).toLocaleString()+' '+(t.toman||'Toman')) : '-'}</td>
                <td>
                    <span class="invoice-status-label ${escapeHtml((inv.status||'').toLowerCase())}">
                        ${statusMap[(inv.status||"").toLowerCase()] || escapeHtml(inv.status||'-')}
                    </span>
                </td>
                <td>${escapeHtml(inv.payment_gateway||'-')}</td>
                <td>${formatDate(inv.paid_at)}</td>
                <td>${formatDate(inv.created_at || inv.invoice_created_at)}</td>
                <td>${payBtn}</td>
            </tr>`;
        });
        html += '</tbody></table></div>';
        document.getElementById('invoices-table-wrap').innerHTML = html;
        document.getElementById('invoices-table-wrap').style.display = '';
        document.getElementById('invoices-none').style.display = 'none';
    } else {
        document.getElementById('invoices-table-wrap').innerHTML = '';
        document.getElementById('invoices-table-wrap').style.display = 'none';
        document.getElementById('invoices-none').style.display = '';
    }
}
document.addEventListener('DOMContentLoaded', function() {
    const skeleton = document.getElementById('invoices-skeleton');
    const tableWrap = document.getElementById('invoices-table-wrap');
    const noneEl = document.getElementById('invoices-none');
    tableWrap.style.display = 'none';
    noneEl.style.display = 'none';
    skeleton.style.display = 'block';

    fetch('https://api.xtremedev.co/endpoints/my_invoices.php?project_id=2&lang=' + encodeURIComponent(lang), {
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
            renderInvoices(data);
        } else {
            tableWrap.innerHTML = '';
            tableWrap.style.display = 'none';
            noneEl.style.display = '';
        }
    })
    .catch(err => {
        skeleton.style.display = 'none';
        tableWrap.innerHTML = '';
        tableWrap.style.display = 'none';
        noneEl.style.display = '';
    });
});
</script>
</body>
</html>