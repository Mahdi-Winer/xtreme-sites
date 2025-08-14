<?php
require_once __DIR__ . '/../../shared/inc/config.php';

$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'en');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : 'en';
$is_rtl = ($lang === 'fa');
$langs = [
    'en' => 'English',
    'fa' => 'فارسی'
];
$menu_items = [
    'en' => [
        'dashboard' => 'Dashboard',
        'users' => 'Users',
        'products' => 'Products',
        'orders' => 'Orders',
        'invoices' => 'Invoices',
        'tickets' => 'Tickets',
        'joinus' => 'Join Us Requests',
        'logout' => 'Logout',
        'profile' => 'Profile',
        'lang_switch' => 'Change language',
        'brand' => 'XtremeDev Admin',
    ],
    'fa' => [
        'dashboard' => 'داشبورد',
        'users' => 'کاربران',
        'products' => 'محصولات',
        'orders' => 'سفارشات',
        'invoices' => 'فاکتورها',
        'tickets' => 'تیکت‌ها',
        'joinus' => 'درخواست همکاری',
        'logout' => 'خروج',
        'profile' => 'پروفایل',
        'lang_switch' => 'تغییر زبان',
        'brand' => 'پنل ادمین XtremeDev',
    ]
];
$self = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg admin-navbar<?php if($is_rtl) echo ' rtl-navbar'; ?>">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php"><?=$menu_items[$lang]['brand']?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-between" id="adminNav">
            <ul class="navbar-nav <?= $is_rtl ? 'me-auto' : 'ms-auto' ?> mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link<?=$self=='index.php'?' active':''?>" href="index.php"><?=$menu_items[$lang]['dashboard']?></a></li>
                <li class="nav-item"><a class="nav-link<?=$self=='users.php'?' active':''?>" href="users.php"><?=$menu_items[$lang]['users']?></a></li>
                <li class="nav-item"><a class="nav-link<?=$self=='products.php'?' active':''?>" href="products.php"><?=$menu_items[$lang]['products']?></a></li>
                <li class="nav-item"><a class="nav-link<?=$self=='orders.php'?' active':''?>" href="orders.php"><?=$menu_items[$lang]['orders']?></a></li>
                <li class="nav-item"><a class="nav-link<?=$self=='invoices.php'?' active':''?>" href="invoices.php"><?=$menu_items[$lang]['invoices']?></a></li>
                <li class="nav-item"><a class="nav-link<?=$self=='support_tickets.php'?' active':''?>" href="support_tickets.php"><?=$menu_items[$lang]['tickets']?></a></li>
                <li class="nav-item"><a class="nav-link<?=$self=='join-us-requests.php'?' active':''?>" href="join-us-requests.php"><?=$menu_items[$lang]['joinus']?></a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php"><?=$menu_items[$lang]['logout']?></a></li>
            </ul>
            <div class="d-flex align-items-center gap-2 navbar-actions">
                <!-- دکمه تغییر زبان -->
                <div class="dropdown lang-dropdown">
                    <button class="lang-btn btn btn-sm btn-light" type="button" id="langDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="<?=$menu_items[$lang]['lang_switch']?>">
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