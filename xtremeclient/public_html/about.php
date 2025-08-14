<?php
session_start();
require_once __DIR__ . '/shared/inc/config.php';

// تعیین زبان
$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$is_rtl = $lang === 'fa';

// بارگذاری ترجمه سایت
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
    <title><?= $translations['about_us_title'] ?? '' ?></title>
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
    body {
      background: var(--surface, #f4f7fa);
      color: var(--text, #222);
    }
    .about-title-center {
      display: flex;
      justify-content: center;
      align-items: center;
      margin-bottom: 2.5rem;
      margin-top: 2.5rem;
    }
    .about-section-title {
      font-weight: 900;
      font-family: Vazirmatn, Tahoma, Arial, sans-serif;
      font-size: 2.1rem;
      letter-spacing: 1.3px;
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
    .about-section-title:after {
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
      max-width: 720px;
      background: var(--surface-alt, #fff);
      margin: 0 auto 3rem auto;
      border-radius: 18px;
      box-shadow: 0 4px 32px var(--shadow-card, #2499fa16);
      padding: 2.5rem 2rem 2rem 2rem;
      font-size: 1.11rem;
      line-height: 2.0;
      color: var(--text, #222);
    }
    .about-section-heading {
      font-size: 1.23rem;
      font-weight: bold;
      margin-top: 2.2rem;
      margin-bottom: 0.7rem;
      color: var(--primary, #2499fa);
    }
    .about-contact {
      margin-top: 2.5rem;
      padding: 1rem 0 0 0;
      border-top: 1px solid #e3eefb;
      display: flex;
      flex-direction: column;
      gap: 0.8rem;
      font-size: 1rem;
    }
    .dark-theme .about-contact {
      border-top: 1px solid #21314a;
    }
    .about-contact-label {
      font-weight: bold;
      color: var(--primary, #2499fa);
      margin-bottom: 0.2rem;
      display: inline-block;
    }
    @media (max-width: 600px) {
      .about-content {
        padding: 1.2rem 0.7rem 1.2rem 0.7rem;
      }
      .about-section-title {
        font-size: 1.3rem;
      }
      .about-section-heading {
        font-size: 1.07rem;
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
        <div class="about-title-center">
            <h1 class="about-section-title"><?= $translations['about_us_title'] ?? '' ?></h1>
        </div>
        <div class="about-content">
            <div class="about-intro">
                <?= $translations['about_us_intro'] ?? '' ?>
            </div>

            <div class="about-section-heading"><?= $translations['about_us_vision'] ?? '' ?></div>
            <div><?= $translations['about_us_vision_text'] ?? '' ?></div>

            <div class="about-section-heading"><?= $translations['about_us_mission'] ?? '' ?></div>
            <div><?= $translations['about_us_mission_text'] ?? '' ?></div>

            <div class="about-section-heading"><?= $translations['about_us_contact'] ?? '' ?></div>
            <div class="about-contact">
                <div>
                    <span class="about-contact-label"><?= $translations['about_us_email'] ?? '' ?>:</span>
                    <a href="mailto:info@example.com" rel="noopener">info@example.com</a>
                </div>
                <div>
                    <span class="about-contact-label"><?= $translations['about_us_phone'] ?? '' ?>:</span>
                    <a href="tel:+982112345678" rel="noopener">+98 21 1234 5678</a>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__.'/includes/footer.php'; ?>
    <?php include __DIR__.'/shared/inc/foot-assets.php'; ?>
    <?php include __DIR__.'/includes/theme-script.php'; ?>
</body>
</html>