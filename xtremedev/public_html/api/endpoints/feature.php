<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit;
require_once __DIR__ . '/../shared/database-config.php';

$feature_id = intval($_GET['id'] ?? 0);
$lang = $_GET['lang'] ?? 'fa';

if(!$feature_id) {
    http_response_code(400);
    echo json_encode(['error'=>'id is required']);
    exit;
}

// گرفتن فیچر فقط با id
$stmt = $mysqli->prepare("SELECT id, image_path, feature_order, badge, badge_color, is_active FROM features WHERE id=? AND is_active=1 LIMIT 1");
$stmt->bind_param('i', $feature_id);
$stmt->execute();
$stmt->bind_result($id, $image_path, $feature_order, $badge, $badge_color, $is_active);

if($stmt->fetch()) {
    $feature = [
        'id' => $id,
        'image_path' => $image_path,
        'feature_order' => $feature_order,
        'badge' => $badge,
        'badge_color' => $badge_color
    ];
} else {
    http_response_code(404);
    echo json_encode(['error'=>'feature not found']);
    $stmt->close();
    exit;
}
$stmt->close();

// ترجمه مربوطه
$stmt = $mysqli->prepare("SELECT title, description, long_description FROM features_translations WHERE feature_id=? AND lang=? LIMIT 1");
$stmt->bind_param('is', $feature['id'], $lang);
$stmt->execute();
$stmt->bind_result($title, $desc, $long_desc);
if($stmt->fetch()) {
    $feature['title'] = $title;
    $feature['description'] = $desc;
    $feature['long_description'] = $long_desc;
} else {
    $feature['title'] = '';
    $feature['description'] = '';
    $feature['long_description'] = '';
}
$stmt->close();

echo json_encode($feature, JSON_UNESCAPED_UNICODE);
exit;
?>