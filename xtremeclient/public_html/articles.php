<?php
session_start();
require_once __DIR__ . '/shared/inc/config.php';

// فقط از کانفیگ برای زبان استفاده کن!
$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$lang = in_array($lang, ALLOWED_LANGS) ? $lang : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$project_id = 2;
$is_rtl = $lang === 'fa';

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
    <title><?= $translations['articles_and_news'] ?? '' ?></title>
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
    .articles-list-row {
      margin: 0 -12px;
    }
    .article-card {
      background: var(--surface-alt,#fff);
      border-radius: 18px;
      box-shadow: 0 4px 16px var(--shadow-card,#2499fa14);
      border: 2px solid var(--border,#2499fa18);
      margin-bottom: 1.8rem;
      transition: box-shadow 0.22s, border 0.18s, background 0.3s, transform 0.4s cubic-bezier(.38,1.3,.6,1), opacity 0.57s cubic-bezier(.38,1.3,.6,1);
      cursor: pointer;
      padding: 0;
      opacity: 0;
      transform: translateY(40px) scale(0.97);
      will-change: transform, opacity;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      min-height: 100%;
      height: 100%;
    }
    .article-card.visible {
      opacity: 1;
      transform: translateY(0) scale(1);
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
      min-height: 150px;
    }
    .dark-theme .article-thumb {
      background: #202b3b;
    }
    .article-card .p-3 { padding: 1.2rem 1.2rem 0.9rem 1.2rem; }
    .article-card h6 { color: var(--primary); font-weight: 700; font-size:1.08rem;margin-bottom:8px;}
    .article-excerpt {
      font-size: 0.99rem;
      min-height: 56px;
      color: var(--text,#222);
      opacity: 0.95;
      margin-bottom: 0.4rem;
    }
    .article-meta {
      display: flex;
      align-items: center;
      font-size: 0.89rem;
      color: #888;
      gap: 10px;
      margin-top: 0.4rem;
    }
    .article-date {
      font-family: inherit;
    }
    .error-message {
      color: #e63946;
      text-align: center;
      font-size: 1.05rem;
      margin: 22px auto 0 auto;
      display: none;
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
      height: 240px;
    }
    .dark-theme .skeleton-card {
      background: linear-gradient(90deg, #232d3b 25%, #1e2632 50%, #232d3b 75%);
    }
    @keyframes skeleton-loading {
      0% { background-position: 200% 0; }
      100% { background-position: -200% 0; }
    }
    @media (max-width: 991.98px) {
      .article-card .p-3 { padding: 0.9rem 0.7rem 0.7rem 0.7rem; }
    }
    @media (max-width: 767.98px) {
      .article-card .p-3 { padding: 0.7rem 0.5rem 0.7rem 0.5rem; }
      .skeleton-card { min-height: 110px; }
    }
    </style>
    <script>
        window.PAGE_TRANSLATIONS = <?= json_encode($translations, JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body<?= $darkThemeActive ? ' class="dark-theme"' : '' ?>>
    <div class="theme-fade-overlay"></div>
    <?php include __DIR__.'/includes/navbar.php'; ?>

    <div class="container py-4">
        <div class="section-title-wrap">
            <h1 class="section-title"><?= $translations['articles_and_news'] ?? '' ?></h1>
        </div>
        <div class="row articles-list-row" id="articles-list">
            <?php for($i=0;$i<6;$i++): ?>
                <div class="col-md-6 col-lg-4 skeleton-row">
                    <div class="skeleton-card"></div>
                </div>
            <?php endfor; ?>
        </div>
        <div id="articles-error" class="error-message"></div>
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
var t = window.PAGE_TRANSLATIONS || {};

document.addEventListener('DOMContentLoaded', function() {
    var lang = <?= json_encode($lang) ?>;
    var project_id = <?=intval($project_id)?>;
    var articlesList = document.getElementById('articles-list');
    var articlesError = document.getElementById('articles-error');
    articlesError.style.display = "none";
    var articlesApiUrl = "https://api.xtremedev.co/endpoints/articles_list.php?project_id=" + project_id + "&lang=" + encodeURIComponent(lang);

    fetch(articlesApiUrl)
        .then(function(res) {
            if(!res.ok) throw new Error("error_loading_articles");
            return res.json();
        })
        .then(function(list) {
            console.log('Articles API result:', list);
            if(!Array.isArray(list) || !list.length) {
                articlesList.innerHTML = "<div style='width:100%;text-align:center;color:#888'>" + (t['not_found_articles'] || '') + "</div>";
                return;
            }
            articlesList.innerHTML = "";
            list.forEach(function(a, idx) {
                if (!a.id) {
                    console.warn('Article without id at index', idx, a);
                }
                var col = document.createElement('div');
                col.className = "col-md-6 col-lg-4";
                var aTag = document.createElement('a');
                if (a.id) {
                    aTag.href = "article-detail.php?id=" + encodeURIComponent(a.id);
                } else {
                    aTag.href = "#";
                    aTag.style.pointerEvents = "none";
                    aTag.style.opacity = "0.6";
                }
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
                desc.className = "article-excerpt";
                desc.textContent = (a.content || "").replace(/<[^>]+>/g,'').slice(0,140) + (a.content && a.content.length > 140 ? "..." : "");
                p3.appendChild(desc);

                var meta = document.createElement('div');
                meta.className = "article-meta";
                var dateDiv = document.createElement('span');
                dateDiv.className = "article-date";
                if(a.created_at){
                    try {
                        dateDiv.textContent = new Date(a.created_at).toLocaleDateString(lang==='fa'?'fa-IR':'en-US', { year:'numeric', month:'short', day:'numeric' });
                    } catch(e) {
                        dateDiv.textContent = a.created_at;
                    }
                }
                meta.appendChild(dateDiv);
                p3.appendChild(meta);

                card.appendChild(p3);
                aTag.appendChild(card);
                col.appendChild(aTag);

                // اگر id نبود، در کارت اخطار بنویسیم
                if (!a.id) {
                    var warn = document.createElement('div');
                    warn.style.color = '#e63946';
                    warn.style.fontSize = '0.95rem';
                    warn.style.marginTop = '8px';
                    warn.textContent = 'No ID';
                    p3.appendChild(warn);
                }
                articlesList.appendChild(col);
            });
            // افکت ظاهر شدن کارت‌ها
            setTimeout(function() {
                var cards = document.querySelectorAll('.article-card');
                var windowHeight = window.innerHeight;
                cards.forEach(function(card) {
                    var rect = card.getBoundingClientRect();
                    if(rect.top < windowHeight - 60) {
                        card.classList.add('visible');
                    }
                    setTimeout(function(){
                        card.classList.add('visible');
                    }, 160);
                });
            }, 100);
        })
        .catch(function(err) {
            articlesList.innerHTML = "";
            articlesError.style.display = "block";
            articlesError.textContent = t['error_loading_articles'] || '';
            console.error('Articles API Error:', err);
        });
});
</script>
</body>
</html>