# E-Way Bill Integration Guide

## Overview
E-Way Bill (Electronic Way Bill) is required for movement of goods exceeding INR 50,000 in value. Integration is built into the Alankit IRN Client for seamless document generation.

## Endpoint Details
- **Base URL**: `https://developers.eraahi.com`
- **Generate**: `/eInvoiceGateway/eiewb/v1.03/ewaybill` (POST)
- **Cancel**: `/eInvoiceGateway/eiewb/v1.03/ewaybill/cancel` (POST)
- **Fetch**: `/eInvoiceGateway/eiewb/v1.03/ewaybill/{ewayBillNo}` (GET)

## Configuration
Set in `config.php`:
```php
'alankit' => [
    // ... existing credentials ...
    'auto_generate_ewaybill' => true, // Enable auto-generation
    'ewaybill_transport_mode' => 'ROAD', // ROAD, RAIL, AIR, SHIP
    'ewaybill_vehicle_type' => 'REGULAR' // REGULAR (normal goods), ABNORMAL (oversized items)
],
```

## Usage Examples

### 1. Generate E-Way Bill
```php
// Load AlankitIrnClient
require_once 'models/invoice/AlankitIrnClient.php';

// Initialize client
$client = new AlankitIrnClient(
    'EXOT_IND_2026',
    'Alankit@123',
    'AL6x9c9S1b7g8h9S7C',
    'f8c4e2d1a9b3c5e7f9a1b3c5e7f9a1b3'
);

// Prepare E-Way Bill data (must include IRN from generated invoice)
$ewaybillData = [
    'invoice_number' => 'INV-001',
    'invoice_date' => '30/03/2026',
    'irn' => '201AB1234567890123456789012345', // IRN from generated invoice
    
    // Seller details
    'seller_gstin' => '07AGAPA5363L002',
    'seller_name' => 'EXOTIC INDIA ART PVT LTD',
    'seller_address' => 'Business Address',
    'seller_city' => 'New Delhi',
    'seller_state' => 'DL',
    'seller_state_code' => '07',
    'seller_pincode' => '110001',
    
    // Buyer details
    'buyer_gstin' => '18AABCT1234H1Z0',
    'buyer_name' => 'Buyer Company Name',
    'buyer_address' => 'Buyer Address',
    'buyer_city' => 'Chennai',
    'buyer_state' => 'TN',
    'buyer_state_code' => '33',
    'buyer_pincode' => '600001',
    
    // Line items
    'line_items' => [
        [
            'item_name' => 'Product Name',
            'hsn' => '4203',
            'quantity' => 10,
            'unit' => 'NOS',
            'tax_rate' => 5
        ]
    ],
    
    // Amount details
    'subtotal' => 10000,
    'tax_amount' => 500,
    'total_amount' => 10500,
    
    // Transport details
    'transport_mode' => 'ROAD',
    'vehicle_number' => 'DL01AB1234',
    'vehicle_type' => 'REGULAR'
];

// Generate E-Way Bill
$response = $client->generateEWayBill($ewaybillData);

if ($response['status'] === true) {
    echo "E-Way Bill Generated: " . $response['eway_bill_no'];
    // Store in database: $response['eway_bill_no'], $response['eway_bill_date']
} else {
    echo "Error: " . $response['message'];
    error_log("E-Way Bill Error: " . $response['message']);
}
```

### 2. Get E-Way Bill Status
```php
$ewayBillNo = 'EWB201234567890123';

$status = $client->getEWayBillStatus($ewayBillNo);

if ($status['status'] === true) {
    echo "Status: " . $status['data']['EwbStatus'];
    echo "Validity: " . $status['data']['EwbValidityDays'] . " days";
}
```

### 3. Cancel E-Way Bill
```php
$ewayBillNo = 'EWB201234567890123';
$reason = 'Wrong details provided'; // Optional custom reason

$response = $client->cancelEWayBill($ewayBillNo, $reason);

if ($response['status'] === true) {
    echo "E-Way Bill cancelled successfully";
} else {
    echo "Cancellation failed: " . $response['message'];
}
```

## Response Structure

### Success Response
```json
{
    "status": true,
    "eway_bill_no": "EWB201234567890123",
    "eway_bill_date": "30/03/2026",
    "eway_bill_validity": "30",
    "eway_bill_status": "ACTIVE",
    "response": {
        "status": true,
        "data": {
            "EwbNo": "EWB201234567890123",
            "EwbDt": "30/03/2026 10:30:00",
            "EwbValidityDays": 30,
            "EwbStatus": "ACTIVE"
        }
    }
}
```

### Error Response
```json
{
    "status": false,
    "message": "E-Way Bill generation failed",
    "response": {
        "status": false,
        "error": "Invalid IRN provided",
        "data": null
    }
}
```

## Database Schema

### Table: vp_invoices_international (New Fields)
```sql
ALTER TABLE vp_invoices_international ADD COLUMN (
    eway_bill_no VARCHAR(20),
    eway_bill_date DATETIME,
    eway_bill_validity INT,
    eway_bill_status VARCHAR(50),
    eway_bill_request_payload LONGTEXT,
    eway_bill_response_payload LONGTEXT
);
```

## Validation Rules

1. **IRN Required**: Must have valid IRN from invoice generation
2. **GSTIN Format**: 15-character alphanumeric for both seller and buyer
3. **Amount Threshold**: E-Way Bill required for goods value > INR 50,000
4. **Transport Mode**: ROAD, RAIL, AIR, or SHIP
5. **Date Format**: DD/MM/YYYY for all dates
6. **HSN Code**: 4 or 6-digit valid HSN code required

## Error Handling

| Error Code | Message | Solution |
|-----------|---------|----------|
| 400 | Invalid IRN | Ensure IRN is generated first |
| 401 | Authentication failed | Check credentials in config.php |
| 409 | Duplicate request | Wait before retrying same data |
| 422 | Invalid data | Validate field values against schema |
| 500 | Server error | Retry after delay |

## Integration Flow Example

```php
// 1. Generate Invoice
$invoiceId = createInvoice($invoiceData);

// 2. Generate IRN
$irnResult = generateAlankitIrnForInvoice($invoiceId);
$irn = $irnResult['irn'];

// 3. Generate E-Way Bill
$ewaybillData['irn'] = $irn;
$ewaybillResult = $client->generateEWayBill($ewaybillData);

if ($ewaybillResult['status']) {
    // 4. Update invoice with E-Way Bill details
    updateInvoice($invoiceId, [
        'eway_bill_no' => $ewaybillResult['eway_bill_no'],
        'eway_bill_date' => $ewaybillResult['eway_bill_date']
    ]);
} else {
    // Log error and alert
    error_log("E-Way Bill generation failed: " . $ewaybillResult['message']);
}
```

## Troubleshooting

**Q: E-Way Bill generation fails with "Invalid IRN"**
- A: Ensure IRN was successfully generated before creating E-Way Bill
- Check IRN format in response (should be 32+ characters)

**Q: "Invalid GSTIN" error**
- A: Verify GSTIN is 15 characters and alphanumeric
- Check buyer GSTIN is registered for invoice state

**Q: Token expiration during E-Way Bill generation**
- A: ForceRefreshAccessToken is enabled by default
- Token auto-refreshes 10 minutes before expiry

**Q: Goods value shows but E-Way Bill fails**
- A: Some items may lack required HSN codes
- Verify all line items have valid 4 or 6-digit HSN

## Logging & Monitoring

All E-Way Bill operations are logged to `log/debug_log.txt`:
```
Alankit E-Way Bill: Token expiring soon, refreshing proactively
Alankit E-Way Bill Generation Error: Invalid IRN provided
Alankit E-Way Bill Cancellation response for EwbNo EWB123456...
```

Monitor logs for critical errors and track E-Way Bill generation rates.

## References
- [Eraahi E-Way Bill API Docs](https://developers.eraahi.com)
- [GST E-Way Bill Portal](https://ewaybillgst.gov.in)
- [Alankit Support](https://support.alankit.com)
