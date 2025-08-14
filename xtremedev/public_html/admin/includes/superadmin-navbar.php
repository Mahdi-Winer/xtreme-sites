<?php
$self = basename($_SERVER['PHP_SELF']);
$is_super_admin = isset($_SESSION['admin_is_super']) && $_SESSION['admin_is_super'];
$menu_items = [
    'dashboard' => 'Dashboard',
    'users' => 'Users',
    'products' => 'Products',
    'projects' => 'Projects',
    'orders' => 'Orders',
    'invoices' => 'Invoices',
    'tickets' => 'Tickets',
    'joinus' => 'Join Us Requests',
    'team' => 'Team',
    'team_roles' => 'Team Roles',
    'logout' => 'Logout',
    'brand' => 'XtremeDev Admin',
    'settings' => 'Site Settings'
];
?>
<style>
:root {
    --nav-bg: #181e27;
    --nav-border: #10131a;
    --nav-link: #b5bed5;
    --nav-link-active: #3bbcff;
    --nav-link-hover: #fff;
    --nav-brand: #3bbcff;
}
.admin-navbar {
    background: var(--nav-bg) !important;
    border-bottom: 1.5px solid var(--nav-border);
    font-family: Vazirmatn, Tahoma, Arial, sans-serif;
    min-height: 54px;
    z-index: 10;
    padding-left: 0 !important;
    padding-right: 0 !important;
    box-shadow: 0 5px 24px #13151c1a;
}
.admin-navbar .navbar-brand {
    color: var(--nav-brand) !important;
    font-weight: 900;
    font-size: 1.36rem;
    margin-right: 18px;
    padding: 7px 20px 7px 7px;
    letter-spacing: .5px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.admin-navbar .navbar-brand i {
    font-size: 1.25rem;
    color: #fff;
}
.admin-navbar .navbar-nav .nav-link {
    color: var(--nav-link);
    font-weight: 700;
    padding: 9px 17px;
    margin: 0 1px;
    font-size: 1.08rem;
    border-radius: 8px;
    transition: color .13s, background .13s;
    letter-spacing: .1px;
    position: relative;
}
.admin-navbar .navbar-nav .nav-link.active,
.admin-navbar .navbar-nav .nav-link:hover {
    color: var(--nav-link-active) !important;
    background: #232e41 !important;
    text-shadow: 0 2px 14px #3bbcff2a;
}
.admin-navbar .navbar-nav .nav-link:after {
    content: '';
    display: block;
    margin: 0 auto;
    height: 2.5px;
    width: 0;
    background: var(--nav-link-active);
    border-radius: 2px;
    transition: width .16s;
}
.admin-navbar .navbar-nav .nav-link.active:after,
.admin-navbar .navbar-nav .nav-link:hover:after {
    width: 50%;
}
.navbar-toggler {
    border: none;
    background: none !important;
    color: #3bbcff !important;
    font-size: 1.25rem;
}
.navbar-toggler:focus { box-shadow: none; }
@media (max-width: 1100px) {
    .admin-navbar .navbar-nav .nav-link { padding: 8px 9px; font-size: 1rem;}
    .admin-navbar .navbar-brand { font-size: 1rem; margin-right: 7px;}
}
@media (max-width: 900px) {
    .admin-navbar .navbar-brand {font-size: 1rem;}
}
@media (max-width: 650px) {
    .admin-navbar .navbar-brand {padding: 7px 7px 7px 2px;}
    .admin-navbar .navbar-nav .nav-link {font-size:.97rem;}
}
</style>
<nav class="navbar navbar-expand-lg admin-navbar" dir="ltr">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-terminal"></i>
            <?=$menu_items['brand']?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
            <i class="bi bi-list"></i>
        </button>
        <div class="collapse navbar-collapse justify-content-between" id="adminNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link<?=$self=='index.php'?' active':''?>" href="index.php"><?=$menu_items['dashboard']?></a></li>
                <li class="nav-item"><a class="nav-link<?=$self=='users.php'?' active':''?>" href="users.php"><?=$menu_items['users']?></a></li>
                <li class="nav-item"><a class="nav-link<?=$self=='products.php'?' active':''?>" href="products.php"><?=$menu_items['products']?></a></li>
                <li class="nav-item"><a class="nav-link<?=$self=='projects.php'?' active':''?>" href="projects.php"><?=$menu_items['projects']?></a></li>
                <li class="nav-item"><a class="nav-link<?=$self=='orders.php'?' active':''?>" href="orders.php"><?=$menu_items['orders']?></a></li>
                <li class="nav-item"><a class="nav-link<?=$self=='invoices.php'?' active':''?>" href="invoices.php"><?=$menu_items['invoices']?></a></li>
                <li class="nav-item"><a class="nav-link<?=$self=='support_tickets.php'?' active':''?>" href="support_tickets.php"><?=$menu_items['tickets']?></a></li>
                <li class="nav-item"><a class="nav-link<?=$self=='join-us-requests.php'?' active':''?>" href="join-us-requests.php"><?=$menu_items['joinus']?></a></li>
                <li class="nav-item"><a class="nav-link<?=$self=='team.php'?' active':''?>" href="team.php"><?=$menu_items['team']?></a></li>
                <li class="nav-item"><a class="nav-link<?=$self=='team_roles.php'?' active':''?>" href="team_roles.php"><?=$menu_items['team_roles']?></a></li>
                <?php if ($is_super_admin): ?>
                <li class="nav-item"><a class="nav-link<?=$self=='admin_settings.php'?' active':''?>" href="admin_settings.php"><?=$menu_items['settings']?></a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="logout.php"><?=$menu_items['logout']?></a></li>
            </ul>
        </div>
    </div>
</nav>