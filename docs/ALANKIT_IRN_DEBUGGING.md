# Alankit IRN API Debugging Guide

## Overview
The Alankit IRN integration now stores complete request and response payloads in the `vp_invoices_international` table for full audit trail and debugging.

## Stored Data

### vp_invoices_international Table Columns
- `request_payload` (TEXT) - JSON formatted request sent to Alankit API
- `response_payload` (TEXT) - JSON formatted response received from Alankit API

## Debugging Queries

### Check IRN Generation Status
```sql
SELECT 
    invoice_id,
    irn,
    ack_number,
    irn_status,
    created_at,
    updated_at,
    request_payload,
    response_payload
FROM vp_invoices_international
WHERE invoice_id = [INVOICE_ID];
```

### Find Failed IRN Generations
```sql
SELECT 
    invoice_id,
    irn_status,
    response_payload,
    created_at
FROM vp_invoices_international
WHERE irn_status = 'failed'
ORDER BY created_at DESC
LIMIT 10;
```

### View Request Payload (Formatted)
```sql
SELECT 
    invoice_id,
    JSON_EXTRACT(request_payload, '$.invoice_number') as invoice_num,
    JSON_EXTRACT(request_payload, '$.seller_gstin') as seller_gstin,
    JSON_EXTRACT(request_payload, '$.buyer_name') as buyer_name,
    JSON_EXTRACT(request_payload, '$.total_amount') as total_amount,
    request_payload
FROM vp_invoices_international
WHERE invoice_id = [INVOICE_ID];
```

### View Response Payload (Formatted)
```sql
SELECT 
    invoice_id,
    JSON_EXTRACT(response_payload, '$.status') as status,
    JSON_EXTRACT(response_payload, '$.irn') as irn,
    JSON_EXTRACT(response_payload, '$.message') as message,
    JSON_EXTRACT(response_payload, '$.ack_number') as ack_number,
    response_payload
FROM vp_invoices_international
WHERE invoice_id = [INVOICE_ID];
```

### Export Payloads for Analysis
```sql
SELECT 
    invoice_id,
    irn_status,
    request_payload,
    response_payload,
    created_at
FROM vp_invoices_international
WHERE irn_status IN ('generated', 'failed')
ORDER BY created_at DESC;
```

## Payload Structure

### Request Payload Example
```json
{
    "invoice_number": "INV-000001",
    "invoice_date": "2026-03-30",
    "seller_gstin": "07AGAPA5363L002",
    "seller_name": "EXOTIC INDIA ART PVT LTD",
    "seller_address": "A-16/1 WAZIRPUR INDUSTRIAL AREA",
    "seller_city": "NEW DELHI",
    "seller_state": "DELHI",
    "seller_country": "IN",
    "buyer_name": "John Doe",
    "buyer_address": "123 Main Street",
    "buyer_city": "New York",
    "buyer_state": "NY",
    "buyer_country": "US",
    "buyer_pincode": "10001",
    "currency": "USD",
    "line_items": [
        {
            "SlNo": 1,
            "ItemCode": "ITEM-001",
            "ItemName": "Product Name",
            "Qty": 2,
            "Unit": "NOS",
            "UnitPrice": 100.00,
            "Amount": 200.00,
            "HSNCode": "1234",
            "TaxRate": 18,
            "GSTAmount": 36.00,
            "TotalAmount": 236.00
        }
    ],
    "subtotal": 200.00,
    "tax_amount": 36.00,
    "discount_amount": 0.00,
    "total_amount": 236.00
}
```

### Success Response Example
```json
{
    "status": true,
    "irn": "2A1BA95B9CD612EC03FB15XXXX",
    "ack_number": "1234567890123",
    "ack_date": "2026-03-30T12:30:45Z",
    "signed_invoice": "<?xml version=\"1.0\"...>",
    "qr_code": "89504E470D0A1A0A...",
    "response": {...}
}
```

### Error Response Example
```json
{
    "status": false,
    "message": "Invalid GSTIN format",
    "error": "GSTIN validation failed",
    "data": {
        "field": "seller_gstin",
        "issue": "Incorrect format"
    }
}
```

## PHP Debugging Code

### Get Invoice with Payloads
```php
global $invoiceModel;

$invoiceId = 123;
$invoice = $invoiceModel->getInternationalInvoiceByInvoiceId($invoiceId);

if ($invoice) {
    $requestPayload = json_decode($invoice['request_payload'], true);
    $responsePayload = json_decode($invoice['response_payload'], true);
    
    echo "Status: " . $invoice['irn_status'] . "\n";
    echo "Request: " . json_encode($requestPayload, JSON_PRETTY_PRINT) . "\n";
    echo "Response: " . json_encode($responsePayload, JSON_PRETTY_PRINT) . "\n";
}
```

### Check IRN Status in Code
```php
$irn = $invoice['irn'];
$status = $invoice['irn_status'];

if ($status === 'generated' && !empty($irn)) {
    echo "IRN generated successfully: " . $irn;
} elseif ($status === 'failed') {
    $error = json_decode($invoice['response_payload'], true);
    echo "IRN generation failed: " . ($error['message'] ?? 'Unknown error');
}
```

## Error Handling

### Common Error Messages
1. **"Invalid GSTIN format"** - Check seller GSTIN in firm_details table
2. **"Buyer details incomplete"** - Verify customer shipping address data
3. **"Invalid HSN Code"** - Ensure HSN codes in invoice items are valid
4. **"Amount mismatch"** - Verify invoice totals match line item sums
5. **"Duplicate invoice"** - Check if invoice number already exists in Alankit

## Troubleshooting Steps

1. **Enable Debug Logging**
   - Check `log/debug_log.txt` for Alankit IRN log entries
   - Search for "Alankit IRN" prefix

2. **Verify Credentials**
   - Check `config.php` for correct username, password, subscription_key
   - Test authentication separately

3. **Validate Payload**
   - Query request_payload from database
   - Verify all required fields are present and formatted correctly

4. **Check Response**
   - Query response_payload from database
   - Look for error message or validation issues

5. **Test in Sandbox**
   - All Eraahi API calls target sandbox environment
   - Create test international invoice and verify IRN generation

## Audit Trail

The stored payloads provide:
- ✅ Complete request history for each invoice
- ✅ Response validation for debugging
- ✅ Compliance audit trail for international invoices
- ✅ Error investigation without API re-calls
- ✅ API integration testing and verification
