<?php
// revoke.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/shared/inc/database-config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'invalid_request']); exit; }

$token = trim($_POST['token'] ?? '');
if (!$token) { http_response_code(400); echo json_encode(['error'=>'missing_token']); exit; }

// حذف هر جا که این توکن باشد
$stmt = $mysqli->prepare("DELETE FROM oauth_tokens WHERE access_token=? OR refresh_token=?");
$stmt->bind_param('ss', $token, $token);
$stmt->execute();
$stmt->close();

echo json_encode(['revoked'=>true]);