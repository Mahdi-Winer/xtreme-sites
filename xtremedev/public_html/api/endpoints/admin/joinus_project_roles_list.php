<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../shared/database-config.php';
// ---- احراز هویت مشابه بالا ----
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
if (!$access_token) { http_response_code(401); echo '{"error":"unauthorized"}'; exit; }
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://auth.xtremedev.co/admininfo.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [ "Authorization: Bearer $access_token" ]);
curl_setopt($ch, CURLOPT_TIMEOUT, 8);
$resp = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$admininfo = @json_decode($resp, true);
if ($httpcode != 200 || !$admininfo || empty($admininfo['role']) || !in_array($admininfo['role'], ['superadmin','manager','support','read_only'])) {
    http_response_code(403); echo '{"error":"forbidden"}'; exit;
}
// ---- خروجی ----
$res = $mysqli->query("SELECT id, project_id, is_active FROM joinus_project_roles ORDER BY id DESC");
$list = [];
while($row = $res->fetch_assoc()) $list[] = $row;
echo json_encode($list, JSON_UNESCAPED_UNICODE);
?>