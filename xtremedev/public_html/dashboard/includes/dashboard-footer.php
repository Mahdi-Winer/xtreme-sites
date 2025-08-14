<?php
require_once __DIR__ . '/../../shared/inc/config.php';

// زبان فعال و راست‌به‌چپ
$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$lang = (defined('ALLOWED_LANGS') && is_array(ALLOWED_LANGS) && in_array($lang, ALLOWED_LANGS)) ? $lang : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$is_rtl = ($lang === 'fa');
$project_id = 1;

// بارگذاری ترجمه‌ها
$translations = [];
$lang_file = __DIR__ . '/../shared/assets/languages/' . $lang . '.json';
if (file_exists($lang_file)) {
    $json = file_get_contents($lang_file);
    $translations = json_decode($json, true);
}

// لیست زبان‌ها از ALLOWED_LANGS و ترجمه
$langs = [];
if (defined('ALLOWED_LANGS') && is_array(ALLOWED_LANGS)) {
    foreach (ALLOWED_LANGS as $code) {
        $display = $translations["language_$code"] ?? strtoupper($code);
        $flag = match($code) {
            'fa' => '🇮🇷',
            'en' => '🇬🇧',
            'ar' => '🇸🇦',
            'tr' => '🇹🇷',
            'de' => '🇩🇪',
            'fr' => '🇫🇷',
            'es' => '🇪🇸',
            'ru' => '🇷🇺',
            default => ''
        };
        $langs[$code] = [
            'text' => $display,
            'flag' => $flag
        ];
    }
}
?>
<footer class="footer-main mt-5" style="direction:<?=$is_rtl?'rtl':'ltr'?>;">
  <div class="container">
    <div class="footer-info" style="display:flex;flex-wrap:wrap;gap:2rem;align-items:flex-start;justify-content:space-between;">
      <div class="footer-block" style="flex:2 1 240px;">
        <div>
          <b id="footer-email-label"><?= $translations['footer_email'] ?? 'Email:' ?></b>
          <span id="footer-email">info@gamestudio.com</span>
        </div>
      </div>
      <div class="footer-block footer-social" style="flex:1 1 180px;">
        <div id="footer-socials-label" style="font-weight:600;margin-bottom:0.3rem;">
          <?= $translations['footer_socials'] ?? 'Socials' ?>
        </div>
        <div id="footer-social-links" style="display:flex;flex-direction:column;gap:4px;">
          <a href="#" title="Telegram" id="footer-telegram" target="_blank"><?= $translations['footer_telegram'] ?? 'Telegram' ?></a>
          <a href="#" title="Instagram" id="footer-instagram" target="_blank"><?= $translations['footer_instagram'] ?? 'Instagram' ?></a>
          <a href="#" title="LinkedIn" id="footer-linkedin" target="_blank"><?= $translations['footer_linkedin'] ?? 'LinkedIn' ?></a>
        </div>
      </div>
      <div class="footer-block footer-enamad" style="flex:1 1 130px;">
        <div id="footer-trust-label" style="font-weight:600;">
          <?= $translations['footer_trust'] ?? 'Our Trust Badges' ?>
        </div>
      </div>
      <div class="footer-block footer-langs" style="flex:1 1 110px;margin-top:1.3em;">
        <div style="font-weight:600; margin-bottom:0.4em;">
          <?= $translations['footer_languages'] ?? 'Languages' ?>
        </div>
        <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:4px;">
          <?php foreach($langs as $code=>$info): ?>
            <li>
              <a href="<?=INC_BASE?>/language_set.php?lang=<?=$code?>&redirect=<?=urlencode($_SERVER['REQUEST_URI'])?>"
                 style="color:#2499fa; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                <?= $info['flag'] ?> <?= $info['text'] ?><?= $lang==$code ? ' <span style="color:#e13a3a;font-size:1.05em;">●</span>' : '' ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <div class="footer-copyright" style="margin-top:2rem;text-align:center;">
      <span id="footer-copyright"></span>
    </div>
  </div>
</footer>
<script>
(function(){
  var lang = <?=json_encode($lang)?>;
  var project_id = <?=intval($project_id)?>;
  var t = window.PAGE_TRANSLATIONS || {};
  fetch("https://api.xtremedev.co/endpoints/settings.php?project_id=" + project_id + "&lang=" + encodeURIComponent(lang))
    .then(function(res){ return res.json(); })
    .then(function(data){
      document.getElementById('footer-email').textContent = data.email || "info@xtremedev.co";
      // شبکه‌های اجتماعی
      var socials = data.socials || {};
      document.getElementById('footer-telegram').href = socials.telegram || "#";
      document.getElementById('footer-instagram').href = socials.instagram || "#";
      document.getElementById('footer-linkedin').href = socials.linkedin || "#";
      // عناوین لینک‌ها را از ترجمه (ترجیحاً window.PAGE_TRANSLATIONS)
      if(t['footer_telegram']) document.getElementById('footer-telegram').textContent = t['footer_telegram'];
      if(t['footer_instagram']) document.getElementById('footer-instagram').textContent = t['footer_instagram'];
      if(t['footer_linkedin']) document.getElementById('footer-linkedin').textContent = t['footer_linkedin'];
      // عنوان سایت
      var siteTitle = data.site_title || "Xtreme Development";
      // Copyright
      document.getElementById('footer-copyright').innerHTML =
        (lang==='fa'
          ? (t['footer_copyright']||'کلیه حقوق برای') + " <span style='font-weight:700;'>" + siteTitle + "</span> &copy; " + (new Date().getFullYear()) + " | " + (t['footer_copyright2']||'محفوظ است')
          : (t['footer_copyright']||'All rights reserved for') + " <span style='font-weight:700;'>" + siteTitle + "</span> &copy; " + (new Date().getFullYear()) + " | " + (t['footer_copyright2']||'')
        );
      // لیبل‌ها
      if(t['footer_email']) document.getElementById('footer-email-label').textContent = t['footer_email'];
      if(t['footer_socials']) document.getElementById('footer-socials-label').textContent = t['footer_socials'];
      if(t['footer_trust']) document.getElementById('footer-trust-label').textContent = t['footer_trust'];
    });
})();
</script>