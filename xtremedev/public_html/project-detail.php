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
<html lang="<?=htmlspecialchars($lang)?>" dir="<?=$is_rtl?'rtl':'ltr'?>">
<head>
    <meta charset="UTF-8">
    <title id="site-title"><?= $translations['project'] ?? '' ?></title>
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
            transition: background 0.3s, color 0.3s;
        }
        .main-content { min-height: 100vh; }
        .project-detail-card {
            background: var(--surface-alt);
            border-radius: 18px;
            box-shadow: 0 4px 18px var(--shadow-card);
            border: 2px solid var(--border);
            margin-top: 3.5rem;
            margin-bottom: 3.5rem;
            padding: 0;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
            overflow: hidden;
            transition: background 0.4s, color 0.4s;
        }
        .project-image {
            width: 100%;
            height: 270px;
            object-fit: cover;
            border-radius: 18px 18px 0 0;
            background: #f3f6fb;
            display: block;
        }
        @media (max-width: 767.98px) {
            .project-image { height: 160px; }
            .project-detail-card { margin-top: 1.3rem; margin-bottom:1.3rem; }
        }
        .project-title {
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
        .project-desc {
            color: var(--text);
            font-size: 1.07rem;
            margin-bottom:1.1rem;
            line-height: 1.85;
            min-height: 60px;
            transition: color 0.4s;
        }
        .dark-theme .project-desc { color: #d8e3f7; }
        .project-long-desc {
            color: var(--text);
            font-size: 1.07rem;
            line-height: 2.0;
            margin-top: 2.1rem;
            margin-bottom: 1.8rem;
            border-top: 1px dashed #eee;
            padding-top: 1.3rem;
            transition: color 0.4s, border-top-color 0.4s;
        }
        .project-long-desc h1,.project-long-desc h2,.project-long-desc h3,
        .project-long-desc h4,.project-long-desc h5 {
            margin-top: 1.5em;
            margin-bottom: 0.7em;
            font-weight: bold;
            color: #2499fa;
            line-height: 1.12;
        }
        .project-long-desc h1 { font-size: 2.2rem; }
        .project-long-desc h2 { font-size: 1.7rem; }
        .project-long-desc h3 { font-size: 1.3rem; }
        .project-long-desc h4 { font-size: 1.09rem; }
        .project-long-desc h5 { font-size: 1.01rem; }
        .project-long-desc img {
            max-width: 100%;
            border-radius: 10px;
            margin: 1.3em auto 1.3em auto;
            display: block;
            box-shadow: 0 2px 12px #2499fa22;
        }
        .project-long-desc a {
            color: #2499fa;
            text-decoration: underline;
            font-weight: 500;
        }
        .project-long-desc a:hover {
            color: #38a8ff;
            text-decoration: underline;
        }
        .project-long-desc b, .project-long-desc strong { font-weight: bold; }
        .project-long-desc i, .project-long-desc em { font-style: italic; }
        .dark-theme .project-long-desc { color: #e8eaf0; border-top-color: #24324d;}
        .dark-theme .project-long-desc h1,
        .dark-theme .project-long-desc h2,
        .dark-theme .project-long-desc h3,
        .dark-theme .project-long-desc h4,
        .dark-theme .project-long-desc h5 { color: #38a8ff; }
        .dark-theme .project-long-desc a { color: #4ee3fa; }
        .dark-theme .project-long-desc a:hover { color: #2499fa; }
        .skeleton-project-detail {
            min-height: 420px;
            background: linear-gradient(90deg, #f4f7fa 25%, #e6edf5 50%, #f4f7fa 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.1s infinite linear;
            border-radius: 18px;
            width: 100%;
            margin-top: 3.5rem;
            margin-bottom: 3.5rem;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
            height: 440px;
        }
        .dark-theme .skeleton-project-detail {
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
    <div id="project-detail-skeleton" class="skeleton-project-detail"></div>
    <div id="project-detail-container" style="display:none"></div>
    <div id="project-detail-error" class="error-message" style="display:none"></div>
</div>
<?php include 'includes/footer.php'; ?>
<?php include __DIR__.'/shared/inc/foot-assets.php'; ?>
<?php include 'includes/theme-script.php'; ?>
<script>
var t = window.PAGE_TRANSLATIONS || {};
var lang = <?=json_encode($lang)?>;
var project_id = <?=intval($project_id)?>;

// ستینگ سایت از API و تغییر title
document.addEventListener('DOMContentLoaded', function(){
    var titleTag = document.getElementById('site-title');
    fetch("https://api.xtremedev.co/endpoints/settings.php?project_id=" + project_id + "&lang=" + encodeURIComponent(lang))
        .then(function(res){
            if(!res.ok) throw new Error('API error');
            return res.json();
        })
        .then(function(data) {
            if(data.site_title) {
                var newTitle = (t['project'] || '') + (data.site_title ? " | " + data.site_title : "");
                document.title = newTitle;
                if(titleTag) titleTag.textContent = newTitle;
            }
        })
        .catch(function(){});
});

// لود پروژه از API و پیام‌های خطا داینامیک
document.addEventListener('DOMContentLoaded', function() {
    var id = (new URLSearchParams(window.location.search)).get('id');
    var skeleton = document.getElementById('project-detail-skeleton');
    var c = document.getElementById('project-detail-container');
    var err = document.getElementById('project-detail-error');

    if(!id) {
        skeleton.style.display = 'none';
        err.style.display = "block";
        err.textContent = t['invalid_project_id'] || '';
        return;
    }

    var apiUrl = "https://api.xtremedev.co/endpoints/public_project_detail.php?id=" + encodeURIComponent(id) + "&lang=" + encodeURIComponent(lang);
    fetch(apiUrl)
        .then(function(res) {
            if(!res.ok) throw new Error("notfound");
            return res.json();
        })
        .then(function(data) {
            skeleton.style.display = 'none';
            c.style.display = "block";
            c.innerHTML = '';

            var pr = (data && data.project) ? data.project : null;
            if(!pr) {
                err.style.display = "block";
                err.textContent = t['project_not_found'] || '';
                return;
            }

            var card = document.createElement('div');
            card.className = "project-detail-card mt-4";
            if(pr.image && pr.image.trim()) {
                var img = document.createElement('img');
                img.className = "project-image";
                img.src = pr.image;
                img.alt = pr.title || '';
                card.appendChild(img);
            }
            var p = document.createElement('div');
            p.className = "p-4";

            var h1 = document.createElement('h1');
            h1.className = "project-title";
            h1.textContent = pr.title || (t['untitled_project'] || '');
            p.appendChild(h1);

            var desc = document.createElement('div');
            desc.className = "project-desc mb-2";
            desc.innerHTML = (pr.description || '').replace(/\n/g,'<br>');
            p.appendChild(desc);

            if(pr.long_description) {
                var ldesc = document.createElement('div');
                ldesc.className = "project-long-desc";
                ldesc.innerHTML = pr.long_description;
                p.appendChild(ldesc);
            }

            card.appendChild(p);
            c.appendChild(card);
        })
        .catch(function(e) {
            skeleton.style.display = 'none';
            err.style.display = "block";
            err.textContent = (e.message === 'notfound')
                ? (t['project_not_found'] || '')
                : (t['error_loading_project'] || '');
        });
});

// Sticky Navbar
const navbar = document.getElementById('mainNavbar');
let lastIsSticky = false;
function checkStickyNavbar() {
    if(window.scrollY > 60) {
        if(!lastIsSticky) {
            navbar.classList.add('sticky-navbar');
            lastIsSticky = true;
        }
    } else {
        if(lastIsSticky) {
            navbar.classList.remove('sticky-navbar');
            lastIsSticky = false;
        }
    }
}
window.addEventListener('scroll', checkStickyNavbar, {passive:true});
window.addEventListener('resize', checkStickyNavbar);
checkStickyNavbar();
</script>
</body>
</html>