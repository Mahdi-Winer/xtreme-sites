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
    echo json_encode(['error'=>'project_id required']);
    exit;
}

// گرفتن لیست چنج‌لاگ‌های فعال این پروژه
$stmt = $mysqli->prepare("SELECT id, version, release_date, order_num FROM changelog WHERE project_id=? AND is_active=1 ORDER BY order_num DESC, release_date DESC, id DESC");
$stmt->bind_param('i', $project_id);
$stmt->execute();
$stmt->bind_result($id, $version, $release_date, $order_num);

$changelogs = [];
while ($stmt->fetch()) {
    $changelogs[] = [
        'id' => $id,
        'version' => $version,
        'release_date' => $release_date,
        'order_num' => $order_num
    ];
}
$stmt->close();

// گرفتن ترجمه هر چنج‌لاگ
foreach ($changelogs as &$c) {
    $stmt = $mysqli->prepare("SELECT title, changes FROM changelog_translations WHERE changelog_id=? AND lang=? LIMIT 1");
    $stmt->bind_param('is', $c['id'], $lang);
    $stmt->execute();
    $stmt->bind_result($title, $changes);
    if ($stmt->fetch()) {
        $c['title'] = $title;
        $c['changes'] = $changes;
    } else {
        $c['title'] = '';
        $c['changes'] = '';
    }
    $stmt->close();
}

echo json_encode($changelogs, JSON_UNESCAPED_UNICODE);
exit;
?>