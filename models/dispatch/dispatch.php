<?php

require_once __DIR__ . '/../../shiprocket_service.php';
require_once __DIR__ . '/../../helpers/dispatch_delivery_dates.php';
require_once __DIR__ . '/../../helpers/dispatch_courier_identity.php';
require_once __DIR__ . '/../../helpers/exotic_india_shipment_api.php';

class Dispatch {
    private $db;
    private ?ShiprocketService $shiprocketService = null;

    public function __construct($conn) {
        $this->db = $conn;
    }

    private function shiprocket(): ShiprocketService
    {
        if ($this->shiprocketService === null) {
            $this->shiprocketService = new ShiprocketService($this->db);
        }

        return $this->shiprocketService;
    }

    public function getShiprocketDefaultPickupLocation(): string
    {
        return $this->shiprocket()->getDefaultPickupLocation();
    }

    private function shiprocketUrl(string $path): string
    {
        return $this->shiprocket()->apiUrl($path);
    }

    /**
     * @param array<string, mixed> $beforeDispatch row before update (empty on create)
     */
    private function maybeLogExoticIndiaShipment(int $dispatchId, array $beforeDispatch = []): void
    {
        if ($dispatchId <= 0) {
            return;
        }

        $result = exotic_india_log_dispatch_shipment($this->db, $dispatchId, $beforeDispatch);
        if (empty($result['success']) && empty($result['skipped'])) {
            error_log(
                'Exotic India shipment-add failed for dispatch '
                . $dispatchId
                . ': '
                . (string) ($result['message'] ?? 'unknown error')
            );
        }
    }

    public function checkDispatchExists($invoiceId, $boxNo) {
        $sql = "SELECT id FROM vp_dispatch_details WHERE invoice_id = ? AND box_no = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param('ii', $invoiceId, $boxNo);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            return $result->num_rows > 0;
        }
        return false;
    }   
    public function createDispatch($data) {
       
        $sql = "INSERT INTO vp_dispatch_details (invoice_id, box_no, order_number, shiprocket_order_id, shiprocket_shipment_id, box_items, length, width, height, weight, volumetric_weight, billing_weight, shipping_charges, dispatch_date, courier_name, courier_company_id, shipper_id, courier_partner_id, awb_code, shipment_status, label_url, tracking_url, etd, edd, groupname, box_size, created_by, created_at, pickup_location, batch_no) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $data['created_by'] = isset($data['created_by']) ? (int)$data['created_by'] : 0;
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['pickup_location'] = $data['pickup_location'] ?? null;
        $boxItemsJson = is_string($data['box_items']) ? $data['box_items'] : json_encode($data['box_items'] ?? []);
        $batch_no = $data['batch_no'] ?? null;

        // Bind parameters must be variables (passed by reference). Prepare local vars:
        $invoice_id = (int)($data['invoice_id'] ?? 0);
        $box_no = (int)($data['box_no'] ?? 0);
        $order_number = $data['order_number'] ?? null;
        $shiprocket_order_id = $data['shiprocket_order_id'] ?? null;
        $shiprocket_shipment_id = $data['shiprocket_shipment_id'] ?? null;
        $box_items = $boxItemsJson;
        $pickup_location = $data['pickup_location'] ?? null;
        $length = (float)($data['length'] ?? 0);
        $width = (float)($data['width'] ?? 0);
        $height = (float)($data['height'] ?? 0);
        $weight = (float)($data['weight'] ?? 0);
        $volumetric_weight = (float)($data['volumetric_weight'] ?? 0);
        $billing_weight = (float)($data['billing_weight'] ?? 0);
        $shipping_charges = (float)($data['shipping_charges'] ?? 0);
        $dispatch_date = $data['dispatch_date'] ?? date('Y-m-d H:i:s');
        $courier_name = $data['courier_name'] ?? null;
        $courier_company_id = isset($data['courier_company_id']) && (int) $data['courier_company_id'] > 0
            ? (int) $data['courier_company_id']
            : null;
        $shipper_id = isset($data['shipper_id']) && (int) $data['shipper_id'] > 0
            ? (int) $data['shipper_id']
            : null;
        $courier_partner_id = isset($data['courier_partner_id']) && (int) $data['courier_partner_id'] > 0
            ? (int) $data['courier_partner_id']
            : null;
        $awb_code = $data['awb_code'] ?? null;
        $shipment_status = $data['shipment_status'] ?? null;
        $label_url = $data['label_url'] ?? null;
        $tracking_url = $data['tracking_url'] ?? null;
        $deliveryDates = extractDispatchDeliveryDates($data, $dispatch_date);
        $etd = $deliveryDates['etd'];
        $edd = $deliveryDates['edd'];
        $groupname = $data['groupname'] ?? null;
        $box_size = $data['box_size'] ?? null;
        $created_by = (int)$data['created_by'];
        $created_at = $data['created_at'];

        $stmt->bind_param(
            'iissssdddddddssiiissssssssisss',
            $invoice_id,
            $box_no,
            $order_number,
            $shiprocket_order_id,
            $shiprocket_shipment_id,
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
            $courier_company_id,
            $shipper_id,
            $courier_partner_id,
            $awb_code,
            $shipment_status,
            $label_url,
            $tracking_url,
            $etd,
            $edd,
            $groupname,
            $box_size,
            $created_by,
            $created_at,
            $pickup_location,
            $batch_no
        );

        if ($stmt->execute()) {
            $dispatchId = (int) $this->db->insert_id;
            $this->maybeLogExoticIndiaShipment($dispatchId);
            return $dispatchId;
        }
        if ($stmt->error) {
            error_log('Database error in createDispatch: ' . $stmt->error);
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
        return $this->shiprocketJsonRequest('POST', '/v1/external/orders/create/adhoc', $payload);
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array{http_code:int,raw:string|false,json:array<string,mixed>|null,curl_error:string,auth_error:string}
     */
    private function shiprocketJsonRequest(string $method, string $path, ?array $payload = null): array
    {
        $apiUrl = $this->shiprocketUrl($path);
        $authToken = $this->getShiprocketToken();
        $authError = $this->shiprocket()->getLastAuthError();
        if ($authToken === '') {
            return [
                'http_code' => 503,
                'raw' => '',
                'json' => ['message' => $authError !== '' ? $authError : 'Shiprocket auth token missing'],
                'curl_error' => '',
                'auth_error' => $authError,
            ];
        }

        $response = $this->executeShiprocketCurl($apiUrl, $method, $authToken, $payload);
        if ((int) ($response['http_code'] ?? 0) === 401) {
            $authToken = $this->shiprocket()->handleUnauthorized();
            $authError = $this->shiprocket()->getLastAuthError();
            if ($authToken !== '') {
                $response = $this->executeShiprocketCurl($apiUrl, $method, $authToken, $payload);
                if ((int) ($response['http_code'] ?? 0) !== 401) {
                    $authError = '';
                }
            }
        }

        $response['auth_error'] = $authError;

        return $response;
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array{http_code:int,raw:string|false,json:array<string,mixed>|null,curl_error:string}
     */
    private function executeShiprocketCurl(string $apiUrl, string $method, string $authToken, ?array $payload = null): array
    {
        $ch = curl_init($apiUrl);
        $json = $payload !== null ? json_encode($payload) : null;
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $authToken,
            'Accept: application/json',
        ]);

        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($json !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            }
        }

        $responseRaw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        $responseDecoded = null;
        if (is_string($responseRaw) && $responseRaw !== '') {
            $decoded = json_decode($responseRaw, true);
            $responseDecoded = is_array($decoded) ? $decoded : null;
        }

        return [
            'http_code' => $httpCode,
            'raw' => $responseRaw,
            'json' => $responseDecoded,
            'curl_error' => $curlErr,
        ];
    }

    private function getShiprocketToken(): string
    {
        return $this->shiprocket()->getToken();
    }

    public function pickupLocations() {
        $response = $this->shiprocketJsonRequest('GET', '/v1/external/settings/company/pickup');
        if (!is_array($response['json'])) {
            return [
                'auth_error' => $response['auth_error'] ?? '',
                'data' => ['shipping_address' => []],
            ];
        }

        return $response['json'];
    }
    // Get available couriers from Shiprocket based on serviceability
    public function getCourierServiceability($pickup_postcode, $delivery_postcode, $weight, $length, $breadth, $height, $cod = 0, $is_return = 0, $qc_check = 0, $mode = null) {
        // Build query parameters for GET request
        $params = [
            'pickup_postcode' => $pickup_postcode,
            'delivery_postcode' => $delivery_postcode,
            'weight' => $weight,
            'length' => $length,
            'breadth' => $breadth,
            'height' => $height,
            'cod' => $cod,
            //'is_return' => $is_return,
        ];
        
        if ($mode !== null) {
            $params['mode'] = $mode;
        }
        
        $path = '/v1/external/courier/serviceability/?' . http_build_query($params);
        $url = $this->shiprocketUrl($path);
        $authToken = $this->getShiprocketToken();
        $authError = $this->shiprocket()->getLastAuthError();
        if ($authToken === '') {
            return [
                'http_code' => 503,
                'data' => ['message' => $authError !== '' ? $authError : 'Shiprocket auth token missing'],
                'success' => false,
                'params' => $params,
                'request_url' => $url,
                'curl_error' => '',
                'auth_error' => $authError,
            ];
        }

        $response = $this->executeShiprocketCurl($url, 'GET', $authToken);
        if ((int) ($response['http_code'] ?? 0) === 401) {
            $authToken = $this->shiprocket()->handleUnauthorized();
            $authError = $this->shiprocket()->getLastAuthError();
            if ($authToken !== '') {
                $response = $this->executeShiprocketCurl($url, 'GET', $authToken);
            }
        }

        $httpCode = (int) ($response['http_code'] ?? 0);
        $responseDecoded = $response['json'] ?? null;

        return [
            'http_code' => $httpCode,
            'data' => $responseDecoded,
            'success' => $httpCode === 200 && !empty($responseDecoded),
            'params' => $params,
            'request_url' => $url,
            'curl_error' => $response['curl_error'] ?? '',
            'auth_error' => $authError,
        ];
    }
    //get labels from shiprocket
    public function getShiprocketLabels($shipmentId) {
        $response = $this->shiprocketJsonRequest('POST', '/v1/external/courier/generate/label', [
            'shipment_id' => [(string) $shipmentId],
        ]);

        return $response['json'] ?? null;
    }
    //get tracking info from shiprocket
    public function getShiprocketTrackingInfo($shipment_id) {
        $response = $this->shiprocketJsonRequest(
            'GET',
            '/v1/external/courier/track/shipment/' . rawurlencode((string) $shipment_id)
        );

        return $response['json'] ?? null;
    }
    //get tracking info from shiprocket by AWB code
    public function getShiprocketTrackingByAWB($awb_code) {
        $response = $this->shiprocketJsonRequest(
            'GET',
            '/v1/external/courier/track/awb/' . rawurlencode((string) $awb_code)
        );

        return $response['json'] ?? null;
    }
    //get awb info from shiprocket (optional courier_id = user-selected courier from serviceability)
    public function getShiprocketAwbInfo($shipment_id, $courier_id = null) {
        $body = ['shipment_id' => $shipment_id];
        if ($courier_id !== null && $courier_id !== '' && is_numeric($courier_id)) {
            $body['courier_id'] = (int) $courier_id;
        }

        $response = $this->shiprocketJsonRequest('POST', '/v1/external/courier/assign/awb', $body);

        return $response['json'] ?? null;
    }
    public function updateDispatchAwbCode($shipment_id, $awb_code, array $extra = []) {
        $fields = array_merge(['awb_code' => $awb_code], $extra);
        return $this->updateDispatchByShiprocketShipmentId((int) $shipment_id, $fields);
    }

    public function updateDispatchByShiprocketShipmentId(int $shipment_id, array $data): bool
    {
        if ($shipment_id <= 0 || empty($data)) {
            return false;
        }

        $setParts = [];
        $types = '';
        $values = [];

        foreach ($data as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $setParts[] = "$field = ?";
            if (in_array($field, ['courier_company_id', 'shipper_id', 'courier_partner_id'], true)) {
                $types .= 'i';
                $values[] = (int) $value;
            } else {
                $types .= 's';
                $values[] = $value;
            }
        }

        if (empty($setParts)) {
            return false;
        }

        $beforeDispatch = [];
        $lookup = $this->db->prepare('SELECT * FROM vp_dispatch_details WHERE shiprocket_shipment_id = ? LIMIT 1');
        if ($lookup) {
            $lookup->bind_param('i', $shipment_id);
            $lookup->execute();
            $beforeDispatch = $lookup->get_result()?->fetch_assoc() ?: [];
            $lookup->close();
        }

        $types .= 'i';
        $values[] = $shipment_id;
        $sql = 'UPDATE vp_dispatch_details SET ' . implode(', ', $setParts) . ' WHERE shiprocket_shipment_id = ?';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param($types, ...$values);
        $result = $stmt->execute();
        $stmt->close();

        if ($result && !empty($beforeDispatch['id'])) {
            $this->maybeLogExoticIndiaShipment((int) $beforeDispatch['id'], $beforeDispatch);
        }

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
    
    public function updateDispatchStatus($dispatchId, $status, $tracking_url = null, $etd = null, $edd = null) {
        $fields = ['shipment_status' => $status];
        $argc = func_num_args();

        if ($argc >= 3 && $tracking_url !== null && $tracking_url !== '') {
            $fields['tracking_url'] = $tracking_url;
        }
        if ($argc >= 4 && $etd !== null && $etd !== '') {
            $normalizedEtd = normalizeDispatchDeliveryDatetime($etd);
            if ($normalizedEtd !== null) {
                $fields['etd'] = $normalizedEtd;
            }
        }
        if ($argc >= 5 && $edd !== null && $edd !== '') {
            $normalizedEdd = normalizeDispatchDeliveryDatetime($edd);
            if ($normalizedEdd !== null) {
                $fields['edd'] = $normalizedEdd;
            }
        }

        return $this->updateDispatch($dispatchId, $fields);
    }

    public function updateDispatch($dispatchId, $data) {
        if (empty($data)) return false;

        $record = $this->getDispatchById($dispatchId);
        $baseDate = is_array($record) ? (string) ($record['dispatch_date'] ?? date('Y-m-d H:i:s')) : date('Y-m-d H:i:s');
        if (array_key_exists('etd', $data) && $data['etd'] !== null && $data['etd'] !== '') {
            $data['etd'] = normalizeDispatchDeliveryDatetime($data['etd'], $baseDate);
        }
        if (array_key_exists('edd', $data) && $data['edd'] !== null && $data['edd'] !== '') {
            $data['edd'] = normalizeDispatchDeliveryDatetime($data['edd'], $baseDate);
        }
        if (array_key_exists('etd', $data) && $data['etd'] === null) {
            unset($data['etd']);
        }
        if (array_key_exists('edd', $data) && $data['edd'] === null) {
            unset($data['edd']);
        }
        if (empty($data)) {
            return false;
        }
        
        $setParts = [];
        $types = '';
        $values = [];
        
        foreach ($data as $field => $value) {
            $setParts[] = "$field = ?";
            if (in_array($field, ['courier_company_id', 'shipper_id', 'courier_partner_id', 'invoice_id', 'box_no', 'created_by'], true)) {
                $types .= 'i';
                $values[] = (int) $value;
            } else {
                $types .= 's';
                $values[] = $value;
            }
        }
        
        $types .= 'i'; // for dispatchId
        $values[] = $dispatchId;
        
        $sql = "UPDATE vp_dispatch_details SET " . implode(', ', $setParts) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param($types, ...$values);
        $result = $stmt->execute();
        $stmt->close();
        if ($result) {
            $this->maybeLogExoticIndiaShipment($dispatchId, is_array($record) ? $record : []);
        }

        return $result;
    }
      
    public function getDispatchRecordsByInvoiceId($invoiceId) {
        $sql = "SELECT * FROM vp_dispatch_details WHERE invoice_id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param('i', $invoiceId);
        if ($stmt->execute()) {
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        return false;
    }
    public function retryShiprocketApiCalls($dispatchId) {
        //fetch dispatch record
        $dispatchRecord = $this->getDispatchById($dispatchId);
        if(!$dispatchRecord) {
            return ['success' => false, 'message' => 'Dispatch record not found'];
        }
        $shipmentId = $dispatchRecord['shiprocket_shipment_id'];
        if(!$shipmentId) {
            return ['success' => false, 'message' => 'No Shiprocket shipment ID associated with this dispatch record'];
        }
        //retry AWB info API call
        $awbInfoResponse = $this->getShiprocketAwbInfo($shipmentId);
        //retry label info API call
        $labelInfoResponse = $this->getShiprocketLabels($shipmentId);
        //update dispatch record with new AWB code and label URL if available
        if($awbInfoResponse && isset($awbInfoResponse['awb_assign_status']) && $awbInfoResponse['awb_assign_status'] == 1) {
            $assignment = buildShiprocketAssignmentUpdate($this->db, $awbInfoResponse, [
                'courier_name' => (string) ($dispatchRecord['courier_name'] ?? ''),
                'courier_id' => (string) ($dispatchRecord['courier_company_id'] ?? ''),
                'partner_code' => 'shiprocket',
            ]);
            if (!empty($assignment)) {
                $this->updateDispatchByShiprocketShipmentId((int) $shipmentId, $assignment);
            }
            $awbCode = $assignment['awb_code'] ?? null;
        }
        if($labelInfoResponse && isset($labelInfoResponse['label_created']) && $labelInfoResponse['label_created'] == 1) {
            $labelUrl = $labelInfoResponse['label_url'] ?? null;
            if($labelUrl) {
                $this->updateDispatchLabelUrl($shipmentId, $labelUrl);
            }
        }
        return ['success' => true,'labelUrl' => $labelUrl ?? null, 'awbCode' => $awbCode ?? null, 'data' => ['awb_info_response' => $awbInfoResponse, 'label_info_response' => $labelInfoResponse], 'message' => 'API calls retried and dispatch record updated if new data was available'];
    }
    public function cancelShiprocketShipment($shiprocketOrderId) {
        //fetch dispatch record
        if(!$shiprocketOrderId) {
            return ['success' => false, 'message' => 'No Shiprocket order ID associated with this dispatch record'];
        }
        $response = $this->shiprocketJsonRequest('POST', '/v1/external/orders/cancel', [
            'ids' => [$shiprocketOrderId],
        ]);
        $responseDecoded = $response['json'] ?? null;
        if (!is_array($responseDecoded)) {
            return [
                'success' => false,
                'message' => (string) ($response['auth_error'] ?? 'No response from Shiprocket cancel API'),
                'data' => $response,
            ];
        }

        return [
            'success' => $responseDecoded['status_code'] ?? $responseDecoded['status'] ?? false,
            'message' => $responseDecoded['message'] ?? 'No response message',
            'data' => $responseDecoded,
        ];
        if(isset($responseDecoded['status_code']) && $responseDecoded['status_code'] == 200) {
            //update dispatch record to mark as cancelled
            // $sql = "UPDATE vp_dispatch_details SET shipment_status = 'cancelled' WHERE id = ?";
            // $stmt = $this->db->prepare($sql);
            // if (!$stmt) return ['success' => false, 'message' => 'Database error: ' . $this->db->error];
            // $stmt->bind_param('i', $dispatchId);
            // if ($stmt->execute()) {
            //     return ['success' => true, 'message' => 'Shipment cancelled successfully and dispatch record updated'];
            // } else {
            //     return ['success' => false, 'message' => 'Shipment cancelled but failed to update dispatch record: ' . $stmt->error];
            // }
        } else {
            return ['success' => false, 'message' => 'Failed to cancel shipment: ' . ($responseDecoded['message'] ?? 'Unknown error'), 'data' => $responseDecoded];
        }
    }
    public function getDispatchByBatchNo($batch_no) {
        // $sql = "SELECT * FROM vp_dispatch_details WHERE batch_no = ?";
        // $stmt = $this->db->prepare($sql);
        // if (!$stmt) return false;

        // $stmt->bind_param('s', $batch_no);
        // if ($stmt->execute()) {
        //     return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        // }
        // return false;
        // Get all dispatch records for this batch that don't have shipment_id (failed shipments)
            $sql = "SELECT dd.* FROM vp_dispatch_details dd
                    INNER JOIN vp_invoices vi ON dd.invoice_id = vi.id
                    WHERE vi.batch_no = ? AND (dd.shiprocket_shipment_id IS NULL OR dd.shiprocket_shipment_id = '')";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('s', $batch_no);
            $stmt->execute();
            $result = $stmt->get_result();
            $dispatch_records = $result->fetch_all(MYSQLI_ASSOC);
            return $dispatch_records;
    }
    public function getDispatchRecordsByIds($ids) {
        if(empty($ids)) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT * FROM vp_dispatch_details WHERE id IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        // Dynamically bind parameters
        $types = str_repeat('i', count($ids));
        $stmt->bind_param($types, ...$ids);

        if ($stmt->execute()) {
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        return false;
    }
    public function getDispatchRecordsByInvoiceIds($invoiceIds) {
        if(empty($invoiceIds)) return [];
        $placeholders = implode(',', array_fill(0, count($invoiceIds), '?'));
        $sql = "SELECT * FROM vp_dispatch_details WHERE invoice_id IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        // Dynamically bind parameters
        $types = str_repeat('i', count($invoiceIds));
        $stmt->bind_param($types, ...$invoiceIds);

        if ($stmt->execute()) {
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $grouped = [];
            foreach ($rows as $row) {
                $invoiceId = (int)($row['invoice_id'] ?? 0);
                if ($invoiceId <= 0) {
                    continue;
                }
                if (!isset($grouped[$invoiceId])) {
                    $grouped[$invoiceId] = [];
                }
                $grouped[$invoiceId][] = $row;
            }

            return $grouped;
        }
        return false;
    }
}