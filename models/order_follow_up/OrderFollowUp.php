<?php

require_once __DIR__ . '/OrderFollowUpSchema.php';

class OrderFollowUp
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        OrderFollowUpSchema::ensureAll($conn);
    }

    /** @return list<string> */
    public static function allowedFollowUpTypes(): array
    {
        return ['reship', 'replace', 'copy'];
    }

    /** @return list<string> */
    public static function allowedPricingModes(): array
    {
        return ['waived', 'same_as_original', 'catalog', 'manual'];
    }

    public static function defaultPricingModeForType(string $followUpType): string
    {
        $followUpType = strtolower(trim($followUpType));

        return match ($followUpType) {
            'reship' => 'waived',
            'replace' => 'same_as_original',
            default => 'catalog',
        };
    }

    /**
     * @return array{
     *   can_start:bool,
     *   disabled_reason:string,
     *   order_lines:list<array<string,mixed>>,
     *   latest_sales_return_id:int
     * }
     */
    public function resolveStartEligibility(string $sourceOrderNumber, string $followUpType = ''): array
    {
        require_once dirname(__DIR__, 2) . '/models/posorder/order.php';

        $sourceOrderNumber = trim($sourceOrderNumber);
        $followUpType = strtolower(trim($followUpType));
        $result = [
            'can_start' => false,
            'disabled_reason' => '',
            'order_lines' => [],
            'latest_sales_return_id' => 0,
        ];

        if ($sourceOrderNumber === '') {
            $result['disabled_reason'] = 'Order number is required.';

            return $result;
        }

        $orderModel = new POSOrder($this->conn);
        $lines = $orderModel->getOrderByOrderNumber($sourceOrderNumber);
        if (!is_array($lines) || $lines === []) {
            $result['disabled_reason'] = 'Order not found.';

            return $result;
        }

        $result['order_lines'] = $lines;

        $stmt = $this->conn->prepare(
            "SELECT id FROM vp_sales_returns
             WHERE order_number = ? AND status = 'finalized'
             ORDER BY id DESC LIMIT 1"
        );
        if ($stmt) {
            $stmt->bind_param('s', $sourceOrderNumber);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $result['latest_sales_return_id'] = (int) ($row['id'] ?? 0);
        }

        if ($followUpType === 'copy') {
            $result['can_start'] = true;

            return $result;
        }

        $hasReturnedLine = false;
        foreach ($lines as $line) {
            if (!is_array($line)) {
                continue;
            }
            if (strtolower(trim((string) ($line['status'] ?? ''))) === 'returned') {
                $hasReturnedLine = true;
                break;
            }
        }

        if (!$hasReturnedLine && $result['latest_sales_return_id'] <= 0) {
            $result['disabled_reason'] = 'Reship and replacement require a returned line or sales return. Use Copy order to reorder.';

            return $result;
        }

        $result['can_start'] = true;

        return $result;
    }

    /**
     * @param array<string, mixed> $data
     * @return array{success:bool, message:string, id?:int}
     */
    public function insertLink(array $data): array
    {
        $source = trim((string) ($data['source_order_number'] ?? ''));
        $followUp = trim((string) ($data['follow_up_order_number'] ?? ''));
        $type = strtolower(trim((string) ($data['follow_up_type'] ?? 'copy')));
        $pricingMode = strtolower(trim((string) ($data['pricing_mode'] ?? 'catalog')));
        $scope = strtolower(trim((string) ($data['scope'] ?? 'full')));

        if ($source === '' || $followUp === '') {
            return ['success' => false, 'message' => 'Source and follow-up order numbers are required.'];
        }
        if (!in_array($type, self::allowedFollowUpTypes(), true)) {
            return ['success' => false, 'message' => 'Invalid follow-up type.'];
        }
        if (!in_array($pricingMode, self::allowedPricingModes(), true)) {
            return ['success' => false, 'message' => 'Invalid pricing mode.'];
        }
        if (!in_array($scope, ['full', 'partial'], true)) {
            $scope = 'full';
        }

        $dup = $this->conn->prepare(
            'SELECT id FROM vp_order_follow_up WHERE follow_up_order_number = ? LIMIT 1'
        );
        if (!$dup) {
            return ['success' => false, 'message' => 'Database error.'];
        }
        $dup->bind_param('s', $followUp);
        $dup->execute();
        $existing = $dup->get_result()->fetch_assoc();
        $dup->close();
        if ($existing) {
            return ['success' => true, 'message' => 'Follow-up link already exists.', 'id' => (int) $existing['id']];
        }

        $salesReturnId = isset($data['sales_return_id']) ? (int) $data['sales_return_id'] : null;
        $sourceInvoiceId = isset($data['source_invoice_id']) ? (int) $data['source_invoice_id'] : null;
        $followUpInvoiceId = isset($data['follow_up_invoice_id']) ? (int) $data['follow_up_invoice_id'] : null;
        $sourcePayable = isset($data['source_payable_total']) ? (float) $data['source_payable_total'] : null;
        $followUpPayable = isset($data['follow_up_payable_total']) ? (float) $data['follow_up_payable_total'] : null;
        $receiptNumber = trim((string) ($data['receipt_number'] ?? ''));
        $remarks = trim((string) ($data['remarks'] ?? ''));
        $createdBy = (int) ($data['created_by'] ?? 0);
        $pricingJson = $data['source_pricing_json'] ?? null;
        if (is_array($pricingJson)) {
            $pricingJson = json_encode($pricingJson, JSON_UNESCAPED_UNICODE);
        }
        if (!is_string($pricingJson) || $pricingJson === '') {
            $pricingJson = null;
        }

        $stmt = $this->conn->prepare(
            'INSERT INTO vp_order_follow_up (
                source_order_number, follow_up_order_number, follow_up_type, pricing_mode, scope,
                sales_return_id, source_invoice_id, follow_up_invoice_id,
                source_payable_total, follow_up_payable_total, receipt_number,
                source_pricing_json, remarks, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        if (!$stmt) {
            return ['success' => false, 'message' => 'Failed to save follow-up link.'];
        }

        $salesReturnIdParam = $salesReturnId > 0 ? $salesReturnId : 0;
        $sourceInvoiceIdParam = $sourceInvoiceId > 0 ? $sourceInvoiceId : 0;
        $followUpInvoiceIdParam = $followUpInvoiceId > 0 ? $followUpInvoiceId : 0;

        $stmt->bind_param(
            'sssssiiiddsssi',
            $source,
            $followUp,
            $type,
            $pricingMode,
            $scope,
            $salesReturnIdParam,
            $sourceInvoiceIdParam,
            $followUpInvoiceIdParam,
            $sourcePayable,
            $followUpPayable,
            $receiptNumber,
            $pricingJson,
            $remarks,
            $createdBy
        );
        $ok = $stmt->execute();
        $newId = (int) $stmt->insert_id;
        $stmt->close();

        return $ok
            ? ['success' => true, 'message' => 'Follow-up order linked.', 'id' => $newId]
            : ['success' => false, 'message' => 'Failed to save follow-up link.'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getFollowUpsForSource(string $sourceOrderNumber): array
    {
        $sourceOrderNumber = trim($sourceOrderNumber);
        if ($sourceOrderNumber === '') {
            return [];
        }

        $stmt = $this->conn->prepare(
            'SELECT * FROM vp_order_follow_up
             WHERE source_order_number = ?
             ORDER BY id DESC'
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('s', $sourceOrderNumber);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($res && ($row = $res->fetch_assoc())) {
            $rows[] = $row;
        }
        $stmt->close();

        return $rows;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getLinkForFollowUpOrder(string $followUpOrderNumber): ?array
    {
        $followUpOrderNumber = trim($followUpOrderNumber);
        if ($followUpOrderNumber === '') {
            return null;
        }

        $stmt = $this->conn->prepare(
            'SELECT * FROM vp_order_follow_up WHERE follow_up_order_number = ? LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $followUpOrderNumber);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    public function updateFollowUpInvoiceId(string $followUpOrderNumber, int $invoiceId): void
    {
        $followUpOrderNumber = trim($followUpOrderNumber);
        $invoiceId = (int) $invoiceId;
        if ($followUpOrderNumber === '' || $invoiceId <= 0) {
            return;
        }

        $stmt = $this->conn->prepare(
            'UPDATE vp_order_follow_up SET follow_up_invoice_id = ? WHERE follow_up_order_number = ?'
        );
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('is', $invoiceId, $followUpOrderNumber);
        $stmt->execute();
        $stmt->close();
    }
}
