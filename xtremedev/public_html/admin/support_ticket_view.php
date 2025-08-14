<?php
session_start();
if (!isset($_SESSION['admin_user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'databaseConfig.php';

// Role check
$admin_id = $_SESSION['admin_user_id'];
$stmt = $mysqli->prepare("SELECT username, email, role FROM admin_users WHERE id=? LIMIT 1");
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$stmt->bind_result($username, $email, $role);
$stmt->fetch();
$stmt->close();

$can_view = in_array($role, ['superadmin', 'manager', 'support', 'read_only']);
if (!$can_view) {
    header("Location: access_denied.php");
    exit;
}

// Get ticket id
$ticket_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($ticket_id <= 0) {
    header("Location: support_tickets.php");
    exit;
}

// Fetch ticket info
$stmt = $mysqli->prepare("
    SELECT t.id, t.subject, t.status, t.created_at, t.updated_at, u.id, u.name, u.email
    FROM support_tickets t
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.id=?
    LIMIT 1
");
$stmt->bind_param('i', $ticket_id);
$stmt->execute();
$stmt->bind_result($tid, $subject, $status, $created_at, $updated_at, $user_id, $user_name, $user_email);
if(!$stmt->fetch()) {
    $stmt->close();
    header("Location: support_tickets.php");
    exit;
}
$stmt->close();

// Fetch all messages
$msgs = [];
$stmt = $mysqli->prepare("
    SELECT id, user_id, role, message, created_at
    FROM ticket_messages
    WHERE ticket_id=?
    ORDER BY created_at ASC
");
$stmt->bind_param('i', $ticket_id);
$stmt->execute();
$stmt->bind_result($mid, $msg_user_id, $msg_role, $msg_text, $msg_created);
while ($stmt->fetch()) {
    $msgs[] = [
        'id' => $mid,
        'user_id' => $msg_user_id,
        'role' => $msg_role,
        'message' => $msg_text,
        'created_at' => $msg_created
    ];
}
$stmt->close();

// Status badge
function status_badge($status) {
    $map = [
        'open'     => ['Open',     '#38a8ff'],
        'answered' => ['Answered', '#38c572'],
        'closed'   => ['Closed',   '#e13a3a'],
    ];
    $d = $map[strtolower($status)] ?? [ucfirst($status), '#6c8cff'];
    return '<span style="display:inline-block;min-width:64px;padding:3px 14px;border-radius:8px;background:'.$d[1].';color:#fff;font-weight:700;font-size:.96rem;text-align:center;">'.$d[0].'</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Ticket #<?=$tid?> | Admin Panel</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <?php include 'includes/styles.php'; ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    body { background: #181f27 !important; color: #e6e9f2 !important; font-family: Vazirmatn, Tahoma, Arial, sans-serif; }
    .container-main { max-width: 750px; margin:40px auto 0 auto; }
    .page-title {
      font-weight:900;
      color:#38a8ff;
      font-size:1.35rem;
      margin-bottom:1.2rem;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .ticket-info {
      background: #232d3b;
      border-radius: 16px;
      box-shadow: 0 2px 24px #38a8ff08;
      border: 1.5px solid #29364b;
      padding: 1.2rem 1.2rem 1rem 1.2rem;
      margin-bottom: 22px;
    }
    .chat-box {
      max-height: 390px;
      overflow-y: auto;
      background: #232d3b;
      border-radius: 12px;
      border: 1.3px solid #29364b;
      padding: 18px 10px 8px 10px;
      margin-bottom: 22px;
    }
    .msg-row {
      display: flex;
      margin-bottom: 13px;
      align-items: flex-start;
    }
    .msg-row.admin { flex-direction: row-reverse; }
    .msg-bubble {
      max-width: 75%;
      min-width: 80px;
      padding: 11px 16px;
      border-radius: 15px;
      font-size: 1rem;
      background: #202942;
      color: #e6e9f2;
      box-shadow: 0 2px 9px #232d3b44;
      line-height: 1.6;
      position: relative;
    }
    .msg-row.admin .msg-bubble { background: #38a8ff; color: #fff; }
    .msg-meta {
      font-size: .88em;
      color: #aad3ff;
      margin: 0 7px;
      margin-top: 2px;
      min-width: 70px;
      text-align: left;
    }
    .msg-row.admin .msg-meta { text-align: right; color: #fff5; }
    .msg-bubble pre { white-space: pre-wrap; font-family: inherit; margin: 0; }
    .msg-bubble strong {color:#f4be42;}
    .back-link {
      color:#aad3ff;
      text-decoration:none;
      margin-top:18px;
      display:inline-block;
      font-size:1.02rem;
      margin-bottom:4px;
    }
    .back-link:hover {color:#fff;text-decoration:underline;}
    @media (max-width: 650px) {
      .container-main{padding:0 2px;}
      .page-title{font-size:1.02rem;}
      .ticket-info{padding:.7rem .6rem;}
      .chat-box{padding:10px 2px;}
    }
  </style>
</head>
<body>
<?php 
switch($role) {
    case 'superadmin':
        include 'includes/superadmin-navbar.php';
        break;
    case 'manager':
        include 'includes/manager-navbar.php';
        break;
    case 'support':
        include 'includes/supporter-navbar.php';
        break;
    case 'read_only':
        include 'includes/readonly-navbar.php';
        break;
    default:
        include 'includes/navbar.php';
}
?>
<div class="container-main">
  <div class="page-title">
    View Ticket #<?=$tid?>
    <?=status_badge($status)?>
  </div>
  <div class="ticket-info">
    <div><b>Subject:</b> <?=htmlspecialchars($subject)?></div>
    <div><b>User:</b> <?=htmlspecialchars($user_name)?> <span style="color:#38a8ff;">[<?=htmlspecialchars($user_email)?>]</span></div>
    <div><b>Created at:</b> <?=htmlspecialchars($created_at)?></div>
    <div><b>Last update:</b> <?=htmlspecialchars($updated_at)?></div>
  </div>
  <div class="chat-box">
    <?php foreach($msgs as $msg): ?>
      <div class="msg-row <?=$msg['role']=='admin'?'admin':''?>">
        <div class="msg-bubble">
          <pre><?=htmlspecialchars($msg['message'])?></pre>
        </div>
        <div class="msg-meta">
          <?=$msg['role']=='admin'?'Admin':'User'?><br>
          <?=htmlspecialchars($msg['created_at'])?>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if(empty($msgs)): ?>
      <div class="text-center text-muted">No messages yet.</div>
    <?php endif; ?>
  </div>
  <a href="support_tickets.php" class="back-link">&larr; Back to tickets list</a>
</div>
<footer class="footer-sticky">
  &copy; <?=date('Y')?> XtremeDev. All rights reserved.
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>