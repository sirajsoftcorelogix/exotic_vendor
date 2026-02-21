<?php 
class Dispatch {
    private $db;

    public function __construct($conn) {
        $this->db = $conn;
    }

    public function createDispatch($data) {
        //check if dispatch already exists for this invoice and box_no
        $sqlCheck = "SELECT id FROM vp_dispatch_details WHERE invoice_id = ? AND box_no = ?";
        $stmtCheck = $this->db->prepare($sqlCheck);
        if (!$stmtCheck) return false;
        $stmtCheck->bind_param('ii', $data['invoice_id'], $data['box_no']);
        $stmtCheck->execute();
        $result = $stmtCheck->get_result();
        if ($result && $result->num_rows > 0) {
            // Dispatch already exists for this invoice and box_no
            return false;
        }
        $sql = "INSERT INTO vp_dispatch_details (invoice_id, box_no, order_number, shiprocket_order_id, shiprocket_shipment_id, shiprocket_tracking_url, box_items, length, width, height, weight, volumetric_weight, billing_weight, shipping_charges, dispatch_date, courier_name, awb_code, shipment_status, label_url, created_by, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $data['created_by'] = isset($data['created_by']) ? (int)$data['created_by'] : 0;
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $boxItemsJson = is_string($data['box_items']) ? $data['box_items'] : json_encode($data['box_items'] ?? []);

        // Bind parameters must be variables (passed by reference). Prepare local vars:
        $invoice_id = (int)($data['invoice_id'] ?? 0);
        $box_no = (int)($data['box_no'] ?? 0);
        $order_number = $data['order_number'] ?? null;
        $shiprocket_order_id = $data['shiprocket_order_id'] ?? null;
        $shiprocket_shipment_id = $data['shiprocket_shipment_id'] ?? null;
        $shiprocket_tracking_url = $data['shiprocket_tracking_url'] ?? null;
        $box_items = $boxItemsJson;
        $length = (float)($data['length'] ?? 0);
        $width = (float)($data['width'] ?? 0);
        $height = (float)($data['height'] ?? 0);
        $weight = (float)($data['weight'] ?? 0);
        $volumetric_weight = (float)($data['volumetric_weight'] ?? 0);
        $billing_weight = (float)($data['billing_weight'] ?? 0);
        $shipping_charges = (float)($data['shipping_charges'] ?? 0);
        $dispatch_date = $data['dispatch_date'] ?? date('Y-m-d H:i:s');
        $courier_name = $data['courier_name'] ?? null;
        $awb_code = $data['awb_code'] ?? null;
        $shipment_status = $data['shipment_status'] ?? null;
        $label_url = $data['label_url'] ?? null;
        $created_by = (int)$data['created_by'];
        $created_at = $data['created_at'];

        $stmt->bind_param(
            'iisssssdddddddsssssis',
            $invoice_id,
            $box_no,
            $order_number,
            $shiprocket_order_id,
            $shiprocket_shipment_id,
            $shiprocket_tracking_url,
            $box_items,
            $length,
            $width,
            $height,
            $weight,
            $volumetric_weight,
            $billing_weight,
            $shipping_charges,
            $dispatch_date,
            $courier_name,
            $awb_code,
            $shipment_status,
            $label_url,
            $created_by,
            $created_at
        );

        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function getDispatchByInvoiceId($invoiceId) {
        $sql = "SELECT * FROM vp_dispatch_details WHERE invoice_id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param('i', $invoiceId);
        if ($stmt->execute()) {
            return $stmt->get_result()->fetch_assoc();
        }
        return false;
    }
    public function getDispatchById($id) {
        $sql = "SELECT * FROM vp_dispatch_details WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            return $stmt->get_result()->fetch_assoc();
        }
        return false;
    }
    //shiprocket api call
   
    public function shiprocketCreateShipment(array $payload) {
        // Replace these with your actual values or config
        $apiUrl = 'https://apiv2.shiprocket.in/v1/external/orders/create/adhoc';        
        $authToken = $this->getShiprocketToken();
        $ch = curl_init($apiUrl);
        $json = json_encode($payload);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $authToken,
            'Accept: application/json'
        ]);
        $responseRaw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        // Log for debugging
        file_put_contents(__DIR__ . '/shiprocket_http_log.txt', date('c') . " - URL: $apiUrl - HTTP: $httpCode - Error: $curlErr - Request: $json - Response: $responseRaw\n", FILE_APPEND);
        @chmod(__DIR__ . '/shiprocket_http_log.txt', 0666);

        $responseDecoded = null;
        if ($responseRaw) {
            $responseDecoded = json_decode($responseRaw, true);
        }

        return [
            'http_code' => $httpCode,
            'raw' => $responseRaw,
            'json' => $responseDecoded,
            'curl_error' => $curlErr
        ];
    }

    private function getShiprocketToken() {
        //shiprocket_api_tokens fetch token and check if valid else generate new token and save to db
        $sql = "SELECT token, expires_at FROM shiprocket_api_tokens ORDER BY id DESC LIMIT 1";
        $result = $this->db->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (strtotime($row['expires_at']) > time()) {
                return $row['token'];
            }else{

                $url = "https://www.exoticindia.com/vendor-api/order/shiprocket-token";
                $headers = [
                'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
                'x-adminapitest: 1',
                'Content-Type: application/x-www-form-urlencoded'
                ];
                $postData = [
                'makeRequestOf' => 'vendors-orderjson'
                ];
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
                $response = curl_exec($ch); 
                curl_close($ch);
                $data = json_decode($response, true);
                $token = $data['shiprocket_token'] ?? '';
                $expire_at = $data['shiprocket_expiry'] ? date('Y-m-d H:i:s', $data['shiprocket_expiry']) : date('Y-m-d H:i:s', time() + 3600); // default 1 hour expiry
                if($token) {
                    $sql = "UPDATE shiprocket_api_tokens SET token = '$token', expires_at = '$expire_at', updated_at = NOW() ORDER BY id DESC LIMIT 1";
                    $this->db->query($sql);
                }
                return $token;
            }
        }

        //return "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOjI1NjAxMjUsInNvdXJjZSI6InNyLWF1dGgtaW50IiwiZXhwIjoxNzcyNDQxODU3LCJqdGkiOiJ0MHQ5OGVra0hVcDkxQWxuIiwiaWF0IjoxNzcxNTc3ODU3LCJpc3MiOiJodHRwczovL3NyLWF1dGguc2hpcHJvY2tldC5pbi9hdXRob3JpemUvdXNlciIsIm5iZiI6MTc3MTU3Nzg1NywiY2lkIjoyOTg1MDcsInRjIjozNjAsInZlcmJvc2UiOmZhbHNlLCJ2ZW5kb3JfaWQiOjAsInZlbmRvcl9jb2RlIjoiIn0.DcsXO_szP7se17CuZE1nHMNIfvjOxLotI7zqSNHYLZM";
        // $url = "https://www.exoticindia.com/vendor-api/order/shiprocket-token";
        // $headers = [
        // 'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
        // 'x-adminapitest: 1',
        // 'Content-Type: application/x-www-form-urlencoded'
        // ];
        // $postData = [
        // 'makeRequestOf' => 'vendors-orderjson'
        // ];
        // $ch = curl_init($url);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        // $response = curl_exec($ch); 
        // curl_close($ch);
        // $data = json_decode($response, true);
        // return $data['shiprocket_token'] ?? '';

    }
    public function pickupLocations() {
        $url = "https://apiv2.shiprocket.in/v1/external/settings/company/pickup";
        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->getShiprocketToken()
        ];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch); 
        curl_close($ch);
        return json_decode($response, true);
    }
    //get labels from shiprocket
    public function getShiprocketLabels($shipmentId) {
        //$url = "https://apiv2.shiprocket.in/v1/external/shipments/{$shipmentId}/label";
        $url = "https://apiv2.shiprocket.in/v1/external/courier/generate/label";
        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->getShiprocketToken()
        ];
        $postData = json_encode([
            "shipment_id" => ["$shipmentId"]
        ]);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $response = curl_exec($ch); 
        curl_close($ch);
        return json_decode($response, true);
    }
    //get tracking info from shiprocket
    public function getShiprocketTrackingInfo($trackingNumber) {
        $url = "https://apiv2.shiprocket.in/v1/external/track/shipment/{$trackingNumber}";
        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->getShiprocketToken()
        ];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch); 
        curl_close($ch);
        return json_decode($response, true);
    }
    //get awb info from shiprocket
    public function getShiprocketAwbInfo($shipment_id) {
        $url = "https://apiv2.shiprocket.in/v1/external/courier/assign/awb";
        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->getShiprocketToken()
        ];
        $postData = json_encode([
            "shipment_id" => $shipment_id
        ]);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch); 
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $response = curl_exec($ch); 
        curl_close($ch);
        return json_decode($response, true);
    }
    public function updateDispatchAwbCode($shipment_id, $awb_code) {
        $sql = "UPDATE vp_dispatch_details SET awb_code = ? WHERE shiprocket_shipment_id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param('si', $awb_code, $shipment_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    public function updateDispatchLabelUrl($shipment_id, $label_url) {
        $sql = "UPDATE vp_dispatch_details SET label_url = ? WHERE shiprocket_shipment_id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        //echo "Updating label URL for shipment ID $shipment_id with URL: $label_url\n";
        $stmt->bind_param('si', $label_url, $shipment_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
        
}