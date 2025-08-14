<?php
header('Content-Type: application/json');
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
if ($httpcode != 200 || !$admininfo || empty($admininfo['role']) || !in_array($admininfo['role'], ['superadmin', 'manager', 'support'])) {
    http_response_code(403); echo json_encode(['error'=>'forbidden']); exit;
}

// ---- افزودن عضو تیم ----
$data = json_decode(file_get_contents('php://input'), true);
if(!$data || !isset($data['role_id']) || !isset($data['translations'])) {
    http_response_code(400); echo '{"error":"bad_request"}'; exit;
}
$role_id = intval($data['role_id']);
$priority = intval($data['priority'] ?? 1);
$photo = $data['photo'] ?? '';
$stmt = $mysqli->prepare("INSERT INTO team (role_id, priority, photo) VALUES (?, ?, ?)");
$stmt->bind_param('iis', $role_id, $priority, $photo);
if(!$stmt->execute()) {
    http_response_code(500); echo '{"error":"db_error"}'; exit;
}
$team_id = $mysqli->insert_id;
$stmt->close();

foreach($data['translations'] as $lang => $t) {
    $name = $t['name'] ?? '';
    $skill = $t['skill'] ?? '';
    $sub_role = $t['sub_role'] ?? '';
    $long_bio = $t['long_bio'] ?? '';
    $stmt2 = $mysqli->prepare("INSERT INTO team_translations (team_id, lang, name, skill, sub_role, long_bio) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt2->bind_param('isssss', $team_id, $lang, $name, $skill, $sub_role, $long_bio);
    $stmt2->execute(); $stmt2->close();
}
echo json_encode(['success'=>1, 'id'=>$team_id]);
exit;
?>