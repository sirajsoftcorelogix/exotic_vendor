# Busy XML Integration - Documentation

## Overview

This document describes how to integrate with **Busy** (accounting software) by generating and downloading invoices in XML format compatible with Busy's import systems.

## Features

- **Secure Token-Based Downloads**: All XML downloads require a valid, time-limited token
- **Invoice Line Items**: Full support for invoice line items with HSN/SAC codes
- **Tax Support**: Includes SGST, CGST, IGST tax entries
- **Backward Compatible**: Supports both current and legacy invoice data structures
- **24-Hour Token Expiry**: Tokens automatically expire after 24 hours for security

---

## API Endpoints

### 1. Generate Single Invoice Busy XML Download URL

**Endpoint**: `GET|POST /index.php?page=invoices&action=generate_busy_xml_url`

**Purpose**: Generate a secure download URL for a single invoice in Busy XML format

**Request Parameters**:
- `invoice_id` (int, required): The invoice ID to generate URL for

**Request Examples**:

```bash
# GET request
curl "http://localhost/exotic_vendor/index.php?page=invoices&action=generate_busy_xml_url&invoice_id=42"

# POST request with JSON
curl -X POST "http://localhost/exotic_vendor/index.php?page=invoices&action=generate_busy_xml_url" \
  -H "Content-Type: application/json" \
  -d '{"invoice_id": 42}'
```

**Response (Success)**:

```json
{
  "success": true,
  "message": "Busy XML download URL generated",
  "download_url": "http://localhost/exotic_vendor/download.php?invoice_id=42&token=BASE64_ENCODED_TOKEN",
  "invoice_id": 42,
  "invoice_number": "INV-000042",
  "token_expires": "2025-03-26 10:30:45"
}
```

**Status Codes**:
- `200 OK`: URL generated successfully
- `400 Bad Request`: Missing or invalid invoice_id
- `404 Not Found`: Invoice does not exist
- `500 Internal Error`: Server error

---

### 2. Generate Batch (Date-Based) Busy XML Download URL

**Endpoint**: `GET|POST /index.php?page=invoices&action=generate_busy_xml_batch_url`

**Purpose**: Generate a secure download URL for all invoices on a specific date

**Request Parameters**:
- `date` (string, required): Date in YYYY-MM-DD format
- `format` (string, optional): Download format - 'zip' (default) or 'consolidated'
  - `zip`: Returns a ZIP file containing individual XML files for each invoice
  - `consolidated`: Returns a single XML file with all invoices in separate vouchers

**Request Examples**:

```bash
# Get all invoices for a date as ZIP
curl "http://localhost/exotic_vendor/index.php?page=invoices&action=generate_busy_xml_batch_url&date=2025-03-24"

# Get all invoices as consolidated XML
curl "http://localhost/exotic_vendor/index.php?page=invoices&action=generate_busy_xml_batch_url&date=2025-03-24&format=consolidated"

# POST request
curl -X POST "http://localhost/exotic_vendor/index.php?page=invoices&action=generate_busy_xml_batch_url" \
  -H "Content-Type: application/json" \
  -d '{"date": "2025-03-24", "format": "zip"}'
```

**Response (Success)**:

```json
{
  "success": true,
  "message": "Batch download URL generated",
  "download_url": "http://localhost/exotic_vendor/download.php?date=2025-03-24&format=zip&token=BASE64_TOKEN",
  "date": "2025-03-24",
  "format": "zip",
  "invoice_count": 5,
  "token_expires": "2025-03-26 10:30:45"
}
```

**Response (Error - No invoices)**:

```json
{
  "success": false,
  "message": "No invoices found for date: 2025-03-24"
}
```

**Status Codes**:
- `200 OK`: URL generated successfully
- `400 Bad Request`: Invalid date format
- `404 Not Found`: No invoices for that date
- `500 Internal Error`: Server error

---

### 3. Download Single Invoice as Busy XML

**Endpoint**: `GET /download.php?invoice_id=<id>&token=<token>`

**Purpose**: Download a single invoice as Busy XML file

**Parameters**:
- `invoice_id` (int, required): Invoice ID
- `token` (string, required): Token from generate URL endpoint

**Usage**:

```bash
curl "http://localhost/exotic_vendor/download.php?invoice_id=42&token=BASE64_ENCODED_TOKEN" \
  -o invoice-42.xml
```

**Status Codes**:
- `200 OK`: XML file downloaded
- `400 Bad Request`: Missing parameters
- `403 Unauthorized`: Invalid or expired token
- `404 Not Found`: Invoice not found
- `500 Internal Error`: Generation error

---

### 4. Download Batch Invoices (by Date) as XML

**Endpoint**: `GET /download.php?date=<YYYY-MM-DD>&format=<zip|consolidated>&token=<token>`

**Purpose**: Download all invoices for a date as ZIP archive or consolidated XML file

**Parameters**:
- `date` (string, required): Date in YYYY-MM-DD format
- `format` (string, optional): 'zip' (default) or 'consolidated'
- `token` (string, required): Token from batch generate URL endpoint

**Usage**:

```bash
# Download as ZIP
curl "http://localhost/exotic_vendor/download.php?date=2025-03-24&format=zip&token=TOKEN" \
  -o invoices_2025-03-24.zip

# Download as consolidated XML
curl "http://localhost/exotic_vendor/download.php?date=2025-03-24&format=consolidated&token=TOKEN" \
  -o invoices_2025-03-24_bulk.xml
```

**Status Codes**:
- `200 OK`: File downloaded (ZIP or XML)
- `400 Bad Request`: Invalid date or missing token
- `403 Unauthorized`: Invalid token
- `404 Not Found`: No invoices for date
- `500 Internal Error`: Generation error

---

## Download Formats

### ZIP Format (Default)

When downloading batch invoices as ZIP:
- Each invoice generates an individual XML file
- Named: `{INVOICE_NUMBER}.xml`
- Files packaged in: `invoices_{DATE}.zip`

**Contents Example**:
```
invoices_2025-03-24.zip
├── INV-000042.xml
├── INV-000043.xml
├── INV-000044.xml
└── INV-000045.xml
```

### Consolidated Format

Single XML file with multiple vouchers:
- All invoices in one file
- Named: `invoices_{DATE}.xml`
- Multiple `<VOUCHER>` elements under `<VOUCHERS>`

**Contents Example**:
```xml
<?xml version="1.0"?>
<BUSYXMLDATA>
  <COMPANY>EXOTIC INDIA</COMPANY>
  <VOUCHERTYPE>Sales</VOUCHERTYPE>
  <VOUCHERS>
    <VOUCHER><!-- Invoice 1 --></VOUCHER>
    <VOUCHER><!-- Invoice 2 --></VOUCHER>
    <VOUCHER><!-- Invoice 3 --></VOUCHER>
  </VOUCHERS>
</BUSYXMLDATA>
```

---

## XML Structure

### Example Output

```xml
<?xml version="1.0"?>
<BUSYXMLDATA>
  <COMPANY>EXOTIC INDIA</COMPANY>
  <VOUCHERTYPE>Sales</VOUCHERTYPE>
  <VOUCHERS>
    <VOUCHER>
      <DATE>24/03/2025</DATE>
      <VOUCHERNO>INV-000042</VOUCHERNO>
      <PARTY>John Doe</PARTY>
      <NARRATION></NARRATION>
      <LEDGERENTRIES>
        <ENTRY>
          <LEDGER>Customers</LEDGER>
          <AMOUNT>1500.00</AMOUNT>
        </ENTRY>
        <ENTRY>
          <LEDGER>Product X</LEDGER>
          <AMOUNT>-1000.00</AMOUNT>
          <QUANTITY>2</QUANTITY>
          <HSNSAC>27082000</HSNSAC>
        </ENTRY>
        <ENTRY>
          <LEDGER>SGST</LEDGER>
          <AMOUNT>-250.00</AMOUNT>
        </ENTRY>
        <ENTRY>
          <LEDGER>CGST</LEDGER>
          <AMOUNT>-250.00</AMOUNT>
        </ENTRY>
      </LEDGERENTRIES>
    </VOUCHER>
  </VOUCHERS>
</BUSYXMLDATA>
```

### XML Elements

| Element | Type | Description |
|---------|------|-------------|
| `COMPANY` | text | Company name |
| `VOUCHERTYPE` | text | Always "Sales" for invoices |
| `DATE` | date | Invoice date (dd/mm/yyyy format) |
| `VOUCHERNO` | text | Invoice number |
| `PARTY` | text | Customer name |
| `NARRATION` | text | Notes/description |
| `ENTRY/LEDGER` | text | Ledger account name |
| `ENTRY/AMOUNT` | decimal | Amount (debit positive, credit negative) |
| `ENTRY/QUANTITY` | integer | Item quantity (optional) |
| `ENTRY/HSNSAC` | text | HSN/SAC code (optional) |

---

## Token Security

### How Tokens Work

1. **Token Generation**: When you call the URL generation endpoint:
   - A base64-encoded token is created containing:
     - Invoice ID
     - User ID
     - Timestamp
     - HMAC-SHA256 hash (prevents tampering)

2. **Token Format** (decoded):
   ```
   {INVOICE_ID}:{USER_ID}:{TIMESTAMP}:{HASH}
   ```

3. **Token Validation** (on download):
   - Invoice ID matches
   - Timestamp is within 24 hours
   - HMAC hash is valid (prevents tampering)
   - If any check fails, download is denied

### Security Features

- **Time-Limited**: Tokens expire after 24 hours
- **Tamper-Proof**: HMAC-SHA256 verification
- **User-Specific**: Each token includes the generating user ID
- **Invoice-Specific**: Token is bound to a specific invoice

---

## Integration Examples

### JavaScript (Frontend)

```javascript
// Generate download URL
async function generateBusyXmlUrl(invoiceId) {
  const response = await fetch(
    `index.php?page=invoices&action=generate_busy_xml_url&invoice_id=${invoiceId}`
  );
  const data = await response.json();
  
  if (data.success) {
    console.log('Download URL:', data.download_url);
    console.log('Expires:', data.token_expires);
    return data.download_url;
  } else {
    console.error('Error:', data.message);
  }
}

// Get download link for UI
async function getDownloadLink(invoiceId) {
  const url = await generateBusyXmlUrl(invoiceId);
  
  // Create or update download button
  const link = document.createElement('a');
  link.href = url;
  link.download = true;
  link.textContent = 'Download Busy XML';
  link.className = 'btn btn-primary';
  
  return link;
}

// Add download button to invoice view
document.addEventListener('DOMContentLoaded', async () => {
  const invoiceId = new URLSearchParams(window.location.search).get('id');
  if (invoiceId) {
    const downloadLink = await getDownloadLink(invoiceId);
    document.getElementById('action-buttons').appendChild(downloadLink);
  }
});
```

### PHP (Backend)

```php
<?php
// Get download URL for an invoice
function getBusyXmlDownloadUrl($invoiceId) {
    $ch = curl_init();
    $url = 'http://localhost/exotic_vendor/index.php?page=invoices&action=generate_busy_xml_url&invoice_id=' . $invoiceId;
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if ($data['success']) {
            return $data['download_url'];
        }
    }
    
    return null;
}

// Download and save XML
function downloadBusyXml($invoiceId) {
    $url = getBusyXmlDownloadUrl($invoiceId);
    
    if ($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $xmlContent = curl_exec($ch);
        curl_close($ch);
        
        $filename = 'invoice_' . $invoiceId . '_busy.xml';
        file_put_contents($filename, $xmlContent);
        
        return $filename;
    }
    
    return null;
}

// Usage
$downloadUrl = getBusyXmlDownloadUrl(42);
echo "Download URL: " . $downloadUrl;
?>
```

### cURL Commands

```bash
# Generate URL
TOKEN_RESPONSE=$(curl -s "http://localhost/exotic_vendor/index.php?page=invoices&action=generate_busy_xml_url&invoice_id=42")
echo "$TOKEN_RESPONSE" | jq .

# Extract download URL
DOWNLOAD_URL=$(echo "$TOKEN_RESPONSE" | jq -r '.download_url')

# Download XML
curl "$DOWNLOAD_URL" -o invoice_42.xml

# View XML content
cat invoice_42.xml
```

---

## Common Issues & Troubleshooting

### Issue: "Unauthorized: Invalid or expired token"

**Causes**:
- Token expired (older than 24 hours)
- Token was tampered with
- Invoice ID doesn't match

**Solution**:
- Generate a new token using the URL generation endpoint
- Ensure you're using the exact URL provided

### Issue: "Invoice not found"

**Causes**:
- Invoice ID doesn't exist
- Invoice was deleted
- Invalid invoice ID

**Solution**:
- Verify the invoice ID is correct
- Check the invoices list to confirm invoice exists

### Issue: "Bad request: Missing parameters"

**Causes**:
- URL missing `invoice_id` parameter
- URL missing `token` parameter

**Solution**:
- Always use the URL generated by the generate endpoint
- Don't manually construct download URLs

### Issue: XML import in Busy fails

**Causes**:
- Incorrect date format
- Invalid ledger names
- Missing required fields
- Encoding issues (should be UTF-8)

**Solution**:
- Verify company name matches Busy setup
- Ensure all ledger names exist in your Busy company
- Check that amounts balance correctly
- Ensure UTF-8 encoding is correct

---

## Database Schema

The system uses the following key tables:

**vp_invoices** (invoice headers)
- `id` - Invoice ID
- `invoice_number` - Invoice number
- `invoice_date` - Date
- `customer_id` - Customer reference
- `total_amount` - Total amount
- `status` - Invoice status

**vp_invoice_items** (line items)
- `id` - Item ID
- `invoice_id` - Foreign key to invoice
- `item_name` - Item description
- `item_code` - Item code
- `hsn` - HSN/SAC code
- `quantity` - Quantity
- `unit_price` - Unit price
- `sgst` - SGST amount
- `cgst` - CGST amount
- `igst` - IGST amount
- `line_total` - Line total

---

## Configuration

### Token Secret

The token secret is configured in `config.php`:

```php
'token_secret' => 'a1b2c3d4e5f678901234567890abcdef'
```

**Important**: Change this to a unique, random string in production!

### Token Expiry

Default token expiry is 24 hours (86400 seconds). To change:

Edit [InvoicesController.php](InvoicesController.php#L1010):
```php
// Change 86400 to desired seconds
if ((time() - (int)$tokenTimestamp) > 86400) {
    return false;
}
```

---

## Support & Further Integration

For additional integration support:
1. Review the BusyXmlGenerator class in `generate-xml.php`
2. Extend the XML schema by modifying lineItem entries
3. Add custom ledger mapping logic as needed
4. Contact system administrator for advanced integration

---

## Version History

- **v1.0** (March 2025): Initial Busy XML integration
  - Secure token-based downloads
  - Support for invoice line items
  - Tax support (SGST/CGST/IGST)
  - API endpoints for URL generation and download
