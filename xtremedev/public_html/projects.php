<?php
session_start();
require_once __DIR__ . '/shared/inc/config.php';

// زبان فقط از ALLOWED_LANGS
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
<html lang="<?=htmlspecialchars($lang)?>" dir="<?=($is_rtl ? 'rtl' : 'ltr')?>">
<head>
    <meta charset="UTF-8">
    <title id="site-title"><?= $translations['projects'] ?? '' ?></title>
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
.section-title {
    font-weight: 900;
    font-family: Vazirmatn, Tahoma, Arial, sans-serif;
    font-size: 2.3rem;
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
.all-projects {
    margin-bottom: 2.5rem;
}
.project-card {
    background: var(--surface-alt);
    border-radius: 18px;
    box-shadow: 0 4px 16px var(--shadow-card);
    border: 2px solid var(--border);
    margin-bottom: 0.8rem;
    transition: box-shadow 0.21s cubic-bezier(.4,2,.6,1), border 0.16s, transform 0.22s cubic-bezier(.5,2,.6,1), background 0.3s;
    cursor: pointer;
    padding: 0;
    height: 100%;
    display: flex;
    flex-direction: column;
    opacity: 0;
    transform: translateY(48px) scale(0.97);
    will-change: transform, opacity;
}
.project-card.visible {
    opacity: 1;
    transform: translateY(0) scale(1);
    transition:
            opacity 0.57s cubic-bezier(.38,1.3,.6,1),
            transform 0.57s cubic-bezier(.38,1.3,.6,1),
            box-shadow 0.21s cubic-bezier(.4,2,.6,1),
            border 0.16s,
            background 0.3s;
}
.project-card:hover, .project-card:focus {
    box-shadow: 0 12px 38px 0 #2499fa4a, 0 2px 8px 0 #2499fa22;
    border-color: var(--border-hover);
    background: var(--surface);
    transform: translateY(-11px) scale(1.03);
}
.project-img {
    width: 100%;
    aspect-ratio: 16 / 9;
    object-fit: cover;
    border-radius: 18px 18px 0 0;
    background: #f2f5fa;
    display: block;
    transition: transform 0.33s cubic-bezier(.45,1.5,.6,1), box-shadow 0.33s;
}
.project-card:hover .project-img, .project-card:focus .project-img {
    transform: scale(1.04) translateY(-7px);
    box-shadow: 0 8px 24px #2499fa22;
}
.project-body {
    padding: 1.4rem 1.4rem 1.1rem 1.4rem;
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
}
.project-title {
    font-weight: 800;
    color: #1a6fc2;
    font-size: 1.23rem;
    margin-bottom: 0.7rem;
    letter-spacing: 0.6px;
    text-shadow: 0 2px 12px #2499fa11;
    transition: color 0.2s;
}
.dark-theme .project-title {
    color: #4eb6ff;
}
.project-card:hover .project-title, .project-card:focus .project-title {
    color: #2499fa;
}
.project-desc {
    color: var(--text);
    font-size: 1.09rem;
    margin-bottom: 0;
    flex: 1 1 auto;
    line-height: 1.85;
    word-break: break-word;
    transition: color 0.3s;
}
.dark-theme .project-desc { color: #cdd6e6; }
@media (max-width: 991.98px) {
    .project-body { padding: 1rem 1rem 0.8rem 1rem;}
}
@media (max-width: 767.98px) {
    .project-body { padding: 0.7rem 0.7rem 0.5rem 0.7rem;}
}
.skeleton-row { margin-bottom: 28px; }
.skeleton-project {
    background: linear-gradient(90deg, #f4f7fa 25%, #e6edf5 50%, #f4f7fa 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.1s infinite linear;
    border-radius: 18px;
    min-height: 280px;
    width: 100%;
}
.dark-theme .skeleton-project {
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
    margin: 2rem auto 0 auto;
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
    <div class="container all-projects">
        <div class="row">
            <div class="col-12 d-flex justify-content-center">
                <h1 class="section-title" id="site-projects-title">
                    <?= $translations['our_projects'] ?? '' ?>
                </h1>
            </div>
        </div>
        <div class="row" id="projects-list">
            <?php for($i=0;$i<6;$i++): ?>
            <div class="col-md-6 col-lg-4 skeleton-row mb-5">
                <div class="skeleton-project" style="min-height:280px"></div>
            </div>
            <?php endfor; ?>
        </div>
        <div id="projects-error" class="error-message"></div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
<?php include __DIR__.'/shared/inc/foot-assets.php'; ?>
<?php include 'includes/theme-script.php'; ?>
<script>
    var t = window.PAGE_TRANSLATIONS || {};
    var lang = <?= json_encode($lang) ?>;
    var project_id = <?=intval($project_id)?>;

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

    function revealOnScroll() {
        var cards = document.querySelectorAll('.project-card');
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

    // دریافت عنوان سایت و پروژه‌ها از API
    document.addEventListener('DOMContentLoaded', function() {
        var siteTitleTag = document.getElementById('site-title');
        var projectsTitle = document.getElementById('site-projects-title');
        fetch("https://api.xtremedev.co/endpoints/settings.php?project_id=" + project_id + "&lang=" + encodeURIComponent(lang))
            .then(function(res){
                if(!res.ok) throw new Error('API error');
                return res.json();
            })
            .then(function(data) {
                if(data.site_title) {
                    var newTitle = (t['projects'] || '') + (data.site_title ? " | " + data.site_title : "");
                    if(siteTitleTag) siteTitleTag.textContent = newTitle;
                    document.title = newTitle;
                }
                if(data.projects_page_title && projectsTitle) {
                    projectsTitle.textContent = data.projects_page_title;
                }
            })
            .catch(function(){});
    });

    document.addEventListener('DOMContentLoaded', function(){
        var list = document.getElementById('projects-list');
        var error = document.getElementById('projects-error');
        error.style.display = 'none';
        var api_url = "https://api.xtremedev.co/endpoints/public_projects_list.php?lang=" + encodeURIComponent(lang);

        fetch(api_url)
            .then(function(res){
                if(!res.ok) throw new Error("network");
                return res.json();
            })
            .then(function(data){
                if(!data.projects || !Array.isArray(data.projects) || !data.projects.length) {
                    list.innerHTML = "<div style='width:100%;text-align:center;color:#999;padding:2rem 0'>" + (t['not_found_projects'] || '') + "</div>";
                    return;
                }
                list.innerHTML = "";
                data.projects.forEach(function(pr){
                    var col = document.createElement('div');
                    col.className = "col-md-6 col-lg-4 d-flex mb-5";
                    var a = document.createElement('a');
                    a.href = "project-detail.php?id=" + encodeURIComponent(pr.id);
                    a.style.textDecoration = "none";
                    a.style.color = "inherit";
                    a.style.width = "100%";
                    var card = document.createElement('div');
                    card.className = "project-card h-100";
                    var img = document.createElement('img');
                    img.className = "project-img";
                    img.src = pr.image;
                    img.alt = pr.title || '';
                    card.appendChild(img);
                    var body = document.createElement('div');
                    body.className = "project-body";
                    var title = document.createElement('div');
                    title.className = "project-title";
                    title.textContent = pr.title || (t['untitled_project'] || '');
                    var desc = document.createElement('div');
                    desc.className = "project-desc";
                    desc.textContent = pr.description || '';
                    body.appendChild(title);
                    body.appendChild(desc);
                    card.appendChild(body);
                    a.appendChild(card);
                    col.appendChild(a);
                    list.appendChild(col);
                });
                revealOnScroll();
            })
            .catch(function(){
                list.innerHTML = "";
                error.style.display = "block";
                error.textContent = t['error_loading_projects'] || '';
            });
    });
</script>
</body>
</html>