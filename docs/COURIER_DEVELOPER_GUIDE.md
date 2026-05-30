# Courier integration — developer guide

> **Full team docs:** [`COURIER_TEAM_HANDBOOK.md`](./COURIER_TEAM_HANDBOOK.md) (file list, DB schema, coding rules)  
> **AI agent prompt:** [`COURIER_AI_AGENT_PROMPT.md`](./COURIER_AI_AGENT_PROMPT.md) (copy-paste for Cursor/ChatGPT)

Share this with anyone adding a new courier (Delhivery, DHL, etc.). **Follow this structure** so dispatch, UI, and credentials stay consistent and merge conflicts stay low.

---

## Team routing (agreed)

| Market | UI screen | Adapters | Owner |
|--------|-----------|---------|--------|
| **Domestic** (IN → IN) | Bulk dispatch (`bulk_dispatch.php`) | `DelhiveryAdapter`, `BlueDartAdapter` | Delhivery / Blue Dart teammates |
| **International** (IN → abroad) | Single order dispatch (`create.php`) | `AramexAdapter`, `DhlAdapter`, `FedExAdapter`, `UPSAdapter` | International team |
| **Legacy domestic fallback** | Bulk dispatch | Shiprocket (existing code) | Until Delhivery / Blue Dart are live |

**Rule:** Do not add partner-specific API code in `DispatchController.php`. Use adapters + gateway only.

---

## Folder layout

```text
helpers/courier/
├── Contracts/
│   └── CourierAdapterInterface.php    ← implement for every partner
├── Adapters/
│   ├── AramexAdapter.php              ← international (live rates)
│   ├── DhlAdapter.php                 ← international (skeleton)
│   ├── FedExAdapter.php               ← international (skeleton)
│   ├── UPSAdapter.php                 ← international (skeleton)
│   ├── DelhiveryAdapter.php           ← domestic (skeleton)
│   ├── BlueDartAdapter.php            ← domestic (skeleton)
│   └── …                              ← one file per partner
├── Gateway/
│   └── CourierGateway.php             ← routes domestic vs international
├── Support/
│   └── CourierUiFormat.php            ← normalizes API → UI JSON
├── CourierDispatchService.php         ← controllers call THIS
├── country_codes.php                  ← uses `countries` table
└── partner_credential_schemas.php     ← JSON templates for Courier accounts UI

models/courier/
├── CourierPartner.php
├── CourierAccount.php                   ← credentials_json + environment
└── CourierShipment.php                  ← courier_shipments + api logs

sql/
├── seed_courier_partners.sql
└── create_courier_shipments_table.sql
```

Optional partner HTTP client at repo root (same pattern as Aramex/DHL):

```text
aramex_service.php      ← Aramex SOAP client
dhl_service.php         ← DHL MyDHL REST client
bluedart_service.php    ← Blue Dart HTTP client (skeleton)
fedex_service.php       ← FedEx REST client (skeleton)
ups_service.php         ← UPS REST client (skeleton)
delhivery_service.php   ← optional; thin HTTP wrapper when implementing Delhivery
```

---

## Adding a new partner (checklist)

1. **DB** — Add row in `courier_partners` (`partner_code` lowercase in code, e.g. `delhivery`).
2. **Credentials template** — Add block in `partner_credential_schemas.php`.
3. **Courier accounts UI** — Admin adds account; JSON + environment flag (sandbox/production).
4. **Adapter** — Create `helpers/courier/Adapters/{Partner}Adapter.php` implementing `CourierAdapterInterface`:
   - `partnerCode()`
   - `getRates($request)`
   - `createShipment($request)`
5. **Register in gateway** — Wire in `CourierGateway.php` (domestic or international branch only).
6. **Log API calls** — Use `CourierShipment::logApiCall()` for audit.
7. **Do not** duplicate UI in a new screen — reuse bulk/single dispatch courier tiles.

---

## Controller pattern (required)

```php
$courierDispatch = new CourierDispatchService($GLOBALS['conn']);
$rateRequest = $courierDispatch->buildRateRequest($input, $orderInfo);
$result = $courierDispatch->getRates($rateRequest);

if (!empty($result['use_shiprocket'])) {
    // legacy Shiprocket path (domestic only)
}

echo json_encode($courierDispatch->formatServiceabilityForUi($result));
```

Same flow for **bulk** and **single order** dispatch.

---

## UI contract (bulk + single must match)

Endpoint: `POST ?page=dispatch&action=getCourierServiceability`

**Success response** (used by `bulk_dispatch.php` courier tiles):

```json
{
  "success": true,
  "provider": "delhivery",
  "international": false,
  "couriers": [
    {
      "id": "delhivery_1_SURFACE",
      "name": "Delhivery Surface",
      "price": 120.00,
      "currency": "INR",
      "etd": "3-5 days",
      "rating": 4.2,
      "partner_code": "delhivery",
      "partner_account_id": 1,
      "product_group": "",
      "product_type": "",
      "service_code": "SURFACE"
    }
  ]
}
```

**Box element data attributes** (already in bulk UI — preserve when extending):

- `data-selected-courier-id`
- `data-selected-courier-name`
- `data-partner-code`
- `data-product-group` / `data-product-type`
- `data-partner-account-id`

Single dispatch (`create.php`) should use the **same** fetch + tile pattern when courier selection is added.

---

## Credentials JSON

Stored in `courier_partner_accounts.credentials_json`. Loaded via:

```php
$credentials = $accountModel->getCredentialsJson($accountId);
```

Use `environment` column + sandbox/production URL fields (see Aramex/Delhivery templates).

---

## Delhivery / Blue Dart teammate — start here

1. Read `DelhiveryAdapter.php` or `BlueDartAdapter.php` (skeleton with TODOs).
2. Implement `getRates()` → return `couriers[]` in UI contract shape.
3. Implement `createShipment()` → return `awb`, `label_url`.
4. Test via bulk dispatch → List couriers (domestic order).
5. When a partner works, its quotes appear in the merged tile list (provider `multi`).

**Do not modify:** international adapters or `create.php` intl flow unless coordinating with international team.

---

## FedEx / UPS / DHL teammate — start here

1. Read `FedExAdapter.php`, `UPSAdapter.php`, or `DhlAdapter.php`.
2. Use `fedex_service.php`, `ups_service.php`, or `dhl_service.php` for HTTP/OAuth.
3. Implement `getRates()` and `createShipment()` for single order dispatch.
4. Test via single dispatch → List couriers (international order).

---

## Aramex / international — start here

1. Read `AramexAdapter.php` + `AramexShipmentBuilder.php`.
2. Wire **single dispatch** `create.php` (not bulk) for create + label.
3. Credentials: two accounts (60524328 / 60525897) as separate Courier account rows.

---

## Git / conflict avoidance

- Shared core: `helpers/courier/*`, `models/courier/*` — coordinate small PRs.
- Delhivery: `DelhiveryAdapter.php` + optional `delhivery_service.php`
- Blue Dart: `BlueDartAdapter.php` + `bluedart_service.php`
- FedEx / UPS / DHL: respective adapter + `*_service.php`
- Aramex: `AramexAdapter.php`, `AramexShipmentBuilder.php`, `create.php`
- `DispatchController`: only calls `CourierDispatchService` — avoid large edits.

---

## Reference

Full architecture notes: `docs/COURIER_INTEGRATION_ARCHITECTURE.md`
