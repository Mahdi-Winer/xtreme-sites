<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../shared/database-config.php';

// گرفتن توکن و چک نقش ادمین
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

// چک نقش
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

// دریافت product_id
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $product_id = isset($data['product_id']) ? intval($data['product_id']) : 0;
} else {
    $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
}

if ($product_id <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid product id.']);
    exit;
}

// اول حذف ترجمه‌های محصول
$stmt = $mysqli->prepare("DELETE FROM product_translations WHERE product_id = ?");
$stmt->bind_param('i', $product_id);
$stmt->execute();
$stmt->close();

// سپس حذف خود محصول
$stmt = $mysqli->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param('i', $product_id);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

if ($affected > 0) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Product not found or already deleted.']);
}
exit;
?>