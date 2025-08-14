<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
require_once __DIR__ . '/../shared/database-config.php';

$project_id = intval($_GET['project_id'] ?? 0);
$lang = $_GET['lang'] ?? 'fa';

if(!$project_id) {
    http_response_code(400);
    echo json_encode(['error'=>'project_id is required']);
    exit;
}

$stmt = $mysqli->prepare("SELECT id, image_path, slide_order FROM slider_data WHERE project_id=? AND is_active=1 ORDER BY slide_order, id");
$stmt->bind_param('i', $project_id);
$stmt->execute();
$stmt->bind_result($id, $image_path, $slide_order);

$sliders = [];
while($stmt->fetch()) {
    $sliders[] = [
        'id' => $id,
        'image_path' => $image_path,
        'slide_order' => $slide_order
    ];
}
$stmt->close();

foreach($sliders as &$s) {
    $stmt = $mysqli->prepare("SELECT title, description FROM slider_translations WHERE slider_id=? AND lang=? LIMIT 1");
    $stmt->bind_param('is', $s['id'], $lang);
    $stmt->execute();
    $stmt->bind_result($title, $desc);
    if($stmt->fetch()) {
        $s['title'] = $title;
        $s['description'] = $desc;
    } else {
        $s['title'] = '';
        $s['description'] = '';
    }
    $stmt->close();
}
unset($s);

echo json_encode($sliders, JSON_UNESCAPED_UNICODE);
exit;
?>