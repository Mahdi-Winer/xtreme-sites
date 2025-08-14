<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://xtremedev.co');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit;
require_once __DIR__ . '/../shared/database-config.php';

$lang = $_GET['lang'] ?? 'fa';
$stmt = $mysqli->prepare("SELECT r.id, r.project_id, t.role_title, t.role_desc
    FROM joinus_project_roles r
    JOIN joinus_project_roles_translations t ON t.role_id = r.id AND t.lang = ?
    WHERE r.is_active=1
    ORDER BY r.project_id ASC, r.id ASC");
$stmt->bind_param('s', $lang);
$stmt->execute();
$stmt->bind_result($id, $project_id, $role_title, $role_desc);

$out = [];
while ($stmt->fetch()) {
    $out[] = [
        'id' => $id,
        'project_id' => $project_id,
        'role_title' => $role_title,
        'role_desc' => $role_desc
    ];
}
$stmt->close();
echo json_encode($out, JSON_UNESCAPED_UNICODE);
exit;
?>