<?php

/**
 * Payload for Exotic India vendor-api/order/modify (item-level status update).
 */
class OrderModifyRequest
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $level,
        public readonly int $orderStatus,
        public readonly string $itemCode,
        public readonly string $size = '',
        public readonly string $color = ''
    ) {
    }

    /**
     * @param array<string, mixed> $data Legacy shape used across controllers/models.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            trim((string) ($data['orderid'] ?? '')),
            trim((string) ($data['level'] ?? 'item')) ?: 'item',
            (int) ($data['order_status'] ?? 0),
            trim((string) ($data['itemcode'] ?? '')),
            trim((string) ($data['size'] ?? '')),
            trim((string) ($data['color'] ?? ''))
        );
    }

    /**
     * @return array<string, string>
     */
    public function toFormFields(): array
    {
        return [
            'makeRequestOf' => 'vendors-orderjson',
            'orderid' => $this->orderId,
            'level' => $this->level,
            'order_status' => (string) $this->orderStatus,
            'itemcode' => $this->itemCode,
            'size' => $this->size,
            'color' => $this->color,
        ];
    }
}
