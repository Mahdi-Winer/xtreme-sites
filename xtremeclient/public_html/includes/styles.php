<style>
    :root {
        --primary: #2499fa;
        --primary-a: rgba(36,153,250,0.97);
        --footer-bg: #2499fa;
        --footer-text: #f4f7fa;
        --footer-text-strong: #fff;
        --footer-border: #dbe6f7;
        --shadow: #2499fa22;
        --shadow-hover: #2499fa33;
        --shadow-card: #2499fa14;
    }
    .dark-theme {
        --footer-bg: #101722;
        --footer-text: #e9ecf3;
        --footer-text-strong: #fff;
        --footer-border: #37425b;
        --shadow: #15203244;
        --shadow-hover: #2499fa44;
        --shadow-card: #15203222;
    }
    body {
        background: var(--surface, #f4f7fa);
        color: var(--text, #222);
        transition: background 0.3s, color 0.3s;
    }

    /* ==== Navbar ==== */
    .navbar {
        background: var(--primary-a) !important;
        box-shadow: 0 2px 16px var(--shadow);
        min-height: 74px;
        padding-top: 10px;
        padding-bottom: 10px;
        z-index: 900;
    }
    .navbar.sticky-navbar {
        position: fixed !important;
        top: 0;
        left: 0;
        right: 0;
        background: var(--primary) !important;
        box-shadow: 0 4px 24px var(--shadow-hover);
        min-height: 62px;
        padding-top: 3px;
        padding-bottom: 3px;
        animation: sticky-fade-in 0.37s;
    }
    @keyframes sticky-fade-in {
        from { transform: translateY(-100%);}
        to   { transform: translateY(0);}
    }
    .nav-placeholder { display: none; }
    .navbar.sticky-navbar + .nav-placeholder {
        display: block;
        height: 62px;
    }

    .navbar-brand {
        display: flex;
        align-items: center;
        padding: 0;
        justify-content: center;
        width: 180px;
        margin: 0;
    }
    .logo-img {
        height: 44px;
        width: auto;
        max-width: 160px;
        object-fit: contain;
        display: block;
        transition: height 0.3s, width 0.3s;
    }
    /* جدید: والد navbar اصلی برای کنترل ترتیب منو و اکشن‌ها */
    .navbar-content {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        width: 100%;
    }

    /* RTL Navbar */
    .rtl-navbar {
        direction: rtl !important;
    }
    .rtl-navbar .navbar-content {
        flex-direction: row-reverse !important;
    }
    .rtl-navbar .navbar-actions {
        flex-direction: row-reverse !important;
    }
    .rtl-navbar .navbar-nav {
        flex-direction: row-reverse !important;
    }
    .rtl-navbar .navbar-brand {
        margin-left: 0 !important;
        margin-right: 1.2rem !important;
    }
    .rtl-navbar .navbar-nav .nav-link {
        padding-left: 0.8rem;
        padding-right: 0.2rem;
    }
    .rtl-navbar .dropdown-menu {
        text-align: right;
        right: 0;
        left: auto;
    }

    /* LTR Navbar */
    .navbar-content {
        flex-direction: row;
    }
    .navbar-actions {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 0.32em;
        margin-inline-start: 0.7em;
    }
    .navbar-nav {
        flex-direction: row !important;
        gap: 0.2em;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }
    .navbar-nav .nav-item {
        margin: 0 0.1em;
    }
    .navbar-nav .nav-link {
        padding-top: 0.85rem;
        padding-bottom: 0.85rem;
        font-size: 1.09rem;
        color: #fff;
        opacity: 0.94;
        transition: opacity 0.12s;
    }
    .navbar-nav .nav-link.active,
    .navbar-nav .nav-link:focus,
    .navbar-nav .nav-link:hover {
        opacity: 1;
        color: #fff;
        font-weight: bold;
    }

    /* دکمه زبان ساده و جمع و جور */
    .lang-dropdown .lang-btn {
        border: none !important;
        background: transparent !important;
        color: #fff !important;
        font-weight: 600;
        font-size: 1.04em;
        padding: 4px 10px 4px 6px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 0.29em;
        box-shadow: none !important;
        outline: none !important;
        transition: background 0.19s;
    }
    .lang-dropdown .lang-btn:after {
        content: '';
        display: inline-block;
        border: solid #fff;
        border-width: 0 2px 2px 0;
        padding: 2.5px;
        margin-right: 5px;
        margin-left: 1px;
        transform: rotate(45deg);
        vertical-align: middle;
        transition: border-color 0.2s;
    }
    .rtl-navbar .lang-dropdown .lang-btn:after {
        margin-left: 5px;
        margin-right: 1px;
    }
    .lang-dropdown .lang-btn:focus,
    .lang-dropdown .lang-btn:hover {
        background: rgba(255,255,255,0.07) !important;
        color: #fff !important;
    }
    .lang-dropdown .dropdown-menu {
        min-width: 90px;
        padding: 0.3rem 0.3rem;
        font-size: 0.97rem;
        border-radius: 10px;
        background: #222;
        color: #fff;
        border: none;
        margin-top: 5px !important;
        box-shadow: 0 2px 16px #0003;
    }
    .lang-dropdown .dropdown-item {
        color: #fff;
        padding: 0.25rem 0.9rem;
        font-size: 0.97rem;
        border-radius: 7px;
        transition: background 0.15s, color 0.15s;
    }
    .lang-dropdown .dropdown-item:hover,
    .lang-dropdown .dropdown-item:focus {
        background: var(--primary);
        color: #fff;
    }
    .lang-dropdown .dropdown-item.active {
        background: #1763a5;
        color: #fff;
    }

    /* دکمه تم */
    .theme-switch {
        background: var(--primary) !important;
        color: #fff !important;
        border: 0;
        border-radius: 12px;
        min-width: 52px;
        min-height: 38px;
        font-size: 1em;
        font-weight: 600;
        box-shadow: none !important;
        cursor: pointer;
        padding: 4px 18px;
        transition: background 0.2s;
        outline: none;
        user-select: none;
        text-align: center;
        letter-spacing: 0.2px;
        display: inline-block;
        vertical-align: middle;
    }
    .theme-switch:active { transform: scale(0.98); }

    /* دکمه ورود */
    .btn-signin {
        background: var(--primary);
        color: #fff !important;
        font-weight: 800;
        border-radius: 10px;
        font-size: 1.07rem;
        padding: 7px 24px;
        transition: background 0.15s, box-shadow 0.18s;
        border: none;
        box-shadow: 0 1px 6px var(--shadow-card);
        letter-spacing: 0.2px;
        margin-inline-start: 0.6em;
        margin-inline-end: 0;
    }
    .btn-signin:hover, .btn-signin:focus {
        background: #155fa0;
        color: #fff !important;
        box-shadow: 0 2px 18px var(--shadow-hover);
        text-decoration: none;
    }
    .dark-theme .btn-signin {
        background: #38a8ff;
        color: #fff !important;
    }
    .dark-theme .btn-signin:hover, .dark-theme .btn-signin:focus {
        background: #2499fa;
        color: #fff !important;
    }

    /* آواتار پروفایل */
    .user-dropdown img {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
        margin-inline-end: 8px;
        margin-inline-start: 0;
    }

    /* ===== Footer ===== */
    .footer-main {
        background: var(--footer-bg);
        color: var(--footer-text);
        text-align: center;
        padding: 2.2rem 0 1.2rem 0;
        border-radius: 36px 36px 0 0;
        font-size: 1.02rem;
        letter-spacing: 1px;
        transition: background 0.4s, color 0.4s;
        position:relative;
    }
    .footer-info {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        align-items: flex-start;
        gap: 2.2rem;
        padding-bottom: 1.2rem;
        border-bottom: 1px solid var(--footer-border);
        margin-bottom: 1rem;
    }
    .footer-info .footer-block { min-width: 180px; }
    .footer-logo { margin-bottom: 0.7rem; }
    .footer-logo img {
        max-width: 58px;
        margin-bottom: 0.6rem;
    }
    .footer-social a {
        color: var(--footer-text-strong);
        font-size: 1.02rem;
        margin: 0 0.5rem;
        transition: color 0.2s;
        text-decoration: none;
        opacity: 0.90;
    }
    .footer-social a:hover { color: #fff; opacity: 1;}
    .footer-enamad img {
        max-width: 70px;
        background: #fff;
        border-radius: 6px;
        display: inline-block;
        margin-top: 0.5rem;
        box-shadow: 0 2px 10px #fff8;
    }
    .footer-copyright {
        background: rgba(0,0,0,0.10);
        color: var(--footer-text);
        border-radius: 0 0 36px 36px;
        padding: 0.6rem 0 0.7rem 0;
        font-size: 0.97rem;
        letter-spacing: 0.7px;
        margin-top: 0.2rem;
    }
    .footer-main b,
    .footer-copyright span[style*="font-weight:700"] {
        color: var(--footer-text-strong) !important;
    }
    @media (max-width: 991.98px) {
        .logo-img { height: 32px !important; max-width: 90px;}
        .navbar-brand { width: 90px; }
        .theme-switch { min-width: 45px; min-height: 27px; font-size: 0.97rem; padding: 1px 11px;}
        .navbar-nav .nav-link { font-size: 0.97rem;}
        .footer-info {
            flex-direction: column;
            align-items: center;
            gap: 1.2rem;
        }
        .footer-info .footer-block { min-width: 0; }
        .navbar-actions { gap: 0.23em; }
        .navbar-content { flex-direction: column !important; align-items: stretch;}
        .rtl-navbar .navbar-content { flex-direction: column !important; }
    }
    @media (max-width: 767.98px) {
        .logo-img { height: 22px !important; max-width: 50px;}
        .navbar-brand { width: 50px; }
        .theme-switch { min-width: 35px; min-height: 19px; font-size: 0.92rem; padding: 1px 7px;}
    }
    @media (max-width: 540px) {
        .footer-main { padding: 1.1rem 0 0.8rem 0; }
        .footer-info { gap: 0.9rem; }
    }
</style>