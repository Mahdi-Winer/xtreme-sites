<?php
session_start();
if (!isset($_SESSION['admin_user_id'])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__.'/../shared/inc/database-config.php';
require_once __DIR__.'/../shared/inc/config.php';

// زبان
$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'en');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : 'en';
$is_rtl = ($lang === 'fa');
$tr = [
    'en' => [
        'dashboard'      => 'Read Only Dashboard',
        'role'           => 'Read Only',
        'logout'         => 'Logout',
        'users'          => 'Users',
        'orders'         => 'Orders',
        'products'       => 'Products',
        'invoices'       => 'Invoices',
        'tickets'        => 'Tickets',
        'recent_orders'  => 'Recent Orders',
        'recent_invoices'=> 'Recent Invoices',
        'recent_tickets' => 'Recent Tickets',
        'new_users'      => 'New Users',
        'user'           => 'User',
        'product'        => 'Product',
        'status'         => 'Status',
        'created'        => 'Created',
        'amount'         => 'Amount',
        'paid_at'        => 'Paid At',
        'subject'        => 'Subject',
        'email'          => 'Email',
        'id'             => 'ID',
        'invoice_no'     => 'Invoice No.',
        'paid_invoices_30days' => 'Paid Invoices - Last 30 Days',
        'amount_label'   => 'Amount',
        'count_label'    => 'Count',
        'paid'           => 'Paid',
        'unpaid'         => 'Unpaid',
        'canceled'       => 'Canceled',
        'no_data'        => 'No data.',
    ],
    'fa' => [
        'dashboard'      => 'داشبورد فقط‌خواندنی',
        'role'           => 'فقط‌خواندنی',
        'logout'         => 'خروج',
        'users'          => 'کاربران',
        'orders'         => 'سفارشات',
        'products'       => 'محصولات',
        'invoices'       => 'فاکتورها',
        'tickets'        => 'تیکت‌ها',
        'recent_orders'  => 'آخرین سفارشات',
        'recent_invoices'=> 'آخرین فاکتورها',
        'recent_tickets' => 'آخرین تیکت‌ها',
        'new_users'      => 'کاربران جدید',
        'user'           => 'کاربر',
        'product'        => 'محصول',
        'status'         => 'وضعیت',
        'created'        => 'تاریخ',
        'amount'         => 'مبلغ',
        'paid_at'        => 'پرداخت',
        'subject'        => 'موضوع',
        'email'          => 'ایمیل',
        'id'             => 'شناسه',
        'invoice_no'     => 'شماره فاکتور',
        'paid_invoices_30days' => 'فاکتورهای پرداخت‌شده ۳۰ روز اخیر',
        'amount_label'   => 'مبلغ',
        'count_label'    => 'تعداد',
        'paid'           => 'پرداخت‌شده',
        'unpaid'         => 'پرداخت‌نشده',
        'canceled'       => 'لغو شده',
        'no_data'        => 'داده‌ای وجود ندارد.',
    ]
];

$admin_id = $_SESSION['admin_user_id'];
$stmt = $mysqli->prepare("SELECT username, email, role FROM admin_users WHERE id=? LIMIT 1");
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$stmt->bind_result($username, $email, $role);
$stmt->fetch();
$stmt->close();

if ($role !== 'read_only' && $role !== 'superadmin') {
    header("Location: access_denied.php");
    exit;
}

// آمار کلی
function get_count($mysqli, $table) {
    $res = $mysqli->query("SELECT COUNT(*) FROM `$table`");
    if ($res === false) die("SQL Error (count $table): ".$mysqli->error);
    $cnt = $res->fetch_row()[0];
    return $cnt;
}
$users_count    = get_count($mysqli, 'users');
$orders_count   = get_count($mysqli, 'orders');
$products_count = get_count($mysqli, 'products');
$invoices_count = get_count($mysqli, 'invoices');
$tickets_count  = get_count($mysqli, 'support_tickets');

// ستون درست برای نام محصول
$product_title_col = ($lang == 'fa') ? 'title_fa' : 'title_en';

// 5 سفارش اخیر
$recent_orders = [];
$res = $mysqli->query("SELECT o.id, u.email, o.product_id, o.created_at, o.order_status, p.{$product_title_col} as product_name
    FROM orders o
    LEFT JOIN users u ON u.id = o.user_id
    LEFT JOIN products p ON p.id = o.product_id
    ORDER BY o.created_at DESC LIMIT 5");
if ($res === false) die("SQL Error (recent_orders): ".$mysqli->error);
while($row = $res->fetch_assoc()) $recent_orders[] = $row;

// 5 فاکتور اخیر
$recent_invoices = [];
$res = $mysqli->query("SELECT i.id, i.invoice_number, i.amount, i.status, i.paid_at, u.email
    FROM invoices i
    LEFT JOIN users u ON u.id = i.user_id
    ORDER BY i.created_at DESC LIMIT 5");
if ($res === false) die("SQL Error (recent_invoices): ".$mysqli->error);
while($row = $res->fetch_assoc()) $recent_invoices[] = $row;

// 5 تیکت اخیر
$recent_tickets = [];
$res = $mysqli->query("SELECT t.id, t.subject, t.status, t.created_at, u.email
    FROM support_tickets t
    LEFT JOIN users u ON u.id = t.user_id
    ORDER BY t.created_at DESC LIMIT 5");
if ($res === false) die("SQL Error (recent_tickets): ".$mysqli->error);
while($row = $res->fetch_assoc()) $recent_tickets[] = $row;

// 5 کاربر جدید
$new_users = [];
$res = $mysqli->query("SELECT id, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");
if ($res === false) die("SQL Error (new_users): ".$mysqli->error);
while($row = $res->fetch_assoc()) $new_users[] = $row;

// داده‌های نمودار (آخرین 30 روز: مبلغ و تعداد فاکتور Paid)
$chart_labels = [];
$chart_paid_amount = [];
$chart_paid_count = [];
for ($i = 29; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = ($lang=='fa') ? substr($day, 2) : $day;
    $q = $mysqli->query("SELECT COUNT(*) as cnt, SUM(amount) as sum FROM invoices WHERE status='paid' AND DATE(paid_at)='$day'");
    if ($q === false) die("SQL Error (chart_paid): ".$mysqli->error);
    $r = $q->fetch_assoc();
    $chart_paid_count[] = intval($r['cnt']);
    $chart_paid_amount[] = intval($r['sum'] ?: 0);
}
?>
<!DOCTYPE html>
<html lang="<?=$lang?>" dir="<?=$is_rtl?'rtl':'ltr'?>">
<head>
    <meta charset="UTF-8">
    <title><?=$tr[$lang]['dashboard']?> | XtremeDev</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php include __DIR__.'/../shared/inc/head-assets.php'; ?>
    <?php include 'includes/admin-styles.php'; ?>
    <style>
        body { background: #181f27 !important; color: #e6e9f2 !important; font-family: Vazirmatn, Tahoma, Arial, sans-serif; min-height: 100vh; }
        .dashboard-container { max-width: 1320px; margin: 30px auto 0 auto; }
        .dashboard-title { font-weight: 900; color: #38a8ff; font-size: 2.3rem; letter-spacing: .7px; }
        .stat-card { border-radius: 20px; background: #232d3b; box-shadow: 0 2px 24px #22292f26; padding: 1.45rem 1.2rem; color: #fff; margin-bottom: 0; text-align:center; transition: box-shadow 0.2s; border: 1.7px solid #29364b; }
        .stat-card-lg { min-height: 120px; display: flex; flex-direction: column; justify-content: center; font-size: 1.13rem; }
        .card-title { font-size:1.23rem; color: #38a8ff; font-weight: 900; margin-bottom: 0.4rem; letter-spacing: .3px; }
        .stat-value { font-size: 2.6rem; font-weight: 900; color: #38a8ff; margin-top: 0.4rem; }
        .chart-card { min-height: 335px; }
        .table, .table thead th, .table tbody td { background: transparent !important; color: #e6e9f2 !important; border-color: #31415a !important; }
        .table thead th { font-weight: 900; color: #38a8ff !important; font-size: 1.01rem; background: #181f27 !important; border-bottom-width: 2.5px; }
        .table td, .table th { vertical-align: middle; }
        .table-responsive { background: transparent; }
        .mini-table tr { transition: background .12s; }
        .mini-table tr:hover { background: #273143 !important; }
        .badge-paid { background:#35c452; color: #fff; font-weight:700; font-size: .97rem; padding: 5px 14px; border-radius: 10px; }
        .badge-unpaid { background:#e13a3a; color: #fff; font-weight:700; font-size: .97rem; padding: 5px 14px; border-radius: 10px; }
        .badge-canceled { background:#888; color: #fff; font-weight:700; font-size: .97rem; padding: 5px 14px; border-radius: 10px; }
        @media (max-width: 991px) { .stat-card-lg {min-height:unset;} .dashboard-title {font-size:1.6rem;} .stat-value {font-size:2rem;} .stat-card {padding:1rem 0.6rem;} .chart-card {min-height: 260px;} }
        @media (max-width: 600px) { .dashboard-container {padding: 0 2px;} .dashboard-title {font-size:1.13rem;} .stat-card {padding:0.7rem 0.4rem;} }
    </style>
</head>
<body>
<?php include 'includes/readonly-navbar.php'; ?>
<div class="dashboard-container container-fluid px-1 px-md-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-3">
        <div>
            <span class="dashboard-title"><?=$tr[$lang]['dashboard']?></span>
            <span class="role-badge ms-2"><?=$tr[$lang]['role']?></span>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
      <span style="font-size:.98rem;color:#b9d5f6;">
        <b><?=htmlspecialchars($username)?></b> (<span style="color:#38a8ff;"><?=htmlspecialchars($email)?></span>)
      </span>
            <a href="logout.php" class="btn btn-sm btn-danger ms-2"><?=$tr[$lang]['logout']?></a>
        </div>
    </div>
    <!-- آمار کلی -->
    <div class="row g-4 mb-4 align-items-stretch">
        <div class="col-6 col-md-2 flex-grow-1">
            <div class="stat-card stat-card-lg"><div class="card-title"><?=$tr[$lang]['users']?></div><div class="stat-value"><?=number_format($users_count)?></div></div>
        </div>
        <div class="col-6 col-md-2 flex-grow-1">
            <div class="stat-card stat-card-lg"><div class="card-title"><?=$tr[$lang]['orders']?></div><div class="stat-value"><?=number_format($orders_count)?></div></div>
        </div>
        <div class="col-6 col-md-2 flex-grow-1">
            <div class="stat-card stat-card-lg"><div class="card-title"><?=$tr[$lang]['products']?></div><div class="stat-value"><?=number_format($products_count)?></div></div>
        </div>
        <div class="col-6 col-md-2 flex-grow-1">
            <div class="stat-card stat-card-lg"><div class="card-title"><?=$tr[$lang]['invoices']?></div><div class="stat-value"><?=number_format($invoices_count)?></div></div>
        </div>
        <div class="col-6 col-md-2 flex-grow-1">
            <div class="stat-card stat-card-lg"><div class="card-title"><?=$tr[$lang]['tickets']?></div><div class="stat-value"><?=number_format($tickets_count)?></div></div>
        </div>
    </div>
    <!-- نمودار فاکتورهای پرداخت‌شده 30 روز اخیر -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="stat-card chart-card">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                    <span class="card-title"><?=$tr[$lang]['paid_invoices_30days']?></span>
                </div>
                <div id="chart30Echart" style="width:100%;height:240px;min-height:160px;"></div>
            </div>
        </div>
    </div>
    <!-- Recent Orders -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="stat-card">
                <div class="card-title mb-2"><?=$tr[$lang]['recent_orders']?></div>
                <div class="table-responsive">
                    <table class="table table-sm mini-table mb-0">
                        <thead>
                        <tr>
                            <th><?=$tr[$lang]['id']?></th>
                            <th><?=$tr[$lang]['user']?></th>
                            <th><?=$tr[$lang]['product']?></th>
                            <th><?=$tr[$lang]['status']?></th>
                            <th><?=$tr[$lang]['created']?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($recent_orders as $order): ?>
                            <tr>
                                <td><?=$order['id']?></td>
                                <td><?=htmlspecialchars($order['email'])?></td>
                                <td><?=htmlspecialchars($order['product_name'])?></td>
                                <td><?=ucfirst($order['order_status'])?></td>
                                <td><?=htmlspecialchars($order['created_at'])?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- Recent Invoices -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="stat-card">
                <div class="card-title mb-2"><?=$tr[$lang]['recent_invoices']?></div>
                <div class="table-responsive">
                    <table class="table table-sm mini-table mb-0">
                        <thead>
                        <tr>
                            <th><?=$tr[$lang]['invoice_no']?></th>
                            <th><?=$tr[$lang]['user']?></th>
                            <th><?=$tr[$lang]['amount']?></th>
                            <th><?=$tr[$lang]['status']?></th>
                            <th><?=$tr[$lang]['paid_at']?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($recent_invoices as $inv): ?>
                            <tr>
                                <td><?=htmlspecialchars($inv['invoice_number'])?></td>
                                <td><?=htmlspecialchars($inv['email'])?></td>
                                <td><?=number_format($inv['amount'])?></td>
                                <td>
                                    <span class="badge badge-<?=$inv['status']?>"><?=$tr[$lang][$inv['status']] ?? ucfirst($inv['status'])?></span>
                                </td>
                                <td><?=htmlspecialchars($inv['paid_at'] ?: '-')?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- Recent Tickets -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="stat-card">
                <div class="card-title mb-2"><?=$tr[$lang]['recent_tickets']?></div>
                <div class="table-responsive">
                    <table class="table table-sm mini-table mb-0">
                        <thead>
                        <tr>
                            <th><?=$tr[$lang]['id']?></th>
                            <th><?=$tr[$lang]['user']?></th>
                            <th><?=$tr[$lang]['subject']?></th>
                            <th><?=$tr[$lang]['status']?></th>
                            <th><?=$tr[$lang]['created']?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($recent_tickets as $t): ?>
                            <tr>
                                <td><?=$t['id']?></td>
                                <td><?=htmlspecialchars($t['email'])?></td>
                                <td><?=htmlspecialchars($t['subject'])?></td>
                                <td><?=ucfirst($t['status'])?></td>
                                <td><?=htmlspecialchars($t['created_at'])?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- New Users -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="stat-card">
                <div class="card-title mb-2"><?=$tr[$lang]['new_users']?></div>
                <div class="table-responsive">
                    <table class="table table-sm mini-table mb-0">
                        <thead>
                        <tr>
                            <th><?=$tr[$lang]['id']?></th>
                            <th><?=$tr[$lang]['email']?></th>
                            <th><?=$tr[$lang]['created']?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($new_users as $user): ?>
                            <tr>
                                <td><?=$user['id']?></td>
                                <td><?=htmlspecialchars($user['email'])?></td>
                                <td><?=htmlspecialchars($user['created_at'])?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
<?php include __DIR__.'/../shared/inc/foot-assets.php'; ?>
<script>
    const chartLabels30 = <?=json_encode($chart_labels)?>;
    const chartPaidAmount30 = <?=json_encode($chart_paid_amount)?>;
    const labelAmount = <?=json_encode($tr[$lang]['amount_label'])?>;

    document.addEventListener("DOMContentLoaded", function() {
        var chart30Echart = echarts.init(document.getElementById('chart30Echart'), null, {renderer: 'canvas', useDirtyRect: false});
        var option = {
            tooltip: {
                trigger: 'axis',
                backgroundColor: '#232d3b',
                borderColor: '#31415a',
                borderWidth: 1,
                textStyle: { color: '#fff', fontFamily: 'Vazirmatn, Tahoma, Arial' }
            },
            grid: { left: 40, right: 20, top: 24, bottom: 30, containLabel: true },
            xAxis: {
                type: 'category',
                data: chartLabels30,
                axisLabel: { color: '#38a8ff', fontFamily: 'Vazirmatn', rotate: 30 },
                axisLine: { lineStyle: { color: '#38a8ff' } }
            },
            yAxis: {
                type: 'value',
                axisLabel: { color: '#e6e9f2', fontFamily: 'Vazirmatn' },
                splitLine: { lineStyle: { color: '#25304a' } }
            },
            series: [{
                name: labelAmount,
                data: chartPaidAmount30,
                type: 'line',
                smooth: true,
                areaStyle: { color: '#38a8ff33' },
                lineStyle: { color: '#38a8ff', width: 2.5 },
                itemStyle: { color: '#38a8ff' },
                symbol: 'circle',
                symbolSize: 6
            }],
            animationDuration: 700
        };
        chart30Echart.setOption(option);
        window.addEventListener('resize', function() { chart30Echart.resize(); });
    });
</script>
</body>
</html>