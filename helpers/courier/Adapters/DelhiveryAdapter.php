<?php

require_once __DIR__ . '/../Contracts/CourierAdapterInterface.php';
require_once __DIR__ . '/../country_codes.php';
require_once __DIR__ . '/../Support/CourierUiFormat.php';
require_once __DIR__ . '/../credential_urls.php';
require_once __DIR__ . '/../../../models/courier/CourierAccount.php';
require_once __DIR__ . '/../../../models/courier/CourierShipment.php';
require_once __DIR__ . '/../../../delhivery_service.php';

/**
 * Delhivery domestic adapter (skeleton — implement API calls here).
 *
 * Teammate: fill getRates() / createShipment() using credentials from
 * courier_partner_accounts.credentials_json (partner_code = delhivery).
 */
class DelhiveryAdapter implements CourierAdapterInterface
{
    private CourierAccount $accountModel;
    private ?CourierShipment $shipmentModel;

    public function __construct(CourierAccount $accountModel, ?CourierShipment $shipmentModel = null)
    {
        $this->accountModel = $accountModel;
        $this->shipmentModel = $shipmentModel;
    }

    public function partnerCode(): string
    {
        return 'delhivery';
    }

    public function getRates(array $request): array
    {
        $destinationCountry = normalizeCountryIso2(
            $request['destination_country']
                ?? ($request['destination']['country_code'] ?? 'IN')
        );
        if (isInternationalShipmentCountry($destinationCountry)) {
            return [
                'success' => false,
                'message' => 'Delhivery adapter is domestic only (IN → IN).',
            ];
        }

        $accounts = $this->accountModel->listActiveAccountsByPartnerCode('delhivery');
        if (!$accounts) {
            return [
                'success' => false,
                'message' => 'No active Delhivery courier account configured. Add one under Courier accounts.',
            ];
        }

        $accountId = (int) ($request['partner_account_id'] ?? 0);
        $accountRow = $this->pickAccount($accounts, $accountId);
        $accountId = (int) ($accountRow['id'] ?? 0);
        $credentials = $this->accountModel->getCredentialsJson($accountId);

        $apiKey = trim((string) ($credentials['api_key'] ?? $credentials['token'] ?? $credentials['api_token'] ?? ''));
        if ($apiKey === '') {
            return [
                'success' => false,
                'message' => 'Delhivery api_token is missing. Save credentials in Courier accounts.',
            ];
        }

        $originPin = trim((string) ($request['pickup']['postcode'] ?? $request['pickup_postcode'] ?? ''));
        if ($originPin === '') {
            $originPin = trim((string) (($credentials['shipper']['postcode'] ?? null) ?? ($credentials['customer_pincode'] ?? '')));
        }
        $destPin = trim((string) ($request['destination']['postcode'] ?? $request['delivery_postcode'] ?? ''));
        if ($originPin === '' || $destPin === '') {
            return [
                'success' => false,
                'message' => 'Origin and destination pincodes are required for Delhivery rates.',
            ];
        }

        // Delhivery cgm is grams, weight-only (API has no dimensions). Use the same actual
        // box weight (kg) sent to Shiprocket serviceability, not volumetric chargeable weight.
        $actualKg = (float) ($request['weight'] ?? 0);
        $billableKg = (float) ($request['chargeable_weight_kg'] ?? $actualKg);
        $chargeableGrams = (int) ceil(max(0.0, $actualKg) * 1000);
        if ($chargeableGrams <= 0) {
            return [
                'success' => false,
                'message' => 'Box weight is required for Delhivery rates.',
            ];
        }

        $urlInfo = resolveCourierCredentialUrls($credentials);
        $environment = (string) ($urlInfo['environment'] ?? 'sandbox');
        $baseUrlOverride = trim((string) ($urlInfo['api_base_url'] ?? ''));
        $clientName = trim((string) ($credentials['client_name'] ?? $credentials['cl'] ?? ''));
        $isCod = !empty($request['cod']);
        $pt = $isCod ? 'COD' : 'Pre-paid';
        $ss = 'Delivered';
        $rateApiPath = trim((string) ($credentials['rate_api_path'] ?? $credentials['calculate_shipping_cost_path'] ?? ''));

        $service = new DelhiveryService($apiKey, $environment, $baseUrlOverride);

        // Match Delhivery One developer portal: md, ss, o_pin, d_pin, cgm, pt, cod (optional cl).
        $baseParams = [
            'o_pin' => $originPin,
            'd_pin' => $destPin,
            'ss' => $ss,
            'pt' => $pt,
            'cod' => $isCod ? 1 : 0,
        ];
        if ($clientName !== '') {
            $baseParams['cl'] = $clientName;
        }

        $quotes = [];
        $debug = [];

        foreach (['S' => 'Surface', 'E' => 'Express'] as $md => $label) {
            $params = array_merge($baseParams, [
                'md' => $md,
                'cgm' => $chargeableGrams,
            ]);

            $resp = $service->estimateFreightCharges($params, $rateApiPath);
            $debug[$md] = $resp;

            if (empty($resp['success'])) {
                continue;
            }

            $breakdown = $this->parseChargeBreakdown($resp['data'] ?? null, $resp['raw'] ?? null);
            $price = $this->resolveDisplayAmount($breakdown);
            if ($price === null || $price <= 0) {
                continue;
            }

            $quotes[] = [
                'id' => 'delhivery_' . $accountId . '_' . ($md === 'S' ? 'SURFACE' : 'EXPRESS'),
                'name' => 'Delhivery - ' . $label,
                'price' => $price,
                'currency' => 'INR',
                'etd' => 'N/A',
                'rating' => 0,
                'partner_code' => 'delhivery',
                'partner_account_id' => $accountId,
                'service_code' => $md === 'S' ? 'SURFACE' : 'EXPRESS',
                'metadata' => [
                    'cgm' => $chargeableGrams,
                    'actual_weight_kg' => $actualKg,
                    'billable_weight_kg' => $billableKg,
                    'gross_amount' => $breakdown['gross_amount'],
                    'total_amount' => $breakdown['total_amount'],
                    'origin_pin' => $originPin,
                    'dest_pin' => $destPin,
                    'payment_type' => $pt,
                    'cod' => $isCod ? 1 : 0,
                ],
            ];
        }

        if ($this->shipmentModel) {
            $this->shipmentModel->logApiCall(
                'delhivery',
                'get_rates',
                $accountId,
                (string) ($request['order_number'] ?? ''),
                $request,
                $debug,
                !empty($quotes),
                !empty($quotes) ? 'Delhivery rates fetched' : 'No rates returned from Delhivery'
            );
        }

        if (empty($quotes)) {
            $hint = $environment === 'sandbox'
                ? 'Account environment is sandbox — switch to production if this is a live token.'
                : 'Verify client_name (cl), token, and pincodes in Courier accounts.';
            return [
                'success' => false,
                'message' => 'Delhivery API returned no rates. ' . $hint,
                'debug' => [
                    'partner' => 'delhivery',
                    'account_id' => $accountId,
                    'environment' => $environment,
                    'client_name' => $clientName,
                    'origin_pin' => $originPin,
                    'dest_pin' => $destPin,
                    'cgm' => $chargeableGrams,
                    'actual_weight_kg' => $actualKg,
                    'responses' => $debug,
                ],
            ];
        }

        return [
            'success' => true,
            'provider' => 'delhivery',
            'couriers' => CourierUiFormat::formatQuotes($quotes),
            'debug' => [
                'partner' => 'delhivery',
                'account_id' => $accountId,
                'environment' => $environment,
                'rate_request' => [
                    'origin_pin' => $originPin,
                    'dest_pin' => $destPin,
                    'cgm' => $chargeableGrams,
                    'actual_weight_kg' => $actualKg,
                    'billable_weight_kg' => $billableKg,
                    'client_name' => $clientName !== '' ? $clientName : null,
                    'payment_type' => $pt,
                    'cod' => $isCod ? 1 : 0,
                    'rate_api_path' => $rateApiPath !== '' ? $rateApiPath : 'kinko/v1/invoice/charges/.json',
                ],
                'responses' => $debug,
            ],
        ];
    }

    /**
     * Delhivery One portal often returns gross_amount=0 and total_amount as the estimate (e.g. 129).
     * When gross is present, prefer it (pre-tax); otherwise use total_amount.
     *
     * @param array{gross_amount:?float,total_amount:?float,charged_weight_g:?int} $breakdown
     */
    private function resolveDisplayAmount(array $breakdown): ?float
    {
        $gross = $breakdown['gross_amount'];
        $total = $breakdown['total_amount'];
        if ($gross !== null && $gross > 0) {
            return (float) $gross;
        }
        if ($total !== null && $total > 0) {
            return (float) $total;
        }
        return null;
    }

    /**
     * @param mixed $data
     * @return array{gross_amount:?float,total_amount:?float,charged_weight_g:?int}
     */
    private function parseChargeBreakdown($data, ?string $raw = null): array
    {
        $row = $this->findChargeRow($data);
        if ($row !== null) {
            return [
                'gross_amount' => isset($row['gross_amount']) && is_numeric($row['gross_amount'])
                    ? (float) $row['gross_amount']
                    : null,
                'total_amount' => isset($row['total_amount']) && is_numeric($row['total_amount'])
                    ? (float) $row['total_amount']
                    : null,
                'charged_weight_g' => isset($row['charged_weight']) && is_numeric($row['charged_weight'])
                    ? (int) $row['charged_weight']
                    : null,
            ];
        }

        $raw = trim((string) ($raw ?? (is_string($data) ? $data : '')));
        $gross = null;
        $total = null;
        if ($raw !== '') {
            if (preg_match('/<gross_amount[^>]*>([\d.]+)/i', $raw, $m)) {
                $gross = (float) $m[1];
            }
            if (preg_match('/<total_amount[^>]*>([\d.]+)/i', $raw, $m)) {
                $total = (float) $m[1];
            }
        }

        return [
            'gross_amount' => $gross,
            'total_amount' => $total,
            'charged_weight_g' => null,
        ];
    }

    /**
     * @param mixed $data
     * @return array<string, mixed>|null
     */
    private function findChargeRow($data): ?array
    {
        if (!is_array($data)) {
            return null;
        }
        if (isset($data['gross_amount']) || isset($data['total_amount'])) {
            return $data;
        }
        foreach ($data as $value) {
            if (!is_array($value)) {
                continue;
            }
            if (isset($value['gross_amount']) || isset($value['total_amount'])) {
                return $value;
            }
        }
        return null;
    }

    public function createShipment(array $request): array
    {
        return [
            'success' => false,
            'message' => 'Delhivery createShipment not implemented yet.',
        ];
    }

    /** @param list<array<string, mixed>> $accounts */
    private function pickAccount(array $accounts, int $requestedAccountId): array
    {
        if ($requestedAccountId > 0) {
            foreach ($accounts as $account) {
                if ((int) ($account['id'] ?? 0) === $requestedAccountId) {
                    return $account;
                }
            }
        }
        return $accounts[0];
    }
}
