<?php
/**
 * Alankit IRN & E-Way Bill API Client
 * For generating E-invoice IRN and E-Way Bill via Eraahi gateway
 * API Documentation: https://developers.eraahi.com
 */
class AlankitIrnClient {
    private $username;
    private $password;
    private $subscriptionKey;
    private $appKey;
    private $gstin;
    private $baseUrl;
    private $token;
    private $sek; // Symmetric Encryption Key from Alankit response
    private $tokenExpiresAt; // Token expiration timestamp
    private $forceRefreshAccessToken; // Force refresh 10 minutes before expiry
    private $lastError;

    // API Endpoints from Eraahi Gateway
    const AUTH_ENDPOINT = '/eInvoiceGateway/eivital/v1.04/auth';
    
    // IRN (Invoice Registration Number) Endpoints
    const IRN_GENERATE_ENDPOINT = '/eInvoiceGateway/eicore/v1.03/Invoice';
    const IRN_CANCEL_ENDPOINT = '/eInvoiceGateway/eicore/v1.03/Invoice/Cancel';
    const IRN_GET_ENDPOINT = '/eInvoiceGateway/eicore/v1.03/Invoice/irn/';
    
    // E-Way Bill Endpoints
    const EWAYBILL_ENDPOINT = '/eInvoiceGateway/eiewb/v1.03/ewaybill';
    const EWAYBILL_CANCEL_ENDPOINT = '/eInvoiceGateway/eiewb/v1.03/ewaybill/cancel';
    const EWAYBILL_GET_ENDPOINT = '/eInvoiceGateway/eiewb/v1.03/ewaybill/';

    /**
     * Constructor
     * @param string $username API username
     * @param string $password API password
     * @param string $subscriptionKey API subscription key for headers
     * @param string $appKey 32-character unique App Key from Alankit (alphanumeric string)
     * @param string $gstin Company GSTIN (required for auth header)
     * @param boolean $forceRefreshAccessToken Refresh token 10 minutes before expiry (default: true)
     */
    public function __construct($username, $password, $subscriptionKey, $appKey, $gstin, $forceRefreshAccessToken = true) {
        // Validate AppKey is exactly 32 characters
        // if (strlen($appKey) !== 32) {
        //     throw new Exception(
        //         "Invalid AppKey length. Expected 32 characters, got " . strlen($appKey) . " characters. " .
        //         "AppKey must be a 32-character alphanumeric string from Alankit configuration."
        //     );
        // }
        
        error_log("Alankit Client: AppKey validated (32 characters, will be hashed to 32 bytes using SHA-256)");
        
        $this->username = $username;
        $this->password = $password;
        $this->subscriptionKey = $subscriptionKey;
        $this->appKey = $appKey;
        $this->gstin = $gstin;
        $this->forceRefreshAccessToken = $forceRefreshAccessToken;
        $this->baseUrl = "https://developers.eraahi.com";
        $this->tokenExpiresAt = null;
        $this->sek = null;
    }

    /**
     * Authenticate with Alankit/Eraahi API
     * Uses username, password, AppKey, and RSA encryption to get Bearer token
     * @return boolean
     */
    public function authenticate() {
        //echo "Alankit Auth: Starting authentication process...\n";
        // Check if token is still valid and doesn't need refresh
        if ($this->isTokenValid() && !$this->shouldRefreshToken()) {
            error_log("Alankit Auth: Using existing valid token (expires at " . date('Y-m-d H:i:s', $this->tokenExpiresAt) . ")");
            return true;
        }

        // Load RSA encryptor for secure payload encryption
        require_once dirname(__FILE__) . '/../../helpers/RsaEncryptor.php';
        //echo "Alankit Auth: Loaded RSA encryptor.\n";
        $url = $this->baseUrl . self::AUTH_ENDPOINT;
        
        // Build credentials payload with ForceRefreshAccessToken
        $credentials = [
            "UserName" => $this->username,
            "Password" => $this->password,
            "AppKey" => $this->appKey,
            "ForceRefreshAccessToken" => $this->forceRefreshAccessToken
        ];
        
        // Encrypt payload using RSA public key
        // Use realpath() to normalize path separators on Windows
        $publicKeyPath = realpath(dirname(__FILE__) . '/../../docs/public.txt');
        if (!$publicKeyPath) {
            $this->lastError = 'Public key file not found at expected path';
            error_log("Alankit Auth Error1: " . $this->lastError);
            return false;
        }
        $encryptedData = RsaEncryptor::securePayload($credentials, $publicKeyPath);
        
        if (!$encryptedData) {
            $this->lastError = 'Failed to encrypt authentication payload. Check public key file.' . $publicKeyPath;
            error_log("Alankit Auth Error2: " . $this->lastError);
            return false;
        }
        
        // Prepare request with encrypted data
        $data = [
            "Data" => $encryptedData
        ];

        $response = $this->sendRequest($url, $data, false);
        
        if ($response && isset($response['Status']) && $response['Status'] === 1) {
            // Extract token and other details from response
            $responseData = $response['Data'] ?? [];
            
            $this->token = $responseData['AuthToken'] ?? null;
            $this->sek = $responseData['Sek'] ?? null; // Symmetric Encryption Key
            
            // Parse TokenExpiry: format is "YYYY-MM-DD HH:MM:SS"
            if (isset($responseData['TokenExpiry'])) {
                try {
                    $expiryDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $responseData['TokenExpiry']);
                    if ($expiryDateTime) {
                        $this->tokenExpiresAt = $expiryDateTime->getTimestamp();
                    } else {
                        // Fallback to 1 hour from now
                        $this->tokenExpiresAt = time() + 3600;
                        error_log("Alankit Auth: Could not parse TokenExpiry, using default 1 hour");
                    }
                } catch (Exception $e) {
                    $this->tokenExpiresAt = time() + 3600;
                    error_log("Alankit Auth: Exception parsing TokenExpiry - " . $e->getMessage());
                }
            } else {
                // Default to 1 hour if expiry not provided
                $this->tokenExpiresAt = time() + 3600;
            }
            
            if (!empty($this->token)) {
                error_log("Alankit Auth: Successfully authenticated with Eraahi API (expires at " . 
                    date('Y-m-d H:i:s', $this->tokenExpiresAt) . ", Sek received: " . 
                    (!empty($this->sek) ? 'Yes' : 'No') . ")");
                return true;
            }
        }
        
        $errorDetails = $response['ErrorDetails'] ?? $response['error'] ?? 'Authentication failed';
        $this->lastError = is_array($errorDetails) ? json_encode($errorDetails) : $errorDetails;
        error_log("Alankit Auth Error3: " . $this->lastError);
        return false;
    }

    /**
     * Check if current token is valid (not expired)
     * @return boolean True if token exists and hasn't expired
     */
    private function isTokenValid() {
        return !empty($this->token) && !empty($this->tokenExpiresAt) && time() < $this->tokenExpiresAt;
    }

    /**
     * Check if token should be refreshed (10 minutes before expiry)
     * Used with ForceRefreshAccessToken setting
     * @return boolean True if token needs refresh
     */
    private function shouldRefreshToken() {
        if (!$this->forceRefreshAccessToken) {
            return false; // Refresh disabled
        }

        if (empty($this->tokenExpiresAt)) {
            return true; // No expiry tracked, should refresh
        }

        // Refresh if token expires within 10 minutes (600 seconds)
        $refreshThreshold = time() + 600;
        $shouldRefresh = $this->tokenExpiresAt <= $refreshThreshold;
        
        if ($shouldRefresh) {
            error_log("Alankit: Token refresh needed. Expires at: " . date('Y-m-d H:i:s', $this->tokenExpiresAt) . 
                     ", Current time: " . date('Y-m-d H:i:s', time()));
        }
        
        return $shouldRefresh;
    }

    /**
     * Generate IRN for E-invoice
     * Implements full encryption flow: Decrypt Sek -> Prepare Payload -> AES Encrypt -> Send
     * @param array $invoiceData Invoice data in standard format
     * @return array Response from API with IRN details
     */
    public function generateIrn($invoiceData) {
        // Always refresh token before IRN generation (Alankit API requirement)
        error_log("Alankit IRN: Authenticating for IRN generation (will always refresh for safety)");
        if (!$this->authenticate()) {
            return ['status' => false, 'message' => 'Authentication failed: ' . $this->lastError];
        }
        
        error_log("Alankit IRN: Token after auth: " . substr($this->token, 0, 20) . "... (expires: " . 
                 date('Y-m-d H:i:s', $this->tokenExpiresAt) . ")");

        // Step 1: Decrypt Sek using AppKey
        if (empty($this->sek)) {
            $this->lastError = 'Sek not available. Authentication may have failed.';
            error_log("Alankit IRN Error: " . $this->lastError);
            return ['status' => false, 'message' => $this->lastError];
        }
        
        $decryptedSek = $this->decryptSek($this->sek, $this->appKey);
        if (!$decryptedSek) {
            $this->lastError = 'Failed to decrypt Sek using AppKey';
            error_log("Alankit IRN Error: " . $this->lastError);
            return ['status' => false, 'message' => $this->lastError];
        }
        error_log("Alankit IRN: Sek decrypted successfully");

        // Step 2: Prepare IRN payload in Alankit format
        $irnPayload = $this->prepareIrnPayload($invoiceData);
        echo "Alankit IRN: Prepared payload for IRN generation:\n";
        print_r($irnPayload); // Debug: Log the prepared payload before encryption
        // Step 3: Base64 encode the JSON payload
        $jsonPayload = json_encode($irnPayload);
        $base64Payload = base64_encode($jsonPayload);
        error_log("Alankit IRN: Payload prepared (" . strlen($base64Payload) . " bytes after Base64)");
        
        // Step 4: Encrypt with decrypted Sek using AES-256 ECB
        $encryptedPayload = $this->encryptWithSek($base64Payload, $decryptedSek);
        if (!$encryptedPayload) {
            $this->lastError = 'Failed to encrypt payload with Sek';
            error_log("Alankit IRN Error: " . $this->lastError);
            return ['status' => false, 'message' => $this->lastError];
        }
        
        // Step 5: Send encrypted request
        $url = $this->baseUrl . self::IRN_GENERATE_ENDPOINT;
        $requestData = ['Data' => $encryptedPayload];
        
        error_log("Alankit IRN: Sending request with token: " . substr($this->token, 0, 20) . "...");
        $response = $this->sendRequest($url, $requestData, true, 'POST', $this->username);
        error_log("Alankit IRN: API response received. Status: " . ($response['Status'] ?? 'unknown'));
        echo "url: " . $url . "\n";

        // Step 6: Decrypt response if needed and extract IRN
        if ($response && isset($response['Status']) && $response['Status'] === 1) {
            // Response Data is encrypted, decrypt it
            $responseData = $response['Data'] ?? null;
            if ($responseData) {
                $decryptedResponse = $this->decryptWithSek($responseData, $decryptedSek);
                if ($decryptedResponse) {
                    $decryptedData = json_decode($decryptedResponse, true);
                } else {
                    $decryptedData = $response;
                }
            } else {
                $decryptedData = $response;
            }
            
            return [
                'status' => true,
                'irn' => $decryptedData['Irn'] ?? $decryptedData['irn'] ?? null,
                'ack_number' => $decryptedData['AckNo'] ?? $decryptedData['ack_number'] ?? null,
                'ack_date' => $decryptedData['AckDt'] ?? $decryptedData['ack_date'] ?? null,
                'signed_invoice' => $decryptedData['SignedInvoice'] ?? $decryptedData['signed_invoice'] ?? null,
                'qr_code' => $decryptedData['QRCode'] ?? $decryptedData['qr_code'] ?? null,
                'response' => $decryptedData
            ];
        }
        
        $errorDetails = $response['ErrorDetails'] ?? $response['error'] ?? $response['message'] ?? 'IRN generation failed';
        $this->lastError = is_array($errorDetails) ? json_encode($errorDetails) : $errorDetails;
        error_log("Alankit IRN Generation Error: " . $this->lastError);
        return [
            'status' => false,
            'message' => $this->lastError,
            'response' => $response
        ];
    }

    /**
     * Decrypt Sek using AppKey (AES-256-CTR mode)
     * Uses SHA-256 hash of 32-character AppKey with zero IV
     * @param string $encryptedSek Base64 encoded encrypted symmetric key
     * @param string $appKey 32-character unique App Key from config
     * @return string|false Decrypted Sek (Base64 encoded) or false on failure
     */
    private function decryptSek($encryptedSek, $appKey) {
        try {
            // Decode Sek from Base64 (comes from API as Base64)
            $encryptedData = base64_decode($encryptedSek, true);
            
            if ($encryptedData === false) {
                error_log("Alankit IRN: Failed to base64 decode Sek");
                return false;
            }
            
            error_log("Alankit IRN: Decrypting Sek (" . $encryptedData . ") with AppKey (hashed to 32 bytes)");
            error_log("Alankit IRN: AppKey used for decryption (original): " . $appKey);
            
            // Use SHA-256 hash of AppKey as decryption key (32 bytes)
            $keyBytes = hash('sha256', $appKey, true);
            
            // Use AES-256-CTR with zero IV (tested and verified working)
            $iv = str_repeat("\0", 16);
            
            $decrypted = openssl_decrypt(
                $encryptedData,
                'aes-256-ctr',
                $keyBytes,
                OPENSSL_RAW_DATA,
                $iv
            );
            
            if ($decrypted !== false) {
                error_log("Alankit IRN: Sek decrypted successfully length: " . strlen($decrypted) . " bytes)");
                error_log("Alankit IRN: Sek decrypted content (Base64): " . base64_encode($decrypted));
                // Return as Base64 for use in encryptWithSek
                return base64_encode($decrypted);
            } else {
                error_log("Alankit IRN: Sek decryption failed - " . openssl_error_string());
                return false;
            }
        } catch (Exception $e) {
            error_log("Alankit IRN: Exception decrypting Sek - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Encrypt payload using decrypted Sek (AES-256 ECB with PKCS7 padding)
     * @param string $base64Payload Base64 encoded JSON payload
     * @param string $decryptedSek Base64 encoded decrypted symmetric key
     * @return string|false Encrypted payload (Base64 encoded) or false on failure
     */
    private function encryptWithSek($base64Payload, $decryptedSek) {
        try {
            // Decode inputs
            $plaintext = base64_decode($base64Payload);
            $keyBytes = base64_decode($decryptedSek);
            
            if ($plaintext === false || $keyBytes === false) {
                error_log("Alankit IRN: Failed to decode payload or Sek from Base64");
                return false;
            }
            
            // AES-256 ECB encryption
            $encrypted = openssl_encrypt(
                $plaintext,
                'AES-256-ECB',
                $keyBytes,
                OPENSSL_RAW_DATA
            );
            
            if ($encrypted === false) {
                error_log("Alankit IRN: OpenSSL encryption failed. Error: " . openssl_error_string());
                return false;
            }
            
            // Return as Base64
            $encryptedData = base64_encode($encrypted);
            error_log("Alankit IRN: Payload encrypted successfully (" . strlen($encryptedData) . " bytes)");
            return $encryptedData;
        } catch (Exception $e) {
            error_log("Alankit IRN: Exception encrypting payload - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Decrypt response using Sek
     * @param string $encryptedData Base64 encoded encrypted response
     * @param string $decryptedSek Base64 encoded decrypted symmetric key
     * @return string|false Decrypted response (JSON string) or false on failure
     */
    private function decryptWithSek($encryptedData, $decryptedSek) {
        try {
            $encrypted = base64_decode($encryptedData);
            $keyBytes = base64_decode($decryptedSek);
            
            if ($encrypted === false || $keyBytes === false) {
                return false;
            }
            
            $decrypted = openssl_decrypt(
                $encrypted,
                'AES-256-ECB',
                $keyBytes,
                OPENSSL_RAW_DATA
            );
            
            return $decrypted !== false ? $decrypted : false;
        } catch (Exception $e) {
            error_log("Alankit IRN: Exception decrypting response - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Prepare IRN payload in official Alankit/Eraahi format
     * Maps internal invoice data to Alankit's required JSON structure
     * @param array $invoice Invoice data from database
     * @return array Formatted payload matching Alankit specifications
     */
    private function prepareIrnPayload($invoice) {
        // Format line items
        $itemList = [];
        if (!empty($invoice['line_items']) && is_array($invoice['line_items'])) {
            foreach ($invoice['line_items'] as $idx => $item) {
                $itemList[] = [
                    'SlNo' => (string)($idx + 1),
                    'PrdDesc' => $item['item_name'] ?? '',
                    'IsServc' => 'N',
                    'HsnCd' => $item['hsn'] ?? '',
                    'Qty' => (float)($item['quantity'] ?? 0),
                    'Unit' => $item['unit'] ?? 'NOS',
                    'UnitPrice' => (float)($item['unit_price'] ?? 0),
                    'TotAmt' => (float)(($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0)),
                    'AssAmt' => (float)(($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0)),
                    'GstRt' => (int)($item['tax_rate'] ?? 0),
                    'IgstAmt' => (float)($item['tax_amount'] ?? 0),
                    'CgstAmt' => 0,
                    'SgstAmt' => 0
                ];
            }
        }

        // Build Alankit IRN payload
        return [
            'Version' => '1.1',
            'TranDtls' => [
                'TaxSch' => 'GST',
                'SupTyp' => $invoice['buyer_gstin'] ? 'B2B' : 'B2C',
                'RegRev' => 'N',
                'EcmGstin' => null,
                'IgstOnIntra' => 'N'
            ],
            'DocDtls' => [
                'Typ' => 'INV',
                'No' => $invoice['invoice_number'] ?? '',
                'Dt' => $invoice['invoice_date'] ?? date('d/m/Y')
            ],
            'SellerDtls' => [
                'Gstin' => $invoice['seller_gstin'] ?? '',
                'LglNm' => $invoice['seller_name'] ?? '',
                'TrdNm' => $invoice['seller_name'] ?? '',
                'Addr1' => $invoice['seller_address'] ?? '',
                'Loc' => $invoice['seller_city'] ?? '',
                'Pin' => (int)($invoice['seller_pincode'] ?? 0),
                'Stcd' => $invoice['seller_state_code'] ?? '',
                'Ph' => $invoice['seller_phone'] ?? '',
                'Em' => $invoice['seller_email'] ?? ''
            ],
            'BuyerDtls' => [
                'Gstin' => $invoice['buyer_gstin'] ?? '',
                'LglNm' => $invoice['buyer_name'] ?? '',
                'TrdNm' => $invoice['buyer_name'] ?? '',
                'Pos' => $invoice['buyer_state_code'] ?? '00',
                'Addr1' => $invoice['buyer_address'] ?? '',
                'Loc' => $invoice['buyer_city'] ?? '',
                'Pin' => (int)($invoice['buyer_pincode'] ?? 0),
                'Stcd' => $invoice['buyer_state_code'] ?? '',
                'Ph' => $invoice['buyer_phone'] ?? '',
                'Em' => $invoice['buyer_email'] ?? ''
            ],
            'ShipDtls' => [
                'Gstin' => $invoice['buyer_gstin'] ?? '',
                'LglNm' => $invoice['buyer_name'] ?? '',
                'TrdNm' => $invoice['buyer_name'] ?? '',
                'Addr1' => $invoice['shipping_address'] ?? '',
                'Loc' => $invoice['shipping_city'] ?? '',
                'Pin' => (int)($invoice['shipping_pincode'] ?? 0),
                'Stcd' => $invoice['shipping_state'] ?? ''
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
            'EwbDtls' => [
                'TransMode' => $invoice['transport_mode'] ?? '1',
                'VehNo' => $invoice['vehicle_number'] ?? '',
                'VehType' => $invoice['vehicle_type'] ?? 'R'
            ]
        ];
    }

    /**
     * Prepare invoice data for Eraahi/Alankit IRN API format
     * Transforms invoice data to match Eraahi E-invoice API requirements
     * @param array $invoice Invoice data from invoices table
     * @return array Formatted payload for IRN generation
     */
    private function prepareInvoicePayload($invoice) {
        // Format line items for Eraahi API
        $lineItems = [];
        if (!empty($invoice['line_items']) && is_array($invoice['line_items'])) {
            foreach ($invoice['line_items'] as $idx => $item) {
                $lineItems[] = [
                    'SlNo' => $idx + 1,
                    'ItemCode' => $item['item_code'] ?? '',
                    'ItemName' => $item['item_name'] ?? '',
                    'Qty' => $item['quantity'] ?? 0,
                    'Unit' => $item['unit'] ?? 'NOS',
                    'UnitPrice' => $item['unit_price'] ?? 0,
                    'Amount' => ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0),
                    'HSNCode' => $item['hsn'] ?? '',
                    'TaxRate' => $item['tax_rate'] ?? 0,
                    'GSTAmount' => $item['tax_amount'] ?? 0,
                    'TotalAmount' => $item['total'] ?? 0
                ];
            }
        }

        // Prepare payload for Eraahi E-invoice API
        return [
            'InvoiceNumber' => $invoice['invoice_number'] ?? '',
            'InvoiceDate' => $invoice['invoice_date'] ?? date('d/m/Y'),
            'DocumentType' => 'INV',
            'InvoiceType' => 'B2B',
            'Currency' => $invoice['currency'] ?? 'INR',
            
            // Seller/Firm Details
            'SellerGSTIN' => $invoice['seller_gstin'] ?? '',
            'SellerName' => $invoice['seller_name'] ?? '',
            'SellerCompanyName' => $invoice['seller_name'] ?? '',
            'SellerAddress' => $invoice['seller_address'] ?? '',
            'SellerCity' => $invoice['seller_city'] ?? '',
            'SellerState' => $invoice['seller_state'] ?? '',
            'SellerStateCode' => $invoice['seller_state_code'] ?? '',
            'SellerCountry' => $invoice['seller_country'] ?? 'IN',
            'SellerPinCode' => $invoice['seller_pincode'] ?? '',
            
            // Buyer/Customer Details
            'BuyerName' => $invoice['buyer_name'] ?? '',
            'BuyerCompanyName' => $invoice['buyer_name'] ?? '',
            'BuyerAddress' => $invoice['buyer_address'] ?? '',
            'BuyerCity' => $invoice['buyer_city'] ?? '',
            'BuyerState' => $invoice['buyer_state'] ?? '',
            'BuyerStateCode' => $invoice['buyer_state_code'] ?? '',
            'BuyerCountry' => $invoice['buyer_country'] ?? 'IN',
            'BuyerPinCode' => $invoice['buyer_pincode'] ?? '',
            'BuyerEmail' => $invoice['buyer_email'] ?? '',
            'BuyerPhoneNumber' => $invoice['buyer_phone'] ?? '',
            
            // Shipping Details
            'ShippingName' => $invoice['shipping_name'] ?? $invoice['buyer_name'] ?? '',
            'ShippingAddress' => $invoice['shipping_address'] ?? $invoice['buyer_address'] ?? '',
            'ShippingCity' => $invoice['shipping_city'] ?? $invoice['buyer_city'] ?? '',
            'ShippingState' => $invoice['shipping_state'] ?? $invoice['buyer_state'] ?? '',
            'ShippingStateCode' => $invoice['shipping_state_code'] ?? '',
            'ShippingCountry' => $invoice['shipping_country'] ?? 'IN',
            'ShippingPinCode' => $invoice['shipping_pincode'] ?? '',
            
            // Line Items
            'LineItems' => $lineItems,
            
            // Amount Details
            'Subtotal' => $invoice['subtotal'] ?? 0,
            'TaxAmount' => $invoice['tax_amount'] ?? 0,
            'DiscountAmount' => $invoice['discount_amount'] ?? 0,
            'TotalAmount' => $invoice['total_amount'] ?? 0,
            
            // Additional Details
            'ReferenceNumber' => $invoice['reference_number'] ?? $invoice['invoice_number'] ?? '',
            'Notes' => $invoice['notes'] ?? '',
            'TransportMode' => $invoice['transport_mode'] ?? 'ROAD',
            'VehicleNumber' => $invoice['vehicle_number'] ?? '',
        ];
    }

    /**
     * Prepare E-Way Bill data for Eraahi/Alankit API format
     * Transforms invoice/shipment data to match Eraahi E-Way Bill API requirements
     * @param array $ewaybill E-way bill data (typically from invoice with IRN)
     * @return array Formatted payload for E-Way Bill generation
     */
    private function prepareEWayBillPayload($ewaybill) {
        // Format line items for E-Way Bill
        $lineItems = [];
        if (!empty($ewaybill['line_items']) && is_array($ewaybill['line_items'])) {
            foreach ($ewaybill['line_items'] as $idx => $item) {
                $lineItems[] = [
                    'SlNo' => $idx + 1,
                    'ItemCode' => $item['item_code'] ?? '',
                    'ItemName' => $item['item_name'] ?? '',
                    'HSNCode' => $item['hsn'] ?? '',
                    'Qty' => $item['quantity'] ?? 0,
                    'Unit' => $item['unit'] ?? 'NOS',
                    'TaxRate' => $item['tax_rate'] ?? 0
                ];
            }
        }

        // Prepare payload for Eraahi E-Way Bill API
        return [
            // Document Reference
            'DocNo' => $ewaybill['invoice_number'] ?? '',
            'DocDt' => $ewaybill['invoice_date'] ?? date('d/m/Y'),
            'DocType' => 'INV', // Invoice type
            'Irn' => $ewaybill['irn'] ?? '', // IRN number from generated invoice
            
            // Consignor (Shipper) Details
            'FromGSTIN' => $ewaybill['seller_gstin'] ?? '',
            'FromTradeName' => $ewaybill['seller_name'] ?? '',
            'FromAddr1' => $ewaybill['seller_address'] ?? '',
            'FromCity' => $ewaybill['seller_city'] ?? '',
            'FromState' => $ewaybill['seller_state'] ?? '',
            'FromStateCode' => $ewaybill['seller_state_code'] ?? '',
            'FromPincode' => $ewaybill['seller_pincode'] ?? '',
            
            // Consignee (Recipient) Details
            'ToGSTIN' => $ewaybill['buyer_gstin'] ?? '',
            'ToTradeName' => $ewaybill['buyer_name'] ?? '',
            'ToAddr1' => $ewaybill['buyer_address'] ?? '',
            'ToCity' => $ewaybill['buyer_city'] ?? '',
            'ToState' => $ewaybill['buyer_state'] ?? '',
            'ToStateCode' => $ewaybill['buyer_state_code'] ?? '',
            'ToPincode' => $ewaybill['buyer_pincode'] ?? '',
            
            // Goods Details
            'ItemList' => $lineItems,
            
            // Transport Details
            'TransMode' => $ewaybill['transport_mode'] ?? 'ROAD', // ROAD, RAIL, AIR, SHIP
            'TransDocNo' => $ewaybill['transport_doc_no'] ?? '',
            'TransDocDt' => $ewaybill['transport_doc_date'] ?? date('d/m/Y'),
            'VehicleNo' => $ewaybill['vehicle_number'] ?? '',
            'VehicleType' => $ewaybill['vehicle_type'] ?? 'REGULAR',
            
            // Amount Details
            'SubTotal' => $ewaybill['subtotal'] ?? 0,
            'TaxAmount' => $ewaybill['tax_amount'] ?? 0,
            'TotalValue' => $ewaybill['total_amount'] ?? 0
        ];
    }

    /**
     * Cancel IRN
     * @param string $irnNumber IRN number to cancel
     * @return array Response
     */
    public function cancelIrn($irnNumber) {
        if (empty($this->token) || !$this->isTokenValid()) {
            if (!$this->authenticate()) {
                return ['status' => false, 'message' => 'Authentication failed'];
            }
        } elseif ($this->shouldRefreshToken()) {
            error_log("Alankit IRN: Token expiring soon, refreshing proactively");
            if (!$this->authenticate()) {
                return ['status' => false, 'message' => 'Token refresh failed'];
            }
        }

        $url = $this->baseUrl . self::IRN_CANCEL_ENDPOINT;
        
        $data = [
            'Irn' => $irnNumber,
            'CnlRsn' => '1', // Cancellation Reason (1 = Duplicate)
            'CnlRem' => 'Cancellation requested' // Cancellation Remark
        ];

        $response = $this->sendRequest($url, $data, true);
        
        if ($response && isset($response['status'])) {
            error_log("Alankit IRN Cancellation response for IRN $irnNumber: " . json_encode($response));
            return $response;
        }
        
        error_log("Alankit IRN Cancellation failed for IRN $irnNumber");
        return ['status' => false, 'message' => 'Cancellation failed'];
    }

    /**
     * Get IRN details by IRN number
     * @param string $irnNumber IRN number
     * @return array Response with IRN details
     */
    public function getIrnStatus($irnNumber) {
        if (empty($this->token) || !$this->isTokenValid()) {
            if (!$this->authenticate()) {
                return ['status' => false, 'message' => 'Authentication failed'];
            }
        } elseif ($this->shouldRefreshToken()) {
            error_log("Alankit IRN: Token expiring soon, refreshing proactively");
            if (!$this->authenticate()) {
                return ['status' => false, 'message' => 'Token refresh failed'];
            }
        }

        $url = $this->baseUrl . self::IRN_GET_ENDPOINT . urlencode($irnNumber);
        
        $response = $this->sendRequest($url, [], true, 'GET');
        
        return $response ?: ['status' => false, 'message' => 'Failed to fetch IRN status'];
    }

    /**
     * Generate E-Way Bill for goods transport
     * @param array $ewaybillData E-way bill data in Eraahi format
     * @return array Response with E-way bill details
     */
    public function generateEWayBill($ewaybillData) {
        if (empty($this->token) || !$this->isTokenValid()) {
            if (!$this->authenticate()) {
                return ['status' => false, 'message' => 'Authentication failed: ' . $this->lastError];
            }
        } elseif ($this->shouldRefreshToken()) {
            error_log("Alankit E-Way Bill: Token expiring soon, refreshing proactively");
            if (!$this->authenticate()) {
                return ['status' => false, 'message' => 'Token refresh failed: ' . $this->lastError];
            }
        }

        $url = $this->baseUrl . self::EWAYBILL_ENDPOINT;
        
        // Format E-Way Bill data using helper function
        $formattedEWayBillData = $this->prepareEWayBillPayload($ewaybillData);
        
        $response = $this->sendRequest($url, $formattedEWayBillData, true);
        
        if ($response && isset($response['status']) && $response['status'] === true) {
            $data = $response['data'] ?? [];
            return [
                'status' => true,
                'eway_bill_no' => $data['EwbNo'] ?? $data['eway_bill_no'] ?? null,
                'eway_bill_date' => $data['EwbDt'] ?? $data['eway_bill_date'] ?? null,
                'eway_bill_validity' => $data['EwbValidityDays'] ?? $data['validity_days'] ?? null,
                'eway_bill_status' => $data['EwbStatus'] ?? $data['status'] ?? null,
                'response' => $response
            ];
        }
        
        $errorDetails = $response['message'] ?? $response['error'] ?? 'E-Way Bill generation failed';
        $this->lastError = is_array($errorDetails) ? json_encode($errorDetails) : $errorDetails;
        error_log("Alankit E-Way Bill Generation Error: " . $this->lastError);
        return [
            'status' => false,
            'message' => $this->lastError,
            'response' => $response
        ];
    }

    /**
     * Cancel E-Way Bill
     * @param string $ewayBillNo E-Way Bill number to cancel
     * @param string $reason Reason for cancellation
     * @return array Response
     */
    public function cancelEWayBill($ewayBillNo, $reason = 'Duplicate') {
        if (empty($this->token) || !$this->isTokenValid()) {
            if (!$this->authenticate()) {
                return ['status' => false, 'message' => 'Authentication failed'];
            }
        } elseif ($this->shouldRefreshToken()) {
            error_log("Alankit E-Way Bill: Token expiring soon, refreshing proactively");
            if (!$this->authenticate()) {
                return ['status' => false, 'message' => 'Token refresh failed'];
            }
        }

        $url = $this->baseUrl . self::EWAYBILL_CANCEL_ENDPOINT;
        
        $data = [
            'EwbNo' => $ewayBillNo,
            'CnlRsn' => '1', // Cancellation reason code
            'CnlRem' => $reason
        ];

        $response = $this->sendRequest($url, $data, true);
        
        if ($response && isset($response['status'])) {
            error_log("Alankit E-Way Bill Cancellation response for EwbNo $ewayBillNo: " . json_encode($response));
            return $response;
        }
        
        error_log("Alankit E-Way Bill Cancellation failed for EwbNo $ewayBillNo");
        return ['status' => false, 'message' => 'E-Way Bill cancellation failed'];
    }

    /**
     * Get E-Way Bill details by E-Way Bill number
     * @param string $ewayBillNo E-Way Bill number
     * @return array Response with E-Way Bill details
     */
    public function getEWayBillStatus($ewayBillNo) {
        if (empty($this->token) || !$this->isTokenValid()) {
            if (!$this->authenticate()) {
                return ['status' => false, 'message' => 'Authentication failed'];
            }
        } elseif ($this->shouldRefreshToken()) {
            error_log("Alankit E-Way Bill: Token expiring soon, refreshing proactively");
            if (!$this->authenticate()) {
                return ['status' => false, 'message' => 'Token refresh failed'];
            }
        }

        $url = $this->baseUrl . self::EWAYBILL_GET_ENDPOINT . urlencode($ewayBillNo);
        
        $response = $this->sendRequest($url, [], true, 'GET');
        
        return $response ?: ['status' => false, 'message' => 'Failed to fetch E-Way Bill status'];
    }

    /**
     * Send HTTP request to Eraahi/Alankit API
     * @param string $url API endpoint URL
     * @param array $data Request payload
     * @param boolean $auth Include authorization header
     * @param string $method HTTP method (POST, GET)
     * @param string $username Optional username for UserName header
     * @return array Decoded JSON response
     */
    private function sendRequest($url, $data = [], $auth = false, $method = 'POST', $username = null) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        // Set headers
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Ocp-Apim-Subscription-Key: ' . $this->subscriptionKey
        ];
        
        // Add GSTIN header for authentication requests
        if (strpos($url, self::AUTH_ENDPOINT) !== false) {
            $headers[] = 'Gstin: ' . $this->gstin;
        }
        
        // Add UserName and GSTIN headers for IRN requests
        if (strpos($url, self::IRN_GENERATE_ENDPOINT) !== false) {
            if ($username) {
                $headers[] = 'user_name: ' . $username;
            }
            // Also add GSTIN header for token validation
            $headers[] = 'Gstin: ' . $this->gstin;
        }
        
        // Add Bearer token if authenticated
        if ($auth && !empty($this->token)) {
            $headers[] = 'AuthToken: ' . $this->token;
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Set request method and data
        if ($method === 'GET') {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        } elseif ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
         //print_r($headers);
         //print_r($data);
         echo "Alankit API Request to $url returned HTTP $httpCode. Response:\n <pre>";
         print_r(json_decode($response, true));
        curl_close($ch);
        
        if ($curlError) {
            error_log("Alankit API cURL Error ($httpCode): $curlError for URL: $url");
            return [
                'status' => false,
                'message' => 'cURL Error: ' . $curlError,
                'http_code' => $httpCode
            ];
        }
        $decoded = json_decode($response, true);
        
        if ($httpCode >= 400) {
            error_log("Alankit API HTTP Error ($httpCode) for URL: $url. Response: " . substr($response, 0, 500));
            return [
                'status' => false,
                'message' => 'HTTP Error ' . $httpCode,
                'data' => $decoded
            ];
        }
        
        return $decoded;
    }

    /**
     * Get last error message
     * @return string
     */
    public function getLastError() {
        return $this->lastError;
    }

    /**
     * Set ForceRefreshAccessToken setting
     * @param boolean $forceRefresh Whether to force refresh token 10 minutes before expiry
     */
    public function setForceRefreshAccessToken($forceRefresh) {
        $this->forceRefreshAccessToken = (bool)$forceRefresh;
        error_log("Alankit: ForceRefreshAccessToken set to " . ($this->forceRefreshAccessToken ? 'true' : 'false'));
    }

    /**
     * Get current ForceRefreshAccessToken setting
     * @return boolean
     */
    public function getForceRefreshAccessToken() {
        return $this->forceRefreshAccessToken;
    }

    /**
     * Get token expiration timestamp
     * @return int Unix timestamp or null if not set
     */
    public function getTokenExpiresAt() {
        return $this->tokenExpiresAt;
    }

    /**
     * Get token expiration time in human-readable format
     * @return string Formatted date/time or 'Not set' if not available
     */
    public function getTokenExpirationTime() {
        return $this->tokenExpiresAt ? date('Y-m-d H:i:s', $this->tokenExpiresAt) : 'Not set';
    }

    /**
     * Get remaining token lifetime in seconds
     * @return int|null Seconds until expiry, or null if no token
     */
    public function getTokenRemainingLife() {
        if (empty($this->tokenExpiresAt)) {
            return null;
        }
        $remaining = $this->tokenExpiresAt - time();
        return $remaining > 0 ? $remaining : 0;
    }

    /**
     * Get Symmetric Encryption Key (Sek) from authentication response
     * @return string|null The Sek value or null if not set
     */
    public function getSek() {
        return $this->sek;
    }
}
