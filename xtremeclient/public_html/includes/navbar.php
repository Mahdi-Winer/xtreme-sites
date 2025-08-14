<?php
require_once __DIR__ . '/../shared/inc/config.php';

$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : DEFAULT_LANG;
$lang = in_array($lang, ALLOWED_LANGS) ? $lang : DEFAULT_LANG;
$is_rtl = ($lang === 'fa');
$project_id = 2;

// بارگذاری ترجمه از فایل JSON
$translations = [];
$lang_file = __DIR__ . '/../shared/assets/languages/' . $lang . '.json';
if (file_exists($lang_file)) {
    $json = file_get_contents($lang_file);
    $translations = json_decode($json, true);
}

// تعریف منو بر اساس کلید ترجمه
$menu_items = [
    ['file' => 'index.php',      'key' => 'home'],
    ['file' => 'articles.php',       'key' => 'articles'],
    ['file' => 'changelog.php',  'key' => 'changelog'],
    ['file' => 'features.php',   'key' => 'features'],
    ['file' => 'dashboard/support.php',    'key' => 'support'],
    ['file' => 'store.php',      'key' => 'store'],
    ['file' => 'https://xtremedev.co/dashboard/join-us.php', 'key' => 'employment'],
    ['file' => 'about.php',      'key' => 'about'],
];
?>
<style>
/* استایل پروفایل و منو */
.btn-profile {
    display: inline-flex;
    align-items: center;
    background: #fff;
    color: #2499fa;
    border-radius: 22px;
    padding: 0.28rem 1.08rem 0.28rem 0.7rem;
    font-weight: 700;
    font-size: 1.07rem;
    border: none;
    box-shadow: 0 1px 8px #2499fa11;
    transition: box-shadow 0.18s, background 0.18s, color 0.18s;
    margin-<?php echo $is_rtl ? 'left' : 'right' ?>: 11px;
    margin-top: 2px;
    text-decoration: none;
    gap: 0.53rem;
}
.btn-profile:hover, .btn-profile:focus {
    background: #f1f7fd;
    color: #174a7a;
    box-shadow: 0 4px 16px #2499fa12;
    text-decoration: none;
}
.btn-profile .avatar {
    width: 31px;
    height: 31px;
    border-radius: 50%;
    object-fit: cover;
    margin-<?php echo $is_rtl ? 'right' : 'left' ?>: 0.26rem;
    background: #e8f0fa;
    border: 2.2px solid #2499fa22;
}
.navbar-hamburger {
    background: none;
    border: none;
    font-size: 2.1rem;
    color: #2499fa;
    position: absolute;
    top: 13px;
    <?php if($is_rtl): ?>left: 19px;<?php else: ?>right: 19px;<?php endif; ?>
    z-index: 200;
    cursor: pointer;
    display: none;
    padding: 3px 10px;
    transition: background 0.19s;
}
.navbar-hamburger:active,
.navbar-hamburger:focus {
    background: #e4f1fd;
    border-radius: 9px;
}
.mobile-nav {
    display: none;
    position: fixed;
    top: 0; <?php echo $is_rtl ? 'right' : 'left' ?>: 0;
    width: 280px;
    max-width: 99vw;
    height: 100vh;
    background: #fff;
    z-index: 1200;
    box-shadow: 0 0 32px #2499fa17;
    transition: transform 0.28s cubic-bezier(.86,0,.07,1), opacity 0.22s;
    transform: translateX(-120%);
    opacity: 0;
    overflow-y: auto;
}
.rtl-navbar.mobile-nav { right: 0; left: auto; transform: translateX(120%); }
.mobile-nav.open {
    transform: translateX(0);
    opacity: 1;
    display: block;
}
.mobile-nav-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: #222b;
    z-index: 1100;
    transition: opacity 0.22s;
    opacity: 0;
}
.mobile-nav.open + .mobile-nav-overlay,
.mobile-nav-overlay.open {
    display: block;
    opacity: 1;
}
.close-mobile-nav {
    background: none;
    border: none;
    font-size: 2rem;
    color: #888;
    position: absolute;
    top: 9px;
    <?php echo $is_rtl ? 'left' : 'right' ?>: 17px;
    z-index: 5;
    cursor: pointer;
    padding: 3px 10px;
}
.mobile-logo {
    text-align: center;
    margin-top: 23px;
    margin-bottom: 25px;
}
.mobile-links {
    list-style: none;
    padding: 0 0 3rem 0;
    margin: 0;
    text-align: <?php echo $is_rtl ? 'right' : 'left' ?>;
}
.mobile-links .nav-item {
    margin: 0;
}
.mobile-links .nav-link {
    display: block;
    color: #2499fa;
    font-size: 1.15rem;
    font-weight: 700;
    padding: 1rem 2.2rem 1rem 1.3rem;
    border-bottom: 1px solid #f0f4fa;
    text-decoration: none;
    transition: background 0.13s, color 0.13s;
}
.mobile-links .nav-link.active,
.mobile-links .nav-link:hover {
    background: #f4f9fe;
    color: #1555a1;
}
.mobile-actions {
    margin: 1.5rem 1.2rem 1.7rem 1.2rem;
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
    align-items: <?php echo $is_rtl ? 'flex-end' : 'flex-start' ?>;
}
.mobile-actions .btn-profile {
    width: 100%;
    justify-content: <?php echo $is_rtl ? 'flex-end' : 'flex-start' ?>;
}
.mobile-actions .theme-switch {
    font-weight: bold;
    color: #2499fa;
    background: #f4f7fa;
    border: none;
    border-radius: 8px;
    padding: 7px 24px;
    font-size: 1.06rem;
    margin-top: 0.4em;
    cursor: pointer;
    transition: background 0.17s;
}
.mobile-actions .theme-switch:active {
    background: #e0eefc;
}
.mobile-actions .lang-btn {
    background: #f7f7fc;
    color: #222;
    border: none;
    border-radius: 8px;
    padding: 6px 18px;
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 3px;
    cursor: pointer;
}
.mobile-actions .dropdown-menu {
    position: static;
    float: none;
    display: none;
    margin-top: 0.1rem;
    box-shadow: 0 2px 12px #2499fa12;
    border-radius: 8px;
}
.mobile-actions .dropdown-menu.show {
    display: block;
}
.mobile-actions .dropdown-item {
    font-size: 0.97rem;
}
.theme-switch {
    font-weight: bold;
    color: #2499fa;
    background: #f4f7fa;
    border: none;
    border-radius: 8px;
    padding: 7px 22px;
    font-size: 1.09rem;
    margin-left: 7px;
    margin-right: 7px;
    cursor: pointer;
    transition: background 0.17s;
}
.theme-switch:active {
    background: #e0eefc;
}
/* ریسپانسیو: فقط یکی از منوها در هر سایز */
@media (min-width: 992px) {
    .mobile-nav,
    .mobile-nav-overlay,
    .navbar-hamburger { display: none !important; }
}
@media (max-width: 991.98px) {
    .navbar .navbar-content { display: none !important; }
    .navbar-hamburger { display: block !important; }
    .mobile-nav,
    .mobile-nav-overlay { display: block; }
}
</style>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm<?php if($is_rtl) echo ' rtl-navbar'; ?>" id="mainNavbar" style="position:relative;">
    <div class="container">
        <div class="navbar-content d-flex align-items-center w-100 justify-content-between">
            <?php if($is_rtl): ?>
                <div class="navbar-actions d-flex align-items-center">
                    <button id="themeSwitch" class="theme-switch me-2" title="<?= $translations['dark'] ?? 'تیره' ?>"><?= $translations['dark'] ?? 'تیره' ?></button>
                    <a href="<?php echo DASHBOARD_DOMAIN ?>" class="btn-profile">
                        <img class="avatar" src="https://ui-avatars.com/api/?name=<?=urlencode($translations['profile'] ?? 'پروفایل')?>&background=2499fa&color=fff" alt="<?= $translations['profile'] ?? 'پروفایل' ?>">
                        <span><?= $translations['profile'] ?? 'پروفایل' ?></span>
                    </a>
                    <div class="dropdown lang-dropdown">
                        <button class="lang-btn" type="button" id="langDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <span id="navbar-lang-text"><?= $translations['language_fa'] ?? 'فارسی' ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="langDropdown">
                            <li><a class="dropdown-item small" href="<?=INC_BASE?>/language_set.php?lang=en&redirect=<?=urlencode($_SERVER['REQUEST_URI'])?>"><?= $translations['language_en'] ?? 'English' ?></a></li>
                            <li><a class="dropdown-item small" href="<?=INC_BASE?>/language_set.php?lang=fa&redirect=<?=urlencode($_SERVER['REQUEST_URI'])?>"><?= $translations['language_fa'] ?? 'فارسی' ?></a></li>
                        </ul>
                    </div>
                </div>
                <div class="d-flex flex-row align-items-center">
                    <ul class="navbar-nav align-items-center flex-row">
                        <?php $menu_loop = array_reverse($menu_items); ?>
                        <?php foreach($menu_loop as $item): ?>
                            <li class="nav-item">
                                <a class="nav-link<?=basename($_SERVER['PHP_SELF'])==$item['file']?' active':''?>" href="<?=htmlspecialchars($item['file'])?>">
                                    <?= isset($translations[$item['key']]) ? $translations[$item['key']] : $item['key'] ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <li>
                            <a class="navbar-brand ms-3" href="index.php">
                                <img class="logo-img" src="resources/xtreme-company-logo.svg" alt="Logo" id="navbar-logo">
                            </a>
                        </li>
                    </ul>
                </div>
            <?php else: ?>
                <div class="d-flex flex-row align-items-center">
                    <a class="navbar-brand me-3" href="index.php">
                        <img class="logo-img" src="resources/xtreme-company-logo.svg" alt="Logo" id="navbar-logo">
                    </a>
                    <ul class="navbar-nav align-items-center flex-row">
                        <?php foreach($menu_items as $item): ?>
                            <li class="nav-item">
                                <a class="nav-link<?=basename($_SERVER['PHP_SELF'])==$item['file']?' active':''?>" href="<?=htmlspecialchars($item['file'])?>">
                                    <?= isset($translations[$item['key']]) ? $translations[$item['key']] : $item['key'] ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="navbar-actions d-flex align-items-center">
                    <div class="dropdown lang-dropdown">
                        <button class="lang-btn" type="button" id="langDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <span id="navbar-lang-text"><?= $translations['language_en'] ?? 'English' ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="langDropdown">
                            <li><a class="dropdown-item small" href="<?=INC_BASE?>/language_set.php?lang=en&redirect=<?=urlencode($_SERVER['REQUEST_URI'])?>"><?= $translations['language_en'] ?? 'English' ?></a></li>
                            <li><a class="dropdown-item small" href="<?=INC_BASE?>/language_set.php?lang=fa&redirect=<?=urlencode($_SERVER['REQUEST_URI'])?>"><?= $translations['language_fa'] ?? 'فارسی' ?></a></li>
                        </ul>
                    </div>
                    <a href="<?php echo DASHBOARD_DOMAIN ?>" class="btn-profile">
                        <img class="avatar" src="https://ui-avatars.com/api/?name=<?=urlencode($translations['profile'] ?? 'Profile')?>&background=2499fa&color=fff" alt="<?= $translations['profile'] ?? 'Profile' ?>">
                        <span><?= $translations['profile'] ?? 'Profile' ?></span>
                    </a>
                    <button id="themeSwitch" class="theme-switch ms-2" title="<?= $translations['dark'] ?? 'Dark' ?>"><?= $translations['dark'] ?? 'Dark' ?></button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- دکمه همبرگر مخصوص موبایل -->
    <button class="navbar-hamburger" id="mobileNavBtn" aria-label="باز کردن منو">
        &#9776;
    </button>
</nav>

<!-- منوی موبایل و overlay -->
<div class="mobile-nav-overlay" id="mobileNavOverlay"></div>
<nav class="mobile-nav<?php if($is_rtl) echo ' rtl-navbar'; ?>" id="mobileNav">
    <button class="close-mobile-nav" id="closeMobileNav" aria-label="بستن">&#10005;</button>
    <div class="mobile-logo">
        <img class="logo-img" src="resources/xtreme-company-logo.svg" alt="Logo" id="mobile-navbar-logo">
    </div>
    <ul class="mobile-links">
        <?php $menu_loop = $is_rtl ? array_reverse($menu_items) : $menu_items; ?>
        <?php foreach($menu_loop as $item): ?>
            <li class="nav-item">
                <a class="nav-link<?=basename($_SERVER['PHP_SELF'])==$item['file']?' active':''?>" href="<?=htmlspecialchars($item['file'])?>">
                    <?= isset($translations[$item['key']]) ? $translations[$item['key']] : $item['key'] ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="mobile-actions">
        <div class="dropdown lang-dropdown">
            <button class="lang-btn" type="button" id="mobileLangDropdown">
                <span id="mobile-navbar-lang-text"><?= $lang === 'fa' ? ($translations['language_fa'] ?? 'فارسی') : ($translations['language_en'] ?? 'English') ?></span>
            </button>
            <ul class="dropdown-menu" id="mobileLangMenu">
                <li><a class="dropdown-item small" href="<?=INC_BASE?>/language_set.php?lang=en&redirect=<?=urlencode($_SERVER['REQUEST_URI'])?>"><?= $translations['language_en'] ?? 'English' ?></a></li>
                <li><a class="dropdown-item small" href="<?=INC_BASE?>/language_set.php?lang=fa&redirect=<?=urlencode($_SERVER['REQUEST_URI'])?>"><?= $translations['language_fa'] ?? 'فارسی' ?></a></li>
            </ul>
        </div>
        <a href="<?php echo DASHBOARD_DOMAIN ?>" class="btn-profile" style="justify-content:center;">
            <img class="avatar" src="https://ui-avatars.com/api/?name=<?=urlencode($translations['profile'] ?? 'Profile')?>&background=2499fa&color=fff" alt="<?= $translations['profile'] ?? 'Profile' ?>">
            <span><?= $translations['profile'] ?? 'Profile' ?></span>
        </a>
        <button id="mobileThemeSwitch" class="theme-switch" title="<?= $translations['dark'] ?? 'Dark' ?>"><?= $translations['dark'] ?? 'Dark' ?></button>
    </div>
</nav>
<div class="nav-placeholder"></div>
<script>
(function(){
    var lang = "<?=htmlspecialchars($lang)?>";
    var project_id = <?=intval($project_id)?>;
    var logoEl = document.getElementById('navbar-logo');
    var mobileLogo = document.getElementById('mobile-navbar-logo');
    fetch("https://api.xtremedev.co/endpoints/settings.php?project_id=" + project_id + "&lang=" + encodeURIComponent(lang))
        .then(function(res){if(!res.ok) throw new Error('API error');return res.json();})
        .then(function(data) {
            if(data.logo_url && logoEl) logoEl.src = data.logo_url;
            if(data.logo_url && mobileLogo) mobileLogo.src = data.logo_url;
        })
        .catch(function(){});
    // موبایل: دکمه همبرگر
    function isMobile() { return window.innerWidth < 992; }
    var mobileNavBtn = document.getElementById('mobileNavBtn');
    var mobileNav = document.getElementById('mobileNav');
    var overlay = document.getElementById('mobileNavOverlay');
    var closeBtn = document.getElementById('closeMobileNav');
    var body = document.body;
    function toggleHamburgerBtn(){
        if(isMobile()) mobileNavBtn.style.display = 'block';
        else mobileNavBtn.style.display = 'none';
    }
    toggleHamburgerBtn();
    window.addEventListener('resize', toggleHamburgerBtn);
    function openMobileNav(){
        mobileNav.classList.add('open');
        overlay.classList.add('open');
        body.classList.add('mobile-menu-open');
    }
    function closeMobileNav(){
        mobileNav.classList.remove('open');
        overlay.classList.remove('open');
        body.classList.remove('mobile-menu-open');
        var mobileLangMenu = document.getElementById('mobileLangMenu');
        if(mobileLangMenu) mobileLangMenu.classList.remove('show');
    }
    mobileNavBtn.addEventListener('click', function(e){
        e.stopPropagation();
        openMobileNav();
    });
    overlay.addEventListener('click', closeMobileNav);
    closeBtn.addEventListener('click', closeMobileNav);
    document.addEventListener('keydown', function(e){
        if(e.key === 'Escape' && mobileNav.classList.contains('open')) closeMobileNav();
    });
    // منوی زبان موبایل
    var mobileLangBtn = document.getElementById('mobileLangDropdown');
    var mobileLangMenu = document.getElementById('mobileLangMenu');
    if(mobileLangBtn && mobileLangMenu){
        mobileLangBtn.addEventListener('click', function(e){
            e.stopPropagation();
            mobileLangMenu.classList.toggle('show');
        });
        document.addEventListener('click', function(e){
            if(mobileLangMenu.classList.contains('show') && !mobileLangMenu.contains(e.target) && e.target!==mobileLangBtn) mobileLangMenu.classList.remove('show');
        });
        var items = mobileLangMenu.querySelectorAll('.dropdown-item');
        items.forEach(function(item){
            item.addEventListener('click', function(){
                mobileLangMenu.classList.remove('show');
                closeMobileNav();
            });
        });
    }
    // پشتیبانی از دکمه theme برای موبایل
    var themeSwitch = document.getElementById('themeSwitch');
    var mobileThemeSwitch = document.getElementById('mobileThemeSwitch');
    if(themeSwitch && mobileThemeSwitch){
        mobileThemeSwitch.addEventListener('click', function(){
            themeSwitch.click();
        });
    }
})();
</script>