<?php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: https://xtremedev.co');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Authorization, Content-Type');
    exit(0);
}
header('Access-Control-Allow-Origin: https://xtremedev.co');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
require_once __DIR__ . '/../shared/database-config.php';

$lang = $_GET['lang'] ?? 'fa';

$stmt = $mysqli->prepare("SELECT id FROM joinus_settings LIMIT 1");
$stmt->execute();
$stmt->bind_result($joinus_id);
if(!$stmt->fetch()) {
    echo json_encode(['error'=>'no joinus_settings']);
    exit;
}
$stmt->close();

$stmt = $mysqli->prepare("SELECT title, `desc`, rules, benefits, logo_url FROM joinus_settings_translations WHERE joinus_settings_id=? AND lang=? LIMIT 1");
$stmt->bind_param('is', $joinus_id, $lang);
$stmt->execute();
$stmt->bind_result($title, $desc, $rules, $benefits, $logo_url);
if($stmt->fetch()) {
    echo json_encode([
        'title'    => $title,
        'desc'     => $desc,
        'rules'    => $rules,
        'benefits' => $benefits,
        'logo_url' => $logo_url
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['error'=>'not found']);
}
$stmt->close();
?>