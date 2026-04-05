/** Micro strip DOM — preset from textiles_config PRINT_JS_PRESET */
(function (g) {
    function row(font, text, padB) {
        var d = document.createElement('div');
        d.textContent = text;
        Object.assign(d.style, {
            flexShrink: '0', width: '100%', textAlign: 'center', font: font, paddingBottom: padB + 'px',
            position: 'relative', zIndex: '2', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap'
        });
        return d;
    }
    g.buildMicroLabelElement = function (p, data) {
        var ffs = p.fontFamily || 'Arial, Helvetica, sans-serif';
        var el = document.createElement('div');
        el.className = 'product-label-sheet';
        Object.assign(el.style, {
            boxSizing: 'border-box', width: p.cw + 'px', height: p.ch + 'px', background: '#fff',
            border: p.showBorders !== false ? (p.border || '1px solid #000') : 'none', color: '#000',
            fontFamily: ffs, display: 'flex', flexDirection: 'column',
            alignItems: 'stretch', padding: (p.pad != null ? p.pad : 12) + 'px', overflow: 'hidden'
        });
        var dt = (data.labelDate != null && String(data.labelDate).trim()) ? String(data.labelDate).trim() : '—';
        var code = String((data.sku != null ? data.sku : '').trim() || '');
        code = code ? '(' + code + ')' : '—';
        var locRaw = data.labelLocation != null ? String(data.labelLocation).trim() : '';
        var loc = locRaw ? '(' + locRaw + ')' : '—';
        var skuPx = p.skuFontPx != null ? p.skuFontPx : p.skuLocationFontPx;
        var skuFs = (skuPx != null) ? skuPx + 'px' : '14pt';
        var skuLh = p.skuLineHeight != null ? p.skuLineHeight : 1.62;
        var skuFont = '600 ' + skuFs + '/' + skuLh + ' ' + ffs;
        var skuToLoc = p.skuLocationGapPx != null ? p.skuLocationGapPx : 3;
        var locToBar = p.skuBarcodeGapPx != null ? p.skuBarcodeGapPx : 8;
        var barDate = p.barcodeDateGapPx != null ? p.barcodeDateGapPx : (p.barcodeLocationGapPx != null ? p.barcodeLocationGapPx : 5);

        el.appendChild(row(skuFont, code, skuToLoc));
        el.appendChild(row(skuFont, loc, locToBar));

        var mid = document.createElement('div');
        Object.assign(mid.style, {
            flex: '1 1 0', minHeight: '0', display: 'flex', flexDirection: 'column', alignItems: 'center',
            justifyContent: 'flex-end', width: '100%', position: 'relative', zIndex: '1'
        });
        var wrap = document.createElement('div');
        wrap.className = 'pl-barcode-wrap';
        Object.assign(wrap.style, { width: '100%', display: 'block', textAlign: 'center', lineHeight: '0' });
        var cv = document.createElement('canvas');
        cv.className = 'pl-barcode';
        Object.assign(cv.style, { display: 'inline-block', verticalAlign: 'top' });
        wrap.appendChild(cv);
        mid.appendChild(wrap);
        el.appendChild(mid);

        var bot = document.createElement('div');
        Object.assign(bot.style, {
            flexShrink: '0', width: '100%', display: 'flex', flexDirection: 'column',
            alignItems: 'center', paddingTop: barDate + 'px'
        });
        var dateEl = document.createElement('div');
        dateEl.textContent = dt;
        Object.assign(dateEl.style, {
            fontFamily: ffs, fontSize: p.dateSize || '9pt', fontWeight: '400', lineHeight: '1.25', color: '#333',
            textAlign: 'center', maxWidth: '100%'
        });
        bot.appendChild(dateEl);
        el.appendChild(bot);
        return el;
    };
})(window);
