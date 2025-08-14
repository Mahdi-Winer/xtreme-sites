<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../shared/database-config.php';

$data = json_decode(file_get_contents('php://input'), true);
if(!$data || !isset($data['settings_id'], $data['lang'])) {
    http_response_code(400); echo '{"error":"bad_request"}'; exit;
}
$settings_id = intval($data['settings_id']);
$lang = $data['lang'];
$site_title = $data['site_title'] ?? '';
$site_intro = $data['site_intro'] ?? '';
$logo_url = $data['logo_url'] ?? '';

$stmt = $mysqli->prepare("SELECT id FROM settings_translations WHERE settings_id=? AND lang=?");
$stmt->bind_param('is', $settings_id, $lang);
$stmt->execute();
$stmt->store_result();
if($stmt->num_rows) {
    $stmt->close();
    $stmt2 = $mysqli->prepare("UPDATE settings_translations SET site_title=?, site_intro=?, logo_url=? WHERE settings_id=? AND lang=?");
    $stmt2->bind_param('sss', $site_title, $site_intro, $logo_url, $settings_id, $lang);
    $stmt2->execute(); $stmt2->close();
} else {
    $stmt->close();
    $stmt2 = $mysqli->prepare("INSERT INTO settings_translations (settings_id, lang, site_title, site_intro, logo_url) VALUES (?, ?, ?, ?, ?)");
    $stmt2->bind_param('issss', $settings_id, $lang, $site_title, $site_intro, $logo_url);
    $stmt2->execute(); $stmt2->close();
}
echo json_encode(['success'=>1]);
?>