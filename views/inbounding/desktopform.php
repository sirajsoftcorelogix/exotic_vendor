<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.default.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<style>
    .draggable-item {
        cursor: grab;
        user-select: none;
    }
    .draggable-item:active {
        cursor: grabbing;
    }
    .custom-scrollbar::-webkit-scrollbar { height: 14px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #e0e0e0; border: 1px solid #ccc; border-radius: 2px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #666; border: 2px solid #e0e0e0; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #555; }
    
    /* Tom Select Customization */
    .ts-wrapper.single .ts-control {
        background: #fff !important;
        border: 1px solid #ccc !important;
        border-radius: 4px !important;
        height: 36px !important;
        padding: 0 8px !important;
        display: flex;
        align-items: center;
        font-size: 13px;
        color: #333;
        box-shadow: none !important;
        background-image: none !important;
    }
    .ts-wrapper.focus .ts-control {
        border-color: #999 !important;
    }
    .ts-dropdown {
        border: 1px solid #ccc;
        border-radius: 0 0 4px 4px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 50; /* Ensure dropdown appears above grid items */
    }

    /* Checkbox List Styles */
    .checkbox-list-container::-webkit-scrollbar { width: 8px; }
    .checkbox-list-container::-webkit-scrollbar-track { background: #f1f1f1; }
    .checkbox-list-container::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }
    .checkbox-list-container::-webkit-scrollbar-thumb:hover { background: #999; }
    .checkbox-item {
        display: flex;
        align-items: center;
        padding: 6px 8px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
    }
    .checkbox-item:hover { background-color: #f9f9f9; }
    .checkbox-item:last-child { border-bottom: none; }
    .checkbox-item input[type="checkbox"] { width: 16px; height: 16px; margin-right: 10px; accent-color: #666; }
    .checkbox-item label { font-size: 13px; color: #333; cursor: pointer; flex-grow: 1; }
    
    /* Dimension Input Style */
    .dim-input { transition: border-color 0.2s; }
    .dim-input:focus { border-color: #d97824 !important; }
</style>

<?php
$record_id = $_GET['id'] ?? '';

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

// --- 2. DETECT CLOTHING/TEXTILES (Corrected for ID lookup) ---
$is_clothing_initial = false; 
$saved_group_id = $data['form2']['group_name'] ?? '';

if (!empty($data['category']) && !empty($saved_group_id)) {
    foreach ($data['category'] as $cat) {
        // Check if this category's ID matches the saved group ID
        // We use loose comparison (==) to handle string "-5" vs integer -5
        if (isset($cat['category']) && $cat['category'] == $saved_group_id) {
            
            // We found the matching group, now check its name
            $catName = strtolower($cat['name'] ?? '');
            $catDisplay = strtolower($cat['display_name'] ?? '');
            
            if (strpos($catName, 'clothing') !== false || strpos($catName, 'textiles') !== false || 
                strpos($catDisplay, 'clothing') !== false || strpos($catDisplay, 'textiles') !== false) {
                $is_clothing_initial = true;
            }
            break; // Stop looping once found
        }
    }
}

// --- 3. HELPER FUNCTION TO RENDER FIELD ---
function renderSizeField($fieldName, $currentValue, $isClothing, $options, $customClass = "") {
    $html = '';
    
    // Check if Clothing (TRUE) -> Render Dropdown
    if ($isClothing) {
        $html .= '<select name="' . $fieldName . '" class="size-input-field ' . $customClass . ' w-full h-10 border border-[#ccc] rounded-[3px] px-3 text-[13px] text-[#333] bg-white focus:outline-none focus:border-[#d97824]">';
        $html .= '<option value="">Select Size</option>';
        foreach ($options as $k => $v) {
            // Strict string comparison for selected state
            $selected = ((string)$currentValue === (string)$k) ? 'selected' : '';
            $html .= '<option value="' . $k . '" ' . $selected . '>' . $v . '</option>';
        }
        $html .= '</select>';
    } 
    // Check if Not Clothing (FALSE) -> Render Text Input
    else {
        $html .= '<input type="text" name="' . $fieldName . '" value="' . htmlspecialchars($currentValue) . '" class="size-input-field ' . $customClass . ' w-full h-10 border border-[#ccc] rounded-[3px] px-3 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]">';
    }
    return $html;
}

// Get the current saved value safely
$currentSize = $data['form2']['size'] ?? '';
?>

<div class="w-full max-w-[1200px] mx-auto p-2 md:p-5 font-['Segoe_UI',Tahoma,Geneva,Verdana,sans-serif] text-[#333]">
    <form action="<?php echo base_url('?page=inbounding&action=updatedesktopform&id='.$record_id); ?>" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="userid_log" value="<?php echo $_SESSION['user']['id'] ?? ''; ?>">
        <div class="flex flex-col md:flex-row items-stretch w-full gap-4 md:gap-0">
            
            <div class="shrink-0 w-full md:w-[150px] bg-[#f4f4f4] border border-[#777] rounded-md p-1 md:ml-5 relative h-[200px] md:h-[200px] group">
    
    <div class="w-full h-full relative flex items-center justify-center bg-white rounded-[3px] overflow-hidden">
        <?php 
            $mainPhoto = $data['form2']['product_photo'] ?? ''; 
            $hasMainPhoto = !empty($mainPhoto);
        ?>
        
        <img id="main_photo_preview" 
             src="<?= $hasMainPhoto ? base_url($mainPhoto) : '' ?>" 
             onclick="openImagePopup(this.src)"
             class="w-full h-full object-contain cursor-zoom-in absolute inset-0 z-10"
             style="<?= $hasMainPhoto ? '' : 'display: none;' ?>">
             
        <div id="main_photo_placeholder"
             class="flex flex-col items-center justify-center text-gray-400 cursor-pointer w-full h-full absolute inset-0 z-20 bg-gray-50 hover:bg-gray-100 transition-colors"
             onclick="document.getElementById('product_photo_main_input').click()"
             style="<?= $hasMainPhoto ? 'display: none;' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
            <span class="text-xs font-bold mt-1">Add Photo</span>
        </div>

        <button type="button"
                id="main_photo_change_btn"
                onclick="document.getElementById('product_photo_main_input').click()"
                class="absolute bottom-0 right-0 bg-black bg-opacity-60 text-white p-2 rounded-tl-md z-30 hover:bg-[#d97824] transition-colors"
                style="<?= $hasMainPhoto ? '' : 'display: none;' ?>"
                title="Change Photo">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
        </button>

        <input type="file" name="product_photo_main" id="product_photo_main_input" class="hidden" accept="image/*">
        <input type="hidden" name="old_product_photo_main" value="<?= htmlspecialchars($mainPhoto) ?>">
    </div>
    
</div>

            <fieldset class="grow border border-[#ccc] rounded-[5px] px-3 md:px-5 pt-[15px] pb-5 bg-white md:ml-2.5 md:mr-5">
                <legend class="text-sm font-bold text-[#333] px-[5px]">Item Linking</legend>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 md:gap-[30px] mb-[15px] items-end">
                    <div class="flex flex-col">
                        <label class="text-xs font-bold text-[#333] mb-1.5">Variant:</label>
                        <select id="variant_select" name="is_variant" 
                                class="h-[36px] text-[13px] border border-[#ccc] rounded px-2.5 text-[#333] w-full focus:outline-none focus:border-[#999]">
                            <option value="" disabled <?php echo empty($data['form2']['is_variant']) ? 'selected' : ''; ?>>Select...</option>
                            <option value="N" <?php echo (empty($data['form2']['is_variant']) || $data['form2']['is_variant'] === 'N') ? 'selected' : ''; ?>>No</option>
                            <option value="Y" <?php echo (isset($data['form2']['is_variant']) && $data['form2']['is_variant'] === 'Y') ? 'selected' : ''; ?>>Yes</option>
                        </select>
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-bold text-[#333] mb-1.5">SKU (Auto):</label>
                        <input type="text" 
                               readonly
                               value="<?php echo htmlspecialchars($data['form2']['sku'] ?? ''); ?>"
                               class="h-[36px] text-[13px] border border-[#ccc] rounded px-2.5 text-[#555] w-full bg-gray-200 cursor-not-allowed focus:outline-none" 
                               placeholder="Generated on Save">
                    </div>
                    <div class="flex flex-col sm:col-span-2 md:col-span-1">
                        <label class="text-xs font-bold text-[#333] mb-1.5">Parent Item Code:</label>
                        <input type="hidden" id="original_variant_status" value="<?php echo $data['form2']['is_variant'] ?? ''; ?>">
                        <div id="wrapper_select" style="display:none;" class="w-full">
                            <select id="item_code_select" name="Item_code" placeholder="Type to search title..." class="w-full">
                                <?php 
                                if (isset($data['form2']['is_variant']) && $data['form2']['is_variant'] === 'Y' && !empty($data['form2']['Item_code'])) { 
                                    $code = $data['form2']['Item_code'];
                                    $title = isset($data['form2']['parent_item_title']) ? $data['form2']['parent_item_title'] : $code;
                                ?>
                                    <option value="<?php echo $code; ?>" selected><?php echo $title; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div id="wrapper_input" style="display:none;">
                            <input type="text" 
                                   id="fixed_item_code_input"
                                   value="<?php echo isset($data['form2']['Item_code']) ? $data['form2']['Item_code'] : ''; ?>" 
                                   readonly
                                   class="h-[36px] text-[13px] border border-[#ccc] rounded px-2.5 text-[#333] w-full bg-gray-100 focus:outline-none" 
                                   name="Item_code">      
                            <input type="hidden" id="existing_item_code" value="<?php echo isset($data['form2']['Item_code']) ? $data['form2']['Item_code'] : ''; ?>">
                        </div>
                    </div>
                    <?php if (!empty($data['form2']['stock_added_date']) && $data['form2']['stock_added_date'] != "0000-00-00"){
                    ?>
                    <div class="flex flex-col">
                        <label class="text-xs font-bold text-[#333] mb-1.5">Stock Added On</label>

                        <?php 
                            // 1. Determine the value in standard Y-m-d format first
                            if (!empty($data['form2']['stock_added_date']) && $data['form2']['stock_added_date'] != "0000-00-00") {
                                $dateValue = date('Y-m-d', strtotime($data['form2']['stock_added_date']));
                            } else {
                                $dateValue = date('Y-m-d');
                            }
                        ?>

                        <div class="relative w-full">
                            <input type="text" 
                                   class="date-picker h-[36px] text-[13px] border border-[#ccc] rounded px-2.5 pr-10 text-[#333] w-full focus:outline-none focus:border-[#999] bg-white cursor-pointer" 
                                   value="<?php echo $dateValue; ?>" 
                                   name="stock_added_date">
                            
                            <div class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                    <div class="flex flex-col">
                        <label class="text-xs font-bold text-[#333] mb-1.5">Added On</label>

                        <?php 
                            // 1. Determine the value in standard Y-m-d format first
                            if (!empty($data['form2']['added_date']) && $data['form2']['added_date'] != "0000-00-00") {
                                $dateValue = date('Y-m-d', strtotime($data['form2']['added_date']));
                            } else {
                                $dateValue = date('Y-m-d');
                            }
                        ?>

                        <div class="relative w-full">
                            <input type="text" 
                                   class="date-picker h-[36px] text-[13px] border border-[#ccc] rounded px-2.5 pr-10 text-[#333] w-full focus:outline-none focus:border-[#999] bg-white cursor-pointer" 
                                   value="<?php echo $dateValue; ?>" 
                                   name="added_date">
                            
                            <div class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </fieldset>
        </div>
        <?php 
        $item_photos = $data['images'] ?? [];
        // Ensure variations exist. If not fetched in getform2data, fetch them here:
        if (!isset($data['form2']['variations'])) {
            global $inboundingModel;
            $variations = $inboundingModel->getVariations($record_id);
        } else {
            $variations = $data['form2']['variations'];
        }

        // Initialize Groups
        $grouped_images = ['-1' => []]; // -1 is Base
        foreach ($variations as $var) { $grouped_images[$var['id']] = []; }

        // Sort images into groups
        foreach ($item_photos as $img) {
            $v_id = $img['variation_id'] ?? '-1';
            // Handle null/0 or deleted variations by defaulting to Base (-1)
            if (empty($v_id) || !isset($grouped_images[$v_id])) $v_id = '-1';
            $grouped_images[$v_id][] = $img;
        }
    ?>

    <div class="mt-[15px] md:mx-5">
    <div class="bg-white rounded-[5px] w-full max-w-full">
        <h3 class="text-[13px] font-bold text-[#333] mb-4">Item Photos & Details</h3>

        <div class="mb-8 border-b-2 border-gray-100 pb-6 w-full">
            <div class="flex items-center gap-2 mb-3">
                <span class="bg-[#d97824] text-white text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider">Main</span>
                <h4 class="text-sm font-bold text-[#333] uppercase">
                    (<?= htmlspecialchars($data['form2']['color'] ?? '') ?> - <?= htmlspecialchars($data['form2']['size'] ?? '') ?>)
                </h4>
            </div>

            <div class="w-full mb-5 overflow-hidden">
                <div class="photo-group-grid flex flex-row overflow-x-auto gap-3 min-h-[140px] p-2 border border-dashed border-gray-300 rounded bg-gray-50 custom-scrollbar" data-var-id="-1">
                    <?php 
                    if (!empty($grouped_images['-1'])) {
                        foreach($grouped_images['-1'] as $img) { renderPhotoCard($img, '-1'); }
                    }
                    ?>
                </div>
            </div>

            <div class="bg-gray-50 p-5 rounded border border-gray-200 w-full">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6 w-full">
                    
                    <div class="w-full min-w-0">
                        <label class="block text-xs font-bold text-[#555] mb-1">Height:</label>
                        <div class="relative w-full">
                            <input type="text" id="dim_height" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" value="<?= htmlspecialchars($data['form2']['height'] ?? '') ?>" name="height">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">inch</span>
                        </div>
                    </div>

                    <div class="w-full min-w-0">
                        <label class="block text-xs font-bold text-[#555] mb-1">Width:</label>
                        <div class="relative w-full">
                            <input type="text" id="dim_width" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" value="<?= htmlspecialchars($data['form2']['width'] ?? '') ?>" name="width">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">inch</span>
                        </div>
                    </div>

                    <div class="w-full min-w-0">
                        <label class="block text-xs font-bold text-[#555] mb-1">Depth:</label>
                        <div class="relative w-full">
                            <input type="text" id="dim_depth" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" value="<?= htmlspecialchars($data['form2']['depth'] ?? '') ?>" name="depth">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">inch</span>
                        </div>
                    </div>

                    <div class="w-full min-w-0">
                        <label class="block text-xs font-bold text-[#555] mb-1">Weight:</label>
                        <div class="relative w-full">
                            <input type="text" id="dim_weight" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" value="<?= htmlspecialchars($data['form2']['weight'] ?? '') ?>" name="weight">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">kg</span>
                        </div>
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Store Location:</label>
                        <input type="text" class="w-full h-[32px] border border-[#ccc] rounded-[3px] px-[10px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" 
                               value="<?= htmlspecialchars($data['form2']['store_location'] ?? '') ?>" 
                               name="store_location">
                    </div>
                    <div class="w-full min-w-0">
                        <label class="block text-xs font-bold text-[#555] mb-1">Colour:</label>
                        <input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] px-3 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" value="<?= htmlspecialchars($data['form2']['color'] ?? '') ?>" name="color">
                    </div>

                    <div class="w-full min-w-0">
                        <label class="block text-xs font-bold text-[#555] mb-1">Size:</label>
                        <?php echo renderSizeField('size', $currentSize, $is_clothing_initial, $sizeOptions); ?>
                    </div>

                    <div class="w-full min-w-0">
                        <label class="block text-xs font-bold text-[#555] mb-1">Quantity:</label>
                        <div class="relative w-full">
                            <input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" value="<?= htmlspecialchars($data['form2']['quantity_received'] ?? '1') ?>" name="quantity_received">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">NOS</span>
                        </div>
                    </div>

                    <div class="w-full min-w-0">
                        <label class="block text-xs font-bold text-[#555] mb-1">CP:</label>
                        <div class="relative w-full">
                            <input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" value="<?= htmlspecialchars($data['form2']['cp'] ?? '') ?>" name="cp">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">INR</span>
                        </div>
                    </div>
                    <div class="w-full min-w-0">
                        <label class="block text-xs font-bold text-[#555] mb-1">Price India:</label>
                        <div class="relative w-full">
                            <input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" value="<?= htmlspecialchars($data['form2']['price_india'] ?? '') ?>" name="price_india">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">INR</span>
                        </div>
                    </div>
                    <div class="w-full min-w-0">
                        <label class="block text-xs font-bold text-[#555] mb-1">Price India MRP:</label>
                        <div class="relative w-full">
                            <input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" value="<?= htmlspecialchars($data['form2']['price_india_mrp'] ?? '') ?>" name="price_india_mrp">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">INR</span>
                        </div>
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">INR Price:</label>
                        <div class="relative flex items-center w-full">
                            <input type="text" class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[40px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" value="<?= htmlspecialchars($data['form2']['inr_pricing'] ?? '') ?>" name = "inr_pricing">
                            <span class="absolute right-[10px] text-xs text-[#777] pointer-events-none">INR</span>
                        </div>
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Amazon Price:</label>
                        <div class="relative flex items-center w-full">
                            <input type="text" class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[40px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" value="<?= htmlspecialchars($data['form2']['amazon_price'] ?? '') ?>" name="amazon_price">
                            <span class="absolute right-[10px] text-xs text-[#777] pointer-events-none">INR</span>
                        </div>
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">USD Price:</label>
                        <div class="relative flex items-center w-full">
                            <input type="text" class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[40px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" value="<?= htmlspecialchars($data['form2']['usd_price'] ?? '') ?>" name="usd_price">
                            <span class="absolute right-[10px] text-xs text-[#777] pointer-events-none">USD</span>
                        </div>
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">HSN Code:</label>
                        <div class="relative flex items-center w-full">
                            <input type="text" class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[40px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" value="<?= htmlspecialchars($data['form2']['hsn_code'] ?? '') ?>" name="hsn_code">
                        </div>
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">GST:</label>
                        <div class="relative flex items-center w-full">
                            <input type="text" class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[40px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" value="<?= htmlspecialchars($data['form2']['gst_rate'] ?? '') ?>" name="gst_rate">
                            <span class="absolute right-[10px] text-xs text-[#777] pointer-events-none">%</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap justify-end items-center mt-6 gap-6 border-t border-dashed border-gray-300 pt-4">
                    <div class="text-right min-w-[100px]">
                        <span class="text-[10px] font-bold text-gray-500 uppercase">Volumetric</span>
                        <div class="text-base font-bold text-[#555]" id="volumetric_weight_display">0.000 kg</div>
                    </div>
                    <div class="hidden sm:block h-8 w-px bg-gray-300"></div>
                    <div class="text-right min-w-[140px]">
                        <span class="text-[10px] font-bold text-gray-500 uppercase">Est. Courier (₹700/kg)</span>
                        <div class="text-base font-bold text-[#d97824]" id="courier_price_display">₹ 0.00</div>
                    </div>
                </div>
            </div>
        </div>

        <div id="variations-container">
            <?php foreach ($variations as $var): ?>
                <div class="variation-card calculation-card mb-8 border-b-2 border-gray-100 pb-6 w-full last:border-0 last:pb-0 last:mb-0 relative group" data-id="<?= $var['id'] ?>">
                    
                    <div class="flex items-center justify-between mb-3 bg-gray-50 p-2 rounded border border-gray-200">
                        <div class="flex items-center gap-2">
                            <?php if(!empty($var['variation_image'])): ?>
                                <img src="<?= base_url($var['variation_image']) ?>" class="w-8 h-8 rounded object-cover border border-gray-300 shrink-0">
                            <?php endif; ?>
                            <span class="bg-[#d97824] text-white text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider">
                                Variation: <?= htmlspecialchars($var['color'] ?? '') ?> - <?= htmlspecialchars($var['size'] ?? '') ?>
                            </span>
                        </div>
                        
                        <div class="flex gap-3">
                            <button type="button" class="clone-var-btn text-blue-600 hover:text-blue-800 text-xs font-bold uppercase flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg> Clone
                            </button>
                            <button type="button" class="remove-var-btn text-red-500 hover:text-red-700 text-xs font-bold uppercase flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg> Remove
                            </button>
                        </div>
                    </div>

                    <div class="flex gap-4 mb-4">
                        <div class="w-[100px] shrink-0">
                            <label class="block text-xs font-bold text-[#555] mb-1">Main Photo:</label>
                            <label class="cursor-pointer block w-full aspect-square bg-white border border-gray-300 border-dashed rounded flex items-center justify-center hover:border-[#d97824] overflow-hidden relative transition-colors">
                                
                                <?php 
                                    // 1. Strict check: Must not be empty and must not be a "0" string
                                    $rawPhoto = $var['variation_image'] ?? '';
                                    $hasPhoto = (!empty($rawPhoto) && $rawPhoto !== '0');
                                ?>

                                <img src="<?= $hasPhoto ? base_url($rawPhoto) : '#' ?>" 
                                     class="preview-img w-full h-full object-cover absolute inset-0 z-10"
                                     style="<?= $hasPhoto ? '' : 'display: none;' ?>">
                                
                                <div class="placeholder-icon flex flex-col items-center justify-center text-gray-400"
                                     style="<?= $hasPhoto ? 'display: none;' : '' ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                                    <span class="text-[9px] mt-1 font-semibold">Upload</span>
                                </div>

                                <input type="file" name="variations[<?= $var['id'] ?>][photo]" accept="image/*" class="hidden variation-file-input">
                                <input type="hidden" name="variations[<?= $var['id'] ?>][old_photo]" value="<?= $hasPhoto ? $rawPhoto : '' ?>">
                            </label>
                        </div>
                        
                        <div class="grow overflow-hidden">
                            <label class="block text-xs font-bold text-[#555] mb-1">Gallery Photos:</label>
                            <div class="photo-group-grid flex flex-row overflow-x-auto gap-3 min-h-[100px] p-2 border border-dashed border-gray-300 rounded bg-gray-50 custom-scrollbar" data-var-id="<?= $var['id'] ?>">
                                <?php 
                                if (!empty($grouped_images[$var['id']])) {
                                    foreach($grouped_images[$var['id']] as $img) { renderPhotoCard($img, $var['id']); }
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded border border-gray-200 w-full shadow-sm">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6 w-full">
                            <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">Height:</label><div class="relative w-full"><input type="text" class="calc-h w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" value="<?= htmlspecialchars($var['height'] ?? '') ?>" name="variations[<?= $var['id'] ?>][height]"><span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">inch</span></div></div>
                            <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">Width:</label><div class="relative w-full"><input type="text" class="calc-w w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" value="<?= htmlspecialchars($var['width'] ?? '') ?>" name="variations[<?= $var['id'] ?>][width]"><span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">inch</span></div></div>
                            <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">Depth:</label><div class="relative w-full"><input type="text" class="calc-d w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" value="<?= htmlspecialchars($var['depth'] ?? '') ?>" name="variations[<?= $var['id'] ?>][depth]"><span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">inch</span></div></div>
                            <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">Weight:</label><div class="relative w-full"><input type="text" class="calc-wt w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" value="<?= htmlspecialchars($var['weight'] ?? '') ?>" name="variations[<?= $var['id'] ?>][weight]"><span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">kg</span></div></div>

                            <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">Location:</label><input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] px-3 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" value="<?= htmlspecialchars($var['store_location'] ?? '') ?>" name="variations[<?= $var['id'] ?>][store_location]"></div>

                            <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">Colour:</label><input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] px-3 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" value="<?= htmlspecialchars($var['color'] ?? '') ?>" name="variations[<?= $var['id'] ?>][color]"></div>
                            <div class="w-full min-w-0">
                                <label class="block text-xs font-bold text-[#555] mb-1">Size:</label>
                                <?php echo renderSizeField("variations[{$var['id']}][size]", $var['size'], $is_clothing_initial, $sizeOptions); ?>
                            </div>
                            <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">Quantity:</label><div class="relative w-full"><input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" value="<?= htmlspecialchars($var['quantity'] ?? '0') ?>" name="variations[<?= $var['id'] ?>][quantity]"><span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">NOS</span></div></div>
                            <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">CP:</label><div class="relative w-full"><input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" value="<?= htmlspecialchars($var['cp'] ?? '') ?>" name="variations[<?= $var['id'] ?>][cp]"><span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">INR</span></div></div>
                            <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">Price India:</label><div class="relative w-full"><input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" value="<?= htmlspecialchars($var['price_india'] ?? '') ?>" name="variations[<?= $var['id'] ?>][price_india]"><span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">INR</span></div></div>
                            <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">Price India MRP:</label><div class="relative w-full"><input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" value="<?= htmlspecialchars($var['price_india_mrp'] ?? '') ?>" name="variations[<?= $var['id'] ?>][price_india_mrp]"><span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">INR</span></div></div>



                            <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">INR Price::</label><div class="relative w-full"><input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" value="<?= htmlspecialchars($var['inr_pricing'] ?? '') ?>" name="variations[<?= $var['id'] ?>][inr_pricing]"><span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">INR</span></div></div>

                            <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">Amazon Price:</label><div class="relative w-full"><input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" value="<?= htmlspecialchars($var['amazon_price'] ?? '') ?>" name="variations[<?= $var['id'] ?>][amazon_price]"><span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">INR</span></div></div>

                            <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">USD Price:</label><div class="relative w-full"><input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" value="<?= htmlspecialchars($var['usd_price'] ?? '') ?>" name="variations[<?= $var['id'] ?>][usd_price]"><span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">USD</span></div></div>

                            <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">HSN Code:</label><div class="relative w-full"><input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" value="<?= htmlspecialchars($var['hsn_code'] ?? '') ?>" name="variations[<?= $var['id'] ?>][hsn_code]"></div></div>

                            <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">GST:</label><div class="relative w-full"><input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" value="<?= htmlspecialchars($var['gst_rate'] ?? '') ?>" name="variations[<?= $var['id'] ?>][gst_rate]"><span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">%</span></div></div>
                            <input type="hidden" name="variations[<?= $var['id'] ?>][id]" value="<?= $var['id'] ?>">
                        </div>

                        <div class="flex flex-wrap justify-end items-center mt-6 gap-6 border-t border-dashed border-gray-300 pt-4">
                            <div class="text-right min-w-[100px]">
                                <span class="text-[10px] font-bold text-gray-500 uppercase">Volumetric</span>
                                <div class="text-base font-bold text-[#555] calc-vol-display">0.000 kg</div>
                            </div>
                            <div class="hidden sm:block h-8 w-px bg-gray-300"></div>
                            <div class="text-right min-w-[140px]">
                                <span class="text-[10px] font-bold text-gray-500 uppercase">Est. Courier (₹700/kg)</span>
                                <div class="text-base font-bold text-[#d97824] calc-price-display">₹ 0.00</div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-6 flex justify-center pb-5">
            <button type="button" onclick="addNewVariation()" class="flex items-center gap-2 bg-[#d97824] text-white px-4 py-2 rounded text-sm font-bold hover:bg-[#c66a1d] transition">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                Add Variation Card
            </button>
        </div>

    </div>
</div>

<template id="variation-template">
    <div class="variation-card calculation-card mb-8 border-b-2 border-gray-100 pb-6 w-full pt-6 border-t-2 mt-6 relative group">
        <div class="flex items-center justify-between mb-3 bg-blue-50 p-2 rounded border border-blue-200">
            <div class="flex items-center gap-2">
                <span class="bg-blue-100 text-blue-600 text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider">New Variation</span>
            </div>
            <div class="flex gap-3">
                <button type="button" class="clone-var-btn text-blue-600 hover:text-blue-800 text-xs font-bold uppercase flex items-center gap-1">
                     <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg> Clone
                </button>
                <button type="button" class="remove-var-btn text-red-500 hover:text-red-700 text-xs font-bold uppercase flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg> Remove
                </button>
            </div>
        </div>
        
        <div class="flex gap-4 mb-4">
            <div class="w-[100px] shrink-0">
                <label class="block text-xs font-bold text-[#555] mb-1">Main Photo:</label>
                <label class="cursor-pointer block w-full aspect-square bg-white border border-gray-300 border-dashed rounded flex items-center justify-center hover:border-[#d97824] overflow-hidden relative transition-colors">
                    
                    <img src="#" class="preview-img w-full h-full object-cover absolute inset-0 z-10" style="display: none;">
                    
                    <div class="placeholder-icon flex flex-col items-center justify-center text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                            <span class="text-[9px] mt-1 font-semibold">Upload</span>
                    </div>
                    
                    <input type="file" name="variations[INDEX][photo]" accept="image/*" class="hidden variation-file-input">
                    <input type="hidden" name="variations[INDEX][old_photo]" value="">
                </label>
            </div>
            <div class="grow flex items-center justify-center bg-gray-50 border border-dashed border-gray-300 rounded text-gray-400 text-xs">Save item to enable Gallery</div>
        </div>

        <div class="bg-white p-5 rounded border border-gray-200 w-full shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6 w-full">
                <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">Height:</label><div class="relative w-full"><input type="text" class="calc-h w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" name="variations[INDEX][height]"><span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">inch</span></div></div>
                <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">Width:</label><div class="relative w-full"><input type="text" class="calc-w w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" name="variations[INDEX][width]"><span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">inch</span></div></div>
                <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">Depth:</label><div class="relative w-full"><input type="text" class="calc-d w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" name="variations[INDEX][depth]"><span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">inch</span></div></div>
                <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">Weight:</label><div class="relative w-full"><input type="text" class="calc-wt w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" name="variations[INDEX][weight]"><span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">kg</span></div></div>
                
                <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">Location:</label><input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] px-3 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" name="variations[INDEX][store_location]"></div>

                <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">Colour:</label><input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] px-3 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" name="variations[INDEX][color]"></div>
                <div class="w-full min-w-0 size-wrapper-js">
                    <label class="block text-xs font-bold text-[#555] mb-1">Size:</label>
                    <select class="size-input-field w-full h-10 border border-[#ccc] rounded-[3px] px-2 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" name="variations[INDEX][size]">
                        <option value="">Select Size</option>
                        <?php 
                        foreach ($sizeOptions as $dbValue => $displayLabel) {
                            echo '<option value="' . htmlspecialchars($dbValue) . '">' . htmlspecialchars($displayLabel) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">Quantity:</label><div class="relative w-full"><input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" value="0" name="variations[INDEX][quantity]"><span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">NOS</span></div></div>
                <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">CP:</label><div class="relative w-full"><input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" name="variations[INDEX][cp]"><span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">INR</span></div></div>
                <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">Price India:</label><div class="relative w-full"><input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" name="variations[INDEX][price_india]"><span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">INR</span></div></div>
                <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">Price India MRP:</label><div class="relative w-full"><input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" name="variations[INDEX][price_india_mrp]"><span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">INR</span></div></div>


                <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">INR Price:</label><div class="relative w-full"><input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" name="variations[INDEX][inr_pricing]"><span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">INR</span></div></div>

                <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">Amazon Price:</label><div class="relative w-full"><input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" name="variations[INDEX][amazon_price]"><span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">INR</span></div></div>

                <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">USD Price:</label><div class="relative w-full"><input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" name="variations[INDEX][usd_price]"><span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">USD</span></div></div>

                <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">HSN Code:</label><div class="relative w-full"><input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" name="variations[INDEX][hsn_code]"></div></div>

                <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">GST:</label><div class="relative w-full"><input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" name="variations[INDEX][gst_rate]"><span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">%</span></div></div>
            </div>

            <div class="flex flex-wrap justify-end items-center mt-6 gap-6 border-t border-dashed border-gray-300 pt-4">
                <div class="text-right min-w-[100px]">
                    <span class="text-[10px] font-bold text-gray-500 uppercase">Volumetric</span>
                    <div class="text-base font-bold text-[#555] calc-vol-display">0.000 kg</div>
                </div>
                <div class="hidden sm:block h-8 w-px bg-gray-300"></div>
                <div class="text-right min-w-[140px]">
                    <span class="text-[10px] font-bold text-gray-500 uppercase">Est. Courier (₹700/kg)</span>
                    <div class="text-base font-bold text-[#d97824] calc-price-display">₹ 0.00</div>
                </div>
            </div>
        </div>
    </div>
</template>

    <?php 
    // Helper Function for Cards
    function renderPhotoCard($img, $varId) {
    ?>
        <div class="draggable-item relative border border-[#ddd] rounded-[4px] p-2 bg-white flex flex-col items-center group cursor-grab active:cursor-grabbing shadow-sm" 
             draggable="true" 
             data-id="<?php echo $img['id']; ?>">
            
            <div class="absolute top-1 right-1 text-gray-400 p-1 bg-white rounded shadow-sm opacity-50 group-hover:opacity-100 transition">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="12" r="1"/><circle cx="9" cy="5" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="19" r="1"/></svg>
            </div>

            <div class="w-full h-32 bg-white flex items-center justify-center overflow-hidden rounded-[2px] border border-[#eee] mb-2" onclick="openImagePopup('uploads/itm_img/<?php echo $img['file_name']; ?>')">
                <img src="uploads/itm_img/<?php echo $img['file_name']; ?>" class="max-w-full max-h-full object-contain cursor-pointer">
            </div>

            <span class="text-[11px] text-[#666] truncate w-full text-center" title="<?php echo $img['file_name']; ?>">
                <?php echo $img['file_name']; ?>
            </span>

            <input type="hidden" name="photo_order[<?php echo $img['id']; ?>]" value="<?php echo $img['display_order']; ?>" class="order-input">
            <input type="hidden" name="photo_variation[<?php echo $img['id']; ?>]" value="<?php echo $varId; ?>" class="variation-input">
        </div>
    <?php } ?>
        <div class="mt-[15px] md:mx-5">
            <fieldset class="border border-[#ccc] rounded-[5px] px-[15px] py-2 pb-3 bg-white w-full">
                <legend class="text-[13px] font-bold text-[#333] px-[5px]">Receipt:</legend>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 md:gap-[50px]">        
                    <div class="flex flex-col">
                        <span class="text-[11px] font-bold text-[#222] mb-[3px]">Gate Entry Date & Time:</span>
                        <span class="text-xs text-[#444]">
                            <?php echo !empty($data['form2']['gate_entry_date_time']) 
                                ? date('d M Y h:i A', strtotime($data['form2']['gate_entry_date_time'])) 
                                : ''; ?>
                        </span>
                    </div>
                    <div class="flex flex-col mb-4">
                        <span class="text-[11px] font-bold text-[#222] mb-[3px]">Received by:</span>
                        <select id="received_by_select" name="received_by_user_id" placeholder="Select User...">
                            <option value="">Select User</option>
                            <?php foreach ($data['user'] as $value1) { 
                                $isSelected = ($data['form2']['received_by_user_id'] == $value1['id']) ? 'selected' : '';
                            ?> 
                                <option value="<?php echo $value1['id']; ?>" <?php echo $isSelected; ?>>
                                    <?php echo $value1['name']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[11px] font-bold text-[#222] mb-[3px]">Feeded By:</span>
                        <select id="updated_by_select" name="updated_by_user_id" placeholder="Select User...">
                            <option value="">Select User</option>
                            <?php 
                                // 1. Get the values safely (force to integer to handle string "0")
                                $dbValue = isset($data['form2']['updated_by_user_id']) ? (int)$data['form2']['updated_by_user_id'] : 0;
                                $sessionValue = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 0;

                                // 2. LOGIC: If DB has a valid ID (> 0), use it. Otherwise, default to Session ID.
                                $selectedId = ($dbValue > 0) ? $dbValue : $sessionValue;

                                foreach ($data['user'] as $value1) { 
                                    // 3. Simple comparison
                                    $isSelected = ($value1['id'] == $selectedId) ? 'selected' : '';
                                ?> 
                                <option value="<?php echo $value1['id']; ?>" <?php echo $isSelected; ?>>
                                    <?php echo $value1['name']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[11px] font-bold text-[#222] mb-[3px]">Vendor:</span>
                        <select name="vendor_code" id="vendor_code" placeholder="Select Vendor...">
                            <option value="">Select Vendor</option>
                            <?php foreach ($data['vendors'] as $key4 => $value4) { 
                                $isSelected = (isset($data['form2']['vendor_code']) && $data['form2']['vendor_code'] == $value4['id']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $value4['id']; ?>" <?php echo $isSelected; ?>>
                                    <?php echo htmlspecialchars($value4['vendor_name'] ?? ''); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
            </fieldset>
        </div>

        <?php 
            $selected_material = $data['form2']['material_code'] ?? '';
            $selected_group_val = $data['form2']['group_name'] ?? ''; 
            $selected_cat_id    = $data['form2']['category_code'] ?? '';
            $sub_raw = $data['form2']['sub_category_code'] ?? '';
            $selected_sub = is_array($sub_raw) ? $sub_raw : explode(',', $sub_raw);
            $sub_sub_raw = $data['form2']['sub_sub_category_code'] ?? '';
            $selected_sub_sub = is_array($sub_sub_raw) ? $sub_sub_raw : explode(',', $sub_sub_raw);

            $categoriesByParent1 = [];
            $rootCategories = [];

            if (!empty($data['category'])) {
                foreach ($data['category'] as $row) {
                    if (isset($row['is_active']) && $row['is_active'] != 1) { continue; }

                    // 1. Get the Parent Key (Strict String)
                    // This matches the 'parent' column in your DB (e.g. "clothing" or "mens_wear|clothing")
                    $parentKey = isset($row['parent']) ? trim((string)$row['parent']) : '';

                    // Skip orphans
                    if ($parentKey === '') continue; 
                    
                    // 2. Identify the "Store Value" (The value used in the parent path)
                    // Priority: 'category' column -> 'id' column
                    // You mentioned "value should store of category field", so we use that.
                    $storageValue = !empty($row['category']) ? $row['category'] : $row['id'];

                    // 3. Build the Tree
                    $categoriesByParent1[$parentKey][] = [
                        'id'          => $row['id'],
                        'name'        => $row['display_name'],
                        'store_val'   => $storageValue // <--- We send this to JS to build paths
                    ];

                    // 4. Identify Root Groups (Parent is "0")
                    if ($parentKey === '0') {
                        $rootCategories[] = [
                            'id'          => $row['id'],
                            'name'        => $row['display_name'],
                            'store_value' => $storageValue // This becomes the root of the path
                        ];
                    }
                }
            }
        ?>
        <div class="mt-[15px] md:mx-5">
            <fieldset class="border border-[#ccc] rounded-[5px] px-[15px] py-4 bg-white">
                <legend class="text-[13px] font-bold text-[#333] px-[5px]">Item Grouping</legend>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                    <div>
                        <label class="block text-xs font-bold text-[#222] mb-1">Material:</label>
                        <div class="flex gap-2 items-center">
                            <div class="grow">
                                <select id="material_select" name="material_code" placeholder="Select Material..." autocomplete="off">
                                    <option value="">Select Material</option>
                                    <?php foreach ($data['material'] as $value2) { 
                                        $isSelected = ($selected_material == $value2['id']) ? 'selected' : '';
                                    ?> 
                                        <option <?php echo $isSelected; ?> value="<?php echo $value2['id']; ?>"> <?php echo $value2['material_name']; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            
                            <button type="button" onclick="openMaterialModal()" class="h-[36px] w-[36px] bg-[#28a745] hover:bg-[#218838] text-white rounded-[4px] flex items-center justify-center shadow-sm transition" title="Add New Material">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#222] mb-1">Group:</label>
                        <select id="group_select" name="group_name" placeholder="Select Group..." autocomplete="off">
                            <option value="">Select Group...</option>
                            <?php foreach($rootCategories as $group): 
                                $isGroupSelected = ($selected_group_val == $group['store_value']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $group['store_value']; ?>" <?php echo $isGroupSelected; ?>>
                                    <?php echo $group['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row gap-5 items-stretch">
                    
                    <div class="w-full md:w-1/3 flex flex-col">
                        <label class="block text-xs font-bold text-[#222] mb-1">Category:</label>
                        <div class="border border-[#ccc] rounded-[4px] bg-white flex-grow h-[200px] flex flex-col">
                            <div id="category_container" class="checkbox-list-container overflow-y-auto p-1 h-full">
                                <div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Group to view options</div>
                            </div>
                        </div>
                    </div>

                    <div class="w-full md:w-1/3 flex flex-col">
                        <label class="block text-xs font-bold text-[#222] mb-1">Sub Category:</label>
                        <div class="border border-[#ccc] rounded-[4px] bg-white flex-grow h-[200px] flex flex-col">
                            <div class="p-1 border-b border-gray-200 bg-gray-50">
                                <input type="text" id="sub_cat_search" placeholder="Search Sub Category..." 
                                       class="w-full h-[28px] text-xs border border-gray-300 rounded px-2 focus:outline-none focus:border-[#d97824]">
                            </div>
                            <div id="sub_category_container" class="checkbox-list-container overflow-y-auto p-1 flex-grow">
                                <div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Category to view options</div>
                            </div>
                        </div>
                    </div>

                    <div class="w-full md:w-1/3 flex flex-col">
                        <label class="block text-xs font-bold text-[#222] mb-1">SubSubCategory:</label>
                        <div class="border border-[#ccc] rounded-[4px] bg-white flex-grow h-[200px] flex flex-col">
                            <div class="p-1 border-b border-gray-200 bg-gray-50">
                                <input type="text" id="sub_sub_cat_search" placeholder="Search Sub Sub Category..." 
                                       class="w-full h-[28px] text-xs border border-gray-300 rounded px-2 focus:outline-none focus:border-[#d97824]">
                            </div>
                            <div id="sub_sub_category_container" class="checkbox-list-container overflow-y-auto p-1 flex-grow">
                                <div class="text-xs text-gray-400 p-2 text-center mt-10">Select Sub Category to view options</div>
                            </div>
                        </div>
                    </div>
                </div>
            </fieldset>
        </div>


        <div class="mt-[15px] md:mx-5">
            <fieldset class="border border-[#ccc] rounded-[5px] px-5 py-[15px] pb-5 bg-white">
                <legend class="text-[13px] font-bold text-[#333] px-[5px]">Item Identification</legend>
                <div class="mb-[15px]">
                    <label class="block text-xs font-bold text-[#222] mb-[5px]">Title:</label>
                    <input type="text" class="w-full h-[34px] border border-[#ccc] rounded-[4px] px-2.5 text-[13px] text-[#333] focus:outline-none focus:border-[#999]" name="product_title" value="<?= htmlspecialchars($data['form2']['product_title'] ?? '') ?>">
                </div>
                <div class="mb-[15px]">
                    <label class="block text-xs font-bold text-[#222] mb-[5px]">Keywords:</label>
                    <input type="text" class="w-full h-[34px] border border-[#ccc] rounded-[4px] px-2.5 text-[13px] text-[#333] focus:outline-none focus:border-[#999]" value="<?= htmlspecialchars($data['form2']['key_words'] ?? '') ?>" name= "key_words">
                </div>
                <div class="mb-[15px]">
                    <label class="block text-xs font-bold text-[#222] mb-[5px]">Snippet Description:</label>
                    <input type="text" class="w-full h-[34px] border border-[#ccc] rounded-[4px] px-2.5 text-[13px] text-[#333] focus:outline-none focus:border-[#999]" value="<?= htmlspecialchars($data['form2']['snippet_description'] ?? '') ?>" name= "snippet_description">
                </div>
                <div class="mb-4">
                    <label class="block text-xs font-bold text-[#222] mb-[5px]">Select Optionals:</label>
                    
                    <div class="border border-[#ccc] rounded-[4px] bg-white h-[200px] flex flex-col">
                        
                        <div class="checkbox-list-container overflow-y-auto p-1 h-full">
                            <?php 
                                // 1. Get the initial data
                                $source_data = $data['form2']['optionals'] ?? [];

                                // 2. FIX: Check if the actual list is hidden inside an 'optionals' key
                                if (isset($source_data['optionals']) && is_array($source_data['optionals'])) {
                                    $available_options = $source_data['optionals'];
                                } else {
                                    $available_options = $source_data;
                                }

                                // 3. Get Saved Values
                                $saved_raw = $data['form2']['optionals'] ?? []; 
                                $saved_values = [];
                                
                                if (is_array($saved_raw)) {
                                    $saved_values = $saved_raw;
                                } elseif (is_string($saved_raw)) {
                                    $saved_values = array_map('trim', explode(',', $saved_raw));
                                }

                                if (!empty($available_options) && is_array($available_options)) {
                                    foreach ($available_options as $key => $val_str) {
                                        
                                        // Ensure strictly string
                                        if (is_array($val_str)) continue; // Skip bad data
                                        $val_str = (string)$val_str; 

                                        // Format Label
                                        $label = str_replace(['OPTIONALS_', '_'], ['', ' '], $val_str); 
                                        $label = ucwords(strtolower($label));               

                                        // Check if checked
                                        $isChecked = in_array($val_str, $saved_values) ? 'checked' : '';
                                        $uniqueId = 'opt_' . md5($val_str); 
                            ?>
                                    
                                    <div class="checkbox-item flex items-center p-2 hover:bg-gray-50 border-b border-gray-100 last:border-0">
                                        <input type="checkbox" 
                                               id="<?= $uniqueId ?>" 
                                               name="optionals[]" 
                                               value="<?= htmlspecialchars($val_str) ?>" 
                                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 cursor-pointer mr-2"
                                               <?= $isChecked ?>>
                                        
                                        <label for="<?= $uniqueId ?>" class="w-full text-sm font-medium text-gray-900 cursor-pointer select-none">
                                            <?= $label ?>
                                        </label>
                                    </div>

                            <?php 
                                    }
                                } else {
                                    echo '<div class="text-xs text-gray-400 p-4 text-center">No options available</div>';
                                }
                            ?>
                        </div>
                    </div>
                </div>
            </fieldset>
        </div>

        <div class="mt-[15px] md:mx-5">
            <fieldset class="border border-[#ccc] rounded-[5px] px-5 py-[15px] bg-white">
                <legend class="text-[13px] font-bold text-[#333] px-[5px]">Invoice Details:</legend>
                
                <?php 
                    $hasImage = !empty($data['form2']['invoice_image']);
                    $imageSrc = $hasImage ? base_url($data['form2']['invoice_image']) : '';
                ?>

                <div class="flex flex-col md:flex-row gap-5 items-stretch">
                    
                    <div class="flex-1 md:max-w-[250px]">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Upload Invoice:</label>
                        <div class="border-2 border-dashed border-gray-300 rounded-[5px] bg-[#f9f9f9] hover:bg-[#f0f0f0] transition-colors cursor-pointer h-[100px] flex flex-col items-center justify-center gap-1 group"
                             onclick="document.getElementById('invoice_input').click()">
                            
                            <input type="file" id="invoice_input" name="invoice_image" class="hidden" accept="image/*" onchange="previewInvoice(this)">
                            
                            <div class="p-2 bg-white rounded-full shadow-sm group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                            </div>
                            <span class="text-[11px] text-[#666] font-medium">Click to Upload</span>
                        </div>
                    </div>

                    <div id="invoice_preview_container" class="flex-1 <?php echo $hasImage ? '' : 'hidden'; ?>">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Current Invoice:</label>
                        
                        <div class="border border-[#ccc] rounded-[5px] p-3 flex gap-4 items-center bg-white h-[100px]">
                            
                            <div class="relative w-[70px] h-[80px] bg-gray-100 border border-gray-200 rounded-[4px] shrink-0 cursor-zoom-in group overflow-hidden"
                                 onclick="openInvoicePopup()">
                                <img id="invoice_img_tag" src="<?php echo $imageSrc; ?>" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 flex items-center justify-center transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-0 group-hover:opacity-100"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="11" y1="8" x2="11" y2="14"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>
                                </div>
                            </div>

                            <div class="flex flex-col gap-2 justify-center">
                                <a id="invoice_download_btn" href="<?php echo $imageSrc; ?>" download="Invoice_Image" class="flex items-center gap-2 text-[12px] font-bold text-[#d97824] hover:text-[#bf7326] transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                    Download
                                </a>

                                <button type="button" onclick="openDeletePopup(event)" class="flex items-center gap-2 text-[12px] font-bold text-red-500 hover:text-red-700 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Invoice Number:</label>
                        <div class="flex flex-col justify-center">
                            <input type="text" class="w-full h-[36px] border border-[#ccc] rounded-[4px] px-2.5 text-[13px] text-[#333] focus:outline-none focus:border-[#999]" name="invoice_no" 
                                   value="<?php echo !empty($data['form2']['invoice_no']) ? $data['form2']['invoice_no'] : ''; ?>">
                        </div>
                    </div>

                </div>
            </fieldset>
        </div>

        <div class="mt-[15px] md:mx-5" style="display:none;">
            <fieldset class="border border-[#ccc] rounded-[5px] px-5 py-[15px] bg-white">
                <legend class="text-[13px] font-bold text-[#333] px-[5px]">Unit:</legend>
                <div class="flex gap-5 items-start mb-[15px]">
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Dimention Unit:</label>
                        <div class="relative flex items-center w-full">
                            <select class="w-full h-[32px] border border-[#ccc] rounded-[3px] px-[10px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" name="dimention_unit">
                                <option value="cm" selected>cm</option>
                                <option value="inch">inch</option>
                            </select>
                        </div>
                    </div>
                     <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Weight Unit:</label>
                        <div class="relative flex items-center w-full">
                            <select class="w-full h-[32px] border border-[#ccc] rounded-[3px] px-[10px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" name="weight_unit">
                                <option value="kg" selected>kg</option>
                                <option value="gm">gm</option>
                            </select>
                        </div>
                    </div>
                </div>
            </fieldset>
        </div>

        <div class="mt-[15px] md:mx-5">
            <fieldset class="border border-[#ccc] rounded-[5px] px-5 py-[15px] bg-white">
                <legend class="text-[13px] font-bold text-[#333] px-[5px]">Stock:</legend>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-start mb-[15px]">

                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Permanently Available:</label>
                        <select class="w-full h-[32px] border border-[#ccc] rounded-[3px] px-[10px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" name="permanently_available">
                            <?php $perm = $data['form2']['permanently_available'] ?? 'N'; ?>
                            <option value="N" <?= ($perm == 'N') ? 'selected' : '' ?>>No</option>
                            <option value="Y" <?= ($perm == 'Y') ? 'selected' : '' ?>>Yes</option>
                        </select>
                    </div>

                    <div class="w-full min-w-0">
                        <label class="block text-xs font-bold text-[#555] mb-1">Warehouse:</label>
                        <select class="w-full h-10 border border-[#ccc] rounded-[3px] px-2 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" name="ware_house_code">
                            <option value="">Select Warehouse</option>
                            <?php 
                                $selectedWH = $data['form2']['ware_house_code'] ?? '';
                                if (!empty($data['address'])) {
                                    foreach ($data['address'] as $va) {
                                        $isSelected = ($selectedWH == $va['id']) ? 'selected' : '';
                            ?>
                                        <option value="<?php echo $va['id']; ?>" <?php echo $isSelected; ?>>
                                            <?php echo htmlspecialchars($va['address_title']); ?>
                                        </option>
                            <?php 
                                    }
                                } 
                            ?>
                        </select>
                    </div>

                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Back Order:</label>
                        <select class="w-full h-[32px] border border-[#ccc] rounded-[3px] px-[10px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" 
                                name="back_order" id="back_order_select" onchange="toggleBackOrderFields()">
                            <?php $backOrder = $data['form2']['back_order'] ?? '0'; ?>
                            <option value="0" <?= ($backOrder == '0') ? 'selected' : '' ?>>No</option>
                            <option value="1" <?= ($backOrder == '1') ? 'selected' : '' ?>>Yes</option>
                        </select>
                    </div>

                    <div class="flex-1 backorder-field" style="display: none;">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Backorder Percentage:</label>
                        <div class="relative w-full">
                            <input type="number" name="backorder_percent" min="1" max="100" placeholder="0"
                                   value="<?= htmlspecialchars($data['form2']['backorder_percent'] ?? '') ?>" 
                                   class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[30px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]">
                            <span class="absolute right-[10px] top-1/2 -translate-y-1/2 text-[13px] text-[#777] pointer-events-none">%</span>
                        </div>
                    </div>

                    <div class="flex-1 backorder-field" style="display: none;">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Backorder Days:</label>
                        <div class="relative w-full">
                            <input type="number" name="backorder_day" min="0" placeholder="0"
                                   value="<?= htmlspecialchars($data['form2']['backorder_day'] ?? '') ?>" 
                                   class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[45px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]">
                            <span class="absolute right-[10px] top-1/2 -translate-y-1/2 text-[13px] text-[#777] pointer-events-none">Days</span>
                        </div>
                    </div>

                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Lead Time:</label>
                        <div class="relative w-full">
                            <input type="text" name="lead_time_days" 
                                   value="<?= htmlspecialchars($data['form2']['lead_time_days'] ?? '10') ?>" 
                                   class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[45px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]">
                            <span class="absolute right-[10px] top-1/2 -translate-y-1/2 text-[13px] text-[#777] pointer-events-none">Days</span>
                        </div>
                    </div>

                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">In Stock Lead Time:</label>
                        <div class="relative w-full">
                            <input type="text" name="in_stock_leadtime_days" 
                                   value="<?= htmlspecialchars($data['form2']['in_stock_leadtime_days'] ?? '0') ?>" 
                                   class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[45px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]">
                            <span class="absolute right-[10px] top-1/2 -translate-y-1/2 text-[13px] text-[#777] pointer-events-none">Days</span>
                        </div>
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Marketplace Vendor:</label>
                        <div class="relative w-full">
                            <input type="number" name="marketplace" 
                                   value="<?= htmlspecialchars($data['form2']['marketplace'] ?? 'exoticindia') ?>" 
                                   class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[45px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]">
                        </div>
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Indian Net Qty.:</label>
                        <div class="relative w-full">
                            <input type="number" name="india_net_qty" 
                                   value="<?= htmlspecialchars($data['form2']['india_net_qty '] ?? '0') ?>" 
                                   class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[45px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]">
                        </div>
                    </div>

                    <div class="flex-1 sm:col-span-2 lg:col-span-2 flex items-start border border-[#eee] rounded bg-gray-50 p-1"> 
                        
                        <div class="flex-1 pr-4 border-r border-[#ccc] flex flex-col justify-center h-[52px]"> 
                            <label class="block text-xs font-bold text-[#222] mb-[3px]">US Block:</label>
                            <div class="flex items-center gap-4">
                                <?php $us_val = $data['form2']['us_block'] ?? 'Y'; ?>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="us_block" value="Y" class="w-3.5 h-3.5 accent-[#666]" <?= ($us_val == 'Y') ? 'checked' : '' ?>>
                                    <span class="ml-1.5 text-[12px] text-[#333]">Yes</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="us_block" value="N" class="w-3.5 h-3.5 accent-[#666]" <?= ($us_val == 'N') ? 'checked' : '' ?>>
                                    <span class="ml-1.5 text-[12px] text-[#333]">No</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex-1 pl-4 flex flex-col justify-center h-[52px]"> 
                            <label class="block text-xs font-bold text-[#222] mb-[3px]">India Block:</label>
                            <div class="flex items-center gap-4">
                                <?php $in_val = $data['form2']['india_block'] ?? 'N'; ?>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="india_block" value="Y" class="w-3.5 h-3.5 accent-[#666]" <?= ($in_val == 'Y') ? 'checked' : '' ?>>
                                    <span class="ml-1.5 text-[12px] text-[#333]">Yes</span>
                                </label>

                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="india_block" value="N" class="w-3.5 h-3.5 accent-[#666]" <?= ($in_val == 'N') ? 'checked' : '' ?>>
                                    <span class="ml-1.5 text-[12px] text-[#333]">No</span>
                                </label>
                            </div>
                        </div>

                    </div>

                </div>
            </fieldset>
        </div>
        
        <div class="flex justify-end gap-4 my-[25px] md:mx-5 mb-10">
            <button type="button" onclick="openPublishPopup()" class="bg-[#28a745] text-white border-none rounded-[4px] py-[10px] px-[30px] font-bold text-sm cursor-pointer shadow-md hover:bg-[#218838] transition flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                Publish Product
            </button>

            <button class="bg-gray-600 text-white border-none rounded-[4px] py-[10px] px-[30px] font-bold text-sm cursor-pointer shadow-md hover:bg-gray-700 transition">
                Save and Draft
            </button>
            
            <button class="bg-[#d97824] text-white border-none rounded-[4px] py-[10px] px-[30px] font-bold text-sm cursor-pointer shadow-md hover:bg-[#c0651a] transition">
                Save and Generate Item Code
            </button>
        </div>
    </form>
</div>
<div id="publishConfirmPopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center z-[80]">
    <div class="bg-white p-6 rounded-md w-[90%] max-w-[400px] shadow-lg relative text-center font-['Segoe_UI']" onclick="event.stopPropagation();">
        <h3 class="text-lg font-bold mb-2 text-gray-800">Publish Product?</h3>
        <p class="text-sm text-gray-600 mb-6">Are you sure you want to publish this product?</p>
        <div class="flex gap-3 justify-center">
            <button type="button" onclick="closePublishPopup()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm font-semibold hover:bg-gray-300 transition">Cancel</button>
            
            <button type="button" onclick="triggerPublishController()" class="bg-[#28a745] text-white px-6 py-2 rounded text-sm font-semibold hover:bg-[#218838] transition shadow-md">Yes, Publish</button>
        </div>
    </div>
</div>
<div id="imagePopup" class="fixed inset-0 bg-black bg-opacity-80 hidden flex justify-center items-center z-[100]" onclick="closeImagePopup(event)">
    <div class="bg-white p-2 rounded-md w-auto max-w-[95vw] max-h-[95vh] relative flex flex-col items-center shadow-2xl" onclick="event.stopPropagation();">
        
        <button onclick="closeImagePopup()" class="absolute -top-3 -right-3 bg-red-600 hover:bg-red-700 text-white w-8 h-8 flex items-center justify-center rounded-full text-sm shadow-md border-2 border-white">✕</button>
        
        <img id="popupImage" class="max-w-full max-h-[90vh] rounded object-contain" src="" alt="Image Preview">
        
    </div>
</div>

<div id="deleteConfirmPopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center z-[60]">
    <div class="bg-white p-6 rounded-md w-[90%] max-w-[400px] shadow-lg relative text-center font-['Segoe_UI']" onclick="event.stopPropagation();">
        <h3 class="text-lg font-bold mb-2 text-gray-800">Remove Invoice?</h3>
        <p class="text-sm text-gray-600 mb-4">
            Are you sure you want to remove the invoice image?<br>
            Type <strong>Delete</strong> below to confirm.
        </p>
        
        <input type="text" 
               id="deleteConfirmationInput" 
               class="border border-gray-300 rounded p-2 w-full mb-5 text-center focus:outline-none focus:border-red-500 text-sm" 
               placeholder="Type Delete here"
               autocomplete="off">
               
        <div class="flex gap-3 justify-center">
            <button type="button" onclick="closeDeletePopup()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm font-semibold hover:bg-gray-300 transition">Cancel</button>
            <button type="button" onclick="confirmDeleteInvoice()" class="bg-[#d32f2f] text-white px-4 py-2 rounded text-sm font-semibold hover:bg-[#b71c1c] transition">Confirm Remove</button>
        </div>
    </div>
</div>
<div id="materialModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center z-[70]">
    <div class="bg-white p-6 rounded-md w-[90%] max-w-[400px] shadow-lg relative font-['Segoe_UI']">
        <h3 class="text-lg font-bold mb-4 text-gray-800 border-b pb-2">Add New Material</h3>
        
        <input type="hidden" id="new_user_id" value="<?php echo $_SESSION['user']['id'] ?? 0; ?>">

        <div class="flex flex-col gap-3">
            <div>
                <label class="block text-xs font-bold text-[#333] mb-1">Material Name:</label>
                <input type="text" id="new_material_name" onkeyup="generateSlug(this.value)" class="w-full h-[34px] border border-[#ccc] rounded px-2.5 text-[13px] focus:outline-none focus:border-[#d97824]" placeholder="e.g. Cotton Fabric">
            </div>

            <div>
                <label class="block text-xs font-bold text-[#333] mb-1">Material Code:</label>
                <input type="text" id="new_material_slug" class="w-full h-[34px] border border-[#ccc] rounded px-2.5 text-[13px] bg-gray-50 focus:outline-none focus:border-[#d97824]" placeholder="e.g. cotton-fabric">
            </div>

            <div class="flex gap-3">
                <div class="flex-1">
                    <label class="block text-xs font-bold text-[#333] mb-1">Active:</label>
                    <select id="new_is_active" class="w-full h-[34px] border border-[#ccc] rounded px-2 text-[13px] focus:outline-none focus:border-[#d97824]">
                        <option value="1" selected>Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>

                <div class="flex-1">
                    <label class="block text-xs font-bold text-[#333] mb-1">Display Order:</label>
                    <input type="number" id="new_display_order" class="w-full h-[34px] border border-[#ccc] rounded px-2.5 text-[13px] focus:outline-none focus:border-[#d97824]" placeholder="Loading...">
                </div>
            </div>
            
            <p id="material_error" class="text-red-500 text-xs mt-1 hidden"></p>
        </div>
        
        <div class="flex gap-3 justify-end mt-5">
            <button type="button" onclick="closeMaterialModal()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm font-semibold hover:bg-gray-300 transition">Cancel</button>
            <button type="button" onclick="saveNewMaterial()" class="bg-[#d97824] text-white px-4 py-2 rounded text-sm font-semibold hover:bg-[#bf7326] transition">Save</button>
        </div>
    </div>
</div>
<script>
    // 1. Open Modal & Fetch Next Order
    function openMaterialModal() {
        const modal = document.getElementById('materialModal');
        modal.classList.remove('hidden');
        
        // Reset fields
        document.getElementById('new_material_name').value = '';
        document.getElementById('new_material_slug').value = '';
        document.getElementById('new_is_active').value = '1';
        document.getElementById('material_error').classList.add('hidden');
        
        // Fetch Next Display Order
        fetch('?page=inbounding&action=getNextMaterialOrderAjax')
            .then(res => res.json())
            .then(data => {
                document.getElementById('new_display_order').value = data.next_order;
            })
            .catch(err => console.error("Error fetching order:", err));

        document.getElementById('new_material_name').focus();
    }

    function closeMaterialModal() {
        document.getElementById('materialModal').classList.add('hidden');
    }

    // 2. Helper: Generate Slug automatically
    function generateSlug(text) {
        const slug = text.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '') // Remove invalid chars
            .trim()
            .replace(/\s+/g, '-');        // Replace spaces with -
        document.getElementById('new_material_slug').value = slug;
    }

    // 3. Save Function
    function saveNewMaterial() {
        const nameVal   = document.getElementById('new_material_name').value.trim();
        const slugVal   = document.getElementById('new_material_slug').value.trim();
        const activeVal = document.getElementById('new_is_active').value;
        const orderVal  = document.getElementById('new_display_order').value;
        const userId    = document.getElementById('new_user_id').value;
        const errorMsg  = document.getElementById('material_error');

        if (!nameVal) {
            errorMsg.innerText = "Material name is required.";
            errorMsg.classList.remove('hidden');
            return;
        }

        const btn = event.target;
        const originalText = btn.innerText;
        btn.innerText = "Saving...";
        btn.disabled = true;

        const formData = new FormData();
        formData.append('material_name', nameVal);
        formData.append('material_slug', slugVal);
        formData.append('is_active', activeVal);
        formData.append('display_order', orderVal);
        formData.append('user_id', userId);

        fetch('<?php echo base_url("?page=inbounding&action=addMaterialAjax"); ?>', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Add to TomSelect
                const ts = document.getElementById('material_select').tomselect;
                if(ts) {
                    ts.addOption({value: data.id, text: data.name});
                    ts.addItem(data.id);
                }
                closeMaterialModal();
            } else {
                errorMsg.innerText = data.message;
                errorMsg.classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            errorMsg.innerText = "An error occurred.";
            errorMsg.classList.remove('hidden');
        })
        .finally(() => {
            btn.innerText = originalText;
            btn.disabled = false;
        });
    }
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select ALL variation grids
    const grids = document.querySelectorAll('.photo-group-grid');
    let draggedItem = null;
    let sourceGrid = null;

    if (grids.length === 0) return;

    // Apply logic to every grid
    grids.forEach(grid => {
        // Init existing items
        const items = grid.querySelectorAll('.draggable-item');
        items.forEach(setupDragEvents);

        // Grid-level events (Drop Zone)
        grid.addEventListener('dragover', handleDragOver);
        grid.addEventListener('drop', handleDrop);
    });

    function setupDragEvents(item) {
        item.addEventListener('dragstart', handleDragStart);
        item.addEventListener('dragend', handleDragEnd);
    }

    function handleDragStart(e) {
        draggedItem = this;
        sourceGrid = this.parentNode;
        this.style.opacity = '0.4';
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', this.innerHTML);
    }

    function handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        return false;
    }

    function handleDrop(e) {
        e.stopPropagation();
        e.preventDefault();

        // Find which grid we dropped into
        const targetGrid = e.target.closest('.photo-group-grid');

        if (draggedItem && targetGrid) {
            
            // 1. Move the Element
            const afterElement = getDragAfterElement(targetGrid, e.clientY, e.clientX);
            if (afterElement == null) {
                targetGrid.appendChild(draggedItem);
            } else {
                targetGrid.insertBefore(draggedItem, afterElement);
            }

            // 2. UPDATE VARIATION ID (CRITICAL FIX)
            // Get the ID of the grid we dropped into (-1, 101, 102, etc.)
            const newVarId = targetGrid.getAttribute('data-var-id');
            const varInput = draggedItem.querySelector('.variation-input');
            if(varInput) {
                varInput.value = newVarId; // Update hidden input value
            }

            // 3. Recalculate Orders
            updateOrderInputs(sourceGrid);
            if (sourceGrid !== targetGrid) {
                updateOrderInputs(targetGrid);
            }
        }
        return false;
    }

    function handleDragEnd(e) {
        this.style.opacity = '1';
        draggedItem = null;
        sourceGrid = null;
    }

    function getDragAfterElement(container, y, x) {
        const draggableElements = [...container.querySelectorAll('.draggable-item:not(.dragging)')];

        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            // Logic for grid layout: approximate based on center point
            const boxCenterY = box.top + box.height / 2;
            const boxCenterX = box.left + box.width / 2;
            
            // Simple distance check usually works best for flexible grids
            const offset = y - boxCenterY; // Primary check on Y
            
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    function updateOrderInputs(gridContainer) {
        if(!gridContainer) return;
        const currentItems = gridContainer.querySelectorAll('.draggable-item');
        currentItems.forEach((item, index) => {
            const input = item.querySelector('.order-input');
            if(input) input.value = index + 1;
        });
    }
});
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
    const apiUrl = '/index.php?page=inbounding&action=getItamcode';
    const formElement = document.querySelector('form');
    const variantSelect = document.getElementById('variant_select');
    const wrapperSelect = document.getElementById('wrapper_select');
    const wrapperInput  = document.getElementById('wrapper_input');
    const fixedInput    = document.getElementById('fixed_item_code_input'); 
    const selectElement = document.getElementById('item_code_select');
    const existingCode  = document.getElementById('existing_item_code').value;
    const originalStatus = document.getElementById('original_variant_status').value;

    // --- INITIALIZE TOM SELECT FOR PARENT ITEM ---
    let tomSelectInstance = new TomSelect("#item_code_select", {
        valueField: 'item_code',
        labelField: 'title',
        searchField: ['item_code', 'title'], 
        maxItems: 1,
        create: false,
        preload: true,
        render: {
            option: function(data, escape) {
                return '<div class="py-1 px-2 flex flex-col">' +
                        '<span class="font-bold text-gray-800">' + escape(data.item_code) + '</span>' +
                        '<span class="text-gray-500 text-xs">' + escape(data.title) + '</span>' +
                    '</div>';
            },
            item: function(data, escape) {
                if (data.title === data.item_code) return '<div>' + escape(data.item_code) + '</div>';
                if (data.title.indexOf(data.item_code) === 0) return '<div>' + escape(data.title) + '</div>';
                return '<div>' + escape(data.item_code) + ' - ' + escape(data.title) + '</div>';
            }
        },
        load: function(query, callback) {
            fetch(apiUrl)
                .then(response => response.json())
                .then(json => { callback(json); })
                .catch(err => { console.error("Error loading items:", err); callback(); });
        }
    });

    // --- VARIANT TOGGLE ---
    function toggleVariantFields(val) {
        if (val === 'Y') {
            wrapperSelect.style.display = 'block';
            wrapperInput.style.display  = 'none';
            tomSelectInstance.enable(); 
            selectElement.disabled = false; 
            fixedInput.disabled = true;
        } else if (val === 'N') {
            wrapperSelect.style.display = 'none';
            wrapperInput.style.display  = 'block';
            tomSelectInstance.disable();
            selectElement.disabled = true;
            fixedInput.disabled = false;
            if (originalStatus === 'Y') {
                fixedInput.value = ""; 
                fixedInput.placeholder = "Auto-generated on Save";
            }
        }
    }

    variantSelect.addEventListener('change', function() {
        toggleVariantFields(this.value);
        if(this.value === 'N') {
            tomSelectInstance.clear(); 
        }
    });

    // --- FORM VALIDATION ---
    formElement.addEventListener('submit', function(e) {
        // 1. Auto-Gen Cleanup
        if(variantSelect.value === 'N' && fixedInput.value === "Auto-generated on Save") {
            fixedInput.value = ""; 
        }
        // 2. Strict Check for Variant Yes
        if (variantSelect.value === 'Y') {
            const selectedVal = tomSelectInstance.getValue();
            if (!selectedVal || selectedVal === "") {
                e.preventDefault(); 
                e.stopPropagation(); 
                alert("⛔ Error: You selected Variant 'Yes' but left the Parent Item Code blank.\n\nPlease search and select a parent item.");
                wrapperSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
                tomSelectInstance.focus();
                return false;
            }
        }
    });

    // --- INITIAL LOAD CHECK ---
    if(variantSelect.value) {
        toggleVariantFields(variantSelect.value);
    } else {
        fixedInput.disabled = true; 
        selectElement.disabled = true;
    }
});
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const commonConfig = {
            create: false,
            sortField: { field: "text", direction: "asc" },
            onInitialize: function() { this.wrapper.classList.add('w-full'); }
        };
        new TomSelect("#vendor_code", commonConfig);
        new TomSelect("#received_by_select", commonConfig);
        new TomSelect("#updated_by_select", commonConfig);
        new TomSelect("#material_select", config);
        new TomSelect("#variant_select", commonConfig);
    });
</script>

<script>
    // 1. Preview Function (Updates Image & Download Link)
    function previewInvoice(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();            
            reader.onload = function(e) {
                // Update Image Source
                const img = document.getElementById('invoice_img_tag');
                img.src = e.target.result;
                
                // Show the Preview Container
                document.getElementById('invoice_preview_container').classList.remove('hidden');
                
                // Update Download Link (Use the Base64 data for immediate download)
                const dlBtn = document.getElementById('invoice_download_btn');
                dlBtn.href = e.target.result;
                dlBtn.download = "New_Invoice_Upload.jpg"; // Temporary name
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // 2. Open Invoice in the Global Popup
    function openInvoicePopup() {
        const imgSrc = document.getElementById('invoice_img_tag').src;
        if(imgSrc && imgSrc !== window.location.href) { // Basic check it's not empty
            openImagePopup(imgSrc); // Reuse your global function
        }
    }

    // 3. Trigger Delete Popup
    function openDeletePopup(event) {
        event.stopPropagation(); 
        const popup = document.getElementById('deleteConfirmPopup');
        popup.classList.remove('hidden');
        const input = document.getElementById('deleteConfirmationInput');
        input.value = '';
        input.focus();
    }

    // 4. Confirm Delete Action
    function confirmDeleteInvoice() {
        const inputVal = document.getElementById('deleteConfirmationInput').value;
        if (inputVal === 'Delete') {
            // Clear File Input
            document.getElementById('invoice_input').value = ""; 
            
            // Clear Image Source
            document.getElementById('invoice_img_tag').src = ""; 
            
            // Hide Container
            document.getElementById('invoice_preview_container').classList.add('hidden');
            
            // Close Popup
            closeDeletePopup();
        } else {
            alert("Please type 'Delete' exactly to confirm.");
        }
    }

    // 5. Close Delete Popup
    function closeDeletePopup() {
        document.getElementById('deleteConfirmPopup').classList.add('hidden');
    }
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. DATA FROM PHP
    const categoriesByParent = <?php echo json_encode($categoriesByParent1); ?>;
    
    // 2. Pre-selection Data
    // These sets now contain STRINGS (e.g., "mens_wear", "shirts") from your DB
    const rawCatId = "<?php echo $selected_cat_id; ?>"; 
    const preSelected = {
        groupVal: "<?php echo $selected_group_val; ?>",
        cat:    new Set(rawCatId ? rawCatId.split(',') : []), 
        sub:    new Set(<?php echo json_encode($selected_sub); ?>.map(String)), 
        subsub: new Set(<?php echo json_encode($selected_sub_sub); ?>.map(String))
    };

    // 3. DOM Elements
    const categoryContainer = document.getElementById('category_container');
    const subCatContainer = document.getElementById('sub_category_container');
    const subSubCatContainer = document.getElementById('sub_sub_category_container');
    
    // 4. Init TomSelect
    const config = { create: false, sortField: { field: "text", direction: "asc" }, controlInput: null };
    if(document.getElementById("material_select")) new TomSelect("#material_select", config);
    
    const groupSelectEl = document.getElementById("group_select");
    let groupTs = null;
    if(groupSelectEl) {
        groupTs = new TomSelect(groupSelectEl, config);
    }

    // --- HELPER: Create Checkbox ---
    function createCheckboxItem(item, inputName, selectedSet, onChangeCallback) {
        const div = document.createElement('div');
        div.className = 'checkbox-item pl-2'; 
        
        // CHANGE 1: Check if the STORE VALUE (String) is in the selected set
        // This ensures pre-filled data works with strings like "mens_wear"
        const valToCheck = String(item.store_val);
        const isChecked = selectedSet.has(valToCheck) ? 'checked' : '';
        
        // CHANGE 2: Set 'value' to item.store_val
        // This ensures "mens_wear" is sent to the DB on save
        div.innerHTML = `
            <input type="checkbox" 
                   id="${inputName}_${item.id}" 
                   name="${inputName}[]" 
                   value="${item.store_val}" 
                   data-parent-path="" 
                   ${isChecked}>
            <label for="${inputName}_${item.id}">${item.name}</label>
        `;
        
        const checkbox = div.querySelector('input');
        checkbox.addEventListener('change', () => { if(onChangeCallback) onChangeCallback(); });
        return div;
    }

    // --- 1. UPDATE CATEGORY LIST (Level 1) ---
    function updateCategoryList(rawGroupValue) {
        subCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Category...</div>';
        subSubCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Sub Category...</div>';

        if(!rawGroupValue) {
             categoryContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Group...</div>';
             return;
        }

        const lookupKey = String(rawGroupValue).trim(); 
        
        if(!categoriesByParent[lookupKey]) {
            categoryContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">No categories found</div>';
            return;
        }

        const items = categoriesByParent[lookupKey];
        categoryContainer.innerHTML = '';
        const seenIds = new Set();
        items.forEach(item => {
            if(!seenIds.has(item.id)){
                seenIds.add(item.id);
                categoryContainer.appendChild(createCheckboxItem(item, 'category_code', preSelected.cat, handleCategoryChange));
            }
        });
        
        if(preSelected.cat.size > 0) handleCategoryChange();
    }

    // --- 2. UPDATE SUB CATEGORY LIST (Level 2) ---
    function handleCategoryChange() {
        const checkedInputs = Array.from(categoryContainer.querySelectorAll('input[type="checkbox"]:checked'));
        subCatContainer.innerHTML = ''; 
        subSubCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Sub Category...</div>';

        if(checkedInputs.length === 0) {
            subCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Category...</div>';
            return;
        }

        const groupVal = groupTs ? groupTs.getValue() : document.getElementById("group_select").value;
        const groupKey = String(groupVal).trim(); 

        let hasAnyOptions = false;

        checkedInputs.forEach(input => {
            // CHANGE 3: Use input.value directly (which is now the string "mens_wear")
            const catStoreVal = input.value; 
            const parentName = input.nextElementSibling.innerText;
            
            // PATH LOGIC: "mens_wear|clothing"
            const lookupKey = catStoreVal + "|" + groupKey;
            
            const children = categoriesByParent[lookupKey];

            if (children && children.length > 0) {
                hasAnyOptions = true;
                const header = document.createElement('div');
                header.className = "text-[11px] font-bold text-[#d97824] bg-gray-50 px-2 py-1 border-b border-t border-gray-200 mt-0 sticky top-0 z-10 group-header";
                header.innerText = parentName; 
                subCatContainer.appendChild(header);

                children.forEach(item => {
                    const el = createCheckboxItem(item, 'sub_category_code', preSelected.sub, handleSubCategoryChange);
                    // Pass the path down to the next level
                    el.querySelector('input').setAttribute('data-parent-path', lookupKey);
                    subCatContainer.appendChild(el);
                });
            }
        });

        if(!hasAnyOptions) subCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">No Sub Categories found</div>';
        else if(preSelected.sub.size > 0) handleSubCategoryChange();
    }

    // --- 3. UPDATE SUB SUB CATEGORY LIST (Level 3) ---
    function handleSubCategoryChange() {
        const checkedInputs = Array.from(subCatContainer.querySelectorAll('input[type="checkbox"]:checked'));
        subSubCatContainer.innerHTML = ''; 

        if(checkedInputs.length === 0) {
            subSubCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select Sub Category...</div>';
            return;
        }

        let hasAnyOptions = false;

        checkedInputs.forEach(input => {
            // CHANGE 4: Use input.value (e.g. "shirts")
            const subCatStoreVal = input.value;
            const parentPath = input.getAttribute('data-parent-path'); 
            const parentName = input.nextElementSibling.innerText;
            
            // PATH LOGIC: "shirts|mens_wear|clothing"
            const lookupKey = subCatStoreVal + "|" + parentPath;
            
            const children = categoriesByParent[lookupKey];

            if (children && children.length > 0) {
                hasAnyOptions = true;
                const header = document.createElement('div');
                header.className = "text-[11px] font-bold text-[#d97824] bg-gray-50 px-2 py-1 border-b border-t border-gray-200 mt-0 sticky top-0 z-10 group-header";
                header.innerText = parentName;
                subSubCatContainer.appendChild(header);

                children.forEach(item => {
                    subSubCatContainer.appendChild(createCheckboxItem(item, 'sub_sub_category_code', preSelected.subsub, null));
                });
            }
        });

        if(!hasAnyOptions) subSubCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">No Sub Sub Categories found</div>';
    }
    
    // --- SEARCH FILTER ---
    function enableSearchFilter(inputId, containerId) {
        const input = document.getElementById(inputId);
        const container = document.getElementById(containerId);
        input.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const items = container.querySelectorAll('.checkbox-item');
            const headers = container.querySelectorAll('.group-header');
            items.forEach(item => {
                const label = item.querySelector('label').innerText;
                item.style.display = label.toLowerCase().includes(filter) ? 'flex' : 'none';
            });
            if(filter.length > 0) headers.forEach(h => h.style.display = 'none');
            else headers.forEach(h => h.style.display = 'block');
        });
    }

    // --- EVENTS ---
    if(groupTs) {
        groupTs.on('change', function(groupValue) {
            preSelected.cat.clear(); 
            preSelected.sub.clear(); 
            preSelected.subsub.clear();        
            updateCategoryList(groupValue);
        });
    }
    
    // --- INITIAL LOAD ---
    if (preSelected.groupVal) {
        updateCategoryList(preSelected.groupVal);
    }
    
    enableSearchFilter('sub_cat_search', 'sub_category_container');
    enableSearchFilter('sub_sub_cat_search', 'sub_sub_category_container');
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Select Inputs
    const heightInput = document.getElementById('dim_height');
    const widthInput  = document.getElementById('dim_width');
    const depthInput  = document.getElementById('dim_depth');
    const weightInput = document.getElementById('dim_weight'); 
    const dimUnitSelect = document.querySelector('select[name="dimention_unit"]');
    
    // Display Elements
    const displayElement = document.getElementById('courier_price_display');
    const detailsElement = document.getElementById('courier_calc_details');
    // New Element for Volumetric Weight
    const volDisplayElement = document.getElementById('volumetric_weight_display');

    function calculateCourierPrice() {
        if (!heightInput || !widthInput || !depthInput || !weightInput) return;

        // 1. Get Raw Values
        let h = parseFloat(heightInput.value) || 0;
        let w = parseFloat(widthInput.value) || 0;
        let d = parseFloat(depthInput.value) || 0;
        let actualWt = parseFloat(weightInput.value) || 0; // In KG
        const dimUnit = dimUnitSelect ? dimUnitSelect.value : 'cm';

        // --- STEP A: NORMALIZE TO INCHES ---
        // If user entered CM, convert to Inch first (divide by 2.54)
        // if (dimUnit === 'cm') {
             h = h / 2.54;
             w = w / 2.54;
             d = d / 2.54;
        // }

        // --- STEP B: ADD BUFFER (4 inches) ---
        let h_in = h + 4;
        let w_in = w + 4;
        let d_in = d + 4;

        // --- STEP C: CALCULATE VOLUMETRIC WEIGHT ---
        // Formula: (L x W x H in inches) / 5000
        const volWt = (h_in * w_in * d_in) / 5000;

        // --- STEP D: CALCULATE ADJUSTED ACTUAL WEIGHT (x 1.5) ---
        const adjustedActualWt = actualWt * 1.5;

        // --- STEP E: DETERMINE CHARGEABLE WEIGHT ---
        const chargeableWt = Math.max(volWt, adjustedActualWt);

        // --- STEP F: CALCULATE PRICE ---
        // Price: ₹700 per KG
        const price = chargeableWt * 700;

        // --- UPDATE UI ---
        
        // 1. Update Volumetric Weight (Compulsory)
        if (volDisplayElement) {
            volDisplayElement.innerText = volWt.toFixed(3) + " kg";
        }

        // 2. Update Price
        if (displayElement) {
            displayElement.innerText = "₹ " + price.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        
        // 3. Update Details text
        if (detailsElement) {
            const usedType = (volWt > adjustedActualWt) ? "Volumetric" : "Actual Weight";
            detailsElement.innerText = `Chargeable: ${chargeableWt.toFixed(3)} kg (${usedType})`;
        }
    }

    // Attach Listeners
    const inputs = [heightInput, widthInput, depthInput, weightInput, dimUnitSelect];
    inputs.forEach(input => {
        if(input) {
            input.addEventListener('input', calculateCourierPrice);
            input.addEventListener('change', calculateCourierPrice);
        }
    });

    // Run immediately on load
    calculateCourierPrice();
});
</script>

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
<script>
    document.addEventListener("DOMContentLoaded", function() {
        flatpickr(".date-picker", {
            altInput: true,         // Create a "fake" input for display
            altFormat: "d M Y",     // Display format: "21 Dec 2025"
            dateFormat: "Y-m-d",    // Save format (hidden value): "2025-12-21"
            allowInput: true        // Allow user to type manually if they want
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.addEventListener('keydown', function(e) {
            // Check if the key pressed is 'Enter'
            if (e.key === 'Enter') {
                // Allow Enter only in Textareas (for multi-line text) or on Buttons (to click them)
                if (e.target.nodeName === 'INPUT' || e.target.nodeName === 'SELECT') {
                    e.preventDefault();
                    return false;
                }
            }
        });
    });
</script>
<script>
    function toggleBackOrderFields() {
        const select = document.getElementById('back_order_select');
        const fields = document.querySelectorAll('.backorder-field');
        const inputs = document.querySelectorAll('.backorder-field input'); // Select the input fields

        if (select && select.value === '1') {
            fields.forEach(el => el.style.display = 'block');
        } else {
            fields.forEach(el => el.style.display = 'none');
            
            // NEW: Clear values immediately when switching to "No"
            inputs.forEach(input => input.value = ''); 
        }
    }
    // Run on load
    document.addEventListener('DOMContentLoaded', toggleBackOrderFields);
</script>
<script>
    // 1. Open Popup
    function openPublishPopup() {
        const popup = document.getElementById('publishConfirmPopup');
        if(popup) {
            popup.classList.remove('hidden');
        } else {
            console.error("Popup element 'publishConfirmPopup' not found!");
        }
    }

    // 2. Close Popup
    function closePublishPopup() {
        const popup = document.getElementById('publishConfirmPopup');
        if(popup) {
            popup.classList.add('hidden');
        }
    }

    // 3. Trigger Controller Function (No Form Submit)
    function triggerPublishController() {
        // Get the current ID from the URL (e.g. &id=123)
        const urlParams = new URLSearchParams(window.location.search);
        const recordId = urlParams.get('id');

        if (recordId) {
            // Redirect directly to the controller action
            // This is a GET request, NOT a form submission
            const targetUrl = `index.php?page=inbounding&action=inbound_product_publish&id=${recordId}`;
            
            // Visual feedback
            const btn = event.target;
            btn.innerText = "Processing...";
            btn.disabled = true;

            window.location.href = targetUrl;
        } else {
            alert("Error: Record ID not found in URL.");
            closePublishPopup();
        }
    }
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let newVariationCounter = 0;
    const container = document.getElementById('variations-container');
    const template = document.getElementById('variation-template');

    // 1. ADD NEW VARIATION FUNCTION
    window.addNewVariation = function() { 
        const newId = 'new_' + newVariationCounter;
        newVariationCounter++;

        const clone = template.content.cloneNode(true);
        updateNames(clone, newId);
        container.appendChild(clone);
    };

    // 2. EVENT DELEGATION
    container.addEventListener('click', function(e) {
        
        // --- REMOVE LOGIC ---
        if (e.target.closest('.remove-var-btn')) {
            const card = e.target.closest('.variation-card');
            if(card && confirm('Remove this variation card?')) {
                card.remove();
            }
        }

        // --- CLONE LOGIC ---
        if (e.target.closest('.clone-var-btn')) {
            const sourceCard = e.target.closest('.variation-card');
            
            // Generate new ID
            const newId = 'new_' + newVariationCounter;
            newVariationCounter++;

            // Create new card from template
            const cloneContent = template.content.cloneNode(true);
            const newCard = cloneContent.querySelector('.variation-card');
            
            // Helper to get value from source
            const getValue = (suffix) => {
                const el = sourceCard.querySelector(`[name$="[${suffix}]"]`);
                return el ? el.value : '';
            };

            // Update names in the new card first
            updateNames(newCard, newId);

            // Set values
            const fields = ['height', 'width', 'depth', 'weight', 'size', 'color', 
                            'quantity_received', 'cp', 'price_india', 'price_india_mrp', 
                            'inr_pricing', 'amazon_price', 'usd_price', 'hsn_code', 
                            'gst_rate', 'ware_house_code', 'store_location'];

            fields.forEach(field => {
                const el = newCard.querySelector(`[name$="[${field}]"]`);
                if(el) el.value = getValue(field);
            });

            // ---------------------------------------------------------
            // FIX: IMAGE CLONING LOGIC
            // ---------------------------------------------------------
            
            // 1. Get Source Elements
            const sourceImg = sourceCard.querySelector('.preview-img');
            const sourceHiddenOldPhoto = sourceCard.querySelector('input[name$="[old_photo]"]');
            
            // 2. Get Clone Elements
            const cloneImg = newCard.querySelector('.preview-img');
            const clonePlaceholder = newCard.querySelector('.placeholder-icon');
            const cloneHiddenOldPhoto = newCard.querySelector('input[name$="[old_photo]"]');

            // 3. Check visibility (Check both class AND inline display style)
            const isSourceVisible = sourceImg && 
                                   !sourceImg.classList.contains('hidden') && 
                                   sourceImg.style.display !== 'none';

            if (isSourceVisible) {
                // Copy the visual source (Base64 or URL)
                cloneImg.src = sourceImg.src;
                
                // FORCE VISIBILITY: Remove inline style 'display: none'
                cloneImg.style.display = 'block'; 
                cloneImg.classList.remove('hidden');
                
                // Hide Placeholder
                clonePlaceholder.style.display = 'none';
                clonePlaceholder.classList.add('hidden');

                // Copy database path if it exists
                if (sourceHiddenOldPhoto && cloneHiddenOldPhoto) {
                    cloneHiddenOldPhoto.value = sourceHiddenOldPhoto.value;
                }
            } else {
                // Reset if source has no image
                cloneImg.src = '#';
                cloneImg.style.display = 'none'; // Re-apply hiding
                clonePlaceholder.style.display = 'flex'; // Show placeholder
                if (cloneHiddenOldPhoto) cloneHiddenOldPhoto.value = '';
            }
            // ---------------------------------------------------------
            // END FIX
            // ---------------------------------------------------------

            container.appendChild(newCard);
            
            // Scroll to new item
            newCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Trigger calculation update
            if (typeof updateCardPrice === "function") {
                setTimeout(() => updateCardPrice(newCard), 100);
            }
        }
    });

    // 3. IMAGE PREVIEW LOGIC (For File Input Changes)
    container.addEventListener('change', function(e) {
        if(e.target.classList.contains('variation-file-input')) {
            const file = e.target.files[0];
            const label = e.target.closest('label');
            const preview = label.querySelector('.preview-img');
            const placeholder = label.querySelector('.placeholder-icon');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(evt) {
                    preview.src = evt.target.result;
                    
                    // FORCE VISIBILITY here too
                    preview.style.display = 'block';
                    preview.classList.remove('hidden');
                    
                    placeholder.style.display = 'none';
                    placeholder.classList.add('hidden');
                }
                reader.readAsDataURL(file);
            }
        }
    });

    function updateNames(node, newId) {
        const inputs = node.querySelectorAll('input, select');
        inputs.forEach(input => {
            const name = input.getAttribute('name');
            if(name) {
                input.setAttribute('name', name.replace('INDEX', newId));
            }
        });
    }
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Unified Calculator Function
    function updateCardPrice(card) {
        if (!card) return;

        // Find inputs within this specific card (or main container)
        // We look for specific classes: .calc-h, .calc-w, .calc-d, .calc-wt
        // NOTE: For the MAIN Item (top of page), you must add these classes to the inputs as well, 
        // OR we fallback to checking IDs if classes aren't found.
        
        let hInput = card.querySelector('.calc-h') || document.getElementById('dim_height');
        let wInput = card.querySelector('.calc-w') || document.getElementById('dim_width');
        let dInput = card.querySelector('.calc-d') || document.getElementById('dim_depth');
        let wtInput = card.querySelector('.calc-wt') || document.getElementById('dim_weight');

        // Output elements
        let volDisplay = card.querySelector('.calc-vol-display') || document.getElementById('volumetric_weight_display');
        let priceDisplay = card.querySelector('.calc-price-display') || document.getElementById('courier_price_display');
        
        // If we are in a variation card, strictly use internal elements
        if (card.classList.contains('calculation-card')) {
             hInput = card.querySelector('.calc-h');
             wInput = card.querySelector('.calc-w');
             dInput = card.querySelector('.calc-d');
             wtInput = card.querySelector('.calc-wt');
             volDisplay = card.querySelector('.calc-vol-display');
             priceDisplay = card.querySelector('.calc-price-display');
        }

        if (!hInput || !wInput || !dInput || !wtInput) return;

        // Get Values
        let h = parseFloat(hInput.value) || 0;
        let w = parseFloat(wInput.value) || 0;
        let d = parseFloat(dInput.value) || 0;
        let actualWt = parseFloat(wtInput.value) || 0;

        // Logic: Add 4 inches buffer + Volumetric Divisor 5000

         h = h / 2.54;
         w = w / 2.54;
         d = d / 2.54;
        let h_in = h + 4;
        let w_in = w + 4;
        let d_in = d + 4;

        // Volumetric Weight
        const volWt = (h_in * w_in * d_in) / 5000;

        // Chargeable Weight = Max(Volumetric, Actual * 1.5)
        const adjustedActualWt = actualWt * 1.5;
        const chargeableWt = Math.max(volWt, adjustedActualWt);

        // Price = Chargeable * 700
        const price = chargeableWt * 700;

        // Update UI
        if (volDisplay) volDisplay.innerText = volWt.toFixed(3) + " kg";
        if (priceDisplay) priceDisplay.innerText = "₹ " + price.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // 2. Event Delegation for Inputs
    // We attach listener to the whole document/body to catch events from dynamic elements
    document.body.addEventListener('input', function(e) {
        if (e.target.matches('.calc-h, .calc-w, .calc-d, .calc-wt') || 
            e.target.id === 'dim_height' || 
            e.target.id === 'dim_width' || 
            e.target.id === 'dim_depth' || 
            e.target.id === 'dim_weight') {
            
            // Find the closest container (Variation Card OR Main Item Container)
            // Assuming Main Item container has class 'bg-gray-50' or similar wrapper
            const card = e.target.closest('.calculation-card') || e.target.closest('.bg-gray-50');
            updateCardPrice(card);
        }
    });

    // 3. Initialize all existing cards on load
    const allCards = document.querySelectorAll('.calculation-card, .bg-gray-50');
    allCards.forEach(card => updateCardPrice(card));
});
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mainInput = document.getElementById('product_photo_input');
        const mainPreview = document.getElementById('main_photo_preview');

        if(mainInput) {
            mainInput.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(evt) {
                        mainPreview.src = evt.target.result;
                        mainPreview.classList.remove('hidden');
                    }
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mainInput = document.getElementById('product_photo_main_input');
        const mainPreview = document.getElementById('main_photo_preview');
        const mainPlaceholder = document.getElementById('main_photo_placeholder');
        const mainChangeBtn = document.getElementById('main_photo_change_btn');

        if(mainInput) {
            mainInput.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(evt) {
                        // Update Source
                        mainPreview.src = evt.target.result;
                        
                        // Show Image, Hide Placeholder, Show Button
                        mainPreview.style.display = 'block';
                        mainPlaceholder.style.display = 'none';
                        mainChangeBtn.style.display = 'block';
                    }
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Use Event Delegation to handle Main Product, Existing Variations, and New Variations
        document.body.addEventListener('input', function(e) {
            
            // 1. Check if the modified input is an "inr_pricing" field
            // We check if the name contains 'inr_pricing' to cover both:
            // Main: name="inr_pricing"
            // Variations: name="variations[x][inr_pricing]"
            if (e.target.name && e.target.name.includes('inr_pricing')) {
                
                const inrInput = e.target;
                const inrValue = parseFloat(inrInput.value);
                
                // 2. Find the container that holds this specific group of inputs
                // Both the main form and variation cards use a grid layout.
                // We look for the closest parent with class 'grid' or 'calculation-card'
                const container = inrInput.closest('.grid') || inrInput.closest('.calculation-card');

                if (container) {
                    // 3. Find the Amazon Price input WITHIN this specific container
                    // This ensures we only update the Amazon price for the specific variation being edited
                    const amazonInput = container.querySelector('input[name*="amazon_price"]');

                    if (amazonInput) {
                        if (!isNaN(inrValue)) {
                            // Calculate 20% increase
                            const calculatedPrice = inrValue + (inrValue * 0.20);
                            // Update the Amazon field (rounded to 2 decimal places)
                            amazonInput.value = calculatedPrice.toFixed(2);
                        } else {
                            // If INR is cleared/invalid, clear Amazon price
                            amazonInput.value = '';
                        }
                    }
                }
            }
        });
    });
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. DATA FROM PHP
    const sizeOptionsObj = <?php echo json_encode($sizeOptions); ?>;
    
    // Generate Options HTML once
    let sizeOptionsHTML = '<option value="">Select Size</option>';
    for (const [key, value] of Object.entries(sizeOptionsObj)) {
        sizeOptionsHTML += `<option value="${key}">${value}</option>`;
    }

    // 2. CHECK IF CATEGORY IS CLOTHING
    function checkIsClothing() {
        let isClothing = false;

        // Check Group Dropdown Text
        const groupSelect = document.getElementById('group_select');
        if (groupSelect && groupSelect.selectedIndex > 0) {
            const groupText = groupSelect.options[groupSelect.selectedIndex].text.toLowerCase();
            if (groupText.includes('clothing') || groupText.includes('textile')) isClothing = true;
        }

        // Check Selected Checkboxes (Category, Sub, SubSub)
        // We look for any checked box with "clothing" or "textile" in its label
        const checkedBoxes = document.querySelectorAll('.checkbox-list-container input[type="checkbox"]:checked');
        checkedBoxes.forEach(cb => {
            const label = cb.nextElementSibling ? cb.nextElementSibling.innerText.toLowerCase() : '';
            if (label.includes('clothing') || label.includes('textile')) isClothing = true;
        });

        return isClothing;
    }

    // 3. TOGGLE FUNCTION
    function toggleAllSizeFields() {
        const isClothing = checkIsClothing();
        
        // Find ALL size inputs (Main + Variations) using the class we added in PHP
        const allSizeInputs = document.querySelectorAll('.size-input-field');

        allSizeInputs.forEach(field => {
            const parent = field.parentElement;
            const currentTag = field.tagName; // 'SELECT' or 'INPUT'
            const currentValue = field.value;
            const currentName = field.name;

            // Logic to prevent unnecessary swapping
            if (isClothing && currentTag === 'SELECT') return;
            if (!isClothing && currentTag === 'INPUT') return;

            // Create New Element
            let newEl;
            if (isClothing) {
                newEl = document.createElement('select');
                newEl.innerHTML = sizeOptionsHTML;
                // Try to set value if it exists in options
                newEl.value = currentValue; 
            } else {
                newEl = document.createElement('input');
                newEl.type = 'text';
                newEl.value = currentValue; // Keep text value
            }

            // Copy Attributes & Classes
            newEl.name = currentName;
            newEl.className = field.className; // Keeps styling

            // Swap
            field.remove();
            parent.appendChild(newEl);
        });
    }

    // 4. LISTENERS
    // Listen to Group Change
    const groupSelect = document.getElementById('group_select');
    if(groupSelect) {
        groupSelect.addEventListener('change', toggleAllSizeFields); // Uses TomSelect change event if native
    }
    // Since you use TomSelect for group, we hook into it if accessible, 
    // but the native select usually updates hiddenly. 
    // If TomSelect hides the native select, we need to listen to TomSelect's event.
    if(document.getElementById('group_select').tomselect) {
        document.getElementById('group_select').tomselect.on('change', toggleAllSizeFields);
    }

    // Listen to Checkbox Changes (Delegation for dynamic lists)
    document.body.addEventListener('change', function(e) {
        if (e.target.type === 'checkbox' && e.target.closest('.checkbox-list-container')) {
            toggleAllSizeFields();
        }
    });

    // 5. HOOK INTO "ADD NEW VARIATION"
    // We need to override or extend the existing addNewVariation function 
    // to ensure new cards get the correct input type immediately.
    const originalAddVar = window.addNewVariation;
    window.addNewVariation = function() {
        // Run original function
        originalAddVar(); 
        
        // After adding, force a check to update the newly added field
        toggleAllSizeFields();
    };
});
</script>