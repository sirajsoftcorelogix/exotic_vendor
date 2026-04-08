<?php
$labelProductId = (int)($products['id'] ?? 0);
if ($labelProductId <= 0) {
    return;
}
$jewelryLabelUrl = base_url('?page=products&action=jewelry_label&id=' . $labelProductId);
$mgStoreLabelUrl = base_url('?page=products&action=mg_store_label&id=' . $labelProductId);
$textileLabelUrl = base_url('?page=products&action=textile_label&id=' . $labelProductId);
?>
<!-- Full-width panel below product + measures (separate card, matches product label UI pattern) -->
<div class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm">
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div class="min-w-0 flex-1">
      <h3 class="font-semibold text-gray-800 text-base leading-tight">Product label</h3>
      <p class="text-xs text-gray-500 mt-1 leading-snug">Opens your browser’s print dialog — pick label size and copies.</p>
    </div>
    <button type="button" id="productLabelPrintOpenBtn"
      class="shrink-0 inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-[#d9822b] hover:bg-[#bf7326] text-white text-sm font-semibold shadow-sm transition-colors">
      <i class="fas fa-print" aria-hidden="true"></i>
      Print labels
    </button>
  </div>
</div>

<div id="productLabelPrintModal" class="hidden fixed inset-0 z-[2000] flex items-center justify-center p-4 bg-black/50" role="dialog" aria-modal="true" aria-labelledby="productLabelPrintModalTitle">
  <div class="bg-white rounded-xl shadow-xl max-w-md w-full overflow-hidden border border-gray-100">
    <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
      <h3 id="productLabelPrintModalTitle" class="font-semibold text-gray-900">Print product labels</h3>
      <button type="button" class="product-label-print-close text-gray-400 hover:text-gray-700 text-xl leading-none px-1" aria-label="Close">&times;</button>
    </div>
    <div class="px-5 py-4 space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1" for="productLabelSizeSelect">Label size</label>
        <select id="productLabelSizeSelect" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
          <option value="jewelry" selected>Jewelry — 100 × 12.9 mm</option>
          <option value="textile">Textile — 64 × 34 mm</option>
          <option value="mg_store">MG store (large) — 75 × 50 mm</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1" for="productLabelCopiesInput">Number of labels</label>
        <input type="number" id="productLabelCopiesInput" min="1" max="99" value="1"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
      </div>
      <p class="text-xs text-gray-500">Each copy prints on its own sheet. The print dialog opens without a new tab.</p>
    </div>
    <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 flex gap-2 justify-end">
      <button type="button" class="product-label-print-close px-4 py-2 text-sm rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100">Cancel</button>
      <button type="button" id="productLabelPrintSubmitBtn"
        class="px-4 py-2 text-sm rounded-lg bg-[#d9822b] hover:bg-[#bf7326] text-white font-medium">Print…</button>
    </div>
  </div>
</div>

<script>
(function () {
  var jewelryBaseUrl = <?php echo json_encode($jewelryLabelUrl, JSON_UNESCAPED_SLASHES); ?>;
  var mgStoreBaseUrl = <?php echo json_encode($mgStoreLabelUrl, JSON_UNESCAPED_SLASHES); ?>;
  var textileBaseUrl = <?php echo json_encode($textileLabelUrl, JSON_UNESCAPED_SLASHES); ?>;
  var modal = document.getElementById('productLabelPrintModal');
  var openBtn = document.getElementById('productLabelPrintOpenBtn');
  var sizeSelect = document.getElementById('productLabelSizeSelect');
  var copiesInput = document.getElementById('productLabelCopiesInput');
  var submitBtn = document.getElementById('productLabelPrintSubmitBtn');
  if (!modal || !openBtn) return;

  function openModal() {
    modal.classList.remove('hidden');
    try { copiesInput && copiesInput.focus(); } catch (e) {}
  }
  function closeModal() {
    modal.classList.add('hidden');
  }

  openBtn.addEventListener('click', openModal);
  modal.querySelectorAll('.product-label-print-close').forEach(function (el) {
    el.addEventListener('click', closeModal);
  });
  modal.addEventListener('click', function (e) {
    if (e.target === modal) closeModal();
  });

  function removePrintIframe(iframe) {
    try {
      if (iframe && iframe.parentNode) iframe.parentNode.removeChild(iframe);
    } catch (e) {}
  }

  function printLabelsInHiddenFrame(url, frameKind) {
    var prev = document.getElementById('product-label-print-iframe');
    if (prev) removePrintIframe(prev);

    var iframe = document.createElement('iframe');
    iframe.id = 'product-label-print-iframe';
    iframe.setAttribute('title', 'Label print');
    iframe.setAttribute('aria-hidden', 'true');
    var w = '120mm';
    var h = '30mm';
    if (frameKind === 'mg') {
      w = '100mm';
      h = '62mm';
    } else if (frameKind === 'textile') {
      w = '72mm';
      h = '40mm';
    }
    iframe.style.cssText = 'position:fixed;left:-9999px;top:0;width:' + w + ';height:' + h + ';border:0;margin:0;padding:0;opacity:0;pointer-events:none;z-index:-1;';

    iframe.addEventListener('load', function onLoad() {
      iframe.removeEventListener('load', onLoad);
      try {
        var cw = iframe.contentWindow;
        if (cw && cw.addEventListener) {
          cw.addEventListener('afterprint', function () {
            setTimeout(function () { removePrintIframe(iframe); }, 200);
          });
        }
      } catch (e) {}
      setTimeout(function () {
        if (document.getElementById('product-label-print-iframe') === iframe) {
          removePrintIframe(iframe);
        }
      }, 120000);
    });

    document.body.appendChild(iframe);
    iframe.src = url;
  }

  function currentBaseUrl() {
    if (!sizeSelect) return jewelryBaseUrl;
    if (sizeSelect.value === 'mg_store') return mgStoreBaseUrl;
    if (sizeSelect.value === 'textile') return textileBaseUrl;
    return jewelryBaseUrl;
  }

  function currentFrameKind() {
    if (!sizeSelect) return 'jewelry';
    if (sizeSelect.value === 'mg_store') return 'mg';
    if (sizeSelect.value === 'textile') return 'textile';
    return 'jewelry';
  }

  submitBtn && submitBtn.addEventListener('click', function () {
    var n = parseInt(copiesInput && copiesInput.value, 10);
    if (isNaN(n) || n < 1) n = 1;
    if (n > 99) n = 99;
    var baseUrl = currentBaseUrl();
    var sep = baseUrl.indexOf('?') >= 0 ? '&' : '?';
    var url = baseUrl + sep + 'copies=' + encodeURIComponent(String(n));
    /* Always iframe: avoids replacing the product page with the label URL (and keeps session on same document). */
    printLabelsInHiddenFrame(url, currentFrameKind());
    closeModal();
  });
})();
</script>
