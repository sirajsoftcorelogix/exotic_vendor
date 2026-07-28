<?php

require_once __DIR__ . '/../Dto/OrderModifyRequest.php';
require_once __DIR__ . '/../Support/VendorOrderFetchParser.php';

/**
 * Exotic India vendor-api order endpoints (modify, fetch).
 */
class OrderClient
{
    /**
     * @return array{success:bool,http_code:int,message:string,raw:string,data:array}
     */
    public function modifyOrderItem(OrderModifyRequest $request): array
    {
        require_once __DIR__ . '/../vendor_api.php';

        $postBody = http_build_query($request->toFormFields());
        $result = exotic_india_api_post('/order/modify', $postBody, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);

        return [
            'success' => !empty($result['success']),
            'http_code' => (int) ($result['http_code'] ?? 0),
            'message' => (string) ($result['message'] ?? ''),
            'raw' => (string) ($result['raw'] ?? ''),
            'data' => is_array($result['data'] ?? null) ? $result['data'] : [],
        ];
    }

    /**
     * POST /order/fetch — live order JSON (import, refresh, investigation).
     *
     * @param array{orderid?:string,from_date?:int,to_date?:int,page?:int} $params
     * @return array{success:bool,message:string,http_code:int,raw:string,data:array,orders:list<array<string,mixed>>}
     */
    public function fetchOrdersJson(array $params): array
    {
        require_once __DIR__ . '/../vendor_api.php';

        $postFields = ['makeRequestOf' => 'vendors-orderjson'];
        if (!empty($params['orderid'])) {
            $postFields['orderid'] = trim((string) $params['orderid']);
        }
        if (isset($params['from_date'])) {
            $postFields['from_date'] = (int) $params['from_date'];
        }
        if (isset($params['to_date'])) {
            $postFields['to_date'] = (int) $params['to_date'];
        }
        if (isset($params['page'])) {
            $postFields['page'] = (int) $params['page'];
        }

        $result = exotic_india_api_post('/order/fetch', http_build_query($postFields), [
            'Content-Type: application/x-www-form-urlencoded',
        ]);

        $data = is_array($result['data'] ?? null) ? $result['data'] : [];
        $orders = VendorOrderFetchParser::normalizeOrdersList(
            is_array($data['orders'] ?? null) ? $data['orders'] : []
        );

        $success = !empty($result['success']) && $orders !== [];
        $message = (string) ($result['message'] ?? '');
        if (!$success && $message === '') {
            $message = $orders === [] ? 'No order data from vendor API' : 'Vendor API request failed';
        }

        return [
            'success' => $success,
            'message' => $message,
            'http_code' => (int) ($result['http_code'] ?? 0),
            'raw' => (string) ($result['raw'] ?? ''),
            'data' => $data,
            'orders' => $orders,
        ];
    }

    /**
     * @return array{success:bool,message:string,http_code:int,raw:string,data:array,orders:list<array<string,mixed>>}
     */
    public function fetchOrderByNumber(string $orderNumber): array
    {
        $orderNumber = trim($orderNumber);
        if ($orderNumber === '') {
            return [
                'success' => false,
                'message' => 'Order number missing',
                'http_code' => 0,
                'raw' => '',
                'data' => [],
                'orders' => [],
            ];
        }

        return $this->fetchOrdersJson(['orderid' => $orderNumber]);
    }
}
