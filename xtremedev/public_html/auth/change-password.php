<?php
header('Content-Type: application/json');
require_once __DIR__.'/shared/inc/database-config.php';

// ***** احراز هویت با OAuth Bearer Token *****
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
$user_id = intval($user_id);

// فقط POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false, 'message'=>'Method Not Allowed']);
    exit;
}

// پارامترها
$old = trim($_POST['old_password'] ?? '');
$new = trim($_POST['new_password'] ?? '');

// اعتبارسنجی
if (!$old || !$new) {
    http_response_code(400);
    echo json_encode(['success'=>false, 'message'=>'Both old and new passwords are required.']);
    exit;
}

// دریافت رمز فعلی از دیتابیس
$stmt = $mysqli->prepare("SELECT password FROM users WHERE id=? LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($password_hash);
if (!$stmt->fetch()) {
    $stmt->close();
    http_response_code(404);
    echo json_encode(['success'=>false, 'message'=>'User not found.']);
    exit;
}
$stmt->close();

// چک رمز فعلی
if (!password_verify($old, $password_hash)) {
    http_response_code(403);
    echo json_encode(['success'=>false, 'message'=>'Current password is incorrect.']);
    exit;
}

// رمز جدید مشابه قبلی نباشد (اختیاری)
if (password_verify($new, $password_hash)) {
    http_response_code(409);
    echo json_encode(['success'=>false, 'message'=>'New password must be different from current password.']);
    exit;
}

// ذخیره رمز جدید (hash)
$new_hash = password_hash($new, PASSWORD_DEFAULT);
$stmt = $mysqli->prepare("UPDATE users SET password=?, updated_at=NOW() WHERE id=?");
$stmt->bind_param('si', $new_hash, $user_id);
if ($stmt->execute()) {
    echo json_encode(['success'=>true]);
} else {
    http_response_code(500);
    echo json_encode(['success'=>false, 'message'=>'Database error.']);
}
$stmt->close();