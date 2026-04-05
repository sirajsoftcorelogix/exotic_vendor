<?php

declare(strict_types=1);

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

/**
 * QR generation and jewelry label inner HTML (print CSS lives in views/products/jewelry_label_*.php).
 * Micro strip: helpers/label/textiles_config.php + views/products/partials/micro_label_build.js.
 */
final class LabelRenderer
{
    public static function qrDataUri(array $config, string $payload): string
    {
        $mult = isset($config['QR_DISPLAY_MULTIPLIER']) ? max(1.0, (float) $config['QR_DISPLAY_MULTIPLIER']) : 3.0;
        $bitmapSize = max(200, (int) round((float) $config['QR_SIZE'] * 4 * $mult));
        $bitmapSize = min($bitmapSize, 800);

        $qrCode = new QrCode(
            data: $payload,
            size: $bitmapSize,
            margin: 2
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return 'data:image/png;base64,' . base64_encode($result->getString());
    }

    /** QR square edge length on paper (mm); base from QR_SIZE (px@96dpi), × QR_DISPLAY_MULTIPLIER, capped to label height. */
    public static function qrDisplayMm(array $config): float
    {
        $baseMm = (float) $config['QR_SIZE'] * 25.4 / 96.0;
        $mult = isset($config['QR_DISPLAY_MULTIPLIER']) ? max(1.0, (float) $config['QR_DISPLAY_MULTIPLIER']) : 3.0;
        $scaled = $baseMm * $mult;
        $reserveTextMm = 0.9;
        $max = max(4.0, (float) $config['LABEL_HEIGHT_MM'] - $reserveTextMm);

        return round(min($scaled, $max), 3);
    }

    /** Horizontal padding on .label-inner L and R (mm); must match jewelry label view CSS. */
    public const INNER_PAD_LR_MM = 2.2;

    /**
     * Max width (mm) for the SKU column so QR + margins + minimum title strip always fit on the label.
     *
     * @param array<string, scalar> $config
     */
    public static function leftColumnMaxMm(array $config, float $qrMm, float $rightRowGapMm = 0.5): float
    {
        $w = (float) $config['LABEL_WIDTH_MM'];
        $innerMm = $w - 2 * self::INNER_PAD_LR_MM;
        $betweenColsMm = 1.0;
        $qrTrackMm = 10 + $qrMm + $rightRowGapMm + 10;
        $minTitleMm = 8.0;

        return max(18.0, $innerMm - $betweenColsMm - $qrTrackMm - $minTitleMm);
    }

    public static function formatMrp(float|int $amount): string
    {
        return number_format((float) $amount, 0, '.', ',');
    }

    /**
     * @param array<string, scalar> $config
     * @param array{item_code?:string,sku?:string,size:string,color:string,mrp:float|int,product_name?:string,title?:string,qr_payload?:string} $row
     */
    public static function renderLabelInnerHtml(array $config, array $row, string $qrDataUri): string
    {
        $codeLine = (string) ($row['item_code'] ?? $row['sku'] ?? '');
        $codeLine = htmlspecialchars($codeLine, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $size = htmlspecialchars((string) $row['size'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $color = htmlspecialchars((string) $row['color'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $mrp = htmlspecialchars(self::formatMrp($row['mrp']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $productNameRaw = (string) ($row['product_name'] ?? $row['title'] ?? '');
        $productNameRaw = str_replace("\xc2\xa0", ' ', $productNameRaw);
        $productName = htmlspecialchars($productNameRaw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeUri = htmlspecialchars($qrDataUri, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return <<<HTML
<div class="label-sheet">
  <div class="label-inner">
    <div class="label-left">
      <span class="sku">{$codeLine}</span>
      <span class="meta">Size: {$size}</span>
      <span class="meta">Color: {$color}</span>
    </div>
    <div class="label-right">
      <img class="qr" src="{$safeUri}" alt="" />
      <div class="label-right-text">
        <div class="mrp-line">
          <span class="mrp">MRP: ₹{$mrp}</span><span class="tax"> · Incl. of all taxes</span>
        </div>
        <div class="product-name">{$productName}</div>
      </div>
    </div>
  </div>
</div>
HTML;
    }
}
