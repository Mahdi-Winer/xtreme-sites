<?php
// oauth-introspect.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/shared/inc/database-config.php';

function json_error($code, $msg) {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

$shared_secret = getenv('INTROSPECT_SECRET') ?: 'CHANGE_ME_INTROSPECT_SECRET';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error(405, 'invalid_request_method');

$token     = trim($_POST['token'] ?? '');
$timestamp = intval($_POST['ts'] ?? 0);
$signature = trim($_POST['sig'] ?? '');

if (!$token) json_error(400, 'missing_token');

// HMAC (اختیاری ولی توصیه‌شده)
if ($shared_secret) {
    if (!$timestamp || !$signature) json_error(401, 'missing_sig');
    if (abs(time() - $timestamp) > 120) json_error(401, 'ts_window');
    $expected = hash_hmac('sha256', $token . '|' . $timestamp, $shared_secret);
    if (!hash_equals($expected, $signature)) json_error(401, 'bad_sig');
}

// Lookup
$stmt = $mysqli->prepare("SELECT user_id, client_id, scope, expires_at, is_admin FROM oauth_tokens WHERE access_token=? LIMIT 1");
$stmt->bind_param('s', $token);
$stmt->execute();
$stmt->bind_result($user_id, $client_id, $scope, $expires_at, $is_admin);
if (!$stmt->fetch()) {
    echo json_encode(['active' => false]);
    exit;
}
$stmt->close();

$exp_ts = strtotime($expires_at);
echo json_encode([
    'active'   => ($exp_ts > time()),
    'user_id'  => (int)$user_id,
    'client_id'=> (int)$client_id,
    'scope'    => $scope,
    'is_admin' => ((int)$is_admin === 1),
    'exp'      => $exp_ts
]);
exit;