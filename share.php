<?php
require_once 'bootstrap/init/init.php';
require_once 'controllers/ProductsController.php';
$controller = new ProductsController();
$product = $controller->viewProduct(); dd($product);

if (!$product) {
    http_response_code(404);
    echo "Product not found.";
    exit;
}

// ---- BUILD SHARE DATA ----
$title = $product['title'] ?: ('Product ' . $product['sku']);
$sku = $product['sku'] ?? '';
$itemCode = $product['item_code'] ?? '';
$color = $product['color'] ?? '';
$size = $product['size'] ?? '';
$category = $product['category'] ?? '';

$descParts = [];
if ($sku) $descParts[] = "SKU: $sku";
if ($itemCode) $descParts[] = "Item: $itemCode";
if ($color) $descParts[] = "Color: $color";
if ($size) $descParts[] = "Size: $size";
if ($category) $descParts[] = "Category: $category";
$description = implode(" | ", $descParts);
if ($description === '') $description = 'Product details';

// Image URL: must be absolute and publicly accessible for WhatsApp preview
$imageRaw = $product['image'] ?? '';
$defaultImage = full_url('assets/img/default-product.png'); // create this file or change path

// If your DB stores full URL, use it. If it stores relative path, convert to absolute.
if ($imageRaw && preg_match('/^https?:\/\//i', $imageRaw)) {
    $ogImage = $imageRaw;
} elseif ($imageRaw) {
    // assume relative to project root
    $ogImage = full_url(ltrim($imageRaw, '/'));
} else {
    $ogImage = $defaultImage;
}

// Current page URL (absolute)
$ogUrl = full_url('share.php?id=' . $id);

// Optional: if you want canonical clean URL
$canonical = $ogUrl;

// ---- OUTPUT HTML ----
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title><?= e($title) ?></title>
  <meta name="description" content="<?= e($description) ?>">

  <!-- ✅ Open Graph (WhatsApp/Facebook/LinkedIn) -->
  <meta property="og:title" content="<?= e($title) ?>">
  <meta property="og:description" content="<?= e($description) ?>">
  <meta property="og:image" content="<?= e($ogImage) ?>">
  <meta property="og:url" content="<?= e($ogUrl) ?>">
  <meta property="og:type" content="product">

  <!-- ✅ Twitter (optional, helps some apps) -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($title) ?>">
  <meta name="twitter:description" content="<?= e($description) ?>">
  <meta name="twitter:image" content="<?= e($ogImage) ?>">

  <link rel="canonical" href="<?= e($canonical) ?>">

  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial; margin: 0; background: #f6f7f9; }
    .wrap { max-width: 920px; margin: 24px auto; padding: 16px; }
    .card { background: #fff; border: 1px solid #e6e8ee; border-radius: 14px; overflow: hidden; box-shadow: 0 10px 20px rgba(0,0,0,.05); display: grid; grid-template-columns: 280px 1fr; }
    .img { background: #fafafa; display: flex; align-items: center; justify-content: center; padding: 16px; }
    .img img { width: 100%; max-width: 260px; height: auto; object-fit: contain; border-radius: 12px; }
    .content { padding: 18px; }
    .title { font-size: 20px; font-weight: 700; margin: 0 0 6px; }
    .meta { color: #556; font-size: 13px; margin-bottom: 12px; }
    .kv { display: grid; grid-template-columns: 160px 1fr; gap: 8px 12px; font-size: 14px; }
    .kv div { padding: 8px 0; border-bottom: 1px dashed #e6e8ee; }
    .label { color: #667; }
    .btns { margin-top: 16px; display: flex; gap: 10px; flex-wrap: wrap; }
    .btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 14px; border-radius: 10px; border: 1px solid #d7dbe6; text-decoration: none; color: #111; background: #fff; font-size: 14px; }
    .btn.primary { background: #25D366; border-color: #25D366; color: #fff; }
    @media (max-width: 780px) { .card { grid-template-columns: 1fr; } }
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
          <div class="label">SKU</div><div><?= e($sku ?: 'N/A') ?></div>
          <div class="label">Item Code</div><div><?= e($itemCode ?: 'N/A') ?></div>
          <div class="label">Color</div><div><?= e($color ?: 'N/A') ?></div>
          <div class="label">Size</div><div><?= e($size ?: 'N/A') ?></div>
          <div class="label">Measurements</div>
          <div><?= e(trim(($product['prod_height'] ?? '') . ' x ' . ($product['prod_width'] ?? '') . ' x ' . ($product['prod_length'] ?? '')) ?: 'N/A') ?></div>
          <div class="label">Weight</div>
          <div><?= e(trim(($product['product_weight'] ?? '') . ' ' . ($product['product_weight_unit'] ?? '')) ?: 'N/A') ?></div>
        </div>

        <?php
          // WhatsApp share link from share page itself (optional)
          $waText = "Product: {$title}\n{$description}\n{$ogUrl}";
          $waHref = "https://wa.me/?text=" . urlencode($waText);
        ?>

        <div class="btns">
          <a class="btn primary" href="<?= e($waHref) ?>" target="_blank" rel="noopener">Share on WhatsApp</a>
          <a class="btn" href="<?= e($ogUrl) ?>" onclick="navigator.clipboard && navigator.clipboard.writeText(this.href); return false;">Copy Link</a>
        </div>

      </div>
    </div>
  </div>
</body>
</html>
