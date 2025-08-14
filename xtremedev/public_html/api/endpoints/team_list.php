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

$sql = "
    SELECT t.id, t.role_id, t.priority, t.photo,
           tt.name, tt.skill, tt.sub_role, tt.long_bio,
           rt.name AS role_name, r.sort_order
    FROM team t
    LEFT JOIN team_translations tt ON tt.team_id = t.id AND tt.lang = ?
    LEFT JOIN roles r ON t.role_id = r.id
    LEFT JOIN roles_translations rt ON rt.role_id = r.id AND rt.lang = ?
    ORDER BY r.sort_order ASC, t.priority ASC, t.id ASC
";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('ss', $lang, $lang);
$stmt->execute();
$stmt->bind_result(
    $id, $role_id, $priority, $photo,
    $name, $skill, $sub_role, $long_bio,
    $role_name, $sort_order
);

$list = [];
while($stmt->fetch()) {
    $list[] = [
        'id'         => $id,
        'role_id'    => $role_id,
        'priority'   => $priority,
        'photo'      => $photo,
        'name'       => $name,
        'skill'      => $skill,
        'sub_role'   => $sub_role,
        'long_bio'   => $long_bio,
        'role_name'  => $role_name,
        'sort_order' => $sort_order
    ];
}
echo json_encode($list, JSON_UNESCAPED_UNICODE);
$stmt->close();
?>