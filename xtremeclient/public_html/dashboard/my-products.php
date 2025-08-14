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

// ورود
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
$project_id = 2;
?>
<!DOCTYPE html>
<html lang="<?=$lang?>" dir="<?=$is_rtl?'rtl':'ltr'?>">
<head>
    <meta charset="UTF-8">
    <title><?=htmlspecialchars($translations['my_products'] ?? 'My Products')?> | XtremeDev</title>
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
        .products-section {
            max-width: 1200px;
            margin: 48px auto 30px auto;
            width: 100%;
        }
        .products-title {
            color: var(--primary, #2499fa);
            font-size: 1.7rem;
            font-weight: 900;
            margin-bottom: 2.1rem;
            letter-spacing: .2px;
            text-align: <?= $is_rtl ? 'right' : 'left' ?>;
        }
        .products-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin-bottom: 2.5rem;
            margin-top: 2.5rem; /* فاصله از عنوان صفحه */
        }
        .product-card {
            background: var(--surface-alt, #fff);
            border-radius: 22px;
            box-shadow: 0 6px 32px var(--shadow-card, #2499fa14);
            border: 1.7px solid var(--border, #2499fa18);
            padding: 2.4rem 1.15rem 1.5rem 1.15rem;
            width: 100%;
            max-width: 320px;
            min-width: 230px;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: box-shadow 0.18s, border-color 0.18s, transform .14s;
            position: relative;
            overflow: visible;
            min-height: 420px;
        }
        .product-card:hover {
            box-shadow: 0 16px 48px #2499fa39;
            border-color: #38a8ff55;
            background: var(--surface);
            transform: translateY(-4px) scale(1.016);
        }
        .product-thumbnail-wrapper {
            position: absolute;
            top: -70px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--surface-alt);
            border-radius: 22px;
            box-shadow: 0 6px 32px #2499fa14, 0 0 0 7px var(--surface);
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 3;
            transition: box-shadow 0.17s, background 0.18s;
        }
        .product-card:hover .product-thumbnail-wrapper {
            box-shadow: 0 12px 40px #2499fa2a, 0 0 0 7px var(--surface-alt);
            background: var(--surface-alt);
        }
        .product-thumbnail {
            width: 160px;
            height: 160px;
            object-fit: cover;
            border-radius: 20px;
            box-shadow: 0 4px 24px #2499fa11;
            background: #e3eefb;
            border: 2px solid #e3eefb;
            transition: border 0.2s, transform 0.21s;
        }
        .product-card:hover .product-thumbnail {
            border: 2.5px solid #2499fa44;
            transform: scale(1.05);
        }
        .product-title {
            font-size: 1.13rem;
            font-weight: 800;
            margin-top: 110px;
            margin-bottom: 0.5rem;
            color: var(--primary);
            text-align: center;
        }
        .product-desc {
            font-size: .99rem;
            color: var(--text);
            min-height: 40px;
            margin-bottom: 0.7rem;
            text-align: center;
        }
        .product-status {
            font-size: 1.01rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #2bc551;
            text-align: center;
        }
        .product-btns-row {
            width: 100%;
            display: flex;
            justify-content: flex-end; /* LTR: دکمه سمت چپ کارت */
            margin-top: 0.7rem;
        }
        html[dir="rtl"] .product-btns-row {
            justify-content: flex-start; /* RTL: دکمه سمت راست کارت */
        }
        .buy-btn {
            padding: 0.52rem 1.5rem;
            border-radius: 11px;
            font-size: 1rem;
            font-weight: 700;
            border: none;
            background: #2bc551;
            color: #fff !important;
            transition: background 0.18s, color 0.18s;
            text-decoration: none;
            display: inline-block;
            margin-top: 0;
        }
        .buy-btn:hover, .buy-btn:focus {
            background: #1a9940;
            color: #fff !important;
            text-decoration: none;
        }
        .dark-theme .product-card {
            background: #222b38 !important;
            border-color: #384c6e !important;
            color: #e6e9f2 !important;
        }
        .dark-theme .product-title { color: #38a8ff !important; }
        .dark-theme .product-desc { color: #b0c2d6 !important;}
        .dark-theme .buy-btn { background: #2bc551 !important; color: #fff !important;}
        .dark-theme .buy-btn:hover, .dark-theme .buy-btn:focus { background: #1a9940 !important; color: #fff !important;}
        /* --- Skeleton Loader --- */
        .skeleton-grid { display: flex; flex-wrap: wrap; gap: 2rem; margin-bottom: 2.5rem; margin-top: 2.5rem;}
        .skeleton-card {
            background: linear-gradient(90deg, #f4f7fa 25%, #e6edf5 50%, #f4f7fa 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.13s infinite linear;
            border-radius: 22px;
            min-height: 260px;
            width: 100%;
            max-width: 320px;
            min-width: 230px;
            display: block;
            margin-bottom: 0;
        }
        .dark-theme .skeleton-card {
            background: linear-gradient(90deg, #232d3b 25%, #1e2632 50%, #232d3b 75%);
        }
        @keyframes skeleton-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        @media (max-width: 900px) {
            .products-grid, .skeleton-grid { gap: 1.15rem;}
            .product-card, .skeleton-card { max-width: 47%; min-width: 145px;}
        }
        @media (max-width: 600px) {
            .products-grid, .skeleton-grid { flex-direction: column; gap: 1.1rem;}
            .product-card, .skeleton-card { width: 100%; max-width: 99%;}
            .product-thumbnail { width: 90px; height: 90px;}
            .product-thumbnail-wrapper { top: -32px; padding: 5px;}
            .product-title { margin-top: 60px;}
        }
        html[dir="rtl"] .products-title {text-align: right;}
    </style>
</head>
<body<?= $darkThemeActive ? ' class="dark-theme"' : '' ?>>
<?php include 'includes/dashboard-navbar.php'; ?>
<div class="main-content">
    <div class="container">
        <div class="products-section">
            <div class="products-title"><?=htmlspecialchars($translations['my_products'] ?? 'My Products')?></div>
            <!-- Skeleton Loader -->
            <div id="products-skeleton" class="skeleton-grid mb-4">
                <?php for($i=0;$i<3;$i++): ?>
                    <div class="skeleton-card"></div>
                <?php endfor; ?>
            </div>
            <div id="products-list" style="display:none;"></div>
            <div id="products-none" style="color:#aaa;font-size:1.07rem;margin-bottom:2rem;display:none;">
                <?=htmlspecialchars($translations['no_products'] ?? 'You have not purchased any products yet.')?>
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
const project_id = <?= (int)$project_id ?>;

function escapeHtml(str) {
    return (str || "").replace(/[<>&"]/g, function(m) {
        return {'<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;'}[m];
    });
}

function renderProducts(products) {
    let html = '';
    if(products && products.length) {
        html += '<div class="products-grid mb-4">';
        products.forEach(p => {
            html += `<div class="product-card">
                <div class="product-thumbnail-wrapper">
                    <img class="product-thumbnail" src="${p.thumbnail ? escapeHtml(p.thumbnail) : '/shared/assets/images/default-product.png'}" loading="lazy" alt="${escapeHtml(p.name)}">
                </div>
                <div class="product-title">${escapeHtml(p.name)}</div>
                <div class="product-desc">${escapeHtml(p.description)}</div>
                <div class="product-status">
                    ${p.price ? (Number(p.price).toLocaleString() + ' ' + (t.toman||'تومان')) : ''}
                </div>
                <div class="product-btns-row">
                    <a href="/download-product.php?id=${p.id}" class="buy-btn" target="_blank">${t.shop_view||t.buy||'View/Download'}</a>
                </div>
            </div>`;
        });
        html += '</div>';
        document.getElementById('products-list').innerHTML = html;
        document.getElementById('products-list').style.display = '';
        document.getElementById('products-none').style.display = 'none';
    } else {
        document.getElementById('products-list').innerHTML = '';
        document.getElementById('products-list').style.display = 'none';
        document.getElementById('products-none').style.display = '';
        document.getElementById('products-none').style.display = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const skeleton = document.getElementById('products-skeleton');
    const productsList = document.getElementById('products-list');
    const productsNone = document.getElementById('products-none');
    productsList.style.display = 'none';
    productsNone.style.display = 'none';
    skeleton.style.display = 'flex';

    fetch('/api/my_products.php?project_id=' + project_id + '&lang=' + encodeURIComponent(lang), {
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
            renderProducts(data);
        } else {
            productsList.innerHTML = '';
            productsList.style.display = 'none';
            productsNone.style.display = '';
            productsNone.style.display = '';
        }
    })
    .catch(err => {
        skeleton.style.display = 'none';
        productsList.innerHTML = '';
        productsList.style.display = 'none';
        productsNone.style.display = '';
        productsNone.style.display = '';
    });
});
</script>
</body>
</html>