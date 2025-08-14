<?php
session_start();
if (!isset($_SESSION['admin_access_token']) || !isset($_SESSION['admin_user_id'])) {
    header("Location: login.php");
    exit;
}

// توابع تاریخ
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
function short_text($str, $len=32) {
    $str = trim($str);
    if (mb_strlen($str, 'UTF-8') > $len) {
        return mb_substr($str, 0, $len-3, 'UTF-8') . '...';
    }
    return $str;
}

// زبان و ترجمه
$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'en');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : 'en';
$is_rtl = ($lang === 'fa');
$tr = [
    'fa' => [
        'dashboard' => 'داشبورد مدیر',
        'role' => 'مدیر',
        'logout' => 'خروج',
        'users' => 'کاربران',
        'orders' => 'سفارش‌ها',
        'products' => 'محصولات',
        'invoices' => 'فاکتورها',
        'paid_invoices_30days' => 'فاکتورهای پرداخت‌شده ۳۰ روز اخیر',
        'paid_invoices_7days' => 'فاکتورهای پرداخت‌شده ۷ روز اخیر',
        'top_products' => 'محصولات پرفروش',
        'product' => 'محصول',
        'sales' => 'تعداد فروش',
        'no_data' => 'داده‌ای وجود ندارد.',
        'recent_orders' => 'آخرین سفارش‌ها',
        'id' => 'شناسه',
        'user' => 'کاربر',
        'status' => 'وضعیت',
        'created' => 'تاریخ ثبت',
        'recent_invoices' => 'آخرین فاکتورها',
        'invoice_no' => 'شماره سفارش',
        'amount' => 'مبلغ',
        'paid_at' => 'تاریخ پرداخت',
        'amount_label' => 'مجموع مبلغ',
        'count_label' => 'تعداد',
        'paid' => 'پرداخت‌شده',
        'unpaid' => 'پرداخت‌نشده',
        'pending' => 'در انتظار',
        'canceled' => 'لغو شده'
    ],
    'en' => [
        'dashboard' => 'Manager Dashboard',
        'role' => 'Manager',
        'logout' => 'Logout',
        'users' => 'Users',
        'orders' => 'Orders',
        'products' => 'Products',
        'invoices' => 'Invoices',
        'paid_invoices_30days' => 'Paid Invoices (30 days)',
        'paid_invoices_7days' => 'Paid Invoices (7 days)',
        'top_products' => 'Top Products',
        'product' => 'Product',
        'sales' => 'Sales',
        'no_data' => 'No data.',
        'recent_orders' => 'Recent Orders',
        'id' => 'ID',
        'user' => 'User',
        'status' => 'Status',
        'created' => 'Created',
        'recent_invoices' => 'Recent Invoices',
        'invoice_no' => 'Order ID',
        'amount' => 'Amount',
        'paid_at' => 'Paid At',
        'amount_label' => 'Total Amount',
        'count_label' => 'Count',
        'paid' => 'Paid',
        'unpaid' => 'Unpaid',
        'pending' => 'Pending',
        'canceled' => 'Canceled'
    ]
];

// دریافت داده از API
$api_url = 'https://api.xtremedev.co/endpoints/admin/dashboard_stats.php?lang=' . urlencode($lang);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $_SESSION['admin_access_token'],
    "Accept-Language: $lang"
]);
$resp = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpcode != 200) {
    die("<div style='color:#d00;padding:2rem;text-align:center;'>API Error: $resp</div>");
}

$data = json_decode($resp, true);
if (!is_array($data)) exit("<div style='color:#d00;padding:2rem;'>API response error.</div>");

// استخراج داده‌ها
$admin = $data['admin'];
$stats = $data['stats'];
$top_products = $data['top_products'];
$recent_orders = $data['recent_orders'];
$recent_invoices = $data['recent_invoices'];
$chart_30 = $data['chart_30days'];
$chart_7 = $data['chart_7days'];

// اطلاعات ادمین برای نمایش
$username = $admin['username'] ?? '';
$email = $admin['email'] ?? '';
$role = $admin['role'] ?? '';
if ($role !== 'manager' && $role !== 'superadmin') {
    header("Location: access_denied.php");
    exit;
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
        .simple-chart-btns button { background: none; border: 1px solid #38a8ff; color: #38a8ff; font-weight: 700; padding: 3px 14px; border-radius: 8px; cursor:pointer; margin-left:7px; margin-bottom: 3px; }
        .simple-chart-btns button.active, .simple-chart-btns button:focus { background: #38a8ff; color: #fff; }
    </style>
</head>
<body>
<?php include 'includes/manager-navbar.php'; ?>
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
            <div class="stat-card stat-card-lg"><div class="card-title"><?=$tr[$lang]['users']?></div><div class="stat-value"><?=number_format($stats['users_count'])?></div></div>
        </div>
        <div class="col-6 col-md-3 flex-grow-1">
            <div class="stat-card stat-card-lg"><div class="card-title"><?=$tr[$lang]['orders']?></div><div class="stat-value"><?=number_format($stats['orders_count'])?></div></div>
        </div>
        <div class="col-6 col-md-3 flex-grow-1">
            <div class="stat-card stat-card-lg"><div class="card-title"><?=$tr[$lang]['products']?></div><div class="stat-value"><?=number_format($stats['products_count'])?></div></div>
        </div>
        <div class="col-6 col-md-3 flex-grow-1">
            <div class="stat-card stat-card-lg"><div class="card-title"><?=$tr[$lang]['invoices']?></div><div class="stat-value"><?=number_format($stats['invoices_count'])?></div></div>
        </div>
    </div>
    <!-- نمودارها -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-md-6">
            <div class="stat-card chart-card">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                    <span class="card-title"><?=$tr[$lang]['paid_invoices_30days']?> (<span id="chart30TypeLabel"><?=$tr[$lang]['amount_label']?></span>)</span>
                    <div class="simple-chart-btns">
                        <button id="btn30Amount" class="active"><?=$tr[$lang]['amount_label']?></button>
                        <button id="btn30Count"><?=$tr[$lang]['count_label']?></button>
                    </div>
                </div>
                <div id="chart30Echart" style="width:100%;height:240px;min-height:160px;"></div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="stat-card chart-card">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                    <span class="card-title"><?=$tr[$lang]['paid_invoices_7days']?> (<span id="chart7TypeLabel"><?=$tr[$lang]['amount_label']?></span>)</span>
                    <div class="simple-chart-btns">
                        <button id="btn7Amount" class="active"><?=$tr[$lang]['amount_label']?></button>
                        <button id="btn7Count"><?=$tr[$lang]['count_label']?></button>
                    </div>
                </div>
                <div id="chart7Echart" style="width:100%;height:240px;min-height:160px;"></div>
            </div>
        </div>
    </div>
    <!-- Top Products -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="stat-card">
                <div class="card-title mb-2"><?=$tr[$lang]['top_products']?></div>
                <table class="table table-sm mini-table mb-0">
                    <thead>
                    <tr><th><?=$tr[$lang]['product']?></th><th><?=$tr[$lang]['sales']?></th></tr>
                    </thead>
                    <tbody>
                    <?php if(count($top_products)): foreach($top_products as $p): ?>
                        <tr>
                            <td><?=htmlspecialchars($p['name'])?></td>
                            <td><?=number_format($p['total_sales'])?></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="2"><?=$tr[$lang]['no_data']?></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
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
                        <?php if(count($recent_orders)): foreach($recent_orders as $order): ?>
                            <tr>
                                <td><?=htmlspecialchars($order['id'])?></td>
                                <td><?=htmlspecialchars($order['user_id'])?></td>
                                <td><?=htmlspecialchars($order['product_name'])?></td>
                                <td><?=ucfirst($order['status'])?></td>
                                <td><?= $lang=='fa' ? to_jalali($order['created_at']) : htmlspecialchars($order['created_at']) ?></td>
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
                        <?php if(count($recent_invoices)): foreach($recent_invoices as $inv): ?>
                            <tr>
                                <td><?=htmlspecialchars($inv['order_id'])?></td>
                                <td><?=htmlspecialchars($inv['user_id'])?></td>
                                <td><?=number_format($inv['amount'])?></td>
                                <td>
                                    <span class="badge badge-<?=htmlspecialchars($inv['status'])?>">
                                      <?=$tr[$lang][$inv['status']] ?? ucfirst($inv['status'])?>
                                    </span>
                                </td>
                                <td><?= $lang=='fa' ? to_jalali($inv['paid_at']) : htmlspecialchars($inv['paid_at'] ?: '-') ?></td>
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
</div>
<?php include 'includes/dashboard-footer.php'; ?>
<?php include __DIR__.'/../shared/inc/foot-assets.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/echarts@5.4.2/dist/echarts.min.js"></script>
<script>
    const chartLabels30 = <?=json_encode($chart_30['labels'])?>;
    const chartPaidAmount30 = <?=json_encode($chart_30['paid_amount'])?>;
    const chartPaidCount30 = <?=json_encode($chart_30['paid_count'])?>;
    const chartLabels7 = <?=json_encode($chart_7['labels'])?>;
    const chartPaidAmount7 = <?=json_encode($chart_7['paid_amount'])?>;
    const chartPaidCount7 = <?=json_encode($chart_7['paid_count'])?>;
    const labelAmount = <?=json_encode($tr[$lang]['amount_label'])?>;
    const labelCount  = <?=json_encode($tr[$lang]['count_label'])?>;
    const colorAmount30 = '#38a8ff';
    const colorCount30  = '#2bc551';
    const colorAmount7  = '#fcb600';
    const colorCount7   = '#2bc551';

    let chart30Echart, chart7Echart, chart30Type = 'amount', chart7Type = 'amount';

    function renderChart30(){
        let data = chart30Type==='amount' ? chartPaidAmount30 : chartPaidCount30;
        let color = chart30Type==='amount' ? colorAmount30 : colorCount30;
        let label = chart30Type==='amount' ? labelAmount : labelCount;
        document.getElementById('chart30TypeLabel').innerText = label;
        document.getElementById('btn30Amount').classList.toggle('active', chart30Type==='amount');
        document.getElementById('btn30Count').classList.toggle('active', chart30Type==='count');
        let option = {
            title: { show: false },
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
                name: label,
                data: data,
                type: 'line',
                smooth: true,
                areaStyle: { color: color+'33' },
                lineStyle: { color: color, width: 2.5 },
                itemStyle: { color: color },
                symbol: 'circle',
                symbolSize: 6
            }],
            animationDuration: 700
        };
        chart30Echart.setOption(option);
    }
    function renderChart7(){
        let data = chart7Type==='amount' ? chartPaidAmount7 : chartPaidCount7;
        let color = chart7Type==='amount' ? colorAmount7 : colorCount7;
        let label = chart7Type==='amount' ? labelAmount : labelCount;
        document.getElementById('chart7TypeLabel').innerText = label;
        document.getElementById('btn7Amount').classList.toggle('active', chart7Type==='amount');
        document.getElementById('btn7Count').classList.toggle('active', chart7Type==='count');
        let option = {
            title: { show: false },
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
                data: chartLabels7,
                axisLabel: { color: '#fcb600', fontFamily: 'Vazirmatn', rotate: 30 },
                axisLine: { lineStyle: { color: '#fcb600' } }
            },
            yAxis: {
                type: 'value',
                axisLabel: { color: '#e6e9f2', fontFamily: 'Vazirmatn' },
                splitLine: { lineStyle: { color: '#25304a' } }
            },
            series: [{
                name: label,
                data: data,
                type: 'line',
                smooth: true,
                areaStyle: { color: color+'33' },
                lineStyle: { color: color, width: 2.5 },
                itemStyle: { color: color },
                symbol: 'circle',
                symbolSize: 6
            }],
            animationDuration: 700
        };
        chart7Echart.setOption(option);
    }
    document.addEventListener("DOMContentLoaded", function() {
        chart30Echart = echarts.init(document.getElementById('chart30Echart'), null, {renderer: 'canvas', useDirtyRect: false});
        chart7Echart  = echarts.init(document.getElementById('chart7Echart'),  null, {renderer: 'canvas', useDirtyRect: false});
        renderChart30();
        renderChart7();
        window.addEventListener('resize', function() {
            chart30Echart.resize();
            chart7Echart.resize();
        });
        document.getElementById('btn30Amount').onclick = function(){ chart30Type='amount';renderChart30(); }
        document.getElementById('btn30Count').onclick  = function(){ chart30Type='count';renderChart30(); }
        document.getElementById('btn7Amount').onclick  = function(){ chart7Type='amount';renderChart7(); }
        document.getElementById('btn7Count').onclick   = function(){ chart7Type='count';renderChart7(); }
    });
</script>
</body>
</html>