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
    <title id="site-title"><?= $translations['home'] ?? '' ?></title>
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
    .hero-section, .feature-card-alt, .fullwidth-slider-wrap, .download-card, .article-card, .skeleton-card, #mainSlider, #mainSlider .carousel-inner {
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
    .hero-section {
      background: linear-gradient(120deg, var(--primary) 75%, var(--surface-alt) 100%);
      color: #fff;
      min-height: 33vh;
      display: flex;
      align-items: center;
      padding-top: 78px;
      padding-bottom: 36px;
      text-align: center;
    }
    .dark-theme .hero-section {
      background: linear-gradient(120deg, #1f2533 75%, #24324d 100%);
    }
    .hero-content h1 {
      font-weight: 900;
      font-size: 2.6rem;
      letter-spacing: 1.2px;
      margin-bottom: 1.1rem;
    }
    .hero-content p {
      font-size: 1.19rem;
      opacity: 0.96;
      margin-bottom: 0;
    }
    .hero-content img {
      max-width:130px;
      max-height:98px;
      margin-bottom:22px;
      display:none;
    }
    .dark-theme .hero-content h1,
    .dark-theme .hero-content p {
      color: #e6e9f2;
      text-shadow: 0 1px 5px #151c2e44;
    }
    .fullwidth-slider-wrap {
      width: 100vw;
      max-width: 100vw;
      position: relative;
      left: 50%;
      right: 50%;
      margin-left: -50vw;
      margin-right: -50vw;
      background: #151c2e;
      z-index: 1;
    }
    .dark-theme .fullwidth-slider-wrap {
      background: #181f2a;
    }
    #mainSlider,
    #mainSlider .carousel-inner {
      direction: ltr !important;
    }
    #mainSlider.carousel {
      width: 100vw !important;
      max-width: 100vw !important;
      position: relative;
      margin: 0 auto 48px auto;
      background: #151c2e;
      border-radius: 0;
      box-shadow: 0 4px 40px #2499fa13;
      overflow: hidden;
    }
    .dark-theme #mainSlider.carousel {
      background: #181f2a;
      box-shadow: 0 4px 40px #15203244;
    }
    #mainSlider .carousel-inner {
      width: 100vw;
      min-height: 320px;
      background: #151c2e;
    }
    .dark-theme #mainSlider .carousel-inner {
      background: #181f2a;
    }
    #mainSlider .carousel-item img {
      width: 100vw;
      min-height: 320px;
      max-height: 64vh;
      object-fit: cover;
      object-position: center center;
      border-radius: 0;
      box-shadow: none;
      margin: 0;
      display: block;
      background: #151c2e;
    }
    .dark-theme #mainSlider .carousel-item img {
      background: #181f2a;
    }
    #mainSlider .carousel-caption {
      background: rgba(36,153,250,0.15);
      border-radius: 18px;
      padding: 1.2rem 2.3rem;
      color: #fff;
      text-shadow: 0 2px 6px #0002;
      bottom: 35px;
    }
    .dark-theme #mainSlider .carousel-caption {
      background: rgba(36,153,250,0.25);
      color: #e6e9f2;
      text-shadow: 0 2px 12px #0006;
    }
    #mainSlider .carousel-control-prev,
    #mainSlider .carousel-control-next {
      width: 6%;
    }
    @media (max-width: 991.98px) {
      #mainSlider .carousel-inner,
      #mainSlider .carousel-item img {
        min-height: 200px;
        max-height: 42vh;
      }
      #mainSlider .carousel-caption {
        padding: .8rem 1.2rem;
        font-size: 1rem;
        bottom: 18px;
      }
    }
    @media (max-width: 767.98px) {
      .fullwidth-slider-wrap { min-width: 100vw; }
      #mainSlider .carousel-inner,
      #mainSlider .carousel-item img {
        min-height: 130px;
        max-height: 210px;
      }
      #mainSlider .carousel-caption {
        font-size: 0.97rem;
        bottom: 8px;
      }
    }
    .features-list-alternating {
      max-width: 1050px;
      margin: 0 auto;
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
    .section-title.features-title {
      margin-top: 2.7rem;
      margin-bottom: 2.3rem;
    }
    .section-title.features-title:after {
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
    .article-card {
      background: var(--surface-alt,#fff);
      border-radius: 18px;
      box-shadow: 0 4px 16px var(--shadow-card,#2499fa14);
      border: 2px solid var(--border,#2499fa18);
      margin-bottom: 1.2rem;
      transition: box-shadow 0.2s, border 0.2s, background 0.3s, transform 0.4s cubic-bezier(.38,1.3,.6,1), opacity 0.57s cubic-bezier(.38,1.3,.6,1);
      cursor: pointer;
      padding: 0;
      opacity: 0;
      transform: translateY(40px) scale(0.97);
      will-change: transform, opacity;
      overflow: hidden;
    }
    .article-card.visible {
      opacity: 1; transform: translateY(0) scale(1);
    }
    .article-card:hover {
      box-shadow: 0 12px 38px 0 #2499fa4a, 0 2px 8px 0 #2499fa22;
      border-color: var(--border-hover,#2499fa44);
      background: var(--surface,#f4f7fa);
      transform: translateY(-6px) scale(1.035);
      z-index: 2;
    }
    .article-thumb {
      width: 100%;
      aspect-ratio: 16 / 9;
      object-fit: cover;
      border-radius: 18px 18px 0 0;
      background: #f3f6fb;
      display: block;
    }
    .dark-theme .article-thumb {
      background: #202b3b;
    }
    .article-card .p-3 { padding: 1.2rem 1.2rem 0.9rem 1.2rem; }
    @media (max-width: 767.98px) {
      .article-card .p-3 { padding: 0.9rem 0.7rem 0.7rem 0.7rem; }
    }
    .skeleton-row { margin-bottom: 1.2rem; }
    .skeleton-card {
      background: linear-gradient(90deg, #f4f7fa 25%, #e6edf5 50%, #f4f7fa 75%);
      background-size: 200% 100%;
      animation: skeleton-loading 1.13s infinite linear;
      border-radius: 18px;
      min-height: 182px;
      width: 100%;
      display: block;
    }
    .dark-theme .skeleton-card {
      background: linear-gradient(90deg, #232d3b 25%, #1e2632 50%, #232d3b 75%);
    }
    @keyframes skeleton-loading {
      0% { background-position: 200% 0; }
      100% { background-position: -200% 0; }
    }
    .error-message {
      color: #e63946;
      text-align: center;
      font-size: 1.05rem;
      margin: 18px auto 0 auto;
      display: none;
    }
    .download-card {
      background: linear-gradient(120deg, #2499fa 70%, #3ed2f0 100%);
      color: #fff;
      border-radius: 19px;
      box-shadow: 0 6px 32px #2499fa1f;
      margin: 64px auto 38px auto;
      max-width: 700px;
      padding: 1.6rem 2.2rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1.5rem;
      flex-wrap: wrap;
    }
    .dark-theme .download-card {
      background: linear-gradient(120deg, #1d2533 70%, #24324d 100%);
      color: #fff;
    }
    .download-card img {
      width: 54px;
      height: 54px;
      object-fit: contain;
      margin-left: 12px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 1px 6px #2499fa1a;
      padding: 7px;
      display: block;
      transition: background 0.5s cubic-bezier(.62,1.5,.33,1);
    }
    .dark-theme .download-card img {
      background: #232d3b;
    }
    .download-card .message {
      font-size: 1.29rem;
      font-weight: 700;
      letter-spacing: 0.7px;
    }
    .download-card .download-button {
      font-size: 1.14rem;
      font-weight: 700;
      letter-spacing: .8px;
      padding: 0.8em 2.1em;
      border-radius: 16px;
      background: #fff;
      color: #2499fa;
      border: none;
      box-shadow: 0 2px 8px #2499fa1b;
      transition: background 0.15s, color 0.15s, box-shadow 0.18s;
      text-decoration: none;
      margin-left: auto;
      display: inline-block;
    }
    .dark-theme .download-card .download-button {
      background: #202b3b;
      color: #3ed2f0;
    }
    .download-card .download-button:hover {
      background: #2499fa;
      color: #fff;
      box-shadow: 0 8px 30px #2499fa22;
    }
    .dark-theme .download-card .download-button:hover {
      background: #3ed2f0;
      color: #202b3b;
    }
    @media (max-width: 767.98px) {
      .download-card {
        flex-direction: column;
        align-items: stretch;
        gap: 1.3rem;
        padding: 1.1rem 0.8rem;
      }
      .download-card .message {
        font-size: 1.01rem;
        margin: 0.6rem 0 0 0;
      }
      .download-card img {
        margin: 0 auto 0.7rem auto;
      }
      .download-card .download-button {
        width: 100%;
        margin: 0;
      }
    }
    .features-all-btn {
      display: inline-block;
      margin: 2.2rem auto 0 auto;
      background: linear-gradient(90deg, #2499fa 15%, #3ed2f0 85%);
      color: #fff;
      font-weight: 700;
      font-size: 1.1rem;
      padding: 0.8em 2.2em;
      border-radius: 15px;
      border: none;
      text-decoration: none;
      box-shadow: 0 2px 10px #2499fa1b;
      transition: background 0.18s, color 0.18s, box-shadow 0.18s;
    }
    .features-all-btn:hover, .features-all-btn:focus {
      background: linear-gradient(90deg, #2070d6 10%, #3ed2f0 90%);
      color: #fff;
      box-shadow: 0 8px 24px #2499fa22;
    }
    .features-btn-center {
      text-align: center;
      margin-top: -1.5rem;
      margin-bottom: 2.3rem;
    }
    </style>
    <script>
        window.PAGE_TRANSLATIONS = <?= json_encode($translations, JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body<?= $darkThemeActive ? ' class="dark-theme"' : '' ?>>
    <div class="theme-fade-overlay"></div>
    <?php include __DIR__.'/includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <img id="site-logo" src="" alt="Logo" style="display:none;">
                <h1 id="hero-title"></h1>
                <p id="hero-intro"></p>
            </div>
        </div>
    </section>

    <!-- Slider -->
    <div class="fullwidth-slider-wrap">
      <div id="mainSlider" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4200">
        <div class="carousel-inner" id="slider-content">
            <div class="carousel-item active" id="slider-loading">
                <div style="width:100vw;height:320px;display:flex;align-items:center;justify-content:center;">
                    <div style="width:2.6rem;height:2.6rem;border:4px solid #eee;border-top:4px solid #2499fa;border-radius:50%;animation:spin 1s linear infinite"></div>
                </div>
                <style>@keyframes spin{100%{transform:rotate(360deg)}}</style>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#mainSlider" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#mainSlider" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
      </div>
    </div>

    <!-- Features Section -->
    <div class="container my-5">
      <div class="section-title-wrap">
        <h2 class="section-title features-title"><?= $translations['client_features'] ?? '' ?></h2>
      </div>
      <div class="features-list-alternating" id="features-list">
        <div id="features-loading" style="text-align:center;width:100%;padding:2.2rem 0;opacity:.7;">
          <div style="width:2.3rem;height:2.3rem;border:4px solid #eee;border-top:4px solid #2499fa;border-radius:50%;animation:spin 1s linear infinite;margin:auto"></div>
          <style>@keyframes spin{100%{transform:rotate(360deg)}}</style>
        </div>
      </div>
      <div class="features-btn-center">
        <a href="features.php" class="features-all-btn"><?= $translations['see_all_features'] ?? '' ?></a>
      </div>
    </div>

    <!-- Articles Section -->
    <section id="articles" class="container py-5">
        <div class="section-title-wrap">
            <h2 class="section-title"><?= $translations['articles_and_news'] ?? '' ?></h2>
        </div>
        <div class="row" id="articles-list">
            <?php for($i=0;$i<3;$i++): ?>
                <div class="col-md-6 col-lg-4 skeleton-row">
                    <div class="skeleton-card"></div>
                </div>
            <?php endfor; ?>
        </div>
        <div id="articles-error" class="error-message"></div>
        <div class="text-center mt-4">
            <a href="articles.php" class="btn btn-outline-primary px-4 fw-bold"><?= $translations['see_all_articles'] ?? '' ?></a>
        </div>
    </section>

    <!-- Download Card Section -->
    <div class="download-card">
      <div class="d-flex align-items-center">
        <img src="http://dl.xtremedev.co/company-resourse/xtreme-company-logo-textless-blue.png" alt="Logo">
        <span class="message ms-3"><?= $translations['buy_and_download_now'] ?? '' ?></span>
      </div>
      <a href="#" class="download-button"><?= $translations['buy'] ?? '' ?></a>
    </div>

    <?php include __DIR__.'/includes/footer.php'; ?>
    <?php include __DIR__.'/shared/inc/foot-assets.php'; ?>
    <?php include __DIR__.'/includes/theme-script.php'; ?>

<script>
// افکت فید تم
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
    var site_project_id = <?=intval($project_id)?>;

    // Hero section (site info)
    var siteTitleEl = document.getElementById('hero-title');
    var siteIntroEl = document.getElementById('hero-intro');
    var siteLogoEl = document.getElementById('site-logo');
    var titleTag = document.getElementById('site-title');

    siteTitleEl.textContent = "...";
    siteIntroEl.textContent = "...";
    siteLogoEl.style.display = 'none';

    // SETTINGS
    fetch("https://api.xtremedev.co/endpoints/settings.php?project_id=2&lang=" + encodeURIComponent(lang))
        .then(function(res){
            if(!res.ok) throw new Error('API error');
            return res.json();
        })
        .then(function(data) {
            console.log('API/settings.php:', data); // نمایش ریسپانس در کنسول

            if(data.site_title) {
                siteTitleEl.textContent = data.site_title;
                document.title = data.site_title;
                if(titleTag) titleTag.textContent = data.site_title;
            }
            if(data.site_intro) {
                siteIntroEl.innerHTML = (data.site_intro+"").replace(/\n/g, "<br>");
            }
            if(data.logo_url) {
                siteLogoEl.src = data.logo_url;
                siteLogoEl.style.display = 'inline-block';
            } else {
                siteLogoEl.style.display = 'none';
            }
        })
        .catch(function(err) {
            siteTitleEl.textContent = t['error_loading_site_settings'] || '';
            siteIntroEl.textContent = '';
            siteLogoEl.style.display = 'none';
        });

    // SLIDER SECTION
    var sliderContent = document.getElementById('slider-content');
    var sliderLoading = document.getElementById('slider-loading');
    var sliderApi = "https://api.xtremedev.co/endpoints/slider_data.php?project_id=2&lang=" + encodeURIComponent(lang);

    fetch(sliderApi)
      .then(function(res){
          if(!res.ok) throw new Error('API error');
          return res.json();
      })
      .then(function(slides){
          console.log('API/slider_data.php:', slides); // نمایش ریسپانس در کنسول
          console.log('API/slider_request:', "https://api.xtremedev.co/endpoints/slider_data.php?project_id=2&lang=" + encodeURIComponent(lang));

          if (!Array.isArray(slides) || slides.length === 0) {
              if(sliderLoading) sliderLoading.innerHTML = t['no_slides_found'] || "";
              return;
          }
          var html = "";
          slides.sort(function(a,b){return (a.slide_order||0)-(b.slide_order||0);});
          slides.forEach(function(slide, idx){
              html += '<div class="carousel-item'+(idx===0?' active':'')+'">';
              html += '<img src="'+slide.image_path+'" class="d-block w-100" alt="'+(slide.title||'')+'">';
              html += '<div class="carousel-caption d-none d-md-block">';
              html += '<h5>'+slide.title+'</h5>';
              html += '<p>'+(slide.description||"")+'</p>';
              html += '</div></div>';
          });
          sliderContent.innerHTML = html;

          var mainSlider = document.getElementById('mainSlider');
          if (mainSlider && mainSlider.carouselInstance) {
              mainSlider.carouselInstance.dispose();
              delete mainSlider.carouselInstance;
          }
          if (mainSlider && typeof bootstrap !== "undefined" && bootstrap.Carousel) {
              mainSlider.carouselInstance = new bootstrap.Carousel(mainSlider, {
                  interval: 4200,
                  ride: 'carousel'
              });
          }
      })
      .catch(function(){
          if(sliderLoading) sliderLoading.innerHTML = t['error_loading_slides'] || "";
      });

    // FEATURES SECTION
    var featuresList = document.getElementById('features-list');
    var featuresLoading = document.getElementById('features-loading');
    var featuresApi = "https://api.xtremedev.co/endpoints/features.php?project_id=2&lang=" + encodeURIComponent(lang);

    fetch(featuresApi)
      .then(function(res){
          if(!res.ok) throw new Error('API error');
          return res.json();
      })
      .then(function(features){
          console.log('API/features.php:', features); // نمایش ریسپانس در کنسول

          if (!Array.isArray(features) || features.length === 0) {
              if(featuresLoading) featuresLoading.innerHTML = t['not_found_features'] || "";
              return;
          }
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
                              (f.title||"")+
                              (f.badge ? ' <span class="feature-card-alt-badge" style="background:'+(f.badge_color||'#3ed2f0')+';">'+f.badge+'</span>' : '') +
                          '</div>';
              html +=     '<div class="feature-card-alt-desc">'+(f.description||"")+'</div>';
              html +=     '<a href="#" class="feature-card-alt-link">'+ (t['more'] || '') +'</a>';
              html +=   '</div>';
              html += '</div>';
          });
          featuresList.innerHTML = html;
      })
      .catch(function(){
          if(featuresLoading) featuresLoading.innerHTML = t['error_loading_features'] || "";
      });
});
</script>
<script>
(function(){
    var t = window.PAGE_TRANSLATIONS || {};
    var lang = <?= json_encode($lang) ?>;

    function revealOnScroll() {
        var cards = document.querySelectorAll('.article-card');
        var windowHeight = window.innerHeight;
        cards.forEach(function(card) {
            var rect = card.getBoundingClientRect();
            if(rect.top < windowHeight - 60) {
                card.classList.add('visible');
            }
        });
    }
    window.addEventListener('scroll', revealOnScroll, {passive:true});
    window.addEventListener('resize', revealOnScroll);

    document.addEventListener('DOMContentLoaded', function() {
        var articlesList = document.getElementById('articles-list');
        var articlesError = document.getElementById('articles-error');
        articlesError.style.display = "none";
        var articlesApiUrl = "https://api.xtremedev.co/endpoints/articles_list.php?project_id=2&lang=" + encodeURIComponent(lang);

        fetch(articlesApiUrl)
            .then(function(res) {
                if(!res.ok) throw new Error("error_loading_articles");
                return res.json();
            })
            .then(function(list) {
                console.log('API/articles_list.php:', list); // نمایش ریسپانس در کنسول

                if(!Array.isArray(list) || !list.length) {
                    articlesList.innerHTML = "<div style='width:100%;text-align:center;color:#888'>" + (t['not_found_articles'] || "") + "</div>";
                    return;
                }
                articlesList.innerHTML = "";
                list.slice(0,3).forEach(function(a) {
                    var col = document.createElement('div');
                    col.className = "col-md-6 col-lg-4";
                    var aTag = document.createElement('a');
                    aTag.href = "article-detail.php?id=" + encodeURIComponent(a.id);
                    aTag.style.textDecoration = "none";
                    aTag.style.color = "inherit";

                    var card = document.createElement('div');
                    card.className = "article-card shadow-sm h-100 p-0";

                    if(a.thumbnail && a.thumbnail.trim()) {
                        var img = document.createElement('img');
                        img.className = "article-thumb";
                        img.src = a.thumbnail;
                        img.alt = a.title || '';
                        card.appendChild(img);
                    }

                    var p3 = document.createElement('div');
                    p3.className = "p-3";
                    var h6 = document.createElement('h6');
                    h6.textContent = a.title || t['untitled_article'] || '';
                    p3.appendChild(h6);

                    var desc = document.createElement('div');
                    desc.style.fontSize = "0.97rem";
                    desc.textContent = (a.content || "").replace(/<[^>]+>/g,'').slice(0,120) + (a.content && a.content.length > 120 ? "..." : "");
                    p3.appendChild(desc);

                    var dateDiv = document.createElement('div');
                    dateDiv.style.color = "#888";
                    dateDiv.style.fontSize = "0.87rem";
                    dateDiv.style.marginTop = "0.4rem";
                    if(a.created_at){
                        try {
                            dateDiv.textContent = new Date(a.created_at).toLocaleDateString(lang==='fa'?'fa-IR':'en-US', { year:'numeric', month:'short', day:'numeric' });
                        } catch(e) {
                            dateDiv.textContent = a.created_at;
                        }
                    }
                    p3.appendChild(dateDiv);

                    card.appendChild(p3);
                    aTag.appendChild(card);
                    col.appendChild(aTag);
                    articlesList.appendChild(col);
                });
                revealOnScroll();
            })
            .catch(function(err) {
                articlesList.innerHTML = "";
                articlesError.style.display = "block";
                articlesError.textContent = t['error_loading_articles'] || "";
            });
    });
})();
</script>
</body>
</html>