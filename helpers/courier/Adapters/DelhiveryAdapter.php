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
        if (!$accounts) {
            return [
                'success' => false,
                'message' => 'No active Delhivery account configured. Add one under Courier accounts.',
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

        // TODO (Delhivery): call Delhivery rate / serviceability API and map to $quotes[].
        // Example quote shape (required for UI):
        // [
        //   'id' => 'delhivery_' . $accountId . '_SURFACE',
        //   'name' => 'Delhivery Surface',
        //   'price' => 120.00,
        //   'currency' => 'INR',
        //   'etd' => '3-5 days',
        //   'rating' => 4.2,
        //   'partner_code' => 'delhivery',
        //   'partner_account_id' => $accountId,
        //   'service_code' => 'SURFACE',
        // ]

        if ($this->shipmentModel) {
            $this->shipmentModel->logApiCall(
                'delhivery',
                'get_rates',
                $accountId,
                (string) ($request['order_number'] ?? ''),
                $request,
                ['status' => 'not_implemented'],
                false,
                'DelhiveryAdapter::getRates not implemented yet'
            );
        }

        return [
            'success' => false,
            'message' => 'Delhivery rates not implemented yet. Implement DelhiveryAdapter::getRates().',
            'debug' => [
                'partner' => 'delhivery',
                'account_id' => $accountId,
                'credentials_keys' => array_keys($credentials),
                'request' => $request,
            ],
        ];
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
