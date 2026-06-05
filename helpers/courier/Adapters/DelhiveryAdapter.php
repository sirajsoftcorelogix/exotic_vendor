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

        $chargeableKg = (float) ($request['chargeable_weight_kg'] ?? $request['weight'] ?? 0);
        $chargeableGrams = (int) round(max(0.0, $chargeableKg) * 1000);
        if ($chargeableGrams <= 0) {
            return [
                'success' => false,
                'message' => 'Chargeable weight is required for Delhivery rates.',
            ];
        }
        // Delhivery invoice API expects weight in grams; use at least 500g for estimate calls.
        $chargeableGrams = max(500, $chargeableGrams);

        $urlInfo = resolveCourierCredentialUrls($credentials);
        $environment = (string) ($urlInfo['environment'] ?? 'sandbox');
        $baseUrlOverride = trim((string) ($urlInfo['api_base_url'] ?? ''));
        $clientName = trim((string) ($credentials['client_name'] ?? $credentials['cl'] ?? ''));
        if ($clientName === '') {
            return [
                'success' => false,
                'message' => 'Delhivery client_name is required in Courier accounts (API parameter cl).',
                'debug' => [
                    'partner' => 'delhivery',
                    'account_id' => $accountId,
                    'environment' => $environment,
                ],
            ];
        }
        $pt = !empty($request['cod']) ? 'COD' : 'Pre-paid';
        $ss = 'Delivered';

        $service = new DelhiveryService($apiKey, $environment, $baseUrlOverride);

        $quotes = [];
        $debug = [];

        foreach (['S' => 'Surface', 'E' => 'Express'] as $md => $label) {
            $params = [
                'md' => $md,
                'cgm' => $chargeableGrams,
                'o_pin' => $originPin,
                'd_pin' => $destPin,
                'ss' => $ss,
                'pt' => $pt,
                'cl' => $clientName,
            ];

            $resp = $service->estimateFreightCharges($params);
            $debug[$md] = $resp;

            if (empty($resp['success'])) {
                continue;
            }

            $price = $this->extractChargeAmount($resp['data'] ?? null, $resp['raw'] ?? null);
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
                    'chargeable_weight_g' => $chargeableGrams,
                    'origin_pin' => $originPin,
                    'dest_pin' => $destPin,
                    'payment_type' => $pt,
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
                    'chargeable_weight_g' => $chargeableGrams,
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
                'responses' => $debug,
            ],
        ];
    }

    /**
     * @param mixed $data
     */
    private function extractChargeAmount($data, ?string $raw = null): ?float
    {
        $keys = ['total_amount', 'Total_amount', 'total', 'amount', 'gross_amount', 'Gross_Amount'];

        $fromArray = static function ($node) use (&$fromArray, $keys): ?float {
            if (!is_array($node)) {
                return null;
            }
            foreach ($keys as $key) {
                if (isset($node[$key]) && is_numeric($node[$key])) {
                    return (float) $node[$key];
                }
            }
            foreach ($node as $value) {
                if (is_array($value)) {
                    $found = $fromArray($value);
                    if ($found !== null && $found > 0) {
                        return $found;
                    }
                }
            }
            return null;
        };

        $amount = $fromArray($data);
        if ($amount !== null && $amount > 0) {
            return $amount;
        }

        $raw = trim((string) ($raw ?? ''));
        if ($raw === '') {
            if (is_string($data)) {
                $raw = $data;
            } else {
                return null;
            }
        }

        if (preg_match('/<total_amount[^>]*>([\d.]+)/i', $raw, $m)) {
            return (float) $m[1];
        }
        if (preg_match('/<gross_amount[^>]*>([\d.]+)/i', $raw, $m)) {
            return (float) $m[1];
        }

        return null;
    }

    public function createShipment(array $request): array
    {
        // TODO (Delhivery): create shipment, return awb + label_url.
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
