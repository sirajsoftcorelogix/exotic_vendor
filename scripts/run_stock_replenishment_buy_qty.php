<?php

declare(strict_types=1);

/**
 * Autofill vp_products.replenishment_buy_qty for book products using
 * BookPurchaseReplenishment (lookback sales + global min-stock / purchase-threshold %).
 *
 * Does not create purchase orders. Order-import still generates POs when stock
 * is at or below the purchase threshold.
 *
 * Usage (project root):
 *   php scripts/run_stock_replenishment_buy_qty.php
 *   php scripts/run_stock_replenishment_buy_qty.php --dry-run
 *   php scripts/run_stock_replenishment_buy_qty.php --limit=200
 */

$isCli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');
}

$root = dirname(__DIR__);
chdir($root);

function replenish_fail(string $msg, int $code = 1): void
{
    fwrite(STDERR, $msg . PHP_EOL);
    exit($code);
}

$configPath = $root . DIRECTORY_SEPARATOR . 'config.php';
if (!is_file($configPath)) {
    replenish_fail('Missing config.php');
}

/** @var array $config */
$config = require $configPath;
$db = $config['db'] ?? [];
if (!is_array($db) || empty($db['host']) || empty($db['name'])) {
    replenish_fail('config.php must define db host and name.');
}

$argvList = $_SERVER['argv'] ?? [];
$dryRun = in_array('--dry-run', $argvList, true);
$limit = 0;
foreach ($argvList as $arg) {
    if (preg_match('/^--limit=(\d+)$/', (string) $arg, $m)) {
        $limit = max(0, (int) $m[1]);
    }
}

try {
    $conn = new mysqli(
        (string) ($db['host'] ?? '127.0.0.1'),
        (string) ($db['user'] ?? ''),
        (string) ($db['pass'] ?? ''),
        (string) ($db['name'] ?? ''),
        (int) ($db['port'] ?? 3306)
    );
} catch (Throwable $e) {
    replenish_fail('DB connect failed: ' . $e->getMessage());
}

if ($conn->connect_error) {
    replenish_fail('DB connect failed: ' . $conn->connect_error);
}

$conn->set_charset((string) ($db['charset'] ?? 'utf8mb4'));

$colRes = $conn->query("SHOW COLUMNS FROM vp_products LIKE 'replenishment_buy_qty'");
$hasCol = $colRes && $colRes->num_rows > 0;
if ($colRes) {
    $colRes->free();
}
if (!$hasCol) {
    $sql = 'ALTER TABLE vp_products ADD COLUMN replenishment_buy_qty INT UNSIGNED NOT NULL DEFAULT 0 AFTER stock_replenishment_months';
    if (!$conn->query($sql)) {
        replenish_fail('Could not add replenishment_buy_qty: ' . $conn->error);
    }
    echo "Added column vp_products.replenishment_buy_qty\n";
}

$conn->query(
    "INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES
        ('stock_replenishment_min_stock_percent', '50'),
        ('stock_replenishment_purchase_threshold_percent', '25')"
);

require_once $root . '/models/product/product.php';
require_once $root . '/helpers/BookPurchaseReplenishment.php';

$productModel = new product($conn);
$service = new BookPurchaseReplenishment($conn);

$total = $productModel->countBookProductsForReplenishment();
$toProcess = $limit > 0 ? min($limit, $total) : $total;
$batchSize = 100;

echo ($dryRun ? "[DRY RUN] " : '') . "Book products: {$total}; processing: {$toProcess}\n";

$processed = 0;
$updated = 0;
$nonzero = 0;
$shouldBuy = 0;
$offset = 0;

while ($processed < $toProcess) {
    $fetch = min($batchSize, $toProcess - $processed);
    $rows = $productModel->listBookProductsForReplenishment($fetch, $offset);
    if ($rows === []) {
        break;
    }

    foreach ($rows as $product) {
        if (!$service->isBookProduct($product)) {
            $processed++;
            continue;
        }

        $evaluation = $service->evaluate($product, 0);
        $qty = (int) ($evaluation['recommended_buy_qty'] ?? 0);
        $prev = (int) ($product['replenishment_buy_qty'] ?? 0);

        if ($qty > 0) {
            $nonzero++;
        }
        if (!empty($evaluation['should_buy'])) {
            $shouldBuy++;
        }
        if ($qty !== $prev) {
            $updated++;
            if (!$dryRun) {
                $service->persistRecommendedBuyQty($productModel, (int) ($product['id'] ?? 0), $evaluation);
            }
        }

        $processed++;
    }

    $offset += count($rows);
    echo "  processed {$processed}/{$toProcess} (qty>0: {$nonzero}, would change: {$updated}, PO-eligible: {$shouldBuy})\n";
}

echo "Done. processed={$processed} changed=" . ($dryRun ? "{$updated} (not written)" : (string) $updated)
    . " nonzero={$nonzero} po_eligible={$shouldBuy}\n";
