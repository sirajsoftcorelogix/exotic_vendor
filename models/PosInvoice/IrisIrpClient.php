<?php
class IrisIrpClient {
    private $clientId;
    private $clientSecret;
    private $username;
    private $password;
    private $baseUrl;
    private $token;

    public function __construct($clientId, $clientSecret, $username, $password, $sandbox = true) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->username = $username;
        $this->password = $password;
        $this->baseUrl = $sandbox 
            ? "https://sandbox-irisirp.com" 
            : "https://api.irisirp.com";
    }

    /** Authenticate and store token */
    public function authenticate() {
        $url = $this->baseUrl . "/authenticate";
        $data = [
            "client_id" => $this->clientId,
            "client_secret" => $this->clientSecret,
            "username" => $this->username,
            "password" => $this->password
        ];

        $response = $this->sendRequest($url, $data);
        if (isset($response['access_token'])) {
            $this->token = $response['access_token'];
            return $this->token;
        }
        throw new Exception("Authentication failed: " . json_encode($response));
    }

    /** Generate IRN */
    public function generateIrn($invoiceData) {
        $url = $this->baseUrl . "/generate-irn";
        return $this->sendRequest($url, $invoiceData, true);
    }

    /** Cancel IRN */
    public function cancelIrn($cancelData) {
        $url = $this->baseUrl . "/cancel-irn";
        return $this->sendRequest($url, $cancelData, true);
    }

    /** Get IRN by Document */
    public function getIrnByDoc($docData) {
        $url = $this->baseUrl . "/get-irn";
        return $this->sendRequest($url, $docData, true);
    }

    /** Generic request handler */
    private function sendRequest($url, $data, $auth = false) {
        $ch = curl_init($url);
        $headers = ['Content-Type:application/json'];
        if ($auth && $this->token) {
            $headers[] = "Authorization: Bearer {$this->token}";
        }

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception("cURL error: " . curl_error($ch));
        }
        curl_close($ch);

        return json_decode($response, true);
    }
}
