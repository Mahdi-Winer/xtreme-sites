<script>
  // Theme Switcher
  const themeBtn = document.getElementById('themeSwitch');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  let theme = localStorage.getItem('theme');
  function setThemeBtnText(current) {
    if(current === 'dark') {
      themeBtn.textContent = "Light";
      themeBtn.title = 'Switch to Light Theme';
    } else {
      themeBtn.textContent = "Dark";
      themeBtn.title = 'Switch to Dark Theme';
    }
  }
  if(!theme) theme = prefersDark ? 'dark' : 'light';
  if(theme === 'dark') document.body.classList.add('dark-theme');
  setThemeBtnText(theme);
  themeBtn.onclick = function() {
    document.body.classList.toggle('dark-theme');
    let active = document.body.classList.contains('dark-theme') ? 'dark' : 'light';
    localStorage.setItem('theme', active);
    setThemeBtnText(active);
  }
</script>