<?php
header('Content-Type: application/json');
require_once __DIR__.'/shared/inc/database-config.php';

// ********* احراز هویت با OAuth Token *********

function getBearerToken() {
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
    if (!empty($header) && preg_match('/Bearer\s(\S+)/', $header, $matches)) {
        return $matches[1];
    }
    return null;
}

$token = getBearerToken();
if (!$token) {
    http_response_code(401);
    echo json_encode(['success'=>false, 'message'=>'Unauthorized']);
    exit;
}

// اعتبارسنجی توکن و انقضا
$stmt = $mysqli->prepare("SELECT user_id, expires_at FROM oauth_tokens WHERE access_token=? LIMIT 1");
$stmt->bind_param('s', $token);
$stmt->execute();
$stmt->bind_result($user_id, $expires_at);
if (!$stmt->fetch()) {
    $stmt->close();
    http_response_code(401);
    echo json_encode(['success'=>false, 'message'=>'Invalid token']);
    exit;
}
$stmt->close();
if (strtotime($expires_at) < time()) {
    http_response_code(401);
    echo json_encode(['success'=>false, 'message'=>'Token expired']);
    exit;
}

// ********* اعتبارسنجی درخواست *********
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false, 'message'=>'Method Not Allowed']);
    exit;
}

$name  = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$photo = trim($_POST['photo'] ?? '');

// ********* اعتبارسنجی مقادیر *********
if (!$name || !$email) {
    http_response_code(400);
    echo json_encode(['success'=>false, 'message'=>'Name and email are required.']);
    exit;
}

// ********* جلوگیری از ایمیل تکراری *********
$stmt = $mysqli->prepare("SELECT id FROM users WHERE email=? AND id!=?");
$stmt->bind_param('si', $email, $user_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    http_response_code(409);
    echo json_encode(['success'=>false, 'message'=>'Email already exists.']);
    exit;
}
$stmt->close();

// ********* آپدیت اطلاعات *********
$stmt = $mysqli->prepare("UPDATE users SET fullname=?, email=?, phone=?, photo=? WHERE id=?");
$stmt->bind_param('ssssi', $name, $email, $phone, $photo, $user_id);
if ($stmt->execute()) {
    echo json_encode(['success'=>true]);
} else {
    http_response_code(500);
    echo json_encode(['success'=>false, 'message'=>'Database error.']);
}
$stmt->close();