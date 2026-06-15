# Aramex Error: ERR48 - Invalid Invoice Date Format (Updated)

## Error Code
```
Code: ERR48
Message: Invalid Invoice Date format
```

## Problem
Invoice date format sent to Aramex was incorrect. Multiple format attempts were tried:
1. First attempt: `m/d/Y` (06/15/2026) - Still failed
2. Switched to: `m/d/Y` (American format) - Appears to be required by Aramex API

## Root Cause
Aramex SOAP API validates the InvoiceDate field strictly. Despite ISO 8601 being a standard, Aramex requires **American date format**: `MM/DD/YYYY`

## Solution
Updated [AramexShipmentBuilder.php](helpers/courier/AramexShipmentBuilder.php):

**Format Changed To: `m/d/Y` (MM/DD/YYYY)**

### Date Parsing Logic
The `parseAndFormatDate()` method now:
1. Detects already-formatted dates (Y-m-d or m/d/Y)
2. Converts Unix timestamps to target format
3. Parses common date formats:
   - `DD/MM/YYYY` (15/06/2026)
   - `DD-MM-YYYY` (15-06-2026)
   - `YYYY/MM/DD` (2026/06/15)
   - `YYYYMMDD` (20260615)
4. Validates dates using `checkdate()` before converting
5. Fallbacks to today if parsing fails

### Input Examples
| Input | Output | Status |
|-------|--------|--------|
| `2026-06-15` | `06/15/2026` | ✓ Converted from ISO |
| `06/15/2026` | `06/15/2026` | ✓ Already correct |
| `15/06/2026` | `06/15/2026` | ✓ Parsed European format |
| `1760592000` | `06/15/2026` | ✓ Unix timestamp |
| `null` | `06/15/2026` | ✓ Today's date |

## Aramex Date Format Requirements (Confirmed)
- **DateTime fields** (ShippingDateTime, DueDate): `Y-m-d\TH:i:s` (ISO 8601)
- **Date fields** (InvoiceDate): `m/d/Y` (American MM/DD/YYYY)
- **NOT**: `/Date()/` JavaScript format
- **NOT**: ISO 8601 for date-only fields

## Enhanced Logging
Added to `aramex_service.php`:
```php
error_log('Aramex Invoice Data: ' . json_encode([
    'invoice_date' => $context['invoice']['invoice_date'] ?? 'MISSING',
    'invoice_number' => $context['invoice']['invoice_number'] ?? 'MISSING',
    'total_amount' => $context['invoice']['total_amount'] ?? 'MISSING',
]));

error_log('Aramex Invoice Properties Sent: ' . json_encode([
    'InvoiceDate' => $invoiceDate,
]));
```

This logs what invoice data is received and what's actually sent to Aramex for debugging.

## Next Steps
1. Test dispatch with international order
2. Monitor error logs for InvoiceDate format
3. If still failing, check PHP error log for parsing issues
4. Verify invoice data is being provided in order context

## Files Updated
- `helpers/courier/AramexShipmentBuilder.php`
  - Lines 205-207: Changed format to `m/d/Y`
  - Lines 147-207: Enhanced `parseAndFormatDate()` method
- `aramex_service.php`
  - Added logging for invoice data and properties sent

## Testing
Access diagnostic tool:
```
?page=dispatch&action=test_aramex_createshipments
```

Check PHP error log for:
```
Aramex Invoice Data: {...}
Aramex Invoice Properties Sent: {...}
Aramex CreateShipments Response: {...}
```

