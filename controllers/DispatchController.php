<?php
require_once 'models/comman/tables.php';
require_once 'models/invoice/invoice.php';
require_once 'models/dispatch/dispatch.php';
require_once 'models/order/order.php';
require_once 'courier_selector.php';
require_once __DIR__ . '/../models/order/stock.php';
$commanModel = new Tables($conn);
$invoiceModel = new Invoice($conn); 
$dispatchModel = new Dispatch($conn);
$ordersModel = new Order($conn);

class DispatchController {
    public function create() {
        global $commanModel, $invoiceModel, $dispatchModel;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
            
            $data = $_POST;
            $data['created_by'] = 1;
            $data['created_at'] = date('Y-m-d H:i:s');
            
            // Validate invoice_id exists
            $invoice = $invoiceModel->getInvoiceById($data['invoice_id']);
            if (!$invoice) {
                $errorMsg = 'Invoice not found';
                //if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'error', 'message' => $errorMsg]);
                // } else {
                //     header('Location: ' . base_url('?page=dispatch&action=create&status=error&message=' . urlencode($errorMsg)));
                // }
                exit();
            }
            
            // Process box dimensions and weights
            $boxes = [];
            if (isset($_POST['box_size']) && is_array($_POST['box_size'])) {
                foreach ($_POST['box_size'] as $boxNo => $boxSize) {
                    $boxes[$boxNo] = [
                        'box_no' => $boxNo,
                        'box_size' => $boxSize,
                        'box_length' => (float)($_POST['box_length'][$boxNo] ?? 0),
                        'box_width' => (float)($_POST['box_width'][$boxNo] ?? 0),
                        'box_height' => (float)($_POST['box_height'][$boxNo] ?? 0),
                        'box_weight' => (float)($_POST['box_weight'][$boxNo] ?? 0),
                        'items' => $_POST['box_items'][$boxNo] ?? [],
                        'order_numbers' => $_POST['order_numbers'][$boxNo] ?? [],
                        'groupname' => $_POST['item_groupnames'][$boxNo][0] ?? '' // assuming all items in box have same groupname, take first one
                    ];
                }
            }
            $data['boxes'] = $boxes;
            
            // Validate required delivery fields  'shipment_type', 'exotic_gst_no'
            $requiredFields = ['delivery_partner', 'pickup_location'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $errorMsg = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                    //if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['status' => 'error', 'message' => $errorMsg]);
                    // } else {
                    //     header('Location: ' . base_url('?page=dispatch&action=create&status=error&message=' . urlencode($errorMsg)));
                    // }
                    exit();
                }
            }

            // Build Shiprocket payload per requested format
            $firm = $commanModel->getRecordById('firm_details', 1) ?? [];
            $address = $commanModel->getDispatchAddress($invoice['vp_order_info_id'] ?? 0) ?? ($invoice['address'] ?? []);

            // prepare order_items by mapping item ids from boxes to invoice items
            $invoiceItems = $invoiceModel->getInvoiceItems($invoice['id'] ?? $data['invoice_id']);
            $itemsMap = [];
            foreach ($invoiceItems as $it) {
                $itemsMap[$it['id']] = $it;
            }

            $shiprocketResponses = [];
            $dispatchRecords = [];
            // print_array($boxes);
            // print_array($data);exit;
            // Call Shiprocket API for each box
            foreach ($boxes as $boxNo => $box) {
                $orderItems = [];
                $subTotal = 0;
                $totalBillableWeight = 0;

                foreach ($box['items'] as $itemId) {
                    if (!isset($itemsMap[$itemId])) continue;
                    $it = $itemsMap[$itemId];
                    $units = isset($it['quantity']) ? (int)$it['quantity'] : 1;
                    $price = isset($it['unit_price']) ? (float)$it['unit_price'] : (isset($it['selling_price']) ? (float)$it['selling_price'] : 0);
                    $rawHsn = $it['hsn'] ?? '';
                    $hsnVal = '';
                    if ($rawHsn !== '') {
                        if (strpos($rawHsn, '.') !== false) {
                            $hsnVal = explode('.', $rawHsn)[0];
                        } else {
                            $hsnDigits = preg_replace('/\D/', '', $rawHsn);
                            $hsnVal = $hsnDigits;
                        }
                        $hsnVal = substr(preg_replace('/\D/','', $hsnVal), 0, 4);
                    }
                    $orderItems[] = [
                        'name' => $it['groupname'] ?? $it['sku'] ?? 'Item',
                        'sku' => $it['item_code'] ?? '',
                        'units' => $units,
                        'selling_price' => $price,
                        'discount' => $it['discount'] ?? '',
                        'tax' => $it['tax_amount'] ?? '',
                        'hsn' => $hsnVal
                    ];
                    $subTotal += $units * $price;
                }

                // Get billable weight from POST data (item_billable_weights array)
                $billableWeights = $_POST['item_billable_weights'][$boxNo] ?? [];
                foreach ($billableWeights as $weight) {
                    $totalBillableWeight += (float)$weight;
                }
                // item_shipping_charges
                $shippingCharges = $_POST['item_shipping_charges'][$boxNo] ?? [];
                $totalShippingCharges = array_sum($shippingCharges);
                // Get order number for this box (first order number from order_numbers array)
                $invOrderNumber = null;
                if (!empty($box['order_numbers'])) {
                    $invOrderNumber = is_array($box['order_numbers']) ? (array_values($box['order_numbers'])[0] ?? null) : $box['order_numbers'];
                }
                $orderNumber = $invOrderNumber ? ($invOrderNumber . '_box_' . $boxNo) : ('order_' . $data['invoice_id'] . '_box' . $boxNo);
               

                $shiprocketPayload = [
                    'order_id' => $orderNumber,
                    'order_date' => date('Y-m-d H:i'),
                    'pickup_location' => $data['pickup_location'] ?? '',
                    'comment' => 'Box ' . $boxNo . ' | seller: ' . ($data['delivery_partner'] ?? 'Exotic India') . ', type: ' . ($data['shipment_type'] ?? 'Standard'),
                    'billing_customer_name' => $address['first_name'] ?? '',
                    'billing_last_name' => $address['last_name'] ?? '',
                    'billing_address' => $address['address_line1'] ?? '',
                    'billing_address_2' => $address['address_line2'] ?? '',
                    'billing_city' => $address['city'] ?? '',
                    'billing_state' => $address['state'] ?? '',
                    'billing_country' => $address['country'] ?? '',
                    'billing_pincode' => $address['zipcode'] ?? '',
                    'billing_email' => $address['email'] ?? '',
                    'billing_phone' => $address['mobile'] ?? '',
                    'shipping_is_billing' => $address['shipping_first_name'] ? false : true,
                    'shipping_customer_name' => $address['shipping_first_name'] ?? '',
                    'shipping_last_name' => $address['shipping_last_name'] ?? '',
                    'shipping_address' => $address['shipping_address_line1'] ?? '',
                    'shipping_address_2' => $address['shipping_address_line2'] ?? '',
                    'shipping_city' => $address['shipping_city'] ?? '',
                    'shipping_pincode' => $address['shipping_zipcode'] ?? '',
                    'shipping_country' => $address['shipping_country'] ?? '',
                    'shipping_state' => $address['shipping_state'] ?? '',
                    'shipping_email' => $address['shipping_email'] ?? '',
                    'shipping_phone' => $address['shipping_mobile'] ?? '',
                    'customer_gst_no' => $address['gst_number'] ?? '',
                    'order_items' => $orderItems,
                    'payment_method' => strtoupper($invoice['payment_method'] ?? 'Prepaid'),
                    'shipping_charges' => $totalShippingCharges,
                    'giftwrap_charges' => 0,
                    'transaction_charges' => 0,
                    'total_discount' => 0,
                    'sub_total' => $subTotal,
                    'length' => $box['box_length'],
                    'breadth' => $box['box_width'],
                    'height' => $box['box_height'],
                    'weight' => $totalBillableWeight > 0 ? $totalBillableWeight : $box['box_weight']
                ];

                // Call Shiprocket API
                $shiprocketResponse = $dispatchModel->shiprocketCreateShipment($shiprocketPayload);
                //save response in log file for debugging
                
                //file_put_contents('shiprocket_response_log.txt', date('Y-m-d H:i:s') . " - Box $boxNo - Payload: " . json_encode($shiprocketPayload) . " - Response: " . json_encode($shiprocketResponse) . "\n", FILE_APPEND);
                //chmod('shiprocket_response_log.txt', 0666); // make log file writable
                //print_array($shiprocketResponse);
                //exit;
                // Validate API response
                if($shiprocketResponse['json']['status'] != 'NEW') {
                    $errorMsg = $shiprocketResponse['json']['status'] ?? 'Failed to create shipment for Box ' . $boxNo . ' on Shiprocket';
                    //if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['status' => 'error', 'message' => $errorMsg]);
                    // } else {
                    //     header('Location: ' . base_url('?page=dispatch&action=create&invoice_id=' . $data['invoice_id'] . '&status=error&message=' . urlencode($errorMsg)));
                    // }
                    exit();

                }
                if (!$shiprocketResponse || !isset($shiprocketResponse['json']['order_id'])) {
                    $errorMsg = $shiprocketResponse['error'] ?? 'Failed to create shipment for Box ' . $boxNo . ' on Shiprocket';
                    //if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['status' => 'error', 'message' => $errorMsg]);
                    // } else {
                    //     header('Location: ' . base_url('?page=dispatch&action=create&invoice_id=' . $data['invoice_id'] . '&status=error&message=' . urlencode($errorMsg)));
                    // }
                    exit();
                }
                
                $shiprocketResponses[$boxNo] = $shiprocketResponse['json'];

                // Create dispatch record for this box
                $dispatchData = [
                    'invoice_id' => $data['invoice_id'],
                    'box_no' => $boxNo,
                    'order_number' => $invOrderNumber,                    
                    'pickup_location' => $data['pickup_location'],
                    'box_items' => implode(',', $box['items']),
                    'length' => $shiprocketPayload['length'],
                    'width' => $shiprocketPayload['breadth'],
                    'height' => $shiprocketPayload['height'],
                    'weight' => $shiprocketPayload['weight'],
                    'volumetric_weight' => ($shiprocketPayload['length'] * $shiprocketPayload['breadth'] * $shiprocketPayload['height']) / 5000,
                    'billing_weight' => $totalBillableWeight,
                    'shipping_charges' => $totalShippingCharges,
                    'dispatch_date' => date('Y-m-d H:i:s'),
                    'courier_name' => $data['delivery_partner'],
                    'shiprocket_order_id' => $shiprocketResponse['json']['order_id'] ?? null,
                    'shiprocket_shipment_id' => $shiprocketResponse['json']['shipment_id'] ?? null,
                    'shiprocket_tracking_url' => $shiprocketResponse['json']['tracking_url'] ?? null,                    
                    'awb_code' => $shiprocketResponse['json']['awb_code'] ?? null,
                    'shipment_status' => $shiprocketResponse['json']['status'] ?? null,
                    'label_url' => $shiprocketResponse['json']['label_url'] ?? null,
                    'groupname' => $box['groupname'] ?? null,
                    'created_by' => $_SESSION['user']['id'] ?? 0,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $dispatchId = $dispatchModel->createDispatch($dispatchData);
                //print_array($dispatchData);
                //echo "Dispatch ID: $dispatchId";exit;

                //awb api call getShiprocketAwbInfo
                $awbInfoResponse = $dispatchModel->getShiprocketAwbInfo($shiprocketResponse['json']['shipment_id']);
                //file_put_contents('shiprocket_awb_response_log.txt', date('Y-m-d H:i:s') . " - Box $boxNo - Shipment ID: " . $shiprocketResponse['json']['shipment_id'] . " - AWB Info Response: " . json_encode($awbInfoResponse) . "\n", FILE_APPEND);
                //chmod('shiprocket_awb_response_log.txt', 0666); // make log file writable
                $dispatchRecords['awb_assign_status'][$boxNo] = $awbInfoResponse['awb_assign_status'] ?? null;
                if($awbInfoResponse && isset($awbInfoResponse['awb_assign_status']) && $awbInfoResponse['awb_assign_status'] == 1) {
                    // Update dispatch record with AWB code
                    $awbCode = $awbInfoResponse['response']['data']['awb_code'];
                    $dispatchModel->updateDispatchAwbCode($shiprocketResponse['json']['shipment_id'], $awbCode);
                    $dispatchRecords['awb'][$boxNo] = $awbCode;
                } else {                   
                    //file_put_contents('shiprocket_awb_response_log.txt', date('Y-m-d H:i:s') . " - Box $boxNo - Shipment ID: " . $shiprocketResponse['json']['shipment_id'] . " - AWB code not found in response\n", FILE_APPEND);
                    //chmod('shiprocket_awb_response_log.txt', 0666); // make log file writable
                }
                //label api call getShiprocketLabelInfo
                $labelInfoResponse = $dispatchModel->getShiprocketLabels($shiprocketResponse['json']['shipment_id']);
                $dispatchRecords['label_created'][$boxNo] = $labelInfoResponse['label_created'] ?? null;
                //file_put_contents('shiprocket_label_response_log.txt', date('Y-m-d H:i:s') . " - Box $boxNo - Shipment ID: " . $shiprocketResponse['json']['shipment_id'] . " - Label Info Response: " . json_encode($labelInfoResponse) . "\n", FILE_APPEND);
                //chmod('shiprocket_label_response_log.txt', 0666); // make log file writable
                $lableAdd = false;
                if($labelInfoResponse && $labelInfoResponse['label_created'] == 1) {
                    // Update dispatch record with label URL
                    $labelUrl = $labelInfoResponse['label_url'];
                    $lableAdd = $dispatchModel->updateDispatchLabelUrl($shiprocketResponse['json']['shipment_id'], $labelUrl);
                    $dispatchRecords['labelUrl'][$boxNo] = $labelUrl;
                } else {
                    //file_put_contents('shiprocket_label_response_log.txt', date('Y-m-d H:i:s') . " - Box $boxNo - Shipment ID: " . $shiprocketResponse['json']['shipment_id'] . " - Label URL ***not found*** in response\n", FILE_APPEND);
                    //chmod('shiprocket_label_response_log.txt', 0666); // make log file writable
                }
                //echo "Label URL update status for Box $boxNo: " . ($lableAdd ? 'Success' : 'Failed') . "\n";
                
                if (!$dispatchId) {
                    $errorMsg = 'Failed to save dispatch record for Box ' . $boxNo;
                    //if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['status' => 'error', 'message' => $errorMsg,'labelUrl' => $shiprocketResponse['json']['label_url'] ?? '','shipmentId' => $shiprocketResponse['json']['shipment_id'] ?? '','labelUpdateStatus' => $lableAdd,'awb' => $shiprocketResponse['json']['awb_code'] ?? '']);
                    // } else {
                    //     header('Location: ' . base_url('?page=dispatch&action=create&status=error&message=' . urlencode($errorMsg)));
                    // }
                    exit();
                }
                $dispatchRecords['ids'][$boxNo] = $dispatchId;
                //update invoice with dispatch status
                //$invoiceModel->updateInvoiceDispatchStatus($data['invoice_id'], 'Dispatched');
                //update orders table with dispatch status using order numbers from this box
                            
                
            }
            
            // All boxes processed successfully
            //if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Dispatch created successfully!',
                    'dispatches' => $dispatchRecords,
                    'invoice_id' => $data['invoice_id']                    
                ]);
            // } else {
            //     header('Location: ' . base_url('?page=dispatch&action=create&status=success&dispatch_ids=' . implode(',', $dispatchRecords['ids']) . '&invoice_id=' . $data['invoice_id']));
            // }
            exit();
        } else {
            // Get list of invoices for dropdown
            $invoice_id = $_GET['invoice_id'] ?? null;
            $invoices = [];
            if ($invoice_id) {
                $invoice = $invoiceModel->getInvoiceById($invoice_id);
                $invoice['items'] = $invoiceModel->getInvoiceItems($invoice_id);
                $invoice['firm_details'] = $commanModel->getRecordById('firm_details', 1);
                $invoice['exotic_address'] = $commanModel->get_exotic_address();
                $pickup = $dispatchModel->pickupLocations();
                $invoice['pickup_locations'] = $pickup['data']['shipping_address'] ?? [];
                if ($invoice && isset($invoice['vp_order_info_id'])) {
                    $invoice['address'] = $commanModel->getDispatchAddress($invoice['vp_order_info_id']);
                }                
                $invoices[] = $invoice;
                //fetch dispatch records for this invoice   
                $dispatchRecords = $dispatchModel->getDispatchRecordsByInvoiceId($invoice_id);
                //print_array($dispatchRecords);
            }
            renderTemplate('views/dispatch/create.php', ['invoices' => $invoices, 'dispatchRecords' => $dispatchRecords]);
        }
    }
    public function retryInvoice() {
        global $dispatchModel;
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            exit();
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $invoiceId = $input['invoice_id'] ?? null;
        if (!$invoiceId) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invoice ID is required']);
            exit();
        }

        // Get all dispatch records for this invoice
        $records = $dispatchModel->getDispatchRecordsByInvoiceId($invoiceId);
        if (empty($records)) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'No dispatch records found for this invoice']);
            exit();
        }

        $retried = 0;
        $failed = 0;
        $errors = [];
        $results = [];
        foreach ($records as $record) {
            // Only retry if awb_code is missing
            if (empty($record['awb_code'])) {
                $result = $dispatchModel->retryShiprocketApiCalls($record['id']);
                $results[] = $result;
                if ($result && isset($result['success']) && $result['success']) {
                    $retried++;
                } else {
                    $failed++;
                    $errors[] = 'Dispatch ID ' . $record['id'] . ': ' . ($result['message'] ?? 'Unknown error');
                }
            }
        }
        //update dispatch status
        foreach ($records as $record) {
            if (isset($record['awb_code']) && !empty($record['awb_code'])) {
                $dispatchModel->updateDispatchStatus($record['id'], 'Dispatched');
            }else {
                $dispatchModel->updateDispatchStatus($record['id'], 'Dispatch Failed');
            }
        }

        if ($retried > 0) {
            echo json_encode([
                'success' => true,
                'message' => "Retried $retried dispatch(es)" . ($failed > 0 ? " ($failed failed)" : ''),
                'retried' => $retried,
                'failed' => $failed,
                'errors' => $errors,
                'results' => $results
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No retries needed or all retries failed', 'errors' => $errors]);
        }
        exit();
    }

    public function retryDispatch() {
        global $dispatchModel;
        //print_array($_POST);
        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dispatchId = $input['dispatch_id'] ?? null;
            if (!$dispatchId) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Dispatch ID is required']);
                exit();
            }
            $result = $dispatchModel->retryShiprocketApiCalls($dispatchId);
                // if ($result['success']) {
                //     header('Content-Type: application/json');
                //     echo json_encode(['status' => 'success', 'message' => 'Retry successful', 'data' => $result['data']]);
                // } else {
                //     header('Content-Type: application/json');
                //     echo json_encode(['status' => 'error', 'message' => $result['message']]);
            // }
             header('Content-Type: application/json');
             echo json_encode($result);
            exit();
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            exit();
        }
    }
    public function mergeLabels() {
        // Merge PDF labels for selected invoices and stream result
        global $dispatchModel;
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            exit();
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $invoiceIds = $input['invoice_ids'] ?? [];
        if (!is_array($invoiceIds) || empty($invoiceIds)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'No invoices selected']);
            exit();
        }

        // gather label URLs from dispatch records
        $labelUrls = [];
        foreach ($invoiceIds as $invId) {
            $records = $dispatchModel->getDispatchRecordsByInvoiceId($invId);
            if (!empty($records)) {
                foreach ($records as $rec) {
                    if (!empty($rec['label_url'])) {
                        $labelUrls[] = $rec['label_url'];
                    }
                }
            }
        }

        if (empty($labelUrls)) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'No labels found for selected invoices']);
            exit();
        }

        // download each PDF to temp directory
        $tempDir = sys_get_temp_dir() . '/pdf_merge_' . uniqid();
        mkdir($tempDir, 0755, true);
        register_shutdown_function(function() use ($tempDir) {
            if (is_dir($tempDir)) {
                $files = array_diff(scandir($tempDir), ['.', '..']);
                foreach ($files as $f) {
                    @unlink($tempDir . '/' . $f);
                }
                @rmdir($tempDir);
            }
        });

        $downloaded = [];
        foreach ($labelUrls as $i => $url) {
            $file = $tempDir . '/' . sprintf('%05d', $i) . '.pdf';
            // basic download
            $content = @file_get_contents($url);
            if ($content && strlen($content) > 100) {
                file_put_contents($file, $content);
                $downloaded[] = $file;
            }
        }
        if (empty($downloaded)) {
            http_response_code(502);
            echo json_encode(['status' => 'error', 'message' => 'Failed to download any label PDFs']);
            exit();
        }

        // merge using FPDI
        require_once 'vendor/autoload.php';
        try {
            $pdf = new \setasign\Fpdi\Fpdi();

            foreach ($downloaded as $file) {
                $pageCount = $pdf->setSourceFile($file);
                for ($page = 1; $page <= $pageCount; $page++) {
                    $tpl = $pdf->importPage($page);
                    $size = $pdf->getTemplateSize($tpl);
                    $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                    $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                    $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);
                }
            }

            // stream PDF to browser
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename=merged_labels.pdf');
            $pdf->Output('I');
            exit();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Error merging PDFs: ' . $e->getMessage()]);
            exit();
        }
    }

    // new bulk print labels action
    public function bulkPrintLabels() {
        global $dispatchModel;
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit();
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $ids = $input['dispatch_ids'] ?? [];
        if (!is_array($ids) || empty($ids)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No dispatch records selected']);
            exit();
        }

        // collect label URLs for the given dispatch ids
        $records = $dispatchModel->getDispatchRecordsByIds($ids);
        $labelUrls = [];
        if (!empty($records)) {
            foreach ($records as $rec) {
                if (!empty($rec['label_url'])) {
                    $labelUrls[] = $rec['label_url'];
                }
            }
        }

        if (empty($labelUrls)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'No labels found for selected dispatches']);
            exit();
        }

        // download and merge similar to mergeLabels, then save merged file to tmp directory accessible by web
        $tempDir = __DIR__ . '/../tmp';
        if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);
        $outFile = $tempDir . '/merged_labels_' . uniqid() . '.pdf';

        require_once 'vendor/autoload.php';
        try {
            $pdf = new \setasign\Fpdi\Fpdi();
            foreach ($labelUrls as $url) {
                $content = @file_get_contents($url);
                if ($content && strlen($content) > 100) {
                    $tmp = tempnam(sys_get_temp_dir(), 'lbl');
                    file_put_contents($tmp, $content);
                    $pageCount = $pdf->setSourceFile($tmp);
                    for ($page = 1; $page <= $pageCount; $page++) {
                        $tpl = $pdf->importPage($page);
                        $size = $pdf->getTemplateSize($tpl);
                        $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                        $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                        $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);
                    }
                    @unlink($tmp);
                }
            }
            $pdf->Output('F', $outFile);
            $publicUrl = base_url('tmp/' . basename($outFile));
            echo json_encode(['success' => true, 'label_url' => $publicUrl]);
            exit();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error merging PDFs: ' . $e->getMessage()]);
            exit();
        }
    }
    
    public function bulkUpdateStatus() {
        global $dispatchModel;
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            exit();
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $invoiceIds = $input['invoice_ids'] ?? [];
        if (!is_array($invoiceIds) || empty($invoiceIds)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'No invoices selected']);
            exit();
        }

        $summary = [
            'processed_invoices' => 0,
            'processed_dispatches' => 0,
            'updated' => 0,
            'errors' => [],
            'details' => []
        ];

        foreach ($invoiceIds as $invId) {
            $summary['processed_invoices']++;
            $records = $dispatchModel->getDispatchRecordsByInvoiceId($invId);
            if (empty($records)) {
                $summary['errors'][] = "No dispatch records for invoice $invId";
                continue;
            }
            foreach ($records as $rec) {
                $summary['processed_dispatches']++;
                $dispatchId = $rec['id'];
                $awb = $rec['awb_code'] ?? '';
                if (empty($awb)) {
                    $summary['details'][$dispatchId] = ['status' => 'no_awb'];
                    continue;
                }

                $resp = $dispatchModel->getShiprocketTrackingByAWB($awb);
               
                if (empty($resp)) {
                    $summary['errors'][] = "Empty response for AWB $awb (dispatch $dispatchId)";
                    $summary['details'][$dispatchId] = ['success' => false, 'error' => 'empty_response'];
                    continue;
                }

                // best-effort extraction of status and tracking URL from response
                $status = null;
                $tracking_url = null;
                if (isset($resp['tracking_data']['shipment_track']) && is_array($resp['tracking_data']['shipment_track'])) {
                    foreach ($resp['tracking_data']['shipment_track'] as $track) {
                        $edd = $track['edd'] ?? null;
                        if (isset($track['current_status'])) {
                            $status = $track['current_status'];
                            break;
                        }
                    }
                }
                $tracking_url = $resp['tracking_data']['track_url'] ?? null;
                //echo "Extracted status: $status, tracking_url: $tracking_url for AWB $awb (dispatch $dispatchId)\n";
                $etd = $resp['tracking_data']['etd'] ?? null;
                
                if ($status !== null || $tracking_url !== null) {
                    $updated = $dispatchModel->updateDispatchStatus($dispatchId, $status ?? '', $tracking_url, $etd, $edd);
                    if ($updated) {
                        $summary['updated']++;
                        $summary['details'][$dispatchId] = ['success' => true, 'status' => $status, 'tracking_url' => $tracking_url, 'etd' => $etd, 'edd' => $edd];
                    } else {
                        $summary['details'][$dispatchId] = ['success' => false, 'error' => 'db_update_failed', 'status' => $status, 'tracking_url' => $tracking_url, 'etd' => $etd, 'edd' => $edd];
                        $summary['errors'][] = "DB update failed for dispatch $dispatchId";
                    }
                } else {
                    $summary['details'][$dispatchId] = ['success' => false, 'error' => 'no_status_in_response', 'raw' => $resp];
                    $summary['errors'][] = "No status in response for AWB $awb (dispatch $dispatchId)";
                }
            }
        }

        echo json_encode(['status' => 'success', 'summary' => $summary]);
        exit();
    }

    public function index() {
        global $dispatchModel;
        global $invoiceModel;
        global $commanModel;
        $invoice_dispatch = [];
        // Pagination params
        $page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
        $perPage = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 10;
        $offset = ($page - 1) * $perPage;
        $filters = [];
        
        // Filter by date range
        if (!empty($_GET['date_range'])) {
            $dateRange = explode(' to ', $_GET['date_range']);
            if (count($dateRange) == 2) {
                $filters['start_date'] = trim($dateRange[0]);
                $filters['end_date'] = trim($dateRange[1]);
            }
        }

        // Filter by AWB number
        if (!empty($_GET['awb_number'])) {
            $filters['awb_number'] = $_GET['awb_number'];
        }

        // Filter by order number
        if (!empty($_GET['order_number'])) {
            $filters['order_number'] = $_GET['order_number'];
        }

        // Filter by invoice number
        if (!empty($_GET['invoice_number'])) {
            $filters['invoice_number'] = $_GET['invoice_number'];
        }

        // Filter by customer contact
        if (!empty($_GET['customer_contact'])) {
            $filters['customer_contact'] = $_GET['customer_contact'];
        }

        // Filter by payment mode
        if (!empty($_GET['payment_mode'])) {
            $filters['payment_mode'] = $_GET['payment_mode'];
        }

        // Filter by status
        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        //dispatch table filter box size
        if (!empty($_GET['box_size'])) {
            $filters['box_size'] = $_GET['box_size'];
        }
        //dispatch table filter category        
        if (!empty($_GET['category'])) {
            $filters['category'] = $_GET['category'];
        }
        //dispatch table filter invoice value
        if (!empty($_GET['invoice_value_min'])) {
            $filters['invoice_value_min'] = floatval($_GET['invoice_value_min']);
        }
        if (!empty($_GET['invoice_value_max'])) {
            $filters['invoice_value_max'] = floatval($_GET['invoice_value_max']);
        }
        if (!empty($_GET['item_code'])) {
            $filters['item_code'] = $_GET['item_code'];
        }
        if (!empty($_GET['created_by'])) {
            $filters['created_by'] = intval($_GET['created_by']);
        }
        if (!empty($_GET['batch_no'])) {
            $filters['batch_no'] = $_GET['batch_no'];

        }
        //Box Weight
        if (!empty($_GET['box_weight_min'])) {
            $filters['box_weight_min'] = floatval($_GET['box_weight_min']);
        }
        if (!empty($_GET['box_weight_max'])) {
            $filters['box_weight_max'] = floatval($_GET['box_weight_max']);
        }


        // Sorting
        $sort = isset($_GET['sort']) && in_array(strtolower($_GET['sort']), ['asc', 'desc']) ? strtolower($_GET['sort']) : 'desc';
        if (!empty($_GET['sort']) && in_array(strtolower($_GET['sort']), ['asc', 'desc'])) {
            $filters['sort'] = strtolower($_GET['sort']);
        } else {
            $filters['sort'] = 'desc'; // Default sort order
        }

        // Get total count for pagination
        $totalInvoices = $invoiceModel->getInvoicesCount($filters);
        $totalPages = ceil($totalInvoices / $perPage);

        // Fetch paginated invoices
        $invoices = $invoiceModel->getAllInvoicesPaginated($perPage, $offset, $filters);
        //print_array($invoices);
        foreach ($invoices as &$invoice) {
            $invoice_dispatch[$invoice['id']] = $dispatchModel->getDispatchRecordsByInvoiceId($invoice['id']);
            //get items
            $invoice['items'] = $invoiceModel->getInvoiceItems($invoice['id']);
        }
        unset($invoice);
        renderTemplate('views/dispatch/index.php', [
            'invoice_dispatch' => $invoice_dispatch,
            'invoices' => $invoices,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'totalInvoices' => $totalInvoices,
            'staffList' => $commanModel->get_staff_list(),
            'country_list' => $commanModel->get_counry_list(),
            'warehouseList' => $commanModel->get_exotic_address()
        ]);
    }
    
    public function cancelDispatch() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['invoice_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing invoice_id']);
            return;
        }

        global $dispatchModel;
        global $commanModel;
        $invoiceId = intval($input['invoice_id']);
        //shipment id from dispatch record
        $dispatchRecords = $dispatchModel->getDispatchRecordsByInvoiceId($invoiceId);

        global $conn;
        try {
            foreach ($dispatchRecords as $record) {
                $shiprocketOrderId = $record['shiprocket_order_id'];
                if ($shiprocketOrderId) {
                    // Cancel shipment via Shiprocket API
                    $response = $dispatchModel->cancelShiprocketShipment($shiprocketOrderId);
                    //print_array($response);
                    $commanModel->updateRecord('vp_dispatch_details', ['shipment_status' => 'cancelled'], $record['id']);
                    if (!$response['success']) {
                        //throw new Exception("Failed to cancel shiprocket order ID: " . $shiprocketOrderId);
                        echo json_encode(['success' => false, 'message' => 'Failed to cancel shipment for dispatch ID ' . $record['id'] . ': ' . ($response['message'] ?? 'Unknown error')]);
                        exit();
                        //echo json_encode($response);
                        //continue; // skip to next record
                    }
                    // Update dispatch record to mark as cancelled
                    //$dispatchModel->updateDispatchStatus($record['id'], 'cancelled');
                    //$commanModel->updateRecord('vp_dispatch_details', ['shipment_status' => 'cancelled'], $record['id']);
                }
            }
            $stockModel = new Stock($conn);
            $stockRestore = $stockModel->restoreStockByInvoiceId($invoiceId);
            if (empty($stockRestore['success'])) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Dispatch updated but stock could not be restored: ' . ($stockRestore['message'] ?? 'unknown'),
                    'stock_restore' => $stockRestore,
                ]);
                return;
            }
            echo json_encode([
                'success' => true,
                'message' => 'Dispatch cancelled successfully',
                'stock_restore' => $stockRestore,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error cancelling dispatch: ' . $e->getMessage()]);
        }
    }
    public function getDispatchDetails() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        if (!isset($_GET['dispatch_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing dispatch_id']);
            return;
        }

        global $dispatchModel;
        $dispatchId = intval($_GET['dispatch_id']);
        $details = $dispatchModel->getDispatchDetailsById($dispatchId);
        if ($details) {
            echo json_encode(['success' => true, 'data' => $details]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Dispatch not found']);
        }
    }
    public function reDispatchInvoice() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['invoice_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing invoice_id']);
            return;
        }

        global $dispatchModel;
        global $commanModel;
        global $invoiceModel;
        $invoiceId = intval($input['invoice_id']);
        //shipment id from dispatch record
        $dispatchRecords = $dispatchModel->getDispatchRecordsByInvoiceId($invoiceId);

        // re-dispatch logic: for each cancelled dispatch, attempt to create a new shipment with same details
        $results = [];
        foreach ($dispatchRecords as $record) {
            if ($record['shipment_status'] === 'cancelled' || $record['shipment_status'] === 'Cancellation Requested') {
                // re-dispatch payload: replicate same fields sent in create()
                // new order identifier so Shiprocket will accept it
                $newOrderId = $record['order_number'] ? ($record['order_number'] . '_re' . time()) : ('order_' . $record['invoice_id'] . '_box' . $record['box_no'] . '_re' . time());

                // fetch invoice & address so we can populate billing/shipping info
                $invoice = $invoiceModel->getInvoiceById($record['invoice_id']);
                $address = $commanModel->getDispatchAddress($invoice['vp_order_info_id'] ?? 0) ?? ($invoice['address'] ?? []);
                $subTotal = 0;
                // assemble order_items from stored box_items (comma list of item IDs)
                $orderItems = [];
                if (!empty($record['box_items'])) {
                    $boxItems = explode(',', $record['box_items']);
                    $invoiceItems = $invoiceModel->getInvoiceItems($invoice['id'] ?? $record['invoice_id']);
                    $itemsMap = [];
                    foreach ($invoiceItems as $it) {
                        $itemsMap[$it['id']] = $it;
                    }
                    foreach ($boxItems as $itm) {
                        if (!isset($itemsMap[$itm])) continue;
                        $it = $itemsMap[$itm];
                        $units = isset($it['quantity']) ? (int)$it['quantity'] : 1;
                        $price = isset($it['unit_price']) ? (float)$it['unit_price'] : (isset($it['selling_price']) ? (float)$it['selling_price'] : 0);
                        $rawHsn = $it['hsn'] ?? '';
                        $hsnVal = '';
                        if ($rawHsn !== '') {
                            if (strpos($rawHsn, '.') !== false) {
                                $hsnVal = explode('.', $rawHsn)[0];
                            } else {
                                $hsnDigits = preg_replace('/\D/', '', $rawHsn);
                                $hsnVal = $hsnDigits;
                            }
                            $hsnVal = substr(preg_replace('/\D/','', $hsnVal), 0, 4);
                        }
                        $orderItems[] = [
                            'name' => $it['groupname'] ?? $it['sku'] ?? 'Item',
                            'sku' => $it['item_code'] ?? '',
                            'units' => $units,
                            'selling_price' => $price,
                            'discount' => $it['discount'] ?? '',
                            'tax' => $it['tax_amount'] ?? '',
                            'hsn' => $hsnVal
                        ];
                        $subTotal += $units * $price;
                    }
                }

                $shiprocketPayload = [
                    'order_id' => $newOrderId,
                    'order_date' => date('Y-m-d H:i'),
                    'pickup_location' => $record['pickup_location'] ?? '',
                    'comment' => 'Re-dispatch of box ' . $record['box_no'],
                    'billing_customer_name' => $address['first_name'] ?? '',
                    'billing_last_name' => $address['last_name'] ?? '',
                    'billing_address' => $address['address_line1'] ?? '',
                    'billing_address_2' => $address['address_line2'] ?? '',
                    'billing_city' => $address['city'] ?? '',
                    'billing_state' => $address['state'] ?? '',
                    'billing_country' => $address['country'] ?? '',
                    'billing_pincode' => $address['zipcode'] ?? '',
                    'billing_email' => $address['email'] ?? '',
                    'billing_phone' => $address['mobile'] ?? '',
                    'shipping_is_billing' => $address['shipping_first_name'] ? false : true,
                    'shipping_customer_name' => $address['shipping_first_name'] ?? '',
                    'shipping_last_name' => $address['shipping_last_name'] ?? '',
                    'shipping_address' => $address['shipping_address_line1'] ?? '',
                    'shipping_address_2' => $address['shipping_address_line2'] ?? '',
                    'shipping_city' => $address['shipping_city'] ?? '',
                    'shipping_pincode' => $address['shipping_zipcode'] ?? '',
                    'shipping_country' => $address['shipping_country'] ?? '',
                    'shipping_state' => $address['shipping_state'] ?? '',
                    'shipping_email' => $address['shipping_email'] ?? '',
                    'shipping_phone' => $address['shipping_mobile'] ?? '',
                    'customer_gst_no' => $address['gst_number'] ?? '',
                    'order_items' => $orderItems,
                    'payment_method' => strtoupper($invoice['payment_method'] ?? 'Prepaid'),
                    'shipping_charges' => $record['shipping_charges'] ?? 0,
                    'giftwrap_charges' => 0,
                    'transaction_charges' => 0,
                    'total_discount' => 0,
                    'sub_total' => $subTotal,
                    'length' => $record['length'] ?? 0,
                    'breadth' => $record['width'] ?? 0,
                    'height' => $record['height'] ?? 0,
                    'weight' => $record['billing_weight'] ?? $record['weight'] ?? 0
                ];

                // call shiprocket just like in create()
                $reDispatchResult = $dispatchModel->shiprocketCreateShipment($shiprocketPayload);
                //print_array($reDispatchResult);
                // validate response
                if (!$reDispatchResult || !isset($reDispatchResult['json']['order_id']) || ($reDispatchResult['json']['status'] ?? '') !== 'NEW') {
                    $msg = $reDispatchResult['json']['status'] ?? $reDispatchResult['error'] ?? 'Failed to create re-dispatch shipment';
                    $results[] = ['dispatch_id' => $record['id'], 'success' => false, 'message' => $msg];
                } else {
                    // update record with details from new shipment
                    $updateDis = $commanModel->updateRecord('vp_dispatch_details', [
                        'shipment_status' => 're-dispatched',
                        'shiprocket_order_id' => $reDispatchResult['json']['order_id'],                       
                        'shiprocket_shipment_id' => $reDispatchResult['json']['shipment_id'] ?? '',
                        'awb_code' => $reDispatchResult['json']['awb_code'] ?? '',
                        'order_number' => $newOrderId,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'is_re_dispatch' => 1,
                        're_dispatch_count' => $record['re_dispatch_count'] + 1,
                        'label_url' => null,
                        'etd' => null,
                        'edd' => null,
                        'shiprocket_tracking_url' => null

                    ], $record['id']);
                    //create new dispatch record with same details but new shipment info
                    // $dispatchData = [
                    //     'invoice_id' => $record['invoice_id'],
                    //     'box_no' => $record['box_no'],
                    //     'order_number' => $newOrderId,                    
                    //     'pickup_location' => $record['pickup_location'],
                    //     'box_items' => $record['box_items'],
                    //     'length' => $record['length'],
                    //     'width' => $record['width'],
                    //     'height' => $record['height'],
                    //     'weight' => $record['weight'],
                    //     'shipping_charges' => $record['shipping_charges'],
                    //     'volumetric_weight' => ($record['length'] * $record['width'] * $record['height']) / 5000,
                    //     'billing_weight' => $record['billing_weight'] ?? $record['weight'] ?? 0,
                    //     'shipping_charges' => $record['shipping_charges'] ?? 0,
                    //     'dispatch_date' => date('Y-m-d H:i:s'),
                    //     'courier_name' => $record['courier_name'] ?? null,
                    //     'shiprocket_order_id' => $reDispatchResult['json']['order_id'] ?? null,
                    //     'shiprocket_shipment_id' => $reDispatchResult['json']['shipment_id'] ?? null,
                    //     'shiprocket_tracking_url' => $reDispatchResult['json']['tracking_url'] ?? null,                    
                    //     'awb_code' => $reDispatchResult['json']['awb_code'] ?? null,
                    //     'shipment_status' => $reDispatchResult['json']['status'] ?? null,
                    //     'label_url' => $reDispatchResult['json']['label_url'] ?? null,
                    //     'groupname' => $record['groupname'] ?? null,
                    //     'created_by' => $_SESSION['user']['id'] ?? 0,
                    //     'created_at' => date('Y-m-d H:i:s'),
                        
                    // ];
                    
                    // $dispatchId = $dispatchModel->createDispatch($dispatchData);
                    $results[] = ['dispatch' => $updateDis, 'success' => true, 'message' => 'Re-dispatch successful', 'new_shiprocket_order_id' => $reDispatchResult['json']['order_id']];
                }
            }
        }
        echo json_encode(['success' => true, 'results' => $results]);
    }
    public function cancelInvoice() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['invoice_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing invoice_id']);
            return;
        }

        global $dispatchModel;
        global $commanModel;
        global $invoiceModel;
        global $conn;
        $invoiceId = intval($input['invoice_id']);
        $invoice = $invoiceModel->getInvoiceById($invoiceId);
        if (!$invoice) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Invoice not found']);
            return;
        }
        $invStatus = strtolower(trim((string)($invoice['status'] ?? '')));
        if ($invStatus === 'cancelled') {
            echo json_encode(['success' => true, 'message' => 'Invoice already cancelled']);
            return;
        }
        //shipment id from dispatch record
        $dispatchRecords = $dispatchModel->getDispatchRecordsByInvoiceId($invoiceId);

        try {
            foreach ($dispatchRecords as $record) {
                $shiprocketOrderId = $record['shiprocket_order_id'];
                if ($shiprocketOrderId) {
                    // Cancel shipment via Shiprocket API
                    $response = $dispatchModel->cancelShiprocketShipment($shiprocketOrderId);
                    //print_array($response);
                    if (!$response['success']) {
                        //throw new Exception("Failed to cancel shiprocket order ID: " . $shiprocketOrderId);
                        echo json_encode(['success' => false, 'message' => 'Failed to cancel shipment for dispatch ID ' . $record['id'] . ': ' . ($response['message'] ?? 'Unknown error')]);
                        exit();
                        //echo json_encode($response);
                        //continue; // skip to next record
                    }
                    // Update dispatch record to mark as cancelled
                    //$dispatchModel->updateDispatchStatus($record['id'], 'cancelled');
                    $commanModel->updateRecord('vp_dispatch_details', ['shipment_status' => 'cancelled'], $record['id']);
                }
            }
            $stockModel = new Stock($conn);
            $stockRestore = $stockModel->restoreStockByInvoiceId($invoiceId);
            if (empty($stockRestore['success'])) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Shipment cancelled but stock could not be restored: ' . ($stockRestore['message'] ?? 'unknown'),
                    'stock_restore' => $stockRestore,
                ]);
                return;
            }
            //update invoice status to cancelled
            $invoiceModel->updateInvoiceStatus($invoiceId, 'cancelled');
            echo json_encode([
                'success' => true,
                'message' => 'Invoice cancelled successfully',
                'stock_restore' => $stockRestore,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error cancelling invoice: ' . $e->getMessage()]);
        }
    }
    public function bulkDispatch(){
        global $dispatchModel;
        global $invoiceModel;
        $invoice_dispatch = [];
        // $invoices = $invoiceModel->getAllInvoices(1000, 0, ['status' => 'Ready for Dispatch']);
        // foreach ($invoices as &$invoice) {
        //     $invoice_dispatch[$invoice['id']] = $dispatchModel->getDispatchRecordsByInvoiceId($invoice['id']);
        // }
        unset($invoice);
        renderTemplate('views/dispatch/bulk_dispatch.php', ['invoices' => [], 'dispatchRecords' => $invoice_dispatch]);
    }
   

    /**
     * Bulk create invoices and dispatch records from bulk dispatch form
     */
    public function bulkCreateInvoicesDispatch() {
        global $invoiceModel, $dispatchModel, $ordersModel, $commanModel;
        is_login();
        
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        if (empty($input['orders']) || !is_array($input['orders'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No orders provided']);
            exit;
        }
        //print_array($input);
        //exit;
        // Generate unique batch number
        $batch_no = 'BATCH-' . date('YmdHis') . '-' . mt_rand(10000, 99999);
        
        $created_invoices = [];
        $created_dispatches = [];
        $errors = [];
        
        try {
            // Process each order
            foreach ($input['orders'] as $orderData) {
                $order_number = $orderData['order_number'] ?? null;
                $customer_id = $orderData['customer_id'] ?? null;
                $customer_name = $orderData['customer_name'] ?? null;
                $boxes = $orderData['boxes'] ?? [];
                $order_ids = $orderData['order_ids'] ?? [];  // Specific order IDs to include

                if (!$order_number || !$customer_id) {
                    $errors[] = "Invalid order data: missing order number or customer id";
                    continue;
                }

                // Get order items from database
                // If specific order_ids are provided, fetch only those items
                // Otherwise, fetch all items for the order_number
                if (!empty($order_ids) && is_array($order_ids)) {
                    // Fetch specific order items by IDs
                    $orders = $ordersModel->getOrdersByIds($order_ids);
                    if (empty($orders)) {
                        $errors[] = "No items found for specified order IDs in order #$order_number";
                        continue;
                    }
                } else {
                    // Fallback: fetch all items for the order number
                    //$orders = $ordersModel->getOrderByOrderNumber($order_number);
                    if (empty($orders)) {
                        $errors[] = "Order #$order_number not found";
                        continue;
                    }
                }

                // Get vp_order_info for address (getDispatchAddress uses vp_order_info id, not vp_orders id)
                $orderInfo = $ordersModel->getRemarksByOrderNumber($order_number);
                $vp_order_info_id = ($orderInfo && isset($orderInfo['id'])) ? (int)$orderInfo['id'] : 0;

                // Calculate totals
                $subtotal = 0;
                $tax_amount = 0;
                $total_amount = 0;

                foreach ($orders as $item) {
                    $item_total = ($item['quantity'] ?? 0) * ($item['finalprice'] ?? 0);
                    $item_tax = ($item_total * ($item['gst'] ?? 0)) / 100;
                    
                    $subtotal += $item_total;
                    $tax_amount += $item_tax;
                    $total_amount += ($item_total + $item_tax);
                }

                // Create invoice
                // Generate invoice number from global_settings
                $globalSettings = $commanModel->getRecordById('global_settings', 1);
                $invoice_prefix = $globalSettings['invoice_prefix'] ?? 'INV';
                $invoice_series = $globalSettings['invoice_series'] ?? 0;
                $invoice_series++;
                
                // Update global_settings with new invoice_series
                $commanModel->updateRecord('global_settings', ['invoice_series' => $invoice_series], ['id' => 1]);
                
                $invoice_number = $invoice_prefix . '-' . str_pad($invoice_series, 6, '0', STR_PAD_LEFT);
                
                $invoiceData = [
                    'invoice_number' => $invoice_number,
                    'invoice_date' => date('Y-m-d'),
                    'customer_id' => $customer_id,
                    'vp_order_info_id' => $vp_order_info_id,
                    'currency' => 'INR',
                    'subtotal' => $subtotal,
                    'tax_amount' => $tax_amount,
                    'discount_amount' => 0,
                    'total_amount' => $total_amount,
                    'status' => 'final',
                    'created_by' => $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'exchange_text' => '',
                    'converted_amount' => 0,
                    'batch_no' => $batch_no
                ];

                $invoiceId = $invoiceModel->createInvoice($invoiceData);
                if (!$invoiceId) {
                    $errors[] = "Failed to create invoice for order #$order_number";
                    continue;
                }

                // Map items to box numbers based on $boxes
                $itemBoxMap = [];
                foreach ($boxes as $boxIndex => $boxData) {
                    $box_no = $boxIndex + 1;
                    $boxItems = $boxData['items'] ?? [];
                    foreach ($boxItems as $itemId) {
                        $itemBoxMap[$itemId] = $box_no;
                    }
                }

                // Create invoice items
                foreach ($orders as $order) {
                    $quantity = $order['quantity'] ?? 0;
                    $finalprice = $order['finalprice'] ?? 0;
                    $gst = $order['gst'] ?? 0;
                    $item_total = $quantity * $finalprice;
                    $tax_amount_item = ($item_total * $gst) / 100;

                    // Determine box_no from boxes mapping, default to 1 if not found
                    $item_box_no = isset($itemBoxMap[$order['id']]) ? $itemBoxMap[$order['id']] : 1;

                    $itemData = [
                        'invoice_id' => $invoiceId,
                        'order_number' => $order_number,
                        'item_code' => $order['item_code'] ?? '',
                        'hsn' => $order['hsn_code'] ?? '',
                        'item_name' => $order['title'] ?? 'Product',
                        'description' => '',
                        'box_no' => $item_box_no,
                        'quantity' => $quantity,
                        'unit_price' => $finalprice,
                        'tax_rate' => $gst,
                        'cgst' => ($item_total * ($gst / 2)) / 100,
                        'sgst' => ($item_total * ($gst / 2)) / 100,
                        'igst' => 0,
                        'tax_amount' => $tax_amount_item,
                        'line_total' => $item_total + $tax_amount_item,
                        'image_url' => '',
                        'groupname' => $order['groupname'] ?? ''
                    ];

                    $invoiceModel->createInvoiceItem($itemData);
                }

                $created_invoices[] = [
                    'invoice_id' => $invoiceId,
                    'invoice_number' => $invoice_number,
                    'order_number' => $order_number,
                    'customer_id' => $customer_id,
                    'customer_name' => $customer_name,
                    'total_amount' => $total_amount
                ];
                
                // Update order status to invoiced
                foreach ($orders as $item) {  
                    $ordersModel->updateOrderById($item['id'], ['invoice_id' => $invoiceId]);
                }    
                //$ordersModel->updateOrderByOrderNumber($order_number, ['invoice_id' => $invoiceId]);
                       
                // Create dispatch records for each box
                foreach ($boxes as $boxIndex => $boxData) {
                    $box_no = $boxIndex + 1;
                    $weight = floatval($boxData['weight'] ?? 0);
                    $box_size = $boxData['box_size'] ?? 'R-1';
                    $items = $boxData['items'] ?? [];
                    $box_size_mapping = [
                        'R-1' => ['length' => 22, 'width' => 17, 'height' => 5],
                        'R-2' => ['length' => 16, 'width' => 13, 'height' => 13],
                        'R-3' => ['length' => 16, 'width' => 11, 'height' => 7],
                        'R-4' => ['length' => 13, 'width' => 10, 'height' => 7],
                        'R-5' => ['length' => 21, 'width' => 11, 'height' => 7],
                        'R-6' => ['length' => 11, 'width' => 10, 'height' => 8],
                        'R-7' => ['length' => 8, 'width' => 6, 'height' => 5],
                        'R-8' => ['length' => 12, 'width' => 12, 'height' => 1.5],
                        'R-9' => ['length' => 17, 'width' => 12, 'height' => 2],
                        'R-10' => ['length' => 12, 'width' => 9, 'height' => 2],
                        'R-11' => ['length' => 10, 'width' => 10, 'height' => 2],
                        'R-12' => ['length' => 13, 'width' => 9, 'height' => 5],
                        'R-13' => ['length' => 11, 'width' => 8, 'height' => 5],
                        'R-14' => ['length' => 14, 'width' => 12, 'height' => 10]
                    ];
                       
                    // Get dimensions from mapping or custom values
                    if ($box_size === 'CUSTOM' || isset($boxData['custom_length'])) {
                        // Custom box size - use provided dimensions
                        $length = floatval($boxData['custom_length'] ?? 0);
                        $width = floatval($boxData['custom_width'] ?? 0);
                        $height = floatval($boxData['custom_height'] ?? 0);
                    } else {
                        // Standard box size - use mapping
                        $length = $box_size_mapping[$box_size]['length'] ?? 0;
                        $width = $box_size_mapping[$box_size]['width'] ?? 0;
                        $height = $box_size_mapping[$box_size]['height'] ?? 0;
                    }
                    $volumetric_weight = ($length * $width * $height) / 5000;
                    $dispatchData = [
                        'invoice_id' => $invoiceId,
                        'box_no' => $box_no,
                        'order_number' => $order_number,
                        'box_items' => implode(',', $items),
                        'length' => $length,
                        'width' => $width,
                        'height' => $height,
                        'weight' => $weight,
                        'volumetric_weight' => $volumetric_weight,
                        'billing_weight' => $weight > $volumetric_weight ? $weight : $volumetric_weight,
                        'shipping_charges' => 0,
                        'dispatch_date' => date('Y-m-d H:i:s'),
                        'courier_name' => null,
                        'shiprocket_order_id' => null,
                        'shiprocket_shipment_id' => null,
                        'shiprocket_tracking_url' => null,
                        'awb_code' => null,
                        'shipment_status' => 'pending',
                        'label_url' => null,
                        'groupname' => $boxData['groupname'] ?? null,
                        'created_by' => $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0,
                        'created_at' => date('Y-m-d H:i:s'),
                        'pickup_location' => $boxData['pickup_location'] ?? 'Head Off',
                        'batch_no' => $batch_no,
                        'box_size' => $box_size
                    ];

                    $dispatchId = $dispatchModel->createDispatch($dispatchData);
                    if ($dispatchId) {
                        $created_dispatches[] = [
                            'dispatch_id' => $dispatchId,
                            'invoice_id' => $invoiceId,
                            'box_no' => $box_no,
                            'order_number' => $order_number,
                            'box_items' => implode(',', $items),
                            'length' => $length,
                            'width' => $width,
                            'height' => $height,
                            'weight' => $weight,
                            'volumetric_weight' => $volumetric_weight,
                            'groupname' => $boxData['groupname'] ?? null,
                            'invoice' => [
                                'invoice_id' => $invoiceId,
                                'invoice_number' => $invoice_number,
                                'customer_id' => $customer_id,
                                'customer_name' => $customer_name,
                                'total_amount' => $total_amount
                            ]
                        ];
                    } else {
                        $errors[] = "Failed to create dispatch for order #$order_number, box #$box_no";
                    }
                }
            }

            // After all invoices and dispatch records are created, create Shiprocket shipments separately
            if (!empty($batch_no) && !empty($created_dispatches)) {
                
                foreach ($created_dispatches as $index => $dispatchInfo) {
                    $dispatchId = $dispatchInfo['dispatch_id'];
                    $invoiceId = $dispatchInfo['invoice_id'];
                    $order_number = $dispatchInfo['order_number'];
                    $box_no = $dispatchInfo['box_no'];
                    $box_items = $dispatchInfo['box_items'];
                    $length = $dispatchInfo['length'];
                    $width = $dispatchInfo['width'];
                    $height = $dispatchInfo['height'];
                    $weight = $dispatchInfo['weight'];
                    $volumetric_weight = $dispatchInfo['volumetric_weight'];
                    $groupname = $dispatchInfo['groupname'] ?? null;

                    // Get invoice and address data for Shiprocket payload
                    $invoice = $invoiceModel->getInvoiceById($invoiceId);
                    $address = $commanModel->getDispatchAddress($invoice['vp_order_info_id'] ?? 0) ?? ($invoice['address'] ?? []);
                    // Fallback: if address empty, get from vp_order_info by order_number
                    if (empty($address) || empty($address['address_line1'] ?? '')) {
                        $orderInfo = $ordersModel->getRemarksByOrderNumber($order_number);
                        $address = is_array($orderInfo) ? $orderInfo : [];
                    }
                    $firm = $commanModel->getRecordById('firm_details', 1) ?? [];
                    
                    // Find original box data from input
                    $boxData = null;
                    foreach ($input['orders'] as $orderData) {
                        if ($orderData['order_number'] == $order_number) {
                            if (!empty($orderData['boxes']) && isset($orderData['boxes'][$box_no - 1])) {
                                $boxData = $orderData['boxes'][$box_no - 1];
                            }
                            break;
                        }
                    }
                    
                    if (!$boxData) {
                        $errors[] = "Box data not found for dispatch #$dispatchId, order #$order_number, box #$box_no";
                        continue;
                    }
                    
                    // Get items for this box
                    $items = [];
                    if (isset($boxData['items'])) {
                        $items = is_array($boxData['items']) ? $boxData['items'] : explode(',', $boxData['items']);
                    }
                    
                    // Prepare order items for Shiprocket
                    $orderItems = [];
                    $subTotal = 0;
                    $totalBillableWeight = 0;
                    
                    $invoiceItems = $invoiceModel->getInvoiceItems($invoiceId);
                    $itemsMapById = [];
                    $itemsMapByCode = [];
                    foreach ($invoiceItems as $invItem) {
                        $itemsMapById[$invItem['id']] = $invItem;
                        $itemsMapByCode[$invItem['item_code'] ?? ''] = $invItem;
                    }
                    foreach ($items as $itemId) {
                        $invItem = $itemsMapById[$itemId] ?? $itemsMapById[(int)$itemId] ?? $itemsMapByCode[$itemId] ?? null;
                        if ($invItem) {
                            $units = $invItem['quantity'] ?? 1;
                            $price = $invItem['unit_price'] ?? 0;
                            $rawHsn = $invItem['hsn'] ?? '';
                            $hsnVal = '';
                            if ($rawHsn !== '') {
                                if (strpos($rawHsn, '.') !== false) {
                                    $hsnVal = explode('.', $rawHsn)[0];
                                } else {
                                    $hsnDigits = preg_replace('/\D/', '', $rawHsn);
                                    $hsnVal = $hsnDigits;
                                }
                                $hsnVal = substr(preg_replace('/\D/','', $hsnVal), 0, 4);
                            }
                            $orderItems[] = [
                                'name' => $invItem['groupname'] ?? $invItem['item_name'] ?? 'Item',
                                'sku' => $invItem['item_code'] ?? '',
                                'units' => $units,
                                'selling_price' => $price,
                                'discount' => 0,
                                'tax' => $invItem['tax_amount'] ?? 0,
                                'hsn' => $hsnVal
                            ];
                            $subTotal += $units * $price;
                            $totalBillableWeight += $invItem['weight'] ?? 0;
                        }
                    }
                    // Fallback: if no items matched, use all invoice items for this box
                    if (empty($orderItems) && !empty($invoiceItems)) {
                        foreach ($invoiceItems as $invItem) {
                            $units = $invItem['quantity'] ?? 1;
                            $price = $invItem['unit_price'] ?? 0;
                            $rawHsn = $invItem['hsn'] ?? '';
                            $hsnVal = $rawHsn ? substr(preg_replace('/\D/', '', $rawHsn), 0, 4) : '';
                            $orderItems[] = [
                                'name' => $invItem['groupname'] ?? $invItem['item_name'] ?? 'Item',
                                'sku' => $invItem['item_code'] ?? 'ITEM',
                                'units' => $units,
                                'selling_price' => $price,
                                'discount' => 0,
                                'tax' => $invItem['tax_amount'] ?? 0,
                                'hsn' => $hsnVal
                            ];
                            $subTotal += $units * $price;
                            $totalBillableWeight += $invItem['weight'] ?? 0;
                        }
                    }
                    
                    // Create unique order ID for Shiprocket
                    $shiprocketOrderId = $order_number . '_box_' . $box_no . '_' . time();
                    $box_size_mapping = [
                        'R-1' => ['length' => 22, 'width' => 17, 'height' => 5], 'R-2' => ['length' => 16, 'width' => 13, 'height' => 13],
                        'R-3' => ['length' => 16, 'width' => 11, 'height' => 7], 'R-4' => ['length' => 13, 'width' => 10, 'height' => 7],
                        'R-5' => ['length' => 21, 'width' => 11, 'height' => 7], 'R-6' => ['length' => 11, 'width' => 10, 'height' => 8],
                        'R-7' => ['length' => 8, 'width' => 6, 'height' => 5], 'R-8' => ['length' => 12, 'width' => 12, 'height' => 1.5],
                        'R-9' => ['length' => 17, 'width' => 12, 'height' => 2], 'R-10' => ['length' => 12, 'width' => 9, 'height' => 2],
                        'R-11' => ['length' => 10, 'width' => 10, 'height' => 2], 'R-12' => ['length' => 13, 'width' => 9, 'height' => 5],
                        'R-13' => ['length' => 11, 'width' => 8, 'height' => 5], 'R-14' => ['length' => 14, 'width' => 12, 'height' => 10]
                    ];
                    $boxSize = $boxData['box_size'] ?? 'R-1';
                    
                    // Determine dimensions: custom > boxData > dispatchInfo > mapping
                    if ($boxSize === 'CUSTOM' || isset($boxData['custom_length'])) {
                        $length = (float)($boxData['custom_length'] ?? $boxData['length'] ?? $dispatchInfo['length'] ?? 0);
                        $width = (float)($boxData['custom_width'] ?? $boxData['width'] ?? $dispatchInfo['width'] ?? 0);
                        $height = (float)($boxData['custom_height'] ?? $boxData['height'] ?? $dispatchInfo['height'] ?? 0);
                    } else {
                        $dims = $box_size_mapping[$boxSize] ?? null;
                        $length = (float)($boxData['length'] ?? $dispatchInfo['length'] ?? $dims['length'] ?? 0);
                        $width = (float)($boxData['width'] ?? $dispatchInfo['width'] ?? $dims['width'] ?? 0);
                        $height = (float)($boxData['height'] ?? $dispatchInfo['height'] ?? $dims['height'] ?? 0);
                    }
                    $volumetric_weight = ($length * $width * $height) / 5000;
                    $weight = (float)($boxData['weight'] ?? $dispatchInfo['weight'] ?? $totalBillableWeight ?: 0.5);

                    // Normalize country to 2-char ISO (Shiprocket expects IN, not India)
                    $billingCountry = trim($address['country'] ?? 'IN');
                    if (stripos($billingCountry, 'india') !== false || $billingCountry === '') {
                        $billingCountry = 'IN';
                    }
                    $shippingCountry = trim($address['shipping_country'] ?? $billingCountry);
                    if (stripos($shippingCountry, 'india') !== false || $shippingCountry === '') {
                        $shippingCountry = 'IN';
                    }

                    // Build customer name (Shiprocket requires billing_customer_name)
                    $billingFirstName = trim($address['first_name'] ?? $address['shipping_first_name'] ?? '');
                    $billingLastName = trim($address['last_name'] ?? $address['shipping_last_name'] ?? '');
                    $billingCustomerName = $billingFirstName ?: ($billingLastName ?: 'Customer');

                    // Use billing or shipping address (Shiprocket requires non-empty billing_address)
                    $billingAddress1 = trim($address['address_line1'] ?? $address['shipping_address_line1'] ?? '');
                    $billingAddress2 = trim($address['address_line2'] ?? $address['shipping_address_line2'] ?? '');
                    if ($billingAddress1 === '' && !empty($address['shipping_address_line1'])) {
                        $billingAddress1 = $address['shipping_address_line1'];
                        $billingAddress2 = $address['shipping_address_line2'] ?? '';
                    }

                    // vp_order_info uses gstin, not gst_number
                    $customerGst = $address['gstin'] ?? $address['gst_number'] ?? '';

                    // Ensure order_items have valid SKU (Shiprocket rejects empty sku)
                    foreach ($orderItems as &$oi) {
                        if (empty($oi['sku'])) {
                            $oi['sku'] = $oi['name'] ? preg_replace('/[^A-Za-z0-9\-]/', '', substr($oi['name'], 0, 20)) : 'ITEM';
                            if ($oi['sku'] === '') $oi['sku'] = 'ITEM';
                        }
                    }
                    unset($oi);
                    if (empty($orderItems)) {
                        $errors[] = "No order items for Shiprocket: order #$order_number, box #$box_no";
                        continue;
                    }

                    // Ensure dimensions/weight are valid (Shiprocket rejects 0)
                    if ($length <= 0 || $width <= 0 || $height <= 0) {
                        $length = $length ?: 1;
                        $width = $width ?: 1;
                        $height = $height ?: 1;
                    }
                    if ($weight <= 0) $weight = 0.5;
                    if ($subTotal <= 0) $subTotal = 0.01;

                    $shiprocketPayload = [
                        'order_id' => $shiprocketOrderId,
                        'order_date' => date('Y-m-d H:i'),
                        'pickup_location' => (string)($boxData['pickup_location'] ?? $firm['pickup_location'] ?? 'Head Off'),
                        'comment' => 'Box ' . $box_no . ' | Batch: ' . $batch_no,
                        'billing_customer_name' => $billingCustomerName,
                        'billing_last_name' => $billingLastName,
                        'billing_address' => $billingAddress1 ?: 'Address required',
                        'billing_address_2' => $billingAddress2,
                        'billing_city' => $address['city'] ?? $address['shipping_city'] ?? '',
                        'billing_state' => $address['state'] ?? $address['shipping_state'] ?? '',
                        'billing_country' => $billingCountry,
                        'billing_pincode' => $address['zipcode'] ?? $address['shipping_zipcode'] ?? '',
                        'billing_email' => $address['email'] ?? $address['shipping_email'] ?? '',
                        'billing_phone' => $address['mobile'] ?? $address['shipping_mobile'] ?? '',
                        'shipping_is_billing' => !empty($address['shipping_first_name']) || !empty($address['shipping_address_line1']) ? false : true,
                        'shipping_customer_name' => $address['shipping_first_name'] ?? $billingFirstName,
                        'shipping_last_name' => $address['shipping_last_name'] ?? $billingLastName,
                        'shipping_address' => $address['shipping_address_line1'] ?? $billingAddress1,
                        'shipping_address_2' => $address['shipping_address_line2'] ?? $billingAddress2,
                        'shipping_city' => $address['shipping_city'] ?? $address['city'] ?? '',
                        'shipping_pincode' => $address['shipping_zipcode'] ?? $address['zipcode'] ?? '',
                        'shipping_country' => $shippingCountry,
                        'shipping_state' => $address['shipping_state'] ?? $address['state'] ?? '',
                        'shipping_email' => $address['shipping_email'] ?? $address['email'] ?? '',
                        'shipping_phone' => $address['shipping_mobile'] ?? $address['mobile'] ?? '',
                        'customer_gst_no' => $customerGst,
                        'order_items' => $orderItems,
                        'payment_method' => strtoupper($invoice['payment_method'] ?? 'Prepaid'),
                        'shipping_charges' => 0,
                        'giftwrap_charges' => 0,
                        'transaction_charges' => 0,
                        'total_discount' => 0,
                        'sub_total' => round($subTotal, 2),
                        'length' => $length,
                        'breadth' => $width,
                        'height' => $height,
                        'weight' => $weight
                    ];
                    
                    // Call Shiprocket API
                    $shiprocketResponse = $dispatchModel->shiprocketCreateShipment($shiprocketPayload);
                    
                    // Update dispatch record with Shiprocket response
                    if ($shiprocketResponse && isset($shiprocketResponse['json']['order_id']) && ($shiprocketResponse['json']['status'] ?? '') === 'NEW') {
                        $updateData = [
                            'shiprocket_order_id' => $shiprocketResponse['json']['order_id'] ?? null,
                            'shiprocket_shipment_id' => $shiprocketResponse['json']['shipment_id'] ?? null,
                            'shiprocket_tracking_url' => $shiprocketResponse['json']['tracking_url'] ?? null,
                            'awb_code' => $shiprocketResponse['json']['awb_code'] ?? null,
                            'shipment_status' => $shiprocketResponse['json']['status'] ?? 'NEW',
                            'label_url' => $shiprocketResponse['json']['label_url'] ?? null,
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                        
                        $dispatchModel->updateDispatch($dispatchId, $updateData);
                        //Update order status to dispatched
                        $ordersModel->updateOrderByOrderNumber($order_number, ['status' => 'Dispatched']);
                        //add shipment_id in created_dispatches array for response
                        $created_dispatches[$index]['shiprocket_shipment_id'] = $shiprocketResponse['json']['shipment_id'] ?? null;
                        // Get AWB info
                        if (!empty($shiprocketResponse['json']['shipment_id'])) {
                            $awbInfoResponse = $dispatchModel->getShiprocketAwbInfo($shiprocketResponse['json']['shipment_id']);
                            if ($awbInfoResponse && isset($awbInfoResponse['awb_assign_status']) && $awbInfoResponse['awb_assign_status'] == 1) {
                                $awbCode = $awbInfoResponse['response']['data']['awb_code'];
                                $dispatchModel->updateDispatchAwbCode($shiprocketResponse['json']['shipment_id'], $awbCode);
                            }
                        }
                        //add awb_code in created_dispatches array for response
                        if (!empty($shiprocketResponse['json']['shipment_id']) && !empty($awbInfoResponse['response']['data']['awb_code'])) {
                            $created_dispatches[$index]['awb_code'] = $awbInfoResponse['response']['data']['awb_code'];
                        }
                        // Get label info
                        if (!empty($shiprocketResponse['json']['shipment_id'])) {
                            $labelInfoResponse = $dispatchModel->getShiprocketLabels($shiprocketResponse['json']['shipment_id']);
                            if ($labelInfoResponse && isset($labelInfoResponse['label_created']) && $labelInfoResponse['label_created'] == 1) {
                                $labelUrl = $labelInfoResponse['label_url'];
                                $dispatchModel->updateDispatchLabelUrl($shiprocketResponse['json']['shipment_id'], $labelUrl);
                            }
                        }
                        //add label_url in created_dispatches array for response
                        if (!empty($shiprocketResponse['json']['shipment_id']) && !empty($labelInfoResponse['label_url'])) {
                            $created_dispatches[$index]['label_url'] = $labelInfoResponse['label_url'];
                        }
                        
                    } else {
                        $errorMsg = $shiprocketResponse['json']['status'] ?? $shiprocketResponse['error'] ?? 'Failed to create shipment for Box ' . $box_no;
                        $errors[] = "Shiprocket error for order #$order_number, box #$box_no: $errorMsg";
                        
                        // Update dispatch status to failed
                        $dispatchModel->updateDispatch($dispatchId, [
                            'shipment_status' => 'failed',
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    }
                }
            }

            // Return success response
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Bulk invoices and dispatches created successfully',
                'batch_no' => $batch_no,
                'invoices_created' => count($created_invoices),
                'dispatches_created' => count($created_dispatches),
                'invoices' => $created_invoices,
                'dispatches' => $created_dispatches,
                'errors' => $errors
            ]);
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error creating invoices: ' . $e->getMessage(),
                'errors' => $errors
            ]);
            exit;
        }
    }

    /**
     * Get available couriers from Shiprocket based on serviceability
     */
    public function getCourierServiceability() {
        global $dispatchModel, $commanModel, $ordersModel;
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Get delivery postcode from order
            $order_number = $input['order_number'] ?? null;
            $length = (float)($input['length'] ?? 0);
            $breadth = (float)($input['breadth'] ?? 0);
            $height = (float)($input['height'] ?? 0);
            $weight = (float)($input['weight'] ?? 0);
            $cod = (int)($input['cod'] ?? 0);
            $is_express = (int)($input['is_express'] ?? 0);
            
            if (empty($order_number) || $weight <= 0 || $length <= 0 || $breadth <= 0 || $height <= 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Order number, weight, and dimensions are required and must be greater than 0'
                ]);
                exit;
            }
            
            // Get firm details for pickup postcode
            $firm = $commanModel->getRecordById('firm_details', 1);
            $pickup_postcode = $firm['pin'] ?? null;
            
            if (empty($pickup_postcode)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Pickup postcode not configured in firm details'
                ]);
                exit;
            }
            
            // Get delivery postcode from order address
            // $sql = "SELECT shipping_zipcode FROM vp_orders WHERE order_number = ?";
            // $stmt = $GLOBALS['conn']->prepare($sql);
            // if (!$stmt) {
            //     throw new Exception('Database error: ' . $GLOBALS['conn']->error);
            // }
            // $stmt->bind_param('s', $order_number);
            // $stmt->execute();
            // $result = $stmt->get_result();
            // $order = $result->fetch_assoc();
            // $stmt->close();

            $orderInfo = $ordersModel->getRemarksByOrderNumber($order_number);
            if (!$orderInfo || empty($orderInfo['shipping_zipcode'])) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Order not found or delivery postcode missing'
                ]);
                exit;
            }
            
            $delivery_postcode = $orderInfo['shipping_zipcode'];
            
            // Call Shiprocket serviceability API
            $serviceability = $dispatchModel->getCourierServiceability(
                $pickup_postcode,
                $delivery_postcode,
                $weight,
                $length,
                $breadth,
                $height,
                $cod,
                0, // is_return
                0  // qc_check
            );
            
            if (!$serviceability['success']) {
                http_response_code($serviceability['http_code'] ?? 400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to fetch courier serviceability',
                    'details' => $serviceability['data']
                ]);
                exit;
            }
            
            // Use Courier Selector to sort and filter couriers
            // Parameters: response data, is_cod, is_express
            $selectedCouriers = prepareCouriers($serviceability['data'], $cod, $is_express);
            print_r($serviceability); // Debug: check selected couriers
            // Format response with sorted courier details
            $couriers = [];
            if (!empty($selectedCouriers['topCourier'])) {
                foreach ($selectedCouriers['topCourier'] as $courier) {
                    $couriers[] = [
                        'id' => $courier['id'] ?? null,
                        'name' => $courier['name'] ?? 'Unknown',
                        'price' => $courier['freight'] ?? 0,
                        'etd' => $courier['etd'] ?? 'N/A',
                        'rating' => $courier['rating'] ?? 0,
                        'estimated_days' => $courier['delivery_performance'] ?? null,
                        'delivery_performance' => $courier['delivery_performance'] ?? 0,
                        'pickup_performance' => $courier['pickup_performance'] ?? 0,
                        'sla_adherence' => $courier['sla_adherence'] ?? 0
                    ];
                }
            }
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'couriers' => $couriers,
                'selected' => $selectedCouriers['selected'] ?? null,
                'message' => 'Couriers fetched and ranked successfully'
            ]);
            exit;
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching couriers: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    public function retryShipments() {
        global $dispatchModel, $ordersModel , $invoiceModel;
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['batch_no']) || empty($input['batch_no'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Batch number is required'
                ]);
                exit;
            }

            $batch_no = $input['batch_no'];
            $errors = [];
            $successful_count = 0;
            $failed_count = 0;

            $dispatch_records = $dispatchModel->getDispatchByBatchNo($batch_no);
            if (empty($dispatch_records)) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'No dispatch records found for batch number: ' . $batch_no
                ]);
                exit;
            }

            foreach ($dispatch_records as $dispatch_record) {
                try {
                    $dispatch_id = $dispatch_record['id'];
                    $order_number = $dispatch_record['order_number'];
                    $invoice_id = $dispatch_record['invoice_id'];

                    // Get order details
                    $order = $ordersModel->getOrderByOrderNumber($order_number);
                    if (!$order) {
                        throw new Exception("Order not found for order number: {$order_number}");
                    }

                    // Get invoice and items
                    $invoice = $invoiceModel->getInvoiceById($invoice_id);
                    if (!$invoice) {
                        throw new Exception("Invoice not found for ID: {$invoice_id}");
                    }

                    // Get invoice items
                    $invoiceItems = $invoiceModel->getInvoiceItems($invoice_id);

                    // Prepare Shiprocket payload
                    $shiprocketPayload = [
                        'order_id' => $dispatch_record['order_number'],
                        'order_date' => date('Y-m-d', strtotime($order['created_at'] ?? date('Y-m-d'))),
                        'pickup_location_id' => $dispatch_record['pickup_location_id'] ?? 1,
                        'channel_id' => 1,
                        'comment' => 'Bulk dispatch - Retry #' . ($dispatch_record['shiprocket_retry_count'] ?? 1),
                        'billing_customer_name' => $order['customer_name'] ?? '',
                        'billing_last_name' => '',
                        'billing_address_1' => $invoice['shipping_address'] ?? '',
                        'billing_address_2' => '',
                        'billing_city' => $invoice['shipping_city'] ?? '',
                        'billing_pincode' => $invoice['shipping_pincode'] ?? '',
                        'billing_state' => $invoice['shipping_state'] ?? '',
                        'billing_country' => 'India',
                        'billing_email' => $order['customer_email'] ?? '',
                        'billing_phone' => $order['customer_phone'] ?? '',
                        'delivery_type' => 'Home',
                        'weight' => (float)$dispatch_record['weight'],
                        'cod_amount' => 0,
                        'order_items' => array_map(function($item) {
                            return [
                                'name' => $item['item_name'] ?? '',
                                'sku' => $item['item_sku'] ?? '',
                                'units' => (int)($item['item_quantity'] ?? 1),
                                'selling_price' => (float)($item['item_price'] ?? 0),
                                'discount' => 0,
                                'tax' => 0,
                                'hsn_code' => $item['hsn_code'] ?? ''
                            ];
                        }, $invoiceItems ?? []),
                        'dimensions' => [
                            'length' => (float)($dispatch_record['box_length'] ?? 0),
                            'breadth' => (float)($dispatch_record['box_width'] ?? 0),
                            'height' => (float)($dispatch_record['box_height'] ?? 0),
                            'unit' => 'inch'
                        ]
                    ];
//print_array($shiprocketPayload);exit;
                    // Call Shiprocket API
                    $shiprocketResponse = $dispatchModel->shiprocketCreateShipment($shiprocketPayload);
                    
                    if ($shiprocketResponse && isset($shiprocketResponse['response_data'])) {
                        $responseData = $shiprocketResponse['response_data'];
                        
                        if (isset($responseData['shipment_id'])) {
                            // Success - update dispatch record
                            $shipment_id = $responseData['shipment_id'];
                            $order_id = $responseData['order_id'] ?? null;

                            // Get AWB info
                            $awbInfo = $dispatchModel->getShiprocketAwbInfo($shipment_id);
                            $awb_code = $awbInfo['awb_code'] ?? null;

                            // Get labels
                            $labelInfo = $dispatchModel->getShiprocketLabels($shipment_id);
                            $label_url = $labelInfo['label_url'] ?? null;

                            // Update dispatch record with shipment details
                            $update_data = [
                                'shipment_id' => $shipment_id,
                                'order_id' => $order_id,
                                'awb_code' => $awb_code,
                                'label_url' => $label_url,
                                'status' => 'dispatched',
                                'dispatched_at' => date('Y-m-d H:i:s'),
                                'shiprocket_response' => json_encode($responseData)
                            ];

                            $dispatchModel->updateDispatch($dispatch_id, $update_data);
                            $successful_count++;
                        } else {
                            throw new Exception($responseData['message'] ?? 'Unknown Shiprocket error');
                        }
                    } else {
                        throw new Exception('Invalid Shiprocket response');
                    }

                } catch (Exception $e) {
                    $failed_count++;
                    $errors[] = "Shiprocket error for order #{$order_number}, dispatch ID #{$dispatch_id}: " . $e->getMessage();
                    error_log("Retry shipment error for order {$order_number}: " . $e->getMessage());
                }
            }

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Retry completed',
                'batch_no' => $batch_no,
                'successful_count' => $successful_count,
                'failed_count' => $failed_count,
                'errors' => $errors
            ]);
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error during retry: ' . $e->getMessage()
            ]);
            exit;
        }
    }
}
?>