<?php
// اگر $logo_url تعریف نشده بود:
if(!isset($logo_url)) $logo_url = "resources/xtreme-company-logo.png";
?>
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm" id="mainNavbar">
  <div class="container">
    <a class="navbar-brand mx-auto" href="/">
      <img class="logo-img" src="<?=htmlspecialchars($logo_url)?>" alt="Logo">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse d-flex flex-row align-items-center" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item"><a class="nav-link<?=basename($_SERVER['PHP_SELF'])=='index.php'?' active':''?>" href="index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link<?=basename($_SERVER['PHP_SELF'])=='projects.php'?' active':''?>" href="projects.php">Projects</a></li>
        <li class="nav-item"><a class="nav-link<?=basename($_SERVER['PHP_SELF'])=='team.php'?' active':''?>" href="team.php">Team</a></li>
        <li class="nav-item"><a class="nav-link<?=basename($_SERVER['PHP_SELF'])=='articles.php'?' active':''?>" href="articles.php">Articles</a></li>
        <li class="nav-item"><a class="nav-link<?=basename($_SERVER['PHP_SELF'])=='joinus.php'?' active':''?>" href="join-us.php">Join Us</a></li>
      </ul>
      <button id="themeSwitch" class="theme-switch ms-3" title="Switch theme">Dark</button>
    </div>
  </div>
</nav>
<div class="nav-placeholder"></div>