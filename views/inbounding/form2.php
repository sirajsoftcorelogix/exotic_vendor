<?php
// 1. PHP Logic (Kept exactly as provided)
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
$category = $form1['category_code'] ?? 'Unknown';
$photo    = $form1['product_photo'] ?? ''; // Previous step photo

$vendor = $form1['vendor_code'] ?? '';
$invoiceImg = $form1['invoice_image'] ?? '';

// Determine Edit Mode & Action URL
$isEdit  = (!empty($vendor) || !empty($invoiceImg));
$formAction = $isEdit
    ? base_url('?page=inbounding&action=updateform2&id=' . $record_id)
    : base_url('?page=inbounding&action=saveform2');
?>


<div class="bg-gray-100 min-h-screen w-full flex items-center justify-center p-0 md:p-6">

    <div class="w-full h-screen md:h-auto md:min-h-[600px] md:max-w-5xl bg-white md:rounded-2xl shadow-2xl overflow-hidden flex flex-col relative border border-gray-200">
        
        <div class="bg-[#d9822b] px-4 py-4 flex items-center justify-between text-white shadow-md z-20 flex-shrink-0">
            <button type="button" id="back-btn" class="p-2 hover:bg-white/20 rounded-full transition">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </button>
            <h1 class="font-semibold text-lg tracking-wide">Invoice Upload - Step: 2/4</h1>
            <div class="w-6"></div> </div>

        <form action="<?php echo $formAction; ?>" method="POST" enctype="multipart/form-data" id="invoiceForm" class="flex flex-col flex-1 overflow-hidden">
            <input type="hidden" name="record_id" value="<?php echo $record_id; ?>">

            <div class="flex-1 overflow-y-auto p-5 md:p-8 bg-gray-50/50">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 h-full">
                    
                    <div class="flex flex-col gap-6">
                        
                        <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-4 shadow-sm">
                            <div class="w-20 h-20 bg-gray-50 border border-gray-100 rounded-lg flex-shrink-0 overflow-hidden flex items-center justify-center">
                                <?php if(!empty($photo)): ?>
                                    <img src="<?php echo base_url($photo); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <?php endif; ?>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xs text-gray-400 font-bold uppercase tracking-wider mb-1">Selected Category</span>
                                <span class="text-gray-800 font-bold text-xl leading-tight"><?php echo htmlspecialchars($category); ?></span>
                            </div>
                        </div>

                        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                            <label class="block text-gray-700 font-bold text-sm mb-2">Select Vendor</label>
                            <div class="relative">
                                <select id="vendor_id" name="vendor_id" class="w-full appearance-none bg-white border border-gray-300 text-gray-700 py-3.5 px-4 pr-8 rounded-lg leading-tight focus:outline-none focus:ring-2 focus:ring-[#d9822b] focus:border-transparent font-medium cursor-pointer hover:border-gray-400 transition-colors">
                                    <option value="">Search and Select Vendor...</option>
                                    <?php if (!empty($data['vendors'])): ?>
                                        <?php foreach ($data['vendors'] as $v) { ?>
                                            <option value="<?php echo $v['id']; ?>" <?php echo ($vendor == $v['id']) ? 'selected' : ''; ?>>
                                                <?php echo $v['vendor_name']; ?>
                                            </option>
                                        <?php } ?>
                                    <?php endif; ?>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                                </div>
                            </div>
                        </div>
                        
                        <div class="hidden md:block mt-auto p-4 bg-orange-50 border border-orange-100 rounded-xl">
                            <h4 class="text-[#d9822b] font-bold text-sm mb-2">Instructions</h4>
                            <p class="text-sm text-gray-600">Please upload a clear photo of the invoice. Ensure the Vendor name and Total Amount are visible.</p>
                        </div>

                    </div>

                    <div class="flex flex-col gap-4 h-full">
                        
                        <div class="relative border-2 border-dashed border-gray-300 rounded-xl p-6 text-center bg-white group hover:border-[#d9822b] hover:bg-orange-50/10 transition-all duration-300">
                            <span class="absolute -top-3 left-1/2 transform -translate-x-1/2 bg-white px-3 font-bold text-gray-700 text-sm flex items-center gap-2 border border-gray-100 rounded-full shadow-sm">
                                <svg class="w-4 h-4 text-[#d9822b]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                Upload Invoice
                            </span>
                            
                            <p class="text-xs text-gray-500 mb-5 mt-3">Drag file here or use buttons below</p>

                            <div class="flex gap-3 justify-center flex-wrap">
                                <label class="cursor-pointer bg-gray-50 hover:bg-gray-100 text-gray-700 px-5 py-2.5 rounded-lg text-xs font-bold flex items-center gap-2 transition border border-gray-200 shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    Choose File
                                    <input type="file" id="invoice" name="invoice" accept="image/*" class="hidden">
                                </label>
                                <label class="cursor-pointer bg-[#d9822b] hover:bg-[#bf7326] text-white px-5 py-2.5 rounded-lg text-xs font-bold flex items-center gap-2 transition shadow-md shadow-orange-100">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path></svg>
                                    Take Photo
                                    <input type="file" name="invoice_capture" accept="image/*" capture="camera" class="hidden" onchange="document.getElementById('invoice').files = this.files; document.getElementById('invoice').dispatchEvent(new Event('change'));">
                                </label>
                            </div>
                            <div id="photo-error" class="text-red-500 text-xs mt-2 font-bold min-h-[20px]"></div>
                        </div>

                        <div class="flex-grow bg-white rounded-xl border border-gray-200 relative overflow-hidden flex items-center justify-center min-h-[300px] shadow-inner">
                             <?php 
                                $showPreview = ($isEdit && !empty($invoiceImg));
                                $src = $showPreview ? base_url($invoiceImg) : '#'; 
                                $hiddenClass = $showPreview ? '' : 'hidden';
                             ?>
                             
                             <div id="placeholder-box" class="absolute inset-0 flex flex-col items-center justify-center text-gray-300 <?php echo $showPreview ? 'hidden' : ''; ?>">
                                 <svg class="w-16 h-16 mb-2 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                 <span class="text-xs font-medium opacity-60">Preview will appear here</span>
                             </div>

                             <img id="preview" src="<?php echo $src; ?>" class="<?php echo $hiddenClass; ?> w-full h-full object-contain p-2">

                             <button type="button" id="delete-preview-btn" class="<?php echo $hiddenClass; ?> absolute top-4 right-4 bg-white/90 backdrop-blur text-red-600 text-xs font-bold px-3 py-1.5 rounded-lg shadow-sm border border-red-100 flex items-center gap-1 hover:bg-red-50 transition z-10">
                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                 Delete
                             </button>
                        </div>

                    </div>
                </div>
            </div>

            <div class="p-5 bg-white border-t border-gray-100 mt-auto z-20">
                <button type="submit" form="invoiceForm" class="w-full bg-[#d9822b] hover:bg-[#bf7326] text-white font-bold text-lg py-3.5 rounded-xl shadow-lg transform transition active:scale-[0.99] flex justify-center items-center gap-2">
                    <?php echo $isEdit ? "Update & Next" : "Update & Next >"; ?>
                </button>
            </div>
        </form>
    </div>
</div>
    <script>
        const invoiceInput = document.getElementById('invoice');
        const previewImg = document.getElementById('preview');
        const deleteBtn = document.getElementById('delete-preview-btn');
        const placeholder = document.getElementById('placeholder-box');
        const errorBox = document.getElementById("photo-error");

        // Preview Logic
        invoiceInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewImg.classList.remove('hidden');
                    deleteBtn.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                    errorBox.textContent = ""; 
                }
                reader.readAsDataURL(file);
            }
        });

        // Delete Logic
        deleteBtn.addEventListener('click', function() {
            invoiceInput.value = ''; // Clear input
            previewImg.src = '#';
            previewImg.classList.add('hidden');
            deleteBtn.classList.add('hidden');
            placeholder.classList.remove('hidden');
        });

        // Back Button Logic
        const recordId = <?php echo json_encode($record_id); ?>;
        document.getElementById("back-btn").addEventListener("click", function () {
            window.location.href = window.location.origin + "/index.php?page=inbounding&action=form1&id=" + recordId;
        });

        // Form Validation
        document.getElementById("invoiceForm").addEventListener("submit", function(e) {
            const isEditMode = "<?php echo $isEdit ? '1' : '0'; ?>";
            const hasFile = invoiceInput.files.length > 0;
            const previewVisible = !previewImg.classList.contains('hidden');

            // If not edit mode and no file, block
            if (isEditMode === '0' && !hasFile) {
                errorBox.textContent = "Please upload an invoice photo.";
                e.preventDefault();
                return false;
            }
            // If edit mode but preview was deleted and no new file, block
            if (isEditMode === '1' && !previewVisible) {
                 errorBox.textContent = "Please upload an invoice photo.";
                 e.preventDefault();
            }
        });
    </script>