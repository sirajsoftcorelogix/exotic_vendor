# Shiprocket Courier Serviceability Integration

## Overview
After items are added to an invoice in the bulk dispatch form, the system automatically fetches available couriers from Shiprocket's serviceability API and displays them with pricing, ETD (Estimated Time of Delivery), and ratings.

## API Integration

### Shiprocket Endpoint
```
https://apiv2.shiprocket.in/v1/external/courier/serviceability/
```

### Required Parameters
- **pickup_postcode** (string): Warehouse/pickup location postal code from `firm_details.pin`
- **delivery_postcode** (string): Customer shipping postal code from `vp_orders.shipping_zipcode`
- **length** (float): Box length in inches
- **breadth** (float): Box width in inches  
- **height** (float): Box height in inches
- **weight** (float): Total weight in kg
- **cod** (integer): 0 or 1 - Whether Cash on Delivery is enabled
- **is_return** (integer): 0 or 1 - Whether this is a return shipment
- **qc_check** (integer): 0 or 1 - Whether quality check is required
- **mode** (string, optional): Delivery mode preference

## Implementation Files Modified

### 1. **models/dispatch/dispatch.php**
Added method: `getCourierServiceability()`
```php
public function getCourierServiceability($pickup_postcode, $delivery_postcode, $weight, $length, $breadth, $height, $cod = 0, $is_return = 0, $qc_check = 0, $mode = null)
```
- Makes POST request to Shiprocket API with Bearer token authentication
- Returns array: `['http_code' => 200, 'data' => [...], 'success' => true/false]`

### 2. **controllers/DispatchController.php**
Added method: `getCourierServiceability()`
- **Endpoint**: `?page=dispatch&action=getCourierServiceability`
- **Method**: POST (JSON)
- **Input Parameters**:
  - `order_number` (required)
  - `length`, `breadth`, `height` (required)
  - `weight` (required)
  - `cod` (optional, default 0)

- **Validation**:
  - Verifies order exists in database
  - Validates all dimensions and weight > 0
  - Checks firm_details has pickup postcode configured
  - Verifies order has delivery postcode

- **Response** (JSON):
```json
{
  "success": true,
  "couriers": [
    {
      "id": 1,
      "name": "Shiprocket Express",
      "price": 50.00,
      "etd": "2-3 days",
      "rating": 4.8,
      "estimated_days": 2,
      "full_data": {...}
    }
  ],
  "message": "Couriers fetched successfully"
}
```

### 3. **index.php**
Added routing case:
```php
case 'getCourierServiceability':
    $controller->getCourierServiceability();
    break;
```

### 4. **views/dispatch/bulk_dispatch.php**
Added JavaScript functionality:

#### Function: `fetchCouriersForBox(boxElement)`
- Extracts box dimensions from selected box size option
- Extracts weight from weight input
- Makes POST fetch request to endpoint
- Displays loading state: "⏳ Fetching available couriers..."
- Formats and displays courier results

#### Automatic Triggers:
1. **After adding items** - Calls after `updateBoxTotals()`
2. **Box size change** - Debounced 500ms
3. **Weight change** - Debounced 1000ms  
4. **New box added** - When "+ Add Box" button creates new box

#### Display Format:
```
Available Couriers:

[Courier Name]
┌─────────────────────────────────────────┐
│ Price: ₹ 50.00 │ ETD: 2-3 days │ Rating: 4.8/5 │
└─────────────────────────────────────────┘

[Another Courier]
┌─────────────────────────────────────────┐
│ Price: ₹ 45.00 │ ETD: 3-4 days │ Rating: 4.5/5 │
└─────────────────────────────────────────┘
```

## Data Flow

```
User adds items to box
    ↓
updateBoxTotals() called
    ↓
fetchCouriersForBox(boxElement) triggered
    ↓
Extract: order_number, length, breadth, height, weight
    ↓
POST to ?page=dispatch&action=getCourierServiceability
    ↓
Controller validates input
    ↓
Query firm_details.pin (pickup postcode)
    ↓
Query vp_orders.shipping_zipcode (delivery postcode)
    ↓
Call Dispatch model getCourierServiceability()
    ↓
Make authenticated request to Shiprocket API
    ↓
Parse response, format courier data
    ↓
Return JSON with couriers array
    ↓
JavaScript displays in availableCourierCompanies div
    ↓
User sees available courier options
```

## Example Request

```javascript
fetch('?page=dispatch&action=getCourierServiceability', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify({
        order_number: '12345',
        length: 22,
        breadth: 17,
        height: 5,
        weight: 2.5,
        cod: 0
    })
})
```

## Error Handling

### Validation Errors (400):
- Missing order number
- Invalid dimensions or weight (≤ 0)
- Pickup postcode not configured in firm_details
- Delivery postcode missing in order

### Not Found (404):
- Order doesn't exist in database
- Order missing shipping_zipcode

### API Errors (Shiprocket):
- API returns error response
- Display: "No couriers available for this route"

### Network Errors:
- Fetch fails
- Display: "Error fetching couriers"

## Testing

1. Navigate to Bulk Dispatch page
2. Add an order number
3. Select items from the modal
4. Box will be created and couriers will auto-fetch
5. Verify courier list displays with:
   - Courier names
   - Pricing in ₹ format
   - ETD/Delivery dates
   - Star ratings

## Requirements

- Firm details must have `pin` (postcode) configured (ID=1 in firm_details table)
- Orders must have `shipping_zipcode` populated
- Valid Shiprocket API token must be present in database (`shiprocket_api_tokens` table)
- Active internet connection for Shiprocket API calls

## Notes

- Courier data is fetched in real-time, no caching
- API calls are debounced to prevent excessive requests when changing dimensions
- Bearer token is automatically fetched/refreshed from database
- All HTML output is sanitized using `escapeHtml()` function
- Responsive design adapts for mobile/tablet viewports
