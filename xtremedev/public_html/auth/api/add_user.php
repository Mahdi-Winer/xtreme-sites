<?php
header('Content-Type: application/json');
require_once __DIR__.'/../shared/inc/database-config.php';

// اعتبارسنجی کلاینت از دیتابیس
$client_id = $_POST['client_id'] ?? '';
$client_secret = $_POST['client_secret'] ?? '';
if ($client_id === '' || $client_secret === '') {
    http_response_code(401);
    echo json_encode(['success'=>false, 'error'=>'invalid_client']);
    exit;
}

$stmt = $mysqli->prepare("SELECT id FROM clients WHERE client_id=? AND client_secret=? LIMIT 1");
$stmt->bind_param('ss', $client_id, $client_secret);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows !== 1) {
    $stmt->close();
    http_response_code(401);
    echo json_encode(['success'=>false, 'error'=>'invalid_client']);
    exit;
}
$stmt->close();

// ادامه کد اضافه‌کردن کاربر دقیقا مثل قبل:
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$errors = [];
if ($name === '') $errors[] = 'Full name is required.';
if ($email === '' && $phone === '') $errors[] = 'At least one of Email or Phone number is required.';
if ($email !== '') {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';
    else {
        $stmt = $mysqli->prepare("SELECT COUNT(*) FROM users WHERE email=?");
        $stmt->bind_param('s', $email);
        $stmt->execute(); $stmt->bind_result($cnt); $stmt->fetch(); $stmt->close();
        if ($cnt > 0) $errors[] = 'This email is already registered.';
    }
}
if ($phone !== '') {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) < 8 || strlen($phone) > 15) $errors[] = 'Invalid phone.';
    else {
        $stmt = $mysqli->prepare("SELECT COUNT(*) FROM users WHERE phone=?");
        $stmt->bind_param('s', $phone);
        $stmt->execute(); $stmt->bind_result($cnt); $stmt->fetch(); $stmt->close();
        if ($cnt > 0) $errors[] = 'This phone number is already registered.';
    }
}
if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success'=>false, 'error'=>implode(' ', $errors)]);
    exit;
}

// ثبت کاربر
$password_hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $mysqli->prepare("INSERT INTO users (fullname, email, phone, password, created_at) VALUES (?, ?, ?, ?, NOW())");
$stmt->bind_param('ssss', $name, $email, $phone, $password_hash);
if ($stmt->execute()) {
    echo json_encode(['success'=>true]);
} else {
    http_response_code(500);
    echo json_encode(['success'=>false, 'error'=>'db_error']);
}
$stmt->close();
?>