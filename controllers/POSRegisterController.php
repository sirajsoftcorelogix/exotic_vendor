<?php
require_once 'models/pos/pos.php';
require_once 'models/user/user.php';

class POSRegisterController
{
    private $product;
    private $pos;

    public function __construct($conn)
    {
        $this->pos     = new pos($conn);
    }

    /**
     * Discard all output buffers (bootstrap whitespace, nested ob_start, notices) so JSON is the only body.
     */
    private function clearBufferedHttpOutput(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    private const POS_PARENT_ITEM_CART_MESSAGE = 'Parent Level Item can not be added to the cart';

    /** Prefer exact variant SKU over shared item_code; deprioritize parent rows. */
    private const VP_PRODUCT_BY_CODE_ORDER_SQL = ' ORDER BY (sku = ?) DESC,
        CASE WHEN LOWER(TRIM(IFNULL(item_level, \'\'))) = \'parent\' THEN 1 ELSE 0 END,
        id ASC ';

    private function isParentItemLevel(?string $itemLevel): bool
    {
        return strtolower(trim((string)$itemLevel)) === 'parent';
    }

    /**
     * Resolve item_level for a catalogue code (sku or item_code), including parent rows.
     */
    private function lookupProductItemLevelForCode(mysqli $conn, string $code): string
    {
        $code = trim($code);
        if ($code === '') {
            return '';
        }
        // Prefer exact SKU, then non-parent rows (many variants share the same item_code).
        $stmt = $conn->prepare(
            'SELECT item_level FROM vp_products
             WHERE is_active = 1 AND (sku = ? OR item_code = ?)
             ORDER BY (sku = ?) DESC,
                      CASE WHEN LOWER(TRIM(IFNULL(item_level, \'\'))) = \'parent\' THEN 1 ELSE 0 END,
                      id ASC
             LIMIT 1'
        );
        if (!$stmt) {
            return '';
        }
        $stmt->bind_param('sss', $code, $code, $code);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return trim((string)($row['item_level'] ?? ''));
    }

    /**
     * @param array<string, mixed> $body Cart JSON (item_level, variation, code, …)
     * @return array{success: false, message: string}|null
     */
    private function cartAddBlockedIfParentLevelProduct(mysqli $conn, array $body): ?array
    {
        $level = strtolower(trim((string)($body['item_level'] ?? '')));
        if ($level === 'variation' || $level === 'standalone') {
            return null;
        }
        if ($level === 'parent') {
            return [
                'success' => false,
                'message' => self::POS_PARENT_ITEM_CART_MESSAGE,
            ];
        }

        $variation = trim((string)($body['variation'] ?? ''));
        if ($variation !== '') {
            return null;
        }

        $code = trim((string)($body['code'] ?? ''));
        if ($code !== '' && $this->isParentItemLevel($this->lookupProductItemLevelForCode($conn, $code))) {
            return [
                'success' => false,
                'message' => self::POS_PARENT_ITEM_CART_MESSAGE,
            ];
        }

        return null;
    }

    private function columnExists(mysqli $conn, string $table, string $column): bool
    {
        $stmt = $conn->prepare(
            'SELECT 1
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
             LIMIT 1'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ss', $table, $column);
        $stmt->execute();
        $exists = (bool)$stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $exists;
    }

    private function ensureHighValueComplianceSchema(mysqli $conn): void
    {
        static $done = false;
        if ($done) {
            return;
        }

        if (!$this->columnExists($conn, 'global_settings', 'high_value_transaction_limit')) {
            @$conn->query("ALTER TABLE global_settings ADD COLUMN high_value_transaction_limit DECIMAL(15,2) NOT NULL DEFAULT 200000.00");
        }
        @$conn->query("UPDATE global_settings SET high_value_transaction_limit = 200000.00 WHERE id = 1 AND (high_value_transaction_limit IS NULL OR high_value_transaction_limit <= 0)");

        $customerColumns = [
            'customer_residency_status' => "ALTER TABLE vp_customers ADD COLUMN customer_residency_status ENUM('INDIAN_RESIDENT','NRI','FOREIGN_NATIONAL') NOT NULL DEFAULT 'INDIAN_RESIDENT' AFTER phone",
            'customer_pan' => "ALTER TABLE vp_customers ADD COLUMN customer_pan VARCHAR(10) NOT NULL DEFAULT '' AFTER customer_residency_status",
            'passport_number' => "ALTER TABLE vp_customers ADD COLUMN passport_number VARCHAR(32) NOT NULL DEFAULT '' AFTER customer_pan",
            'country_of_residence' => "ALTER TABLE vp_customers ADD COLUMN country_of_residence VARCHAR(128) NOT NULL DEFAULT '' AFTER passport_number",
        ];
        foreach ($customerColumns as $column => $sql) {
            if (!$this->columnExists($conn, 'vp_customers', $column)) {
                @$conn->query($sql);
            }
        }

        $invoiceColumns = [
            'is_high_value_transaction' => "ALTER TABLE vp_invoices ADD COLUMN is_high_value_transaction TINYINT(1) NOT NULL DEFAULT 0 AFTER total_amount",
            'high_value_transaction_limit' => "ALTER TABLE vp_invoices ADD COLUMN high_value_transaction_limit DECIMAL(15,2) NULL AFTER is_high_value_transaction",
            'high_value_compliance_status' => "ALTER TABLE vp_invoices ADD COLUMN high_value_compliance_status ENUM('NOT_REQUIRED','PENDING','COMPLETED') NOT NULL DEFAULT 'NOT_REQUIRED' AFTER high_value_transaction_limit",
        ];
        foreach ($invoiceColumns as $column => $sql) {
            if (!$this->columnExists($conn, 'vp_invoices', $column)) {
                @$conn->query($sql);
            }
        }

        $done = true;
    }

    private function getHighValueTransactionLimit(mysqli $conn): float
    {
        $this->ensureHighValueComplianceSchema($conn);
        $limit = 200000.00;
        $res = $conn->query('SELECT high_value_transaction_limit FROM global_settings WHERE id = 1 LIMIT 1');
        if ($res && ($row = $res->fetch_assoc())) {
            $configured = (float)($row['high_value_transaction_limit'] ?? 0);
            if ($configured > 0) {
                $limit = $configured;
            }
        }
        return $limit;
    }

    private function normalizeResidencyStatus(string $status): string
    {
        $status = strtoupper(trim($status));
        return in_array($status, ['INDIAN_RESIDENT', 'NRI', 'FOREIGN_NATIONAL'], true)
            ? $status
            : 'INDIAN_RESIDENT';
    }

    private function normalizePan(string $pan): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($pan)));
    }

    private function normalizePassport(string $passport): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($passport)));
    }

    private function normalizeAadhaar(string $aadhaar): string
    {
        return preg_replace('/\D/', '', trim($aadhaar));
    }

    /**
     * @return array{ok:bool,is_high_value:bool,limit:float,errors:list<string>,cash_warning_required:bool,residency_status:string,pan:string,aadhaar:string,passport:string,country_of_residence:string,gstin:string,derived_pan_from_gstin:string}
     */
    private function evaluateHighValueCompliance(array $payload, float $invoiceAmount, float $cashAmount, string $paymentMode, mysqli $conn): array
    {
        $limit = $this->getHighValueTransactionLimit($conn);
        $isHighValue = $invoiceAmount >= $limit;
        $residency = $this->normalizeResidencyStatus((string)($payload['customer_residency_status'] ?? 'INDIAN_RESIDENT'));
        $pan = $this->normalizePan((string)($payload['customer_pan'] ?? ''));
        $aadhaar = $this->normalizeAadhaar((string)($payload['customer_aadhaar'] ?? ''));
        $passport = $this->normalizePassport((string)($payload['passport_number'] ?? ''));
        $countryOfResidence = trim((string)($payload['country_of_residence'] ?? ''));
        $gstin = strtoupper(trim((string)($payload['confirm_gstin'] ?? '')));
        $derivedPan = '';
        if ($gstin !== '' && preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', $gstin)) {
            $derivedPan = substr($gstin, 2, 10);
            if ($pan === '') {
                $pan = $derivedPan;
            }
        }

        $errors = [];
        if ($isHighValue && $gstin === '') {
            if ($residency === 'INDIAN_RESIDENT') {
                if ($pan === '') {
                    $errors[] = 'PAN is required for Indian resident high value transactions.';
                }
            } elseif ($residency === 'NRI') {
                if ($pan === '' && ($passport === '' || $countryOfResidence === '')) {
                    $errors[] = 'For NRI customers, enter PAN or Passport Number with Country of Residence.';
                }
            } else {
                if ($passport === '') {
                    $errors[] = 'Passport Number is required for foreign national high value transactions.';
                }
                if ($countryOfResidence === '') {
                    $errors[] = 'Country of Residence is required for foreign national high value transactions.';
                }
            }
        }

        if ($pan !== '' && !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $pan)) {
            $errors[] = 'PAN format is invalid.';
        }
        if ($passport !== '' && strlen($passport) < 6) {
            $errors[] = 'Passport Number must be at least 6 characters.';
        }
        if ($aadhaar !== '' && !preg_match('/^\d{12}$/', $aadhaar)) {
            $errors[] = 'Aadhaar must be 12 digits.';
        }

        $cashWarningRequired = (strtolower($paymentMode) === 'cash' && $cashAmount >= $limit);
        if ($cashWarningRequired && (string)($payload['sec269st_cash_warning_confirmed'] ?? '') !== '1') {
            $errors[] = 'Cash receipt warning under Section 269ST must be confirmed.';
        }

        return [
            'ok' => empty($errors),
            'is_high_value' => $isHighValue,
            'limit' => $limit,
            'errors' => $errors,
            'cash_warning_required' => $cashWarningRequired,
            'residency_status' => $residency,
            'pan' => $pan,
            'aadhaar' => $aadhaar,
            'passport' => $passport,
            'country_of_residence' => $countryOfResidence,
            'gstin' => $gstin,
            'derived_pan_from_gstin' => $derivedPan,
        ];
    }

    private function persistCustomerComplianceDetails(mysqli $conn, int $customerId, array $compliance): void
    {
        if ($customerId <= 0) {
            return;
        }
        $this->ensureHighValueComplianceSchema($conn);
        $residency = (string)($compliance['residency_status'] ?? 'INDIAN_RESIDENT');
        $pan = (string)($compliance['pan'] ?? '');
        $passport = (string)($compliance['passport'] ?? '');
        $country = (string)($compliance['country_of_residence'] ?? '');

        $stmt = $conn->prepare(
            'UPDATE vp_customers
             SET customer_residency_status = ?, customer_pan = ?, passport_number = ?, country_of_residence = ?
             WHERE id = ?'
        );
        if ($stmt) {
            $stmt->bind_param('ssssi', $residency, $pan, $passport, $country, $customerId);
            $stmt->execute();
            $stmt->close();
        }
    }

    private function findInvoiceIdForOrderNumber(mysqli $conn, string $orderNumber): ?int
    {
        $stmt = $conn->prepare(
            'SELECT id FROM vp_invoices
             WHERE vp_order_info_id = (SELECT id FROM vp_order_info WHERE order_number = ? LIMIT 1)
             ORDER BY id DESC LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return !empty($row['id']) ? (int)$row['id'] : null;
    }

    private function markInvoiceHighValueIfPresent(mysqli $conn, ?int $invoiceId, array $compliance): void
    {
        if (!$invoiceId) {
            return;
        }
        $isHighValue = !empty($compliance['is_high_value']) ? 1 : 0;
        $limit = (float)($compliance['limit'] ?? 200000.00);
        $status = $isHighValue ? 'COMPLETED' : 'NOT_REQUIRED';
        $stmt = $conn->prepare(
            'UPDATE vp_invoices
             SET is_high_value_transaction = ?, high_value_transaction_limit = ?, high_value_compliance_status = ?
             WHERE id = ?'
        );
        if ($stmt) {
            $stmt->bind_param('idsi', $isHighValue, $limit, $status, $invoiceId);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * After full payment: import order from vendor API and create tax invoice for receipt screen.
     */
    private function finalizePosReceiptInvoice(mysqli $conn, string $orderNumber, string $paymentStage, array $compliance, string $customInvoiceNumber = ''): array
    {
        $out = [
            'import_status' => '',
            'show_invoice_pdf_button' => false,
            'show_invoice_preview_button' => false,
            'invoice_id' => 0,
            'invoice_pdf_url' => '',
            'invoice_preview_url' => '',
            'invoice_pdf_disabled_hint' => 'Tax invoice is available after payment is received in full.',
        ];

        if ($paymentStage !== 'final') {
            return $out;
        }

        try {
            $ordersCtrl = $this->getOrdersControllerForImport();
            $import = $ordersCtrl->importSingleOrderForCheckoutWithRetry($orderNumber, 4, 2);

            if (!$ordersCtrl->isOrderReadyForPosCheckout($orderNumber)) {
                $out['import_status'] = 'failed';
                $out['invoice_pdf_disabled_hint'] = 'Order is not in the system yet (vendor API may still be syncing). '
                    . 'Open Orders to import, then create the invoice from Invoices.';
                if (!empty($import['message'])) {
                    $out['invoice_pdf_disabled_hint'] .= ' (' . (string)$import['message'] . ')';
                }
                return $out;
            }

            $posInv = $this->getPosInvoiceControllerForCheckout();
            $invRes = $posInv->createAutoInvoiceForOrder($orderNumber, $customInvoiceNumber);

            if (!empty($invRes['success']) && !empty($invRes['invoice_id'])) {
                $invoiceId = (int)$invRes['invoice_id'];
                $this->markInvoiceHighValueIfPresent($conn, $invoiceId, $compliance);
                $out['import_status'] = !empty($import['success']) ? 'success' : 'failed';
                $out = $this->applyPosReceiptInvoiceLinks($out, $invoiceId);
                return $out;
            }

            $existingId = $this->findInvoiceIdForOrderNumber($conn, $orderNumber);
            if ($existingId) {
                $this->markInvoiceHighValueIfPresent($conn, $existingId, $compliance);
                $out['import_status'] = !empty($import['success']) ? 'success' : 'failed';
                $out = $this->applyPosReceiptInvoiceLinks($out, $existingId);
                return $out;
            }

            $out['import_status'] = 'failed';
            $out['invoice_pdf_disabled_hint'] = $invRes['message']
                ?? ($import['message'] ?? 'Invoice could not be created. Create it from POS Invoices after import.');
            return $out;
        } catch (\Throwable $e) {
            $out['import_status'] = 'failed';
            $out['invoice_pdf_disabled_hint'] = 'Invoice step failed: open POS Invoices after import.';
            error_log('[POS checkout finalize invoice] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $out;
        }
    }

    /**
     * POS counter sale is handed over immediately, so mark imported local rows as shipped
     * and mirror the item-level status to Exotic vendor API (shipped admin code = 5).
     *
     * @return array{local_rows:int,local_updated:bool,api_called:int,api_failed:int,message:string}
     */
    private function markPosCheckoutOrderShipped(mysqli $conn, string $orderNumber): array
    {
        $result = [
            'local_rows' => 0,
            'local_updated' => false,
            'api_called' => 0,
            'api_failed' => 0,
            'message' => '',
        ];

        $orderNumber = trim($orderNumber);
        if ($orderNumber === '') {
            $result['message'] = 'Order number missing for shipped status sync.';
            return $result;
        }

        $stmt = $conn->prepare('SELECT id, item_code, size, color FROM vp_orders WHERE order_number = ? ORDER BY id ASC');
        if (!$stmt) {
            $result['message'] = 'Could not prepare order item lookup for shipped status sync.';
            return $result;
        }

        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $result['local_rows'] = count($rows);
        if (empty($rows)) {
            $result['message'] = 'Order was not found locally for shipped status sync.';
            return $result;
        }

        foreach ($rows as $row) {
            $apiRes = $this->updateExoticVendorOrderItemStatus([
                'orderid' => $orderNumber,
                'level' => 'item',
                'order_status' => 5,
                'itemcode' => trim((string)($row['item_code'] ?? '')),
                'size' => trim((string)($row['size'] ?? '')),
                'color' => trim((string)($row['color'] ?? '')),
            ]);
            ++$result['api_called'];
            if (empty($apiRes['success'])) {
                ++$result['api_failed'];
                error_log('[POS shipped status API] Order ' . $orderNumber . ' item ' . (string)($row['id'] ?? '') . ': ' . (string)($apiRes['error'] ?? 'failed'));
            }
        }

        $upd = $conn->prepare("UPDATE vp_orders SET status = 'shipped' WHERE order_number = ?");
        if (!$upd) {
            $result['message'] = 'Could not prepare local shipped status update.';
            return $result;
        }
        $upd->bind_param('s', $orderNumber);
        $result['local_updated'] = $upd->execute();
        $upd->close();

        if ($result['api_failed'] > 0) {
            $result['message'] = 'Order marked shipped locally, but Exotic shipped status API failed for ' . $result['api_failed'] . ' item(s).';
        } elseif (!$result['local_updated']) {
            $result['message'] = 'Exotic shipped status API completed, but local shipped status update failed.';
        } else {
            $result['message'] = 'Order marked shipped locally and on Exotic.';
        }

        return $result;
    }

    /**
     * Mirrors models/comman/tables.php::updateExoticIndiaOrderStatus for POS checkout.
     *
     * @param array{orderid:string,level:string,order_status:int,itemcode:string,size:string,color:string} $apiData
     * @return array{success:bool,http_code:int,raw:string,error:string}
     */
    private function updateExoticVendorOrderItemStatus(array $apiData): array
    {
        $postData = [
            'makeRequestOf' => 'vendors-orderjson',
            'orderid' => $apiData['orderid'],
            'level' => $apiData['level'],
            'order_status' => (string)$apiData['order_status'],
            'itemcode' => $apiData['itemcode'],
            'size' => $apiData['size'],
            'color' => $apiData['color'],
        ];
        $headers = [
            'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
            'x-adminapitest: 1',
            'Content-Type: application/x-www-form-urlencoded',
        ];

        $ch = curl_init('https://www.exoticindia.com/vendor-api/order/modify');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $raw = (string)$response;
        $ok = ($error === '' && $httpCode >= 200 && $httpCode < 300);
        return [
            'success' => $ok,
            'http_code' => $httpCode,
            'raw' => $raw,
            'error' => $error !== '' ? $error : ($ok ? '' : 'HTTP ' . $httpCode . ' ' . substr($raw, 0, 500)),
        ];
    }

    private function appendHighValueComplianceToNote(string $note, float $invoiceAmount, string $paymentMode, array $compliance): string
    {
        if (empty($compliance['is_high_value'])) {
            return $note;
        }

        $lines = [
            'High Value Transaction Compliance',
            'Invoice amount: ' . number_format($invoiceAmount, 2, '.', ''),
            'Limit: ' . number_format((float)($compliance['limit'] ?? 200000.00), 2, '.', ''),
            'Payment mode: ' . $paymentMode,
            'Residency: ' . (string)($compliance['residency_status'] ?? ''),
        ];

        foreach ([
            'GSTIN' => $compliance['gstin'] ?? '',
            'PAN' => $compliance['pan'] ?? '',
            'PAN derived from GSTIN' => $compliance['derived_pan_from_gstin'] ?? '',
            'Aadhaar' => $compliance['aadhaar'] ?? '',
            'Passport' => $compliance['passport'] ?? '',
            'Country of residence' => $compliance['country_of_residence'] ?? '',
        ] as $label => $value) {
            $value = trim((string)$value);
            if ($value !== '') {
                $lines[] = $label . ': ' . $value;
            }
        }

        if (!empty($compliance['cash_warning_required'])) {
            $lines[] = 'Sec 269ST cash warning acknowledged: YES';
        }

        $block = implode("\n", $lines);
        return trim($note) !== '' ? trim($note) . "\n\n" . $block : $block;
    }

    /**
     * @return list<array{code:string,title:string,quantity:float,local_stock:float,shortage:float}>
     */
    private function detectLocalStockWarningsFromCart(array $cartData): array
    {
        $items = $cartData['cartitems'] ?? $cartData['cart_items'] ?? $cartData['items'] ?? $cartData['lines'] ?? [];
        if (!is_array($items)) {
            return [];
        }

        $warnings = [];
        foreach ($items as $row) {
            if (!is_array($row) || !array_key_exists('local_stock', $row)) {
                continue;
            }
            $qty = (float)($row['quantity'] ?? $row['qty'] ?? $row['prqt'] ?? 1);
            if ($qty <= 0) {
                $qty = 1;
            }
            $localStock = (float)str_replace(',', '', (string)($row['local_stock'] ?? 0));
            if ($qty <= $localStock) {
                continue;
            }
            $warnings[] = [
                'code' => trim((string)($row['code'] ?? $row['item_code'] ?? $row['sku'] ?? '')),
                'title' => trim((string)($row['name'] ?? $row['title'] ?? $row['product_name'] ?? 'Item')),
                'quantity' => $qty,
                'local_stock' => $localStock,
                'shortage' => max(0, $qty - $localStock),
            ];
        }

        return $warnings;
    }

    private function appendLocalStockWarningsToNote(string $note, array $warnings): string
    {
        if (empty($warnings)) {
            return $note;
        }

        $lines = ['Local Stock Warning'];
        foreach ($warnings as $w) {
            $label = trim((string)($w['code'] ?? '')) !== ''
                ? trim((string)$w['code'])
                : trim((string)($w['title'] ?? 'Item'));
            $local = (float)($w['local_stock'] ?? 0);
            $lines[] = $label . ' — Local Stock = ' . $local . ', Proceed Y';
        }

        $block = implode("\n", $lines);
        return trim($note) !== '' ? trim($note) . "\n\n" . $block : $block;
    }

    public function index()
    {
        // slug => label
        $categories = getCategories();
        require_once 'models/user/user.php';
        require_once 'models/customer/Customer.php';
        global $conn;   // use existing DB connection
        $usersModel = new User($conn);   //  create instance

        if ((int)($_SESSION['warehouse_id'] ?? 0) <= 0 && $conn instanceof mysqli) {
            $defWh = $this->getDefaultWarehouseRow($conn);
            if ($defWh !== null && !empty($defWh['id'])) {
                $_SESSION['warehouse_id'] = $defWh['id'];
            }
        }

        $warehouseName = 'No Warehouse';

        if (!empty($_SESSION['warehouse_id'])) {
            $warehouse = $usersModel->getWarehouseById($_SESSION['warehouse_id']);
            $warehouseName = $warehouse['address_title'] ?? 'No Warehouse';
        }
        // Add "All Products" (slug => label)
        // Put it first:
        $categories = ['allProducts' => 'All Products'] + $categories;

        // Clear legacy Exotic-cart session keys so nothing re-fires discounts/coupons or stale debug.
        foreach (['cart_success', 'cart_error', 'coupon_message', 'coupon_status'] as $k) {
            unset($_SESSION[$k]);
        }
        unset(
            $_SESSION['discount_coupon'],
            $_SESSION['gift_voucher'],
            $_SESSION['custom_discount'],
            $_SESSION['pos_coupon_api_debug'],
            $_SESSION['pos_order_create_api_debug']
        );

        $customerModel = new Customer($conn);
        $highValueTransactionLimit = $conn instanceof mysqli ? $this->getHighValueTransactionLimit($conn) : 200000.00;
        $selected_customer = null;
        if (!empty($_SESSION['pos_customer_id'])) {
            $cid = (int)$_SESSION['pos_customer_id'];
            if ($cid > 0) {
                $row = $customerModel->getCustomerById($cid);
                if (!empty($row['id'])) {
                    $nm = (string)($row['name'] ?? '');
                    $ph = (string)($row['phone'] ?? '');
                    $em = (string)($row['email'] ?? '');
                    $selected_customer = [
                        'id' => (int)$row['id'],
                        'name' => $nm,
                        'phone' => $ph,
                        'email' => $em,
                        'text' => trim($nm . ' | ' . $ph . ($em !== '' ? ' | ' . $em : '')),
                    ];
                } else {
                    unset($_SESSION['pos_customer_id']);
                }
            }
        }
        // slug => svg icon
        $categoryIcons = [
            'allProducts' => '
                ☰
            ',
            'paintings' => '
                <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" />
                    <circle cx="8.5" cy="8.5" r="1.5" />
                    <path d="M21 15l-5-5L5 21" />
                </svg>
            ',
            'sculptures' => '
                <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2l3 7h7l-5.5 4.5L18 22l-6-4-6 4 1.5-8.5L2 9h7z" />
                </svg>
            ',
            'textiles' => '
                <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 4l4 4-4 4M8 4L4 8l4 4M4 8h16v12H4z" />
                </svg>
            ',
            'jewelry' => '
                <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="8" />
                    <path d="M12 4v8l4 4" />
                </svg>
            ',
            'homeandliving' => '
                <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 12l9-9 9 9M9 21V9h6v12" />
                </svg>
            ',
            'book' => '
                <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19.5A2.5 2.5 0 016.5 17H20M6.5 2H20v15H6.5A2.5 2.5 0 014 14.5v-10A2.5 2.5 0 016.5 2z" />
                </svg>
            ',
        ];

        // Build final array: slug => [label, icon]
        $categoryData = [];
        foreach ($categories as $slug => $label) {
            $categoryData[$slug] = [
                'label' => $label,
                'icon'  => $categoryIcons[$slug] ?? '', // fallback
            ];
        }

        $rawCountries = function_exists('country_array') ? country_array() : ['IN' => 'India'];
        asort($rawCountries);
        $countryList = [];
        if (isset($rawCountries['IN'])) {
            $countryList['IN'] = $rawCountries['IN'];
            unset($rawCountries['IN']);
        }
        foreach ($rawCountries as $code => $name) {
            $iso = strtoupper(substr(trim((string)$code), 0, 2));
            if ($iso !== '' && !isset($countryList[$iso])) {
                $countryList[$iso] = (string)$name;
            }
        }

        $resolveCountryIdForStates = function (string $iso, array $names = []) use ($conn): int {
            if (!$conn instanceof mysqli) {
                return 0;
            }

            $codes = array_values(array_unique(array_filter([
                strtoupper(substr(trim($iso), 0, 2)),
                strtoupper(trim($iso)),
            ])));
            $nameCandidates = array_values(array_unique(array_filter(array_map('trim', $names))));
            $sql = 'SELECT id FROM countries WHERE UPPER(country_code) IN (?, ?) OR name IN (?, ?) LIMIT 1';
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return 0;
            }

            $codeA = $codes[0] ?? '';
            $codeB = $codes[1] ?? $codeA;
            $nameA = $nameCandidates[0] ?? '';
            $nameB = $nameCandidates[1] ?? $nameA;
            $stmt->bind_param('ssss', $codeA, $codeB, $nameA, $nameB);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return (int)($row['id'] ?? 0);
        };
        $posCountryStates = [];
        $loadPosStatesForCountry = function (int $countryId) use ($conn): array {
            if (!$conn instanceof mysqli) {
                return [];
            }

            require_once 'models/country/state.php';
            $stateModel = new State($conn);
            $stateRows = $stateModel->getAllStates($countryId);
            $states = [];
            foreach (($stateRows['states'] ?? []) as $row) {
                $name = trim((string)($row['name'] ?? ''));
                if ($name !== '') {
                    $states[] = ['id' => (int)($row['id'] ?? 0), 'name' => $name];
                }
            }

            return $states;
        };
        if ($conn instanceof mysqli) {
            $indiaCountryId = $resolveCountryIdForStates('IN', ['India']) ?: 105;
            $usCountryId = $resolveCountryIdForStates('US', ['United States', 'USA', 'United States of America']);
            $posCountryStates = [
                'IN' => $loadPosStatesForCountry($indiaCountryId),
                'US' => $loadPosStatesForCountry($usCountryId),
            ];
        }

        $posStorePincode = $conn instanceof mysqli ? $this->resolveStorePincodeForPos($conn) : '';

        renderTemplate('views/pos_register/index.php', [
            'categories' => $categoryData,
            'warehouse_name' => $warehouseName,
            'pos_store_pincode' => $posStorePincode,
            // Minimal placeholder until new cart; view must not depend on Exotic retrieve shape.
            'cartData' => [
                'items' => [],
                'grand_total' => 0.0,
                'currency' => 'INR',
            ],
            'selected_customer' => $selected_customer,
            'high_value_transaction_limit' => $highValueTransactionLimit,
            'country_list' => $countryList,
            'pos_india_states' => $posCountryStates['IN'] ?? [],
            'pos_country_states' => $posCountryStates,
        ]);
    }

    /** JSON list of supported POS states by ISO country code. */
    public function states_by_country(): void
    {
        is_login();
        $this->clearBufferedHttpOutput();
        header('Content-Type: application/json; charset=utf-8');
        global $conn;

        $country = strtoupper(substr(trim((string)($_GET['country'] ?? 'IN')), 0, 2));
        if (!in_array($country, ['IN', 'US'], true)) {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!$conn instanceof mysqli) {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $resolveCountryId = function (string $iso, array $names = []) use ($conn): int {
            $codes = array_values(array_unique(array_filter([
                strtoupper(substr(trim($iso), 0, 2)),
                strtoupper(trim($iso)),
            ])));
            $nameCandidates = array_values(array_unique(array_filter(array_map('trim', $names))));
            $stmt = $conn->prepare('SELECT id FROM countries WHERE UPPER(country_code) IN (?, ?) OR name IN (?, ?) LIMIT 1');
            if (!$stmt) {
                return 0;
            }

            $codeA = $codes[0] ?? '';
            $codeB = $codes[1] ?? $codeA;
            $nameA = $nameCandidates[0] ?? '';
            $nameB = $nameCandidates[1] ?? $nameA;
            $stmt->bind_param('ssss', $codeA, $codeB, $nameA, $nameB);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return (int)($row['id'] ?? 0);
        };
        $countryId = $country === 'US'
            ? $resolveCountryId('US', ['United States', 'USA', 'United States of America'])
            : $resolveCountryId('IN', ['India']);
        if ($countryId <= 0) {
            $countryId = $country === 'US' ? 233 : 105;
        }

        require_once 'models/country/state.php';
        $stateModel = new State($conn);
        $stateRows = $stateModel->getAllStates($countryId);
        $out = [];
        foreach (($stateRows['states'] ?? []) as $row) {
            $name = trim((string)($row['name'] ?? ''));
            if ($name !== '') {
                $out[] = ['id' => (int)($row['id'] ?? 0), 'name' => $name];
            }
        }
        echo json_encode($out, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * JSON autocomplete for POS customer field (vp_customers). Requires q length >= 2.
     */
    public function customer_search()
    {
        is_login();
        $this->clearBufferedHttpOutput();
        header('Content-Type: application/json; charset=utf-8');

        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $len = function_exists('mb_strlen') ? mb_strlen($q, 'UTF-8') : strlen($q);
        if ($len < 2) {
            echo json_encode(['success' => true, 'customers' => []], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        require_once 'models/customer/Customer.php';
        global $conn;
        $customerModel = new Customer($conn);
        $customers = $customerModel->searchCustomersForPos($q, 40);

        echo json_encode([
            'success' => true,
            'customers' => $customers,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    /**
     * Fetch billing/shipping for POS confirmation popup.
     * Priority: vp_customers (+ pos_customer_details) → last vp_order_info → session pos_customer_form.
     */
    public function customer_order_info()
    {
        is_login();
        global $conn;
        $this->clearBufferedHttpOutput();
        header('Content-Type: application/json; charset=utf-8');

        $customerId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;

        $pick = static function (string ...$parts): string {
            foreach ($parts as $p) {
                $t = trim((string)$p);
                if ($t !== '') {
                    return $t;
                }
            }

            return '';
        };

        require_once 'models/customer/Customer.php';
        $customerModel = new Customer($conn);
        if ($conn instanceof mysqli) {
            $this->ensureHighValueComplianceSchema($conn);
        }
        $fromVc = $customerId > 0 ? $customerModel->getCustomerBillingShippingForPos($customerId) : ['billing' => [], 'shipping' => []];
        $billingVc = $fromVc['billing'];
        $shippingVc = $fromVc['shipping'];
        $customerRow = $customerId > 0 ? ($customerModel->getCustomerById($customerId) ?: []) : [];

        $billingOrder = [];
        $shippingOrder = [];

        if ($customerId > 0) {
            $stmt = $conn->prepare('SELECT * FROM vp_order_info WHERE customer_id = ? ORDER BY id DESC LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('i', $customerId);
                $stmt->execute();
                $info = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($info) {
                    $billingOrder = [
                        'first_name' => trim((string)($info['first_name'] ?? '')),
                        'last_name' => trim((string)($info['last_name'] ?? '')),
                        'email' => trim((string)($info['email'] ?? '')),
                        'phone' => trim((string)($info['mobile'] ?? '')),
                        'address1' => trim((string)($info['address_line1'] ?? '')),
                        'address2' => trim((string)($info['address_line2'] ?? '')),
                        'city' => trim((string)($info['city'] ?? '')),
                        'state' => trim((string)($info['state'] ?? '')),
                        'zip' => trim((string)($info['zipcode'] ?? '')),
                        'country' => trim((string)($info['country'] ?? 'IN')),
                        'gstin' => trim((string)($info['gstin'] ?? '')),
                    ];
                    $shippingOrder = [
                        'shipping_first_name' => trim((string)($info['shipping_first_name'] ?? '')),
                        'shipping_last_name' => trim((string)($info['shipping_last_name'] ?? '')),
                        'sname' => trim((string)(($info['shipping_first_name'] ?? '').' '.($info['shipping_last_name'] ?? ''))),
                        'saddress1' => trim((string)($info['shipping_address_line1'] ?? '')),
                        'saddress2' => trim((string)($info['shipping_address_line2'] ?? '')),
                        'scity' => trim((string)($info['shipping_city'] ?? '')),
                        'sstate' => trim((string)($info['shipping_state'] ?? '')),
                        'szip' => trim((string)($info['shipping_zipcode'] ?? '')),
                        'scountry' => trim((string)($info['shipping_country'] ?? 'IN')),
                        'sphone' => trim((string)($info['shipping_mobile'] ?? '')),
                    ];
                }
            }
        }

        $billingSession = [];
        $shippingSession = [];
        if (!empty($_SESSION['pos_customer_form'])) {
            $form = $_SESSION['pos_customer_form'];
            $billingSession = [
                'first_name' => trim((string)($form['first_name'] ?? '')),
                'last_name' => trim((string)($form['last_name'] ?? '')),
                'email' => trim((string)($form['cus_email'] ?? '')),
                'phone' => trim((string)($form['mobile'] ?? '')),
                'address1' => trim((string)($form['address_line1'] ?? '')),
                'address2' => trim((string)($form['address_line2'] ?? '')),
                'city' => trim((string)($form['city'] ?? '')),
                'state' => trim((string)($form['state'] ?? '')),
                'zip' => trim((string)($form['zipcode'] ?? '')),
                'country' => trim((string)($form['country'] ?? 'IN')),
                'gstin' => trim((string)($form['gstin'] ?? '')),
            ];
            $shippingSession = [
                'shipping_first_name' => trim((string)($form['shipping_first_name'] ?? '')),
                'shipping_last_name' => trim((string)($form['shipping_last_name'] ?? '')),
                'sname' => trim((string)(($form['shipping_first_name'] ?? '').' '.($form['shipping_last_name'] ?? ''))),
                'saddress1' => trim((string)($form['shipping_address_line1'] ?? '')),
                'saddress2' => trim((string)($form['shipping_address_line2'] ?? '')),
                'scity' => trim((string)($form['shipping_city'] ?? '')),
                'sstate' => trim((string)($form['shipping_state'] ?? '')),
                'szip' => trim((string)($form['shipping_zipcode'] ?? '')),
                'scountry' => trim((string)($form['shipping_country'] ?? 'IN')),
                'sphone' => trim((string)($form['shipping_mobile'] ?? '')),
            ];
        }

        $billing = [
            'first_name' => $pick($billingVc['first_name'] ?? '', $billingOrder['first_name'] ?? '', $billingSession['first_name'] ?? ''),
            'last_name' => $pick($billingVc['last_name'] ?? '', $billingOrder['last_name'] ?? '', $billingSession['last_name'] ?? ''),
            'email' => $pick($billingVc['email'] ?? '', $billingOrder['email'] ?? '', $billingSession['email'] ?? ''),
            'phone' => $pick($billingVc['phone'] ?? '', $billingOrder['phone'] ?? '', $billingSession['phone'] ?? ''),
            'address1' => $pick($billingVc['address1'] ?? '', $billingOrder['address1'] ?? '', $billingSession['address1'] ?? ''),
            'address2' => $pick($billingVc['address2'] ?? '', $billingOrder['address2'] ?? '', $billingSession['address2'] ?? ''),
            'city' => $pick($billingVc['city'] ?? '', $billingOrder['city'] ?? '', $billingSession['city'] ?? ''),
            'state' => $pick($billingVc['state'] ?? '', $billingOrder['state'] ?? '', $billingSession['state'] ?? ''),
            'zip' => $pick($billingVc['zip'] ?? '', $billingOrder['zip'] ?? '', $billingSession['zip'] ?? ''),
            'country' => $pick($billingVc['country'] ?? '', $billingOrder['country'] ?? '', $billingSession['country'] ?? ''),
            'gstin' => $pick($billingVc['gstin'] ?? '', $billingOrder['gstin'] ?? '', $billingSession['gstin'] ?? ''),
        ];

        $shipping = [
            'shipping_first_name' => $pick($shippingVc['shipping_first_name'] ?? '', $shippingOrder['shipping_first_name'] ?? '', $shippingSession['shipping_first_name'] ?? ''),
            'shipping_last_name' => $pick($shippingVc['shipping_last_name'] ?? '', $shippingOrder['shipping_last_name'] ?? '', $shippingSession['shipping_last_name'] ?? ''),
            'sname' => $pick($shippingVc['sname'] ?? '', $shippingOrder['sname'] ?? '', $shippingSession['sname'] ?? ''),
            'saddress1' => $pick($shippingVc['saddress1'] ?? '', $shippingOrder['saddress1'] ?? '', $shippingSession['saddress1'] ?? ''),
            'saddress2' => $pick($shippingVc['saddress2'] ?? '', $shippingOrder['saddress2'] ?? '', $shippingSession['saddress2'] ?? ''),
            'scity' => $pick($shippingVc['scity'] ?? '', $shippingOrder['scity'] ?? '', $shippingSession['scity'] ?? ''),
            'sstate' => $pick($shippingVc['sstate'] ?? '', $shippingOrder['sstate'] ?? '', $shippingSession['sstate'] ?? ''),
            'szip' => $pick($shippingVc['szip'] ?? '', $shippingOrder['szip'] ?? '', $shippingSession['szip'] ?? ''),
            'scountry' => $pick($shippingVc['scountry'] ?? '', $shippingOrder['scountry'] ?? '', $shippingSession['scountry'] ?? ''),
            'sphone' => $pick($shippingVc['sphone'] ?? '', $shippingOrder['sphone'] ?? '', $shippingSession['sphone'] ?? ''),
        ];

        echo json_encode([
            'success' => true,
            'billing' => $billing,
            'shipping' => $shipping,
            'compliance' => [
                'customer_residency_status' => $this->normalizeResidencyStatus((string)($customerRow['customer_residency_status'] ?? 'INDIAN_RESIDENT')),
                'customer_pan' => $this->normalizePan((string)($customerRow['customer_pan'] ?? '')),
                'passport_number' => $this->normalizePassport((string)($customerRow['passport_number'] ?? '')),
                'country_of_residence' => trim((string)($customerRow['country_of_residence'] ?? '')),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    public function stockReport()
    {
        is_login();
        require_once 'models/user/user.php';
        global $conn;
        $usersModel = new User($conn);

        $sessionWh = (int) ($_SESSION['warehouse_id'] ?? 0);
        // Match helpers/html_helpers.php hasPermission(): admin is role_id == 1 (loose, handles string "1").
        $isAdmin = isset($_SESSION['user']['role_id']) && $_SESSION['user']['role_id'] == 1;

        $reportWh = $sessionWh;
        if ($isAdmin && isset($_GET['warehouse_id'])) {
            $reqWh = (int) $_GET['warehouse_id'];
            if ($reqWh > 0) {
                $check = $usersModel->getWarehouseById($reqWh);
                if (!empty($check['id'])) {
                    $reportWh = $reqWh;
                }
            }
        }

        $warehouseName = 'No Warehouse';
        if ($reportWh > 0) {
            $warehouse = $usersModel->getWarehouseById($reportWh);
            $warehouseName = $warehouse['address_title'] ?? 'No Warehouse';
        }

        $filters = [
            'search' => $_GET['search'] ?? '',
            'category' => $_GET['category'] ?? 'allProducts',
            'stock_status' => $_GET['stock_status'] ?? 'all',
            'limit' => $_GET['limit'] ?? 200,
            'page_no' => isset($_GET['page_no']) ? max(1, (int)$_GET['page_no']) : 1,
            'warehouse_id' => $reportWh,
        ];

        $categories = ['allProducts' => 'All Products'] + getCategories();
        $totalRows = $this->pos->getStockReportCount($filters);
        $rows = $this->pos->getStockReport($filters);
        $limit = (int)($filters['limit'] ?? 200);
        $pageNo = (int)($filters['page_no'] ?? 1);
        $totalPages = $limit > 0 ? (int)ceil($totalRows / $limit) : 1;
        if ($totalPages < 1) {
            $totalPages = 1;
        }

        $warehouses = $isAdmin ? $usersModel->getAllWarehouses() : [];

        renderTemplate('views/pos_register/stock_report.php', [
            'warehouse_name' => $warehouseName,
            'categories' => $categories,
            'filters' => $filters,
            'rows' => $rows,
            'page_no' => $pageNo,
            'limit' => $limit,
            'total_rows' => $totalRows,
            'total_pages' => $totalPages,
            'can_change_warehouse' => $isAdmin,
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * DataTables AJAX endpoint for products list
     */
    public function productsAjax_bk()
    {
        // Prefer infinite-scroll params if provided
        $pageNo  = isset($_GET['page_no']) ? max(1, (int)$_GET['page_no']) : null;
        $perPage = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : null;

        if ($pageNo !== null && $perPage !== null) {
            $length = $perPage;
            $start  = ($pageNo - 1) * $perPage;
            $draw   = 0; // not needed for infinite scroll
        } else {
            // DataTables fallback
            $draw   = isset($_GET['draw'])   ? (int)$_GET['draw']   : 0;
            $start  = isset($_GET['start'])  ? (int)$_GET['start']  : 0;
            $length = isset($_GET['length']) ? (int)$_GET['length'] : 12;
            $pageNo = (int) floor($start / max(1, $length)) + 1;
        }

        // DataTables global search
        $searchValue = '';
        if (isset($_GET['search']['value'])) {
            $searchValue = trim($_GET['search']['value']);
        }

        // Custom filters (add back category/product_code if needed)
        $category    = isset($_GET['category']) ? trim($_GET['category']) : '';
        //$productCode = isset($_GET['product_code']) ? trim($_GET['product_code']) : '';
        $productName = isset($_GET['product_name']) ? trim($_GET['product_name']) : '';

        // ordering (allow simple defaults for infinite scroll)
        $orderColumnIndex = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 2;
        $orderDir         = isset($_GET['order'][0]['dir'])    ? $_GET['order'][0]['dir']         : 'asc';

        $columns = [
            0 => 'image',
            1 => 'item_code',
            2 => 'title',
            3 => 'stock_qty',
            4 => 'price',
        ];
        $orderColumn = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : 'title';

        $result = $this->pos->getProductsDataTable(
            $start,
            $length,
            $searchValue,
            $productName,
            $orderColumn,
            $orderDir,
            $category
        );

        // total_pages helpful for frontend
        $totalFiltered = (int) ($result['recordsFiltered'] ?? 0);
        $totalPages = ($length > 0) ? (int) ceil($totalFiltered / $length) : 1;

        $response = [
            'draw'            => $draw,
            'recordsTotal'    => $result['recordsTotal'] ?? 0,
            'recordsFiltered' => $result['recordsFiltered'] ?? 0,
            'data'            => $result['data'] ?? [],
            'current_page'    => $pageNo,
            'per_page'        => $length,
            'total_pages'     => $totalPages,
        ];

        $this->clearBufferedHttpOutput();
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    public function productsAjax()
    {
        $pageNo  = $_GET['page_no'] ?? 1;
        $perPage = $_GET['per_page'] ?? 48;

        $start  = ($pageNo - 1) * $perPage;
        // Fastest POS: fetch one extra row to decide has_more (avoid COUNT(*))
        $length = $perPage + 1;

        $searchValue = $_GET['search']['value'] ?? '';

        $category    = $_GET['category'] ?? '';
        $productName = trim((string)($_GET['product_name'] ?? ''));
        $productCode = trim((string)($_GET['product_code'] ?? ''));
        // Same text in both fields (current POS UI) would AND two LIKE blocks and drop title-only matches.
        if ($productName !== '' && $productCode !== '' && $productName === $productCode) {
            $productCode = '';
        }

        $minPrice = $_GET['min_price'] ?? '';
        $maxPrice = $_GET['max_price'] ?? '';
        // Match stock report default: all rows with latest movement for warehouse (in|out|low|all).
        $stockFilter = strtolower(trim((string)($_GET['stock_filter'] ?? 'all')));

        // SORT
        $sortBy = $_GET['sort_by'] ?? '';

        switch ($sortBy) {
            case 'price_low_high':
                $orderColumn = 'price_india';
                $orderDir = 'asc';
                break;

            case 'price_high_low':
                $orderColumn = 'price_india';
                $orderDir = 'desc';
                break;

            case 'name_asc':
                $orderColumn = 'title';
                $orderDir = 'asc';
                break;

            case 'name_desc':
                $orderColumn = 'title';
                $orderDir = 'desc';
                break;

            default:
                $orderColumn = 'title';
                $orderDir = 'asc';
        }

        $result = $this->pos->getProductsDataTable(
            $start,
            $length,
            $searchValue,
            $productName,
            $orderColumn,
            $orderDir,
            $category,
            $productCode,
            $minPrice,
            $maxPrice,
            $stockFilter
        );

        $rows = $result['data'] ?? [];
        $totalFiltered = (int)($result['recordsFiltered'] ?? 0);
        if (count($rows) > (int)$perPage) {
            $rows = array_slice($rows, 0, (int)$perPage);
        }
        $hasMore = ((int)$pageNo * (int)$perPage) < $totalFiltered;
        $totalPages = $perPage > 0 ? (int)ceil($totalFiltered / (int)$perPage) : 1;

        $this->clearBufferedHttpOutput();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'data' => $rows,
            'current_page' => $pageNo,
            'has_more' => $hasMore,
            'per_page' => (int)$perPage,
            'total_rows' => $totalFiltered,
            'total_pages' => $totalPages
        ]);
        exit;
    }
    function cleanValue($value)
    {
        if (!$value) return '';

        // remove anything starting with (uuid OR (HTTP
        $value = preg_replace('/\((uuid|HTTP).*$/', '', $value);

        return trim($value);
    }
    function fixImageUrl($path)
    {
        if ($path === null || $path === '') {
            return '';
        }

        $path = trim((string)$path);
        if ($path === '') {
            return '';
        }
        // Protocol-relative URL
        if (strpos($path, '//') === 0) {
            return 'https:' . $path;
        }
        // Already absolute
        if (strpos($path, 'http') === 0) {
            return $path;
        }

        // Relative path → CDN (ensure single slash join)
        $suffix = $path[0] === '/' ? $path : '/' . $path;
        return 'https://cdn.exoticindia.com' . $suffix;
    }

    /**
     * Product API sometimes returns fields at the root or under a nested "data" object.
     */
    private function unwrapProductApiResponse(array $data): array
    {
        if (!empty($data['data']) && is_array($data['data'])) {
            $inner = $data['data'];
            unset($data['data']);
            // Wrapper keys first; nested product object wins on conflicts (non-empty image/name).
            return array_merge($data, $inner);
        }
        return $data;
    }

    /** Prefer cleaned API text; fallback to VP row when upstream is empty. */
    private function mergeProductTextField($apiRaw, $dbRaw): string
    {
        $fromApi = $this->cleanValue(is_string($apiRaw) ? $apiRaw : '');
        if ($fromApi !== '') {
            return $fromApi;
        }
        return trim((string)$dbRaw);
    }

    /** Size/color for cart: trust vp_products row first (variant facets), then API. */
    private function posProductFacetFromDbFirst(array $dbRow, array $apiData, string $field): string
    {
        $dbVal = trim((string)($dbRow[$field] ?? ''));
        if ($dbVal !== '' && $dbVal !== '0' && strcasecmp($dbVal, 'n/a') !== 0) {
            return $dbVal;
        }

        return $this->mergeProductTextField($apiData[$field] ?? '', $dbRow[$field] ?? '');
    }

    /** GST % for POS: prefer API gst_rate / gst_percent / gst, else vp_products.gst. */
    private function mergeGstPercentField(array $apiData, array $dbRow): string
    {
        foreach (['gst_rate', 'gst_percent', 'gst'] as $k) {
            if (!array_key_exists($k, $apiData)) {
                continue;
            }
            $v = $apiData[$k];
            if ($v === null || $v === '') {
                continue;
            }
            if (is_string($v)) {
                $v = preg_replace('/\s*%?\s*$/', '', trim($v));
            }
            if (is_numeric($v)) {
                $f = (float)$v;

                return $f == (int)$f ? (string)(int)$f : rtrim(rtrim(sprintf('%.4f', $f), '0'), '.');
            }
        }

        return trim((string)($dbRow['gst'] ?? ''));
    }

    /** India MRP (₹): prefer API keys, then vp_products.mrp_india. */
    private function mergeMrpRupee(array $apiData, array $dbRow): float
    {
        foreach (['mrp_india', 'mrp', 'max_retail_price', 'list_price', 'mrp_inr'] as $k) {
            if (!array_key_exists($k, $apiData)) {
                continue;
            }
            $v = $apiData[$k];
            if ($v === null || $v === '') {
                continue;
            }
            if (is_numeric($v)) {
                $f = (float)$v;
                if ($f > 0) {
                    return $f;
                }
            }
        }

        $db = isset($dbRow['mrp_india']) ? (float)$dbRow['mrp_india'] : 0.0;

        return $db > 0 ? $db : 0.0;
    }

    /** Resolved GST percent for pricing (same rules as gst_percent label). Returns 0 when unknown. */
    private function resolveGstPercentAsNumber(array $apiData, array $dbRow): float
    {
        $s = trim($this->mergeGstPercentField($apiData, $dbRow));
        if ($s === '') {
            return 0.0;
        }
        $n = (float)$s;

        return $n > 0 ? $n : 0.0;
    }

    /** Multiply unit price by (1 + GST%/100); base is treated as taxable value when GST % is present. */
    private function applyGstInclusiveToUnitPrice(float $base, array $dbRow, array $apiData = []): float
    {
        if ($base <= 0) {
            return $base;
        }
        $pct = $this->resolveGstPercentAsNumber($apiData, $dbRow);
        if ($pct <= 0) {
            return $base;
        }

        return round($base * (1 + $pct / 100), 2);
    }

    /** First positive amount from named keys on an API payload (same keys used elsewhere for catalog sync). */
    private function pickPositivePriceFromApiArray(array $data): float
    {
        foreach (['price_india', 'price_india_suggested', 'price', 'itemprice', 'finalprice'] as $k) {
            if (!array_key_exists($k, $data)) {
                continue;
            }
            $v = $data[$k];
            if ($v === null || $v === '') {
                continue;
            }
            $f = (float)$v;
            if ($f > 0) {
                return $f;
            }
        }

        return 0.0;
    }

    /** POS uses India retail when present (vp_products.price_india), else API / itemprice. */
    private function mergeSellingPrice(array $apiData, array $dbRow): float
    {
        $fromApi = $this->pickPositivePriceFromApiArray($apiData);
        if ($fromApi > 0) {
            return $fromApi;
        }
        foreach (['price_india', 'price_india_suggested', 'finalprice', 'itemprice'] as $k) {
            if (empty($dbRow[$k])) {
                continue;
            }
            $f = (float)$dbRow[$k];
            if ($f > 0) {
                return $f;
            }
        }

        return 0.0;
    }

    /**
     * Base unit (₹) for POS product modal: prefer local vp_products price_india / price_india_suggested
     * (treated as ex-GST), then fall back to mergeSellingPrice (API + DB). Upstream /product/code
     * prices are often already GST-inclusive; using them as input to applyGstInclusiveToUnitPrice
     * double-applied tax (wrong display: (base + GST) * (1 + GST%)).
     */
    private function mergePosProductSellingBaseExGst(array $apiData, array $dbRow): float
    {
        foreach (['price_india', 'price_india_suggested'] as $k) {
            if (empty($dbRow[$k])) {
                continue;
            }
            $f = (float)$dbRow[$k];
            if ($f > 0) {
                return $f;
            }
        }

        return $this->mergeSellingPrice($apiData, $dbRow);
    }

    /**
     * India retail unit price from vp_products for a cart/API product code (sku or item_code).
     */
    private function resolveIndiaSellPriceFromVp($conn, string $code): float
    {
        if ($code === '' || !$conn) {
            return 0.0;
        }
        $stmt = $conn->prepare(
            'SELECT price_india, price_india_suggested, finalprice, itemprice, gst
             FROM vp_products WHERE is_active = 1 AND (sku = ? OR item_code = ?)'
            . self::VP_PRODUCT_BY_CODE_ORDER_SQL . ' LIMIT 1'
        );
        if (!$stmt) {
            return 0.0;
        }
        $stmt->bind_param('sss', $code, $code, $code);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            return 0.0;
        }
        foreach (['price_india', 'price_india_suggested', 'finalprice', 'itemprice'] as $k) {
            $f = (float)($row[$k] ?? 0);
            if ($f > 0) {
                return $this->applyGstInclusiveToUnitPrice($f, $row, []);
            }
        }

        return 0.0;
    }

    /**
     * vp_products.gst fallback only (matches mergeGstPercentField / POS product modal).
     */
    private function fetchVpProductGstFallbackRow($conn, string $code): array
    {
        if ($code === '' || !$conn) {
            return [];
        }
        $stmt = $conn->prepare(
            'SELECT gst FROM vp_products WHERE is_active = 1 AND (sku = ? OR item_code = ?)'
            . self::VP_PRODUCT_BY_CODE_ORDER_SQL . ' LIMIT 1'
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('sss', $code, $code, $code);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return is_array($row) ? $row : [];
    }

    /** First usable image path/URL from a /product/code JSON payload. */
    private function pickRawImageFromProductApiArray(array $data): string
    {
        foreach (['image', 'image_url', 'thumbnail', 'thumb', 'imageurl'] as $k) {
            if (!empty($data[$k]) && is_string($data[$k])) {
                $v = trim($data[$k]);
                if ($v !== '') {
                    return $v;
                }
            }
        }
        return '';
    }

    /**
     * All active VP product ids whose sku or item_code matches (multiple rows per style/variant).
     *
     * @return array<int, int>
     */
    private function resolveVpProductIdsForStockLookup($conn, string $code): array
    {
        if ($code === '' || !$conn) {
            return [];
        }
        $stmt = $conn->prepare(
            'SELECT id FROM vp_products WHERE is_active = 1 AND (sku = ? OR item_code = ?) ORDER BY id ASC'
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('ss', $code, $code);
        $stmt->execute();
        $res = $stmt->get_result();
        $ids = [];
        while ($row = $res->fetch_assoc()) {
            if (!empty($row['id'])) {
                $ids[] = (int)$row['id'];
            }
        }
        $stmt->close();

        return $ids;
    }

    /**
     * Latest movement row for product + warehouse (same join as POS stock): qty + bin/shelf location.
     *
     * @return array{running_stock: float, location: string}
     */
    private function getWarehouseStockSnapshotForProductId($conn, int $productId, int $warehouseId): array
    {
        $empty = ['running_stock' => 0.0, 'location' => ''];
        if ($productId <= 0 || $warehouseId <= 0 || !$conn) {
            return $empty;
        }
        $sql = '
            SELECT sm.running_stock, sm.location
            FROM vp_stock_movements sm
            INNER JOIN (
                SELECT product_id, MAX(id) AS max_id
                FROM vp_stock_movements
                WHERE warehouse_id = ?
                GROUP BY product_id
            ) latest ON latest.product_id = sm.product_id AND latest.max_id = sm.id
            WHERE sm.warehouse_id = ? AND sm.product_id = ?
            LIMIT 1';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return $empty;
        }
        $stmt->bind_param('iii', $warehouseId, $warehouseId, $productId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row || !array_key_exists('running_stock', $row)) {
            return $empty;
        }

        return [
            'running_stock' => (float)$row['running_stock'],
            'location' => trim((string)($row['location'] ?? '')),
        ];
    }

    /** Latest running_stock for one SKU at one warehouse (same basis as POS product grid). */
    private function getWarehouseStockForProductId($conn, int $productId, int $warehouseId): float
    {
        return $this->getWarehouseStockSnapshotForProductId($conn, $productId, $warehouseId)['running_stock'];
    }

    /** Sum of latest running_stock per warehouse for this product (all locations). */
    private function getTotalStockAcrossWarehouses($conn, int $productId): float
    {
        if ($productId <= 0 || !$conn) {
            return 0.0;
        }
        $sql = '
            SELECT COALESCE(SUM(sm.running_stock), 0) AS t
            FROM vp_stock_movements sm
            INNER JOIN (
                SELECT warehouse_id, product_id, MAX(id) AS max_id
                FROM vp_stock_movements
                WHERE product_id = ?
                GROUP BY warehouse_id, product_id
            ) latest ON sm.warehouse_id = latest.warehouse_id
                AND sm.product_id = latest.product_id
                AND sm.id = latest.max_id
            WHERE sm.product_id = ?';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return 0.0;
        }
        $stmt->bind_param('ii', $productId, $productId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return isset($row['t']) ? (float)$row['t'] : 0.0;
    }

    /** Default warehouse row from exotic_address (POS / GRN “default store”). */
    private function getDefaultWarehouseRow($conn): ?array
    {
        if (!$conn) {
            return null;
        }
        $stmt = $conn->prepare(
            'SELECT id, address_title FROM exotic_address WHERE is_active = 1 AND is_default = 1 ORDER BY id ASC LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (empty($row['id'])) {
            return null;
        }

        return [
            'id' => (int)$row['id'],
            'address_title' => trim((string)($row['address_title'] ?? '')),
        ];
    }

    /**
     * Single-line footer text from exotic_address row marked is_default (receipt / printouts).
     */
    private function getDefaultExoticAddressFooterString(mysqli $conn): string
    {
        $stmt = $conn->prepare(
            'SELECT display_name, address_title, `address` FROM exotic_address WHERE is_active = 1 AND is_default = 1 ORDER BY id ASC LIMIT 1'
        );
        if (!$stmt) {
            return '';
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            return '';
        }
        $disp = trim((string)($row['display_name'] ?? ''));
        $title = trim((string)($row['address_title'] ?? ''));
        $addr = trim(preg_replace('/\s+/u', ' ', strip_tags((string)($row['address'] ?? ''))));
        $parts = [];
        if ($disp !== '') {
            $parts[] = $disp;
        }
        if ($addr !== '') {
            $parts[] = $addr;
        } elseif ($title !== '') {
            $parts[] = $title;
        }

        return trim(implode(', ', $parts));
    }

    /**
     * @return string|null Error message, or null if OK / validation skipped (no VP row / no warehouse).
     */
    private function validateQtyAgainstWarehouse($conn, string $code, int $qty): ?string
    {
        if ($qty < 1) {
            return null;
        }
        $warehouseId = (int)($_SESSION['warehouse_id'] ?? 0);
        if ($warehouseId <= 0) {
            return null;
        }
        $ids = $this->resolveVpProductIdsForStockLookup($conn, trim($code));
        if ($ids === []) {
            return null;
        }
        $avail = 0.0;
        foreach ($ids as $pid) {
            $avail += $this->getWarehouseStockForProductId($conn, $pid, $warehouseId);
        }
        $maxAllowed = (int)floor($avail);
        if ($qty > $maxAllowed) {
            return 'Quantity cannot exceed available stock in this warehouse (' . $maxAllowed . ' available).';
        }

        return null;
    }

    /**
     * Other VP rows with the same item_code (excluding the opened variant), with warehouse stock when available.
     *
     * @return array<int, array{id:int, sku:string, title:string, stock_qty:float|int}>
     */
    private function fetchSiblingSkusByItemCode($conn, string $itemCode, string $excludeSku, int $warehouseId): array
    {
        if ($itemCode === '' || !$conn || $excludeSku === '') {
            return [];
        }

        if ($warehouseId <= 0) {
            $sql = 'SELECT id, sku, title, 0 AS stock_qty
                    FROM vp_products
                    WHERE is_active = 1
                      AND LOWER(TRIM(IFNULL(item_level, \'\'))) <> \'parent\'
                      AND item_code = ? AND sku <> ?
                    ORDER BY sku ASC';
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return [];
            }
            $stmt->bind_param('ss', $itemCode, $excludeSku);
            $stmt->execute();
            $res = $stmt->get_result();
            $out = [];
            while ($row = $res->fetch_assoc()) {
                $out[] = [
                    'id' => (int)($row['id'] ?? 0),
                    'sku' => (string)($row['sku'] ?? ''),
                    'title' => (string)($row['title'] ?? ''),
                    'stock_qty' => (float)($row['stock_qty'] ?? 0),
                ];
            }
            $stmt->close();

            return $out;
        }

        $sql = '
            SELECT p.id, p.sku, p.title, COALESCE(sm.running_stock, 0) AS stock_qty
            FROM vp_products p
            LEFT JOIN (
                SELECT sm1.product_id, sm1.running_stock
                FROM vp_stock_movements sm1
                INNER JOIN (
                    SELECT product_id, MAX(id) AS max_id
                    FROM vp_stock_movements
                    WHERE warehouse_id = ?
                    GROUP BY product_id
                ) latest ON latest.product_id = sm1.product_id AND latest.max_id = sm1.id
                WHERE sm1.warehouse_id = ?
            ) sm ON sm.product_id = p.id
            WHERE p.is_active = 1
              AND LOWER(TRIM(IFNULL(p.item_level, \'\'))) <> \'parent\'
              AND p.item_code = ? AND p.sku <> ?
            ORDER BY p.sku ASC';

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('iiss', $warehouseId, $warehouseId, $itemCode, $excludeSku);
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        while ($row = $res->fetch_assoc()) {
            $out[] = [
                'id' => (int)($row['id'] ?? 0),
                'sku' => (string)($row['sku'] ?? ''),
                'title' => (string)($row['title'] ?? ''),
                'stock_qty' => (float)($row['stock_qty'] ?? 0),
            ];
        }
        $stmt->close();

        return $out;
    }

    public function siblingSkusAjax(): void
    {
        $this->clearBufferedHttpOutput();
        header('Content-Type: application/json');

        $itemCode = isset($_GET['item_code']) ? trim((string)$_GET['item_code']) : '';
        $excludeSku = isset($_GET['exclude_sku']) ? trim((string)$_GET['exclude_sku']) : '';
        global $conn;
        $warehouseId = (int)($_SESSION['warehouse_id'] ?? 0);

        echo json_encode([
            'data' => $this->fetchSiblingSkusByItemCode($conn, $itemCode, $excludeSku, $warehouseId),
        ]);
        exit;
    }

    public function getProductApi()
    {
        $code = isset($_GET['code']) ? trim((string)$_GET['code']) : '';

        if ($code === '') {
            $this->clearBufferedHttpOutput();
            header('Content-Type: application/json');
            echo json_encode(['status' => false]);
            exit;
        }

        global $conn;
        $dbItemCode = '';
        $dbSku = '';
        $dbImageRaw = '';
        $dbRow = [];
        if (!empty($conn)) {
            $stmt = $conn->prepare(
                'SELECT id, item_code, sku, title, image, material, size, color, hsn, gst,
                        price_india, price_india_suggested, itemprice, finalprice, mrp_india,
                        product_weight, product_weight_unit,
                        prod_height, prod_width, prod_length, length_unit, item_level
                 FROM vp_products WHERE is_active = 1
                   AND (sku = ? OR item_code = ?)'
                . self::VP_PRODUCT_BY_CODE_ORDER_SQL . ' LIMIT 1'
            );
            if ($stmt) {
                $stmt->bind_param('sss', $code, $code, $code);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($row) {
                    $dbRow = $row;
                    $dbItemCode = trim((string)($row['item_code'] ?? ''));
                    $dbSku = trim((string)($row['sku'] ?? ''));
                    $dbImageRaw = trim((string)($row['image'] ?? ''));
                }
            }
        }

        $res = $this->exotic_api_call('/product/code', 'GET', ['code' => $code]);

        $data = $this->unwrapProductApiResponse($res['data'] ?? []);
        $apiImageRaw = $this->pickRawImageFromProductApiArray($data);
        $imageResolved = $this->fixImageUrl($apiImageRaw);
        $imageFromDb = $this->fixImageUrl($dbImageRaw);

        if ($imageResolved === '' && $imageFromDb !== '') {
            $imageResolved = $imageFromDb;
        }

        $data2 = null;
        $sellingPrice = $this->mergePosProductSellingBaseExGst($data, $dbRow);
        $variantDiffers = ($dbItemCode !== '' && strcasecmp($dbItemCode, $code) !== 0);
        // One base item_code fetch when variant response is missing image, price, MRP, or GST (avoids 2–3 sequential /product/code calls).
        if ($variantDiffers) {
            $mrpFromVariant = $this->mergeMrpRupee($data, $dbRow);
            $gstFromVariant = $this->resolveGstPercentAsNumber($data, $dbRow);
            $needBaseFetch =
                ($imageResolved === '')
                || ($sellingPrice <= 0)
                || ($mrpFromVariant <= 0)
                || ($gstFromVariant <= 0);
            if ($needBaseFetch) {
                $res2 = $this->exotic_api_call('/product/code', 'GET', ['code' => $dbItemCode]);
                $data2 = $this->unwrapProductApiResponse($res2['data'] ?? []);
                if ($imageResolved === '') {
                    $imgBase = $this->fixImageUrl($this->pickRawImageFromProductApiArray($data2));
                    if ($imgBase !== '') {
                        $imageResolved = $imgBase;
                    }
                }
                if ($sellingPrice <= 0) {
                    $altSell = $this->mergePosProductSellingBaseExGst($data2, $dbRow);
                    if ($altSell > 0) {
                        $sellingPrice = $altSell;
                    }
                }
            }
        }

        if ($imageResolved === '' && $imageFromDb !== '') {
            $imageResolved = $imageFromDb;
        }

        $gstApiForSell = $data;
        if ($this->resolveGstPercentAsNumber($data, $dbRow) <= 0 && $data2 !== null) {
            $gstApiForSell = $data2;
        }
        $sellingPrice = $this->applyGstInclusiveToUnitPrice($sellingPrice, $dbRow, $gstApiForSell);

        $dimApi = $this->cleanValue($data['dimensions'] ?? '');
        $dbH = isset($dbRow['prod_height']) ? trim((string)$dbRow['prod_height']) : '';
        $dbW = isset($dbRow['prod_width']) ? trim((string)$dbRow['prod_width']) : '';
        $dbL = isset($dbRow['prod_length']) ? trim((string)$dbRow['prod_length']) : '';
        $dbLu = isset($dbRow['length_unit']) ? trim((string)$dbRow['length_unit']) : '';
        $builtDbDims = '';
        if ($dbH !== '' || $dbW !== '' || $dbL !== '') {
            $builtDbDims = implode(' × ', array_filter([$dbH, $dbW, $dbL], static function ($x) {
                return trim((string)$x) !== '';
            }));
            if ($builtDbDims !== '' && $dbLu !== '') {
                $builtDbDims .= ' ' . $dbLu;
            }
        }
        $dimensionsMerged = $dimApi !== '' ? $dimApi : $builtDbDims;

        $wKgApi = trim((string)($data['product_weight_kg'] ?? ''));
        $dbPw = isset($dbRow['product_weight']) ? trim((string)$dbRow['product_weight']) : '';
        $dbPwu = isset($dbRow['product_weight_unit']) ? trim((string)$dbRow['product_weight_unit']) : '';

        $warehouseId = (int)($_SESSION['warehouse_id'] ?? 0);
        $currentWarehouseName = '';
        if ($warehouseId > 0 && !empty($conn)) {
            require_once 'models/user/user.php';
            $usersModel = new User($conn);
            $whRow = $usersModel->getWarehouseById($warehouseId);
            if (!empty($whRow)) {
                $currentWarehouseName = trim((string)($whRow['address_title'] ?? ''));
            }
        }

        $vpId = isset($dbRow['id']) ? (int)$dbRow['id'] : 0;
        $warehouseLocationOut = '';
        if ($vpId > 0 && $warehouseId > 0) {
            $snap = $this->getWarehouseStockSnapshotForProductId($conn, $vpId, $warehouseId);
            $stockQtyOut = $snap['running_stock'];
            $warehouseLocationOut = $snap['location'];
        } else {
            $stockQtyOut = $data['stock'] ?? 0;
        }

        $siblingSkus = [];
        if ($dbItemCode !== '') {
            $siblingSkus = $this->fetchSiblingSkusByItemCode($conn, $dbItemCode, trim($code), $warehouseId);
        }

        $totalQtyAllWarehouses = null;
        $defaultStoreQty = null;
        $defaultStoreName = '';
        if ($vpId > 0) {
            $totalQtyAllWarehouses = $this->getTotalStockAcrossWarehouses($conn, $vpId);
            $defWh = $this->getDefaultWarehouseRow($conn);
            if ($defWh !== null) {
                $defaultStoreName = $defWh['address_title'];
                $defaultStoreQty = $this->getWarehouseStockSnapshotForProductId($conn, $vpId, (int)$defWh['id'])['running_stock'];
            }
        }

        $mrpOut = $this->mergeMrpRupee($data, $dbRow);
        if ($mrpOut <= 0 && $data2 !== null) {
            $altMrp = $this->mergeMrpRupee($data2, $dbRow);
            if ($altMrp > 0) {
                $mrpOut = $altMrp;
            }
        }

        // echo '<pre>'; print_r($data['addon_options']); exit;
        $product = [
            'requested_code' => $code,
            'item_code' => $dbItemCode,
            'sku' => $dbSku,
            'title' => $this->mergeProductTextField($data['name'] ?? '', $dbRow['title'] ?? ''),
            'image' => $imageResolved,
            'price' => $sellingPrice,

            'material' => $this->mergeProductTextField($data['material'] ?? '', $dbRow['material'] ?? ''),
            'size' => $this->posProductFacetFromDbFirst($dbRow, $data, 'size'),
            'color' => $this->posProductFacetFromDbFirst($dbRow, $data, 'color'),
            'hsn' => $this->mergeProductTextField($data['hsn'] ?? '', $dbRow['hsn'] ?? ''),
            'gst_percent' => $this->mergeGstPercentField($data, $dbRow),
            'mrp' => $mrpOut,

            //  NEW FIELDS (warehouse running stock when VP row + session warehouse match POS grid)
            'stock_qty' => $stockQtyOut,
            'warehouse_location' => $warehouseLocationOut,
            'total_qty_available' => $totalQtyAllWarehouses,
            'current_warehouse_name' => $currentWarehouseName,
            'default_store_qty' => $defaultStoreQty,
            'default_store_name' => $defaultStoreName,
            'maincategory' => $data['maincategory'] ?? '',
            'dimensions' => $dimensionsMerged,
            'weight' => $wKgApi,
            'product_weight' => $dbPw,
            'product_weight_unit' => $dbPwu,
            'prod_height' => $dbH,
            'prod_width' => $dbW,
            'prod_length' => $dbL,
            'length_unit' => $dbLu,
            'express_shipping_cost' => $data['express_shipping_cost'] ?? 0,
            'express_shipping_option' => $data['express_shipping_option'] ?? null,
            'addon_options' => $data['addon_options'] ?? [],
            'sibling_skus' => $siblingSkus,
            'item_level' => trim((string)($dbRow['item_level'] ?? '')),
            'is_parent_level' => $this->isParentItemLevel($dbRow['item_level'] ?? ''),
        ];

        $this->clearBufferedHttpOutput();

        header('Content-Type: application/json');

        echo json_encode(['data' => $product]);
        exit;
    }

    /**
     * Check product stock in current warehouse and alternatives.
     * Accepts product_id (preferred) or item_code/sku via `q`.
     */
    public function productAvailability()
    {
        global $conn;
        $this->clearBufferedHttpOutput();
        header('Content-Type: application/json');

        $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $currentWarehouseId = (int)($_SESSION['warehouse_id'] ?? 0);

        if ($productId <= 0 && $q === '') {
            echo json_encode(['success' => false, 'message' => 'Missing product identifier.']);
            exit;
        }

        if ($productId <= 0) {
            $sql = 'SELECT id, item_code, sku, title
                    FROM vp_products
                    WHERE is_active = 1 AND (sku = ? OR item_code = ?)'
                . self::VP_PRODUCT_BY_CODE_ORDER_SQL . ' LIMIT 1';
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Could not prepare product query.']);
                exit;
            }
            $stmt->bind_param('sss', $q, $q, $q);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'Product not found.']);
                exit;
            }
            $productId = (int)$product['id'];
        } else {
            $stmt = $conn->prepare("SELECT id, item_code, sku, title FROM vp_products WHERE id = ? LIMIT 1");
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Could not prepare product query.']);
                exit;
            }
            $stmt->bind_param('i', $productId);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'Product not found.']);
                exit;
            }
        }

        $stockSql = "
            SELECT sm.warehouse_id,
                   COALESCE(ea.address_title, CONCAT('Warehouse #', sm.warehouse_id)) AS warehouse_name,
                   sm.running_stock AS stock_qty
            FROM vp_stock_movements sm
            INNER JOIN (
                SELECT warehouse_id, product_id, MAX(id) AS max_id
                FROM vp_stock_movements
                WHERE product_id = ?
                GROUP BY warehouse_id, product_id
            ) latest ON latest.max_id = sm.id
            LEFT JOIN exotic_address ea ON ea.id = sm.warehouse_id
            WHERE sm.product_id = ?
            ORDER BY warehouse_name ASC";

        $stockStmt = $conn->prepare($stockSql);
        if (!$stockStmt) {
            echo json_encode(['success' => false, 'message' => 'Could not prepare stock query.']);
            exit;
        }
        $stockStmt->bind_param('ii', $productId, $productId);
        $stockStmt->execute();
        $rows = $stockStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stockStmt->close();

        $currentWarehouse = null;
        $alternativeWarehouses = [];
        foreach ($rows as $row) {
            $wid = (int)($row['warehouse_id'] ?? 0);
            $stockQty = (float)($row['stock_qty'] ?? 0);
            $entry = [
                'warehouse_id' => $wid,
                'warehouse_name' => (string)($row['warehouse_name'] ?? ''),
                'stock_qty' => $stockQty,
            ];
            if ($wid === $currentWarehouseId) {
                $currentWarehouse = $entry;
            } elseif ($stockQty > 0) {
                $alternativeWarehouses[] = $entry;
            }
        }

        if ($currentWarehouse === null) {
            $currentWarehouse = [
                'warehouse_id' => $currentWarehouseId,
                'warehouse_name' => 'Current Store',
                'stock_qty' => 0,
            ];
        }

        $currentAvailable = ((float)$currentWarehouse['stock_qty']) > 0;
        $message = '';
        if (!$currentAvailable && !empty($alternativeWarehouses)) {
            $altNames = array_values(array_filter(array_map(static function ($w) {
                return trim((string)($w['warehouse_name'] ?? ''));
            }, $alternativeWarehouses)));
            $message = 'Product not available in this store, but you still can create an order from another store (' . implode(', ', $altNames) . ')';
        }

        echo json_encode([
            'success' => true,
            'product' => [
                'id' => (int)$product['id'],
                'item_code' => (string)($product['item_code'] ?? ''),
                'sku' => (string)($product['sku'] ?? ''),
                'title' => (string)($product['title'] ?? ''),
            ],
            'current_warehouse' => $currentWarehouse,
            'current_available' => $currentAvailable,
            'alternative_warehouses' => $alternativeWarehouses,
            'message' => $message,
        ]);
        exit;
    }
    /**
     * Same-origin JSON proxy for Exotic retail cart endpoints (browser cannot send x-api-* headers / CORS).
     * Forwards to https://www.exoticindia.com/api via exotic_api_call().
     */
    public function cartApi(): void
    {
        is_login();
        $this->clearBufferedHttpOutput();
        header('Content-Type: application/json; charset=utf-8');

        $op = trim((string)($_REQUEST['op'] ?? ''));
        switch ($op) {
            case 'retrieve':
                // Same discount / gift query + header as add/modifyqty so cart totals reflect applied coupon.
                $ctx = $this->exoticCartDiscountContext();
                $this->emitCartApiResponse($this->exotic_api_call(
                    '/cart/retrieve',
                    'GET',
                    $ctx['query'],
                    null,
                    null,
                    $ctx['extraHeaders']
                ));
                return;

            case 'add':
                if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
                    echo json_encode(['success' => false, 'message' => 'POST required'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                    exit;
                }
                $raw = (string)file_get_contents('php://input');
                $body = json_decode($raw, true);
                if (!is_array($body)) {
                    $body = $_POST;
                }
                global $conn;
                if ($conn instanceof mysqli) {
                    $parentBlock = $this->cartAddBlockedIfParentLevelProduct(
                        $conn,
                        is_array($body) ? $body : []
                    );
                    if ($parentBlock !== null) {
                        echo json_encode($parentBlock, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                        exit;
                    }
                }
                $split = $this->buildExoticCartAddSplit(is_array($body) ? $body : []);
                $ctxAdd = $this->exoticCartDiscountContext();
                $primaryRes = $this->exotic_api_call(
                    '/cart/add',
                    'POST',
                    $split['query'],
                    $split['post'],
                    null,
                    $ctxAdd['extraHeaders']
                );
                $addRes = $primaryRes;
                $upstream = [
                    'api_base' => 'https://www.exoticindia.com/api',
                    'endpoint' => 'POST /cart/add',
                    'browser_request_json' => is_array($body) ? $body : ['_raw' => $raw],
                    'discount_query_merged_into_url' => $ctxAdd['query'],
                    'extra_headers' => $ctxAdd['extraHeaders'],
                    'attempts' => [
                        [
                            'label' => 'primary',
                            'request_url' => $this->exoticCartAddPublicUrl($split['query']),
                            'post_body' => $split['post'],
                            'response' => $this->compactUpstreamCartSnapshot($primaryRes),
                        ],
                    ],
                ];
                $this->emitCartApiResponse($addRes, ['upstream' => $upstream]);

                return;

            case 'modifyqty':
                // GET /api/cart/modifyqty?cartid=&newqty=&variationqtylist=&discountcoupondetails=
                // (cart line value is cartref in retrieve JSON; query param name is cartid.)
                $cartid = $this->resolveCartLineIdFromRequest();
                $newqty = trim((string)($_GET['newqty'] ?? $_REQUEST['newqty'] ?? $_GET['qty'] ?? $_REQUEST['qty'] ?? ''));
                $variationqtylist = trim((string)($_GET['variationqtylist'] ?? $_REQUEST['variationqtylist'] ?? ''));
                $discountStr = $this->getSessionDiscountCouponDetailsString();
                $query = [
                    'cartid' => $cartid,
                    'newqty' => $newqty,
                ];
                if ($variationqtylist !== '') {
                    $query['variationqtylist'] = $variationqtylist;
                }
                if ($discountStr !== '') {
                    $query['discountcoupondetails'] = $discountStr;
                }
                $extraHeaders = $discountStr !== ''
                    ? ['x-api-discountcoupondetails:' . $discountStr]
                    : [];
                $this->emitCartApiResponse($this->exotic_api_call('/cart/modifyqty', 'GET', $query, null, null, $extraHeaders));
                return;

            case 'delete':
                $cartid = $this->resolveCartLineIdFromRequest();
                $this->emitCartApiResponse($this->exotic_api_call('/cart/delete', 'GET', [
                    'cartid' => $cartid,
                ]));
                return;

            case 'addcoupon':
                $couponId = trim((string)($_GET['couponid'] ?? $_REQUEST['couponid'] ?? ''));
                $res = $this->exotic_api_call('/cart/addcoupon', 'GET', [
                    'couponid' => $couponId,
                ]);
                if ($this->isExoticCartSuccess($res)) {
                    $data = is_array($res['data'] ?? null) ? $res['data'] : [];
                    $details = $data['discountcoupondetails'] ?? $data['discount_coupon_details'] ?? null;
                    if ($details !== null && $details !== '') {
                        if (is_array($details)) {
                            $_SESSION['pos_exotic_cart_coupon_details'] = json_encode($details, JSON_UNESCAPED_UNICODE);
                            $_SESSION['discount_coupon'] = ['discountcoupondetails' => $details];
                        } else {
                            $_SESSION['pos_exotic_cart_coupon_details'] = (string)$details;
                            $_SESSION['discount_coupon'] = ['discountcoupondetails' => (string)$details];
                        }
                    } elseif ($couponId !== '') {
                        // API may succeed without echoing details; still attach code so retrieve/add/modifyqty send context.
                        $_SESSION['pos_exotic_cart_coupon_details'] = $couponId;
                        $_SESSION['discount_coupon'] = ['discountcoupondetails' => $couponId];
                    }
                }
                $this->emitCartApiResponse($res);
                return;

            case 'removecoupon':
                unset($_SESSION['pos_exotic_cart_coupon_details'], $_SESSION['discount_coupon']);
                $remoteRm = $this->exotic_api_call('/cart/removecoupon', 'GET', []);
                if ($this->isExoticCartSuccess($remoteRm)) {
                    $this->emitCartApiResponse($remoteRm);

                    return;
                }
                // Session is cleared so add/modify no longer sends discountcoupondetails; client still refreshes retrieve.
                echo json_encode([
                    'success' => true,
                    'http_code' => 200,
                    'data' => array_merge(
                        is_array($remoteRm['data'] ?? null) ? $remoteRm['data'] : [],
                        ['message' => 'Coupon cleared for this terminal.']
                    ),
                    'raw' => (string)($remoteRm['raw'] ?? ''),
                ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                exit;

            case 'customdiscount':
                $customReduce = (string)($_GET['custom_reduce'] ?? $_REQUEST['custom_reduce'] ?? '0');
                if ($customReduce === '' || (float)$customReduce <= 0) {
                    unset($_SESSION['pos_exotic_cart_custom_reduce']);
                    $customReduce = '0';
                } else {
                    $_SESSION['pos_exotic_cart_custom_reduce'] = $customReduce;
                }
                $this->emitCartApiResponse($this->exotic_api_call('/cart/addcustomdiscount', 'GET', [
                    'custom_reduce' => $customReduce,
                ]));
                return;

            default:
                echo json_encode(['success' => false, 'message' => 'Unknown cart op'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                exit;
        }
    }

    /**
     * Build Exotic POST /api/cart/add request: query params per API collection, form body buynow/code/qty/variation/options.
     *
     * @param array<string, mixed> $body Decoded JSON from POS (code, qty, variation, options, optional buynow 0|1).
     * @return array{query: array<string, string>, post: array<string, string>}
     */
    private function buildExoticCartAddSplit(array $body): array
    {
        $code = trim((string)($body['code'] ?? ''));
        $qty = (int)($body['qty'] ?? 1);
        if ($qty < 1) {
            $qty = 1;
        }

        // Required: 0 = add to cart, 1 = buy now (https://www.exoticindia.com/api/cart/add)
        $buynow = '0';
        if (array_key_exists('buynow', $body)) {
            $bn = (int)$body['buynow'];
            $buynow = $bn === 1 ? '1' : '0';
        }

        $post = [
            'buynow' => $buynow,
            'code' => $code,
            'qty' => (string)$qty,
        ];

        $variation = isset($body['variation']) ? trim((string)$body['variation']) : '';
        if ($variation !== '') {
            $post['variation'] = $variation;
        }
        if (isset($body['options']) && trim((string)$body['options']) !== '') {
            $post['options'] = (string)$body['options'];
        }

        $stockCheck = isset($body['stock_check_code']) ? trim((string)$body['stock_check_code']) : '';
        if ($stockCheck !== '') {
            $post['stock_check_code'] = $stockCheck;
        }

        $ctx = $this->exoticCartDiscountContext();

        return ['query' => $ctx['query'], 'post' => $post];
    }

    /**
     * Query params + optional x-api-discountcoupondetails header for Exotic cart GETs that must reflect coupons.
     * Omit empty discount/gift params — sending e.g. discountcoupondetails= with no value can break /cart/add upstream.
     *
     * @return array{query: array<string, string>, extraHeaders: list<string>}
     */
    private function exoticCartDiscountContext(): array
    {
        $discountStr = trim($this->getSessionDiscountCouponDetailsString());
        $giftStr = '';
        if (!empty($_SESSION['pos_exotic_cart_gift_voucher'])) {
            $gv = $_SESSION['pos_exotic_cart_gift_voucher'];
            $giftStr = is_string($gv) ? trim($gv) : trim(json_encode($gv, JSON_UNESCAPED_UNICODE));
        } elseif (!empty($_SESSION['gift_voucher'])) {
            $gv = $_SESSION['gift_voucher'];
            $giftStr = is_string($gv) ? trim($gv) : trim(json_encode($gv, JSON_UNESCAPED_UNICODE));
        }
        $query = [];
        if ($discountStr !== '') {
            $query['discountcoupondetails'] = $discountStr;
        }
        if ($giftStr !== '') {
            $query['giftvoucherdetails'] = $giftStr;
        }
        $customReduce = trim((string)($_SESSION['pos_exotic_cart_custom_reduce'] ?? ''));
        if ($customReduce !== '' && (float)$customReduce > 0) {
            $query['custom_reduce'] = $customReduce;
        }
        $extraHeaders = [];
        if ($discountStr !== '') {
            $extraHeaders[] = 'x-api-discountcoupondetails:' . $discountStr;
        }

        return ['query' => $query, 'extraHeaders' => $extraHeaders];
    }

    private function getSessionDiscountCouponDetailsString(): string
    {
        if (!empty($_SESSION['pos_exotic_cart_coupon_details'])) {
            $dcd = $_SESSION['pos_exotic_cart_coupon_details'];

            return is_string($dcd) ? $dcd : json_encode($dcd, JSON_UNESCAPED_UNICODE);
        }
        if (!empty($_SESSION['discount_coupon']['discountcoupondetails'])) {
            $v = $_SESSION['discount_coupon']['discountcoupondetails'];

            return is_string($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE);
        }

        return '';
    }

    /**
     * Cart line id from POS (supports cartid / legacy cartref naming).
     */
    private function resolveCartLineIdFromRequest(): string
    {
        foreach (['cartid', 'cart_id', 'cartref'] as $key) {
            $v = $_GET[$key] ?? $_REQUEST[$key] ?? null;
            $t = trim((string)($v ?? ''));
            if ($t !== '') {
                return $t;
            }
        }

        return '';
    }

    /**
     * @param array{data?: mixed, code?: int, raw?: string} $res
     */
    private function isExoticCartSuccess(array $res): bool
    {
        $c = (int)($res['code'] ?? 0);
        if ($c < 200 || $c >= 300) {
            return false;
        }
        $d = $res['data'] ?? [];
        if (!is_array($d)) {
            return true;
        }
        if (array_key_exists('success', $d)) {
            $sv = $d['success'];
            if ($sv === false || $sv === 0 || $sv === '0' || $sv === 'false' || $sv === 'False') {
                return false;
            }
        }
        if (isset($d['status'])) {
            $st = strtolower((string)$d['status']);
            if (in_array($st, ['error', 'fail', 'failed'], true)) {
                return false;
            }
        }
        if (isset($d['error'])) {
            $ev = $d['error'];
            if ($ev === true) {
                return false;
            }
            if (is_string($ev) && trim($ev) !== '') {
                return false;
            }
            if (is_array($ev) && $ev !== []) {
                return false;
            }
        }

        return true;
    }

    /**
     * Turn API "error" payloads (string, list, or nested assoc) into one user-visible line.
     *
     * @param mixed $value
     */
    private function humanizeExoticApiMixedValue($value, int $depth = 0): string
    {
        if ($depth > 10) {
            return '';
        }
        if (is_string($value)) {
            $t = trim($value);

            return $t;
        }
        if (is_int($value) || is_float($value)) {
            return trim((string)$value);
        }
        if (!is_array($value)) {
            return '';
        }
        if ($value === []) {
            return '';
        }
        // List: join first few human-readable parts.
        if ($value === [] || array_keys($value) === range(0, count($value) - 1)) {
            $parts = [];
            foreach ($value as $item) {
                $s = $this->humanizeExoticApiMixedValue($item, $depth + 1);
                if ($s !== '') {
                    $parts[] = $s;
                }
                if (count($parts) >= 5) {
                    break;
                }
            }

            return implode('; ', $parts);
        }
        $msgKeys = [
            'message', 'Message', 'error', 'Error', 'errormessage', 'msg', 'reason', 'detail',
            'description', 'error_description', 'title', 'text', 'errorMessage',
            'UserMessage', 'userMessage', 'statusMessage', 'StatusMessage', 'exceptionMessage',
        ];
        foreach ($msgKeys as $k) {
            if (!array_key_exists($k, $value)) {
                continue;
            }
            $s = $this->humanizeExoticApiMixedValue($value[$k], $depth + 1);
            if ($s !== '') {
                return $s;
            }
        }
        foreach (['errors', 'Errors', 'validation', 'ValidationErrors'] as $ek) {
            if (empty($value[$ek])) {
                continue;
            }
            $s = $this->humanizeExoticApiMixedValue($value[$ek], $depth + 1);
            if ($s !== '') {
                return $s;
            }
        }
        foreach (['data', 'result', 'payload', 'response'] as $wrap) {
            if (empty($value[$wrap]) || !is_array($value[$wrap])) {
                continue;
            }
            $inner = $this->extractExoticMessageFromAssoc($value[$wrap], $depth + 1);
            if ($inner !== '') {
                return $inner;
            }
        }
        foreach ($value as $sub) {
            if (!is_array($sub)) {
                continue;
            }
            $nested = $this->humanizeExoticApiMixedValue($sub, $depth + 1);
            if ($nested !== '') {
                return $nested;
            }
        }
        foreach ($value as $item) {
            if (!is_string($item)) {
                continue;
            }
            $t = trim($item);
            if ($t !== '') {
                return $t;
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $arr
     */
    private function extractExoticMessageFromAssoc(array $arr, int $depth = 0): string
    {
        if ($depth > 10) {
            return '';
        }

        return $this->humanizeExoticApiMixedValue($arr, $depth);
    }

    /**
     * Best-effort user-facing message from Exotic cart JSON (shape varies by endpoint).
     *
     * @param array{data?: mixed, raw?: string} $res
     */
    private function extractExoticCartUserMessage(array $res): string
    {
        $d = $res['data'] ?? null;
        if (is_array($d)) {
            $msg = $this->extractExoticMessageFromAssoc($d, 0);
            if ($msg !== '') {
                return $msg;
            }
        }
        $raw = trim((string)($res['raw'] ?? ''));
        if ($raw !== '' && strpos($raw, '{') !== false) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $msg = $this->extractExoticMessageFromAssoc($decoded, 0);
                if ($msg !== '') {
                    return $msg;
                }
            }
        }
        if ($raw !== '' && strlen($raw) < 400 && strpos($raw, '<') === false) {
            return $raw;
        }

        return '';
    }

    /**
     * Full URL as sent to Exotic (GET query on /cart/add).
     *
     * @param array<string, string|int|float> $queryParams
     */
    private function exoticCartAddPublicUrl(array $queryParams): string
    {
        $base = 'https://www.exoticindia.com/api';
        $url = rtrim($base, '/') . '/cart/add';
        if ($queryParams !== []) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($queryParams);
        }

        return $url;
    }

    /**
     * Trimmed upstream response for debug JSON (same limits as proxy raw).
     *
     * @param array{data?: mixed, code?: int, raw?: string} $res
     *
     * @return array<string, mixed>
     */
    private function compactUpstreamCartSnapshot(array $res): array
    {
        $raw = (string)($res['raw'] ?? '');
        if (strlen($raw) > 65536) {
            $raw = substr($raw, 0, 65536) . '…(truncated)';
        }

        return [
            'http_code' => (int)($res['code'] ?? 0),
            'success_evaluated' => $this->isExoticCartSuccess($res),
            'message_extracted' => $this->extractExoticCartUserMessage($res),
            'data' => $res['data'] ?? [],
            'raw' => $raw,
        ];
    }

    /**
     * @param array{data?: mixed, code?: int, raw?: string} $res
     * @param array<string, mixed> $extra Merged into JSON (e.g. upstream Exotic request/response for /cart/add)
     */
    private function emitCartApiResponse(array $res, array $extra = []): void
    {
        $raw = (string)($res['raw'] ?? '');
        if (strlen($raw) > 65536) {
            $raw = substr($raw, 0, 65536) . '…(truncated)';
        }
        $ok = $this->isExoticCartSuccess($res);
        $msg = $this->extractExoticCartUserMessage($res);
        if (!$ok && $msg === '' && $raw !== '' && strpos(trim($raw), '<') !== false) {
            $plain = trim(preg_replace('/\s+/', ' ', strip_tags($raw)));
            if (strlen($plain) >= 12 && strlen($plain) <= 4000) {
                $msg = $plain;
            }
        }
        if (!$ok && $msg === '') {
            $msg = 'Cart request failed (HTTP ' . (int)($res['code'] ?? 0) . ').';
        }
        $payload = array_merge([
            'success' => $ok,
            'message' => $msg,
            'http_code' => (int)($res['code'] ?? 0),
            'data' => $res['data'] ?? [],
            'raw' => $raw,
        ], $extra);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    /**
     * @param list<string> $extraHttpHeaders Additional request headers (full "Name:value" lines).
     */
    public function exotic_api_call($endpoint, $method = 'GET', $params = [], $postData = null, ?string $apiBaseUrl = null, array $extraHttpHeaders = [])
    {
        require_once dirname(__DIR__) . '/helpers/api_call_logger.php';

        // echo "<pre>";
        // print_r($_SESSION['discount_coupon']['discountcoupondetails']);
        // exit;

        $ep = '/' . ltrim((string)$endpoint, '/');
        if (strtoupper((string)$method) === 'POST' && rtrim($ep, '/') === '/order/create'
                && is_file(dirname(__DIR__) . '/.pos_skip_exotic_order_create_api')) {
            $d = ['orderid' => 'LOCAL-' . gmdate('YmdHis')];
            $j = json_encode($d);
            api_call_log_write([
                'kind' => 'exotic_api_local_stub',
                'endpoint' => $ep,
                'method' => strtoupper((string)$method),
                'note' => '.pos_skip_exotic_order_create_api present — order/create not sent remotely',
                'response_http_code' => 200,
                'response_raw' => $j,
                'response_decoded' => $d,
            ]);

            return ['data' => $d, 'code' => 200, 'raw' => $j];
        }


        $base = $apiBaseUrl ?? 'https://www.exoticindia.com/api';
        $url = rtrim($base, '/') . $endpoint;
        if ($params) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
        }

        $encodedPostData = null;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        // $headers = [
        //     'x-api-key: aeRGoUvQLCxztK0Wzxmv9O2VRJ2H1B44',
        //     'x-api-deviceid: POS-Store_1',
        //     'x-api-appplayerid: POS-Web-Terminal',
        //     'x-api-countrycode: IN',
        //     'x-api-euid:' . ($_SESSION['user']['id'] ?? ''),
        //     'User-Agent: ExoticPOS-Web/1.0'
        // ];
        $deviceId = $this->resolveApiDeviceId();
        $headers = [
            'x-api-key: aeRGoUvQLCxztK0Wzxmv9O2VRJ2H1B44',
            'x-api-deviceid: ' . $deviceId,
            'x-api-appplayerid: POS-Web-Terminal',
            'x-api-countrycode: IN',
            // Keep API-issued euid in session; do not use local user id here.
            'x-api-euid:' . (string)($_SESSION['x_api_euid'] ?? ''),
            'User-Agent: ExoticPOS'
        ];
        // Forward optional evolving API session headers when available.
        if (!empty($_SESSION['x_api_jwt'])) {
            $headers[] = 'x-api-jwt:' . (string)$_SESSION['x_api_jwt'];
        }
        if (!empty($_SESSION['x_api_browsehistory'])) {
            $headers[] = 'x-api-browsehistory:' . (string)$_SESSION['x_api_browsehistory'];
        }
        if (!empty($_SESSION['x_api_etd'])) {
            $headers[] = 'x-api-etd:' . (string)$_SESSION['x_api_etd'];
        }
        if (!empty($_SESSION['x_api_etd_pincode'])) {
            $headers[] = 'x-api-etd-pincode:' . (string)$_SESSION['x_api_etd_pincode'];
        }
        foreach ($extraHttpHeaders as $line) {
            if (is_string($line) && $line !== '') {
                $headers[] = $line;
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $capturedHeaders = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $headerLine) use (&$capturedHeaders) {
            $len = strlen($headerLine);
            $header = explode(':', $headerLine, 2);
            if (count($header) < 2) {
                return $len;
            }
            $name = strtolower(trim($header[0]));
            if (in_array($name, ['x-api-euid', 'x-api-jwt', 'x-api-browsehistory', 'x-api-etd', 'x-api-etd-pincode'], true)) {
                $capturedHeaders[$name] = trim($header[1]);
            }
            return $len;
        });

        if ($method === 'POST' && $postData !== null) {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            if (is_array($postData)) {
                $encodedPostData = http_build_query($postData);
            } elseif (is_string($postData)) {
                $encodedPostData = $postData;
            } else {
                $encodedPostData = (string)$postData;
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedPostData);
        }

        $response = curl_exec($ch);
        //   echo '<pre>';
        // print_r($response);
        // exit;
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);

        curl_close($ch);

        if (!empty($capturedHeaders['x-api-euid'])) {
            $_SESSION['x_api_euid'] = $capturedHeaders['x-api-euid'];
        }
        if (!empty($capturedHeaders['x-api-jwt'])) {
            $_SESSION['x_api_jwt'] = $capturedHeaders['x-api-jwt'];
        }
        if (!empty($capturedHeaders['x-api-browsehistory'])) {
            $_SESSION['x_api_browsehistory'] = $capturedHeaders['x-api-browsehistory'];
        }
        if (!empty($capturedHeaders['x-api-etd'])) {
            $_SESSION['x_api_etd'] = $capturedHeaders['x-api-etd'];
        }
        if (!empty($capturedHeaders['x-api-etd-pincode'])) {
            $_SESSION['x_api_etd_pincode'] = $capturedHeaders['x-api-etd-pincode'];
        }

        $body = (string)$response;
        $decoded = json_decode($body, true);
        $data = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];

        api_call_log_write([
            'kind' => 'exotic_api_http',
            'endpoint' => $ep,
            'method' => strtoupper((string)$method),
            'base_url' => $base,
            'request_url' => $url,
            'request_headers' => api_call_log_sanitize_header_lines($headers),
            'request_query_params' => $params,
            'request_post_body' => $encodedPostData,
            'curl_error' => $curlErr !== '' ? $curlErr : null,
            'response_http_code' => $httpCode,
            'response_session_headers_from_api' => $capturedHeaders,
            'response_raw' => $body,
            'response_decoded' => $data,
        ]);

        return [
            'data' => $data,
            'code' => $httpCode,
            'raw' => $body,
        ];
    }

    /**
     * Use real store/warehouse label for x-api-deviceid.
     * Falls back to POS-Store_<warehouse_id> / POS-Store_1.
     */
    private function resolveApiDeviceId(): string
    {
        $fallbackId = (int)($_SESSION['warehouse_id'] ?? 0);
        if ($fallbackId <= 0) {
            $fallbackId = 1;
        }
        $fallback = 'POS-Store_' . $fallbackId;

        if (empty($_SESSION['warehouse_id'])) {
            return $fallback;
        }

        global $conn;
        if (empty($conn)) {
            return $fallback;
        }

        try {
            $usersModel = new User($conn);
            $warehouse = $usersModel->getWarehouseById((int)$_SESSION['warehouse_id']);
            $name = trim((string)($warehouse['address_title'] ?? ''));
            if ($name === '') {
                return $fallback;
            }
            // Header-safe normalization.
            $name = preg_replace('/\s+/', '_', $name);
            $name = preg_replace('/[^A-Za-z0-9_\-]/', '', (string)$name);
            return $name !== '' ? $name : $fallback;
        } catch (\Throwable $e) {
            return $fallback;
        }
    }
    public function add_customer()
    {
        global $conn;

        $this->clearBufferedHttpOutput();
        header('Content-Type: application/json; charset=utf-8');

        require_once 'models/customer/Customer.php';
        $customerModel = new Customer($conn);

        $first = $_POST['first_name'] ?? '';
        $last  = $_POST['last_name'] ?? '';
        $phone = $_POST['mobile'] ?? '';
        $email = $_POST['cus_email'] ?? '';

        if (!$first || !$phone) {
            echo json_encode([
                "success" => false,
                "message" => "Name and phone required"
            ]);
            exit;
        }

        $name = trim($first . ' ' . $last);

        $stmt = $conn->prepare("
        INSERT INTO vp_customers (name,email,phone)
        VALUES (?,?,?)
    ");
        if (!$stmt) {
            echo json_encode([
                "success" => false,
                "message" => "Database error (prepare): " . $conn->error
            ]);
            exit;
        }
        $stmt->bind_param("sss", $name, $email, $phone);
        try {
            $executed = $stmt->execute();
        } catch (\mysqli_sql_exception $e) {
            $stmt->close();
            $dup = str_contains($e->getMessage(), 'Duplicate entry')
                || str_contains($e->getMessage(), 'unique_email_phone')
                || $e->getSqlState() === '23000';
            if ($dup) {
                $lookup = $conn->prepare(
                    'SELECT id, name, email, phone FROM vp_customers WHERE email = ? AND phone = ? LIMIT 1'
                );
                if ($lookup) {
                    $lookup->bind_param('ss', $email, $phone);
                    $lookup->execute();
                    $existing = $lookup->get_result()->fetch_assoc();
                    $lookup->close();
                    if (!empty($existing['id'])) {
                        $id = (int)$existing['id'];
                        $_SESSION['pos_customer_id'] = $id;
                        $_SESSION['pos_customer_form'] = $_POST;
                        $customerModel->upsertPosCustomerDetailsFromPost($id, $_POST);
                        echo json_encode([
                            'success' => true,
                            'message' => 'This email and phone are already registered; using existing customer.',
                            'customer' => [
                                'id' => $id,
                                'name' => $existing['name'] ?? $name,
                                'phone' => $existing['phone'] ?? $phone,
                                'email' => $existing['email'] ?? $email,
                            ],
                        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                        exit;
                    }
                }
                echo json_encode([
                    'success' => false,
                    'message' => 'A customer with this email and phone already exists.',
                ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                exit;
            }
            echo json_encode([
                'success' => false,
                'message' => 'Could not save customer: ' . $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        if (!$executed) {
            echo json_encode([
                "success" => false,
                "message" => "Could not save customer: " . $stmt->error
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            $stmt->close();
            exit;
        }

        $id = (int)$stmt->insert_id;
        $stmt->close();

        if ($id <= 0) {
            echo json_encode([
                "success" => false,
                "message" => "Customer was not created (no insert id)."
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        /*  STORE FULL BILLING + SHIPPING IN SESSION */
        $_SESSION['pos_customer_id'] = $id;
        $_SESSION['pos_customer_form'] = $_POST;
        $customerModel->upsertPosCustomerDetailsFromPost($id, $_POST);

        echo json_encode([
            "success" => true,
            "customer" => [
                "id" => $id,
                "name" => $name,
                "phone" => $phone,
                "email" => $email
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        exit;
    }
    public function set_customer()
    {

        // $customerId = $_POST['customer_id'] ?? '';
        $customerId = $_POST['customer_id'] ?? '';

        if ($customerId) {
            $_SESSION['pos_customer_id'] = $customerId;
            unset($_SESSION['pos_customer_form']); // ⭐ VERY IMPORTANT
        } else {
            unset($_SESSION['pos_customer_id']);
        }

        $this->clearBufferedHttpOutput();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["success" => true]);
        exit;
    }

    /**
     * POST JSON: address confirm fields + payment_* + customer_id + order_total (from live cart).
     * Proxies Exotic POST /order/create, then inserts pos_payments with receipt number.
     */
    private const POS_DUMMY_BILLING_ADDRESS1 = 'dummy Address';
    private const POS_DUMMY_PHONE = '8031404444';
    private const POS_DUMMY_CITY = 'Delhi';
    private const POS_DEFAULT_STATE = 'Delhi';

    /**
     * Placeholder billing email when none provided: dummy-{timestamp}-{1000-9999}@exoticindia.com
     */
    private function generatePosDummyEmail(): string
    {
        return sprintf('dummy-%d-%d@exoticindia.com', time(), random_int(1000, 9999));
    }

    /**
     * Extract 6-digit Indian pincode from free-text address (exotic_address.address).
     */
    private function extractPincodeFromAddressText(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }
        if (preg_match('/\b(\d{6})\b/', $text, $matches)) {
            return (string)$matches[1];
        }

        return '';
    }

    /**
     * Pincode for current POS store (session warehouse, else default exotic_address; else firm_details.pin).
     */
    private function resolveStorePincodeForPos(mysqli $conn): string
    {
        $whId = (int)($_SESSION['warehouse_id'] ?? 0);
        $row = null;

        if ($whId > 0) {
            $stmt = $conn->prepare(
                'SELECT `address`, display_name, address_title FROM exotic_address WHERE id = ? AND is_active = 1 LIMIT 1'
            );
            if ($stmt) {
                $stmt->bind_param('i', $whId);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc() ?: null;
                $stmt->close();
            }
        }

        if (!$row) {
            $res = $conn->query(
                'SELECT `address`, display_name, address_title FROM exotic_address
                 WHERE is_active = 1 AND is_default = 1 ORDER BY id ASC LIMIT 1'
            );
            if ($res) {
                $row = $res->fetch_assoc() ?: null;
                $res->free();
            }
        }

        if (!$row) {
            $res = $conn->query(
                'SELECT `address`, display_name, address_title FROM exotic_address
                 WHERE is_active = 1 ORDER BY id ASC LIMIT 1'
            );
            if ($res) {
                $row = $res->fetch_assoc() ?: null;
                $res->free();
            }
        }

        if ($row) {
            foreach (['address', 'display_name', 'address_title'] as $col) {
                $pin = $this->extractPincodeFromAddressText((string)($row[$col] ?? ''));
                if ($pin !== '') {
                    return $pin;
                }
            }
        }

        require_once __DIR__ . '/../models/comman/tables.php';
        $comman = new Tables($conn);
        $firm = $comman->getRecordById('firm_details', 1);
        if (is_array($firm)) {
            foreach (['pin', 'pincode', 'zip', 'zipcode'] as $col) {
                $pin = trim((string)($firm[$col] ?? ''));
                if ($pin !== '') {
                    return $pin;
                }
            }
        }

        return '';
    }

    /**
     * Fill API-required billing fields when blank so order/create validation passes.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalizePosCheckoutBillingPayload(array $payload, ?mysqli $conn = null): array
    {
        if (trim((string)($payload['confirm_first_name'] ?? '')) === '') {
            // Left empty; validatePosCheckoutAddressPayload blocks checkout.
        }
        if (trim((string)($payload['confirm_email'] ?? '')) === '') {
            $payload['confirm_email'] = $this->generatePosDummyEmail();
        }
        if (trim((string)($payload['confirm_phone'] ?? '')) === '') {
            $payload['confirm_phone'] = self::POS_DUMMY_PHONE;
        }
        if (trim((string)($payload['confirm_address1'] ?? '')) === '') {
            $payload['confirm_address1'] = self::POS_DUMMY_BILLING_ADDRESS1;
        }
        if (trim((string)($payload['confirm_city'] ?? '')) === '') {
            $payload['confirm_city'] = self::POS_DUMMY_CITY;
        }
        if (trim((string)($payload['confirm_state'] ?? '')) === '') {
            $payload['confirm_state'] = self::POS_DEFAULT_STATE;
        }
        if (trim((string)($payload['confirm_zip'] ?? '')) === '' && $conn instanceof mysqli) {
            $storePin = $this->resolveStorePincodeForPos($conn);
            if ($storePin !== '') {
                $payload['confirm_zip'] = $storePin;
            }
        }

        return $payload;
    }

    private function validatePosCheckoutAddressPayload(array $payload): array
    {
        $errors = [];
        if (trim((string)($payload['confirm_first_name'] ?? '')) === '') {
            $errors[] = 'First name';
        }
        if (trim((string)($payload['confirm_state'] ?? '')) === '') {
            $errors[] = 'State';
        }
        if (trim((string)($payload['confirm_zip'] ?? '')) === '') {
            $errors[] = 'ZIP / Pincode';
        }
        if (trim((string)($payload['confirm_phone'] ?? '')) === '') {
            $errors[] = 'Phone';
        }

        return $errors;
    }

    public function checkout_create(): void
    {
        is_login();
        $this->clearBufferedHttpOutput();
        header('Content-Type: application/json; charset=utf-8');
        global $conn;

        require_once dirname(__DIR__) . '/helpers/pos_payment_receipt.php';

        $raw = (string)file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON body'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $payload = $this->normalizePosCheckoutBillingPayload($payload, $conn);

        $customerId = (int)($payload['customer_id'] ?? 0);
        $sessionCid = (int)($_SESSION['pos_customer_id'] ?? 0);
        if ($customerId <= 0 || $sessionCid <= 0 || $customerId !== $sessionCid) {
            echo json_encode(['success' => false, 'message' => 'Select a customer before checkout.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $orderTotal = round((float)($payload['order_total'] ?? 0), 2);
        if ($orderTotal <= 0) {
            echo json_encode(['success' => false, 'message' => 'Cart total is missing or zero. Refresh the cart and try again.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $paymentAmount = round((float)($payload['payment_amount'] ?? 0), 2);
        $paymentStage = strtolower(trim((string)($payload['payment_stage'] ?? 'final')));
        if ($paymentAmount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Payment amount must be greater than zero.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        if ($paymentStage === 'final') {
            if ($paymentAmount + 0.02 < $orderTotal) {
                echo json_encode(['success' => false, 'message' => 'Final payment must match order total ₹ ' . $orderTotal], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                exit;
            }
            if ($paymentAmount - 0.02 > $orderTotal) {
                echo json_encode(['success' => false, 'message' => 'Over payment is not allowed for final settlement.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                exit;
            }
        } elseif ($paymentStage === 'partial' || $paymentStage === 'advance') {
            if ($paymentAmount + 0.02 >= $orderTotal) {
                echo json_encode(['success' => false, 'message' => 'Partial / advance must be less than order total ₹ ' . $orderTotal], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                exit;
            }
        }

        $paymentMode = trim((string)($payload['payment_mode'] ?? 'cash'));
        $txn = trim((string)($payload['transaction_id'] ?? ''));
        if ($paymentMode === 'razorpay' && $txn === '') {
            echo json_encode(['success' => false, 'message' => 'Razorpay requires a transaction ID.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $addressErrors = $this->validatePosCheckoutAddressPayload($payload);
        if (!empty($addressErrors)) {
            echo json_encode([
                'success' => false,
                'message' => 'Please complete required order fields: ' . implode(', ', array_slice($addressErrors, 0, 8))
                    . (count($addressErrors) > 8 ? ' and ' . (count($addressErrors) - 8) . ' more' : ''),
                'fields' => $addressErrors,
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $compliance = $this->evaluateHighValueCompliance($payload, $orderTotal, $paymentAmount, $paymentMode, $conn);

        $ctx = $this->exoticCartDiscountContext();
        $retrieve = $this->exotic_api_call(
            '/cart/retrieve',
            'GET',
            $ctx['query'],
            null,
            null,
            $ctx['extraHeaders']
        );
        if (!$this->isExoticCartSuccess($retrieve)) {
            echo json_encode([
                'success' => false,
                'message' => 'Could not load cart before checkout. Try refreshing the cart.',
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }
        $cartData = is_array($retrieve['data'] ?? null) ? $retrieve['data'] : [];
        $items = $cartData['cartitems'] ?? $cartData['cart_items'] ?? $cartData['items'] ?? $cartData['lines'] ?? [];
        if (!is_array($items) || count($items) === 0) {
            echo json_encode(['success' => false, 'message' => 'Cart is empty.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }
        $localStockWarnings = $this->detectLocalStockWarningsFromCart($cartData);

        $postBody = $this->buildOrderCreatePostFromPayload($payload, $cartData);
        if (trim((string)($postBody['checkoutdata'] ?? '')) === '') {
            echo json_encode([
                'success' => false,
                'message' => 'Cart response did not include checkoutdata (required by Exotic order/create). Refresh the cart or add items again.',
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }
        $createRes = $this->exotic_api_call(
            '/order/create',
            'POST',
            $ctx['query'],
            $postBody,
            null,
            $ctx['extraHeaders']
        );

        $requestBodyDebug = $this->summarizeOrderCreateDebugBody($postBody);
        $_SESSION['pos_order_create_api_debug'] = [
            'at' => gmdate('c'),
            // Backward-compatible fields used by existing UI.
            'http_code' => (int)($createRes['code'] ?? 0),
            'data' => $createRes['data'] ?? [],
            'raw_snippet' => substr((string)($createRes['raw'] ?? ''), 0, 12000),
            // Rich request/response blocks for checkout popup debug.
            'request' => [
                'endpoint' => '/order/create',
                'method' => 'POST',
                'query' => $ctx['query'] ?? [],
                'body' => $requestBodyDebug,
            ],
            'response' => [
                'http_code' => (int)($createRes['code'] ?? 0),
                'data' => $createRes['data'] ?? [],
                'raw_snippet' => substr((string)($createRes['raw'] ?? ''), 0, 12000),
            ],
        ];

        if (!$this->isExoticCartSuccess($createRes)) {
            $d = is_array($createRes['data'] ?? null) ? $createRes['data'] : [];
            $msg = trim((string)($d['message'] ?? $d['error'] ?? $d['errormessage'] ?? ''));
            if ($msg === '') {
                $msg = 'Order create failed (HTTP ' . (int)($createRes['code'] ?? 0) . ').';
            }
            echo json_encode([
                'success' => false,
                'message' => $msg,
                'order_create_debug' => $_SESSION['pos_order_create_api_debug'],
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $orderNumber = $this->extractExoticOrderNumberFromCreateResponse(is_array($createRes['data'] ?? null) ? $createRes['data'] : []);
        if ($orderNumber === '') {
            echo json_encode([
                'success' => false,
                'message' => 'Order was created but no order number was returned. Check Last order-create API in the payment modal.',
                'order_create_debug' => $_SESSION['pos_order_create_api_debug'],
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $posLinePrices = $payload['pos_line_prices'] ?? null;
        $receiptCouponDiscount = round((float)($payload['receipt_coupon_discount'] ?? 0), 2);
        $receiptCashDiscount = round((float)($payload['receipt_cash_discount'] ?? 0), 2);
        $receiptGiftDiscount = round((float)($payload['receipt_gift_discount'] ?? 0), 2);
        if ((!is_array($posLinePrices) || count($posLinePrices) === 0)) {
            $discountPool = $receiptCouponDiscount + $receiptCashDiscount + $receiptGiftDiscount;
            if ($discountPool > 0.001) {
                $posLinePrices = $this->buildPosLinePricesFromCartDiscountPool($cartData, $discountPool);
            }
        }
        if (is_array($posLinePrices) && count($posLinePrices) > 0) {
            if (count($posLinePrices) !== count($items)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Line price payload does not match the current cart (' . count($posLinePrices) . ' rows vs '
                        . count($items) . ' items). Refresh the cart and try again.',
                    'order_number' => $orderNumber,
                    'order_create_debug' => $_SESSION['pos_order_create_api_debug'] ?? null,
                ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                exit;
            }
            foreach ($posLinePrices as $ln) {
                if (!is_array($ln)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid line price row. Refresh the cart and try again.',
                        'order_number' => $orderNumber,
                    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                    exit;
                }
                if (trim((string)($ln['itemcode'] ?? '')) === '') {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Line price payload is missing item code for one or more rows.',
                        'order_number' => $orderNumber,
                    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                    exit;
                }
            }
            $editRes = $this->exoticPosEditOrderPrices($orderNumber, $posLinePrices);
            if (!$this->isExoticCartSuccess($editRes)) {
                $em = $this->extractExoticCartUserMessage($editRes);
                if ($em === '') {
                    $em = 'HTTP ' . (int)($editRes['code'] ?? 0);
                }
                echo json_encode([
                    'success' => false,
                    'message' => 'Order ' . $orderNumber . ' was created but Exotic rejected line prices: ' . $em
                        . ' You may need to fix prices manually or retry before recording payment locally.',
                    'order_number' => $orderNumber,
                    'order_create_debug' => $_SESSION['pos_order_create_api_debug'] ?? null,
                ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                exit;
            }
        }

        $short = pos_payment_resolve_short_code_for_warehouse($conn, (int)($_SESSION['warehouse_id'] ?? 0));
        try {
            $receiptNo = pos_payment_generate_next_receipt_number($conn, $short);
        } catch (\Throwable $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Receipt number error: ' . $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $note = $this->appendHighValueComplianceToNote(trim((string)($payload['payment_note'] ?? '')), $orderTotal, $paymentMode, $compliance);
        $note = $this->appendLocalStockWarningsToNote($note, $localStockWarnings);
        $userId = pos_payment_resolve_session_user_id();
        $whId = (int)($_SESSION['warehouse_id'] ?? 0);

        $pay = pos_payment_insert_row(
            $conn,
            $orderNumber,
            $receiptNo,
            $customerId,
            $paymentStage,
            $paymentMode,
            $paymentAmount,
            $txn,
            $note,
            $userId,
            $whId,
            true,
            $orderTotal
        );

        if (empty($pay['success'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Exotic order ' . $orderNumber . ' was created but local payment row failed: ' . (string)($pay['error'] ?? 'unknown'),
                'order_number' => $orderNumber,
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        require_once 'models/customer/Customer.php';
        $posDetailsModel = new Customer($conn);
        $posDetailsModel->upsertPosCustomerDetailsFromConfirmPayload($customerId, $payload);
        $this->persistCustomerComplianceDetails($conn, $customerId, $compliance);
        $customInvoiceNumber = trim((string)($payload['custom_invoice_number'] ?? ''));
        if (!($paymentStage === 'final' && abs($paymentAmount - $orderTotal) <= 0.02)) {
            $customInvoiceNumber = '';
        }
        $_SESSION['pos_checkout_invoice_discounts'] = [
            'line_discount' => round((float)($payload['receipt_line_discount'] ?? 0), 2),
        ];
        $invoiceMeta = $this->finalizePosReceiptInvoice($conn, $orderNumber, $paymentStage, $compliance, $customInvoiceNumber);
        $shippedStatusMeta = $this->markPosCheckoutOrderShipped($conn, $orderNumber);

        $modeLabel = $this->mapPosPaymentModeLabel($paymentMode);
        $dt = new \DateTime('now', new \DateTimeZone('Asia/Kolkata'));
        $receiptSubtotalGoods = round((float)($payload['receipt_subtotal_goods'] ?? $orderTotal), 2);
        if ($receiptSubtotalGoods <= 0 && $orderTotal > 0) {
            $receiptSubtotalGoods = $orderTotal;
        }
        $receiptGstTotal = round((float)($payload['receipt_gst_total'] ?? 0), 2);
        $receiptCouponDiscount = round((float)($payload['receipt_coupon_discount'] ?? 0), 2);
        $receiptCashDiscount = round((float)($payload['receipt_cash_discount'] ?? 0), 2);
        $receiptGiftDiscount = round((float)($payload['receipt_gift_discount'] ?? 0), 2);
        $receiptLineDiscount = round((float)($payload['receipt_line_discount'] ?? 0), 2);

        $_SESSION['pos_last_checkout_receipt'] = [
            'receipt_number' => $receiptNo,
            'receipt_date_formatted' => $dt->format('d M Y, h:i A'),
            'order_id' => $orderNumber,
            'payment_stage' => $paymentStage,
            'payment_mode_label' => $modeLabel,
            'transaction_id' => $txn,
            'receipt_banner_text' => 'Thank you. Payment of ₹ ' . number_format($paymentAmount, 2, '.', ',') . ' recorded for order ' . $orderNumber . '.',
            'receipt_billing_block' => $this->formatAddressLinesFromPayload($payload, 'billing'),
            'receipt_shipping_block' => $this->formatAddressLinesFromPayload($payload, 'shipping'),
            'receipt_lines' => [],
            'receipt_subtotal_goods' => $receiptSubtotalGoods,
            'receipt_gst_total' => $receiptGstTotal,
            'receipt_coupon_discount' => $receiptCouponDiscount,
            'receipt_gift_discount' => $receiptGiftDiscount,
            'receipt_line_discount' => $receiptLineDiscount,
            'receipt_cash_discount' => $receiptCashDiscount,
            'receipt_grand_total' => $orderTotal,
            'receipt_qty_total' => 0.0,
            'receipt_agg_sgst' => 0.0,
            'receipt_agg_cgst' => 0.0,
            'receipt_agg_igst' => 0.0,
            'receipt_amount_in_words' => '',
            'receipt_amount_received' => $paymentAmount,
            'receipt_pending_amount' => (float)($pay['pending_amount'] ?? 0),
            'import_status' => $invoiceMeta['import_status'],
            'show_invoice_pdf_button' => $invoiceMeta['show_invoice_pdf_button'],
            'show_invoice_preview_button' => $invoiceMeta['show_invoice_preview_button'] ?? false,
            'invoice_id' => (int)($invoiceMeta['invoice_id'] ?? 0),
            'invoice_pdf_url' => $invoiceMeta['invoice_pdf_url'],
            'invoice_preview_url' => $invoiceMeta['invoice_preview_url'] ?? '',
            'invoice_pdf_disabled_hint' => $invoiceMeta['invoice_pdf_disabled_hint'],
            'is_payment_in_full' => ($paymentStage === 'final' && (float)($pay['pending_amount'] ?? 0) <= 0.02),
            'receipt_company_legal_name' => 'EXOTIC INDIA ART PVT LTD',
            'receipt_company_tagline' => '',
            'receipt_company_gstin' => '',
            'receipt_company_pan' => '',
            'receipt_title_main' => 'PAYMENT RECEIPT',
            'receipt_place_of_supply' => '',
            'receipt_terms' => [
                'Goods once sold will not be taken back.',
                'Subject to jurisdiction of competent courts at New Delhi.',
            ],
            'receipt_office_footer' => '',
            'receipt_signature_date' => $dt->format('d M Y'),
            'payment_history_url' => 'index.php?page=payments&order_number=' . rawurlencode($orderNumber) . '&order_exact=1',
            'invoice_poitem_ids' => $this->resolveInvoicePoitemIdsForOrderNumber($conn, $orderNumber),
        ];

        $successMessage = 'Order placed.';
        if (!empty($localStockWarnings)) {
            $successMessage .= ' Local stock warning: ' . count($localStockWarnings) . ' item(s) sold above local stock.';
        }
        if (!empty($shippedStatusMeta['message']) && (empty($shippedStatusMeta['local_updated']) || !empty($shippedStatusMeta['api_failed']))) {
            $successMessage .= ' ' . $shippedStatusMeta['message'];
        }

        echo json_encode([
            'success' => true,
            'message' => $successMessage,
            'order_number' => $orderNumber,
            'receipt_number' => $receiptNo,
            'payment_id' => (int)($pay['payment_id'] ?? 0),
            'local_stock_warnings' => $localStockWarnings,
            'shipped_status_sync' => $shippedStatusMeta,
            'redirect_url' => 'index.php?page=pos_register&action=checkout-receipt',
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    public function checkout_receipt(): void
    {
        is_login();
        global $conn;
        $row = $_SESSION['pos_last_checkout_receipt'] ?? null;
        if (!is_array($row) || empty($row['receipt_number'])) {
            header('Location: index.php?page=pos_register&action=list');
            exit;
        }
        unset($_SESSION['pos_last_checkout_receipt']);
        $row = $this->fillReceiptInvoicePdfFromDb($conn, $row);
        if (empty($row['invoice_poitem_ids']) || !is_array($row['invoice_poitem_ids'])) {
            $row['invoice_poitem_ids'] = $this->resolveInvoicePoitemIdsForOrderNumber($conn, $row['order_id'] ?? '');
        }
        renderTemplateClean('views/pos_register/order_confirmation.php', $row, 'Order confirmation');
    }

    /**
     * OrdersController import methods use global $ordersModel; requiring that controller
     * from inside a method only creates local variables in the required file.
     */
    private function bootstrapOrderImportGlobals(): void
    {
        global $conn, $ordersModel, $productModel, $commanModel, $savedSearchModel, $poInvoiceModel;

        if (isset($ordersModel) && is_object($ordersModel)) {
            return;
        }

        require_once 'models/order/order.php';
        require_once 'models/comman/tables.php';
        require_once 'models/searches/saved_search.php';
        require_once 'models/order/po_invoice.php';
        require_once 'models/product/product.php';

        $ordersModel = new Order($conn);
        $commanModel = new Tables($conn);
        $savedSearchModel = new SavedSearch($conn);
        $poInvoiceModel = new POInvoice($conn);
        $productModel = new Product($conn);
    }

    private function getOrdersControllerForImport(): OrdersController
    {
        global $conn;
        $this->bootstrapOrderImportGlobals();
        require_once __DIR__ . '/OrdersController.php';

        return new OrdersController();
    }

    private function getPosInvoiceControllerForCheckout(): PosInvoiceController
    {
        global $conn, $invoiceModel, $usersModel, $commanModel;

        $this->bootstrapOrderImportGlobals();

        if (!isset($invoiceModel) || !is_object($invoiceModel)) {
            require_once 'models/PosInvoice/invoice.php';
            require_once 'models/user/user.php';
            $invoiceModel = new POSInvoice($conn);
            $usersModel = new User($conn);
        }
        if (!isset($commanModel) || !is_object($commanModel)) {
            require_once 'models/comman/tables.php';
            $commanModel = new Tables($conn);
        }

        require_once __DIR__ . '/PosInvoiceController.php';

        return new PosInvoiceController();
    }

    /**
     * vp_orders.id values for invoice create (InvoicesController expects poitem[]).
     */
    private function resolveInvoicePoitemIdsForOrderNumber(mysqli $conn, $orderNumber): array
    {
        $orderNumber = trim((string)$orderNumber);
        if ($orderNumber === '') {
            return [];
        }
        $stmt = $conn->prepare('SELECT id FROM vp_orders WHERE order_number = ? ORDER BY id ASC');
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        $ids = [];
        if ($result) {
            while ($line = $result->fetch_assoc()) {
                $id = (int)($line['id'] ?? 0);
                if ($id > 0) {
                    $ids[] = $id;
                }
            }
        }
        $stmt->close();

        return $ids;
    }

    /**
     * Import POS order lines if needed, stash invoice item ids in session, redirect to invoice create.
     */
    public function create_invoice_from_receipt(): void
    {
        is_login();
        global $conn;

        $orderNumber = trim((string)($_GET['order_number'] ?? ''));
        if ($orderNumber === '') {
            renderTemplate('views/errors/not_found.php', ['message' => 'Order number missing for invoice.'], 'No items selected');
            exit;
        }

        $ordersCtrl = $this->getOrdersControllerForImport();

        if (!$ordersCtrl->isOrderReadyForPosCheckout($orderNumber)) {
            $import = $ordersCtrl->importSingleOrderForCheckoutWithRetry($orderNumber, 6, 2);
            if (!$ordersCtrl->isOrderReadyForPosCheckout($orderNumber)) {
                $hint = trim((string)($import['message'] ?? ''));
                $message = 'Order lines are not in the system yet. Open Orders to import order '
                    . htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8') . ', then try again.';
                if ($hint !== '') {
                    $message .= ' (' . htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') . ')';
                }
                renderTemplate('views/errors/not_found.php', ['message' => $message], 'No items selected');
                exit;
            }
        }

        $itemIds = $this->resolveInvoicePoitemIdsForOrderNumber($conn, $orderNumber);
        if ($itemIds === []) {
            renderTemplate('views/errors/not_found.php', [
                'message' => 'No order line items found for order '
                    . htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8') . '.',
            ], 'No items selected');
            exit;
        }

        $_SESSION['invoice_items'] = $itemIds;
        $_SESSION['invoice_pos_flag'] = 1;
        header('Location: index.php?page=invoices&action=create');
        exit;
    }

    /**
     * If checkout did not store an invoice PDF link, resolve the latest invoice for this order from the DB.
     */
    private function applyPosReceiptInvoiceLinks(array $row, int $invoiceId): array
    {
        if ($invoiceId <= 0) {
            return $row;
        }
        $row['invoice_id'] = $invoiceId;
        $row['show_invoice_pdf_button'] = true;
        $row['show_invoice_preview_button'] = true;
        $row['invoice_pdf_url'] = 'index.php?page=invoices&action=generate_pdf&invoice_id=' . $invoiceId;
        $row['invoice_preview_url'] = 'index.php?page=invoices&action=preview&invoice_id=' . $invoiceId;
        $row['invoice_pdf_disabled_hint'] = '';

        return $row;
    }

    private function enrichPosCheckoutReceiptRow(array $row): array
    {
        $stage = strtolower(trim((string)($row['payment_stage'] ?? '')));
        $pending = (float)($row['receipt_pending_amount'] ?? 0);
        $row['is_payment_in_full'] = ($stage === 'final' && $pending <= 0.02);

        $invoiceId = (int)($row['invoice_id'] ?? 0);
        if ($invoiceId <= 0 && !empty($row['invoice_pdf_url'])) {
            if (preg_match('/invoice_id=(\d+)/', (string)$row['invoice_pdf_url'], $m)) {
                $invoiceId = (int)$m[1];
                $row['invoice_id'] = $invoiceId;
            }
        }

        if ($row['is_payment_in_full'] && $invoiceId > 0) {
            $row = $this->applyPosReceiptInvoiceLinks($row, $invoiceId);
        } elseif (!$row['is_payment_in_full']) {
            $row['show_invoice_pdf_button'] = false;
            $row['show_invoice_preview_button'] = false;
            if (trim((string)($row['invoice_pdf_disabled_hint'] ?? '')) === '') {
                $row['invoice_pdf_disabled_hint'] = 'Tax invoice preview is available after payment is received in full.';
            }
        }

        return $row;
    }

    private function fillReceiptInvoicePdfFromDb(mysqli $conn, array $row): array
    {
        if (!empty($row['show_invoice_pdf_button']) && !empty($row['invoice_pdf_url'])) {
            return $this->enrichPosCheckoutReceiptRow($row);
        }
        $orderNum = trim((string)($row['order_id'] ?? ''));
        if ($orderNum === '') {
            return $this->enrichPosCheckoutReceiptRow($row);
        }
        $invoiceId = $this->findInvoiceIdForOrderNumber($conn, $orderNum);
        if ($invoiceId) {
            if (trim((string)($row['import_status'] ?? '')) === '') {
                $row['import_status'] = 'success';
            }
            $row = $this->applyPosReceiptInvoiceLinks($row, $invoiceId);
        }

        return $this->enrichPosCheckoutReceiptRow($row);
    }

    /**
     * POST /api/order/create body (application/x-www-form-urlencoded) per ExoticIndia API:
     * payment_type, buynow, checkoutdata (verbatim from cart retrieve), cod/codcharges fixed for counter sale,
     * billing/shipping fields, optional Razorpay / store_payment_details.
     *
     * @param array<string, mixed> $payload JSON from POS (confirm_* billing/shipping, payment_* , transaction_id, …)
     * @param array<string, mixed> $cartData Decoded JSON from GET /cart/retrieve (same session as checkout).
     *
     * @return array<string, string>
     */
    /**
     * True when Confirm Billing & Shipping modal has shipping address data filled in.
     */
    private function hasPosConfirmShippingFilled(array $payload): bool
    {
        if (!empty($payload['confirm_shipping_same_as_billing'])
            && (string)$payload['confirm_shipping_same_as_billing'] === '1') {
            return true;
        }

        foreach ([
            'confirm_sfirst_name',
            'confirm_slast_name',
            'confirm_sname',
            'confirm_saddress1',
            'confirm_saddress2',
            'confirm_scity',
            'confirm_sstate',
            'confirm_szip',
            'confirm_sphone',
        ] as $key) {
            if (trim((string)($payload[$key] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * When shipping is filled in POS confirm step, omit s* fields on order/create (checkoutdata + billing only).
     */
    private function shouldOmitShippingOnOrderCreate(array $payload): bool
    {
        if (!empty($payload['confirm_omit_shipping_api'])
            && (string)$payload['confirm_omit_shipping_api'] === '1') {
            return true;
        }

        return $this->hasPosConfirmShippingFilled($payload);
    }

    private function buildOrderCreatePostFromPayload(array $payload, array $cartData): array
    {
        $posMode = strtolower(trim((string)($payload['payment_mode'] ?? 'cash')));
        $storePaymentMode = $this->mapPosPaymentModeToExoticPaymentType($posMode);
        $paymentType = 'offline';

        /** Exact string from GET /cart/retrieve JSON — posted as-is (only URL-encoded as form field by HTTP client). */
        $checkoutdata = $this->extractCheckoutDataStringFromCart($cartData);

        $omitShippingOnOrder = $this->shouldOmitShippingOnOrderCreate($payload);

        $country = strtoupper(substr(trim((string)($payload['confirm_country'] ?? 'IN')), 0, 2));
        if ($country === '') {
            $country = 'IN';
        }
        $email = trim((string)($payload['confirm_email'] ?? ''));
        if ($email === '') {
            $email = $this->generatePosDummyEmail();
            $customerId = (int)($payload['customer_id'] ?? 0);
            global $conn;
            if ($customerId > 0 && $conn instanceof mysqli) {
                $stmt = $conn->prepare(
                    "UPDATE vp_customers SET email = ? WHERE id = ? AND (email = '' OR email LIKE 'dummy-%@exoticindia.com')"
                );
                if ($stmt) {
                    $stmt->bind_param('si', $email, $customerId);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        $whId = (int)($_SESSION['warehouse_id'] ?? 0);
        $storeId = $whId > 0 ? (string)$whId : '1';
        $txn = trim((string)($payload['transaction_id'] ?? ''));
        $txnField = $txn !== '' ? $txn : '0';

        $out = [
            'payment_type' => $paymentType,
            'buynow' => '0',
            'checkoutdata' => $checkoutdata,
            'cod' => '0',
            'codcharges' => '0.00',
            'first_name' => trim((string)($payload['confirm_first_name'] ?? '')),
            'last_name' => trim((string)($payload['confirm_last_name'] ?? '')),
            'email' => $email,
            'address1' => trim((string)($payload['confirm_address1'] ?? '')) !== ''
                ? trim((string)$payload['confirm_address1'])
                : self::POS_DUMMY_BILLING_ADDRESS1,
            'address2' => trim((string)($payload['confirm_address2'] ?? '')),
            'city' => trim((string)($payload['confirm_city'] ?? '')) !== ''
                ? trim((string)$payload['confirm_city'])
                : self::POS_DUMMY_CITY,
            'state' => trim((string)($payload['confirm_state'] ?? '')) !== ''
                ? trim((string)$payload['confirm_state'])
                : self::POS_DEFAULT_STATE,
            'zip' => trim((string)($payload['confirm_zip'] ?? '')),
            'country' => $country,
            'phone' => trim((string)($payload['confirm_phone'] ?? '')) !== ''
                ? trim((string)$payload['confirm_phone'])
                : self::POS_DUMMY_PHONE,
            'gstin' => trim((string)($payload['confirm_gstin'] ?? '')),
            'store_payment_details' => $storeId . '|' . $storePaymentMode . '|' . $txnField,
        ];

        if (!$omitShippingOnOrder) {
            $sf = trim((string)($payload['confirm_sfirst_name'] ?? ''));
            $sl = trim((string)($payload['confirm_slast_name'] ?? ''));
            $sname = trim((string)($payload['confirm_sname'] ?? ''));
            if ($sname === '') {
                $sname = trim($sf . ' ' . $sl);
            }
            $scountry = strtoupper(substr(trim((string)($payload['confirm_scountry'] ?? 'IN')), 0, 2));
            if ($scountry === '') {
                $scountry = 'IN';
            }
            $out['sname'] = $sname;
            $out['saddress1'] = trim((string)($payload['confirm_saddress1'] ?? ''));
            $out['saddress2'] = trim((string)($payload['confirm_saddress2'] ?? ''));
            $out['scity'] = trim((string)($payload['confirm_scity'] ?? ''));
            $out['sstate'] = trim((string)($payload['confirm_sstate'] ?? ''));
            $out['szip'] = trim((string)($payload['confirm_szip'] ?? ''));
            $out['scountry'] = $scountry;
            $out['sphone'] = trim((string)($payload['confirm_sphone'] ?? ''));
        }

        if ($storePaymentMode === 'razorpay') {
            $rzPay = trim((string)($payload['razorpay_payment_id'] ?? $txn));
            if ($rzPay !== '') {
                $out['razorpay_payment_id'] = $rzPay;
            }
            $rzo = trim((string)($payload['razorpay_order_id'] ?? ''));
            if ($rzo !== '') {
                $out['razorpay_order_id'] = $rzo;
            }
            $rzs = trim((string)($payload['razorpay_signature'] ?? ''));
            if ($rzs !== '') {
                $out['razorpay_signature'] = $rzs;
            }
            $mc = trim((string)($payload['magiccheckout_done'] ?? ''));
            if ($mc === '1') {
                $out['magiccheckout_done'] = '1';
            }
        }

        return $out;
    }

    /** Use exact payment mode label expected by order/create payload. */
    private function mapPosPaymentModeToExoticPaymentType(string $posMode): string
    {
        $m = strtolower(trim($posMode));
        $map = [
            'cash' => 'cash',
            'upi' => 'UPI',
            'bank_transfer' => 'bank_transfer',
            'pos_machine' => 'pos_machine',
            'cheque' => 'cheque',
            'razorpay' => 'razorpay',
            'cod' => 'cod',
            'offline' => 'offline',
        ];

        return $map[$m] ?? 'cash';
    }

    /**
     * checkoutdata string exactly as in the cart retrieve payload (do not serialize or rebuild).
     *
     * @param array<string, mixed> $cartData
     */
    private function extractCheckoutDataStringFromCart(array $cartData): string
    {
        $candidates = [
            $cartData['checkoutdata'] ?? null,
            $cartData['checkout_data'] ?? null,
        ];
        if (isset($cartData['data']) && is_array($cartData['data'])) {
            $candidates[] = $cartData['data']['checkoutdata'] ?? null;
        }
        if (isset($cartData['cart']) && is_array($cartData['cart'])) {
            $candidates[] = $cartData['cart']['checkoutdata'] ?? null;
        }
        foreach ($candidates as $raw) {
            if (is_string($raw) && $raw !== '') {
                return $raw;
            }
        }

        return '';
    }

    /**
     * Keep order/create debug readable in popup; checkoutdata can be very large.
     *
     * @param array<string, string> $postBody
     *
     * @return array<string, string|int>
     */
    private function summarizeOrderCreateDebugBody(array $postBody): array
    {
        $debugBody = $postBody;
        $checkoutRaw = (string)($postBody['checkoutdata'] ?? '');
        $len = strlen($checkoutRaw);
        if ($len > 1200) {
            $debugBody['checkoutdata'] = substr($checkoutRaw, 0, 1200) . ' ... [truncated]';
        }
        $debugBody['checkoutdata_length'] = $len;

        return $debugBody;
    }

    /**
     * POST /api/order/pos_editorderprices — POS item-level unit prices after order/create.
     *
     * @param list<array{itemcode?: string, size?: string, color?: string, price?: string}> $lines
     *
     * @return array{data: mixed, code: int, raw: string}
     */
    /**
     * Spread cart-level coupon/custom discount across lines for pos_editorderprices when the client did not send overrides.
     *
     * @return list<array{itemcode: string, size: string, color: string, price: string}>
     */
    private function buildPosLinePricesFromCartDiscountPool(array $cartData, float $pool): array
    {
        $pool = round(max(0, $pool), 2);
        if ($pool <= 0.001) {
            return [];
        }

        $items = $cartData['cartitems'] ?? $cartData['cart_items'] ?? $cartData['items'] ?? $cartData['lines'] ?? [];
        if (!is_array($items) || count($items) === 0) {
            return [];
        }

        $rows = [];
        $weights = [];
        foreach ($items as $row) {
            if (!is_array($row)) {
                continue;
            }
            $qty = (float)($row['quantity'] ?? $row['qty'] ?? $row['prqt'] ?? 1);
            if ($qty <= 0) {
                $qty = 1;
            }
            $unit = null;
            foreach (['unit_price', 'item_price', 'single_price', 'original_price', 'price', 'selling_price'] as $k) {
                if (isset($row[$k]) && $row[$k] !== '' && is_numeric($row[$k])) {
                    $unit = (float)$row[$k];
                    break;
                }
            }
            if ($unit === null) {
                foreach (['line_total', 'linetotal', 'lineamount', 'amount'] as $k) {
                    if (isset($row[$k]) && $row[$k] !== '' && is_numeric($row[$k])) {
                        $unit = (float)$row[$k] / $qty;
                        break;
                    }
                }
            }
            if ($unit === null || $unit < 0) {
                $unit = 0.0;
            }
            $ext = round($unit * $qty, 2);
            $rows[] = [
                'itemcode' => trim((string)($row['code'] ?? $row['item_code'] ?? $row['sku'] ?? '')),
                'size' => trim((string)($row['size'] ?? '')),
                'color' => trim((string)($row['color'] ?? '')),
                'qty' => $qty,
                'extended' => $ext,
            ];
            $weights[] = $ext;
        }

        if (count($rows) === 0) {
            return [];
        }

        $sumW = array_sum($weights);
        $remaining = $pool;
        $cuts = [];
        $n = count($rows);
        for ($i = 0; $i < $n; $i++) {
            if ($i === $n - 1) {
                $cuts[$i] = round($remaining, 2);
            } elseif ($sumW > 0.001) {
                $share = round(($pool * $weights[$i]) / $sumW, 2);
                $cuts[$i] = $share;
                $remaining = round($remaining - $share, 2);
            } else {
                $share = round($pool / $n, 2);
                $cuts[$i] = $share;
                $remaining = round($remaining - $share, 2);
            }
        }

        $out = [];
        foreach ($rows as $i => $row) {
            if ($row['itemcode'] === '') {
                continue;
            }
            $cut = min($row['extended'], max(0, $cuts[$i] ?? 0));
            $effExt = max(0, round($row['extended'] - $cut, 2));
            $unitAfter = $row['qty'] >= 1 ? round($effExt / $row['qty'], 2) : $effExt;
            $out[] = [
                'itemcode' => $row['itemcode'],
                'size' => $row['size'],
                'color' => $row['color'],
                'price' => number_format($unitAfter, 2, '.', ''),
            ];
        }

        return $out;
    }

    private function exoticPosEditOrderPrices(string $orderId, array $lines): array
    {
        $post = ['orderid' => $orderId];
        $i = 0;
        foreach ($lines as $ln) {
            if (!is_array($ln)) {
                continue;
            }
            $post['itemcode[' . $i . ']'] = trim((string)($ln['itemcode'] ?? ''));
            $post['size[' . $i . ']'] = trim((string)($ln['size'] ?? ''));
            $post['color[' . $i . ']'] = trim((string)($ln['color'] ?? ''));
            $post['price[' . $i . ']'] = trim((string)($ln['price'] ?? ''));
            ++$i;
        }

        return $this->exotic_api_call('/order/pos_editorderprices', 'POST', [], $post);
    }

    private function extractExoticOrderNumberFromCreateResponse(array $data): string
    {
        foreach (['orderid', 'order_id', 'OrderId', 'ordernumber', 'order_number', 'order_no', 'orderNo'] as $k) {
            if (!empty($data[$k])) {
                return trim((string)$data[$k]);
            }
        }
        if (!empty($data['order']) && is_array($data['order'])) {
            return $this->extractExoticOrderNumberFromCreateResponse($data['order']);
        }
        if (!empty($data['data']) && is_array($data['data'])) {
            return $this->extractExoticOrderNumberFromCreateResponse($data['data']);
        }

        return '';
    }

    private function mapPosPaymentModeLabel(string $mode): string
    {
        $m = strtolower(trim($mode));
        $map = [
            'cash' => 'Cash',
            'cod' => 'Cash',
            'upi' => 'UPI',
            'bank_transfer' => 'Bank transfer',
            'pos_machine' => 'POS machine',
            'razorpay' => 'Razorpay',
            'cheque' => 'Cheque',
        ];

        return $map[$m] ?? strtoupper($m);
    }

    /**
     * @param 'billing'|'shipping' $which
     *
     * @return list<string>
     */
    private function formatAddressLinesFromPayload(array $p, string $which): array
    {
        if ($which === 'shipping') {
            $name = trim((string)($p['confirm_sfirst_name'] ?? '') . ' ' . (string)($p['confirm_slast_name'] ?? ''));
            $lines = [
                $name,
                trim((string)($p['confirm_saddress1'] ?? '')),
                trim((string)($p['confirm_saddress2'] ?? '')),
                trim((string)($p['confirm_scity'] ?? '')) . ', ' . trim((string)($p['confirm_sstate'] ?? '')) . ' ' . trim((string)($p['confirm_szip'] ?? '')),
                trim((string)($p['confirm_scountry'] ?? '')),
                'Ph: ' . trim((string)($p['confirm_sphone'] ?? '')),
            ];
        } else {
            $name = trim((string)($p['confirm_first_name'] ?? '') . ' ' . (string)($p['confirm_last_name'] ?? ''));
            $lines = [
                $name,
                trim((string)($p['confirm_address1'] ?? '')),
                trim((string)($p['confirm_address2'] ?? '')),
                trim((string)($p['confirm_city'] ?? '')) . ', ' . trim((string)($p['confirm_state'] ?? '')) . ' ' . trim((string)($p['confirm_zip'] ?? '')),
                trim((string)($p['confirm_country'] ?? '')),
                'Ph: ' . trim((string)($p['confirm_phone'] ?? '')),
            ];
        }

        return array_values(array_filter(array_map('trim', $lines), static function ($x) {
            return $x !== '' && !preg_match('/^Ph:\s*$/', $x);
        }));
    }
}
