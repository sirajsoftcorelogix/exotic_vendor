<?php

declare(strict_types=1);

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

/**
 * QR generation and label inner markup (styles live in label.php / batch_example.php).
 */
final class LabelRenderer
{
    public static function qrDataUri(array $config, string $payload): string
    {
        $bitmapSize = max(120, (int) round((float) $config['QR_SIZE'] * 4));

        $qrCode = new QrCode(
            data: $payload,
            size: $bitmapSize,
            margin: 2
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return 'data:image/png;base64,' . base64_encode($result->getString());
    }

    /** QR square edge length on paper (mm); QR_SIZE = reference px at 96dpi. */
    public static function qrDisplayMm(array $config): float
    {
        $mm = (float) $config['QR_SIZE'] * 25.4 / 96.0;
        $max = (float) $config['LABEL_HEIGHT_MM'] - 2.0;

        return round(min($mm, max(4.0, $max)), 3);
    }

    public static function formatMrp(float|int $amount): string
    {
        return number_format((float) $amount, 0, '.', ',');
    }

    /**
     * @param array<string, scalar> $config
     * @param array{sku:string,size:string,color:string,mrp:float|int,qr_payload?:string} $row
     */
    public static function renderLabelInnerHtml(array $config, array $row, string $qrDataUri): string
    {
        $sku = htmlspecialchars((string) $row['sku'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $size = htmlspecialchars((string) $row['size'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $color = htmlspecialchars((string) $row['color'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $mrp = htmlspecialchars(self::formatMrp($row['mrp']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeUri = htmlspecialchars($qrDataUri, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return <<<HTML
<div class="label-sheet">
  <div class="label-inner">
    <div class="label-left">
      <span class="sku">{$sku}</span>
      <span class="meta">Size: {$size}</span>
      <span class="meta">Color: {$color}</span>
    </div>
    <div class="label-right">
      <img class="qr" src="{$safeUri}" alt="" />
      <div class="label-right-text">
        <span class="mrp">MRP: ₹{$mrp}</span>
        <span class="tax">Incl. of all taxes</span>
      </div>
    </div>
  </div>
</div>
HTML;
    }
}
