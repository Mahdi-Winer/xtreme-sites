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

// ---------- اعتبارسنجی نقش ادمین ----------
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

// ---------- گرفتن داده POST و اعتبارسنجی ----------
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'missing_product_id']);
    exit;
}

$product_id  = intval($data['id']);
$project_id  = isset($data['project_id']) ? intval($data['project_id']) : 0;
$category_id = isset($data['category_id']) ? intval($data['category_id']) : 0;
$price       = isset($data['price']) ? floatval($data['price']) : 0;
$is_active   = isset($data['is_active']) ? (int)$data['is_active'] : 1;
$languages   = isset($data['languages']) && is_array($data['languages']) ? $data['languages'] : ['en','fa'];
$translations= $data['translations'] ?? [];

$errors = [];
if ($product_id <= 0) $errors[] = 'Invalid product id.';
if ($project_id <= 0) $errors[] = 'Invalid project id.';
if ($category_id <= 0) $errors[] = 'Invalid category id.';
if ($price < 0) $errors[] = 'Invalid price.';
if (!in_array($is_active, [0,1])) $errors[] = 'Invalid is_active value.';

foreach ($languages as $lng) {
    if (empty($translations[$lng]['name']) || empty($translations[$lng]['description'])) {
        $errors[] = "Translation for $lng is required.";
    }
}
if ($errors) {
    http_response_code(422);
    echo json_encode(['error' => $errors]);
    exit;
}

// ---------- بررسی وجود محصول ----------
$stmt = $mysqli->prepare("SELECT COUNT(*) FROM products WHERE id=?");
$stmt->bind_param('i', $product_id);
$stmt->execute();
$stmt->bind_result($cnt);
$stmt->fetch();
$stmt->close();
if ($cnt == 0) {
    http_response_code(404);
    echo json_encode(['error'=>'Product not found.']);
    exit;
}

// ---------- بررسی وجود project و category ----------
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

// ---------- بروزرسانی جدول محصول ----------
$stmt = $mysqli->prepare(
    "UPDATE products SET project_id=?, category_id=?, price=?, is_active=? WHERE id=?"
);
$stmt->bind_param('iidii', $project_id, $category_id, $price, $is_active, $product_id);
$stmt->execute();
$stmt->close();

// ---------- بروزرسانی ترجمه‌ها ----------
foreach ($languages as $lng) {
    $name = trim($translations[$lng]['name']);
    $desc = trim($translations[$lng]['description']);
    // آیا ترجمه وجود دارد؟
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM product_translations WHERE product_id=? AND lang=?");
    $stmt->bind_param('is', $product_id, $lng);
    $stmt->execute();
    $stmt->bind_result($cnt);
    $stmt->fetch();
    $stmt->close();
    if ($cnt > 0) {
        $stmt = $mysqli->prepare("UPDATE product_translations SET name=?, description=? WHERE product_id=? AND lang=?");
        $stmt->bind_param('ssis', $name, $desc, $product_id, $lng);
    } else {
        $stmt = $mysqli->prepare("INSERT INTO product_translations (product_id, lang, name, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('isss', $product_id, $lng, $name, $desc);
    }
    $stmt->execute();
    $stmt->close();
}

echo json_encode(['success'=>true]);
exit;
?>