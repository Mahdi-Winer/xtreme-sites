<?php
session_start();
require_once __DIR__ . '/shared/inc/config.php';

// زبان فقط از ALLOWED_LANGS
$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$is_rtl = $lang === 'fa';
$project_id = 1;

// ترجمه‌ها برای عنوان و ... (در صورت نیاز)
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
    <title><?= $translations['about_us_title'] ?? 'About Us' ?></title>
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
        .about-section {
            max-width: 900px;
            margin: 0 auto;
            background: var(--surface-alt);
            border-radius: 18px;
            box-shadow: 0 4px 18px var(--shadow-card);
            border: 2.2px solid var(--border);
            padding: 2.8rem 2.2rem 2.3rem 2.2rem;
            margin-top: 60px;
            margin-bottom: 60px;
            text-align: center;
        }
        .about-title {
            font-family: Vazirmatn, Tahoma, Arial, sans-serif;
            font-weight: 900;
            font-size: 2.2rem;
            letter-spacing: .7px;
            margin-bottom: 2.2rem;
            margin-top: 0;
            text-align: center;
            background: linear-gradient(90deg, #2499fa 15%, #3ed2f0 85%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
            position: relative;
            display: inline-block;
            padding-bottom: 8px;
        }
        .about-title:after {
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
        .about-content {
            font-size: 1.12rem;
            line-height: 2.05;
            color: var(--text);
            margin-top: 1.8rem;
            word-break: break-word;
            text-align: initial;
        }
        .about-content strong { color: var(--primary); }
        .about-content p { margin-bottom: 1.3rem; }
        @media (max-width: 600px) {
            .about-section { padding: 1.1rem 0.7rem 1.2rem 0.7rem; }
            .about-title { font-size: 1.26rem; }
        }
        .about-loading {
            color: #888;
            text-align: center;
            font-size: 1.05rem;
            margin-top: 2.2rem;
        }
        .about-error {
            color: #e63946;
            text-align: center;
            font-size: 1.08rem;
            margin-top: 2.2rem;
        }
    </style>
</head>
<body<?= $darkThemeActive ? ' class="dark-theme"' : '' ?>>
<?php include __DIR__.'/includes/navbar.php'; ?>

<div class="about-section">
    <h1 class="about-title"><?= $translations['about_us_title'] ?? 'About Us' ?></h1>
    <div class="about-content" id="about-us-content">
        <div class="about-loading"><?= $translations['loading'] ?? 'در حال بارگذاری...' ?></div>
    </div>
</div>

<?php include __DIR__.'/includes/footer.php'; ?>
<?php include __DIR__.'/shared/inc/foot-assets.php'; ?>
<?php include __DIR__.'/includes/theme-script.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var aboutEl = document.getElementById('about-us-content');
    var lang = <?= json_encode($lang) ?>;
    var project_id = <?= intval($project_id) ?>;
    fetch("https://api.xtremedev.co/endpoints/settings.php?project_id=" + project_id + "&lang=" + encodeURIComponent(lang))
        .then(res => {
            if(!res.ok) throw new Error("API Error");
            return res.json();
        })
        .then(data => {
            if (data.about_us_html && data.about_us_html.trim()) {
                aboutEl.innerHTML = data.about_us_html;
            } else {
                aboutEl.innerHTML = "<div class='about-error'><?= $translations['no_data'] ?? 'درباره ما یافت نشد.' ?></div>";
            }
        })
        .catch(() => {
            aboutEl.innerHTML = "<div class='about-error'><?= $translations['error_loading_site_settings'] ?? 'خطا در دریافت تنظیمات سایت' ?></div>";
        });
});
</script>
</body>
</html>