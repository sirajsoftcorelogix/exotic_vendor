# Courier Integration — Team Handbook

**Project:** Exotic Vendor ERP  
**Module:** Multi-partner courier dispatch (domestic bulk + international single order)  
**Audience:** Developers, QA, and anyone using an AI coding agent on this module  

Share this document with your teammate before they start work. For AI agents, also share [`COURIER_AI_AGENT_PROMPT.md`](./COURIER_AI_AGENT_PROMPT.md).

---

## 1. What we are building

A **partner-agnostic dispatch layer** so multiple couriers can be added without rewriting controllers or UI.

| Market | Route | UI screen | Partners | Status |
|--------|-------|-----------|----------|--------|
| **Domestic** (IN → IN) | Bulk dispatch | `views/dispatch/bulk_dispatch.php` | Delhivery, Blue Dart | Skeleton adapters; Shiprocket fallback |
| **International** (IN → abroad) | Single order | `views/dispatch/create.php` | Aramex, DHL, FedEx, UPS | Aramex rates live; others skeleton |
| **Legacy domestic** | Bulk dispatch | Same bulk UI | Shiprocket | Active fallback until direct APIs are live |

**Golden rule:** Controllers never call courier APIs directly. They call `CourierDispatchService` only.

---

## 2. Architecture (call flow)

```text
┌─────────────────────────────────────────────────────────────────┐
│  UI: bulk_dispatch.php  OR  create.php                          │
│  POST ?page=dispatch&action=getCourierServiceability            │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  controllers/DispatchController.php                             │
│  getCourierServiceability()  — thin; no partner logic             │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  helpers/courier/CourierDispatchService.php                     │
│  buildRateRequest() → getRates() → formatServiceabilityForUi()  │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  helpers/courier/Gateway/CourierGateway.php                     │
│  Routes domestic vs international; merges multi-partner quotes  │
└──────────────┬──────────────────────────────┬───────────────────┘
               │                              │
               ▼                              ▼
   Domestic adapters                 International adapters
   • DelhiveryAdapter                • AramexAdapter (live)
   • BlueDartAdapter                 • DhlAdapter
                                     • FedExAdapter
                                     • UPSAdapter
               │                              │
               ▼                              ▼
   *_service.php (HTTP/SOAP)         models/courier/CourierAccount
   CourierShipment::logApiCall()     (credentials_json from DB)
```

**Create shipment flow** (same gateway, not yet fully wired in all UI paths):

```text
CourierDispatchService / CourierGateway::createShipment()
  → adapter.createShipment()
  → partner *_service.php
  → return { success, awb, label_url, partner_shipment_id }
```

---

## 3. Complete file inventory

### 3.1 Core module (shared — coordinate PRs)

| File | Purpose |
|------|---------|
| `helpers/courier/CourierDispatchService.php` | **Single entry point** for controllers (bulk + single dispatch) |
| `helpers/courier/Gateway/CourierGateway.php` | Routes domestic/intl; merges rates; createShipment routing |
| `helpers/courier/Contracts/CourierAdapterInterface.php` | Interface every adapter must implement |
| `helpers/courier/Support/CourierUiFormat.php` | Normalizes gateway output → UI JSON contract |
| `helpers/courier/country_codes.php` | ISO2 country lookup via `countries` table |
| `helpers/courier/credential_urls.php` | Resolves sandbox/production URLs from credentials JSON |
| `helpers/courier/partner_credential_schemas.php` | JSON templates for Courier Accounts admin UI |

### 3.2 Partner adapters (one owner per file)

| File | Partner | Market | Status |
|------|---------|--------|--------|
| `helpers/courier/Adapters/DelhiveryAdapter.php` | Delhivery | Domestic | Skeleton |
| `helpers/courier/Adapters/BlueDartAdapter.php` | Blue Dart | Domestic | Skeleton |
| `helpers/courier/Adapters/AramexAdapter.php` | Aramex | International | **Live rates** |
| `helpers/courier/Adapters/DhlAdapter.php` | DHL Express | International | Skeleton |
| `helpers/courier/Adapters/FedExAdapter.php` | FedEx | International | Skeleton |
| `helpers/courier/Adapters/UPSAdapter.php` | UPS | International | Skeleton |

### 3.3 Partner HTTP/SOAP clients (repo root)

| File | Partner | Notes |
|------|---------|-------|
| `aramex_service.php` | Aramex | SOAP; rates + createInternationalShipment |
| `dhl_service.php` | DHL | MyDHL REST; OAuth via api_key/secret |
| `bluedart_service.php` | Blue Dart | HTTP skeleton |
| `fedex_service.php` | FedEx | REST + OAuth skeleton |
| `ups_service.php` | UPS | REST + OAuth skeleton |
| `delhivery_service.php` | Delhivery | *Not created yet — optional when implementing* |

### 3.4 Aramex-specific helpers

| File | Purpose |
|------|---------|
| `helpers/courier/AramexShipmentBuilder.php` | Builds CreateShipments SOAP payload (EXP export, GST, label ReportID 9729) |

### 3.5 Models

| File | Purpose |
|------|---------|
| `models/courier/CourierPartner.php` | CRUD for `courier_partners`; auto-creates table |
| `models/courier/CourierAccount.php` | Accounts + `credentials_json` + `environment`; auto-migrates columns |
| `models/courier/CourierShipment.php` | `courier_shipments` + `courier_api_logs`; `logApiCall()` |

### 3.6 Controllers

| File | Purpose |
|------|---------|
| `controllers/DispatchController.php` | Dispatch UI actions; calls `CourierDispatchService` for rates |
| `controllers/CourierPartnersController.php` | Admin: manage partner master list |
| `controllers/CourierAccountsController.php` | Admin: manage accounts + JSON credentials editor |

### 3.7 Views

| File | Purpose |
|------|---------|
| `views/dispatch/bulk_dispatch.php` | **Domestic bulk dispatch** — courier tiles, dimensions, fetch rates |
| `views/dispatch/create.php` | **Single order dispatch** — international create (wire courier tiles here) |
| `views/dispatch/index.php` | Dispatch listing |
| `views/courier_partners/index.php` | Partner admin UI |
| `views/courier_accounts/index.php` | Account + JSON credentials UI |

### 3.8 SQL migrations / seeds

| File | Purpose |
|------|---------|
| `sql/create_courier_partners_table.sql` | `courier_partners` DDL |
| `sql/create_courier_accounts_tables.sql` | `courier_partner_accounts` + legacy key/value creds table |
| `sql/create_courier_shipments_table.sql` | `courier_shipments` + `courier_api_logs` |
| `sql/seed_courier_partners.sql` | Seed ARAMEX, DHL, DELHIVERY, BLUEDART, FEDEX, UPS, SHIPROCKET |

Run seeds on each environment after deploy:

```sql
SOURCE sql/seed_courier_partners.sql;
```

Tables are also auto-created by PHP models on first use (`CourierPartner`, `CourierAccount`, `CourierShipment`).

### 3.9 Documentation

| File | Purpose |
|------|---------|
| `docs/COURIER_TEAM_HANDBOOK.md` | **This document** — file list, DB, coding rules |
| `docs/COURIER_DEVELOPER_GUIDE.md` | Quick-start checklist for new partners |
| `docs/COURIER_INTEGRATION_ARCHITECTURE.md` | Long-term architecture vision |
| `docs/COURIER_AI_AGENT_PROMPT.md` | Copy-paste prompt for AI coding agents |
| `SHIPROCKET_COURIER_SERVICEABILITY.md` | Legacy Shiprocket serviceability notes |

### 3.10 Legacy / related (do not break)

| File | Purpose |
|------|---------|
| `models/dispatch/dispatch.php` | Shiprocket token + API; legacy domestic fallback |
| `courier_selector.php` | Quote ranking helper (Shiprocket-shaped; future: normalized quotes) |
| `test_armex.php` | Manual Aramex API test script |
| `test_dhl.php` | Manual DHL API test script |

### 3.11 Routes (`index.php`)

| URL | Handler |
|-----|---------|
| `?page=courier_partners` | Partner admin |
| `?page=courier_accounts` | Account + credentials admin |
| `?page=dispatch&action=getCourierServiceability` | Fetch courier rates (bulk + single) |
| `?page=dispatch&action=bulk_dispatch` | Bulk dispatch create |
| `?page=dispatch` (default) | Single order dispatch |

---

## 4. Database structure

### 4.1 Entity relationship

```text
courier_partners (1) ──< courier_partner_accounts (many)
                              │
                              ├── credentials_json (TEXT)
                              ├── environment (sandbox | production)
                              └── priority, is_active, account_code

courier_shipments ── logs ──> courier_api_logs
     │
     └── links to invoice/box/order (optional legacy_dispatch_id)
```

### 4.2 Table: `courier_partners`

Master list of courier brands.

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK | |
| `partner_code` | VARCHAR(50) UNIQUE | Stored UPPER in DB; **lowercase in PHP code** |
| `partner_name` | VARCHAR(120) | Display name |
| `supports_domestic` | TINYINT | 1 = can ship IN→IN |
| `supports_international` | TINYINT | 1 = can ship abroad |
| `is_active` | TINYINT | |
| `notes` | TEXT | |

**Seeded partners:**

| partner_code | Domestic | International |
|--------------|----------|---------------|
| ARAMEX | ✓ | ✓ |
| DHL | | ✓ |
| DELHIVERY | ✓ | |
| BLUEDART | ✓ | |
| FEDEX | | ✓ |
| UPS | | ✓ |
| SHIPROCKET | ✓ | |

### 4.3 Table: `courier_partner_accounts`

One row per API account (supports multiple accounts per partner).

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK | Used as `partner_account_id` in UI + adapters |
| `partner_id` | INT FK | → `courier_partners.id` |
| `account_code` | VARCHAR(80) | Unique per partner, e.g. `60524328` |
| `account_name` | VARCHAR(140) | Human label |
| `is_active` | TINYINT | |
| `priority` | INT | Lower = preferred when multiple accounts |
| `credentials_json` | TEXT | **All API secrets in one JSON object** |
| `environment` | VARCHAR(20) | `sandbox` or `production` |
| `tags_json` | TEXT | Optional routing tags |
| `notes` | TEXT | |

**Load credentials in code:**

```php
$accountModel = new CourierAccount($conn);
$credentials = $accountModel->getCredentialsJson($accountId);
$accounts = $accountModel->listActiveAccountsByPartnerCode('delhivery');
```

### 4.4 Table: `courier_partner_account_credentials` (legacy)

Key/value credential rows — **deprecated for new work**. Use `credentials_json` instead. Table kept for backward compatibility.

### 4.5 Table: `courier_shipments`

Unified shipment record (partner-agnostic).

| Column | Type | Notes |
|--------|------|-------|
| `partner_code` | VARCHAR(50) | e.g. `aramex`, `delhivery` |
| `partner_account_id` | INT | FK-ish to account row |
| `partner_shipment_id` | VARCHAR(120) | Partner's shipment ID |
| `awb` | VARCHAR(80) | Airway bill / tracking number |
| `label_url` | TEXT | |
| `product_group` / `product_type` | VARCHAR | Aramex EXP/PPX etc. |
| `status` | VARCHAR(40) | Default `draft` |
| `invoice_id`, `box_no`, `order_number` | | Link to ERP dispatch |
| `legacy_dispatch_id` | INT | Link to old `vp_dispatch_details` |

### 4.6 Table: `courier_api_logs`

Audit log for every partner API call.

| Column | Type | Notes |
|--------|------|-------|
| `partner_code` | VARCHAR(50) | |
| `partner_account_id` | INT NULL | |
| `action` | VARCHAR(80) | e.g. `get_rates`, `create_shipment` |
| `reference_key` | VARCHAR(120) | Usually order_number |
| `request_json` / `response_json` | MEDIUMTEXT | |
| `success` | TINYINT | |
| `error_message` | TEXT | |

**Log from adapters:**

```php
$this->shipmentModel->logApiCall(
    'delhivery',
    'get_rates',
    $accountId,
    $orderNumber,
    $requestPayload,
    $responsePayload,
    $success,
    $errorMessage
);
```

### 4.7 Related ERP tables (read-only for adapters)

| Table | Used for |
|-------|----------|
| `vp_orders` / order info via `ordersModel` | Shipping address, country, zipcode |
| `countries` | ISO2 country resolution (`country_codes.php`) |
| `firm_details` | Pickup location / postcode |
| `vp_dispatch_details` | Legacy Shiprocket dispatch records |

---

## 5. Coding structure & rules

### 5.1 Layer responsibilities

| Layer | May do | Must NOT do |
|-------|--------|-------------|
| **Controller** (`DispatchController`) | Validate input, load order, call `CourierDispatchService`, return JSON | Partner API calls, credential handling, payload building |
| **CourierDispatchService** | Build rate request, call gateway, format UI response | Partner-specific logic |
| **CourierGateway** | Route domestic/intl, merge quotes, delegate createShipment | Build partner API payloads |
| **Adapter** (`*Adapter.php`) | Load credentials, call `*_service.php`, map to UI contract, log API | Modify controllers or views |
| **Service** (`*_service.php`) | HTTP/SOAP transport, auth, raw API methods | Dispatch business logic, DB writes |

### 5.2 Adapter interface (required)

Every adapter implements `CourierAdapterInterface`:

```php
interface CourierAdapterInterface
{
    public function partnerCode(): string;           // lowercase, e.g. 'bluedart'
    public function getRates(array $request): array; // returns couriers[]
    public function createShipment(array $request): array; // returns awb, label_url
}
```

### 5.3 Rate request shape (from `CourierDispatchService::buildRateRequest`)

```php
[
    'order_number' => 'ORD-123',
    'partner_code' => '',              // empty = fetch all partners for market
    'partner_account_id' => 0,         // 0 = pick default/first active account
    'weight' => 1.5,
    'chargeable_weight_kg' => 2.0,     // max(actual, volumetric L×W×H/5000)
    'length' => 30, 'breadth' => 20, 'height' => 10,
    'cod' => 0,
    'destination_country' => 'US',
    'destination' => [
        'line1', 'line2', 'city', 'state', 'postcode', 'country_code'
    ],
    'pickup_location' => 'Head Off',
    'is_international' => true,
]
```

### 5.4 Rate response shape (adapter → gateway → UI)

**Adapter success:**

```php
[
    'success' => true,
    'couriers' => [
        [
            'id' => 'delhivery_1_SURFACE',       // unique per quote
            'name' => 'Delhivery Surface',
            'price' => 120.00,
            'currency' => 'INR',
            'etd' => '3-5 days',
            'rating' => 4.2,
            'partner_code' => 'delhivery',
            'partner_account_id' => 1,
            'product_group' => '',               // Aramex: EXP
            'product_type' => '',                // Aramex: PPX
            'service_code' => 'SURFACE',
        ],
    ],
]
```

**Gateway multi-partner merge:** `provider: "multi"`, all quotes sorted by price.

**Domestic fallback:** `use_shiprocket: true` when no direct partner returns rates.

### 5.5 Create shipment response shape

```php
[
    'success' => true,
    'awb' => '1234567890',
    'label_url' => 'https://...',
    'partner_shipment_id' => '...',
]
```

### 5.6 Credentials JSON conventions

- Stored in `courier_partner_accounts.credentials_json`
- Template per partner in `helpers/courier/partner_credential_schemas.php`
- Use `sandbox_api_base_url` / `production_api_base_url` keys
- `environment` column on account row selects which URL bucket to use
- Resolve URLs via `resolveCourierCredentialUrls($credentials)` in `credential_urls.php`
- **Never commit real passwords, PINs, or API secrets to git**

### 5.7 Chargeable weight (shared formula)

```php
CourierGateway::chargeableWeightKg($actualKg, $lengthCm, $widthCm, $heightCm)
// volumetric = L × W × H / 5000
// chargeable = max(actual, volumetric)
```

### 5.8 International vs domestic detection

```php
normalizeCountryIso2($country, $conn);           // uses countries table
isInternationalShipmentCountry($iso2, $conn);      // true if not IN
```

---

## 6. How to add a new partner (step-by-step)

1. **SQL** — Add row to `courier_partners` (or extend `seed_courier_partners.sql`).
2. **Credentials template** — Add block in `partner_credential_schemas.php`.
3. **Service file** (optional) — Create `{partner}_service.php` at repo root for HTTP/SOAP.
4. **Adapter** — Create `helpers/courier/Adapters/{Partner}Adapter.php` implementing `CourierAdapterInterface`.
5. **Gateway** — Register in `$domesticAdapters` or `$internationalAdapters` in `CourierGateway.php`.
6. **Admin** — Add account via `?page=courier_accounts` with JSON credentials.
7. **Test** — Domestic: bulk dispatch. International: single dispatch.
8. **Log** — Every API call via `CourierShipment::logApiCall()`.

**Do NOT:** add partner logic to `DispatchController.php`, duplicate courier tile UI, or create a separate dispatch screen.

---

## 7. Ownership matrix (avoid merge conflicts)

| Teammate focus | Files they own | Do not touch |
|----------------|----------------|--------------|
| **Delhivery** | `DelhiveryAdapter.php`, optional `delhivery_service.php` | International adapters, Aramex files |
| **Blue Dart** | `BlueDartAdapter.php`, `bluedart_service.php` | International adapters |
| **FedEx** | `FedExAdapter.php`, `fedex_service.php` | Domestic adapters |
| **UPS** | `UPSAdapter.php`, `ups_service.php` | Domestic adapters |
| **DHL** | `DhlAdapter.php`, `dhl_service.php` | Domestic adapters |
| **Aramex / intl lead** | `AramexAdapter.php`, `AramexShipmentBuilder.php`, `aramex_service.php`, `create.php` intl wiring | Domestic adapters |
| **Shared (coordinate PRs)** | `CourierGateway.php`, `CourierDispatchService.php`, `CourierUiFormat.php`, models | Large refactors without team sync |

---

## 8. Testing checklist

### Domestic (bulk dispatch)

1. Open `?page=dispatch&action=bulk_dispatch` (or bulk dispatch from menu).
2. Enter domestic order, dimensions, weight.
3. Click fetch couriers → `POST ?page=dispatch&action=getCourierServiceability`.
4. Expect: Delhivery + Blue Dart attempts; if none live → Shiprocket fallback.
5. Check `courier_api_logs` for logged attempts.

### International (single dispatch)

1. Open single order dispatch for non-IN shipping country.
2. Fetch couriers via same endpoint.
3. Expect: Aramex quotes (if credentials configured); DHL/FedEx/UPS in `rejected_couriers` until implemented.
4. Manual scripts: `test_armex.php`, `test_dhl.php`.

---

## 9. Git & PR guidelines

- Keep PRs **small and partner-scoped** (one adapter + one service file).
- Do not commit credentials or PINs.
- Run `php -l` on changed PHP files before PR.
- Shared files (`CourierGateway.php`): announce in team chat before editing.
- Prefer extending adapters over modifying `DispatchController.php`.

---

## 10. Quick reference links

| Need | Document |
|------|----------|
| Fast checklist | `docs/COURIER_DEVELOPER_GUIDE.md` |
| Long-term design | `docs/COURIER_INTEGRATION_ARCHITECTURE.md` |
| AI agent context | `docs/COURIER_AI_AGENT_PROMPT.md` |
| Credential templates | `helpers/courier/partner_credential_schemas.php` |
| Live adapter example | `helpers/courier/Adapters/AramexAdapter.php` |
| Skeleton adapter example | `helpers/courier/Adapters/DelhiveryAdapter.php` |

---

*Last updated: May 2026 — reflects Delhivery, Blue Dart, Aramex, DHL, FedEx, UPS scaffolding.*
