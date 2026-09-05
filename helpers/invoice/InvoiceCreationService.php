<?php

require_once __DIR__ . '/InvoiceRequestBuilder.php';
require_once __DIR__ . '/../invoice_number_resolver.php';
require_once __DIR__ . '/../../models/product/product.php';
require_once __DIR__ . '/../../models/order/stock.php';

class InvoiceCreationService
{
    private mysqli $conn;

    /** @var POSInvoice|Invoice */
    private $invoiceModel;

    /** @var Order|POSOrder|null */
    private $ordersModel;

    private $commanModel;

    public function __construct(mysqli $conn, $invoiceModel, $ordersModel = null, $commanModel = null)
    {
        $this->conn = $conn;
        $this->invoiceModel = $invoiceModel;
        $this->ordersModel = $ordersModel;
        $this->commanModel = $commanModel;
    }

    /**
     * @param array<string, mixed> $post
     * @param array<string, mixed> $options
     */
    public function createFromPost(array $post, array $options = []): array
    {
        return $this->create(InvoiceRequestBuilder::fromPost($post, $options));
    }

    /**
     * @param array<string, mixed> $request
     */
    public function create(array $request): array
    {
        $header = is_array($request['header'] ?? null) ? $request['header'] : [];
        $lines = is_array($request['lines'] ?? null) ? $request['lines'] : [];
        $options = is_array($request['options'] ?? null) ? $request['options'] : [];
        $orderNumbers = is_array($request['order_numbers'] ?? null) ? $request['order_numbers'] : [];

        $customerId = (int)($header['customer_id'] ?? 0);
        if ($customerId <= 0 || $lines === []) {
            return ['success' => false, 'message' => 'Invalid parameters'];
        }

        // Validate High Value Transaction Compliance (PAN / Passport / GSTIN) for Offline / Cash Payments
        $requiresCompliance = $this->isOfflineCashPayment($header, $request, $orderNumbers);
        if ($requiresCompliance) {
            require_once __DIR__ . '/../compliance/HighValueComplianceValidator.php';
            $invoiceTotal = (float)($header['total_amount'] ?? 0);
            $gstin = $this->resolveOrderGstin($header, $orderNumbers);
            if ($gstin !== '') {
                $header['gstin'] = $gstin;
            }
            $complianceEval = HighValueComplianceValidator::validateCustomerCompliance($this->conn, $customerId, $invoiceTotal, $header);
            if (empty($complianceEval['ok'])) {
                return [
                    'success' => false,
                    'require_compliance' => true,
                    'compliance_code' => $complianceEval['code'] ?? 'COMPLIANCE_REQUIRED',
                    'customer_id' => $customerId,
                    'customer_name' => $complianceEval['customer_name'] ?? '',
                    'invoice_total' => $invoiceTotal,
                    'limit' => $complianceEval['limit'] ?? 200000.00,
                    'missing_fields' => $complianceEval['missing_fields'] ?? [],
                    'gstin' => $complianceEval['gstin'] ?? $gstin,
                    'pan' => $complianceEval['pan'] ?? '',
                    'residency_status' => $complianceEval['residency_status'] ?? '',
                    'message' => $complianceEval['message'] ?? 'High value transaction compliance document (PAN/Passport/GSTIN) is required before creating the tax invoice.',
                ];
            }
        }

        if (!empty($options['duplicate_order_check'])) {
            foreach ($orderNumbers as $orderNumber) {
                $existing = $this->invoiceModel->getActiveInvoiceForOrderNumber($orderNumber);
                if ($existing) {
                    return [
                        'success' => false,
                        'message' => 'Invoice already exists for Order Number: ' . $orderNumber,
                    ];
                }
            }
        }

        $currency = (string)($header['currency'] ?? 'INR');
        foreach ($lines as $line) {
            $lineCurrency = (string)($line['currency'] ?? $currency);
            if ($lineCurrency !== $currency) {
                return ['success' => false, 'message' => 'All items must have the same currency'];
            }
        }

        $customInvoiceNumber = trim((string)($options['custom_invoice_number'] ?? ''));
        $reserved = is_array($options['reserved_invoice_numbers'] ?? null)
            ? $options['reserved_invoice_numbers']
            : [];
        $numberResult = resolve_invoice_number($this->conn, $customInvoiceNumber, $reserved);
        if (empty($numberResult['success'])) {
            return [
                'success' => false,
                'message' => (string)($numberResult['message'] ?? 'Invalid invoice number.'),
            ];
        }
        $invoiceNumber = (string)$numberResult['invoice_number'];

        $invoiceData = [
            'invoice_number' => $invoiceNumber,
            'invoice_date' => (string)($header['invoice_date'] ?? date('Y-m-d')),
            'customer_id' => $customerId,
            'vp_order_info_id' => (int)($header['vp_order_info_id'] ?? 0),
            'currency' => $currency,
            'subtotal' => (float)($header['subtotal'] ?? 0),
            'tax_amount' => (float)($header['tax_amount'] ?? 0),
            'discount_amount' => (float)($header['discount_amount'] ?? 0),
            'total_amount' => (float)($header['total_amount'] ?? 0),
            'status' => (string)($header['status'] ?? 'final'),
            'created_by' => (int)($header['created_by'] ?? 0),
            'created_at' => date('Y-m-d H:i:s'),
            'pos_flag' => (int)($header['pos_flag'] ?? 1),
            'batch_no' => (string)($header['batch_no'] ?? ''),
            'exchange_text' => '',
            'converted_amount' => 0.0,
        ];

        if ($currency !== '' && $currency !== 'INR') {
            $currencyRecord = $this->getCurrencyByCode($currency);
            if ($currencyRecord) {
                $exchangeRate = (float)($currencyRecord['rate_export'] ?? 1);
                $invoiceData['converted_amount'] = (float)$invoiceData['total_amount'] * $exchangeRate;
                $invoiceData['exchange_text'] = 'Exchange Rate ('
                    . ($currencyRecord['currency_unit'] ?? $currency)
                    . ' to INR): '
                    . number_format($exchangeRate, 6);
            }
        }

        $invoiceId = (int)$this->invoiceModel->createInvoice($invoiceData);
        if ($invoiceId <= 0) {
            return ['success' => false, 'message' => 'Failed to create invoice'];
        }

        $productModel = new Product($this->conn);
        $itemCreated = 0;
        $itemsFailed = [];

        foreach ($lines as $line) {
            $qty = (int)($line['quantity'] ?? 0);
            $unitPrice = (float)($line['unit_price'] ?? 0);
            $taxRate = (float)($line['tax_rate'] ?? 0);
            $amount = $qty * $unitPrice;
            $cgstRate = (float)($line['cgst_rate'] ?? 0);
            $sgstRate = (float)($line['sgst_rate'] ?? 0);
            $igstRate = (float)($line['igst_rate'] ?? 0);

            $itemData = [
                'invoice_id' => $invoiceId,
                'order_number' => (string)($line['order_number'] ?? ''),
                'item_code' => (string)($line['item_code'] ?? ''),
                'item_name' => (string)($line['item_name'] ?? ''),
                'image_url' => (string)($line['image_url'] ?? ''),
                'description' => (string)($line['description'] ?? ''),
                'box_no' => (string)($line['box_no'] ?? ''),
                'hsn' => (string)($line['hsn'] ?? ''),
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'tax_rate' => $taxRate,
                'cgst' => ($amount * $cgstRate) / 100,
                'sgst' => ($amount * $sgstRate) / 100,
                'igst' => ($amount * $igstRate) / 100,
                'tax_amount' => ($amount * $taxRate) / 100,
                'line_total' => (float)($line['line_total'] ?? ($amount + (($amount * $taxRate) / 100))),
                'groupname' => (string)($line['groupname'] ?? ''),
            ];

            if (method_exists($productModel, 'getProductIdForInvoiceLine')) {
                $size = (string)($line['size'] ?? '');
                $color = (string)($line['color'] ?? '');
                if ($size !== '' || $color !== '') {
                    $itemData['product_id'] = $productModel->getProductIdForInvoiceLine(
                        (string)$itemData['order_number'],
                        (string)$itemData['item_code'],
                        $size,
                        $color
                    );
                } else {
                    $itemData['product_id'] = $productModel->getProductIdForInvoiceLine(
                        (string)$itemData['order_number'],
                        (string)$itemData['item_code']
                    );
                }
            }

            if ($this->invoiceModel->createInvoiceItem($itemData)) {
                $itemCreated++;
            } else {
                $itemsFailed[] = (string)$itemData['order_number'];
            }
        }

        $stockModel = new Stock($this->conn);
        $stockResult = $stockModel->applyInvoiceStockOnCreate($invoiceId, (string)$invoiceData['status']);
        if (empty($stockResult['success'])) {
            return [
                'success' => false,
                'message' => 'Invoice saved but stock update failed: ' . ($stockResult['message'] ?? 'Unknown error'),
                'invoice_id' => $invoiceId,
                'invoice_number' => $invoiceNumber,
                'items_created' => $itemCreated,
                'items_failed' => $itemsFailed,
            ];
        }

        $international = $request['international'] ?? null;
        if (is_array($international) && method_exists($this->invoiceModel, 'insert_international_invoice_data')) {
            $international['invoice_id'] = $invoiceId;
            $this->invoiceModel->insert_international_invoice_data($international);
        }

        $discountMeta = $request['discount_meta'] ?? null;
        $lineItemsMeta = is_array($request['line_items_meta'] ?? null) ? $request['line_items_meta'] : [];
        if (is_array($discountMeta) && method_exists($this->invoiceModel, 'updateInvoiceNotes')) {
            $this->persistPosDiscountNotes($invoiceId, $discountMeta, $lineItemsMeta, $invoiceData);
        }

        if (!empty($options['update_order_invoice_id']) && $this->ordersModel !== null) {
            $updateBy = (string)($options['update_order_by'] ?? 'order_number');
            if ($updateBy === 'vp_order_id') {
                foreach ($lines as $line) {
                    $orderId = (int)($line['vp_order_id'] ?? 0);
                    if ($orderId > 0 && method_exists($this->ordersModel, 'updateOrderById')) {
                        $this->ordersModel->updateOrderById($orderId, ['invoice_id' => $invoiceId]);
                    }
                }
            } else {
                foreach ($orderNumbers as $orderNumber) {
                    if (method_exists($this->ordersModel, 'updateOrderByOrderNumber')) {
                        $this->ordersModel->updateOrderByOrderNumber($orderNumber, ['invoice_id' => $invoiceId]);
                    }
                }
            }
        }

        if (!empty($options['clear_invoice_session'])) {
            unset($_SESSION['invoice_items']);
        }

        return [
            'success' => true,
            'message' => 'Invoice created with ' . $itemCreated . ' items',
            'invoice_id' => $invoiceId,
            'invoice_number' => $invoiceNumber,
            'items_created' => $itemCreated,
            'items_failed' => $itemsFailed,
        ];
    }

    /**
     * @param array<string, mixed> $discountMeta
     * @param list<array<string, mixed>> $lineItemsMeta
     * @param array<string, mixed> $invoiceData
     */
    private function persistPosDiscountNotes(
        int $invoiceId,
        array $discountMeta,
        array $lineItemsMeta,
        array $invoiceData
    ): void {
        $discountMeta['gst_total'] = round(
            (float)($discountMeta['gst_total'] ?? $invoiceData['tax_amount'] ?? 0),
            2
        );
        $discountMeta['grand_total'] = round(
            (float)($discountMeta['grand_total'] ?? $invoiceData['total_amount'] ?? 0),
            2
        );
        if (empty($discountMeta['subtotal_goods']) && $discountMeta['grand_total'] > 0) {
            $discountMeta['subtotal_goods'] = $discountMeta['grand_total'];
        }
        if (!isset($discountMeta['discounts_absorbed']) || $discountMeta['discounts_absorbed'] === '') {
            $discountMeta['discounts_absorbed'] = true;
        }

        $payload = [
            'pos_discounts' => [
                'subtotal_goods' => round((float)($discountMeta['subtotal_goods'] ?? 0), 2),
                'gst_total' => round((float)($discountMeta['gst_total'] ?? 0), 2),
                'coupon_discount' => round((float)($discountMeta['coupon_discount'] ?? 0), 2),
                'cash_discount' => round((float)($discountMeta['cash_discount'] ?? 0), 2),
                'gift_discount' => round((float)($discountMeta['gift_discount'] ?? 0), 2),
                'line_discount' => round((float)($discountMeta['line_discount'] ?? 0), 2),
                'grand_total' => round((float)($discountMeta['grand_total'] ?? 0), 2),
                'discounts_absorbed' => !empty($discountMeta['discounts_absorbed']),
                'custom_discount_mode' => trim((string)($discountMeta['custom_discount_mode'] ?? '')),
                'custom_discount_value' => round((float)($discountMeta['custom_discount_value'] ?? 0), 2),
                'coupon_display_name' => trim((string)($discountMeta['coupon_display_name'] ?? '')),
            ],
        ];
        if (array_key_exists('apply_export_gst', $discountMeta)) {
            $payload['pos_discounts']['apply_export_gst'] = !empty($discountMeta['apply_export_gst']) ? 1 : 0;
        }
        if ($lineItemsMeta !== []) {
            $payload['line_items'] = $lineItemsMeta;
        }

        $hasSummary = ($payload['pos_discounts']['subtotal_goods'] > 0)
            || ($payload['pos_discounts']['coupon_discount'] > 0)
            || ($payload['pos_discounts']['cash_discount'] > 0)
            || ($payload['pos_discounts']['gift_discount'] > 0)
            || ($payload['pos_discounts']['line_discount'] > 0)
            || ($payload['pos_discounts']['grand_total'] > 0)
            || $lineItemsMeta !== [];
        if (!$hasSummary) {
            return;
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return;
        }

        $this->invoiceModel->updateInvoiceNotes($invoiceId, $json);
    }

    private function getCurrencyByCode(string $code): ?array
    {
        if ($this->commanModel === null) {
            return null;
        }

        $row = $this->commanModel->getRecordByField('currency_master', 'currency_code', strtoupper($code));

        return is_array($row) ? $row : null;
    }

    /**
     * Resolve effective payment mode for high-value compliance check.
     * Offline/cash transactions require compliance; online payments and COD (courier collected) bypass.
     *
     * @param array<string, mixed> $header
     * @param array<string, mixed> $request
     * @param list<string> $orderNumbers
     */
    private function isOfflineCashPayment(array $header, array $request, array $orderNumbers): bool
    {
        $rawMode = strtolower(trim((string)($header['payment_mode'] ?? $header['payment_type'] ?? $request['payment_mode'] ?? '')));

        if ($rawMode === '' && $this->ordersModel && method_exists($this->ordersModel, 'getAddressInfoByOrderNumber')) {
            foreach ($orderNumbers as $orderNumber) {
                $info = $this->ordersModel->getAddressInfoByOrderNumber((string)$orderNumber);
                if (is_array($info)) {
                    $rawMode = strtolower(trim((string)($info['payment_mode'] ?? $info['payment_type'] ?? '')));
                    if ($rawMode !== '') {
                        break;
                    }
                }
            }
        }

        // Online payment modes (Razorpay, UPI, Bank Transfer, Card, etc.) and COD are exempt from local compliance collection
        $onlineOrCodModes = ['upi', 'bank_transfer', 'razorpay', 'cod', 'pos_machine', 'cheque', 'online', 'card', 'credit_card', 'debit_card', 'prepaid'];
        if ($rawMode !== '' && in_array($rawMode, $onlineOrCodModes, true)) {
            return false;
        }

        // Default: offline/cash payments require compliance validation
        return true;
    }

    /**
     * Billing/shipping GSTIN from the invoice header or vp_order_info.
     *
     * @param array<string, mixed> $header
     * @param list<string> $orderNumbers
     */
    private function resolveOrderGstin(array $header, array $orderNumbers): string
    {
        require_once __DIR__ . '/../compliance/HighValueComplianceValidator.php';
        $gstin = HighValueComplianceValidator::resolveGstinFromPayload($header);
        if ($gstin !== '') {
            return $gstin;
        }

        if ($this->ordersModel && method_exists($this->ordersModel, 'getAddressInfoByOrderNumber')) {
            foreach ($orderNumbers as $orderNumber) {
                $info = $this->ordersModel->getAddressInfoByOrderNumber((string)$orderNumber);
                if (!is_array($info)) {
                    continue;
                }
                $gstin = HighValueComplianceValidator::resolveGstinFromPayload($info);
                if ($gstin !== '') {
                    return $gstin;
                }
            }
        }

        return '';
    }
}
