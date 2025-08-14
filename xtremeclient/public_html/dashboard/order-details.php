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
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$darkThemeActive = (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark');
?>
<!DOCTYPE html>
<html lang="<?=$lang?>" dir="<?=$is_rtl?'rtl':'ltr'?>">
<head>
    <meta charset="UTF-8">
    <title><?=htmlspecialchars($translations['order_details'] ?? 'Order Details')?> | XtremeDev</title>
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
        .order-details-section {
            max-width: 550px;
            margin: 44px auto 30px auto;
            width: 100%;
            background: var(--surface-alt, #fff);
            border-radius: 22px;
            box-shadow: 0 4px 32px var(--shadow-card, #2499fa14);
            border: 1.7px solid var(--border, #2499fa18);
            padding: 2.1rem 1.75rem 1.5rem 1.75rem;
            position: relative;
        }
        .order-header {
            display: flex;
            align-items: center;
            gap: 1.2rem;
            margin-bottom: 1.3rem;
        }
        .order-product-thumb {
            width: 82px;
            height: 82px;
            border-radius: 17px;
            object-fit: cover;
            box-shadow: 0 4px 24px #2499fa11;
            background: #e3eefb;
            border: 2px solid #e3eefb;
        }
        .order-product-info {
            flex: 1;
        }
        .order-product-title {
            font-size: 1.16rem;
            font-weight: 800;
            color: var(--primary, #2499fa);
            margin-bottom: .4rem;
        }
        .order-product-desc {
            color: var(--text, #222);
            font-size: .98rem;
            margin-bottom: .2rem;
        }
        .order-status-label {
            display: inline-block;
            padding: .32rem .95rem;
            border-radius: 17px;
            font-size: .97rem;
            font-weight: 700;
            color: #fff;
            background: #2499fa;
        }
        .order-status-label.completed { background: #2bc551; }
        .order-status-label.pending { background: #ffb100; }
        .order-status-label.cancelled,.order-status-label.failed { background: #fa3d3d; }
        .order-status-label.refunded { background: #7b7b7b; }
        .order-status-label.processing { background: #2499fa; }
        .order-status-label.expired { background: #9b9b9b; }
        .order-details-table {
            width: 100%;
            margin-top: 1.6rem;
        }
        .order-details-table th,
        .order-details-table td {
            text-align: <?= $is_rtl ? 'right' : 'left' ?>;
            font-size: .97rem;
            padding: .38rem 0;
            line-height: 1.7;
        }
        .order-details-table th {
            color: var(--primary, #2499fa);
            font-weight: 700;
            min-width: 110px;
        }
        .order-details-table td {
            color: var(--text, #222);
        }
        .order-back-link {
            color: var(--primary, #2499fa);
            text-decoration: none;
            font-size: 1.03rem;
            font-weight: 800;
            margin-bottom: 1.6rem;
            display: inline-block;
        }
        .dark-theme .order-details-section {background: #222b38;}
        .dark-theme .order-product-title {color: #38a8ff;}
        .dark-theme .order-product-desc {color: #b0c2d6;}
        .dark-theme .order-details-table td {color: #e6e9f2;}
        .dark-theme .order-details-table th {color: #38a8ff;}
        .dark-theme .order-back-link {color: #38a8ff;}
        @media (max-width: 700px) {
            .order-details-section {max-width:99vw; padding: 1.2rem .7rem;}
            .order-header {flex-direction:column; gap:.6rem;}
            .order-product-thumb {width:65px;height:65px;}
        }
    </style>
</head>
<body<?= $darkThemeActive ? ' class="dark-theme"' : '' ?>>
<?php include 'includes/dashboard-navbar.php'; ?>
<div class="main-content">
    <div class="container">
        <div class="order-details-section">
            <a href="orders.php" class="order-back-link">&larr; <?=htmlspecialchars($translations['back_to_orders'] ?? 'Back to Orders')?></a>
            <div id="order-details-loader" style="margin:2.4rem 0;text-align:center;">
                <span><?=htmlspecialchars($translations['loading'] ?? 'Loading...')?></span>
            </div>
            <div id="order-details-content" style="display:none;"></div>
            <div id="order-details-error" style="display:none;color:#d33;text-align:center;margin-top:2rem;font-weight:700;font-size:1.1rem;"></div>
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
    "completed": t.completed || "Completed",
    "processing": t.processing || "Processing",
    "pending": t.pending || "Pending",
    "cancelled": t.cancelled || "Cancelled",
    "failed": t.failed || "Failed",
    "refunded": t.refunded || "Refunded",
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
function renderOrderDetails(order) {
    let html = `<div class="order-header">
        <img class="order-product-thumb" src="${order.product_thumbnail ? escapeHtml(order.product_thumbnail) : '/shared/assets/images/default-product.png'}" alt="">
        <div class="order-product-info">
            <div class="order-product-title">${escapeHtml(order.product_name||'-')}</div>
            <div class="order-product-desc">${escapeHtml(order.product_desc||'')}</div>
            <div class="order-status-label ${escapeHtml((order.order_status||'').toLowerCase())}">
                ${statusMap[(order.order_status||"").toLowerCase()] || escapeHtml(order.order_status||'-')}
            </div>
        </div>
    </div>
    <table class="order-details-table">
        <tr>
            <th>${t.order_id || "Order ID"}</th>
            <td>${order.order_id}</td>
        </tr>
        <tr>
            <th>${t.date || "Date"}</th>
            <td>${formatDate(order.order_created_at)}</td>
        </tr>
        <tr>
            <th>${t.price || "Price"}</th>
            <td>${order.product_price ? (Number(order.product_price).toLocaleString()+' '+(t.toman||'Toman')) : '-'}</td>
        </tr>
        <tr>
            <th>${t.invoice_status || "Invoice Status"}</th>
            <td>${statusMap[(order.invoice_status||"").toLowerCase()] || escapeHtml(order.invoice_status||'-')}</td>
        </tr>
        <tr>
            <th>${t.payment_gateway || "Payment Gateway"}</th>
            <td>${escapeHtml(order.payment_gateway||'-')}</td>
        </tr>
        <tr>
            <th>${t.paid_at || "Paid At"}</th>
            <td>${formatDate(order.paid_at)}</td>
        </tr>
    </table>`;
    document.getElementById('order-details-content').innerHTML = html;
}
document.addEventListener('DOMContentLoaded', function() {
    const order_id = <?= (int)$order_id ?>;
    if(!order_id) {
        document.getElementById('order-details-loader').style.display = 'none';
        document.getElementById('order-details-error').innerText = t.invalid_order_id || 'Invalid order ID.';
        document.getElementById('order-details-error').style.display = '';
        return;
    }
    fetch('https://api.xtremedev.co/endpoints/order_details.php?id=' + order_id + '&lang=' + encodeURIComponent(lang), {
        headers: {
            'Authorization': 'Bearer <?=$access_token?>'
        }
    })
    .then(async res => {
        let text = await res.text();
        console.log("Server response:", text); // برای دیباگ
        let data;
        try { data = JSON.parse(text); }
        catch (e) { data = text; }
        document.getElementById('order-details-loader').style.display = 'none';
        if (data && !data.error) {
            renderOrderDetails(data);
            document.getElementById('order-details-content').style.display = '';
        } else {
            document.getElementById('order-details-error').innerText = t.order_not_found || 'Order not found.';
            document.getElementById('order-details-error').style.display = '';
        }
    })
    .catch(err => {
        document.getElementById('order-details-loader').style.display = 'none';
        document.getElementById('order-details-error').innerText = t.error_loading_order || 'Error loading order details.';
        document.getElementById('order-details-error').style.display = '';
    });
});
</script>
</body>
</html>