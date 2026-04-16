<?php 
class AlankitIrnNew {
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

   public function __construct($username, $password, $subscriptionKey, $appKey, $gstin, $forceRefreshAccessToken = true) {
       
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
   
    function generateRandomString($length = 32) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        //return $randomString;
        
        return base64_encode($randomString);
    }
    function authRequest(){
$RequestPayload = '{
    "UserName":"AL001",
    "Password":"Alankit@123",
    "AppKey":"'.$this->appKey.'",
    "ForceRefreshAccessToken": true
}';


// $publicKey ;


$publicKey = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEArxd93uLDs8HTPqcSPpxZ
rf0Dc29r3iPp0a8filjAyeX4RAH6lWm9qFt26CcE8ESYtmo1sVtswvs7VH4Bjg/F
DlRpd+MnAlXuxChij8/vjyAwE71ucMrmZhxM8rOSfPML8fniZ8trr3I4R2o4xWh6
no/xTUtZ02/yUEXbphw3DEuefzHEQnEF+quGji9pvGnPO6Krmnri9H4WPY0ysPQQ
Qd82bUZCk9XdhSZcW/am8wBulYokITRMVHlbRXqu1pOFmQMO5oSpyZU3pXbsx+Ox
IOc4EDX0WMa9aH4+snt18WAXVGwF2B4fmBk7AtmkFzrTmbpmyVqA3KO2IjzMZPw0
hQIDAQAB
-----END PUBLIC KEY-----';


$Base64RequestPayload = base64_encode(  $RequestPayload  );

$data =  $Base64RequestPayload;
  
openssl_public_encrypt($data, $encryptedData, $publicKey);
  
return base64_encode($encryptedData);  
    }
    /**
     * 
     * encryption is EncryptedSek which is auth response's "Sek" value
     * decryption_key is base64_decode($AppKey)
     */
    function decryptSek($encryption, $AppKey){
        $ciphering = "AES-256-ECB";
        $options = 0;
        $decryption_iv = '';
        $decryption_key =  base64_decode($AppKey);        
        $decryption=openssl_decrypt ($encryption, $ciphering,$decryption_key, $options, $decryption_iv);
        error_log("Decrypted Sek (Base64 Encoded): " . base64_encode($decryption));
        return base64_encode($decryption);
    }
    /**
     * RequestPayload is the JSON payload for IRN generation request
     * dataB64 is Base64RequestPayload
     * DecryptedSek is the decrypted SEK obtained from decryptSek() function
     */
    function encryptBySymmetricKey($dataB64, $DecryptedSek) {
        $data = base64_decode($dataB64);                                                // the data to encrypt
        $sek = base64_decode($DecryptedSek);                                            // the SEK
        $encDataB64 = openssl_encrypt($data, "aes-256-ecb", $sek, 0);                   // the Base64 encoded ciphertext
        return $encDataB64;
    }
    /**
     * encryption is data return from irn api
     * DecryptedSek is Sample Encrypted Sek
     */
    function decrypt_irn($encryption, $DecryptedSek){
        $options = 0;
        $ciphering = "AES-256-ECB";
        //$DecryptedSek = "y5XmaaigS7l1KJsoo/seilAQP0wGtHr9AsbyQ/PSwoI=";      // Sample Encrypted Sek  aX/vpj/5zH4+73h92sATT8YuKvR+fieLgGKYId5mCyLlCZqCyF1TFO3b86rJ7WI6
        $decryption_key =  base64_decode($DecryptedSek);

        $decryption_iv = '';
                
        $decryption=openssl_decrypt ($encryption, $ciphering,$decryption_key, $options, $decryption_iv);
        error_log("Decrypted IRN: " . $decryption);
        return $decryption;
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
    public function sendRequest($endpoint, $data = [], $auth = false, $method = 'POST', $username = null) {
        if($endpoint == 'AUTH_ENDPOINT'){
            $url = $this->baseUrl . self::AUTH_ENDPOINT;
        } elseif($endpoint == 'IRN_GENERATE_ENDPOINT'){
            $url = $this->baseUrl . self::IRN_GENERATE_ENDPOINT;
        } elseif($endpoint == 'IRN_CANCEL_ENDPOINT'){
            $url = $this->baseUrl . self::IRN_CANCEL_ENDPOINT;
        } elseif($endpoint == 'IRN_GET_ENDPOINT'){
            $url = $this->baseUrl . self::IRN_GET_ENDPOINT . ($data['irn'] ?? '');
        } elseif($endpoint == 'EWAYBILL_ENDPOINT'){
            $url = $this->baseUrl . self::EWAYBILL_ENDPOINT;
        } elseif($endpoint == 'EWAYBILL_CANCEL_ENDPOINT'){
            $url = $this->baseUrl . self::EWAYBILL_CANCEL_ENDPOINT;
        } elseif($endpoint == 'EWAYBILL_GET_ENDPOINT'){
            $url = $this->baseUrl . self::EWAYBILL_GET_ENDPOINT . ($data['ewaybill'] ?? '');
        }
        
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
            $headers[] = 'Authorization: Bearer ' . $this->token;
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
        //  print_r($headers);
        //  print_r($data);
        //  echo "Alankit API Request to $url returned HTTP $httpCode. Response:\n";
        //  print_r($response);
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
     * create seperate function for sending irn generation curl request to alankit api
     */
    function generateIrn($data, $accessToken){
        $url = $this->baseUrl . self::IRN_GENERATE_ENDPOINT;
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Gstin: 07AGAPA5363L002',
            'user_name: AL001',
            'Ocp-Apim-Subscription-Key: AL6x9c9S1b7g8h9S7C',
            'AuthToken :' . $accessToken
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Set request method and data
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
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
    private function determineSupTyp($invoice) {
        $country = $invoice['buyer_country'] ?? '';
        $buyerType = $invoice['buyer_type'] ?? ''; // e.g., 'business', 'sez', 'export', 'deemed'
        $hasPayment = $invoice['has_payment'] ?? true; // boolean: true for with payment, false for without

        if ($country === 'IN') {
            if ($buyerType === 'business') {
                return 'B2B';
            }
            // Add more IN-specific logic if needed
            return 'B2B'; // default for IN
        }

        if ($buyerType === 'sez') {
            return $hasPayment ? 'SEZWP' : 'SEZWOP';
        }

        if ($buyerType === 'export') {
            return $hasPayment ? 'EXPWP' : 'EXPWOP';
        }

        if ($buyerType === 'deemed') {
            return 'DEXP';
        }

        // Default fallback
        return 'EXPWP';
    }
    /**
     * Prepare IRN payload in official Alankit/Eraahi format
     * Maps internal invoice data to Alankit's required JSON structure
     * @param array $invoice Invoice data from database
     * @return array Formatted payload matching Alankit specifications
     */
    public function prepareIrnPayload($invoice) {
        // Format line items
        $itemList = [];
        if (!empty($invoice['line_items']) && is_array($invoice['line_items'])) {
            foreach ($invoice['line_items'] as $idx => $item) {
                $itemList[] = [
                    'SlNo' => (string)($idx + 1),
                    'PrdDesc' => $item['item_name'] ?? '',
                    'IsServc' => 'N',
                    'HsnCd' => substr($item['hsn'] ?? '', 0, 4),
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
        }

        // Build Alankit IRN payload
        return [
            'Version' => '1.1',
            'TranDtls' => [
                'TaxSch' => 'GST',
                'SupTyp' => $this->determineSupTyp($invoice),
                'RegRev' => 'N',
                'EcmGstin' => null,
                'IgstOnIntra' => 'N'
            ],
            'DocDtls' => [
                'Typ' => 'INV',
                'No' => (string)($invoice['invoice_number'] ?? ''),
                'Dt' => $invoice['invoice_date'] ? date('d/m/Y', strtotime($invoice['invoice_date'])) : date('d/m/Y')
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
                'Pos' => $invoice['pos'] ?? '96',
                'Addr1' => $invoice['buyer_address'] ?? '',
                'Loc' => $invoice['buyer_city'] ?? '',
                'Pin' => (int)($invoice['buyer_pincode'] ?? 0),
                'Stcd' => $invoice['buyer_state_code'] ?? '',
                'Ph' => $invoice['buyer_phone'] ?? '',
                'Em' => $invoice['buyer_email'] ?? ''
            ],
            // "DispDtls" => [
            //     "Nm" => "ABC company pvt ltd",
            //     "Addr1" => $invoice['shipping_address'] ?? '',                
            //     "Loc" => $invoice['shipping_city'] ?? '',
            //     "Pin" => (int)($invoice['shipping_pincode'] ?? 0),
            //     "Stcd" => $invoice['shipping_state'] ?? ''
            // ],
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
            "PayDtls" => null,
            "RefDtls" => null,
            "AddlDocDtls" => null,
            "ExpDtls" => [
                'ShipBNo' => (string)($invoice['shipping_bill_number'] ?? ''),
                'ShipBDt' => date('d/m/Y', strtotime($invoice['shipping_bill_date'])),
                'Port' => (string)($invoice['shipping_port_code'] ?? ''),
                'RefClm' => (string)($invoice['shipping_ref_clm'] ?? ''),
                'ForCur' => (string)($invoice['shipping_currency'] ?? ''),
                'CntCode' => (string)($invoice['shipping_country_code'] ?? ''),
                'ExpDuty' => (float)($invoice['shipping_exp_duty'] ?? 0)
            ],
            "EwbDtls" => null,
        ];
    }

   
}
?>