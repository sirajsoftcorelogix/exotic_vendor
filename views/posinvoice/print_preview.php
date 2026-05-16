<?php
$h = static function ($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};
$invNo = trim((string)($invoice_number ?? ''));
$pageTitle = $invNo !== '' ? 'Tax Invoice ' . $invNo : 'Tax Invoice';
?>
<div class="no-print fixed inset-x-0 top-0 z-50 border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur">
  <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-3">
    <div>
      <div class="text-sm font-semibold text-slate-900"><?= $h($pageTitle) ?></div>
      <div class="text-xs text-slate-500">Use Print, then choose Save as PDF if needed.</div>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <button type="button" onclick="window.print()" class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">Print invoice</button>
      <?php if (!empty($invoice_pdf_url)): ?>
        <a href="<?= $h((string)$invoice_pdf_url) ?>" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Download PDF</a>
      <?php endif; ?>
      <button type="button" onclick="window.close()" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Close</button>
    </div>
  </div>
</div>

<div class="invoice-print-root mx-auto max-w-[210mm] px-4 pb-10 pt-20 print:max-w-none print:px-0 print:pt-0">
  <?= $invoice_html ?? '' ?>
</div>

<style>
  @page {
    size: A4 portrait;
    margin: 12mm 14mm;
  }
  @media print {
    .no-print {
      display: none !important;
    }
    body {
      margin: 0 !important;
      padding: 0 !important;
      background: #fff !important;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .invoice-print-root {
      padding: 0 !important;
      max-width: none !important;
    }
  }
</style>

<script>
  (function () {
    var params = new URLSearchParams(window.location.search);
    if (params.get("autoprint") === "1") {
      window.addEventListener("load", function () {
        window.setTimeout(function () { window.print(); }, 400);
      });
    }
  })();
</script>
