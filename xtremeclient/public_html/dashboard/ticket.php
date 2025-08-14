<?php
session_start();
require_once __DIR__.'/../shared/inc/config.php';

// زبان و ترجمه
$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'fa');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : 'fa';
$is_rtl = ($lang === 'fa');

// ترجمه‌ها از فایل json
$translations = [];
$lang_file = __DIR__ . '/../shared/assets/languages/' . $lang . '.json';
if (file_exists($lang_file)) {
    $json = file_get_contents($lang_file);
    $translations = json_decode($json, true);
}

if(!isset($_SESSION['user_profile'])) {
    $client_id = 'xtremedev-web';
    $redirect_uri = 'https://xtremedev.co/oauth-callback.php';
    $state = bin2hex(random_bytes(8));
    $login_url = "https://auth.xtremedev.co/authorize.php?client_id=$client_id&redirect_uri=" . urlencode($redirect_uri) . "&response_type=code&scope=basic&state=$state";
    header("Location: $login_url");
    exit;
}
$access_token = $_SESSION['access_token'] ?? '';
$tid = isset($_GET['id']) ? intval($_GET['id']) : 0;
$darkThemeActive = (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark');
?>
<!DOCTYPE html>
<html lang="<?=$lang?>" dir="<?=$is_rtl?'rtl':'ltr'?>">
<head>
    <meta charset="UTF-8">
    <title><?=htmlspecialchars(($translations['ticket'] ?? 'Ticket')." #".$tid)?> | XtremeDev</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php include __DIR__.'/../shared/inc/head-assets.php'; ?>
    <?php include __DIR__.'/includes/dashboard-styles.php'; ?>
    <style>
        body, html { min-height: 100vh; }
        body {
            background: var(--surface) !important;
            color: var(--text) !important;
            font-family: 'Vazirmatn', Tahoma, Arial, sans-serif;
            display: flex; flex-direction: column;
        }
        body.dark-theme {
            --surface: #181f2a;
            --surface-alt: #222b38;
            --text: #e6e9f2;
            background: var(--surface) !important;
            color: var(--text) !important;
        }
        .main-content { flex: 1 0 auto; background: transparent !important; }
        .ticket-card {
            background: var(--surface-alt, #fff);
            border-radius: 18px;
            box-shadow: 0 2px 24px var(--shadow-card, #2499fa14);
            border: 1.5px solid var(--border, #2499fa18);
            padding: 2.2rem 1.3rem 1.3rem 1.3rem;
            max-width: 540px;
            margin: 46px auto 30px auto;
            width: 100%;
        }
        .ticket-title {
            color: var(--primary, #2499fa);
            font-size: 1.2rem;
            font-weight: 900;
            margin-bottom: 1.5rem;
            letter-spacing: .2px;
            text-align: left;
        }
        .ticket-meta {
            font-size: 1.05rem;
            color: #888;
            margin-bottom: 1.1rem;
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
        }
        .ticket-status {
            font-weight: 800;
            font-size: 1.07rem;
        }
        .messages-thread {
            margin-bottom: 1.5rem;
        }
        .ticket-msg-item {
            margin-bottom: 1.25rem;
            border-radius: 10px;
            padding: 1rem 1.2rem 0.7rem 1.2rem;
            background: #f8fafc;
            border: 1.2px solid #e4e7ef;
            position: relative;
            font-size: 1.03rem;
            word-break: break-word;
            box-shadow: 0 2px 10px #2499fa0a;
        }
        .ticket-msg-item.admin-msg {
            background: #eaf6fe;
            border-color: #b8e0fa;
        }
        .ticket-msg-head {
            font-size: 0.97rem;
            font-weight: 700;
            color: #2499fa;
            margin-bottom: 0.3rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        .ticket-msg-item.admin-msg .ticket-msg-head {
            color: #0d8cf7;
        }
        .msg-date {
            font-size: 0.90rem;
            color: #999;
            font-weight: 400;
            margin-left: 10px;
            direction: ltr;
        }
        .ticket-msg-body {
            white-space: pre-line;
            color: #222;
        }
        body.dark-theme .ticket-card {
            background: #222b38 !important;
            color: #e6e9f2 !important;
            box-shadow: 0 2px 24px #0d111c77;
            border-color: #384c6e !important;
        }
        body.dark-theme .ticket-title { color: #38a8ff !important; }
        body.dark-theme .ticket-meta { color: #8da7c7 !important; }
        body.dark-theme .ticket-msg-item {
            background: #181f2a !important;
            color: #e6e9f2 !important;
            border-color: #384c6e !important;
        }
        body.dark-theme .ticket-msg-item.admin-msg {
            background: #17344d !important;
            border-color: #2c4e72 !important;
        }
        body.dark-theme .ticket-msg-head { color: #38a8ff !important; }
        body.dark-theme .ticket-msg-item.admin-msg .ticket-msg-head { color: #e0f0ff !important; }
        body.dark-theme .ticket-msg-body { color: #e6e9f2 !important; }
        .back-btn {
            width: 100%;
            margin: 0 0 0.8rem 0;
            min-width: 120px;
            font-size: 1.07rem;
            font-weight: 700;
            border-radius: 10px;
            background: #e4e7ef;
            color: #2499fa;
            border: none;
            transition: background 0.18s;
            padding: 0.7rem 1.5rem;
            display: block;
            text-align: center;
        }
        .back-btn:hover, .back-btn:focus {
            background: #d1e9ff;
            color: #145d99;
        }
        body.dark-theme .back-btn {
            background: #1a253b !important;
            color: #38a8ff !important;
        }
        body.dark-theme .back-btn:hover, body.dark-theme .back-btn:focus {
            background: #222b38 !important;
            color: #fff !important;
        }
        .reply-form {
            margin-top: 2.2rem;
            margin-bottom: 0.5rem;
        }
        .reply-form label {
            font-weight: 700;
            color: #2499fa;
            margin-bottom: 7px;
            display: block;
        }
        .reply-form textarea {
            background: #f8fafc;
            border: 1.2px solid #b8e0fa;
            border-radius: 8px;
            font-size: 1.03rem;
            padding: 0.8rem 1rem;
            width: 100%;
            color: #222;
            min-height: 86px;
            max-height: 260px;
            margin-bottom: 10px;
            transition: border .14s, background .14s, color .14s;
            resize: vertical;
        }
        .reply-form textarea:focus { border-color: #2499fa; outline: none; }
        body.dark-theme .reply-form textarea {
            background: #181f2a !important;
            color: #e6e9f2 !important;
            border-color: #384c6e !important;
        }
        .reply-form button {
            width: 100%;
            background: #2499fa;
            color: #fff;
            font-weight: 800;
            border-radius: 10px;
            margin-bottom: 0.6rem;
            margin-top: 0.5rem;
            border: none;
            padding: 0.7rem 1.5rem;
            font-size: 1.07rem;
            transition: background .13s, color .13s;
            display: block;
            text-align: center;
        }
        .reply-form button:hover, .reply-form button:focus {
            background: #155fa0;
            color: #fff;
        }
        body.dark-theme .reply-form button {
            background: #38a8ff !important;
            color: #fff !important;
        }
        body.dark-theme .reply-form button:hover, body.dark-theme .reply-form button:focus {
            background: #2499fa !important;
            color: #fff !important;
        }
        .alert { font-size:1.03rem; border-radius:8px; margin-bottom:1.2rem; }
        /* لودینگ اسکلتون */
        .ticket-skeleton-meta,
        .ticket-skeleton-msg {
            border-radius: 12px;
            background: linear-gradient(90deg, #f4f7fa 25%, #e6edf5 50%, #f4f7fa 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.13s infinite linear;
            margin-bottom: 1.1rem;
        }
        .ticket-skeleton-meta {
            height: 32px;
            width: 70%;
            max-width: 300px;
        }
        .ticket-skeleton-msg {
            height: 52px;
            width: 100%;
            margin-bottom: 1.3rem;
        }
        .dark-theme .ticket-skeleton-meta,
        .dark-theme .ticket-skeleton-msg {
            background: linear-gradient(90deg, #232d3b 25%, #1e2632 50%, #232d3b 75%);
        }
        @keyframes skeleton-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        @media (max-width: 600px) {
            .ticket-card { padding: 1.3rem 0.7rem 1.1rem 0.7rem; }
            .ticket-msg-item { padding: 0.8rem 0.6rem 0.6rem 0.6rem; font-size: 0.98rem;}
            .ticket-skeleton-meta { height:28px; }
            .ticket-skeleton-msg { height:44px; }
        }
        html[dir="rtl"] .ticket-title {text-align:right;}
    </style>
</head>
<body<?= $darkThemeActive ? ' class="dark-theme"' : '' ?>>
<?php include 'includes/dashboard-navbar.php'; ?>

<div class="main-content">
    <div class="container">
        <div class="ticket-card" id="ticket-card">
            <!-- لودینگ اسکلتون -->
            <div id="ticket-loading">
                <div class="ticket-skeleton-meta"></div>
                <div class="ticket-skeleton-meta" style="width:48%;"></div>
                <div style="margin:1.4rem 0 1rem 0;">
                    <div class="ticket-skeleton-msg"></div>
                    <div class="ticket-skeleton-msg" style="width:85%;"></div>
                    <div class="ticket-skeleton-msg" style="width:60%;"></div>
                </div>
            </div>
            <div id="ticket-content" style="display:none;"></div>
        </div>
    </div>
</div>

<?php include 'includes/dashboard-footer.php'; ?>
<?php include __DIR__.'/../shared/inc/foot-assets.php'; ?>
<?php if(file_exists('includes/theme-script.php')) include 'includes/theme-script.php'; ?>
<script>
const t = <?=json_encode($translations, JSON_UNESCAPED_UNICODE)?>;
const lang = <?=json_encode($lang)?>;
const access_token = <?=json_encode($access_token)?>;
const ticket_id = <?=json_encode($tid)?>;
const isRtl = <?= $is_rtl ? 'true' : 'false' ?>;
function escapeHtml(str) {
    return (str || "").replace(/[<>&"]/g, function(m) {
        return {'<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;'}[m];
    });
}
function toJalali(dateStr) {
    if (!dateStr) return '';
    const d = dateStr.split(' ')[0].split('-');
    let gy = parseInt(d[0]), gm = parseInt(d[1]), gd = parseInt(d[2]);
    const g_d_m = [0,31,59,90,120,151,181,212,243,273,304,334];
    let jy = (gy<=1600) ? 0 : 979, gy2 = (gy<=1600) ? gy+621 : gy-1600;
    let days = (365*gy2) + Math.floor((gy2+3)/4) - Math.floor((gy2+99)/100) + Math.floor((gy2+399)/400) - 80 + gd + g_d_m[gm-1];
    if (gm>2 && ((gy%4==0 && gy%100!=0)||gy%400==0)) days++;
    jy += 33*Math.floor(days/12053); days %= 12053;
    jy += 4*Math.floor(days/1461); days %= 1461;
    if (days > 365) { jy += Math.floor((days-1)/365); days = (days-1)%365; }
    let jm, jd;
    if (days < 186) { jm = 1+Math.floor(days/31); jd = 1+(days%31); }
    else { jm = 7+Math.floor((days-186)/30); jd = 1+((days-186)%30); }
    return jy + '/' + (jm<10?'0':'')+jm + '/' + (jd<10?'0':'')+jd;
}

function renderTicket(data) {
    const $loading = document.getElementById('ticket-loading');
    const $content = document.getElementById('ticket-content');
    if (data.error) {
        $loading.innerHTML = '<div class="alert alert-danger text-center">' + escapeHtml(t.not_found || 'Ticket not found.') + '</div>';
        return;
    }
    $loading.style.display = 'none';
    $content.style.display = '';
    let statusMap = {
        open: { color: "#ffa500", label: t.open },
        answered: { color: "#2499fa", label: t.answered },
        closed: { color: "#aaa", label: t.closed }
    };
    let statusObj = statusMap[data.status] || { color: "#555", label: data.status };
    let createdVal = (lang === 'fa') ? toJalali(data.created_at) : (data.created_at || '');
    let subject = escapeHtml(data.subject || '');
    let ticketMeta = `
        <div class="ticket-title">${subject}</div>
        <div class="ticket-meta">
            <span>
                <span class="ticket-status" style="color:${statusObj.color}">
                    ${t.status}: ${escapeHtml(statusObj.label)}
                </span>
            </span>
            <span>
                ${t.created}: ${escapeHtml(createdVal)}
            </span>
        </div>
    `;
    // پیام‌ها
    let messagesHtml = '';
    if (data.messages && data.messages.length) {
        data.messages.forEach(m => {
            let isAdmin = (m.sender === 'admin');
            let who = isAdmin ? (t.support||'Support') : (t.you||'You');
            let msgDate = (lang === 'fa') ? toJalali(m.created_at) : (m.created_at || '');
            messagesHtml += `
                <div class="ticket-msg-item ${isAdmin ? 'admin-msg' : 'user-msg'}">
                    <div class="ticket-msg-head">
                        ${escapeHtml(who)}
                        <span class="msg-date">${escapeHtml(msgDate)}</span>
                    </div>
                    <div class="ticket-msg-body">${escapeHtml(m.message).replace(/\n/g, '<br>')}</div>
                </div>
            `;
        });
    } else {
        messagesHtml = `<div style="color:#aaa;">${t.no_messages||'No messages for this ticket.'}</div>`;
    }
    let threadHtml = `<div class="messages-thread">${messagesHtml}</div>`;
    // فرم پاسخ (اگر بسته نباشد)
    let replyFormHtml = '';
    if (data.status !== 'closed') {
        replyFormHtml = `
            <form class="reply-form" id="reply-form" autocomplete="off">
                <label for="reply">${t.your_reply||'Your reply:'}</label>
                <textarea name="reply" id="reply" required placeholder="${t.type_reply||'Type your reply...'}"></textarea>
                <button type="submit">${t.reply_btn||'Send Reply'}</button>
                <div id="reply-alert" style="margin-top:8px;"></div>
            </form>
        `;
    } else {
        replyFormHtml = `<div class="alert alert-warning text-center mb-0">${t.closed_notice||'This ticket is closed.'}</div>`;
    }
    // دکمه بازگشت
    let backBtnHtml = `<a href="tickets.php" class="back-btn">${t.back_to_tickets||'← My Tickets'}</a>`;
    $content.innerHTML = ticketMeta + threadHtml + replyFormHtml + backBtnHtml;
}

document.addEventListener('DOMContentLoaded', function() {
    fetch('https://api.xtremedev.co/endpoints/ticket_detail.php?ticket_id=' + encodeURIComponent(ticket_id), {
        headers: { 'Authorization': 'Bearer ' + access_token }
    })
    .then(async res => {
        let text = await res.text();
        console.log("Server response (ticket detail):", text); // نمایش ریسپانس سرور در کنسول
        let data;
        try { data = JSON.parse(text); } catch(e){ data = text; }
        renderTicket(data);
    })
    .catch(err => {
        document.getElementById('ticket-loading').innerHTML =
            '<div class="alert alert-danger text-center">'+escapeHtml(t.not_found||'Ticket not found.')+'</div>';
    });

    // ثبت پاسخ
    document.addEventListener('submit', function(ev) {
        if (ev.target && ev.target.id === 'reply-form') {
            ev.preventDefault();
            let reply = document.getElementById('reply').value.trim();
            let alertBox = document.getElementById('reply-alert');
            alertBox.innerHTML = '';
            if (!reply) {
                alertBox.innerHTML = `<div class="alert alert-danger text-center">${t.msg_empty||'Your reply cannot be empty.'}</div>`;
                return;
            }
            if (reply.length > 2000) {
                alertBox.innerHTML = `<div class="alert alert-danger text-center">${t.msg_toolong||'Your reply is too long.'}</div>`;
                return;
            }
            let btn = ev.target.querySelector('button[type=submit]');
            btn.disabled = true;
            btn.textContent = '...';
            fetch('https://api.xtremedev.co/endpoints/reply_ticket.php', {
                method: 'POST',
                headers: {'Authorization': 'Bearer ' + access_token},
                body: new URLSearchParams({ticket_id: ticket_id, message: reply})
            })
            .then(async res => {
                let text = await res.text();
                console.log("Server response (reply):", text); // نمایش ریسپانس سرور بعد از ارسال پیام
                let data;
                try { data = JSON.parse(text); } catch(e){ data = text; }
                if (res.ok && data.success) {
                    alertBox.innerHTML = `<div class="alert alert-success text-center">${t.msg_sent||'Your reply was sent successfully.'}</div>`;
                    setTimeout(()=>window.location.reload(), 700);
                } else {
                    alertBox.innerHTML = `<div class="alert alert-danger text-center">${t.msg_failed||'Failed to send your reply.'}</div>`;
                }
            })
            .catch(() => {
                alertBox.innerHTML = `<div class="alert alert-danger text-center">${t.msg_failed||'Failed to send your reply.'}</div>`;
            })
            .finally(()=>{
                btn.disabled = false;
                btn.textContent = t.reply_btn||'Send Reply';
            });
        }
    });
});
</script>
</body>
</html>