<?php
session_start();
if (!isset($_SESSION['admin_user_id']) || !isset($_SESSION['admin_access_token'])) { header("Location: login.php"); exit; }
$aid = isset($_GET['id'])?intval($_GET['id']):0;
if(!$aid) die('Article ID not found.');

$api_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https":"http") .
    "://".$_SERVER['HTTP_HOST']."/api/endpoints/projects_list.php";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$projects_json = curl_exec($ch);
curl_close($ch);
$projectsList = @json_decode($projects_json, true);
if (!is_array($projectsList)) $projectsList = [];

// گرفتن مقاله
$aid = intval($_GET['id']);
$geturl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on'?'https':'http')."://".$_SERVER['HTTP_HOST']."/api/endpoints/articles_get.php?id=$aid";
$article_json = @file_get_contents($geturl);
$art = @json_decode($article_json, true);
if(!$art) die('Article not found.');

$error = '';
$success = false;

// ارسال فرم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = intval($_POST['project_id']);
    $thumbnail = trim($_POST['thumbnail']);
    $title_fa = trim($_POST['title_fa']); $content_fa = trim($_POST['content_fa']); $body_fa = trim($_POST['body_fa']);
    $title_en = trim($_POST['title_en']); $content_en = trim($_POST['content_en']); $body_en = trim($_POST['body_en']);
    if(!$project_id || !$title_fa || !$title_en) $error = 'پروژه و عنوان‌ها ضروری است.';
    else {
        $data = [
            "id" => $aid,
            "project_id" => $project_id,
            "thumbnail" => $thumbnail,
            "translations" => [
                "fa" => ["title"=>$title_fa, "content"=>$content_fa, "body"=>$body_fa],
                "en" => ["title"=>$title_en, "content"=>$content_en, "body"=>$body_en]
            ]
        ];
        $payload = json_encode($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on'?'https':'http')."://".$_SERVER['HTTP_HOST']."/api/endpoints/admin/articles_edit.php");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer '.$_SESSION['admin_access_token'],
            'Content-Type: application/json'
        ]);
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $resp = @json_decode($result,true);
        if($httpcode==200 && isset($resp['success'])) {
            $success = true;
            // refresh article data
            $article_json = @file_get_contents($geturl);
            $art = @json_decode($article_json, true);
        } else {
            $error = isset($resp['error']) ? $resp['error'] : 'خطا در ویرایش مقاله';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ویرایش مقاله</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <!-- TinyMCE Dark -->
    <script src="https://cdn.tiny.cloud/1/irnrp1bnwa6iqujrcfr69gp5jzyu5da8dx7xgdtry9d9ppe2/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
    tinymce.init({
        selector: '.tinymce',
        height: 320,
        plugins: 'image link code lists directionality',
        toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist | outdent indent | image link code | ltr rtl',
        directionality : "rtl",
        skin: "oxide-dark",
        content_css: "dark",
        relative_urls : false,
        remove_script_host : false,
        convert_urls : true,
        branding: false,
        menubar: false
    });
    </script>
    <style>
        body { background: #181f27; color: #e6e9f2; font-family: Vazirmatn, Tahoma, Arial, sans-serif; }
        .container-main { max-width: 640px; margin:40px auto 0 auto; }
        .card { background: #222b3b; border-radius: 15px; box-shadow: 0 2px 22px #0002; padding: 24px 32px 20px 32px; margin-bottom: 40px; }
        .form-label { font-weight:700; margin-bottom:4px; color:#aad3ff; display:block; }
        .form-control { background: #232d3b !important; color: #e6e9f2 !important; border: 1.2px solid #31415a; border-radius: 8px; padding: 7px 14px; font-size: 1rem; transition: border .15s; width:100%; margin-bottom: 0; }
        .form-control:focus { outline: none; border-color: #38a8ff; background: #253040; }
        .form-group { margin-bottom: 26px; }
        .btn-main { background: #38a8ff; color: #fff; border-radius: 7px; font-weight: 700; padding: 8px 15px; border: none; }
        .btn-main:hover { background: #2499fa; }
        .alert { margin: 1rem 0; padding: .7rem 1rem; border-radius:8px;}
        .alert-danger { background: #e13a3a; color: #fff; }
        .alert-success { background: #27b36a; color: #fff; }
        /* TinyMCE override for dark padding */
        .tox .tox-edit-area__iframe { background: #181f27 !important; }
    </style>
</head>
<body>
<div class="container-main">
    <div class="card">
        <h2 style="color:#38a8ff">ویرایش مقاله</h2>
        <?php if($success): ?>
            <div class="alert alert-success">ویرایش انجام شد! <a href="articles_list.php" style="color:#fff;text-decoration:underline">بازگشت به لیست</a></div>
        <?php elseif($error): ?>
            <div class="alert alert-danger"><?=htmlspecialchars($error)?></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label class="form-label">پروژه <span style="color:#e13a3a;">*</span></label>
                <select class="form-control" name="project_id" required>
                    <option value="">انتخاب پروژه</option>
                    <?php foreach($projectsList as $prj): ?>
                        <option value="<?=htmlspecialchars($prj['id'])?>" <?=$art['project_id']==$prj['id']?'selected':''?>><?=htmlspecialchars($prj['name'])?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">آدرس تصویر (اختیاری)</label>
                <input type="url" class="form-control" name="thumbnail" value="<?=htmlspecialchars($art['thumbnail'])?>">
            </div>
            <div class="form-group">
                <label class="form-label">عنوان (فارسی) <span style="color:#e13a3a;">*</span></label>
                <input type="text" class="form-control" name="title_fa" required value="<?=htmlspecialchars($art['translations']['fa']['title']??'')?>">
            </div>
            <div class="form-group">
                <label class="form-label">خلاصه (فارسی)</label>
                <input type="text" class="form-control" name="content_fa" value="<?=htmlspecialchars($art['translations']['fa']['content']??'')?>">
            </div>
            <div class="form-group">
                <label class="form-label">بدنه (فارسی)</label>
                <textarea class="form-control tinymce" name="body_fa"><?=htmlspecialchars($art['translations']['fa']['body']??'')?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">عنوان (انگلیسی) <span style="color:#e13a3a;">*</span></label>
                <input type="text" class="form-control" name="title_en" required value="<?=htmlspecialchars($art['translations']['en']['title']??'')?>">
            </div>
            <div class="form-group">
                <label class="form-label">خلاصه (انگلیسی)</label>
                <input type="text" class="form-control" name="content_en" value="<?=htmlspecialchars($art['translations']['en']['content']??'')?>">
            </div>
            <div class="form-group">
                <label class="form-label">بدنه (انگلیسی)</label>
                <textarea class="form-control tinymce" name="body_en"><?=htmlspecialchars($art['translations']['en']['body']??'')?></textarea>
            </div>
            <button class="btn-main" style="margin-top:18px;">ذخیره تغییرات</button>
            <a href="articles_list.php" class="btn-main" style="background:#313a48;margin-top:18px;">بازگشت</a>
        </form>
    </div>
</div>
</body>
</html>