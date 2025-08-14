<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
require_once __DIR__ . '/../shared/database-config.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400); echo '{"error":"missing_id"}'; exit;
}

// مقاله اصلی
$stmt = $mysqli->prepare("SELECT id, project_id, thumbnail, created_at FROM articles WHERE id=?");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($aid, $project_id, $thumbnail, $created_at);
if (!$stmt->fetch()) {
    http_response_code(404); echo '{"error":"not_found"}'; exit;
}
$stmt->close();

$article = [
    'id' => $aid,
    'project_id' => $project_id,
    'thumbnail' => $thumbnail,
    'created_at' => $created_at
];

// ترجمه‌ها
$stmt2 = $mysqli->prepare("SELECT lang, title, content, body FROM article_translations WHERE article_id=?");
$stmt2->bind_param('i', $id);
$stmt2->execute();
$stmt2->bind_result($lang, $title, $content, $body);
$translations = [];
while ($stmt2->fetch()) {
    $translations[$lang] = [
        'title' => $title,
        'content' => $content,
        'body' => $body
    ];
}
$stmt2->close();

$article['translations'] = $translations;
echo json_encode($article, JSON_UNESCAPED_UNICODE);
exit;
?>