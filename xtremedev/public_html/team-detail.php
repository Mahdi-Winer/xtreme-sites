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
    <title id="site-title"><?= $translations['team_member_detail'] ?? '' ?></title>
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
        html, body {
            height: 100%;
            min-height: 100%;
        }
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
            justify-content: flex-start;
        }
        .team-detail-card {
            background: var(--surface-alt);
            border-radius: 18px;
            box-shadow: 0 4px 18px var(--shadow-card);
            border: 2px solid var(--border);
            margin-top: 3.5rem;
            margin-bottom: 3.5rem;
            padding: 0;
            max-width: 1000px;
            min-width: 680px;
            margin-left: auto;
            margin-right: auto;
            overflow: hidden;
            transition: background 0.4s, color 0.4s;
            position: relative;
        }
        .member-photo {
            width: 132px;
            height: 132px;
            object-fit: cover;
            border-radius: 50%;
            margin: 2.3rem auto 0.6rem auto;
            background: #f3f6fb;
            display: block;
            box-shadow: 0 2px 18px #2499fa19;
            border: 4px solid #fff;
        }
        .member-title {
            font-weight: 900;
            font-size: 2rem;
            letter-spacing: 1.3px;
            margin-bottom: 0.2rem;
            background: linear-gradient(90deg, #2499fa 15%, #3ed2f0 85%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
            text-align: center;
            width: 100%;
            margin-top: 0.9rem;
        }
        .member-role {
            display: block;
            font-size: 1.07em;
            color: #38a8ff;
            font-weight: 600;
            margin-bottom: 1.1rem;
            text-align: center;
            letter-spacing: 0.5px;
        }
        .member-subrole {
            display: block;
            font-size: 1.01em;
            color: #666;
            font-weight: 500;
            opacity: 0.8;
            margin-bottom: 0.45rem;
            text-align: center;
            letter-spacing: 0.1px;
        }
        .dark-theme .member-subrole { color: #a6b2cf; }
        .member-skill {
            display: block;
            color: #2499fa;
            font-size: 1.03em;
            font-weight: 700;
            text-align: center;
            margin-bottom: 1.2rem;
            letter-spacing: 0.7px;
        }
        .member-long-bio {
            color: var(--text);
            font-size: 1.06rem;
            line-height: 2.0;
            margin-top: 2.1rem;
            margin-bottom: 1.8rem;
            border-top: 1px dashed #eee;
            padding-top: 1.3rem;
            text-align: center;
            transition: color 0.3s, border-top-color 0.3s;
        }
        .member-long-bio h1,
        .member-long-bio h2,
        .member-long-bio h3,
        .member-long-bio h4,
        .member-long-bio h5 {
            margin-top: 1.5em;
            margin-bottom: 0.7em;
            font-weight: bold;
            color: #2499fa;
            line-height: 1.12;
            letter-spacing: 0.2px;
            text-align: center;
        }
        .member-long-bio h1 { font-size: 2.0rem; }
        .member-long-bio h2 { font-size: 1.5rem; }
        .member-long-bio h3 { font-size: 1.25rem; }
        .member-long-bio h4 { font-size: 1.1rem; }
        .member-long-bio h5 { font-size: 1rem; }
        .member-long-bio img {
            max-width: 100%;
            border-radius: 10px;
            margin: 1.3em auto 1.3em auto;
            display: block;
            box-shadow: 0 2px 12px #2499fa22;
        }
        .member-long-bio a {
            color: #2499fa;
            text-decoration: underline;
            font-weight: 500;
        }
        .member-long-bio a:hover {
            color: #38a8ff;
            text-decoration: underline;
        }
        .member-long-bio b, .member-long-bio strong { font-weight: bold; }
        .member-long-bio i, .member-long-bio em { font-style: italic; }
        .dark-theme .member-long-bio { color: #e8eaf0; border-top-color: #24324d;}
        .dark-theme .member-long-bio h1,
        .dark-theme .member-long-bio h2,
        .dark-theme .member-long-bio h3,
        .dark-theme .member-long-bio h4,
        .dark-theme .member-long-bio h5 { color: #38a8ff; }
        .dark-theme .member-long-bio a { color: #4ee3fa; }
        .dark-theme .member-long-bio a:hover { color: #2499fa; }
        .skeleton-team-detail-card {
            background: linear-gradient(90deg, #f4f7fa 25%, #e6edf5 50%, #f4f7fa 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.13s infinite linear;
            border-radius: 18px;
            width: 100%;
            height: 430px;
            margin-top: 3.5rem;
            margin-bottom: 3.5rem;
            max-width: 1000px;
            min-width: 680px;
            margin-left: auto;
            margin-right: auto;
            display: block;
        }
        .dark-theme .skeleton-team-detail-card {
            background: linear-gradient(90deg, #232d3b 25%, #1e2632 50%, #232d3b 75%);
        }
        @keyframes skeleton-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        .error-message {
            color: #e63946;
            text-align: center;
            font-size: 1.25rem;
            margin: 3.8rem auto 2.2rem auto;
            display: none;
        }
        @media (max-width: 767.98px) {
            .team-detail-card, .skeleton-team-detail-card { margin-top: 1.3rem; margin-bottom:1.3rem; min-width: 0;}
            .member-photo { width: 92px; height: 92px; }
            .member-title { font-size: 1.25rem; }
            .skeleton-team-detail-card { height: 210px; }
        }
    </style>
    <script>
        window.PAGE_TRANSLATIONS = <?= json_encode($translations, JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body<?= $darkThemeActive ? ' class="dark-theme"' : '' ?>>
<?php include 'includes/navbar.php'; ?>
<div class="main-content">
    <div id="skeleton-team-detail" class="skeleton-team-detail-card"></div>
    <div class="team-detail-card mt-4" id="team-detail-content" style="display:none;">
        <div id="member-error" class="error-message"></div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
<?php include __DIR__.'/shared/inc/foot-assets.php'; ?>
<?php include 'includes/theme-script.php'; ?>
<script>
var t = window.PAGE_TRANSLATIONS || {};
var lang = <?=json_encode($lang)?>;
var project_id = <?=intval($project_id)?>;

// تنظیمات سایت و عنوان صفحه از API
document.addEventListener('DOMContentLoaded', function(){
    var titleTag = document.getElementById('site-title');
    fetch("https://api.xtremedev.co/endpoints/settings.php?project_id=" + project_id + "&lang=" + encodeURIComponent(lang))
        .then(function(res){
            if(!res.ok) throw new Error('API error');
            return res.json();
        })
        .then(function(data) {
            if(data.site_title) {
                var newTitle = (t['team_member_detail'] || '') + (data.site_title ? " | " + data.site_title : "");
                document.title = newTitle;
                if(titleTag) titleTag.textContent = newTitle;
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

// لود داینامیک دیتیل عضو تیم از API
document.addEventListener('DOMContentLoaded', function(){
    var content = document.getElementById('team-detail-content');
    var skeleton = document.getElementById('skeleton-team-detail');
    var errorDiv = document.getElementById('member-error');
    skeleton.style.display = "block";
    content.style.display = "none";
    errorDiv.style.display = "none";

    function getParam(name) {
        var url = new URL(window.location.href);
        return url.searchParams.get(name);
    }
    var id = getParam('id');
    if(!id) {
        skeleton.style.display = "none";
        content.style.display = "block";
        errorDiv.style.display = "block";
        errorDiv.textContent = t['team_member_not_found'] || '';
        return;
    }
    var apiUrl = "https://api.xtremedev.co/endpoints/team_detail.php?id=" + encodeURIComponent(id) + "&lang=" + encodeURIComponent(lang);

    fetch(apiUrl)
        .then(function(res) {
            if(res.status === 404) throw {notfound: true};
            if(!res.ok) throw new Error("network");
            return res.json();
        })
        .then(function(m) {
            skeleton.style.display = "none";
            content.style.display = "block";
            if(!m || !m.name) {
                errorDiv.style.display = "block";
                errorDiv.textContent = t['team_member_not_found'] || '';
                return;
            }
            var html = '';
            if (m.photo && m.photo.trim()) {
                html += '<img class="member-photo" src="' + m.photo.replace(/"/g,"&quot;") + '" alt="' + (m.name.replace(/"/g,"&quot;")) + '">';
            }
            html += '<div class="p-4">';
            if (m.name) html += '<h1 class="member-title">' + m.name + '</h1>';
            if (m.role_name) html += '<span class="member-role">' + m.role_name + '</span>';
            if (m.sub_role) html += '<span class="member-subrole">' + m.sub_role + '</span>';
            if (m.skill) html += '<span class="member-skill">' + m.skill + '</span>';
            // بیوی بلند با هندلینگ خالی بودن
            var longBio = (m.long_bio || '').trim();
            if (longBio) {
                html += '<div class="member-long-bio">' + longBio + '</div>';
            } else {
                html += '<div class="member-long-bio" style="opacity:0.7;font-style:italic;">' + (t['team_member_no_long_bio'] || 'عضو تیم مورد نظر توضیحاتی برای این زبان ننوشته است.') + '</div>';
            }
            html += '</div>';
            content.innerHTML = html;
        })
        .catch(function(err) {
            skeleton.style.display = "none";
            content.style.display = "block";
            errorDiv.style.display = "block";
            errorDiv.textContent = (err && err.notfound) ? (t['team_member_not_found'] || '') : (t['error_loading_team_member'] || '');
        });
});
</script>
</body>
</html>