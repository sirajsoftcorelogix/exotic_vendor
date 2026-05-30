<?php

require_once __DIR__ . '/../Contracts/CourierAdapterInterface.php';
require_once __DIR__ . '/../country_codes.php';
require_once __DIR__ . '/../../../aramex_service.php';
require_once __DIR__ . '/../../../models/courier/CourierAccount.php';
require_once __DIR__ . '/../../../models/courier/CourierShipment.php';

class AramexAdapter implements CourierAdapterInterface
{
    private CourierAccount $accountModel;
    private ?CourierShipment $shipmentModel;

    /** @var list<string> */
    private array $fallbackProductTypes = ['PPX', 'PDX', 'GPX', 'EPX'];

    public function __construct(CourierAccount $accountModel, ?CourierShipment $shipmentModel = null)
    {
        $this->accountModel = $accountModel;
        $this->shipmentModel = $shipmentModel;
    }

    public function partnerCode(): string
    {
        return 'aramex';
    }

    public function createShipment(array $request): array
    {
        $accountId = (int) ($request['partner_account_id'] ?? 0);
        $credentials = $this->accountModel->getCredentialsJson($accountId);
        $service = new AramexService($credentials);
        return $service->createInternationalShipment($request);
    }

    /**
     * @param array<string, mixed> $request
     * @return array{success:bool,couriers?:list<array<string,mixed>>,message?:string,debug?:array<string,mixed>}
     */
    public function getRates(array $request): array
    {
        $accounts = $this->accountModel->listActiveAccountsByPartnerCode('aramex');
        if (!$accounts) {
            return [
                'success' => false,
                'message' => 'No active Aramex account configured. Add one under Courier accounts.',
            ];
        }

        $requestedAccountId = (int) ($request['partner_account_id'] ?? 0);
        $accountRow = $this->pickAccount($accounts, $requestedAccountId);
        $accountId = (int) ($accountRow['id'] ?? 0);
        $credentials = $this->accountModel->getCredentialsJson($accountId);
        if (trim((string) ($credentials['account_number'] ?? '')) === '') {
            return ['success' => false, 'message' => 'Aramex account number is missing in credentials.'];
        }

        $destination = is_array($request['destination'] ?? null) ? $request['destination'] : [];
        $destCountry = normalizeCountryIso2($destination['country_code'] ?? $destination['country'] ?? '');
        if ($destCountry === '' || $destCountry === 'IN') {
            return ['success' => false, 'message' => 'International destination country is required for Aramex rates.'];
        }

        $weight = (float) ($request['chargeable_weight_kg'] ?? $request['weight'] ?? 0);
        if ($weight <= 0) {
            return ['success' => false, 'message' => 'Weight must be greater than 0.'];
        }

        $origin = $this->buildOriginAddress($credentials);
        $destAddress = $this->buildDestinationAddress($destination, $destCountry);
        $productGroup = (string) ($credentials['default_product_group'] ?? 'EXP');
        $productTypes = $this->productTypesToTry($credentials);

        $service = new AramexService($credentials);
        $couriers = [];
        $attempts = [];
        $orderNumber = (string) ($request['order_number'] ?? '');

        foreach ($productTypes as $productType) {
            $rateOptions = [
                'product_group' => $productGroup,
                'product_type' => $productType,
            ];
            $response = $service->calculateRate($origin, $destAddress, $weight, $rateOptions);
            $attempts[] = [
                'product_group' => $productGroup,
                'product_type' => $productType,
                'response' => $response,
            ];

            if ($this->shipmentModel) {
                $this->shipmentModel->logApiCall(
                    'aramex',
                    'calculate_rate',
                    $accountId,
                    $orderNumber !== '' ? $orderNumber : null,
                    [
                        'origin' => $origin,
                        'destination' => $destAddress,
                        'weight' => $weight,
                        'options' => $rateOptions,
                    ],
                    $response,
                    !empty($response['success']),
                    !empty($response['success']) ? null : (string) ($response['error'] ?? 'Rate failed')
                );
            }

            if (empty($response['success'])) {
                continue;
            }

            $quote = $this->parseRateQuote($response['data'] ?? null, $productGroup, $productType);
            if ($quote === null) {
                continue;
            }

            $courierId = 'aramex_' . $accountId . '_' . $productGroup . '_' . $productType;
            $couriers[] = [
                'id' => $courierId,
                'name' => 'Aramex ' . $productType . ' (' . ($accountRow['account_code'] ?? $credentials['account_number']) . ')',
                'price' => $quote['amount'],
                'currency' => $quote['currency'],
                'etd' => $quote['etd'],
                'rating' => 4.5,
                'partner_code' => 'aramex',
                'partner_account_id' => $accountId,
                'product_group' => $productGroup,
                'product_type' => $productType,
                'account_number' => (string) ($credentials['account_number'] ?? ''),
            ];
        }

        if (!$couriers) {
            return [
                'success' => false,
                'message' => 'Aramex returned no rates for this route. Check credentials, ProductType, or destination.',
                'debug' => [
                    'partner' => 'aramex',
                    'account_id' => $accountId,
                    'origin' => $origin,
                    'destination' => $destAddress,
                    'weight_kg' => $weight,
                    'attempts' => $attempts,
                ],
            ];
        }

        usort($couriers, static function ($a, $b) {
            return ((float) $a['price']) <=> ((float) $b['price']);
        });

        return [
            'success' => true,
            'couriers' => $couriers,
            'debug' => [
                'partner' => 'aramex',
                'account_id' => $accountId,
                'origin' => $origin,
                'destination' => $destAddress,
                'weight_kg' => $weight,
                'attempts' => $attempts,
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

    /** @return list<string> */
    private function productTypesToTry(array $credentials): array
    {
        $types = [];
        $default = strtoupper(trim((string) ($credentials['default_product_type'] ?? '')));
        if ($default !== '') {
            $types[] = $default;
        }
        foreach ($this->fallbackProductTypes as $type) {
            if (!in_array($type, $types, true)) {
                $types[] = $type;
            }
        }
        return $types;
    }

    /** @param array<string, mixed> $credentials */
    private function buildOriginAddress(array $credentials): array
    {
        $shipper = is_array($credentials['shipper'] ?? null) ? $credentials['shipper'] : [];
        return [
            'Line1' => (string) ($shipper['line1'] ?? ''),
            'Line2' => (string) ($shipper['line2'] ?? ''),
            'City' => (string) ($shipper['city'] ?? 'Delhi'),
            'StateOrProvinceCode' => (string) ($shipper['state'] ?? ''),
            'PostCode' => (string) ($shipper['postcode'] ?? '110052'),
            'CountryCode' => normalizeCountryIso2($shipper['country_code'] ?? 'IN'),
        ];
    }

    /** @param array<string, mixed> $destination */
    private function buildDestinationAddress(array $destination, string $countryCode): array
    {
        return [
            'Line1' => (string) ($destination['line1'] ?? $destination['address_line1'] ?? ''),
            'Line2' => (string) ($destination['line2'] ?? $destination['address_line2'] ?? ''),
            'City' => trim((string) ($destination['city'] ?? $destination['shipping_city'] ?? '')),
            'StateOrProvinceCode' => (string) ($destination['state'] ?? $destination['shipping_state'] ?? ''),
            'PostCode' => (string) ($destination['postcode'] ?? $destination['zipcode'] ?? $destination['shipping_zipcode'] ?? ''),
            'CountryCode' => $countryCode,
        ];
    }

    /** @return array{amount:float,currency:string,etd:string}|null */
    private function parseRateQuote($data, string $productGroup, string $productType): ?array
    {
        if ($data === null) {
            return null;
        }
        $arr = json_decode(json_encode($data), true);
        if (!is_array($arr)) {
            return null;
        }

        $amount = null;
        $currency = 'USD';
        if (isset($arr['TotalAmount']['Value'])) {
            $amount = (float) $arr['TotalAmount']['Value'];
            $currency = (string) ($arr['TotalAmount']['CurrencyCode'] ?? $currency);
        } elseif (isset($arr['TotalAmount'])) {
            $amount = (float) $arr['TotalAmount'];
        }

        if ($amount === null) {
            $amount = $this->findFirstNumeric($arr, ['Amount', 'Value', 'Total', 'Price']);
            if ($amount === null) {
                return null;
            }
        }

        $etd = 'N/A';
        foreach (['DeliveryDate', 'ExpectedDeliveryDate', 'EstimatedDeliveryDate'] as $key) {
            if (!empty($arr[$key])) {
                $etd = is_array($arr[$key]) ? json_encode($arr[$key]) : (string) $arr[$key];
                break;
            }
        }

        return [
            'amount' => round($amount, 2),
            'currency' => strtoupper($currency !== '' ? $currency : 'USD'),
            'etd' => $productGroup . '/' . $productType . ' · ' . $etd,
        ];
    }

    /** @param array<string, mixed> $arr */
    private function findFirstNumeric(array $arr, array $keys): ?float
    {
        foreach ($keys as $key) {
            if (isset($arr[$key]) && is_numeric($arr[$key])) {
                return (float) $arr[$key];
            }
        }
        foreach ($arr as $value) {
            if (is_array($value)) {
                $found = $this->findFirstNumeric($value, $keys);
                if ($found !== null) {
                    return $found;
                }
            }
        }
        return null;
    }
}
