<?php
session_start();

// ------ اطلاعات کلاینت را اینجا وارد کن ------
$client_id = 'xtremeclient-web'; // دقیقاً همان که در auth.xtremedev.co ثبت کردی
$client_secret = 'jGcrnxXHFru#UT:-pfDpK)6!5b!G,+sbebEemXzzoP2oRPFDc~uW9w.KyP>n,Xd~4aqKjeUdDkDb@?>0Xp=VP9]YxWHJ}_siwr-=';
$redirect_uri = 'https://xtremeclient.com/oauth-callback.php'; // همین فایل در سایت فعلی
// ---------------------------------------------

// 1. چک پارامتر code
if (!isset($_GET['code'])) {
    die('No code received from SSO.');
}

// 2. درخواست access_token از SSO
$ch = curl_init('https://auth.xtremedev.co/token.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'grant_type' => 'authorization_code',
    'code' => $_GET['code'],
    'redirect_uri' => $redirect_uri,
    'client_id' => $client_id,
    'client_secret' => $client_secret,
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
if(curl_errno($ch)) die("Curl error: " . curl_error($ch));
curl_close($ch);

$data = json_decode($response, true);

// 3. اگر موفق بود، توکن را نگه دار و پروفایل کاربر را واکشی کن
if (isset($data['access_token'])) {
    $_SESSION['access_token'] = $data['access_token'];
    $_SESSION['refresh_token'] = $data['refresh_token'] ?? null;
    $_SESSION['token_expires'] = time() + ($data['expires_in'] ?? 3600);

    // واکشی پروفایل کاربر از SSO
    $ch2 = curl_init('https://auth.xtremedev.co/userinfo.php');
    curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $data['access_token']]);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    $profile_json = curl_exec($ch2);
    curl_close($ch2);

    $profile = json_decode($profile_json, true);

    if (isset($profile['id'])) {
        $_SESSION['user_profile'] = $profile;
        // انتقال به داشبورد
        header("Location: /dashboard");
        exit;
    } else {
        echo "<b>دریافت پروفایل کاربر ناموفق بود:</b><br>";
        echo "<pre>" . htmlspecialchars($profile_json) . "</pre>";
        exit;
    }
} else {
    echo "<b>دریافت توکن ناموفق بود:</b><br>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    exit;
}