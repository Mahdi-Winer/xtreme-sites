<?php
session_start();
if (!isset($_SESSION['admin_user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'databaseConfig.php';

$admin_id = $_SESSION['admin_user_id'];
$stmt = $mysqli->prepare("SELECT role FROM admin_users WHERE id=? LIMIT 1");
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();

// فقط سوپرادمین و منیجر اجازه حذف دارند
if (!in_array($role, ['superadmin', 'manager'])) {
    header("Location: access_denied.php"); exit;
}

// دریافت ID کاربر
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($user_id <= 0) {
    header("Location: users.php"); exit;
}

// چک وجود کاربر
$stmt = $mysqli->prepare("SELECT id FROM users WHERE id=? LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($uid);
if (!$stmt->fetch()) {
    $stmt->close();
    header("Location: users.php"); exit;
}
$stmt->close();

// حذف کاربر
$stmt = $mysqli->prepare("DELETE FROM users WHERE id=? LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->close();

// انتقال به لیست کاربران
header("Location: users.php?deleted=1");
exit;
?>