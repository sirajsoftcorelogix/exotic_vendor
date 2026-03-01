<?php

/**
 * Shiprocket Shipping Labels PDF Merger
 * 
 * Requirements:
 *   composer require setasign/fpdf setasign/fpdi
 *   OR
 *   composer require tecnickcom/tcpdf
 * 
 * Install via: composer require setasign/fpdi setasign/fpdf
 */

require_once 'vendor/autoload.php';

use setasign\Fpdi\Fpdi;

// ============================================================
// CONFIGURATION
// ============================================================

// Option 1: Put all your PDF URLs in this array
$pdfUrls = [
    'https://kr-shipmultichannel-mum.s3.ap-south-1.amazonaws.com/298507/labels/ac5085f685cbdc5425e540db64062fd9.pdf',
    // Add more URLs here...
    // 'https://your-s3-url.com/label2.pdf',
    // 'https://your-s3-url.com/label3.pdf',
];

// Option 2: Load URLs from a text file (one URL per line) — recommended for 200+ URLs
// Comment out the $pdfUrls array above and uncomment below:
// $pdfUrls = file('urls.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// Output file name
$outputFile = 'merged_shipping_labels.pdf';

// Temp directory to store downloaded PDFs
$tempDir = sys_get_temp_dir() . '/pdf_merge_' . uniqid();

// ============================================================
// SETTINGS
// ============================================================

// Number of parallel download threads (via curl_multi)
$batchSize = 10;

// Retry failed downloads
$maxRetries = 3;

// Request timeout per PDF (seconds)
$timeout = 30;

// ============================================================
// MAIN SCRIPT
// ============================================================

echo "Starting PDF merge process...\n";
echo "Total labels to process: " . count($pdfUrls) . "\n\n";

// Create temp directory
if (!mkdir($tempDir, 0755, true)) {
    die("Failed to create temp directory: $tempDir\n");
}

// Register cleanup on exit
register_shutdown_function(function () use ($tempDir) {
    deleteDirectory($tempDir);
});

// Download all PDFs
$downloadedFiles = downloadPDFs($pdfUrls, $tempDir, $batchSize, $maxRetries, $timeout);

if (empty($downloadedFiles)) {
    die("No PDFs were downloaded successfully.\n");
}

echo "\nSuccessfully downloaded: " . count($downloadedFiles) . "/" . count($pdfUrls) . " files\n";
echo "Merging PDFs...\n";

// Merge all PDFs
mergePDFs($downloadedFiles, $outputFile);

echo "\n✅ Done! Merged PDF saved as: $outputFile\n";
echo "Total pages: " . countPages($outputFile) . "\n";


// ============================================================
// FUNCTIONS
// ============================================================

/**
 * Download PDFs in batches using curl_multi for speed
 */
function downloadPDFs(array $urls, string $tempDir, int $batchSize, int $maxRetries, int $timeout): array
{
    $downloadedFiles = [];
    $batches = array_chunk($urls, $batchSize, true);
    $total = count($urls);
    $completed = 0;
    $failed = [];

    foreach ($batches as $batch) {
        $mh = curl_multi_init();
        $handles = [];
        $fileHandles = [];

        foreach ($batch as $index => $url) {
            $filename = $tempDir . '/' . sprintf('%05d', $index) . '_' . md5($url) . '.pdf';
            $fh = fopen($filename, 'wb');
            if (!$fh) {
                echo "⚠️  Could not open file for writing: $filename\n";
                continue;
            }

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_FILE           => $fh,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (PDF Merger Bot)',
                CURLOPT_FAILONERROR    => true,
            ]);

            curl_multi_add_handle($mh, $ch);
            $handles[(int)$ch] = ['url' => $url, 'file' => $filename, 'index' => $index];
            $fileHandles[(int)$ch] = $fh;
        }

        // Execute all handles
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);

        // Process results
        foreach ($handles as $chId => $info) {
            // Find the handle — rebuild from our stored info
        }

        // Better approach: process via curl_multi_info_read
        while ($done = curl_multi_info_read($mh)) {
            $ch = $done['handle'];
            $chId = (int)$ch;
            $info = $handles[$chId];

            fclose($fileHandles[$chId]);

            if ($done['result'] === CURLE_OK && filesize($info['file']) > 100) {
                $downloadedFiles[$info['index']] = $info['file'];
                $completed++;
                echo "✓ [{$completed}/{$total}] Downloaded: " . basename($info['url']) . "\n";
            } else {
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                echo "✗ Failed (HTTP {$httpCode}): {$info['url']}\n";
                $failed[] = $info;
                @unlink($info['file']);
            }

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        curl_multi_close($mh);
    }

    // Retry failed downloads
    if (!empty($failed) && $maxRetries > 0) {
        echo "\nRetrying " . count($failed) . " failed downloads...\n";
        foreach ($failed as $info) {
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                echo "  Retry {$attempt}/{$maxRetries}: {$info['url']}\n";
                $content = @file_get_contents($info['url']);
                if ($content && strlen($content) > 100) {
                    file_put_contents($info['file'], $content);
                    $downloadedFiles[$info['index']] = $info['file'];
                    $completed++;
                    echo "  ✓ Succeeded on retry {$attempt}\n";
                    break;
                }
                sleep(2);
            }
        }
    }

    // Sort by original index to maintain order
    ksort($downloadedFiles);

    return array_values($downloadedFiles);
}

/**
 * Merge all downloaded PDFs into one using FPDI
 */
function mergePDFs(array $files, string $outputFile): void
{
    $pdf = new Fpdi();
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    $fileCount = count($files);
    $current = 0;

    foreach ($files as $file) {
        $current++;

        try {
            $pageCount = $pdf->setSourceFile($file);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);

                // Add page with same orientation and size as source
                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height']);
            }

            echo "  Merged [{$current}/{$fileCount}]: " . basename($file) . " ({$pageCount} page(s))\n";

        } catch (Exception $e) {
            echo "  ⚠️  Skipping corrupted PDF: " . basename($file) . " — " . $e->getMessage() . "\n";
        }
    }

    $pdf->Output($outputFile, 'F');
}

/**
 * Count pages in final merged PDF
 */
function countPages(string $file): int
{
    try {
        $reader = new Fpdi();
        return $reader->setSourceFile($file);
    } catch (Exception $e) {
        return -1;
    }
}

/**
 * Recursively delete a directory
 */
function deleteDirectory(string $dir): void
{
    if (!is_dir($dir)) return;
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = "$dir/$file";
        is_dir($path) ? deleteDirectory($path) : unlink($path);
    }
    rmdir($dir);
}