<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../shared/database-config.php';

$res = $mysqli->query("SELECT id, created_at, updated_at FROM settings");
$list = [];
while($row = $res->fetch_assoc()) $list[] = $row;
echo json_encode($list);
?>