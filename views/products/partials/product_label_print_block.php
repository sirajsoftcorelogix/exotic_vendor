<?php
/**
 * Product detail — label print modal (PDF, multiple sizes).
 * Expects $products from product_detail (getProduct).
 *
 * Jewelry (small): 100 × 12.9 mm — SKU / Size / Color | barcode (SKU) | MRP + tax / product title.
 * Micro (textiles): SKU, location (same type as SKU), barcode, date — textiles_config (`PRINT_JS_PRESET`) + micro_label_build.js.
 */
$_tx = require dirname(__DIR__, 3) . '/helpers/label/textiles_config.php';
$microClientPreset = $_tx['PRINT_JS_PRESET'];

$pid = (int)($products['id'] ?? 0);
$labelDetailUrl = base_url('?page=products&action=detail&id=' . $pid);
$rawImg = trim((string)($products['image'] ?? ''));
if ($rawImg === '') {
    $labelImageUrl = 'https://placehold.co/400x400/png?text=No+image';
} elseif (preg_match('#^https?://#i', $rawImg)) {
    $labelImageUrl = $rawImg;
} else {
    $labelImageUrl = base_url($rawImg);
}
$dimParts = [];
if (!empty($products['prod_length'])) {
    $dimParts[] = 'L: ' . $products['prod_length'] . ' ' . ($products['length_unit'] ?? '');
}
if (!empty($products['prod_width'])) {
    $dimParts[] = 'W: ' . $products['prod_width'] . ' ' . ($products['length_unit'] ?? '');
}
if (!empty($products['prod_height'])) {
    $dimParts[] = 'H: ' . $products['prod_height'] . ' ' . ($products['length_unit'] ?? '');
}
$labelDims = $dimParts ? implode(' × ', $dimParts) : '';
$wt = '';
if (!empty($products['product_weight']) && (float)$products['product_weight'] > 0) {
    $wt = $products['product_weight'] . ' ' . ($products['product_weight_unit'] ?? '');
}

$mrpRaw = $products['itemprice'] ?? $products['cost_price'] ?? '';
$mrpFormatted = $mrpRaw;
if ($mrpRaw !== '' && $mrpRaw !== null && is_numeric($mrpRaw)) {
    $mrpFormatted = number_format((float)$mrpRaw, 0, '.', ',');
}

$PRODUCT_LABEL_DATA = [
    'detailUrl' => $labelDetailUrl,
    'productId' => $pid,
    'imageUrl' => $labelImageUrl,
    'itemCode' => $products['item_code'] ?? '',
    'sku' => $products['sku'] ?? '',
    'title' => $products['title'] ?? '',
    'mrp' => $mrpRaw,
    'mrpFormatted' => $mrpFormatted,
    'group' => $products['groupname'] ?? '',
    'dims' => $labelDims,
    'weight' => $wt,
    'color' => $products['color'] ?? '',
    'size' => $products['size'] ?? '',
    'taxNote' => 'Incl. of all taxes',
    'labelLocation' => trim((string)($products['location'] ?? '')),
    'labelDate' => date('d-m-Y'),
];
/** Bust browser/CDN cache for barcode + capture libs whenever this partial changes. */
$productLabelPrintAssetVer = (string) (int) @filemtime(__FILE__);
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js?v=<?php echo htmlspecialchars($productLabelPrintAssetVer, ENT_QUOTES, 'UTF-8'); ?>"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js?v=<?php echo htmlspecialchars($productLabelPrintAssetVer, ENT_QUOTES, 'UTF-8'); ?>"></script>
<script><?php readfile(__DIR__ . '/micro_label_build.js'); ?></script>

<!-- Trigger -->
<div class="bg-white rounded-lg border border-amber-200 p-3 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <h3 class="font-semibold text-gray-800 text-sm">Product label</h3>
            <p class="text-xs text-gray-500">Opens your browser’s print dialog — pick label size and copies.</p>
        </div>
        <button type="button" onclick="openProductLabelModal()"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#d9822b] hover:bg-[#bf7326] text-white text-sm font-medium transition-colors">
            <i class="fas fa-print"></i>
            Print labels
        </button>
    </div>
</div>

<!-- Modal -->
<div id="productLabelModal" class="hidden fixed inset-0 z-[2000] flex items-center justify-center p-4 bg-black/50">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-semibold text-gray-900">Print product labels</h3>
            <button type="button" onclick="closeProductLabelModal()" class="text-gray-400 hover:text-gray-700 text-xl leading-none">&times;</button>
        </div>
        <div class="px-5 py-4 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Label size</label>
                <select id="productLabelSize"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    <option value="small" selected>Jewelry — 100 × 12.9 mm</option>
                    <option value="micro">Micro (textiles) — 25 × 15 mm</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Number of labels</label>
                <input type="number" id="productLabelQty" min="1" max="99" value="1"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
            </div>
            <p class="text-xs text-gray-500">Barcode encodes <strong>SKU</strong> (falls back to item code if empty). <strong>Micro:</strong> SKU → location → barcode → date. Use “Actual size” / 100% if needed.</p>
        </div>
        <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 flex gap-2 justify-end">
            <button type="button" onclick="closeProductLabelModal()"
                class="px-4 py-2 text-sm rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100">Cancel</button>
            <button type="button" id="productLabelPrintBtn" onclick="generateProductLabelPrint()"
                class="px-4 py-2 text-sm rounded-lg bg-[#d9822b] hover:bg-[#bf7326] text-white font-medium">Print…</button>
        </div>
    </div>
</div>

<!-- Capture host: avoid width:0/overflow:hidden so html2canvas can clone reliably (see generateProductLabelPrint). -->
<div id="product-label-pdf-queue" class="fixed left-0 top-0 pointer-events-none opacity-0"
    style="z-index:-1;width:1px;height:1px;overflow:visible;clip-path:inset(100%);" aria-hidden="true"></div>

<script>
(function() {
    window.PRODUCT_LABEL_DATA = <?php echo json_encode($PRODUCT_LABEL_DATA, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    /** Unix mtime of this PHP file — if it doesn’t change after you save, the server is serving a stale copy. */
    window.PRODUCT_LABEL_PRINT_BLOCK_MT = <?php echo (int)@filemtime(__FILE__); ?>;

    /**
     * Printable size for each preset = wMm × hMm (openPrintWindowWithLabelImages: @page + img in mm).
     * Keep cw / wMm === ch / hMm so the PNG aspect matches paper and nothing is stretched (here: 20 px/mm).
     * micro: SKU, location (same font as SKU), flex mid (barcode), date.
     */
    window.LABEL_PRESETS = {
        small: {
            name: 'Jewelry Size',
            layout: 'jewelry',
            wMm: 100,
            hMm: 12.9,
            barcodeOffsetMm: 10,
            offsetXMm: 0,
            offsetYMm: 0,
            orient: 'landscape',
            cw: 2000,
            ch: 258,
            border: '1px solid #000000',
            borderRadius: '36px',
            fontFamily: 'Arial, Helvetica, sans-serif',
            leftSkuPx: 32,
            leftMetaPx: 27,
            rightMrpPx: 36,
            rightSmallPx: 21,
            barCol: 1050,
            barUnit: 2.025,
            barHeight: 180,
            barFont: 20,
            pad: 44
        },
        micro: <?php echo json_encode($microClientPreset, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
    };

    window.openProductLabelModal = function() {
        document.getElementById('productLabelModal').classList.remove('hidden');
    };
    window.closeProductLabelModal = function() {
        document.getElementById('productLabelModal').classList.add('hidden');
    };

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function buildJewelryLabelElement(preset, data) {
        const el = document.createElement('div');
        el.className = 'product-label-sheet';
        el.style.boxSizing = 'border-box';
        el.style.width = preset.cw + 'px';
        el.style.height = preset.ch + 'px';
        el.style.background = '#ffffff';
        el.style.border = preset.border || '1px solid #000000';
        if (preset.borderRadius) {
            el.style.borderRadius = preset.borderRadius;
        }
        el.style.color = '#000000';
        el.style.fontFamily = preset.fontFamily || 'Arial, Helvetica, sans-serif';
        el.style.display = 'flex';
        el.style.flexDirection = 'row';
        el.style.alignItems = 'stretch';
        el.style.justifyContent = 'space-between';
        el.style.overflow = 'visible';

        const sidePad = preset.pad != null ? preset.pad : 16;
        const innerW = preset.cw - 2 * sidePad;
        const colW = Math.floor(innerW / 3);

        el.style.paddingLeft = sidePad + 'px';
        el.style.paddingRight = sidePad + 'px';

        const skuVal = esc((data.sku || data.itemCode || '').trim() || '—');
        const sizeVal = esc((data.size || '').trim() || '—');
        const colorVal = esc((data.color || '').trim() || '—');
        const mrpShow = data.mrpFormatted != null && data.mrpFormatted !== ''
            ? String(data.mrpFormatted)
            : (data.mrp != null && data.mrp !== '' ? String(data.mrp) : '—');
        const taxNote = esc((data.taxNote || 'Incl. of all taxes').trim());
        const productTitle = esc((data.title || '').trim() || '—');

        const leftSkuPx = preset.leftSkuPx || 34;
        const leftMetaPx = preset.leftMetaPx || 28;
        const rightMrpPx = preset.rightMrpPx || 38;
        const rightSmallPx = preset.rightSmallPx || 22;

        const leftCol = document.createElement('div');
        leftCol.style.flex = '0 0 ' + colW + 'px';
        leftCol.style.width = colW + 'px';
        leftCol.style.display = 'flex';
        leftCol.style.flexDirection = 'column';
        leftCol.style.alignItems = 'flex-start';
        leftCol.style.justifyContent = 'center';
        leftCol.style.boxSizing = 'border-box';
        leftCol.style.paddingRight = '8px';
        leftCol.style.overflow = 'visible';
        leftCol.innerHTML =
            '<div style="font-size:' + leftSkuPx + 'px;font-weight:800;line-height:1.3;white-space:nowrap;text-overflow:ellipsis;max-width:100%;">' + skuVal + '</div>' +
            '<div style="font-size:' + leftMetaPx + 'px;font-weight:400;line-height:1.28;margin-top:1px;white-space:nowrap;text-overflow:ellipsis;max-width:100%;">Size: ' + sizeVal + '</div>' +
            '<div style="font-size:' + leftMetaPx + 'px;font-weight:400;line-height:1.28;margin-top:1px;white-space:nowrap;text-overflow:ellipsis;max-width:100%;">Color: ' + colorVal + '</div>';

        const centerCol = document.createElement('div');
        centerCol.style.flex = '0 0 ' + colW + 'px';
        centerCol.style.width = colW + 'px';
        centerCol.style.display = 'flex';
        centerCol.style.alignItems = 'center';
        centerCol.style.justifyContent = 'center';
        centerCol.style.boxSizing = 'border-box';
        centerCol.style.paddingLeft = '6px';
        centerCol.style.paddingRight = '1px';
        var offMm = preset.barcodeOffsetMm != null ? Number(preset.barcodeOffsetMm) : 0;
        if (offMm > 0 && preset.wMm) {
            centerCol.style.marginLeft = Math.round((offMm / preset.wMm) * preset.cw) + 'px';
        }
        const barWrap = document.createElement('div');
        barWrap.className = 'pl-barcode-wrap';
        barWrap.style.maxWidth = '100%';
        barWrap.style.overflow = 'visible';
        barWrap.style.display = 'flex';
        barWrap.style.alignItems = 'center';
        barWrap.style.justifyContent = 'center';
        const barCanvas = document.createElement('canvas');
        barCanvas.className = 'pl-barcode';
        barWrap.appendChild(barCanvas);
        centerCol.appendChild(barWrap);

        const rightCol = document.createElement('div');
        rightCol.style.flex = '0 0 ' + colW + 'px';
        rightCol.style.width = colW + 'px';
        rightCol.style.display = 'flex';
        rightCol.style.flexDirection = 'column';
        rightCol.style.alignItems = 'flex-start';
        rightCol.style.justifyContent = 'center';
        rightCol.style.boxSizing = 'border-box';
        rightCol.style.paddingLeft = '0';
        rightCol.style.gap = '0';
        rightCol.style.overflow = 'visible';
        var rhsShiftPx = preset.wMm ? Math.round((10 / preset.wMm) * preset.cw) : 200;
        rightCol.innerHTML =
            '<div style="margin-left:-' + rhsShiftPx + 'px;display:flex;flex-direction:column;align-items:flex-start;justify-content:center;gap:0">' +
            '<div style="font-size:' + rightMrpPx + 'px;line-height:1.12;white-space:nowrap;text-overflow:ellipsis;max-width:100%;"><span style="font-weight:800">MRP: ₹' + mrpShow + '</span> <span style="font-weight:400;opacity:0.92;font-size:' + rightSmallPx + 'px">' + taxNote + '</span></div>' +
            '<div style="font-size:' + leftMetaPx + 'px;font-weight:400;line-height:1.2;margin-top:0;max-width:100%;white-space:normal;word-wrap:break-word;overflow-wrap:break-word;">' + productTitle + '</div>' +
            '</div>';

        el.appendChild(leftCol);
        el.appendChild(centerCol);
        el.appendChild(rightCol);
        return el;
    }

    function buildLabelElement(preset, data) {
        if (preset.layout === 'jewelry') {
            return buildJewelryLabelElement(preset, data);
        }
        if (preset.layout === 'micro') {
            return window.buildMicroLabelElement(preset, data);
        }
        const el = document.createElement('div');
        el.className = 'product-label-sheet';
        el.style.boxSizing = 'border-box';
        el.style.width = preset.cw + 'px';
        el.style.height = preset.ch + 'px';
        el.style.background = '#fff';
        el.style.border = preset.border || '3px solid #000';
        el.style.color = '#000';
        el.style.fontFamily = preset.fontFamily || 'Arial, Helvetica, sans-serif';
        el.style.display = 'flex';
        el.style.flexDirection = 'row';
        el.style.alignItems = 'stretch';
        el.style.overflow = 'hidden';

        const barCol = document.createElement('div');
        barCol.style.flex = '0 0 auto';
        barCol.style.width = preset.barCol + 'px';
        barCol.style.display = 'flex';
        barCol.style.alignItems = 'center';
        barCol.style.justifyContent = 'center';
        barCol.style.padding = preset.pad + 'px';
        barCol.style.borderRight = '3px solid #000';
        barCol.style.boxSizing = 'border-box';
        const barWrap = document.createElement('div');
        barWrap.className = 'pl-barcode-wrap';
        barWrap.style.maxWidth = '100%';
        barWrap.style.overflow = 'hidden';
        barWrap.style.display = 'flex';
        barWrap.style.alignItems = 'center';
        barWrap.style.justifyContent = 'center';
        const barCanvas = document.createElement('canvas');
        barCanvas.className = 'pl-barcode';
        barWrap.appendChild(barCanvas);
        barCol.appendChild(barWrap);

        const imgCol = document.createElement('div');
        imgCol.style.flex = '0 0 ' + Math.round(preset.cw * 0.28) + 'px';
        imgCol.style.borderRight = '3px solid #000';
        imgCol.style.display = 'flex';
        imgCol.style.alignItems = 'center';
        imgCol.style.justifyContent = 'center';
        imgCol.style.padding = preset.pad + 'px';
        const img = document.createElement('img');
        img.src = data.imageUrl;
        try {
            const abs = new URL(data.imageUrl, window.location.href);
            if ((abs.protocol === 'http:' || abs.protocol === 'https:') && abs.origin !== window.location.origin) {
                img.crossOrigin = 'anonymous';
            }
        } catch (e) { /* keep default — same-page relative URLs stay non-CORS for capture */ }
        img.alt = '';
        img.style.maxWidth = '100%';
        img.style.maxHeight = '95%';
        img.style.objectFit = 'contain';
        imgCol.appendChild(img);

        const textCol = document.createElement('div');
        textCol.style.flex = '1';
        textCol.style.padding = preset.pad + 'px';
        textCol.style.display = 'flex';
        textCol.style.flexDirection = 'column';
        textCol.style.justifyContent = 'center';
        textCol.style.gap = (preset.smallMeta * 0.35) + 'px';
        textCol.style.overflow = 'hidden';

        const titleEl = document.createElement('div');
        titleEl.style.fontSize = preset.title + 'px';
        titleEl.style.fontWeight = '800';
        titleEl.style.lineHeight = '1.15';
        titleEl.style.maxHeight = (preset.title * 3.5) + 'px';
        titleEl.style.overflow = 'hidden';
        titleEl.textContent = data.title || '';

        const itemEl = document.createElement('div');
        itemEl.style.fontSize = preset.meta + 'px';
        itemEl.style.fontWeight = '700';
        itemEl.innerHTML = '<span style="font-weight:600">Item:</span> ' + esc(data.itemCode);

        const skuEl = document.createElement('div');
        skuEl.style.fontSize = preset.smallMeta + 'px';
        skuEl.style.fontWeight = '600';
        skuEl.innerHTML = '<span style="opacity:.85">SKU:</span> ' + esc(data.sku);

        const mrpEl = document.createElement('div');
        mrpEl.style.fontSize = preset.meta + 'px';
        mrpEl.style.fontWeight = '800';
        mrpEl.textContent = 'MRP: ₹' + (data.mrp != null && data.mrp !== '' ? data.mrp : '—');

        textCol.appendChild(titleEl);
        textCol.appendChild(itemEl);
        textCol.appendChild(skuEl);
        textCol.appendChild(mrpEl);

        if (data.group) {
            const g = document.createElement('div');
            g.style.fontSize = preset.smallMeta + 'px';
            g.textContent = data.group;
            textCol.appendChild(g);
        }
        const extra = [];
        if (data.size) extra.push('Size: ' + data.size);
        if (data.color) extra.push('Color: ' + data.color);
        if (data.dims) extra.push(data.dims);
        if (data.weight) extra.push('Wt: ' + data.weight);
        if (extra.length) {
            const ex = document.createElement('div');
            ex.style.fontSize = preset.smallMeta + 'px';
            ex.style.lineHeight = '1.25';
            ex.style.opacity = '0.95';
            ex.textContent = extra.join(' · ');
            textCol.appendChild(ex);
        }

        el.appendChild(barCol);
        el.appendChild(imgCol);
        el.appendChild(textCol);
        return el;
    }

    function barcodeTextFromData(data) {
        const raw = String(data.sku || data.itemCode || '').trim();
        if (raw.length) return raw;
        const pid = data.productId != null ? String(data.productId) : '';
        return pid.length ? ('ID' + pid) : '0';
    }

    function initBarcodeOnCanvas(canvas, value, preset) {
        const text = String(value).trim() || '0';
        const isJewelry = preset.layout === 'jewelry';
        const isMicro = preset.layout === 'micro';
        const bcMargin = isJewelry ? 2 : (isMicro ? 0 : 4);
        const bcFontOpt = (isJewelry || isMicro) ? '' : 'bold';
        const showBarcodeText = isMicro && preset.barDisplayValue === false ? false : true;
        const bcFont = preset.fontFamily || 'Arial, Helvetica, sans-serif';
        const bcOpts = {
            format: 'CODE128',
            width: preset.barUnit != null ? preset.barUnit : 2,
            height: preset.barHeight,
            displayValue: showBarcodeText,
            font: bcFont,
            fontOptions: bcFontOpt,
            fontSize: preset.barFont != null ? preset.barFont : 14,
            margin: bcMargin,
            background: '#ffffff',
            lineColor: '#000000'
        };
        try {
            JsBarcode(canvas, text, bcOpts);
        } catch (e) {
            try {
                JsBarcode(canvas, text.replace(/[^\x20-\x7E]/g, ''), bcOpts);
            } catch (e2) {
                JsBarcode(canvas, 'INVALID', Object.assign({}, bcOpts, {
                    height: Math.min(preset.barHeight, 40)
                }));
            }
        }
        const pad = preset.pad != null ? preset.pad : 0;
        var maxW;
        if (preset.barMaxWidth != null && preset.barMaxWidth > 0) {
            maxW = preset.barMaxWidth;
        } else if (preset.layout === 'jewelry') {
            const sidePad = preset.pad != null ? preset.pad : 0;
            const innerW = preset.cw - 2 * sidePad;
            const colW = Math.floor(innerW / 3);
            const inset = preset.barWidthInsetPx != null ? preset.barWidthInsetPx : 10;
            maxW = Math.max(80, colW - inset);
        } else if (preset.barCol != null) {
            maxW = preset.barCol - pad * 2 - 8;
        } else {
            const sideEach = preset.barHorizontalMarginPx != null ? preset.barHorizontalMarginPx : 8;
            maxW = Math.max(40, preset.cw - pad * 2 - 2 * sideEach);
        }
        if (maxW > 40 && canvas.width > 0) {
            const nw = Math.floor(maxW);
            if (Math.abs(canvas.width - nw) > 1) {
                const nh = Math.max(1, Math.floor(canvas.height * (nw / canvas.width)));
                const src = document.createElement('canvas');
                src.width = canvas.width;
                src.height = canvas.height;
                src.getContext('2d').drawImage(canvas, 0, 0);
                canvas.width = nw;
                canvas.height = nh;
                canvas.getContext('2d').drawImage(src, 0, 0, src.width, src.height, 0, 0, nw, nh);
            }
        }
    }

    /** Opens print document: one page per image, physical size = preset.wMm × preset.hMm (CSS mm). */
    function openPrintWindowWithLabelImages(imageDataUrls, preset) {
        const wMm = preset.wMm;
        const hMm = preset.hMm;
        const ox = preset.offsetXMm != null ? preset.offsetXMm : 0;
        const oy = preset.offsetYMm != null ? preset.offsetYMm : 0;
        const pagesHtml = imageDataUrls.map(function(src, idx) {
            const last = idx === imageDataUrls.length - 1;
            return '<div class="label-page' + (last ? ' label-page--last' : '') + '"><img src="' + src + '" alt="" /></div>';
        }).join('');
        const style =
            '@page { size: ' + wMm + 'mm ' + hMm + 'mm; margin: 0; }' +
            '* { box-sizing: border-box; }' +
            'html, body { margin: 0; padding: 0; }' +
            'body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }' +
            '.label-page { width: ' + wMm + 'mm; height: ' + hMm + 'mm; page-break-after: always; page-break-inside: avoid; overflow: hidden; padding: ' + oy + 'mm 0 0 ' + ox + 'mm; }' +
            '.label-page--last { page-break-after: auto; }' +
            '.label-page img { display: block; width: calc(' + wMm + 'mm - ' + ox + 'mm); height: calc(' + hMm + 'mm - ' + oy + 'mm); object-fit: fill; }';
        const html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Print labels</title><style>' + style + '</style></head><body>' + pagesHtml + '</body></html>';

        const pw = window.open('', '_blank');
        if (!pw) {
            alert('Allow pop-ups for this site to print labels.');
            return;
        }
        pw.document.open();
        pw.document.write(html);
        pw.document.close();

        let printFired = false;
        function doPrint() {
            if (printFired) return;
            printFired = true;
            try {
                pw.focus();
                pw.print();
            } catch (e) { /* ignore */ }
        }
        setTimeout(doPrint, 300);

        pw.addEventListener('afterprint', function() {
            try { pw.close(); } catch (e) { /* ignore */ }
        });
    }

    window.generateProductLabelPrint = async function() {
        const sizeKey = document.getElementById('productLabelSize').value;
        let qty = parseInt(document.getElementById('productLabelQty').value, 10);
        if (isNaN(qty) || qty < 1) qty = 1;
        if (qty > 99) qty = 99;

        const preset = window.LABEL_PRESETS[sizeKey];
        const data = window.PRODUCT_LABEL_DATA;
        if (!preset || !data) {
            alert('Label data missing.');
            return;
        }

        const queue = document.getElementById('product-label-pdf-queue');
        const queueParent = queue.parentNode;
        const queueNext = queue.nextSibling;
        queue.innerHTML = '';
        try {
            if (queueParent && document.body !== queueParent) {
                document.body.appendChild(queue);
            }
        } catch (eMoveQ) { /* keep in place */ }
        queue.style.cssText = 'position:fixed!important;left:0!important;top:0!important;width:auto!important;height:auto!important;min-width:' + preset.cw + 'px!important;overflow:visible!important;opacity:0!important;pointer-events:none!important;z-index:2147483646!important;clip:unset!important;clip-path:none!important;';

        const sheets = [];
        for (let i = 0; i < qty; i++) {
            const sheet = buildLabelElement(preset, data);
            queue.appendChild(sheet);
            sheets.push(sheet);
        }

        const bcVal = barcodeTextFromData(data);
        sheets.forEach(function(sheet) {
            const canvas = sheet.querySelector('canvas.pl-barcode');
            if (canvas) initBarcodeOnCanvas(canvas, bcVal, preset);
        });

        await new Promise(function(r) { setTimeout(r, 280); });

        const btn = document.getElementById('productLabelPrintBtn');
        const prevHtml = btn.innerHTML;
        btn.disabled = true;
        btn.textContent = 'Preparing…';

        var TRANSPARENT_GIF = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

        function makeOncloneForSheet(sourceEl) {
            return function(clonedDoc) {
                try {
                    var clonedSheet = clonedDoc.querySelector('.product-label-sheet[data-pl-capture="1"]')
                        || clonedDoc.querySelector('.product-label-sheet');
                    var origCanvas = sourceEl.querySelector('canvas.pl-barcode');
                    var clonedCanvas = clonedSheet ? clonedSheet.querySelector('canvas.pl-barcode') : null;
                    if (origCanvas && clonedCanvas && origCanvas.width > 0 && origCanvas.height > 0) {
                        clonedCanvas.width = origCanvas.width;
                        clonedCanvas.height = origCanvas.height;
                        clonedCanvas.getContext('2d').drawImage(origCanvas, 0, 0);
                    }
                } catch (eClone) {
                    console.warn('Label onclone canvas sync:', eClone);
                }
            };
        }

        function captureOpts(el, scale, useCors) {
            return {
                useCORS: useCors,
                allowTaint: false,
                backgroundColor: '#ffffff',
                logging: false,
                foreignObjectRendering: false,
                scale: scale,
                width: Math.max(1, Math.ceil(el.offsetWidth || preset.cw)),
                height: Math.max(1, Math.ceil(el.offsetHeight || preset.ch)),
                scrollX: 0,
                scrollY: 0,
                onclone: makeOncloneForSheet(el)
            };
        }

        async function captureSheetAsPng(element) {
            element.setAttribute('data-pl-capture', '1');
            try {
                try {
                    return (await html2canvas(element, captureOpts(element, 2, true))).toDataURL('image/png');
                } catch (e1) {
                    console.warn('Label capture retry:', e1);
                    var imgs = element.querySelectorAll('img');
                    var bak = [];
                    for (var j = 0; j < imgs.length; j++) {
                        bak.push({ src: imgs[j].getAttribute('src'), x: imgs[j].getAttribute('crossorigin') });
                        imgs[j].removeAttribute('crossorigin');
                        imgs[j].setAttribute('src', TRANSPARENT_GIF);
                    }
                    try {
                        return (await html2canvas(element, captureOpts(element, 1, false))).toDataURL('image/png');
                    } finally {
                        for (var k = 0; k < imgs.length; k++) {
                            if (bak[k].src != null) imgs[k].setAttribute('src', bak[k].src);
                            else imgs[k].removeAttribute('src');
                            if (bak[k].x) imgs[k].setAttribute('crossorigin', bak[k].x);
                            else imgs[k].removeAttribute('crossorigin');
                        }
                    }
                }
            } finally {
                element.removeAttribute('data-pl-capture');
            }
        }

        try {
            const imageDataUrls = [];
            for (let i = 0; i < sheets.length; i++) {
                imageDataUrls.push(await captureSheetAsPng(sheets[i]));
            }

            openPrintWindowWithLabelImages(imageDataUrls, preset);
            closeProductLabelModal();
        } catch (err) {
            console.error(err);
            var detail = err && err.message ? err.message : String(err);
            alert('Could not prepare labels for printing.\n\n' + detail + '\n\nIf a product photo is on another domain, labels were retried without the photo. Check the browser console for more detail.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = prevHtml;
            queue.innerHTML = '';
            queue.className = 'fixed left-0 top-0 pointer-events-none opacity-0';
            queue.style.cssText = 'z-index:-1;width:1px;height:1px;overflow:visible;clip-path:inset(100%);';
            if (queueParent) {
                try {
                    if (queueNext && queueNext.parentNode === queueParent) {
                        queueParent.insertBefore(queue, queueNext);
                    } else {
                        queueParent.appendChild(queue);
                    }
                } catch (eRestore) { /* ignore */ }
            }
        }
    };

    document.getElementById('productLabelModal') && document.getElementById('productLabelModal').addEventListener('click', function(e) {
        if (e.target === this) closeProductLabelModal();
    });

    (function initProductLabelSizeSelect() {
        var sel = document.getElementById('productLabelSize');
        if (!sel || !window.LABEL_PRESETS) return;
        try {
            var saved = sessionStorage.getItem('productLabelLastSize');
            if (saved === 'small' || saved === 'micro') sel.value = saved;
        } catch (e) { /* private mode / blocked */ }
        sel.addEventListener('change', function() {
            try {
                sessionStorage.setItem('productLabelLastSize', this.value);
            } catch (e2) { /* ignore */ }
        });
    })();
})();
</script>
