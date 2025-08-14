<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit;

require_once __DIR__ . '/../../shared/client-database-config.php';
require_once __DIR__ . '/../../shared/auth-helper.php';

$user_id = getUserIdFromBearerToken();
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$device      = isset($_POST['device'])      ? trim($_POST['device'])            : null;
$login_time  = isset($_POST['login_time'])  ? trim($_POST['login_time'])        : null;
$logout_time = array_key_exists('logout_time', $_POST) ? trim($_POST['logout_time']) : null;
$playtime    = array_key_exists('playtime', $_POST)    ? trim($_POST['playtime'])    : null;

if (!$device || !$login_time) {
    http_response_code(400);
    echo json_encode(['error' => 'device_and_login_time_required']);
    exit;
}

// مقداردهی درست برای bind_param
$types = 'isssi'; // i: user_id, s: device, s: login_time, s: logout_time, i: playtime

// اگر logout_time یا playtime نبود، باید با NULL مقداردهی کنیم
// توجه: اگر playtime رشته است، نوع داده را به 's' تغییر بده!
if ($playtime === '') $playtime = null;
if ($logout_time === '') $logout_time = null;

// اگر playtime عددی نیست، نوع داده را برای bind_param به 's' تغییر بده:
if (!is_numeric($playtime) && $playtime !== null) $types = 'issss';

$stmt = $mysqli->prepare("INSERT INTO sessions (user_id, device, login_time, logout_time, playtime) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param($types, $user_id, $device, $login_time, $logout_time, $playtime);

if ($stmt->execute()) {
    $session_id = $stmt->insert_id;
    $stmt->close();

    // حالا رکورد جدید را بخوانیم و برگردانیم
    $stmt2 = $mysqli->prepare("SELECT id, user_id, device, login_time, logout_time, playtime FROM sessions WHERE id = ?");
    $stmt2->bind_param('i', $session_id);
    $stmt2->execute();
    $stmt2->bind_result($id, $user_id2, $device2, $login_time2, $logout_time2, $playtime2);
    if ($stmt2->fetch()) {
        $session = [
            'id' => $id,
            'user_id' => $user_id2,
            'device' => $device2,
            'login_time' => $login_time2,
            'logout_time' => $logout_time2,
            'playtime' => $playtime2
        ];
        echo json_encode(['ok' => true, 'session' => $session]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'not_found']);
    }
    $stmt2->close();
} else {
    http_response_code(500);
    echo json_encode(['error' => 'db_error', 'msg' => $mysqli->error]);
}