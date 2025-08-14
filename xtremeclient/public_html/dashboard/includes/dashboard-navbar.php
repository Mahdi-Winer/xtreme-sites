<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../shared/inc/config.php';

// زبان و راست‌به‌چپ
$lang = $_COOKIE['site_lang'] ?? (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'en');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : (in_array($lang, ['fa', 'en']) ? $lang : 'en');
$is_rtl = ($lang === 'fa');
$project_id = 1;

// ترجمه از فایل json
$translations = [];
$lang_file = __DIR__ . '/../../shared/assets/languages/' . $lang . '.json';
if (file_exists($lang_file)) {
    $json = file_get_contents($lang_file);
    $translations = json_decode($json, true);
}

// لیست زبان‌ها با پرچم
$langs = [
    'en' => [
        'flag' => '🇬🇧',
        'text' => $translations['language_en'] ?? ''
    ],
    'fa' => [
        'flag' => '🇮🇷',
        'text' => $translations['language_fa'] ?? ''
    ]
];

// آرایه منوها با ساب‌منو
$menu_items = [
    ['href' => 'index.php',         'key' => 'dashboard'],
    ['href' => 'shop.php',          'key' => 'shop'],
    ['href' => 'my-products.php',   'key' => 'my_products'],
    ['href' => 'orders.php',        'key' => 'my_orders'],
    ['href' => 'invoices.php',      'key' => 'invoices'],
    ['href' => 'tickets.php',       'key' => 'support'],
    [
        'key' => 'client',
        'submenu' => [
            ['href' => 'client-mods.php',            'key' => 'client_mods'],
            ['href' => 'client-appearance.php',      'key' => 'client_appearance'],
            ['href' => 'client-settings.php',        'key' => 'client_general_settings'],
        ]
    ]
];

// کاربر از سشن SSO
$user = null;
if (isset($_SESSION['user_profile'])) {
    $profile = $_SESSION['user_profile'];
    $user = [
        'name'  => $profile['fullname'] ?? '',
        'email' => $profile['email'] ?? '',
        'photo' => !empty($profile['photo']) ? $profile['photo'] : "http://dl.xtremedev.co/company-resourse/default-pfp.png"
    ];
}

// SSO
$client_id = 'xtremedev-web';
$redirect_uri = 'https://xtremedev.co/oauth-callback.php';
$sso_login_url = "https://auth.xtremedev.co/authorize.php?client_id=$client_id&redirect_uri=" . urlencode($redirect_uri) . "&response_type=code&scope=basic&state=" . bin2hex(random_bytes(8));
?>
<?php include __DIR__ . '/dashboard-styles.php'; ?>
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
/* فقط منوی زبان همیشه لایت */
.lang-dropdown .dropdown-menu, 
.lang-dropdown .dropdown-menu.show {
    background: #f3f7fb !important;
    color: #2499fa !important;
    border: none;
    box-shadow: 0 6px 32px #111a2a22 !important;
}
.lang-dropdown .dropdown-item {
    color: #2499fa !important;
    background: transparent !important;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 0.5em;
}
.lang-dropdown .dropdown-item.active,
.lang-dropdown .dropdown-item:active,
.lang-dropdown .dropdown-item:focus,
.lang-dropdown .dropdown-item:hover {
    background: #e5f3fd !important;
    color: #111 !important;
}
/* حتی در دارک تم هم منوی زبان لایت بماند */
body.dark-theme .lang-dropdown .dropdown-menu {
    background: #f3f7fb !important;
    color: #2499fa !important;
}
body.dark-theme .lang-dropdown .dropdown-item {
    color: #2499fa !important;
}
body.dark-theme .lang-dropdown .dropdown-item.active,
body.dark-theme .lang-dropdown .dropdown-item:active,
body.dark-theme .lang-dropdown .dropdown-item:focus,
body.dark-theme .lang-dropdown .dropdown-item:hover {
    background: #e5f3fd !important;
    color: #111 !important;
}
/* بقیه ساب منوها در دارک تم تیره */
body.dark-theme .dropdown-menu,
body.dark-theme .navbar .dropdown-menu,
body.dark-theme .mobile-nav .dropdown-menu {
    background: #232e44 !important;
    color: #e6e9f2 !important;
    border: none;
    box-shadow: 0 6px 32px #111a2a44 !important;
}
body.dark-theme .dropdown-menu .dropdown-item,
body.dark-theme .navbar .dropdown-menu .dropdown-item,
body.dark-theme .mobile-nav .dropdown-menu .dropdown-item {
    background: transparent !important;
    color: #e6e9f2 !important;
    transition: background 0.18s, color 0.18s;
}
body.dark-theme .dropdown-menu .dropdown-item.active,
body.dark-theme .dropdown-menu .dropdown-item:active,
body.dark-theme .dropdown-menu .dropdown-item:focus,
body.dark-theme .dropdown-menu .dropdown-item:hover {
    background: #2d3b5c !important;
    color: #ffeb3b !important;
}
/* ... بقیه استایل‌ها همان قبلی ... */
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
                <button id="themeSwitch" class="theme-switch ms-2" title="<?= $translations['dark'] ?? '' ?>">
                    <?= $translations['dark'] ?? '' ?>
                </button>
                <?php if($user): ?>
                    <div class="dropdown user-dropdown ms-2">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <img id="user-avatar" src="<?=htmlspecialchars($user['photo'])?>" alt="Avatar" style="width:36px;height:36px;border-radius:50%;margin-left:7px;">
                            <span id="user-name" style="font-weight:700;color:#fff;"><?= htmlspecialchars($user['name'] ?: $user['email']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="profile.php"><?= $translations['profile'] ?? '' ?></a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><?= $translations['signout'] ?? '' ?></a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?=$sso_login_url?>" class="btn btn-light ms-2" style="font-weight:700;"><?= $translations['signin'] ?? '' ?></a>
                <?php endif; ?>
                <div class="dropdown lang-dropdown">
                    <button class="lang-btn" type="button" id="langDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <span id="navbar-lang-text">
                            <?= $langs[$lang]['flag'].' '.$langs[$lang]['text'] ?>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="langDropdown">
                        <?php foreach ($langs as $code => $info): ?>
                            <li>
                                <a class="dropdown-item small" href="../shared/inc/language_set.php?lang=<?= $code ?>&redirect=<?=urlencode($_SERVER['REQUEST_URI'])?>">
                                    <?= $info['flag'].' '.$info['text'] ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <div class="d-flex flex-row-reverse align-items-center">
                <ul class="navbar-nav align-items-center flex-row flex-row-reverse">
                    <?php
                    $menu_items_rtl = array_reverse($menu_items);
                    foreach($menu_items_rtl as $item):
                        if(isset($item['submenu'])): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle<?= (in_array(basename($_SERVER['PHP_SELF']), array_map(function($s){return $s['href'];}, $item['submenu']))) ? ' active' : '' ?>"
                                   href="#" id="clientDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <?= $translations[$item['key']] ?? '' ?>
                                </a>
                                <ul class="dropdown-menu<?= $is_rtl ? ' dropdown-menu-end' : '' ?>" aria-labelledby="clientDropdown">
                                    <?php foreach($item['submenu'] as $sub): ?>
                                        <li><a class="dropdown-item<?= (basename($_SERVER['PHP_SELF']) == $sub['href'])?' active':'' ?>" href="<?= $sub['href'] ?>"><?= $translations[$sub['key']] ?? '' ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                        <?php
                        else: ?>
                            <li class="nav-item">
                                <a class="nav-link<?=basename($_SERVER['PHP_SELF'])==$item['href']?' active':''?>" href="<?= $item['href'] ?>">
                                    <?= $translations[$item['key']] ?? '' ?>
                                </a>
                            </li>
                        <?php endif;
                    endforeach; ?>
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
                    <?php foreach($menu_items as $item): ?>
                        <?php if(isset($item['submenu'])): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle<?= (in_array(basename($_SERVER['PHP_SELF']), array_map(function($s){return $s['href'];}, $item['submenu']))) ? ' active' : '' ?>"
                                   href="#" id="clientDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <?= $translations[$item['key']] ?? '' ?>
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="clientDropdown">
                                    <?php foreach($item['submenu'] as $sub): ?>
                                        <li><a class="dropdown-item<?= (basename($_SERVER['PHP_SELF']) == $sub['href'])?' active':'' ?>" href="<?= $sub['href'] ?>"><?= $translations[$sub['key']] ?? '' ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link<?=basename($_SERVER['PHP_SELF'])==$item['href']?' active':''?>" href="<?= $item['href'] ?>">
                                    <?= $translations[$item['key']] ?? '' ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="navbar-actions d-flex flex-row align-items-center">
                <div class="dropdown lang-dropdown me-2">
                    <button class="lang-btn" type="button" id="langDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <span id="navbar-lang-text">
                            <?= $langs[$lang]['flag'].' '.$langs[$lang]['text'] ?>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="langDropdown">
                        <?php foreach ($langs as $code => $info): ?>
                            <li>
                                <a class="dropdown-item small" href="../shared/inc/language_set.php?lang=<?= $code ?>&redirect=<?=urlencode($_SERVER['REQUEST_URI'])?>">
                                    <?= $info['flag'].' '.$info['text'] ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php if($user): ?>
                    <div class="dropdown user-dropdown me-2">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <img id="user-avatar" src="<?=htmlspecialchars($user['photo'])?>" alt="Avatar" style="width:36px;height:36px;border-radius:50%;margin-right:7px;">
                            <span id="user-name" style="font-weight:700;color:#fff;"><?= htmlspecialchars($user['name'] ?: $user['email']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="profile.php"><?= $translations['profile'] ?? '' ?></a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><?= $translations['signout'] ?? '' ?></a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?=$sso_login_url?>" class="btn btn-light me-2" style="font-weight:700;"><?= $translations['signin'] ?? '' ?></a>
                <?php endif; ?>
                <button id="themeSwitch" class="theme-switch" title="<?= $translations['dark'] ?? '' ?>"><?= $translations['dark'] ?? '' ?></button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <button class="navbar-hamburger" id="mobileNavBtn" aria-label="<?= $translations['more'] ?? '' ?>" style="display:none;">
        &#9776;
    </button>
</nav>
<!-- منوی موبایل و overlay -->
<div class="mobile-nav-overlay" id="mobileNavOverlay"></div>
<nav class="mobile-nav<?php if($is_rtl) echo ' rtl-navbar'; ?>" id="mobileNav">
    <button class="close-mobile-nav" id="closeMobileNav" aria-label="<?= $translations['close'] ?? '' ?>">&#10005;</button>
    <div class="mobile-logo">
        <img class="logo-img" src="../resources/xtreme-company-logo.svg" alt="Logo" id="mobile-navbar-logo">
    </div>
    <?php if($user): ?>
    <div class="user-info">
        <img class="user-avatar" src="<?=htmlspecialchars($user['photo'])?>" alt="Avatar">
        <span class="user-name"><?= htmlspecialchars($user['name'] ?: $user['email']); ?></span>
    </div>
    <div class="user-actions">
        <a class="dropdown-item" href="profile.php"><?= $translations['profile'] ?? '' ?></a>
        <a class="dropdown-item text-danger" href="logout.php"><?= $translations['signout'] ?? '' ?></a>
    </div>
    <?php else: ?>
    <div class="user-actions">
        <a href="<?=$sso_login_url?>" class="btn-signin"><?= $translations['signin'] ?? '' ?></a>
    </div>
    <?php endif; ?>
    <ul class="mobile-links">
        <?php foreach($menu_items as $item): ?>
            <?php if(isset($item['submenu'])): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle<?= (in_array(basename($_SERVER['PHP_SELF']), array_map(function($s){return $s['href'];}, $item['submenu']))) ? ' active' : '' ?>"
                       href="#" id="mobileClientDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <?= $translations[$item['key']] ?? '' ?>
                    </a>
                    <ul class="dropdown-menu<?= $is_rtl ? ' dropdown-menu-end' : '' ?>" aria-labelledby="mobileClientDropdown">
                        <?php foreach($item['submenu'] as $sub): ?>
                            <li><a class="dropdown-item<?= (basename($_SERVER['PHP_SELF']) == $sub['href'])?' active':'' ?>" href="<?= $sub['href'] ?>"><?= $translations[$sub['key']] ?? '' ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link<?=basename($_SERVER['PHP_SELF'])==$item['href']?' active':''?>" href="<?= $item['href'] ?>">
                        <?= $translations[$item['key']] ?? '' ?>
                    </a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
    <div class="mobile-actions">
        <div class="dropdown lang-dropdown">
            <button class="lang-btn" type="button" id="mobileLangDropdown">
                <span id="mobile-navbar-lang-text">
                    <?= $langs[$lang]['flag'].' '.$langs[$lang]['text'] ?>
                </span>
            </button>
            <ul class="dropdown-menu" id="mobileLangMenu">
                <?php foreach ($langs as $code => $info): ?>
                    <li>
                        <a class="dropdown-item small" href="../shared/inc/language_set.php?lang=<?= $code ?>&redirect=<?=urlencode($_SERVER['REQUEST_URI'])?>">
                            <?= $info['flag'].' '.$info['text'] ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <button id="mobileThemeSwitch" class="theme-switch" title="<?= $translations['dark'] ?? '' ?>"><?= $translations['dark'] ?? '' ?></button>
    </div>
</nav>
<div class="nav-placeholder"></div>
<script>
(function(){
    var lang = "<?=htmlspecialchars($lang)?>";
    var project_id = <?=intval($project_id)?>;
    var logoEl = document.getElementById('navbar-logo');
    var mobileLogo = document.getElementById('mobile-navbar-logo');
    var navbarLangText = document.getElementById('navbar-lang-text');
    var mobileLangText = document.getElementById('mobile-navbar-lang-text');
    var langsDict = <?=json_encode($langs, JSON_UNESCAPED_UNICODE)?>;
    fetch("https://api.xtremedev.co/endpoints/settings.php?project_id=" + project_id + "&lang=" + encodeURIComponent(lang))
        .then(function(res){
            if(!res.ok) throw new Error('API error');
            return res.json();
        })
        .then(function(data) {
            if(data.logo_url && logoEl) logoEl.src = data.logo_url;
            if(data.logo_url && mobileLogo) mobileLogo.src = data.logo_url;
        }).catch(function(){});
    if(navbarLangText && langsDict[lang]) navbarLangText.innerHTML = langsDict[lang].flag + ' ' + langsDict[lang].text;
    if(mobileLangText && langsDict[lang]) mobileLangText.innerHTML = langsDict[lang].flag + ' ' + langsDict[lang].text;

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