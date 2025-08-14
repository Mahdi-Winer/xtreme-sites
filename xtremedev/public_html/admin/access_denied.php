<?php
session_start();
$is_logged_in = isset($_SESSION['admin_user_id']);
$role = $_SESSION['admin_role'] ?? null;
$username = $_SESSION['admin_username'] ?? null;

// دوزبانه
$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'en');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : 'en';
$is_rtl = ($lang === 'fa');
$tr = [
    'en' => [
        'access_denied' => 'Access Denied',
        'no_permission' => 'You do not have permission to access this page.',
        'user'          => 'User',
        'role'          => 'Role',
        'dashboard'     => 'Go to Dashboard',
        'footer'        => 'All rights reserved.',
        'superadmin'    => 'Superadmin',
        'manager'       => 'Manager',
        'support'       => 'Support',
        'read_only'     => 'Read Only',
        'unknown'       => 'Unknown',
    ],
    'fa' => [
        'access_denied' => 'دسترسی غیرمجاز',
        'no_permission' => 'شما اجازه دسترسی به این صفحه را ندارید.',
        'user'          => 'کاربر',
        'role'          => 'نقش',
        'dashboard'     => 'بازگشت به داشبورد',
        'footer'        => 'تمامی حقوق محفوظ است.',
        'superadmin'    => 'سوپرادمین',
        'manager'       => 'مدیر',
        'support'       => 'پشتیبان',
        'read_only'     => 'فقط‌خواندنی',
        'unknown'       => 'نامشخص',
    ]
];

function role_label($role, $lang, $tr) {
    $map = [
        'superadmin' => 'superadmin',
        'manager'    => 'manager',
        'support'    => 'support',
        'read_only'  => 'read_only',
    ];
    $key = $map[$role] ?? 'unknown';
    return $tr[$lang][$key] ?? $tr[$lang]['unknown'];
}
?>
<!DOCTYPE html>
<html lang="<?=$lang?>" dir="<?=$is_rtl?'rtl':'ltr'?>">
<head>
    <meta charset="UTF-8">
    <title><?=$tr[$lang]['access_denied']?> | XtremeDev Admin</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php
    // اگر استایل‌های کلی پنل ادمین دارید اینجا قرار دهید (head-assets.php)
    include __DIR__.'/../shared/inc/head-assets.php';
    include 'includes/admin-styles.php';
    ?>
    <style>
        body {
            min-height: 100vh;
            background: #181f27 !important;
            color: #e6e9f2 !important;
            font-family: Vazirmatn, Tahoma, Arial, sans-serif;
            margin: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .access-denied-card {
            background: #232d3b;
            border-radius: 22px;
            box-shadow: 0 2px 24px #e13a3a11;
            border: 1.5px solid #e13a3a55;
            padding: 2.3rem 2.4rem 2rem 2.4rem;
            max-width: 410px;
            width: 96vw;
            text-align: center;
            margin: 60px auto;
        }
        .access-title {
            color: #e13a3a;
            font-weight: 900;
            font-size: 2.1rem;
            letter-spacing: .2px;
            margin-bottom: .7rem;
        }
        .access-msg {
            color: #e6e9f2;
            font-size: 1.18rem;
            margin-bottom: 1.7rem;
            margin-top: 1rem;
            font-weight: 500;
        }
        .btn-back {
            background: #38a8ff;
            color: #fff;
            border-radius: 10px;
            font-weight: 700;
            border: none;
            padding: 0.8rem 2.2rem;
            font-size: 1.09rem;
            margin-top: 1.2rem;
            margin-bottom: 8px;
            transition: background .15s;
            display: inline-block;
        }
        .btn-back:hover,
        .btn-back:focus {
            background: #2499fa;
            color: #fff;
            text-decoration: none;
        }
        .access-footer {
            color: #b9d5f6;
            font-size: .98rem;
            margin-top: 2.7rem;
            text-align: center;
            opacity: .8;
            letter-spacing: .2px;
        }
        @media (max-width: 650px) {
            .access-denied-card { padding: 1.5rem 0.7rem; font-size: 1rem;}
            .access-title { font-size: 1.3rem;}
            .access-msg { font-size: 1.04rem;}
        }
    </style>
</head>
<body>
<div class="access-denied-card">
    <div class="access-title">
        <span style="font-size:2.6rem;">&#9940;</span>
        <br>
        <?=$tr[$lang]['access_denied']?>
    </div>
    <div class="access-msg">
        <?=$tr[$lang]['no_permission']?><br>
        <?php if($is_logged_in && $username): ?>
            <span style="color:#aad3ff;display:block;margin-top:10px;">
          <?=$tr[$lang]['user']?>: <b><?=htmlspecialchars($username)?></b>
          <?php if($role): ?>
              (<span style="font-weight:600;"><?=role_label($role, $lang, $tr)?></span>)
          <?php endif; ?>
        </span>
        <?php endif; ?>
    </div>
    <a href="index.php" class="btn btn-back"><?=$tr[$lang]['dashboard']?></a>
    <div class="access-footer">
        &copy; <?=date('Y')?> XtremeDev. <?=$tr[$lang]['footer']?>
    </div>
</div>
<?php include __DIR__.'/../shared/inc/foot-assets.php'; ?>
</body>
</html>