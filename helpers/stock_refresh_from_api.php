<?php

require_once __DIR__ . '/../models/product/product.php';

/**
 * Resolve vp_products.id from an imported order line payload.
 */
function resolveProductIdFromOrderImportLine(product $productModel, array $line): int
{
    $itemCode = trim((string) ($line['item_code'] ?? ''));
    $size = trim((string) ($line['size'] ?? ''));
    $color = trim((string) ($line['color'] ?? ''));
    $sku = trim((string) ($line['sku'] ?? ''));

    if ($itemCode !== '') {
        $row = $productModel->findByItemCodeSizeColor($itemCode, $size, $color);
        if (is_array($row) && !empty($row['id'])) {
            return (int) $row['id'];
        }
    }

    if ($sku !== '') {
        $row = $productModel->getProductByskuExact($sku);
        if (is_array($row) && !empty($row['id'])) {
            return (int) $row['id'];
        }
    }

    return 0;
}

/**
 * For fresh products (no movements, or one zero-balance movement), run stock report refresh.
 * Failures are logged and do not block order import.
 *
 * @param array<int, true> $refreshedProductIds Dedupe within a single import batch.
 * @return array<string, mixed>
 */
function tryRefreshFreshProductStockFromApiForOrderLine(
    mysqli $conn,
    product $productModel,
    array $line,
    array &$refreshedProductIds = []
): array {
    $productId = resolveProductIdFromOrderImportLine($productModel, $line);
    if ($productId <= 0) {
        return ['attempted' => false, 'skipped' => true, 'reason' => 'product_not_found'];
    }

    if (isset($refreshedProductIds[$productId])) {
        return [
            'attempted' => false,
            'skipped' => true,
            'reason' => 'already_refreshed_in_batch',
            'product_id' => $productId,
        ];
    }

    $eligibility = $productModel->getStockReportInlineRefreshEligibility($productId);
    if (empty($eligibility['eligible'])) {
        return [
            'attempted' => false,
            'skipped' => true,
            'reason' => 'not_fresh',
            'product_id' => $productId,
            'movement_count' => (int) ($eligibility['movement_count'] ?? 0),
        ];
    }

    $refreshedProductIds[$productId] = true;

    try {
        require_once __DIR__ . '/../controllers/POSRegisterController.php';
        $posController = new POSRegisterController($conn);
        $result = $posController->performStockReportRefresh($productId);

        return array_merge(['attempted' => true, 'skipped' => false], $result);
    } catch (Throwable $e) {
        error_log('[order import fresh stock refresh] product_id=' . $productId . ': ' . $e->getMessage());

        return [
            'attempted' => true,
            'skipped' => false,
            'success' => false,
            'product_id' => $productId,
            'message' => $e->getMessage(),
        ];
    }
}
