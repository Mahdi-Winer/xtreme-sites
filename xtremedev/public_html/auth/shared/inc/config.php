<?php
// دامنه اصلی برای ساب‌دامین (حتماً با نقطه اول!)
if (!defined('MAIN_DOMAIN')) define('MAIN_DOMAIN', '.localhost');

// زبان‌های مجاز
if (!defined('ALLOWED_LANGS')) define('ALLOWED_LANGS', ['fa', 'en', 'ru']);

// زبان پیش‌فرض
if (!defined('DEFAULT_LANG')) define('DEFAULT_LANG', 'en');

// مسیر assetها (در صورت نیاز برای تغییر ساختار در آینده)
if (!defined('ASSETS_BASE')) define('ASSETS_BASE', '/shared/assets');
if (!defined('INC_BASE')) define('INC_BASE', '/shared/inc');