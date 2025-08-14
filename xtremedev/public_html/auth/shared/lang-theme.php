<?php
// shared/lang-theme.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$lang = 'en';   // مقدار پیش‌فرض زبان
$theme = 'light'; // مقدار پیش‌فرض تم

if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/inc/database-config.php';
    $stmt = $mysqli->prepare("SELECT lang, theme FROM users WHERE id=?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($user_lang, $user_theme);
    if ($stmt->fetch()) {
        $lang = $user_lang ?: $lang;
        $theme = $user_theme ?: $theme;
    }
    $stmt->close();
} else {
    if (isset($_COOKIE['site_lang'])) {
        $lang = $_COOKIE['site_lang'];
    }
    if (isset($_COOKIE['site_theme'])) {
        $theme = $_COOKIE['site_theme'];
    }
}

// تعریف ثابت برای استفاده آسان در سایر فایل‌ها
define('SITE_LANG', $lang);
define('SITE_THEME', $theme);