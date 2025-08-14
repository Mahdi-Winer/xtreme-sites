<style>
:root {
  --primary: #2499fa;
  --primary-a: rgba(36,153,250,0.97);
  --surface: #f4f7fa;
  --surface-alt: #fff;
  --text: #222;
  --shadow: #2499fa22;
  --shadow-card: #2499fa14;
  --shadow-hover: #2499fa33;
  --border: #2499fa18;
  --border-hover: #2499fa44;
  --footer-bg: #2499fa;
  --footer-text: #f4f7fa;
  --footer-text-strong: #fff;
  --footer-border: #dbe6f7;
}
.dark-theme {
  --surface: #181f2a;
  --surface-alt: #202b3b;
  --text: #e6e9f2;
  --shadow: #15203244;
  --shadow-card: #15203222;
  --shadow-hover: #2499fa44;
  --border: #2499fa28;
  --border-hover: #2499fa66;
  --footer-bg: #101722;
  --footer-text: #e9ecf3;
  --footer-text-strong: #fff;
  --footer-border: #37425b;
}
body {
  font-family: 'Vazirmatn', Tahoma, Arial, sans-serif;
  background: var(--surface);
  color: var(--text);
  transition: background 0.4s, color 0.4s;
}
.navbar {
  background: var(--primary-a) !important;
  box-shadow: 0 2px 16px var(--shadow);
  min-height: 74px;
  padding-top: 10px;
  padding-bottom: 10px;
  position: static;
  transition: min-height 0.3s, padding 0.3s, background 0.3s, box-shadow 0.3s, position 0s;
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
  margin-right: 1.2rem;
  padding: 0;
  justify-content: center;
  width: 230px;
}
.logo-img {
  height: 54px;
  width: auto;
  max-width: 256px;
  object-fit: contain;
  display: block;
  transition: height 0.3s, width 0.3s;
}
.theme-switch {
  background: var(--primary) !important;
  color: #fff !important;
  border: 0;
  border-radius: 16px;
  min-width: 72px;
  min-height: 38px;
  font-size: 1rem;
  font-weight: 600;
  box-shadow: none !important;
  cursor: pointer;
  padding: 2px 18px;
  transition: background 0.2s;
  outline: none;
  user-select: none;
  text-align: center;
  letter-spacing: 0.2px;
  margin-left: 1.1rem;
  display: inline-block;
  vertical-align: middle;
}
.theme-switch:active { transform: scale(0.98); }
.navbar-nav .nav-link {
  padding-top: 0.85rem;
  padding-bottom: 0.85rem;
  font-size: 1.12rem;
}

/* =================== FOOTER =================== */
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
  width: 100%;
  margin-top: auto;
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
@media (max-width: 991px) {
  .footer-info {
    flex-direction: column;
    align-items: center;
    gap: 1.2rem;
  }
  .footer-info .footer-block { min-width: 0; }
}
@media (max-width: 540px) {
  .footer-main { padding: 1.1rem 0 0.8rem 0; }
  .footer-info { gap: 0.9rem; }
}
/* =================== END FOOTER =================== */

@media (max-width: 991.98px) {
  .logo-img { height: 38px !important; max-width: 120px;}
  .navbar-brand { width: 130px; }
  .theme-switch { min-width: 60px; min-height: 30px; font-size: 0.97rem; padding: 1px 11px; margin-left: .7rem;}
  .navbar-nav .nav-link { font-size: 1.01rem;}
}
@media (max-width: 767.98px) {
  .logo-img { height: 28px !important; max-width: 80px;}
  .navbar-brand { width: 80px; }
  .theme-switch { min-width: 50px; min-height: 26px; font-size: 0.92rem; padding: 1px 7px; margin-left: 0.5rem;}
}
</style>