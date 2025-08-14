<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
require_once __DIR__ . '/../shared/database-config.php';

$project_id = $_GET['project_id'];
$lang = $_GET['lang'] ?? 'fa';
if (!$project_id) { http_response_code(400); echo '{"error":"missing_project_id"}'; exit; }

$stmt = $mysqli->prepare("
    SELECT a.id, a.project_id, a.thumbnail, a.created_at, t.title, t.content, t.body
    FROM articles a
    LEFT JOIN article_translations t ON a.id = t.article_id AND t.lang = ?
    WHERE a.project_id = ?
    ORDER BY a.created_at DESC
");
$stmt->bind_param('si', $lang, $project_id);
$stmt->execute();

// get_result نداری، پس با bind_result بخوان
$stmt->bind_result($id, $project_id, $thumbnail, $created_at, $title, $content, $body);
$articles = [];
while($stmt->fetch()) {
    $articles[] = [
        'id' => $id,
        'project_id' => $project_id,
        'thumbnail' => $thumbnail,
        'created_at' => $created_at,
        'title' => $title,
        'content' => $content,
        'body' => $body
    ];
}
echo json_encode($articles);
$stmt->close();
exit;
?>