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

$now = date('Y-m-d H:i:s');

$stmt = $mysqli->prepare("SELECT id, type, icon, link, created_at, expires_at FROM notifications WHERE project_id=? AND is_active=1 AND (expires_at IS NULL OR expires_at > ?) ORDER BY created_at DESC");
$stmt->bind_param('is', $project_id, $now);
$stmt->execute();
$stmt->bind_result($id, $type, $icon, $link, $created_at, $expires_at);

$notifications = [];
while($stmt->fetch()) {
    $notifications[] = [
        'id' => $id,
        'type' => $type,
        'icon' => $icon,
        'link' => $link,
        'created_at' => $created_at,
        'expires_at' => $expires_at
    ];
}
$stmt->close();

foreach($notifications as &$n) {
    $stmt = $mysqli->prepare("SELECT title, description FROM notifications_translations WHERE notification_id=? AND lang=? LIMIT 1");
    $stmt->bind_param('is', $n['id'], $lang);
    $stmt->execute();
    $stmt->bind_result($title, $desc);
    if($stmt->fetch()) {
        $n['title'] = $title;
        $n['description'] = $desc;
    } else {
        $n['title'] = '';
        $n['description'] = '';
    }
    $stmt->close();
}
unset($n);

echo json_encode($notifications, JSON_UNESCAPED_UNICODE);
exit;
?>