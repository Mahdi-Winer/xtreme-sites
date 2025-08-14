<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../shared/database-config.php';

$result = $mysqli->query("SELECT id, name, domain, created_at FROM projects ORDER BY name");
$projects = [];
while($row = $result->fetch_assoc()) {
    $projects[] = $row;
}
echo json_encode($projects);
exit;
?>