<?php
session_start();

// تمام متغیرهای سشن را پاک کن
$_SESSION = [];

// اگر کوکی سشن ست شده، حذفش کن (اختیاری ولی توصیه‌شده)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// سشن را کامل نابود کن
session_destroy();

// ریدایرکت به صفحه لاگین
header("Location: login.php");
exit;
?>