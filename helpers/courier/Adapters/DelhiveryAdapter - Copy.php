<?php

require_once __DIR__ . '/../Contracts/CourierAdapterInterface.php';
require_once __DIR__ . '/../country_codes.php';
require_once __DIR__ . '/../Support/CourierUiFormat.php';
require_once __DIR__ . '/../../../models/courier/CourierAccount.php';
require_once __DIR__ . '/../../../models/courier/CourierShipment.php';

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
        $accounts = $this->accountModel->listActiveAccountsByPartnerCode('delhivery');
        if (!$accounts || empty($accounts)) {
            // For testing/demo: return mock data with account_id = 0
            // In production, you would require an actual account to be configured
            $mockAccountId = 0;
            $quotes = $this->fetchDelhiveryRates($request, [], $mockAccountId);
            
            if ($this->shipmentModel) {
                $this->shipmentModel->logApiCall(
                    'delhivery',
                    'get_rates',
                    $mockAccountId,
                    (string) ($request['order_number'] ?? ''),
                    $request,
                    ['quotes' => $quotes, 'note' => 'mock_data_no_account'],
                    !empty($quotes),
                    'Using mock rates (no account configured). Configure one under Courier Accounts for production.'
                );
            }
            
            return [
                'success' => true,
                'couriers' => $quotes,
                'debug' => [
                    'partner' => 'delhivery',
                    'account_id' => $mockAccountId,
                    'note' => 'No Delhivery account configured. Add one under Courier Accounts for production rates.',
                    'quotes_count' => count($quotes),
                ],
            ];
        }

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

        $accountId = (int) ($request['partner_account_id'] ?? 0);
        $accountRow = $this->pickAccount($accounts, $accountId);
        $accountId = (int) ($accountRow['id'] ?? 0);
        $credentials = $this->accountModel->getCredentialsJson($accountId);

        // Build Delhivery rate request
        $weight = (float) ($request['weight'] ?? 0);
        $chargeableWeight = (float) ($request['chargeable_weight_kg'] ?? $weight);
        $destinationPostcode = (string) ($request['destination']['postcode'] ?? '');
        $destinationCity = (string) ($request['destination']['city'] ?? '');
        
        if (empty($destinationPostcode)) {
            return [
                'success' => false,
                'message' => 'Delivery postcode is required for Delhivery serviceability check.',
            ];
        }

        // Call Delhivery API via delhivery_service.php (placeholder for now)
        // TODO: Implement actual Delhivery API call using credentials
        $quotes = $this->fetchDelhiveryRates($request, $credentials, $accountId);

        if ($this->shipmentModel) {
            $this->shipmentModel->logApiCall(
                'delhivery',
                'get_rates',
                $accountId,
                (string) ($request['order_number'] ?? ''),
                $request,
                ['quotes' => $quotes],
                !empty($quotes),
                empty($quotes) ? 'No rates returned from Delhivery' : 'Rates fetched successfully'
            );
        }

        if (empty($quotes)) {
            return [
                'success' => false,
                'message' => 'Delhivery is not serviceable for this route.',
                'debug' => [
                    'partner' => 'delhivery',
                    'account_id' => $accountId,
                    'destination_postcode' => $destinationPostcode,
                    'weight' => $weight,
                ],
            ];
        }

        return [
            'success' => true,
            'couriers' => $quotes,
            'debug' => [
                'partner' => 'delhivery',
                'account_id' => $accountId,
                'quotes_count' => count($quotes),
            ],
        ];
    }

    /**
     * Fetch Delhivery rates for the given shipment.
     * 
     * TODO: Replace with actual Delhivery API call
     */
    private function fetchDelhiveryRates(array $request, array $credentials, int $accountId): array
    {
        $weight = (float) ($request['weight'] ?? 0);
        $destinationPostcode = (string) ($request['destination']['postcode'] ?? '');
        $cod = (int) ($request['cod'] ?? 0);

        // Validate weight
        if ($weight <= 0) {
            return []; // No quotes for invalid weight
        }

        // Mock quotes for demonstration (replace with actual API call)
        $quotes = [];

        // Surface delivery - always available
        $surfacePrice = (float) $this->calculatePrice(120, $weight);
        $quotes[] = [
            'id' => 'delhivery_' . (int)$accountId . '_SURFACE',
            'name' => 'Delhivery - Surface',
            'price' => $surfacePrice,
            'currency' => 'INR',
            'etd' => '3-5 days',
            'rating' => 4.2,
            'partner_code' => 'delhivery',
            'partner_account_id' => (int)$accountId,
            'service_code' => 'SURFACE',
            'product_group' => '',
            'product_type' => 'Standard',
        ];

        // Express delivery (if weight <= 25kg)
        if ($weight <= 25) {
            $expressPrice = (float) $this->calculatePrice(250, $weight);
            $quotes[] = [
                'id' => 'delhivery_' . (int)$accountId . '_EXPRESS',
                'name' => 'Delhivery - Express',
                'price' => $expressPrice,
                'currency' => 'INR',
                'etd' => '1-2 days',
                'rating' => 4.5,
                'partner_code' => 'delhivery',
                'partner_account_id' => (int)$accountId,
                'service_code' => 'EXPRESS',
                'product_group' => '',
                'product_type' => 'Express',
            ];
        }

        // Next day delivery (if weight <= 15kg)
        if ($weight <= 15) {
            $nextdayPrice = (float) $this->calculatePrice(350, $weight);
            $quotes[] = [
                'id' => 'delhivery_' . (int)$accountId . '_NEXTDAY',
                'name' => 'Delhivery - Next Day',
                'price' => $nextdayPrice,
                'currency' => 'INR',
                'etd' => '1 day',
                'rating' => 4.6,
                'partner_code' => 'delhivery',
                'partner_account_id' => (int)$accountId,
                'service_code' => 'NEXTDAY',
                'product_group' => '',
                'product_type' => 'Next Day',
            ];
        }

        return $quotes;
    }

    /**
     * Calculate Delhivery shipping price based on base rate and weight.
     * TODO: Replace with actual Delhivery pricing logic
     */
    private function calculatePrice(float $baseRate, float $weight): float
    {
        // Simple calculation: base + (weight - 0.5kg) * surcharge
        if ($weight <= 0.5) {
            return (float) $baseRate;
        }
        $surchargePerKg = 10.0;
        $price = (float) $baseRate + (($weight - 0.5) * $surchargePerKg);
        return max(0, (float) $price); // Ensure non-negative
    }

    public function createShipment(array $request): array
    {
        $accountId = (int) ($request['partner_account_id'] ?? 0);
        $shipmentId = (string) ($request['shipment_id'] ?? 'DHC-' . time());
        $orderNumber = (string) ($request['order_number'] ?? '');
        $weight = (float) ($request['weight'] ?? 0);
        
        // Validate required fields
        if (empty($orderNumber) || $weight <= 0) {
            return [
                'success' => false,
                'message' => 'Order number and weight are required for Delhivery shipment creation.',
            ];
        }

        // Get account if configured
        $accounts = $this->accountModel->listActiveAccountsByPartnerCode('delhivery');
        if ($accounts && !empty($accounts)) {
            $accountRow = $this->pickAccount($accounts, $accountId);
            $accountId = (int) ($accountRow['id'] ?? 0);
            $credentials = $this->accountModel->getCredentialsJson($accountId);
        } else {
            $credentials = [];
        }

        // Generate mock AWB number and label URL (replace with real API call)
        $awbNumber = 'DHC' . str_pad((string) $accountId, 2, '0', STR_PAD_LEFT) . str_pad((string) time() % 100000, 5, '0', STR_PAD_LEFT);
        $labelUrl = base_url('tmp/delhivery_label_' . $awbNumber . '.pdf');

        // Log API call
        if ($this->shipmentModel) {
            $this->shipmentModel->logApiCall(
                'delhivery',
                'create_shipment',
                $accountId,
                $orderNumber,
                $request,
                [
                    'awb' => $awbNumber,
                    'label_url' => $labelUrl,
                    'status' => 'created',
                ],
                true,
                'Shipment created successfully (mock data)'
            );
        }

        return [
            'success' => true,
            'message' => 'Delhivery shipment created successfully',
            'awb' => $awbNumber,
            'awb_code' => $awbNumber,
            'label_url' => $labelUrl,
            'shipment_id' => $awbNumber,
            'tracking_url' => 'https://track.delhivery.com/?waybill=' . $awbNumber,
            'status' => 'created',
            'debug' => [
                'partner' => 'delhivery',
                'account_id' => $accountId,
                'order_number' => $orderNumber,
                'weight' => $weight,
                'note' => 'Mock shipment created. Integrate actual Delhivery API for production.',
            ],
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
