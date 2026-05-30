<?php

require_once __DIR__ . '/../Contracts/CourierAdapterInterface.php';
require_once __DIR__ . '/../country_codes.php';
require_once __DIR__ . '/../../../models/courier/CourierAccount.php';
require_once __DIR__ . '/../../../models/courier/CourierShipment.php';

/**
 * DHL Express international adapter (skeleton — implement API calls here).
 *
 * Single order dispatch (IN → abroad). Uses DhlService at repo root.
 */
class DhlAdapter implements CourierAdapterInterface
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
        return 'dhl';
    }

    public function getRates(array $request): array
    {
        $accounts = $this->accountModel->listActiveAccountsByPartnerCode('dhl');
        if (!$accounts) {
            return [
                'success' => false,
                'message' => 'No active DHL account configured. Add one under Courier accounts.',
            ];
        }

        $destination = is_array($request['destination'] ?? null) ? $request['destination'] : [];
        $destCountry = normalizeCountryIso2(
            $request['destination_country'] ?? ($destination['country_code'] ?? ''),
            $GLOBALS['conn'] ?? null
        );
        if ($destCountry === '' || $destCountry === 'IN') {
            return ['success' => false, 'message' => 'International destination country is required for DHL rates.'];
        }

        $weight = (float) ($request['chargeable_weight_kg'] ?? $request['weight'] ?? 0);
        if ($weight <= 0) {
            return ['success' => false, 'message' => 'Weight must be greater than 0.'];
        }

        $accountId = (int) ($request['partner_account_id'] ?? 0);
        $accountRow = $this->pickAccount($accounts, $accountId);
        $accountId = (int) ($accountRow['id'] ?? 0);
        $credentials = $this->accountModel->getCredentialsJson($accountId);

        // TODO (DHL): build payload with DhlService::buildRatesPayload(), call getRates(), map to couriers[].
        if ($this->shipmentModel) {
            $this->shipmentModel->logApiCall(
                'dhl',
                'get_rates',
                $accountId,
                (string) ($request['order_number'] ?? ''),
                $request,
                ['status' => 'not_implemented'],
                false,
                'DhlAdapter::getRates not implemented yet'
            );
        }

        return [
            'success' => false,
            'message' => 'DHL rates not implemented yet. Implement DhlAdapter::getRates().',
            'debug' => [
                'partner' => 'dhl',
                'account_id' => $accountId,
                'destination_country' => $destCountry,
                'weight_kg' => $weight,
                'credentials_keys' => array_keys($credentials),
            ],
        ];
    }

    public function createShipment(array $request): array
    {
        // TODO (DHL): create shipment via DhlService::createShipment().
        return [
            'success' => false,
            'message' => 'DHL createShipment not implemented yet.',
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
