<?php
require_once __DIR__ . '/../../shared/database-config.php';
header('Content-Type: application/json');

$headers = getallheaders();
if (!isset($headers['Authorization']) || !preg_match('/Bearer\s+(.+)/', $headers['Authorization'], $m)) {
    http_response_code(401); echo '{"error":"Unauthorized"}'; exit;
}
// TODO: اعتبارسنجی توکن و نقش

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['id'])) { http_response_code(400); echo '{"error":"Bad request"}'; exit; }

$project_id = intval($data['id']);
$image = $data['image'] ?? '';
$is_active = (isset($data['status']) && $data['status'] === 'active') ? 1 : 0;
$languages = (isset($data['languages']) && is_array($data['languages'])) ? $data['languages'] : ['en'];
$translations = $data['translations'] ?? [];

// بروزرسانی پروژه
$stmt = $mysqli->prepare("UPDATE public_projects SET image=?, is_active=? WHERE id=?");
$stmt->bind_param('sii', $image, $is_active, $project_id);
if(!$stmt->execute()){
    http_response_code(500); echo '{"error":"DB error"}'; exit;
}
$stmt->close();

// بروزرسانی ترجمه‌ها
if ($translations && $languages) {
    foreach ($languages as $lang) {
        $t = $translations[$lang] ?? [];
        $title = $t['title'] ?? '';
        $desc = $t['description'] ?? '';
        $long_desc = $t['long_description'] ?? '';

        // آیا ترجمه وجود دارد؟
        $stmt2 = $mysqli->prepare("SELECT COUNT(*) FROM project_translations WHERE project_id=? AND lang=?");
        $stmt2->bind_param('is', $project_id, $lang);
        $stmt2->execute();
        $stmt2->bind_result($cnt); $stmt2->fetch(); $stmt2->close();

        if($cnt>0){
            $stmt3 = $mysqli->prepare("UPDATE project_translations SET title=?, description=?, long_description=? WHERE project_id=? AND lang=?");
            $stmt3->bind_param('sssis', $title, $desc, $long_desc, $project_id, $lang);
            $stmt3->execute();
            $stmt3->close();
        } else {
            $stmt3 = $mysqli->prepare("INSERT INTO project_translations (project_id, lang, title, description, long_description) VALUES (?, ?, ?, ?, ?)");
            $stmt3->bind_param('issss', $project_id, $lang, $title, $desc, $long_desc);
            $stmt3->execute();
            $stmt3->close();
        }
    }
}
echo json_encode(['success'=>1, 'id'=>$project_id]);