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
        'edit_invoice'  => 'Edit Invoice',
        'invoice_number'=> 'Invoice Number',
        'amount'        => 'Amount (IRR)',
        'status'        => 'Status',
        'unpaid'        => 'Unpaid',
        'paid'          => 'Paid',
        'canceled'      => 'Canceled',
        'paid_at'       => 'Paid At (YYYY-MM-DD hh:mm:ss)',
        'description'   => 'Description',
        'save'          => 'Save Changes',
        'back'          => 'Back',
        'user'          => 'User',
        'order'         => 'Order',
        'success'       => 'Invoice updated successfully.',
        'invalid_status'=> 'The status is invalid!',
        'error_update'  => 'Error updating invoice!',
    ],
    'fa' => [
        'edit_invoice'  => 'ویرایش فاکتور',
        'invoice_number'=> 'شماره فاکتور',
        'amount'        => 'مبلغ (ریال)',
        'status'        => 'وضعیت',
        'unpaid'        => 'پرداخت نشده',
        'paid'          => 'پرداخت شده',
        'canceled'      => 'لغو شده',
        'paid_at'       => 'تاریخ پرداخت (YYYY-MM-DD hh:mm:ss)',
        'description'   => 'توضیحات',
        'save'          => 'ذخیره تغییرات',
        'back'          => 'بازگشت',
        'user'          => 'کاربر',
        'order'         => 'سفارش',
        'success'       => 'فاکتور با موفقیت ویرایش شد.',
        'invalid_status'=> 'وضعیت نامعتبر است!',
        'error_update'  => 'خطا در ویرایش فاکتور!',
    ]
];

// فقط سوپرادمین و منیجر اجازه دارند
$admin_id = $_SESSION['admin_user_id'];
$stmt = $mysqli->prepare("SELECT username, email, role FROM admin_users WHERE id=? LIMIT 1");
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$stmt->bind_result($username, $email, $role);
$stmt->fetch();
$stmt->close();

if (!in_array($role, ['superadmin','manager'])) {
    header("Location: access_denied.php");
    exit;
}

// دریافت فاکتور
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

$message = "";
$success = false;

// اگر فرم ارسال شده باشد
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_invoice_number = trim($_POST['invoice_number']);
    $new_amount = intval($_POST['amount']);
    $new_status = $_POST['status'] ?? $status;
    $new_description = trim($_POST['description']);
    $new_paid_at = trim($_POST['paid_at']);

    if (!in_array($new_status, ['unpaid', 'paid', 'canceled'])) {
        $message = $tr[$lang]['invalid_status'];
    } else {
        $stmt = $mysqli->prepare("UPDATE invoices SET invoice_number=?, amount=?, status=?, paid_at=?, description=? WHERE id=? LIMIT 1");
        $stmt->bind_param('sisssi', $new_invoice_number, $new_amount, $new_status, $new_paid_at, $new_description, $invoice_id);
        if($stmt->execute()) {
            $success = true;
            $message = $tr[$lang]['success'];
            $invoice_number = $new_invoice_number;
            $amount = $new_amount;
            $status = $new_status;
            $description = $new_description;
            $paid_at = $new_paid_at;
        } else {
            $message = $tr[$lang]['error_update'];
        }
        $stmt->close();
    }
}

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
    <title><?=$tr[$lang]['edit_invoice']?> #<?=htmlspecialchars($invoice_number)?> | XtremeDev Admin</title>
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
        <?=$tr[$lang]['edit_invoice']?>
        <span style="font-size:1.04rem;color:#b9d5f6;font-weight:700;margin-left:8px;">
      [#<?=htmlspecialchars($invoice_number)?>]
    </span>
    </div>
    <div class="card-detail">
        <?php if($message): ?>
            <div class="<?= $success ? 'msg-success' : 'msg-error' ?>">
                <?=htmlspecialchars($message)?>
            </div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <label class="form-label"><?=$tr[$lang]['invoice_number']?>:</label>
            <input type="text" name="invoice_number" class="form-control" value="<?=htmlspecialchars($invoice_number)?>" required>

            <label class="form-label"><?=$tr[$lang]['amount']?>:</label>
            <input type="number" name="amount" class="form-control" value="<?=htmlspecialchars($amount)?>" required>

            <label class="form-label"><?=$tr[$lang]['status']?>:</label>
            <select name="status" class="form-select" required>
                <option value="unpaid" <?=$status=='unpaid'?'selected':''?>><?=$tr[$lang]['unpaid']?></option>
                <option value="paid" <?=$status=='paid'?'selected':''?>><?=$tr[$lang]['paid']?></option>
                <option value="canceled" <?=$status=='canceled'?'selected':''?>><?=$tr[$lang]['canceled']?></option>
            </select>

            <label class="form-label"><?=$tr[$lang]['paid_at']?>:</label>
            <input type="text" name="paid_at" class="form-control" value="<?=htmlspecialchars($paid_at)?>">

            <label class="form-label"><?=$tr[$lang]['description']?>:</label>
            <input type="text" name="description" class="form-control" value="<?=htmlspecialchars($description)?>">

            <button type="submit" class="btn btn-save mt-2"><?=$tr[$lang]['save']?></button>
            <a href="invoices.php" class="btn btn-secondary ms-2"><?=$tr[$lang]['back']?></a>
        </form>
        <hr style="border-color:#31415a;">
        <div style="font-size:1.01rem;color:#aad3ff;">
            <b><?=$tr[$lang]['user']?>:</b> <?=htmlspecialchars($user_name)?> <span style="color:#38a8ff;">[<?=htmlspecialchars($user_email)?>]</span>
            <br>
            <b><?=$tr[$lang]['order']?>:</b>
            <?php if($order_id): ?>
                <a href="order_details.php?id=<?=$order_id?>" style="color:#aad3ff;font-size:.95em;text-decoration:underline;">#<?=$order_id?></a>
            <?php else: ?>
                ---
            <?php endif; ?>
        </div>
    </div>
</div>
<footer class="footer-sticky">
    &copy; <?=date('Y')?> XtremeDev. All rights reserved.
</footer>
<?php include __DIR__.'/../shared/inc/foot-assets.php'; ?>
</body>
</html>