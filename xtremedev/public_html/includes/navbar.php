<?php
require_once __DIR__ . '/../shared/inc/config.php';

$lang = $_COOKIE['site_lang'] ?? (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$is_rtl = ($lang === 'fa');
$project_id = 1;

$translations = [];
$lang_file = __DIR__ . '/../shared/assets/languages/' . $lang . '.json';
if (file_exists($lang_file)) {
    $json = file_get_contents($lang_file);
    $translations = json_decode($json, true);
}

$native_language_names = [
    'fa' => 'فارسی',
    'en' => 'English',
    'ar' => 'العربية',
    'tr' => 'Türkçe',
    'fr' => 'Français',
    'de' => 'Deutsch',
    'es' => 'Español',
    'ru' => 'Русский',
    'zh' => '中文',
    'ja' => '日本語',
];

$langs = [];
if(defined('ALLOWED_LANGS') && is_array(ALLOWED_LANGS)){
    foreach(ALLOWED_LANGS as $code){
        $flag = '';
        if ($code === 'fa') $flag = '🇮🇷';
        elseif ($code === 'en') $flag = '🇬🇧';
        elseif ($code === 'ar') $flag = '🇸🇦';
        elseif ($code === 'tr') $flag = '🇹🇷';
        elseif ($code === 'fr') $flag = '🇫🇷';
        elseif ($code === 'de') $flag = '🇩🇪';
        elseif ($code === 'es') $flag = '🇪🇸';
        elseif ($code === 'ru') $flag = '🇷🇺';
        elseif ($code === 'zh') $flag = '🇨🇳';
        elseif ($code === 'ja') $flag = '🇯🇵';
        $langs[$code] = [
            'text' => $native_language_names[$code] ?? strtoupper($code),
            'flag' => $flag
        ];
    }
}

$menu_items = [
    ['file' => 'index.php',      'key' => 'home'],
    ['file' => 'projects.php',   'key' => 'projects'],
    ['file' => 'team.php',       'key' => 'team'],
    ['file' => 'articles.php',   'key' => 'articles'],
    ['file' => 'about-us.php',   'key' => 'about_us'],
];
?>
<style>
:root {
    --main-blue: #2499fa;
    --main-blue-dark: #1555a1;
    --main-bg: #fff;
    --main-bg-dark: #20232a;
    --navbar-shadow: 0 1px 8px #2499fa11;
    --navbar-shadow-dark: 0 2px 16px #01010133;
}
.navbar {
    background: var(--main-bg);
    box-shadow: var(--navbar-shadow);
    min-height: 64px;
    font-family: inherit;
    z-index: 1040;
}
.rtl-navbar {
    direction: rtl;
}
.navbar .logo-img {
    height: 41px;
    width: auto;
    max-width: 170px;
}
.navbar-brand {
    display: flex;
    align-items: center;
    padding: 0;
}
.navbar-content {
    width: 100%;
    min-height: 64px;
    padding: 0;
}
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
.theme-switch:active { background: #e0eefc; }
@media (max-width: 991.98px) {
    .mobile-actions .btn-profile {
        width: 100%;
        justify-content: center;
        margin: 0;
    }
    .mobile-actions .theme-switch {
        font-size: 1.07rem;
        padding: 7px 24px;
        margin: 0;
        width: 100%;
    }
}
@media (max-width: 600px) {
    .navbar .logo-img,
    .mobile-logo .logo-img {
        max-width: 110px;
        height: 31px;
    }
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
.mobile-links .nav-item { margin: 0; }
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
.mobile-actions .dropdown-menu.show { display: block; }
.mobile-actions .dropdown-item { font-size: 0.97rem; }
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

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm<?= $is_rtl ? ' rtl-navbar' : '' ?>" id="mainNavbar" style="position:relative;">
    <div class="container">
        <div class="navbar-content d-flex align-items-center w-100 justify-content-between">
            <?php if($is_rtl): ?>
                <div class="navbar-actions d-flex align-items-center">
                    <button id="themeSwitch" class="theme-switch me-2"></button>
                    <a href="<?= DASHBOARD_DOMAIN ?>" class="btn-profile">
                        <img class="avatar" src="http://dl.xtremedev.co/company-resourse/default-pfp.png" alt="<?= $translations['profile'] ?? '' ?>">
                        <span><?= $translations['profile'] ?? '' ?></span>
                    </a>
                    <div class="dropdown lang-dropdown">
                        <button class="lang-btn" type="button" id="langDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <span id="navbar-lang-text"><?= $langs[$lang]['flag'].' '.$langs[$lang]['text'] ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="langDropdown">
                            <?php foreach($langs as $code=>$info): ?>
                                <li><a class="dropdown-item small" href="<?=INC_BASE?>/language_set.php?lang=<?=$code?>&redirect=<?=urlencode($_SERVER['REQUEST_URI'])?>"><?=$info['flag'].' '.$info['text']?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <div class="d-flex flex-row align-items-center">
                    <ul class="navbar-nav align-items-center flex-row">
                        <?php $menu_loop = array_reverse($menu_items); ?>
                        <?php foreach($menu_loop as $item): ?>
                            <li class="nav-item">
                                <a class="nav-link<?=basename($_SERVER['PHP_SELF'])==$item['file']?' active':''?>" href="<?=htmlspecialchars($item['file'])?>">
                                    <?= isset($translations[$item['key']]) ? $translations[$item['key']] : '' ?>
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
                                    <?= isset($translations[$item['key']]) ? $translations[$item['key']] : '' ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="navbar-actions d-flex align-items-center">
                    <div class="dropdown lang-dropdown">
                        <button class="lang-btn" type="button" id="langDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <span id="navbar-lang-text"><?= $langs[$lang]['flag'].' '.$langs[$lang]['text'] ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="langDropdown">
                            <?php foreach($langs as $code=>$info): ?>
                                <li><a class="dropdown-item small" href="<?=INC_BASE?>/language_set.php?lang=<?=$code?>&redirect=<?=urlencode($_SERVER['REQUEST_URI'])?>"><?=$info['flag'].' '.$info['text']?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <a href="<?= DASHBOARD_DOMAIN ?>" class="btn-profile">
                        <img class="avatar" src="http://dl.xtremedev.co/company-resourse/default-pfp.png" alt="<?= $translations['profile'] ?? '' ?>">
                        <span><?= $translations['profile'] ?? '' ?></span>
                    </a>
                    <button id="themeSwitch" class="theme-switch ms-2"></button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <button class="navbar-hamburger" id="mobileNavBtn" aria-label="<?= $translations['more'] ?? '' ?>">
        &#9776;
    </button>
</nav>

<div class="mobile-nav-overlay" id="mobileNavOverlay"></div>
<nav class="mobile-nav<?php if($is_rtl) echo ' rtl-navbar'; ?>" id="mobileNav">
    <button class="close-mobile-nav" id="closeMobileNav" aria-label="<?= $translations['close'] ?? '' ?>">&#10005;</button>
    <div class="mobile-logo">
        <img class="logo-img" src="resources/xtreme-company-logo.svg" alt="Logo" id="mobile-navbar-logo">
    </div>
    <ul class="mobile-links">
        <?php $menu_loop = $is_rtl ? array_reverse($menu_items) : $menu_items; ?>
        <?php foreach($menu_loop as $item): ?>
            <li class="nav-item">
                <a class="nav-link<?=basename($_SERVER['PHP_SELF'])==$item['file']?' active':''?>" href="<?=htmlspecialchars($item['file'])?>">
                    <?= isset($translations[$item['key']]) ? $translations[$item['key']] : '' ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="mobile-actions">
        <div class="dropdown lang-dropdown">
            <button class="lang-btn" type="button" id="mobileLangDropdown">
                <span id="mobile-navbar-lang-text"><?= $langs[$lang]['flag'].' '.$langs[$lang]['text'] ?></span>
            </button>
            <ul class="dropdown-menu" id="mobileLangMenu">
                <?php foreach($langs as $code => $info): ?>
                    <li>
                        <a class="dropdown-item small" href="<?=INC_BASE?>/language_set.php?lang=<?=$code?>&redirect=<?=urlencode($_SERVER['REQUEST_URI'])?>">
                            <?= $info['flag'].' '.$info['text'] ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <a href="<?= DASHBOARD_DOMAIN ?>" class="btn-profile">
            <img class="avatar" src="http://dl.xtremedev.co/company-resourse/default-pfp.png" alt="<?= $translations['profile'] ?? '' ?>">
            <span><?= $translations['profile'] ?? '' ?></span>
        </a>
        <button id="mobileThemeSwitch" class="theme-switch"></button>
    </div>
</nav>
<div class="nav-placeholder"></div>

<script>
window.PAGE_TRANSLATIONS = <?=json_encode($translations, JSON_UNESCAPED_UNICODE)?>;
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var lang = "<?=htmlspecialchars($lang)?>";
    var project_id = <?=intval($project_id)?>;
    var logoEl = document.getElementById('navbar-logo');
    var mobileLogo = document.getElementById('mobile-navbar-logo');
    var navbarLangText = document.getElementById('navbar-lang-text');
    var mobileLangText = document.getElementById('mobile-navbar-lang-text');
    var langsDict = <?=json_encode($langs, JSON_UNESCAPED_UNICODE)?>;
    var t = window.PAGE_TRANSLATIONS || {};
    fetch("https://api.xtremedev.co/endpoints/settings.php?project_id=" + project_id + "&lang=" + encodeURIComponent(lang))
        .then(function(res){if(!res.ok) throw new Error('API error');return res.json();})
        .then(function(data) {
            if(data.logo_url && logoEl) logoEl.src = data.logo_url;
            if(data.logo_url && mobileLogo) mobileLogo.src = data.logo_url;
        }).catch(function(){});
    if(navbarLangText && langsDict[lang]) navbarLangText.innerHTML = langsDict[lang].flag + ' ' + langsDict[lang].text;
    if(mobileLangText && langsDict[lang]) mobileLangText.innerHTML = langsDict[lang].flag + ' ' + langsDict[lang].text;

    // دکمه تم: متن را براساس حالت فعلی و ترجمه تغییر بده
    var themeSwitch = document.getElementById('themeSwitch');
    var mobileThemeSwitch = document.getElementById('mobileThemeSwitch');
    function getThemeMode() {
        if(document.body.classList.contains('dark-theme')) return 'dark';
        if(document.body.classList.contains('light-theme')) return 'light';
        var match = document.cookie.match(/theme=(dark|light)/);
        if(match) return match[1];
        return 'light';
    }
    function updateThemeButtonText() {
        var mode = getThemeMode();
        var darkLabel = t['dark'] || 'Dark';
        var lightLabel = t['light'] || 'Light';
        if(themeSwitch) {
            themeSwitch.textContent = mode === 'dark' ? lightLabel : darkLabel;
            themeSwitch.title = themeSwitch.textContent;
        }
        if(mobileThemeSwitch) {
            mobileThemeSwitch.textContent = mode === 'dark' ? lightLabel : darkLabel;
            mobileThemeSwitch.title = mobileThemeSwitch.textContent;
        }
    }
    updateThemeButtonText();
    if(themeSwitch) {
        themeSwitch.addEventListener('click', function(){
            setTimeout(updateThemeButtonText, 10);
        });
    }
    if(mobileThemeSwitch) {
        mobileThemeSwitch.addEventListener('click', function(){
            setTimeout(updateThemeButtonText, 10);
        });
    }

    // موبایل: دکمه همبرگر و سایر بخش‌ها مثل قبل...
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
    if(themeSwitch && mobileThemeSwitch){
        mobileThemeSwitch.addEventListener('click', function(){
            themeSwitch.click();
        });
    }
});
</script>