<?php
/**
 * share.php
 * Public product share page with Open Graph tags for WhatsApp preview card.
 * URL: https://yourdomain.com/exotic_vendor/share.php?id=7135
 */

require_once 'bootstrap/init/init.php';
require_once __DIR__ . '/settings/database/database.php';

$conn = Database::getConnection();

/**
 * Safe HTML escape
 */
if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Build absolute URL based on current script directory.
 * Example: /exotic_vendor/share.php  -> base = https://domain.com/exotic_vendor/
 */
function full_url(string $path): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    return $scheme . '://' . $host . ($dir ? $dir . '/' : '/') . ltrim($path, '/');
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    http_response_code(404);
    echo "Invalid product id.";
    exit;
}

// Fetch product
$sql = "
    SELECT
        id,
        title,
        item_code,
        sku,
        image,
        `groupname` AS category,
        color,
        size,
        prod_height,
        prod_width,
        prod_length,
        product_weight,
        product_weight_unit
    FROM vp_products
    WHERE id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo "Query prepare failed: " . e($conn->error);
    exit;
}

$stmt->bind_param("i", $product_id);
$stmt->execute();
$res = $stmt->get_result();
$product = $res ? $res->fetch_assoc() : null;

if (!$product) {
    http_response_code(404);
    echo "Product not found.";
    exit;
}

// -------- Build OG Title / Description / Image --------
$title = trim((string)($product['title'] ?? ''));
if ($title === '') {
    $title = 'Product ' . (string)($product['sku'] ?? $product_id);
}

$item = trim((string)($product['item_code'] ?? ''));
$sku  = trim((string)($product['sku'] ?? ''));
$cat  = trim((string)($product['category'] ?? ''));

$dimParts = [];
if (!empty($product['prod_height'])) $dimParts[] = $product['prod_height'] . 'H';
if (!empty($product['prod_width']))  $dimParts[] = $product['prod_width'] . 'W';
if (!empty($product['prod_length'])) $dimParts[] = $product['prod_length'] . 'L';
$dimensions = $dimParts ? (implode(' x ', $dimParts)) : '';

$weight = '';
if (isset($product['product_weight']) && (float)$product['product_weight'] > 0) {
    $unit = trim((string)($product['product_weight_unit'] ?? ''));
    $weight = 'Weight: ' . rtrim(rtrim((string)$product['product_weight'], '0'), '.') . ($unit ? " $unit" : '');
}

$descParts = array_filter([
    $item ? "Item: $item" : '',
    $sku ? "SKU: $sku" : '',
    $cat ? "Category: $cat" : '',
    $dimensions,
    $weight,
]);

$description = implode(' | ', $descParts);
if ($description === '') $description = 'Product details';

// OG image: absolute public URL required by WhatsApp
$imageRaw = trim((string)($product['image'] ?? ''));
$defaultImage = full_url('assets/img/default-product.png'); // ensure this exists or change

if ($imageRaw !== '' && preg_match('/^https?:\/\//i', $imageRaw)) {
    $ogImage = $imageRaw;
} elseif ($imageRaw !== '') {
    $ogImage = full_url(ltrim($imageRaw, '/'));
} else {
    $ogImage = $defaultImage;
}

// OG URL (absolute)
$ogUrl = full_url('share.php?id=' . $product_id);

// Optional: canonical
$canonical = $ogUrl;

// Guess MIME for OG (helps some scrapers)
$ogImageType = 'image/jpeg';
$lower = strtolower(parse_url($ogImage, PHP_URL_PATH) ?? '');
if (str_ends_with($lower, '.png'))  $ogImageType = 'image/png';
if (str_ends_with($lower, '.webp')) $ogImageType = 'image/webp';

// If you want to force refresh WhatsApp cache while testing, share URL like:
// share.php?id=7135&v=<?=time()?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($description) ?>">

    <!-- ✅ Open Graph for WhatsApp/Facebook -->
    <meta property="og:title" content="<?= e($title) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta property="og:image:secure_url" content="<?= e($ogImage) ?>">
    <meta property="og:image:type" content="<?= e($ogImageType) ?>">
    <meta property="og:url" content="<?= e($ogUrl) ?>">
    <meta property="og:type" content="product">
    <meta property="og:site_name" content="Exotic Vendor">

    <!-- Optional: dimensions help preview in some platforms -->
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <!-- ✅ Twitter (optional) -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($title) ?>">
    <meta name="twitter:description" content="<?= e($description) ?>">
    <meta name="twitter:image" content="<?= e($ogImage) ?>">

    <link rel="canonical" href="<?= e($canonical) ?>">

    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 0; background: #f6f7f9; }
        .wrap { max-width: 920px; margin: 24px auto; padding: 16px; }
        .card { background: #fff; border: 1px solid #e6e8ee; border-radius: 14px; overflow: hidden; box-shadow: 0 10px 20px rgba(0,0,0,.05); display: grid; grid-template-columns: 280px 1fr; }
        .img { background: #fafafa; display: flex; align-items: center; justify-content: center; padding: 16px; }
        .img img { width: 100%; max-width: 260px; height: auto; object-fit: contain; border-radius: 12px; }
        .content { padding: 18px; }
        .title { font-size: 20px; font-weight: 700; margin: 0 0 6px; }
        .meta { color: #556; font-size: 13px; margin-bottom: 12px; line-height: 1.4; }
        .kv { display: grid; grid-template-columns: 160px 1fr; gap: 8px 12px; font-size: 14px; }
        .kv div { padding: 8px 0; border-bottom: 1px dashed #e6e8ee; }
        .label { color: #667; font-weight: 600; }
        .btns { margin-top: 16px; display: flex; gap: 10px; flex-wrap: wrap; }
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 14px; border-radius: 10px; border: 1px solid #d7dbe6; text-decoration: none; color: #111; background: #fff; font-size: 14px; cursor: pointer; }
        .btn.primary { background: #25D366; border-color: #25D366; color: #fff; }
        .btn.dark { background: #111827; border-color: #111827; color: #fff; }
        @media (max-width: 780px) { .card { grid-template-columns: 1fr; } .kv { grid-template-columns: 120px 1fr; } }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="img">
            <img src="<?= e($ogImage) ?>" alt="<?= e($title) ?>">
        </div>

        <div class="content">
            <h1 class="title"><?= e($title) ?></h1>
            <div class="meta"><?= e($description) ?></div>

            <div class="kv">
                <div class="label">Item Code</div><div><?= e($item ?: 'N/A') ?></div>
                <div class="label">SKU</div><div><?= e($sku ?: 'N/A') ?></div>
                <div class="label">Category</div><div><?= e($cat ?: 'N/A') ?></div>
                <div class="label">Color</div><div><?= e(trim((string)($product['color'] ?? '')) ?: 'N/A') ?></div>
                <div class="label">Size</div><div><?= e(trim((string)($product['size'] ?? '')) ?: 'N/A') ?></div>
                <div class="label">Measurements</div>
                <div><?= e($dimensions ?: 'N/A') ?></div>
                <div class="label">Weight</div>
                <div><?= e($weight ?: 'N/A') ?></div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
