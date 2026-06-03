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

function createVendorMailer(int $timeoutSeconds = 20): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = defined('smtpHost') ? smtpHost : 'glacier.mxrouting.net';
    $mail->SMTPAuth = true;
    $mail->Username = defined('smtpUser') ? smtpUser : 'vendoradmin@exoticindia.com';
    $mail->Password = defined('smtpPass') ? smtpPass : '';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = defined('smtpPort') ? (int) smtpPort : 587;
    $mail->Timeout = max(5, $timeoutSeconds);
    $mail->CharSet = PHPMailer::CHARSET_UTF8;

    return $mail;
}

/**
 * @return array{success: bool, message: string}
 */
function sendVendorOtpEmail(string $toEmail, string $otp, string $subject, string $templateFile): array
{
    try {
        $mail = createVendorMailer(20);
        $fromEmail = defined('smtpUser') ? smtpUser : 'vendoradmin@exoticindia.com';
        $mail->setFrom($fromEmail, 'Exotic India Vendor Portal');
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
        if ($info === '') {
            $info = 'Could not connect to mail server. Please try again or contact support.';
        }

        return ['success' => false, 'message' => 'Mailer error: ' . $info];
    } catch (Throwable $e) {
        error_log('Vendor OTP mail failed: ' . $e->getMessage());

        return ['success' => false, 'message' => 'Could not send OTP email. Please try again.'];
    }
}
