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

        return [
            'success' => false,
            'message' => vendorFriendlyMailErrorMessage($info !== '' ? $info : $e->getMessage()),
        ];
    } catch (Throwable $e) {
        error_log('Vendor OTP mail failed: ' . $e->getMessage());

        return ['success' => false, 'message' => 'Could not send OTP email. Please try again.'];
    }
}
