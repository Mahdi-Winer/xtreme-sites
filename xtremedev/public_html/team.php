<?php
session_start();
require_once __DIR__ . '/shared/inc/config.php';

$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$project_id = 1;
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
<html lang="<?=htmlspecialchars($lang)?>" dir="<?=($is_rtl?'rtl':'ltr')?>">
<head>
    <meta charset="UTF-8">
    <title id="site-title"><?= $translations['our_team'] ?? '' ?></title>
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
            transition: background 0.4s, color 0.4s;
        }
        .main-content {
            flex: 1 0 auto;
            display: flex;
            flex-direction: column;
            min-height: 0;
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
        .section-title-wrap { text-align: center; }
        .role-title {
            font-weight: 900;
            font-family: Vazirmatn, Tahoma, Arial, sans-serif;
            font-size: 1.45rem;
            letter-spacing: 1.1px;
            text-align: center;
            margin-bottom: 2.1rem;
            margin-top: 3.1rem;
            background: linear-gradient(90deg, #2499fa 15%, #3ed2f0 85%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
            position: relative;
            display: block;
            padding-bottom: 7px;
            margin-left: auto;
            margin-right: auto;
        }
        .role-title:after {
            content: '';
            display: block;
            width: 54px;
            height: 3px;
            background: linear-gradient(90deg, #2499fa 10%, #3ed2f0 90%);
            border-radius: 2px;
            margin: 0 auto;
            margin-top: 7px;
            opacity: 0.85;
        }
        .team-section {
            margin-bottom: 2rem;
        }
        .team-card {
            background: var(--surface-alt);
            border-radius: 18px;
            box-shadow: 0 4px 16px var(--shadow-card);
            border: 2px solid var(--border);
            margin-bottom: 1.2rem;
            transition: box-shadow 0.2s, border 0.2s, background 0.3s, transform 0.4s cubic-bezier(.38,1.3,.6,1), opacity 0.57s cubic-bezier(.38,1.3,.6,1);
            cursor: pointer;
            padding: 1.5rem 1.2rem;
            opacity: 0;
            transform: translateY(40px) scale(0.97);
            will-change: transform, opacity;
            min-height: 180px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: inherit;
        }
        .team-card.visible {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        .team-card:hover, .team-card:focus {
            box-shadow: 0 12px 38px 0 #2499fa4a, 0 2px 8px 0 #2499fa22;
            border-color: var(--border-hover);
            background: var(--surface);
            transform: translateY(-6px) scale(1.035);
            z-index: 2;
            text-decoration: none;
            color: inherit;
        }
        .team-card h5 {
            font-weight: 800;
            font-size: 1.18rem;
            margin-bottom: 0.25rem;
            letter-spacing: 0.6px;
            color: #2499fa;
            text-shadow: 0 2px 12px #2499fa11;
            transition: color 0.2s;
            display: block;
            text-align: center;
        }
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
        .team-card span {
            font-size: 1.01rem;
            color: #111;
            opacity: .8;
            margin-top: 0.3rem;
            text-align: center;
            display: block;
        }
        .dark-theme .team-card span { color: #c7d7f2; }
        .dark-theme .sub-role { color: #a6b2cf; }
        .team-skeleton-row {
            margin-bottom: 1.3rem;
        }
        .skeleton-team-card {
            background: linear-gradient(90deg, #f4f7fa 25%, #e6edf5 50%, #f4f7fa 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.13s infinite linear;
            border-radius: 18px;
            min-height: 180px;
            width: 100%;
            display: block;
        }
        .dark-theme .skeleton-team-card {
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
            .logo-img { height: 38px !important; max-width: 120px;}
            .navbar-brand { width: 130px; }
            .theme-switch { min-width: 60px; min-height: 30px; font-size: 0.97rem; padding: 1px 11px; margin-left: .7rem;}
            .navbar-nav .nav-link { font-size: 1.01rem;}
        }
        @media (max-width: 767.98px) {
            .logo-img { height: 28px !important; max-width: 80px;}
            .navbar-brand { width: 80px; }
            .theme-switch { min-width: 50px; min-height: 26px; font-size: 0.92rem; padding: 1px 7px; margin-left: 0.5rem;}
        }
        @media (max-width: 540px) {
            .team-section { padding: 0 0.2rem; }
        }
    </style>
    <script>
        window.PAGE_TRANSLATIONS = <?= json_encode($translations, JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body<?= $darkThemeActive ? ' class="dark-theme"' : '' ?>>
<?php include 'includes/navbar.php'; ?>
<div class="main-content">
    <section class="container py-5 team-section">
        <div class="section-title-wrap">
            <h1 class="section-title" id="team-page-title"><?= $translations['our_team'] ?? '' ?></h1>
        </div>
        <div id="team-skeleton">
            <div class="row">
                <?php for($i=0;$i<6;$i++): ?>
                <div class="col-md-6 col-lg-4 team-skeleton-row">
                    <div class="skeleton-team-card"></div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
        <div id="team-error" class="error-message"></div>
        <div id="team-roles"></div>
    </section>
</div>
<?php include 'includes/footer.php'; ?>
<?php include __DIR__.'/shared/inc/foot-assets.php'; ?>
<?php include 'includes/theme-script.php'; ?>
<script>
var t = window.PAGE_TRANSLATIONS || {};
var lang = <?=json_encode($lang)?>;
var project_id = <?=intval($project_id)?>;

// ستینگ سایت و عنوان صفحه از API
document.addEventListener('DOMContentLoaded', function(){
    var titleTag = document.getElementById('site-title');
    var pageTitleEl = document.getElementById('team-page-title');
    fetch("https://api.xtremedev.co/endpoints/settings.php?project_id=" + project_id + "&lang=" + encodeURIComponent(lang))
        .then(function(res){
            if(!res.ok) throw new Error('API error');
            return res.json();
        })
        .then(function(data) {
            if(data.site_title) {
                var newTitle = (t['our_team'] || '') + (data.site_title ? " | " + data.site_title : "");
                document.title = newTitle;
                if(titleTag) titleTag.textContent = newTitle;
            }
            if(data.team_page_title && pageTitleEl) {
                pageTitleEl.textContent = data.team_page_title;
            }
        })
        .catch(function(){});
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

// انیمیشن ظاهر شدن کارت تیم
function revealOnScroll() {
    var cards = document.querySelectorAll('.team-card');
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
    var teamRolesContainer = document.getElementById('team-roles');
    var skeleton = document.getElementById('team-skeleton');
    var errorDiv = document.getElementById('team-error');
    skeleton.style.display = "block";
    errorDiv.style.display = "none";
    teamRolesContainer.innerHTML = "";

    var apiUrl = "https://api.xtremedev.co/endpoints/team_list.php?lang=" + encodeURIComponent(lang);

    fetch(apiUrl)
        .then(function(res) {
            if(!res.ok) throw new Error("network");
            return res.json();
        })
        .then(function(list) {
            skeleton.style.display = "none";
            if(!list.length) {
                teamRolesContainer.innerHTML = "<div style='width:100%;text-align:center;color:#888'>" + (t['not_found_team'] || '') + "</div>";
                return;
            }
            var roles = {};
            list.forEach(function(m) {
                var role = m.role_name || "";
                if(!roles[role]) {
                    roles[role] = { sort_order: m.sort_order || 0, members: [] };
                }
                roles[role].members.push(m);
            });
            var sortedRoles = Object.keys(roles).map(function(role){
                return { name: role, sort_order: roles[role].sort_order, members: roles[role].members };
            }).sort(function(a, b) {
                return (a.sort_order || 0) - (b.sort_order || 0);
            });

            sortedRoles.forEach(function(roleObj) {
                var roleDiv = document.createElement('div');
                roleDiv.className = "mb-2 mt-5 text-center";
                var h2 = document.createElement('h2');
                h2.className = "role-title";
                h2.textContent = roleObj.name;
                roleDiv.appendChild(h2);
                teamRolesContainer.appendChild(roleDiv);

                var row = document.createElement('div');
                row.className = "row justify-content-center";

                roleObj.members.forEach(function(m) {
                    var col = document.createElement('div');
                    col.className = "col-md-6 col-lg-4 d-flex justify-content-center";
                    var a = document.createElement('a');
                    a.href = "team-detail.php?id=" + encodeURIComponent(m.id);
                    a.style.textDecoration = "none";
                    a.style.color = "inherit";
                    a.style.display = "block";
                    a.style.width = "100%";

                    var card = document.createElement('div');
                    card.className = "team-card";

                    var h5 = document.createElement('h5');
                    h5.textContent = m.name || (t['untitled_member'] || '');
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
                    row.appendChild(col);
                });

                teamRolesContainer.appendChild(row);
            });
            revealOnScroll();
        })
        .catch(function(err) {
            skeleton.style.display = "none";
            errorDiv.style.display = "block";
            errorDiv.textContent = t['error_loading_team'] || '';
        });
});
</script>
</body>
</html>