<?php
// endpoints/admin/edit_user.php

header('Content-Type: application/json');
require_once __DIR__.'/../../shared/auth-helper.php';

// ========== 1. دریافت Bearer Token ==========
function getBearerToken() {
    $header = '';
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $header = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) {
            $header = $headers['Authorization'];
        }
    }
    if ($header && preg_match('/Bearer\s+(\S+)/', $header, $matches)) {
        return $matches[1];
    }
    return null;
}

// ========== 2. احراز هویت ادمین ==========
$access_token = getBearerToken();
if (!$access_token) {
    http_response_code(401);
    echo json_encode(['success'=>false, 'error'=>'unauthorized']);
    exit;
}

// استعلام اطلاعات ادمین از سرور auth
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://auth.xtremedev.co/admininfo.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_token"
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 8);
$resp = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpcode != 200 || !$resp) {
    http_response_code(401);
    echo json_encode(['success'=>false, 'error'=>'invalid_admin_token']);
    exit;
}
$admininfo = @json_decode($resp, true);
if (!$admininfo || empty($admininfo['role'])) {
    http_response_code(401);
    echo json_encode(['success'=>false, 'error'=>'invalid_admin_info']);
    exit;
}
if (!in_array($admininfo['role'], ['superadmin', 'manager'])) {
    http_response_code(403);
    echo json_encode(['success'=>false, 'error'=>'forbidden']);
    exit;
}

// ========== 3. دریافت و اعتبارسنجی پارامترها ==========
$input = $_POST;

// فیلدهای اجباری
$user_id = isset($input['id']) ? intval($input['id']) : 0;
$name    = trim($input['name'] ?? '');
$email   = trim($input['email'] ?? '');
$phone   = trim($input['phone'] ?? '');
$photo   = trim($input['photo'] ?? '');
$is_active = isset($input['is_active']) ? (intval($input['is_active']) ? 1 : 0) : 0;
$password = $input['password'] ?? '';

// اعتبارسنجی اولیه
$errors = [];
if ($user_id <= 0) $errors[] = 'Invalid user id.';
if ($name === '')  $errors[] = 'Full name is required.';
if ($email === '' && $phone === '') $errors[] = 'At least one of email or phone required.';
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';
if ($phone !== '') {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) < 8 || strlen($phone) > 15) $errors[] = 'Invalid phone.';
}
if ($password !== '' && strlen($password) < 6) $errors[] = 'Password must be at least 6 chars.';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success'=>false, 'error'=>implode(' ', $errors)]);
    exit;
}

// ========== 4. ارسال به سرور مرکزی ==========
// مقادیر را ارسال کن به auth.xtremedev.co/api/edit_user.php با client_id/secret
define('AUTH_API_CLIENT_ID', 'admin-panel');  // مقدار واقعی را جایگزین کن
define('AUTH_API_CLIENT_SECRET', 'KB1UX!X%9MxPF7^hYqpL*hn}~,kdq>4RVtV~F=uW6u_U2HgvFWi?g9*=zpUp40%i%PP751gP2E+5nCaZk#JEzw9xE=E~6M1qqH9*'); // مقدار واقعی را جایگزین کن

$post_data = [
    'client_id'    => AUTH_API_CLIENT_ID,
    'client_secret'=> AUTH_API_CLIENT_SECRET,
    'id'           => $user_id,
    'name'         => $name,
    'email'        => $email,
    'phone'        => $phone,
    'photo'        => $photo,
    'is_active'    => $is_active,
    'password'     => $password
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://auth.xtremedev.co/api/edit_user.php");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$resp = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpcode == 200 && $resp) {
    echo $resp;
    exit;
} else {
    http_response_code(500);
    echo json_encode(['success'=>false, 'error'=>'Central API error']);
    exit;
}
?>