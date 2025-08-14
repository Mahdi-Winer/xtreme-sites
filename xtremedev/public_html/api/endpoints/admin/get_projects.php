<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../shared/database-config.php';

// دریافت توکن و چک نقش ادمین (مثل همه endpointهای ادمین)
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

// چک نقش فقط برای سوپرادمین و مدیر
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
if ($httpcode != 200 || !$admininfo || empty($admininfo['role']) || !in_array($admininfo['role'], ['superadmin', 'manager'])) {
    http_response_code(403);
    echo json_encode(['error'=>'forbidden']);
    exit;
}

// گرفتن لیست پروژه‌ها
$projects = [];
$res = $mysqli->query("SELECT id, name, domain, created_at FROM projects ORDER BY id DESC");
if($res){
    while ($row = $res->fetch_assoc()) {
        $projects[] = $row;
    }
    $res->free();
}
echo json_encode(['projects' => $projects]);
exit;
?>