<?php
/**
 * debug-share.php?id=7135
 * Shows what WhatsApp scraper sees
 */

require_once 'bootstrap/init/init.php';
require_once __DIR__ . '/settings/database/database.php';

$conn = Database::getConnection();
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    die("Please provide product ID: debug-share.php?id=7135");
}

// Fetch product
$sql = "SELECT id, title, item_code, sku, image FROM vp_products WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    die("Product not found");
}

// Build URLs
function full_url($path) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    return $scheme . '://' . $host . ($dir ? $dir . '/' : '/') . ltrim($path, '/');
}

$shareUrl = full_url('share.php?id=' . $product_id);

// Build image URL (same logic as share.php)
$imageRaw = trim($product['image'] ?? '');
if ($imageRaw && preg_match('/^https?:\/\//i', $imageRaw)) {
    $ogImage = str_replace('http://', 'https://', $imageRaw);
} elseif ($imageRaw) {
    $scheme = 'https';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    $ogImage = $scheme . '://' . $host . ($dir ? $dir . '/' : '/') . ltrim($imageRaw, '/');
} else {
    $ogImage = full_url('assets/img/default-product.png');
}

// Test image accessibility
$ch = curl_init($ogImage);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'facebookexternalhit/1.1');
$response = curl_exec($ch);
$imageHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$imageContentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

// Test share page accessibility
$ch = curl_init($shareUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'facebookexternalhit/1.1');
$pageResponse = curl_exec($ch);
$pageHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Extract OG tags from share page
preg_match_all('/<meta\s+property="og:([^"]+)"\s+content="([^"]+)"/i', $pageResponse, $ogTags);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>WhatsApp Share Debug - Product #<?= $product_id ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { background: linear-gradient(135deg, #25D366 0%, #128C7E 100%); color: white; padding: 30px; border-radius: 12px 12px 0 0; }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .content { background: white; padding: 30px; border-radius: 0 0 12px 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .section { margin: 30px 0; padding: 20px; border-radius: 8px; border: 2px solid #e5e7eb; }
        .section h2 { color: #111827; margin-bottom: 15px; font-size: 20px; display: flex; align-items: center; gap: 10px; }
        .status { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: 600; }
        .status.pass { background: #d1fae5; color: #065f46; }
        .status.fail { background: #fee2e2; color: #991b1b; }
        .status.warning { background: #fef3c7; color: #92400e; }
        .info-row { display: grid; grid-template-columns: 200px 1fr; gap: 15px; padding: 12px 0; border-bottom: 1px solid #f3f4f6; }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-weight: 600; color: #6b7280; }
        .info-value { color: #111827; word-break: break-all; font-family: 'Courier New', monospace; font-size: 13px; }
        .preview-card { border: 2px solid #e5e7eb; border-radius: 12px; overflow: hidden; margin-top: 20px; max-width: 500px; }
        .preview-card img { width: 100%; height: 300px; object-fit: cover; background: #f9fafb; }
        .preview-card-content { padding: 16px; background: #f9fafb; }
        .preview-card-title { font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 8px; }
        .preview-card-desc { font-size: 14px; color: #6b7280; }
        .preview-card-url { font-size: 12px; color: #9ca3af; margin-top: 8px; text-transform: uppercase; }
        .og-tags { background: #f9fafb; padding: 15px; border-radius: 6px; margin-top: 15px; }
        .og-tag { font-family: 'Courier New', monospace; font-size: 13px; padding: 6px 0; color: #374151; }
        .og-tag strong { color: #059669; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: #25D366; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; margin: 10px 10px 10px 0; transition: background 0.3s; }
        .btn:hover { background: #128C7E; }
        .btn.secondary { background: #4267B2; }
        .btn.secondary:hover { background: #365899; }
        pre { background: #1f2937; color: #f9fafb; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 12px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 WhatsApp Share Debug Tool</h1>
            <p>Product ID: <?= $product_id ?> | <?= htmlspecialchars($product['title']) ?></p>
        </div>
        
        <div class="content">
            <!-- Status Overview -->
            <div class="section" style="border-color: <?= ($pageHttpCode == 200 && $imageHttpCode == 200) ? '#10b981' : '#ef4444' ?>;">
                <h2>
                    <?= ($pageHttpCode == 200 && $imageHttpCode == 200) ? '✅' : '❌' ?>
                    Overall Status
                </h2>
                <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px;">
                    <span class="status <?= $pageHttpCode == 200 ? 'pass' : 'fail' ?>">
                        Share Page: <?= $pageHttpCode == 200 ? 'OK' : 'FAILED' ?>
                    </span>
                    <span class="status <?= $imageHttpCode == 200 ? 'pass' : 'fail' ?>">
                        Image: <?= $imageHttpCode == 200 ? 'OK' : 'FAILED' ?>
                    </span>
                    <span class="status <?= str_starts_with($shareUrl, 'https://') ? 'pass' : 'fail' ?>">
                        HTTPS: <?= str_starts_with($shareUrl, 'https://') ? 'YES' : 'NO' ?>
                    </span>
                </div>
            </div>
            
            <!-- URLs -->
            <div class="section">
                <h2>📋 URLs</h2>
                <div class="info-row">
                    <div class="info-label">Share URL:</div>
                    <div class="info-value"><?= htmlspecialchars($shareUrl) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Image URL:</div>
                    <div class="info-value"><?= htmlspecialchars($ogImage) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">WhatsApp Link:</div>
                    <div class="info-value">https://wa.me/?text=<?= urlencode($shareUrl) ?></div>
                </div>
            </div>
            
            <!-- Accessibility Tests -->
            <div class="section">
                <h2>🌐 Accessibility Tests</h2>
                <div class="info-row">
                    <div class="info-label">Share Page Status:</div>
                    <div class="info-value">
                        <span class="status <?= $pageHttpCode == 200 ? 'pass' : 'fail' ?>">
                            HTTP <?= $pageHttpCode ?>
                        </span>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Image Status:</div>
                    <div class="info-value">
                        <span class="status <?= $imageHttpCode == 200 ? 'pass' : 'fail' ?>">
                            HTTP <?= $imageHttpCode ?>
                        </span>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Image Content-Type:</div>
                    <div class="info-value"><?= htmlspecialchars($imageContentType) ?></div>
                </div>
            </div>
            
            <!-- Open Graph Tags Found -->
            <div class="section">
                <h2>🏷️ Open Graph Tags Found</h2>
                <?php if (!empty($ogTags[1])): ?>
                    <div class="og-tags">
                        <?php foreach ($ogTags[1] as $i => $property): ?>
                            <div class="og-tag">
                                <strong>og:<?= htmlspecialchars($property) ?></strong> = "<?= htmlspecialchars($ogTags[2][$i]) ?>"
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #dc2626; font-weight: 600;">❌ No Open Graph tags found! Check your share.php file.</p>
                <?php endif; ?>
            </div>
            
            <!-- Preview Card -->
            <div class="section">
                <h2>📱 WhatsApp Preview (How it will look)</h2>
                <div class="preview-card">
                    <img src="<?= htmlspecialchars($ogImage) ?>" 
                         alt="Preview" 
                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22300%22%3E%3Crect fill=%22%23ddd%22 width=%22400%22 height=%22300%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 fill=%22%23999%22%3EImage Failed to Load%3C/text%3E%3C/svg%3E';">
                    <div class="preview-card-content">
                        <div class="preview-card-url"><?= parse_url($shareUrl, PHP_URL_HOST) ?></div>
                        <div class="preview-card-title"><?= htmlspecialchars($product['title']) ?></div>
                        <div class="preview-card-desc">
                            SKU: <?= htmlspecialchars($product['sku']) ?> | Item: <?= htmlspecialchars($product['item_code']) ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="section">
                <h2>🔧 Test Actions</h2>
                <a href="<?= htmlspecialchars($shareUrl) ?>" target="_blank" class="btn">
                    📄 View Share Page
                </a>
                <a href="https://wa.me/?text=<?= urlencode($shareUrl) ?>" target="_blank" class="btn">
                    💬 Test WhatsApp Share
                </a>
                <a href="https://developers.facebook.com/tools/debug/?q=<?= urlencode($shareUrl) ?>" target="_blank" class="btn secondary">
                    🔍 Facebook Debugger
                </a>
            </div>
            
            <!-- Troubleshooting -->
            <?php if ($pageHttpCode != 200 || $imageHttpCode != 200 || empty($ogTags[1])): ?>
            <div class="section" style="background: #fef2f2; border-color: #ef4444;">
                <h2>❌ Issues Found</h2>
                <ul style="margin-left: 20px; line-height: 2;">
                    <?php if ($pageHttpCode != 200): ?>
                        <li><strong>Share page is not accessible (HTTP <?= $pageHttpCode ?>)</strong> - Check your share.php file exists and has no errors</li>
                    <?php endif; ?>
                    
                    <?php if ($imageHttpCode != 200): ?>
                        <li><strong>Image is not accessible (HTTP <?= $imageHttpCode ?>)</strong> - Check image path and file permissions</li>
                    <?php endif; ?>
                    
                    <?php if (empty($ogTags[1])): ?>
                        <li><strong>No Open Graph tags found</strong> - Check your share.php has proper &lt;meta property="og:*"&gt; tags</li>
                    <?php endif; ?>
                    
                    <?php if (!str_starts_with($shareUrl, 'https://')): ?>
                        <li><strong>Not using HTTPS</strong> - WhatsApp requires HTTPS for previews</li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php else: ?>
            <div class="section" style="background: #f0fdf4; border-color: #10b981;">
                <h2>✅ Everything Looks Good!</h2>
                <p style="line-height: 1.8; color: #065f46;">
                    Your share page and image are accessible. If WhatsApp still doesn't show the preview:
                </p>
                <ol style="margin: 15px 0 0 20px; line-height: 2; color: #065f46;">
                    <li>Use the Facebook Debugger above to clear WhatsApp's cache</li>
                    <li>Make sure you're sharing ONLY the URL (not text before it)</li>
                    <li>Wait 5-10 seconds after pasting URL in WhatsApp for preview to generate</li>
                    <li>Try on a different WhatsApp account/number to bypass cache</li>
                </ol>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
