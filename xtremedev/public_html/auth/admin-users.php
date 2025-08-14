<?php
// admin-users.php
header('Content-Type: application/json');
require_once __DIR__.'/shared/inc/database-config.php';
require_once __DIR__.'/shared/auth-helper.php';

$admin_id = getAdminIdFromBearerToken();
if (!$admin_id) {
    http_response_code(401);
    echo json_encode(['error'=>'unauthorized']);
    exit;
}

$res = $mysqli->query("SELECT id, username, email, role, status, created_at FROM admin_users");
$out = [];
while($row = $res->fetch_assoc()) $out[] = $row;
echo json_encode($out);
exit;
?>