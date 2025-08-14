<?php
session_start();
require_once __DIR__ . '/shared/inc/config.php';

// تنظیم زبان
$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$is_rtl = $lang === 'fa';

// بارگذاری ترجمه سایت
$translations = [];
$lang_file = __DIR__ . '/shared/assets/languages/' . $lang . '.json';
if (file_exists($lang_file)) {
    $json = file_get_contents($lang_file);
    $translations = json_decode($json, true);
}
$darkThemeActive = (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark');
?>
<!DOCTYPE html>
<html lang="<?=htmlspecialchars($lang)?>" dir="<?=$is_rtl ? 'rtl' : 'ltr'?>">
<head>
    <meta charset="UTF-8">
    <title><?= $translations['shop_title'] ?? '' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include __DIR__.'/shared/inc/head-assets.php'; ?>
    <?php include __DIR__.'/includes/styles.php'; ?>
    <style>
    :root {
      --primary: #2499fa;
      --surface: #f4f7fa;
      --surface-alt: #fff;
      --shadow-card: #2499fa14;
      --border: #2499fa18;
      --border-hover: #2499fa44;
      --text: #222;
      --price: #16a34a;
      --category-bg: #e3eefb;
      --category-bg-active: #2499fa;
      --category-text-active: #fff;
      --category-text: #2499fa;
    }
    .dark-theme {
      --surface: #181f2a;
      --surface-alt: #202b3b;
      --text: #e6e9f2;
      --shadow-card: #15203222;
      --border: #2499fa28;
      --border-hover: #2499fa66;
      --category-bg: #172234;
      --category-bg-active: #2499fa;
      --category-text-active: #fff;
      --category-text: #70baff;
    }
    body {
      background: var(--surface);
      color: var(--text);
    }
    .shop-title-center {
      display: flex;
      justify-content: center;
      align-items: center;
      margin-bottom: 2.5rem;
    }
    .shop-section-title {
      font-weight: 900;
      font-family: Vazirmatn, Tahoma, Arial, sans-serif;
      font-size: 2.1rem;
      letter-spacing: 1.3px;
      text-align: center;
      margin-bottom: 0;
      margin-top: 2.5rem;
      background: linear-gradient(90deg, #2499fa 15%, #3ed2f0 85%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      text-fill-color: transparent;
      position: relative;
      display: inline-block;
      padding-bottom: 8px;
    }
    .shop-section-title:after {
      content: '';
      display: block;
      width: 68px;
      height: 4px;
      background: linear-gradient(90deg, #2499fa 10%, #3ed2f0 90%);
      border-radius: 2px;
      margin: 0 auto;
      margin-top: 8px;
      opacity: 0.85;
    }
    .shop-categories {
      display: flex;
      flex-wrap: wrap;
      gap: 0.7rem;
      margin: 0 auto 2.2rem auto;
      justify-content: center;
      padding: 0 0.5rem;
    }
    .shop-category-btn {
      background: var(--category-bg);
      color: var(--category-text);
      border: none;
      border-radius: 18px;
      padding: 0.62em 1.4em;
      font-weight: bold;
      font-size: 1.01rem;
      cursor: pointer;
      transition: background 0.19s, color 0.21s;
      outline: none;
      box-shadow: 0 2px 6px var(--shadow-card);
    }
    .shop-category-btn.active,
    .shop-category-btn:focus {
      background: var(--category-bg-active);
      color: var(--category-text-active);
    }
    .shop-product-list {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 2rem;
      max-width: 1080px;
      margin: 0 auto 3rem auto;
      padding: 0 1rem;
    }
    .shop-product-card {
      background: var(--surface-alt);
      border-radius: 22px;
      box-shadow: 0 6px 32px var(--shadow-card);
      border: 2px solid var(--border);
      padding: 2.3rem 1.1rem 1.5rem 1.1rem; /* بیشتر برای تامبنیل */
      transition: box-shadow 0.2s, border 0.2s, background 0.3s, transform 0.4s cubic-bezier(.38,1.3,.6,1);
      position: relative;
      display: flex;
      flex-direction: column;
      align-items: center;
      min-height: 450px;
      overflow: visible;
    }
    .shop-product-card:hover, .shop-product-card:focus {
      box-shadow: 0 16px 48px #2499fa39;
      border-color: var(--border-hover);
      background: var(--surface);
      transform: translateY(-3px) scale(1.012);
      z-index: 2;
    }
    .shop-product-thumbnail-wrapper {
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
    .shop-product-card:hover .shop-product-thumbnail-wrapper {
      box-shadow: 0 12px 40px #2499fa2a, 0 0 0 7px var(--surface-alt);
      background: var(--surface-alt);
    }
    .shop-product-thumbnail {
      width: 160px;
      height: 160px;
      object-fit: cover;
      border-radius: 20px;
      box-shadow: 0 4px 24px #2499fa11;
      background: #e3eefb;
      border: 2px solid #e3eefb;
      transition: border 0.2s, transform 0.21s;
    }
    .shop-product-card:hover .shop-product-thumbnail {
      border: 2.5px solid #2499fa44;
      transform: scale(1.05);
    }
    .shop-product-title {
      font-size: 1.13rem;
      font-weight: 800;
      margin-top: 110px; /* فاصله بعد از تامبنیل */
      margin-bottom: 0.5rem;
      color: var(--primary);
      text-align: center;
    }
    .shop-product-desc {
      font-size: 0.99rem;
      color: var(--text);
      text-align: center;
      margin-bottom: 1.1rem;
      min-height: 46px;
      line-height: 1.67;
      opacity: 0.95;
    }
    .shop-product-price {
      color: var(--price);
      font-weight: 900;
      font-size: 1.12rem;
      margin-bottom: 1.3rem;
      letter-spacing: 0.2px;
      text-align: center;
    }
    .shop-product-actions {
      margin-top: auto;
      display: flex;
      justify-content: center;
      gap: 0.7rem;
    }
    .shop-buy-btn {
      background: var(--primary);
      color: #fff;
      border: none;
      border-radius: 12px;
      font-weight: bold;
      font-size: 1rem;
      padding: 0.67em 1.5em;
      cursor: pointer;
      box-shadow: 0 2px 8px #2499fa18;
      transition: background 0.15s, box-shadow 0.22s;
    }
    .shop-buy-btn:hover, .shop-buy-btn:focus {
      background: #176ad2;
      box-shadow: 0 6px 20px #2499fa29;
    }
    .shop-buy-btn-green {
      background: #16a34a;
      color: #fff;
      border: none;
      border-radius: 12px;
      font-weight: bold;
      font-size: 1rem;
      padding: 0.67em 1.5em;
      cursor: pointer;
      box-shadow: 0 2px 8px #16a34a18;
      transition: background 0.15s, box-shadow 0.22s;
      margin-right: 0;
      margin-left: 0;
    }
    .shop-buy-btn-green:hover, .shop-buy-btn-green:focus {
      background: #0e8938;
      box-shadow: 0 6px 20px #16a34a29;
    }
    .shop-product-empty {
      text-align: center;
      color: #888;
      margin: 3.5rem 0 2.5rem 0;
      font-size: 1.2rem;
      opacity: 0.82;
      width: 100%;
    }
    .shop-product-list-loading {
      text-align:center;
      padding:2.2rem 0;
      opacity:.7;
      grid-column: 1 / -1;
    }
    .shop-product-list-loading .loader {
      width:2.3rem;height:2.3rem;border:4px solid #eee;border-top:4px solid #2499fa;
      border-radius:50%;animation:spin 1s linear infinite;margin:auto
    }
    @keyframes spin{100%{transform:rotate(360deg)}}
    .error-message {
      color: #e63946;
      text-align: center;
      font-size: 1.09rem;
      margin: 2.5rem auto 0 auto;
      display: none;
    }
    @media (max-width: 991.98px) {
      .shop-product-list {
        grid-template-columns: repeat(2, 1fr);
      }
      .shop-product-thumbnail {
        width: 120px;
        height: 120px;
      }
      .shop-product-thumbnail-wrapper {
        top: -44px;
        padding: 7px;
      }
      .shop-product-title {
        margin-top: 80px;
      }
    }
    @media (max-width: 600px) {
      .shop-product-list {
        grid-template-columns: 1fr;
        gap: 1.2rem;
      }
      .shop-product-card {
        padding: 1.7rem 0.7rem 1rem 0.7rem;
        min-height: 260px;
      }
      .shop-product-thumbnail {
        width: 90px;
        height: 90px;
      }
      .shop-product-thumbnail-wrapper {
        top: -32px;
        padding: 5px;
      }
      .shop-product-title {
        margin-top: 60px;
      }
    }
    html[dir="rtl"] .shop-product-card, html[dir="rtl"] .shop-product-list { text-align: right;}
    html[dir="rtl"] .shop-categories { flex-direction: row-reverse;}
    </style>
    <script>
        window.PAGE_TRANSLATIONS = <?= json_encode($translations, JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body<?= $darkThemeActive ? ' class="dark-theme"' : '' ?>>
    <div class="theme-fade-overlay"></div>
    <?php include __DIR__.'/includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="shop-title-center">
            <h1 class="shop-section-title"><?= $translations['shop_title'] ?? '' ?></h1>
        </div>
        <div class="shop-categories" id="shop-categories"></div>
        <div class="shop-product-list" id="shop-product-list">
            <div class="shop-product-list-loading" id="shop-product-list-loading">
              <div class="loader"></div>
            </div>
        </div>
        <div id="shop-product-error" class="error-message"></div>
    </div>

    <?php include __DIR__.'/includes/footer.php'; ?>
    <?php include __DIR__.'/shared/inc/foot-assets.php'; ?>
    <?php include __DIR__.'/includes/theme-script.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var t = window.PAGE_TRANSLATIONS || {};
    var lang = <?= json_encode($lang) ?>;
    var project_id = 2;
    var $catList = document.getElementById('shop-categories');
    var $productList = document.getElementById('shop-product-list');
    var $productLoading = document.getElementById('shop-product-list-loading');
    var $productError = document.getElementById('shop-product-error');
    var activeCategory = 0;

    function renderCategories(categories) {
        var html = '<button class="shop-category-btn'+(activeCategory==0?' active':'')+'" data-category="0">'+(t['all_categories']||'')+'</button>';
        categories.forEach(function(cat) {
            html += '<button class="shop-category-btn'+(activeCategory==cat.id?' active':'')+'" data-category="'+cat.id+'">'+(cat.title||'')+'</button>';
        });
        $catList.innerHTML = html;
        var btns = $catList.querySelectorAll('.shop-category-btn');
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
            $productList.innerHTML = '<div class="shop-product-empty">'+(t['not_found_products']||'')+'</div>';
            return;
        }
        var html = "";
        products.forEach(function(p){
            html += '<div class="shop-product-card">';
            html +=    '<div class="shop-product-thumbnail-wrapper">';
            html +=      '<img class="shop-product-thumbnail" src="'+(p.thumbnail ? p.thumbnail : '/shared/assets/images/default-product.png')+'" loading="lazy" alt="'+(p.name||'')+'">';
            html +=    '</div>';
            html +=    '<div class="shop-product-title">'+(p.name||'')+'</div>';
            html +=    '<div class="shop-product-desc">'+(p.description||'')+'</div>';
            html +=    '<div class="shop-product-price">'+priceFormat(p.price)+'</div>';
            html +=    '<div class="shop-product-actions">';
            html +=      '<a href="/product.php?id='+p.id+'" class="shop-buy-btn">'+(t['shop_view']||'')+'</a>';
            html +=      '<a href="/buy.php?id='+p.id+'" class="shop-buy-btn-green">'+(t['shop_buy']||'')+'</a>';
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