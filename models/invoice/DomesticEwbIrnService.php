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
    private $infoDtlsColumnChecked = false;
    
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
            $this->ensureInfoDtlsColumn();
            // Validate required data
            if (!$invoice || empty($items) || !$customer || !$firm) {
                $this->lastError = "Missing required invoice, items, customer, or firm data";
                return ['status' => false, 'message' => $this->lastError];
            }
            
            // Check if record already exists
            $existingRecord = $this->getEwbIrnRecord($invoiceId);
            if (!$existingRecord) {
                $this->createEwbIrnRecord($invoiceId);
                //echo "Domestic EWB: Created new record for invoice #$invoiceId\n";
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
            //print_r($irnPayload); // Debug: Show prepared payload
            //error_log("Domestic EWB: Prepared IRN payload for invoice #$invoiceId");
            
            // Step 2: Authenticate and get access token
            $authreq = $alankitClient->authRequest();
            // call api request to auth
            // Prepare request with encrypted data
            $data = [
                "Data" => $authreq
            ];
            echo "Alankit IRN: Sending authentication request for invoice #$invoiceId\n";
            $authResponse = $alankitClient->sendRequest('AUTH_ENDPOINT', $data, false);
            
            if (!$authResponse || !isset($authResponse['Data']['AuthToken'])) {
                $result['status'] = false;
                $result['errors'][] = 'Authentication failed: ' . ($authResponse['message'] ?? 'Unknown error');
                $this->updateIrnStatus($invoiceId, 'failed', $result['errors'][0], $irnPayload, null);
                $this->lastError = $result['errors'][0];
                return $result;
            }
            //echo "Domestic EWB: Authentication successful, received access token\n";
            $accessToken = $authResponse['Data']['AuthToken'];
            $encryptedSek = $authResponse['Data']['Sek'] ?? null;
            
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
            
            //error_log("Domestic EWB: SEK decrypted successfully");
            
            // Step 4: Generate IRN
            //error_log("Domestic EWB: Generating IRN for invoice #$invoiceId");
            //$irnResponse = $alankitClient->generateIrn($irnPayload, $accessToken);
            $payloadreq = base64_encode(json_encode($irnPayload));
            $encryptedPayload = $alankitClient->encryptBySymmetricKey($payloadreq, $decryptedSek);
            if (!$encryptedPayload) {
                error_log("Alankit IRN: Payload encryption failed for invoice #$invoiceId");
                return false;
            }
            echo '<br><br>'.$encryptedPayload.'<br><br>';
            // Send IRN generation request with encrypted payload
            //$irnResponse = $alankitClient->sendRequest('IRN_GENERATE_ENDPOINT', ['Data' => $encryptedPayload], true, $accessToken);
            $irnResponse = $alankitClient->generateIrn(['Data' => $encryptedPayload], $accessToken);   
            print_r($irnResponse);
                  
            if ($irnResponse && isset($irnResponse['Data'])) {
                $decryptedResponse = $alankitClient->decrypt_irn($irnResponse['Data'], $decryptedSek);
                $irnResponse = json_decode($decryptedResponse, true);
                //echo "Alankit IRN: IRN generation response decrypted for invoice #$invoiceId\n";
                //print_r($irnResponse);
            } else {
                error_log("Alankit IRN: No response data received for invoice #$invoiceId");
                //$irnResponse = null;
            }

            $infoDtls = null;
            if (is_array($irnResponse) && array_key_exists('InfoDtls', $irnResponse)) {
                $infoDtls = is_array($irnResponse['InfoDtls']) ? json_encode($irnResponse['InfoDtls']) : (string)$irnResponse['InfoDtls'];
            }

            $isDupIrn = false;
            if (
                is_array($irnResponse)
                && isset($irnResponse['InfoDtls'])
                && is_array($irnResponse['InfoDtls'])
                && isset($irnResponse['InfoDtls']['InfCd'])
            ) {
                $isDupIrn = (strtoupper(trim((string)$irnResponse['InfoDtls']['InfCd'])) === 'DUPIRN');
            }

            // For DUPIRN response, preserve existing IRN/EWB fields and only store InfoDtls.
            if ($isDupIrn) {
                $this->updateInfoDtlsOnly($invoiceId, $infoDtls);

                $dupMessage = trim((string)($irnResponse['InfoDtls']['Desc'] ?? 'Duplicate IRN (DUPIRN)'));
                if ($dupMessage === '') {
                    $dupMessage = 'Duplicate IRN (DUPIRN)';
                }

                $result['status'] = false;
                $result['errors'][] = $dupMessage;
                if (!empty($irnResponse['Irn'])) {
                    $result['irn'] = (string)$irnResponse['Irn'];
                }
                $this->lastError = $dupMessage;
                return $result;
            }

            if (!$irnResponse || !isset($irnResponse['Irn'])) {
                $result['status'] = false;
                $result['errors'][] = 'IRN generation failed: ' . ($irnResponse['message'] ?? 'Unknown error');
                $this->updateIrnStatus($invoiceId, 'failed', $result['errors'][0], $irnPayload, $irnResponse);
                $this->updateEwbStatus($invoiceId, 'failed', $result['errors'][0], $irnPayload, $irnResponse, null, null, null, null, null, null, null, $infoDtls);
                $this->lastError = $result['errors'][0];
                error_log("Domestic EWB: " . $result['errors'][0]);
                return $result;
            }
            
            if($irnResponse && isset($irnResponse['Status']) && $irnResponse['Status'] === 'ACT') {
                $irn = $irnResponse['Irn'];
                $result['irn'] = $irn;
                $result['irn_message'] = 'IRN generated successfully';                
                // Update IRN status in database
                $this->updateIrnStatus($invoiceId, 'generated', null, $irnPayload, $irnResponse, $irn); 

                $ewbNo = isset($irnResponse['EwbNo']) ? (string)$irnResponse['EwbNo'] : null;
                $genGstin = isset($irnResponse['GenGstin']) ? (string)$irnResponse['GenGstin'] : null;
                $ewbDate = $this->normalizeDbDateTime($irnResponse['EwbDt'] ?? null);
                $ewbValidTill = $this->normalizeDbDateTime($irnResponse['EwbValidTill'] ?? null);
                $ewbStatus = !empty($ewbNo) ? 'generated' : 'pending';

                $this->updateEwbStatus(
                    $invoiceId,
                    $ewbStatus,
                    null,
                    $irnPayload,
                    $irnResponse,
                    $ewbNo,
                    !empty($ewbData['veh_no']) ? (string)$ewbData['veh_no'] : null,
                    !empty($ewbData['veh_type']) ? (string)$ewbData['veh_type'] : null,
                    $ewbNo,
                    $ewbDate,
                    $ewbValidTill,
                    $genGstin,
                    $infoDtls
                );
                               
            } else {
                $this->updateEwbStatus(
                    $invoiceId,
                    'failed',
                    null,
                    $irnPayload,
                    $irnResponse,
                    null,
                    !empty($ewbData['veh_no']) ? (string)$ewbData['veh_no'] : null,
                    !empty($ewbData['veh_type']) ? (string)$ewbData['veh_type'] : null,
                    null,
                    null,
                    null,
                    null,
                    $infoDtls
                );
            }
            
            
            // Save vehicle data if E-way bill was included
            if (!empty($ewbData['veh_no']) && !empty($ewbData['veh_type']) && empty($irnResponse['EwbNo'])) {
                $this->updateEwbStatus(
                    $invoiceId,
                    'generated',
                    null,
                    null,
                    null,
                    null,
                    $ewbData['veh_no'],
                    $ewbData['veh_type'],
                    null,
                    null,
                    null,
                    null,
                    $infoDtls
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
     * Regenerate only E-way bill using an existing generated IRN.
     *
     * @param int $invoiceId
     * @param string $irn
     * @param array $invoice
     * @param array $customer
     * @param array $ewbData
     * @return array{status:bool,message:string,ewb?:string,response?:array<string,mixed>}
     */
    public function regenerateEwbWithIrn($invoiceId, $irn, $invoice, $customer, $ewbData = []) {
        try {
            $this->ensureInfoDtlsColumn();

            $irn = trim((string)$irn);
            if ($invoiceId <= 0 || $irn === '') {
                $msg = 'Invoice ID and generated IRN are required for EWB regeneration.';
                $this->lastError = $msg;
                return ['status' => false, 'message' => $msg];
            }

            require_once __DIR__ . '/AlankitIrnNew.php';
            if (empty($this->alankitConfig['username']) || empty($this->alankitConfig['password'])
                || empty($this->alankitConfig['subscription_key']) || empty($this->alankitConfig['app_key'])
                || empty($this->alankitConfig['gstin'])) {
                $msg = 'Missing Alankit API credentials in configuration';
                $this->lastError = $msg;
                return ['status' => false, 'message' => $msg];
            }

            $alankitClient = new AlankitIrnNew(
                $this->alankitConfig['username'],
                $this->alankitConfig['password'],
                $this->alankitConfig['subscription_key'],
                $this->alankitConfig['app_key'],
                $this->alankitConfig['gstin'],
                $this->alankitConfig['force_refresh_access_token'] ?? true
            );

            $authReq = $alankitClient->authRequest();
            $authResponse = $alankitClient->sendRequest('AUTH_ENDPOINT', ['Data' => $authReq], false);
            if (!$authResponse || !isset($authResponse['Data']['AuthToken'])) {
                $msg = 'Authentication failed: ' . ($authResponse['message'] ?? 'Unknown error');
                $this->updateEwbStatus($invoiceId, 'failed', $msg, null, $authResponse, null, null, null, null, null, null, null, null);
                $this->lastError = $msg;
                return ['status' => false, 'message' => $msg];
            }

            $accessToken = (string)$authResponse['Data']['AuthToken'];
            $encryptedSek = $authResponse['Data']['Sek'] ?? null;
            if (!$encryptedSek) {
                $msg = 'No encrypted SEK received from authentication';
                $this->updateEwbStatus($invoiceId, 'failed', $msg, null, $authResponse, null, null, null, null, null, null, null, null);
                $this->lastError = $msg;
                return ['status' => false, 'message' => $msg];
            }

            $decryptedSek = $alankitClient->decryptSek($encryptedSek, $this->alankitConfig['app_key']);
            if (!$decryptedSek) {
                $msg = 'SEK decryption failed';
                $this->updateEwbStatus($invoiceId, 'failed', $msg, null, $authResponse, null, null, null, null, null, null, null, null);
                $this->lastError = $msg;
                return ['status' => false, 'message' => $msg];
            }

            $distance = isset($ewbData['distance']) ? (int)$ewbData['distance'] : 100;
            if ($distance <= 0) {
                $distance = 100;
            }
            $transMode = trim((string)($ewbData['trans_mode'] ?? '1'));
            if ($transMode === '') {
                $transMode = '1';
            }

            $invoiceNo = trim((string)($invoice['invoice_number'] ?? ''));
            if ($invoiceNo === '') {
                $invoiceNo = (string)$invoiceId;
            }

            $ewbPayload = [
                'Irn' => $irn,
                //'Distance' => $distance,
                'Distance' => 0,
                'TransMode' => $transMode,
                'VehNo' => 'ka123456',// Hardcoded for testing; replace with actual logic as needed
                'VehType' => 'R',// Hardcoded for testing; replace with actual logic as needed
                //'VehNo' => trim((string)($ewbData['veh_no'] ?? '')),
                //'VehType' => trim((string)($ewbData['veh_type'] ?? '')),
                'TransDocNo' => trim((string)($ewbData['trans_doc_no'] ?? $invoiceNo)),
                'TransDocDt' => trim((string)($ewbData['trn_doc_dt'] ?? date('d/m/Y'))),
            ];

            $ewbResponse = $alankitClient->generateEwb($ewbPayload, $accessToken, $decryptedSek);
            $infoDtls = null;
            if (is_array($ewbResponse) && array_key_exists('InfoDtls', $ewbResponse)) {
                $infoDtls = is_array($ewbResponse['InfoDtls']) ? json_encode($ewbResponse['InfoDtls']) : (string)$ewbResponse['InfoDtls'];
            }

            $ewbNo = isset($ewbResponse['EwbNo']) ? (string)$ewbResponse['EwbNo'] : null;
            $genGstin = isset($ewbResponse['GenGstin']) ? (string)$ewbResponse['GenGstin'] : null;
            $ewbDate = $this->normalizeDbDateTime($ewbResponse['EwbDt'] ?? null);
            $ewbValidTill = $this->normalizeDbDateTime($ewbResponse['EwbValidTill'] ?? null);
            $ewbStatus = !empty($ewbNo) ? 'generated' : 'failed';

            $errorMessage = null;
            if ($ewbStatus !== 'generated') {
                $errorMessage = trim((string)($ewbResponse['message'] ?? $ewbResponse['ErrorMessage'] ?? 'EWB regeneration failed'));
            }

            $this->updateEwbStatus(
                $invoiceId,
                $ewbStatus,
                $errorMessage,
                $ewbPayload,
                $ewbResponse,
                $ewbNo,
                trim((string)($ewbData['veh_no'] ?? '')),
                trim((string)($ewbData['veh_type'] ?? '')),
                $ewbNo,
                $ewbDate,
                $ewbValidTill,
                $genGstin,
                $infoDtls
            );

            if ($ewbStatus === 'generated') {
                return [
                    'status' => true,
                    'message' => 'E-way bill regenerated successfully',
                    'ewb' => (string)$ewbNo,
                    'response' => is_array($ewbResponse) ? $ewbResponse : [],
                ];
            }

            $this->lastError = $errorMessage ?: 'E-way bill regeneration failed';
            return [
                'status' => false,
                'message' => $this->lastError,
                'response' => is_array($ewbResponse) ? $ewbResponse : [],
            ];
        } catch (\Throwable $e) {
            $this->lastError = 'Exception: ' . $e->getMessage();
            error_log("Domestic EWB regenerate exception for invoice #$invoiceId: " . $this->lastError);
            return [
                'status' => false,
                'message' => $this->lastError,
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
        $buyerDisplayName = trim((string)($customer['first_name'] ?? ''));
        if ($buyerDisplayName === '') {
            $buyerDisplayName = 'Buyer';
        }
        $buyerTradeName = trim((string)($customer['trade_name'] ?? ''));
        if ($buyerTradeName === '') {
            $buyerTradeName = $buyerDisplayName;
        }
        
        // Determine buyer and shipping details
        $isBusiness = (trim($customer['country'] ?? '') === 'IN');
        //$buyerGstin = $isBusiness ? ($customer['gstin'] ?? '') : 'URP';
        $buyerGstin = '07AAACE1288P2Z8'; // Hardcoded GSTIN for testing; replace with actual logic as needed

        $buyerStateCode = $isBusiness ? trim($customer['state_code']) : '';
        $buyerPincode = $isBusiness ? (trim($customer['zipcode'] ?? '') ?: '000000') : '999999';
        $shippingState = $isBusiness ? trim($customer['shipping_state'] ?? '') : trim($customer['state'] ?? '');
        $shippingPincode = $isBusiness ? (trim($customer['shipping_zipcode'] ?? '') ?: trim($customer['zipcode'])) : '999999';
        $shippingStateCode = $isBusiness ? trim($customer['shipping_state_code']) : trim($customer['state_code']);
        return [
            'Version' => '1.1',
            'TranDtls' => [
                'TaxSch' => 'GST',
                'SupTyp' => $isBusiness ? 'B2B' : 'B2C',
                'RegRev' => 'N',
                //'EcmGstin' => $firm['gst'] ?? '',
                //'EcmGstin' => $alankitConfig['gstin'] ?? '07AGAPA5363L002',
                'IgstOnIntra' => 'N'
            ],
            'DocDtls' => [
                'Typ' => 'INV',
                'No' => (string)$invoiceNumber,
                'Dt' => $invoice['invoice_date'] ? date('d/m/Y', strtotime($invoice['invoice_date'])) : date('d/m/Y')
            ],
            'SellerDtls' => [
                'Gstin' => $alankitConfig['gstin'] ?? '07AGAPA5363L002',
                'LglNm' => $firm['firm_name'] ?? '',
                'TrdNm' => $firm['firm_name'] ?? '',
                'Addr1' => $firm['address'] ?? '',
                'Loc' => $firm['city'] ?? '',
                'Pin' => (int)($firm['pin'] ?? 0),
                'Stcd' => (string)($firm['state_code'] ?? ''),
                'Ph' => $firm['phone'] ?? '',
                'Em' => $firm['email'] ?? ''
            ],
            'BuyerDtls' => [
                'Gstin' => $buyerGstin,
                'LglNm' => $buyerDisplayName,
                'TrdNm' => $buyerTradeName,
                'Pos' => $buyerStateCode,
                'Addr1' => $buyerAddress,
                'Loc' => $customer['city'] ?? '',
                'Pin' => (int)$buyerPincode,
                'Stcd' => (string)$buyerStateCode,
                'Ph' => $customer['mobile'] ?? '',
                'Em' => $customer['email'] ?? ''
            ],
            // 'ShipDtls' => [
            //     'Gstin' => $buyerGstin,
            //     'LglNm' => $buyerDisplayName,
            //     'TrdNm' => $buyerTradeName,
            //     'Addr1' => $shippingAddress ?: $buyerAddress,
            //     'Loc' => $customer['city'] ?? '',
            //     'Pin' => (int)$shippingPincode,
            //     'Stcd' => (string)$shippingStateCode
            // ],
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
                //'TransId' => substr(preg_replace('/\s+/', '', (string)($ewbData['trans_id'] ?? '')), 0, 15),
                //'TransName' => (string)($ewbData['trans_name'] ?? ''),
                'Distance' => (int)($ewbData['distance'] ?? 100),
                'TransDocNo' => (string)$invoiceNumber,
                'TransDocDt' => (string)($ewbData['trn_doc_dt'] ?? date('d/m/Y')),
                //'VehNo' => (string)($ewbData['veh_no'] ?? ''),
                //'VehType' => (string)($ewbData['veh_type'] ?? 'R'),
                'VehNo' => 'ka123456', // Hardcoded for testing; replace with actual logic as needed
                'VehType' => 'R', // Hardcoded for testing; replace with actual logic as needed
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
    private function updateEwbStatus($invoiceId, $status, $error = null, $payload = null, $response = null, $ewb = null, $vehNo = null, $vehType = null, $ewbNo = null, $ewbDate = null, $ewbValidTill = null, $genGstin = null, $infoDtls = null) {
        $query = "UPDATE vp_domestic_ewb_irn
                  SET ewb_status = ?,
                      ewb_error = ?,
                      ewb_payload = ?,
                      ewb_response = ?,
                      ewb = ?,
                      veh_no = ?,
                      veh_type = ?,
                      ewb_no = ?,
                      ewb_date = ?,
                      ewb_valid_till = ?,
                      gen_gstin = ?,
                      info_dtls = ?,
                      ewb_generated_at = NOW()
                  WHERE vp_invoices_id = ?";
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            error_log("Failed to prepare statement: " . $this->db->error);
            return false;
        }
        $payloadJson = $payload ? json_encode($payload) : null;
        $responseJson = $response ? json_encode($response) : null;
        $stmt->bind_param("ssssssssssssi", $status, $error, $payloadJson, $responseJson, $ewb, $vehNo, $vehType, $ewbNo, $ewbDate, $ewbValidTill, $genGstin, $infoDtls, $invoiceId);
        return $stmt->execute();
    }

    /**
     * Add info_dtls column on older databases if missing.
     */
    private function ensureInfoDtlsColumn() {
        if ($this->infoDtlsColumnChecked) {
            return;
        }

        $this->infoDtlsColumnChecked = true;
        $check = $this->db->query("SHOW COLUMNS FROM vp_domestic_ewb_irn LIKE 'info_dtls'");
        if ($check && $check->num_rows > 0) {
            return;
        }

        $alter = "ALTER TABLE vp_domestic_ewb_irn ADD COLUMN info_dtls LONGTEXT NULL COMMENT 'InfoDtls from Alankit IRN/EWB response' AFTER gen_gstin";
        if (!$this->db->query($alter)) {
            error_log("Domestic EWB: Failed to add info_dtls column: " . $this->db->error);
        }
    }

    /**
     * Persist only InfoDtls for special responses like DUPIRN without touching IRN/EWB status fields.
     */
    private function updateInfoDtlsOnly($invoiceId, $infoDtls) {
        $query = "UPDATE vp_domestic_ewb_irn SET info_dtls = ? WHERE vp_invoices_id = ?";
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            error_log("Failed to prepare info_dtls update: " . $this->db->error);
            return false;
        }
        $stmt->bind_param("si", $infoDtls, $invoiceId);
        return $stmt->execute();
    }

    /**
     * Normalize various EWB datetime formats into MySQL DATETIME (Y-m-d H:i:s).
     */
    private function normalizeDbDateTime($raw): ?string {
        if ($raw === null) {
            return null;
        }
        $value = trim((string)$raw);
        if ($value === '') {
            return null;
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $ts);
    }
}
?>

