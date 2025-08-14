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
    <title id="site-title"><?= $translations['article'] ?? '' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include 'shared/inc/head-assets.php'; ?>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700&display=swap" rel="stylesheet">
    <?php include 'includes/styles.php'; ?>
    <style>
        :root, body {
            --surface: #f4f7fa;
            --surface-alt: #fff;
            --text: #222;
            --primary: #2499fa;
            --shadow-card: #2499fa14;
            --border: #2499fa18;
            --border-hover: #2499fa44;
        }
        body.dark-theme {
            --surface: #181f2a;
            --surface-alt: #202b3b;
            --text: #e6e9f2;
            --shadow-card: #15203222;
            --border: #2499fa28;
            --border-hover: #2499fa66;
        }
        html, body { height: 100%; min-height: 100%; }
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background: var(--surface);
            color: var(--text);
            transition: background 0.3s, color 0.3s;
        }
        .main-content {
            flex: 1 0 auto;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        .article-detail-card {
            background: var(--surface-alt);
            border-radius: 18px;
            box-shadow: 0 4px 18px var(--shadow-card);
            border: 2px solid var(--border);
            margin-top: 3.5rem;
            margin-bottom: 3.5rem;
            padding: 0;
            max-width: 780px;
            margin-left: auto;
            margin-right: auto;
            overflow: hidden;
            transition: background 0.4s, color 0.4s;
        }
        .article-thumb {
            width: 100%;
            aspect-ratio: 16 / 9;
            object-fit: cover;
            border-radius: 18px 18px 0 0;
            background: #f3f6fb;
            display: block;
            max-height: 400px;
        }
        @media (max-width: 767.98px) {
            .article-thumb { max-height: 200px; }
            .article-detail-card { margin-top: 1.3rem; margin-bottom:1.3rem; }
        }
        .article-title {
            font-weight: 900;
            font-size: 2rem;
            letter-spacing: 1.3px;
            margin-bottom: 0.6rem;
            background: linear-gradient(90deg, #2499fa 15%, #3ed2f0 85%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
            margin-top: 1.5rem;
            display: block;
            text-align: center;
            width: 100%;
        }
        .article-short-content {
            color: #333;
            font-size: 1.09rem;
            margin-bottom:1.1rem;
            line-height: 1.7;
            text-align: center;
            white-space: pre-line;
        }
        .dark-theme .article-short-content { color: #d8e3f7; }
        .article-date {
            text-align: center;
            color: #888;
            font-size: 0.98rem;
            margin-bottom: 1.4rem;
            margin-top: -0.7rem;
            letter-spacing: 0.6px;
        }
        .article-body {
            color: #222;
            font-size: 1.07rem;
            line-height: 2.0;
            margin-top: 2.1rem;
            margin-bottom: 1.8rem;
            border-top: 1px dashed #eee;
            padding-top: 1.3rem;
            word-break: break-word;
        }
        .article-body h1,
        .article-body h2,
        .article-body h3,
        .article-body h4,
        .article-body h5 {
            margin-top: 1.5em;
            margin-bottom: 0.7em;
            font-weight: bold;
            color: #2499fa;
            line-height: 1.12;
            letter-spacing: 0.2px;
            text-align: center;
        }
        .article-body h1 { font-size: 2.2rem; }
        .article-body h2 { font-size: 1.7rem; }
        .article-body h3 { font-size: 1.3rem; }
        .article-body h4 { font-size: 1.09rem; }
        .article-body h5 { font-size: 1.01rem; }
        .article-body img {
            max-width: 100%;
            border-radius: 10px;
            margin: 1.3em auto 1.3em auto;
            display: block;
            box-shadow: 0 2px 12px #2499fa22;
        }
        .article-body a {
            color: #2499fa;
            text-decoration: underline;
            font-weight: 500;
        }
        .article-body a:hover {
            color: #38a8ff;
            text-decoration: underline;
        }
        .article-body b, .article-body strong { font-weight: bold; }
        .article-body i, .article-body em { font-style: italic; }
        .dark-theme .article-body { color: #e8eaf0; border-top-color: #24324d;}
        .dark-theme .article-body h1,
        .dark-theme .article-body h2,
        .dark-theme .article-body h3,
        .dark-theme .article-body h4,
        .dark-theme .article-body h5 { color: #38a8ff; }
        .dark-theme .article-body a { color: #4ee3fa; }
        .dark-theme .article-body a:hover { color: #2499fa; }
        .skeleton-article-detail {
            min-height: 380px;
            background: linear-gradient(90deg, #f4f7fa 25%, #e6edf5 50%, #f4f7fa 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.1s infinite linear;
            border-radius: 18px;
            width: 100%;
            margin-top: 3.5rem;
            margin-bottom: 3.5rem;
            max-width: 780px;
            margin-left: auto;
            margin-right: auto;
            height: 420px;
        }
        .dark-theme .skeleton-article-detail {
            background: linear-gradient(90deg, #232d3b 25%, #1e2632 50%, #232d3b 75%);
        }
        @keyframes skeleton-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        .error-message {
            color: #e63946;
            text-align: center;
            font-size: 1.1rem;
            margin: 3.5rem auto 0 auto;
        }
    </style>
    <script>
        window.PAGE_TRANSLATIONS = <?= json_encode($translations, JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body<?= $darkThemeActive ? ' class="dark-theme"' : '' ?>>
<?php include 'includes/navbar.php'; ?>
<div class="main-content">
    <div id="article-detail-skeleton" class="skeleton-article-detail"></div>
    <div id="article-detail-container" style="display:none"></div>
    <div id="article-detail-error" class="error-message" style="display:none"></div>
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
    fetch("https://api.xtremedev.co/endpoints/settings.php?project_id=" + project_id + "&lang=" + encodeURIComponent(lang))
        .then(function(res){
            if(!res.ok) throw new Error('API error');
            return res.json();
        })
        .then(function(data) {
            if(data.site_title) {
                var newTitle = (t['article'] || '') + (data.site_title ? " | " + data.site_title : "");
                document.title = newTitle;
                if(titleTag) titleTag.textContent = newTitle;
            }
        })
        .catch(function(){});
});

function formatDate(dateStr) {
    if(lang === 'fa') {
        return dateStr ? dateStr.replace(/-/g,'/').slice(0,10) : '';
    } else {
        var d = new Date(dateStr);
        return d.toLocaleDateString('en-US', {year:'numeric', month:'short', day:'numeric'});
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var id = (new URLSearchParams(window.location.search)).get('id');
    var skeleton = document.getElementById('article-detail-skeleton');
    var c = document.getElementById('article-detail-container');
    var err = document.getElementById('article-detail-error');

    if(!id) {
        skeleton.style.display = 'none';
        err.style.display = "block";
        err.textContent = t['invalid_article_id'] || '';
        return;
    }

    var apiUrl = "/api/endpoints/articles_get.php?id=" + encodeURIComponent(id) + "&lang=" + encodeURIComponent(lang);
    fetch(apiUrl)
        .then(function(res) {
            if(!res.ok) throw new Error("notfound");
            return res.json();
        })
        .then(function(art) {
            skeleton.style.display = 'none';
            c.style.display = "block";
            c.innerHTML = '';

            let tr = (art.translations && art.translations[lang]) 
                ? art.translations[lang]
                : (art.translations && art.translations.fa)
                    ? art.translations.fa
                    : (art.translations && art.translations.en)
                        ? art.translations.en
                        : null;
            if(!art || !art.id || !tr) {
                err.style.display = "block";
                err.textContent = t['article_not_found'] || '';
                return;
            }

            var card = document.createElement('div');
            card.className = "article-detail-card mt-4";
            if(art.thumbnail && art.thumbnail.trim()) {
                var img = document.createElement('img');
                img.className = "article-thumb";
                img.src = art.thumbnail;
                img.alt = tr.title || '';
                card.appendChild(img);
            }
            var p = document.createElement('div');
            p.className = "p-4";
            var h1 = document.createElement('h1');
            h1.className = "article-title";
            h1.textContent = tr.title || (t['untitled_article'] || '');
            p.appendChild(h1);

            if(tr.content) {
                var sum = document.createElement('div');
                sum.className = "article-short-content";
                sum.textContent = tr.content;
                p.appendChild(sum);
            }
            var date = document.createElement('div');
            date.className = "article-date";
            date.textContent = formatDate(art.created_at);
            p.appendChild(date);

            if(tr.body) {
                var body = document.createElement('div');
                body.className = "article-body";
                body.innerHTML = tr.body;
                p.appendChild(body);
            }
            card.appendChild(p);
            c.appendChild(card);
        })
        .catch(function(e) {
            skeleton.style.display = 'none';
            err.style.display = "block";
            err.textContent = (e.message === 'notfound')
                ? (t['article_not_found'] || '')
                : (t['error_loading_article'] || '');
        });
});
</script>
</body>
</html>