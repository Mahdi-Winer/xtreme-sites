<?php
require_once __DIR__ . '/../shared/database-config.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: https://xtremedev.co');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Authorization, Content-Type');
    exit(0);
}
header('Access-Control-Allow-Origin: https://xtremedev.co');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

// خواندن زبان از ورودی
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'fa';

// کوئری گرفتن لیست پروژه‌های فعال با ترجمه مناسب
$stmt = $mysqli->prepare("
    SELECT p.id, p.image, t.title, t.description
    FROM public_projects p
    LEFT JOIN project_translations t ON t.project_id = p.id AND t.lang = ?
    WHERE p.is_active=1
    ORDER BY p.id DESC
");
$stmt->bind_param('s', $lang);
$stmt->execute();

// --- اصلاح این بخش (بدون get_result) ---
$stmt->bind_result($id, $image, $title, $description);
$list = [];
while ($stmt->fetch()) {
    $list[] = [
        'id'          => $id,
        'image'       => $image,
        'title'       => $title,
        'description' => $description
    ];
}
$stmt->close();

echo json_encode(['projects' => $list]);