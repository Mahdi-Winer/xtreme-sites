<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://xtremedev.co');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
require_once __DIR__ . '/../../shared/database-config.php';

// --- دریافت Bearer Token ---
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

// --- چک نقش ادمین (superadmin, manager, support) ---
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
$allowed_roles = ['superadmin', 'manager', 'support'];
if ($httpcode != 200 || !$admininfo || empty($admininfo['role']) || !in_array($admininfo['role'], $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['error'=>'forbidden']);
    exit;
}

// --- دریافت پارامترها ---
$ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
$new_status = isset($_POST['status']) ? trim($_POST['status']) : null;
$reply_text = isset($_POST['reply']) ? trim($_POST['reply']) : null;

if (!$ticket_id) {
    http_response_code(400);
    echo json_encode(['error'=>'Invalid ticket id.']);
    exit;
}

// اگر پیام جدید ارسال شده (فرم پاسخ)، ابتدا پیام را ثبت کن و بعد وضعیت را حتماً answered بکن
if ($reply_text !== null && $reply_text !== '') {
    $stmt = $mysqli->prepare("INSERT INTO ticket_messages (ticket_id, sender, message, created_at) VALUES (?, 'admin', ?, NOW())");
    if ($stmt) {
        $stmt->bind_param('is', $ticket_id, $reply_text);
        if($stmt->execute()) {
            // وضعیت همیشه answered شود
            $stmt2 = $mysqli->prepare("UPDATE tickets SET status='answered' WHERE id=?");
            $stmt2->bind_param('i', $ticket_id);
            $stmt2->execute();
            $stmt2->close();

            echo json_encode(['success'=>true, 'message'=>'Reply sent successfully.']);
            exit;
        } else {
            http_response_code(500);
            echo json_encode(['error'=>'Failed to send reply.']);
            exit;
        }
        $stmt->close();
    } else {
        http_response_code(500);
        echo json_encode(['error'=>'DB error.']);
        exit;
    }
}

// اگر فقط وضعیت تغییر داده شده است (و reply ارسال نشده)
$allowed_statuses = ['open','answered','closed'];
if ($new_status && in_array($new_status, $allowed_statuses, true)) {
    $stmt = $mysqli->prepare("UPDATE tickets SET status=? WHERE id=?");
    if ($stmt) {
        $stmt->bind_param('si', $new_status, $ticket_id);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success'=>true, 'message'=>'Ticket status updated successfully.']);
        exit;
    } else {
        http_response_code(500);
        echo json_encode(['error'=>'DB error.']);
        exit;
    }
}

// اگر نه reply و نه status معتبر ارسال نشده
echo json_encode(['error'=>'No action performed.']);
exit;