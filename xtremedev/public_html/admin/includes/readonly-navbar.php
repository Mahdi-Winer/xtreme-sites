<?php
// اگر مسیر فایل config.php فرق دارد، صحیح کن
require_once __DIR__ . '/../../shared/inc/config.php';

$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'en');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : 'en';
$is_rtl = ($lang === 'fa');
$langs = [
    'en' => 'English',
    'fa' => 'فارسی'
];
$menu_items = [
    ['file' => 'index.php',           'key' => 'dashboard'],
    ['file' => 'users.php',           'key' => 'users'],
    ['file' => 'products.php',        'key' => 'products'],
    ['file' => 'orders.php',          'key' => 'orders'],
    ['file' => 'invoices.php',        'key' => 'invoices'],
    ['file' => 'support_tickets.php', 'key' => 'tickets'],
    ['file' => 'logout.php',          'key' => 'logout'],
];
$labels = [
    'en' => [
        'dashboard' => 'Dashboard',
        'users'     => 'Users',
        'products'  => 'Products',
        'orders'    => 'Orders',
        'invoices'  => 'Invoices',
        'tickets'   => 'Tickets',
        'logout'    => 'Logout',
        'brand'     => 'XtremeDev Admin',
        'lang_switch' => 'Change language',
    ],
    'fa' => [
        'dashboard' => 'داشبورد',
        'users'     => 'کاربران',
        'products'  => 'محصولات',
        'orders'    => 'سفارشات',
        'invoices'  => 'فاکتورها',
        'tickets'   => 'تیکت‌ها',
        'logout'    => 'خروج',
        'brand'     => 'پنل ادمین XtremeDev',
        'lang_switch' => 'تغییر زبان',
    ]
];
$self = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg admin-navbar<?php if($is_rtl) echo ' rtl-navbar'; ?>">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php"><?=$labels[$lang]['brand']?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav <?= $is_rtl ? 'me-auto' : 'ms-auto' ?> mb-2 mb-lg-0">
                <?php foreach($menu_items as $item): ?>
                    <li class="nav-item">
                        <a class="nav-link<?=$self == $item['file'] ? ' active' : ''?>" href="<?=$item['file']?>">
                            <?=$labels[$lang][$item['key']]?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="d-flex align-items-center gap-2 navbar-actions">
                <div class="dropdown lang-dropdown">
                    <button class="lang-btn btn btn-sm btn-light" type="button" id="langDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="<?=$labels[$lang]['lang_switch']?>">
                        <span class="d-none d-md-inline"><?=$langs[$lang]?></span>
                        <span class="d-inline d-md-none" style="font-size:1.14rem;"><i class="bi bi-translate"></i></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="langDropdown">
                        <?php foreach($langs as $k => $title): ?>
                            <?php if($k !== $lang): ?>
                                <li>
                                    <a class="dropdown-item small"
                                       href="../shared/inc/language_set.php?lang=<?=$k?>&redirect=<?=urlencode($_SERVER['REQUEST_URI'])?>">
                                        <?=$title?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>