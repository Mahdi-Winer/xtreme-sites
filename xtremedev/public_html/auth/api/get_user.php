<?php
header('Content-Type: application/json');
require_once __DIR__.'/../shared/inc/database-config.php';

// اعتبارسنجی کلاینت از دیتابیس
$client_id = $_POST['client_id'] ?? '';
$client_secret = $_POST['client_secret'] ?? '';
if ($client_id === '' || $client_secret === '') {
    http_response_code(401);
    echo json_encode(['error'=>'invalid_client']);
    exit;
}

$stmt = $mysqli->prepare("SELECT id FROM clients WHERE client_id=? AND client_secret=? LIMIT 1");
$stmt->bind_param('ss', $client_id, $client_secret);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows !== 1) {
    $stmt->close();
    http_response_code(401);
    echo json_encode(['error'=>'invalid_client']);
    exit;
}
$stmt->close();

// دریافت و اعتبارسنجی پارامتر id
$user_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['error'=>'invalid_id']);
    exit;
}

// دریافت اطلاعات کاربر
$stmt = $mysqli->prepare("SELECT id, fullname, email, phone, photo, is_active FROM users WHERE id=? LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($id, $name, $email, $phone, $photo, $is_active);
if ($stmt->fetch()) {
    echo json_encode([
        'id'        => $id,
        'name'      => $name,
        'email'     => $email,
        'phone'     => $phone,
        'photo'     => $photo,
        'is_active' => $is_active
    ]);
} else {
    http_response_code(404);
    echo json_encode(['error'=>'not_found']);
}
$stmt->close();
?>