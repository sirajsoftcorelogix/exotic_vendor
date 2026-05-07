## Goal
Integrate **multiple domestic + international courier partners** (Shiprocket, direct carrier APIs, aggregators, etc.) with:

- **Clean separation of concerns** (controllers don’t know partner details)
- **Code reuse** (one central orchestration flow)
- **Partner add/remove ease** (drop-in adapters)
- **Auditability** (every request/response stored, consistent status model)
- **Reliability** (retries, idempotency, webhook handling)

This document proposes an architecture that fits the current codebase style (plain PHP controllers/models) while creating a scalable foundation.

---

## Current pain points (what we saw in this repo)

- Shiprocket logic is currently embedded in:
  - `controllers/DispatchController.php` (payload build + branching + validation)
  - `models/dispatch/dispatch.php` (Shiprocket token + API calls)
- DB schema (`vp_dispatch_details`) stores Shiprocket-specific fields (`shiprocket_order_id`, `shiprocket_shipment_id`, …), making multi-partner hard.
- Partner selection exists (`courier_selector.php`) but is **Shiprocket-response shaped**, not partner-agnostic.

---

## Target design (high-level)

### Layers

1. **Domain / Use-cases**
   - “Create shipment”, “Get rates”, “Generate label”, “Cancel shipment”, “Track shipment”
   - Partner-agnostic input/output models

2. **Courier Gateway (Orchestrator)**
   - Central service that controllers call
   - Picks partner (manual choice, rules, fallback) and delegates to adapter
   - Persists unified shipment records + logs

3. **Partner Adapters**
   - `ShiprocketAdapter`, `DHLAdapter`, `FedExAdapter`, `DelhiveryAdapter`, `BlueDartAdapter`, etc.
   - Each adapter translates domain models ↔ partner API payloads

4. **Infrastructure**
   - HTTP client wrapper (curl behind one interface)
   - Token store / credential store
   - Webhook receiver + signature verifier

---

## Multi-account requirement (critical)

You need support for:

- UPS, Aramex, DHL, FedEx, Blue Dart
- **Multiple accounts per partner**
- Account choice by destination/rate advantage (example: DHL-AU account best for Australia, DHL-EU account best for Europe)

So routing must be:

- **Partner selection** (DHL vs FedEx vs UPS...)
- then **Account selection inside that partner** (DHL account #1 vs #2 vs #3)

The account must be a first-class entity in DB + quote + shipment records.

---

## Core abstractions (interfaces)

Create these under a single module namespace/folder, e.g. `src/Courier/` (or `helpers/courier/` if you want minimal refactor).

### 1) Domain models (partner-agnostic DTOs)

- **`ShipmentRequest`**
  - `reference` (idempotency key, e.g. invoice+box)
  - `shipper` (pickup location/contact)
  - `consignee` (billing/shipping address/contact)
  - `packages[]` (length/width/height/weight/billableWeight)
  - `items[]` (sku/name/qty/value/hsn/tax/…)
  - `paymentMode` (prepaid/cod), `codAmount`
  - `serviceLevel` (economy/express), `isInternational`
  - `documents` (invoice url, ewaybill, etc. if needed for intl)

- **`Shipment`**
  - internal `shipment_id` (your DB id)
  - `partner` (e.g. `shiprocket`)
  - `partner_shipment_id`, `awb`, `tracking_url`
  - `status` (normalized enum), `status_text`, `status_at`

- **`RateRequest` / `RateQuote[]`**
  - returns normalized quotes (total charge, etd days, service level, carrier name, partner metadata)

- **`LabelResult`**
  - label url/pdf bytes, mime, page size

### 2) Adapter capability interfaces

Not all partners support everything. Use optional capability interfaces.

- `CourierAdapterInterface`
  - `public function getPartnerCode(): string;`
  - `public function createShipment(ShipmentRequest $req): ShipmentCreateResult;`

- Optional capabilities:
  - `RateProviderInterface::getRates(RateRequest $req): RateQuoteResult`
  - `LabelProviderInterface::getLabel(string $partnerShipmentId): LabelResult`
  - `TrackingProviderInterface::track(string $awbOrId): TrackingResult`
  - `CancellationInterface::cancel(string $partnerShipmentId): CancelResult`
  - `PickupInterface::schedulePickup(PickupRequest $req): PickupResult`

### 3) Courier Gateway (Orchestrator) interface

- `CourierGateway`
  - `createShipment(CreateShipmentCommand $cmd): CreateShipmentResponse`
  - `getRates(GetRatesCommand $cmd): GetRatesResponse`
  - `getLabel(GetLabelCommand $cmd): LabelResponse`
  - `cancelShipment(CancelShipmentCommand $cmd): CancelResponse`
  - `ingestWebhook(WebhookCommand $cmd): WebhookResponse`

Controllers call **only** the gateway.

---

## Database design (multi-partner friendly)

### 1) Unified shipments table (replace Shiprocket-specific columns)

Create a new table (recommended) rather than patching `vp_dispatch_details` in-place:

#### `courier_shipments`
- `id` (PK)
- `invoice_id` (FK)
- `box_no` (int)
- `order_number` (string) — optional
- `partner_code` (varchar) — `shiprocket`, `dhl`, `fedex`, …
- `partner_shipment_id` (varchar) — shipment/order id at partner
- `awb` (varchar)
- `tracking_url` (text)
- `service_level` (economy/express/…)
- `payment_mode` (prepaid/cod)
- `cod_amount` (decimal)
- `is_international` (tinyint)
- `currency` (varchar(3))
- `charges_total` (decimal) — what partner charged
- `label_url` (text) — if partner hosts it
- `status` (varchar) — normalized status enum (see below)
- `status_text` (varchar) — raw partner status text
- `status_at` (datetime)
- `created_at`, `updated_at`
- `cancelled_at` (nullable)

Keep your existing `vp_dispatch_details` as legacy; link via `legacy_dispatch_id` if needed.

### 2) Event/status history

#### `courier_shipment_events`
- `id` (PK)
- `shipment_id` (FK → `courier_shipments.id`)
- `partner_code`
- `partner_event_id` (nullable)
- `status` (normalized)
- `partner_status` (raw)
- `message` (text)
- `payload_json` (json/text)
- `event_at` (datetime)
- `created_at`

### 3) API request/response logs (central observability)

#### `courier_api_logs`
- `id` (PK)
- `shipment_id` (nullable FK)
- `partner_code`
- `operation` (create_shipment / rates / label / track / cancel / pickup)
- `request_json` / `response_json` / `response_raw`
- `http_code`, `duration_ms`
- `error_class`, `error_message`
- `created_at`

### 4) Credentials / token cache

#### `courier_partner_credentials`
- `partner_code`
- `account_code` (required, unique per partner account)
- `account_name` (human readable, e.g. "DHL AU Export")
- `tenant_id` / `warehouse_id` (if applicable)
- `credentials_json` (encrypted if possible)
- `enabled` (bool)
- `priority` (int; lower = preferred when scores tie)
- `is_default` (bool)
- `tags_json` (optional; e.g. `["intl","express","australia-focus"]`)
- `created_at`, `updated_at`

#### `courier_partner_tokens`
- `partner_code`
- `account_code`
- `token` (encrypted)
- `expires_at`
- `created_at`, `updated_at`

This generalizes your current `shiprocket_api_tokens`.

### 5) Account routing rules

Add a rules table so account can be chosen automatically:

#### `courier_account_routing_rules`
- `id` (PK)
- `partner_code` (nullable; null = applies to all partners)
- `account_code` (FK-ish reference to credentials row)
- `destination_country` (nullable)
- `destination_region` (nullable; e.g. `EU`, `ANZ`, `NA`)
- `destination_postcode_prefix` (nullable)
- `service_level` (nullable; economy/express)
- `shipment_type` (nullable; domestic/international)
- `min_weight`, `max_weight` (nullable)
- `min_order_value`, `max_order_value` (nullable)
- `payment_mode` (nullable; prepaid/cod)
- `priority` (int)
- `active` (bool)
- `effective_from`, `effective_to` (nullable)
- `created_at`, `updated_at`

Use this for deterministic routing before live-rate scoring.

---

## Normalized shipment status enum

Define a small normalized set (store raw too):

- `DRAFT` (created locally only)
- `CREATED` (partner shipment created)
- `PICKUP_SCHEDULED`
- `PICKED_UP`
- `IN_TRANSIT`
- `OUT_FOR_DELIVERY`
- `DELIVERED`
- `RTO_IN_TRANSIT`
- `RTO_DELIVERED`
- `CANCELLED`
- `FAILED`

Every adapter maps partner statuses → this enum.

---

## Flow: Create Shipment (recommended)

### Controller responsibilities (thin)

- Validate UI fields
- Build a `CreateShipmentCommand` (invoiceId, boxNo, partner selection, options)
- Call `CourierGateway::createShipment(...)`
- Render success/failure

### CourierGateway responsibilities (central)

- Load invoice, address, items, boxes (existing model logic can remain)
- Build **one** `ShipmentRequest` (domain DTO)
- Choose partner:
  - explicit selection (UI drop-down)
  - or rules engine (cheapest/fastest/reliable) using available rates
  - optional fallback (if partner fails)
- Call adapter’s `createShipment`
- Persist to `courier_shipments` + `courier_shipment_events`
- Store API log to `courier_api_logs`

### Adapter responsibilities (isolated)

- Convert DTO → partner payload
- Call partner API (via shared HTTP client)
- Validate response
- Return normalized `ShipmentCreateResult`

---

## Flow: Rates + Courier selection (multi-partner)

### Strategy

- Gateway calls `getRates()` across all enabled adapters that implement `RateProviderInterface`.
- For each adapter, it queries **all eligible accounts** for that partner.
- Merge quotes into a single normalized array.
- Run a **partner-agnostic ranking** (your current `courier_selector.php` logic can be adapted to accept normalized rows).

### Quote model must include account identity

Extend normalized quote shape with:

- `partner_code`
- `account_code`
- `account_name`
- `carrier_name`
- `service_level`
- `charges_total`
- `etd_days`
- `destination_country`
- `score_breakdown` (optional)

This allows audit: "Why was DHL account 2 picked for Europe?"

### Recommendation

Refactor `courier_selector.php` to accept this shape:

- `carrier_name`
- `partner_code`
- `account_code`
- `account_name`
- `service_level`
- `charges_total`
- `etd_days`
- `cod_supported`
- `reliability_metrics` (optional)
- `raw_meta` (partner-specific extras)

Then ranking is independent of Shiprocket.

---

## Webhooks + tracking updates

Add a single endpoint:

- `POST /?page=courier_webhook&partner=shiprocket`
- `POST /?page=courier_webhook&partner=fedex`

Gateway flow:

- Verify signature (adapter-specific)
- Parse event → normalized status/event
- Upsert in `courier_shipment_events`
- Update `courier_shipments.status/status_at`

This is critical for reliability and reduces manual tracking calls.

---

## Error handling & reliability

### Idempotency

Use `ShipmentRequest.reference` (e.g. `INV-{invoice_id}-BOX-{box_no}`) and store it in DB. If the same reference is requested again:

- return existing shipment if already created
- or re-try safe operations only

When multi-account is active, also store:

- `selected_partner_code`
- `selected_account_code`
- `selection_reason` (rule match, cheapest, fastest, manual override)

### Retries

For transient HTTP failures:

- retry with exponential backoff in gateway (not inside controller)
- record every attempt in `courier_api_logs`

### Fallback (optional)

If partner fails at create:

- attempt second partner if configured
- or mark `FAILED` and show action to retry with another partner

---

## International shipping specifics (design notes)

International partners usually require:

- Commercial invoice lines (hsn, country of origin, item value, currency)
- IEC/GST, KYC docs, export category
- HS codes beyond 4 digits, sometimes 6/8 digits

Recommendation:

- Extend `ShipmentRequest.items[]` with fields for intl (origin country, hs code, incoterms)
- Keep adapter-specific requirements inside the adapter via validation errors (returned as structured errors)

---

## Suggested folder structure (minimal but scalable)

Option A (recommended): `src/Courier/`

- `src/Courier/Domain/*` (DTOs, enums)
- `src/Courier/Gateway/CourierGateway.php`
- `src/Courier/Adapters/ShiprocketAdapter.php`
- `src/Courier/Adapters/DHLAdapter.php`
- `src/Courier/Infra/HttpClient.php`
- `src/Courier/Repo/CourierShipmentRepository.php`

Option B (lowest change): `helpers/courier/` + `models/courier/`

---

## Migration plan (Shiprocket → adapter) without breaking production

1. **Create new tables** (`courier_shipments`, `courier_api_logs`, `courier_shipment_events`).
2. Implement `CourierGateway` + `ShiprocketAdapter`:
   - Move `getShiprocketToken()` logic into a `ShiprocketAuth` helper inside adapter/infra.
   - Move curl calls into shared `HttpClient`.
3. Update `DispatchController`:
   - Replace direct calls to `$dispatchModel->shiprocketCreateShipment(...)` with `$courierGateway->createShipment(...)`.
4. Keep writing legacy `vp_dispatch_details` during a transition window if existing screens depend on it.
5. Add second adapter (e.g. Delhivery) to validate extensibility.

---

## What “adding a new partner” looks like

To add a new courier partner:

- Create `Adapters/<Partner>Adapter.php` implementing `CourierAdapterInterface`
- Add partner credentials in `courier_partner_credentials`
- Register adapter in a single `CourierAdapterRegistry`
- UI automatically shows the partner as an option if enabled

No controller changes required.

---

## What “adding a new account” looks like

To add a second/third account under the same partner:

1. Insert credentials row with same `partner_code`, new `account_code`.
2. (Optional) Add `courier_account_routing_rules` for destination optimization.
3. Enable account.

No code changes required in controller/gateway/adapter.

---

## Recommended account-selection algorithm

Use a 3-stage approach:

1. **Rule pre-filter**
   - Apply routing rules by destination, service level, shipment type, weight/value, payment mode.
   - If rules match, limit candidate accounts to matched set.

2. **Live quote stage**
   - Fetch rates for candidate accounts only.
   - Normalize all quotes (partner + account + service).

3. **Rank + choose**
   - Score using weighted model (cost, ETA, reliability, SLA, RTO performance).
   - Tie-break with `priority` then `is_default`.

Store the full decision trail in `courier_api_logs` / selection audit table.

---

## Partner list mapping for your ask

Create initial adapters:

- `UPSAdapter`
- `AramexAdapter`
- `DHLAdapter`
- `FedExAdapter`
- `BlueDartAdapter`
- Keep existing `ShiprocketAdapter` during migration

Each adapter must support account-scoped auth/token handling:

- Token key = `partner_code + account_code` (not just partner)
- Separate expiry/refresh per account

---

## Recommended next steps for this repo

- Introduce the gateway + adapter layer while keeping current dispatch UI.
- Generalize DB fields away from Shiprocket-specific columns.
- Refactor courier selection to accept normalized quotes from multiple partners.
- Add webhook ingestion to keep shipment statuses accurate.

