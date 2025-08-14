<?php
session_start();
require_once __DIR__.'/../shared/inc/config.php';

if (!isset($_SESSION['admin_user_id']) || !isset($_SESSION['admin_access_token'])) {
    header("Location: login.php");
    exit;
}

$lang = 'en';
$is_rtl = false;

$username = $_SESSION['admin_username'] ?? '';
$email    = $_SESSION['admin_email'] ?? '';
$role     = $_SESSION['admin_role'] ?? '';
$access_token = $_SESSION['admin_access_token'] ?? '';

if (!in_array($role, ['superadmin', 'manager', 'support'])) {
    header("Location: access_denied.php");
    exit;
}

$ticket_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($ticket_id <= 0) {
    header("Location: support_tickets.php");
    exit;
}

$message = '';
$success = false;

// --- Handle status/reply POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $api_url = 'https://api.xtremedev.co/endpoints/admin/manage_ticket.php';
    $payload = [
        'ticket_id' => $ticket_id,
        'lang'      => $lang
    ];
    if (isset($_POST['status'])) $payload['status'] = $_POST['status'];
    if (isset($_POST['reply']) && trim($_POST['reply']) !== '') $payload['reply'] = trim($_POST['reply']);

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $access_token"
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $api_resp = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = $api_resp ? json_decode($api_resp, true) : [];
    if ($httpcode === 200 && !empty($data['success'])) {
        $success = true;
        $message = $data['message'] ?? (isset($payload['reply']) ? "Reply sent successfully." : "Ticket status updated successfully.");
    } else {
        $success = false;
        $message = $data['error'] ?? "Failed to process request.";
    }
}

// --- Get ticket info & messages ---
$api_url = 'https://api.xtremedev.co/endpoints/admin/ticket_info.php';
$params = ['id' => $ticket_id, 'lang' => $lang];
$ch = curl_init($api_url . '?' . http_build_query($params));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_token"
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$api_resp = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$ticket = [];
$msgs = [];
if ($httpcode === 200 && $api_resp) {
    $data = json_decode($api_resp, true);
    if (isset($data['ticket'])) $ticket = $data['ticket'];
    if (isset($data['messages'])) $msgs = $data['messages'];
}
if (!$ticket) {
    header("Location: support_tickets.php");
    exit;
}

// Status badge
function status_badge($status) {
    $map = [
        'open'     => ['Open',     '#38a8ff'],
        'answered' => ['Answered', '#38c572'],
        'closed'   => ['Closed',   '#e13a3a'],
    ];
    $d = $map[strtolower($status)] ?? ['Unknown', '#6c8cff'];
    return '<span style="display:inline-block;min-width:64px;padding:3px 14px;border-radius:8px;background:'.$d[1].';color:#fff;font-weight:700;font-size:.96rem;text-align:center;">'.$d[0].'</span>';
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Manage Ticket #<?=$ticket['id']?> | XtremeDev Admin</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php include __DIR__.'/../shared/inc/head-assets.php'; ?>
    <?php include 'includes/admin-styles.php'; ?>
    <style>
        body { background: #181f27 !important; color: #e6e9f2 !important; font-family: Vazirmatn, Tahoma, Arial, sans-serif; }
        .container-main { max-width: 820px; margin:48px auto 0 auto; }
        .page-title { font-weight:900; color:#38a8ff; font-size:2.1rem; margin-bottom:1.2rem; display: flex; align-items: center; gap: 14px; }
        .ticket-info { background: #232d3b; border-radius: 16px; box-shadow: 0 2px 24px #38a8ff08; border: 1.5px solid #29364b; padding: 1.2rem 1.2rem 1rem 1.2rem; margin-bottom: 22px; }
        .chat-box { max-height: 420px; overflow-y: auto; background: #232d3b; border-radius: 12px; border: 1.3px solid #29364b; padding: 18px 10px 8px 10px; margin-bottom: 22px; }
        .msg-row { display: flex; margin-bottom: 13px; align-items: flex-start; }
        .msg-row.admin { flex-direction: row-reverse; }
        .msg-bubble { max-width: 75%; min-width: 80px; padding: 11px 16px; border-radius: 15px; font-size: 1rem; background: #202942; color: #e6e9f2; box-shadow: 0 2px 9px #232d3b44; line-height: 1.6; position: relative; }
        .msg-row.admin .msg-bubble { background: #38a8ff; color: #fff; }
        .msg-meta { font-size: .88em; color: #aad3ff; margin: 0 7px; margin-top: 2px; min-width: 70px; text-align: left; }
        .msg-row.admin .msg-meta { text-align: right; color: #fff5; }
        .msg-bubble pre { white-space: pre-wrap; font-family: inherit; margin: 0; }
        .msg-bubble strong {color:#f4be42;}
        .form-label { color:#aad3ff;font-weight:700;margin-bottom:5px;}
        .form-control, .form-select { background: #232d3b; color: #e6e9f2; border-color: #31415a; border-radius: 8px; font-size: 1.03rem; margin-bottom: 16px; }
        .form-control:focus, .form-select:focus { border-color:#38a8ff; background:#253040; color:#fff; }
        .btn-send { background: linear-gradient(90deg,#38a8ff,#44e1ff 90%); color: #fff; border-radius: 11px; font-weight: 800; border: none; padding: 11px 38px; margin-top: 6px; transition: background .15s, box-shadow .15s; font-size: 1.15rem; box-shadow:0 2px 14px #38a8ff33;}
        .btn-send:hover { background: linear-gradient(90deg,#2499fa,#1bc6e8 90%); color: #fff; box-shadow: 0 5px 24px #38a8ff40;}
        .btn-secondary { background:#31415a; color:#aad3ff; border-radius:8px; border:none; padding:7px 28px; font-weight:700; font-size:1.03rem; margin-left:10px; margin-bottom: 6px;}
        .btn-secondary:hover { background:#202942; color:#fff; }
        .msg-success, .msg-error { padding: 12px 22px; border-radius: 8px; font-weight: 700; margin-bottom: 20px; font-size: 1.07rem; }
        .msg-success {background:#38c57233;color:#38c572;border:1.5px solid #38c57288;}
        .msg-error {background:#e13a3a22;color:#e13a3a;border:1.5px solid #e13a3a99;}
        .back-link { color:#aad3ff; text-decoration:none; margin-top:18px; display:inline-block; font-size:1.1rem; margin-bottom:4px; }
        .back-link:hover {color:#fff;text-decoration:underline;}
        .reply-box { background: #232d3b; border-radius: 18px; box-shadow:0 2px 14px #29364b22; border:2px solid #29364b; padding: 1.2rem 1.5rem; margin-bottom: 24px; }
        .reply-title { font-weight:800; color:#38a8ff; font-size:1.18rem; margin-bottom:12px; letter-spacing:.1px; }
        .footer-sticky {
            flex-shrink: 0;
            margin-top: 50px;
            width: 100%;
            background: linear-gradient(90deg, #232d3b 40%, #273c54 100%);
            color: #aad3ff;
            padding: 20px 0 9px 0;
            text-align: center;
            border-top: 2.5px solid #31415a;
            font-size: 1.08rem;
            letter-spacing: .18px;
            box-shadow: 0 -2px 24px #38a8ff11;
            position: relative;
        }
        .footer-sticky .footer-inner {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
        }
        .footer-sticky svg {
            display: inline-block;
            width: 22px;
            height: 22px;
            fill: #38a8ff;
            margin-bottom: -4px;
            margin-right: 2px;
        }
        .footer-sticky a {
            color: #aad3ff;
            text-decoration: underline dotted;
            transition: color .13s;
        }
        .footer-sticky a:hover {
            color: #fff;
        }
        @media (max-width: 700px) {
            .container-main{padding:0 2px;}
            .page-title{font-size:1.1rem;}
            .ticket-info{padding:.8rem .5rem;}
            .chat-box{padding:10px 2px;}
            .btn-send, .btn-secondary { width:100%; margin-left:0; }
            .reply-box {padding:.9rem .5rem;}
        }
    </style>
</head>
<body>
<?php
switch($role) {
    case 'superadmin': include 'includes/superadmin-navbar.php'; break;
    case 'manager':    include 'includes/manager-navbar.php'; break;
    case 'support':    include 'includes/supporter-navbar.php'; break;
    default:           include 'includes/navbar.php';
}
?>
<div class="container-main">
    <div class="page-title">
        Manage Ticket #<?=$ticket['id']?> <?=status_badge($ticket['status'])?>
    </div>
    <div class="ticket-info">
        <div><b>Subject:</b> <?=htmlspecialchars($ticket['subject'])?></div>
        <div><b>User:</b> <?=htmlspecialchars($ticket['user_name'])?> <span style="color:#38a8ff;">[<?=htmlspecialchars($ticket['user_email'])?>]</span></div>
        <div><b>Created at:</b> <?=htmlspecialchars($ticket['created_at'])?></div>
    </div>
    <?php if($message): ?>
        <div class="<?= $success ? 'msg-success' : 'msg-error' ?>"><?=htmlspecialchars($message)?></div>
    <?php endif; ?>
    <div class="reply-box">
        <div class="reply-title">Send Reply</div>
        <form method="post" autocomplete="off">
            <textarea name="reply" class="form-control" rows="4" placeholder="Write your reply..." required></textarea>
            <button type="submit" class="btn btn-send mt-2">Send Reply</button>
        </form>
    </div>
    <div class="chat-box">
        <?php foreach($msgs as $msg): ?>
            <div class="msg-row <?=$msg['sender']=='admin'?'admin':''?>">
                <div class="msg-bubble">
                    <pre><?=htmlspecialchars($msg['message'])?></pre>
                </div>
                <div class="msg-meta">
                    <?=$msg['sender']=='admin' ? 'Admin' : 'User'?><br>
                    <?=htmlspecialchars($msg['created_at'])?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if(empty($msgs)): ?>
            <div class="text-center text-muted">No messages yet.</div>
        <?php endif; ?>
    </div>
    <form method="post" autocomplete="off" style="margin-bottom:16px;">
        <label class="form-label">Change status:</label>
        <select name="status" class="form-select" style="max-width:230px;display:inline-block;">
            <option value="open" <?=$ticket['status']=='open'?'selected':''?>>Open</option>
            <option value="answered" <?=$ticket['status']=='answered'?'selected':''?>>Answered</option>
            <option value="closed" <?=$ticket['status']=='closed'?'selected':''?>>Closed</option>
        </select>
        <button type="submit" class="btn btn-secondary ms-2 mb-3" style="vertical-align:baseline;padding:7px 24px;font-size:1.02em;">Update</button>
    </form>
    <a href="support_tickets.php" class="back-link">&larr; Back to tickets list</a>
</div>
<footer class="footer-sticky">
    <div class="footer-inner">
        <svg viewBox="0 0 24 24">
            <path d="M12 2C6.477 2 2 6.477 2 12c0 5.523 4.477 10 10 10s10-4.477 10-10c0-5.523-4.477-10-10-10zm-.25 4.7c.67 0 1.25.58 1.25 1.3s-.58 1.3-1.25 1.3-1.25-.58-1.25-1.3.58-1.3 1.25-1.3zm2.46 12.29c-.19.07-.4.11-.61.11-.23 0-.45-.04-.65-.13l-1.21-.54c-.2-.09-.32-.31-.32-.58V10.6c0-.33.26-.6.59-.6h.01c.32 0 .59.27.59.6v5.53l.82.36c.31.14.44.5.29.8-.07.15-.19.26-.32.31z"/>
        </svg>
        <span>
            &copy; <?=date('Y')?> <a href="https://xtremedev.co" target="_blank" rel="noopener">XtremeDev</a>. All rights reserved.
        </span>
    </div>
</footer>
<?php include __DIR__.'/../shared/inc/foot-assets.php'; ?>
</body>
</html>