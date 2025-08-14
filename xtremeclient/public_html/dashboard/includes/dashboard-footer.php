<?php
$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : 'fa';
$lang = in_array($lang, ['fa','en']) ? $lang : 'fa';
$is_rtl = ($lang === 'fa');
$project_id = 1;

// بارگذاری ترجمه از فایل json
$translations = [];
$lang_file = __DIR__ . '/../../shared/assets/languages/' . $lang . '.json';
if (file_exists($lang_file)) {
    $json = file_get_contents($lang_file);
    $translations = json_decode($json, true);
}
?>
<footer class="footer-main mt-5" style="direction:<?=$is_rtl?'rtl':'ltr'?>;">
  <div class="container">
    <div class="footer-info" style="display:flex;flex-wrap:wrap;gap:2rem;align-items:flex-start;justify-content:space-between;">
      <div class="footer-block" style="flex:2 1 240px;">
        <div><b id="footer-email-label"></b> <span id="footer-email"></span></div>
      </div>
      <div class="footer-block footer-social" style="flex:1 1 180px;">
        <div id="footer-socials-label" style="font-weight:600;margin-bottom:0.3rem;"></div>
        <div id="footer-social-links" style="display:flex;flex-direction:column;gap:4px;">
          <a href="#" id="footer-telegram" target="_blank"></a>
          <a href="#" id="footer-instagram" target="_blank"></a>
          <a href="#" id="footer-linkedin" target="_blank"></a>
        </div>
      </div>
      <div class="footer-block footer-enamad" style="flex:1 1 130px;">
        <div id="footer-trust-label" style="font-weight:600;"></div>
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
  var t = <?=json_encode($translations, JSON_UNESCAPED_UNICODE)?>;

  // مقداردهی اولیه لیبل‌ها فقط از ترجمه، اگر نبود خالی
  document.getElementById('footer-email-label').textContent = t['footer_email'] || t['email'] || '';
  document.getElementById('footer-socials-label').textContent = t['footer_socials'] || t['socials'] || '';
  document.getElementById('footer-trust-label').textContent = t['footer_trust'] || t['trust'] || '';
  document.getElementById('footer-telegram').textContent   = t['footer_telegram'] || t['telegram'] || '';
  document.getElementById('footer-instagram').textContent  = t['footer_instagram'] || t['instagram'] || '';
  document.getElementById('footer-linkedin').textContent   = t['footer_linkedin'] || t['linkedin'] || '';

  fetch("https://api.xtremedev.co/endpoints/settings.php?project_id=" + project_id + "&lang=" + encodeURIComponent(lang))
    .then(function(res){ return res.json(); })
    .then(function(data){
      document.getElementById('footer-email').textContent = data.email || '';
      // شبکه‌های اجتماعی
      var socials = data.socials || {};
      document.getElementById('footer-telegram').href = socials.telegram || "#";
      document.getElementById('footer-instagram').href = socials.instagram || "#";
      document.getElementById('footer-linkedin').href = socials.linkedin || "#";
      // عنوان سایت
      var siteTitle = data.site_title || '';
      // Copyright
      var copyright = "";
      if (lang==='fa') {
          copyright =
              (t['footer_copyright']||t['copyright']||'') +
              (siteTitle ? " <span style='font-weight:700;'>" + siteTitle + "</span> &copy; " + (new Date().getFullYear()) : '') +
              ((t['footer_copyright2']||t['copyright2']) ? " | " + (t['footer_copyright2']||t['copyright2']) : '');
      } else {
          copyright =
              (t['footer_copyright']||t['copyright']||'') +
              (siteTitle ? " <span style='font-weight:700;'>" + siteTitle + "</span> &copy; " + (new Date().getFullYear()) : '');
      }
      document.getElementById('footer-copyright').innerHTML = copyright;
    });
})();
</script>