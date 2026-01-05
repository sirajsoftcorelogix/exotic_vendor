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
$form2 = $data['form2'] ?? []; 
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

// PROCESS CATEGORIES
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

// Prepare Warehouse Options
$warehouseOptions = "";
if (!empty($data['address'])) {
    foreach ($data['address'] as $va) {
        $warehouseOptions .= '<option value="'.$va['id'].'">'.htmlspecialchars($va['address_title']).'</option>';
    }
}

// --- 1. DEFINE SIZE OPTIONS ---
$sizeOptions = [
    'XS'   => 'Extra Small (XS)(34)',
    'S'    => 'Small (S)(36)',
    'M'    => 'Medium (M)(38)',
    'L'    => 'Large (L)(40)',
    'XL'   => 'Extra Large (XL)(42)',
    'XXL'  => 'Extra Extra Large (XXL)(44)',
    'XXXL' => 'Extra Extra Extra Large (XXXL)(46)',
];
$colorMapData = $data['form2']['gecolormaps']['colormaps'] ?? [];


function renderColorMapField($index, $value) {
    $fieldName = "variations[$index][colormaps]";
    
    // Clean the value to ensure valid HTML attribute
    $cleanValue = htmlspecialchars(trim((string)$value));

    return '
    <div class="colormap-wrapper mt-2" style="display:none;">
        <label class="block text-xs font-bold text-black mb-1">Color Map (Dropdown):</label>
        
        <select name="'.$fieldName.'" 
                class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none bg-white colormap-select"
                data-saved-value="'.$cleanValue.'">
            <option value="">Select Color Map</option>
        </select>
        
        </div>';
}
function renderSizeField($index, $value, $categoryCode, $sizeOptions) {
    $fieldName = "variations[$index][size]";
    
    // Normalize: lowercase and trim
    $check = strtolower(trim((string)$categoryCode));
    
    // CHECK: Does it contain 'textiles' OR 'clothing'?
    $isClothing = (strpos($check, 'textiles') !== false || strpos($check, 'clothing') !== false);

    if ($isClothing) {
        // Render Dropdown
        $html = '<select name="'.$fieldName.'" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none bg-white size-input">';
        $html .= '<option value="">Select Size</option>';
        foreach ($sizeOptions as $optVal => $optLabel) {
            // Strict comparison is safer, trim just in case
            $isSelected = (trim((string)$value) === (string)$optVal) ? 'selected' : '';
            $html .= '<option value="'.$optVal.'" '.$isSelected.'>'.$optLabel.'</option>';
        }
        $html .= '</select>';
    } else {
        // Render Disabled Input
        // Note: We keep the value here so we don't lose data if they accidentally saved a size for non-clothing
        $html = '<input type="text" name="'.$fieldName.'" value="'.htmlspecialchars($value).'" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm  cursor-not-allowed size-input" >';
    }
    return $html;
}

// Prepare Data
$mainVar = [
    'color'           => $form2['color'] ?? '',
    'size'            => $form2['size'] ?? '',
    'quantity'        => $form2['quantity_received'] ?? '1',
    'cp'              => $form2['cp'] ?? '',
    'photo'           => $form2['product_photo'] ?? '',
    'invoice'         => $form2['invoice_image'] ?? '',
    'height'          => $form2['height'] ?? '',
    'width'           => $form2['width'] ?? '',
    'depth'           => $form2['depth'] ?? '',
    'weight'          => $form2['weight'] ?? '',
    'store_location'  => $form2['store_location'] ?? '',
    'colormaps'         => $form2['colormaps'] ?? '',
];

global $inboundingModel;
$extraVars = $inboundingModel->getVariations($record_id);

$viewVariations = [];
$viewVariations[] = $mainVar; 

foreach ($extraVars as $ex) {
    $viewVariations[] = [
        'id'              => $ex['id'],
        'color'           => $ex['color'],
        'size'            => $ex['size'],
        'quantity'        => $ex['quantity_received'] ?? '1',
        'cp'              => $ex['cp'],
        'photo'           => $ex['variation_image'] ?? '',
        'height'          => $ex['height'] ?? '',
        'width'           => $ex['width'] ?? '',
        'depth'           => $ex['depth'] ?? '',
        'weight'          => $ex['weight'] ?? '',
        'store_location'  => $ex['store_location'] ?? '',
        'colormaps'  => $ex['colormaps'] ?? '',
    ];
}

$temp_code      = $form2['temp_code'] ?? '';
$vendor_name    = $form2['vendor_name'] ?? '';
$gate_entry_date_time = $form2['gate_entry_date_time'] ?? '';
$material_code        = $form2['material_code'] ?? '';

$formAction = base_url('?page=inbounding&action=submitStep3');
?>

<div class="w-full min-h-screen bg-white p-2 md:p-6 font-sans">
    <div class="max-w-[1400px] mx-auto border border-gray-400 rounded-lg overflow-hidden">
        
        <div class="bg-[#ea8c1e] px-4 py-3 flex items-center justify-between text-white relative">
            <button type="button" id="back-btn" class="p-1 hover:bg-white/20 rounded-full transition"><i class="fa-solid fa-arrow-left text-xl"></i></button>
            <h1 class="font-bold text-lg md:text-xl tracking-wide mx-auto">Item Details - Step 3/4</h1>
            <button type="button" id="cancel-btn" class="p-1 hover:bg-white/20 rounded-full transition"><i class="fa-solid fa-xmark text-xl"></i></button> 
        </div>

        <form action="<?php echo $formAction; ?>" method="POST" enctype="multipart/form-data" id="mainForm" class="p-4 space-y-6">
            <input type="hidden" name="record_id" value="<?php echo $record_id; ?>">
            <input type="hidden" name="userid_log" value="<?php echo $_SESSION['user']['id'] ?? ''; ?>">
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                
                <div class="flex flex-row bg-gray-100 border border-gray-300 rounded p-2 gap-3 min-w-[350px] lg:w-1/3">
                    <div class="w-24 h-28 bg-white border border-gray-300 flex-shrink-0 cursor-pointer overflow-hidden" onclick="openImagePopup('<?= !empty($mainVar['invoice']) ? base_url($mainVar['invoice']) : '' ?>')">
                        <?php if(!empty($mainVar['invoice'])): ?>
                            <img src="<?php echo base_url($mainVar['invoice']); ?>" class="w-full h-full object-cover hover:opacity-90">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-400 text-xs text-center">No Inv</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex flex-col justify-center text-sm text-black space-y-1.5 w-full">
                        <div class="font-bold">Vendor: <span class="font-normal"><?php echo htmlspecialchars($vendor_name); ?></span></div>
                        <div class="font-bold">Invoice Date: <span class="font-normal">11 Dec 2025</span></div> <div class="font-bold">Entry: <span class="font-normal"><?php echo date('d M Y', strtotime($gate_entry_date_time ?: 'now')); ?></span></div>
                        <div class="font-bold">Received By: <span class="font-normal"><?php echo htmlspecialchars($currentuserDetails['name']); ?></span></div>
                    </div>
                </div>

                <div class="flex-1 flex flex-wrap gap-2">
                    <?php if(!empty($display_categories)): ?>
                        <?php foreach ($display_categories as $item) { ?>
                            <label class="cursor-pointer group relative">
                                <input type="radio" name="category" value="<?= $item['value'] ?>" class="peer sr-only category-radio" <?php if ($saved_category_code == $item['value']) echo 'checked'; ?>>
                                
                                <div class="w-24 h-28 bg-black text-white flex flex-col items-center justify-center p-2 rounded transition-all
                                              peer-checked:bg-gray-300 peer-checked:text-black border border-black shadow-sm">
                                    <div class="absolute top-2 left-2 w-3.5 h-3.5 rounded-full border-2 border-white peer-checked:border-black flex items-center justify-center">
                                         <div class="w-1.5 h-1.5 rounded-full bg-white peer-checked:bg-black opacity-0 peer-checked:opacity-100"></div>
                                    </div>
                                    
                                    <i class="<?= $item['icon'] ?> text-3xl mb-2"></i>
                                    <span class="text-[10px] font-bold uppercase text-center"><?= $item['label'] ?></span>
                                </div>
                            </label>
                        <?php } ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 border-t border-b border-gray-200 py-4">
                <div>
                    <label class="block text-gray-800 font-bold text-xs mb-1">Gate Entry Date</label>
                    <?php 
                    $inputValue = (!empty($gate_entry_date_time) && $gate_entry_date_time != "0000-00-00 00:00:00") 
                    ? date('Y-m-d\TH:i', strtotime($gate_entry_date_time)) 
                    : date('Y-m-d\TH:i');
                    ?>
                    <input type="datetime-local" name="gate_entry_date_time" value="<?php echo $inputValue; ?>" class="w-full border border-gray-400 rounded px-2 py-1 text-sm focus:border-[#ea8c1e] outline-none">
                </div>
                <div>
                    <label class="block text-gray-800 font-bold text-xs mb-1">Material</label>
                    <select name="material_code" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-[#ea8c1e] outline-none bg-white">
                        <option value="">Select Material</option>
                        <?php foreach ($data['material'] as $value2) { 
                            $isSelected = ($material_code == $value2['id']) ? 'selected' : '';
                        ?> 
                        <option <?php echo $isSelected; ?> value="<?php echo $value2['id']; ?>"> <?php echo $value2['material_name']; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-800 font-bold text-xs mb-1">Received By</label>
                    <input type="hidden" name="received_by_user_id" value="<?php echo $_SESSION['user']['id']; ?>">
                    <input type="text" name="received_by_name" value="<?php echo htmlspecialchars($currentuserDetails['name']); ?>"  class="w-full bg-gray-100 border border-gray-400 rounded px-2 py-1 text-sm text-gray-600 cursor-not-allowed">
                </div>
            </div>
            
            <div id="variations-container" class="space-y-6">
                <?php foreach($viewVariations as $index => $var): ?>
                    <div class="variation-card border border-gray-300 rounded-lg p-3 bg-gray-50/50" data-index="<?php echo $index; ?>">
                        
                        <div class="flex justify-between items-center border-b border-gray-300 pb-2 mb-4">
                            <h3 class="font-bold text-black text-sm">
                                <?php echo ($index === 0) ? 'Main Variant' : 'Variant ' . ($index + 1); ?>
                            </h3>
                            <div class="flex gap-3">
                                <button type="button" class="clone-variation-btn text-blue-600 hover:text-blue-800 font-bold text-xs uppercase flex items-center gap-1">
                                    <i class="fa-regular fa-copy"></i> Clone
                                </button>
                                
                                <button type="button" class="remove-variation-btn <?php echo ($index === 0) ? 'hidden' : ''; ?> text-red-500 hover:text-red-700 font-bold text-xs uppercase">
                                    Remove ✕
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-col md:flex-row gap-6">
                            <div class="w-32 md:w-40 flex-shrink-0">
                                <label class="cursor-pointer group relative block w-full aspect-square bg-white border border-gray-400 rounded flex items-center justify-center hover:border-black overflow-hidden">
                                    <?php $hasPhoto = !empty($var['photo']); ?>
                                    <img src="<?php echo $hasPhoto ? base_url($var['photo']) : '#'; ?>" class="preview-img <?php echo $hasPhoto ? '' : 'hidden'; ?> w-full h-full object-cover absolute inset-0 z-10">
                                    
                                    <div class="placeholder-icon <?php echo $hasPhoto ? 'hidden' : ''; ?> flex flex-col items-center justify-center text-gray-500">
                                        <i class="fa-solid fa-camera text-2xl mb-1"></i>
                                    </div>
                                    <input type="file" name="variations[<?php echo $index; ?>][photo]" accept="image/*" class="hidden variation-file-input" capture="camera">
                                    <input type="hidden" name="variations[<?php echo $index; ?>][old_photo]" value="<?php echo $var['photo']; ?>">
                                    <input type="hidden" name="variations[<?php echo $index; ?>][id]" value="<?php echo $var['id'] ?? ''; ?>">
                                </label>
                                <div class="text-[10px] text-center font-bold mt-1 text-gray-500">Upload Photo</div>
                            </div>

                            <div class="flex-1 grid grid-cols-2 md:grid-cols-6 gap-x-4 gap-y-4 items-start">
                                <div>
                                    <label class="block text-xs font-bold text-black mb-1">Color:</label>
                                    <input type="text" name="variations[<?php echo $index; ?>][color]" value="<?php echo $var['color']; ?>" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-black mb-1">Quantity:</label>
                                    <input type="number" min="0" 
                                       name="variations[<?php echo $index; ?>][quantity]" 
                                       value="<?php echo (isset($var['quantity']) && $var['quantity'] !== '') ? $var['quantity'] : 1; ?>" 
                                       class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none">
                                </div>
                                
                                <div class="size-container">
                                    <label class="block text-xs font-bold text-black mb-1">Size:</label>
                                    <?php echo renderSizeField($index, $var['size'], $saved_category_code, $sizeOptions); ?>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-black mb-1">Cost Price(INR):</label>
                                    <input type="number" step="any" min="0" name="variations[<?php echo $index; ?>][cp]" value="<?php echo $var['cp']; ?>" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-black mb-1">Height (inch):</label>
                                    <input type="number" step="any" min="0" name="variations[<?php echo $index; ?>][height]" value="<?php echo $var['height'] ?? ''; ?>" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-black mb-1">Width (inch):</label>
                                    <input type="number" step="any" min="0" name="variations[<?php echo $index; ?>][width]" value="<?php echo $var['width'] ?? ''; ?>" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-black mb-1">Depth (inch):</label>
                                    <input type="number" step="any" min="0" name="variations[<?php echo $index; ?>][depth]" value="<?php echo $var['depth'] ?? ''; ?>" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-black mb-1">Weight (kg):</label>
                                    <input type="number" step="any" min="0" name="variations[<?php echo $index; ?>][weight]" value="<?php echo $var['weight'] ?? ''; ?>" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-black mb-1">Location:</label>
                                    <input type="text" name="variations[<?php echo $index; ?>][store_location]" value="<?php echo $var['store_location'] ?? ''; ?>" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none">
                                </div>
                                <!-- <div>
                                    <label class="block text-xs font-bold text-black mb-1">Coloraps:</label>
                                    <input type="text" name="variations[<?php echo $index; ?>][colormaps]" value="<?php echo $var['colormaps']; ?>" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none">
                                </div> -->

                                <div>
                                    <?php echo renderColorMapField($index, $var['colormaps'] ?? ''); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                 <button type="button" id="add-variation-btn" class="bg-[#ea8c1e] hover:bg-orange-600 text-white font-bold py-2 px-6 rounded shadow text-xs uppercase">
                    Add Variant
                </button>
                <button type="submit" class="bg-[#ea8c1e] hover:bg-orange-600 text-white font-bold py-2 px-6 rounded shadow text-xs uppercase">
                    Save Item
                </button>
            </div>
        </form>
    </div>
</div>

<div id="imagePopup" class="fixed inset-0 bg-black bg-opacity-70 hidden flex justify-center items-center z-50" onclick="closeImagePopup(event)">
    <div class="bg-white p-2 rounded-md max-w-4xl max-h-[90vh] relative flex flex-col items-center" onclick="event.stopPropagation();">
        <button onclick="closeImagePopup()" class="absolute -top-3 -right-3 bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center shadow-lg font-bold">✕</button>
        <img id="popupImage" class="max-w-full max-h-[85vh] rounded" src="" alt="Image Preview">
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('variations-container');
        const addBtn = document.getElementById('add-variation-btn');
        const radioButtons = document.querySelectorAll('.category-radio');
        const mainForm = document.getElementById('mainForm'); 
        let variationCount = <?php echo count($viewVariations); ?>;
        
        // 1. DATA FROM PHP
        const colorMapDB = <?php echo json_encode($colorMapData); ?>; 
        
        // Size Options
        const sizeOptionsHTML = `
            <option value="">Select Size</option>
            <?php foreach ($sizeOptions as $k => $v) { echo '<option value="'.$k.'">'.$v.'</option>'; } ?>
        `;

        // 2. HELPER: Detect Category Type
        function getCategoryType() {
            const selectedRadio = document.querySelector('input[name="category"]:checked');
            let info = { isClothing: false, mapKey: null };

            if (selectedRadio) {
                const parentLabel = selectedRadio.closest('label');
                const labelText = parentLabel ? parentLabel.innerText.toLowerCase().trim() : '';
                const val = selectedRadio.value.toLowerCase().trim();

                // Clothing Check
                if (labelText.includes('textile') || labelText.includes('clothing') || val.includes('textile') || val.includes('clothing')) {
                    info.isClothing = true;
                }

                // Map Key Check
                if (labelText.includes('textile') || labelText.includes('clothing') || val.includes('textile') || val.includes('clothing')) {
                    info.mapKey = 'textiles'; 
                } 
                else if (labelText.includes('jewelry') || labelText.includes('jewellery') || val.includes('jewelry') || val.includes('jewellery')) {
                    info.mapKey = 'jewelry'; 
                }
            }
            return info;
        }

        // 3. TOGGLE SIZE FIELDS
        function toggleSizeFields() {
            const { isClothing } = getCategoryType();
            const cards = document.querySelectorAll('.variation-card');
            
            cards.forEach(card => {
                const index = card.getAttribute('data-index');
                const sizeContainer = card.querySelector('.size-container');
                const existingInput = sizeContainer.querySelector('.size-input');
                
                if (existingInput) {
                    const currentTag = existingInput.tagName;
                    if (isClothing && currentTag === 'SELECT') return;
                    if (!isClothing && currentTag === 'INPUT') return;
                }

                const currentValue = existingInput ? existingInput.value : '';
                if(existingInput) existingInput.remove();

                let newField;
                if (isClothing) {
                    newField = document.createElement('select');
                    newField.innerHTML = sizeOptionsHTML;
                    newField.value = currentValue; 
                } else {
                    newField = document.createElement('input');
                    newField.type = 'text';
                    newField.value = ''; 
                }

                newField.name = `variations[${index}][size]`;
                newField.classList.add('w-full', 'border', 'border-gray-400', 'rounded', 'px-2', 'py-1.5', 'text-sm', 'focus:border-black', 'outline-none', 'size-input');
                sizeContainer.appendChild(newField);
            });
        }

        // 4. TOGGLE COLOR MAP FIELDS (Logic Fixed for Selection)
        function toggleColorMapFields() {
            const { mapKey } = getCategoryType();
            const wrappers = document.querySelectorAll('.colormap-wrapper');

            wrappers.forEach(wrapper => {
                const select = wrapper.querySelector('.colormap-select');
                
                // Hide if no match
                if (!mapKey || !colorMapDB[mapKey]) {
                    wrapper.style.display = 'none';
                    return;
                }

                wrapper.style.display = 'block';

                // Populate Options (Only if key changed to avoid reset)
                if (select.getAttribute('data-loaded-key') !== mapKey) {
                    
                    // Get saved value from Attribute (Database) OR current Value (Cloned/Edited)
                    const savedVal = (select.getAttribute('data-saved-value') || select.value || "").trim();
                    
                    let html = '<option value="">Select Color Map</option>';
                    
                    colorMapDB[mapKey].forEach(colorName => {
                        html += `<option value="${colorName}">${colorName}</option>`;
                    });

                    select.innerHTML = html;
                    select.setAttribute('data-loaded-key', mapKey);

                    // --- FORCE SELECTION LOGIC ---
                    if (savedVal) {
                        select.value = savedVal; // Try direct set
                        
                        // Fallback: Loose comparison logic
                        if (select.selectedIndex <= 0) {
                            Array.from(select.options).forEach(opt => {
                                if (opt.value.toLowerCase() === savedVal.toLowerCase()) {
                                    opt.selected = true;
                                }
                            });
                        }
                    }
                }
            });
        }

        function updateAllFields() {
            toggleSizeFields();
            toggleColorMapFields();
        }

        radioButtons.forEach(radio => {
            radio.addEventListener('change', updateAllFields);
        });

        // 5. ADD NEW VARIATION
        function createVariationCardHTML(index, count) {
            const { isClothing } = getCategoryType();

            let sizeFieldHTML;
            if (isClothing) {
                sizeFieldHTML = `<select name="variations[${index}][size]" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none bg-white size-input">${sizeOptionsHTML}</select>`;
            } else {
                sizeFieldHTML = `<input type="text" name="variations[${index}][size]" value="" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm size-input" >`;
            }

            return `
                <div class="variation-card border border-gray-300 rounded-lg p-3 bg-gray-50/50 animate-fade-in" data-index="${index}">
                    <div class="flex justify-between items-center border-b border-gray-300 pb-2 mb-4">
                        <h3 class="font-bold text-black text-sm">Variant ${count}</h3>
                        <div class="flex gap-3">
                            <button type="button" class="clone-variation-btn text-blue-600 hover:text-blue-800 font-bold text-xs uppercase flex items-center gap-1">Clone</button>
                            <button type="button" class="remove-variation-btn text-red-500 hover:text-red-700 font-bold text-xs uppercase">Remove ✕</button>
                        </div>
                    </div>

                    <div class="flex flex-col md:flex-row gap-6">
                        <div class="w-32 md:w-40 flex-shrink-0">
                            <label class="cursor-pointer group relative block w-full aspect-square bg-white border border-gray-400 rounded flex items-center justify-center hover:border-black overflow-hidden">
                                <img src="#" class="preview-img hidden w-full h-full object-cover absolute inset-0 z-10">
                                <div class="placeholder-icon flex flex-col items-center justify-center text-gray-500"><i class="fa-solid fa-camera text-2xl mb-1"></i></div>
                                <input type="file" name="variations[${index}][photo]" accept="image/*" class="hidden variation-file-input" capture="camera">
                                <input type="hidden" name="variations[${index}][old_photo]" value="">
                            </label>
                            <div class="text-[10px] text-center font-bold mt-1 text-gray-500">Upload Photo</div>
                        </div>

                        <div class="flex-1 grid grid-cols-2 md:grid-cols-6 gap-x-4 gap-y-4 items-start">
                            <div><label class="block text-xs font-bold text-black mb-1">Color (Manual):</label><input type="text" name="variations[${index}][color]" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none"></div>
                            
                            <div class="colormap-wrapper mt-2" style="display:none;">
                                <label class="block text-xs font-bold text-black mb-1">Color Map (Dropdown):</label>
                                <select name="variations[${index}][colormaps]" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none bg-white colormap-select" data-saved-value="">
                                    <option value="">Select Color Map</option>
                                </select>
                            </div>

                            <div><label class="block text-xs font-bold text-black mb-1">Quantity <span class="text-red-500">*</span>:</label><input type="number" min="0" name="variations[${index}][quantity]" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none required-field"></div>
                            
                            <div class="size-container">
                                <label class="block text-xs font-bold text-black mb-1">Size:</label>
                                ${sizeFieldHTML}
                            </div>

                            <div><label class="block text-xs font-bold text-black mb-1">Cost Price(INR) <span class="text-red-500">*</span>:</label><input type="number" step="any" min="0" name="variations[${index}][cp]" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none required-field"></div>
                            <div><label class="block text-xs font-bold text-black mb-1">Height (inch):</label><input type="number" step="any" min="0" name="variations[${index}][height]" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none"></div>
                            <div><label class="block text-xs font-bold text-black mb-1">Width (inch):</label><input type="number" step="any" min="0" name="variations[${index}][width]" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none"></div>
                            <div><label class="block text-xs font-bold text-black mb-1">Depth (inch):</label><input type="number" step="any" min="0" name="variations[${index}][depth]" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none"></div>
                            <div><label class="block text-xs font-bold text-black mb-1">Weight (kg):</label><input type="number" step="any" min="0" name="variations[${index}][weight]" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none"></div>
                            <div><label class="block text-xs font-bold text-black mb-1">Location <span class="text-red-500">*</span>:</label><input type="text" name="variations[${index}][store_location]" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none required-field"></div>
                        </div>
                    </div>
                </div>
            `;
        }

        addBtn.addEventListener('click', function() {
            variationCount++;
            const html = createVariationCardHTML(variationCount - 1, variationCount);
            container.insertAdjacentHTML('beforeend', html);
            setTimeout(updateAllFields, 50);
        });

        // 6. EVENT DELEGATION
        container.addEventListener('click', function(e) {
            if (e.target.closest('.remove-variation-btn')) {
                const card = e.target.closest('.variation-card');
                if(card) card.remove();
            }

            if (e.target.closest('.clone-variation-btn')) {
                const sourceCard = e.target.closest('.variation-card');
                
                const getData = (name) => {
                    const el = sourceCard.querySelector(`[name*="[${name}]"]`);
                    return el ? el.value : '';
                };

                const data = {
                    color: getData('color'),
                    colormaps: getData('colormaps'),
                    quantity: getData('quantity') || 1,
                    size: getData('size'),
                    cp: getData('cp'),
                    height: getData('height'),
                    width: getData('width'),
                    depth: getData('depth'),
                    weight: getData('weight'),
                    store_location: getData('store_location'),
                    old_photo: getData('old_photo') 
                };

                variationCount++;
                const html = createVariationCardHTML(variationCount - 1, variationCount);
                container.insertAdjacentHTML('beforeend', html);

                const newCard = container.lastElementChild;
                
                // IMPORTANT: Populate options THEN select value
                setTimeout(() => {
                    updateAllFields(); 

                    const setData = (name, value) => {
                        const el = newCard.querySelector(`[name*="[${name}]"]`);
                        if(el) el.value = value;
                    };

                    setData('color', data.color);
                    setData('quantity', data.quantity || 1); 
                    setData('size', data.size);
                    setData('cp', data.cp);
                    setData('height', data.height);
                    setData('width', data.width);
                    setData('depth', data.depth);
                    setData('weight', data.weight);
                    setData('store_location', data.store_location);
                    setData('old_photo', data.old_photo); 
                    
                    // --- SPECIFIC CLONE LOGIC FOR COLOR MAP ---
                    const cmapSelect = newCard.querySelector('.colormap-select');
                    if(cmapSelect) {
                        cmapSelect.setAttribute('data-saved-value', data.colormaps); // Set attribute for consistency
                        cmapSelect.value = data.colormaps; // Set visual value
                    }

                    const sourceImg = sourceCard.querySelector('.preview-img');
                    const newImg = newCard.querySelector('.preview-img');
                    const newPlaceholder = newCard.querySelector('.placeholder-icon');

                    if (sourceImg && !sourceImg.classList.contains('hidden') && sourceImg.getAttribute('src') !== '#') {
                        newImg.src = sourceImg.src;            
                        newImg.classList.remove('hidden');    
                        newPlaceholder.classList.add('hidden'); 
                    }
                }, 50);
            }
        });
        container.addEventListener('change', function(e) {
            if (e.target.classList.contains('variation-file-input')) {
                const input = e.target;
                const file = input.files[0];
                const label = input.closest('label'); // The parent wrapper
                const previewImg = label.querySelector('.preview-img');
                const placeholder = label.querySelector('.placeholder-icon');

                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        previewImg.classList.remove('hidden');
                        placeholder.classList.add('hidden');
                    }
                    reader.readAsDataURL(file);
                }
            }
        });
        // 7. VALIDATION
        mainForm.addEventListener('submit', function(e) {
            let isValid = true;
            let firstErrorElement = null;

            // Check Category Selection
            const category = document.querySelector('input[name="category"]:checked');
            if (!category) {
                alert("Please select a Category from the top list.");
                e.preventDefault();
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }

            // Check Variation Cards
            const variationCards = document.querySelectorAll('.variation-card');
            variationCards.forEach((card) => {
                const qtyInput = card.querySelector('input[name*="[quantity]"]');
                
                // Helper functions
                const setError = (input) => {
                    input.classList.remove('border-gray-400');
                    input.classList.add('border-red-500', 'bg-red-50');
                    isValid = false;
                    if(!firstErrorElement) firstErrorElement = input;
                };
                const clearError = (input) => {
                    input.classList.remove('border-red-500', 'bg-red-50');
                    input.classList.add('border-gray-400');
                }

                // 1. VALIDATE QUANTITY (Keep this)
                if (!qtyInput.value || qtyInput.value <= 0) {
                    setError(qtyInput); 
                } else {
                    clearError(qtyInput);
                }

                // 2. VALIDATE COST PRICE (Removed)
                // if (!cpInput.value || cpInput.value < 0) setError(cpInput); else clearError(cpInput);

                // 3. VALIDATE LOCATION (Removed)
                // if (!locInput.value.trim()) setError(locInput); else clearError(locInput);
            });

            if (!isValid) {
                e.preventDefault();
                alert("Please fill in the required Quantity.");
                if(firstErrorElement) {
                    firstErrorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstErrorElement.focus();
                }
            }
        });

        // Initial Run
        updateAllFields();
    });

    // Navigation & Popups
    var id = <?php echo json_encode($record_id); ?>;
    document.getElementById("back-btn").addEventListener("click", function () {
        window.location.href = window.location.origin + "/index.php?page=inbounding&action=form2&id=" + id;
    });
    document.getElementById("cancel-btn").addEventListener("click", function () {
        window.location.href = window.location.origin + "/index.php?page=inbounding&action=list";
    });
    
    function openImagePopup(imageUrl) {
        if(!imageUrl) return;
        document.getElementById('popupImage').src = imageUrl;
        document.getElementById('imagePopup').classList.remove('hidden');
    }
    function closeImagePopup(event) {
        document.getElementById('imagePopup').classList.add('hidden');
        document.getElementById('popupImage').src = '';
    } 
</script>