<?php

declare(strict_types=1);

/**
 * Price India on product detail = base price_india + GST (same as POS sell price).
 *
 * @param array<string, mixed> $product
 */
function labelPriceIndiaIncludingGst(array $product): float
{
    $base = (float) ($product['price_india'] ?? 0);
    if ($base <= 0) {
        return 0.0;
    }

    $gstPercent = max(0.0, (float) ($product['gst'] ?? 0));

    return $base * (1 + $gstPercent / 100);
}

/**
 * @param array<string, mixed> $product
 */
function formatLabelPriceIndia(array $product, int $decimals = 0): string
{
    $amount = labelPriceIndiaIncludingGst($product);
    if ($amount <= 0) {
        return '';
    }

    return number_format($amount, $decimals, '.', ',');
}
