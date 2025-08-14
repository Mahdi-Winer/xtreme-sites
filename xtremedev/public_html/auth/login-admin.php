<?php
// login-admin.php -- ورود ادمین و صدور توکن
header('Content-Type: application/json');
require_once __DIR__.'/shared/inc/database-config.php';

// فقط POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false, 'error'=>'POST required']);
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    http_response_code(400);
    echo json_encode(['success'=>false, 'error'=>'username and password required']);
    exit;
}

// جستجو در جدول admin_users
$stmt = $mysqli->prepare("SELECT id, password_hash, role, status FROM admin_users WHERE username=? OR email=? LIMIT 1");
$stmt->bind_param('ss', $username, $username);
$stmt->execute();
$stmt->bind_result($admin_id, $password_hash, $role, $status);
if ($stmt->fetch() && $status === 'active' && password_verify($password, $password_hash)) {
    $stmt->close();
    // تولید access_token و refresh_token
    $access_token  = bin2hex(random_bytes(32));
    $refresh_token = bin2hex(random_bytes(40));
    $expires_in    = 3600; // 1 hour
    $exp           = date('Y-m-d H:i:s', time() + $expires_in);

    $admin_client_id = 2; // مقدار client_id مخصوص ادمین (از جدول clients)

    // ذخیره در oauth_tokens با is_admin=1 و client_id=2
    $stmt2 = $mysqli->prepare("INSERT INTO oauth_tokens (access_token, refresh_token, user_id, client_id, expires_at, is_admin, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
    $stmt2->bind_param('ssiss', $access_token, $refresh_token, $admin_id, $admin_client_id, $exp);
    $stmt2->execute();
    $stmt2->close();

    echo json_encode([
        'success'        => true,
        'access_token'   => $access_token,
        'refresh_token'  => $refresh_token,
        'expires_in'     => $expires_in,
        'role'           => $role
    ]);
    exit;
}
$stmt->close();
http_response_code(401);
echo json_encode(['success'=>false, 'error'=>'Invalid credentials']);
exit;
?>