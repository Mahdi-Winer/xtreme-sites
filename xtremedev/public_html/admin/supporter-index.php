<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['admin_user_id'])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__.'/../shared/inc/database-config.php';
require_once __DIR__.'/../shared/inc/config.php';

function to_jalali($date_gregorian) {
    if(!$date_gregorian) return '';
    $parts = explode(' ', $date_gregorian);
    $date = $parts[0];
    list($gy,$gm,$gd) = explode('-', $date);
    return gregorian_to_jalali_simple($gy, $gm, $gd) . (isset($parts[1]) && $parts[1]!='00:00:00' ? ' '.substr($parts[1],0,5) : '');
}
function gregorian_to_jalali_simple($gy, $gm, $gd) {
    $g_days_in_month = [31,28,31,30,31,30,31,31,30,31,30,31];
    $j_days_in_month = [31,31,31,31,31,31,30,30,30,30,30,29];
    $gy = intval($gy); $gm = intval($gm); $gd = intval($gd);
    $gy2 = ($gm > 2)? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + intval(($gy2 + 3) / 4) - intval(($gy2 + 99) / 100)
        + intval(($gy2 + 399) / 400) + $gd;
    for ($i=0; $i < $gm - 1; ++$i)
        $days += $g_days_in_month[$i];
    $jy = -1595 + (33 * intval($days / 12053));
    $days %= 12053;
    $jy += 4 * intval($days/1461);
    $days %= 1461;
    if ($days > 365) {
        $jy += intval(($days - 1) / 365);
        $days = ($days - 1) % 365;
    }
    for ($j=0; $j < 11 && $days >= $j_days_in_month[$j]; ++$j)
        $days -= $j_days_in_month[$j];
    $jm = $j + 1;
    $jd = $days + 1;
    return sprintf("%04d/%02d/%02d", $jy, $jm, $jd);
}

// زبان و ترجمه
$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'en');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : 'en';
$is_rtl = ($lang === 'fa');
$tr = [
    'en' => [
        'dashboard'      => 'Supporter Dashboard',
        'role'           => 'Supporter',
        'logout'         => 'Logout',
        'users'          => 'Users',
        'tickets'        => 'Tickets',
        'orders'         => 'Orders',
        'paid_invoices'  => 'Paid Invoices',
        'recent_tickets' => 'Recent Tickets',
        'recent_orders'  => 'Recent Orders',
        'new_users'      => 'New Users',
        'user'           => 'User',
        'subject'        => 'Subject',
        'status'         => 'Status',
        'created'        => 'Created',
        'product'        => 'Product',
        'amount'         => 'Amount',
        'id'             => 'ID',
        'email'          => 'Email',
        'open'           => 'Open',
        'closed'         => 'Closed',
        'pending'        => 'Pending',
        'no_data'        => 'No data.',
        'brand'          => 'XtremeDev Admin',
        'profile'        => 'Profile',
        'tickets_30days'  => 'New Tickets - Last 30 Days',
    ],
    'fa' => [
        'dashboard'      => 'داشبورد پشتیبان',
        'role'           => 'پشتیبان',
        'logout'         => 'خروج',
        'users'          => 'کاربران',
        'tickets'        => 'تیکت‌ها',
        'orders'         => 'سفارشات',
        'paid_invoices'  => 'فاکتورهای پرداخت‌شده',
        'recent_tickets' => 'آخرین تیکت‌ها',
        'recent_orders'  => 'آخرین سفارشات',
        'new_users'      => 'کاربران جدید',
        'user'           => 'کاربر',
        'subject'        => 'موضوع',
        'status'         => 'وضعیت',
        'created'        => 'تاریخ',
        'product'        => 'محصول',
        'amount'         => 'مبلغ',
        'id'             => 'شناسه',
        'email'          => 'ایمیل',
        'open'           => 'باز',
        'closed'         => 'بسته',
        'pending'        => 'در انتظار',
        'no_data'        => 'داده‌ای وجود ندارد.',
        'brand'          => 'پنل ادمین XtremeDev',
        'profile'        => 'پروفایل',
        'tickets_30days' => 'تیکت‌های جدید ۳۰ روز اخیر',
    ]
];

// اطلاعات ادمین
$admin_id = $_SESSION['admin_user_id'];
$stmt = $mysqli->prepare("SELECT username, email, role FROM admin_users WHERE id=? LIMIT 1");
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$stmt->bind_result($username, $email, $role);
$stmt->fetch();
$stmt->close();

if ($role !== 'support' && $role !== 'superadmin') {
    header("Location: access_denied.php");
    exit;
}

// آمار کلی
function get_count($mysqli, $table, $where = '1') {
    $res = $mysqli->query("SELECT COUNT(*) FROM `$table` WHERE $where");
    $cnt = $res ? $res->fetch_row()[0] : 0;
    return $cnt;
}
$users_count = get_count($mysqli, 'users');
$tickets_count = get_count($mysqli, 'support_tickets');
$orders_count = get_count($mysqli, 'orders');
$paid_invoices_count = get_count($mysqli, 'invoices', "status='paid'");

// ۵ تیکت اخیر
$recent_tickets = [];
$sql_recent_tickets = "SELECT t.id, t.subject, t.status, t.created_at, u.email
    FROM support_tickets t
    LEFT JOIN users u ON u.id = t.user_id
    ORDER BY t.created_at DESC LIMIT 5";
$res = $mysqli->query($sql_recent_tickets);
if (!$res) {
    echo "<div style='color:red'>SQL Error recent_tickets: ".$mysqli->error."</div>";
} else {
    while($row = $res->fetch_assoc()) $recent_tickets[] = $row;
}

// ۵ سفارش اخیر
$recent_orders = [];
$sql_recent_orders = "SELECT o.id, u.email, o.product_id, o.created_at, o.order_status, p.title_" . ($lang=='fa'?'fa':'en') . " as product_name
    FROM orders o
    LEFT JOIN users u ON u.id = o.user_id
    LEFT JOIN products p ON p.id = o.product_id
    ORDER BY o.created_at DESC LIMIT 5";
$res = $mysqli->query($sql_recent_orders);
if (!$res) {
    echo "<div style='color:red'>SQL Error recent_orders: ".$mysqli->error."</div>";
} else {
    while($row = $res->fetch_assoc()) $recent_orders[] = $row;
}

// ۵ کاربر جدید
$new_users = [];
$sql_new_users = "SELECT id, email, created_at FROM users ORDER BY created_at DESC LIMIT 5";
$res = $mysqli->query($sql_new_users);
if (!$res) {
    echo "<div style='color:red'>SQL Error new_users: ".$mysqli->error."</div>";
} else {
    while($row = $res->fetch_assoc()) $new_users[] = $row;
}

// داده‌های نمودار تیکت‌های جدید (۳۰ روز اخیر)
$chart_labels = [];
$chart_tickets = [];
for ($i = 29; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = ($lang=='fa') ? to_jalali($day) : $day;
    $q = $mysqli->query("SELECT COUNT(*) as cnt FROM support_tickets WHERE DATE(created_at)='$day'");
    if (!$q) {
        echo "<div style='color:red'>SQL Error chart_tickets: ".$mysqli->error."</div>";
        $chart_tickets[] = 0;
    } else {
        $r = $q->fetch_assoc();
        $chart_tickets[] = intval($r['cnt']);
    }
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
        .badge-open { background:#38a8ff; color: #fff; font-weight:700; font-size: .97rem; padding: 5px 14px; border-radius: 10px; }
        .badge-closed { background:#35c452; color: #fff; font-weight:700; font-size: .97rem; padding: 5px 14px; border-radius: 10px; }
        .badge-pending { background:#e13a3a; color: #fff; font-weight:700; font-size: .97rem; padding: 5px 14px; border-radius: 10px; }
        @media (max-width: 991px) { .stat-card-lg {min-height:unset;} .dashboard-title {font-size:1.6rem;} .stat-value {font-size:2rem;} .stat-card {padding:1rem 0.6rem;} .chart-card {min-height: 260px;} }
        @media (max-width: 600px) { .dashboard-container {padding: 0 2px;} .dashboard-title {font-size:1.13rem;} .stat-card {padding:0.7rem 0.4rem;} }
    </style>
</head>
<body>
<?php include 'includes/supporter-navbar.php'; ?>
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
        <div class="col-6 col-md-3 flex-grow-1">
            <div class="stat-card stat-card-lg">
                <div class="card-title"><?=$tr[$lang]['users']?></div>
                <div class="stat-value"><?=number_format($users_count)?></div>
            </div>
        </div>
        <div class="col-6 col-md-3 flex-grow-1">
            <div class="stat-card stat-card-lg">
                <div class="card-title"><?=$tr[$lang]['tickets']?></div>
                <div class="stat-value"><?=number_format($tickets_count)?></div>
            </div>
        </div>
        <div class="col-6 col-md-3 flex-grow-1">
            <div class="stat-card stat-card-lg">
                <div class="card-title"><?=$tr[$lang]['orders']?></div>
                <div class="stat-value"><?=number_format($orders_count)?></div>
            </div>
        </div>
        <div class="col-6 col-md-3 flex-grow-1">
            <div class="stat-card stat-card-lg">
                <div class="card-title"><?=$tr[$lang]['paid_invoices']?></div>
                <div class="stat-value"><?=number_format($paid_invoices_count)?></div>
            </div>
        </div>
    </div>
    <!-- نمودار تیکت‌های 30 روز اخیر با ECharts -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="stat-card chart-card">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                    <span class="card-title"><?=$tr[$lang]['tickets_30days']?></span>
                </div>
                <div id="ticketsEChart30" style="width:100%;height:240px;min-height:160px;"></div>
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
                        <?php if (count($recent_tickets)): foreach($recent_tickets as $t): ?>
                            <tr>
                                <td><?=$t['id']?></td>
                                <td><?=htmlspecialchars($t['email'])?></td>
                                <td><?=htmlspecialchars($t['subject'])?></td>
                                <td>
                                    <span class="badge badge-<?=strtolower($t['status'])?>">
                                        <?=$tr[$lang][strtolower($t['status'])] ?? ucfirst($t['status'])?>
                                    </span>
                                </td>
                                <td><?=$lang=='fa'?to_jalali($t['created_at']):htmlspecialchars($t['created_at'])?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="5"><?=$tr[$lang]['no_data']?></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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
                        <?php if (count($recent_orders)): foreach($recent_orders as $order): ?>
                            <tr>
                                <td><?=$order['id']?></td>
                                <td><?=htmlspecialchars($order['email'])?></td>
                                <td><?=htmlspecialchars($order['product_name'])?></td>
                                <td><?=ucfirst($order['order_status'])?></td>
                                <td><?=$lang=='fa'?to_jalali($order['created_at']):htmlspecialchars($order['created_at'])?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="5"><?=$tr[$lang]['no_data']?></td></tr>
                        <?php endif; ?>
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
                        <tr><th><?=$tr[$lang]['id']?></th><th><?=$tr[$lang]['email']?></th><th><?=$tr[$lang]['created']?></th></tr>
                        </thead>
                        <tbody>
                        <?php if (count($new_users)): foreach($new_users as $user): ?>
                            <tr>
                                <td><?=$user['id']?></td>
                                <td><?=htmlspecialchars($user['email'])?></td>
                                <td><?=$lang=='fa'?to_jalali($user['created_at']):htmlspecialchars($user['created_at'])?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="3"><?=$tr[$lang]['no_data']?></td></tr>
                        <?php endif; ?>
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
    const chartLabels = <?=json_encode($chart_labels)?>;
    const chartTickets = <?=json_encode($chart_tickets)?>;
    document.addEventListener("DOMContentLoaded", function() {
        var chartDom = document.getElementById('ticketsEChart30');
        var myChart = echarts.init(chartDom, null, {renderer: 'canvas', useDirtyRect: false});
        var option = {
            title: {
                text: "<?=$lang=='fa'?'تیکت جدید':'New Ticket'?>",
                left: 'center',
                top: 8,
                textStyle: {
                    fontFamily: 'Vazirmatn, Tahoma, Arial',
                    fontSize: 15,
                    color: '#38a8ff'
                }
            },
            tooltip: {
                trigger: 'axis',
                backgroundColor: '#232d3b',
                borderColor: '#31415a',
                borderWidth: 1,
                textStyle: {
                    color: '#fff',
                    fontFamily: 'Vazirmatn, Tahoma, Arial'
                }
            },
            grid: {
                left: 40, right: 20, top: 60, bottom: 30, containLabel: true
            },
            xAxis: {
                type: 'category',
                data: chartLabels,
                axisLabel: {
                    color: '#38a8ff',
                    fontFamily: 'Vazirmatn',
                    rotate: 30
                },
                axisLine: { lineStyle: { color: '#38a8ff' } }
            },
            yAxis: {
                type: 'value',
                axisLabel: { color: '#e6e9f2', fontFamily: 'Vazirmatn' },
                splitLine: { lineStyle: { color: '#25304a' } }
            },
            series: [{
                name: "<?=$lang=='fa'?'تیکت جدید':'New Ticket'?>",
                data: chartTickets,
                type: 'line',
                smooth: true,
                areaStyle: {
                    color: 'rgba(56,168,255,0.15)'
                },
                lineStyle: {
                    color: '#38a8ff',
                    width: 2.5
                },
                itemStyle: {
                    color: '#38a8ff'
                },
                symbol: 'circle',
                symbolSize: 6
            }],
            animationDuration: 900
        };
        myChart.setOption(option);
        window.addEventListener('resize', myChart.resize);
    });
</script>
</body>
</html>