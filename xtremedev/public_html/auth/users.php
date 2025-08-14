<?php
// api/users.php
header('Content-Type: application/json');
require_once __DIR__.'/../shared/inc/database-config.php';
require_once __DIR__.'/../shared/auth-helper.php';

// فقط ادمین معتبر اجازه دارد
$admin_id = getAdminIdFromBearerToken();
if (!$admin_id) {
    http_response_code(401);
    echo json_encode(['error'=>'unauthorized']);
    exit;
}

// خروجی همه کاربران (users)
$res = $mysqli->query("SELECT id, email, phone, created_at, status, display_name FROM users");
$users = [];
while($row = $res->fetch_assoc()) $users[] = $row;

echo json_encode($users);
exit;
?>