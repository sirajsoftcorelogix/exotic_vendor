<?php
$jewelryLabelProductId = (int)($products['id'] ?? 0);
if ($jewelryLabelProductId <= 0) {
    return;
}
$jewelryLabelUrl = base_url('?page=products&action=jewelry_label&id=' . $jewelryLabelProductId);
?>
<div class="bg-white rounded-lg border border-amber-200 p-3 shadow-sm">
  <div class="flex flex-wrap items-center justify-between gap-2">
    <div>
      <h3 class="font-semibold text-gray-800 text-sm">Jewelry label</h3>
      <p class="text-xs text-gray-500">100 × 12.9 mm — QR (SKU), Color, Size, MRP &amp; SKU. Opens print dialog in a new tab.</p>
    </div>
    <a href="<?php echo htmlspecialchars($jewelryLabelUrl, ENT_QUOTES, 'UTF-8'); ?>"
      target="_blank" rel="noopener noreferrer"
      class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#d9822b] hover:bg-[#bf7326] text-white text-sm font-medium transition-colors">
      <i class="fas fa-print" aria-hidden="true"></i>
      Print jewelry label
    </a>
  </div>
</div>
