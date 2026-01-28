<?php
/**
 * test-image-access.php
 * Test if images are accessible externally (like WhatsApp would access them)
 */

require_once 'bootstrap/init/init.php';
if (!function_exists('full_url')) {
    function full_url(string $path): string {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        return $scheme . '://' . $host . ($dir ? $dir . '/' : '/') . ltrim($path, '/');
    }
}
$testImageUrl = 'https://cdn.exoticindia.com/images/products/original/books-2019-012/mzy457.webp'; // Change to your actual image

// Test 1: Check if URL is HTTPS
$isHttps = str_starts_with($testImageUrl, 'https://');

// Test 2: Check HTTP headers
$ch = curl_init($testImageUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For testing only
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'WhatsApp/2.0'); // Simulate WhatsApp

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$error = curl_error($ch);
curl_close($ch);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Image Accessibility Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        .result { padding: 15px; margin: 10px 0; border-radius: 6px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        pre { background: #f9f9f9; padding: 15px; border-radius: 4px; overflow-x: auto; }
        img { max-width: 400px; margin-top: 20px; border: 2px solid #ddd; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🖼️ Image Accessibility Test</h1>
        
        <h3>Testing URL:</h3>
        <pre><?= htmlspecialchars($testImageUrl) ?></pre>
        
        <h3>Results:</h3>
        
        <div class="result <?= $isHttps ? 'success' : 'error' ?>">
            <strong>HTTPS:</strong> <?= $isHttps ? '✅ Yes' : '❌ No - REQUIRED for WhatsApp!' ?>
        </div>
        
        <div class="result <?= $httpCode == 200 ? 'success' : 'error' ?>">
            <strong>HTTP Status:</strong> <?= $httpCode ?> 
            <?= $httpCode == 200 ? '✅ OK' : '❌ Failed' ?>
        </div>
        
        <div class="result <?= strpos($contentType, 'image/') === 0 ? 'success' : 'error' ?>">
            <strong>Content Type:</strong> <?= htmlspecialchars($contentType) ?>
            <?= strpos($contentType, 'image/') === 0 ? '✅ Valid' : '❌ Invalid' ?>
        </div>
        
        <?php if ($error): ?>
        <div class="result error">
            <strong>cURL Error:</strong> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        
        <h3>Response Headers:</h3>
        <pre><?= htmlspecialchars($response) ?></pre>
        
        <h3>Visual Test:</h3>
        <img src="<?= htmlspecialchars($testImageUrl) ?>" alt="Test Image">
        
        <h3>What This Means:</h3>
        <ul>
            <li><strong>HTTP 200:</strong> Image is accessible ✅</li>
            <li><strong>HTTP 403:</strong> Permission denied - check file permissions ❌</li>
            <li><strong>HTTP 404:</strong> Image not found - check path ❌</li>
            <li><strong>HTTP 500:</strong> Server error - check error logs ❌</li>
        </ul>
    </div>
</body>
</html>