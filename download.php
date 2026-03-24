<?php
/**
 * Busy XML Download Endpoint
 * Secure token-based XML download for invoices
 * 
 * Usage: /download.php?invoice_id=1042&token=BASE64_ENCODED_TOKEN
 */

// Get database configuration
$config = require 'config.php';

// Database connection
$conn = new mysqli(
    $config['db']['host'],
    $config['db']['user'],
    $config['db']['pass'],
    $config['db']['name']
);

if ($conn->connect_error) {
    http_response_code(500);
    exit('Database connection failed');
}

$conn->set_charset($config['db']['charset']);

// Define token secret
if (!defined('TOKEN_SECRET')) {
    define('TOKEN_SECRET', $config['token_secret'] ?? 'default-secret-key');
}

// Load required classes
require_once 'models/invoice/invoice.php';
require_once 'generate-xml.php';

// Get parameters
$invoiceId = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (!$invoiceId || !$token) {
    http_response_code(400);
    exit('Bad request: Missing parameters');
}

// Validate token
if (!validateToken($token, $invoiceId)) {
    http_response_code(403);
    exit('Unauthorized: Invalid or expired token');
}

// Fetch invoice from database
$invoiceModel = new Invoice($conn);
$invoice = $invoiceModel->getInvoiceById($invoiceId);

if (!$invoice) {
    http_response_code(404);
    exit('Invoice not found');
}

// Fetch invoice items
$items = $invoiceModel->getInvoiceItems($invoiceId);

// Calculate tax totals
$invoice['sgst'] = array_sum(array_column($items, 'sgst'));
$invoice['cgst'] = array_sum(array_column($items, 'cgst'));
$invoice['igst'] = array_sum(array_column($items, 'igst'));

// Generate XML
$generator = new BusyXmlGenerator();
$xml = $generator->generate($invoice, $items);

// Output file
header('Content-Type: application/xml; charset=utf-8');
header('Content-Disposition: attachment; filename="' . 
       htmlspecialchars($invoice['invoice_number'] ?? 'invoice') . '_busy.xml"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

echo $xml;
exit;

/**
 * Validate secure token
 * 
 * @param string $token      Base64 encoded token
 * @param int $invoiceId     Invoice ID to validate against
 * @return bool              True if token is valid
 */
function validateToken($token, $invoiceId)
{
    try {
        $secret = defined('TOKEN_SECRET') ? TOKEN_SECRET : 'default-secret-key';
        
        $decoded = base64_decode($token, true);
        if (!$decoded) {
            return false;
        }

        $parts = explode(':', $decoded);
        if (count($parts) !== 4) {
            return false;
        }

        list($tokenInvoiceId, $tokenUserId, $tokenTimestamp, $tokenHash) = $parts;

        // Verify invoice ID matches
        if ((int)$tokenInvoiceId !== $invoiceId) {
            return false;
        }

        // Verify token not expired (24 hours)
        if ((time() - (int)$tokenTimestamp) > 86400) {
            return false;
        }

        // Verify HMAC hash
        $expectedHash = hash_hmac('sha256', $invoiceId . ':' . $tokenUserId . ':' . $tokenTimestamp, $secret);
        if (!hash_equals($expectedHash, $tokenHash)) {
            return false;
        }

        return true;
    } catch (Exception $e) {
        return false;
    }
}