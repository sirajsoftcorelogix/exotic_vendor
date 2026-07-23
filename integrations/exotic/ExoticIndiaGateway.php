<?php

require_once __DIR__ . '/Clients/OrderClient.php';
require_once __DIR__ . '/Clients/RetailApiClient.php';
require_once __DIR__ . '/Dto/OrderModifyRequest.php';
require_once __DIR__ . '/Support/OrderStatusResolver.php';

/**
 * Central entry point for Exotic India integrations.
 *
 * - vendor-api (https://www.exoticindia.com/vendor-api/*) — order modify, product fetch, etc.
 * - retail-api (https://www.exoticindia.com/api/*) — POS cart, product/code, order/create
 */
class ExoticIndiaGateway
{
    private OrderClient $orderClient;
    private OrderStatusResolver $statusResolver;
    private ?RetailApiClient $retailApiClient;

    public function __construct(
        OrderClient $orderClient,
        OrderStatusResolver $statusResolver,
        ?RetailApiClient $retailApiClient = null
    ) {
        $this->orderClient = $orderClient;
        $this->statusResolver = $statusResolver;
        $this->retailApiClient = $retailApiClient;
    }

    public static function create(?mysqli $db = null): self
    {
        global $conn;

        $db = $db ?? $conn;
        if (!$db instanceof mysqli) {
            throw new RuntimeException('ExoticIndiaGateway requires a mysqli connection.');
        }

        return new self(
            new OrderClient(),
            new OrderStatusResolver($db),
            RetailApiClient::create($db)
        );
    }

    public function retail(): RetailApiClient
    {
        if ($this->retailApiClient === null) {
            $this->retailApiClient = RetailApiClient::create();
        }

        return $this->retailApiClient;
    }

    /**
     * @param array<string, mixed> $apiData Legacy payload: orderid, level, order_status, itemcode, size, color.
     * @return array{success:bool,http_code:int,message:string,raw:string,data?:array}
     */
    public function updateOrderItemStatus(array $apiData): array
    {
        $request = OrderModifyRequest::fromArray($apiData);

        if ($request->orderId === '' || $request->itemCode === '' || $request->orderStatus <= 0) {
            return [
                'success' => false,
                'http_code' => 0,
                'message' => 'Invalid order modify request (order id, item code, and status are required).',
                'raw' => '',
            ];
        }

        return $this->orderClient->modifyOrderItem($request);
    }

    /**
     * Resolve local status slug and sync one vp_orders line to Exotic India.
     *
     * @param array<string, mixed> $orderRow Row from vp_orders (order_number, item_code, size, color).
     * @return array{success:bool,http_code:int,message:string,raw:string,skipped?:bool,data?:array}
     */
    public function updateOrderLineFromSlug(string $statusSlug, array $orderRow): array
    {
        $adminId = $this->statusResolver->resolveAdminId($statusSlug);
        if ($adminId <= 0) {
            return [
                'success' => true,
                'skipped' => true,
                'http_code' => 0,
                'message' => 'This status is not synced to Exotic India.',
                'raw' => '',
            ];
        }

        return $this->updateOrderItemStatus([
            'orderid' => $orderRow['order_number'] ?? '',
            'level' => 'item',
            'order_status' => $adminId,
            'itemcode' => $orderRow['item_code'] ?? '',
            'size' => $orderRow['size'] ?? '',
            'color' => $orderRow['color'] ?? '',
        ]);
    }

    public function resolveAdminIdForStatusSlug(string $slug): int
    {
        return $this->statusResolver->resolveAdminId($slug);
    }
}
