<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../shared/database-config.php';
// ---- احراز هویت Bearer Token ادمین ---- (کد بالا)
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
if ($httpcode != 200 || !$admininfo || empty($admininfo['role']) || !in_array($admininfo['role'], ['superadmin','manager','support'])) {
    http_response_code(403); echo '{"error":"forbidden"}'; exit;
}
// ---- داده ها ----
$data = json_decode(file_get_contents('php://input'), true);
$id = intval($data['id'] ?? 0);
$is_active = isset($data['is_active']) ? intval($data['is_active']) : 1;
$lang = $data['lang'] ?? 'fa';
$title = $data['title'] ?? '';
$desc = $data['description'] ?? '';
if(!$title) { http_response_code(400); echo '{"error":"bad_request"}'; exit; }

if($id) {
    $stmt = $mysqli->prepare("UPDATE joinus_projects SET is_active=? WHERE id=?");
    $stmt->bind_param('ii', $is_active, $id);
    $stmt->execute(); $stmt->close();
    // ترجمه
    $stmt = $mysqli->prepare("SELECT id FROM joinus_projects_translations WHERE project_id=? AND lang=?");
    $stmt->bind_param('is', $id, $lang);
    $stmt->execute();
    $stmt->store_result();
    if($stmt->num_rows) {
        $stmt->close();
        $stmt2 = $mysqli->prepare("UPDATE joinus_projects_translations SET title=?, description=? WHERE project_id=? AND lang=?");
        $stmt2->bind_param('ssis', $title, $desc, $id, $lang);
        $stmt2->execute(); $stmt2->close();
    } else {
        $stmt->close();
        $stmt2 = $mysqli->prepare("INSERT INTO joinus_projects_translations (project_id, lang, title, description) VALUES (?, ?, ?, ?)");
        $stmt2->bind_param('isss', $id, $lang, $title, $desc);
        $stmt2->execute(); $stmt2->close();
    }
    echo json_encode(['success'=>1,'id'=>$id]);
} else {
    $stmt = $mysqli->prepare("INSERT INTO joinus_projects (is_active) VALUES (?)");
    $stmt->bind_param('i', $is_active);
    $stmt->execute();
    $id = $mysqli->insert_id;
    $stmt->close();
    $stmt2 = $mysqli->prepare("INSERT INTO joinus_projects_translations (project_id, lang, title, description) VALUES (?, ?, ?, ?)");
    $stmt2->bind_param('isss', $id, $lang, $title, $desc);
    $stmt2->execute(); $stmt2->close();
    echo json_encode(['success'=>1,'id'=>$id]);
}
?>