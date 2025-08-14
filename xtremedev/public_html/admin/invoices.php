<?php
session_start();
if (!isset($_SESSION['admin_user_id']) || !isset($_SESSION['admin_access_token'])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__.'/../shared/inc/config.php';

$username = $_SESSION['admin_username'] ?? '';
$email    = $_SESSION['admin_email'] ?? '';
$role     = $_SESSION['admin_role'] ?? '';
$can_edit = in_array($role, ['superadmin','manager']);
$can_view = in_array($role, ['support','read_only']);
if (!$can_edit && !$can_view) {
    header("Location: access_denied.php");
    exit;
}
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
// --- Get invoices from API ---
$api_url = 'https://api.xtremedev.co/endpoints/admin/list_invoices.php';
$params = ['lang' => 'en'];
if($search !== '') $params['search'] = $search;
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url . ($params ? ('?' . http_build_query($params)) : ''));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $_SESSION['admin_access_token']
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 12);
$resp = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$invoices = [];
if ($httpcode === 200 && $resp) {
    $data = json_decode($resp, true);
    if (is_array($data) && isset($data['invoices']) && is_array($data['invoices'])) {
        $invoices = $data['invoices'];
    }
}
function role_badge($role) {
    $map = [
        'superadmin' => ['Superadmin', '#38a8ff'],
        'manager'    => ['Manager',    '#00e9c2'],
        'support'    => ['Support',    '#ffb13a'],
        'read_only'  => ['Read Only',  '#6c8cff'],
    ];
    $d = $map[$role] ?? ['Unknown', '#aaa'];
    return '<span class="role-badge" style="background:'.$d[1].';">'.$d[0].'</span>';
}
function status_badge($status) {
    $map = [
        'paid'     => ['Paid',     '#38c572'],
        'unpaid'   => ['Unpaid',   '#f4be42'],
        'canceled' => ['Canceled', '#e13a3a'],
    ];
    $d = $map[strtolower($status)] ?? ['Unknown', '#6c8cff'];
    return '<span style="display:inline-block;min-width:64px;padding:6px 19px;border-radius:16px;background:'.$d[1].';color:#fff;font-weight:700;font-size:.99rem;text-align:center;">'.$d[0].'</span>';
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Invoices | XtremeDev Admin</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php include __DIR__.'/../shared/inc/head-assets.php'; ?>
    <?php include 'includes/admin-styles.php'; ?>
    <style>
        :root {
            --primary: #38a8ff;
            --surface: #181f27;
            --surface-alt: #232d3b;
            --text: #e6e9f2;
            --shadow-card: #38a8ff0c;
            --border: #38a8ff22;
        }
        body {
            background: var(--surface, #181f27) !important;
            color: var(--text, #e6e9f2) !important;
            font-family: Vazirmatn, Tahoma, Arial, sans-serif;
            min-height: 100vh;
            display: flex; flex-direction: column;
        }
        .container-main { max-width: 1200px; margin:40px auto 0 auto; flex: 1 0 auto; width: 100%; }
        .page-title { font-weight:900; color:var(--primary, #38a8ff); font-size:1.7rem; letter-spacing:.5px; margin-bottom:1.2rem; display: flex; align-items: center; gap: 12px; }
        .role-badge { display:inline-block; padding: 3px 14px; font-size: 0.98rem; border-radius: 9px; color:#fff; font-weight:700; letter-spacing: .3px; vertical-align:middle; margin-left: 8px; box-shadow:0 1px 6px #22292f1a; }
        .search-box { margin-bottom: 24px; margin-top: 4px; display: flex; justify-content: flex-start; gap: 10px; align-items: flex-start; }
        .search-input {
            background: var(--surface-alt, #232d3b);
            color: var(--primary, #38a8ff);
            border: 1.4px solid var(--border, #38a8ff22);
            border-radius: 11px;
            padding: 8px 18px;
            font-size: 1.07rem;
            min-width: 180px;
            max-width: 250px;
            transition: border .15s, background .15s, color .15s;
            box-shadow: 0 2px 8px var(--shadow-card);
        }
        .search-input:focus {
            outline: none;
            border-color: var(--primary, #38a8ff);
            background: #1e2836;
            color: #fff;
        }
        .search-btn {
            background: var(--primary, #38a8ff);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 8px 24px;
            font-weight: 700;
            font-size:1.02rem;
            transition: background .15s;
        }
        .search-btn:hover { background: #2499fa; color: #fff; }
        .clear-btn {
            background: #29364b;
            color: #aad3ff;
            border: none;
            border-radius: 10px;
            padding: 8px 16px;
            font-weight: 600;
            font-size:1.02rem;
            transition: background .15s;
            margin-left: 2px;
        }
        .clear-btn:hover { background: #202942; color: #fff; }
        .invoices-table-responsive {
            width: 100%;
            overflow-x: auto;
            margin-bottom: 2.5rem;
            margin-top: 2.5rem;
            border-radius: 17px;
            box-shadow: 0 2px 16px var(--shadow-card);
            background: var(--surface-alt, #232d3b);
        }
        table.invoices-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 950px;
            background: transparent;
        }
        table.invoices-table th,
        table.invoices-table td {
            padding: 1.07rem .88rem;
            text-align: center;
        }
        table.invoices-table th {
            background: #1e2836;
            color: var(--primary, #38a8ff);
            font-weight: 900;
            font-size: 1.06rem;
            border-bottom: 2px solid var(--border, #38a8ff22);
        }
        table.invoices-table td {
            font-size: 1.02rem;
            color: var(--text, #e6e9f2);
            border-bottom: 1px solid var(--border, #38a8ff22);
            vertical-align: middle;
        }
        .action-btns { display: flex; flex-direction: row; gap: 9px; justify-content: center; align-items: center; }
        .btn-details {
            background: #43597a;
            color: #fff;
            border-radius: 8px;
            font-weight: 700;
            border: none;
            padding: 5px 22px;
            font-size:1.01rem;
            transition: background .15s;
            text-decoration: none;
        }
        .btn-details:hover { background: #2a3343; color: #fff; text-decoration: none; }
        .btn-edit {
            background: var(--primary, #38a8ff);
            color: #fff;
            border-radius: 8px;
            font-weight: 700;
            border: none;
            padding: 5px 22px;
            font-size:1.01rem;
            transition: background .15s;
            text-decoration: none;
        }
        .btn-edit:hover { background: #2499fa; color: #fff; text-decoration: none; }
        .footer-sticky {
            flex-shrink: 0;
            margin-top: auto;
            width: 100%;
            background: #232d3b;
            color: #aad3ff;
            padding: 18px 0 8px 0;
            text-align: center;
            border-top: 1.6px solid #31415a;
            font-size: 0.95rem;
            letter-spacing: .2px;
        }
        @media (max-width: 600px) {
            .container-main {padding:0 2px;}
            .page-title {font-size:1.1rem;}
            .role-badge {font-size:0.78rem;padding:2px 8px;}
            .search-box {flex-wrap:wrap;}
            .invoices-table-responsive {font-size:0.93rem;}
        }
        @media (max-width: 900px) { .container-main {max-width:99vw;} }
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
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-3">
        <div class="page-title">
            Invoices
            <?=role_badge($role)?>
        </div>
        <div>
            <span style="font-size:1rem;color:#b9d5f6;">
                <b><?=htmlspecialchars($username)?></b> (<span style="color:#38a8ff;"><?=htmlspecialchars($email)?></span>)
            </span>
            <a href="logout.php" class="btn btn-sm btn-danger ms-2">Logout</a>
        </div>
    </div>
    <!-- Search -->
    <form method="get" class="search-box" autocomplete="off">
        <input type="text" name="search" class="search-input" placeholder="Search by invoice/order id, status or user..." value="<?=htmlspecialchars($search)?>">
        <button type="submit" class="search-btn">Search</button>
        <?php if($search): ?>
            <a href="invoices.php" class="clear-btn">Clear</a>
        <?php endif; ?>
    </form>
    <div class="invoices-table-responsive">
        <table class="invoices-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Order ID</th>
                <th>User</th>
                <th>Amount</th>
                <th>Gateway</th>
                <th>Status</th>
                <th>Created</th>
                <th>Paid At</th>
                <th style="width:110px;">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($invoices as $inv): ?>
                <tr>
                    <td><?=htmlspecialchars($inv['id'])?></td>
                    <td><?=htmlspecialchars($inv['order_id'])?></td>
                    <td><?=htmlspecialchars($inv['user_name'])?></td>
                    <td><?=number_format($inv['amount'])?></td>
                    <td><?=htmlspecialchars($inv['payment_gateway'] ?? '')?></td>
                    <td><?=status_badge($inv['status'])?></td>
                    <td><?=htmlspecialchars($inv['created_at'])?></td>
                    <td><?=htmlspecialchars($inv['paid_at'])?></td>
                    <td class="action-btns">
                        <?php if($can_edit): ?>
                            <a href="edit_invoice.php?id=<?=$inv['id']?>" class="btn-edit">Edit</a>
                        <?php else: ?>
                            <a href="invoice_details.php?id=<?=$inv['id']?>" class="btn-details">Details</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if(empty($invoices)): ?>
                <tr><td colspan="9" class="text-center text-muted" style="color:#8ba7c7;font-size:1.15rem;">No invoices found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<footer class="footer-sticky">
    &copy; <?=date('Y')?> XtremeDev. All rights reserved.
</footer>
<?php include __DIR__.'/../shared/inc/foot-assets.php'; ?>
</body>
</html>