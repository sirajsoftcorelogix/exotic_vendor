/** MG store / large label DOM — preset from mg_store_label_config PRINT_JS_PRESET */
(function (g) {
    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }
    function textLine(px, weight, text) {
        var d = document.createElement('div');
        d.style.width = '100%';
        d.style.fontSize = px + 'px';
        d.style.fontWeight = weight;
        d.style.lineHeight = '1.22';
        d.style.textAlign = 'left';
        d.style.wordWrap = 'break-word';
        d.style.overflowWrap = 'break-word';
        d.innerHTML = text;
        return d;
    }
    g.buildMgStoreLabelElement = function (p, data) {
        var ffs = p.fontFamily || 'Arial, Helvetica, sans-serif';
        var el = document.createElement('div');
        el.className = 'product-label-sheet';
        var pad = p.pad != null ? p.pad : 28;
        var showBorder = p.showBorders !== false;
        Object.assign(el.style, {
            boxSizing: 'border-box',
            width: p.cw + 'px',
            height: p.ch + 'px',
            background: '#fff',
            border: showBorder ? (p.border || '1px solid #000') : 'none',
            color: '#000',
            fontFamily: ffs,
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'stretch',
            padding: pad + 'px',
            overflow: 'hidden'
        });

        var barOuter = document.createElement('div');
        Object.assign(barOuter.style, {
            flexShrink: '0',
            width: '100%',
            textAlign: 'center',
            lineHeight: '0'
        });
        var barWrap = document.createElement('div');
        barWrap.className = 'pl-barcode-wrap';
        Object.assign(barWrap.style, { display: 'inline-block', maxWidth: '100%' });
        var cv = document.createElement('canvas');
        cv.className = 'pl-barcode';
        Object.assign(cv.style, { display: 'block', verticalAlign: 'top', imageRendering: 'pixelated' });
        barWrap.appendChild(cv);
        barOuter.appendChild(barWrap);
        el.appendChild(barOuter);

        var textAfter = p.textAfterBarcodePx != null ? p.textAfterBarcodePx : 10;
        var skuPx = p.skuLinePx != null ? p.skuLinePx : 34;
        var titlePx = p.titleLinePx != null ? p.titleLinePx : 30;
        var bodyPx = p.bodyLinePx != null ? p.bodyLinePx : 26;
        var gapPx = Math.max(4, Math.round(bodyPx * 0.12));

        var block = document.createElement('div');
        Object.assign(block.style, {
            flex: '1 1 0',
            minHeight: '0',
            width: '100%',
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'flex-start',
            justifyContent: 'flex-start',
            paddingTop: textAfter + 'px',
            gap: gapPx + 'px'
        });

        var skuCode = String((data.sku != null ? data.sku : '').trim() || '—');
        block.appendChild(textLine(skuPx, '700', '<span style="font-weight:700">SKU:</span> ' + esc(skuCode)));

        var title = String(data.title || '').trim() || '—';
        block.appendChild(textLine(titlePx, '700', esc(title)));

        var mat = String(data.material || '').trim();
        block.appendChild(textLine(bodyPx, '400', 'Material: ' + esc(mat || '—')));

        var priceShow = data.mrpFormatted != null && data.mrpFormatted !== ''
            ? String(data.mrpFormatted)
            : (data.mrp != null && data.mrp !== '' ? String(data.mrp) : '—');
        var gst = String(data.priceTaxNote || data.taxNote || 'Incl. GST').trim();
        block.appendChild(textLine(bodyPx, '400', 'Price: ' + esc(priceShow) + ' (' + esc(gst) + ')'));

        var dimLine = String(data.mgDimsLine || '').trim();
        if (dimLine) {
            block.appendChild(textLine(bodyPx, '400', esc(dimLine)));
        }

        el.appendChild(block);
        return el;
    };
})(window);
