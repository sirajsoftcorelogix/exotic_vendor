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
        //$this->baseUrl = "https://developers.eraahi.com"; //Sandbox 
        $this->baseUrl = "https://www.alankitgst.com/"; // Production
        
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
    "UserName":"API_Yash@exoticindi",
    "Password":"Exotic#2040",
    "AppKey":"'.$this->appKey.'",
    "ForceRefreshAccessToken": true
}';


// $publicKey //sandbox;
/*$publicKey = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEArxd93uLDs8HTPqcSPpxZ
rf0Dc29r3iPp0a8filjAyeX4RAH6lWm9qFt26CcE8ESYtmo1sVtswvs7VH4Bjg/F
DlRpd+MnAlXuxChij8/vjyAwE71ucMrmZhxM8rOSfPML8fniZ8trr3I4R2o4xWh6
no/xTUtZ02/yUEXbphw3DEuefzHEQnEF+quGji9pvGnPO6Krmnri9H4WPY0ysPQQ
Qd82bUZCk9XdhSZcW/am8wBulYokITRMVHlbRXqu1pOFmQMO5oSpyZU3pXbsx+Ox
IOc4EDX0WMa9aH4+snt18WAXVGwF2B4fmBk7AtmkFzrTmbpmyVqA3KO2IjzMZPw0
hQIDAQAB
-----END PUBLIC KEY-----';
*/

// $publicKey //production;
$publicKey = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAjo1FvyiKcQ9hDR2+vH0+
O2XazuLbo2bPfRiiUnpaPhE3ly+Pwh05gvEuzo2UhUIDg98cX4E0vbfWOF1po2wW
TBxb8jMY1nAJ8fz1xyHc1Wa7KZ0CeTvAGeifkMux7c22pMu6pBGJN8f3q7MnIW/u
SJloJF6+x4DZcgvnDUlgZD3Pcoi3GJF1THbWQi5pDQ8U9hZsSJfpsuGKnz41QRsK
s7Dz7qmcKT2WwN3ULWikgCzywfuuREWb4TVE2p3e9WuoDNPUziLZFeUfMP0NqYsi
GVYHs1tVI25G42AwIVJoIxOWys8Zym9AMaIBV6EMVOtQUBbNIZufix/TwqTlxNPQ
VwIDAQAB
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
        // $url = $this->baseUrl . self::IRN_GENERATE_ENDPOINT;
        // $ch = curl_init();
        
        // curl_setopt($ch, CURLOPT_URL, $url);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_ENCODING, '');
        // curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        // curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        // //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        // //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
       
        // $headers = [
        //     'Content-Type: application/json',
        //     'Accept: application/json',
        //     'Gstin: 07AGAPA5363L002',
        //     'user_name: AL001',
        //     'Ocp-Apim-Subscription-Key: AL6x9c9S1b7g8h9S7C',
        //     'AuthToken :' . $accessToken
        // ];
        // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // // Set request method and data
        // //curl_setopt($ch, CURLOPT_POST, true);
        // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        // $response = curl_exec($ch);
        // $curlError = curl_error($ch);
        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // curl_close($ch);
        
        // if ($curlError) {
        //     error_log("Alankit API cURL Error ($httpCode): $curlError for URL: $url");
        //     return [
        //         'status' => false,
        //         'message' => 'cURL Error: ' . $curlError,
        //         'http_code' => $httpCode
        //     ];
        // }

        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => $this->baseUrl . self::IRN_GENERATE_ENDPOINT,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'Ocp-Apim-Subscription-Key: AL6x9c9S1b7g8h9S7C',
            'Gstin: 07AADCE1400C1ZJ',
            'user_name: API_Yash@exoticindi',
            'AuthToken: '.$accessToken,
            'Content-Type: application/json'
            //'Cookie: sess_map=fqcuxerztqqzbryduezaywetarayrduvcaebxuzfaubacufxccubxurxbdttrwqvrxbzcfrszstsquwezbeswaueqvbtzzxsueufyzdsqyacfefubucaqeqaeduuvyuaydbvbrsryxqubruvydafdrsxveqecbdcdyaxvawuuwaayadq'
        ),
        ));

        $response = curl_exec($curl);
        $decoded = json_decode($response, true);
        
        // if ($httpCode >= 400) {
        //     error_log("Alankit API HTTP Error ($httpCode) for URL: $url. Response: " . substr($response, 0, 500));
        //     return [
        //         'status' => false,
        //         'message' => 'HTTP Error ' . $httpCode,
        //         'data' => $decoded
        //     ];
        // }
        
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
        $country = strtoupper(trim((string)($invoice['buyer_country'] ?? '')));
        $currency = strtoupper(trim((string)($invoice['currency'] ?? 'INR')));
        $isExport = ($currency !== 'INR') || ($country !== 'IN' && $country !== 'INDIA' && $country !== '');

        $formatStcd = static function($val, string $fallback = '96'): string {
            $raw = trim((string)$val);
            if ($raw === '' || $raw === '0' || $raw === '00') {
                return $fallback;
            }
            if (is_numeric($raw)) {
                $num = (int)$raw;
                return $num > 0 ? sprintf('%02d', $num) : $fallback;
            }
            if (strlen($raw) <= 2) {
                return strtoupper($raw);
            }
            return $fallback;
        };

        $formatPhone = static function($ph): ?string {
            if ($ph === null) {
                return null;
            }
            $digits = preg_replace('/\D/', '', (string)$ph);
            if (strlen($digits) >= 6 && strlen($digits) <= 12) {
                return $digits;
            }
            return null;
        };

        $formatEmail = static function($em): ?string {
            if ($em === null) {
                return null;
            }
            $str = trim((string)$em);
            if (strlen($str) >= 6 && strlen($str) <= 100 && filter_var($str, FILTER_VALIDATE_EMAIL)) {
                return $str;
            }
            return null;
        };

        // Format line items
        $itemList = [];
        if (!empty($invoice['line_items']) && is_array($invoice['line_items'])) {
            foreach ($invoice['line_items'] as $idx => $item) {
                $qty = max(0.001, (float)($item['quantity'] ?? 0));
                $unitPrice = round((float)($item['unit_price'] ?? 0), 2);
                $totAmt = round($qty * $unitPrice, 2);
                $taxRate = round((float)($item['tax_rate'] ?? 0), 2);
                $igstAmt = round((float)($item['tax_amount'] ?? $item['igst'] ?? 0), 2);
                $cgstAmt = round((float)($item['cgst'] ?? 0), 2);
                $sgstAmt = round((float)($item['sgst'] ?? 0), 2);
                $calcTotItemVal = $totAmt + $igstAmt + $cgstAmt + $sgstAmt;
                $totItemVal = isset($item['total']) ? round((float)$item['total'], 2) : round($calcTotItemVal, 2);

                $hsnClean = preg_replace('/\D/', '', (string)($item['hsn'] ?? ''));
                $hsnCd = strlen($hsnClean) >= 2 ? substr($hsnClean, 0, 8) : '9703';

                $itemList[] = [
                    'SlNo' => (string)($idx + 1),
                    'PrdDesc' => (string)($item['item_name'] ?? 'Product'),
                    'IsServc' => 'N',
                    'HsnCd' => $hsnCd,
                    'Qty' => $qty,
                    'Unit' => (string)($item['unit'] ?? 'PCS'),
                    'UnitPrice' => $unitPrice,
                    'TotAmt' => $totAmt,
                    'AssAmt' => $totAmt,
                    'GstRt' => $taxRate,
                    'IgstAmt' => $igstAmt,
                    'CgstAmt' => $cgstAmt,
                    'SgstAmt' => $sgstAmt,
                    'TotItemVal' => $totItemVal,
                ];
            }
        }

        $sellerGstin = trim((string)($invoice['seller_gstin'] ?? ''));
        $sellerStcd = '';
        if (strlen($sellerGstin) >= 2 && ctype_digit(substr($sellerGstin, 0, 2)) && (int)substr($sellerGstin, 0, 2) > 0) {
            $sellerStcd = sprintf('%02d', (int)substr($sellerGstin, 0, 2));
        } else {
            $sellerStcd = $formatStcd($invoice['seller_state_code'] ?? '', '07');
        }

        $buyerStcd = $formatStcd($invoice['buyer_state_code'] ?? '', $isExport ? '96' : '07');
        $shipStcd = $formatStcd($invoice['shipping_state_code'] ?? $invoice['shipping_state'] ?? '', $buyerStcd);

        $transId = strtoupper(trim((string)($invoice['trans_id'] ?? '')));
        $transName = trim((string)($invoice['trans_name'] ?? ''));
        $vehNo = strtoupper(trim((string)($invoice['veh_no'] ?? '')));
        $vehType = strtoupper(trim((string)($invoice['veh_type'] ?? 'R')));
        if ($vehType !== 'ODC') {
            $vehType = 'R';
        }
        $transDocNo = trim((string)($invoice['trans_doc_no'] ?? ''));
        $transMode = trim((string)($invoice['trans_mode'] ?? '1'));
        if ($transMode === '') {
            $transMode = '1';
        }

        $ewbDtls = null;
        if ($transId !== '') {
            $ewbDtls = [
                'TransId' => $transId,
                'TransName' => $transName,
                'Distance' => 0,
            ];
        } elseif ($vehNo !== '' || $transDocNo !== '') {
            $validVehNo = (strlen($vehNo) >= 4 && strlen($vehNo) <= 20);
            $validDocNo = (strlen($transDocNo) >= 1 && strlen($transDocNo) <= 15);
            if ($validVehNo || $validDocNo) {
                $ewbDtls = [
                    'Distance' => 0,
                    'TransMode' => $transMode,
                    'VehType' => $vehType,
                ];
                if ($validVehNo) {
                    $ewbDtls['VehNo'] = $vehNo;
                }
                if ($validDocNo) {
                    $ewbDtls['TransDocNo'] = $transDocNo;
                    $ewbDtls['TransDocDt'] = !empty($invoice['trans_doc_dt'])
                        ? date('d/m/Y', strtotime($invoice['trans_doc_dt']))
                        : date('d/m/Y');
                }
            }
        }

        $buyerAddress = trim((string)($invoice['buyer_address'] ?? ''));
        if ($buyerAddress === '') {
            $buyerAddress = trim((string)($invoice['shipping_address'] ?? 'Export Address'));
        }
        $shippingAddress = trim((string)($invoice['shipping_address'] ?? ''));
        if ($shippingAddress === '') {
            $shippingAddress = $buyerAddress;
        }

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
                'Dt' => !empty($invoice['invoice_date']) ? date('d/m/Y', strtotime($invoice['invoice_date'])) : date('d/m/Y')
            ],
            'SellerDtls' => [
                'Gstin' => (string)($invoice['seller_gstin'] ?? ''),
                'LglNm' => (string)($invoice['seller_name'] ?? ''),
                'TrdNm' => (string)($invoice['seller_name'] ?? ''),
                'Addr1' => (string)($invoice['seller_address'] ?? ''),
                'Loc' => (string)($invoice['seller_city'] ?? 'New Delhi'),
                'Pin' => (int)($invoice['seller_pincode'] ?? 110055),
                'Stcd' => $sellerStcd,
                'Ph' => $formatPhone($invoice['seller_phone'] ?? null),
                'Em' => $formatEmail($invoice['seller_email'] ?? null)
            ],
            'BuyerDtls' => [
                'Gstin' => (string)($invoice['buyer_gstin'] ?? ($isExport ? 'URP' : '')),
                'LglNm' => (string)($invoice['buyer_name'] ?? 'Buyer'),
                'TrdNm' => (string)($invoice['buyer_name'] ?? 'Buyer'),
                'Pos' => $formatStcd($invoice['pos'] ?? $buyerStcd, $buyerStcd),
                'Addr1' => $buyerAddress,
                'Loc' => (string)($invoice['buyer_city'] ?? ($isExport ? 'Foreign City' : 'Delhi')),
                'Pin' => (int)($invoice['buyer_pincode'] ?? ($isExport ? 999999 : 110001)),
                'Stcd' => $buyerStcd,
                'Ph' => $formatPhone($invoice['buyer_phone'] ?? null),
                'Em' => $formatEmail($invoice['buyer_email'] ?? null)
            ],
            'ShipDtls' => [
                'Gstin' => (string)($invoice['buyer_gstin'] ?? ($isExport ? 'URP' : '')),
                'LglNm' => (string)($invoice['shipping_name'] ?? $invoice['buyer_name'] ?? 'Buyer'),
                'TrdNm' => (string)($invoice['shipping_name'] ?? $invoice['buyer_name'] ?? 'Buyer'),
                'Addr1' => $shippingAddress,
                'Loc' => (string)($invoice['shipping_city'] ?? $invoice['buyer_city'] ?? ($isExport ? 'Foreign City' : 'Delhi')),
                'Pin' => (int)($invoice['shipping_pincode'] ?? $invoice['buyer_pincode'] ?? ($isExport ? 999999 : 110001)),
                'Stcd' => $shipStcd
            ],
            'ItemList' => $itemList,
            'ValDtls' => [
                'AssVal' => round((float)($invoice['subtotal'] ?? 0), 2),
                'CgstVal' => round((float)($invoice['cgst_total'] ?? 0), 2),
                'SgstVal' => round((float)($invoice['sgst_total'] ?? 0), 2),
                'IgstVal' => round((float)($invoice['tax_amount'] ?? 0), 2),
                'CesVal' => 0,
                'Discount' => round((float)($invoice['discount_amount'] ?? 0), 2),
                'OthChrg' => 0,
                'RndOffAmt' => 0,
                'TotInvVal' => round((float)($invoice['total_amount'] ?? 0), 2)
            ],
            'PayDtls' => null,
            'RefDtls' => null,
            'AddlDocDtls' => null,
            'ExpDtls' => $isExport ? [
                'ShipBNo' => (string)($invoice['shipping_bill_number'] ?? ''),
                'ShipBDt' => !empty($invoice['shipping_bill_date']) ? date('d/m/Y', strtotime($invoice['shipping_bill_date'])) : date('d/m/Y'),
                'Port' => (string)($invoice['shipping_port_code'] ?? 'INABG1'),
                'RefClm' => (string)($invoice['shipping_ref_clm'] ?? 'N'),
                'ForCur' => (string)($invoice['shipping_currency'] ?? 'USD'),
                'CntCode' => (string)($invoice['shipping_country_code'] ?? 'US'),
                'ExpDuty' => round((float)($invoice['shipping_exp_duty'] ?? 0), 2)
            ] : null,
            'EwbDtls' => $ewbDtls
        ];
    }

    /**
     * Prepare E-Way Bill payload in official Alankit/Eraahi format
     * @param array $ewbData E-way bill data
     * @return array Formatted payload matching Alankit specifications
     */
    public function prepareEwbPayload($ewbData) {
        $payload = [
            'Irn' => $ewbData['irn'] ?? '',
            'Distance' => (int)($ewbData['distance'] ?? 100),
            'DispDtls' => [
                'Nm' => (string)($ewbData['Nm'] ?? 'ABC company pvt ltd'),
                'Addr1' => (string)($ewbData['Addr1'] ?? ''),
                'Addr2' => (string)($ewbData['Addr2'] ?? ''),
                'Loc' => (string)($ewbData['Loc'] ?? ''),
                'Pin' => (int)($ewbData['Pin'] ?? 0),
                'Stcd' => (string)($ewbData['Stcd'] ?? '')
            ],
            'ExpShipDtls' => [
                'Gstin' => (string)($ewbData['Gstin'] ?? ''),
                'TrdNm' => (string)($ewbData['TrdNm'] ?? 'ABC company pvt ltd'),
                'Addr1' => (string)($ewbData['Addr1'] ?? ''),
                'Addr2' => (string)($ewbData['Addr2'] ?? ''),
                'Loc' => (string)($ewbData['Loc'] ?? ''),
                'Pin' => (int)($ewbData['Pin'] ?? 0),
                'Stcd' => (string)($ewbData['Stcd'] ?? '')
            ]
        ];

        $transId = trim((string)($ewbData['trans_id'] ?? ''));
        if ($transId === '') {
            $payload['TransMode'] = (string)($ewbData['trans_mode'] ?? '1');
            $payload['VehNo'] = trim((string)($ewbData['veh_no'] ?? ''));
            $payload['VehType'] = trim((string)($ewbData['veh_type'] ?? 'R'));
            $payload['TrnDocNo'] = trim((string)($ewbData['trn_doc_no'] ?? ''));
            $payload['TrnDocDt'] = (string)($ewbData['trn_doc_dt'] ?? date('d/m/Y'));
        } else {
            $payload['TransId'] = substr(preg_replace('/\s+/', '', $transId), 0, 15);
            $payload['TransName'] = (string)($ewbData['trans_name'] ?? 'trans name');
        }

        return $payload;
    }

    /**
     * Generate E-Way Bill with encrypted request and response
     * @param array $ewbData E-way bill data
     * @param string $accessToken Authentication token
     * @param string $decryptedSek Decrypted SEK for encryption/decryption
     * @return array API response
     */
    public function generateEwb($ewbData, $accessToken, $decryptedSek) {
        $url = $this->baseUrl . self::EWAYBILL_ENDPOINT;
        
        // Prepare EWB payload
        //$payload = $this->prepareEwbPayload($ewbData);
        
        // Encrypt payload
        $payloadJson = json_encode($ewbData);
        //echo "Alankit EWB: Payload JSON: " . $payloadJson . "\n";
        $payloadB64 = base64_encode($payloadJson);
        //echo "Alankit EWB: Payload Base64: " . $payloadB64 . "\n";
        $encryptedPayload = $this->encryptBySymmetricKey($payloadB64, $decryptedSek);
        
        if (!$encryptedPayload) {
            error_log("Alankit EWB: Payload encryption failed");
            return [
                'status' => false,
                'message' => 'Payload encryption failed'
            ];
        }
        
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
            'Gstin: ' . $this->gstin,
            'user_name: API_Yash@exoticindi',
            'Ocp-Apim-Subscription-Key: ' . $this->subscriptionKey,
            'AuthToken: ' . $accessToken
        ];
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['Data' => $encryptedPayload]));
        
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($curlError) {
            error_log("Alankit EWB cURL Error ($httpCode): $curlError for URL: $url");
            return [
                'status' => false,
                'message' => 'cURL Error: ' . $curlError,
                'http_code' => $httpCode
            ];
        }
        //echo "Alankit EWB API Request to $url returned HTTP $httpCode. Response:\n";
        //print_r($response);
        //echo "\n";
        //echo "Decrypted SEK used for EWB encryption/decryption: " . $decryptedSek . "\n";
        //echo "Encrypted EWB Payload sent to API: " . $encryptedPayload . "\n";
        $decoded = json_decode($response, true);
        
        // if ($httpCode >= 400) {
        //     error_log("Alankit EWB HTTP Error ($httpCode) for URL: $url. Response: " . substr($response, 0, 500));
        //     return [
        //         'status' => false,
        //         'message' => 'HTTP Error ' . $httpCode,
        //         'data' => $decoded
        //     ];
        // }
        //ErrorDetails
        if (isset($decoded['ErrorDetails'])) {
            error_log("Alankit EWB API Error: " . json_encode($decoded['ErrorDetails']));
            return [
                'status' => false,
                'message' => 'API Error',
                'ErrorMessage' => $decoded['ErrorDetails'],
                'Data' => $decoded['Data']
            ];
        }
        // Decrypt response
        if ($decoded && isset($decoded['Data'])) {
            $decryptedResponse = $this->decrypt_irn($decoded['Data'], $decryptedSek);
            $ewbResponse = json_decode($decryptedResponse, true);
            error_log("Alankit EWB: Response decrypted successfully");
            return $ewbResponse;
        }
        
        error_log("Alankit EWB: No response data received");
        return $decoded;
    }
}
?>