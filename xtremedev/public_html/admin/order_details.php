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
        'order_details'   => 'Order Details',
        'order_id'        => 'Order ID',
        'order_number'    => 'Order Number',
        'status'          => 'Status',
        'amount'          => 'Amount',
        'irr'             => 'IRR',
        'payment_gateway' => 'Payment Gateway',
        'payment_ref'     => 'Payment Ref',
        'created_at'      => 'Created At',
        'paid_at'         => 'Paid At',
        'user'            => 'User',
        'product'         => 'Product',
        'edit_user'       => 'Edit User',
        'edit_product'    => 'Edit Product',
        'back'            => 'Back to orders list',
        'paid'            => 'Paid',
        'pending'         => 'Pending',
        'canceled'        => 'Canceled',
        'failed'          => 'Failed',
        'all_rights'      => 'All rights reserved.',
    ],
    'fa' => [
        'order_details'   => 'جزئیات سفارش',
        'order_id'        => 'شماره سفارش',
        'order_number'    => 'کد سفارش',
        'status'          => 'وضعیت',
        'amount'          => 'مبلغ',
        'irr'             => 'ریال',
        'payment_gateway' => 'درگاه پرداخت',
        'payment_ref'     => 'کد تراکنش',
        'created_at'      => 'تاریخ ایجاد',
        'paid_at'         => 'تاریخ پرداخت',
        'user'            => 'کاربر',
        'product'         => 'محصول',
        'edit_user'       => 'ویرایش کاربر',
        'edit_product'    => 'ویرایش محصول',
        'back'            => 'بازگشت به لیست سفارش‌ها',
        'paid'            => 'پرداخت شده',
        'pending'         => 'در انتظار',
        'canceled'        => 'لغو شده',
        'failed'          => 'ناموفق',
        'all_rights'      => 'تمامی حقوق محفوظ است.',
    ]
];

// دسترسی نقش‌ها
$admin_id = $_SESSION['admin_user_id'];
$stmt = $mysqli->prepare("SELECT username, email, role FROM admin_users WHERE id=? LIMIT 1");
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$stmt->bind_result($username, $email, $role);
$stmt->fetch();
$stmt->close();

$can_edit = in_array($role, ['superadmin','manager']);
$can_view = in_array($role, ['support','read_only']);
if (!$can_edit && !$can_view) {
    header("Location: access_denied.php");
    exit;
}

// گرفتن آی‌دی سفارش
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($order_id <= 0) {
    header("Location: orders.php");
    exit;
}

// دریافت اطلاعات سفارش (عنوان محصول دوزبانه)
$title_col = ($lang=='fa' ? 'p.title_fa' : 'p.title_en');
$stmt = $mysqli->prepare("
    SELECT o.id, o.order_number, u.id, u.name, u.email, p.id, $title_col, o.amount, o.payment_gateway, o.payment_ref, o.order_status, o.created_at, o.paid_at
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

// بج وضعیت دوزبانه
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
    <title><?=$tr[$lang]['order_details']?> #<?=htmlspecialchars($order_number)?> | XtremeDev Admin</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php include __DIR__.'/../shared/inc/head-assets.php'; ?>
    <?php include 'includes/admin-styles.php'; ?>
    <style>
        html, body { height: 100%; }
        body { background: #181f27 !important; color: #e6e9f2 !important; font-family: Vazirmatn, Tahoma, Arial, sans-serif; min-height: 100vh; margin: 0; display: flex; flex-direction: column; }
        .container-main { max-width: 700px; margin:40px auto 0 auto; flex: 1 0 auto; width: 100%; }
        .page-title { font-weight:900; color:#38a8ff; font-size:1.5rem; letter-spacing:.5px; margin-bottom:1.2rem; display: flex; align-items: center; gap: 12px; }
        .card-detail { background: #232d3b; border-radius: 18px; box-shadow: 0 2px 24px #38a8ff14; border: 1.5px solid #29364b; padding: 1.8rem 1.8rem 1.2rem 1.8rem; margin-bottom: 20px; }
        .detail-row { display: flex; align-items: center; margin-bottom: 1.1rem; flex-wrap: wrap; }
        .detail-label { font-weight: bold; color: #aad3ff; width: 170px; min-width: 120px; font-size: 1.02rem; }
        .detail-value { font-size: 1.07rem; color: #e6e9f2; word-break: break-word; }
        .back-link { color:#aad3ff; text-decoration:none; margin-top:18px; display:inline-block; font-size:1.02rem; margin-bottom:4px; }
        .back-link:hover {color:#fff;text-decoration:underline;}
        .footer-sticky { flex-shrink: 0; margin-top: auto; width: 100%; background: #232d3b; color: #aad3ff; padding: 18px 0 8px 0; text-align: center; border-top: 1.6px solid #31415a; font-size: 0.95rem; letter-spacing: .2px; }
        @media (max-width: 650px) { .container-main {padding:0 2px;} .page-title {font-size:1.1rem;} .card-detail {padding:1.1rem 0.7rem;} .detail-label {width:120px;font-size:0.98rem;} .detail-value {font-size:.96rem;} }
    </style>
</head>
<body>
<?php
switch($role) {
    case 'superadmin': include 'includes/superadmin-navbar.php'; break;
    case 'manager':    include 'includes/manager-navbar.php'; break;
    case 'support':    include 'includes/supporter-navbar.php'; break;
    case 'read_only':  include 'includes/readonly-navbar.php'; break;
    default:           include 'includes/navbar.php';
}
?>
<div class="container-main">
    <div class="page-title">
        <?=$tr[$lang]['order_details']?>
        <span style="font-size:1.04rem;color:#b9d5f6;font-weight:700;margin-left:8px;">
      [#<?=htmlspecialchars($order_number)?>]
    </span>
    </div>
    <div class="card-detail">
        <div class="detail-row">
            <div class="detail-label"><?=$tr[$lang]['order_id']?>:</div>
            <div class="detail-value"><?=htmlspecialchars($oid)?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><?=$tr[$lang]['order_number']?>:</div>
            <div class="detail-value"><?=htmlspecialchars($order_number)?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><?=$tr[$lang]['status']?>:</div>
            <div class="detail-value"><?=status_badge($order_status, $lang, $tr)?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><?=$tr[$lang]['amount']?>:</div>
            <div class="detail-value"><?=number_format($amount)?> <span style="color:#f4be42;font-size:.95em;"><?=$tr[$lang]['irr']?></span></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><?=$tr[$lang]['payment_gateway']?>:</div>
            <div class="detail-value"><?=htmlspecialchars($payment_gateway)?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><?=$tr[$lang]['payment_ref']?>:</div>
            <div class="detail-value"><?=htmlspecialchars($payment_ref)?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><?=$tr[$lang]['created_at']?>:</div>
            <div class="detail-value"><?=htmlspecialchars($created_at)?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><?=$tr[$lang]['paid_at']?>:</div>
            <div class="detail-value"><?=htmlspecialchars($paid_at)?></div>
        </div>
        <hr style="border-color:#31415a;">
        <div class="detail-row">
            <div class="detail-label"><?=$tr[$lang]['user']?>:</div>
            <div class="detail-value">
                <?=htmlspecialchars($user_name)?>
                <span style="color:#38a8ff;">[<?=htmlspecialchars($user_email)?>]</span>
                <a href="edit_user.php?id=<?=$user_id?>" style="color:#aad3ff;font-size:.95em;text-decoration:underline;"><?=$tr[$lang]['edit_user']?></a>
            </div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><?=$tr[$lang]['product']?>:</div>
            <div class="detail-value">
                <?=htmlspecialchars($product_title)?>
                <a href="edit_product.php?id=<?=$product_id?>" style="color:#aad3ff;font-size:.95em;text-decoration:underline;"><?=$tr[$lang]['edit_product']?></a>
            </div>
        </div>
    </div>
    <a href="orders.php" class="back-link">&larr; <?=$tr[$lang]['back']?></a>
</div>
<footer class="footer-sticky">
    &copy; <?=date('Y')?> XtremeDev. <?=$tr[$lang]['all_rights']?>
</footer>
<?php include __DIR__.'/../shared/inc/foot-assets.php'; ?>
</body>
</html>