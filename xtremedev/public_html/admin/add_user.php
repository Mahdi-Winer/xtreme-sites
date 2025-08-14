<?php
session_start();
if (!isset($_SESSION['admin_user_id']) || !isset($_SESSION['admin_access_token'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['admin_username'] ?? '';
$email    = $_SESSION['admin_email'] ?? '';
$role     = $_SESSION['admin_role'] ?? '';
if (!in_array($role, ['superadmin', 'manager'])) {
    header("Location: access_denied.php");
    exit;
}

$success = false;
$errors = [];
$name = '';
$user_email = '';
$phone = '';
$password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $user_email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    $post_data = [
        'name'     => $name,
        'email'    => $user_email,
        'phone'    => $phone,
        'password' => $password
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.xtremedev.co/endpoints/admin/add_user.php");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer ".$_SESSION['admin_access_token']
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $resp = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = @json_decode($resp, true);

    if ($httpcode == 200 && isset($data['success']) && $data['success']) {
        $success = true;
        $name = $user_email = $phone = $password = '';
    } else {
        if (!empty($data['error'])) {
            $errors[] = htmlspecialchars($data['error']);
        } else {
            $errors[] = "Unknown error";
        }
    }
}

function role_badge($role) {
    $map = [
        'superadmin' => ['Superadmin', '#38a8ff'],
        'manager'    => ['Manager',    '#00e9c2'],
        'support'    => ['Support',    '#ffb13a'],
        'read_only'  => ['Read Only',  '#6c8cff'],
    ];
    $d = $map[$role] ?? ['Unknown', '#aaa'];
    return '<span class="role-badge" style="background:'.$d[1].';">'.$d[0].'</span>';
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Add User | XtremeDev Admin</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php include __DIR__.'/../shared/inc/head-assets.php'; ?>
    <?php include 'includes/admin-styles.php'; ?>
    <style>
        html, body { height: 100%; }
        body { background: #181f27 !important; color: #e6e9f2 !important; font-family: Vazirmatn, Tahoma, Arial, sans-serif; min-height: 100vh; margin: 0; display: flex; flex-direction: column; }
        .container-main { max-width: 980px; margin: 48px auto 0 auto; flex: 1 0 auto; width: 97vw; }
        .page-title { font-weight:900; color:#38a8ff; font-size:2.1rem; letter-spacing:.5px; margin-bottom:1.2rem; display: flex; align-items: center; gap: 14px; }
        .role-badge { display:inline-block; padding: 3px 14px; font-size: 0.95rem; border-radius: 9px; color:#fff; font-weight:700; letter-spacing: .3px; vertical-align:middle; margin-left: 8px; box-shadow:0 1px 6px #22292f1a; }
        .user-form-box {
            background: #232d3b;
            border-radius: 28px;
            box-shadow: 0 2px 22px #29364b22;
            border:2.2px solid #29364b;
            padding: 2.7rem 3.2rem 1.8rem 3.2rem;
            margin-bottom: 42px;
            max-width: 820px;
            margin-left: auto;
            margin-right: auto;
        }
        label {font-weight:700;color:#aad3ff;}
        .form-control { background: #232d3b; color: #e6e9f2; border: 1.5px solid #31415a; border-radius: 12px; padding: 14px 19px; font-size: 1.13rem; margin-bottom: 15px; transition: border .15s; }
        .form-control:focus { outline: none; border-color: #38a8ff; background: #253040; color: #fff; }
        .btn-submit { background: linear-gradient(90deg,#38a8ff,#44e1ff 90%); color: #fff; font-weight: 800; border-radius: 13px; padding: 15px 0; width: 270px; font-size: 1.2rem; margin-top: 28px; margin-bottom: 12px; box-shadow: 0 2px 14px #38a8ff33; border: none; transition: background .18s, box-shadow .16s; letter-spacing: .2px;}
        .btn-submit:hover { background: linear-gradient(90deg,#2499fa,#1bc6e8 90%); color: #fff; box-shadow: 0 5px 24px #38a8ff40;}
        .form-link { color:#aad3ff; text-decoration:none; margin-top:24px; display:inline-block; font-size:1.10rem; margin-bottom:2px; }
        .form-link:hover {color:#fff;text-decoration:underline;}
        .alert-success { background: #202e3d; color: #aef3c8; border: 1.7px solid #1db67a; font-weight:700; font-size: 1.09rem; border-radius:13px; }
        .alert-danger { background: #32212a; color: #ffb1d1; border: 1.7px solid #e13a3a; font-weight:700; font-size: 1.09rem; border-radius:13px; }
        @media (max-width: 1100px) {
            .container-main { max-width: 100vw; }
            .user-form-box { max-width: 99vw; padding: 2.3rem 1.1rem 1.3rem 1.1rem; }
        }
        @media (max-width: 700px) {
            .container-main{padding:0 2px;}
            .page-title{font-size:1.1rem;}
            .user-form-box{padding: 1.1rem 0.2rem;}
            .btn-submit {width:100%;}
        }
        .footer-sticky {
            flex-shrink: 0;
            margin-top: 50px;
            width: 100%;
            background: linear-gradient(90deg, #232d3b 40%, #273c54 100%);
            color: #aad3ff;
            padding: 20px 0 9px 0;
            text-align: center;
            border-top: 2.5px solid #31415a;
            font-size: 1.08rem;
            letter-spacing: .18px;
            box-shadow: 0 -2px 24px #38a8ff11;
            position: relative;
        }
        .footer-sticky .footer-inner {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
        }
        .footer-sticky svg {
            display: inline-block;
            width: 22px;
            height: 22px;
            fill: #38a8ff;
            margin-bottom: -4px;
            margin-right: 2px;
        }
        .footer-sticky a {
            color: #aad3ff;
            text-decoration: underline dotted;
            transition: color .13s;
        }
        .footer-sticky a:hover {
            color: #fff;
        }
    </style>
</head>
<body>
<?php
switch($role) {
    case 'superadmin': include 'includes/superadmin-navbar.php'; break;
    case 'manager':    include 'includes/manager-navbar.php'; break;
    default:           include 'includes/navbar.php';
}
?>
<div class="container-main">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-3">
        <div class="page-title">
            Add User
            <?=role_badge($role)?>
        </div>
        <div>
      <span style="font-size:1rem;color:#b9d5f6;">
        <b><?=htmlspecialchars($username)?></b> (<span style="color:#38a8ff;"><?=htmlspecialchars($email)?></span>)
      </span>
            <a href="logout.php" class="btn btn-sm btn-danger ms-2">Logout</a>
        </div>
    </div>
    <div class="user-form-box">
        <?php if($success): ?>
            <div class="alert alert-success mb-3">User successfully added!</div>
        <?php elseif($errors): ?>
            <div class="alert alert-danger mb-3"><?=implode('<br>', array_map('htmlspecialchars', $errors))?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <label for="name">Full Name <span style="color:#ff8e8e">*</span></label>
            <input type="text" class="form-control" id="name" name="name" required value="<?=htmlspecialchars($name)?>">
            <label for="email">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?=htmlspecialchars($user_email)?>" placeholder="Optional if phone entered">
            <label for="phone">Phone Number</label>
            <input type="text" class="form-control" id="phone" name="phone" value="<?=htmlspecialchars($phone)?>" placeholder="Optional if email entered" pattern="[0-9+ ]*">
            <label for="password">Password <span style="color:#ff8e8e">*</span></label>
            <input type="password" class="form-control" id="password" name="password" required minlength="6" value="">
            <button type="submit" class="btn btn-submit mt-2">Add User</button>
        </form>
        <a href="users.php" class="form-link">&larr; Back to users list</a>
    </div>
</div>
<footer class="footer-sticky">
    <div class="footer-inner">
        <svg viewBox="0 0 24 24">
            <path d="M12 2C6.477 2 2 6.477 2 12c0 5.523 4.477 10 10 10s10-4.477 10-10c0-5.523-4.477-10-10-10zm-.25 4.7c.67 0 1.25.58 1.25 1.3s-.58 1.3-1.25 1.3-1.25-.58-1.25-1.3.58-1.3 1.25-1.3zm2.46 12.29c-.19.07-.4.11-.61.11-.23 0-.45-.04-.65-.13l-1.21-.54c-.2-.09-.32-.31-.32-.58V10.6c0-.33.26-.6.59-.6h.01c.32 0 .59.27.59.6v5.53l.82.36c.31.14.44.5.29.8-.07.15-.19.26-.32.31z"/>
        </svg>
        <span>
            &copy; <?=date('Y')?> <a href="https://xtremedev.co" target="_blank" rel="noopener">XtremeDev</a>. All rights reserved.
        </span>
    </div>
</footer>
<?php include __DIR__.'/../shared/inc/foot-assets.php'; ?>
</body>
</html>