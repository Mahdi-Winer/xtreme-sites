<?php
session_start();
require_once 'db.php';

$lang = $_GET['lang'] ?? null;
$theme = $_GET['theme'] ?? null;

// اگر کاربر لاگین باشد، تنظیمات را در دیتابیس ذخیره کن
if (isset($_SESSION['user_id'])) {
    if ($lang) {
        $stmt = $mysqli->prepare("UPDATE users SET lang=? WHERE id=?");
        $stmt->bind_param('si', $lang, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
        setcookie('site_lang', $lang, time() + 31536000, '/');
    }
    if ($theme) {
        $stmt = $mysqli->prepare("UPDATE users SET theme=? WHERE id=?");
        $stmt->bind_param('si', $theme, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
        setcookie('site_theme', $theme, time() + 31536000, '/');
    }
} else {
    // اگر کاربر لاگین نیست، فقط روی کوکی ذخیره کن
    if ($lang) setcookie('site_lang', $lang, time() + 31536000, '/');
    if ($theme) setcookie('site_theme', $theme, time() + 31536000, '/');
}

// بعد از ذخیره، کاربر را به مسیر قبلی برگردان
header('Location: ' . ($_GET['redirect'] ?? '/'));
exit;