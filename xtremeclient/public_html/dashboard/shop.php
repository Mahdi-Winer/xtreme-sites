<?php
session_start();
require_once __DIR__ . '/../shared/inc/config.php';

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
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>" dir="<?= $is_rtl ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($translations['shop_title'] ?? 'Shop') ?> | XtremeDev</title>
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
        --btn-navy: #24325b;
        --btn-navy-hover: #2d45a0;
    }
    body.dark-theme {
        --surface: #181f2a;
        --surface-alt: #202b3b;
        --text: #e6e9f2;
        --shadow-card: #15203222;
        --border: #2499fa28;
        --btn-navy: #24325b;
        --btn-navy-hover: #2d45a0;
    }
    body {
        font-family: Vazirmatn, Tahoma, Arial, sans-serif;
        background: var(--surface);
        color: var(--text);
        min-height: 100vh;
    }
    .dashboard-shop-title {
        font-size: 2rem;
        font-weight: 900;
        color: var(--primary, #2499fa);
        text-align: center;
        margin: 2.2rem 0 1.3rem 0;
        letter-spacing: 0.8px;
    }
    .dashboard-shop-categories {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
        justify-content: center;
        margin-bottom: 2rem;
    }
    .dashboard-shop-category-btn {
        background: var(--surface-alt);
        color: var(--primary, #2499fa);
        border: 2px solid var(--border, #2499fa18);
        border-radius: 14px;
        padding: 0.5em 1.3em;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        transition: background 0.19s, color 0.21s, border 0.21s;
        outline: none;
        box-shadow: 0 1px 6px var(--shadow-card, #2499fa14);
    }
    .dashboard-shop-category-btn.active,
    .dashboard-shop-category-btn:focus {
        background: var(--primary, #2499fa);
        color: #fff;
        border-color: var(--primary, #2499fa);
    }
    .dashboard-shop-list {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
        max-width: 1100px;
        margin: 0 auto 2.5rem auto;
        padding: 0 1rem;
    }
    .dashboard-shop-card {
        background: var(--surface-alt);
        border-radius: 18px;
        box-shadow: 0 4px 26px var(--shadow-card);
        border: 1.7px solid var(--border);
        padding: 2.3rem 1rem 1.5rem 1rem; /* جای بیشتر برای تامبنیل */
        display: flex;
        flex-direction: column;
        align-items: center;
        min-width: 200px;
        max-width: 340px;
        min-height: 420px;
        position: relative;
        overflow: visible;
        transition: box-shadow 0.2s, border 0.2s, background 0.2s, transform 0.32s cubic-bezier(.38,1.3,.6,1);
    }
    .dashboard-shop-card:hover, .dashboard-shop-card:focus-within {
        box-shadow: 0 16px 48px #2499fa39;
        border-color: var(--primary, #2499fa);
        background: var(--surface);
        transform: translateY(-4px) scale(1.016);
        z-index: 2;
    }
    .dashboard-shop-thumbnail-wrapper {
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
    .dashboard-shop-card:hover .dashboard-shop-thumbnail-wrapper {
        box-shadow: 0 12px 40px #2499fa2a, 0 0 0 7px var(--surface-alt);
        background: var(--surface-alt);
    }
    .dashboard-shop-thumbnail {
        width: 160px;
        height: 160px;
        object-fit: cover;
        border-radius: 20px;
        box-shadow: 0 4px 24px #2499fa11;
        background: #e3eefb;
        border: 2px solid #e3eefb;
        transition: border 0.2s, transform 0.21s;
    }
    .dashboard-shop-card:hover .dashboard-shop-thumbnail {
        border: 2.5px solid #2499fa44;
        transform: scale(1.05);
    }
    .dashboard-shop-title-product {
        font-size: 1.13rem;
        font-weight: 800;
        margin-top: 110px; /* فاصله بعد از تامبنیل */
        margin-bottom: 0.5rem;
        color: var(--primary, #2499fa);
        text-align: center;
    }
    .dashboard-shop-desc {
        font-size: 0.99rem;
        color: var(--text);
        text-align: center;
        margin-bottom: 0.8rem;
        min-height: 36px;
        line-height: 1.6;
        opacity: 0.95;
    }
    .dashboard-shop-price {
        color: #16a34a;
        font-weight: 900;
        font-size: 1.11rem;
        margin-bottom: 1rem;
        text-align: center;
    }
    .dashboard-shop-actions {
        margin-top: auto;
        display: flex;
        gap: 0.7rem;
        justify-content: flex-end; /* LTR: دکمه‌ها سمت چپ کارت */
        width: 100%;
    }
    html[dir="rtl"] .dashboard-shop-actions {
        justify-content: flex-start; /* RTL: دکمه‌ها سمت راست کارت */
    }
    .dashboard-shop-btn {
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
    }
    .dashboard-shop-btn:hover, .dashboard-shop-btn:focus {
        background: #176ad2;
        box-shadow: 0 6px 20px #2499fa29;
    }
    .dashboard-shop-btn-green {
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
    }
    .dashboard-shop-btn-green:hover, .dashboard-shop-btn-green:focus {
        background: #0e8938;
        box-shadow: 0 6px 20px #16a34a29;
    }
    .dashboard-shop-empty {
        text-align: center;
        color: #888;
        margin: 2.5rem 0 2rem 0;
        font-size: 1.1rem;
        opacity: 0.82;
        width: 100%;
    }
    .dashboard-shop-loading {
        text-align:center;
        padding:2.2rem 0;
        opacity:.7;
        grid-column: 1 / -1;
    }
    .dashboard-shop-loading .loader {
        width:2.1rem;height:2.1rem;border:3px solid #eee;border-top:3px solid #2499fa;
        border-radius:50%;animation:spin 1s linear infinite;margin:auto
    }
    @keyframes spin{100%{transform:rotate(360deg)}}
    .dashboard-shop-error {
        color: #e63946;
        text-align: center;
        font-size: 1.08rem;
        margin: 2.5rem auto 0 auto;
        display: none;
    }
    @media (max-width: 991.98px) {
        .dashboard-shop-list {
            grid-template-columns: repeat(2, 1fr);
        }
        .dashboard-shop-thumbnail {
            width: 120px;
            height: 120px;
        }
        .dashboard-shop-thumbnail-wrapper {
            top: -44px;
            padding: 7px;
        }
        .dashboard-shop-title-product {
            margin-top: 80px;
        }
    }
    @media (max-width: 600px) {
        .dashboard-shop-list {
            grid-template-columns: 1fr;
            gap: 1.2rem;
        }
        .dashboard-shop-card {
            padding: 1.7rem 0.7rem 1rem 0.7rem;
            min-height: 260px;
        }
        .dashboard-shop-thumbnail {
            width: 90px;
            height: 90px;
        }
        .dashboard-shop-thumbnail-wrapper {
            top: -32px;
            padding: 5px;
        }
        .dashboard-shop-title-product {
            margin-top: 60px;
        }
    }
    html[dir="rtl"] .dashboard-shop-card, html[dir="rtl"] .dashboard-shop-list { text-align: right;}
    html[dir="rtl"] .dashboard-shop-categories { flex-direction: row-reverse;}
    </style>
    <?php include __DIR__ . '/includes/dashboard-styles.php'; ?>
</head>
<body<?= $darkThemeActive ? ' class="dark-theme"' : '' ?>>
<?php include 'includes/dashboard-navbar.php'; ?>

<div class="main-content">
  <div class="container" style="max-width:1100px;padding-top:38px;padding-bottom:16px;">
    <div class="dashboard-shop-title">
      <?= htmlspecialchars($translations['shop_title'] ?? 'Shop') ?>
    </div>
    <div class="dashboard-shop-categories" id="shop-categories"></div>
    <div class="dashboard-shop-list" id="shop-product-list">
      <div class="dashboard-shop-loading" id="shop-product-list-loading">
        <div class="loader"></div>
      </div>
    </div>
    <div id="shop-product-error" class="dashboard-shop-error"></div>
  </div>
</div>

<?php include 'includes/dashboard-footer.php'; ?>
<?php include __DIR__ . '/../shared/inc/foot-assets.php'; ?>
<?php if(file_exists('includes/theme-script.php')) include 'includes/theme-script.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var t = <?= json_encode($translations, JSON_UNESCAPED_UNICODE) ?>;
    var lang = <?= json_encode($lang) ?>;
    var project_id = 2;
    var $catList = document.getElementById('shop-categories');
    var $productList = document.getElementById('shop-product-list');
    var $productLoading = document.getElementById('shop-product-list-loading');
    var $productError = document.getElementById('shop-product-error');
    var activeCategory = 0;

    function renderCategories(categories) {
        var html = '<button class="dashboard-shop-category-btn'+(activeCategory==0?' active':'')+'" data-category="0">'+(t['all_categories']||'')+'</button>';
        categories.forEach(function(cat) {
            html += '<button class="dashboard-shop-category-btn'+(activeCategory==cat.id?' active':'')+'" data-category="'+cat.id+'">'+(cat.title||'')+'</button>';
        });
        $catList.innerHTML = html;
        var btns = $catList.querySelectorAll('.dashboard-shop-category-btn');
        btns.forEach(function(btn){
            btn.onclick = function(){
                var catId = parseInt(btn.getAttribute('data-category'));
                if(catId !== activeCategory) {
                    activeCategory = catId;
                    btns.forEach(function(b){ b.classList.remove('active'); });
                    btn.classList.add('active');
                    loadProducts();
                }
            }
        });
    }

    function priceFormat(price) {
        if(!price) return '';
        return (t['currency']||'')+' '+price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    function renderProducts(products) {
        if (!Array.isArray(products) || products.length === 0) {
            $productList.innerHTML = '<div class="dashboard-shop-empty">'+(t['not_found_products']||'')+'</div>';
            return;
        }
        var html = "";
        products.forEach(function(p){
            html += '<div class="dashboard-shop-card">';
            html +=    '<div class="dashboard-shop-thumbnail-wrapper">';
            html +=      '<img class="dashboard-shop-thumbnail" src="'+(p.thumbnail ? p.thumbnail : '/shared/assets/images/default-product.png')+'" loading="lazy" alt="'+(p.name||'')+'">';
            html +=    '</div>';
            html +=    '<div class="dashboard-shop-title-product">'+(p.name||'')+'</div>';
            html +=    '<div class="dashboard-shop-desc">'+(p.description||'')+'</div>';
            html +=    '<div class="dashboard-shop-price">'+priceFormat(p.price)+'</div>';
            html +=    '<div class="dashboard-shop-actions">';
            html +=      '<a href="product.php?id='+p.id+'" class="dashboard-shop-btn">'+(t['shop_view']||'')+'</a>';
            html +=      '<a href="buy.php?id='+p.id+'" class="dashboard-shop-btn-green">'+(t['shop_buy']||'')+'</a>';
            html +=    '</div>';
            html += '</div>';
        });
        $productList.innerHTML = html;
    }

    function loadCategories() {
        fetch('https://api.xtremedev.co/endpoints/get_product_categories.php?project_id='+project_id+'&lang='+encodeURIComponent(lang))
        .then(function(res){ return res.json(); })
        .then(function(categories){
            renderCategories(categories);
        });
    }

    function loadProducts() {
        $productError.style.display = "none";
        $productLoading.style.display = "block";
        var url = 'https://api.xtremedev.co/endpoints/products.php?project_id='+project_id+'&lang='+encodeURIComponent(lang);
        if(activeCategory) url += '&category_id='+activeCategory;
        fetch(url)
          .then(function(res){
              if(!res.ok) throw new Error('API error');
              return res.json();
          })
          .then(function(products){
              $productLoading.style.display = "none";
              renderProducts(products);
          })
          .catch(function(err){
              $productLoading.style.display = "none";
              $productError.style.display = "block";
              $productError.textContent = t['error_loading_products'] || '';
          });
    }

    loadCategories();
    loadProducts();
});
</script>
</body>
</html>