<?php
session_start();
if (!isset($_SESSION['admin_user_id'])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__.'/../shared/inc/database-config.php';
require_once __DIR__.'/../shared/inc/config.php';

// دوزبانه
$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'en');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : 'en';
$is_rtl = ($lang === 'fa');
$tr = [
    'en' => [
        'edit_order'    => 'Edit Order',
        'order_number'  => 'Order Number',
        'status'        => 'Status',
        'pending'       => 'Pending',
        'paid'          => 'Paid',
        'failed'        => 'Failed',
        'canceled'      => 'Canceled',
        'amount'        => 'Amount (IRR)',
        'payment_gateway'=> 'Payment Gateway',
        'payment_ref'   => 'Payment Ref',
        'paid_at'       => 'Paid At (YYYY-MM-DD hh:mm:ss)',
        'save'          => 'Save Changes',
        'back'          => 'Back',
        'user'          => 'User',
        'product'       => 'Product',
        'success'       => 'Order successfully updated!',
        'invalid_status'=> 'Invalid status!',
        'error_update'  => 'Error updating order!',
    ],
    'fa' => [
        'edit_order'    => 'ویرایش سفارش',
        'order_number'  => 'شماره سفارش',
        'status'        => 'وضعیت',
        'pending'       => 'در انتظار',
        'paid'          => 'پرداخت شده',
        'failed'        => 'ناموفق',
        'canceled'      => 'لغو شده',
        'amount'        => 'مبلغ (ریال)',
        'payment_gateway'=> 'درگاه پرداخت',
        'payment_ref'   => 'کد پیگیری پرداخت',
        'paid_at'       => 'تاریخ پرداخت (YYYY-MM-DD hh:mm:ss)',
        'save'          => 'ذخیره تغییرات',
        'back'          => 'بازگشت',
        'user'          => 'کاربر',
        'product'       => 'محصول',
        'success'       => 'سفارش با موفقیت ویرایش شد!',
        'invalid_status'=> 'وضعیت نامعتبر است!',
        'error_update'  => 'خطا در ویرایش سفارش!',
    ]
];

$admin_id = $_SESSION['admin_user_id'];
$stmt = $mysqli->prepare("SELECT role FROM admin_users WHERE id=? LIMIT 1");
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();

if (!in_array($role, ['superadmin','manager'])) {
    header("Location: access_denied.php");
    exit;
}

// دریافت سفارش
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($order_id <= 0) {
    header("Location: orders.php");
    exit;
}

// دریافت اطلاعات سفارش فعلی
$title_col = ($lang=='fa' ? 'p.title_fa' : 'p.title_en');
$stmt = $mysqli->prepare("
    SELECT o.id, o.order_number, o.user_id, u.name, u.email, o.product_id, $title_col, o.amount, o.payment_gateway, o.payment_ref, o.order_status, o.created_at, o.paid_at
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN products p ON o.product_id = p.id
    WHERE o.id=?
    LIMIT 1
");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$stmt->bind_result(
    $oid, $order_number, $user_id, $user_name, $user_email, $product_id, $product_title,
    $amount, $payment_gateway, $payment_ref, $order_status, $created_at, $paid_at
);
if(!$stmt->fetch()) {
    $stmt->close();
    header("Location: orders.php");
    exit;
}
$stmt->close();

$message = "";
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = $_POST['order_status'] ?? $order_status;
    $new_amount = intval($_POST['amount']);
    $new_payment_gateway = trim($_POST['payment_gateway']);
    $new_payment_ref = trim($_POST['payment_ref']);
    $new_order_number = trim($_POST['order_number']);
    $new_paid_at = trim($_POST['paid_at']);

    if (!in_array($new_status, ['pending', 'paid', 'failed', 'canceled'])) {
        $message = $tr[$lang]['invalid_status'];
    } else {
        $stmt = $mysqli->prepare("UPDATE orders SET order_number=?, order_status=?, amount=?, payment_gateway=?, payment_ref=?, paid_at=? WHERE id=? LIMIT 1");
        $stmt->bind_param('ssisssi', $new_order_number, $new_status, $new_amount, $new_payment_gateway, $new_payment_ref, $new_paid_at, $order_id);
        if($stmt->execute()) {
            $success = true;
            $message = $tr[$lang]['success'];

            // فعال/غیرفعال کردن محصول کاربر
            if ($new_status === 'paid') {
                $q = $mysqli->prepare("SELECT id FROM user_products WHERE user_id=? AND product_id=? LIMIT 1");
                $q->bind_param('ii', $user_id, $product_id);
                $q->execute();
                $q->store_result();
                if($q->num_rows === 0) {
                    $insert = $mysqli->prepare("INSERT INTO user_products (user_id, product_id, bought_at) VALUES (?, ?, ?)");
                    $dt = $new_paid_at && $new_paid_at !== '0000-00-00 00:00:00' ? $new_paid_at : date('Y-m-d H:i:s');
                    $insert->bind_param('iis', $user_id, $product_id, $dt);
                    $insert->execute();
                    $insert->close();
                }
                $q->close();
            } else {
                $del = $mysqli->prepare("DELETE FROM user_products WHERE user_id=? AND product_id=?");
                $del->bind_param('ii', $user_id, $product_id);
                $del->execute();
                $del->close();
            }

            // مقادیر جدید را بازخوانی کن
            $order_status = $new_status;
            $amount = $new_amount;
            $payment_gateway = $new_payment_gateway;
            $payment_ref = $new_payment_ref;
            $order_number = $new_order_number;
            $paid_at = $new_paid_at;
        } else {
            $message = $tr[$lang]['error_update'];
        }
        $stmt->close();
    }
}

// بج وضعیت سفارش دوزبانه
function status_badge($status, $lang, $tr) {
    $map = [
        'paid'     => ['paid',     '#38c572'],
        'pending'  => ['pending',  '#f4be42'],
        'canceled' => ['canceled', '#e13a3a'],
        'failed'   => ['failed',   '#a34a4a'],
    ];
    $d = $map[strtolower($status)] ?? ['unknown', '#6c8cff'];
    $label = $tr[$lang][$d[0]] ?? ucfirst($status);
    return '<span style="display:inline-block;min-width:72px;padding:3px 14px;border-radius:8px;background:'.$d[1].';color:#fff;font-weight:700;font-size:.96rem;text-align:center;">'.$label.'</span>';
}
?>
<!DOCTYPE html>
<html lang="<?=$lang?>" dir="<?=$is_rtl?'rtl':'ltr'?>">
<head>
    <meta charset="UTF-8">
    <title><?=$tr[$lang]['edit_order']?> #<?=htmlspecialchars($order_number)?> | XtremeDev Admin</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php include __DIR__.'/../shared/inc/head-assets.php'; ?>
    <?php include 'includes/admin-styles.php'; ?>
    <style>
        body { background: #181f27 !important; color: #e6e9f2 !important; font-family: Vazirmatn, Tahoma, Arial, sans-serif; min-height: 100vh; margin: 0; display: flex; flex-direction: column; }
        .container-main { max-width: 700px; margin:40px auto 0 auto; flex: 1 0 auto; width: 100%; }
        .page-title { font-weight:900; color:#38a8ff; font-size:1.5rem; letter-spacing:.5px; margin-bottom:1.2rem; display: flex; align-items: center; gap: 12px; }
        .card-detail { background: #232d3b; border-radius: 18px; box-shadow: 0 2px 24px #38a8ff14; border: 1.5px solid #29364b; padding: 1.8rem 1.8rem 1.2rem 1.8rem; margin-bottom: 20px; }
        .form-label { color:#aad3ff;font-weight:700;}
        .form-control, .form-select { background: #181f27; color: #e6e9f2; border-color: #31415a; border-radius: 8px; font-size: 1.03rem; margin-bottom: 16px; }
        .form-control:focus, .form-select:focus { border-color:#38a8ff; background:#232d3b; color:#fff; }
        .btn-save { background: #38a8ff; color: #fff; border-radius: 7px; font-weight: 700; border: none; padding: 7px 32px; transition: background .15s; font-size: 1.1rem; }
        .btn-save:hover { background: #2499fa; color: #fff; }
        .msg-success, .msg-error { padding: 12px 22px; border-radius: 8px; font-weight: 700; margin-bottom: 20px; font-size: 1.01rem; }
        .msg-success {background:#38c57233;color:#38c572;border:1.5px solid #38c57288;}
        .msg-error {background:#e13a3a22;color:#e13a3a;border:1.5px solid #e13a3a99;}
        .footer-sticky { flex-shrink: 0; margin-top: auto; width: 100%; background: #232d3b; color: #aad3ff; padding: 18px 0 8px 0; text-align: center; border-top: 1.6px solid #31415a; font-size: 0.95rem; letter-spacing: .2px; }
        @media (max-width: 650px) { .container-main {padding:0 2px;} .page-title {font-size:1.1rem;} .card-detail {padding:1.1rem 0.7rem;} }
    </style>
</head>
<body>
<?php
switch($role) {
    case 'superadmin': include 'includes/superadmin-navbar.php'; break;
    case 'manager':    include 'includes/manager-navbar.php'; break;
    default:           include 'includes/navbar.php';
}
?>
<div class="container-main">
    <div class="page-title">
        <?=$tr[$lang]['edit_order']?>
        <span style="font-size:1.04rem;color:#b9d5f6;font-weight:700;margin-left:8px;">
      [#<?=htmlspecialchars($order_number)?>]
    </span>
    </div>
    <div class="card-detail">
        <?php if($message): ?>
            <div class="<?= $success ? 'msg-success' : 'msg-error' ?>">
                <?=htmlspecialchars($message)?>
            </div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <label class="form-label"><?=$tr[$lang]['order_number']?>:</label>
            <input type="text" name="order_number" class="form-control" value="<?=htmlspecialchars($order_number)?>" required>

            <label class="form-label"><?=$tr[$lang]['status']?>:</label>
            <select name="order_status" class="form-select" required>
                <option value="pending" <?=$order_status=='pending'?'selected':''?>><?=$tr[$lang]['pending']?></option>
                <option value="paid" <?=$order_status=='paid'?'selected':''?>><?=$tr[$lang]['paid']?></option>
                <option value="failed" <?=$order_status=='failed'?'selected':''?>><?=$tr[$lang]['failed']?></option>
                <option value="canceled" <?=$order_status=='canceled'?'selected':''?>><?=$tr[$lang]['canceled']?></option>
            </select>

            <label class="form-label"><?=$tr[$lang]['amount']?>:</label>
            <input type="number" name="amount" class="form-control" value="<?=htmlspecialchars($amount)?>" required>

            <label class="form-label"><?=$tr[$lang]['payment_gateway']?>:</label>
            <input type="text" name="payment_gateway" class="form-control" value="<?=htmlspecialchars($payment_gateway)?>">

            <label class="form-label"><?=$tr[$lang]['payment_ref']?>:</label>
            <input type="text" name="payment_ref" class="form-control" value="<?=htmlspecialchars($payment_ref)?>">

            <label class="form-label"><?=$tr[$lang]['paid_at']?>:</label>
            <input type="text" name="paid_at" class="form-control" value="<?=htmlspecialchars($paid_at)?>">

            <button type="submit" class="btn btn-save mt-2"><?=$tr[$lang]['save']?></button>
            <a href="orders.php" class="btn btn-secondary ms-2"><?=$tr[$lang]['back']?></a>
        </form>
        <hr style="border-color:#31415a;">
        <div style="font-size:1.01rem;color:#aad3ff;">
            <b><?=$tr[$lang]['user']?>:</b> <?=htmlspecialchars($user_name)?> <span style="color:#38a8ff;">[<?=htmlspecialchars($user_email)?>]</span>
            <br>
            <b><?=$tr[$lang]['product']?>:</b> <?=htmlspecialchars($product_title)?>
        </div>
    </div>
</div>
<footer class="footer-sticky">
    &copy; <?=date('Y')?> XtremeDev. All rights reserved.
</footer>
<?php include __DIR__.'/../shared/inc/foot-assets.php'; ?>
</body>
</html>