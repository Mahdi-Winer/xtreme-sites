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
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'fa';

$stmt = $mysqli->prepare("
    SELECT r.id, r.sort_order, rt.name
    FROM roles r
    LEFT JOIN roles_translations rt ON rt.role_id = r.id AND rt.lang = ?
    ORDER BY r.sort_order, r.id
");
$stmt->bind_param('s', $lang);
$stmt->execute();
$stmt->bind_result($id, $sort_order, $name);

$list = [];
while ($stmt->fetch()) {
    $list[] = compact('id', 'sort_order', 'name');
}
echo json_encode($list, JSON_UNESCAPED_UNICODE);
$stmt->close();
?>