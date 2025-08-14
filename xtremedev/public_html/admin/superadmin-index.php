<?php
session_start();
if (!isset($_SESSION['admin_access_token'])) {
    header("Location: admin-login.php");
    exit;
}

function to_jalali($date_gregorian) { return $date_gregorian; } // غیرفعال برای انگلیسی

// API call
$api_url = 'https://api.xtremedev.co/endpoints/admin/dashboard_stats.php?lang=en';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $_SESSION['admin_access_token'],
    "Accept-Language: en"
]);
$resp = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpcode != 200) {
    die("<div style='color:#d00;padding:2rem;text-align:center;'>API Error: $resp</div>");
}

$data = json_decode($resp, true);
if (!is_array($data)) exit("<div style='color:#d00;padding:2rem;'>API response error.</div>");

$admin = $data['admin'];
$stats = $data['stats'];
$top_products = $data['top_products'];
$recent_orders = $data['recent_orders'];
$recent_invoices = $data['recent_invoices'];
$recent_tickets = $data['recent_tickets'];
$chart_30 = $data['chart_30days'];
$chart_7 = $data['chart_7days'];
$today = $data['today'];

$username = $admin['username'] ?? '';
$email = $admin['email'] ?? '';
$role = $admin['role'] ?? '';
if ($role !== 'superadmin') {
    header("Location: access_denied.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | XtremeDev</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php include __DIR__.'/../shared/inc/head-assets.php'; ?>
    <?php include __DIR__.'/includes/admin-styles.php'; ?>
    <style>
        :root {
            --main-bg: #11141a;
            --card-bg: #181e27;
            --card-dark: #171c22;
            --table-th-bg: #16191e;
            --table-td-bg1: #181e27;
            --table-td-bg2: #171c22;
            --border: #232633;
            --primary: #3bbcff;
            --primary-soft: #1a7fa8;
            --success: #23b37a;
            --danger: #d94d4d;
            --warning: #fbb040;
            --gray: #798da3;
            --text-main: #e3e8ef;
            --text-soft: #b3bac9;
        }
        body {
            background: var(--main-bg) !important;
            color: var(--text-main) !important;
            font-family: Vazirmatn, Tahoma, Arial, sans-serif;
            min-height: 100vh;
        }
        .dashboard-container { max-width: 1320px; margin: 30px auto 0 auto; }
        .dashboard-title { font-weight: 900; color: var(--primary); font-size: 2.05rem; letter-spacing: .7px; }
        .stat-card {
            border-radius: 20px;
            background: var(--card-bg);
            box-shadow: 0 2px 22px #000a;
            padding: 1.5rem 1.3rem;
            color: var(--text-main);
            margin-bottom: 0;
            text-align:center;
            border: 1.7px solid var(--border);
            transition: box-shadow 0.2s;
        }
        .stat-card-lg {
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            font-size: 1.13rem;
        }
        .main-cards-row { display:flex; gap:20px; margin-bottom:24px; }
        .main-cards-row .mini-stat-card { text-align:center; }
        .mini-stat-card {
            border-radius: 14px;
            background: var(--card-dark);
            border:1.2px solid var(--border);
            color:var(--text-main);
            padding: .95rem 0 .65rem 0;
            font-size:1.1rem;
            flex:1 1 0; min-width:0;
        }
        .mini-stat-card .stat-value { font-size:1.23rem;color:var(--text-main);font-weight:900;margin:0; }
        .card-title {
            font-size:1.22rem;
            color: var(--primary);
            font-weight: 900;
            margin-bottom: .5rem;
            letter-spacing: .25px;
        }
        .stat-value { font-size: 2.1rem; font-weight: 900; color: var(--primary); margin-top: 0.35rem; }
        .chart-card { min-height: 335px; }
        .table-responsive {
            background: transparent;
            border-radius:15px;
            box-shadow:0 2px 14px #111b2b45;
            margin-bottom:18px;
        }
        .table {
            width:100%;
            min-width: 900px;
            color: var(--text-main);
            background: transparent;
            border-collapse:collapse;
        }
        .table-striped>tbody>tr:nth-of-type(odd)>* {
            background-color: var(--table-td-bg1) !important;
        }
        .table-striped>tbody>tr:nth-of-type(even)>* {
            background-color: var(--table-td-bg2) !important;
        }
        .table thead th {
            font-weight:900;
            color:var(--primary);
            background:var(--table-th-bg) !important;
            border-bottom-width:2.5px;
            font-size:1.07rem;
            padding:18px 0 16px 0;
        }
        .table th, .table td {
            border-color: var(--border) !important;
            text-align:center;
            padding:1.01rem .7rem;
        }
        .table td {
            color: var(--text-soft) !important;
            font-weight: 500;
            font-size:1.01rem;
            vertical-align: middle;
        }
        .mini-table tr:hover {
            background: #232c3b !important;
        }
        .badge-paid {
            background: var(--success);
            color: #fff;
            font-weight:700;
            font-size: .99rem;
            padding: 4px 16px;
            border-radius: 10px;
        }
        .badge-unpaid {
            background: var(--danger);
            color: #fff;
            font-weight:700;
            font-size: .99rem;
            padding: 4px 16px;
            border-radius: 10px;
        }
        .badge-canceled {
            background: var(--gray);
            color: #fff;
            font-weight:700;
            font-size: .99rem;
            padding: 4px 16px;
            border-radius: 10px;
        }
        .badge-pending {
            background: var(--warning);
            color: #181e27;
            font-weight:700;
            font-size: .99rem;
            padding: 4px 16px;
            border-radius: 10px;
        }
        .badge-open {
            background: var(--primary-soft);
            color:#fff;
            font-weight:700;
            font-size: .99rem;
            padding: 4px 16px;
            border-radius: 10px;
        }
        .main-switch-btns button, .simple-chart-btns button {
            background: none;
            border: 1px solid var(--primary);
            color: var(--primary);
            font-weight: 700;
            padding: 4px 14px;
            border-radius: 8px;
            cursor:pointer;
            margin-left:7px;
            margin-bottom: 3px;
            font-size:1.02rem;
            transition: background .13s, color .13s;
        }
        .main-switch-btns button.active, .main-switch-btns button:focus,
        .simple-chart-btns button.active, .simple-chart-btns button:focus {
            background: var(--primary);
            color: #fff;
        }
        @media (max-width: 1100px) { .dashboard-container {max-width:99vw;} .table {min-width:680px;} }
        @media (max-width: 900px) { .stat-card-lg {min-height:unset;} .dashboard-title {font-size:1.4rem;} .stat-value {font-size:2rem;} .stat-card {padding:1rem 0.6rem;} .chart-card {min-height: 220px;} }
        @media (max-width: 800px) { .main-cards-row {flex-direction:column;gap:9px;} }
        @media (max-width: 600px) { .dashboard-container {padding: 0 2px;} .dashboard-title {font-size:1.1rem;} .stat-card {padding:0.7rem 0.4rem;} }
    </style>
</head>
<body>
<?php include 'includes/superadmin-navbar.php'; ?>
<div class="dashboard-container container-fluid px-1 px-md-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-3">
        <div>
            <span class="dashboard-title">Dashboard</span>
            <span class="role-badge ms-2">Role</span>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span style="font-size:.98rem;color:var(--text-soft);">
                <b><?=htmlspecialchars($username)?></b> (<span style="color:var(--primary);"><?=htmlspecialchars($email)?></span>)
            </span>
            <a href="logout.php" class="btn btn-sm btn-danger ms-2">Logout</a>
        </div>
    </div>
    <!-- Main Stats -->
    <div class="row g-4 mb-4 align-items-stretch">
        <div class="col-6 col-md-2 flex-grow-1">
            <div class="stat-card stat-card-lg"><div class="card-title">Users</div><div class="stat-value"><?=number_format($stats['users_count'])?></div></div>
        </div>
        <div class="col-6 col-md-2 flex-grow-1">
            <div class="stat-card stat-card-lg"><div class="card-title">Orders</div><div class="stat-value"><?=number_format($stats['orders_count'])?></div></div>
        </div>
        <div class="col-6 col-md-2 flex-grow-1">
            <div class="stat-card stat-card-lg"><div class="card-title">Products</div><div class="stat-value"><?=number_format($stats['products_count'])?></div></div>
        </div>
        <div class="col-6 col-md-2 flex-grow-1">
            <div class="stat-card stat-card-lg"><div class="card-title">Invoices</div><div class="stat-value"><?=number_format($stats['invoices_count'])?></div></div>
        </div>
        <div class="col-6 col-md-2 flex-grow-1">
            <div class="stat-card stat-card-lg"><div class="card-title">Tickets</div><div class="stat-value"><?=number_format($stats['tickets_count'])?></div></div>
        </div>
    </div>
    <!-- Main Amount/Count Cards -->
    <div class="main-cards-row mb-3">
        <div class="main-switch-btns" style="display:flex;align-items:center;gap:7px;margin-bottom:0;">
            <button id="mainBtnAmount" class="active">Total Amount</button>
            <button id="mainBtnCount">Count</button>
        </div>
        <div class="mini-stat-card">
            <div style="font-size:.94rem;color:var(--text-soft)">Today</div>
            <div class="stat-value" id="mainCardToday"></div>
        </div>
        <div class="mini-stat-card">
            <div style="font-size:.94rem;color:var(--text-soft)">7 days</div>
            <div class="stat-value" id="mainCard7"></div>
        </div>
        <div class="mini-stat-card">
            <div style="font-size:.94rem;color:var(--text-soft)">30 days</div>
            <div class="stat-value" id="mainCard30"></div>
        </div>
    </div>
    <!-- Charts -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-md-6">
            <div class="stat-card chart-card">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                    <span class="card-title">Paid Invoices (30 days) (<span id="chart30TypeLabel">Total Amount</span>)</span>
                    <div class="simple-chart-btns">
                        <button id="btn30Amount" class="active">Total Amount</button>
                        <button id="btn30Count">Count</button>
                    </div>
                </div>
                <div id="chart30Echart" style="width:100%;height:240px;min-height:160px;"></div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="stat-card chart-card">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                    <span class="card-title">Paid Invoices (7 days) (<span id="chart7TypeLabel">Total Amount</span>)</span>
                    <div class="simple-chart-btns">
                        <button id="btn7Amount" class="active">Total Amount</button>
                        <button id="btn7Count">Count</button>
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
                <div class="card-title mb-2">Top Products</div>
                <div class="table-responsive">
                <table class="table table-sm table-striped mini-table mb-0">
                    <thead>
                    <tr><th>Product</th><th>Sales</th></tr>
                    </thead>
                    <tbody>
                    <?php if(count($top_products)): foreach($top_products as $p): ?>
                        <tr>
                            <td><?=htmlspecialchars($p['name'])?></td>
                            <td><?=number_format($p['total_sales'])?></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="2">No data.</td></tr>
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
                <div class="card-title mb-2">Recent Orders</div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mini-table mb-0">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Product</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if(count($recent_orders)): foreach($recent_orders as $order): ?>
                            <tr>
                                <td><?=htmlspecialchars($order['id'])?></td>
                                <td><?=htmlspecialchars($order['user_id'])?></td>
                                <td><?=htmlspecialchars($order['product_name'])?></td>
                                <td><?=ucfirst($order['status'])?></td>
                                <td><?=htmlspecialchars($order['created_at'])?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="5">No data.</td></tr>
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
                <div class="card-title mb-2">Recent Invoices</div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mini-table mb-0">
                        <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>User</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Paid At</th>
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
                                      <?=ucfirst($inv['status'])?>
                                    </span>
                                </td>
                                <td><?=htmlspecialchars($inv['paid_at'] ?: '-')?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="5">No data.</td></tr>
                        <?php endif; ?>
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
                <div class="card-title mb-2">Recent Tickets</div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mini-table mb-0">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if(count($recent_tickets)): foreach($recent_tickets as $t): ?>
                            <tr>
                                <td><?=htmlspecialchars($t['id'])?></td>
                                <td><?=htmlspecialchars($t['user_id'])?></td>
                                <td><?=htmlspecialchars($t['subject'])?></td>
                                <td><?=ucfirst($t['status'])?></td>
                                <td><?=htmlspecialchars($t['created_at'])?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="5">No data.</td></tr>
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
    const chartSumAmount30 = <?=json_encode($chart_30['sum_amount'])?>;
    const chartSumCount30 = <?=json_encode($chart_30['sum_count'])?>;
    const chartSumAmount7 = <?=json_encode($chart_7['sum_amount'])?>;
    const chartSumCount7 = <?=json_encode($chart_7['sum_count'])?>;
    const todayAmount = <?=json_encode($today['sum_amount'])?>;
    const todayCount = <?=json_encode($today['sum_count'])?>;
    const labelAmount = "Total Amount";
    const labelCount  = "Count";
    const colorAmount30 = "#3bbcff";
    const colorCount30 = "#22ce77";
    const colorAmount7 = "#fbb040";
    const colorCount7 = "#22ce77";

    // Main cards
    let mainType = 'amount';
    function updateMainCards() {
        document.getElementById('mainCardToday').innerText = mainType === 'amount' ? number_format(todayAmount) : number_format(todayCount);
        document.getElementById('mainCard7').innerText     = mainType === 'amount' ? number_format(chartSumAmount7) : number_format(chartSumCount7);
        document.getElementById('mainCard30').innerText    = mainType === 'amount' ? number_format(chartSumAmount30) : number_format(chartSumCount30);
        document.getElementById('mainBtnAmount').classList.toggle('active', mainType==='amount');
        document.getElementById('mainBtnCount').classList.toggle('active', mainType==='count');
    }
    function number_format(x) {
        return x ? x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") : "0";
    }

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
                backgroundColor: '#181e27',
                borderColor: '#232633',
                borderWidth: 1,
                textStyle: { color: '#fff', fontFamily: 'Vazirmatn, Tahoma, Arial' }
            },
            grid: { left: 40, right: 20, top: 24, bottom: 30, containLabel: true },
            xAxis: {
                type: 'category',
                data: chartLabels30,
                axisLabel: { color: '#3bbcff', fontFamily: 'Vazirmatn', rotate: 30 },
                axisLine: { lineStyle: { color: '#3bbcff' } }
            },
            yAxis: {
                type: 'value',
                axisLabel: { color: '#b3bac9', fontFamily: 'Vazirmatn' },
                splitLine: { lineStyle: { color: '#232633' } }
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
                backgroundColor: '#181e27',
                borderColor: '#232633',
                borderWidth: 1,
                textStyle: { color: '#fff', fontFamily: 'Vazirmatn, Tahoma, Arial' }
            },
            grid: { left: 40, right: 20, top: 24, bottom: 30, containLabel: true },
            xAxis: {
                type: 'category',
                data: chartLabels7,
                axisLabel: { color: '#fbb040', fontFamily: 'Vazirmatn', rotate: 30 },
                axisLine: { lineStyle: { color: '#fbb040' } }
            },
            yAxis: {
                type: 'value',
                axisLabel: { color: '#b3bac9', fontFamily: 'Vazirmatn' },
                splitLine: { lineStyle: { color: '#232633' } }
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
        document.getElementById('mainBtnAmount').onclick = function(){ mainType='amount'; updateMainCards(); }
        document.getElementById('mainBtnCount').onclick  = function(){ mainType='count'; updateMainCards(); }
        updateMainCards();

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