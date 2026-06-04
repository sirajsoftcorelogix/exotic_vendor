<?php
/**
 * OTP SMTP test page (web).
 *
 * URL: http://localhost/exotic_vendor/test_otp_smtp.php
 *      http://localhost/exotic_vendor/test_otp_smtp.php?key=YOUR_EXPECTED_SECRET_KEY
 *
 * Uses the same init + mail_helper as login / forgot-password OTP emails.
 */

declare(strict_types=1);

$root = __DIR__;
$init = $root . '/bootstrap/init/init.php';
if (!is_file($init)) {
    $init = $root . '/folders/bootstrap/init/init.php';
}
if (!is_file($init)) {
    http_response_code(500);
    echo '<h1>Missing init.php</h1><p>Copy <code>folders/bootstrap/init/init.php</code> to <code>bootstrap/init/init.php</code> and configure SMTP.</p>';
    exit;
}

require_once $init;
require_once $root . '/vendor/autoload.php';
require_once $root . '/helpers/mail_helper.php';

function otpSmtpTestAllowed(): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (in_array($ip, ['127.0.0.1', '::1'], true)) {
        return true;
    }

    $key = trim((string) ($_GET['key'] ?? $_POST['key'] ?? ''));
    if ($key === '' || !defined('EXPECTED_SECRET_KEY') || EXPECTED_SECRET_KEY === '') {
        return false;
    }

    return hash_equals((string) EXPECTED_SECRET_KEY, $key);
}

function otpSmtpMask(string $value, int $visible = 2): string
{
    if ($value === '') {
        return '(empty)';
    }
    if (strlen($value) <= $visible) {
        return str_repeat('*', strlen($value));
    }

    return substr($value, 0, $visible) . str_repeat('*', max(4, strlen($value) - $visible));
}

function otpSmtpConfigSummary(): array
{
    return [
        'host' => defined('smtpHost') ? smtpHost : '',
        'port' => defined('smtpPort') ? (int) smtpPort : 587,
        'secure' => defined('smtpSecure') ? (string) smtpSecure : '(auto)',
        'user' => defined('smtpUser') ? smtpUser : '',
        'pass_set' => defined('smtpPass') && smtpPass !== '',
        'from' => function_exists('vendorSmtpFromEmail') ? vendorSmtpFromEmail() : '',
    ];
}

if (!otpSmtpTestAllowed()) {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>OTP SMTP Test — Access denied</title>
        <style>
            body { font-family: system-ui, sans-serif; max-width: 520px; margin: 3rem auto; padding: 0 1rem; color: #333; }
            code { background: #f4f4f4; padding: 2px 6px; border-radius: 4px; }
        </style>
    </head>
    <body>
        <h1>Access denied</h1>
        <p>Open from <strong>localhost</strong>, or append your <code>EXPECTED_SECRET_KEY</code> from init:</p>
        <p><code>test_otp_smtp.php?key=…</code></p>
    </body>
    </html>
    <?php
    exit;
}

$result = null;
$debugLog = [];
$isPost = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
$action = $_POST['action'] ?? '';

if ($isPost && $action === 'connect') {
    try {
        $mail = createVendorMailer(30);
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = static function ($str, $level) use (&$debugLog): void {
            $debugLog[] = "[{$level}] {$str}";
        };
        $connected = $mail->smtpConnect();
        if ($connected) {
            $mail->smtpClose();
        }
        $result = [
            'ok' => (bool) $connected,
            'title' => $connected ? 'SMTP connection OK' : 'SMTP connection failed',
            'detail' => $connected ? 'Authenticated and connected to the mail server.' : trim((string) $mail->ErrorInfo),
        ];
    } catch (Throwable $e) {
        $result = [
            'ok' => false,
            'title' => 'SMTP connection failed',
            'detail' => $e->getMessage(),
        ];
    }
}

if ($isPost && $action === 'send_otp') {
    $to = trim((string) ($_POST['email'] ?? ''));
    $template = $_POST['template'] ?? 'login_otp.html';
    $allowedTemplates = [
        'login_otp.html' => 'VendorDesk - Login OTP (test)',
        'password_recovery.html' => 'VendorDesk - Password Recovery - OTP Inside (test)',
    ];
    if (!isset($allowedTemplates[$template])) {
        $template = 'login_otp.html';
    }

    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $result = ['ok' => false, 'title' => 'Invalid email', 'detail' => 'Enter a valid recipient address.'];
    } else {
        $otp = (string) random_int(100000, 999999);
        $send = sendVendorOtpEmail($to, $otp, $allowedTemplates[$template], $template);
        $result = [
            'ok' => $send['success'],
            'title' => $send['success'] ? 'OTP email sent' : 'OTP email failed',
            'detail' => $send['message'],
            'smtp_error' => $send['smtp_error'] ?? null,
            'otp' => $otp,
            'to' => $to,
            'template' => $template,
        ];
    }
}

$cfg = otpSmtpConfigSummary();
$keyParam = trim((string) ($_GET['key'] ?? $_POST['key'] ?? ''));
$keyQs = $keyParam !== '' ? '?key=' . rawurlencode($keyParam) : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OTP SMTP Test</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, Segoe UI, sans-serif; margin: 0; background: #f5f6f8; color: #1a1a1a; }
        .wrap { max-width: 720px; margin: 2rem auto; padding: 0 1rem 3rem; }
        h1 { font-size: 1.5rem; margin-bottom: 0.25rem; }
        .sub { color: #666; font-size: 0.9rem; margin-bottom: 1.5rem; }
        .card { background: #fff; border-radius: 10px; padding: 1.25rem 1.5rem; margin-bottom: 1rem; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        .card h2 { font-size: 1rem; margin: 0 0 1rem; color: #d06706; }
        dl { display: grid; grid-template-columns: 140px 1fr; gap: 0.35rem 1rem; margin: 0; font-size: 0.9rem; }
        dt { color: #666; }
        dd { margin: 0; font-family ui-monospace, monospace; word-break: break-all; }
        label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem; }
        input, select { width: 100%; padding: 0.55rem 0.65rem; border: 1px solid #ccc; border-radius: 6px; font-size: 0.95rem; }
        .field { margin-bottom: 1rem; }
        .actions { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 1rem; }
        button { cursor: pointer; border: none; border-radius: 6px; padding: 0.6rem 1.1rem; font-size: 0.9rem; font-weight: 600; }
        .btn-primary { background: #d06706; color: #fff; }
        .btn-secondary { background: #e8eaed; color: #333; }
        .result { border-radius: 8px; padding: 1rem; margin-bottom: 1rem; font-size: 0.9rem; }
        .result.ok { background: #e8f5e9; border: 1px solid #a5d6a7; }
        .result.fail { background: #ffebee; border: 1px solid #ef9a9a; }
        .result strong { display: block; margin-bottom: 0.35rem; }
        pre { background: #1e1e1e; color: #e0e0e0; padding: 0.75rem 1rem; border-radius: 6px; overflow: auto; font-size: 0.75rem; max-height: 280px; white-space: pre-wrap; word-break: break-word; }
        .hint { font-size: 0.8rem; color: #666; margin-top: 0.5rem; }
        .badge { display: inline-block; font-size: 0.75rem; padding: 0.15rem 0.5rem; border-radius: 4px; background: #fff3e0; color: #e65100; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>OTP SMTP Test</h1>
    <p class="sub">Same stack as login OTP: <code>sendVendorOtpEmail()</code> + <code>helpers/mail_helper.php</code></p>

    <div class="card">
        <h2>Current SMTP config</h2>
        <dl>
            <dt>Host</dt><dd><?= htmlspecialchars($cfg['host'] ?: '(not set)', ENT_QUOTES, 'UTF-8') ?></dd>
            <dt>Port</dt><dd><?= (int) $cfg['port'] ?></dd>
            <dt>Encryption</dt><dd><?= htmlspecialchars($cfg['secure'], ENT_QUOTES, 'UTF-8') ?></dd>
            <dt>Username</dt><dd><?= htmlspecialchars($cfg['user'] ?: '(not set)', ENT_QUOTES, 'UTF-8') ?></dd>
            <dt>Password</dt><dd><?= $cfg['pass_set'] ? 'set (' . htmlspecialchars(otpSmtpMask(defined('smtpPass') ? smtpPass : ''), ENT_QUOTES, 'UTF-8') . ')' : '<span class="badge">missing</span>' ?></dd>
            <dt>From</dt><dd><?= htmlspecialchars($cfg['from'] ?: '(not set)', ENT_QUOTES, 'UTF-8') ?></dd>
        </dl>
    </div>

    <?php if ($result !== null): ?>
        <div class="result <?= !empty($result['ok']) ? 'ok' : 'fail' ?>">
            <strong><?= htmlspecialchars($result['title'], ENT_QUOTES, 'UTF-8') ?></strong>
            <div><?= htmlspecialchars($result['detail'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php if (!empty($result['otp'])): ?>
                <p class="hint">Test OTP (not saved to DB): <code><?= htmlspecialchars((string) $result['otp'], ENT_QUOTES, 'UTF-8') ?></code>
                    → <?= htmlspecialchars((string) ($result['to'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <?php if (!empty($result['smtp_error'])): ?>
                <p class="hint"><strong>Technical:</strong></p>
                <pre><?= htmlspecialchars((string) $result['smtp_error'], ENT_QUOTES, 'UTF-8') ?></pre>
            <?php endif; ?>
        </div>
        <?php if ($debugLog !== []): ?>
            <div class="card">
                <h2>SMTP debug log</h2>
                <pre><?= htmlspecialchars(implode("\n", $debugLog), ENT_QUOTES, 'UTF-8') ?></pre>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="card">
        <h2>1. Connection only</h2>
        <p class="hint">Opens SMTP and authenticates; does not send mail.</p>
        <form method="post" action="<?= htmlspecialchars('test_otp_smtp.php' . $keyQs, ENT_QUOTES, 'UTF-8') ?>">
            <?php if ($keyParam !== ''): ?>
                <input type="hidden" name="key" value="<?= htmlspecialchars($keyParam, ENT_QUOTES, 'UTF-8') ?>">
            <?php endif; ?>
            <input type="hidden" name="action" value="connect">
            <div class="actions">
                <button type="submit" class="btn-secondary">Test SMTP connect</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>2. Send OTP email</h2>
        <form method="post" action="<?= htmlspecialchars('test_otp_smtp.php' . $keyQs, ENT_QUOTES, 'UTF-8') ?>">
            <?php if ($keyParam !== ''): ?>
                <input type="hidden" name="key" value="<?= htmlspecialchars($keyParam, ENT_QUOTES, 'UTF-8') ?>">
            <?php endif; ?>
            <input type="hidden" name="action" value="send_otp">
            <div class="field">
                <label for="email">Recipient email</label>
                <input type="email" id="email" name="email" required
                       value="<?= htmlspecialchars((string) ($_POST['email'] ?? (defined('smtpUser') ? smtpUser : '')), ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="you@example.com">
            </div>
            <div class="field">
                <label for="template">Template</label>
                <select id="template" name="template">
                    <option value="login_otp.html" <?= ($_POST['template'] ?? '') === 'login_otp.html' ? 'selected' : '' ?>>Login OTP (login_otp.html)</option>
                    <option value="password_recovery.html" <?= ($_POST['template'] ?? '') === 'password_recovery.html' ? 'selected' : '' ?>>Password recovery OTP (password_recovery.html)</option>
                </select>
            </div>
            <p class="hint">Generates a random 6-digit OTP for the email body only; it is not written to <code>vp_users.remember_token</code>.</p>
            <div class="actions">
                <button type="submit" class="btn-primary">Send test OTP email</button>
            </div>
        </form>
    </div>

    <p class="hint">CLI alternative: <code>php scripts/test_smtp.php recipient@example.com</code></p>
</div>
</body>
</html>
