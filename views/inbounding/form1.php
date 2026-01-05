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
$form1 = $data['form1'] ?? [];
$raw_categories = $data['category'] ?? [];

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
$vendor = $form1['vendor_code'] ?? '';
$invoiceImg = $form1['invoice_image'] ?? '';
$invoice_no = $form1['invoice_no'] ?? '';

// Determine Edit Mode
$isEdit  = (!empty($vendor) || !empty($invoiceImg));
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
?>

<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

<style>
    /* Custom overrides for Tom Select to match Tailwind */
    .ts-control {
        border-radius: 0.75rem; /* rounded-xl */
        padding: 12px 16px;
        border-color: #d1d5db;
        font-family: 'Inter', sans-serif;
        font-size: 1rem;
    }
    .ts-control.focus {
        border-color: #d9822b;
        box-shadow: 0 0 0 2px rgba(217, 130, 43, 0.2);
    }
    .ts-dropdown {
        border-radius: 0.5rem;
        border-color: #d9822b;
        overflow: hidden;
        z-index: 50;
    }
    .ts-dropdown .active {
        background-color: #fff7ed; /* orange-50 */
        color: #9a3412; /* orange-800 */
    }
</style>

<div class="w-full flex items-center justify-center p-0 md:p-6 bg-gray-100 min-h-screen">

    <div class="w-full h-screen md:h-auto md:min-h-[700px] md:max-w-5xl bg-white md:rounded-2xl shadow-2xl overflow-hidden flex flex-col relative border border-gray-200">
        
        <div class="bg-[#d9822b] px-4 py-4 flex items-center justify-between text-white shadow-md z-20 flex-shrink-0">
            <button type="button" id="back-btn" class="p-2 hover:bg-white/20 rounded-full transition">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </button>

            <h1 class="font-semibold text-lg tracking-wide">Invoice Upload - Step: 1/4</h1>

            <button type="button" id="cancel-btn" class="p-2 hover:bg-white/20 rounded-full transition">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form action="<?php echo $formAction; ?>" method="POST" enctype="multipart/form-data" id="invoiceForm" class="flex flex-col flex-1 overflow-hidden">
            <input type="hidden" name="record_id" value="<?php echo $record_id; ?>">

            <div class="flex-1 overflow-y-auto p-5 md:p-8 bg-gray-50/50">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 h-full">
                    
                    <div class="flex flex-col gap-6">
                        
                        <div class="relative border-2 border-dashed border-gray-300 rounded-xl p-6 text-center bg-white group hover:border-[#d9822b] hover:bg-orange-50/10 transition-all duration-300">
                            <span class="absolute -top-3 left-1/2 transform -translate-x-1/2 bg-white px-3 font-bold text-gray-700 text-sm flex items-center gap-2 border border-gray-100 rounded-full shadow-sm">
                                <svg class="w-4 h-4 text-[#d9822b]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                Upload Invoice
                            </span>
                            
                            <p class="text-xs text-gray-500 mb-5 mt-3">Upload Image (JPG, PNG) or PDF</p>

                            <div class="flex gap-3 justify-center flex-wrap">
                                <label class="cursor-pointer bg-gray-50 hover:bg-gray-100 text-gray-700 px-5 py-2.5 rounded-lg text-xs font-bold flex items-center gap-2 transition border border-gray-200 shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    Choose File
                                    <input type="file" id="invoice" name="invoice" accept="image/*,application/pdf" class="hidden">
                                </label>
                                <label class="cursor-pointer bg-[#d9822b] hover:bg-[#bf7326] text-white px-5 py-2.5 rounded-lg text-xs font-bold flex items-center gap-2 transition shadow-md shadow-orange-100">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path></svg>
                                    Take Photo
                                    <input type="file" name="invoice_capture" accept="image/*,application/pdf" capture="camera" class="hidden" onchange="document.getElementById('invoice').files = this.files; document.getElementById('invoice').dispatchEvent(new Event('change'));">
                                </label>
                            </div>
                            <div id="photo-error" class="text-red-500 text-xs mt-2 font-bold min-h-[20px]"></div>
                        </div>

                        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm relative z-30">
                            <label class="block text-gray-700 font-bold text-sm mb-2 ml-1">Select Vendor</label>
                            
                            <select id="vendor_id" name="vendor_id" placeholder="Type to search vendor..." autocomplete="off">
                                <option value="">Select Vendor...</option>
                                <?php if (!empty($data['vendors'])): ?>
                                    <?php foreach ($data['vendors'] as $v) { ?>
                                        <option value="<?php echo $v['id']; ?>" <?php echo ($vendor == $v['id']) ? 'selected' : ''; ?>>
                                            <?php echo $v['vendor_name']; ?>
                                        </option>
                                    <?php } ?>
                                <?php endif; ?>
                            </select>
                            <p id="vendor-error" class="text-red-500 text-xs mt-1 font-semibold hidden"></p>
                        </div>
                        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm relative z-0">
                            <label class="block text-gray-700 font-bold text-sm mb-2 ml-1">Invoice Number</label>
                            <input type="text" 
                                   name="invoice_no" 
                                   placeholder="Enter Invoice No"
                                   value="<?php echo $invoice_no; ?>" 
                                   class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-[#d9822b] focus:border-[#d9822b] outline-none font-medium shadow-sm transition-all text-gray-800">
                                   <p id="invoice-no-error" class="text-red-500 text-xs mt-1 font-semibold hidden"></p>
                        </div>
                        <div class="hidden md:block mt-auto p-4 bg-orange-50 border border-orange-100 rounded-xl text-sm text-orange-800">
                            <p class="font-bold mb-1">Instructions:</p>
                            <p class="opacity-80">Search for the vendor in the dropdown above. If not found, contact the admin.</p>
                        </div>

                    </div>

                    <div class="flex flex-col gap-4 h-full">

                        <div class="flex-grow bg-white rounded-xl border border-gray-200 relative overflow-hidden flex items-center justify-center min-h-[300px] shadow-inner">
                             
                             <div id="placeholder-box" class="absolute inset-0 flex flex-col items-center justify-center text-gray-300 <?php echo $placeholderClass; ?>">
                                 <svg class="w-16 h-16 mb-2 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                 <span class="text-xs font-medium opacity-60">Preview will appear here</span>
                             </div>

                             <img id="preview" src="<?php echo (!$isPdf) ? $src : '#'; ?>" class="<?php echo $imgPreviewClass; ?> w-full h-full object-contain p-2">

                             <iframe id="pdf-preview" src="<?php echo ($isPdf) ? $src : ''; ?>" class="<?php echo $pdfPreviewClass; ?> w-full h-full p-2 border-0"></iframe>

                             <button type="button" id="delete-preview-btn" class="<?php echo ($showPreview) ? '' : 'hidden'; ?> absolute top-4 right-4 bg-white/90 backdrop-blur text-red-600 text-xs font-bold px-3 py-1.5 rounded-lg shadow-sm border border-red-100 flex items-center gap-1 hover:bg-red-50 transition z-10">
                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                 Delete
                             </button>
                        </div>

                    </div>
                </div>
            </div>

            <div class="p-5 bg-white border-t border-gray-100 mt-auto z-20">
                <button type="submit" form="invoiceForm" class="w-full bg-[#d9822b] hover:bg-[#bf7326] text-white font-bold text-lg py-3.5 rounded-xl shadow-lg transform transition active:scale-[0.99] flex justify-center items-center gap-2">
                    <?php echo $isEdit ? "Update & Next" : "Update & Next"; ?><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </button>
            </div>
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
    // 1. Initialize Tom Select (Searchable Dropdown)
    new TomSelect("#vendor_id",{
        create: false,
        sortField: {
            field: "text",
            direction: "asc"
        }
    });

    const invoiceInput = document.getElementById('invoice');
    const previewImg = document.getElementById('preview');
    const pdfPreview = document.getElementById('pdf-preview');
    const deleteBtn = document.getElementById('delete-preview-btn');
    const placeholder = document.getElementById('placeholder-box');
    const errorBox = document.getElementById("photo-error");

    // 2. Preview Logic
    invoiceInput.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                if (file.type === 'application/pdf') {
                    previewImg.classList.add('hidden');
                    pdfPreview.src = e.target.result;
                    pdfPreview.classList.remove('hidden');
                } else {
                    pdfPreview.classList.add('hidden');
                    previewImg.src = e.target.result;
                    previewImg.classList.remove('hidden');
                }
                deleteBtn.classList.remove('hidden');
                placeholder.classList.add('hidden');
                errorBox.textContent = ""; 
            }
            reader.readAsDataURL(file);
        }
    });

    // 3. Delete Logic
    deleteBtn.addEventListener('click', function() {
        invoiceInput.value = ''; 
        previewImg.src = '#';
        previewImg.classList.add('hidden');
        pdfPreview.src = ''; 
        pdfPreview.classList.add('hidden');
        deleteBtn.classList.add('hidden');
        placeholder.classList.remove('hidden');
    });

    // 4. Back Button Logic
    const recordId = <?php echo json_encode($record_id); ?>;
    document.getElementById("back-btn").addEventListener("click", function () {
        window.location.href = window.location.origin + "/index.php?page=inbounding&action=list";
    });
    document.getElementById("cancel-btn").addEventListener("click", function () {
        window.location.href = window.location.origin + "/index.php?page=inbounding&action=list";
    });

    // ---------------------------------------------
    // 5. VALIDATION LOGIC (UPDATED for Inline Errors)
    // ---------------------------------------------
    document.getElementById("invoiceForm").addEventListener("submit", function(e) {
        let isValid = true;
        
        // --- 1. Get Inputs & Error Containers ---
        const vendorInput = document.getElementById("vendor_id");
        const vendorError = document.getElementById("vendor-error");
        
        const invoiceNoInput = document.querySelector('input[name="invoice_no"]');
        const invoiceError = document.getElementById("invoice-no-error");

        // --- 2. Reset Previous Errors ---
        vendorError.classList.add("hidden");
        vendorError.innerText = "";
        invoiceError.classList.add("hidden");
        invoiceError.innerText = "";
        
        // Remove red borders
        invoiceNoInput.classList.remove("border-red-500", "ring-2", "ring-red-100");
        const tsControl = document.querySelector('.ts-control');
        if(tsControl) tsControl.style.borderColor = "#d1d5db"; // Reset TomSelect border


        // --- 5. Stop Form if Invalid ---
        if (!isValid) {
            e.preventDefault(); 
        }
    });

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