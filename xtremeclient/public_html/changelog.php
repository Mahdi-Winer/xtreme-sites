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
    <title><?= $translations['changelog_title'] ?? 'Change Log' ?></title>
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
    .changelog-list {
      max-width: 900px;
      margin: 0 auto 2.5rem auto;
    }
    .changelog-card {
      background: var(--surface-alt);
      border-radius: 22px;
      box-shadow: 0 6px 32px var(--shadow-card);
      border: 2px solid var(--border);
      margin-bottom: 2rem;
      padding: 2.1rem 2rem 1.8rem 2rem;
      transition: box-shadow 0.2s, border 0.2s, background 0.3s, transform 0.4s cubic-bezier(.38,1.3,.6,1);
      position: relative;
    }
    .changelog-card:hover, .changelog-card:focus {
      box-shadow: 0 16px 48px #2499fa39;
      border-color: var(--border-hover);
      background: var(--surface);
      transform: translateY(-3px) scale(1.012);
      z-index: 2;
    }
    .changelog-version {
      font-size: 1.35rem;
      font-weight: 900;
      color: #2499fa;
      margin-bottom: 0.7rem;
      display: flex;
      align-items: center;
      gap: 0.7em;
    }
    .changelog-release-date {
      font-size: 1.04rem;
      color: #6c757d;
      margin-bottom: 0.8rem;
      font-weight: bold;
      letter-spacing: 0.6px;
      display: inline-block;
    }
    .changelog-title {
      font-size: 1.18rem;
      font-weight: 800;
      color: var(--primary);
      margin-bottom: 0.8rem;
      margin-top: 0.2rem;
      letter-spacing: 0.2px;
    }
    .changelog-changes {
      font-size: 1.09rem;
      color: var(--text);
      margin-bottom: 0.7rem;
      line-height: 2;
    }
    .changelog-empty {
      text-align: center;
      color: #888;
      margin: 3.5rem 0 2.5rem 0;
      font-size: 1.2rem;
      opacity: 0.82;
    }
    .changelog-list-loading {
      text-align:center;
      padding:2.2rem 0;
      opacity:.7;
    }
    .changelog-list-loading .loader {
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
    @media (max-width: 767.98px) {
      .changelog-card {
        padding: 1rem 0.7rem 1rem 0.7rem;
      }
      .changelog-version {
        font-size: 1.05rem;
      }
      .changelog-title {
        font-size: 1rem;
      }
      .changelog-changes {
        font-size: 0.98rem;
      }
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
            <h1 class="section-title"><?= $translations['changelog_title'] ?? 'Change Log' ?></h1>
        </div>
        <div class="changelog-list" id="changelog-list">
            <div class="changelog-list-loading" id="changelog-loading">
              <div class="loader"></div>
            </div>
        </div>
        <div id="changelog-error" class="error-message"></div>
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
    var changelogList = document.getElementById('changelog-list');
    var changelogLoading = document.getElementById('changelog-loading');
    var changelogError = document.getElementById('changelog-error');
    changelogError.style.display = 'none';

    var changelogApi = "https://api.xtremedev.co/endpoints/changelog.php?project_id=2&lang=" + encodeURIComponent(lang);

    // نمایش آدرس درخواست در کنسول
    console.log('API/changelog_request:', changelogApi);

    fetch(changelogApi)
      .then(function(res){
          if(!res.ok) throw new Error('API error');
          return res.json();
      })
      .then(function(changelogs){
          console.log('API/changelog.php:', changelogs); // نمایش ریسپانس کامل در کنسول

          if (!Array.isArray(changelogs) || changelogs.length === 0) {
              changelogList.innerHTML = '<div class="changelog-empty">' + (t['not_found_changelogs'] || 'No changelogs found.') + '</div>';
              return;
          }
          changelogLoading.style.display = "none";
          var html = "";
          changelogs.forEach(function(cl){
              html += '<div class="changelog-card">';
              html +=   '<div class="changelog-version">' + (t['changelog_version'] || 'Version') + ' ' + (cl.version || '') + '</div>';
              html +=   '<div class="changelog-release-date">' + (t['changelog_release_date'] || 'Released:') + ' ' + (cl.release_date || '') + '</div>';
              if(cl.title)
                html += '<div class="changelog-title">' + cl.title + '</div>';
              html +=   '<div class="changelog-changes">' + (cl.changes||'') + '</div>';
              html += '</div>';
          });
          changelogList.innerHTML = html;
      })
      .catch(function(err){
          changelogLoading.style.display = "none";
          changelogError.style.display = "block";
          changelogError.textContent = t['error_loading_changelogs'] || 'Error loading changelogs.';
      });
});
</script>
</body>
</html>