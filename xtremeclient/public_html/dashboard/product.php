<?php
session_start();
require_once __DIR__ . '/../shared/inc/config.php';

// زبان و راست‌به‌چپ
$lang = $_COOKIE['site_lang'] ?? (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
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
    $client_id = 'xtremeclient-web';
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
$darkThemeActive = (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark');

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>" dir="<?= $is_rtl ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($translations['shop_title'] ?? 'Shop') ?> | XtremeDev</title>
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
    .dashboard-product-container {
        max-width: 500px;
        margin: 48px auto 30px auto;
        background: var(--surface-alt, #fff);
        border-radius: 18px;
        box-shadow: 0 2px 24px var(--shadow-card, #2499fa14);
        border: 1.5px solid var(--border, #2499fa18);
        padding: 2.3rem 1.5rem 1.6rem 1.5rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        min-height: 340px;
        position: relative;
        transition: box-shadow 0.2s;
    }
    /* thumbnail اصلاح‌شده */
    .dashboard-product-thumbnail-wrapper {
        background: var(--surface-alt, #fff);
        border-radius: 22px;
        box-shadow: 0 6px 32px #2499fa14, 0 0 0 7px var(--surface);
        padding: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 3;
        margin-top: -68px;
        margin-bottom: 24px;
        position: relative;
        left: auto;
        top: auto;
        transform: none;
    }
    .dashboard-product-thumbnail {
        width: 160px;
        height: 160px;
        object-fit: cover;
        border-radius: 20px;
        box-shadow: 0 4px 24px #2499fa11;
        background: #e3eefb;
        border: 2px solid #e3eefb;
        transition: border 0.2s, transform 0.21s;
        display: block;
    }
    .dashboard-product-title {
        font-size: 1.17rem;
        font-weight: 900;
        margin-top: 0px;
        margin-bottom: 0.8rem;
        color: var(--primary, #2499fa);
        text-align: center;
        letter-spacing: .5px;
    }
    .dashboard-product-desc {
        font-size: 1.01rem;
        color: var(--text, #222);
        text-align: center;
        margin-bottom: 1.4rem;
        line-height: 1.7;
        opacity: .96;
        min-height: 42px;
    }
    .dashboard-product-price {
        color: #16a34a;
        font-weight: 900;
        font-size: 1.22rem;
        margin-bottom: 1.1rem;
        text-align: center;
    }
    .dashboard-product-actions {
        margin-top: 1.2rem;
        display: flex;
        justify-content: center;
        gap: 0.7rem;
        width: 100%;
    }
    .dashboard-product-btn {
        background: var(--primary, #2499fa);
        color: #fff;
        border: none;
        border-radius: 10px;
        font-weight: bold;
        font-size: 0.98rem;
        padding: 0.6em 1.2em;
        cursor: pointer;
        box-shadow: 0 1px 6px #2499fa18;
        transition: background 0.15s, box-shadow 0.17s;
        text-decoration: none;
        display: inline-block;
    }
    .dashboard-product-btn:hover, .dashboard-product-btn:focus {
        background: #176ad2;
        box-shadow: 0 6px 20px #2499fa29;
        color: #fff;
        text-decoration: none;
    }
    .dashboard-product-btn-green {
        background: #16a34a;
        color: #fff;
        border: none;
        border-radius: 10px;
        font-weight: bold;
        font-size: 0.98rem;
        padding: 0.6em 1.2em;
        cursor: pointer;
        box-shadow: 0 1px 6px #16a34a18;
        transition: background 0.15s, box-shadow 0.17s;
        text-decoration: none;
        display: inline-block;
    }
    .dashboard-product-btn-green:hover, .dashboard-product-btn-green:focus {
        background: #0e8938;
        box-shadow: 0 6px 20px #16a34a29;
        color: #fff;
        text-decoration: none;
    }
    .dashboard-product-error {
        text-align: center;
        color: #e63946;
        margin: 2.7rem 0 1.6rem 0;
        font-size: 1.1rem;
        opacity: 0.9;
        min-height: 20px;
    }
    .dashboard-product-loading {
        text-align: center;
        padding: 2.2rem 0;
        opacity: .7;
    }
    .dashboard-product-loading .loader {
        width:2.1rem;height:2.1rem;border:3px solid #eee;border-top:3px solid #2499fa;
        border-radius:50%;animation:spin 1s linear infinite;margin:auto
    }
    @keyframes spin{100%{transform:rotate(360deg)}}
    @media (max-width: 600px) {
        .dashboard-product-container { padding: 1.3rem 0.7rem 1.1rem 0.7rem; }
        .dashboard-product-thumbnail { width: 90px; height: 90px;}
        .dashboard-product-thumbnail-wrapper { margin-top: -34px; margin-bottom: 15px; }
    }
    html[dir="rtl"] .dashboard-product-container { text-align: right;}
    </style>
</head>
<body<?= $darkThemeActive ? ' class="dark-theme"' : '' ?>>
<?php include 'includes/dashboard-navbar.php'; ?>

<div class="main-content">
    <div class="container">
        <div class="dashboard-product-container" id="product-container">
            <div class="dashboard-product-loading" id="product-loading">
                <div class="loader"></div>
            </div>
            <div id="product-content" style="display:none;"></div>
            <div class="dashboard-product-error" id="product-error" style="display:none;"></div>
        </div>
    </div>
</div>

<?php include 'includes/dashboard-footer.php'; ?>
<?php include __DIR__ . '/../shared/inc/foot-assets.php'; ?>
<?php if(file_exists('includes/theme-script.php')) include 'includes/theme-script.php'; ?>
<script>
const t = <?= json_encode($translations, JSON_UNESCAPED_UNICODE) ?>;
const lang = <?= json_encode($lang) ?>;
const product_id = <?= json_encode($product_id) ?>;

function priceFormat(price) {
    if(!price) return '';
    return (t['currency']||'')+' '+price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
function escapeHtml(str) {
    return (str||'').replace(/[<>&"]/g, function(m) {
        return {'<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;'}[m];
    });
}
function renderProduct(prod) {
    const $content = document.getElementById('product-content');
    let thumb = prod.thumbnail ? prod.thumbnail : '/shared/assets/images/default-product.png';
    let html = `
        <div class="dashboard-product-thumbnail-wrapper">
            <img class="dashboard-product-thumbnail" src="${escapeHtml(thumb)}" loading="lazy" alt="${escapeHtml(prod.name||'')}">
        </div>
        <div class="dashboard-product-title">${escapeHtml(prod.name||'')}</div>
        <div class="dashboard-product-desc">${escapeHtml(prod.description||'')}</div>
        <div class="dashboard-product-price">${priceFormat(prod.price)}</div>
        <div class="dashboard-product-actions">
            <a href="buy.php?id=${encodeURIComponent(prod.id)}" class="dashboard-product-btn-green">${t['shop_buy']||'Buy'}</a>
            <a href="shop.php" class="dashboard-product-btn">${t['back_to_shop']||t['shop']||'Back to Shop'}</a>
        </div>
    `;
    $content.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', function(){
    const $loading = document.getElementById('product-loading');
    const $content = document.getElementById('product-content');
    const $error = document.getElementById('product-error');
    $loading.style.display = '';
    $content.style.display = 'none';
    $error.style.display = 'none';

    fetch('https://api.xtremedev.co/endpoints/product_detail.php?id=' + encodeURIComponent(product_id) + '&lang=' + encodeURIComponent(lang))
    .then(async res => {
        let text = await res.text();
        let data;
        try { data = JSON.parse(text); } catch(e){ data = text; }
        console.log("Server response (product detail):", text, data);
        $loading.style.display = 'none';
        if (data && !data.error && data.id) {
            $content.style.display = '';
            renderProduct(data);
        } else {
            $error.textContent = t['feature_not_found'] || t['not_found_products'] || 'Product not found.';
            $error.style.display = '';
        }
    })
    .catch(err => {
        console.log("Server error (product detail):", err);
        $loading.style.display = 'none';
        $error.textContent = t['error_loading_products'] || 'Error loading product.';
        $error.style.display = '';
    });
});
</script>
</body>
</html>