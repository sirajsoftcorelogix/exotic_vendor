<?php
/**
 * E-way Bill and IRN Generation Service for Domestic Invoices
 * Handles IRN generation and E-way bill creation using Alankit API
 * Pattern follows generateAlankitIrnForInvoice from InvoicesController
 */
class DomesticEwbIrnService {
    private $db;
    private $lastError;
    private $alankitConfig;
    
    public function __construct($db, $alankitConfig = []) {
        $this->db = $db;
        $this->alankitConfig = $alankitConfig;
        $this->lastError = null;
    }
    
    /**
     * Get the last error message
     */
    public function getLastError() {
        return $this->lastError;
    }
    
    /**
     * Generate IRN and E-way bill for a domestic invoice
     * Follows the sequence: Auth -> Prepare Payload -> Generate IRN -> Generate EWB
     * 
     * @param int $invoiceId Invoice ID
     * @param array $invoice Full invoice details
     * @param array $items Invoice line items
     * @param array $customer Customer details
     * @param array $firm Firm details
     * @param array $ewbData E-way bill data (veh_no, veh_type)
     * @return array Result with status, irn, ewb, and messages
     */
    public function generateIrnAndEwb($invoiceId, $invoice, $items, $customer, $firm, $ewbData = []) {
        try {
            echo "Domestic EWB: Starting IRN and EWB generation for invoice #$invoiceId\n";
            // Validate required data
            if (!$invoice || empty($items) || !$customer || !$firm) {
                $this->lastError = "Missing required invoice, items, customer, or firm data";
                return ['status' => false, 'message' => $this->lastError];
            }
            
            // Check if record already exists
            $existingRecord = $this->getEwbIrnRecord($invoiceId);
            if (!$existingRecord) {
                $this->createEwbIrnRecord($invoiceId);
                echo "Domestic EWB: Created new record for invoice #$invoiceId\n";
            }
            
            // Initialize Alankit client with credentials
            require_once __DIR__ . '/AlankitIrnNew.php';
            
            if (empty($this->alankitConfig['username']) || empty($this->alankitConfig['password']) 
                || empty($this->alankitConfig['subscription_key']) || empty($this->alankitConfig['app_key'])
                || empty($this->alankitConfig['gstin'])) {
                $this->lastError = "Missing Alankit API credentials in configuration";
                error_log("Alankit IRN: " . $this->lastError);
                return ['status' => false, 'message' => $this->lastError];
            }
            echo "Domestic EWB: Alankit API credentials loaded successfully.\n";
            $alankitClient = new AlankitIrnNew(
                $this->alankitConfig['username'],
                $this->alankitConfig['password'],
                $this->alankitConfig['subscription_key'],
                $this->alankitConfig['app_key'],
                $this->alankitConfig['gstin'],
                $this->alankitConfig['force_refresh_access_token'] ?? true
            );
            
            $result = [
                'status' => true,
                'irn' => null,
                'ewb' => null,
                'irn_message' => null,
                'ewb_message' => null,
                'errors' => []
            ];
            
            // Step 1: Prepare IRN payload from invoice data (includes EwbDtls if vehicle data provided)
            $irnPayload = $this->prepareIrnPayload($invoice, $items, $customer, $firm, $ewbData);
            print_r($irnPayload); // Debug: Show prepared payload
            error_log("Domestic EWB: Prepared IRN payload for invoice #$invoiceId");
            
            // Step 2: Authenticate and get access token
            $authResponse = $alankitClient->sendRequest('AUTH_ENDPOINT', []);
            if (!$authResponse || !isset($authResponse['token'])) {
                $result['status'] = false;
                $result['errors'][] = 'Authentication failed: ' . ($authResponse['message'] ?? 'Unknown error');
                $this->updateIrnStatus($invoiceId, 'failed', $result['errors'][0], $irnPayload, null);
                $this->lastError = $result['errors'][0];
                return $result;
            }
            echo "Domestic EWB: Authentication successful, received access token\n";
            $accessToken = $authResponse['token'];
            $encryptedSek = $authResponse['sek'] ?? null;
            
            if (!$encryptedSek) {
                $result['status'] = false;
                $result['errors'][] = 'No encrypted SEK received from authentication';
                $this->updateIrnStatus($invoiceId, 'failed', $result['errors'][0], $irnPayload, $authResponse);
                $this->lastError = $result['errors'][0];
                return $result;
            }
            
            // Step 3: Decrypt SEK using AppKey (same sequence as generateAlankitIrnForInvoice)
            $decryptedSek = $alankitClient->decryptSek($encryptedSek, $this->alankitConfig['app_key']);
            if (!$decryptedSek) {
                $result['status'] = false;
                $result['errors'][] = 'SEK decryption failed';
                $this->updateIrnStatus($invoiceId, 'failed', $result['errors'][0], $irnPayload, $authResponse);
                $this->lastError = $result['errors'][0];
                return $result;
            }
            
            error_log("Domestic EWB: SEK decrypted successfully");
            
            // Step 4: Generate IRN
            error_log("Domestic EWB: Generating IRN for invoice #$invoiceId");
            $irnResponse = $alankitClient->generateIrn($irnPayload, $accessToken);
            
            if (!$irnResponse || !isset($irnResponse['data']['Irn'])) {
                $result['status'] = false;
                $result['errors'][] = 'IRN generation failed: ' . ($irnResponse['message'] ?? 'Unknown error');
                $this->updateIrnStatus($invoiceId, 'failed', $result['errors'][0], $irnPayload, $irnResponse);
                $this->lastError = $result['errors'][0];
                error_log("Domestic EWB: " . $result['errors'][0]);
                return $result;
            }
            
            $irn = $irnResponse['data']['Irn'];
            $result['irn'] = $irn;
            $result['irn_message'] = 'IRN generated successfully';
            
            // Update IRN status in database
            $this->updateIrnStatus($invoiceId, 'generated', null, $irnPayload, $irnResponse, $irn);
            
            // Save vehicle data if E-way bill was included
            if (!empty($ewbData['veh_no']) && !empty($ewbData['veh_type'])) {
                $this->updateEwbStatus(
                    $invoiceId,
                    'generated',
                    null,
                    null,
                    null,
                    null,
                    $ewbData['veh_no'],
                    $ewbData['veh_type']
                );
                error_log("Domestic EWB: IRN generated with E-way bill details - VehNo: {$ewbData['veh_no']}, VehType: {$ewbData['veh_type']}, IRN: $irn");
            } else {
                error_log("Domestic EWB: IRN generated for invoice #$invoiceId - IRN: $irn");
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->lastError = 'Exception: ' . $e->getMessage();
            error_log("Domestic EWB Exception for invoice #$invoiceId: " . $this->lastError);
            return [
                'status' => false,
                'message' => $this->lastError,
                'errors' => [$this->lastError]
            ];
        }
    }
    
    /**
     * Prepare IRN payload following AlankitIrnNew format
     * @param array $ewbData Optional E-way bill data to include (veh_no, veh_type)
     */
    private function prepareIrnPayload($invoice, $items, $customer, $firm, $ewbData = []) {
        // Format line items
        $itemList = [];
        foreach ($items as $idx => $item) {
            $itemList[] = [
                'SlNo' => (string)($idx + 1),
                'PrdDesc' => $item['item_name'] ?? '',
                'IsServc' => 'N',
                'HsnCd' => substr($item['hsn'] ?? '', 0, 8),
                'Qty' => (float)($item['quantity'] ?? 0),
                'Unit' => $item['unit'] ?? 'NOS',
                'UnitPrice' => (float)($item['unit_price'] ?? 0),
                'TotAmt' => (float)(($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0)),
                'AssAmt' => (float)(($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0)),
                'GstRt' => (int)($item['tax_rate'] ?? 0),
                'IgstAmt' => (float)($item['tax_amount'] ?? 0),
                'TotItemVal' => (float)(($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0) + ($item['tax_amount'] ?? 0)),
                'CgstAmt' => 0,
                'SgstAmt' => 0
            ];
        }
        
        $invoiceNumberParts = explode('-', $invoice['invoice_number'] ?? '');
        $invoiceNumber = end($invoiceNumberParts);
        $buyerAddress = ($customer['address_line1'] ?? '') . ' ' . ($customer['address_line2'] ?? '');
        $shippingAddress = ($customer['shipping_address_line1'] ?? '') . ' ' . ($customer['shipping_address_line2'] ?? '');
        
        // Determine buyer and shipping details
        $isBusiness = (trim($customer['country'] ?? '') === 'IN');
        $buyerGstin = $isBusiness ? ($customer['gstin'] ?? '') : 'URP';
        $buyerStateCode = $isBusiness ? (trim($customer['state_code'] ?? '') ?: '96') : '96';
        $buyerPincode = $isBusiness ? (trim($customer['zipcode'] ?? '') ?: '000000') : '999999';
        $shippingState = $isBusiness ? (trim($customer['state'] ?? '') ?: '96') : '96';
        $shippingPincode = $isBusiness ? (trim($customer['zipcode'] ?? '') ?: '000000') : '999999';
        
        return [
            'Version' => '1.1',
            'TranDtls' => [
                'TaxSch' => 'GST',
                'SupTyp' => $isBusiness ? 'B2B' : 'B2C',
                'RegRev' => 'N',
                'EcmGstin' => null,
                'IgstOnIntra' => 'N'
            ],
            'DocDtls' => [
                'Typ' => 'INV',
                'No' => (string)$invoiceNumber,
                'Dt' => $invoice['invoice_date'] ? date('d/m/Y', strtotime($invoice['invoice_date'])) : date('d/m/Y')
            ],
            'SellerDtls' => [
                'Gstin' => $firm['gstin'] ?? '',
                'LglNm' => $firm['name'] ?? '',
                'TrdNm' => $firm['name'] ?? '',
                'Addr1' => $firm['address'] ?? '',
                'Loc' => $firm['city'] ?? '',
                'Pin' => (int)($firm['pincode'] ?? 0),
                'Stcd' => (string)($firm['state_code'] ?? ''),
                'Ph' => $firm['phone'] ?? '',
                'Em' => $firm['email'] ?? ''
            ],
            'BuyerDtls' => [
                'Gstin' => $buyerGstin,
                'LglNm' => $customer['first_name'] ?? 'Buyer',
                'TrdNm' => $customer['first_name'] ?? 'Buyer',
                'Pos' => $buyerStateCode,
                'Addr1' => $buyerAddress,
                'Loc' => $customer['city'] ?? '',
                'Pin' => (int)$buyerPincode,
                'Stcd' => (string)$buyerStateCode,
                'Ph' => $customer['phone'] ?? '',
                'Em' => $customer['email'] ?? ''
            ],
            'ShipDtls' => [
                'Gstin' => $buyerGstin,
                'LglNm' => $customer['first_name'] ?? 'Buyer',
                'TrdNm' => $customer['first_name'] ?? 'Buyer',
                'Addr1' => $shippingAddress ?: $buyerAddress,
                'Loc' => $customer['city'] ?? '',
                'Pin' => (int)$shippingPincode,
                'Stcd' => (string)$shippingState
            ],
            'ItemList' => $itemList,
            'ValDtls' => [
                'AssVal' => (float)($invoice['subtotal'] ?? 0),
                'CgstVal' => 0,
                'SgstVal' => 0,
                'IgstVal' => (float)($invoice['tax_amount'] ?? 0),
                'CesVal' => 0,
                'Discount' => (float)($invoice['discount_amount'] ?? 0),
                'OthChrg' => 0,
                'RndOffAmt' => 0,
                'TotInvVal' => (float)($invoice['total_amount'] ?? 0)
            ],
            'PayDtls' => null,
            'RefDtls' => null,
            'AddlDocDtls' => null,
            'EwbDtls' => !empty($ewbData['veh_no']) && !empty($ewbData['veh_type']) ? [
                'TransId' => substr(preg_replace('/\s+/', '', (string)($ewbData['trans_id'] ?? '')), 0, 15),
                'TransName' => (string)($ewbData['trans_name'] ?? 'Transport'),
                'Distance' => (int)($ewbData['distance'] ?? 100),
                'TransDocNo' => (string)($ewbData['trans_doc_no'] ?? date('YmdHis')),
                'TransDocDt' => (string)($ewbData['trn_doc_dt'] ?? date('d/m/Y')),
                'VehNo' => (string)($ewbData['veh_no'] ?? ''),
                'VehType' => (string)($ewbData['veh_type'] ?? 'R'),
                'TransMode' => (string)($ewbData['trans_mode'] ?? '1')
            ] : null
        ];
    }
    
    /**
     * Prepare E-way bill payload
     */
    private function prepareEwbPayload($irn, $ewbData, $invoice, $customer) {
        return [
            'Irn' => $irn,
            'Distance' => (int)($ewbData['distance'] ?? 100),
            'TransId' => substr(preg_replace('/\s+/', '', (string)($ewbData['trans_id'] ?? '')), 0, 15),
            'TransName' => (string)($ewbData['trans_name'] ?? 'Transport'),
            'TrnDocDt' => (string)($ewbData['trn_doc_dt'] ?? date('d/m/Y')),
            'DispDtls' => [
                'Nm' => $customer['first_name'] ?? 'Buyer',
                'Addr1' => ($customer['shipping_address_line1'] ?? '') . ' ' . ($customer['shipping_address_line2'] ?? ''),
                'Loc' => $customer['city'] ?? '',
                'Pin' => (int)($customer['shipping_zipcode'] ?? 0),
                'Stcd' => (string)($customer['shipping_state'] ?? '96')
            ],
            'ExpShipDtls' => [
                'Gstin' => (string)($invoice['seller_gstin'] ?? ''),
                'TrdNm' => (string)($invoice['seller_name'] ?? 'Seller'),
                'Addr1' => (string)($invoice['seller_address'] ?? ''),
                'Loc' => (string)($invoice['seller_city'] ?? ''),
                'Pin' => (int)($invoice['seller_pincode'] ?? 0),
                'Stcd' => (string)($invoice['seller_state_code'] ?? '')
            ]
        ];
    }
    
    /**
     * Create a new E-way bill record
     */
    private function createEwbIrnRecord($invoiceId) {
        $query = "INSERT INTO vp_domestic_ewb_irn (vp_invoices_id, irn_status, ewb_status) VALUES (?, 'pending', 'pending')";
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            error_log("Failed to prepare statement: " . $this->db->error);
            return false;
        }
        $stmt->bind_param("i", $invoiceId);
        return $stmt->execute();
    }
    
    /**
     * Get existing E-way bill record
     */
    public function getEwbIrnRecord($invoiceId) {
        $query = "SELECT * FROM vp_domestic_ewb_irn WHERE vp_invoices_id = ?";
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            error_log("Failed to prepare statement: " . $this->db->error);
            return null;
        }
        $stmt->bind_param("i", $invoiceId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Update IRN status and data
     */
    private function updateIrnStatus($invoiceId, $status, $error = null, $payload = null, $response = null, $irn = null) {
        $query = "UPDATE vp_domestic_ewb_irn SET irn_status = ?, irn_error = ?, irn_payload = ?, irn_response = ?, irn = ?, irn_generated_at = NOW() WHERE vp_invoices_id = ?";
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            error_log("Failed to prepare statement: " . $this->db->error);
            return false;
        }
        $payloadJson = $payload ? json_encode($payload) : null;
        $responseJson = $response ? json_encode($response) : null;
        $stmt->bind_param("sssssi", $status, $error, $payloadJson, $responseJson, $irn, $invoiceId);
        return $stmt->execute();
    }
    
    /**
     * Update E-way bill status and data
     */
    private function updateEwbStatus($invoiceId, $status, $error = null, $payload = null, $response = null, $ewb = null, $vehNo = null, $vehType = null) {
        $query = "UPDATE vp_domestic_ewb_irn SET ewb_status = ?, ewb_error = ?, ewb_payload = ?, ewb_response = ?, ewb = ?, veh_no = ?, veh_type = ?, ewb_generated_at = NOW() WHERE vp_invoices_id = ?";
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            error_log("Failed to prepare statement: " . $this->db->error);
            return false;
        }
        $payloadJson = $payload ? json_encode($payload) : null;
        $responseJson = $response ? json_encode($response) : null;
        $stmt->bind_param("sssssssi", $status, $error, $payloadJson, $responseJson, $ewb, $vehNo, $vehType, $invoiceId);
        return $stmt->execute();
    }
}
?>

