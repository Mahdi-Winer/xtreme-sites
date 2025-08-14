<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://xtremedev.co');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit;
require_once __DIR__ . '/../shared/database-config.php';

$lang = $_GET['lang'] ?? 'fa';
$stmt = $mysqli->prepare("SELECT p.id, t.title, t.description
    FROM joinus_projects p
    JOIN joinus_projects_translations t ON t.project_id = p.id AND t.lang = ?
    WHERE p.is_active = 1
    ORDER BY p.id ASC");
$stmt->bind_param('s', $lang);
$stmt->execute();
$stmt->bind_result($id, $title, $desc);

$out = [];
while ($stmt->fetch()) {
    $out[] = [
        'id' => $id,
        'title' => $title,
        'description' => $desc
    ];
}
$stmt->close();
echo json_encode($out, JSON_UNESCAPED_UNICODE);
exit;
?>