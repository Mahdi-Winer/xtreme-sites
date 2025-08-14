<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../shared/database-config.php';
require_once __DIR__ . '/../../shared/auth-admin-helper.php';

getAdminInfoOrExit(); // همه نقش‌های ادمین

$project_id = intval($_GET['project_id'] ?? 0);
$where = $project_id ? "WHERE project_id=$project_id" : "";
$res = $mysqli->query("SELECT * FROM joinus_project_roles $where ORDER BY id ASC");
$out = [];
while($row = $res->fetch_assoc()) $out[] = $row;
echo json_encode($out, JSON_UNESCAPED_UNICODE);