<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../shared/database-config.php';
require_once __DIR__ . '/../../shared/auth-admin-helper.php';

getAdminInfoOrExit(); // هر نقش ادمین مجاز است

$res = $mysqli->query("SELECT * FROM joinus_projects ORDER BY id ASC");
$out = [];
while($row = $res->fetch_assoc()) $out[] = $row;
echo json_encode($out, JSON_UNESCAPED_UNICODE);