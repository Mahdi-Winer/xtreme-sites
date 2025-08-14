<?php
// admininfo.php -- اطلاعات ادمین بر اساس توکن ادمین

header('Content-Type: application/json');
require_once __DIR__ . '/shared/inc/database-config.php';

// گرفتن access_token از هدر Authorization
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
if (!$header || !preg_match('/Bearer\s(\S+)/', $header, $matches)) {
    http_response_code(401);
    echo json_encode(['error' => 'no_token']);
    exit;
}
$access_token = $matches[1];

// پیدا کردن admin_id از جدول oauth_tokens
$stmt = $mysqli->prepare("SELECT user_id FROM oauth_tokens WHERE access_token=? AND expires_at > NOW() AND is_admin=1 LIMIT 1");
$stmt->bind_param('s', $access_token);
$stmt->execute();
$stmt->bind_result($admin_id);
if (!$stmt->fetch()) {
    http_response_code(401);
    echo json_encode(['error' => 'invalid_token']);
    exit;
}
$stmt->close();

// گرفتن اطلاعات ادمین از جدول admin_users
$stmt2 = $mysqli->prepare("SELECT id, username, email, role, status, created_at FROM admin_users WHERE id=? LIMIT 1");
$stmt2->bind_param('i', $admin_id);
$stmt2->execute();
$stmt2->store_result();
if ($stmt2->num_rows !== 1) {
    http_response_code(404);
    echo json_encode(['error' => 'not_found']);
    exit;
}
$stmt2->bind_result($id, $username, $email, $role, $status, $created_at);
$stmt2->fetch();
$stmt2->close();

$admin = [
    'id'         => $id,
    'username'   => $username,
    'email'      => $email,
    'role'       => $role,
    'status'     => $status,
    'created_at' => $created_at
];

echo json_encode($admin);
exit;
?>