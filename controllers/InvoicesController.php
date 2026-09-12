<?php
require_once 'models/invoice/invoice.php';
require_once 'models/order/order.php';
require_once 'models/user/user.php';
require_once 'models/comman/tables.php';
require_once 'models/product/product.php';
require_once 'models/courier/CourierPartner.php';
require_once 'models/port/PortMaster.php';
require_once 'models/country/country.php';
require_once __DIR__ . '/../helpers/international_invoice_defaults.php';
require_once __DIR__ . '/../helpers/invoice/pos_order_pricing.php';
require_once __DIR__ . '/../helpers/app_settings.php';

$invoiceModel = new Invoice($conn);
$ordersModel = new Order($conn);
$usersModel = new User($conn);
$commanModel = new Tables($conn);

class InvoicesController
{
    public function index()
    {
        is_login();
        global $invoiceModel;

        $page_no = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $limit = in_array($limit, [10, 20, 50, 100]) ? $limit : 50;
        $offset = ($page_no - 1) * $limit;

        $invoices = $invoiceModel->getAllInvoices($limit, $offset);
        $total_records = $invoiceModel->countAllInvoices();

        $data = [
            'invoices' => $invoices,
            'page_no' => $page_no,
            'total_pages' => ceil($total_records / $limit),
            'total_records' => $total_records,
            'limit' => $limit
        ];

        renderTemplate('views/invoices/index.php', $data, 'Invoices');
    }

    public function cancelCreate()
    {
        is_login();
        if (isset($_SESSION['invoice_items'])) {
            unset($_SESSION['invoice_items']);
        }
        if (isset($_SESSION['invoice_pos_flag'])) {
            unset($_SESSION['invoice_pos_flag']);
        }
        if (isset($_SESSION['pos_checkout_invoice_snapshot'])) {
            unset($_SESSION['pos_checkout_invoice_snapshot']);
        }

        $redirect = base_url('?page=orders&action=list');
        header('Location: ' . $redirect);
        exit;
    }

    public function create()
    {
        is_login();
        global $invoiceModel, $ordersModel, $usersModel, $commanModel, $conn;

        $rawPoitem = $_POST['poitem'] ?? $_GET['poitem'] ?? null;
        $rawOrderNumber = $_POST['order_number'] ?? $_GET['order_number'] ?? null;
        $rawOrderId = $_POST['order_id'] ?? $_GET['order_id'] ?? null;

        $hasExplicitInput = ($rawPoitem !== null && $rawPoitem !== '' && $rawPoitem !== [])
            || ($rawOrderNumber !== null && trim((string)(is_array($rawOrderNumber) ? ($rawOrderNumber[0] ?? '') : $rawOrderNumber)) !== '')
            || ($rawOrderId !== null && trim((string)(is_array($rawOrderId) ? ($rawOrderId[0] ?? '') : $rawOrderId)) !== '');

        $itemIds = [];

        if ($rawPoitem !== null && $rawPoitem !== '' && $rawPoitem !== []) {
            if (is_array($rawPoitem)) {
                foreach ($rawPoitem as $val) {
                    $id = (int)$val;
                    if ($id > 0) {
                        $itemIds[] = $id;
                    }
                }
            } else {
                $parts = explode(',', (string)$rawPoitem);
                foreach ($parts as $val) {
                    $id = (int)trim($val);
                    if ($id > 0) {
                        $itemIds[] = $id;
                    }
                }
            }
        }

        if (empty($itemIds) && $rawOrderNumber !== null) {
            $orderNumbers = is_array($rawOrderNumber) ? $rawOrderNumber : [$rawOrderNumber];
            foreach ($orderNumbers as $onum) {
                $onumStr = trim((string)$onum);
                if ($onumStr !== '') {
                    $lines = $ordersModel->getOrderByOrderNumber($onumStr);
                    if (is_array($lines)) {
                        foreach ($lines as $line) {
                            $id = (int)($line['id'] ?? 0);
                            if ($id > 0 && !in_array($id, $itemIds, true)) {
                                $itemIds[] = $id;
                            }
                        }
                    }
                }
            }
        }

        if (empty($itemIds) && $rawOrderId !== null) {
            $orderIds = is_array($rawOrderId) ? $rawOrderId : [$rawOrderId];
            foreach ($orderIds as $oid) {
                $oidVal = trim((string)$oid);
                if ($oidVal !== '') {
                    if (is_numeric($oidVal)) {
                        $orderRow = $ordersModel->getOrderById((int)$oidVal);
                        if (is_array($orderRow)) {
                            $id = (int)($orderRow['id'] ?? 0);
                            if ($id > 0 && !in_array($id, $itemIds, true)) {
                                $itemIds[] = $id;
                            }
                            $orderNo = trim((string)($orderRow['order_number'] ?? ''));
                            if ($orderNo !== '') {
                                $lines = $ordersModel->getOrderByOrderNumber($orderNo);
                                if (is_array($lines)) {
                                    foreach ($lines as $line) {
                                        $lid = (int)($line['id'] ?? 0);
                                        if ($lid > 0 && !in_array($lid, $itemIds, true)) {
                                            $itemIds[] = $lid;
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        $lines = $ordersModel->getOrderByOrderNumber($oidVal);
                        if (is_array($lines)) {
                            foreach ($lines as $line) {
                                $id = (int)($line['id'] ?? 0);
                                if ($id > 0 && !in_array($id, $itemIds, true)) {
                                    $itemIds[] = $id;
                                }
                            }
                        }
                    }
                }
            }
        }

        if (empty($itemIds)) {
            if (!$hasExplicitInput && isset($_SESSION['invoice_items']) && !empty($_SESSION['invoice_items']) && is_array($_SESSION['invoice_items'])) {
                $itemIds = $_SESSION['invoice_items'];
            } else {
                unset($_SESSION['invoice_items']);
                unset($_SESSION['invoice_pos_flag']);
                renderTemplate('views/errors/not_found.php', ['message' => 'No items selected for Invoice.'], 'No items selected');
                exit;
            }
        }

        $posFlag = isset($_POST['pos_flag']) ? (int)$_POST['pos_flag'] : (isset($_GET['pos_flag']) ? (int)$_GET['pos_flag'] : 0);
        if ($posFlag === 0 && !empty($_SESSION['invoice_pos_flag'])) {
            $posFlag = 1;
        }

        if (!empty($itemIds)) {
            $_SESSION['invoice_items'] = array_values(array_unique($itemIds));
        }
        if (!empty($_SESSION['invoice_pos_flag'])) {
            unset($_SESSION['invoice_pos_flag']);
        }

        // Fetch order data for selected items
        $data = [];
        foreach ($itemIds as $id) {
            $order = $ordersModel->getOrderById($id);
            if ($order) {
                $data['data'][] = $order;
            }
        }

        if (empty($data['data'])) {
            unset($_SESSION['invoice_items']);
            renderTemplate('views/errors/not_found.php', ['message' => 'No valid order items found for Invoice.'], 'No items selected');
            exit;
        }

        // Check if an active (non-cancelled) invoice is already generated for any of the selected orders
        $firstOrderNo = trim((string)($data['data'][0]['order_number'] ?? ''));
        if ($firstOrderNo !== '') {
            $existingInvoice = $invoiceModel->getActiveInvoiceForOrderNumber($firstOrderNo);
            if (is_array($existingInvoice) && !empty($existingInvoice['id'])) {
                unset($_SESSION['invoice_items']);
                unset($_SESSION['invoice_pos_flag']);
                $noticeMessage = 'Invoice already created for Order #' . $firstOrderNo . ' (Invoice #' . ($existingInvoice['invoice_number'] ?? $existingInvoice['id']) . ').';
                $_SESSION['flash_notice_message'] = $noticeMessage;
                $redirectUrl = base_url('?page=invoices&action=view&id=' . (int)$existingInvoice['id'] . '&already_created=1');
                header('Location: ' . $redirectUrl);
                exit;
            }
        }
        //customer info
        $orderNumber = [];
        $data['customer'] = $commanModel->getRecordById('vp_customers', isset($data['data'][0]['customer_id']) ? $data['data'][0]['customer_id'] : 0);
        foreach ($data['data'] as $key => $order) {
            //same order_number validation
            if (!in_array($order['order_number'], $orderNumber)) {
                $orderNumber[] = $order['order_number'];
                $data['customer_address'][$key] = $commanModel->get_customer_address($order['order_number']);
            }
        }

        $firstAddress = !empty($data['customer_address']) && is_array($data['customer_address'])
            ? reset($data['customer_address'])
            : null;
        $pricingMap = pos_order_build_line_display_pricing_map(
            $data['data'],
            null,
            is_array($firstAddress) ? $firstAddress : null,
            $commanModel
        );

        foreach ($data['data'] as $key => $order) {
            $gstRate = (float)($order['gst'] ?? 0);
            $qty = max(1, (int)($order['quantity'] ?? 1));
            $lineId = (int)($order['id'] ?? 0);
            $pricing = $pricingMap[$lineId] ?? null;

            if (is_array($pricing) && isset($pricing['taxable_value'])) {
                $unitPriceBeforeGst = round((float)$pricing['taxable_value'] / $qty, 4);
            } else {
                $unitPriceBeforeGst = pos_order_pretax_unit_price($order, 'disc');
            }
            $data['data'][$key]['unit_price'] = number_format($unitPriceBeforeGst, 2, '.', '');
        }
        //firm info
        $data['firm'] = app_setting_firm_details();
        //$data['customer_address'] = $commanModel->get_customer_address(isset($data['data'][0]['order_number']) ? $data['data'][0]['order_number'] : 0);
        //address info
        $data['exotic_address'] = $commanModel->get_exotic_address();

        $data['users'] = $usersModel->getAllUsers();
        $data['invoiceModel'] = null; // placeholder for next invoice number logic
        $data['pos_flag'] = $posFlag;
        $data['eway_transporters'] = $conn instanceof mysqli
            ? (new CourierPartner($conn))->getEwayTransporters()
            : [];

        $firstCurrency = $data['data'][0]['currency'] ?? 'INR';
        if ($firstCurrency && $firstCurrency !== 'INR') {
            $firstAddress = null;
            if (!empty($data['customer_address']) && is_array($data['customer_address'])) {
                $firstAddress = reset($data['customer_address']);
            }
            $data['international_defaults'] = buildInternationalInvoiceDefaults(
                $data['data'],
                is_array($firstAddress) ? $firstAddress : null,
                is_array($data['firm'] ?? null) ? $data['firm'] : null,
                $commanModel,
                $GLOBALS['conn'] ?? null
            );
            if ($conn instanceof mysqli) {
                $portModel = new PortMaster($conn);
                $data['shipping_ports'] = $portModel->getActivePorts('', '', 200);
                $typeLabels = PortMaster::portTypeLabels();
                $data['shipping_port_types'] = [];
                foreach (['air', 'sea', 'inland', 'dry'] as $typeKey) {
                    if (isset($typeLabels[$typeKey])) {
                        $data['shipping_port_types'][$typeKey] = $typeLabels[$typeKey];
                    }
                }
                $data['international_defaults'] = $portModel->applyInvoiceDefaults($data['international_defaults']);
                $countryModel = new Country($conn);
                $data['invoice_countries'] = $countryModel->getAllCountries()['countries'] ?? [];
            }
        }

        renderTemplate('views/invoices/create.php', $data, 'Create Invoice');
        exit;
    }

    public function createPost()
    {
        is_login();
        global $invoiceModel, $ordersModel, $commanModel, $conn;
        header('Content-Type: application/json');

        $currency = isset($_POST['currency']) && is_array($_POST['currency']) ? $_POST['currency'] : [];
        $firstCurrency = $currency[0] ?? 'INR';
        $orderNumbers = isset($_POST['order_number']) && is_array($_POST['order_number']) ? $_POST['order_number'] : [];
        $isInternational = ($firstCurrency && $firstCurrency !== 'INR');

        if ($isInternational) {
            $firstOrderNumber = $orderNumbers[0] ?? '';
            $orderAddress = $firstOrderNumber !== ''
                ? $commanModel->get_customer_address($firstOrderNumber)
                : null;
            $firm = app_setting_firm_details();
            $orderRows = [];
            foreach ($orderNumbers as $orderNumber) {
                $lines = $ordersModel->getOrderByOrderNumber($orderNumber);
                if (is_array($lines) && !empty($lines)) {
                    $orderRows[] = $lines[0];
                }
            }
            $_POST = array_merge(
                $_POST,
                mergeInternationalInvoiceDefaults(
                    $_POST,
                    $orderRows,
                    is_array($orderAddress) ? $orderAddress : null,
                    is_array($firm) ? $firm : null,
                    $commanModel,
                    $conn
                )
            );
        }

        $result = $this->invoiceCreationService()->createFromPost($_POST, [
            'source' => 'order_list',
            'duplicate_order_check' => true,
            'clear_invoice_session' => true,
            'update_order_invoice_id' => true,
        ]);

        if (empty($result['success'])) {
            echo json_encode($result);
            exit;
        }

        $invoiceId = (int)($result['invoice_id'] ?? 0);

        echo json_encode(array_merge($result, [
            'is_international' => $isInternational,
        ]));
        exit;
    }

    private function invoiceCreationService(): InvoiceCreationService
    {
        global $conn, $invoiceModel, $ordersModel, $commanModel;
        require_once __DIR__ . '/../helpers/invoice/InvoiceCreationService.php';

        return new InvoiceCreationService($conn, $invoiceModel, $ordersModel, $commanModel);
    }

    /**
     * Flatten Alankit / IRN error payloads into unique readable lines.
     *
     * @param mixed $value
     * @return list<string>
     */
    private function flattenEinvoiceErrors($value): array
    {
        $lines = [];
        $walk = function ($item) use (&$walk, &$lines): void {
            if ($item === null || $item === '' || $item === false) {
                return;
            }
            if (is_string($item)) {
                $trim = trim($item);
                if ($trim === '') {
                    return;
                }
                $decoded = json_decode($trim, true);
                if (json_last_error() === JSON_ERROR_NONE && (is_array($decoded) || is_object($decoded))) {
                    $walk($decoded);
                    return;
                }
                $lines[] = $trim;
                return;
            }
            if (is_object($item)) {
                $item = (array) $item;
            }
            if (is_array($item)) {
                $code = $item['ErrorCode'] ?? $item['error_code'] ?? $item['ErrorCd'] ?? $item['errorCode'] ?? '';
                $msg = $item['ErrorMessage'] ?? $item['error_message'] ?? $item['ErrorMsg'] ?? $item['ErrorDesc']
                    ?? $item['message'] ?? $item['Message'] ?? $item['InfMsg'] ?? '';
                if ($code !== '' || $msg !== '') {
                    $line = trim(($code !== '' ? '[' . $code . '] ' : '') . $msg);
                    if ($line !== '') {
                        $lines[] = $line;
                    }
                    return;
                }
                foreach ($item as $child) {
                    $walk($child);
                }
                return;
            }
            $cast = trim((string) $item);
            if ($cast !== '') {
                $lines[] = $cast;
            }
        };
        $walk($value);

        $unique = [];
        foreach ($lines as $line) {
            if ($line !== '' && !in_array($line, $unique, true)) {
                $unique[] = $line;
            }
        }

        return $unique;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function emitEinvoiceJson(array $payload): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    public function regenerateIrn()
    {
        is_login();
        global $conn, $invoiceModel, $commanModel;

        require_once dirname(__DIR__) . '/helpers/courier/country_codes.php';

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!is_array($input)) {
                $input = $_POST;
            }

            $invoiceId = isset($input['invoice_id']) ? (int)$input['invoice_id'] : 0;

            if ($invoiceId <= 0) {
                $this->emitEinvoiceJson([
                    'success' => false,
                    'message' => 'Invalid invoice ID',
                    'errors' => ['Invalid invoice ID'],
                ]);
            }

            $invoice = $invoiceModel->getInvoiceById($invoiceId);
            if (!$invoice) {
                $this->emitEinvoiceJson([
                    'success' => false,
                    'message' => 'Invoice not found',
                    'errors' => ['Invoice not found for ID #' . $invoiceId],
                ]);
            }

            $isInternational = false;
            if (!empty($invoice['currency']) && strtoupper((string) $invoice['currency']) !== 'INR') {
                $isInternational = true;
            } else {
                $shippingCountry = $invoice['address']['shipping_country'] ?? $invoice['address']['country'] ?? 'IN';
                $destCountry = normalizeCountryIso2($shippingCountry, $conn);
                $isInternational = isInternationalShipmentCountry($destCountry, $conn);
            }

            if ($isInternational) {
                $internationalFields = ['pre_carriage_by', 'port_of_loading', 'port_of_discharge', 'country_of_origin', 'country_of_final_destination', 'final_destination', 'usd_export_rate', 'ap_cost', 'freight_charge', 'insurance_charge', 'shipping_bill_number', 'shipping_bill_date', 'shipping_port', 'shipping_ref_clm', 'shipping_currency', 'shipping_country_code', 'shipping_exp_duty'];
                $internationalData = [];
                foreach ($internationalFields as $field) {
                    if (isset($input[$field])) {
                        $value = $input[$field];
                        if ($field === 'shipping_bill_date') {
                            $internationalData[$field] = normalize_mysql_date($value);
                        } else if (in_array($field, ['usd_export_rate', 'ap_cost', 'freight_charge', 'insurance_charge', 'shipping_exp_duty'])) {
                            $internationalData[$field] = floatval($value);
                        } else {
                            $internationalData[$field] = trim((string)$value);
                        }
                    }
                }

                if (!empty($internationalData['shipping_port'])) {
                    $internationalData['port_code'] = strtoupper((string) $internationalData['shipping_port']);
                }

                if (!empty($internationalData)) {
                    $invoiceModel->updateInvoiceInternational($invoiceId, $internationalData);
                }

                $irn = $this->generateAlankitIrnForInvoice($invoiceId);

                if ($irn) {
                    $this->emitEinvoiceJson([
                        'success' => true,
                        'message' => 'E-Invoice (IRN) generated successfully',
                        'is_international' => true,
                    ]);
                }

                $internationalRecord = $invoiceModel->getInternationalInvoiceByInvoiceId($invoiceId) ?: [];
                $responsePayload = [];
                if (!empty($internationalRecord['response_payload'])) {
                    $decodedResponse = json_decode((string) $internationalRecord['response_payload'], true);
                    $responsePayload = is_array($decodedResponse) ? $decodedResponse : [];
                }
                $errors = $this->flattenEinvoiceErrors([
                    $internationalRecord['irn_error_message'] ?? null,
                    $internationalRecord['ewb_error_message'] ?? null,
                    $responsePayload['ErrorDetails'] ?? null,
                    $responsePayload['InfoDtls'] ?? null,
                    $responsePayload['message'] ?? $responsePayload['Message'] ?? null,
                ]);
                if ($errors === []) {
                    $errors[] = 'Failed to generate IRN for this international invoice.';
                }
                $this->emitEinvoiceJson([
                    'success' => false,
                    'message' => $errors[0],
                    'errors' => $errors,
                    'irn_error_message' => $internationalRecord['irn_error_message'] ?? '',
                    'is_international' => true,
                ]);
            }

            $items = $invoiceModel->getInvoiceItems($invoiceId);
            if (empty($items)) {
                $this->emitEinvoiceJson([
                    'success' => false,
                    'message' => 'No items found for invoice #' . $invoiceId,
                    'errors' => ['No items found for invoice #' . $invoiceId],
                ]);
            }

            $customer = $commanModel->getRecordById('vp_order_info', $invoice['vp_order_info_id'] ?? 0);
            if (!$customer) {
                $customer = $commanModel->getRecordById('vp_customers', $invoice['customer_id'] ?? 0);
            }
            if (!$customer) {
                $customer = $invoice['address'] ?? [];
            }

            $firm = app_setting_firm_details() ?: [];

            $config = include dirname(__DIR__) . '/config.php';
            $alankitConfig = $config['alankit'] ?? [];

            require_once dirname(__DIR__) . '/models/invoice/DomesticEwbIrnService.php';
            $service = new DomesticEwbIrnService($conn, $alankitConfig);

            $result = $service->generateIrnAndEwb(
                $invoiceId,
                $invoice,
                $items,
                $customer,
                $firm,
                []
            );

            if (!empty($result['status']) && $result['status'] === true) {
                $this->emitEinvoiceJson([
                    'success' => true,
                    'message' => $result['irn_message'] ?? 'E-Invoice (IRN) generated successfully!',
                    'irn' => $result['irn'] ?? '',
                    'is_international' => false,
                ]);
            }

            $stored = $service->getEwbIrnRecord($invoiceId);
            $storedResponseErrors = [];
            if (is_array($stored) && !empty($stored['irn_response'])) {
                $decodedStored = json_decode((string) $stored['irn_response'], true);
                if (is_array($decodedStored)) {
                    $storedResponseErrors = [
                        $decodedStored['ErrorDetails'] ?? null,
                        $decodedStored['InfoDtls'] ?? null,
                        $decodedStored['message'] ?? $decodedStored['Message'] ?? null,
                    ];
                }
            }
            $errors = $this->flattenEinvoiceErrors([
                $result['errors'] ?? null,
                $result['error_details'] ?? null,
                $result['message'] ?? null,
                $result['irn_error'] ?? null,
                $result['ewb_error'] ?? null,
                is_array($stored) ? ($stored['irn_error'] ?? null) : null,
                is_array($stored) ? ($stored['ewb_error'] ?? null) : null,
                $storedResponseErrors,
            ]);
            if ($errors === []) {
                $errors[] = 'Failed to generate E-Invoice.';
            }

            $this->emitEinvoiceJson([
                'success' => false,
                'message' => $errors[0],
                'errors' => $errors,
                'error_details' => $result['error_details'] ?? '',
                'is_international' => false,
            ]);
        } catch (Throwable $e) {
            $this->emitEinvoiceJson([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => array_values(array_filter([
                    $e->getMessage(),
                    'File: ' . basename($e->getFile()) . ' (line ' . $e->getLine() . ')',
                ])),
            ]);
        }
    }

    public function generateIrnForInvoice($invoiceId)
    {
        is_login();
        global $invoiceModel, $commanModel;

        $invoice = $invoiceModel->getInvoiceById($invoiceId);
        $items = $invoiceModel->getInvoiceItems($invoiceId);

        if (!$invoice || empty($items)) {
            return false;
        }

        // Prepare data for IRN generation
        $invoiceData = $commanModel->prepareIrisIrpInvoiceData($invoice, $items);

        require_once 'models/invoice/IrisIrpClient.php';
        // Initialize IrisIrpClient
        $irisClient = new IrisIrpClient(
            IRIS_IRP_CLIENT_ID,
            IRIS_IRP_CLIENT_SECRET,
            IRIS_IRP_USERNAME,
            IRIS_IRP_PASSWORD,
            IRIS_IRP_SANDBOX
        );

        try {
            // Authenticate
            $irisClient->authenticate();

            // Generate IRN
            $response = $irisClient->generateIrn($invoiceData);

            if (isset($response['irn'])) {
                // Update invoice with IRN details
                $updateData = [
                    'irn' => $response['irn'],
                    'ack_number' => $response['ack_number'] ?? '',
                    'ack_date' => isset($response['ack_date']) ? date('Y-m-d H:i:s', strtotime($response['ack_date'])) : null,
                    'signed_invoice' => $response['signed_invoice'] ?? '',
                    'qrcode_string' => $response['qr_code'] ?? '',
                    'irn_status' => 'generated'
                ];
                $invoiceModel->updateInvoice($invoiceId, $updateData);
                return true;
            } else {
                // Log error or handle failure
                return false;
            }
        } catch (Exception $e) {
            // Log exception or handle error
            return false;
        }
    }

    /**
     * Generate Alankit IRN for International Invoices
     * @param int $invoiceId Invoice ID
     * @param string $invoiceNumber Invoice number
     * @param array $invoiceData Invoice data
     * @return boolean
     */
    public function generateAlankitIrnForInvoice_old($invoiceId)
    {
        global $invoiceModel, $commanModel;

        try {
            // Get full invoice details
            $invoice = $invoiceModel->getInvoiceById($invoiceId);
            $items = $invoiceModel->getInvoiceItems($invoiceId);
            $internationalData = $invoiceModel->getInternationalInvoiceByInvoiceId($invoiceId);

            if (!$invoice || empty($items)) {
                error_log("Alankit IRN: Missing invoice or items for invoice #$invoiceId");
                return false;
            }

            // Get customer and firm details
            $customer = $commanModel->getRecordById('vp_order_info', $invoice['vp_order_info_id'] ?? 0);
            $firm = app_setting_firm_details();

            if (!$customer || !$firm) {
                error_log("Alankit IRN: Missing customer or firm details for invoice #$invoiceId");
                return false;
            }
            //fetch customer from vp_customers form email and phone
            $customer_info = $commanModel->getRecordById('vp_customers', $invoice['customer_id'] ?? 0);

            // Prepare line items
            $lineItems = [];
            foreach ($items as $idx => $item) {
                $lineItems[] = [
                    'item_number' => $idx + 1,
                    'item_code' => $item['item_code'] ?? '',
                    'item_name' => $item['item_name'] ?? '',
                    'description' => $item['description'] ?? '',
                    'hsn' => $item['hsn'] ?? '',
                    'quantity' => $item['quantity'] ?? 0,
                    'unit' => 'PCS',
                    'unit_price' => $item['unit_price'] ?? 0,
                    'amount' => ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0),
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'sgst' => $item['sgst'] ?? 0,
                    'cgst' => $item['cgst'] ?? 0,
                    'igst' => $item['igst'] ?? 0,
                    'tax_amount' => $item['tax_amount'] ?? 0,
                    'total' => $item['line_total'] ?? 0
                ];
            }

            // Prepare Alankit IRN payload
            $buyerAddress = ($customer['address_line1'] ?? '') . ' ' . ($customer['address_line2'] ?? '');
            $shippingAddress = ($customer['shipping_address_line1'] ?? '') . ' ' . ($customer['shipping_address_line2'] ?? '');
            $irnPayload = [
                'invoice_number' => $invoice['invoice_number'] ?? '',
                'invoice_date' => $invoice['invoice_date'] ? date('Y-m-d', strtotime($invoice['invoice_date'])) : date('Y-m-d'),
                'seller_gstin' => '07AADCE1400C1ZJ',
                'seller_name' => $firm['firm_name'] ?? '',
                'seller_address' => $firm['address'] ?? '',
                'seller_city' => $firm['city'] ?? '',
                'seller_state' => $firm['state'] ?? '',
                'seller_pincode' => $firm['pin'],
                'seller_email' => $firm['email'] ?? '',
                'seller_phone' => $firm['phone'] ?? '',
                'seller_state_code' => $firm['state_code'] ?? '',
                'seller_country' => 'IN',
                'buyer_name' => ($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''),
                'buyer_address' => $buyerAddress,
                'buyer_city' => trim($customer['city']) ?? $customer['city'] ?? '',
                'buyer_state' => trim($customer['state']) ?? $customer['state'] ?? '',
                'buyer_country' => trim($customer['country']) ?? $customer['country'] ?? 'IN',
                'buyer_pincode' => trim($customer['zipcode']) ?? $customer['zipcode'] ?? '',
                'buyer_state_code' => trim($customer['state_code']) ?? $customer['state_code'] ?? '',
                'buyer_email' => $customer_info['email'] ?? '',
                'buyer_phone' => $customer_info['phone'] ?? '',
                'buyer_gstin' => $customer['gstin'] ?? '',
                'shipping_name' => $customer['shipping_first_name'] . ' ' . $customer['shipping_last_name'] ?? '',
                'shipping_address' => trim($shippingAddress) ? $shippingAddress : $buyerAddress,
                'shipping_city' => trim($shippingAddress) ? $customer['shipping_city'] : $customer['city'] ?? '',
                'shipping_state' => trim($shippingAddress) ? $customer['shipping_state'] : $customer['state'] ?? '',
                'shipping_country' => trim($shippingAddress) ? $customer['shipping_country'] : $customer['country'] ?? 'IN',
                'shipping_pincode' => trim($shippingAddress) ? $customer['shipping_zipcode'] : $customer['zipcode'] ?? '',
                'currency' => $invoice['currency'] ?? 'INR',
                'line_items' => $lineItems,
                'subtotal' => $invoice['subtotal'] ?? 0,
                'tax_amount' => $invoice['tax_amount'] ?? 0,
                'discount_amount' => $invoice['discount_amount'] ?? 0,
                'total_amount' => $invoice['total_amount'] ?? 0,
                'notes' => $internationalData['final_destination'] ?? '',
                'reference_number' => $invoice['invoice_number'] ?? ''
            ];

            // Load Alankit IRN Client
            require_once 'models/invoice/AlankitIrnClient.php';

            // Get Alankit API credentials from config
            $config = include 'config.php';
            $alankitConfig = $config['alankit'] ?? [];

            if (
                empty($alankitConfig) ||
                empty($alankitConfig['username']) ||
                empty($alankitConfig['password']) ||
                empty($alankitConfig['subscription_key']) ||
                empty($alankitConfig['app_key']) ||
                empty($alankitConfig['gstin'])
            ) {
                error_log("Alankit IRN: Missing API credentials in config.php. Please configure alankit section with username, password, subscription_key, app_key, and gstin");
                return false;
            }

            // Initialize Alankit client with credentials from config
            // Option 1: Use existing AppKey from config
            $alankitClient = new AlankitIrnClient(
                $alankitConfig['username'],
                $alankitConfig['password'],
                $alankitConfig['subscription_key'],
                $alankitConfig['app_key'],
                $alankitConfig['gstin'],
                $alankitConfig['force_refresh_access_token'] ?? true
            );

            // Option 2 (Alternative): Create with auto-generated AppKey
            // $alankitClient = AlankitIrnClient::createWithGeneratedKey(
            //     $alankitConfig['username'],
            //     $alankitConfig['password'],
            //     $alankitConfig['subscription_key'],
            //     $alankitConfig['gstin'],
            //     true  // Use AES-256 (64-char hex AppKey)
            // );
            //echo "Alankit IRN: Initialized AlankitIrnClient with provided credentials.\n";
            // Generate IRN
            $response = $alankitClient->generateIrn($irnPayload);

            if ($response && isset($response['status']) && $response['status'] === true) {
                // Update invoice with IRN details and store payloads for audit trail
                $updateData = [
                    'irn' => $response['irn'] ?? null,
                    'ack_number' => $response['ack_number'] ?? null,
                    'ack_date' => $response['ack_date'] ? date('Y-m-d H:i:s', strtotime($response['ack_date'])) : null,
                    'signed_invoice' => $response['signed_invoice'] ?? null,
                    'qrcode_string' => $response['qr_code'] ?? null,
                    'irn_status' => 'generated',
                    'request_payload' => json_encode($irnPayload),
                    'response_payload' => json_encode($response)
                ];

                // Update invoice international table with IRN details
                $invoiceModel->updateInvoice($invoiceId, $updateData);

                error_log("Alankit IRN generated successfully for invoice #$invoiceId: " . ($response['irn'] ?? 'No IRN'));
                return true;
            } else {
                // Store request and error response for debugging
                $updateData = [
                    'irn_status' => 'failed',
                    'request_payload' => json_encode($irnPayload),
                    'response_payload' => json_encode($response ?? ['error' => 'No response received'])
                ];

                $invoiceModel->updateInvoice($invoiceId, $updateData);
                error_log("Alankit IRN generation failed for invoice #$invoiceId: " . ($response['message'] ?? 'Unknown error'));
                return false;
            }
        } catch (Exception $e) {
            error_log("Alankit IRN Exception for invoice #$invoiceId: " . $e->getMessage());
            return false;
        }
    }
    public function generateAlankitIrnForInvoice($invoiceId)
    {
        global $invoiceModel, $commanModel;

        try {
            // Get full invoice details
            $invoice = $invoiceModel->getInvoiceById($invoiceId);
            $items = $invoiceModel->getInvoiceItems($invoiceId);
            $internationalData = $invoiceModel->getInternationalInvoiceByInvoiceId($invoiceId);
            //print_r($internationalData);
            if (!$invoice || empty($items)) {
                error_log("Alankit IRN: Missing invoice or items for invoice #$invoiceId");
                return false;
            }

            // Get customer and firm details
            $customer = $commanModel->getRecordById('vp_order_info', $invoice['vp_order_info_id'] ?? 0);
            $firm = app_setting_firm_details();

            if (!$customer || !$firm) {
                error_log("Alankit IRN: Missing customer or firm details for invoice #$invoiceId");
                return false;
            }
            require_once 'models/invoice/AlankitIrnNew.php';

            // Get Alankit API credentials from config
            $config = include 'config.php';
            $alankitConfig = $config['alankit'] ?? [];

            //authenticate 
            $alankitClient = new AlankitIrnNew(
                $alankitConfig['username'],
                $alankitConfig['password'],
                $alankitConfig['subscription_key'],
                $alankitConfig['app_key'],
                $alankitConfig['gstin'],
                $alankitConfig['force_refresh_access_token'] ?? true
            );
            //fetch customer from vp_customers form email and phone
            $customer_info = $commanModel->getRecordById('vp_customers', $invoice['customer_id'] ?? 0);

            // Prepare line items
            $lineItems = [];
            foreach ($items as $idx => $item) {
                $lineItems[] = [
                    'item_number' => $idx + 1,
                    'item_code' => $item['item_code'] ?? '',
                    'item_name' => $item['item_name'] ?? '',
                    'description' => $item['description'] ?? '',
                    'hsn' => $item['hsn'] ?? '',
                    'quantity' => $item['quantity'] ?? 0,
                    'unit' => 'PCS',
                    'unit_price' => $item['unit_price'] ?? 0,
                    'amount' => ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0),
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'sgst' => $item['sgst'] ?? 0,
                    'cgst' => $item['cgst'] ?? 0,
                    'igst' => $item['igst'] ?? 0,
                    'tax_amount' => $item['tax_amount'] ?? 0,
                    'total' => $item['line_total'] ?? 0
                ];
            }

            // Prepare Alankit IRN payload
            $buyerAddress = trim(($customer['address_line1'] ?? '') . ' ' . ($customer['address_line2'] ?? ''));
            $shippingAddress = trim(($customer['shipping_address_line1'] ?? '') . ' ' . ($customer['shipping_address_line2'] ?? ''));

            // Set buyer and shipping details for direct export
            $countryRaw = strtoupper(trim((string)($customer['country'] ?? '')));
            $invCurrency = strtoupper(trim((string)($invoice['currency'] ?? 'INR')));
            $isDirectExport = ($invCurrency !== 'INR') || ($countryRaw !== '' && $countryRaw !== 'IN' && $countryRaw !== 'INDIA');

            $buyerGstin = $isDirectExport ? 'URP' : (trim((string)($customer['gstin'] ?? '')) ?: 'URP');

            $rawBuyerStateCode = trim((string)($customer['state_code'] ?? ''));
            $buyerStateCode = $isDirectExport ? '96' : (is_numeric($rawBuyerStateCode) && (int)$rawBuyerStateCode > 0 ? sprintf('%02d', (int)$rawBuyerStateCode) : '07');

            $buyerPincode = $isDirectExport ? 999999 : ((int)($customer['zipcode'] ?? 0) ?: 110001);

            $rawShipStateCode = trim((string)($customer['shipping_state_code'] ?? $customer['state_code'] ?? ''));
            $shippingStateCode = $isDirectExport ? '96' : (is_numeric($rawShipStateCode) && (int)$rawShipStateCode > 0 ? sprintf('%02d', (int)$rawShipStateCode) : $buyerStateCode);

            $shippingPincode = $isDirectExport ? 999999 : ((int)($customer['shipping_zipcode'] ?? $customer['zipcode'] ?? 0) ?: 110001);

            $buyerEmail = trim((string)($customer_info['email'] ?? $customer['shipping_email'] ?? $customer['email'] ?? ''));
            $buyerPhone = trim((string)($customer_info['phone'] ?? $customer['shipping_phone'] ?? $customer['phone'] ?? $customer['mobile'] ?? ''));

            $buyerFirstName = trim((string)($customer['first_name'] ?? ''));
            $buyerLastName = trim((string)($customer['last_name'] ?? ''));
            $buyerFullName = trim($buyerFirstName . ' ' . $buyerLastName);
            if ($buyerFullName === '') {
                $buyerFullName = trim((string)($customer['name'] ?? 'Buyer'));
            }

            $shipFirstName = trim((string)($customer['shipping_first_name'] ?? ''));
            $shipLastName = trim((string)($customer['shipping_last_name'] ?? ''));
            $shipFullName = trim($shipFirstName . ' ' . $shipLastName);
            if ($shipFullName === '') {
                $shipFullName = $buyerFullName;
            }

            $irnPayload = [
                'invoice_number' => $invoice['invoice_number'] ?? '',
                'invoice_date' => $invoice['invoice_date'] ?? date('Y-m-d'),
                'seller_gstin' => $alankitConfig['gstin'] ?? '07AADCE1400C1ZJ',
                'seller_name' => $firm['firm_name'] ?? '',
                'seller_address' => $firm['address'] ?? '',
                'seller_city' => $firm['city'] ?? '',
                'seller_state' => $firm['state'] ?? '',
                'seller_pincode' => $firm['pin'] ?? 110055,
                'seller_email' => $firm['email'] ?? '',
                'seller_phone' => $firm['phone'] ?? '',
                'seller_state_code' => sprintf('%02d', (int)($firm['state_code'] ?? 7)),
                'seller_country' => 'IN',
                'buyer_name' => $buyerFullName,
                'buyer_address' => $buyerAddress !== '' ? $buyerAddress : ($shippingAddress !== '' ? $shippingAddress : 'Export Address'),
                'buyer_city' => trim((string)($customer['city'] ?? '')) ?: (trim((string)($customer['shipping_city'] ?? '')) ?: ($isDirectExport ? 'Foreign City' : 'Delhi')),
                'buyer_state' => trim((string)($customer['state'] ?? '')),
                'buyer_country' => trim((string)($customer['country'] ?? '')) ?: ($isDirectExport ? 'US' : 'IN'),
                'buyer_pincode' => $buyerPincode,
                'buyer_state_code' => $buyerStateCode,
                'buyer_email' => $buyerEmail,
                'buyer_phone' => $buyerPhone,
                'buyer_gstin' => $buyerGstin,
                'shipping_name' => $shipFullName,
                'shipping_address' => $shippingAddress !== '' ? $shippingAddress : ($buyerAddress !== '' ? $buyerAddress : 'Export Address'),
                'shipping_city' => trim((string)($customer['shipping_city'] ?? '')) ?: (trim((string)($customer['city'] ?? '')) ?: ($isDirectExport ? 'Foreign City' : 'Delhi')),
                'shipping_state' => trim((string)($customer['shipping_state'] ?? $customer['state'] ?? '')),
                'shipping_state_code' => $shippingStateCode,
                'shipping_country' => trim((string)($customer['shipping_country'] ?? $customer['country'] ?? '')) ?: ($isDirectExport ? 'US' : 'IN'),
                'shipping_pincode' => $shippingPincode,
                'currency' => $invoice['currency'] ?? 'INR',
                'line_items' => $lineItems,
                'subtotal' => $invoice['subtotal'] ?? 0,
                'tax_amount' => $invoice['tax_amount'] ?? 0,
                'discount_amount' => $invoice['discount_amount'] ?? 0,
                'total_amount' => $invoice['total_amount'] ?? 0,
                'notes' => $internationalData['final_destination'] ?? '',
                'reference_number' => $invoice['invoice_number'] ?? '',
                'pos' => $isDirectExport ? '96' : $shippingStateCode,
                'buyer_type' => $isDirectExport ? 'export' : 'business',
                'has_payment' => true,
                'shipping_bill_number' => empty($internationalData['shipping_bill_number']) ? ($invoice['invoice_number'] ?? '') : $internationalData['shipping_bill_number'],
                'shipping_bill_date' => empty($internationalData['shipping_bill_date']) ? date('d/m/Y') : $internationalData['shipping_bill_date'],
                'shipping_port_code' => empty($internationalData['shipping_port']) ? 'INABG1' : $internationalData['shipping_port'],
                'shipping_ref_clm' => empty($internationalData['shipping_ref_clm']) ? 'N' : $internationalData['shipping_ref_clm'],
                'shipping_currency' => empty($internationalData['shipping_currency']) ? 'USD' : $internationalData['shipping_currency'],
                'shipping_country_code' => empty($internationalData['shipping_country_code']) ? 'US' : $internationalData['shipping_country_code'],
                'shipping_exp_duty' => empty($internationalData['shipping_exp_duty']) ? 0 : (float)($internationalData['shipping_exp_duty']),
                'transport_selection' => $internationalData['transport_selection'] ?? 'id',
                'trans_id' => $internationalData['trans_id'] ?? '',
                'trans_name' => $internationalData['trans_name'] ?? '',
                'trans_doc_no' => $internationalData['trans_doc_no'] ?? '',
                'trans_doc_dt' => $internationalData['trans_doc_dt'] ?? '',
                'veh_no' => $internationalData['veh_no'] ?? '',
                'veh_type' => $internationalData['veh_type'] ?? ''
            ];
            // echo '<br><br><pre>';
            // print_r($irnPayload);
            $authreq = $alankitClient->authRequest();
            // call api request to auth
            // Prepare request with encrypted data
            $data = [
                "Data" => $authreq
            ];
            //echo "Alankit IRN: Sending authentication request for invoice #$invoiceId\n";
            $authdata = $alankitClient->sendRequest('AUTH_ENDPOINT', $data, false);

            if (!$authdata || !isset($authdata['Data']['AuthToken'])) {
                error_log("Alankit IRN: Authentication failed for invoice #$invoiceId. Response: " . json_encode($authdata));
                return false;
            }

            $accessToken = $authdata['Data']['AuthToken'];
            $sek = $authdata['Data']['Sek'];
            //echo "sek: $sek <br>";
            $apkey = $alankitConfig['app_key'];
            //echo "Alankit IRN: Authentication successful, accessToken #$accessToken. Access token obtained.\n";
            $decryptedSek = $alankitClient->decryptSek($sek, $apkey);
            // Encrypt IRN payload using SEK
            $payload = $alankitClient->prepareIrnPayload($irnPayload);
            //echo '<br><br>'.json_encode($payload).'<br><br>';
            $payloadreq = base64_encode(json_encode($payload));
            //$pyload = "ewogICAgIlZlcnNpb24iOiAiMS4xIiwKICAgICJUcmFuRHRscyI6IHsKICAgICAgICAiVGF4U2NoIjogIkdTVCIsCiAgICAgICAgIlN1cFR5cCI6ICJCMkIiLAogICAgICAgICJSZWdSZXYiOiAiWSIsCiAgICAgICAgIkVjbUdzdGluIjogbnVsbCwKICAgICAgICAiSWdzdE9uSW50cmEiOiAiTiIKICAgIH0sCiAgICAiRG9jRHRscyI6IHsKICAgICAgICAiVHlwIjogIklOViIsCiAgICAgICAgIk5vIjogInRlc3QwOTA0MjAyNiIsCiAgICAgICAgIkR0IjogIjA5LzA0LzIwMjYiCiAgICB9LAogICAgIlNlbGxlckR0bHMiOiB7CiAgICAgICAgIkdzdGluIjogIjA3QUdBUEE1MzYzTDAwMiIsCiAgICAgICAgIkxnbE5tIjogIk5JQyBjb21wYW55IHB2dCBsdGQiLAogICAgICAgICJUcmRObSI6ICJOSUMgSW5kdXN0cmllcyIsCiAgICAgICAgIkFkZHIxIjogIjV0aCBibG9jaywga3V2ZW1wdSBsYXlvdXQiLAogICAgICAgICJBZGRyMiI6ICJrdXZlbXB1IGxheW91dCIsCiAgICAgICAgIkxvYyI6ICJHQU5ESElOQUdBUiIsCiAgICAgICAgIlBpbiI6IDExMDA1NSwKICAgICAgICAiU3RjZCI6ICIwNyIsCiAgICAgICAgIlBoIjogIjkwMDAwMDAwMDAiLAogICAgICAgICJFbSI6ICJhYmNAZ21haWwuY29tIgogICAgfSwKICAgICJCdXllckR0bHMiOiB7CiAgICAgICAgIkdzdGluIjogIjI5QVdHUFY3MTA3QjFaMSIsCiAgICAgICAgIkxnbE5tIjogIlhZWiBjb21wYW55IHB2dCBsdGQiLAogICAgICAgICJUcmRObSI6ICJYWVogSW5kdXN0cmllcyIsCiAgICAgICAgIlBvcyI6ICIxMiIsCiAgICAgICAgIkFkZHIxIjogIjd0aCBibG9jaywga3V2ZW1wdSBsYXlvdXQiLAogICAgICAgICJBZGRyMiI6ICJrdXZlbXB1IGxheW91dCIsCiAgICAgICAgIkxvYyI6ICJHQU5ESElOQUdBUiIsCiAgICAgICAgIlBpbiI6IDU2MjE2MCwKICAgICAgICAiU3RjZCI6ICIyOSIsCiAgICAgICAgIlBoIjogIjkxMTExMTExMTExIiwKICAgICAgICAiRW0iOiAieHl6QHlhaG9vLmNvbSIKICAgIH0sCiAgICAiRGlzcER0bHMiOiB7CiAgICAgICAgIk5tIjogIkFCQyBjb21wYW55IHB2dCBsdGQiLAogICAgICAgICJBZGRyMSI6ICI3dGggYmxvY2ssIGt1dmVtcHUgbGF5b3V0IiwKICAgICAgICAiQWRkcjIiOiAia3V2ZW1wdSBsYXlvdXQiLAogICAgICAgICJMb2MiOiAiQmFuYWdhbG9yZSIsCiAgICAgICAgIlBpbiI6IDU2MjE2MCwKICAgICAgICAiU3RjZCI6ICIyOSIKICAgIH0sCiAgICAiU2hpcER0bHMiOiB7CiAgICAgICAgIkdzdGluIjogIjI5QVdHUFY3MTA3QjFaMSIsCiAgICAgICAgIkxnbE5tIjogIkNCRSBjb21wYW55IHB2dCBsdGQiLAogICAgICAgICJUcmRObSI6ICJrdXZlbXB1IGxheW91dCIsCiAgICAgICAgIkFkZHIxIjogIjd0aCBibG9jaywga3V2ZW1wdSBsYXlvdXQiLAogICAgICAgICJBZGRyMiI6ICJrdXZlbXB1IGxheW91dCIsCiAgICAgICAgIkxvYyI6ICJCYW5hZ2Fsb3JlIiwKICAgICAgICAiUGluIjogNTYyMTYwLAogICAgICAgICJTdGNkIjogIjI5IgogICAgfSwKICAgICJJdGVtTGlzdCI6IFsKICAgICAgICB7CiAgICAgICAgICAgICJTbE5vIjogIjEiLAogICAgICAgICAgICAiUHJkRGVzYyI6ICJSaWNlIiwKICAgICAgICAgICAgIklzU2VydmMiOiAiTiIsCiAgICAgICAgICAgICJIc25DZCI6ICIxMDAxIiwKICAgICAgICAgICAgIkJhcmNkZSI6ICIxMjM0NTYiLAogICAgICAgICAgICAiUXR5IjogMTAwLjM0NSwKICAgICAgICAgICAgIkZyZWVRdHkiOiAxMCwKICAgICAgICAgICAgIlVuaXQiOiAiQkFHIiwKICAgICAgICAgICAgIlVuaXRQcmljZSI6IDk5LjU0NSwKICAgICAgICAgICAgIlRvdEFtdCI6IDk5ODguODQsCiAgICAgICAgICAgICJEaXNjb3VudCI6IDEwLAogICAgICAgICAgICAiUHJlVGF4VmFsIjogMSwKICAgICAgICAgICAgIkFzc0FtdCI6IDk5NzguODQsCiAgICAgICAgICAgICJHc3RSdCI6IDEyLAogICAgICAgICAgICAiSWdzdEFtdCI6IDExOTcuNDYsCiAgICAgICAgICAgICJDZ3N0QW10IjogMCwKICAgICAgICAgICAgIlNnc3RBbXQiOiAwLAogICAgICAgICAgICAiQ2VzUnQiOiA1LAogICAgICAgICAgICAiQ2VzQW10IjogNDk4Ljk0LAogICAgICAgICAgICAiQ2VzTm9uQWR2bEFtdCI6IDEwLAogICAgICAgICAgICAiU3RhdGVDZXNSdCI6IDEyLAogICAgICAgICAgICAiU3RhdGVDZXNBbXQiOiAxMTk3LjQ2LAogICAgICAgICAgICAiU3RhdGVDZXNOb25BZHZsQW10IjogNSwKICAgICAgICAgICAgIk90aENocmciOiAxMCwKICAgICAgICAgICAgIlRvdEl0ZW1WYWwiOiAxMjg5Ny43LAogICAgICAgICAgICAiT3JkTGluZVJlZiI6ICIzMjU2IiwKICAgICAgICAgICAgIk9yZ0NudHJ5IjogIkFHIiwKICAgICAgICAgICAgIlByZFNsTm8iOiAiMTIzNDUiLAogICAgICAgICAgICAiQmNoRHRscyI6IHsKICAgICAgICAgICAgICAgICJObSI6ICIxMjM0NTYiLAogICAgICAgICAgICAgICAgIkV4cER0IjogIjAxLzA4LzIwMjMiLAogICAgICAgICAgICAgICAgIldyRHQiOiAiMDEvMDkvMjAyMyIKICAgICAgICAgICAgfSwKICAgICAgICAgICAgIkF0dHJpYkR0bHMiOiBbCiAgICAgICAgICAgICAgICB7CiAgICAgICAgICAgICAgICAgICAgIk5tIjogIlJpY2UiLAogICAgICAgICAgICAgICAgICAgICJWYWwiOiAiMTAwMDAiCiAgICAgICAgICAgICAgICB9CiAgICAgICAgICAgIF0KICAgICAgICB9CiAgICBdLAogICAgIlZhbER0bHMiOiB7CiAgICAgICAgIkFzc1ZhbCI6IDk5NzguODQsCiAgICAgICAgIkNnc3RWYWwiOiAwLAogICAgICAgICJTZ3N0VmFsIjogMCwKICAgICAgICAiSWdzdFZhbCI6IDExOTcuNDYsCiAgICAgICAgIkNlc1ZhbCI6IDUwOC45NCwKICAgICAgICAiU3RDZXNWYWwiOiAxMjAyLjQ2LAogICAgICAgICJEaXNjb3VudCI6IDEwLAogICAgICAgICJPdGhDaHJnIjogMjAsCiAgICAgICAgIlJuZE9mZkFtdCI6IDAuMywKICAgICAgICAiVG90SW52VmFsIjogMTI5MDgsCiAgICAgICAgIlRvdEludlZhbEZjIjogMTI4OTcuNwogICAgfSwKICAgICJQYXlEdGxzIjogewogICAgICAgICJObSI6ICJBQkNERSIsCiAgICAgICAgIkFjY0RldCI6ICI1Njk3Mzg5NzEzMjEwIiwKICAgICAgICAiTW9kZSI6ICJDYXNoIiwKICAgICAgICAiRmluSW5zQnIiOiAiU0JJTjExMDAwIiwKICAgICAgICAiUGF5VGVybSI6ICIxMDAiLAogICAgICAgICJQYXlJbnN0ciI6ICJHaWZ0IiwKICAgICAgICAiQ3JUcm4iOiAidGVzdCIsCiAgICAgICAgIkRpckRyIjogInRlc3QiLAogICAgICAgICJDckRheSI6IDEwMCwKICAgICAgICAiUGFpZEFtdCI6IDEwMDAwLAogICAgICAgICJQYXltdER1ZSI6IDUwMDAKICAgIH0sCiAgICAiUmVmRHRscyI6IHsKICAgICAgICAiSW52Um0iOiAiVEVTVCIsCiAgICAgICAgIkRvY1BlcmREdGxzIjogewogICAgICAgICAgICAiSW52U3REdCI6ICIwMS8wOC8yMDIzIiwKICAgICAgICAgICAgIkludkVuZER0IjogIjAxLzA5LzIwMjMiCiAgICAgICAgfSwKICAgICAgICAiUHJlY0RvY0R0bHMiOiBbCiAgICAgICAgICAgIHsKICAgICAgICAgICAgICAgICJJbnZObyI6ICJET0MvMDAyIiwKICAgICAgICAgICAgICAgICJJbnZEdCI6ICIwMS8wOC8yMDIzIiwKICAgICAgICAgICAgICAgICJPdGhSZWZObyI6ICIxMjM0NTYiCiAgICAgICAgICAgIH0KICAgICAgICBdLAogICAgICAgICJDb250ckR0bHMiOiBbCiAgICAgICAgICAgIHsKICAgICAgICAgICAgICAgICJSZWNBZHZSZWZyIjogIkRvYy8wMDMiLAogICAgICAgICAgICAgICAgIlJlY0FkdkR0IjogIjAxLzA4LzIwMjMiLAogICAgICAgICAgICAgICAgIlRlbmRSZWZyIjogIkFiYzAwMSIsCiAgICAgICAgICAgICAgICAiQ29udHJSZWZyIjogIkNvMTIzIiwKICAgICAgICAgICAgICAgICJFeHRSZWZyIjogIllvNDU2IiwKICAgICAgICAgICAgICAgICJQcm9qUmVmciI6ICJEb2MtNDU2IiwKICAgICAgICAgICAgICAgICJQT1JlZnIiOiAiRG9jLTc4OSIsCiAgICAgICAgICAgICAgICAiUE9SZWZEdCI6ICIwMS8wOC8yMDIzIgogICAgICAgICAgICB9CiAgICAgICAgXQogICAgfSwKICAgICJBZGRsRG9jRHRscyI6IFsKICAgICAgICB7CiAgICAgICAgICAgICJVcmwiOiAiaHR0cHM6Ly9laW52LWFwaXNhbmRib3gubmljLmluIiwKICAgICAgICAgICAgIkRvY3MiOiAiVGVzdCBEb2MiLAogICAgICAgICAgICAiSW5mbyI6ICJEb2N1bWVudCBUZXN0IgogICAgICAgIH0KICAgIF0sCiAgICAiRXhwRHRscyI6IHsKICAgICAgICAiU2hpcEJObyI6ICJBLTI0OCIsCiAgICAgICAgIlNoaXBCRHQiOiAiMDEvMDgvMjAyMyIsCiAgICAgICAgIlBvcnQiOiAiSU5BQkcxIiwKICAgICAgICAiUmVmQ2xtIjogIk4iLAogICAgICAgICJGb3JDdXIiOiAiQUVEIiwKICAgICAgICAiQ250Q29kZSI6ICJBRSIsCiAgICAgICAgIkV4cER1dHkiOiBudWxsCiAgICB9LAogICAgIkV3YkR0bHMiOiB7CiAgICAgICAgIlRyYW5zSWQiOiAiMTJBV0dQVjcxMDdCMVoxIiwKICAgICAgICAiVHJhbnNOYW1lIjogIlhZWiBFWFBPUlRTIiwKICAgICAgICAiRGlzdGFuY2UiOiAxMDAsCiAgICAgICAgIlRyYW5zRG9jTm8iOiAiRE9DMDEiLAogICAgICAgICJUcmFuc0RvY0R0IjogIjA0LzA0LzIwMjQiLAogICAgICAgICJWZWhObyI6ICJrYTEyMzQ1NiIsCiAgICAgICAgIlZlaFR5cGUiOiAiUiIsCiAgICAgICAgIlRyYW5zTW9kZSI6ICIxIgogICAgfQp9";
            $encryptedPayload = $alankitClient->encryptBySymmetricKey($payloadreq, $decryptedSek);
            if (!$encryptedPayload) {
                error_log("Alankit IRN: Payload encryption failed for invoice #$invoiceId");
                return false;
            }
            //echo '<br><br>'.$encryptedPayload.'<br><br>';
            // Send IRN generation request with encrypted payload
            //$irnResponse = $alankitClient->sendRequest('IRN_GENERATE_ENDPOINT', ['Data' => $encryptedPayload], true, $accessToken);
            $irnResponse = $alankitClient->generateIrn(['Data' => $encryptedPayload], $accessToken);
            //echo "Alankit IRN: IRN generation response of irn #$invoiceId\n";
            //print_r($irnResponse);
            //echo "Alankit IRN: End of IRN generation response for invoice #$invoiceId\n";
            //decrypt response
            if ($irnResponse && isset($irnResponse['Data'])) {
                $decryptedResponse = $alankitClient->decrypt_irn($irnResponse['Data'], $decryptedSek);
                $irnResponse = json_decode($decryptedResponse, true);
                //echo "Alankit IRN: IRN generation response decrypted for invoice #$invoiceId\n";
                //print_r($irnResponse);
            } else {
                error_log("Alankit IRN: No response data received for invoice #$invoiceId");
                //$irnResponse = null;
            }

            if ($irnResponse && isset($irnResponse['Status']) && $irnResponse['Status'] === 'ACT') {
                // Update invoice with IRN details and store payloads for audit trail
                $updateData = [
                    'irn' => $irnResponse['Irn'] ?? null,
                    'ack_number' => $irnResponse['AckNo'] ?? null,
                    'ack_date' => $irnResponse['AckDt'] ? date('Y-m-d H:i:s', strtotime($irnResponse['AckDt'])) : null,
                    'signed_invoice' => $irnResponse['SignedInvoice'] ?? null,
                    'qrcode_string' => $irnResponse['SignedQRCode'] ?? null,
                    'ewb_no' => $irnResponse['EwbNo'] ?? null,
                    'ewb_date' => $irnResponse['EwbDt'] ? date('Y-m-d H:i:s', strtotime($irnResponse['EwbDt'])) : null,
                    'ewb_valid_till' => $irnResponse['EwbValidTill'] ? date('Y-m-d H:i:s', strtotime($irnResponse['EwbValidTill'])) : null,
                    'irn_status' => 'generated',
                    'request_payload' => json_encode($payload),
                    'response_payload' => json_encode($irnResponse)
                ];
                //call ewb generate api if ewb number is not generated
                    if (empty($irnResponse['EwbNo'])) {
                        // Prepare EWB data for generation
                        // $ewbData = [
                        //     'irn' => $irnResponse['Irn'] ?? '',
                        //     'distance' => 100, // Default distance; can be customized from $irnPayload if needed
                        //     'trans_mode' => '1', // Default transport mode
                        //     'trans_id' => $irnPayload['EwbDtls']['TransId'] ?? '12AWGPV7107B1Z1',
                        //     'trans_name' => $irnPayload['EwbDtls']['TransName'] ?? '',
                        //     'trn_doc_dt' => $irnPayload['EwbDtls']['TransDocDt'] ?? date('d/m/Y'),
                        //     'trn_doc_no' => $irnPayload['EwbDtls']['TransDocNo'] ?? '',
                        //     'veh_no' => $irnPayload['EwbDtls']['VehNo'] ?? '',
                        //     'veh_type' => $irnPayload['EwbDtls']['VehType'] ?? 'R'
                        // ];
                        $ewbData = [
                            'irn' => $irnResponse['Irn'] ?? '',
                            'Distance' => 0, 
                        ];
                        if(!empty($internationalData['trans_id'])){
                           $ewbData['TransId'] = $internationalData['trans_id'];
                           $ewbData['TransName'] = $internationalData['trans_name'];
                        }else{
                            $ewbData['TransDocDt'] = $internationalData['trans_doc_dt'] ?? date('d/m/Y');
                            $ewbData['VehNo'] = $internationalData['veh_no'] ?? '';
                            $ewbData['VehType'] = $internationalData['veh_type'] ?? 'R';
                            $ewbData['TransMode'] = $internationalData['trans_mode'] ?? '1';
                        }
                        $ewbData = [ 
                            "DispDtls" => [ 
                                "Nm" => $customer['first_name'] . ' ' . $customer['last_name'] ?? '',
                                "Addr1" => trim($shippingAddress) ? $shippingAddress : $buyerAddress,
                                "Addr2" => "",
                                "Loc" => trim($customer['shipping_city']) ? $customer['shipping_city'] : $customer['city'] ?? '',
                                "Pin" => trim($shippingPincode) ? $shippingPincode : $buyerPincode,
                                "Stcd" => $customer['shipping_state_code'] ?? $customer['state_code'] ?? ''
                            ],  
                            "ExpDtls" => [
                            'ShipBNo' => (string)($internationalData['shipping_bill_number'] ?? ''),
                            'ShipBDt' => date('d/m/Y', strtotime($internationalData['shipping_bill_date'])),
                            'Port' => (string)($internationalData['shipping_port_code'] ?? ''),
                            'RefClm' => (string)($internationalData['shipping_ref_clm'] ?? ''),
                            'ForCur' => (string)($internationalData['shipping_currency'] ?? ''),
                            'CntCode' => (string)($internationalData['shipping_country_code'] ?? ''),
                            'ExpDuty' => (float)($internationalData['shipping_exp_duty'] ?? 0)
                        ],                      
                            // "ExpShipDtls" => [
                            // "Gstin" => "07AAACE1288P2Z8",
                            // "TrdNm" => "test",
                            // "Addr1" => "test",
                            // "Addr2" => "test",
                            // "Loc" => "test",
                            // "Pin" => 110055,
                            // "Stcd" => "07"
                            // ]  
                        ];                            
                            
                        //echo "*Alankit EWB: Sending EWB generation request for invoice #$invoiceId\n";
                        //print_r($ewbData);
                        //echo "<br><br>";
                        $ewbResponse = $alankitClient->generateEwb($ewbData, $accessToken, $decryptedSek);
                        //print_r($ewbResponse);
                        //echo "<br><br>*Alankit EWB\n";
                        if ($ewbResponse && isset($ewbResponse['EwbNo'])) {
                            $updateData['ewb_no'] = $ewbResponse['EwbNo'] ?? null;
                            $updateData['ewb_date'] = isset($ewbResponse['EwbDt']) ? date('Y-m-d H:i:s', strtotime($ewbResponse['EwbDt'])) : null;
                            $updateData['ewb_valid_till'] = isset($ewbResponse['EwbValidTill']) ? date('Y-m-d H:i:s', strtotime($ewbResponse['EwbValidTill'])) : null;
                            $updateData['ewb_request_payload'] = json_encode($ewbData);
                            $updateData['ewb_response_payload'] = json_encode($ewbResponse);
                            error_log("Alankit EWB generated successfully for invoice #$invoiceId: " . ($ewbResponse['EwbNo'] ?? 'No EWB'));
                        } else {
                            error_log("Alankit EWB generation failed for invoice #$invoiceId: " . ($ewbResponse['message'] ?? 'Unknown error'));
                            $updateData['ewb_request_payload'] = json_encode($ewbData);
                            $updateData['ewb_response_payload'] = json_encode($ewbResponse ?? ['error' => 'No response received']);
                            $updateData['ewb_error_message'] = json_encode($ewbResponse['ErrorDetails'] ?? $ewbResponse['message'] ?? 'Unknown error');
                        }
                    }

                // Update invoice international table with IRN details
                $invoiceModel->updateInvoiceInternational($invoiceId, $updateData);
                $invoiceModel->syncInvoiceEwbData($invoiceId, [
                    'irn' => $updateData['irn'] ?? null,
                    'ewb_number' => $updateData['ewb_no'] ?? null,
                    'ack_number' => $updateData['ack_number'] ?? null,
                    'ack_date' => $updateData['ack_date'] ?? null,
                ]);

                error_log("Alankit IRN generated successfully for invoice #$invoiceId: " . ($irnResponse['irn'] ?? 'No IRN'));
                return true;
            } else if($irnResponse && isset($irnResponse['InfoDtls'])) {
                // Handle specific error code for duplicate IRN
                $updateData = [
                    'irn_status' => 'duplicate',
                    'irn' => $irnResponse['InfoDtls'][0]['Desc']['Irn'] ?? null,
                    'ack_number' => $irnResponse['InfoDtls'][0]['Desc']['AckNo'] ?? null,
                    'ack_date' => isset($irnResponse['InfoDtls'][0]['Desc']['AckDt']) ? date('Y-m-d H:i:s', strtotime($irnResponse['InfoDtls'][0]['Desc']['AckDt'])) : null,
                    'request_payload' => json_encode($payload),
                    'response_payload' => json_encode($irnResponse),
                    'irn_error_message' => json_encode($irnResponse['InfoDtls'][0]['InfMsg'] ?? 'Duplicate IRN error')
                ];

                $invoiceModel->updateInvoiceInternational($invoiceId, $updateData);
                $invoiceModel->syncInvoiceEwbData($invoiceId, [
                    'irn' => $updateData['irn'] ?? null,
                    'ewb_number' => null,
                    'ack_number' => $updateData['ack_number'] ?? null,
                    'ack_date' => $updateData['ack_date'] ?? null,
                ]);
                error_log("Alankit IRN generation duplicate for invoice #$invoiceId: " . ($irnResponse['InfoDtls']['InfMsg'] ?? 'Duplicate IRN error'));
                return true;
                
            }else {
                // Store request and error response for debugging
                $updateData = [
                    'irn_status' => 'failed.',
                    'request_payload' => json_encode($payload),
                    'response_payload' => json_encode($irnResponse ?? ['error' => 'No response received']),
                    'irn_error_message' => json_encode($irnResponse['ErrorDetails'] ?? 'Unknown error')
                ];

                $invoiceModel->updateInvoiceInternational($invoiceId, $updateData);
                error_log("Alankit IRN generation failed for invoice #$invoiceId: " . ($irnResponse['message'] ?? 'Unknown error'));
                return false;
            }
        } catch (Exception $e) {
            error_log("Alankit IRN Exception for invoice #$invoiceId: " . $e->getMessage());
            try {
                $invoiceModel->updateInvoiceInternational($invoiceId, [
                    'irn_status' => 'failed',
                    'irn_error_message' => $e->getMessage(),
                ]);
            } catch (Throwable $ignored) {
            }
            return false;
        }
    }

    /**
     * Generate an Alankit E-Way bill for a non-INR invoice.
     *
     * @param int $invoiceId
     * @param array<string, mixed> $ewbData
     * @return array<string, mixed>
     */
    public function generateAlankitEwbForInvoice($invoiceId, array $ewbData = [])
    {
        global $invoiceModel, $commanModel;

        $invoiceId = (int) $invoiceId;
        if ($invoiceId <= 0) {
            return [
                'status' => false,
                'message' => 'Invalid invoice id.',
            ];
        }

        $invoice = $invoiceModel->getInvoiceById($invoiceId);
        $items = $invoiceModel->getInvoiceItems($invoiceId);
        $internationalData = $invoiceModel->getInternationalInvoiceByInvoiceId($invoiceId);

        if (!$invoice || empty($items)) {
            return [
                'status' => false,
                'message' => 'Invoice or items not found.',
            ];
        }

        $customer = $commanModel->getRecordById('vp_order_info', $invoice['vp_order_info_id'] ?? 0);
        $firm = app_setting_firm_details();
        //port_code details
        $portCode = $internationalData['port_code'] ?? $internationalData['shipping_port'] ?? '';
        //get details from shipping_port_master
        $shippingPortDetails = $commanModel->getRecordByField('shipping_port_master', 'port_code', $portCode);
        if (!$customer || !$firm) {
            return [
                'status' => false,
                'message' => 'Customer or firm data is incomplete.',
            ];
        }

        require_once 'models/invoice/AlankitIrnNew.php';

        $config = include 'config.php';
        $alankitConfig = $config['alankit'] ?? [];

        $alankitClient = new AlankitIrnNew(
            $alankitConfig['username'],
            $alankitConfig['password'],
            $alankitConfig['subscription_key'],
            $alankitConfig['app_key'],
            $alankitConfig['gstin'],
            $alankitConfig['force_refresh_access_token'] ?? true
        );

        $buyerAddress = trim((string) (($customer['address_line1'] ?? '') . ' ' . ($customer['address_line2'] ?? '')));
        $shippingAddress = trim((string) (($customer['shipping_address_line1'] ?? '') . ' ' . ($customer['shipping_address_line2'] ?? '')));
        $zip = trim((string) ($customer['shipping_zipcode'] ?? $customer['zipcode'] ?? ''));
        $payload = [
            'Irn' => (string) ($internationalData['irn'] ?? ''),
            'Distance' => 0,
            'DispDtls' => [
                'Nm' => $firm['firm_name'] ?? '',
                'Addr1' => trim((string) ($firm['firm_address'] ?? '')),                
                'Loc' => trim((string) ($firm['firm_city'] ?? '')),
                'Pin' => $firm['firm_pin'] ?? '',
                'Stcd' => trim((string) ($firm['state_code'] ?? '')),
            ],
            "ExpShipDtls" => [
                "Addr1" => $shippingPortDetails['port_name'] ?? 'Port Name',                
                "Loc" => $shippingPortDetails['city'] ?? 'City',
                "Pin" => $shippingPortDetails['pincode'] ?? 110020,
                //"Stcd"=> trim((string) ($customer['shipping_state_code'] ?? $customer['state_code'] ?? ''))
                //"Pin" => 110020,
                "Stcd"=> '07'
            ],            
        ];

        if (!empty($ewbData)) {
            if (!empty($ewbData['trans_id'])) {
                $payload['TransId'] = trim((string) $ewbData['trans_id']);
                $payload['TransName'] = trim((string) ($ewbData['trans_name'] ?? ''));
            } else {
                $payload['TransMode'] = trim((string) ($ewbData['trans_mode'] ?? '1'));
                $payload['VehNo'] = trim((string) ($ewbData['veh_no'] ?? ''));
                $payload['VehType'] = trim((string) ($ewbData['veh_type'] ?? 'R'));
                $payload['TransDocNo'] = trim((string) ($ewbData['trans_doc_no'] ?? ''));
                $payload['TransDocDt'] = trim((string) ($ewbData['trans_doc_dt'] ?? date('d/m/Y')));
            }
        }

        try {
            $authreq = $alankitClient->authRequest();
            $authdata = $alankitClient->sendRequest('AUTH_ENDPOINT', ['Data' => $authreq], false);

            if (!$authdata || !isset($authdata['Data']['AuthToken'])) {
                return [
                    'status' => false,
                    'message' => 'Alankit authentication failed.',
                    'details' => $authdata,
                ];
            }

            $accessToken = $authdata['Data']['AuthToken'];
            $sek = $authdata['Data']['Sek'];
            $decryptedSek = $alankitClient->decryptSek($sek, $alankitConfig['app_key']);

            $ewbResponse = $alankitClient->generateEwb($payload, $accessToken, $decryptedSek);

            $updateData = [
                'ewb_request_payload' => json_encode($payload),
                'ewb_response_payload' => json_encode($ewbResponse ?? ['error' => 'No response received']),
            ];

            if ($ewbResponse && isset($ewbResponse['EwbNo'])) {
                $updateData['ewb_no'] = $ewbResponse['EwbNo'] ?? null;
                $updateData['ewb_date'] = !empty($ewbResponse['EwbDt'])
                    ? date('Y-m-d H:i:s', strtotime((string) $ewbResponse['EwbDt']))
                    : null;
                $updateData['ewb_valid_till'] = !empty($ewbResponse['EwbValidTill'])
                    ? date('Y-m-d H:i:s', strtotime((string) $ewbResponse['EwbValidTill']))
                    : null;
                $updateData['ewb_error_message'] = null;

                $invoiceModel->updateInvoiceInternational($invoiceId, $updateData);
                $invoiceModel->syncInvoiceEwbData($invoiceId, [
                    'irn' => $internationalData['irn'] ?? null,
                    'ewb_number' => $ewbResponse['EwbNo'] ?? null,
                    'ack_number' => $internationalData['ack_number'] ?? null,
                    'ack_date' => $internationalData['ack_date'] ?? null,
                ]);

                return [
                    'status' => true,
                    'ewb' => $ewbResponse['EwbNo'] ?? '',
                    'ewb_no' => $ewbResponse['EwbNo'] ?? '',
                    'ewb_date' => $updateData['ewb_date'],
                    'ewb_valid_till' => $updateData['ewb_valid_till'],
                    'ewb_message' => 'E-Way bill generated successfully.',
                ];
            }
            $updateData['irn'] = $internationalData['irn'] ?? null; 
            $updateData['ewb_error_message'] = json_encode($ewbResponse['ErrorDetails'] ?? $ewbResponse['message'] ?? 'Unknown error');
            $invoiceModel->updateInvoiceInternational($invoiceId, $updateData);

            return [
                'updateInvoiceInternational' => $updateData,
                'ewb_response' => $ewbResponse,
                'status' => false,
                'message' => $ewbResponse['message'] ?? 'Failed to generate E-Way bill.',
                'error_details' => $ewbResponse['ErrorDetails'] ?? ($ewbResponse['message'] ?? 'Unknown error'),
            ];
        } catch (Exception $e) {
            error_log("Alankit EWB Exception for invoice #$invoiceId: " . $e->getMessage());

            return [
                'status' => false,
                'message' => 'Exception generating E-Way bill.',
                'error_details' => $e->getMessage(),
            ];
        }
    }

    public function resolveInvoiceContext(): ?array
    {
        global $invoiceModel, $commanModel;

        $id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : (isset($_POST['invoice_id']) ? (int)$_POST['invoice_id'] : 0));
        $orderNumber = trim((string)($_GET['order_number'] ?? $_POST['order_number'] ?? ''));

        $invoice = null;
        if ($id > 0) {
            $invoice = $invoiceModel->getInvoiceById($id);
        } elseif ($orderNumber !== '') {
            $invoice = $invoiceModel->getInvoiceByOrderNumber($orderNumber);
        }

        if (!$invoice) {
            return null;
        }

        $id = (int)$invoice['id'];
        $items = $invoiceModel->getInvoiceItems($id);
        $intlData = $invoiceModel->getInternationalInvoiceByInvoiceId($id);

        $vpOrderInfoId = (int)($invoice['vp_order_info_id'] ?? 0);
        $orderInfo = null;
        if ($vpOrderInfoId > 0 && $commanModel !== null) {
            $orderInfo = $commanModel->getRecordById('vp_order_info', $vpOrderInfoId);
        }
        if (!is_array($orderInfo) && !empty($items[0]['order_number']) && $commanModel !== null) {
            $orderInfo = $commanModel->get_customer_address($items[0]['order_number']);
        }
        if (!is_array($orderInfo)) {
            $orderInfo = [];
        }

        if ($orderNumber === '') {
            $orderNumber = (string)($items[0]['order_number'] ?? $orderInfo['order_number'] ?? '');
        }

        $firm = function_exists('app_setting_firm_details') ? app_setting_firm_details() : [];

        return [
            'invoice' => $invoice,
            'items' => $items,
            'intlData' => $intlData,
            'orderInfo' => $orderInfo,
            'firm' => $firm,
            'orderNumber' => $orderNumber,
            'id' => $id,
        ];
    }

    public function view()
    {
        is_login();

        $ctx = $this->resolveInvoiceContext();
        if ($ctx === null) {
            renderTemplate('views/errors/not_found.php', ['message' => 'Invoice not found.'], 'Not Found');
            return;
        }

        $data = [
            'invoice' => $ctx['invoice'],
            'items' => $ctx['items'],
            'internationalData' => $ctx['intlData'],
            'order_info' => $ctx['orderInfo'],
            'firm' => $ctx['firm'],
            'order_number' => $ctx['orderNumber'],
        ];

        renderTemplate('views/invoices/view.php', $data, 'Invoice Details');
    }

    public function einvoiceInput()
    {
        is_login();
        $ctx = $this->resolveInvoiceContext();
        if ($ctx === null) {
            renderTemplate('views/errors/not_found.php', ['message' => 'Invoice not found for E-Invoice generation.'], 'Not Found');
            return;
        }

        $invoiceId = $ctx['id'];
        $orderNumber = $ctx['orderNumber'];
        $invoice = $ctx['invoice'];
        $items = $ctx['items'];
        $intlData = $ctx['intlData'] ?? [];
        $orderInfo = $ctx['orderInfo'];
        $firm = $ctx['firm'];

        $isExport = ($invoice['currency'] ?? 'INR') !== 'INR' || (!empty($orderInfo['country']) && strtoupper(trim($orderInfo['country'])) !== 'IN');
        $elig = [
            'is_export' => $isExport,
            'is_b2b' => !empty($orderInfo['gstin']),
            'scenario' => $isExport ? 'Export' : (!empty($orderInfo['gstin']) ? 'Domestic B2B' : 'B2C'),
            'grand_total' => (float)($invoice['total_amount'] ?? 0),
        ];

        renderTemplate('views/pos_register/einvoice_input.php', [
            'order_info' => $orderInfo,
            'invoice' => $invoice,
            'items' => $items,
            'firm' => $firm,
            'existing_record' => $intlData,
            'eligibility' => $elig,
            'order_number' => $orderNumber,
            'submit_url' => base_url('?page=invoices&action=einvoice-submit&id=' . $invoiceId),
            'back_url' => base_url('?page=invoices&action=view&id=' . $invoiceId),
            'ewaybill_input_url' => base_url('?page=invoices&action=ewaybill-input&id=' . $invoiceId),
        ], 'Generate E-Invoice');
    }

    public function einvoiceSubmit()
    {
        global $invoiceModel;
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        $ctx = $this->resolveInvoiceContext();
        if ($ctx === null) {
            echo json_encode(['success' => false, 'message' => 'Invoice context not found.']);
            exit;
        }

        $invoiceId = $ctx['id'];

        if (!empty($_POST)) {
            $updateIntl = [];
            $fields = [
                'pre_carriage_by', 'port_of_loading', 'port_of_discharge', 'country_of_origin',
                'country_of_final_destination', 'final_destination', 'usd_export_rate', 'ap_cost',
                'freight_charge', 'insurance_charge', 'shipping_bill_number', 'shipping_bill_date',
                'shipping_port', 'shipping_ref_clm', 'shipping_currency', 'shipping_country_code',
                'shipping_exp_duty', 'transport_selection', 'trans_id', 'trans_name',
                'trans_doc_no', 'trans_doc_dt', 'veh_no', 'veh_type', 'trans_mode'
            ];
            foreach ($fields as $f) {
                if (isset($_POST[$f])) {
                    $val = trim((string)$_POST[$f]);
                    if ($f === 'shipping_bill_date') {
                        $updateIntl[$f] = normalize_mysql_date($val);
                    } elseif (in_array($f, ['usd_export_rate', 'ap_cost', 'freight_charge', 'insurance_charge', 'shipping_exp_duty'], true)) {
                        $updateIntl[$f] = (float)$val;
                    } else {
                        $updateIntl[$f] = $val;
                    }
                }
            }
            if (!empty($_POST['shipping_port_code'])) {
                $updateIntl['shipping_port'] = strtoupper(trim((string)$_POST['shipping_port_code']));
                $updateIntl['port_code'] = strtoupper(trim((string)$_POST['shipping_port_code']));
            }
            if (!empty($updateIntl)) {
                $invoiceModel->updateInvoiceInternational($invoiceId, $updateIntl);
            }
        }

        $irnOk = $this->generateAlankitIrnForInvoice($invoiceId);
        $latestIntl = $invoiceModel->getInternationalInvoiceByInvoiceId($invoiceId) ?: [];

        if ($irnOk && !empty($latestIntl['irn'])) {
            echo json_encode([
                'success' => true,
                'message' => 'E-Invoice (IRN) generated successfully!',
                'irn' => (string)$latestIntl['irn'],
                'ack_number' => (string)($latestIntl['ack_number'] ?? ''),
                'ack_date' => (string)($latestIntl['ack_date'] ?? ''),
                'ewaybill_url' => base_url('?page=invoices&action=ewaybill-input&id=' . $invoiceId),
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $errorMsg = $latestIntl['irn_error_message'] ?? 'Failed to generate IRN via Alankit API.';
        echo json_encode([
            'success' => false,
            'message' => $errorMsg,
            'error_details' => $errorMsg,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    public function ewaybillInput()
    {
        is_login();
        $ctx = $this->resolveInvoiceContext();
        if ($ctx === null) {
            renderTemplate('views/errors/not_found.php', ['message' => 'Invoice not found for E-Way Bill generation.'], 'Not Found');
            return;
        }

        $invoiceId = $ctx['id'];
        $orderNumber = $ctx['orderNumber'];
        $invoice = $ctx['invoice'];
        $intlData = $ctx['intlData'] ?? [];
        $orderInfo = $ctx['orderInfo'];
        $firm = $ctx['firm'];

        $isExport = ($invoice['currency'] ?? 'INR') !== 'INR' || (!empty($orderInfo['country']) && strtoupper(trim($orderInfo['country'])) !== 'IN');
        $elig = [
            'is_export' => $isExport,
            'is_b2b' => !empty($orderInfo['gstin']),
            'scenario' => $isExport ? 'Export' : (!empty($orderInfo['gstin']) ? 'Domestic B2B' : 'B2C'),
            'grand_total' => (float)($invoice['total_amount'] ?? 0),
        ];

        renderTemplate('views/pos_register/ewaybill_input.php', [
            'order_info' => $orderInfo,
            'invoice' => $invoice,
            'firm' => $firm,
            'existing_record' => $intlData,
            'eligibility' => $elig,
            'order_number' => $orderNumber,
            'submit_url' => base_url('?page=invoices&action=ewaybill-submit&id=' . $invoiceId),
            'back_url' => base_url('?page=invoices&action=view&id=' . $invoiceId),
        ], 'Generate E-Way bill');
    }

    public function ewaybillSubmit()
    {
        global $invoiceModel;
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        $ctx = $this->resolveInvoiceContext();
        if ($ctx === null) {
            echo json_encode(['success' => false, 'message' => 'Invoice context not found.']);
            exit;
        }

        $invoiceId = $ctx['id'];

        $ewbData = [
            'trans_id' => trim((string)($_POST['trans_id'] ?? $_POST['transporter_id'] ?? '')),
            'trans_name' => trim((string)($_POST['trans_name'] ?? $_POST['transporter_name'] ?? '')),
            'trans_mode' => trim((string)($_POST['trans_mode'] ?? '1')),
            'veh_no' => trim((string)($_POST['veh_no'] ?? $_POST['vehicle_number'] ?? '')),
            'veh_type' => trim((string)($_POST['veh_type'] ?? 'R')),
            'trans_doc_no' => trim((string)($_POST['trans_doc_no'] ?? $_POST['doc_no'] ?? '')),
            'trans_doc_dt' => trim((string)($_POST['trans_doc_dt'] ?? $_POST['doc_dt'] ?? '')),
            'distance' => (int)($_POST['distance'] ?? 0),
        ];

        $invoiceModel->updateInvoiceInternational($invoiceId, [
            'trans_id' => $ewbData['trans_id'],
            'trans_name' => $ewbData['trans_name'],
            'trans_mode' => $ewbData['trans_mode'],
            'veh_no' => $ewbData['veh_no'],
            'veh_type' => $ewbData['veh_type'],
            'trans_doc_no' => $ewbData['trans_doc_no'],
            'trans_doc_dt' => $ewbData['trans_doc_dt'],
        ]);

        $res = $this->generateAlankitEwbForInvoice($invoiceId, $ewbData);
        $latestIntl = $invoiceModel->getInternationalInvoiceByInvoiceId($invoiceId) ?: [];

        $ewbNo = (string)($latestIntl['ewb_no'] ?? $res['ewb'] ?? '');
        $ok = (!empty($res['status']) || $ewbNo !== '');

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'E-Way Bill generated successfully!' : ($res['message'] ?? 'Failed to generate E-Way bill.'),
            'ewb_no' => $ewbNo,
            'ewb_number' => $ewbNo,
            'ewb_date' => (string)($latestIntl['ewb_date'] ?? ''),
            'ewb_valid_till' => (string)($latestIntl['ewb_valid_till'] ?? ''),
            'ewb_status' => (string)($latestIntl['ewb_status'] ?? ($ok ? 'generated' : 'failed')),
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    public function generatePdf()
    {
        $this->posInvoiceController()->generatePdf();
    }

    /**
     * Shared POS invoice HTML renderer (tax / proforma / preview).
     */
    private function posInvoiceController(): PosInvoiceController
    {
        require_once __DIR__ . '/PosInvoiceController.php';

        return new PosInvoiceController();
    }

    public function previewInvoice()
    {
        is_login();
        header('Content-Type: application/json');

        try {
            global $commanModel;

            $previewData = json_decode(file_get_contents('php://input'), true);

            $orderNumber = $previewData['orderid'] ?? null;
            // echo '<pre>';
            // print_r($orderNumber);
            // exit;
            if ($orderNumber) {

                global $invoiceModel;

                $invoice = $invoiceModel->getInvoiceByOrderNumber($orderNumber);

                if (!$invoice) {
                    echo json_encode(['success' => false, 'message' => 'Invoice not found']);
                    exit;
                }

                $items = $invoiceModel->getInvoiceItems($invoice['id']);

                $html = $this->posInvoiceController()->generateInvoiceHtml($invoice, $items, 'preview');

                echo json_encode([
                    'success' => true,
                    'html' => $html
                ]);
                exit;
            }
            //print_r($previewData);
            // Get preview data from POST
            $invoiceDate = isset($previewData['invoice_date']) ? $previewData['invoice_date'] : date('Y-m-d');
            $customerId = isset($previewData['customer_id']) ? (int)$previewData['customer_id'] : 0;
            $vpAddressInfoId = isset($previewData['vp_order_info_id']) ? trim($previewData['vp_order_info_id']) : '';
            //$currency = isset($previewData['currency']) ? $previewData['currency'] : [];
            $subtotal = isset($previewData['subtotal']) ? floatval($previewData['subtotal']) : 0;
            $taxAmount = isset($previewData['tax_amount']) ? floatval($previewData['tax_amount']) : 0;
            $discountAmount = isset($previewData['discount_amount']) ? floatval($previewData['discount_amount']) : 0;
            $totalAmount = isset($previewData['total_amount']) ? floatval($previewData['total_amount']) : 0;

            // Get items
            $items = isset($previewData['items']) ? (array)$previewData['items'] : [];

            if (empty($items)) {
                echo json_encode(['success' => false, 'message' => 'No items to preview']);
                exit;
            }
            //print_array($currency);exit;
            // Build invoice data structure
            $invoice = [
                'invoice_number' => 'PREVIEW',
                'invoice_date' => $invoiceDate,
                'currency' => $items[0]['currency'] ?? 'INR',
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'vp_order_info_id' => $vpAddressInfoId,
                'terms_and_conditions' => ''
            ];
            //print_array($invoice);exit;
            // Get firm settings for terms and conditions
            require_once __DIR__ . '/../helpers/app_settings.php';
            $firmSettings = app_setting_global_settings();
            $invoice['terms_and_conditions'] = $firmSettings['terms_and_conditions'] ?? '';

            // Convert items to proper format for HTML generation
            $invoiceItems = [];
            foreach ($items as $item) {
                $quantity = floatval($item['quantity'] ?? 0);
                $unitPrice = floatval($item['unit_price'] ?? 0);
                $lineTotal = $quantity * $unitPrice;

                // Calculate GST amounts based on unit price and quantity
                $sgstPercent = floatval($item['sgst'] ?? 0);
                $cgstPercent = floatval($item['cgst'] ?? 0);
                $igstPercent = floatval($item['igst'] ?? 0);

                $sgstAmount = ($lineTotal * $sgstPercent) / 100;
                $cgstAmount = ($lineTotal * $cgstPercent) / 100;
                $igstAmount = ($lineTotal * $igstPercent) / 100;
                $totalTaxAmount = $sgstAmount + $cgstAmount + $igstAmount;

                $invoiceItems[] = [
                    'box_no' => $item['box_no'] ?? '',
                    'color' => $item['color'] ?? '',
                    'size' => $item['size'] ?? '',
                    'order_number' => $item['order_number'] ?? '',
                    'item_code' => $item['item_code'] ?? '',
                    'product_id' => $item['product_id'] ?? '',
                    'item_name' => $item['item_name'] ?? '',
                    'hsn' => $item['hsn'] ?? '',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'sgst' => $sgstAmount,
                    'cgst' => $cgstAmount,
                    'igst' => $igstAmount,
                    'tax_amount' => $totalTaxAmount,
                    'line_total' => $lineTotal + $totalTaxAmount
                ];
            }

            // Generate the invoice HTML using the tax invoice template
            $html = $this->posInvoiceController()->generateInvoiceHtml($invoice, $invoiceItems, 'preview');

            if (empty($html)) {
                echo json_encode(['success' => false, 'message' => 'Failed to generate preview HTML']);
                exit;
            }

            echo json_encode([
                'success' => true,
                'html' => $html,
                //'invoice_id' => $invoice['id']
            ]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error generating preview: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    public function clearSessionItems()
    {
        is_login();
        header('Content-Type: application/json');

        if (isset($_SESSION['invoice_items'])) {
            unset($_SESSION['invoice_items']);
        }

        echo json_encode(['success' => true, 'message' => 'Invoice items cleared from session']);
        exit;
    }
    public function printInvoice()
    {
        is_login();
        global $invoiceModel;

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            echo '<p>Invalid Invoice ID.</p>';
            exit;
        }

        $invoice = $invoiceModel->getInvoiceById($id);
        $items = $invoiceModel->getInvoiceItems($id);

        if (!$invoice) {
            echo '<p>Invoice not found.</p>';
            exit;
        }

        $data = [
            'invoice' => $invoice,
            'items' => $items
        ];

        renderTemplate('views/invoices/print.php', $data, 'Print Invoice');
    }
    public function fetchItems()
    {
        is_login();
        header('Content-Type: application/json');
        $inputdata = json_decode(file_get_contents('php://input'), true);
        global $ordersModel;
        //print_r($inputdata);exit;
        $customerId = isset($inputdata['customer_id']) ? (int)$inputdata['customer_id'] : 0;
        $itemIds = isset($inputdata['item_ids']) ? $inputdata['item_ids'] : [];
        $search = isset($inputdata['search']) ? $inputdata['search'] : 0;
        // if (empty($itemIds) || !is_array($itemIds)) {
        //     echo json_encode(['success' => false, 'message' => 'No item IDs provided']);
        //     exit;
        // }

        $itemsData = [];
        // foreach ($itemIds as $id) {
        //     $order = $ordersModel->getOrderById($id);
        //     if ($order) {
        //         $itemsData[] = $order;
        //     }
        // }

        //echo $customerId.'---'.$search;
        $orderItems = $ordersModel->getOrderItemsByCustomerId($customerId, $search, []);

        //calculate unit price before gst
        foreach ($orderItems as $key => $order) {
            $unitPriceBeforeGst = pos_order_pretax_unit_price($order, 'disc');
            $orderItems[$key]['unit_price'] = number_format($unitPriceBeforeGst, 2, '.', '');
        }

        echo json_encode(['success' => true, 'items' => $orderItems, 'selected_items' => $itemsData]);
        exit;
    }

    public function create_auto_from_order()
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');
        $input = json_decode(file_get_contents('php://input'), true);
        $orderNumber = trim((string)($input['orderid'] ?? ''));
        if ($orderNumber === '') {
            echo json_encode(['success' => false, 'message' => 'Order number missing']);
            exit;
        }
        require_once __DIR__ . '/PosInvoiceController.php';
        $posInv = new PosInvoiceController();
        echo json_encode($posInv->createAutoInvoiceForOrder($orderNumber), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    public function create_auto_from_order_bk()
    {
        is_login();
        header('Content-Type: application/json');

        try {

            global $conn, $invoiceModel;

            $input = json_decode(file_get_contents('php://input'), true);
            $orderNumber = $input['orderid'] ?? null;

            if (!$orderNumber) {
                echo json_encode(['success' => false, 'message' => 'Order number missing']);
                exit;
            }

            // skip auto-create only if a non-cancelled invoice already exists
            $existing = $invoiceModel->getActiveInvoiceForOrderNumber($orderNumber);
            if ($existing) {
                echo json_encode(['success' => true]);
                exit;
            }

            // ===== GET ORDER ITEMS DIRECT =====
            $sql = "SELECT * FROM vp_orders WHERE order_number = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $orderNumber);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                echo json_encode(['success' => false, 'message' => 'Order not found']);
                exit;
            }

            $items = [];
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }

            // ===== GET ADDRESS INFO =====
            $sql2 = "SELECT * FROM vp_order_info WHERE order_number = ? LIMIT 1";
            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param("s", $orderNumber);
            $stmt2->execute();
            $info = $stmt2->get_result()->fetch_assoc();

            if (!$info) {
                echo json_encode(['success' => false, 'message' => 'Order address not found']);
                exit;
            }

            require_once __DIR__ . '/../helpers/invoice/invoice_gst.php';
            require_once __DIR__ . '/../helpers/app_settings.php';
            $useIgst = invoice_order_info_uses_igst($info, app_setting_firm_details());

            // ===== BUILD POST LIKE MANUAL CREATE =====
            $_POST = [
                'invoice_date' => date('Y-m-d'),
                'customer_id' => $items[0]['customer_id'],
                'vp_order_info_id' => $info['id'],
                'status' => 'final',
                'subtotal' => 0,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => 0
            ];

            foreach ($items as $i => $it) {

                $_POST['order_number'][] = $it['order_number'];
                $_POST['item_code'][] = $it['item_code'];
                $_POST['item_name'][] = $it['title'];
                $_POST['hsn'][] = $it['hsn'];
                $_POST['quantity'][] = $it['quantity'];

                $unit = pos_order_pretax_unit_price($it, 'disc');

                $_POST['unit_price'][] = $unit;
                $_POST['tax_rate'][] = $it['gst'];

                $gstRates = invoice_gst_component_rates((float)$it['gst'], $useIgst);
                $_POST['cgst'][] = $gstRates['cgst_rate'];
                $_POST['sgst'][] = $gstRates['sgst_rate'];
                $_POST['igst'][] = $gstRates['igst_rate'];

                $_POST['box_no'][] = '';

                $_POST['currency'][] = $it['currency'];

                $_POST['subtotal'] += $unit * $it['quantity'];
                $_POST['tax_amount'] += ($unit * $it['quantity']) * ($it['gst'] / 100);
            }

            $_POST['total_amount'] = $_POST['subtotal'] + $_POST['tax_amount'];

            // ===== CALL EXISTING CREATE =====
            return $this->createPost();
        } catch (Exception $e) {

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }





    /**
     * Download invoice(s) as Busy XML
     * - Single invoice: /download.php?invoice_id=X&token=Y
     * - Batch by date: /download.php?date=YYYY-MM-DD&token=Y (returns ZIP with all invoices for that date)
     */
    public function downloadBusyXml()
    {
        global $conn;
        require_once 'controllers/OrdersAPIController.php';
        $ordersApi = new OrdersAPIController($conn);

        try {
            // Get parameters
            $invoiceId = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;
            $token = isset($_GET['token']) ? trim($_GET['token']) : '';
            $date = isset($_GET['date']) ? trim($_GET['date']) : '';
            $format = isset($_GET['format']) ? trim($_GET['format']) : 'zip'; // zip or consolidated

            if (!$token) {
                http_response_code(400);
                exit('Bad request: Missing token');
            }

            // Validate token
            if (!$ordersApi->isValidToken($token)) {
                http_response_code(403);
                exit('Unauthorized');
            }
            //date if blank then set to privious day
            if (empty($date)) {
                $date = date('Y-m-d', strtotime('-1 day'));
            }

            require_once 'generate-xml.php';
            global $invoiceModel;

            // Mode 1: Single invoice download
            if ($invoiceId > 0) {
                return $this->downloadSingleInvoiceXml($invoiceId);
            }

            // Mode 2: Batch download by date
            if (!empty($date)) {
                return $this->downloadBatchInvoiceXml($date, $format);
            }

            // If neither invoice_id nor date provided
            http_response_code(400);
            exit('Bad request: Must provide either invoice_id or date parameter');
        } catch (Exception $e) {
            http_response_code(500);
            exit('Error: ' . $e->getMessage());
        }
    }

    /**
     * Download single invoice as XML
     */
    private function downloadSingleInvoiceXml($invoiceId)
    {
        global $invoiceModel;

        $invoice = $invoiceModel->getInvoiceById($invoiceId);
        if (!$invoice) {
            http_response_code(404);
            exit('Invoice not found');
        }

        if (strtolower(trim((string)($invoice['status'] ?? ''))) === 'cancelled') {
            http_response_code(400);
            exit('Cancelled invoices cannot be exported to BUSY XML');
        }

        $items = $invoiceModel->getInvoiceItems($invoiceId);

        // Add calculated tax totals
        $invoice['sgst'] = array_sum(array_column($items, 'sgst'));
        $invoice['cgst'] = array_sum(array_column($items, 'cgst'));
        $invoice['igst'] = array_sum(array_column($items, 'igst'));

        // Generate XML
        require_once 'generate-xml.php';
        $generator = new BusyXmlGenerator();
        $xml = $generator->generate($invoice, $items);

        // Stream as downloadable file
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="' .
            htmlspecialchars($invoice['invoice_number'] ?? 'invoice') . '_busy.txt"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $xml;
        exit;
    }

    /**
     * Download batch invoices for a date as ZIP or consolidated XML
     */
    private function downloadBatchInvoiceXml($date, $format = 'zip')
    {
        global $invoiceModel, $conn;

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            http_response_code(400);
            exit('Bad request: Invalid date format. Use YYYY-MM-DD');
        }

        // Fetch all non-cancelled invoices for the given date
        $sql = "SELECT id FROM vp_invoices WHERE DATE(invoice_date) = ? AND LOWER(TRIM(COALESCE(status, ''))) <> 'cancelled' ORDER BY invoice_date ASC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            http_response_code(500);
            exit('Database error: ' . $conn->error);
        }

        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();

        $invoiceIds = [];
        while ($row = $result->fetch_assoc()) {
            $invoiceIds[] = $row['id'];
        }

        if (empty($invoiceIds)) {
            http_response_code(404);
            exit('No invoices found for date: ' . $date);
        }

        require_once 'generate-xml.php';

        if ($format === 'consolidated') {
            return $this->downloadConsolidatedXml($invoiceIds, $date);
        } else {
            return $this->downloadZipArchive($invoiceIds, $date);
        }
    }

    /**
     * Download invoices as consolidated single XML file
     */
    private function downloadConsolidatedXml($invoiceIds, $date)
    {
        global $invoiceModel;

        $allVouchers = [];

        // Collect all invoice data
        foreach ($invoiceIds as $invoiceId) {
            $invoice = $invoiceModel->getInvoiceById($invoiceId);
            $items = $invoiceModel->getInvoiceItems($invoiceId);

            if ($invoice && !empty($items)) {
                $invoice['sgst'] = array_sum(array_column($items, 'sgst'));
                $invoice['cgst'] = array_sum(array_column($items, 'cgst'));
                $invoice['igst'] = array_sum(array_column($items, 'igst'));
                $allVouchers[] = ['invoice' => $invoice, 'items' => $items];
            }
        }

        if (empty($allVouchers)) {
            http_response_code(404);
            exit('No valid invoices found');
        }

        // Generate consolidated XML
        $generator = new BusyXmlGenerator();
        $xml = $generator->generateConsolidated($allVouchers);

        // Stream as downloadable file
        $filename = 'invoices_' . $date . '_busy.txt';
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . htmlspecialchars($filename) . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $xml;
        exit;
    }

    /**
     * Download invoices as ZIP archive with individual XML files
     */
    private function downloadZipArchive($invoiceIds, $date)
    {
        global $invoiceModel;

        require_once 'generate-xml.php';

        // Create temporary directory for XML files
        $tempDir = sys_get_temp_dir() . '/busy_xml_' . uniqid();
        if (!mkdir($tempDir, 0755, true)) {
            http_response_code(500);
            exit('Failed to create temporary directory');
        }

        $generator = new BusyXmlGenerator();
        $xmlFiles = [];

        try {
            // Generate XML for each invoice
            foreach ($invoiceIds as $invoiceId) {
                $invoice = $invoiceModel->getInvoiceById($invoiceId);
                // add address info to invoice data
                if ($invoice) {
                    global $commanModel;
                    $customer = $commanModel->getRecordById('vp_order_info', $invoice['vp_order_info_id'] ?? 0);
                    if ($customer) {
                        $invoice['customer_name'] = $customer['first_name'] . ' ' . $customer['last_name'];
                        $invoice['customer_address1'] = trim(($customer['address_line1'] ?? ''));
                        $invoice['customer_address2'] = trim(($customer['address_line2'] ?? ''));
                        $invoice['customer_address3'] = trim(($customer['city'] ?? ''));
                        $invoice['customer_address4'] = trim(($customer['state'] ?? ''));
                        $invoice['customer_state'] = trim(($customer['state'] ?? ''));
                        $invoice['customer_zipcode'] = trim(($customer['zipcode'] ?? ''));
                        $invoice['customer_mobile'] = trim(($customer['mobile'] ?? ''));
                        $invoice['customer_email'] = trim(($customer['email'] ?? ''));
                        $invoice['customer_gstin'] = trim(($customer['gstin'] ?? ''));
                    } else {
                        $invoice['customer_name'] = '';
                        $invoice['customer_address1'] = '';
                        $invoice['customer_address2'] = '';
                        $invoice['customer_address3'] = '';
                        $invoice['customer_address4'] = '';
                        $invoice['customer_state'] = '';
                        $invoice['customer_zipcode'] = '';
                        $invoice['customer_mobile'] = '';
                        $invoice['customer_email'] = '';
                        $invoice['customer_gstin'] = '';
                    }
                    $invoice['narration'] = $invoice['customer_name'] . ' ' . ($invoice['customer_address1'] ?? '') . ' ' . ($invoice['customer_address2'] ?? '') . ' ' . ($invoice['customer_address3'] ?? '') . ' ' . ($invoice['customer_address4'] ?? '');
                }
                $address = $commanModel->get_exotic_address();
                $invoice['exotic_address'] = $address[0]['address'] ?? '';
                $items = $invoiceModel->getInvoiceItems($invoiceId);
                $invoice['total_qty'] = array_sum(array_column($items, 'quantity'));
                if ($invoice && !empty($items)) {
                    $invoice['sgst'] = array_sum(array_column($items, 'sgst'));
                    $invoice['cgst'] = array_sum(array_column($items, 'cgst'));
                    $invoice['igst'] = array_sum(array_column($items, 'igst'));

                    $xml = $generator->generate($invoice, $items);
                    $filename = $invoice['invoice_number'] ?? ('invoice_' . $invoiceId);
                    // Sanitize filename by removing path separators and invalid characters
                    $sanitized_filename = preg_replace('/[\/\\:*?"<>|]/', '_', $filename);
                    $filepath = $tempDir . '/' . $sanitized_filename . '.txt';

                    if (file_put_contents($filepath, $xml) === false) {
                        throw new Exception('Failed to write XML file: ' . $filename);
                    }

                    $xmlFiles[] = ['path' => $filepath, 'name' => basename($filepath)];
                }
            }

            if (empty($xmlFiles)) {
                http_response_code(404);
                exit('No valid invoices found');
            }

            // Create ZIP archive
            $zipFile = $tempDir . '/invoices_' . $date . '.zip';
            $zip = new \ZipArchive();

            if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new Exception('Failed to create ZIP archive');
            }

            // Add XML files to ZIP
            foreach ($xmlFiles as $file) {
                $zip->addFile($file['path'], $file['name']);
            }

            if (!$zip->close()) {
                throw new Exception('Failed to close ZIP archive');
            }

            // Stream ZIP file
            if (!file_exists($zipFile)) {
                throw new Exception('ZIP file not created');
            }

            $filesize = filesize($zipFile);
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="invoices_' . $date . '.zip"');
            header('Content-Length: ' . $filesize);
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');

            readfile($zipFile);

            // Cleanup temporary files
            foreach ($xmlFiles as $file) {
                @unlink($file['path']);
            }
            @unlink($zipFile);
            @rmdir($tempDir);

            exit;
        } catch (Exception $e) {
            // Cleanup on error
            foreach ($xmlFiles as $file) {
                @unlink($file['path']);
            }
            @unlink($zipFile ?? '');
            @rmdir($tempDir);

            http_response_code(500);
            exit('Error: ' . $e->getMessage());
        }
    }
}
