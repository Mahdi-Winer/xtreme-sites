<?php
session_start();
if (!isset($_SESSION['admin_access_token'])) {
    header("Location: login.php");
    exit;
}

// اطلاعات ادمین را با توکن از auth مرکزی بگیر
$api_url = 'https://auth.xtremedev.co/admininfo.php'; // آدرس واقعی
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $_SESSION['admin_access_token']
]);
$resp = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpcode != 200) {
    // توکن نامعتبر یا منقضی: ری‌دایرکت به لاگین
    header("Location: admin-login.php");
    exit;
}

$data = @json_decode($resp, true);
if (!isset($data['role'])) {
    header('Location: access_denied.php');
    exit;
}

switch ($data['role']) {
    case 'superadmin':
        header('Location: superadmin-index.php');
        break;
    case 'manager':
        header('Location: manager-index.php');
        break;
    case 'support':
        header('Location: supporter-index.php');
        break;
    case 'read_only':
        header('Location: readonly-index.php');
        break;
    default:
        header('Location: access_denied.php');
}
exit;