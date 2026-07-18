<?php

require_once __DIR__ . '/../invoice_number_resolver.php';
require_once __DIR__ . '/pos_order_pricing.php';

class InvoiceRequestBuilder
{
    /**
     * Build a normalized invoice create request from HTTP POST (create form / POS internal post).
     *
     * @param array<string, mixed> $post
     * @param array<string, mixed> $options discount_meta, line_items_meta, reserved_invoice_numbers, clear_invoice_session
     * @return array<string, mixed>
     */
    public static function fromPost(array $post, array $options = []): array
    {
        $orderNumbers = isset($post['order_number']) && is_array($post['order_number']) ? $post['order_number'] : [];

        return [
            'source' => (string)($options['source'] ?? 'post'),
            'header' => [
                'invoice_date' => isset($post['invoice_date']) ? (string)$post['invoice_date'] : date('Y-m-d'),
                'customer_id' => isset($post['customer_id']) ? (int)$post['customer_id'] : 0,
                'vp_order_info_id' => isset($post['vp_order_info_id']) ? (int)$post['vp_order_info_id'] : 0,
                'status' => isset($post['status']) ? trim((string)$post['status']) : 'final',
                'subtotal' => isset($post['subtotal']) ? (float)$post['subtotal'] : 0.0,
                'tax_amount' => isset($post['tax_amount']) ? (float)$post['tax_amount'] : 0.0,
                'discount_amount' => isset($post['discount_amount']) ? (float)$post['discount_amount'] : 0.0,
                'total_amount' => isset($post['total_amount']) ? (float)$post['total_amount'] : 0.0,
                'currency' => self::firstCurrency($post['currency'] ?? []),
                'pos_flag' => isset($post['pos_flag'])
                    ? (int)$post['pos_flag']
                    : ((string)($options['source'] ?? '') === 'pos' ? 1 : 0),
                'batch_no' => trim((string)($post['batch_no'] ?? '')),
                'created_by' => (int)($options['created_by'] ?? ($_SESSION['user']['id'] ?? 0)),
            ],
            'lines' => self::linesFromPost($post),
            'discount_meta' => is_array($options['discount_meta'] ?? null) ? $options['discount_meta'] : null,
            'line_items_meta' => is_array($options['line_items_meta'] ?? null) ? $options['line_items_meta'] : [],
            'international' => self::internationalFromPost($post),
            'options' => [
                'custom_invoice_number' => trim((string)($post['custom_invoice_number'] ?? ($options['custom_invoice_number'] ?? ''))),
                'reserved_invoice_numbers' => is_array($options['reserved_invoice_numbers'] ?? null)
                    ? $options['reserved_invoice_numbers']
                    : [],
                'clear_invoice_session' => !array_key_exists('clear_invoice_session', $options)
                    || !empty($options['clear_invoice_session']),
                'duplicate_order_check' => !array_key_exists('duplicate_order_check', $options)
                    || !empty($options['duplicate_order_check']),
                'update_order_invoice_id' => !array_key_exists('update_order_invoice_id', $options)
                    || !empty($options['update_order_invoice_id']),
            ],
            'order_numbers' => array_values(array_unique(array_map('strval', $orderNumbers))),
        ];
    }

    /**
     * @param list<array<string, mixed>> $orderItems vp_orders rows
     * @param array<string, mixed> $headerOverrides
     */
    public static function fromOrderLines(
        array $orderItems,
        array $headerOverrides,
        array $options = []
    ): array {
        $lines = [];
        foreach ($orderItems as $order) {
            $qty = max(1, (int)($order['quantity'] ?? 1));
            $gst = (float)($order['gst'] ?? 0);
            $unitPretax = pos_order_pretax_unit_price($order, 'disc');

            $amount = $unitPretax * $qty;
            $taxAmount = ($amount * $gst) / 100;
            $cgstRate = $gst / 2;
            $sgstRate = $gst / 2;

            $lines[] = [
                'order_number' => (string)($order['order_number'] ?? ''),
                'item_code' => (string)($order['item_code'] ?? ''),
                'item_name' => (string)($order['title'] ?? 'Product'),
                'hsn' => (string)($order['hsn'] ?? ($order['hsn_code'] ?? '')),
                'quantity' => $qty,
                'unit_price' => round($unitPretax, 4),
                'tax_rate' => $gst,
                'cgst_rate' => $cgstRate,
                'sgst_rate' => $sgstRate,
                'igst_rate' => 0.0,
                'box_no' => (string)($order['box_no'] ?? ''),
                'currency' => (string)($order['currency'] ?? 'INR'),
                'image_url' => (string)($order['image'] ?? ''),
                'groupname' => (string)($order['groupname'] ?? ''),
                'size' => (string)($order['size'] ?? ''),
                'color' => (string)($order['color'] ?? ''),
                'vp_order_id' => (int)($order['id'] ?? 0),
                'line_total' => round($amount + $taxAmount, 2),
            ];
        }

        $subtotal = 0.0;
        $taxAmount = 0.0;
        foreach ($lines as $line) {
            $amount = (float)$line['unit_price'] * (int)$line['quantity'];
            $subtotal += $amount;
            $taxAmount += ($amount * (float)$line['tax_rate']) / 100;
        }

        $orderNumbers = array_values(array_unique(array_filter(array_map(
            static fn(array $line): string => (string)($line['order_number'] ?? ''),
            $lines
        ))));

        return [
            'source' => (string)($options['source'] ?? 'dispatch'),
            'header' => array_merge([
                'invoice_date' => date('Y-m-d'),
                'customer_id' => 0,
                'vp_order_info_id' => 0,
                'status' => 'final',
                'subtotal' => round($subtotal, 2),
                'tax_amount' => round($taxAmount, 2),
                'discount_amount' => 0.0,
                'total_amount' => round($subtotal + $taxAmount, 2),
                'currency' => $lines[0]['currency'] ?? 'INR',
                'pos_flag' => (int)($options['pos_flag'] ?? 0),
                'batch_no' => '',
                'created_by' => (int)($options['created_by'] ?? ($_SESSION['user']['id'] ?? 0)),
            ], $headerOverrides),
            'lines' => $lines,
            'discount_meta' => is_array($options['discount_meta'] ?? null) ? $options['discount_meta'] : null,
            'line_items_meta' => is_array($options['line_items_meta'] ?? null) ? $options['line_items_meta'] : [],
            'international' => null,
            'options' => [
                'custom_invoice_number' => trim((string)($options['custom_invoice_number'] ?? '')),
                'reserved_invoice_numbers' => is_array($options['reserved_invoice_numbers'] ?? null)
                    ? $options['reserved_invoice_numbers']
                    : [],
                'clear_invoice_session' => false,
                'duplicate_order_check' => !empty($options['duplicate_order_check']),
                'update_order_invoice_id' => !array_key_exists('update_order_invoice_id', $options)
                    || !empty($options['update_order_invoice_id']),
                'update_order_by' => (string)($options['update_order_by'] ?? 'order_number'),
            ],
            'order_numbers' => $orderNumbers,
        ];
    }

    /**
     * @param array<string, mixed> $post
     * @return list<array<string, mixed>>
     */
    private static function linesFromPost(array $post): array
    {
        $orderNumbers = isset($post['order_number']) && is_array($post['order_number']) ? $post['order_number'] : [];
        $lines = [];

        foreach ($orderNumbers as $idx => $orderNumber) {
            $qty = isset($post['quantity'][$idx]) ? (int)$post['quantity'][$idx] : 0;
            $unitPrice = isset($post['unit_price'][$idx]) ? (float)$post['unit_price'][$idx] : 0.0;
            $taxRate = isset($post['tax_rate'][$idx]) ? (float)$post['tax_rate'][$idx] : 0.0;
            $cgstRate = isset($post['cgst'][$idx]) ? (float)$post['cgst'][$idx] : 0.0;
            $sgstRate = isset($post['sgst'][$idx]) ? (float)$post['sgst'][$idx] : 0.0;
            $igstRate = isset($post['igst'][$idx]) ? (float)$post['igst'][$idx] : 0.0;
            $amount = $qty * $unitPrice;
            $taxAmount = ($amount * $taxRate) / 100;

            $lines[] = [
                'order_number' => (string)$orderNumber,
                'item_code' => trim((string)($post['item_code'][$idx] ?? '')),
                'item_name' => trim((string)($post['item_name'][$idx] ?? '')),
                'hsn' => trim((string)($post['hsn'][$idx] ?? '')),
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'tax_rate' => $taxRate,
                'cgst_rate' => $cgstRate,
                'sgst_rate' => $sgstRate,
                'igst_rate' => $igstRate,
                'box_no' => trim((string)($post['box_no'][$idx] ?? '')),
                'currency' => (string)($post['currency'][$idx] ?? 'INR'),
                'image_url' => trim((string)($post['image_url'][$idx] ?? '')),
                'groupname' => trim((string)($post['groupname'][$idx] ?? '')),
                'line_total' => round($amount + $taxAmount, 2),
            ];
        }

        return $lines;
    }

    /**
     * @param mixed $currencyField
     */
    private static function firstCurrency($currencyField): string
    {
        if (is_array($currencyField)) {
            return (string)($currencyField[0] ?? 'INR');
        }

        return (string)($currencyField ?: 'INR');
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>|null
     */
    private static function internationalFromPost(array $post): ?array
    {
        $currency = self::firstCurrency($post['currency'] ?? []);
        if ($currency === '' || $currency === 'INR') {
            return null;
        }

        return [
            'pre_carriage_by' => trim((string)($post['pre_carriage_by'] ?? '')),
            'port_of_loading' => trim((string)($post['port_of_loading'] ?? '')),
            'port_of_discharge' => trim((string)($post['port_of_discharge'] ?? '')),
            'country_of_origin' => trim((string)($post['country_of_origin'] ?? '')),
            'country_of_final_destination' => trim((string)($post['country_of_final_destination'] ?? '')),
            'final_destination' => trim((string)($post['final_destination'] ?? '')),
            'usd_export_rate' => (float)($post['usd_export_rate'] ?? 0),
            'ap_cost' => (float)($post['ap_cost'] ?? 0),
            'freight_charge' => (float)($post['freight_charge'] ?? 0),
            'insurance_charge' => (float)($post['insurance_charge'] ?? 0),
            'shipping_bill_number' => trim((string)($post['shipping_bill_number'] ?? '')),
            'shipping_bill_date' => trim((string)($post['shipping_bill_date'] ?? '')),
            'shipping_port' => trim((string)($post['shipping_port'] ?? '')),
            'shipping_ref_clm' => trim((string)($post['shipping_ref_clm'] ?? '')),
            'shipping_currency' => trim((string)($post['shipping_currency'] ?? '')),
            'shipping_country_code' => trim((string)($post['shipping_country_code'] ?? '')),
            'shipping_exp_duty' => (float)($post['shipping_exp_duty'] ?? 0),
        ];
    }
}
