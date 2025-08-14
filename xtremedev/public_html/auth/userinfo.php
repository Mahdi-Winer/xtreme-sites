<?php
// userinfo.php روی SSO (auth.xtremedev.co)
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/shared/inc/database-config.php';

if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
    http_response_code(401);
    echo json_encode(['error' => 'no_token']);
    exit;
}
list(, $token) = explode(' ', $_SERVER['HTTP_AUTHORIZATION'], 2);

$stmt = $mysqli->prepare("SELECT user_id FROM oauth_tokens WHERE access_token=? AND expires_at > NOW() LIMIT 1");
$stmt->bind_param('s', $token);
$stmt->execute();
$stmt->bind_result($user_id);
if (!$stmt->fetch()) {
    http_response_code(401);
    echo json_encode(['error' => 'invalid_token']);
    exit;
}
$stmt->close();

// حالا اطلاعات کاربر رو بده (بدون get_result)
$stmt2 = $mysqli->prepare("SELECT id, fullname, email, phone, created_at FROM users WHERE id=? LIMIT 1");
$stmt2->bind_param('i', $user_id);
$stmt2->execute();
$stmt2->store_result();
if ($stmt2->num_rows !== 1) {
    http_response_code(404);
    echo json_encode(['error' => 'not_found']);
    exit;
}
$stmt2->bind_result($u_id, $u_fullname, $u_email, $u_phone, $u_created_at);
$stmt2->fetch();
$stmt2->close();

$user = [
    'id' => $u_id,
    'fullname' => $u_fullname,
    'email' => $u_email,
    'phone' => $u_phone,
    'created_at' => $u_created_at,
];

echo json_encode($user);
exit;
?>