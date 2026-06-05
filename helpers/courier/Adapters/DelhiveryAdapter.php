<?php

require_once __DIR__ . '/../Contracts/CourierAdapterInterface.php';
require_once __DIR__ . '/../country_codes.php';
require_once __DIR__ . '/../Support/CourierUiFormat.php';
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
            return $this->demoRatesResponse(
                $request,
                0,
                'Demo rates shown until a Delhivery courier account is configured.'
            );
        }

        $accountId = (int) ($request['partner_account_id'] ?? 0);
        $accountRow = $this->pickAccount($accounts, $accountId);
        $accountId = (int) ($accountRow['id'] ?? 0);
        $credentials = $this->accountModel->getCredentialsJson($accountId);

        $apiKey = trim((string) ($credentials['api_key'] ?? $credentials['token'] ?? $credentials['api_token'] ?? ''));
        if ($apiKey === '') {
            return $this->demoRatesResponse(
                $request,
                $accountId,
                'Demo rates shown until Delhivery api_token is saved in Courier accounts.'
            );
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

        $environment = (string) ($credentials['environment'] ?? 'sandbox');
        $clientName = trim((string) ($credentials['client_name'] ?? $credentials['cl'] ?? ''));
        $baseUrlOverride = '';
        if ($environment === 'sandbox') {
            $baseUrlOverride = trim((string) ($credentials['sandbox_api_base_url'] ?? ''));
        } else {
            $baseUrlOverride = trim((string) ($credentials['production_api_base_url'] ?? ''));
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
            ];
            if ($clientName !== '') {
                $params['cl'] = $clientName;
            }

            $resp = $service->estimateFreightCharges($params);
            $debug[$md] = $resp;

            if (empty($resp['success'])) {
                continue;
            }

            $data = $resp['data'] ?? null;
            $amount = null;
            if (is_array($data)) {
                // Different Delhivery responses exist; attempt common keys.
                foreach (['total_amount', 'Total_amount', 'total', 'amount', 'gross_amount'] as $k) {
                    if (isset($data[$k])) {
                        $amount = $data[$k];
                        break;
                    }
                }
                if ($amount === null && isset($data['data']) && is_array($data['data'])) {
                    foreach (['total_amount', 'Total_amount', 'total', 'amount', 'gross_amount'] as $k) {
                        if (isset($data['data'][$k])) {
                            $amount = $data['data'][$k];
                            break;
                        }
                    }
                }
            }
            $price = is_numeric($amount) ? (float) $amount : null;
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
            return $this->demoRatesResponse(
                $request,
                $accountId,
                'Live Delhivery API returned no rates — showing demo quotes for now.',
                ['partner' => 'delhivery', 'account_id' => $accountId, 'environment' => $environment, 'response' => $debug]
            );
        }

        return [
            'success' => true,
            'provider' => 'delhivery',
            'is_demo' => false,
            'couriers' => CourierUiFormat::formatQuotes($quotes),
            'debug' => [
                'partner' => 'delhivery',
                'account_id' => $accountId,
                'environment' => $environment,
                'responses' => $debug,
            ],
        ];
    }

    /** @param array<string, mixed> $request */
    private function demoRatesResponse(array $request, int $accountId, string $message, ?array $extraDebug = null): array
    {
        return [
            'success' => true,
            'provider' => 'delhivery',
            'is_demo' => true,
            'demo_message' => $message,
            'message' => $message,
            'couriers' => CourierUiFormat::formatQuotes($this->buildDemoQuotes($request, $accountId)),
            'debug' => array_merge(['partner' => 'delhivery', 'demo' => true], $extraDebug ?? []),
        ];
    }

    /** @param array<string, mixed> $request
     *  @return list<array<string, mixed>>
     */
    private function buildDemoQuotes(array $request, int $accountId): array
    {
        $weight = (float) ($request['chargeable_weight_kg'] ?? $request['weight'] ?? 0);
        if ($weight <= 0) {
            $weight = 0.5;
        }

        $quotes = [
            [
                'id' => 'delhivery_' . $accountId . '_SURFACE_DEMO',
                'name' => 'Delhivery - Surface',
                'price' => $this->calculateDemoPrice(120.0, $weight),
                'currency' => 'INR',
                'etd' => '3-5 days',
                'rating' => 4.2,
                'partner_code' => 'delhivery',
                'partner_account_id' => $accountId,
                'service_code' => 'SURFACE',
                'metadata' => ['is_demo' => true],
            ],
        ];

        if ($weight <= 25) {
            $quotes[] = [
                'id' => 'delhivery_' . $accountId . '_EXPRESS_DEMO',
                'name' => 'Delhivery - Express',
                'price' => $this->calculateDemoPrice(250.0, $weight),
                'currency' => 'INR',
                'etd' => '1-2 days',
                'rating' => 4.5,
                'partner_code' => 'delhivery',
                'partner_account_id' => $accountId,
                'service_code' => 'EXPRESS',
                'metadata' => ['is_demo' => true],
            ];
        }

        if ($weight <= 15) {
            $quotes[] = [
                'id' => 'delhivery_' . $accountId . '_NEXTDAY_DEMO',
                'name' => 'Delhivery - Next Day',
                'price' => $this->calculateDemoPrice(350.0, $weight),
                'currency' => 'INR',
                'etd' => '1 day',
                'rating' => 4.6,
                'partner_code' => 'delhivery',
                'partner_account_id' => $accountId,
                'service_code' => 'NEXTDAY',
                'metadata' => ['is_demo' => true],
            ];
        }

        return $quotes;
    }

    private function calculateDemoPrice(float $baseRate, float $weight): float
    {
        if ($weight <= 0.5) {
            return round($baseRate, 2);
        }

        return round($baseRate + (($weight - 0.5) * 10.0), 2);
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
