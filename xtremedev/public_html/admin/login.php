<?php
// admin-login.php -- فرم ورود ادمین (توکن محور)
session_start();
require_once __DIR__.'/../shared/inc/config.php';

if (isset($_SESSION['admin_access_token'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$username || !$password) {
        $error = "Please enter both username and password.";
    } else {
        // درخواست به API لاگین ادمین (login-admin.php)
        $api_url = "https://auth.xtremedev.co/login-admin.php";
        $post_fields = http_build_query([
            'username' => $username,
            'password' => $password
        ]);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        $resp = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = @json_decode($resp, true);

        if ($httpcode == 200 && isset($data['success']) && $data['success'] && !empty($data['access_token'])) {
            // ذخیره توکن‌ها در سشن
            $_SESSION['admin_access_token'] = $data['access_token'];
            $_SESSION['admin_refresh_token'] = $data['refresh_token'];
            $_SESSION['admin_login_time'] = time();

            // --- دریافت اطلاعات کامل ادمین از API مرکزی ---
            $token = $data['access_token'];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://auth.xtremedev.co/admininfo.php");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $token"
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            $resp_info = curl_exec($ch);
            $httpcode_info = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $info = @json_decode($resp_info, true);

            if ($httpcode_info == 200 && is_array($info)) {
                $_SESSION['admin_user_id']   = $info['id'] ?? null;
                $_SESSION['admin_username']  = $info['username'] ?? '';
                $_SESSION['admin_email']     = $info['email'] ?? '';
                $_SESSION['admin_role']      = $info['role'] ?? '';
                $_SESSION['admin_status']    = $info['status'] ?? '';
                $_SESSION['admin_created_at']= $info['created_at'] ?? '';
                // بقیه فیلدها هم اگر خواستی اضافه کن
                header("Location: index.php");
                exit;
            } else {
                $error = "Login succeeded but failed to fetch admin info. Please try again.";
            }
        } else {
            $error = $data['error'] ?? 'Login failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Admin Login | XtremeDev</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        html, body {
            height: 100%;
            background: #181f27;
            color: #e6e9f2;
            font-family: Vazirmatn, Tahoma, Arial, sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-center-container {
            min-height: 100vh;
            min-height: 100dvh;
            width: 100vw;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            width: 100%;
            max-width: 400px;
            background: #232d3b;
            border-radius: 16px;
            box-shadow: 0 6px 36px #22292f77;
            padding: 2.5rem 2rem;
            color: #fff;
            animation: fadein 0.7s;
        }
        @media (max-width: 480px) {
            .login-box { padding: 1.5rem 0.7rem; }
        }
        .login-box h2 { color: #38a8ff; font-weight: 900; letter-spacing: 1px; margin-bottom: 1.5rem; }
        .form-label { color: #38a8ff; font-weight: 700; }
        .form-control { border-radius: 9px; min-height: 44px; background: #181f27; color: #fff; border:1.5px solid #384c6e;}
        .form-control:focus { border-color: #38a8ff; background: #161e2e; color: #fff;}
        .btn-primary { background: #2499fa; border:0; font-weight:800; }
        .btn-primary:hover { background: #38a8ff; }
        @keyframes fadein {
            from {opacity:0; transform: translateY(40px);}
            to {opacity:1; transform: none;}
        }
    </style>
</head>
<body>
<div class="login-center-container">
    <div class="login-box shadow">
        <h2 class="mb-4 text-center">Admin Login</h2>
        <?php if($error): ?>
            <div class="alert alert-danger"><?=$error?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <label class="form-label" for="username">Username</label>
            <input type="text" class="form-control mb-3" id="username" name="username" required autofocus>
            <label class="form-label" for="password">Password</label>
            <input type="password" class="form-control mb-4" id="password" name="password" required>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
    </div>
</div>
</body>
</html>