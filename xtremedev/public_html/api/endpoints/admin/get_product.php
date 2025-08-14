<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../shared/database-config.php';

// --------- توکن ادمین (اجباری) ----------
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

// --------- اعتبارسنجی نقش ادمین ---------
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
if ($httpcode != 200 || !$admininfo || empty($admininfo['role']) || !in_array($admininfo['role'], ['superadmin', 'manager', 'support', 'read_only'])) {
    http_response_code(403);
    echo json_encode(['error'=>'forbidden']);
    exit;
}

// --------- پارامتر id ---------
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'missing_product_id']);
    exit;
}

// --------- گرفتن اطلاعات محصول ---------
$stmt = $mysqli->prepare("SELECT id, project_id, category_id, price, is_active, created_at FROM products WHERE id=? LIMIT 1");
$stmt->bind_param('i', $product_id);
$stmt->execute();
$stmt->bind_result($id, $project_id, $category_id, $price, $is_active, $created_at);
if (!$stmt->fetch()) {
    $stmt->close();
    http_response_code(404);
    echo json_encode(['error' => 'not_found']);
    exit;
}
$stmt->close();

$product = [
    'id'          => $id,
    'project_id'  => $project_id,
    'category_id' => $category_id,
    'price'       => $price,
    'is_active'   => $is_active,
    'created_at'  => $created_at,
    'translations'=> []
];

// --------- گرفتن ترجمه‌ها ---------
$stmt = $mysqli->prepare("SELECT lang, name, description FROM product_translations WHERE product_id=?");
$stmt->bind_param('i', $product_id);
$stmt->execute();
$stmt->bind_result($lang, $name, $description);
while ($stmt->fetch()) {
    $product['translations'][$lang] = [
        'name'        => $name,
        'description' => $description
    ];
}
$stmt->close();

echo json_encode($product, JSON_UNESCAPED_UNICODE);
exit;
?>