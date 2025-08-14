<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://xtremedev.co');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit;
require_once __DIR__ . '/../shared/database-config.php';
require_once __DIR__ . '/../shared/auth-helper.php';

$user_id = getUserIdFromBearerToken();
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error'=>'unauthorized']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error'=>'method_not_allowed']);
    exit;
}

// داده ها را بگیر
$project_id = intval($_POST['project_id'] ?? 0);
$role_id    = intval($_POST['role_id'] ?? 0);
$fullname   = trim($_POST['fullname'] ?? '');
$email      = trim($_POST['email'] ?? '');
$skills     = trim($_POST['skills'] ?? '');
$desc       = trim($_POST['desc'] ?? '');

// اعتبارسنجی
$errors = [];
if (!$project_id)   $errors[] = 'project_id_required';
if (!$role_id)      $errors[] = 'role_id_required';
if (!$fullname)     $errors[] = 'fullname_required';
if (!$email)        $errors[] = 'email_required';
if (!$skills)       $errors[] = 'skills_required';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'invalid_email';

if ($errors) {
    http_response_code(422);
    echo json_encode(['success'=>false, 'errors'=>$errors]);
    exit;
}

// فایل رزومه (اختیاری)
$cv_file = null;
if (!empty($_FILES['cv']['name'])) {
    $ext = strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION));
    $allowed = ['pdf','doc','docx','txt','zip','rar'];
    if (!in_array($ext, $allowed)) {
        http_response_code(422);
        echo json_encode(['success'=>false, 'errors'=>['invalid_file_type']]);
        exit;
    }
    $cv_dir = dirname(__DIR__) . '/uploads/joinus_cv/';
    if (!is_dir($cv_dir)) mkdir($cv_dir,0777,true);
    $filename = 'cv_' . $user_id . '_' . time() . '.' . $ext;
    $target = $cv_dir . $filename;
    if (!move_uploaded_file($_FILES['cv']['tmp_name'], $target)) {
        http_response_code(500);
        echo json_encode(['success'=>false, 'errors'=>['upload_failed']]);
        exit;
    }
    $cv_file = '/uploads/joinus_cv/' . $filename;
}

// ثبت در دیتابیس
$stmt = $mysqli->prepare("INSERT INTO joinus_requests
    (project_id, role_id, fullname, email, skills, `desc`, cv_file, created_at, user_id, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, 0)");
if(!$stmt){
    http_response_code(500);
    echo json_encode(['success'=>false, 'error'=>'stmt_prepare', 'mysqli_error'=>$mysqli->error]);
    exit;
}
$stmt->bind_param("iisssssi", $project_id, $role_id, $fullname, $email, $skills, $desc, $cv_file, $user_id);
$res = $stmt->execute();
if(!$res){
    http_response_code(500);
    echo json_encode(['success'=>false, 'error'=>'stmt_execute', 'mysqli_error'=>$stmt->error]);
    exit;
}

// موفقیت
echo json_encode(['success'=>true, 'id'=>$mysqli->insert_id]);
exit;