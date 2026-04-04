# Alankit IRN API Integration Guide

## Overview
The system now automatically generates Alankit IRN (Invoice Registration Number) for international invoices (non-INR currency).

## Configuration

### Step 1: Add API Credentials to `.env` or `config.php`

Add the following configuration variables to your `.env` file or define them in `config.php`:

```php
// Alankit IRN API Configuration
define('ALANKIT_USERNAME', 'your_alankit_username');
define('ALANKIT_PASSWORD', 'your_alankit_password');
define('ALANKIT_API_KEY', 'your_alankit_api_key');
define('ALANKIT_SANDBOX', true); // Set to false for production
```

### Step 2: Obtain Alankit Credentials

1. Visit: https://www.alankitconsulting.com (or contact Alankit support)
2. Register for API access
3. Get your API credentials:
   - Username
   - Password
   - API Key
4. Note: Use sandbox credentials for testing, production credentials for live

## How It Works

### Automatic IRN Generation
When an international invoice is created (non-INR currency):

1. Invoice is created in the system
2. International invoice details are saved
3. **AlankitIrnClient** automatically generates IRN via Alankit API
4. IRN, acknowledgment number, QR code, and signed invoice are stored in the database

### File Structure
- **AlankitIrnClient.php**: Alankit API client class
  - Handles authentication
  - Manages IRN generation
  - Supports IRN cancellation and status checking

### API Endpoints Used
- Authentication: `/api/auth/login`
- IRN Generation: `/api/irn/generate`
- IRN Cancellation: `/api/irn/cancel`
- IRN Status: `/api/irn/status/{irn}`

## Database Fields Updated

When IRN is successfully generated, the following fields in `vp_invoices_international` are updated:

- `irn` - The generated Invoice Registration Number
- `ack_number` - Acknowledgment Number from Alankit
- `ack_date` - Acknowledgment Date
- `signed_invoice` - Digitally signed invoice (if provided by API)
- `qrcode_string` - QR code string for the invoice
- `irn_status` - Status indicator (e.g., 'generated')

## Error Handling

Errors are logged to the error log with context:
- Missing API credentials
- Authentication failures
- API communication errors
- Missing invoice/customer data

Check logs if IRN generation fails:
```
error_log("Alankit IRN: [error message]");
```

## Testing

### Step 1: Set Up Sandbox
Use `ALANKIT_SANDBOX = true` in your config for testing

### Step 2: Create Test International Invoice
1. Create an invoice with currency = USD (or any non-INR)
2. The system will automatically attempt IRN generation
3. Check logs for success/failure

### Step 3: Verify IRN
```sql
SELECT irn, ack_number, irn_status FROM vp_invoices_international 
WHERE invoice_id = [invoice_id];
```

## API Response Format

Successful IRN Generation:
```json
{
  "success": true,
  "irn": "12345678901234567890",
  "ack_number": "1234567890",
  "ack_date": "2026-03-30T10:30:00Z",
  "signed_invoice": "base64_encoded_pdf",
  "qr_code": "base64_encoded_qr_code"
}
```

Failed Response:
```json
{
  "success": false,
  "message": "Error description",
  "http_code": 400
}
```

## Troubleshooting

### Issue: "Authentication failed"
- Verify ALANKIT_USERNAME, ALANKIT_PASSWORD, ALANKIT_API_KEY
- Check credentials are correctly added to config
- Ensure API account is active with Alankit

### Issue: "Missing invoice or items"
- Ensure invoice and items are created before IRN generation
- Check invoice items are properly saved in database

### Issue: "Missing customer or firm details"
- Verify firm_details table is populated (ID=1)
- Verify vp_order_info records exist for customer

### Issue: API timeout
- Check network connectivity to Alankit API
- Verify firewall/proxy settings allow external API calls
- Check Alankit API status page

## Manual IRN Generation

To manually trigger IRN generation for an existing invoice:

```php
$invoicesController = new InvoicesController();
$invoicesController->generateAlankitIrnForInvoice(
    $invoiceId, 
    $invoiceNumber, 
    [] // optional invoice data
);
```

## Security Notes

1. Never commit API credentials to version control
2. Use environment variables or secure config files
3. Store credentials in `.env` file (not in version control)
4. Use sandbox for development, production keys for live
5. Log IRN generation attempts for audit trail

## Support

For Alankit API support, contact: support@alankitconsulting.com

For implementation issues, check:
1. Error logs: `log/debug_log.txt`
2. Invoice details: Check vp_invoices_international table
3. API credentials: Verify in config.php
