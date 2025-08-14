<?php
session_start();
require_once __DIR__ . '/shared/inc/config.php';

// فقط زبان‌های مجاز از ALLOWED_LANGS
$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$project_id = 2;
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
<html lang="<?=htmlspecialchars($lang)?>" dir="<?=$is_rtl ? 'rtl' : 'ltr'?>">
<head>
    <meta charset="UTF-8">
    <title id="site-title"><?= $translations['site_title'] ?? 'XtremeClient' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include __DIR__.'/shared/inc/head-assets.php'; ?>
    <?php include __DIR__.'/includes/styles.php'; ?>
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
}
.hero-section {
    background: linear-gradient(120deg, var(--primary) 75%, var(--surface-alt) 100%);
    color: #fff;
    min-height: 30vh;
    display: flex;
    align-items: center;
    transition: background 0.4s;
    padding-top: 70px;
    padding-bottom: 22px;
}
.dark-theme .hero-section {
    background: linear-gradient(120deg, #1f2533 75%, #24324d 100%);
}
.hero-content {
    width: 100%;
    max-width: 880px;
    margin: 0 auto;
    z-index: 2;
    padding: 2.5rem 1.5rem 2rem 1.5rem;
    text-align: center;
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
.project-card, .team-card, .article-card {
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
}
.project-card.visible, .team-card.visible, .article-card.visible {
    opacity: 1;
    transform: translateY(0) scale(1);
}
.project-card:hover, .team-card:hover, .article-card:hover,
.project-card:focus, .team-card:focus, .article-card:focus {
    box-shadow: 0 12px 38px 0 #2499fa4a, 0 2px 8px 0 #2499fa22;
    border-color: var(--border-hover);
    background: var(--surface);
    transform: translateY(-6px) scale(1.035);
    z-index: 2;
}

/* === تصاویر پروژه و مقاله با نسبت 16:9 === */
.project-card img, .article-thumb {
    width: 100%;
    aspect-ratio: 16 / 9;
    object-fit: cover;
    border-radius: 18px 18px 0 0;
    transition: transform 0.33s cubic-bezier(.45,1.5,.6,1), box-shadow 0.33s;
    background: #f3f6fb;
    display: block;
}

.project-card:hover img, .article-card:hover .article-thumb,
.project-card:focus img, .article-card:focus .article-thumb {
    transform: scale(1.04) translateY(-4px);
    box-shadow: 0 8px 24px #2499fa22;
}

.project-title {
    font-weight: 800;
    font-size: 1.18rem;
    margin-bottom: 0.7rem;
    letter-spacing: 0.6px;
    color: #2499fa;
    text-shadow: 0 2px 12px #2499fa11;
    transition: color 0.2s;
    display: inline-block;
}
.team-card h5 { font-weight: 700; color: var(--primary);}
.team-card span { color: #111; margin-top: 0.3rem; font-size: 1.01rem; opacity: .8; display: block; text-align:center;}
.member-role {
    display: block;
    font-size: 1.07em;
    color: #38a8ff;
    font-weight: 600;
    margin-bottom: 0.55rem;
    text-align: center;
    letter-spacing: 0.4px;
}
.dark-theme .member-role { color: #4ee3fa; }
.sub-role {
    display: block;
    font-size: 0.98em;
    color: #666;
    opacity: 0.75;
    margin-bottom: 0.45rem;
    margin-top: 0.12rem;
    text-align: center;
    font-weight: 500;
    letter-spacing: 0.1px;
}
.dark-theme .team-card span { color: #c7d7f2; }
.dark-theme .sub-role { color: #a6b2cf; }
.article-card .p-3 {
    padding: 1.2rem 1.2rem 0.9rem 1.2rem;
}
.article-card h6 { color: var(--primary); font-weight: 700; }
/* --- Skeleton Loading (Cards) --- */
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
@media (max-width: 991.98px) {
    .hero-content { padding: 1.2rem 0.4rem 1.2rem 0.4rem; }
}
@media (max-width: 767.98px) {
    .hero-content { padding: 1rem 0.05rem 1rem 0.05rem; }
    .skeleton-card { min-height: 110px; }
}
</style>
    <script>
        // ترجمه‌ها برای جاوااسکریپت
        window.PAGE_TRANSLATIONS = <?= json_encode($translations, JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body<?= $darkThemeActive ? ' class="dark-theme"' : '' ?>>
<?php include __DIR__.'/includes/navbar.php'; ?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <img id="site-logo" src="" alt="Logo" style="max-width:130px;max-height:98px;margin-bottom:22px;display:none;">
            <h1 id="hero-title"></h1>
            <p id="hero-intro" style="font-size:1.2rem;"></p>
        </div>
    </div>
</section>

<!-- Projects Section -->
<section id="projects" class="container py-5">
    <div class="section-title-wrap">
        <h2 class="section-title"><?= $translations['our_projects'] ?? '' ?></h2>
    </div>
    <div class="row" id="projects-list">
        <?php for($i=0;$i<3;$i++): ?>
            <div class="col-md-6 col-lg-4 skeleton-row">
                <div class="skeleton-card"></div>
            </div>
        <?php endfor; ?>
    </div>
    <div id="projects-error" class="error-message"></div>
    <div class="text-center mt-4">
        <a href="projects.php" class="btn btn-outline-primary px-4 fw-bold"><?= $translations['see_all_projects'] ?? '' ?></a>
    </div>
</section>

<!-- Team Section -->
<section id="team" class="container py-5">
    <div class="section-title-wrap">
        <h2 class="section-title"><?= $translations['our_team'] ?? '' ?></h2>
    </div>
    <div class="row" id="team-list">
        <?php for($i=0;$i<3;$i++): ?>
            <div class="col-md-6 col-lg-4 skeleton-row">
                <div class="skeleton-card"></div>
            </div>
        <?php endfor; ?>
    </div>
    <div id="team-error" class="error-message"></div>
    <div class="text-center mt-4">
        <a href="team.php" class="btn btn-outline-primary px-4 fw-bold"><?= $translations['see_all_team'] ?? '' ?></a>
    </div>
</section>

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

<?php include __DIR__.'/includes/footer.php'; ?>
<?php include __DIR__.'/shared/inc/foot-assets.php'; ?>
<?php include __DIR__.'/includes/theme-script.php'; ?>

<script>
    // ترجمه‌ها برای پیام‌های خطا و ... از window.PAGE_TRANSLATIONS
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
        var cards = document.querySelectorAll('.project-card, .team-card, .article-card');
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

    // Hero Section (Site Settings)
    document.addEventListener('DOMContentLoaded', function() {
        var siteTitleEl = document.getElementById('hero-title');
        var siteIntroEl = document.getElementById('hero-intro');
        var siteLogoEl = document.getElementById('site-logo');
        var titleTag = document.getElementById('site-title');

        fetch("https://api.xtremedev.co/endpoints/settings.php?project_id=" + project_id + "&lang=" + encodeURIComponent(lang))
            .then(function(res){
                if(!res.ok) throw new Error('API error');
                return res.json();
            })
            .then(function(data) {
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
    });

    // --- Projects Loader ---
    document.addEventListener('DOMContentLoaded', function() {
        // Projects
        var projectsList = document.getElementById('projects-list');
        var projectsError = document.getElementById('projects-error');
        projectsError.style.display = "none";
        var projectsApiUrl = "https://api.xtremedev.co/endpoints/public_projects_list.php?lang=" + encodeURIComponent(lang);

        fetch(projectsApiUrl)
            .then(function(res) {
                if(!res.ok) throw new Error("خطا در دریافت اطلاعات");
                return res.json();
            })
            .then(function(apiData) {
                if(!apiData.projects || !apiData.projects.length) {
                    projectsList.innerHTML = "<div style='width:100%;text-align:center;color:#888'>" + (t['not_found_projects'] || "") + "</div>";
                    return;
                }
                projectsList.innerHTML = "";
                apiData.projects.slice(0,3).forEach(function(pr) {
                    var col = document.createElement('div');
                    col.className = "col-md-6 col-lg-4";
                    var a = document.createElement('a');
                    a.href = "project-detail.php?id=" + encodeURIComponent(pr.id);
                    a.style.textDecoration = "none";
                    a.style.color = "inherit";
                    var card = document.createElement('div');
                    card.className = "project-card shadow-sm h-100";

                    if(pr.image && pr.image.trim()) {
                        var img = document.createElement('img');
                        img.src = pr.image;
                        img.alt = pr.title || '';
                        card.appendChild(img);
                    }
                    var p3 = document.createElement('div');
                    p3.className = "p-3";
                    var h5 = document.createElement('h5');
                    h5.className = "project-title";
                    h5.textContent = pr.title || "---";
                    p3.appendChild(h5);

                    var desc = document.createElement('p');
                    desc.textContent = pr.description || "";
                    p3.appendChild(desc);

                    card.appendChild(p3);
                    a.appendChild(card);
                    col.appendChild(a);
                    projectsList.appendChild(col);
                });
                revealOnScroll();
            })
            .catch(function(err) {
                projectsList.innerHTML = "";
                projectsError.style.display = "block";
                projectsError.textContent = t['error_loading_projects'] || "";
            });

        // Team
        var teamList = document.getElementById('team-list');
        var teamError = document.getElementById('team-error');
        teamError.style.display = "none";
        var teamApiUrl = "https://api.xtremedev.co/endpoints/team_list.php?lang=" + encodeURIComponent(lang);
        var defaultPhoto = "https://ui-avatars.com/api/?name=Team+Member&background=eeeeee&color=222&rounded=true";

        fetch(teamApiUrl)
            .then(function(res) {
                if(!res.ok) throw new Error("خطا در دریافت اطلاعات");
                return res.json();
            })
            .then(function(list) {
                if(!list.length) {
                    teamList.innerHTML = "<div style='width:100%;text-align:center;color:#888'>" + (t['not_found_team'] || "") + "</div>";
                    return;
                }
                teamList.innerHTML = "";
                list.slice(0,3).forEach(function(m) {
                    var col = document.createElement('div');
                    col.className = "col-md-6 col-lg-4";
                    var a = document.createElement('a');
                    a.href = "team-detail.php?id=" + encodeURIComponent(m.id);
                    a.style.textDecoration = "none";
                    a.style.color = "inherit";
                    var card = document.createElement('div');
                    card.className = "team-card shadow-sm p-3 text-center h-100";

                    if (m.photo && m.photo.trim()) {
                        var img = document.createElement('img');
                        img.src = m.photo;
                        img.alt = m.name || '';
                        img.style.width = "66px";
                        img.style.height = "66px";
                        img.style.borderRadius = "50%";
                        img.style.background = "#eee";
                        img.style.objectFit = "cover";
                        img.onerror = function(){ this.src = defaultPhoto; };
                        img.style.display = "block";
                        img.style.margin = "0 auto 0.7rem auto";
                        card.appendChild(img);
                    }

                    var h5 = document.createElement('h5');
                    h5.textContent = m.name || "---";
                    card.appendChild(h5);

                    if (m.role_name && m.role_name.trim()) {
                        var role = document.createElement('span');
                        role.className = "member-role";
                        role.textContent = m.role_name;
                        card.appendChild(role);
                    }

                    if (m.sub_role && m.sub_role.trim()) {
                        var subRole = document.createElement('span');
                        subRole.className = "sub-role";
                        subRole.textContent = m.sub_role;
                        card.appendChild(subRole);
                    }

                    if (m.skill && m.skill.trim()) {
                        var skill = document.createElement('span');
                        skill.textContent = m.skill;
                        card.appendChild(skill);
                    }

                    a.appendChild(card);
                    col.appendChild(a);
                    teamList.appendChild(col);
                });
                revealOnScroll();
            })
            .catch(function(err) {
                teamList.innerHTML = "";
                teamError.style.display = "block";
                teamError.textContent = t['error_loading_team'] || "";
            });

        // Articles
        var articlesList = document.getElementById('articles-list');
        var articlesError = document.getElementById('articles-error');
        articlesError.style.display = "none";
        var articlesApiUrl = "https://api.xtremedev.co/endpoints/articles_list.php?project_id=2&lang=" + encodeURIComponent(lang);

        fetch(articlesApiUrl)
            .then(function(res) {
                if(!res.ok) throw new Error("خطا در دریافت اطلاعات");
                return res.json();
            })
            .then(function(list) {
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
                    h6.textContent = a.title || "---";
                    p3.appendChild(h6);

                    var desc = document.createElement('div');
                    desc.style.fontSize = "0.97rem";
                    desc.textContent = a.content || "";
                    p3.appendChild(desc);

                    var dateDiv = document.createElement('div');
                    dateDiv.style.color = "#888";
                    dateDiv.style.fontSize = "0.87rem";
                    dateDiv.style.marginTop = "0.4rem";
                    dateDiv.textContent = a.created_at ? new Date(a.created_at).toLocaleDateString(lang==='fa'?'fa-IR':'en-US', { year:'numeric', month:'short', day:'numeric' }) : "";
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
</script>
</body>
</html>