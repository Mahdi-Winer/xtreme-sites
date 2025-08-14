<?php
// endpoints/admin/get_user.php
header('Content-Type: application/json');

// ----------- 1. دریافت Bearer Token -----------
function getBearerToken() {
    $header = '';
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $header = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) {
            $header = $headers['Authorization'];
        }
    }
    if ($header && preg_match('/Bearer\s+(\S+)/', $header, $matches)) {
        return $matches[1];
    }
    return null;
}

// ----------- 2. چک توکن ادمین و نقش -----------
$access_token = getBearerToken();
if (!$access_token) {
    http_response_code(401);
    echo json_encode(['error'=>'unauthorized']);
    exit;
}

// استعلام اطلاعات ادمین از سرور auth
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://auth.xtremedev.co/admininfo.php");
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

// ----------- 3. اعتبارسنجی ورودی id -----------
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['error'=>'invalid_id']);
    exit;
}

// ----------- 4. فراخوانی سرور مرکزی (auth) برای گرفتن اطلاعات کاربر -----------
define('AUTH_API_CLIENT_ID', 'admin-panel');     // مقدار واقعی را جایگزین کن
define('AUTH_API_CLIENT_SECRET', 'KB1UX!X%9MxPF7^hYqpL*hn}~,kdq>4RVtV~F=uW6u_U2HgvFWi?g9*=zpUp40%i%PP751gP2E+5nCaZk#JEzw9xE=E~6M1qqH9*'); // مقدار واقعی را جایگزین کن

$post_data = [
    'client_id'    => AUTH_API_CLIENT_ID,
    'client_secret'=> AUTH_API_CLIENT_SECRET,
    'id'           => $user_id
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://auth.xtremedev.co/api/get_user.php");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 8);
$resp = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpcode == 200 && $resp) {
    echo $resp;
    exit;
} else {
    http_response_code(500);
    echo json_encode(['error'=>'central_api_error']);
    exit;
}
?>