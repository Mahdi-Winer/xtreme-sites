<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../shared/inc/config.php';

// زبان و راست‌به‌چپ
$lang = $_COOKIE['site_lang'] ?? 'en';
$lang = in_array($lang, ['fa','en']) ? $lang : 'en';
$is_rtl = ($lang === 'fa');
$project_id = 1;

// منوهای دوزبانه
$menu_items = [
    'fa' => [
        ['href' => 'index.php',         'title' => 'داشبورد'],
        ['href' => 'my-products.php',   'title' => 'محصولات من'],
        ['href' => 'orders.php',        'title' => 'سفارش‌ها'],
        ['href' => 'invoices.php',      'title' => 'فاکتورها'],
        ['href' => 'tickets.php',       'title' => 'پشتیبانی'],
        ['href' => 'join-us.php',       'title' => 'همکاری'],
    ],
    'en' => [
        ['href' => 'index.php',         'title' => 'Dashboard'],
        ['href' => 'my-products.php',   'title' => 'My Products'],
        ['href' => 'orders.php',        'title' => 'Orders'],
        ['href' => 'invoices.php',      'title' => 'Invoices'],
        ['href' => 'tickets.php',       'title' => 'Support'],
        ['href' => 'join-us.php',       'title' => 'Join Us'],
    ]
];
$langs = ['en' => 'English', 'fa' => 'فارسی'];

// کاربر از سشن SSO
$user = null;
if (isset($_SESSION['user_profile'])) {
    $profile = $_SESSION['user_profile'];
    $user = [
        'name'  => $profile['fullname'] ?? '',
        'email' => $profile['email'] ?? '',
        'photo' => !empty($profile['photo'])
            ? $profile['photo']
            : "http://dl.xtremedev.co/company-resourse/default-pfp.png"
    ];
}

// SSO
$client_id = 'xtremedev-web';
$redirect_uri = 'https://xtremedev.co/oauth-callback.php';
$sso_login_url = "https://auth.xtremedev.co/authorize.php?client_id=$client_id&redirect_uri=" . urlencode($redirect_uri) . "&response_type=code&scope=basic&state=" . bin2hex(random_bytes(8));
?>
<style>
@media (max-width:991.98px) {
    .navbar-hamburger {
        display: block;
        background: none;
        border: none;
        padding: 0.5rem 0.7rem;
        margin: 0 6px;
        font-size: 1.9rem;
        color: #fff;
        z-index: 10010;
        position: absolute;
        top: 10px;
        <?php if($is_rtl): ?>right: 8px;<?php else: ?>left: 8px;<?php endif; ?>
    }
    .navbar .navbar-nav,
    .navbar .navbar-actions,
    .navbar .navbar-brand {
        display: none !important;
    }
    .mobile-nav-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(36,153,250,0.08);
        z-index: 10010;
    }
    .mobile-nav {
        display: flex;
        flex-direction: column;
        background: #fff;
        position: fixed;
        top: 0;
        left: 0;
        right: auto;
        border-radius: 0 0 18px 18px;
        width: 82vw;
        max-width: 340px;
        min-height: 100vh;
        box-shadow: 0 10px 40px #2499fa26;
        z-index: 10012;
        transform: translateX(-110%);
        transition: transform 0.33s cubic-bezier(.51,1.5,.6,1);
        padding: 0 0 1.3rem 0;
    }
    .mobile-nav.open {
        transform: translateX(0);
    }
    .rtl-navbar .mobile-nav {
        left: auto;
        right: 0;
        border-radius: 0 0 18px 18px;
        transform: translateX(110%);
    }
    .rtl-navbar .mobile-nav.open {
        transform: translateX(0);
    }
    .mobile-nav .close-mobile-nav {
        border: none;
        background: none;
        font-size: 2.2rem;
        color: #2499fa;
        padding: 0.8rem 1.4rem 0.5rem 1.4rem;
        text-align: <?php echo $is_rtl?'left':'right' ?>;
        width: 100%;
        cursor: pointer;
    }
    .mobile-nav .mobile-logo {
        text-align: center;
        padding: 0 0 1.1rem 0;
    }
    .mobile-nav .logo-img { height: 42px; width: auto; }
    .mobile-nav .mobile-links {
        list-style: none;
        padding: 0 0.4rem;
        margin: 0;
    }
    .mobile-nav .mobile-links .nav-item {
        margin: 0.6rem 0;
    }
    .mobile-nav .mobile-links .nav-link {
        color: #2499fa;
        font-weight: 700;
        font-size: 1.13rem;
        padding: 0.6rem 1.2rem;
        display: block;
        border-radius: 9px;
        transition: background 0.18s;
        text-align: <?php echo $is_rtl?'right':'left' ?>;
    }
    .mobile-nav .mobile-links .nav-link.active,
    .mobile-nav .mobile-links .nav-link:hover {
        background: #f1f7fd;
        color: #111;
    }
    .mobile-nav .mobile-actions {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        padding: 0.5rem 1.2rem 0 1.2rem;
        gap: 0.7rem;
    }
    .mobile-nav .btn-signin, .mobile-nav .theme-switch {
        width: 100%;
        margin: 0.2rem 0;
    }
    .mobile-nav .dropdown {
        width: 100%;
    }
    .mobile-nav .lang-btn {
        color: #2499fa;
        background: #f3f7fb;
        border: none;
        width: 100%;
        text-align: <?php echo $is_rtl?'right':'left' ?>;
        padding: 0.6rem 0.9rem;
        border-radius: 7px;
        margin-bottom: 0.4rem;
    }
    .mobile-nav .dropdown-menu {
        position: static;
        min-width: 100%;
        box-shadow: none;
        border-radius: 0 0 8px 8px;
        background: #f3f7fb;
        margin: 0.2rem 0 0 0;
        display: none;
    }
    .mobile-nav .dropdown-menu.show { display: block; }
    .mobile-nav .dropdown-item {
        color: #2499fa !important;
        background: none !important;
    }
    .mobile-nav .dropdown-item:hover,
    .mobile-nav .dropdown-item:focus,
    .mobile-nav .dropdown-item.active {
        background: #e5f3fd !important;
        color: #111 !important;
    }
    .mobile-nav .user-info {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        margin-bottom: 0.6rem;
        padding: 0.6rem 0.5rem 0.3rem 0.5rem;
        border-bottom: 1px solid #e6eaf0;
    }
    .mobile-nav .user-avatar {
        width: 37px;
        height: 37px;
        border-radius: 50%;
        object-fit: cover;
    }
    .mobile-nav .user-name {
        font-weight: 700;
        font-size: 1.07rem;
        color: #2499fa;
        margin: 0 0.2rem;
    }
    .mobile-nav .user-actions {
        display: flex;
        flex-direction: column;
        gap: 0.07rem;
        margin-bottom: 0.9rem;
        padding: 0 1.2rem;
    }
    .mobile-nav .user-actions .dropdown-item {
        font-size: 1rem;
        color: #2499fa !important;
        padding-left: 0;
        padding-right: 0;
    }
    .mobile-nav .user-actions .dropdown-item.text-danger {
        color: #e74c3c !important;
    }
    body.mobile-menu-open .mobile-nav-overlay { display: block; }
    body.mobile-menu-open .mobile-nav { display: flex; }
}
/* Dark theme for mobile nav */
body.dark-theme .mobile-nav {
    background: #192434;
}
body.dark-theme .mobile-nav .nav-link {
    color: #fff;
}
body.dark-theme .mobile-nav .nav-link.active,
body.dark-theme .mobile-nav .nav-link:hover {
    background: #26344a;
    color: #ffeb3b;
}
body.dark-theme .mobile-nav .close-mobile-nav {
    color: #4ee3fa;
}
body.dark-theme .mobile-nav .mobile-logo {
    background: #1a2235;
}
body.dark-theme .mobile-nav .lang-btn {
    background: #1a2235;
    color: #4ee3fa;
}
body.dark-theme .mobile-nav .dropdown-menu {
    background: #1a2235;
}
body.dark-theme .mobile-nav .dropdown-item {
    color: #e7f3ff !important;
    background: none !important;
}
body.dark-theme .mobile-nav .dropdown-item:hover,
body.dark-theme .mobile-nav .dropdown-item:focus,
body.dark-theme .mobile-nav .dropdown-item.active {
    background: #26344a !important;
    color: #ffeb3b !important;
}
body.dark-theme .mobile-nav .user-name {
    color: #4ee3fa;
}
body.dark-theme .mobile-nav .user-actions .dropdown-item {
    color: #4ee3fa !important;
}
body.dark-theme .mobile-nav .user-actions .dropdown-item.text-danger {
    color: #ff6a6a !important;
}
@media (min-width:992px) {
    .navbar-hamburger,
    .mobile-nav-overlay,
    .mobile-nav { display: none !important; }
}
</style>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm<?= $is_rtl ? ' rtl-navbar' : '' ?>" id="mainNavbar" style="position:relative;">
    <div class="container">
        <div class="navbar-content w-100 d-flex<?= $is_rtl ? ' flex-row-reverse' : '' ?> justify-content-between align-items-center">
            <?php if($is_rtl): ?>
            <div class="navbar-actions d-flex flex-row-reverse align-items-center">
                <button id="themeSwitch" class="theme-switch ms-2" title="تغییر تم"><?= $lang === 'fa' ? 'تیره' : 'Dark' ?></button>
                <?php if($user): ?>
                    <div class="dropdown user-dropdown ms-2">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <img id="user-avatar" src="<?=htmlspecialchars($user['photo'])?>" alt="Avatar" style="width:36px;height:36px;border-radius:50%;margin-left:7px;">
                            <span id="user-name" style="font-weight:700;color:#fff;"><?= htmlspecialchars($user['name'] ?: $user['email']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="profile.php">پروفایل</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php">خروج</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?=$sso_login_url?>" class="btn btn-light ms-2" style="font-weight:700;">ورود</a>
                <?php endif; ?>
                <div class="dropdown lang-dropdown">
                    <button class="lang-btn" type="button" id="langDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <span id="navbar-lang-text"><?= $langs[$lang] ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="langDropdown">
                        <li><a class="dropdown-item small" href="../shared/inc/language_set.php?lang=fa&redirect=<?=urlencode($_SERVER['REQUEST_URI'])?>">فارسی</a></li>
                        <li><a class="dropdown-item small" href="../shared/inc/language_set.php?lang=en&redirect=<?=urlencode($_SERVER['REQUEST_URI'])?>">English</a></li>
                    </ul>
                </div>
            </div>
            <div class="d-flex flex-row-reverse align-items-center">
                <ul class="navbar-nav align-items-center flex-row flex-row-reverse">
                    <?php foreach(array_reverse($menu_items['fa']) as $item): ?>
                        <li class="nav-item">
                            <a class="nav-link<?=basename($_SERVER['PHP_SELF'])==$item['href']?' active':''?>" href="<?= $item['href'] ?>"><?= $item['title'] ?></a>
                        </li>
                    <?php endforeach; ?>
                    <li>
                        <a class="navbar-brand ms-3" href="index.php">
                            <img class="logo-img" src="../resources/xtreme-company-logo.svg" alt="Logo" style="max-height:40px;" id="navbar-logo">
                        </a>
                    </li>
                </ul>
            </div>
            <?php else: ?>
            <div class="d-flex flex-row align-items-center">
                <a class="navbar-brand me-3" href="index.php">
                    <img class="logo-img" src="../resources/xtreme-company-logo.svg" alt="Logo" style="max-height:40px;" id="navbar-logo">
                </a>
                <ul class="navbar-nav align-items-center flex-row">
                    <?php foreach($menu_items['en'] as $item): ?>
                        <li class="nav-item">
                            <a class="nav-link<?=basename($_SERVER['PHP_SELF'])==$item['href']?' active':''?>" href="<?= $item['href'] ?>"><?= $item['title'] ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="navbar-actions d-flex flex-row align-items-center">
                <div class="dropdown lang-dropdown me-2">
                    <button class="lang-btn" type="button" id="langDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <span id="navbar-lang-text"><?= $langs[$lang] ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="langDropdown">
                        <li><a class="dropdown-item small" href="../shared/inc/language_set.php?lang=en&redirect=<?=urlencode($_SERVER['REQUEST_URI'])?>">English</a></li>
                        <li><a class="dropdown-item small" href="../shared/inc/language_set.php?lang=fa&redirect=<?=urlencode($_SERVER['REQUEST_URI'])?>">فارسی</a></li>
                    </ul>
                </div>
                <?php if($user): ?>
                    <div class="dropdown user-dropdown me-2">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <img id="user-avatar" src="<?=htmlspecialchars($user['photo'])?>" alt="Avatar" style="width:36px;height:36px;border-radius:50%;margin-right:7px;">
                            <span id="user-name" style="font-weight:700;color:#fff;"><?= htmlspecialchars($user['name'] ?: $user['email']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php">Sign out</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?=$sso_login_url?>" class="btn btn-light me-2" style="font-weight:700;">Sign in</a>
                <?php endif; ?>
                <button id="themeSwitch" class="theme-switch" title="Switch theme"><?= $lang === 'fa' ? 'تیره' : 'Dark' ?></button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- دکمه همبرگر مخصوص موبایل -->
    <button class="navbar-hamburger" id="mobileNavBtn" aria-label="باز کردن منو" style="display:none;">
        &#9776;
    </button>
</nav>
<!-- منوی موبایل و overlay -->
<div class="mobile-nav-overlay" id="mobileNavOverlay"></div>
<nav class="mobile-nav<?php if($is_rtl) echo ' rtl-navbar'; ?>" id="mobileNav">
    <button class="close-mobile-nav" id="closeMobileNav" aria-label="بستن">&#10005;</button>
    <div class="mobile-logo">
        <img class="logo-img" src="../resources/xtreme-company-logo.svg" alt="Logo" id="mobile-navbar-logo">
    </div>
    <?php if($user): ?>
    <div class="user-info">
        <img class="user-avatar" src="<?=htmlspecialchars($user['photo'])?>" alt="Avatar">
        <span class="user-name"><?= htmlspecialchars($user['name'] ?: $user['email']); ?></span>
    </div>
    <div class="user-actions">
        <a class="dropdown-item" href="profile.php"><?= $lang=='fa'?'پروفایل':'Profile' ?></a>
        <a class="dropdown-item text-danger" href="logout.php"><?= $lang=='fa'?'خروج':'Sign out' ?></a>
    </div>
    <?php else: ?>
    <div class="user-actions">
        <a href="<?=$sso_login_url?>" class="btn-signin"><?= $lang=='fa'?'ورود':'Sign in' ?></a>
    </div>
    <?php endif; ?>
    <ul class="mobile-links">
        <?php foreach($menu_items[$lang] as $item): ?>
            <li class="nav-item">
                <a class="nav-link<?=basename($_SERVER['PHP_SELF'])==$item['href']?' active':''?>" href="<?= $item['href'] ?>"><?= $item['title'] ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="mobile-actions">
        <div class="dropdown lang-dropdown">
            <button class="lang-btn" type="button" id="mobileLangDropdown">
                <span id="mobile-navbar-lang-text"><?= $langs[$lang] ?></span>
            </button>
            <ul class="dropdown-menu" id="mobileLangMenu">
                <li><a class="dropdown-item small" href="../shared/inc/language_set.php?lang=en&redirect=<?=urlencode($_SERVER['REQUEST_URI'])?>">English</a></li>
                <li><a class="dropdown-item small" href="../shared/inc/language_set.php?lang=fa&redirect=<?=urlencode($_SERVER['REQUEST_URI'])?>">فارسی</a></li>
            </ul>
        </div>
        <button id="mobileThemeSwitch" class="theme-switch" title="<?= $lang=='fa'?'تغییر تم':'Switch theme' ?>"><?= $lang=='fa'?'تیره':'Dark' ?></button>
    </div>
</nav>
<div class="nav-placeholder"></div>
<script>
(function(){
    // گرفتن لوگو و سایر تنظیمات از API
    var lang = "<?=htmlspecialchars($lang)?>";
    var project_id = <?=intval($project_id)?>;
    var logoEl = document.getElementById('navbar-logo');
    var mobileLogo = document.getElementById('mobile-navbar-logo');
    var navbarLangText = document.getElementById('navbar-lang-text');
    var mobileLangText = document.getElementById('mobile-navbar-lang-text');
    var langsDict = {'fa':'فارسی','en':'English'};
    fetch("https://api.xtremedev.co/endpoints/settings.php?project_id=" + project_id + "&lang=" + encodeURIComponent(lang))
        .then(function(res){
            if(!res.ok) throw new Error('API error');
            return res.json();
        })
        .then(function(data) {
            if(data.logo_url && logoEl) logoEl.src = data.logo_url;
            if(data.logo_url && mobileLogo) mobileLogo.src = data.logo_url;
        }).catch(function(){});
    if(navbarLangText && langsDict[lang]) navbarLangText.textContent = langsDict[lang];
    if(mobileLangText && langsDict[lang]) mobileLangText.textContent = langsDict[lang];

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
        body.classList.add('mobile-menu-open');
    }
    function closeMobileNav(){
        mobileNav.classList.remove('open');
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
    // پشتیبانی از دکمه تم برای موبایل
    var themeSwitch = document.getElementById('themeSwitch');
    var mobileThemeSwitch = document.getElementById('mobileThemeSwitch');
    if(themeSwitch && mobileThemeSwitch){
        mobileThemeSwitch.addEventListener('click', function(){
            themeSwitch.click();
        });
    }
})();
</script>