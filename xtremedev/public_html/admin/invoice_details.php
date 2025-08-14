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
        'invoice_details'=> 'Invoice Details',
        'invoice_id'     => 'Invoice ID',
        'invoice_number' => 'Invoice Number',
        'status'         => 'Status',
        'amount'         => 'Amount',
        'toman'          => 'Toman',
        'created_at'     => 'Created At',
        'paid_at'        => 'Paid At',
        'description'    => 'Description',
        'user'           => 'User',
        'order'          => 'Order',
        'edit_user'      => 'Edit User',
        'back'           => 'Back to invoices list',
        'paid'           => 'Paid',
        'unpaid'         => 'Unpaid',
        'canceled'       => 'Canceled',
        'all_rights'     => 'All rights reserved.',
    ],
    'fa' => [
        'invoice_details'=> 'جزئیات فاکتور',
        'invoice_id'     => 'شماره فاکتور',
        'invoice_number' => 'کد فاکتور',
        'status'         => 'وضعیت',
        'amount'         => 'مبلغ',
        'toman'          => 'تومان',
        'created_at'     => 'تاریخ ایجاد',
        'paid_at'        => 'تاریخ پرداخت',
        'description'    => 'توضیحات',
        'user'           => 'کاربر',
        'order'          => 'سفارش',
        'edit_user'      => 'ویرایش کاربر',
        'back'           => 'بازگشت به لیست فاکتورها',
        'paid'           => 'پرداخت شده',
        'unpaid'         => 'پرداخت نشده',
        'canceled'       => 'لغو شده',
        'all_rights'     => 'تمامی حقوق محفوظ است.',
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

// گرفتن آی‌دی فاکتور
$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($invoice_id <= 0) {
    header("Location: invoices.php");
    exit;
}

// دریافت اطلاعات فاکتور
$stmt = $mysqli->prepare("
    SELECT i.id, i.invoice_number, i.user_id, u.name, u.email, i.order_id, i.amount, i.status, i.created_at, i.paid_at, i.description
    FROM invoices i
    LEFT JOIN users u ON i.user_id = u.id
    WHERE i.id=?
    LIMIT 1
");
$stmt->bind_param('i', $invoice_id);
$stmt->execute();
$stmt->bind_result(
    $id, $invoice_number, $user_id, $user_name, $user_email,
    $order_id, $amount, $status, $created_at, $paid_at, $description
);
if(!$stmt->fetch()) {
    $stmt->close();
    header("Location: invoices.php");
    exit;
}
$stmt->close();

// بج وضعیت دوزبانه
function status_badge($status, $lang, $tr) {
    $map = [
        'paid'     => ['paid',     '#38c572'],
        'unpaid'   => ['unpaid',   '#f4be42'],
        'canceled' => ['canceled', '#e13a3a'],
    ];
    $d = $map[strtolower($status)] ?? ['unknown', '#6c8cff'];
    $label = $tr[$lang][$d[0]] ?? ucfirst($status);
    return '<span style="display:inline-block;min-width:64px;padding:3px 14px;border-radius:8px;background:'.$d[1].';color:#fff;font-weight:700;font-size:.96rem;text-align:center;">'.$label.'</span>';
}
?>
<!DOCTYPE html>
<html lang="<?=$lang?>" dir="<?=$is_rtl?'rtl':'ltr'?>">
<head>
    <meta charset="UTF-8">
    <title><?=$tr[$lang]['invoice_details']?> #<?=htmlspecialchars($invoice_number)?> | XtremeDev Admin</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php include __DIR__.'/../shared/inc/head-assets.php'; ?>
    <?php include 'includes/admin-styles.php'; ?>
    <style>
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
        <?=$tr[$lang]['invoice_details']?>
        <span style="font-size:1.04rem;color:#b9d5f6;font-weight:700;margin-left:8px;">
      [#<?=htmlspecialchars($invoice_number)?>]
    </span>
    </div>
    <div class="card-detail">
        <div class="detail-row">
            <div class="detail-label"><?=$tr[$lang]['invoice_id']?>:</div>
            <div class="detail-value"><?=htmlspecialchars($id)?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><?=$tr[$lang]['invoice_number']?>:</div>
            <div class="detail-value"><?=htmlspecialchars($invoice_number)?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><?=$tr[$lang]['status']?>:</div>
            <div class="detail-value"><?=status_badge($status, $lang, $tr)?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><?=$tr[$lang]['amount']?>:</div>
            <div class="detail-value"><?=number_format($amount)?> <span style="color:#f4be42;font-size:.95em;"><?=$tr[$lang]['toman']?></span></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><?=$tr[$lang]['created_at']?>:</div>
            <div class="detail-value"><?=htmlspecialchars($created_at)?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><?=$tr[$lang]['paid_at']?>:</div>
            <div class="detail-value"><?=htmlspecialchars($paid_at)?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label"><?=$tr[$lang]['description']?>:</div>
            <div class="detail-value"><?=htmlspecialchars($description)?></div>
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
            <div class="detail-label"><?=$tr[$lang]['order']?>:</div>
            <div class="detail-value">
                <?php if($order_id): ?>
                    <a href="order_details.php?id=<?=$order_id?>" style="color:#aad3ff;font-size:.95em;text-decoration:underline;">#<?=$order_id?></a>
                <?php else: ?>
                    ---
                <?php endif; ?>
            </div>
        </div>
    </div>
    <a href="invoices.php" class="back-link">&larr; <?=$tr[$lang]['back']?></a>
</div>
<footer class="footer-sticky">
    &copy; <?=date('Y')?> XtremeDev. <?=$tr[$lang]['all_rights']?>
</footer>
<?php include __DIR__.'/../shared/inc/foot-assets.php'; ?>
</body>
</html>