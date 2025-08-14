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
$id = intval($_GET['id'] ?? 0);
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'fa';

$stmt = $mysqli->prepare("
    SELECT t.id, t.role_id, t.priority, t.photo,
           tt.name, tt.skill, tt.sub_role, tt.long_bio,
           rt.name AS role_name
    FROM team t
    LEFT JOIN team_translations tt ON tt.team_id = t.id AND tt.lang = ?
    LEFT JOIN roles_translations rt ON rt.role_id = t.role_id AND rt.lang = ?
    WHERE t.id=?
    LIMIT 1
");
$stmt->bind_param('ssi', $lang, $lang, $id);
$stmt->execute();
$stmt->bind_result(
    $id, $role_id, $priority, $photo,
    $name, $skill, $sub_role, $long_bio,
    $role_name
);

if ($stmt->fetch()) {
    $row = [
        'id'        => $id,
        'role_id'   => $role_id,
        'priority'  => $priority,
        'photo'     => $photo,
        'name'      => $name,
        'skill'     => $skill,
        'sub_role'  => $sub_role,
        'long_bio'  => $long_bio,
        'role_name' => $role_name
    ];
    echo json_encode($row, JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(404); echo '{"error":"Not found"}';
}
$stmt->close();
?>