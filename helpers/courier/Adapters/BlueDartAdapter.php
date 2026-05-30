<?php

require_once __DIR__ . '/../Contracts/CourierAdapterInterface.php';
require_once __DIR__ . '/../country_codes.php';
require_once __DIR__ . '/../../../models/courier/CourierAccount.php';
require_once __DIR__ . '/../../../models/courier/CourierShipment.php';

/**
 * Blue Dart domestic adapter (skeleton — implement API calls here).
 *
 * Bulk dispatch (IN → IN). Credentials in courier_partner_accounts.credentials_json (partner_code = bluedart).
 */
class BlueDartAdapter implements CourierAdapterInterface
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
        return 'bluedart';
    }

    public function getRates(array $request): array
    {
        $accounts = $this->accountModel->listActiveAccountsByPartnerCode('bluedart');
        if (!$accounts) {
            return [
                'success' => false,
                'message' => 'No active Blue Dart account configured. Add one under Courier accounts.',
            ];
        }

        $destinationCountry = normalizeCountryIso2(
            $request['destination_country']
                ?? ($request['destination']['country_code'] ?? 'IN'),
            $GLOBALS['conn'] ?? null
        );
        if (isInternationalShipmentCountry($destinationCountry, $GLOBALS['conn'] ?? null)) {
            return [
                'success' => false,
                'message' => 'Blue Dart adapter is domestic only (IN → IN).',
            ];
        }

        $accountId = (int) ($request['partner_account_id'] ?? 0);
        $accountRow = $this->pickAccount($accounts, $accountId);
        $accountId = (int) ($accountRow['id'] ?? 0);
        $credentials = $this->accountModel->getCredentialsJson($accountId);

        // TODO (Blue Dart): call pincode serviceability / rate API via BlueDartService.
        if ($this->shipmentModel) {
            $this->shipmentModel->logApiCall(
                'bluedart',
                'get_rates',
                $accountId,
                (string) ($request['order_number'] ?? ''),
                $request,
                ['status' => 'not_implemented'],
                false,
                'BlueDartAdapter::getRates not implemented yet'
            );
        }

        return [
            'success' => false,
            'message' => 'Blue Dart rates not implemented yet. Implement BlueDartAdapter::getRates().',
            'debug' => [
                'partner' => 'bluedart',
                'account_id' => $accountId,
                'credentials_keys' => array_keys($credentials),
                'request' => $request,
            ],
        ];
    }

    public function createShipment(array $request): array
    {
        // TODO (Blue Dart): create waybill via BlueDartService; return awb + label_url.
        return [
            'success' => false,
            'message' => 'Blue Dart createShipment not implemented yet.',
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
