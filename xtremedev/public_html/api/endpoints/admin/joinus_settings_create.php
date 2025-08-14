<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../shared/database-config.php';

$mysqli->query("INSERT INTO joinus_settings () VALUES ()");
$id = $mysqli->insert_id;
echo json_encode(['success'=>1, 'id'=>$id]);
?>