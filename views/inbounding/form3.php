<?php
// 1. PHP Logic & Auth
is_login();
require_once 'settings/database/database.php';
$conn = Database::getConnection();
require_once 'models/user/user.php';
$usersModel = new User($conn);
$currentuserDetails = $usersModel->getUserById($_SESSION['user']['id']);
unset($usersModel);
$record_id = $_GET['id'] ?? '';
$form2 = $data['form2'] ?? []; // Main table data
$saved_category_code = $form2['group_name'] ?? ''; 

$raw_categories = $data['category'] ?? []; 
$icon_map = [
    'sculptures'    => 'fa-solid fa-monument',
    'book'          => 'fa-solid fa-book',
    'jewelry'       => 'fa-solid fa-gem',
    'textiles'      => 'fa-solid fa-shirt',
    'paintings'     => 'fa-solid fa-palette',
    'homeandliving' => 'fa-solid fa-couch',
    'default'       => 'fa-solid fa-box-open'
];

// 4. PROCESS CATEGORIES
$display_categories = [];
if (!empty($raw_categories)) {
    foreach ($raw_categories as $cat) {
        if (isset($cat['parent_id']) && $cat['parent_id'] == 0) {
            $iconClass = $icon_map[$cat['name']] ?? $icon_map['default'];
            $display_categories[] = [
                'value' => $cat['category'],    
                'label' => $cat['display_name'], 
                'icon'  => $iconClass
            ];
        }
    }
}

// 3. Prepare Variations for View
// We merge the Main Variation (from vp_inbound) with Extra Variations (from vp_variations)

// A. Main Variation (Index 0)
// 3. Prepare Variations for View

// A. Main Variation (Index 0) -> COMES FROM INBOUND TABLE
$mainVar = [
    'color'    => $form2['color'] ?? '',
    'size'     => $form2['size'] ?? '',
    'quantity' => $form2['quantity_received'] ?? '',
    'cp'       => $form2['cp'] ?? '',
    'photo'    => $form2['product_photo'] ?? '',
    'invoice'  => $form2['invoice_image'] ?? '',
    // Add Dimensions here (sourced from vp_inbound / form2)
    'height'   => $form2['height'] ?? '',
    'width'    => $form2['width'] ?? '',
    'depth'    => $form2['depth'] ?? '',
    'weight'   => $form2['weight'] ?? ''
];

// B. Extra Variations (Index 1+) -> COMES FROM VARIATIONS TABLE
global $inboundingModel;
$extraVars = $inboundingModel->getVariations($record_id);

// C. Merge
$viewVariations = [];
$viewVariations[] = $mainVar; // Index 0

foreach ($extraVars as $ex) {
    $viewVariations[] = [
        'id'       => $ex['id'],
        'color'    => $ex['color'],
        'size'     => $ex['size'],
        'quantity' => $ex['quantity'],
        'cp'       => $ex['cp'],
        'photo'    => $ex['variation_image'] ?? '',
        // Add Dimensions here (sourced from vp_variations)
        'height'   => $ex['height'] ?? '',
        'width'    => $ex['width'] ?? '',
        'depth'    => $ex['depth'] ?? '',
        'weight'   => $ex['weight'] ?? ''
    ];
}

// 4. Other Fields
$temp_code      = $form2['temp_code'] ?? '';
$vendor_name    = $form2['vendor_name'] ?? '';
$gate_entry_date_time = $form2['gate_entry_date_time'] ?? '';
$material_code        = $form2['material_code'] ?? '';
$height = $form2['height'] ?? '';
$width  = $form2['width'] ?? '';
$depth  = $form2['depth'] ?? '';
$weight = $form2['weight'] ?? '';

// Single Action URL
$formAction = base_url('?page=inbounding&action=submitStep3');
?>

<div class="w-full flex items-center justify-center p-0 md:p-6 bg-gray-100 min-h-screen">
    <div class="w-full h-screen md:h-auto md:min-h-[700px] md:max-w-6xl bg-white md:rounded-2xl shadow-2xl overflow-hidden flex flex-col relative border border-gray-200">

        <div class="bg-[#d9822b] px-4 py-4 flex items-center justify-between text-white shadow-md z-20 flex-shrink-0">
            <button type="button" id="back-btn" class="p-2 hover:bg-white/20 rounded-full transition">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
            </button>
            <h1 class="font-semibold text-lg tracking-wide">Item Details - Step: 3/4</h1>
            <button type="button" id="cancel-btn" class="p-2 hover:bg-white/20 rounded-full transition">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button> 
        </div>

        <form action="<?php echo $formAction; ?>" method="POST" enctype="multipart/form-data" class="flex flex-col flex-1 overflow-hidden" id="mainForm">
            <input type="hidden" name="record_id" value="<?php echo $record_id; ?>">

            <div class="flex-1 overflow-y-auto p-4 md:p-6 bg-gray-50/50">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 h-full">

                    <div class="flex flex-col gap-5">

                        <div class="bg-white border border-gray-200 rounded-xl p-4 flex gap-4 shadow-sm relative overflow-hidden">
                            <div class="absolute top-0 right-0 -mt-2 -mr-2 w-16 h-16 bg-orange-50 rounded-full z-0"></div>
                            <div class="w-20 h-20 bg-gray-50 border border-gray-100 rounded-lg flex-shrink-0 overflow-hidden flex items-center justify-center z-10">
                                <?php if(!empty($mainVar['invoice'])): ?>
                                    <img src="<?php echo base_url($mainVar['invoice']); ?>" class="w-full h-full object-cover" onclick="openImagePopup('<?= $mainVar['invoice'] ?>')">
                                <?php else: ?>
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <?php endif; ?>
                            </div>
                            <div class="flex flex-col justify-center gap-1 z-10">
                                <div><span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Temp Code</span> <div class="font-bold text-gray-800 leading-none"><?php echo htmlspecialchars($temp_code); ?></div></div>
                                
                                <div><span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Vendor</span> <div class="font-bold text-gray-800 leading-none"><?php echo htmlspecialchars($vendor_name); ?></div></div>
                            </div>
                        </div>
                        <div class="flex flex-col gap-4">
                            <div class="bg-white border border-gray-200 rounded-xl p-4 md:p-6 shadow-sm h-full">
                                <div class="flex items-center gap-2 mb-4 border-b border-gray-100 pb-2">
                                    <span class="bg-[#d9822b] w-1 h-5 rounded-full"></span>
                                    <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Select Category</h3>
                                </div>
                                
                                <div class="grid grid-cols-3 gap-3">
                                    <?php if(empty($display_categories)): ?>
                                        <p class="col-span-3 text-center text-gray-400 text-xs">No categories found.</p>
                                    <?php else: ?>
                                        <?php foreach ($display_categories as $item) { ?>
                                            <label class="relative cursor-pointer group">
                                                <input type="radio" name="category" value="<?= $item['value'] ?>" class="peer sr-only" <?php if ($saved_category_code == $item['value']) echo 'checked'; ?>>
                                                
                                                <div class="aspect-square flex flex-col items-center justify-center p-2 rounded-xl transition-all duration-200
                                                            bg-gray-900 text-white border-2 border-transparent shadow-sm
                                                            peer-checked:bg-white peer-checked:text-black peer-checked:border-black peer-checked:shadow-md hover:shadow-lg">
                                                    <div class="absolute top-2 left-2 w-4 h-4 rounded-full border-2 border-current flex items-center justify-center">
                                                        <div class="w-2 h-2 rounded-full bg-current opacity-0 peer-checked:opacity-100 transition-opacity"></div>
                                                    </div>
                                                    <i class="<?= $item['icon'] ?> text-3xl mb-2 transition-transform duration-200 peer-checked:scale-110"></i>
                                                    <span class="text-[10px] md:text-[11px] font-bold uppercase tracking-wide text-center leading-tight"><?= $item['label'] ?></span>
                                                </div>
                                            </label>
                                        <?php } ?>
                                    <?php endif; ?>
                                </div>
                                <div id="category-error" class="text-red-500 text-xs mt-3 text-center font-bold"></div>
                            </div>
                        </div>
                        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex flex-col gap-4">
                            <div>
                                <label class="block text-gray-700 font-bold text-xs uppercase mb-1">Gate Entry Date & Time</label>
                                <?php 
                                $inputValue = (!empty($gate_entry_date_time) && $gate_entry_date_time != "0000-00-00 00:00:00") 
                                ? date('Y-m-d\TH:i', strtotime($gate_entry_date_time)) 
                                : date('Y-m-d\TH:i');
                                ?>
                                <input type="datetime-local" name="gate_entry_date_time" value="<?php echo $inputValue; ?>" class="w-full bg-gray-50 border border-gray-300 text-gray-700 py-2.5 px-3 rounded-lg focus:ring-2 focus:ring-[#d9822b] outline-none shadow-sm text-sm">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-bold text-xs uppercase mb-1">Received By</label>
                                <input type="hidden" name="received_by_user_id" value="<?php echo $_SESSION['user']['id']; ?>">
                                <input type="text" name="received_by_name" value="<?php echo htmlspecialchars($currentuserDetails['name']); ?>" readonly class="w-full bg-gray-100 border border-gray-300 text-gray-500 py-2.5 px-3 rounded-lg cursor-not-allowed font-medium text-sm">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-bold text-xs uppercase mb-1">Material</label>
                                <div class="relative">
                                    <select name="material_code" class="w-full appearance-none bg-white border border-gray-300 text-gray-700 py-2.5 px-3 pr-8 rounded-lg focus:ring-2 focus:ring-[#d9822b] outline-none font-medium cursor-pointer shadow-sm text-sm">
                                        <option value="">Select Material</option>
                                        <?php foreach ($data['material'] as $value2) { 
                                            $isSelected = ($material_code == $value2['id']) ? 'selected' : '';
                                            ?> 
                                            <option <?php echo $isSelected; ?> value="<?php echo $value2['id']; ?>"> <?php echo $value2['material_name']; ?></option>
                                        <?php } ?>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-4 h-full">
                        <div id="variations-container" class="flex flex-col gap-4">

                            <?php foreach($viewVariations as $index => $var): ?>
                                <div class="variation-card bg-white p-4 rounded-xl border border-gray-200 shadow-sm relative transition-all hover:shadow-md" data-index="<?php echo $index; ?>">
                                    
                                    <div class="flex justify-between items-center mb-3">
                                        <h4 class="font-bold text-gray-700 text-sm">
                                            <?php echo ($index === 0) ? 'Base Variation' : 'Variation ' . ($index + 1); ?>
                                        </h4>
                                        <button type="button" class="remove-variation-btn <?php echo ($index === 0) ? 'hidden' : ''; ?> text-red-500 hover:bg-red-50 p-1 rounded transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>

                                    <div class="flex flex-row gap-4">
                                        <div class="w-24 flex-shrink-0 flex flex-col gap-2">
                                            <label class="cursor-pointer group relative w-24 h-24 bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center hover:border-[#d9822b] hover:bg-orange-50/20 transition overflow-hidden">
                                                <?php $hasPhoto = !empty($var['photo']); ?>
                                                <img src="<?php echo $hasPhoto ? base_url($var['photo']) : '#'; ?>" class="preview-img <?php echo $hasPhoto ? '' : 'hidden'; ?> w-full h-full object-cover absolute inset-0 z-10">
                                                <div class="placeholder-icon <?php echo $hasPhoto ? 'hidden' : ''; ?> flex flex-col items-center justify-center text-gray-400 group-hover:text-[#d9822b] transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                    <span class="text-[9px] uppercase font-bold">Photo</span>
                                                </div>
                                                <input type="file" name="variations[<?php echo $index; ?>][photo]" accept="image/*" class="hidden variation-file-input">
                                                <input type="hidden" name="variations[<?php echo $index; ?>][old_photo]" value="<?php echo $var['photo']; ?>">
                                                <input type="hidden" name="variations[<?php echo $index; ?>][id]" value="<?php echo $var['id'] ?? ''; ?>">
                                            </label>
                                        </div>

                                        <div class="flex-1 flex flex-col gap-3">
                                            <div class="grid grid-cols-2 gap-3">
                                                <div>
                                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Color</label>
                                                    <input type="text" name="variations[<?php echo $index; ?>][color]" value="<?php echo $var['color']; ?>" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-[#d9822b] outline-none text-sm">
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Size</label>
                                                    <input type="text" name="variations[<?php echo $index; ?>][size]" value="<?php echo $var['size']; ?>" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-[#d9822b] outline-none text-sm">
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Quantity</label>
                                                    <input type="number" min="0" name="variations[<?php echo $index; ?>][quantity]" value="<?php echo $var['quantity']; ?>" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-[#d9822b] outline-none text-sm">
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">CP (Price)</label>
                                                    <input type="number" step="any" min="0" name="variations[<?php echo $index; ?>][cp]" value="<?php echo $var['cp']; ?>" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-[#d9822b] outline-none text-sm">
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-4 gap-2 bg-gray-50 p-2 rounded-lg border border-gray-100">
                                                <div>
                                                    <label class="block text-[9px] font-bold text-gray-400 uppercase mb-1">H (cm)</label>
                                                    <input type="number" step="any" min="0" placeholder="H" name="variations[<?php echo $index; ?>][height]" value="<?php echo $var['height'] ?? ''; ?>" class="w-full border border-gray-300 rounded p-1.5 focus:ring-1 focus:ring-[#d9822b] outline-none text-xs text-center">
                                                </div>
                                                <div>
                                                    <label class="block text-[9px] font-bold text-gray-400 uppercase mb-1">W (cm)</label>
                                                    <input type="number" step="any" min="0" placeholder="W" name="variations[<?php echo $index; ?>][width]" value="<?php echo $var['width'] ?? ''; ?>" class="w-full border border-gray-300 rounded p-1.5 focus:ring-1 focus:ring-[#d9822b] outline-none text-xs text-center">
                                                </div>
                                                <div>
                                                    <label class="block text-[9px] font-bold text-gray-400 uppercase mb-1">D (cm)</label>
                                                    <input type="number" step="any" min="0" placeholder="D" name="variations[<?php echo $index; ?>][depth]" value="<?php echo $var['depth'] ?? ''; ?>" class="w-full border border-gray-300 rounded p-1.5 focus:ring-1 focus:ring-[#d9822b] outline-none text-xs text-center">
                                                </div>
                                                <div>
                                                    <label class="block text-[9px] font-bold text-gray-400 uppercase mb-1">Wt (kg)</label>
                                                    <input type="number" step="any" min="0" placeholder="Kg" name="variations[<?php echo $index; ?>][weight]" value="<?php echo $var['weight'] ?? ''; ?>" class="w-full border border-gray-300 rounded p-1.5 focus:ring-1 focus:ring-[#d9822b] outline-none text-xs text-center">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" id="add-variation-btn" class="w-full py-3 rounded-xl border-2 border-dashed border-[#d9822b] text-[#d9822b] font-bold hover:bg-orange-50 transition flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>
                            Add Variation
                        </button>
                    </div>
                </div>
            </div>

            <div class="p-5 bg-white border-t border-gray-100 mt-auto z-20">
                <button type="submit" class="w-full bg-[#d9822b] hover:bg-[#bf7326] text-white font-bold text-lg py-3.5 rounded-xl shadow-lg transform transition active:scale-[0.99] flex justify-center items-center gap-2">
                    Save & Next <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('variations-container');
        const addBtn = document.getElementById('add-variation-btn');
        
        // Counter logic
        let variationCount = <?php echo count($viewVariations); ?>;

        // 1. ADD VARIATION
        addBtn.addEventListener('click', function() {
            variationCount++;
            const index = variationCount - 1; 

            // Inside addBtn.addEventListener...
            // Inside addBtn.addEventListener ...
            const html = `
                <div class="variation-card bg-white p-4 rounded-xl border border-gray-200 shadow-sm relative transition-all hover:shadow-md animate-fade-in" data-index="${index}">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="font-bold text-gray-700 text-sm">Variation ${variationCount}</h4>
                        <button type="button" class="remove-variation-btn text-red-500 hover:bg-red-50 p-1 rounded transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </div>
                    <div class="flex flex-row gap-4">
                        <div class="w-24 flex-shrink-0 flex flex-col gap-2">
                            <label class="cursor-pointer group relative w-24 h-24 bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center hover:border-[#d9822b] hover:bg-orange-50/20 transition overflow-hidden">
                                <img src="#" class="preview-img hidden w-full h-full object-cover absolute inset-0 z-10">
                                <div class="placeholder-icon flex flex-col items-center justify-center text-gray-400 group-hover:text-[#d9822b] transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    <span class="text-[9px] uppercase font-bold">Photo</span>
                                </div>
                                <input type="file" name="variations[${index}][photo]" accept="image/*" class="hidden variation-file-input">
                                <input type="hidden" name="variations[${index}][old_photo]" value="">
                            </label>
                        </div>
                        
                        <div class="flex-1 flex flex-col gap-3">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Color</label>
                                    <input type="text" name="variations[${index}][color]" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-[#d9822b] outline-none text-sm">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Size</label>
                                    <input type="text" name="variations[${index}][size]" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-[#d9822b] outline-none text-sm">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Quantity</label>
                                    <input type="number" min="0" name="variations[${index}][quantity]" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-[#d9822b] outline-none text-sm">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">CP (Price)</label>
                                    <input type="number" step="any" min="0" name="variations[${index}][cp]" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-[#d9822b] outline-none text-sm">
                                </div>
                            </div>

                            <div class="grid grid-cols-4 gap-2 bg-gray-50 p-2 rounded-lg border border-gray-100">
                                <div>
                                    <label class="block text-[9px] font-bold text-gray-400 uppercase mb-1">H (cm)</label>
                                    <input type="number" step="any" min="0" placeholder="H" name="variations[${index}][height]" class="w-full border border-gray-300 rounded p-1.5 focus:ring-1 focus:ring-[#d9822b] outline-none text-xs text-center">
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold text-gray-400 uppercase mb-1">W (cm)</label>
                                    <input type="number" step="any" min="0" placeholder="W" name="variations[${index}][width]" class="w-full border border-gray-300 rounded p-1.5 focus:ring-1 focus:ring-[#d9822b] outline-none text-xs text-center">
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold text-gray-400 uppercase mb-1">D (cm)</label>
                                    <input type="number" step="any" min="0" placeholder="D" name="variations[${index}][depth]" class="w-full border border-gray-300 rounded p-1.5 focus:ring-1 focus:ring-[#d9822b] outline-none text-xs text-center">
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold text-gray-400 uppercase mb-1">Wt (kg)</label>
                                    <input type="number" step="any" min="0" placeholder="Kg" name="variations[${index}][weight]" class="w-full border border-gray-300 rounded p-1.5 focus:ring-1 focus:ring-[#d9822b] outline-none text-xs text-center">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        });

        // 2. REMOVE VARIATION
        container.addEventListener('click', function(e) {
            if (e.target.closest('.remove-variation-btn')) {
                const card = e.target.closest('.variation-card');
                if(card) card.remove();
            }
        });

        // 3. IMAGE PREVIEW (Handles both Gallery and Camera files)
        container.addEventListener('change', function(e) {
            if (e.target.classList.contains('variation-file-input')) {
                const file = e.target.files[0];
                const label = e.target.closest('label');
                const preview = label.querySelector('.preview-img');
                const placeholder = label.querySelector('.placeholder-icon');

                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        preview.src = evt.target.result;
                        preview.classList.remove('hidden');
                        placeholder.classList.add('hidden');
                    }
                    reader.readAsDataURL(file);
                }
            }
        });
    });

    // Navigation Scripts
    var id = <?php echo json_encode($record_id); ?>;
    document.getElementById("back-btn").addEventListener("click", function () {
        window.location.href = window.location.origin + "/index.php?page=inbounding&action=form2&id=" + id;
    });
    document.getElementById("cancel-btn").addEventListener("click", function () {
        window.location.href = window.location.origin + "/index.php?page=inbounding&action=list";
    });
</script>

<div id="imagePopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center z-50" onclick="closeImagePopup(event)">
    <div class="bg-white p-4 rounded-md max-w-3xl max-h-3xl relative flex flex-col items-center" onclick="event.stopPropagation();">
        <button onclick="closeImagePopup()" class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-full text-sm">✕</button>
        <img id="popupImage" class="max-w-full max-h-[80vh] rounded" src="" alt="Image Preview">
    </div>
</div>
<script>
    function openImagePopup(imageUrl) {
        popupImage.src = imageUrl;
        document.getElementById('imagePopup').classList.remove('hidden');
    }
    function closeImagePopup(event) {
        document.getElementById('imagePopup').classList.add('hidden');
        document.getElementById('popupImage').src = '';
    } 
</script>