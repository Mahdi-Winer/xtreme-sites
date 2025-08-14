<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../shared/database-config.php';

$id = intval($_GET['id'] ?? 0);
if(!$id) { http_response_code(400); echo '{"error":"bad_request"}'; exit; }
$mysqli->query("DELETE FROM settings WHERE id=$id");
echo json_encode(['success'=>1]);
?>