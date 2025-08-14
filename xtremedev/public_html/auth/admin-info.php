<?php
// admin-userinfo.php
header('Content-Type: application/json');
require_once __DIR__.'/shared/inc/database-config.php';

// چک دسترسی: فقط ادمین‌های مرکزی با توکن معتبر
require_once __DIR__.'/shared/auth-helper.php';
$admin_id = getAdminIdFromBearerToken();
if (!$admin_id) {
    http_response_code(401);
    echo json_encode(['error'=>'unauthorized']);
    exit;
}

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    echo json_encode(['error'=>'missing_id']);
    exit;
}

$stmt = $mysqli->prepare("SELECT id, username, email, role, status, created_at FROM admin_users WHERE id=? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows !== 1) {
    http_response_code(404);
    echo json_encode(['error'=>'not_found']);
    exit;
}
$stmt->bind_result($id, $username, $email, $role, $status, $created_at);
$stmt->fetch();
$stmt->close();

echo json_encode([
    'id'         => $id,
    'username'   => $username,
    'email'      => $email,
    'role'       => $role,
    'status'     => $status,
    'created_at' => $created_at
]);
exit;
?>