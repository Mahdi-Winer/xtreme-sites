<?php
if (!isset($lang)) {
    $lang = $_COOKIE['site_lang'] ?? DEFAULT_LANG;
}
?>
    <link rel="stylesheet" href="<?=ASSETS_BASE?>/css/bootstrap.min.css">
<?php if ($lang === 'fa'): ?>
    <link rel="stylesheet" href="<?=ASSETS_BASE?>/css/bootstrap.rtl.min.css">
<?php endif; ?>
    <link rel="stylesheet" href="<?=ASSETS_BASE?>/fonts/font-awesome/css/all.min.css">
    <link rel="stylesheet" href="<?=ASSETS_BASE?>/fonts/vazirmatn/vazirmatn.css">
    <link rel="stylesheet" href="<?=ASSETS_BASE?>/css/style.css">
<?php
if (file_exists(__DIR__ . '/dashboard-admin-styles.php')) include __DIR__ . '/dashboard-admin-styles.php';
?>