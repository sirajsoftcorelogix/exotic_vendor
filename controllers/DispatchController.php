<?php
require_once 'models/comman/tables.php';
require_once 'models/invoice/invoice.php';
require_once 'models/dispatch/dispatch.php';
$commanModel = new Tables($conn);
$invoiceModel = new Invoice($conn); 
$dispatchModel = new Dispatch($conn);

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
                        'order_numbers' => $_POST['order_numbers'][$boxNo] ?? []
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
                        'name' => $it['item_name'] ?? $it['sku'] ?? 'Item',
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
                
                file_put_contents('shiprocket_response_log.txt', date('Y-m-d H:i:s') . " - Box $boxNo - Payload: " . json_encode($shiprocketPayload) . " - Response: " . json_encode($shiprocketResponse) . "\n", FILE_APPEND);
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
                    'created_by' => $_SESSION['user_id'] ?? 0,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $dispatchId = $dispatchModel->createDispatch($dispatchData);
                //print_array($dispatchData);
                //echo "Dispatch ID: $dispatchId";exit;

                //awb api call getShiprocketAwbInfo
                $awbInfoResponse = $dispatchModel->getShiprocketAwbInfo($shiprocketResponse['json']['shipment_id']);
                file_put_contents('shiprocket_awb_response_log.txt', date('Y-m-d H:i:s') . " - Box $boxNo - Shipment ID: " . $shiprocketResponse['json']['shipment_id'] . " - AWB Info Response: " . json_encode($awbInfoResponse) . "\n", FILE_APPEND);
                //chmod('shiprocket_awb_response_log.txt', 0666); // make log file writable
                if($awbInfoResponse && isset($awbInfoResponse['awb_assign_status']) && $awbInfoResponse['awb_assign_status'] == 1) {
                    // Update dispatch record with AWB code
                    $awbCode = $awbInfoResponse['response']['data']['awb_code'];
                    $dispatchModel->updateDispatchAwbCode($shiprocketResponse['json']['shipment_id'], $awbCode);
                    $dispatchRecords['awb'][$boxNo] = $awbCode;
                } else {
                    file_put_contents('shiprocket_awb_response_log.txt', date('Y-m-d H:i:s') . " - Box $boxNo - Shipment ID: " . $shiprocketResponse['json']['shipment_id'] . " - AWB code not found in response\n", FILE_APPEND);
                    //chmod('shiprocket_awb_response_log.txt', 0666); // make log file writable
                }
                //label api call getShiprocketLabelInfo
                $labelInfoResponse = $dispatchModel->getShiprocketLabels($shiprocketResponse['json']['shipment_id']);
                file_put_contents('shiprocket_label_response_log.txt', date('Y-m-d H:i:s') . " - Box $boxNo - Shipment ID: " . $shiprocketResponse['json']['shipment_id'] . " - Label Info Response: " . json_encode($labelInfoResponse) . "\n", FILE_APPEND);
                //chmod('shiprocket_label_response_log.txt', 0666); // make log file writable
                $lableAdd = false;
                if($labelInfoResponse && $labelInfoResponse['label_created'] == 1) {
                    // Update dispatch record with label URL
                    $labelUrl = $labelInfoResponse['label_url'];
                    $lableAdd = $dispatchModel->updateDispatchLabelUrl($shiprocketResponse['json']['shipment_id'], $labelUrl);
                    $dispatchRecords['labelUrl'][$boxNo] = $labelUrl;
                } else {
                    file_put_contents('shiprocket_label_response_log.txt', date('Y-m-d H:i:s') . " - Box $boxNo - Shipment ID: " . $shiprocketResponse['json']['shipment_id'] . " - Label URL ***not found*** in response\n", FILE_APPEND);
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
                //add label URL in dispatch record                
                
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
            }
            renderTemplate('views/dispatch/create.php', ['invoices' => $invoices]);
        }
    }
}
?>