<?php
/**
 * Product detail — label print modal (PDF, multiple sizes).
 * Expects $products from product_detail (getProduct).
 *
 * Jewelry (small): 100 × 12.9 mm — SKU / Size / Color | barcode (SKU) | MRP + tax / product title.
 * Micro (textiles): 25 × 15 mm — SKU / location+date in equal top+bottom flex bands; CODE128 vertically centred on the label.
 */
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
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

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
            <p class="text-xs text-gray-500">Barcode encodes <strong>SKU</strong> (falls back to item code if empty). Choose <strong>Micro</strong> for the 25×15 mm strip; your last choice is remembered for this browser. Use “Actual size” / 100% if needed.</p>
        </div>
        <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 flex gap-2 justify-end">
            <button type="button" onclick="closeProductLabelModal()"
                class="px-4 py-2 text-sm rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100">Cancel</button>
            <button type="button" id="productLabelPrintBtn" onclick="generateProductLabelPrint()"
                class="px-4 py-2 text-sm rounded-lg bg-[#d9822b] hover:bg-[#bf7326] text-white font-medium">Print…</button>
        </div>
    </div>
</div>

<!-- Off-screen render queue -->
<div id="product-label-pdf-queue" class="fixed left-0 top-0 -z-10 pointer-events-none opacity-0 overflow-hidden"
    style="width:0;height:0;" aria-hidden="true"></div>

<script>
(function() {
    window.PRODUCT_LABEL_DATA = <?php echo json_encode($PRODUCT_LABEL_DATA, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

    /**
     * Printable size for each preset = wMm × hMm (openPrintWindowWithLabelImages: @page + img in mm).
     * Keep cw / wMm === ch / hMm so the PNG aspect matches paper and nothing is stretched (here: 20 px/mm).
     * micro: equal flex top/bottom bands; barcode row centred vertically on the label; SKU | CODE128 | location | date.
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
        /** 25×15 mm — Seznik jewellery thermal (Amazon B0FK2X6VYF). helpers/label/textiles_config.php */
        micro: {
            name: 'Micro (textiles)',
            layout: 'micro',
            wMm: 25,
            hMm: 15,
            offsetXMm: 0,
            offsetYMm: 0,
            orient: 'landscape',
            cw: 500,
            ch: 300,
            pad: 20,
            border: '1px solid #000000',
            fontFamily: 'Arial, Helvetica, sans-serif',
            dateSize: '8pt',
            /** Same px for SKU + location so capture/print match exactly (~14pt @ 96dpi). */
            skuLocationFontPx: 19,
            showBorders: true,
            barUnit: 1,
            barHeight: 34,
            barDisplayValue: false,
            barFont: 8,
            barHorizontalMarginPx: 6,
            /** SKU→barcode gap = max(half SKU line, skuBarcodeGapPx ?? half line). microTopBandFlex / microBotBandFlex: SKU band vs bottom band. */
            microTopBandFlex: 1.12,
            microBotBandFlex: 0.88
        }
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

    function buildMicroLabelElement(preset, data) {
        const el = document.createElement('div');
        el.className = 'product-label-sheet';
        el.style.boxSizing = 'border-box';
        el.style.width = preset.cw + 'px';
        el.style.height = preset.ch + 'px';
        el.style.background = '#ffffff';
        el.style.border = preset.showBorders !== false ? (preset.border || '1px solid #000000') : 'none';
        el.style.color = '#000000';
        el.style.fontFamily = preset.fontFamily || 'Arial, Helvetica, sans-serif';
        el.style.display = 'flex';
        el.style.flexDirection = 'column';
        el.style.alignItems = 'stretch';
        el.style.justifyContent = 'flex-start';
        const padPx = preset.pad != null ? preset.pad : 40;
        el.style.padding = padPx + 'px';
        el.style.overflow = 'hidden';

        const locRaw = (data.labelLocation != null && String(data.labelLocation).trim() !== '')
            ? String(data.labelLocation).trim()
            : '';
        const locDisplay = locRaw !== '' ? '(' + locRaw + ')' : '—';

        const dateStr = (data.labelDate != null && String(data.labelDate).trim() !== '')
            ? String(data.labelDate).trim()
            : '—';

        const codeRaw = String((data.sku || data.itemCode || '').trim() || '');
        const codeDisplay = codeRaw !== '' ? '(' + codeRaw + ')' : '—';

        const skuLocFs = preset.skuLocationFontPx != null
            ? (preset.skuLocationFontPx + 'px')
            : (preset.codeSize || '14pt');
        const microSkuLineHeight = 1.4;
        var skuFontPxForGap;
        if (preset.skuLocationFontPx != null) {
            skuFontPxForGap = preset.skuLocationFontPx;
        } else {
            var cs = String(preset.codeSize || '14pt');
            var ptMatch = cs.match(/^([\d.]+)\s*pt$/i);
            var pxMatch = cs.match(/^([\d.]+)\s*px$/i);
            if (ptMatch) skuFontPxForGap = parseFloat(ptMatch[1]) * (96 / 72);
            else if (pxMatch) skuFontPxForGap = parseFloat(pxMatch[1]);
            else skuFontPxForGap = 14 * (96 / 72);
        }
        const halfLineGapPx = Math.max(1, Math.round(skuFontPxForGap * microSkuLineHeight / 2));
        const skuBarGap = Math.max(
            halfLineGapPx,
            preset.skuBarcodeGapPx != null ? preset.skuBarcodeGapPx : halfLineGapPx
        );
        const skuTop = document.createElement('div');
        skuTop.style.flexShrink = '0';
        skuTop.style.width = '100%';
        skuTop.style.textAlign = 'center';
        skuTop.style.fontSize = skuLocFs;
        skuTop.style.fontWeight = '600';
        skuTop.style.lineHeight = String(microSkuLineHeight);
        skuTop.style.paddingBottom = skuBarGap + 'px';
        skuTop.style.marginBottom = '0';
        skuTop.style.position = 'relative';
        skuTop.style.zIndex = '2';
        skuTop.style.overflow = 'hidden';
        skuTop.style.textOverflow = 'ellipsis';
        skuTop.style.whiteSpace = 'nowrap';
        skuTop.textContent = codeDisplay;

        const mid = document.createElement('div');
        mid.style.flex = '0 0 auto';
        mid.style.minHeight = '0';
        mid.style.display = 'block';
        mid.style.width = '100%';
        mid.style.marginBottom = '8px';
        mid.style.position = 'relative';
        mid.style.zIndex = '1';

        const barWrap = document.createElement('div');
        barWrap.className = 'pl-barcode-wrap';
        barWrap.style.width = '100%';
        barWrap.style.maxWidth = '100%';
        barWrap.style.display = 'block';
        barWrap.style.textAlign = 'center';
        barWrap.style.lineHeight = '0';
        const barCanvas = document.createElement('canvas');
        barCanvas.className = 'pl-barcode';
        barCanvas.style.display = 'inline-block';
        barCanvas.style.verticalAlign = 'top';
        barWrap.appendChild(barCanvas);
        mid.appendChild(barWrap);

        const bottomStack = document.createElement('div');
        bottomStack.style.flexShrink = '0';
        bottomStack.style.width = '100%';
        bottomStack.style.display = 'flex';
        bottomStack.style.flexDirection = 'column';
        bottomStack.style.alignItems = 'center';
        bottomStack.style.justifyContent = 'flex-start';
        bottomStack.style.gap = '0';
        bottomStack.style.paddingTop = '0';

        const locEl = document.createElement('div');
        locEl.style.fontSize = skuLocFs;
        locEl.style.fontWeight = '600';
        locEl.style.lineHeight = '1.4';
        locEl.style.textAlign = 'center';
        locEl.style.maxWidth = '100%';
        locEl.style.overflow = 'hidden';
        locEl.style.textOverflow = 'ellipsis';
        locEl.style.whiteSpace = 'nowrap';
        locEl.textContent = locDisplay;

        const dateEl = document.createElement('div');
        dateEl.style.fontSize = preset.dateSize || '8pt';
        dateEl.style.fontWeight = '400';
        dateEl.style.lineHeight = '1.25';
        dateEl.style.marginTop = '0';
        dateEl.style.color = '#333333';
        dateEl.style.textAlign = 'center';
        dateEl.style.maxWidth = '100%';
        dateEl.textContent = dateStr;

        bottomStack.appendChild(locEl);
        bottomStack.appendChild(dateEl);

        const topFlex = preset.microTopBandFlex != null ? preset.microTopBandFlex : 1.12;
        const botFlex = preset.microBotBandFlex != null ? preset.microBotBandFlex : 1.12;
        const topRegion = document.createElement('div');
        topRegion.style.flex = topFlex + ' 1 0';
        topRegion.style.minHeight = '0';
        topRegion.style.display = 'flex';
        topRegion.style.flexDirection = 'column';
        topRegion.style.justifyContent = 'flex-end';
        topRegion.style.alignItems = 'stretch';
        topRegion.appendChild(skuTop);

        const botRegion = document.createElement('div');
        botRegion.style.flex = botFlex + ' 1 0';
        botRegion.style.minHeight = '0';
        botRegion.style.display = 'flex';
        botRegion.style.flexDirection = 'column';
        botRegion.style.justifyContent = 'flex-start';
        botRegion.style.alignItems = 'stretch';
        botRegion.appendChild(bottomStack);

        el.appendChild(topRegion);
        el.appendChild(mid);
        el.appendChild(botRegion);
        return el;
    }

    function buildLabelElement(preset, data) {
        if (preset.layout === 'jewelry') {
            return buildJewelryLabelElement(preset, data);
        }
        if (preset.layout === 'micro') {
            return buildMicroLabelElement(preset, data);
        }
        const el = document.createElement('div');
        el.className = 'product-label-sheet';
        el.style.boxSizing = 'border-box';
        el.style.width = preset.cw + 'px';
        el.style.height = preset.ch + 'px';
        el.style.background = '#fff';
        el.style.border = preset.border || '3px solid #000';
        el.style.color = '#000';
        el.style.fontFamily = 'system-ui, Segoe UI, Roboto, sans-serif';
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
        try {
            JsBarcode(canvas, text, {
                format: 'CODE128',
                width: preset.barUnit != null ? preset.barUnit : 2,
                height: preset.barHeight,
                displayValue: showBarcodeText,
                fontOptions: bcFontOpt,
                fontSize: preset.barFont != null ? preset.barFont : 14,
                margin: bcMargin,
                background: '#ffffff',
                lineColor: '#000000'
            });
        } catch (e) {
            try {
                JsBarcode(canvas, text.replace(/[^\x20-\x7E]/g, ''), {
                    format: 'CODE128',
                    width: preset.barUnit != null ? preset.barUnit : 2,
                    height: preset.barHeight,
                    displayValue: showBarcodeText,
                    fontOptions: bcFontOpt,
                    fontSize: preset.barFont != null ? preset.barFont : 14,
                    margin: bcMargin,
                    background: '#ffffff',
                    lineColor: '#000000'
                });
            } catch (e2) {
                JsBarcode(canvas, 'INVALID', {
                    format: 'CODE128',
                    width: preset.barUnit != null ? preset.barUnit : 2,
                    height: Math.min(preset.barHeight, 40),
                    displayValue: showBarcodeText,
                    fontSize: preset.barFont != null ? preset.barFont : 14,
                    margin: bcMargin,
                    background: '#ffffff',
                    lineColor: '#000000'
                });
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

        var pxPerMmW = preset.cw / preset.wMm;
        var pxPerMmH = preset.ch / preset.hMm;
        if (Math.abs(pxPerMmW - pxPerMmH) > 0.02) {
            console.warn('Label preset: cw/wMm and ch/hMm should match (avoid distorted print).', {
                key: sizeKey,
                wMm: preset.wMm,
                hMm: preset.hMm,
                cw: preset.cw,
                ch: preset.ch,
                pxPerMmW: pxPerMmW,
                pxPerMmH: pxPerMmH
            });
        }

        const queue = document.getElementById('product-label-pdf-queue');
        queue.innerHTML = '';
        queue.style.width = preset.cw + 'px';
        queue.style.height = 'auto';
        queue.style.opacity = '0';
        queue.style.position = 'fixed';
        queue.style.left = '-9999px';
        queue.style.top = '0';

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
                var clonedSheet = clonedDoc.querySelector('.product-label-sheet[data-pl-capture="1"]');
                if (!clonedSheet) clonedSheet = clonedDoc.querySelector('.product-label-sheet');
                var origCanvas = sourceEl.querySelector('canvas.pl-barcode');
                var clonedCanvas = clonedSheet ? clonedSheet.querySelector('canvas.pl-barcode') : null;
                if (origCanvas && clonedCanvas && origCanvas.width > 0 && origCanvas.height > 0) {
                    clonedCanvas.width = origCanvas.width;
                    clonedCanvas.height = origCanvas.height;
                    clonedCanvas.getContext('2d').drawImage(origCanvas, 0, 0);
                }
            };
        }

        async function captureSheetAsPng(element) {
            var baseOpts = {
                useCORS: true,
                allowTaint: false,
                backgroundColor: '#ffffff',
                logging: false,
                width: preset.cw,
                height: preset.ch,
                windowWidth: preset.cw,
                windowHeight: preset.ch,
                foreignObjectRendering: false
            };
            element.setAttribute('data-pl-capture', '1');
            try {
                var canvas = await html2canvas(element, Object.assign({}, baseOpts, {
                    scale: 2,
                    onclone: makeOncloneForSheet(element)
                }));
                return canvas.toDataURL('image/png');
            } catch (firstErr) {
                console.warn('Label capture retry (no product photo / lower scale):', firstErr);
                var imgs = element.querySelectorAll('img');
                var imgBackup = [];
                for (var j = 0; j < imgs.length; j++) {
                    var im = imgs[j];
                    imgBackup.push({
                        src: im.getAttribute('src'),
                        crossorigin: im.getAttribute('crossorigin')
                    });
                    im.removeAttribute('crossorigin');
                    im.setAttribute('src', TRANSPARENT_GIF);
                }
                try {
                    var canvas2 = await html2canvas(element, Object.assign({}, baseOpts, {
                        scale: 1,
                        useCORS: false,
                        onclone: makeOncloneForSheet(element)
                    }));
                    return canvas2.toDataURL('image/png');
                } finally {
                    for (var k = 0; k < imgs.length; k++) {
                        if (imgBackup[k].src != null) imgs[k].setAttribute('src', imgBackup[k].src);
                        else imgs[k].removeAttribute('src');
                        if (imgBackup[k].crossorigin) imgs[k].setAttribute('crossorigin', imgBackup[k].crossorigin);
                        else imgs[k].removeAttribute('crossorigin');
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
