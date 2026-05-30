# AI Agent Prompt — Courier Integration Module

Copy everything inside the **prompt block** below and paste it as the first message (or system context) when using Cursor, Copilot, ChatGPT, or any AI coding agent on this project.

---

## Prompt (copy from here)

```markdown
You are working on the **Exotic Vendor ERP** courier dispatch module — a plain PHP (no framework) codebase at `exotic_vendor/`.

## Your scope

Implement or extend **one courier partner at a time** following the existing adapter pattern. Do NOT rewrite controllers, UI, or shared gateway code unless explicitly asked.

## Business routing (team agreement)

| Market | UI | Partners | Adapter location |
|--------|-----|----------|------------------|
| Domestic IN→IN | Bulk dispatch (`views/dispatch/bulk_dispatch.php`) | Delhivery, Blue Dart (+ Shiprocket legacy fallback) | `helpers/courier/Adapters/DelhiveryAdapter.php`, `BlueDartAdapter.php` |
| International IN→abroad | Single order dispatch (`views/dispatch/create.php`) | Aramex (live), DHL, FedEx, UPS | `helpers/courier/Adapters/AramexAdapter.php`, `DhlAdapter.php`, `FedExAdapter.php`, `UPSAdapter.php` |

## Architecture — strict call chain

```
UI → DispatchController → CourierDispatchService → CourierGateway → *Adapter → *_service.php
```

**Rules:**
1. Controllers (`DispatchController.php`) must NEVER contain partner API logic.
2. Adapters must implement `CourierAdapterInterface` in `helpers/courier/Contracts/CourierAdapterInterface.php`.
3. HTTP/SOAP transport lives in repo-root `*_service.php` files (e.g. `aramex_service.php`, `fedex_service.php`).
4. Adapters load credentials from DB via `CourierAccount::getCredentialsJson($accountId)`.
5. Every API call must be logged via `CourierShipment::logApiCall()`.
6. Do NOT duplicate courier selection UI — reuse the existing tile contract in bulk/single dispatch.

## Database tables

### courier_partners
- Master partner list. `partner_code` is UPPER in DB, **lowercase in PHP** (`delhivery`, `bluedart`, `aramex`, `dhl`, `fedex`, `ups`).
- Columns: `supports_domestic`, `supports_international`, `is_active`.

### courier_partner_accounts
- One row per API account (multiple accounts per partner allowed).
- **credentials_json** (TEXT): single JSON object with all secrets and URLs.
- **environment**: `sandbox` or `production` — selects URL bucket.
- Load: `CourierAccount::listActiveAccountsByPartnerCode('delhivery')` and `getCredentialsJson($id)`.

### courier_shipments
- Unified shipment records: `partner_code`, `partner_account_id`, `awb`, `label_url`, `status`, etc.

### courier_api_logs
- Audit log: `partner_code`, `action`, `request_json`, `response_json`, `success`.

Credential templates per partner: `helpers/courier/partner_credential_schemas.php`.
URL resolution: `helpers/courier/credential_urls.php` → `resolveCourierCredentialUrls($credentials)`.

## CourierAdapterInterface (must implement)

```php
public function partnerCode(): string;           // lowercase e.g. 'bluedart'
public function getRates(array $request): array;
public function createShipment(array $request): array;
```

## Rate request (built by CourierDispatchService::buildRateRequest)

Key fields: `order_number`, `partner_code` (empty = all partners), `partner_account_id`, `weight`, `chargeable_weight_kg`, `length`, `breadth`, `height`, `cod`, `destination_country`, `destination` (address array), `is_international`.

Chargeable weight: `max(actual_kg, L×W×H/5000)` via `CourierGateway::chargeableWeightKg()`.

Country helpers: `normalizeCountryIso2()`, `isInternationalShipmentCountry()` in `helpers/courier/country_codes.php` (uses `countries` table).

## getRates() must return (UI contract)

```php
[
    'success' => true,
    'couriers' => [
        [
            'id' => 'delhivery_1_SURFACE',
            'name' => 'Delhivery Surface',
            'price' => 120.00,
            'currency' => 'INR',
            'etd' => '3-5 days',
            'rating' => 4.2,
            'partner_code' => 'delhivery',
            'partner_account_id' => 1,
            'product_group' => '',
            'product_type' => '',
            'service_code' => 'SURFACE',
        ],
    ],
]
```

On failure: `['success' => false, 'message' => '...', 'debug' => [...]]`.

Gateway merges all domestic/international adapters when `partner_code` is empty. Domestic failures fall back to Shiprocket via `use_shiprocket: true`.

## createShipment() must return

```php
['success' => true, 'awb' => '...', 'label_url' => '...', 'partner_shipment_id' => '...']
```

## API endpoint (already wired — do not recreate)

`POST ?page=dispatch&action=getCourierServiceability`

Controller code pattern:
```php
$courierDispatch = new CourierDispatchService($GLOBALS['conn']);
$rateRequest = $courierDispatch->buildRateRequest($input, $orderInfo);
$result = $courierDispatch->getRates($rateRequest);
echo json_encode($courierDispatch->formatServiceabilityForUi($result));
```

## File map (edit only what you own)

### Shared (avoid unless coordinated)
- `helpers/courier/CourierDispatchService.php`
- `helpers/courier/Gateway/CourierGateway.php`
- `helpers/courier/Support/CourierUiFormat.php`
- `models/courier/CourierAccount.php`, `CourierShipment.php`, `CourierPartner.php`

### Per-partner (your workspace)
| Partner | Adapter | Service | Status |
|---------|---------|---------|--------|
| Delhivery | `Adapters/DelhiveryAdapter.php` | `delhivery_service.php` (optional) | Skeleton |
| Blue Dart | `Adapters/BlueDartAdapter.php` | `bluedart_service.php` | Skeleton |
| Aramex | `Adapters/AramexAdapter.php` | `aramex_service.php` | Live rates |
| DHL | `Adapters/DhlAdapter.php` | `dhl_service.php` | Skeleton |
| FedEx | `Adapters/FedExAdapter.php` | `fedex_service.php` | Skeleton |
| UPS | `Adapters/UPSAdapter.php` | `ups_service.php` | Skeleton |

Reference implementations:
- **Live:** `AramexAdapter.php` + `aramex_service.php` + `AramexShipmentBuilder.php`
- **Skeleton:** `DelhiveryAdapter.php`

### Admin UI
- `?page=courier_accounts` — JSON credentials editor
- `helpers/courier/partner_credential_schemas.php` — add template when adding new partner

### SQL
- `sql/seed_courier_partners.sql` — partner seed data
- `sql/create_courier_shipments_table.sql` — shipments + logs

## Adding a new partner checklist

1. Row in `courier_partners` (+ seed file)
2. Block in `partner_credential_schemas.php`
3. `{partner}_service.php` at repo root (HTTP/SOAP only)
4. `Adapters/{Partner}Adapter.php` implementing interface
5. Register in `CourierGateway.php` → `$domesticAdapters` OR `$internationalAdapters`
6. Log all API calls
7. Test via bulk (domestic) or single dispatch (international)

## Coding standards for this repo

- Plain PHP, mysqli, no framework
- Match existing naming: PascalCase classes, snake_case DB columns
- Minimize diff scope — do not refactor unrelated code
- Never commit secrets (passwords, PINs, API keys)
- Use `resolveCourierCredentialUrls()` for sandbox/production URLs
- Run `php -l` on changed files
- Comments only for non-obvious business logic

## What NOT to do

- Do NOT add partner API calls in `DispatchController.php`
- Do NOT create new dispatch screens for courier selection
- Do NOT store credentials outside `credentials_json`
- Do NOT modify another teammate's adapter without coordination
- Do NOT break Shiprocket fallback for domestic (`use_shiprocket` path)
- Do NOT hardcode country names — use `country_codes.php` + `countries` table

## Full human-readable docs in repo

- `docs/COURIER_TEAM_HANDBOOK.md` — complete file list, DB schema, ownership
- `docs/COURIER_DEVELOPER_GUIDE.md` — quick checklist
- `docs/COURIER_INTEGRATION_ARCHITECTURE.md` — long-term design

When asked to implement a partner, start by reading that partner's existing adapter skeleton and service file, then follow the Aramex adapter pattern for response mapping.
```

---

## How to use this prompt

### Cursor / VS Code
1. Open the courier adapter file you are working on.
2. Start a new chat and paste the full prompt above.
3. Add your task: e.g. *"Implement DelhiveryAdapter::getRates() using Delhivery pincode serviceability API."*

### ChatGPT / Claude
1. Paste the prompt as the first message.
2. Upload or paste the relevant adapter + service file contents.
3. Reference `docs/COURIER_TEAM_HANDBOOK.md` for file paths.

### Per-partner add-on (append to prompt)

**Delhivery / Blue Dart:**
> I own domestic bulk dispatch only. Edit `DelhiveryAdapter.php` (or `BlueDartAdapter.php`) and optional service file. Do not touch international adapters.

**FedEx / UPS / DHL:**
> I own international single dispatch only. Edit my adapter + service file. Do not touch domestic adapters or bulk_dispatch.php.

**Aramex:**
> Aramex rates are live. Focus on createShipment wiring in single dispatch (`create.php`) and `AramexShipmentBuilder.php`. Two accounts: 60524328 and 60525897 as separate courier account rows.
