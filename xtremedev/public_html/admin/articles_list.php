<?php
session_start();
if (!isset($_SESSION['admin_user_id']) || !isset($_SESSION['admin_access_token'])) {
    header("Location: login.php");
    exit;
}
$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : 'fa';
$is_rtl = ($lang === 'fa');

// گرفتن لیست پروژه‌ها از API
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
?>
<!DOCTYPE html>
<html lang="<?=$lang?>" dir="<?=$is_rtl?'rtl':'ltr'?>">
<head>
    <meta charset="UTF-8">
    <title>لیست مقالات | Admin</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        body { background: #181f27; color: #e6e9f2; font-family: Vazirmatn, Tahoma, Arial, sans-serif; }
        .container-main { max-width: 900px; margin:40px auto 0 auto; width: 100%; }
        .card { background: #222b3b; border-radius: 15px; box-shadow: 0 2px 22px #0002; padding: 26px 36px 24px 36px; margin-bottom: 40px; }
        .btn-main { background: #38a8ff; color: #fff; border-radius: 7px; font-weight: 700; padding: 8px 15px; border: none; }
        .btn-main:hover { background: #2499fa; }
        .btn-xs { font-size: .93rem; padding: 5px 12px; }
        .btn-danger { background: #e13a3a; color: #fff; }
        .btn-danger:hover { background: #c81313; }
        .article-table { width:100%; border-collapse:collapse; margin-bottom:14px; }
        .article-table th, .article-table td { padding:10px 8px; border-bottom:1px solid #31415a; }
        .article-table th { color:#aad3ff; font-weight:700; background:#232d3b; }
        .article-table td { color:#e6e9f2; }

        .themed-select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background: #232d3b url("data:image/svg+xml;utf8,<svg fill='white' height='14' viewBox='0 0 24 24' width='14' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/></svg>") no-repeat;
            background-position: <?=$is_rtl?'left 10px center':'right 10px center'?>;
            background-size:18px 18px;
            color: #e6e9f2;
            border: 1.5px solid #31415a;
            padding: 9px 38px 9px 16px;
            font-size: 1rem;
            border-radius: 7px;
            box-shadow: 0 2px 12px #0002;
            transition: border .15s, box-shadow .15s;
            min-width:180px;
            outline: none;
            margin-left:5px;
        }
        .themed-select:focus {
            border-color: #38a8ff;
            box-shadow: 0 0 0 2px #38a8ff60;
            background-color: #253040;
        }
        .themed-select option {
            background: #232d3b;
            color: #e6e9f2;
        }
        @media (max-width: 900px) { 
            .container-main {padding:0 2px;} 
            .card {padding:14px 6px;} 
            .themed-select { font-size:.97rem; min-width:120px; }
        }
    </style>
</head>
<body>
<div class="container-main">
    <div class="card">
        <div style="margin-bottom: 15px;">
            <form method="get" style="display:inline-block;">
                <select class="themed-select" id="project-select" name="project_id" onchange="this.form.submit()">
                    <option value="">انتخاب پروژه</option>
                    <?php foreach($projectsList as $prj): ?>
                        <option value="<?=htmlspecialchars($prj['id'])?>" <?=isset($_GET['project_id'])&&$_GET['project_id']==$prj['id']?'selected':''?>><?=htmlspecialchars($prj['name'])?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <a href="article_add.php" class="btn btn-main">افزودن مقاله</a>
        </div>
        <div id="articles-table-block">
        <?php
        if(isset($_GET['project_id']) && $_GET['project_id']) {
            $pid = intval($_GET['project_id']);
            $list_url_fa = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https":"http") .
                "://".$_SERVER['HTTP_HOST']."/api/endpoints/articles_list.php?project_id=$pid&lang=fa";
            $list_url_en = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https":"http") .
                "://".$_SERVER['HTTP_HOST']."/api/endpoints/articles_list.php?project_id=$pid&lang=en";
            $fa_json = @file_get_contents($list_url_fa);
            $en_json = @file_get_contents($list_url_en);
            $list_fa = @json_decode($fa_json, true); if(!is_array($list_fa)) $list_fa=[];
            $list_en = @json_decode($en_json, true); if(!is_array($list_en)) $list_en=[];
            $articlesList = [];
            foreach($list_fa as $fa){
                $en = null;
                foreach($list_en as $e) if($e['id']==$fa['id']) $en = $e;
                $articlesList[] = [
                    'id'=>$fa['id'],
                    'project_id'=>$fa['project_id'],
                    'thumbnail'=>$fa['thumbnail'],
                    'created_at'=>$fa['created_at'],
                    'title_fa'=>$fa['title']??'',
                    'title_en'=>$en['title']??'',
                ];
            }
            if(!count($articlesList)): ?>
            <div style="text-align:center;color:#8af;">مقاله‌ای نیست</div>
            <?php else: ?>
            <table class="article-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>عنوان (FA)</th>
                    <th>عنوان (EN)</th>
                    <th>تصویر</th>
                    <th>تاریخ ثبت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($articlesList as $article): ?>
                <tr>
                    <td><?=$article['id']?></td>
                    <td><?=htmlspecialchars($article['title_fa'])?></td>
                    <td><?=htmlspecialchars($article['title_en'])?></td>
                    <td><?=$article['thumbnail']?'<img src="'.htmlspecialchars($article['thumbnail']).'" style="max-width:60px;max-height:30px;border-radius:5px;">':'-'?></td>
                    <td><?=$article['created_at']?></td>
                    <td>
                        <a href="article_edit.php?id=<?=$article['id']?>" class="btn btn-main btn-xs">ویرایش</a>
                        <a href="article_delete.php?id=<?=$article['id']?>&project_id=<?=$article['project_id']?>" class="btn btn-danger btn-xs" onclick="return confirm('حذف شود؟')">حذف</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            </table>
            <?php endif;
        } else { ?>
            <div style="margin:2rem 0;text-align:center;color:#8af">ابتدا پروژه را انتخاب کنید</div>
        <?php } ?>
        </div>
    </div>
</div>
</body>
</html>