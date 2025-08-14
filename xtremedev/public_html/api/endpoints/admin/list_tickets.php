<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://xtremedev.co');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
require_once __DIR__ . '/../../shared/database-config.php';

// مقادیر client_id و client_secret را ست کن
define('AUTH_CLIENT_ID', 'admin-panel');
define('AUTH_CLIENT_SECRET', 'KB1UX!X%9MxPF7^hYqpL*hn}~,kdq>4RVtV~F=uW6u_U2HgvFWi?g9*=zpUp40%i%PP751gP2E+5nCaZk#JEzw9xE=E~6M1qqH9*');

// --------- Bearer Token ----------
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

// --------- چک نقش ادمین ---------
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

// --------- پارامترها ---------
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// --------- مرحله 1: خواندن تیکت‌ها ---------
$tickets = [];
$user_ids = [];
$params = [];
$sql = "SELECT id, user_id, subject, status, created_at FROM tickets";
if($search !== '') {
    $sql .= " WHERE subject LIKE ?";
    $params[] = "%$search%";
}
$sql .= " ORDER BY created_at DESC LIMIT 100";

$stmt = $mysqli->prepare($sql);
if ($params) {
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
}
$stmt->execute();
$stmt->bind_result($id, $user_id, $subject, $status, $created_at);
while($stmt->fetch()) {
    $tickets[] = [
        'id'         => $id,
        'user_id'    => $user_id,
        'subject'    => $subject,
        'status'     => $status,
        'created_at' => $created_at
    ];
    if($user_id) $user_ids[$user_id] = true;
}
$stmt->close();

// --------- مرحله 2: گرفتن تعداد پیام هر تیکت ---------
$ticket_msg_counts = [];
if(count($tickets)) {
    $ids = array_column($tickets, 'id');
    $ids_in = implode(',', array_map('intval', $ids));
    $res = $mysqli->query("SELECT ticket_id, COUNT(*) as cnt FROM ticket_messages WHERE ticket_id IN ($ids_in) GROUP BY ticket_id");
    while($row = $res->fetch_assoc()) {
        $ticket_msg_counts[$row['ticket_id']] = intval($row['cnt']);
    }
    $res->free();
}

// --------- مرحله 3: گرفتن اطلاعات کاربران از get_user.php ---------
$user_info_cache = [];
foreach(array_keys($user_ids) as $uid) {
    $ch = curl_init("https://auth.xtremedev.co/api/get_user.php");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'client_id'     => AUTH_CLIENT_ID,
        'client_secret' => AUTH_CLIENT_SECRET,
        'id'            => intval($uid)
    ]);
    $resp = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpcode == 200 && $resp) {
        $info = json_decode($resp, true);
        $user_info_cache[$uid] = [
            'name' => $info['name'] ?? '',
            'email' => $info['email'] ?? ''
        ];
    } else {
        $user_info_cache[$uid] = ['name'=>'', 'email'=>''];
    }
}

// --------- مرحله 4: خروجی نهایی ---------
foreach($tickets as &$tk) {
    $uid = $tk['user_id'];
    $tk['user_name'] = $user_info_cache[$uid]['name'] ?? '';
    $tk['user_email'] = $user_info_cache[$uid]['email'] ?? '';
    $tk['message_count'] = $ticket_msg_counts[$tk['id']] ?? 0;
    unset($tk['user_id']);
}
unset($tk);

echo json_encode(['tickets' => $tickets], JSON_UNESCAPED_UNICODE);
exit;
?>