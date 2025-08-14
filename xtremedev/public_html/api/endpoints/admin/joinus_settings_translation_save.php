<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../shared/database-config.php';

$data = json_decode(file_get_contents('php://input'), true);
if(!$data || !isset($data['joinus_settings_id'], $data['lang'])) {
    http_response_code(400); echo '{"error":"bad_request"}'; exit;
}
$joinus_settings_id = intval($data['joinus_settings_id']);
$lang = $data['lang'];
$title = $data['title'] ?? '';
$desc = $data['desc'] ?? '';
$rules = $data['rules'] ?? '';
$benefits = $data['benefits'] ?? '';
$logo_url = $data['logo_url'] ?? '';

$stmt = $mysqli->prepare("SELECT id FROM joinus_settings_translations WHERE joinus_settings_id=? AND lang=?");
$stmt->bind_param('is', $joinus_settings_id, $lang);
$stmt->execute();
$stmt->store_result();
if($stmt->num_rows) {
    $stmt->close();
    $stmt2 = $mysqli->prepare("UPDATE joinus_settings_translations SET title=?, `desc`=?, rules=?, benefits=?, logo_url=? WHERE joinus_settings_id=? AND lang=?");
    $stmt2->bind_param('sssssis', $title, $desc, $rules, $benefits, $logo_url, $joinus_settings_id, $lang);
    $stmt2->execute(); $stmt2->close();
} else {
    $stmt->close();
    $stmt2 = $mysqli->prepare("INSERT INTO joinus_settings_translations (joinus_settings_id, lang, title, `desc`, rules, benefits, logo_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt2->bind_param('issssss', $joinus_settings_id, $lang, $title, $desc, $rules, $benefits, $logo_url);
    $stmt2->execute(); $stmt2->close();
}
echo json_encode(['success'=>1]);
?>