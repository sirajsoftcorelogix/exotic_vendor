# Aramex Integration Fixes Applied

## Problem Identified
The Aramex API was returning empty errors array:
```json
{
  "success": false,
  "errors": {}
}
```

## Root Cause
Two main issues discovered:

### Issue 1: Missing Phone Numbers
The Aramex API requires phone numbers in the consignee contact information:
- `Consignee.Contact.PhoneNumber1` - Phone Number is empty
- `Consignee.Contact.CellPhone` - Cell Phone is empty

Error codes: REQ22, REQ23

### Issue 2: Nested Error Structure
The Aramex response has nested notifications:
```
Shipments.ProcessedShipment.Notifications.Notification = [...]
```

But the parser was only checking top-level `Notifications` array.

## Fixes Applied

### 1. **AramexShipmentBuilder.php** - Added Phone Number Extraction
Created `extractPhone()` method that tries multiple field names:
- `shipping_phone`
- `shipping_mobile`
- `shipping_cell`
- `shipping_telephone`
- `phone`
- `mobile`
- `cell`
- `telephone`

Updated consignee Contact to use this method for both `PhoneNumber1` and `CellPhone`.

**Files Updated:**
- `helpers/courier/AramexShipmentBuilder.php`
  - Lines 107-119: Updated Contact structure to call `self::extractPhone($address)`
  - Lines 125-139: Added `extractPhone()` method

### 2. **aramex_service.php** - Enhanced Error Parsing
Improved `formatResponse()` to:
- Check top-level `HasErrors` flag
- Check nested `Shipments.ProcessedShipment.HasErrors` flag
- Extract errors from both locations

Added `extractNotificationErrors()` method to handle:
- Nested structure: `{Notification: [{...}, {...}]}`
- Direct array: `[{...}, {...}]`
- Both object and array formats

**Files Updated:**
- `aramex_service.php`
  - Lines 230-275: Improved `formatResponse()` method
  - Lines 277-313: Added `extractNotificationErrors()` method

### 3. **test_aramex_createshipments.php** - Updated Test Data
Added phone numbers to test context:
- `shipping_phone: '+1 212 555 1234'`
- `shipping_mobile: '+1 212 555 5678'`
- `shipping_email: 'john@example.com'`

**Files Updated:**
- `test_aramex_createshipments.php`
  - Lines 24-26: Added phone number fields

## Error Response Now Returns
Before:
```json
{
  "success": false,
  "errors": {}
}
```

After:
```json
{
  "success": false,
  "errors": [
    {
      "code": "REQ22",
      "message": "Consignee.Contact.PhoneNumber1 - Phone Number1 is empty",
      "type": "error"
    },
    {
      "code": "REQ23",
      "message": "Consignee.Contact.CellPhone - Cell Phone is empty",
      "type": "error"
    }
  ],
  "has_errors": true,
  "raw_response": {...full response...}
}
```

## How Phone Numbers Flow

1. **DispatchController** builds address from order/customer data
2. **CourierGateway** passes address to **AramexAdapter**
3. **AramexAdapter** calls **AramexShipmentBuilder**
4. **AramexShipmentBuilder** calls `extractPhone()` to find phone number
5. Phone goes into **Shipment Request** → **Consignee.Contact.PhoneNumber1** and **.CellPhone**
6. **Aramex API** accepts the request

## Next Steps

### Immediate:
1. Ensure order records include phone numbers (in customer or shipping address)
2. Test dispatch with international order
3. Verify shipment creation succeeds
4. Extract AWB and label URL

### For Robustness:
1. Add phone number validation in dispatch form
2. Make phone number required for international orders
3. Handle Aramex-specific validation errors
4. Log successful shipments to verify end-to-end flow

## Testing

Run the diagnostic tool:
```
?page=dispatch&action=test_aramex_createshipments
```

This now shows:
- Request structure
- Response structure  
- Actual error codes and messages
- Full raw response for debugging

## Common Issues to Watch For

| Field | Requirement | Current Fallback |
|-------|-------------|-----------------|
| Phone | Required | Tries 8 field names |
| Email | Optional | `shipping_email` or `email` |
| Name | Required | `first_name + last_name` |
| Address | Required | Must have address_line1, city, state, zip |
| Country | Required | ISO 2-letter code |
| Weight | Required | Must be > 0 |

## Performance Impact
Minimal - only added a lookup for phone number fields (no API calls, simple array checks).

## Files Modified
- `helpers/courier/AramexShipmentBuilder.php`
- `aramex_service.php`
- `test_aramex_createshipments.php`

## Rollback Plan
If issues occur, revert these three files to their previous versions. The changes are isolated to error handling and phone number extraction.
