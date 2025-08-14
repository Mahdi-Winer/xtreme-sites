<?php
session_start();
require_once __DIR__ . '/shared/inc/config.php';

$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$project_id = 1;
$is_rtl = $lang === 'fa';

// بارگذاری ترجمه از فایل زبان
$translations = [];
$lang_file = __DIR__ . '/shared/assets/languages/' . $lang . '.json';
if (file_exists($lang_file)) {
    $json = file_get_contents($lang_file);
    $translations = json_decode($json, true);
}
$darkThemeActive = (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark');
?>
<!DOCTYPE html>
<html lang="<?=htmlspecialchars($lang)?>" dir="<?=($is_rtl?'rtl':'ltr')?>">
<head>
    <meta charset="UTF-8">
    <title id="site-title"><?= $translations['articles'] ?? '' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include 'shared/inc/head-assets.php'; ?>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700&display=swap" rel="stylesheet">
    <?php include 'includes/styles.php'; ?>
    <style>
        :root {
            --surface: #f4f7fa;
            --surface-alt: #fff;
            --text: #222;
            --primary: #2499fa;
            --shadow-card: #2499fa14;
            --border: #2499fa18;
            --border-hover: #2499fa44;
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-content {
            flex: 1 0 auto;
            display: flex;
            flex-direction: column;
        }
        .section-title {
            font-weight: 900;
            font-family: Vazirmatn, Tahoma, Arial, sans-serif;
            font-size: 2.1rem;
            letter-spacing: 1.3px;
            text-align: center;
            margin-bottom: 2.5rem; margin-top: 2.5rem;
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
        .section-title-wrap { text-align: center; }

        .article-card {
            background: var(--surface-alt);
            border-radius: 18px;
            box-shadow: 0 4px 16px var(--shadow-card);
            border: 2px solid var(--border);
            margin-bottom: 1.2rem;
            transition: box-shadow 0.2s, border 0.2s, background 0.3s, transform 0.4s cubic-bezier(.38,1.3,.6,1), opacity 0.57s cubic-bezier(.38,1.3,.6,1);
            cursor: pointer;
            padding: 0;
            opacity: 0;
            transform: translateY(40px) scale(0.97);
            will-change: transform, opacity;
            min-height: 160px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }
        .article-card.visible {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        .article-card:hover, .article-card:focus {
            box-shadow: 0 12px 38px 0 #2499fa4a, 0 2px 8px 0 #2499fa22;
            border-color: var(--border-hover);
            background: var(--surface);
            transform: translateY(-6px) scale(1.035);
            z-index: 2;
        }
        .article-thumb {
            width: 100%;
            height: 110px;
            object-fit: cover;
            border-radius: 18px 18px 0 0;
            background: #f3f6fb;
            display: block;
            transition: transform 0.33s cubic-bezier(.45,1.5,.6,1), box-shadow 0.33s;
        }
        .article-card:hover .article-thumb,
        .article-card:focus .article-thumb {
            transform: scale(1.04) translateY(-4px);
            box-shadow: 0 8px 24px #2499fa22;
        }
        .article-card .p-3 {
            padding: 0.8rem 1rem 0.6rem 1rem;
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }
        .article-card h6 {
            color: var(--primary);
            font-weight: 800;
            font-size: 1.13rem;
            margin-bottom: 0.7rem;
            line-height: 1.3;
            min-height: 30px;
        }
        .article-snippet {
            font-size: 0.98rem;
            color: var(--text);
            margin-bottom: 0.5rem;
            flex: 1 1 auto;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-clamp: 2;
            line-height: 1.55;
            min-height: 2.9em;
            transition: color 0.3s;
        }
        .article-date {
            color: #888;
            font-size: 0.87rem;
            margin-top: 0.3rem;
            text-align: <?=($lang==='fa'?'left':'right')?>;
        }
        .dark-theme .article-card h6 { color: #38a8ff; }
        .dark-theme .article-snippet { color: #d8e3f7; }
        .dark-theme .article-date { color: #a7b9da; }
        .skeleton-row { margin-bottom: 24px; }
        .skeleton-article {
            background: linear-gradient(90deg, #f4f7fa 25%, #e6edf5 50%, #f4f7fa 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.1s infinite linear;
            border-radius: 18px;
            min-height: 220px;
            width: 100%;
        }
        .dark-theme .skeleton-article {
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
    </style>
    <script>
        window.PAGE_TRANSLATIONS = <?= json_encode($translations, JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body<?= $darkThemeActive ? ' class="dark-theme"' : '' ?>>
<?php include 'includes/navbar.php'; ?>
<div class="main-content">
    <section class="container py-5">
        <div class="section-title-wrap">
            <h1 class="section-title" id="articles-page-title"><?= $translations['articles'] ?? '' ?></h1>
        </div>
        <div class="row" id="articles-list">
            <?php for($i=0;$i<6;$i++): ?>
                <div class="col-md-6 col-lg-4 mb-4 skeleton-row">
                    <div class="skeleton-article" style="height:220px;"></div>
                </div>
            <?php endfor; ?>
        </div>
        <div id="articles-error" class="error-message"></div>
    </section>
</div>
<?php include 'includes/footer.php'; ?>
<?php include __DIR__.'/shared/inc/foot-assets.php'; ?>
<?php include 'includes/theme-script.php'; ?>
<script>
var t = window.PAGE_TRANSLATIONS || {};
var lang = <?=json_encode($lang)?>;
var project_id = <?=intval($project_id)?>;

document.addEventListener('DOMContentLoaded', function(){
    var titleTag = document.getElementById('site-title');
    var pageTitleEl = document.getElementById('articles-page-title');
    fetch("https://api.xtremedev.co/endpoints/settings.php?project_id=" + project_id + "&lang=" + encodeURIComponent(lang))
        .then(function(res){
            if(!res.ok) throw new Error('API error');
            return res.json();
        })
        .then(function(data) {
            if(data.site_title) {
                var newTitle = (t['articles'] || '') + (data.site_title ? " | " + data.site_title : "");
                document.title = newTitle;
                if(titleTag) titleTag.textContent = newTitle;
            }
            if(data.articles_page_title && pageTitleEl) {
                pageTitleEl.textContent = data.articles_page_title;
            }
        })
        .catch(function(){});
});

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
window.addEventListener('DOMContentLoaded', revealOnScroll);
setTimeout(revealOnScroll, 200);

document.addEventListener('DOMContentLoaded', function() {
    var articlesList = document.getElementById('articles-list');
    var articlesError = document.getElementById('articles-error');
    articlesError.style.display = "none";
    var articlesApiUrl = "/api/endpoints/articles_list.php?project_id=" + <?=intval($project_id)?> + "&lang=" + encodeURIComponent(lang);

    fetch(articlesApiUrl)
        .then(function(res) {
            if(!res.ok) throw new Error("خطا در دریافت اطلاعات");
            return res.json();
        })
        .then(function(list) {
            if(!Array.isArray(list) || !list.length) {
                articlesList.innerHTML = "<div style='width:100%;text-align:center;color:#888'>" + (t['not_found_articles'] || '') + "</div>";
                return;
            }
            articlesList.innerHTML = "";
            list.slice(0,12).forEach(function(a) {
                var col = document.createElement('div');
                col.className = "col-md-6 col-lg-4 mb-4";
                var aTag = document.createElement('a');
                aTag.href = "article-detail.php?id=" + encodeURIComponent(a.id);
                aTag.style.textDecoration = "none";
                aTag.style.color = "inherit";

                var card = document.createElement('div');
                card.className = "article-card h-100";

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
                h6.textContent = a.title || (t['untitled_article'] || '');
                p3.appendChild(h6);

                var desc = document.createElement('div');
                desc.className = "article-snippet";
                desc.textContent = a.content || "";
                p3.appendChild(desc);

                var dateDiv = document.createElement('div');
                dateDiv.className = "article-date";
                dateDiv.textContent = a.created_at ? (
                    lang === 'fa'
                      ? a.created_at.replace(/-/g,'/').slice(0,10)
                      : (new Date(a.created_at)).toLocaleDateString('en-US', {year:'numeric',month:'short',day:'numeric'})
                ) : '';
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
            articlesError.textContent = t['error_loading_articles'] || '';
        });
});
</script>
</body>
</html>