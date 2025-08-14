<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://xtremedev.co');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once __DIR__ . '/../../shared/database-config.php';

// --- احراز هویت Bearer Token ---
function getBearerToken() {
    $header = '';
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) $header = $_SERVER['HTTP_AUTHORIZATION'];
    elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    elseif (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) $header = $headers['Authorization'];
    }
    if ($header && preg_match('/Bearer\s+(\S+)/', $header, $matches)) {
        return $matches[1];
    }
    return null;
}
$access_token = getBearerToken();
if (!$access_token) {
    http_response_code(401); echo json_encode(['error' => 'unauthorized']); exit;
}
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://auth.xtremedev.co/admininfo.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $access_token"]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$resp = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$admininfo = @json_decode($resp, true);
if ($httpcode != 200 || !$admininfo || empty($admininfo['role']) || !in_array($admininfo['role'], ['superadmin', 'manager'])) {
    http_response_code(403); echo json_encode(['error'=>'forbidden']); exit;
}

// --- اعتبارسنجی ورودی ---
$data = json_decode(file_get_contents('php://input'), true);
if(!$data || !isset($data['project_id']) || !isset($data['translations']) || !is_array($data['translations'])) {
    http_response_code(400); echo '{"error":"bad_request"}'; exit;
}
$project_id = intval($data['project_id']);
$thumbnail = $data['thumbnail'] ?? '';

$stmt = $mysqli->prepare("INSERT INTO articles (project_id, thumbnail) VALUES (?, ?)");
$stmt->bind_param('is', $project_id, $thumbnail);
if(!$stmt->execute()) {
    http_response_code(500); echo '{"error":"db_error"}'; exit;
}
$article_id = $stmt->insert_id;
$stmt->close();

foreach($data['translations'] as $lang => $t) {
    $title = $t['title'] ?? '';
    $content = $t['content'] ?? '';
    $body = $t['body'] ?? '';
    $stmt2 = $mysqli->prepare("INSERT INTO article_translations (article_id, lang, title, content, body) VALUES (?, ?, ?, ?, ?)");
    $stmt2->bind_param('issss', $article_id, $lang, $title, $content, $body);
    $stmt2->execute();
    $stmt2->close();
}
echo json_encode(['success'=>1, 'id'=>$article_id]);
exit;
?>