<?php

require_once __DIR__ . '/Config/AlankitConfig.php';
require_once __DIR__ . '/Clients/AlankitApiClient.php';
require_once __DIR__ . '/Support/PayloadBuilder.php';
require_once __DIR__ . '/Validators/PreFlightValidator.php';
require_once __DIR__ . '/Services/EInvoiceService.php';

use Integrations\Alankit\Config\AlankitConfig;
use Integrations\Alankit\Services\EInvoiceService;
use Integrations\Alankit\Validators\PreFlightValidator;

/**
 * Modern Alankit Gateway Facade for E-Invoice (IRN) and E-Way Bill (EWB) integration.
 *
 * Example usage:
 * $alankit = AlankitGateway::create();
 * $result = $alankit->generateIrn($invoiceId, $invoice, $items, $customer, $firm);
 */
class AlankitGateway
{
    private mysqli $db;
    private AlankitConfig $config;
    private EInvoiceService $service;

    public function __construct(mysqli $db, AlankitConfig $config)
    {
        $this->db = $db;
        $this->config = $config;
        $this->service = new EInvoiceService($db, $config);
    }

    /**
     * Create gateway instance from database connection and config.
     */
    public static function create(?mysqli $db = null, ?AlankitConfig $config = null): self
    {
        global $conn;

        $db = $db ?? $conn;
        if (!$db instanceof mysqli) {
            throw new RuntimeException('AlankitGateway requires an active mysqli database connection.');
        }

        $config = $config ?? AlankitConfig::fromAppConfig();

        return new self($db, $config);
    }

    public function getConfig(): AlankitConfig
    {
        return $this->config;
    }

    /**
     * Pre-flight validate invoice data against GST schema rules.
     *
     * @return array{valid:bool, errors:list<string>}
     */
    public function validateRequest(
        array $invoice,
        array $items,
        array $customer,
        array $firm
    ): array {
        return PreFlightValidator::validateIrnRequest($invoice, $items, $customer, $firm);
    }

    /**
     * Generate E-Invoice IRN (and optional E-Way Bill).
     *
     * @param int $invoiceId
     * @param array<string, mixed> $invoice
     * @param list<array<string, mixed>> $items
     * @param array<string, mixed> $customer
     * @param array<string, mixed> $firm
     * @param array<string, mixed> $ewbData
     * @return array<string, mixed>
     */
    public function generateIrn(
        int $invoiceId,
        array $invoice,
        array $items,
        array $customer,
        array $firm,
        array $ewbData = []
    ): array {
        return $this->service->generateIrn($invoiceId, $invoice, $items, $customer, $firm, $ewbData);
    }
}
