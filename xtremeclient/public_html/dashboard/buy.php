<?php
session_start();
require_once __DIR__ . '/../shared/inc/config.php';

// زبان و راست‌به‌چپ
$lang = $_COOKIE['site_lang'] ?? (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$is_rtl = $lang === 'fa';

// ترجمه
$translations = [];
$lang_file = __DIR__ . '/../shared/assets/languages/' . $lang . '.json';
if (file_exists($lang_file)) {
    $json = file_get_contents($lang_file);
    $translations = json_decode($json, true);
}
function t($k) {
    global $translations;
    return isset($translations[$k]) ? $translations[$k] : '';
}

// ------ SSO ------
if (!isset($_SESSION['user_profile'])) {
    $client_id = 'xtremeclient-web';
    $redirect_uri = 'https://xtremeclient.com/oauth-callback.php';
    $state = bin2hex(random_bytes(8));
    $login_url = "https://auth.xtremedev.co/authorize.php?client_id=$client_id&redirect_uri=" . urlencode($redirect_uri) . "&response_type=code&scope=basic&state=$state";
    header("Location: $login_url");
    exit;
}

$access_token = $_SESSION['access_token'] ?? '';
$profile = $_SESSION['user_profile'];
$darkThemeActive = (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark');

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$product_id) {
    header('Location: shop.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>" dir="<?= $is_rtl ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars(t('buy_and_download_now') ?: t('buy')) ?> | XtremeDev</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php include __DIR__ . '/../shared/inc/head-assets.php'; ?>
    <?php include __DIR__ . '/includes/dashboard-styles.php'; ?>
    <style>
    body {
        font-family: Vazirmatn, Tahoma, Arial, sans-serif;
        background: var(--surface, #f4f7fa);
        color: var(--text, #222);
        min-height: 100vh;
    }
    body.dark-theme {
        background: var(--surface, #181f2a) !important;
        color: var(--text, #e6e9f2) !important;
    }
    .main-content { flex: 1 0 auto; }
    .dashboard-buy-card {
        max-width: 420px;
        margin: 56px auto 30px auto;
        background: var(--surface-alt, #fff);
        border-radius: 16px;
        box-shadow: 0 2px 24px var(--shadow-card, #2499fa14);
        border: 1.5px solid var(--border, #2499fa18);
        padding: 2.1rem 1.1rem 2.1rem 1.1rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        min-height: 220px;
        position: relative;
        transition: box-shadow 0.2s, background 0.2s, color 0.2s;
    }
    /* دارک مود برای کارت */
    body.dark-theme .dashboard-buy-card {
        background: #232d3a;
        color: #e6e9f2;
        border: 1.5px solid #2a3546;
        box-shadow: 0 2px 24px #10151b44;
    }
    .buy-title {
        color: var(--primary, #2499fa);
        font-size: 1.18rem;
        font-weight: 900;
        margin-bottom: 1.5rem;
        text-align: center;
    }
    .buy-status-message {
        font-size: 1.08rem;
        color: #16a34a;
        margin-bottom: 1.5rem;
        text-align: center;
        font-weight: 700;
        word-break: break-word;
    }
    .buy-error-message {
        font-size: 1.08rem;
        color: #e63946;
        margin-bottom: 1.5rem;
        text-align: center;
        font-weight: 700;
        word-break: break-word;
    }
    .pay-btn {
        background: #16a34a;
        color: #fff;
        border: none;
        border-radius: 10px;
        font-weight: bold;
        font-size: 1.07rem;
        padding: 0.7em 1.5em;
        cursor: pointer;
        box-shadow: 0 1px 6px #16a34a18;
        transition: background 0.15s, box-shadow 0.17s;
        margin-bottom: 0.7rem;
        width: 100%;
        max-width: 220px;
        display: none;
    }
    .pay-btn:hover, .pay-btn:focus {
        background: #0e8938;
    }
    .back-shop-link {
        margin-top: 1.7rem;
        display: block;
        min-width: 120px;
        font-size: 1.01rem;
        font-weight: 700;
        border-radius: 10px;
        background: #e4e7ef;
        color: #2499fa;
        border: none;
        transition: background 0.18s;
        padding: 0.7rem 1.5rem;
        text-align: center;
        text-decoration: none;
    }
    .back-shop-link:hover, .back-shop-link:focus {
        background: #d1e9ff;
        color: #145d99;
    }
    body.dark-theme .back-shop-link {
        background: #243447;
        color: #aad8ff;
    }
    body.dark-theme .back-shop-link:hover, body.dark-theme .back-shop-link:focus {
        background: #1c2839;
        color: #83bfff;
    }
    @media (max-width: 600px) {
        .dashboard-buy-card { padding: 1.3rem 0.7rem 1.1rem 0.7rem; }
    }
    html[dir="rtl"] .dashboard-buy-card { text-align: right;}
    </style>
</head>
<body<?= $darkThemeActive ? ' class="dark-theme"' : '' ?>>
<?php include 'includes/dashboard-navbar.php'; ?>

<div class="main-content">
    <div class="container">
        <div class="dashboard-buy-card">
            <div class="buy-title"><?= htmlspecialchars(t('buy_and_download_now') ?: t('buy')) ?></div>
            <div id="buy-status"></div>
            <button class="pay-btn" id="pay-btn" style="display:none"><?= htmlspecialchars(t('pay_now')) ?></button>
            <a href="shop.php" class="back-shop-link"><?= htmlspecialchars(t('back_to_shop') ?: t('shop')) ?></a>
        </div>
    </div>
</div>

<?php include 'includes/dashboard-footer.php'; ?>
<?php include __DIR__ . '/../shared/inc/foot-assets.php'; ?>
<?php if(file_exists('includes/theme-script.php')) include 'includes/theme-script.php'; ?>
<script>
const t = <?= json_encode($translations, JSON_UNESCAPED_UNICODE) ?>;
const product_id = <?= json_encode($product_id) ?>;
let invoice_id = null;

function tr(key) {
    return (t[key] !== undefined) ? t[key] : '';
}

function showStatus(type, msg) {
    const $status = document.getElementById('buy-status');
    if(type === 'success')
        $status.innerHTML = `<div class="buy-status-message">${msg}</div>`;
    else
        $status.innerHTML = `<div class="buy-error-message">${msg}</div>`;
}

function showPayBtn(show) {
    let btn = document.getElementById('pay-btn');
    if(btn) btn.style.display = show ? 'block' : 'none';
}

function createOrderAndInvoice() {
    showStatus('success', tr('processing'));
    showPayBtn(false);

    fetch('https://api.xtremedev.co/endpoints/create_order_invoice.php', {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer <?= htmlspecialchars($access_token) ?>',
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'product_id=' + encodeURIComponent(product_id)
    })
    .then(res => res.text())
    .then(text => {
        let data;
        try { data = JSON.parse(text); } catch(e){ data = text; }
        console.group("API: create_order_invoice.php");
        console.log("Raw response:", text);
        console.log("Parsed response:", data);
        console.groupEnd();
        if(data && data.success && data.invoice_id) {
            invoice_id = data.invoice_id;
            showStatus('success', tr('order_created'));
            showPayBtn(true);
        } else if(data && data.invoice_id) {
            invoice_id = data.invoice_id;
            showStatus('success', tr('order_created'));
            showPayBtn(true);
        } else {
            showStatus('error', tr('buy_fail'));
            showPayBtn(false);
        }
    })
    .catch(err => {
        console.group("API: create_order_invoice.php");
        console.error("Network or JS error:", err);
        console.groupEnd();
        showStatus('error', tr('buy_fail'));
        showPayBtn(false);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    createOrderAndInvoice();
});

document.getElementById('pay-btn').onclick = function() {
    if(invoice_id)
        window.location = 'pay-invoice.php?id=' + encodeURIComponent(invoice_id);
};
</script>
</body>
</html>