<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://xtremedev.co');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

require_once __DIR__.'/../shared/database-config.php';
require_once __DIR__.'/../shared/auth-helper.php';

// فقط با توکن معتبر
$user_id = getUserIdFromBearerToken();
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error'=>'unauthorized']);
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    http_response_code(400);
    echo json_encode(['error'=>'bad_id']);
    exit;
}

// فقط درخواست‌هایی که برای این کاربر است
$stmt = $mysqli->prepare("SELECT id, project_id, role_id, fullname, email, skills, `desc`, cv_file, created_at, status, updated_at, admin_note 
    FROM joinus_requests WHERE id=? AND user_id=? LIMIT 1");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    // اگر فایل رزومه با /uploads شروع نشد، اصلاح کن (بسته به پروژه شما)
    if($row['cv_file'] && strpos($row['cv_file'], '/uploads/') !== 0) {
        $row['cv_file'] = '/uploads/joinus_cv/' . $row['cv_file'];
    }
    echo json_encode($row, JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(404);
    echo json_encode(['error'=>'not_found']);
}
exit;