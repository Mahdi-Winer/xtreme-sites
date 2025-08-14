<?php
session_start();
require_once __DIR__ . '/shared/inc/config.php';

// زبان فقط از کانفیگ
$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$project_id = 2;
$is_rtl = $lang === 'fa';

// فقط و فقط بارگذاری ترجمه از فایل زبان
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
    <title><?= $translations['client_features'] ?? 'Features' ?></title>
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
    html, body, :root {
      transition: background 0.5s cubic-bezier(.62,1.5,.33,1),
                  background-color 0.5s cubic-bezier(.62,1.5,.33,1),
                  color 0.5s cubic-bezier(.62,1.5,.33,1);
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
    .section-title {
      font-weight: 900;
      font-family: Vazirmatn, Tahoma, Arial, sans-serif;
      font-size: 2.1rem;
      letter-spacing: 1.3px;
      text-align: center;
      margin-bottom: 2.5rem;
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
    .section-title:after {
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
    .section-title-wrap {
      text-align: center;
    }
    .features-list-alternating {
      max-width: 1100px;
      margin: 0 auto 2.5rem auto;
    }
    .feature-card-alt {
      background: var(--surface-alt);
      border-radius: 22px;
      box-shadow: 0 6px 32px var(--shadow-card);
      border: 2px solid var(--border);
      margin-bottom: 2.7rem;
      padding: 0;
      overflow: hidden;
      display: flex;
      flex-direction: row;
      align-items: stretch;
      min-height: 210px;
      transition: box-shadow 0.2s, border 0.2s, background 0.3s, transform 0.4s cubic-bezier(.38,1.3,.6,1);
    }
    .feature-card-alt.even {
      flex-direction: row-reverse;
    }
    .feature-card-alt:hover, .feature-card-alt:focus {
      box-shadow: 0 16px 48px #2499fa39;
      border-color: var(--border-hover);
      background: var(--surface);
      transform: translateY(-3px) scale(1.012);
      z-index: 2;
    }
    .feature-card-alt-imgbox {
      flex: 1 1 320px;
      min-width: 180px;
      max-width: 46%;
      background: #f3f6fb;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.5s cubic-bezier(.62,1.5,.33,1);
    }
    .dark-theme .feature-card-alt-imgbox {
      background: #202b3b;
    }
    .feature-card-alt-img {
      width: 100%;
      height: 100%;
      aspect-ratio: 16/9;
      object-fit: cover;
      border-radius: 0;
      min-height: 160px;
      max-height: 100%;
      display: block;
    }
    .feature-card-alt-content {
      flex: 2 1 360px;
      padding: 2.2rem 2.1rem 2.1rem 2.1rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .feature-card-alt-title {
      font-size: 1.5rem;
      font-weight: 900;
      color: #2499fa;
      margin-bottom: 0.8rem;
      display: flex;
      align-items: center;
      gap: 0.8em;
    }
    .feature-card-alt-badge {
      display: inline-block;
      background: #3ed2f0;
      color: #fff;
      font-size: 1.02em;
      font-weight: 800;
      border-radius: 10px;
      padding: 0.16em 1em;
      margin-right: 0.7em;
      letter-spacing: 0.2px;
      box-shadow: 0 2px 7px #3ed2f033;
    }
    .feature-card-alt-desc {
      font-size: 1.07rem;
      opacity: 0.97;
      margin-bottom: 1.37rem;
      margin-top: 0.3rem;
      line-height: 1.7;
      color: var(--text);
    }
    .feature-card-alt-link {
      font-size: 1.09rem;
      font-weight: 700;
      letter-spacing: .6px;
      padding: 0.7em 2.1em;
      border-radius: 12px;
      background: #2499fa;
      color: #fff;
      border: none;
      display: inline-block;
      box-shadow: 0 2px 8px #2499fa1b;
      transition: background 0.17s, color 0.17s, box-shadow 0.17s;
      text-decoration: none;
      margin-top: auto;
      align-self: flex-end;
    }
    .feature-card-alt-link:hover {
      background: #2070d6;
      color: #fff;
      box-shadow: 0 6px 22px #2499fa22;
    }
    @media (max-width: 991.98px) {
      .feature-card-alt-content {
        padding: 1.3rem 1rem 1.2rem 1rem;
      }
      .feature-card-alt-title { font-size: 1.15rem; }
      .feature-card-alt { min-height: 110px; }
      .feature-card-alt-imgbox { max-width: 49%; }
    }
    @media (max-width: 767.98px) {
      .feature-card-alt, .feature-card-alt.even {
        flex-direction: column !important;
        min-height: 0;
      }
      .feature-card-alt-imgbox { max-width: 100%; }
      .feature-card-alt-content { padding: 1.2rem 0.5rem 1.2rem 0.5rem; }
      .feature-card-alt-title { font-size: 1.07rem;}
    }
    .features-list-loading {
      text-align:center;
      padding:2.2rem 0;
      opacity:.7;
    }
    .features-list-loading .loader {
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
    </style>
    <script>
        window.PAGE_TRANSLATIONS = <?= json_encode($translations, JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body<?= $darkThemeActive ? ' class="dark-theme"' : '' ?>>
    <div class="theme-fade-overlay"></div>
    <?php include __DIR__.'/includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="section-title-wrap">
            <h1 class="section-title"><?= $translations['client_features'] ?? '' ?></h1>
        </div>
        <div class="features-list-alternating" id="features-list">
            <div class="features-list-loading" id="features-loading">
              <div class="loader"></div>
            </div>
        </div>
        <div id="features-error" class="error-message"></div>
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
    var project_id = <?=intval($project_id)?>;
    var featuresList = document.getElementById('features-list');
    var featuresLoading = document.getElementById('features-loading');
    var featuresError = document.getElementById('features-error');
    featuresError.style.display = 'none';

    var featuresApi = "https://api.xtremedev.co/endpoints/features.php?project_id=2&lang=" + encodeURIComponent(lang);

    fetch(featuresApi)
      .then(function(res){
          if(!res.ok) throw new Error('API error');
          return res.json();
      })
      .then(function(features){
          console.log('API/features.php:', features); // نمایش ریسپانس کامل در کنسول

          if (!Array.isArray(features) || features.length === 0) {
              featuresLoading.innerHTML = t['not_found_features'] || "No features found.";
              return;
          }
          featuresLoading.style.display = "none";
          var html = "";
          features.sort(function(a,b){return (a.feature_order||0)-(b.feature_order||0);});
          features.forEach(function(f, idx){
              var evenClass = (idx % 2 === 1) ? ' even' : '';
              html += '<div class="feature-card-alt'+evenClass+'">';
              html +=   '<div class="feature-card-alt-imgbox">';
              html +=     '<img class="feature-card-alt-img" src="'+(f.image_path||'https://via.placeholder.com/500x280?text=No+Image')+'" alt="'+(f.title||"")+'">';
              html +=   '</div>';
              html +=   '<div class="feature-card-alt-content">';
              html +=     '<div class="feature-card-alt-title">'+
                              (f.title || t['untitled_feature'] || "")+
                              (f.badge ? ' <span class="feature-card-alt-badge" style="background:'+(f.badge_color||'#3ed2f0')+';">'+f.badge+'</span>' : '') +
                          '</div>';
              html +=     '<div class="feature-card-alt-desc">'+(f.description||"")+'</div>';
              html +=     '<a href="feature_detail.php?id='+encodeURIComponent(f.id)+'" class="feature-card-alt-link">'+ (t['more'] || 'More') +'</a>';
              html +=   '</div>';
              html += '</div>';
          });
          featuresList.innerHTML = html;
      })
      .catch(function(err){
          featuresLoading.style.display = "none";
          featuresError.style.display = "block";
          featuresError.textContent = t['error_loading_features'] || 'Error loading features.';
      });
});
</script>
</body>
</html>