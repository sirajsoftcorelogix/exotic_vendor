<?php
// 1. PHP Logic & Login Check
is_login();
require_once 'settings/database/database.php';
$conn = Database::getConnection();
require_once 'models/user/user.php';
$usersModel = new User($conn);
$userDetails = $usersModel->getUserById($_SESSION['user']['id']);
unset($usersModel);

$record_id = $_GET['id'] ?? ''; 

// Fetch Data
$form1 = $form1 ?? ($data['form1'] ?? []);
$raw_categories = $category ?? ($data['category'] ?? []);
$vendorsList = $vendors ?? ($data['vendors'] ?? []);

// --- NEW LOGIC START ---
$cat_id = $form1['group_name'] ?? ''; 
$category_display_name = 'Unknown'; 

if (!empty($raw_categories) && !empty($cat_id)) {
    foreach ($raw_categories as $cat_item) {
        if (isset($cat_item['category']) && $cat_item['category'] == $cat_id) {
            $category_display_name = $cat_item['display_name'];
            break; 
        }
    }
}
// --- NEW LOGIC END ---

$photo    = $form1['product_photo'] ?? ''; 
$selectedVendorCode = trim((string)($form1['vendor_code'] ?? ''));
$invoiceImg = $form1['invoice_image'] ?? '';
$invoice_no = $form1['invoice_no'] ?? '';

// Determine Edit Mode
$isEdit  = (!empty($selectedVendorCode) || !empty($invoiceImg));
$formAction = $isEdit
    ? base_url('?page=inbounding&action=updateform1&id=' . $record_id)
    : base_url('?page=inbounding&action=saveform1');

// --- PDF CHECK LOGIC ---
$showPreview = ($isEdit && !empty($invoiceImg));
$fileExt = pathinfo($invoiceImg, PATHINFO_EXTENSION);
$isPdf = (strtolower($fileExt) === 'pdf');
$src = $showPreview ? base_url($invoiceImg) : '#'; 

// Determine visibility classes based on file type
$placeholderClass = $showPreview ? 'hidden' : '';
$imgPreviewClass  = ($showPreview && !$isPdf) ? '' : 'hidden';
$pdfPreviewClass  = ($showPreview && $isPdf) ? '' : 'hidden';
$pdfPreviewLayoutClass = ($showPreview && $isPdf) ? ' form1-preview--pdf' : '';
$pdfPreviewPanelClass  = ($showPreview && $isPdf) ? ' form1-preview-panel--pdf' : '';
?>

<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

<style>
    /* Mobile / tablet first — form1 invoice step */
    .form1-page {
        --form1-brand: #d9822b;
        --form1-brand-dark: #bf7326;
        min-height: 100dvh;
        min-height: -webkit-fill-available;
    }
    .form1-shell {
        min-height: 100dvh;
        min-height: -webkit-fill-available;
    }
    @supports (padding: max(0px)) {
        .form1-header { padding-top: max(0.75rem, env(safe-area-inset-top)); }
        .form1-footer { padding-bottom: max(1rem, env(safe-area-inset-bottom)); }
    }
    .form1-touch-btn {
        min-height: 48px;
        -webkit-tap-highlight-color: transparent;
    }
    .form1-input {
        font-size: 16px; /* prevents iOS zoom on focus */
    }
    .form1-preview {
        min-height: 11rem;
        aspect-ratio: 4 / 3;
    }
    @media (min-width: 768px) {
        .form1-preview {
            min-height: 16rem;
            aspect-ratio: auto;
        }
    }
    @media (min-width: 1024px) {
        .form1-preview {
            min-height: 20rem;
        }
    }
    /* PDF: tall scrollable iframe (full document height inside viewer) */
    .form1-preview--pdf {
        aspect-ratio: auto;
        min-height: auto;
        align-items: stretch;
        justify-content: flex-start;
    }
    .form1-preview--pdf .form1-pdf-frame {
        display: block;
        width: 100%;
        min-height: min(85vh, 56rem);
        height: min(85vh, 56rem);
        max-height: none;
    }
    @media (min-width: 1024px) {
        .form1-preview-panel--pdf {
            min-height: auto;
        }
        .form1-preview--pdf .form1-pdf-frame {
            min-height: calc(100dvh - 11rem);
            height: calc(100dvh - 11rem);
        }
    }
    .form1-page .ts-wrapper { width: 100%; }
    .form1-page .ts-control {
        border-radius: 0.75rem;
        min-height: 48px;
        padding: 12px 16px;
        border-color: #d1d5db;
        font-size: 16px;
    }
    .form1-page .ts-control.focus {
        border-color: var(--form1-brand);
        box-shadow: 0 0 0 2px rgba(217, 130, 43, 0.2);
    }
    .form1-page .ts-dropdown {
        border-radius: 0.5rem;
        border-color: var(--form1-brand);
        z-index: 60;
    }
    .form1-page .ts-dropdown .active {
        background-color: #fff7ed;
        color: #9a3412;
    }
</style>

<div class="form1-page w-full bg-gray-100 flex justify-center md:py-4 lg:py-6">

    <div class="form1-shell w-full max-w-5xl bg-white flex flex-col md:rounded-2xl md:shadow-xl md:border md:border-gray-200 md:min-h-0 md:h-auto lg:min-h-[640px] overflow-hidden">

        <header class="form1-header bg-[#d9822b] text-white shadow-md z-30 flex-shrink-0">
            <div class="flex items-center gap-2 px-3 py-3 sm:px-4">
                <button type="button" id="back-btn" class="form1-touch-btn w-11 h-11 shrink-0 flex items-center justify-center hover:bg-white/20 rounded-full transition" aria-label="Back to list">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                </button>
                <div class="flex-1 min-w-0 text-center px-1">
                    <p class="text-[10px] sm:text-xs font-medium uppercase tracking-wider text-white/80">Inbound · Step 1 of 4</p>
                    <h1 class="font-semibold text-base sm:text-lg truncate">Invoice upload</h1>
                </div>
                <button type="button" id="cancel-btn" class="form1-touch-btn w-11 h-11 shrink-0 flex items-center justify-center hover:bg-white/20 rounded-full transition" aria-label="Cancel">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="px-4 pb-3" role="progressbar" aria-valuenow="1" aria-valuemin="1" aria-valuemax="4" aria-label="Step 1 of 4">
                <div class="h-1.5 rounded-full bg-white/25 overflow-hidden">
                    <div class="h-full w-1/4 rounded-full bg-white transition-all"></div>
                </div>
            </div>
        </header>

        <form action="<?php echo $formAction; ?>" method="POST" enctype="multipart/form-data" id="invoiceForm" class="flex flex-col flex-1 min-h-0">
            <input type="hidden" name="record_id" value="<?php echo htmlspecialchars((string) $record_id, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="flex-1 min-h-0 overflow-y-auto overscroll-contain px-4 py-4 sm:px-5 sm:py-5 md:px-6 md:py-6 bg-gray-50/80">

                <p class="text-sm text-gray-600 mb-4 leading-relaxed md:hidden">
                    Add an invoice photo or PDF. Vendor is optional — tap <strong class="text-gray-800">Skip</strong> or <strong class="text-gray-800">Next</strong> when ready.
                </p>

                <div class="flex flex-col gap-4 lg:grid lg:grid-cols-2 lg:gap-6 lg:items-start">

                    <div class="flex flex-col gap-4 order-1 lg:order-1">

                        <section class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4 sm:p-5" aria-labelledby="form1-upload-heading">
                            <h2 id="form1-upload-heading" class="text-sm font-bold text-gray-800 mb-1 flex items-center gap-2">
                                <span class="w-7 h-7 rounded-lg bg-orange-100 text-[#d9822b] flex items-center justify-center text-xs">1</span>
                                Upload invoice
                            </h2>
                            <p class="text-xs text-gray-500 mb-4 pl-9">JPG, PNG, or PDF</p>

                            <div class="flex flex-col gap-2.5 sm:flex-row sm:gap-3">
                                <label class="form1-touch-btn flex-1 cursor-pointer bg-gray-50 active:bg-gray-100 text-gray-800 px-4 rounded-xl text-sm font-bold flex items-center justify-center gap-2 border border-gray-200">
                                    <svg class="w-5 h-5 shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    Choose file
                                    <input type="file" id="invoice" name="invoice" accept="image/*,application/pdf" class="hidden">
                                </label>
                                <label class="form1-touch-btn flex-1 cursor-pointer bg-[#d9822b] active:bg-[#bf7326] text-white px-4 rounded-xl text-sm font-bold flex items-center justify-center gap-2 shadow-md shadow-orange-100/80">
                                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path></svg>
                                    Take photo
                                    <input type="file" name="invoice_capture" accept="image/*,application/pdf" capture="environment" class="hidden" onchange="document.getElementById('invoice').files = this.files; document.getElementById('invoice').dispatchEvent(new Event('change'));">
                                </label>
                            </div>
                            <div id="photo-error" class="text-red-600 text-xs mt-2 font-semibold min-h-[1.25rem]"></div>
                        </section>

                        <section class="order-2 lg:hidden bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden<?php echo $pdfPreviewPanelClass; ?>" aria-labelledby="form1-preview-heading-mobile">
                            <div class="px-4 py-2.5 border-b border-gray-100 flex items-center justify-between">
                                <h2 id="form1-preview-heading-mobile" class="text-sm font-bold text-gray-800">Preview</h2>
                                <button type="button" id="delete-preview-btn-mobile" class="<?php echo ($showPreview) ? '' : 'hidden'; ?> text-red-600 text-xs font-bold px-2.5 py-1.5 rounded-lg bg-red-50 border border-red-100">Remove</button>
                            </div>
                            <div id="form1-preview-box-mobile" class="form1-preview relative flex items-center justify-center bg-gray-50<?php echo $pdfPreviewLayoutClass; ?>">
                                <div id="placeholder-box-mobile" class="absolute inset-0 flex flex-col items-center justify-center text-gray-400 px-4 text-center <?php echo $placeholderClass; ?>">
                                    <svg class="w-12 h-12 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    <span class="text-xs font-medium">Preview appears after upload</span>
                                </div>
                                <img id="preview-mobile" src="<?php echo (!$isPdf) ? htmlspecialchars($src, ENT_QUOTES, 'UTF-8') : '#'; ?>" alt="" class="<?php echo $imgPreviewClass; ?> w-full h-full object-contain p-2 max-h-[40vh]">
                                <iframe id="pdf-preview-mobile" src="<?php echo ($isPdf) ? htmlspecialchars($src, ENT_QUOTES, 'UTF-8') : ''; ?>" class="<?php echo $pdfPreviewClass; ?> form1-pdf-frame border-0" title="PDF preview"></iframe>
                            </div>
                        </section>

                        <section class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4 sm:p-5 relative z-20" aria-labelledby="form1-vendor-heading">
                            <div class="flex items-start justify-between gap-2 mb-3">
                                <h2 id="form1-vendor-heading" class="text-sm font-bold text-gray-800 flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-lg bg-orange-100 text-[#d9822b] flex items-center justify-center text-xs">2</span>
                                    Vendor <span class="font-normal text-gray-500 text-xs">(optional)</span>
                                </h2>
                                <button type="button"
                                        id="vendor-cache-sync-btn"
                                        class="form1-touch-btn shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-xl border border-gray-300 bg-white text-gray-600 active:border-[#d9822b] active:text-[#d9822b]"
                                        title="Refresh vendors from catalog"
                                        aria-label="Refresh vendor list">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M3 9h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9z"></path>
                                        <path d="M3 9l2.45-4.9A2 2 0 0 1 7.24 3h9.52a2 2 0 0 1 1.8 1.1L21 9"></path>
                                        <path d="M12 3v6"></path>
                                    </svg>
                                </button>
                            </div>
                            <select id="vendor_code" name="vendor_code" placeholder="Search vendor..." autocomplete="off" class="w-full">
                                <option value="">Select vendor</option>
                                <?php if (!empty($vendorsList)): ?>
                                    <?php foreach ($vendorsList as $v): ?>
                                        <?php
                                        $vendorExternalCode = trim((string)($v['vendor_id'] ?? ''));
                                        if ($vendorExternalCode === '') {
                                            continue;
                                        }
                                        $isSelected = $selectedVendorCode !== '' && $selectedVendorCode === $vendorExternalCode;
                                        $vendorLabel = $vendorExternalCode . ' - ' . ($v['vendor_name'] ?? '');
                                        ?>
                                        <option value="<?php echo htmlspecialchars($vendorExternalCode, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $isSelected ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($vendorLabel, ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <p id="vendor-error" class="text-red-600 text-xs mt-1.5 font-semibold hidden"></p>
                        </section>

                        <section class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4 sm:p-5" aria-labelledby="form1-invoice-no-heading">
                            <h2 id="form1-invoice-no-heading" class="text-sm font-bold text-gray-800 mb-3 flex items-center gap-2">
                                <span class="w-7 h-7 rounded-lg bg-orange-100 text-[#d9822b] flex items-center justify-center text-xs">3</span>
                                Invoice number
                            </h2>
                            <input type="text"
                                   name="invoice_no"
                                   placeholder="Enter invoice number"
                                   value="<?php echo htmlspecialchars($invoice_no, ENT_QUOTES, 'UTF-8'); ?>"
                                   autocomplete="off"
                                   inputmode="text"
                                   class="form1-input w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#d9822b] focus:border-[#d9822b] outline-none font-medium text-gray-800">
                            <p id="invoice-no-error" class="text-red-600 text-xs mt-1.5 font-semibold hidden"></p>
                        </section>

                        <details class="lg:hidden group bg-orange-50 border border-orange-100 rounded-2xl text-sm text-orange-900">
                            <summary class="px-4 py-3 font-bold cursor-pointer list-none flex items-center justify-between">
                                Tips
                                <span class="text-orange-600 group-open:rotate-180 transition-transform">▼</span>
                            </summary>
                            <p class="px-4 pb-4 text-orange-800/90 leading-relaxed">Search vendors in the list above. Use refresh to sync from Exotic India if a vendor is missing.</p>
                        </details>

                        <div class="hidden lg:block p-4 bg-orange-50 border border-orange-100 rounded-2xl text-sm text-orange-800">
                            <p class="font-bold mb-1">Tips</p>
                            <p class="opacity-90 leading-relaxed">Vendor is optional. Sync the vendor list with the refresh button if needed.</p>
                        </div>
                    </div>

                    <aside class="hidden lg:flex flex-col gap-4 order-2 lg:sticky lg:top-4">
                        <section id="form1-preview-panel-desktop" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex-1 flex flex-col min-h-[20rem]<?php echo $pdfPreviewPanelClass; ?>" aria-labelledby="form1-preview-heading-desktop">
                            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                <h2 id="form1-preview-heading-desktop" class="text-sm font-bold text-gray-800">Preview</h2>
                                <button type="button" id="delete-preview-btn" class="<?php echo ($showPreview) ? '' : 'hidden'; ?> text-red-600 text-xs font-bold px-3 py-1.5 rounded-lg bg-red-50 border border-red-100 hover:bg-red-100 flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    Remove
                                </button>
                            </div>
                            <div id="form1-preview-box-desktop" class="form1-preview flex-1 relative flex items-center justify-center bg-gray-50 min-h-[20rem]<?php echo $pdfPreviewLayoutClass; ?>">
                                <div id="placeholder-box" class="absolute inset-0 flex flex-col items-center justify-center text-gray-400 <?php echo $placeholderClass; ?>">
                                    <svg class="w-16 h-16 mb-2 opacity-35" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    <span class="text-sm font-medium">Preview appears after upload</span>
                                </div>
                                <img id="preview" src="<?php echo (!$isPdf) ? htmlspecialchars($src, ENT_QUOTES, 'UTF-8') : '#'; ?>" alt="Invoice preview" class="<?php echo $imgPreviewClass; ?> w-full h-full object-contain p-3">
                                <iframe id="pdf-preview" src="<?php echo ($isPdf) ? htmlspecialchars($src, ENT_QUOTES, 'UTF-8') : ''; ?>" class="<?php echo $pdfPreviewClass; ?> form1-pdf-frame border-0" title="PDF preview"></iframe>
                            </div>
                        </section>
                    </aside>
                </div>
            </div>

            <footer class="form1-footer flex-shrink-0 z-30 bg-white/95 backdrop-blur-sm border-t border-gray-200 px-3 py-3 sm:px-4 sm:py-4 shadow-[0_-4px_20px_rgba(0,0,0,0.06)]">
                <div class="grid grid-cols-3 gap-2 sm:gap-3 max-w-5xl mx-auto">
                    <button type="button" id="footer-cancel-btn" class="form1-touch-btn bg-white active:bg-gray-50 text-gray-700 font-bold text-sm sm:text-base rounded-xl border border-gray-300 px-2">
                        Cancel
                    </button>
                    <button type="submit" id="skip-btn" class="form1-touch-btn bg-gray-100 active:bg-gray-200 text-gray-800 font-bold text-sm sm:text-base rounded-xl border border-gray-200 px-2">
                        Skip
                    </button>
                    <button type="submit" class="form1-touch-btn bg-[#d9822b] active:bg-[#bf7326] text-white font-bold text-sm sm:text-base rounded-xl shadow-md flex items-center justify-center gap-1 px-2">
                        <span>Next</span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4 sm:w-5 sm:h-5 shrink-0" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </button>
                </div>
            </footer>
        </form>
    </div>
</div>

<div id="imagePopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center z-50" onclick="closeImagePopup(event)">
    <div class="bg-white p-4 rounded-md max-w-3xl max-h-3xl relative flex flex-col items-center" onclick="event.stopPropagation();">
        <button onclick="closeImagePopup()" class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-full text-sm">✕</button>
        <img id="popupImage" class="max-w-full max-h-[80vh] rounded" src="" alt="Image Preview">
    </div>
</div>

<script>
    function initForm1VendorTomSelect() {
        const vendorEl = document.getElementById('vendor_code');
        if (!vendorEl || vendorEl.tomselect) {
            return vendorEl && vendorEl.tomselect ? vendorEl.tomselect : null;
        }
        return new TomSelect('#vendor_code', {
            create: false,
            sortField: {
                field: 'text',
                direction: 'asc'
            }
        });
    }

    initForm1VendorTomSelect();

    const vendorSyncBtn = document.getElementById('vendor-cache-sync-btn');
    if (vendorSyncBtn) {
        vendorSyncBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const runSync = function () {
                const origHtml = vendorSyncBtn.innerHTML;
                vendorSyncBtn.disabled = true;
                vendorSyncBtn.classList.add('opacity-60', 'cursor-wait');

                fetch(<?php echo json_encode(base_url('index.php?page=vendors&action=fetchAllVendors')); ?>, {
                    method: 'GET',
                    credentials: 'include',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(function (res) {
                        return res.json().then(function (data) {
                            return { ok: res.ok, data: data };
                        }).catch(function () {
                            return { ok: false, data: {} };
                        });
                    })
                    .then(function (payload) {
                        const data = payload.data || {};
                        const success = payload.ok && data.success === true && data.status !== 'error';
                        if (!success) {
                            if (typeof showGlobalToast === 'function') {
                                showGlobalToast(data.message || 'Could not sync vendors from the catalog API.', 'error');
                            } else {
                                alert(data.message || 'Could not sync vendors from the catalog API.');
                            }
                            return;
                        }
                        const msg = 'Inserted: ' + (data.inserted || 0) + ', Updated: ' + (data.updated || 0) + ', Total: ' + (data.total || 0) + '.';
                        if (typeof showGlobalToast === 'function') {
                            showGlobalToast(msg, 'success');
                        }
                        window.location.reload();
                    })
                    .catch(function () {
                        if (typeof showGlobalToast === 'function') {
                            showGlobalToast('Network error while syncing vendors.', 'error');
                        } else {
                            alert('Network error while syncing vendors.');
                        }
                    })
                    .finally(function () {
                        vendorSyncBtn.disabled = false;
                        vendorSyncBtn.classList.remove('opacity-60', 'cursor-wait');
                        vendorSyncBtn.innerHTML = origHtml;
                    });
            };

            const confirmMsg = 'Sync vendors from Exotic India now? (Same as Vendors → Sync from API.)';
            if (typeof customConfirm === 'function') {
                customConfirm(confirmMsg, { title: 'Refresh vendor list?' }).then(function (ok) {
                    if (ok) runSync();
                });
            } else if (confirm(confirmMsg)) {
                runSync();
            }
        });
    }

    const invoiceInput = document.getElementById('invoice');
    const errorBox = document.getElementById('photo-error');

    const previewPairs = [
        {
            img: document.getElementById('preview'),
            pdf: document.getElementById('pdf-preview'),
            placeholder: document.getElementById('placeholder-box'),
            deleteBtn: document.getElementById('delete-preview-btn'),
            box: document.getElementById('form1-preview-box-desktop'),
            panel: document.getElementById('form1-preview-panel-desktop'),
        },
        {
            img: document.getElementById('preview-mobile'),
            pdf: document.getElementById('pdf-preview-mobile'),
            placeholder: document.getElementById('placeholder-box-mobile'),
            deleteBtn: document.getElementById('delete-preview-btn-mobile'),
            box: document.getElementById('form1-preview-box-mobile'),
            panel: document.querySelector('[aria-labelledby="form1-preview-heading-mobile"]'),
        },
    ].filter(function (p) { return p.img && p.pdf; });

    function form1SetPdfPreviewLayout(isPdf) {
        previewPairs.forEach(function (p) {
            if (p.box) {
                p.box.classList.toggle('form1-preview--pdf', !!isPdf);
            }
            if (p.panel) {
                p.panel.classList.toggle('form1-preview-panel--pdf', !!isPdf);
            }
        });
    }

    function form1SetPreviewVisible(show) {
        previewPairs.forEach(function (p) {
            if (p.deleteBtn) {
                p.deleteBtn.classList.toggle('hidden', !show);
            }
            if (p.placeholder) {
                p.placeholder.classList.toggle('hidden', show);
            }
        });
    }

    function form1ShowPreviewDataUrl(dataUrl, isPdf) {
        previewPairs.forEach(function (p) {
            if (isPdf) {
                p.img.classList.add('hidden');
                p.pdf.src = dataUrl;
                p.pdf.classList.remove('hidden');
            } else {
                p.pdf.classList.add('hidden');
                p.pdf.src = '';
                p.img.src = dataUrl;
                p.img.classList.remove('hidden');
            }
        });
        form1SetPdfPreviewLayout(isPdf);
        form1SetPreviewVisible(true);
        if (errorBox) {
            errorBox.textContent = '';
        }
    }

    function form1ClearPreview() {
        previewPairs.forEach(function (p) {
            p.img.src = '#';
            p.img.classList.add('hidden');
            p.pdf.src = '';
            p.pdf.classList.add('hidden');
        });
        form1SetPdfPreviewLayout(false);
        form1SetPreviewVisible(false);
    }

    invoiceInput.addEventListener('change', function (event) {
        const file = event.target.files[0];
        if (!file) {
            return;
        }
        const reader = new FileReader();
        reader.onload = function (e) {
            const isPdf = file.type === 'application/pdf' || /\.pdf$/i.test(file.name);
            form1ShowPreviewDataUrl(e.target.result, isPdf);
        };
        reader.readAsDataURL(file);
    });

    function form1BindDelete(btn) {
        if (!btn) {
            return;
        }
        btn.addEventListener('click', function () {
            invoiceInput.value = '';
            form1ClearPreview();
        });
    }
    previewPairs.forEach(function (p) {
        form1BindDelete(p.deleteBtn);
    });

    // Back / Cancel — return to inbounding list
    const form1ListUrl = <?php echo json_encode(base_url('?page=inbounding&action=list')); ?>;
    function form1GoToInboundList() {
        window.location.href = form1ListUrl;
    }
    document.getElementById("back-btn").addEventListener("click", form1GoToInboundList);
    document.getElementById("cancel-btn").addEventListener("click", form1GoToInboundList);
    document.getElementById("footer-cancel-btn").addEventListener("click", form1GoToInboundList);

    // Popup Logic for Images
    function openImagePopup(imageUrl) {
        if(!imageUrl.toLowerCase().endsWith('.pdf')) {
            document.getElementById('popupImage').src = imageUrl;
            document.getElementById('imagePopup').classList.remove('hidden');
        }
    }
    function closeImagePopup(event) {
        document.getElementById('imagePopup').classList.add('hidden');
        document.getElementById('popupImage').src = '';
    } 
</script>