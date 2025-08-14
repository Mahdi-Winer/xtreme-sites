<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../shared/database-config.php';

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
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

// چک نقش ادمین
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://auth.xtremedev.co/admininfo.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_token"
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 8);
$resp = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$admininfo = @json_decode($resp, true);
if ($httpcode != 200 || !$admininfo || empty($admininfo['role']) || !in_array($admininfo['role'], ['superadmin', 'manager'])) {
    http_response_code(403);
    echo json_encode(['error'=>'forbidden']);
    exit;
}

// پارس و اعتبارسنجی داده ورودی
$data = json_decode(file_get_contents('php://input'), true);

$project_id   = isset($data['project_id']) ? intval($data['project_id']) : 0;
$category_id  = isset($data['category_id']) ? intval($data['category_id']) : 0;
$price        = isset($data['price']) ? floatval($data['price']) : 0;
$is_active    = isset($data['is_active']) ? (int)$data['is_active'] : 1;
$languages    = isset($data['languages']) && is_array($data['languages']) ? $data['languages'] : ['en','fa'];
$translations = isset($data['translations']) ? $data['translations'] : [];

$errors = [];
if ($project_id <= 0) $errors[] = 'Invalid project id.';
if ($category_id <= 0) $errors[] = 'Invalid category id.';
if ($price < 0) $errors[] = 'Invalid price.';
if (!in_array($is_active, [0, 1])) $errors[] = 'Invalid is_active value.';

foreach ($languages as $lng) {
    if (empty($translations[$lng]['name']) || empty($translations[$lng]['description'])) {
        $errors[] = "Translation for $lng is required (name & description).";
    }
}
if ($errors) {
    http_response_code(422);
    echo json_encode(['error' => $errors]);
    exit;
}

// بررسی وجود project_id در دیتابیس
$stmt = $mysqli->prepare("SELECT COUNT(*) FROM projects WHERE id=?");
$stmt->bind_param('i', $project_id);
$stmt->execute();
$stmt->bind_result($cnt);
$stmt->fetch();
$stmt->close();
if ($cnt == 0) {
    http_response_code(422);
    echo json_encode(['error'=>'Project not found.']);
    exit;
}

// بررسی وجود category_id برای همین پروژه و فعال بودن
$stmt = $mysqli->prepare("SELECT COUNT(*) FROM product_categories WHERE id=? AND project_id=? AND is_active=1");
$stmt->bind_param('ii', $category_id, $project_id);
$stmt->execute();
$stmt->bind_result($cnt);
$stmt->fetch();
$stmt->close();
if ($cnt == 0) {
    http_response_code(422);
    echo json_encode(['error'=>'Category not found.']);
    exit;
}

// درج محصول
$stmt = $mysqli->prepare("INSERT INTO products (project_id, category_id, price, is_active, created_at) VALUES (?, ?, ?, ?, NOW())");
$stmt->bind_param('iidi', $project_id, $category_id, $price, $is_active);
if (!$stmt->execute()) {
    $stmt->close();
    http_response_code(500);
    echo json_encode(['error'=>'db_insert_failed']);
    exit;
}
$product_id = $stmt->insert_id;
$stmt->close();

// درج ترجمه‌ها
foreach ($languages as $lng) {
    $name = trim($translations[$lng]['name']);
    $desc = trim($translations[$lng]['description']);
    $stmt = $mysqli->prepare("INSERT INTO product_translations (product_id, lang, name, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('isss', $product_id, $lng, $name, $desc);
    if (!$stmt->execute()) {
        $stmt->close();
        http_response_code(500);
        echo json_encode(['error' => "insert_translation_failed_$lng"]);
        exit;
    }
    $stmt->close();
}

echo json_encode(['success'=>true, 'id'=>$product_id]);
exit;
?>