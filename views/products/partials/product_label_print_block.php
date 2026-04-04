<?php
/**
 * Product detail — label print modal (PDF, multiple sizes).
 * Expects $products from product_detail (getProduct).
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

$PRODUCT_LABEL_DATA = [
    'detailUrl' => $labelDetailUrl,
    'productId' => $pid,
    'imageUrl' => $labelImageUrl,
    'itemCode' => $products['item_code'] ?? '',
    'sku' => $products['sku'] ?? '',
    'title' => $products['title'] ?? '',
    'mrp' => $products['itemprice'] ?? $products['cost_price'] ?? '',
    'group' => $products['groupname'] ?? '',
    'dims' => $labelDims,
    'weight' => $wt,
    'color' => $products['color'] ?? '',
    'size' => $products['size'] ?? '',
];
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<!-- Trigger -->
<div class="bg-white rounded-lg border border-amber-200 p-3 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <h3 class="font-semibold text-gray-800 text-sm">Product label</h3>
            <p class="text-xs text-gray-500">PDF for thermal / laser printers — pick label size and copies.</p>
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
                    <option value="small">Small — 50 × 25 mm (2&quot; × 1&quot;)</option>
                    <option value="medium" selected>Medium — 76 × 51 mm (3&quot; × 2&quot;)</option>
                    <option value="large">Large — 100 × 150 mm (shelf)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Number of labels</label>
                <input type="number" id="productLabelQty" min="1" max="99" value="1"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
            </div>
            <p class="text-xs text-gray-500">Barcode encodes item code (or SKU). Use “actual size” when printing the PDF.</p>
        </div>
        <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 flex gap-2 justify-end">
            <button type="button" onclick="closeProductLabelModal()"
                class="px-4 py-2 text-sm rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100">Cancel</button>
            <button type="button" id="productLabelPdfBtn" onclick="generateProductLabelPdf()"
                class="px-4 py-2 text-sm rounded-lg bg-[#d9822b] hover:bg-[#bf7326] text-white font-medium">Download PDF</button>
        </div>
    </div>
</div>

<!-- Off-screen render queue -->
<div id="product-label-pdf-queue" class="fixed left-0 top-0 -z-10 pointer-events-none opacity-0 overflow-hidden"
    style="width:0;height:0;" aria-hidden="true"></div>

<script>
(function() {
    window.PRODUCT_LABEL_DATA = <?php echo json_encode($PRODUCT_LABEL_DATA, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

    window.LABEL_PRESETS = {
        small: {
            name: 'Small',
            wMm: 50,
            hMm: 25,
            orient: 'landscape',
            cw: 1000,
            ch: 500,
            barCol: 340,
            barUnit: 1,
            barHeight: 72,
            barFont: 11,
            pad: 16,
            title: 26,
            meta: 20,
            smallMeta: 17
        },
        medium: {
            name: 'Medium',
            wMm: 76.2,
            hMm: 50.8,
            orient: 'landscape',
            cw: 1200,
            ch: 800,
            barCol: 420,
            barUnit: 1.35,
            barHeight: 120,
            barFont: 16,
            pad: 24,
            title: 40,
            meta: 28,
            smallMeta: 22
        },
        large: {
            name: 'Large',
            wMm: 100,
            hMm: 150,
            orient: 'portrait',
            cw: 1000,
            ch: 1500,
            barCol: 380,
            barUnit: 1.6,
            barHeight: 160,
            barFont: 20,
            pad: 32,
            title: 48,
            meta: 32,
            smallMeta: 26
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

    function buildLabelElement(preset, data) {
        const el = document.createElement('div');
        el.className = 'product-label-sheet';
        el.style.boxSizing = 'border-box';
        el.style.width = preset.cw + 'px';
        el.style.height = preset.ch + 'px';
        el.style.background = '#fff';
        el.style.border = '3px solid #000';
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
        img.crossOrigin = 'anonymous';
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
        const raw = String(data.itemCode || data.sku || '').trim();
        if (raw.length) return raw;
        const pid = data.productId != null ? String(data.productId) : '';
        return pid.length ? ('ID' + pid) : '0';
    }

    function initBarcodeOnCanvas(canvas, value, preset) {
        const text = String(value).trim() || '0';
        try {
            JsBarcode(canvas, text, {
                format: 'CODE128',
                width: preset.barUnit,
                height: preset.barHeight,
                displayValue: true,
                fontOptions: 'bold',
                fontSize: preset.barFont,
                margin: 4,
                background: '#ffffff',
                lineColor: '#000000'
            });
        } catch (e) {
            try {
                JsBarcode(canvas, text.replace(/[^\x20-\x7E]/g, ''), {
                    format: 'CODE128',
                    width: preset.barUnit,
                    height: preset.barHeight,
                    displayValue: true,
                    fontSize: preset.barFont,
                    margin: 4,
                    background: '#ffffff',
                    lineColor: '#000000'
                });
            } catch (e2) {
                JsBarcode(canvas, 'INVALID', {
                    format: 'CODE128',
                    width: preset.barUnit,
                    height: Math.min(preset.barHeight, 40),
                    displayValue: true,
                    fontSize: preset.barFont,
                    margin: 2,
                    background: '#ffffff',
                    lineColor: '#000000'
                });
            }
        }
        const maxW = preset.barCol - preset.pad * 2 - 8;
        if (canvas.width > maxW && maxW > 40) {
            const nw = Math.floor(maxW);
            const nh = Math.floor(canvas.height * (maxW / canvas.width));
            const src = document.createElement('canvas');
            src.width = canvas.width;
            src.height = canvas.height;
            src.getContext('2d').drawImage(canvas, 0, 0);
            canvas.width = nw;
            canvas.height = nh;
            canvas.getContext('2d').drawImage(src, 0, 0, src.width, src.height, 0, 0, nw, nh);
        }
    }

    window.generateProductLabelPdf = async function() {
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

        await new Promise(function(r) { setTimeout(r, 150); });

        const btn = document.getElementById('productLabelPdfBtn');
        const prevHtml = btn.innerHTML;
        btn.disabled = true;
        btn.textContent = 'Building PDF…';

        try {
            const jsPDF = window.jspdf.jsPDF;
            const pdf = new jsPDF({
                orientation: preset.orient,
                unit: 'mm',
                format: [preset.wMm, preset.hMm]
            });

            for (let i = 0; i < sheets.length; i++) {
                if (i > 0) {
                    pdf.addPage([preset.wMm, preset.hMm], preset.orient);
                }
                const element = sheets[i];
                const canvas = await html2canvas(element, {
                    scale: 2,
                    useCORS: true,
                    backgroundColor: '#ffffff',
                    logging: false,
                    width: preset.cw,
                    height: preset.ch,
                    windowWidth: preset.cw,
                    windowHeight: preset.ch,
                    onclone: function(clonedDoc) {
                        const origCanvas = element.querySelector('canvas.pl-barcode');
                        const clonedSheet = clonedDoc.querySelector('.product-label-sheet');
                        const clonedCanvas = clonedSheet ? clonedSheet.querySelector('canvas.pl-barcode') : null;
                        if (origCanvas && clonedCanvas) {
                            const ctx = clonedCanvas.getContext('2d');
                            clonedCanvas.width = origCanvas.width;
                            clonedCanvas.height = origCanvas.height;
                            ctx.drawImage(origCanvas, 0, 0);
                        }
                    }
                });
                const imgData = canvas.toDataURL('image/jpeg', 0.95);
                pdf.addImage(imgData, 'JPEG', 0, 0, preset.wMm, preset.hMm);
            }

            const safeCode = (data.itemCode || 'product').replace(/[^\w\-]+/g, '_');
            pdf.save('product_label_' + safeCode + '_' + sizeKey + '.pdf');
        } catch (err) {
            console.error(err);
            alert('Could not create PDF. If images are on another domain, try hosting them on the same site or check the console.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = prevHtml;
            queue.innerHTML = '';
        }
    };

    document.getElementById('productLabelModal') && document.getElementById('productLabelModal').addEventListener('click', function(e) {
        if (e.target === this) closeProductLabelModal();
    });
})();
</script>
