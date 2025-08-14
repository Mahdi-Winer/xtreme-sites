<?php
require_once __DIR__ . '/../shared/database-config.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: https://xtremedev.co');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Authorization, Content-Type');
    exit(0);
}
header('Access-Control-Allow-Origin: https://xtremedev.co');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'fa';

$stmt = $mysqli->prepare("
    SELECT p.id, p.image, t.title, t.description, t.long_description
    FROM public_projects p
    LEFT JOIN project_translations t ON t.project_id = p.id AND t.lang = ?
    WHERE p.is_active=1 AND p.id=?
    LIMIT 1
");
$stmt->bind_param('si', $lang, $id);
$stmt->execute();

$stmt->bind_result($pid, $image, $title, $description, $long_description);
if ($stmt->fetch()) {
    $project = [
        'id' => $pid,
        'image' => $image,
        'title' => $title,
        'description' => $description,
        'long_description' => $long_description
    ];
    echo json_encode(['project' => $project]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'not found']);
}
$stmt->close();