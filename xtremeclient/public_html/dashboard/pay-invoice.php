<?php
session_start();
require_once __DIR__ . '/../shared/inc/config.php';

$gateways = [
    [
        'id'    => 'mellat',
        'logo'  => 'https://dl.xtremedev.co/shared-resource/psp-logo/mellat-beh-pardakht.png',
    ],
    [
        'id'    => 'sep',
        'logo'  => 'https://dl.xtremedev.co/shared-resource/psp-logo/sep.png',
    ],
    [
        'id'    => 'zarinpal',
        'logo'  => 'https://dl.xtremedev.co/shared-resource/psp-logo/zarinpal.png',
    ],
    [
        'id'    => 'sadad',
        'logo'  => 'https://dl.xtremedev.co/shared-resource/psp-logo/sadad.png',
    ],
];

$lang = $_COOKIE['site_lang'] ?? (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$is_rtl = $lang === 'fa';

$translations = [];
$lang_file = __DIR__ . '/../shared/assets/languages/' . $lang . '.json';
if (file_exists($lang_file)) {
    $json = file_get_contents($lang_file);
    $translations = json_decode($json, true);
}
function t($k) {
    global $translations;
    return isset($translations[$k]) ? $translations[$k] : '';
}

if (!isset($_SESSION['user_profile'])) {
    $client_id = 'xtremeclient-web';
    $redirect_uri = 'https://xtremeclient.com/oauth-callback.php';
    $state = bin2hex(random_bytes(8));
    $login_url = "https://auth.xtremedev.co/authorize.php?client_id=$client_id&redirect_uri=" . urlencode($redirect_uri) . "&response_type=code&scope=basic&state=$state";
    header("Location: $login_url");
    exit;
}

$access_token = $_SESSION['access_token'] ?? '';
$profile = $_SESSION['user_profile'];
$darkThemeActive = (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark');

$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$invoice_id) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>" dir="<?= $is_rtl ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars(t('pay_invoice')) ?> | XtremeDev</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php include __DIR__ . '/../shared/inc/head-assets.php'; ?>
    <?php include __DIR__ . '/includes/dashboard-styles.php'; ?>
    <style>
    body {
        font-family: Vazirmatn, Tahoma, Arial, sans-serif;
        background: var(--surface, #f4f7fa);
        color: var(--text, #222);
    }
    body.dark-theme {
        background: var(--surface, #181f2a) !important;
        color: var(--text, #e6e9f2) !important;
    }
    .main-content { flex: 1 0 auto; min-height: 90vh; }
    .pay-layout {
        display: flex;
        flex-direction: <?= $is_rtl ? 'row-reverse' : 'row' ?>;
        gap: 32px;
        max-width: 1240px;
        margin: 56px auto 32px auto;
        align-items: flex-start;
        width: 98vw;
    }
    .wide-col {
        flex: 2 1 0;
        display: flex;
        flex-direction: column;
        gap: 22px;
        min-width: 330px;
        max-width: 850px;
    }
    .rules-box, .gateway-card {
        background: var(--surface-alt, #fff);
        border-radius: 18px;
        box-shadow: 0 2px 18px var(--shadow-card, #2499fa10);
        border: 1.5px solid var(--border, #2499fa12);
        padding: 2.1rem 2rem 1.7rem 2rem;
        transition: box-shadow 0.2s, background 0.2s, color 0.2s;
    }
    .rules-box {
        font-size: 1.13rem;
        color: #5d6e8c;
        margin-bottom: 0;
    }
    .rules-box ul {
        margin: 0 0 0.7rem 0;
        padding: 0 2.1em;
    }
    .rules-box li {
        margin-bottom: 0.55em;
        font-size: 1.00em;
    }
    .gateway-card {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        justify-content: flex-start;
        margin-bottom: 0;
    }
    .gateway-select-label {
        font-size: 1.07rem;
        font-weight: bold;
        margin-bottom: 0.7rem;
        color: #2499fa;
        width: 100%;
        text-align: <?= $is_rtl ? 'right' : 'left' ?>;
        margin-top: 0.6rem;
    }
    .gateway-list {
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 1.2rem;
        margin-bottom: 1.35rem;
    }
    .gateway-option {
        display: flex;
        align-items: center;
        gap: 1.2rem;
        padding: 1.1rem 1.1rem;
        border-radius: 13px;
        background: #f5f7fa;
        cursor: pointer;
        border: 2px solid transparent;
        transition: border 0.16s, background 0.16s, box-shadow 0.12s;
        font-size: 1.11rem;
        font-weight: 700;
        color: #222;
        user-select: none;
        min-height: 62px;
        box-shadow: 0 1px 6px #d9e6ef2a;
    }
    .gateway-option.selected, .gateway-option:focus-within {
        border: 2px solid #2499fa;
        background: #e6f1ff;
        box-shadow: 0 2px 12px #2499fa22;
    }
    body.dark-theme .gateway-option {
        background: #1a2230 !important;
        color: #e6e9f2;
        box-shadow: 0 1px 6px #151d28a5;
        border: 2px solid transparent;
    }
    body.dark-theme .gateway-option.selected, body.dark-theme .gateway-option:focus-within {
        background: #243447 !important;
        border: 2px solid #61bdfc;
        box-shadow: 0 2px 12px #2499fa32;
    }
    .gateway-option input[type=radio] {
        accent-color: #2499fa;
        width: 1.15em;
        height: 1.15em;
        margin-inline-end: 0;
    }
    .gateway-logo {
        display: inline-flex;
        align-items: center;
        width: 42px; height: 42px;
        border-radius: 7px;
        background: #fff;
        overflow: hidden;
        justify-content: center;
        box-shadow: 0 2px 8px #0001;
        border: 1px solid #e4e7ef;
    }
    .gateway-logo img, .gateway-logo svg {
        width: 37px; height: 37px; display: block;
        object-fit: contain;
    }
    .gateway-info {
        display: flex; flex-direction: column; gap: 0.11em;
    }
    .gateway-label {
        font-size: 1.09em; font-weight: bold;
    }
    .gateway-desc {
        font-size: 0.98em; color: #688;
        font-weight: 400; margin-top: 2px;
    }
    .pay-btn {
        background: #16a34a;
        color: #fff;
        border: none;
        border-radius: 10px;
        font-weight: bold;
        font-size: 1.14rem;
        padding: 0.85em 1.7em;
        cursor: pointer;
        box-shadow: 0 1px 6px #16a34a18;
        transition: background 0.15s, box-shadow 0.17s;
        margin-bottom: 0.7rem;
        width: 100%;
        max-width: 270px;
        display: block;
        margin-inline: auto;
        margin-top: 1.1rem;
    }
    .pay-btn:disabled {
        opacity: 0.55;
        cursor: not-allowed;
    }
    .pay-btn:hover, .pay-btn:focus {
        background: #0e8938;
    }
    .back-link {
        margin-top: 1.7rem;
        display: block;
        min-width: 120px;
        font-size: 1.01rem;
        font-weight: 700;
        border-radius: 10px;
        background: #e4e7ef;
        color: #2499fa;
        border: none;
        transition: background 0.18s, color 0.18s;
        padding: 0.7rem 1.5rem;
        text-align: center;
        text-decoration: none;
    }
    .back-link:hover, .back-link:focus {
        background: #d1e9ff;
        color: #145d99;
    }
    body.dark-theme .back-link {
        background: #1a2230 !important;
        color: #aad8ff !important;
        border: 1px solid #28324c;
    }
    body.dark-theme .back-link:hover, body.dark-theme .back-link:focus {
        background: #243447 !important;
        color: #83bfff !important;
    }
    /* ستون باریک */
    .side-col {
        flex: 0 0 310px;
        max-width: 350px;
        min-width: 230px;
        display: flex;
        flex-direction: column;
        gap: 0;
    }
    .invoice-card {
        background: var(--surface-alt, #fff);
        border-radius: 18px;
        box-shadow: 0 2px 28px var(--shadow-card, #2499fa14);
        border: 1.5px solid var(--border, #2499fa18);
        padding: 2.3rem 2.1rem 2.1rem 2.1rem;
        margin-bottom: 0;
        transition: box-shadow 0.2s, background 0.2s, color 0.2s;
    }
    .pay-title {
        color: var(--primary, #2499fa);
        font-size: 1.26rem;
        font-weight: 900;
        margin-bottom: 2.2rem;
        text-align: center;
    }
    .invoice-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 9px;
        margin-bottom: 1.6rem;
    }
    .invoice-table td {
        padding: 0.42em 0.5em;
        font-size: 1.07em;
        background: #f7fafc;
        border-radius: 7px;
        vertical-align: middle;
        color: #444;
        min-width: 95px;
        max-width: 300px;
        word-break: break-word;
    }
    .invoice-table td.label {
        font-weight: bold;
        color: #2499fa;
        width: 120px;
        background: #e8f2fc;
    }
    .invoice-table tr.product-row td {
        background: #f5faf6 !important;
        color: #227a34;
    }
    .pay-error-message, .pay-status-message {
        font-weight: 700;
        margin-bottom: 1.2rem;
        text-align: center;
        font-size: 1.08rem;
    }
    .pay-error-message { color: #e63946; }
    .pay-status-message { color: #16a34a; }
    body.dark-theme .rules-box,
    body.dark-theme .gateway-card,
    body.dark-theme .invoice-card {
        background: #232d3a;
        color: #e6e9f2;
        border: 1.5px solid #2a3546;
        box-shadow: 0 2px 24px #10151b44;
    }
    body.dark-theme .invoice-table td {
        background: #1a2230;
        color: #e6e9f2;
    }
    body.dark-theme .invoice-table td.label {
        background: #1e2a3d;
        color: #61bdfc;
    }
    body.dark-theme .invoice-table tr.product-row td {
        background: #1a3320 !important;
        color: #4efb95;
    }
    @media (max-width: 1200px) {
        .pay-layout { max-width: 99vw; }
        .wide-col { max-width: 99vw; }
        .side-col { max-width: 99vw; }
    }
    @media (max-width: 900px) {
        .pay-layout { flex-direction: column; gap:24px; }
        .wide-col, .side-col { max-width:100%; min-width:0; }
    }
    @media (max-width: 600px) {
        .rules-box, .gateway-card, .invoice-card { padding: 1.1rem 0.6rem 1.1rem 0.6rem; }
        .invoice-table td, .invoice-table td.label { font-size: 0.99em; padding: 0.35em 0.32em; }
        .rules-box { padding: .6rem .4rem .4rem .4rem; }
    }
    </style>
</head>
<body<?= $darkThemeActive ? ' class="dark-theme"' : '' ?>>
<?php include 'includes/dashboard-navbar.php'; ?>

<div class="main-content">
    <div class="container">
        <div class="pay-layout">
            <!-- ستون پهن (قوانین و انتخاب درگاه) -->
            <div class="wide-col">
                <div class="rules-box">
                    <b><?= htmlspecialchars(t('buy_rules_title')) ?></b>
                    <ul>
                        <li><?= htmlspecialchars(t('buy_rule_1')) ?></li>
                        <li><?= htmlspecialchars(t('buy_rule_2')) ?></li>
                        <li><?= htmlspecialchars(t('buy_rule_3')) ?></li>
                    </ul>
                </div>
                <div class="gateway-card">
                    <form id="pay-form" style="width:100%;display:none" autocomplete="off">
                        <label class="gateway-select-label"><?= htmlspecialchars(t('select_gateway')) ?></label>
                        <div class="gateway-list" id="gateway-list"></div>
                        <button type="submit" class="pay-btn" id="pay-btn" disabled><?= htmlspecialchars(t('pay_now')) ?></button>
                    </form>
                    <a href="dashboard.php" class="back-link"><?= htmlspecialchars(t('back_to_dashboard')) ?></a>
                </div>
            </div>
            <!-- ستون باریک (اطلاعات فاکتور) -->
            <div class="side-col">
                <div class="invoice-card">
                    <div class="pay-title"><?= htmlspecialchars(t('pay_invoice')) ?></div>
                    <div id="pay-status"></div>
                    <div id="invoice-info">
                        <table class="invoice-table" id="invoice-table">
                            <tr><td colspan="2" style="text-align:center"><?= htmlspecialchars(t('loading')) ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/dashboard-footer.php'; ?>
<?php include __DIR__ . '/../shared/inc/foot-assets.php'; ?>
<?php if(file_exists('includes/theme-script.php')) include 'includes/theme-script.php'; ?>
<script>
const t = <?= json_encode($translations, JSON_UNESCAPED_UNICODE) ?>;
const invoice_id = <?= json_encode($invoice_id) ?>;
const access_token = <?= json_encode($access_token) ?>;
const is_rtl = <?= json_encode($is_rtl) ?>;
const gateways = <?= json_encode($gateways, JSON_UNESCAPED_UNICODE) ?>;

let selectedGateway = "";

function tr(key) {
    return (t[key] !== undefined) ? t[key] : '';
}
function showStatus(type, msg) {
    const $status = document.getElementById('pay-status');
    if(type === 'success')
        $status.innerHTML = `<div class="pay-status-message">${msg}</div>`;
    else if(type === 'error')
        $status.innerHTML = `<div class="pay-error-message">${msg}</div>`;
    else
        $status.innerHTML = '';
}

function renderInvoiceTable(inv) {
    let html = '';
    html += `<tr><td class="label">${tr('invoice_id')}</td><td>${inv.id}</td></tr>`;
    html += `<tr><td class="label">${tr('amount')}</td><td>${Number(inv.amount).toLocaleString()} ریال</td></tr>`;
    html += `<tr><td class="label">${tr('status')}</td><td>${inv.status === 'unpaid' ? tr('unpaid') : (inv.status === 'paid' ? tr('paid') : inv.status)}</td></tr>`;
    html += `<tr><td class="label">${tr('created_at')}</td><td>${inv.created_at ? inv.created_at.replace('T', ' ').substring(0, 19) : '-'}</td></tr>`;
    if(inv.payment_gateway)
        html += `<tr><td class="label">${tr('gateway')}</td><td>${inv.payment_gateway}</td></tr>`;
    if(inv.products && Array.isArray(inv.products) && inv.products.length) {
        html += `<tr class="product-row"><td class="label">${tr('products')}</td><td><ul style="margin:0;padding-${is_rtl ? 'right' : 'left'}:1.1em;list-style:square;">`;
        inv.products.forEach(p => {
            html += `<li>${p.name ? p.name : ''} <span style="color:#888;font-size:0.99em">${p.price ? Number(p.price).toLocaleString() + ' ریال' : ''}</span></li>`;
        });
        html += `</ul></td></tr>`;
    }
    return html;
}
function renderGatewayOptions() {
    const $list = document.getElementById('gateway-list');
    $list.innerHTML = '';
    gateways.forEach(gw => {
        const id = 'gw-' + gw.id;
        const div = document.createElement('div');
        div.className = 'gateway-option';
        div.tabIndex = 0;
        div.onclick = () => {
            document.querySelectorAll('.gateway-option').forEach(el => el.classList.remove('selected'));
            div.classList.add('selected');
            document.getElementById(id).checked = true;
            selectedGateway = gw.id;
            document.getElementById('pay-btn').disabled = false;
        };
        div.innerHTML = `
            <span class="gateway-logo">
                <img src="${gw.logo}" alt="${tr('gateway_' + gw.id + '_label')}">
            </span>
            <input type="radio" name="gateway" id="${id}" value="${gw.id}" style="margin-inline-end:7px;">
            <span class="gateway-info">
                <span class="gateway-label">${tr('gateway_' + gw.id + '_label')}</span>
                <span class="gateway-desc">${tr('gateway_' + gw.id + '_desc')}</span>
            </span>
        `;
        $list.appendChild(div);
    });
}

function loadInvoiceInfo() {
    showStatus('', '');
    fetch("https://api.xtremedev.co/endpoints/get_invoice_info.php?id=" + encodeURIComponent(invoice_id), {
        headers: {
            'Authorization': 'Bearer ' + access_token
        }
    })
    .then(res => res.json())
    .then(data => {
        if(data && data.success && data.invoice) {
            document.getElementById('invoice-table').innerHTML = renderInvoiceTable(data.invoice);
            if(data.invoice.status === 'unpaid') {
                document.getElementById('pay-form').style.display = '';
                renderGatewayOptions();
            } else {
                showStatus('error', tr('already_paid'));
                document.getElementById('pay-form').style.display = 'none';
            }
        } else {
            document.getElementById('invoice-table').innerHTML = `<tr><td colspan="2">${tr('not_found_invoice')}</td></tr>`;
            document.getElementById('pay-form').style.display = 'none';
        }
    })
    .catch(e => {
        document.getElementById('invoice-table').innerHTML = `<tr><td colspan="2">${tr('error_occured')}</td></tr>`;
        document.getElementById('pay-form').style.display = 'none';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    loadInvoiceInfo();
    document.getElementById('pay-form').onsubmit = function(e) {
        e.preventDefault();
        if(!selectedGateway) {
            showStatus('error', tr('select_gateway_first'));
            return false;
        }
        document.getElementById('pay-btn').disabled = true;
        showStatus('success', tr('redirecting_to_gateway'));
        const callbackurl = window.location.origin + "/pay_invoice.php?id=" + invoice_id;
        fetch('https://api.xtremedev.co/endpoints/pay_invoice.php', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + access_token,
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'invoice_id=' + encodeURIComponent(invoice_id) + '&gateway=' + encodeURIComponent(selectedGateway) + '&callbackurl=' + encodeURIComponent(callbackurl)
        })
        .then(res => res.json())
        .then(data => {
            if(data && data.success && data.payment_link) {
                window.location = data.payment_link;
            } else {
                document.getElementById('pay-btn').disabled = false;
                showStatus('error', (data && data.error) ? data.error : tr('error_occured'));
            }
        })
        .catch(err => {
            document.getElementById('pay-btn').disabled = false;
            showStatus('error', tr('error_occured'));
        });
        return false;
    };
});
</script>
</body>
</html>