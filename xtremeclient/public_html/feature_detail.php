<?php
session_start();
require_once __DIR__ . '/shared/inc/config.php';

// زبان فقط از کانفیگ
$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$is_rtl = $lang === 'fa';

// فقط و فقط بارگذاری ترجمه از فایل زبان
$translations = [];
$lang_file = __DIR__ . '/shared/assets/languages/' . $lang . '.json';
if (file_exists($lang_file)) {
    $json = file_get_contents($lang_file);
    $translations = json_decode($json, true);
}
$darkThemeActive = (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark');

// گرفتن feature_id از GET
$feature_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>
<!DOCTYPE html>
<html lang="<?=htmlspecialchars($lang)?>" dir="<?=$is_rtl ? 'rtl' : 'ltr'?>">
<head>
    <meta charset="UTF-8">
    <title><?= $translations['feature_detail'] ?? '' ?></title>
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
    }
    .dark-theme {
      --surface: #181f2a;
      --surface-alt: #202b3b;
      --text: #e6e9f2;
      --shadow-card: #15203222;
      --border: #2499fa28;
      --border-hover: #2499fa66;
    }
    body {
      background: var(--surface);
      color: var(--text);
      font-family: 'Vazirmatn', 'Tahoma', Arial, sans-serif;
    }
    .theme-fade-overlay {
      position: fixed;
      z-index: 99999;
      inset: 0;
      background: var(--surface, #fff);
      pointer-events: none;
      opacity: 0;
      transition: opacity .5s cubic-bezier(.62,1.5,.33,1), background .5s cubic-bezier(.62,1.5,.33,1);
      will-change: opacity, background;
    }
    body.theme-fade-active .theme-fade-overlay {
      opacity: 1;
      pointer-events: all;
    }
    .feature-detail-card {
      max-width: 680px;
      margin: 3rem auto 2rem auto;
      background: var(--surface-alt);
      border-radius: 28px;
      box-shadow: 0 6px 32px var(--shadow-card);
      border: 2px solid var(--border);
      padding: 2.3rem 2.1rem;
      display: flex;
      flex-direction: column;
      align-items: center;
      transition: box-shadow 0.2s, border 0.2s, background 0.3s, transform 0.4s cubic-bezier(.38,1.3,.6,1);
      min-height: 320px;
    }
    .feature-detail-img {
      width: 100%;
      max-width:440px;
      min-height: 160px;
      max-height: 320px;
      border-radius: 18px;
      object-fit: cover;
      margin-bottom: 1.5rem;
      box-shadow: 0 2px 12px #2499fa22;
      background: #f3f6fb;
      display: block;
    }
    .dark-theme .feature-detail-img {
      background: #202b3b;
    }
    .feature-detail-title {
      font-size: 2.1rem;
      font-weight: 900;
      color: #2499fa;
      margin-bottom: 1rem;
      text-align: center;
      display: flex;
      align-items: center;
      gap: 1rem;
      flex-wrap: wrap;
    }
    .feature-detail-badge {
      display: inline-block;
      background: #3ed2f0;
      color: #fff;
      font-size: 1.12em;
      font-weight: 800;
      border-radius: 12px;
      padding: 0.18em 1.15em;
      letter-spacing: 0.2px;
      box-shadow: 0 2px 7px #3ed2f033;
    }
    .feature-detail-desc {
      font-size: 1.15rem;
      opacity: 0.97;
      margin-bottom: 1.1rem;
      margin-top: 0.3rem;
      text-align: center;
      color: var(--text);
      line-height: 1.8;
    }
    .feature-detail-longdesc {
      font-size: 1.09rem;
      color: var(--text);
      margin-top: 1.1rem;
      line-height: 2.05;
      text-align: justify;
    }
    .feature-back-btn {
      margin: 2rem auto 0 0;
      display: inline-block;
      background: linear-gradient(90deg, #2499fa 15%, #3ed2f0 85%);
      color: #fff;
      font-weight: 700;
      font-size: 1.08rem;
      padding: 0.7em 2em;
      border-radius: 15px;
      border: none;
      text-decoration: none;
      box-shadow: 0 2px 10px #2499fa1b;
      transition: background 0.18s, color 0.18s, box-shadow 0.18s;
    }
    .feature-back-btn:hover, .feature-back-btn:focus {
      background: linear-gradient(90deg, #2070d6 10%, #3ed2f0 90%);
      color: #fff;
      box-shadow: 0 8px 24px #2499fa22;
    }
    .feature-detail-loading {
      text-align:center;
      padding:2.2rem 0;
      opacity:0.7;
    }
    .feature-detail-loading .loader {
      width:2.3rem;height:2.3rem;border:4px solid #eee;border-top:4px solid #2499fa;
      border-radius:50%;animation:spin 1s linear infinite;margin:auto
    }
    @keyframes spin{100%{transform:rotate(360deg)}}
    .error-message {
      color: #e63946;
      text-align: center;
      font-size: 1.18rem;
      margin: 3rem auto 0 auto;
      display: none;
    }
    @media (max-width: 767.98px) {
      .feature-detail-card {
        padding: 1.2rem 0.5rem;
      }
      .feature-detail-title {
        font-size: 1.28rem;
      }
      .feature-detail-img {
        max-width: 100%;
        min-height: 80px;
        max-height: 180px;
      }
    }
    </style>
    <script>
        window.PAGE_TRANSLATIONS = <?= json_encode($translations, JSON_UNESCAPED_UNICODE) ?>;
        window.FEATURE_ID = <?= intval($feature_id) ?>;
    </script>
</head>
<body<?= $darkThemeActive ? ' class="dark-theme"' : '' ?>>
    <div class="theme-fade-overlay"></div>
    <?php include __DIR__.'/includes/navbar.php'; ?>

    <div class="container py-5">
        <div id="feature-detail">
            <div class="feature-detail-loading" id="feature-detail-loading">
              <div class="loader"></div>
            </div>
        </div>
        <div id="feature-detail-error" class="error-message"></div>
        <div class="mt-4 mb-2" style="text-align:center;">
            <a href="features.php" class="feature-back-btn"><?= $translations['back_to_features'] ?? '' ?></a>
        </div>
    </div>

    <?php include __DIR__.'/includes/footer.php'; ?>
    <?php include __DIR__.'/shared/inc/foot-assets.php'; ?>
    <?php include __DIR__.'/includes/theme-script.php'; ?>

<script>
window.fadeTheme = function(nextThemeClass) {
  var body = document.body;
  var overlay = document.querySelector('.theme-fade-overlay');
  if (!overlay) return;
  overlay.style.background = getComputedStyle(document.body).backgroundColor || '#fff';
  body.classList.add('theme-fade-active');
  setTimeout(function() {
    if(nextThemeClass === 'dark-theme') body.classList.add('dark-theme');
    else body.classList.remove('dark-theme');
    setTimeout(function(){
      overlay.style.background = getComputedStyle(document.body).backgroundColor || '#fff';
    }, 14);
    setTimeout(function(){
      body.classList.remove('theme-fade-active');
    }, 400);
  }, 28);
};
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var t = window.PAGE_TRANSLATIONS || {};
    var lang = <?= json_encode($lang) ?>;
    var featureId = window.FEATURE_ID;
    var featureDetail = document.getElementById('feature-detail');
    var featureLoading = document.getElementById('feature-detail-loading');
    var featureError = document.getElementById('feature-detail-error');
    featureError.style.display = 'none';

    if(!featureId) {
        featureLoading.style.display = "none";
        featureError.style.display = "block";
        featureError.textContent = t['feature_not_found'] || '';
        return;
    }

    var apiUrl = "https://api.xtremedev.co/endpoints/feature.php?id=" + featureId + "&lang=" + encodeURIComponent(lang);

    // نمایش آدرس درخواست در کنسول
    console.log('API/feature_request:', apiUrl);

    fetch(apiUrl)
    .then(function(res){
        if(!res.ok) throw new Error('API error');
        return res.json();
    })
    .then(function(f){
        // نمایش ریسپانس سرور در کنسول
        console.log('API/feature.php:', f);
        if (!f || f.error) {
            featureLoading.style.display = "none";
            featureError.style.display = "block";
            featureError.textContent = t['feature_not_found'] || '';
            return;
        }
        featureLoading.style.display = "none";
        var html = '';
        html += '<div class="feature-detail-card">';
        html +=   '<img class="feature-detail-img" src="'+(f.image_path||'https://via.placeholder.com/500x280?text=No+Image')+'" alt="'+(f.title||"")+'">';
        html +=   '<div class="feature-detail-title">'+
                    (f.title || t['untitled_feature'] || '') +
                    (f.badge ? ' <span class="feature-detail-badge" style="background:'+(f.badge_color||'#3ed2f0')+';">'+f.badge+'</span>' : '') +
                  '</div>';
        html +=   '<div class="feature-detail-desc">'+(f.description||'')+'</div>';
        html +=   '<div class="feature-detail-longdesc">'+(f.long_description||'')+'</div>';
        html += '</div>';
        featureDetail.innerHTML = html;
    })
    .catch(function(err){
        featureLoading.style.display = "none";
        featureError.style.display = "block";
        featureError.textContent = t['error_loading_features'] || '';
    });
});
</script>
</body>
</html>