<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../shared/database-config.php';

// ---------- دریافت و اعتبارسنجی توکن ادمین ----------
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

// ---------- دریافت اطلاعات ادمین از auth مرکزی ----------
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

// ---------- پارامترهای lang و search ----------
$lang = 'fa';
if (isset($_GET['lang']) && preg_match('/^[a-z]{2}$/', $_GET['lang'])) {
    $lang = $_GET['lang'];
} elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    if (isset($langs[0]) && preg_match('/^[a-z]{2}$/', $langs[0])) {
        $lang = $langs[0];
    }
}
if (!in_array($lang, ['fa','en'])) $lang = 'fa';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// ---------- واکشی محصولات با ترجمه ----------
$products = [];
if ($search !== '') {
    $search_sql = "%$search%";
    $stmt = $mysqli->prepare(
        "SELECT 
            p.id, 
            p.project_id,
            p.price, 
            p.is_active, 
            p.created_at,
            COALESCE(pt.name, '') AS name, 
            COALESCE(pt.description, '') AS description
         FROM products p
         LEFT JOIN product_translations pt ON pt.product_id = p.id AND pt.lang = ?
         WHERE 
            p.id LIKE ? OR
            pt.name LIKE ? OR
            pt.description LIKE ?
         ORDER BY p.created_at DESC"
    );
    $stmt->bind_param("ssss", $lang, $search_sql, $search_sql, $search_sql);
    $stmt->execute();
    $stmt->bind_result($id, $project_id, $price, $is_active, $created_at, $name, $description);
    while($stmt->fetch()) {
        $products[] = [
            'id'          => $id,
            'project_id'  => $project_id,
            'name'        => $name,
            'description' => $description,
            'price'       => $price,
            'is_active'   => (int)$is_active,
            'status'      => ($is_active ? 'active' : 'inactive'),
            'created_at'  => $created_at
        ];
    }
    $stmt->close();
} else {
    $stmt = $mysqli->prepare(
        "SELECT 
            p.id, 
            p.project_id,
            p.price, 
            p.is_active, 
            p.created_at,
            COALESCE(pt.name, '') AS name, 
            COALESCE(pt.description, '') AS description
         FROM products p
         LEFT JOIN product_translations pt ON pt.product_id = p.id AND pt.lang = ?
         ORDER BY p.created_at DESC"
    );
    $stmt->bind_param("s", $lang);
    $stmt->execute();
    $stmt->bind_result($id, $project_id, $price, $is_active, $created_at, $name, $description);
    while($stmt->fetch()) {
        $products[] = [
            'id'          => $id,
            'project_id'  => $project_id,
            'name'        => $name,
            'description' => $description,
            'price'       => $price,
            'is_active'   => (int)$is_active,
            'status'      => ($is_active ? 'active' : 'inactive'),
            'created_at'  => $created_at
        ];
    }
    $stmt->close();
}

echo json_encode($products, JSON_UNESCAPED_UNICODE);
exit;
?>