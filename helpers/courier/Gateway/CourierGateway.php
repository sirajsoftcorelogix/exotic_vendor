<?php

require_once __DIR__ . '/../country_codes.php';
require_once __DIR__ . '/../Contracts/CourierAdapterInterface.php';
require_once __DIR__ . '/../Adapters/AramexAdapter.php';
require_once __DIR__ . '/../Adapters/DelhiveryAdapter.php';
require_once __DIR__ . '/../Adapters/BlueDartAdapter.php';
require_once __DIR__ . '/../Adapters/DhlAdapter.php';
require_once __DIR__ . '/../Adapters/FedExAdapter.php';
require_once __DIR__ . '/../Adapters/UPSAdapter.php';
require_once __DIR__ . '/../../../models/courier/CourierAccount.php';
require_once __DIR__ . '/../../../models/courier/CourierShipment.php';

/**
 * Routes dispatch operations to the correct courier adapter.
 *
 * Routing rules (team agreement):
 *   Domestic (IN → IN)     → DelhiveryAdapter, BlueDartAdapter (bulk dispatch); Shiprocket legacy fallback
 *   International (≠ IN)   → AramexAdapter, DhlAdapter, FedExAdapter, UPSAdapter (single order dispatch)
 *
 * When partner_code is omitted, rates from all active adapters for that market are merged.
 */
class CourierGateway
{
    private CourierAccount $accountModel;
    private CourierShipment $shipmentModel;

    /** @var array<string, CourierAdapterInterface> */
    private array $domesticAdapters;

    /** @var array<string, CourierAdapterInterface> */
    private array $internationalAdapters;

    private AramexAdapter $aramexAdapter;

    public function __construct($conn)
    {
        $this->accountModel = new CourierAccount($conn);
        $this->shipmentModel = new CourierShipment($conn);
        $this->aramexAdapter = new AramexAdapter($this->accountModel, $this->shipmentModel);

        $this->domesticAdapters = [
            'delhivery' => new DelhiveryAdapter($this->accountModel, $this->shipmentModel),
            'bluedart' => new BlueDartAdapter($this->accountModel, $this->shipmentModel),
        ];

        $this->internationalAdapters = [
            'aramex' => $this->aramexAdapter,
            'dhl' => new DhlAdapter($this->accountModel, $this->shipmentModel),
            'fedex' => new FedExAdapter($this->accountModel, $this->shipmentModel),
            'ups' => new UPSAdapter($this->accountModel, $this->shipmentModel),
        ];
    }

    /**
     * @param array<string, mixed> $request
     * @return array{success:bool,provider?:string,couriers?:list<array<string,mixed>>,message?:string,debug?:array<string,mixed>,use_shiprocket?:bool,international?:bool,rejected_couriers?:list<array<string,mixed>>}
     */
    public function getRates(array $request): array
    {
        $destinationCountry = normalizeCountryIso2(
            $request['destination_country']
                ?? ($request['destination']['country_code'] ?? ($request['destination']['country'] ?? 'IN')),
            $GLOBALS['conn'] ?? null
        );

        if (isInternationalShipmentCountry($destinationCountry, $GLOBALS['conn'] ?? null)) {
            return $this->getInternationalRates($request);
        }

        return $this->getDomesticRates($request);
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function createShipment(array $request): array
    {
        $destinationCountry = normalizeCountryIso2(
            $request['destination_country']
                ?? ($request['destination']['country_code'] ?? 'IN'),
            $GLOBALS['conn'] ?? null
        );

        if (isInternationalShipmentCountry($destinationCountry, $GLOBALS['conn'] ?? null)) {
            return $this->createInternationalShipment($request);
        }

        return $this->createDomesticShipment($request);
    }

    /** @param array<string, mixed> $request */
    private function getDomesticRates(array $request): array
    {
        $provider = strtolower(trim((string) ($request['partner_code'] ?? '')));

        if ($provider !== '' && !isset($this->domesticAdapters[$provider])) {
            return [
                'success' => false,
                'use_shiprocket' => true,
                'message' => 'Unknown domestic provider: ' . $provider,
            ];
        }

        if ($provider !== '') {
            return $this->wrapAdapterRates($this->domesticAdapters[$provider]->getRates($request), $provider);
        }

        return $this->aggregateAdapterRates($this->domesticAdapters, $request, true);
    }

    /** @param array<string, mixed> $request */
    private function getInternationalRates(array $request): array
    {
        $provider = strtolower(trim((string) ($request['partner_code'] ?? '')));

        if ($provider !== '' && !isset($this->internationalAdapters[$provider])) {
            return [
                'success' => false,
                'message' => 'Unsupported international courier provider: ' . $provider,
            ];
        }

        if ($provider !== '') {
            $result = $this->internationalAdapters[$provider]->getRates($request);
            $result['provider'] = $provider;
            $result['international'] = true;
            return $result;
        }

        $result = $this->aggregateAdapterRates($this->internationalAdapters, $request, false);
        $result['international'] = true;
        return $result;
    }

    /** @param array<string, mixed> $request */
    private function createDomesticShipment(array $request): array
    {
        $provider = strtolower(trim((string) ($request['partner_code'] ?? 'delhivery')));
        $adapter = $this->domesticAdapters[$provider] ?? null;

        if ($adapter instanceof CourierAdapterInterface) {
            return $adapter->createShipment($request);
        }

        return [
            'success' => false,
            'use_shiprocket' => true,
            'message' => 'Domestic createShipment — use legacy Shiprocket path for provider: ' . $provider,
        ];
    }

    /** @param array<string, mixed> $request */
    private function createInternationalShipment(array $request): array
    {
        $partner = strtolower(trim((string) ($request['partner_code'] ?? 'aramex')));
        $adapter = $this->internationalAdapters[$partner] ?? null;

        if ($adapter instanceof CourierAdapterInterface) {
            return $adapter->createShipment($request);
        }

        return ['success' => false, 'message' => 'Unsupported international provider: ' . $partner];
    }

    /**
     * @param array<string, CourierAdapterInterface> $adapters
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    private function aggregateAdapterRates(array $adapters, array $request, bool $domestic): array
    {
        $couriers = [];
        $debug = [];
        $rejected = [];

        foreach ($adapters as $code => $adapter) {
            $result = $adapter->getRates($request);
            $debug[$code] = $result['debug'] ?? null;

            if (!empty($result['success']) && !empty($result['couriers'])) {
                foreach ($result['couriers'] as $quote) {
                    if (is_array($quote)) {
                        $couriers[] = $quote;
                    }
                }
                continue;
            }

            $rejected[] = [
                'partner_code' => $code,
                'message' => (string) ($result['message'] ?? 'No rates returned.'),
            ];
        }

        if ($couriers) {
            usort($couriers, static function ($a, $b) {
                return ((float) ($a['price'] ?? 0)) <=> ((float) ($b['price'] ?? 0));
            });

            return [
                'success' => true,
                'provider' => 'multi',
                'couriers' => $couriers,
                'rejected_couriers' => $rejected,
                'debug' => $debug,
            ];
        }

        if ($domestic) {
            return [
                'success' => false,
                'use_shiprocket' => true,
                'message' => 'No domestic courier rates available — using Shiprocket fallback.',
                'rejected_couriers' => $rejected,
                'debug' => $debug,
            ];
        }

        return [
            'success' => false,
            'message' => 'No international courier rates available.',
            'rejected_couriers' => $rejected,
            'debug' => $debug,
        ];
    }

    /** @param array<string, mixed> $result */
    private function wrapAdapterRates(array $result, string $provider): array
    {
        $result['provider'] = $provider;

        if (!empty($result['success'])) {
            return $result;
        }

        if ($provider === 'delhivery' || $provider === 'bluedart') {
            return [
                'success' => false,
                'use_shiprocket' => true,
                'message' => $result['message'] ?? ($provider . ' unavailable — using Shiprocket.'),
                'debug' => $result['debug'] ?? null,
            ];
        }

        return $result;
    }

    public static function chargeableWeightKg(float $actualKg, float $lengthCm, float $widthCm, float $heightCm): float
    {
        $actualKg = max(0.0, $actualKg);
        $volKg = 0.0;
        if ($lengthCm > 0 && $widthCm > 0 && $heightCm > 0) {
            $volKg = ($lengthCm * $widthCm * $heightCm) / 5000.0;
        }
        return max($actualKg, $volKg) > 0 ? max($actualKg, $volKg) : 0.0;
    }
}
