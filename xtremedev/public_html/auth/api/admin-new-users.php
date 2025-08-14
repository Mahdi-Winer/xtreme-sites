<?php
// admin-new-users.php - قرار بگیرد روی سرور مرکزی (auth.xtremedev.co)

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/inc/database-config.php';

// 1. دریافت client_id و client_secret (از هدر یا POST/GET)
$client_id = '';
$client_secret = '';
if (isset($_SERVER['HTTP_CLIENT_ID'])) $client_id = $_SERVER['HTTP_CLIENT_ID'];
if (isset($_SERVER['HTTP_CLIENT_SECRET'])) $client_secret = $_SERVER['HTTP_CLIENT_SECRET'];
if (isset($_POST['client_id'])) $client_id = $_POST['client_id'];
if (isset($_POST['client_secret'])) $client_secret = $_POST['client_secret'];
if (isset($_GET['client_id'])) $client_id = $_GET['client_id'];
if (isset($_GET['client_secret'])) $client_secret = $_GET['client_secret'];

// 2. چک اعتبار کلاینت
if (!$client_id || !$client_secret) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized (missing client_id/client_secret)']);
    exit;
}
$stmt = $mysqli->prepare("SELECT 1 FROM clients WHERE client_id=? AND client_secret=? LIMIT 1");
$stmt->bind_param('ss', $client_id, $client_secret);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized (invalid client_id/client_secret)']);
    exit;
}
$stmt->close();

// 3. واکشی کاربران جدید
$limit = 20;
$stmt = $mysqli->prepare("SELECT id, email, fullname, phone, created_at FROM users ORDER BY created_at DESC LIMIT ?");
$stmt->bind_param('i', $limit);
$stmt->execute();
// --- استفاده از bind_result و حلقه معمولی ---
$stmt->bind_result($id, $email, $fullname, $phone, $created_at);
$users = [];
while ($stmt->fetch()) {
    $users[] = [
        'id'         => $id,
        'email'      => $email,
        'fullname'   => $fullname,
        'phone'      => $phone,
        'created_at' => $created_at
    ];
}
$stmt->close();

echo json_encode($users, JSON_UNESCAPED_UNICODE);
exit;