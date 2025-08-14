<?php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: https://xtremedev.co');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Authorization, Content-Type');
    exit(0);
}
header('Access-Control-Allow-Origin: https://xtremedev.co');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
require_once __DIR__ . '/../../shared/database-config.php';

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
    http_response_code(401); echo json_encode(['error' => 'unauthorized']); exit;
}
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://auth.xtremedev.co/admininfo.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $access_token"]);
curl_setopt($ch, CURLOPT_TIMEOUT, 8);
$resp = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$admininfo = @json_decode($resp, true);
if ($httpcode != 200 || !$admininfo || empty($admininfo['role']) || !in_array($admininfo['role'], ['superadmin', 'manager'])) {
    http_response_code(403); echo json_encode(['error'=>'forbidden']); exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if(!$data || !isset($data['id']) || !isset($data['sort_order']) || !isset($data['translations'])) {
    http_response_code(400); echo '{"error":"bad_request"}'; exit;
}
$role_id = intval($data['id']);
$sort_order = intval($data['sort_order']);
$stmt = $mysqli->prepare("UPDATE roles SET sort_order=? WHERE id=?");
$stmt->bind_param('ii', $sort_order, $role_id);
$stmt->execute();
$stmt->close();

foreach($data['translations'] as $lang => $t) {
    $name = $t['name'] ?? '';
    $stmt2 = $mysqli->prepare("SELECT 1 FROM roles_translations WHERE role_id=? AND lang=?");
    $stmt2->bind_param('is', $role_id, $lang);
    $stmt2->execute();
    $stmt2->store_result();
    $exists = $stmt2->num_rows > 0;
    $stmt2->close();

    if($exists){
        $stmt3 = $mysqli->prepare("UPDATE roles_translations SET name=? WHERE role_id=? AND lang=?");
        $stmt3->bind_param('sis', $name, $role_id, $lang);
        $stmt3->execute(); $stmt3->close();
    } else {
        $stmt3 = $mysqli->prepare("INSERT INTO roles_translations (role_id, lang, name) VALUES (?, ?, ?)");
        $stmt3->bind_param('iss', $role_id, $lang, $name);
        $stmt3->execute(); $stmt3->close();
    }
}
echo json_encode(['success'=>1, 'id'=>$role_id]);
exit;
?>