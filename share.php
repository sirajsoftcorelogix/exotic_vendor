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
 */
function full_url(string $path): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    return $scheme . '://' . $host . ($dir ? $dir . '/' : '/') . ltrim($path, '/');
}

$uri = $_SERVER['REQUEST_URI']; 
$afterShare = explode('share.php/', $uri)[1];
$value = explode('&', $afterShare)[0];

$product_id = base64_decode($value) ?: 0;
if ($product_id <= 0) {
    http_response_code(404);
    echo "Invalid product id.";
    exit;
}

// Fetch product
$sql = "
    SELECT
    vp.id AS product_id,
    vp.title,
    vp.item_code,
    vp.sku,
    vp.image,
    vp.`groupname` AS category,
    vp.color,
    vp.size,
    vp.prod_height,
    vp.prod_width,
    vp.prod_length,
    vp.product_weight,
    vp.product_weight_unit,
    pl.quantity
FROM vp_products AS vp
INNER JOIN purchase_list AS pl 
    ON vp.id = pl.product_id
WHERE vp.id = ?
LIMIT 1";

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

// -------- Build fields --------
$title = trim((string)($product['title'] ?? ''));
if ($title === '') {
    $title = 'Product ' . (string)($product['sku'] ?? $product_id);
}

$item  = trim((string)($product['item_code'] ?? ''));
$sku   = trim((string)($product['sku'] ?? ''));
$cat   = trim((string)($product['category'] ?? ''));
$color = trim((string)($product['color'] ?? ''));
$size  = trim((string)($product['size'] ?? ''));
$qty   = isset($product['quantity']) ? (int)$product['quantity'] : '0';

// Dimensions format EXACT like you asked: "HxWxL: 0 x 0 x 0"
$h = (string)($product['prod_height'] ?? '0');
$w = (string)($product['prod_width']  ?? '0');
$l = (string)($product['prod_length'] ?? '0');
$dimensions = trim(($h !== '' ? $h : '0') . " x " . ($w !== '' ? $w : '0') . " x " . ($l !== '' ? $l : '0'));

// Weight text
$weightVal = (string)($product['product_weight'] ?? '');
$weightUnit = trim((string)($product['product_weight_unit'] ?? ''));
$weight = '';
if ($weightVal !== '' && (float)$weightVal > 0) {
    $weight = rtrim(rtrim($weightVal, '0'), '.');
    $weight = $weight . ($weightUnit ? " $weightUnit" : '');
}

// OG image: absolute public URL required by WhatsApp
$imageRaw = trim((string)($product['image'] ?? ''));
$defaultImage = full_url('assets/img/default-product.png'); // ensure this exists

if ($imageRaw !== '' && preg_match('/^https?:\/\//i', $imageRaw)) {
    $ogImage = $imageRaw;
} elseif ($imageRaw !== '') {
    $ogImage = full_url(ltrim($imageRaw, '/'));
} else {
    $ogImage = $defaultImage;
}

// OG URL (absolute)
$ogUrl = full_url('share.php?id=' . $product_id . ($qty ? '&qty='.$qty : ''));

// Optional: canonical
$canonical = $ogUrl;

// Guess MIME for OG (helps some scrapers)
$ogImageType = 'image/jpeg';
$lower = strtolower(parse_url($ogImage, PHP_URL_PATH) ?? '');
if (function_exists('str_ends_with')) {
    if (str_ends_with($lower, '.png'))  $ogImageType = 'image/png';
    if (str_ends_with($lower, '.webp')) $ogImageType = 'image/webp';
} else {
    if (substr($lower, -4) === '.png')  $ogImageType = 'image/png';
    if (substr($lower, -5) === '.webp') $ogImageType = 'image/webp';
}

/**
 * ✅ WhatsApp preview (og:description)
 * Keep it short but include the fields you requested.
 */
$descLines = [
    //"Quantity to be Purchased: {$qty}",
    "SKU: " . ($sku !== '' ? $sku : 'N/A'),
    "Color: " . ($color !== '' ? $color : 'N/A'),
    "Size: " . ($size !== '' ? $size : 'N/A'),
    "Dimensions (HxWxL): " . ($dimensions !== '' ? $dimensions : '0 x 0 x 0'),
    "Weight: " . ($weight !== '' ? $weight : 'N/A'),
];
$description = implode(" | ", $descLines);

// Share text (WhatsApp)
//. "Quantity to be Purchased: {$qty}\n"
//. "SKU: " . ($sku ?: '') . "\n" 
$waText = $title . "\n"    
    . "Color: " . ($color ?: '') . "\n"
    . "Size: " . ($size ?: '') . "\n"
    . "Dimensions (HxWxL): " . ($dimensions ?: '0 x 0 x 0') . "\n"
    . "Weight: " . ($weight ?: '') . "\n"
    . "\n";

$waHref = "https://wa.me/?text=" . urlencode($waText);

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= e($title) ?></title>
    <!-- ✅ Open Graph for WhatsApp/Facebook -->
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta property="og:image:secure_url" content="<?= e($ogImage) ?>">
    <meta property="og:image:type" content="<?= e($ogImageType) ?>">
    <meta property="og:url" content="<?= e($ogUrl) ?>">
    <meta property="og:type" content="Product">
    <meta property="og:site_name" content="Exotic India Art">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <!-- ✅ Twitter (optional) -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="<?= e($ogImage) ?>">

    <link rel="canonical" href="<?= e($canonical) ?>">

    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 0; background: radial-gradient(1200px 600px at 20% 0%, #fff6ed, #f6f7f9 55%); }
        .wrap { max-width: 980px; margin: 26px auto; padding: 16px; }
        .shell { display: grid; gap: 14px; }
        .card {
            background: #fff;
            border: 1px solid #e6e8ee;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 14px 28px rgba(0,0,0,.08);
            display: grid;
            grid-template-columns: 360px 1fr;
        }
        .img {
            background: linear-gradient(180deg, #fff, #fafafa);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px;
            position: relative;
        }
        .img img {
            width: 100%;
            max-width: 320px;
            height: auto;
            object-fit: contain;
            border-radius: 14px;
            box-shadow: 0 12px 18px rgba(0,0,0,.10);
            transition: transform .2s ease;
        }
        .card:hover .img img { transform: scale(1.02); }

        .badge {
            position:absolute; top:14px; left:14px;
            font-size: 11px; font-weight: 700;
            color:#7c2d12;
            background:#fff7ed;
            border:1px solid #fed7aa;
            padding:6px 10px; border-radius:999px;
        }

        .content { padding: 18px 18px 20px; }
        .title { font-size: 20px; font-weight: 800; margin: 0 0 6px; color:#0f172a; letter-spacing: -0.2px;}
        .sub { color:#64748b; font-size: 13px; margin: 0 0 12px; line-height: 1.4;}
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }
        .tile {
            border:1px solid #eef2ff;
            background:#f8fafc;
            border-radius: 14px;
            padding: 10px 12px;
        }
        .k { font-size: 11px; color:#64748b; font-weight:700; text-transform: uppercase; letter-spacing:.04em;}
        .v { margin-top: 4px; font-size: 14px; font-weight: 700; color:#0f172a; }
        .v.muted { font-weight: 600; color:#334155; }

        .actions {
            display:flex; gap:10px; flex-wrap:wrap;
            margin-top: 14px;
        }
        .btn {
            display:inline-flex; align-items:center; justify-content:center;
            padding: 10px 14px;
            border-radius: 12px;
            border:1px solid #d7dbe6;
            text-decoration:none;
            color:#0f172a;
            background:#fff;
            font-size: 14px;
            font-weight: 700;
            cursor:pointer;
            transition: transform .08s ease, box-shadow .15s ease;
            gap:8px;
        }
        .btn:hover { box-shadow: 0 8px 14px rgba(0,0,0,.08); transform: translateY(-1px); }
        .btn.primary { background:#25D366; border-color:#25D366; color:#fff; }
        .btn.orange { background:#ea580c; border-color:#ea580c; color:#fff; }
        .btn.dark { background:#111827; border-color:#111827; color:#fff; }

        .hint { margin-top: 10px; font-size: 12px; color:#64748b; }

        @media (max-width: 840px) {
            .card { grid-template-columns: 1fr; }
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="shell">
        <div class="card">
            <div class="img">
                <img src="<?= e($ogImage) ?>">
            </div>

            <div class="content">
                

                <!-- ✅ Requested fields (UI) -->
                <div class="grid">
                    <div class="tile">
                        <div class="k">Quantity to be Purchased</div>
                        <div class="v"><?= (int)$qty ?></div>
                    </div>
                    <div class="tile">
                        <div class="k">SKU</div>
                        <div class="v"><?= e($sku ?: 'N/A') ?></div>
                    </div>

                    <div class="tile">
                        <div class="k">Color</div>
                        <div class="v muted"><?= e($color ?: 'N/A') ?></div>
                    </div>
                    <div class="tile">
                        <div class="k">Size</div>
                        <div class="v muted"><?= e($size ?: 'N/A') ?></div>
                    </div>

                    <div class="tile" style="grid-column: 1 / -1;">
                        <div class="k">Dimensions (HxWxL)</div>
                        <div class="v muted"><?= e($dimensions ?: '0 x 0 x 0') ?></div>
                    </div>

                    <div class="tile" style="grid-column: 1 / -1;">
                        <div class="k">Weight</div>
                        <div class="v muted"><?= e($weight ?: 'N/A') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
