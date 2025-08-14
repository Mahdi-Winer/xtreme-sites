<?php
header('Content-Type: application/json');
require_once __DIR__.'/../shared/inc/database-config.php';

// ----------- 1. اعتبارسنجی کلاینت از جدول clients -----------
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

// ----------- 2. دریافت و اعتبارسنجی پارامترها -----------
$user_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$photo   = trim($_POST['photo'] ?? '');
$is_active = isset($_POST['is_active']) ? (intval($_POST['is_active']) ? 1 : 0) : 0;
$password = $_POST['password'] ?? '';

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

// ----------- 3. بررسی تکراری بودن ایمیل/موبایل -----------
if ($email !== '') {
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM users WHERE email=? AND id<>?");
    $stmt->bind_param('si', $email, $user_id);
    $stmt->execute();
    $stmt->bind_result($cnt);
    $stmt->fetch();
    $stmt->close();
    if ($cnt > 0) {
        http_response_code(409);
        echo json_encode(['success'=>false, 'error'=>'This email is already registered for another user.']);
        exit;
    }
}
if ($phone !== '') {
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM users WHERE phone=? AND id<>?");
    $stmt->bind_param('si', $phone, $user_id);
    $stmt->execute();
    $stmt->bind_result($cnt);
    $stmt->fetch();
    $stmt->close();
    if ($cnt > 0) {
        http_response_code(409);
        echo json_encode(['success'=>false, 'error'=>'This phone number is already registered for another user.']);
        exit;
    }
}

// ----------- 4. ویرایش اطلاعات کاربر -----------
if ($password !== '') {
    $password_hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare(
        "UPDATE users SET name=?, email=?, phone=?, photo=?, password=?, is_active=?, updated_at=NOW() WHERE id=?"
    );
    $stmt->bind_param('ssssssi', $name, $email, $phone, $photo, $password_hashed, $is_active, $user_id);
} else {
    $stmt = $mysqli->prepare(
        "UPDATE users SET name=?, email=?, phone=?, photo=?, is_active=?, updated_at=NOW() WHERE id=?"
    );
    $stmt->bind_param('ssssii', $name, $email, $phone, $photo, $is_active, $user_id);
}
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

if ($affected > 0) {
    echo json_encode(['success'=>true, 'message'=>'User successfully updated!']);
    exit;
} else {
    echo json_encode(['success'=>false, 'error'=>'No changes applied to user data.']);
    exit;
}
?>