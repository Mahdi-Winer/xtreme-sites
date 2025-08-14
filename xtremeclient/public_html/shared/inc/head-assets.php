<?php
require_once __DIR__ . '/config.php';
if (!isset($lang)) {
    $lang = $_COOKIE['site_lang'] ?? DEFAULT_LANG;
}
?>
<!-- Favicon -->
<link rel="icon" type="image/png" href="http://dl.xtremedev.co/company-resourse/xtreme-company-logo-textless-blue.png">

<?php if ($lang === 'fa'): ?>
<link rel="stylesheet" href="<?=ASSETS_BASE?>/css/bootstrap.rtl.min.css">
<?php else: ?>
<link rel="stylesheet" href="<?=ASSETS_BASE?>/css/bootstrap.min.css">
<?php endif; ?>
<link rel="stylesheet" href="<?=ASSETS_BASE?>/fonts/font-awesome/css/all.min.css">
<link rel="stylesheet" href="<?=ASSETS_BASE?>/css/style.css">

<style>
<?php if ($lang === 'fa'): ?>
    /* IRANSans */
    @font-face {
        font-family: 'IranSans';
        src: url('<?=ASSETS_BASE?>/fonts/iran-sans/IRANSansWeb.woff2') format('woff2'),
             url('<?=ASSETS_BASE?>/fonts/iran-sans/IRANSansWeb.woff') format('woff');
        font-weight: 400;
        font-style: normal;
        font-display: swap;
    }
    @font-face {
        font-family: 'IranSans';
        src: url('<?=ASSETS_BASE?>/fonts/iran-sans/IRANSansWeb_Bold.woff2') format('woff2'),
             url('<?=ASSETS_BASE?>/fonts/iran-sans/IRANSansWeb_Bold.woff') format('woff');
        font-weight: 700;
        font-style: normal;
        font-display: swap;
    }
    @font-face {
        font-family: 'IranSans';
        src: url('<?=ASSETS_BASE?>/fonts/iran-sans/IRANSansWeb_Light.woff2') format('woff2'),
             url('<?=ASSETS_BASE?>/fonts/iran-sans/IRANSansWeb_Light.woff') format('woff');
        font-weight: 300;
        font-style: normal;
        font-display: swap;
    }
    @font-face {
        font-family: 'IranSans';
        src: url('<?=ASSETS_BASE?>/fonts/iran-sans/IRANSansWeb_Medium.woff2') format('woff2'),
             url('<?=ASSETS_BASE?>/fonts/iran-sans/IRANSansWeb_Medium.woff') format('woff');
        font-weight: 500;
        font-style: normal;
        font-display: swap;
    }
    @font-face {
        font-family: 'IranSans';
        src: url('<?=ASSETS_BASE?>/fonts/iran-sans/IRANSansWeb_Black.woff2') format('woff2'),
             url('<?=ASSETS_BASE?>/fonts/iran-sans/IRANSansWeb_Black.woff') format('woff');
        font-weight: 900;
        font-style: normal;
        font-display: swap;
    }
    @font-face {
        font-family: 'IranSans';
        src: url('<?=ASSETS_BASE?>/fonts/iran-sans/IRANSansWeb_UltraLight.woff2') format('woff2'),
             url('<?=ASSETS_BASE?>/fonts/iran-sans/IRANSansWeb_UltraLight.woff') format('woff');
        font-weight: 200;
        font-style: normal;
        font-display: swap;
    }
    body, html {
        font-family: 'IranSans', Vazirmatn, Tahoma, Arial, sans-serif !important;
    }
<?php else: ?>
    /* Roboto */
    @font-face {
        font-family: 'Roboto';
        src: url('<?=ASSETS_BASE?>/fonts/roboto/Roboto-Regular-webfont.woff') format('woff');
        font-weight: 400;
        font-style: normal;
        font-display: swap;
    }
    @font-face {
        font-family: 'Roboto';
        src: url('<?=ASSETS_BASE?>/fonts/roboto/Roboto-Bold-webfont.woff') format('woff');
        font-weight: 700;
        font-style: normal;
        font-display: swap;
    }
    @font-face {
        font-family: 'Roboto';
        src: url('<?=ASSETS_BASE?>/fonts/roboto/Roboto-Light-webfont.woff') format('woff');
        font-weight: 300;
        font-style: normal;
        font-display: swap;
    }
    @font-face {
        font-family: 'Roboto';
        src: url('<?=ASSETS_BASE?>/fonts/roboto/Roboto-Medium-webfont.woff') format('woff');
        font-weight: 500;
        font-style: normal;
        font-display: swap;
    }
    @font-face {
        font-family: 'Roboto';
        src: url('<?=ASSETS_BASE?>/fonts/roboto/Roboto-Thin-webfont.woff') format('woff');
        font-weight: 100;
        font-style: normal;
        font-display: swap;
    }
    @font-face {
        font-family: 'Roboto';
        src: url('<?=ASSETS_BASE?>/fonts/roboto/Roboto-Italic-webfont.woff') format('woff');
        font-weight: 400;
        font-style: italic;
        font-display: swap;
    }
    @font-face {
        font-family: 'Roboto';
        src: url('<?=ASSETS_BASE?>/fonts/roboto/Roboto-BoldItalic-webfont.woff') format('woff');
        font-weight: 700;
        font-style: italic;
        font-display: swap;
    }
    @font-face {
        font-family: 'Roboto';
        src: url('<?=ASSETS_BASE?>/fonts/roboto/Roboto-LightItalic-webfont.woff') format('woff');
        font-weight: 300;
        font-style: italic;
        font-display: swap;
    }
    @font-face {
        font-family: 'Roboto';
        src: url('<?=ASSETS_BASE?>/fonts/roboto/Roboto-MediumItalic-webfont.woff') format('woff');
        font-weight: 500;
        font-style: italic;
        font-display: swap;
    }
    @font-face {
        font-family: 'Roboto';
        src: url('<?=ASSETS_BASE?>/fonts/roboto/Roboto-ThinItalic-webfont.woff') format('woff');
        font-weight: 100;
        font-style: italic;
        font-display: swap;
    }
    body, html {
        font-family: 'Roboto', Arial, sans-serif !important;
    }
<?php endif; ?>
</style>
<?php
if (file_exists(__DIR__ . '/dashboard-admin-styles.php')) include __DIR__ . '/dashboard-admin-styles.php';
?>