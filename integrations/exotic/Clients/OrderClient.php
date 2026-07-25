<?php

require_once __DIR__ . '/../Dto/OrderModifyRequest.php';

/**
 * Exotic India vendor-api order endpoints (phase 1: /order/modify).
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
}
