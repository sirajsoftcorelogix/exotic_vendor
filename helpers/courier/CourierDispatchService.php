<?php

require_once __DIR__ . '/country_codes.php';
require_once __DIR__ . '/Gateway/CourierGateway.php';
require_once __DIR__ . '/Support/CourierUiFormat.php';

/**
 * Single entry point for dispatch screens (bulk + single order).
 * Controllers should call this class — not individual adapters or Shiprocket.
 */
class CourierDispatchService
{
    private CourierGateway $gateway;
    /** @var mysqli|object */
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->gateway = new CourierGateway($conn);
    }

    /**
     * Build a partner-agnostic rate request from dispatch UI / API input.
     *
     * @param array<string, mixed> $input  POST JSON from bulk_dispatch or create.php
     * @param array<string, mixed> $orderInfo  Row from vp_order_info
     * @return array<string, mixed>
     */
    public function buildRateRequest(array $input, array $orderInfo): array
    {
        $length = (float) ($input['length'] ?? 0);
        $breadth = (float) ($input['breadth'] ?? 0);
        $height = (float) ($input['height'] ?? 0);
        $weight = (float) ($input['weight'] ?? 0);

        // Bulk dispatch box sizes are in inches; volumetric formula expects cm (L×W×H÷5000).
        $lengthCm = $length * 2.54;
        $breadthCm = $breadth * 2.54;
        $heightCm = $height * 2.54;

        $destinationCountry = normalizeCountryIso2(
            $orderInfo['shipping_country'] ?? $orderInfo['country'] ?? 'IN',
            $this->conn
        );

        return [
            'order_number' => (string) ($input['order_number'] ?? $orderInfo['order_number'] ?? ''),
            'partner_code' => (string) ($input['partner_code'] ?? ''),
            'partner_account_id' => (int) ($input['partner_account_id'] ?? 0),
            'weight' => $weight,
            'chargeable_weight_kg' => CourierGateway::chargeableWeightKg($weight, $lengthCm, $breadthCm, $heightCm),
            'length' => $length,
            'breadth' => $breadth,
            'height' => $height,
            'length_cm' => $lengthCm,
            'breadth_cm' => $breadthCm,
            'height_cm' => $heightCm,
            'actual_weight_kg' => $weight,
            'cod' => (int) ($input['cod'] ?? 0),
            'destination_country' => $destinationCountry,
            'destination' => [
                'line1' => $orderInfo['shipping_address_line1'] ?? $orderInfo['address_line1'] ?? '',
                'line2' => $orderInfo['shipping_address_line2'] ?? $orderInfo['address_line2'] ?? '',
                'city' => $orderInfo['shipping_city'] ?? $orderInfo['city'] ?? '',
                'state' => $orderInfo['shipping_state'] ?? $orderInfo['state'] ?? '',
                'postcode' => $orderInfo['shipping_zipcode'] ?? $orderInfo['zipcode'] ?? '',
                'country_code' => $destinationCountry,
            ],
            'pickup_location' => (string) ($input['pickup_location'] ?? ''),
            'is_international' => isInternationalShipmentCountry($destinationCountry, $this->conn),
        ];
    }

    /**
     * @param array<string, mixed> $rateRequest
     * @return array<string, mixed>
     */
    public function getRates(array $rateRequest): array
    {
        $result = $this->gateway->getRates($rateRequest);
        if (!empty($result['success'])) {
            $result['international'] = !empty($rateRequest['is_international']);
        }
        return $result;
    }

    /**
     * @param array<string, mixed> $gatewayResult
     * @return array<string, mixed>
     */
    public function formatServiceabilityForUi(array $gatewayResult): array
    {
        return CourierUiFormat::serviceabilityResponse($gatewayResult);
    }

    public function isInternationalOrder(array $orderInfo): bool
    {
        return isInternationalShipmentCountry(
            $orderInfo['shipping_country'] ?? $orderInfo['country'] ?? 'IN',
            $this->conn
        );
    }
}
