<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://xtremedev.co');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
require_once __DIR__ . '/../../shared/database-config.php';

// ==== احراز هویت Bearer Token ====
function getBearerToken() {
    $header = '';
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) $header = $_SERVER['HTTP_AUTHORIZATION'];
    elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    elseif (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) $header = $headers['Authorization'];
    }
    if ($header && preg_match('/Bearer\s+(\S+)/', $header, $matches)) {
        return $matches[1];
    }
    return null;
}
$access_token = getBearerToken();
if (!$access_token) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

// ==== چک نقش ادمین ====
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://auth.xtremedev.co/admininfo.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_token"
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 8);
$resp = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$admininfo = @json_decode($resp, true);
$allowed_roles = ['superadmin', 'manager', 'support', 'read_only'];
if ($httpcode != 200 || !$admininfo || empty($admininfo['role']) || !in_array($admininfo['role'], $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['error'=>'forbidden']);
    exit;
}

// ==== دریافت شناسه تیکت ====
$ticket_id = intval($_GET['id'] ?? 0);
if (!$ticket_id) {
    http_response_code(400);
    echo json_encode(['error'=>'Invalid ticket id.']);
    exit;
}

// ==== گرفتن اطلاعات تیکت ====
$stmt = $mysqli->prepare("SELECT id, user_id, subject, status, created_at FROM tickets WHERE id=? LIMIT 1");
$stmt->bind_param('i', $ticket_id);
$stmt->execute();
$stmt->bind_result($tid, $user_id, $subject, $status, $created_at);
if(!$stmt->fetch()) {
    $stmt->close();
    http_response_code(404);
    echo json_encode(['error'=>'Ticket not found.']);
    exit;
}
$stmt->close();

// ==== گرفتن اطلاعات کاربر از auth ====
$user_name  = '';
$user_email = '';
if ($user_id) {
    $ch = curl_init("https://auth.xtremedev.co/api/get_user.php");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'client_id'     => 'admin-panel',
        'client_secret' => 'KB1UX!X%9MxPF7^hYqpL*hn}~,kdq>4RVtV~F=uW6u_U2HgvFWi?g9*=zpUp40%i%PP751gP2E+5nCaZk#JEzw9xE=E~6M1qqH9*',
        'id'            => intval($user_id)
    ]);
    $user_resp = curl_exec($ch);
    $user_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($user_http == 200 && $user_resp) {
        $info = json_decode($user_resp, true);
        $user_name  = $info['name'] ?? '';
        $user_email = $info['email'] ?? '';
    }
}

// ==== گرفتن پیام‌ها ====
$msgs = [];
$stmt = $mysqli->prepare("SELECT id, sender, message, created_at FROM ticket_messages WHERE ticket_id=? ORDER BY created_at ASC");
$stmt->bind_param('i', $ticket_id);
$stmt->execute();
$stmt->bind_result($mid, $sender, $msg_text, $msg_created);
while ($stmt->fetch()) {
    $msgs[] = [
        'id' => $mid,
        'sender' => $sender,
        'message' => $msg_text,
        'created_at' => $msg_created
    ];
}
$stmt->close();

// ==== خروجی نهایی ====
echo json_encode([
    'ticket' => [
        'id'         => $tid,
        'subject'    => $subject,
        'status'     => $status,
        'created_at' => $created_at,
        'user_name'  => $user_name,
        'user_email' => $user_email
    ],
    'messages' => $msgs
], JSON_UNESCAPED_UNICODE);
exit;