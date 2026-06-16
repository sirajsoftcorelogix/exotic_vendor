# Aramex CreateShipments API Diagnostics

## Problem
`createResult` returns empty errors array:
```json
{
  "success": false,
  "errors": {}
}
```

## Root Cause Analysis

The Aramex SOAP API is returning `HasErrors: true` but the `Notifications` field is empty or missing error details.

## Diagnostic Steps

### Step 1: Run the CreateShipments Test
Access the detailed API test:
```
http://your-domain/index.php?page=dispatch&action=test_aramex_createshipments
```

This will show:
- Request parameters being sent to Aramex
- Raw SOAP response object
- Parsed errors (if any)
- Full response for debugging

### Step 2: Check PHP Error Log
The improved error handling now logs detailed information:
```bash
tail -f /path/to/php-error.log
```

Look for log entries like:
- `Aramex CreateShipments Request: {...}`
- `Aramex CreateShipments Response: {...}`
- `Aramex CreateShipments Error: {...}`
- `Aramex CreateShipments Exception: ...`

### Step 3: Check Courier API Logs
Query the `courier_api_logs` table for Aramex CreateShipments calls:
```sql
SELECT * FROM courier_api_logs 
WHERE partner_code = 'aramex' 
  AND method = 'create_shipment'
ORDER BY created_at DESC 
LIMIT 10;
```

## Common Aramex Errors

| Error Code | Issue | Solution |
|-----------|-------|----------|
| `MethodNotAllowed` | SOAP method call syntax wrong | Check method signature |
| `InvalidRequest` | Malformed request parameters | Validate address, weight, dimensions |
| `AuthenticationFailed` | Wrong credentials | Verify username, password, account number, PIN |
| `InvalidDestination` | Destination country not supported | Check country code normalization |
| `InvalidWeight` | Weight outside allowed range | Ensure weight > 0 and reasonable |
| `MissingRequiredField` | Required field empty | Check shipper/consignee details |

## Response Format

### Success Response
```json
{
  "success": true,
  "data": {
    "HasErrors": false,
    "Shipments": {
      "ProcessedShipment": {
        "ID": "AWB123456789",
        "ShipmentLabel": {
          "LabelURL": "https://..."
        }
      }
    }
  }
}
```

### Error Response (Improved)
```json
{
  "success": false,
  "errors": [
    {
      "code": "ERROR_CODE",
      "message": "Detailed error message",
      "type": "error"
    }
  ],
  "has_errors": true,
  "raw_response": { ... }
}
```

## Changes Made

### 1. **aramax_service.php**
- Improved `formatResponse()` to extract detailed error messages
- Added error logging to `createInternationalShipment()`
- Captures `Notifications` array from SOAP response

### 2. **DispatchController.php**
- Removed debug `print_array()` calls
- Enhanced error display with detailed error breakdown
- Shows error codes and messages from Aramex

### 3. **test_aramex_createshipments.php** (NEW)
- Step-by-step diagnostic for CreateShipments calls
- Shows request and response objects
- Displays parsed errors
- Lists error codes and messages

## Next Steps

1. **Run diagnostic**: Visit `?page=dispatch&action=test_aramex_createshipments`
2. **Analyze response**: Look for error codes and messages
3. **Check logs**: Review PHP error log for full request/response details
4. **Validate data**: Ensure all required fields are present and valid
5. **Check credentials**: Verify Aramex account credentials are correct

## Files Updated
- ✅ `aramex_service.php` - Enhanced error handling and logging
- ✅ `controllers/DispatchController.php` - Better error display
- ✅ `test_aramex_createshipments.php` - NEW diagnostic tool
- ✅ `index.php` - Added route for diagnostic
