# Fetch Vouchers API Integration Document

## Overview
The **Fetch Vouchers** API retrieves a paginated list of invoices (vouchers) from the system. It supports date-range filtering and returns the data perfectly formatted for integration into accounting and ERP software like Busy.

---

## Endpoint Details

**URL:**  
`https://seller.exoticindia.com/index.php?page=invoices&action=api_fetch_vouchers`

**Method:**  
`GET` or `POST`

---

## Authentication

All requests to this endpoint must be authenticated using an API token.
You can provide the token in one of two ways:

1. **Authorization Header (Recommended):**
   ```http
   Authorization: Bearer YOUR_API_TOKEN
   ```
2. **Query Parameter / Form Data:**
   ```http
   ?token=YOUR_API_TOKEN
   ```

---

## Request Parameters

The endpoint accepts the following parameters (can be sent as URL query string in `GET` or form-data/JSON in `POST`):

| Parameter    | Type   | Required | Default | Description |
|--------------|--------|----------|---------|-------------|
| `start_date` | String | No       | `null`  | The start date to filter invoices. Format: `YYYY-MM-DD`. |
| `end_date`   | String | No       | `null`  | The end date to filter invoices. Format: `YYYY-MM-DD`. |
| `date`       | String | No       | `null`  | A single date to fetch invoices for. Overrides `start_date` and `end_date` if they are not provided. Format: `YYYY-MM-DD`. |
| `page_no`    | Int    | No       | `1`     | The page number to fetch for paginated results. |
| `limit`      | Int    | No       | `50`    | The number of records to return per page. Max allowed is 1000. |

> [!NOTE]
> If no dates are provided, the API will return all historical invoices chronologically (paginated).

---

## Response Structure

The response is returned in JSON format. A successful request returns a `200 OK` status with the following structure:

### Success Response Example

```json
{
  "company": "MAIN_COMPANY",
  "totalVouchers": 1,
  "vouchers": [
    {
      "Series Name": "Main Company",
      "VchDate": "16-Mar-2026",
      "VchNo.": "INV-20260316-1234",
      "Sales Type": "Sales",
      "Party Name": "John Doe",
      "GSTIN": "22AAAAA0000A1Z5",
      "Material Centre": "Main Location",
      "Narration": "",
      "Shipping Details": {
        "Party Name": "John Doe",
        "Address": "123 Main St",
        "PinCode": "110001",
        "State": "Delhi",
        "Country": "INDIA",
        "Email ID": "johndoe@example.com",
        "Mobile No.": "9876543210",
        "GSTIN": "22AAAAA0000A1Z5"
      },
      "Item Details": [
        {
          "Item Name": "Cotton Kurta",
          "HSN Code": "6211",
          "Qty": 2,
          "Unit": "PCS",
          "MRP": 1500.0,
          "Sales Price": 1500.0,
          "Amount": 3000.0,
          "GST Rate": 5.0,
          "Discount": 0.0
        }
      ],
      "Bill Sundry Details": [
        {
          "Bill Sundry Name": "GST",
          "Percentage": 0,
          "Amount": 150.0
        },
        {
          "Bill Sundry Name": "Discount",
          "Percentage": 0,
          "Amount": -100.0
        }
      ]
    }
  ],
  "pagination": {
    "total_records": 100,
    "total_pages": 2,
    "current_page": 1,
    "limit": 50
  }
}
```

### Response Field Descriptions

| Field | Description |
|-------|-------------|
| **`company`** | Identifies the company data scope. |
| **`totalVouchers`** | The number of vouchers included in the current page/response array. |
| **`vouchers`** | Array of invoice objects perfectly formatted for ERP ingestion. |
| **`pagination`** | Metadata to help navigate through large datasets. Includes `total_records`, `total_pages`, `current_page`, and `limit`. |

#### Inside `vouchers` array:
- `Shipping Details`: Contains the comprehensive address and contact mapping for the customer.
- `Item Details`: Array containing all line-items purchased. Calculations (`Amount`) reflect actual paid totals (`Qty * Sales Price`).
- `Bill Sundry Details`: Contains invoice-level additions and deductions, primarily taxes (`GST`) and discounts (`Discount`).

---

## Error Responses

The API uses standard HTTP status codes for errors.

### 401 Unauthorized
Returned when the API token is missing, invalid, or expired.
```json
{
  "success": false,
  "message": "Unauthorized: Invalid or missing API token."
}
```

### 400 Bad Request
Returned when provided dates do not match the expected `YYYY-MM-DD` format.
```json
{
  "success": false,
  "message": "Invalid date parameter. Required format: YYYY-MM-DD"
}
```

### 500 Internal Server Error
Returned when the server encounters an issue while processing the request (e.g., database connectivity).
```json
{
  "success": false,
  "message": "Error fetching vouchers: [Detailed Error Message]"
}
```

---

## Implementation Example (cURL)

```bash
curl --location --request GET 'https://seller.exoticindia.com/index.php?page=invoices&action=api_fetch_vouchers&start_date=2026-03-01&end_date=2026-03-15&page_no=1&limit=50' \
--header 'Authorization: Bearer YOUR_API_TOKEN' \
--header 'Accept: application/json'
```
