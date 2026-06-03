<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Clean output buffers and return JSON (avoids broken JSON from ob_start / warnings).
 */
function vendorJsonResponse(array $payload, int $httpCode = 200): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload);
    exit;
}

function vendorMailTemplatePath(string $filename): string
{
    return dirname(__DIR__) . '/templates/' . ltrim($filename, '/');
}

function loadVendorMailTemplate(string $filename): string
{
    $path = vendorMailTemplatePath($filename);
    if (!is_readable($path)) {
        throw new RuntimeException('Email template not found: ' . $filename);
    }
    $html = file_get_contents($path);
    if ($html === false) {
        throw new RuntimeException('Could not read email template: ' . $filename);
    }

    return $html;
}

/**
 * Apply SMTP settings from bootstrap/init/init.php (smtpHost, smtpPort, smtpUser, smtpPass, optional smtpSecure).
 */
function vendorConfigureSmtp(PHPMailer $mail): void
{
    if (!defined('smtpHost') || smtpHost === '' || !defined('smtpUser') || smtpUser === '') {
        throw new RuntimeException('SMTP is not configured. Set smtpHost and smtpUser in bootstrap/init/init.php.');
    }
    if (!defined('smtpPass') || smtpPass === '') {
        throw new RuntimeException('SMTP password is empty. Set smtpPass in bootstrap/init/init.php.');
    }

    $mail->isSMTP();
    $mail->Host = smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = smtpUser;
    $mail->Password = smtpPass;
    $port = defined('smtpPort') ? (int) smtpPort : 587;
    $mail->Port = $port;

    if (defined('smtpSecure')) {
        $secure = strtolower(trim((string) smtpSecure));
        if ($secure === 'tls' || $secure === 'starttls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($secure === 'ssl' || $secure === 'smtps') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        }
    } else {
        $mail->SMTPSecure = $port === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    }
}

function vendorSmtpFromEmail(): string
{
    if (defined('smtpFrom') && smtpFrom !== '') {
        return smtpFrom;
    }

    return smtpUser;
}

function createVendorMailer(int $timeoutSeconds = 20): PHPMailer
{
    $mail = new PHPMailer(true);
    vendorConfigureSmtp($mail);
    $mail->Timeout = max(5, $timeoutSeconds);
    $mail->CharSet = PHPMailer::CHARSET_UTF8;

    return $mail;
}

/**
 * Plain-language message for end users; technical details stay in server logs.
 */
function vendorFriendlyMailErrorMessage(string $technical = ''): string
{
    $lower = strtolower($technical);

    if ($technical === '') {
        return 'We could not send your email right now. Please try again in a few minutes.';
    }

    if (
        str_contains($lower, 'connect')
        || str_contains($lower, 'timeout')
        || str_contains($lower, 'timed out')
        || str_contains($lower, 'unreachable')
        || preg_match('/smtp code:\s*110/i', $technical)
    ) {
        return 'We could not send your email right now. Please try again in a few minutes.';
    }

    if (
        str_contains($lower, 'authenticate')
        || str_contains($lower, 'authentication')
        || str_contains($lower, 'credentials')
        || preg_match('/smtp code:\s*53[45]/i', $technical)
    ) {
        return 'Email is temporarily unavailable. Please contact support if this continues.';
    }

    if (
        (str_contains($lower, 'invalid') && (str_contains($lower, 'address') || str_contains($lower, 'recipient')))
        || str_contains($lower, 'not a valid')
    ) {
        return 'That email address could not be used. Please check it and try again.';
    }

    return 'We could not send your OTP email. Please try again or contact support.';
}

/**
 * @return array{success: bool, message: string, smtp_error?: string}
 */
function sendVendorOtpEmail(string $toEmail, string $otp, string $subject, string $templateFile): array
{
    try {
        $mail = createVendorMailer(20);
        $mail->setFrom(vendorSmtpFromEmail(), 'Exotic India Vendor Portal');
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = $subject;

        $htmlBody = loadVendorMailTemplate($templateFile);
        $htmlBody = str_replace('{{OTP_CODE}}', $otp, $htmlBody);
        $htmlBody = str_replace('{{CURRENT_YEAR}}', date('Y'), $htmlBody);
        $mail->Body = $htmlBody;
        $mail->AltBody = 'Your OTP is: ' . $otp;

        $mail->send();

        return ['success' => true, 'message' => 'OTP sent to your email.'];
    } catch (MailException $e) {
        $info = isset($mail) ? trim((string) ($mail->ErrorInfo ?? '')) : trim($e->getMessage());
        error_log('Vendor OTP mail failed: ' . $e->getMessage() . ($info !== '' ? ' | ' . $info : ''));

        $technical = $info !== '' ? $info : $e->getMessage();

        return [
            'success' => false,
            'message' => vendorFriendlyMailErrorMessage($technical),
            'smtp_error' => $technical,
        ];
    } catch (Throwable $e) {
        error_log('Vendor OTP mail failed: ' . $e->getMessage());
        $technical = trim($e->getMessage());

        return [
            'success' => false,
            'message' => 'Could not send OTP email. Please try again.',
            'smtp_error' => $technical !== '' ? $technical : 'Unknown mail error.',
        ];
    }
}
