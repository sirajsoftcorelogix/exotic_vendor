# Aramex sandbox (official TEST credentials)

Use this account in **Courier Accounts** (`?page=courier_accounts`) with **Environment = sandbox** for integration testing before switching to production accounts `60524328` / `60525897`.

**API base URL (confirmed):** `https://ws.aramex.net/ShippingAPI.V2`

| Aramex API field | Our `credentials_json` key |
|------------------|------------------------------|
| UserName | `username` |
| Password | `password` |
| Version | `version` |
| AccountNumber | `account_number` |
| AccountPin | `account_pin` |
| AccountEntity | `account_entity` |
| AccountCountryCode | `account_country_code` |
| Source | `client_source` |

## Paste into Courier Accounts → credentials JSON

Set **account_code** to `60531487-SANDBOX`, **environment** to `sandbox`, then paste:

```json
{
  "username": "test.api@aramex.com",
  "password": "Aramex@12345",
  "version": "v1.0",
  "account_number": "60531487",
  "account_pin": "654654",
  "account_entity": "BOM",
  "account_country_code": "IN",
  "client_source": 24,
  "environment": "sandbox",
  "sandbox_api_base_url": "https://ws.aramex.net/ShippingAPI.V2",
  "sandbox_shipping_wsdl": "https://ws.aramex.net/ShippingAPI.V2/Shipping/Service_1_0.svc?wsdl",
  "sandbox_tracking_wsdl": "https://ws.aramex.net/ShippingAPI.V2/Tracking/Service_1_0.svc?wsdl",
  "production_api_base_url": "https://ws.aramex.net/ShippingAPI.V2",
  "production_shipping_wsdl": "https://ws.aramex.net/ShippingAPI.V2/Shipping/Service_1_0.svc?wsdl",
  "production_tracking_wsdl": "https://ws.aramex.net/ShippingAPI.V2/Tracking/Service_1_0.svc?wsdl",
  "rating_currency": "USD",
  "default_product_group": "EXP",
  "default_product_type": "PPX",
  "label_info": {
    "report_id": 9729,
    "report_type": "URL"
  },
  "shipper_gstin": "07AADCE1400C1ZJ",
  "exporter_type": "UT",
  "shipper": {
    "company_name": "Exotic India Art Pvt Ltd",
    "full_name": "Ritvik",
    "phone": "9810028401",
    "email": "vipin@exoticindia.com",
    "line1": "A-16/1, Wazirpur Industrial Area",
    "line2": "",
    "city": "Delhi",
    "postcode": "110052",
    "state": "",
    "country_code": "IN"
  }
}
```

## Test from CLI

After saving the account row, note its `id` and run:

```bash
php test_armex.php <courier_account_id>
```

## Production vs sandbox

| | Sandbox (test) | Production (Exotic India) |
|--|----------------|---------------------------|
| Username | `test.api@aramex.com` | From Aramex onboarding |
| Account | `60531487` | `60524328`, `60525897` |
| Entity | `BOM` | `DEL` |
| PIN | `654654` | Per account (store in DB only) |

**Do not use sandbox credentials in production.** Create separate courier account rows per live Aramex account number.
