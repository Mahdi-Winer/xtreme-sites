<?php
require_once __DIR__ . '/config.php';
$lang = isset($_GET['lang']) ? strtolower($_GET['lang']) : DEFAULT_LANG;
if (!in_array($lang, ALLOWED_LANGS)) $lang = DEFAULT_LANG;

// اگر روی لوکال هستی این خط را فقط برای تست تغییر بده
// setcookie('site_lang', $lang, time() + 3600 * 24 * 365, "/", "");
// اگر روی سرور واقعی هستی این خط را بگذار:
setcookie('site_lang', $lang, time() + 3600 * 24 * 365, "/", MAIN_DOMAIN);

$_COOKIE['site_lang'] = $lang;

$redirect = $_GET['redirect'] ?? $_SERVER['HTTP_REFERER'] ?? '/';
header('Location: ' . $redirect);
exit;