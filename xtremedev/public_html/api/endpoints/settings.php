<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit;

require_once __DIR__ . '/../shared/database-config.php';

$project_id = intval($_GET['project_id'] ?? 0);
$lang = $_GET['lang'] ?? 'fa';

if(!$project_id) {
    http_response_code(400);
    echo json_encode(['error'=>'project_id is required']);
    exit;
}

// گرفتن رکورد settings برای پروژه
$stmt = $mysqli->prepare("SELECT id FROM settings WHERE project_id=? LIMIT 1");
$stmt->bind_param('i', $project_id);
$stmt->execute();
$stmt->bind_result($settings_id);
if(!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error'=>'settings not found']);
    exit;
}
$stmt->close();

// گرفتن ترجمه با فیلد about_us_html
$stmt = $mysqli->prepare("SELECT site_title, site_intro, logo_url, about_us FROM settings_translations WHERE settings_id=? AND lang=? LIMIT 1");
$stmt->bind_param('is', $settings_id, $lang);
$stmt->execute();
$stmt->bind_result($site_title, $site_intro, $logo_url, $about_us_html);
if($stmt->fetch()) {
    $result = [
        'project_id' => $project_id,
        'lang' => $lang,
        'site_title' => $site_title,
        'site_intro' => $site_intro,
        'logo_url' => $logo_url,
        'about_us_html' => $about_us_html
    ];
    $stmt->close();
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}
$stmt->close();

// اگر ترجمه نبود، یک ترجمه دیگر را به عنوان دِفالت بده
$stmt = $mysqli->prepare("SELECT lang, site_title, site_intro, logo_url, about_us FROM settings_translations WHERE settings_id=? LIMIT 1");
$stmt->bind_param('i', $settings_id);
$stmt->execute();
$stmt->bind_result($def_lang, $def_title, $def_intro, $def_logo, $def_about_us_html);
if($stmt->fetch()) {
    $result = [
        'project_id' => $project_id,
        'lang' => $def_lang,
        'site_title' => $def_title,
        'site_intro' => $def_intro,
        'logo_url' => $def_logo,
        'about_us_html' => $def_about_us_html
    ];
    $stmt->close();
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}
$stmt->close();

http_response_code(404);
echo json_encode(['error'=>'settings translation not found']);
exit;