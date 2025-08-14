<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://xtremedev.co');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit;
require_once __DIR__ . '/../shared/database-config.php';
require_once __DIR__ . '/../shared/auth-helper.php';

$lang = isset($_GET['lang']) && in_array($_GET['lang'], ['fa','en']) ? $_GET['lang'] : 'fa';

$user_id = getUserIdFromBearerToken();
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error'=>'unauthorized']);
    exit;
}

$request_id = intval($_GET['id'] ?? 0);
if (!$request_id) {
    http_response_code(400);
    echo json_encode(['error'=>'id is required']);
    exit;
}

$sql = "SELECT
  j.id, j.project_id, j.role_id, j.fullname, j.email, j.skills, j.`desc`, j.cv_file, j.created_at, j.user_id, j.status, j.updated_at, j.admin_note,
  p.is_active AS project_is_active,
  pt.title AS project_title, pt.description AS project_desc,
  r.project_id AS role_project_id, r.is_active AS role_is_active,
  rt.role_title, rt.role_desc
FROM joinus_requests j
LEFT JOIN joinus_projects p ON j.project_id = p.id
LEFT JOIN joinus_projects_translations pt ON pt.project_id = p.id AND pt.lang = ?
LEFT JOIN joinus_project_roles r ON j.role_id = r.id
LEFT JOIN joinus_project_roles_translations rt ON rt.role_id = r.id AND rt.lang = ?
WHERE j.id = ? AND j.user_id = ?
LIMIT 1";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param('ssii', $lang, $lang, $request_id, $user_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result(
    $id, $project_id, $role_id, $fullname, $email, $skills, $desc, $cv_file, $created_at, $user_id2, $status, $updated_at, $admin_note,
    $project_is_active,
    $project_title, $project_desc,
    $role_project_id, $role_is_active,
    $role_title, $role_desc
);

if($stmt->fetch()) {
    $out = [
        'id'            => $id,
        'project_id'    => $project_id,
        'project_is_active' => $project_is_active,
        'project_title' => $project_title,
        'project_desc'  => $project_desc,
        'role_id'       => $role_id,
        'role_project_id' => $role_project_id,
        'role_is_active' => $role_is_active,
        'role_title'    => $role_title,
        'role_desc'     => $role_desc,
        'fullname'      => $fullname,
        'email'         => $email,
        'skills'        => $skills,
        'desc'          => $desc,
        'cv_file'       => $cv_file,
        'created_at'    => $created_at,
        'user_id'       => $user_id2,
        'status'        => $status,
        'updated_at'    => $updated_at,
        'admin_note'    => $admin_note
    ];
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(404);
    echo json_encode(['error'=>'not_found']);
}
$stmt->close();