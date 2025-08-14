<?php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Authorization, Content-Type');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    exit;
}
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
require_once __DIR__ . '/../../shared/database-config.php';
require_once __DIR__ . '/../../shared/auth-admin-helper.php';

getAdminInfoOrExit();

$request_id = intval($_GET['id'] ?? 0);
$lang = isset($_GET['lang']) && in_array($_GET['lang'], ['fa','en']) ? $_GET['lang'] : 'fa';

// نمایش یک درخواست خاص
if ($request_id) {
    $sql = "SELECT
      j.*, 
      p.is_active AS project_is_active,
      pt.title AS project_title,
      r.is_active AS role_is_active,
      rt.role_title
    FROM joinus_requests j
    LEFT JOIN joinus_projects p ON j.project_id = p.id
    LEFT JOIN joinus_projects_translations pt ON pt.project_id = p.id AND pt.lang = ?
    LEFT JOIN joinus_project_roles r ON j.role_id = r.id
    LEFT JOIN joinus_project_roles_translations rt ON rt.role_id = r.id AND rt.lang = ?
    WHERE j.id=? LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ssi', $lang, $lang, $request_id);
    $stmt->execute();
    $stmt->store_result();
    $meta = $stmt->result_metadata();
    if($meta){
        $fields = [];
        $row = [];
        while ($field = $meta->fetch_field()) {
            $fields[] = &$row[$field->name];
        }
        call_user_func_array([$stmt, 'bind_result'], $fields);

        if($stmt->fetch()) {
            $result = [];
            foreach($row as $k => $v) $result[$k] = $v;
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['error'=>'not_found']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['error'=>'db_metadata']);
    }
    $stmt->close();
    exit;
}

// لیست کامل (با سرچ اختیاری)
$search = trim($_GET['search'] ?? '');
$where = "1";
$params = [];
$types = "";

if($search !== "") {
    $where .= " AND (j.fullname LIKE ? OR j.email LIKE ? OR j.id=?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = intval($search);
    $types .= "ssi";
}

$sql = "SELECT 
  j.id, j.fullname, j.email, j.skills, j.status, j.created_at,
  pt.title AS project_title,
  rt.role_title
FROM joinus_requests j
LEFT JOIN joinus_projects_translations pt ON pt.project_id = j.project_id AND pt.lang = ?
LEFT JOIN joinus_project_roles_translations rt ON rt.role_id = j.role_id AND rt.lang = ?
WHERE $where
ORDER BY j.id DESC
LIMIT 250";

if($search !== "") {
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ssssi', $lang, $lang, $params[0], $params[1], $params[2]);
} else {
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ss', $lang, $lang);
}
$stmt->execute();
$stmt->store_result();

$meta = $stmt->result_metadata();
if($meta){
    $fields = [];
    $row = [];
    while ($field = $meta->fetch_field()) {
        $fields[] = &$row[$field->name];
    }
    call_user_func_array([$stmt, 'bind_result'], $fields);
    $out = [];
    while ($stmt->fetch()) {
        $item = [];
        foreach($row as $k => $v) $item[$k] = $v;
        $out[] = $item;
    }
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([]);
}
$stmt->close();