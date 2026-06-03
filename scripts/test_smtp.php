<?php

/**
 * SMTP connectivity test (CLI only).
 *
 * Usage: php scripts/test_smtp.php [recipient@example.com]
 *
 * Reads bootstrap/init/init.php — same config as login OTP emails.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run from command line only.\n");
    exit(1);
}

$root = dirname(__DIR__);
$init = $root . '/bootstrap/init/init.php';
if (!is_file($init)) {
    $init = $root . '/folders/bootstrap/init/init.php';
}
if (!is_file($init)) {
    fwrite(STDERR, "Missing init.php. Copy folders/bootstrap/init/init.php to bootstrap/init/init.php and set SMTP.\n");
    exit(1);
}

$initSource = file_get_contents($init);
if ($initSource === false) {
    fwrite(STDERR, "Could not read {$init}\n");
    exit(1);
}
foreach (['smtpHost', 'smtpSecure', 'smtpUser', 'smtpPass', 'smtpFrom'] as $name) {
    if (preg_match("/define\s*\(\s*['\"]{$name}['\"]\s*,\s*['\"]([^'\"]*)['\"]\s*\)/", $initSource, $m)) {
        define($name, $m[1]);
    }
}
if (preg_match("/define\s*\(\s*['\"]smtpPort['\"]\s*,\s*(\d+)\s*\)/", $initSource, $m)) {
    define('smtpPort', (int) $m[1]);
}

require_once $root . '/vendor/autoload.php';
require_once $root . '/helpers/mail_helper.php';

$to = $argv[1] ?? (defined('smtpUser') ? smtpUser : '');
if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php scripts/test_smtp.php recipient@example.com\n");
    exit(1);
}

echo "Host: " . (defined('smtpHost') ? smtpHost : '(not set)') . "\n";
echo "Port: " . (defined('smtpPort') ? smtpPort : '587') . "\n";
echo "User: " . (defined('smtpUser') ? smtpUser : '(not set)') . "\n";
echo "Secure: " . (defined('smtpSecure') ? smtpSecure : '(auto)') . "\n";
echo "From: " . vendorSmtpFromEmail() . "\n";
echo "To: {$to}\n\n";

try {
    $mail = createVendorMailer(30);
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = static function ($str, $level): void {
        echo "[{$level}] {$str}\n";
    };
    $mail->setFrom(vendorSmtpFromEmail(), 'SMTP Test');
    $mail->addAddress($to);
    $mail->Subject = 'Vendor portal SMTP test';
    $mail->Body = 'If you received this, SMTP is working. Sent at ' . date('c');
    $mail->send();
    echo "\nOK: Message sent.\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "\nFAILED: " . $e->getMessage() . "\n");
    if (isset($mail) && !empty($mail->ErrorInfo)) {
        fwrite(STDERR, "PHPMailer: " . $mail->ErrorInfo . "\n");
    }
    exit(1);
}
