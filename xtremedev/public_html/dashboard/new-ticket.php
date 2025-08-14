<?php
session_start();
require_once __DIR__.'/../shared/inc/config.php';

$lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'en');
$lang = (defined('ALLOWED_LANGS') && in_array($lang, ALLOWED_LANGS)) ? $lang : (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'en');
$is_rtl = ($lang === 'fa');
$darkThemeActive = (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark');

// ترجمه از فایل
$translations = [];
$lang_file = __DIR__ . '/../shared/assets/languages/' . $lang . '.json';
if (file_exists($lang_file)) {
    $json = file_get_contents($lang_file);
    $translations = json_decode($json, true);
}

// ------ SSO ------
if (!isset($_SESSION['user_profile'])) {
    $client_id = 'xtremedev-web';
    $redirect_uri = 'https://xtremedev.co/oauth-callback.php';
    $state = bin2hex(random_bytes(8));

    // theme را هم از کوکی بگیر و اگر نبود پیش‌فرض light
    $theme = isset($_COOKIE['theme']) && in_array($_COOKIE['theme'], ['light','dark']) ? $_COOKIE['theme'] : 'light';

    $login_url = "https://auth.xtremedev.co/authorize.php?client_id=$client_id"
        . "&redirect_uri=" . urlencode($redirect_uri)
        . "&response_type=code"
        . "&scope=basic"
        . "&state=$state"
        . "&lang=" . urlencode($lang)
        . "&theme=" . urlencode($theme); // تم را هم اضافه کردیم

    header("Location: $login_url");
    exit;
}

$access_token = $_SESSION['access_token'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?=$lang?>" dir="<?=$is_rtl?'rtl':'ltr'?>">
<head>
    <meta charset="UTF-8">
    <title><?= $translations['new_ticket'] ?? '' ?> | XtremeDev</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php include __DIR__.'/../shared/inc/head-assets.php'; ?>
    <?php include __DIR__.'/includes/dashboard-styles.php'; ?>
    <style>
        body, html { min-height: 100vh; }
        body {
            background: var(--surface, #f4f7fa) !important;
            color: var(--text, #222) !important;
            font-family: 'Vazirmatn', Tahoma, Arial, sans-serif;
            display: flex;
            flex-direction: column;
        }
        body.dark-theme {
            --surface: #181f2a;
            --surface-alt: #222b38;
            --text: #e6e9f2;
            background: var(--surface) !important;
            color: var(--text) !important;
        }
        .main-content { flex: 1 0 auto; background: transparent !important;}
        .new-ticket-card {
            background: var(--surface-alt, #fff);
            border-radius: 18px;
            box-shadow: 0 2px 24px var(--shadow-card, #2499fa14);
            border: 1.5px solid var(--border, #2499fa18);
            padding: 2.2rem 1.3rem 1.7rem 1.3rem;
            max-width: 430px;
            margin: 40px auto 30px auto;
            width: 100%;
        }
        .new-ticket-card h2 {
            color: var(--primary, #2499fa);
            font-size: 1.33rem;
            font-weight: 900;
            margin-bottom: 1.8rem;
            letter-spacing: .4px;
            text-align: center;
        }
        .form-label { font-weight: 700; color: #2499fa; }
        .form-control {
            border-radius: 9px;
            font-size: 1rem;
            border: 1.5px solid #dbe6f7;
            margin-bottom: 1rem;
            background: var(--surface, #f4f7fa);
            color: var(--text, #222);
            min-height: 44px;
            line-height: 1.6;
            transition: border 0.2s;
        }
        .form-control:focus {
            border-color: #38a8ff;
            background: var(--surface, #f4f7fa);
            color: var(--text, #222);
        }
        .btn-primary { background: #2499fa; border:0; font-weight:800; }
        .btn-primary:hover { background: #38a8ff; }
        .back-btn {
            margin: 1.7rem auto 0 auto;
            display: block;
            min-width: 120px;
            font-size: 1.07rem;
            font-weight: 700;
            border-radius: 10px;
            background: #e4e7ef;
            color: #2499fa;
            border: none;
            transition: background 0.18s;
            padding: 0.7rem 1.5rem;
            text-align: center;
        }
        .back-btn:hover, .back-btn:focus {
            background: #d1e9ff;
            color: #145d99;
        }
        .alert-danger, .alert-success { font-size: 1rem; border-radius:8px;}
        body.dark-theme .new-ticket-card {
            background: #222b38 !important;
            color: #e6e9f2 !important;
            box-shadow: 0 2px 24px #0d111c77;
            border-color: #384c6e !important;
        }
        body.dark-theme .form-control {
            background: #111a27 !important;
            color: #fff !important;
            border-color: #384c6e !important;
            caret-color: #fff !important;
        }
        body.dark-theme .form-control:focus {
            border-color: #38a8ff !important;
            background: #161e2e !important;
            color: #fff !important;
        }
        body.dark-theme .form-label { color: #38a8ff !important; }
        body.dark-theme .back-btn { background: #1a253b !important; color: #38a8ff !important; }
        body.dark-theme .back-btn:hover, body.dark-theme .back-btn:focus { background: #222b38 !important; color: #fff !important;}
        @media (max-width: 600px) {
            .new-ticket-card { padding: 1.3rem 0.7rem 1.1rem 0.7rem; }
        }
        html[dir="rtl"] .new-ticket-card h2 {text-align: right;}
        #debug-response {
            background: #23272e;
            color: #fff;
            border-radius: 11px;
            margin: 2.5rem auto 1.5rem auto;
            max-width: 430px;
            font-size: 0.93rem;
            padding: 1.1rem 1rem 1.1rem 1rem;
            direction: ltr;
            box-shadow: 0 2px 16px #0002;
            overflow-x: auto;
            word-break: break-all;
        }
        #debug-response-title {
            color: #2bc551;
            font-weight: 900;
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }
    </style>
    <script>
        window.PAGE_TRANSLATIONS = <?= json_encode($translations, JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body<?= $darkThemeActive ? ' class="dark-theme"' : '' ?>>
<?php include 'includes/dashboard-navbar.php'; ?>

<div class="main-content">
    <div class="container">
        <div class="new-ticket-card">
            <h2><?= $translations['new_ticket'] ?? '' ?></h2>
            <div id="form-alert"></div>
            <form id="ticket-form" autocomplete="off">
                <div class="mb-3">
                    <label for="subject" class="form-label"><?= $translations['subject'] ?? '' ?></label>
                    <input type="text" class="form-control" id="subject" name="subject" maxlength="128" required placeholder="<?= $translations['subject_ph'] ?? '' ?>">
                </div>
                <div class="mb-3">
                    <label for="message" class="form-label"><?= $translations['message'] ?? '' ?></label>
                    <textarea class="form-control" id="message" name="message" rows="5" maxlength="2000" required placeholder="<?= $translations['message_ph'] ?? '' ?>"></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100 mt-1"><?= $translations['submit_ticket'] ?? '' ?></button>
            </form>
            <a href="tickets.php" class="back-btn"><?= $translations['back'] ?? '' ?></a>
        </div>
    </div>
</div>

<div id="debug-response" style="display:none;">
    <div id="debug-response-title">API Response:</div>
    <pre id="debug-response-pre" style="margin:0;white-space:pre-wrap;"></pre>
</div>

<?php include 'includes/dashboard-footer.php'; ?>
<?php include __DIR__.'/../shared/inc/foot-assets.php'; ?>
<?php if(file_exists('includes/theme-script.php')) include 'includes/theme-script.php'; ?>
<script>
const t = window.PAGE_TRANSLATIONS || {};

function showDebugResponse(res) {
    let $box = document.getElementById('debug-response');
    let $pre = document.getElementById('debug-response-pre');
    $box.style.display = '';
    if (typeof res === 'string') {
        $pre.textContent = res;
    } else {
        $pre.textContent = JSON.stringify(res, null, 2);
    }
}

document.getElementById('ticket-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const subject = document.getElementById('subject').value.trim();
    const message = document.getElementById('message').value.trim();
    const alert = document.getElementById('form-alert');
    alert.innerHTML = '';

    if (!subject || !message) {
        alert.innerHTML = `<div class="alert alert-danger text-center">${t['required']||''}</div>`;
        return;
    }
    if (subject.length > 128) {
        alert.innerHTML = `<div class="alert alert-danger text-center">${t['subject_long']||''}</div>`;
        return;
    }

    const btn = this.querySelector('button[type=submit]');
    btn.disabled = true;
    btn.textContent = '...';

    fetch('https://api.xtremedev.co/endpoints/create_ticket.php', {
        method: 'POST',
        headers: {
            'Authorization':'Bearer <?=htmlspecialchars($access_token)?>'
        },
        body: new URLSearchParams({subject, message})
    })
    .then(async res => {
        let text = await res.text();
        let data;
        try { data = JSON.parse(text); } catch(e) { data = text; }
        showDebugResponse(data);
        if (res.ok && data.success) {
            alert.innerHTML = `<div class="alert alert-success text-center">${t['success']||''}</div>`;
            setTimeout(()=>window.location = 'tickets.php', 900);
        } else {
            alert.innerHTML = `<div class="alert alert-danger text-center">${t['fail']||''}</div>`;
        }
    })
    .catch((err) => {
        showDebugResponse(String(err));
        alert.innerHTML = `<div class="alert alert-danger text-center">${t['fail']||''}</div>`;
    })
    .finally(()=>{
        btn.disabled = false;
        btn.textContent = t['submit_ticket']||'';
    });
});
</script>
</body>
</html>