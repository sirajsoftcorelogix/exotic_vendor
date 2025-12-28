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
?>

<div class="w-full max-w-[1200px] mx-auto p-2 md:p-5 font-['Segoe_UI',Tahoma,Geneva,Verdana,sans-serif] text-[#333]">
    <form action="<?php echo base_url('?page=inbounding&action=updatedesktopform&id='.$record_id); ?>" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="userid_log" value="<?php echo $_SESSION['user']['id'] ?? ''; ?>">
        <div class="flex flex-col md:flex-row items-stretch w-full gap-4 md:gap-0">
            
            <div class="shrink-0 w-full md:w-[150px] bg-[#f4f4f4] border border-[#777] rounded-md p-1 md:ml-5 relative h-[200px] md:h-auto">
                <img src="<?php echo base_url($data['form2']['product_photo']); ?>" class="w-full h-full object-contain md:object-cover rounded-[3px] block bg-[#ddd]" onclick="openImagePopup('<?= $data['form2']['product_photo'] ?>')">
            </div>

            <fieldset class="grow border border-[#ccc] rounded-[5px] px-3 md:px-5 pt-[15px] pb-5 bg-white md:ml-2.5 md:mr-5">
                <legend class="text-sm font-bold text-[#333] px-[5px]">Item Linking</legend>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 md:gap-[30px] mb-[15px] items-end">
                    <div class="flex flex-col">
                        <label class="text-xs font-bold text-[#333] mb-1.5">Variant:</label>
                        <select id="variant_select" name="is_variant" 
                                class="h-[36px] text-[13px] border border-[#ccc] rounded px-2.5 text-[#333] w-full focus:outline-none focus:border-[#999]">
                            <option value="" disabled <?php echo empty($data['form2']['is_variant']) ? 'selected' : ''; ?>>Select...</option>
                            <option value="Y" <?php echo (isset($data['form2']['is_variant']) && $data['form2']['is_variant'] === 'Y') ? 'selected' : ''; ?>>Yes</option>
                            <option value="N" <?php echo (isset($data['form2']['is_variant']) && $data['form2']['is_variant'] === 'N') ? 'selected' : ''; ?>>No</option>
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

                        <input type="text" 
                               class="date-picker h-[36px] text-[13px] border border-[#ccc] rounded px-2.5 text-[#333] w-full focus:outline-none focus:border-[#999] bg-white" 
                               value="<?php echo $dateValue; ?>" 
                               name="stock_added_date">
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
        <fieldset class="border border-[#ccc] rounded-[5px] px-5 py-[15px] bg-white">
            <legend class="text-[13px] font-bold text-[#333] px-[5px]">Item Photos (Grouped by Variation)</legend>

            <div class="mb-6">
                <h4 class="text-xs font-bold text-[#d97824] uppercase">
                    Base Variant(<?= htmlspecialchars($data['form2']['color'] ?? '') ?>-<?= htmlspecialchars($data['form2']['size'] ?? '') ?>)
                </h4>
                <div class="photo-group-grid grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 min-h-[80px] p-2 border border-dashed border-gray-200 rounded bg-gray-50/50" 
                     data-var-id="-1">
                    <?php 
                    if (!empty($grouped_images['-1'])) {
                        foreach($grouped_images['-1'] as $img) { renderPhotoCard($img, '-1'); }
                    }
                    ?>
                </div>
            </div>

            <?php foreach ($variations as $var): ?>
                <div class="mb-6">
                    <div class="flex items-center gap-2 border-b border-gray-200 pb-1 mb-3">
                        <?php if(!empty($var['variation_image'])): ?>
                            <img src="<?= base_url($var['variation_image']) ?>" class="w-6 h-6 rounded object-cover border border-gray-300">
                        <?php endif; ?>
                        <h4 class="text-xs font-bold text-[#d97824] uppercase">
                            <?= htmlspecialchars($var['color'] ?? '') ?> - <?= htmlspecialchars($var['size'] ?? '') ?>
                        </h4>
                    </div>
                    
                    <div class="photo-group-grid grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 min-h-[80px] p-2 border border-dashed border-gray-200 rounded bg-gray-50/50" 
                         data-var-id="<?= $var['id'] ?>">
                        <?php 
                        if (!empty($grouped_images[$var['id']])) {
                            foreach($grouped_images[$var['id']] as $img) { renderPhotoCard($img, $var['id']); }
                        }
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>

        </fieldset>
    </div>

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
                        <span class="text-[11px] font-bold text-[#222] mb-[3px]">Updated by:</span>
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
            $groupMap = [];
            if (!empty($data['category'])) {
                foreach ($data['category'] as $row) {
                    if (isset($row['is_active']) && $row['is_active'] != 1) { continue; }
                    $categoriesByParent1[$row['parent_id']][] = [
                        'id'    => $row['id'],
                        'name' => $row['display_name']
                    ];
                    if ($row['parent_id'] == 0) {
                        $storeValue = $row['category'] ?? $row['display_name'];
                        $rootCategories[] = [
                            'id'          => $row['id'],
                            'name'        => $row['display_name'],
                            'store_value' => $storeValue 
                        ];
                        $groupMap[$storeValue] = $row['id'];
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
                            <div id="sub_category_container" class="checkbox-list-container overflow-y-auto p-1 h-full">
                                <div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Category to view options</div>
                            </div>
                        </div>
                    </div>

                    <div class="w-full md:w-1/3 flex flex-col">
                        <label class="block text-xs font-bold text-[#222] mb-1">SubSubCategory:</label>
                        <div class="border border-[#ccc] rounded-[4px] bg-white flex-grow h-[200px] flex flex-col">
                            <div id="sub_sub_category_container" class="checkbox-list-container overflow-y-auto p-1 h-full">
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
                <div class="">
                    <label class="block text-xs font-bold text-[#222] mb-[5px]">Description Icons:</label>
                    
                    <div class="border border-[#ccc] rounded-[4px] bg-white h-[200px] flex flex-col">
                        
                        <div class="checkbox-list-container overflow-y-auto p-1 h-full">
                            <?php 
                                $icon_options = $data['form2']['icon_data']['description_icons'] ?? [];
                                $saved_raw = $data['form2']['description_icons'] ?? ''; 
                                $saved_values = is_array($saved_raw) ? $saved_raw : explode(',', $saved_raw);

                                if (!empty($icon_options)) {
                                    foreach ($icon_options as $key => $label) {
                                        $isChecked = in_array($key, $saved_values) ? 'checked' : '';
                                        $uniqueId = 'icon_' . $key; 
                            ?>
                                    <div class="checkbox-item">
                                        <input type="checkbox" 
                                               id="<?= $uniqueId ?>" 
                                               name="description_icons[]" 
                                               value="<?= $key ?>" 
                                               <?= $isChecked ?>>
                                            
                                        <label for="<?= $uniqueId ?>"><?= $label ?></label>
                                    </div>
                            <?php 
                                    }
                                } else {
                                    echo '<div class="text-xs text-gray-400 p-2 text-center mt-5">No icons available</div>';
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

        <div class="mt-[15px] md:mx-5">
            <fieldset class="border border-[#ccc] rounded-[5px] px-5 py-[15px] bg-white">
                <legend class="text-[13px] font-bold text-[#333] px-[5px]">Pricing:</legend>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 items-start mb-[15px]">
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">INR Price:</label>
                        <div class="relative flex items-center w-full">
                            <input type="text" class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[40px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" value="<?= htmlspecialchars($data['form2']['inr_pricing'] ?? '') ?>" name = "inr_pricing">
                            <span class="absolute right-[10px] text-xs text-[#777] pointer-events-none">INR</span>
                        </div>
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">CP:</label>
                        <div class="relative flex items-center w-full">
                            <input type="text" class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[40px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" value="<?= htmlspecialchars($data['form2']['cp'] ?? '') ?>" name = "cp">
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
                <legend class="text-[13px] font-bold text-[#333] px-[5px]">Dimensions:</legend>
                
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 items-start mb-[15px]">
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Height:</label>
                        <div class="relative flex items-center w-full">
                            <input type="text" id="dim_height" class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[40px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" value="<?= htmlspecialchars($data['form2']['height'] ?? '') ?>" name="height">
                            <span class="absolute right-[10px] text-xs text-[#777] pointer-events-none">inch</span>
                        </div>
                    </div>
                     <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Width:</label>
                        <div class="relative flex items-center w-full">
                            <input type="text" id="dim_width" class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[40px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" value="<?= htmlspecialchars($data['form2']['width'] ?? '') ?>" name="width">
                            <span class="absolute right-[10px] text-xs text-[#777] pointer-events-none">inch</span>
                        </div>
                    </div>
                     <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Depth:</label>
                        <div class="relative flex items-center w-full">
                            <input type="text" id="dim_depth" class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[40px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" value="<?= htmlspecialchars($data['form2']['depth'] ?? '') ?>" name="depth">
                            <span class="absolute right-[10px] text-xs text-[#777] pointer-events-none">inch</span>
                        </div>
                    </div>
                     <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Weight:</label>
                        <div class="relative flex items-center w-full">
                            <input type="text" id="dim_weight" class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[40px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" value="<?= htmlspecialchars($data['form2']['weight'] ?? '') ?>" name="weight">
                            <span class="absolute right-[10px] text-xs text-[#777] pointer-events-none">kg</span>
                        </div>
                    </div>
                     <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Size:</label>
                        <input type="text" class="w-full h-[32px] border border-[#ccc] rounded-[3px] px-[10px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" value="<?= htmlspecialchars($data['form2']['size'] ?? '') ?>" name="size">
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Colour:</label>
                        <input type="text" class="w-full h-[32px] border border-[#ccc] rounded-[3px] px-[10px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" value="<?= htmlspecialchars($data['form2']['color'] ?? '') ?>" name="color">
                    </div>
                </div>

                <div class="flex justify-end items-stretch border-t border-dashed border-[#ddd] pt-3 mt-2 gap-3">
                    
                    <div class="text-right bg-gray-50 p-2 rounded border border-gray-200 min-w-[120px]">
                        <span class="text-xs font-bold text-gray-500 block">Volumetric Weight</span>
                        <div class="text-lg font-bold text-[#555]" id="volumetric_weight_display">0.000 kg</div>
                    </div>

                    <div class="text-right bg-gray-50 p-2 rounded border border-gray-200 min-w-[180px]">
                        <span class="text-xs font-bold text-gray-500 block">Est. Courier Price (₹700/kg)</span>
                        <div class="text-lg font-bold text-[#d97824]" id="courier_price_display">₹ 0.00</div>
                        <div class="text-[10px] text-gray-400" id="courier_calc_details">Enter dimensions to calculate</div>
                    </div>

                </div>

            </fieldset>
        </div>

        <div class="mt-[15px] md:mx-5">
            <fieldset class="border border-[#ccc] rounded-[5px] px-5 py-[15px] bg-white">
                <legend class="text-[13px] font-bold text-[#333] px-[5px]">Stock:</legend>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-start mb-[15px]">
                    
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Quantity:</label>
                        <div class="relative flex items-center w-full">
                            <input type="text" class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[40px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" 
                                   value="<?= htmlspecialchars($data['form2']['quantity_received'] ?? '0') ?>" 
                                   name="quantity_received">
                            <span class="absolute right-[10px] text-xs text-[#777] pointer-events-none">NOS</span>
                        </div>
                    </div>

                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Permanently Available:</label>
                        <select class="w-full h-[32px] border border-[#ccc] rounded-[3px] px-[10px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" name="permanently_available">
                            <?php $perm = $data['form2']['permanently_available'] ?? 'Y'; ?>
                            <option value="N" <?= ($perm == 'N') ? 'selected' : '' ?>>No</option>
                            <option value="Y" <?= ($perm == 'Y') ? 'selected' : '' ?>>Yes</option>
                        </select>
                    </div>

                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Warehouse:</label>
                        <select class="w-full h-[32px] border border-[#ccc] rounded-[3px] px-[10px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" name="ware_house_code">
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
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Store Location:</label>
                        <input type="text" class="w-full h-[32px] border border-[#ccc] rounded-[3px] px-[10px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" 
                               value="<?= htmlspecialchars($data['form2']['store_location'] ?? '') ?>" 
                               name="store_location">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-start mb-[15px] mt-[10px]">
    
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
                                   value="<?= htmlspecialchars($data['form2']['lead_time_days'] ?? '0') ?>" 
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

                    <div class="flex-1 lg:col-span-2 flex items-start border border-[#eee] rounded bg-gray-50 p-1"> 
                        
                        <div class="flex-1 pr-4 border-r border-[#ccc] flex flex-col justify-center h-[52px]"> 
                            <label class="block text-xs font-bold text-[#222] mb-[3px]">US Stock:</label>
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
                            <label class="block text-xs font-bold text-[#222] mb-[3px]">India Stock:</label>
                            <div class="flex items-center gap-4">
                                <?php $in_val = $data['form2']['india_block'] ?? 'Y'; ?>
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
            <button class="bg-gray-600 text-white border-none rounded-[4px] py-[10px] px-[30px] font-bold text-sm cursor-pointer shadow-md hover:bg-gray-700 transition">
                Save and Draft
            </button>
            
            <button class="bg-[#d97824] text-white border-none rounded-[4px] py-[10px] px-[30px] font-bold text-sm cursor-pointer shadow-md hover:bg-[#c0651a] transition">
                Save and Generate Item Code
            </button>
        </div>
    </form>
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
    // 1. PHP Data
    const categoriesByParent = <?php echo json_encode($categoriesByParent1); ?>;
    const groupMap = <?php echo json_encode($groupMap); ?>; 
    
    // 2. Pre-selection Data
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
    
    // 4. TomSelect Config
    const config = { create: false, sortField: { field: "text", direction: "asc" }, controlInput: null };
    new TomSelect("#material_select", config);
    const groupTs = new TomSelect("#group_select", config);

    // --- HELPER: Create Single Checkbox HTML ---
    function createCheckboxItem(item, inputName, selectedSet, onChangeCallback) {
        const div = document.createElement('div');
        div.className = 'checkbox-item pl-2'; // Added padding-left for hierarchy look
        const isChecked = selectedSet.has(String(item.id)) ? 'checked' : '';
        
        div.innerHTML = `
            <input type="checkbox" id="${inputName}_${item.id}" name="${inputName}[]" value="${item.id}" ${isChecked}>
            <label for="${inputName}_${item.id}">${item.name}</label>
        `;
        
        const checkbox = div.querySelector('input');
        checkbox.addEventListener('change', () => { if(onChangeCallback) onChangeCallback(); });
        return div;
    }

    // --- HELPER: Render Simple List (For Main Categories) ---
    function renderSimpleList(container, items, selectedSet, inputName, onChangeCallback) {
        container.innerHTML = ''; 
        if (!items || items.length === 0) {
            container.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">No options available</div>';
            return;
        }
        
        // Deduplicate
        const seenIds = new Set();
        items.forEach(item => {
            if(!seenIds.has(item.id)){
                seenIds.add(item.id);
                container.appendChild(createCheckboxItem(item, inputName, selectedSet, onChangeCallback));
            }
        });
    }

    // --- 1. UPDATE CATEGORY LIST (Based on Group) ---
    function updateCategoryList(groupId) {
        subCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Category...</div>';
        subSubCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Sub Category...</div>';

        if(!groupId || !categoriesByParent[groupId]) {
            categoryContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Group...</div>';
            return;
        }

        renderSimpleList(categoryContainer, categoriesByParent[groupId], preSelected.cat, 'category_code', handleCategoryChange);
        
        if(preSelected.cat.size > 0) handleCategoryChange();
    }

    // --- 2. UPDATE SUB CATEGORY LIST (Grouped by Parent Category) ---
    function handleCategoryChange() {
        // Get all checked category checkboxes
        const checkedInputs = Array.from(categoryContainer.querySelectorAll('input[type="checkbox"]:checked'));
        
        subCatContainer.innerHTML = ''; // Clear container
        subSubCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Sub Category...</div>';

        if(checkedInputs.length === 0) {
            subCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Category...</div>';
            return;
        }

        let hasAnyOptions = false;

        // Loop through selected parents to create groups
        checkedInputs.forEach(input => {
            const parentId = input.value;
            const parentName = input.nextElementSibling.innerText; // Get name from Label
            const children = categoriesByParent[parentId];

            if (children && children.length > 0) {
                hasAnyOptions = true;

                // Create Group Header
                const header = document.createElement('div');
                header.className = "text-[11px] font-bold text-[#d97824] bg-gray-50 px-2 py-1 border-b border-t border-gray-200 mt-0 sticky top-0 z-10";
                header.innerText = parentName; // e.g. "Hindi"
                subCatContainer.appendChild(header);

                // Render Children for this parent
                children.forEach(item => {
                    subCatContainer.appendChild(createCheckboxItem(item, 'sub_category_code', preSelected.sub, handleSubCategoryChange));
                });
            }
        });

        if(!hasAnyOptions) {
             subCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">No Sub Categories found</div>';
        } else {
             // If we had pre-selected sub-cats, trigger next level
             if(preSelected.sub.size > 0) handleSubCategoryChange();
        }
    }

    // --- 3. UPDATE SUB SUB CATEGORY LIST (Grouped by Sub Category) ---
    function handleSubCategoryChange() {
        const checkedInputs = Array.from(subCatContainer.querySelectorAll('input[type="checkbox"]:checked'));
        
        subSubCatContainer.innerHTML = ''; // Clear container

        if(checkedInputs.length === 0) {
            subSubCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select Sub Category...</div>';
            return;
        }

        let hasAnyOptions = false;

        checkedInputs.forEach(input => {
            const parentId = input.value;
            const parentName = input.nextElementSibling.innerText;
            const children = categoriesByParent[parentId];

            if (children && children.length > 0) {
                hasAnyOptions = true;
                
                // Create Group Header
                const header = document.createElement('div');
                header.className = "text-[11px] font-bold text-[#d97824] bg-gray-50 px-2 py-1 border-b border-t border-gray-200 mt-0 sticky top-0 z-10";
                header.innerText = parentName;
                subSubCatContainer.appendChild(header);

                // Render Children
                children.forEach(item => {
                    subSubCatContainer.appendChild(createCheckboxItem(item, 'sub_sub_category_code', preSelected.subsub, null));
                });
            }
        });

        if(!hasAnyOptions) {
             subSubCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">No Sub Sub Categories found</div>';
        }
    }
    
    // --- EVENTS ---
    groupTs.on('change', function(groupValue) {
        preSelected.cat.clear(); 
        preSelected.sub.clear(); 
        preSelected.subsub.clear();        
        const groupId = groupMap[groupValue]; 
        updateCategoryList(groupId);
    });
    
    // --- INITIAL LOAD ---
    if (preSelected.groupVal) {
        const initialGroupId = groupMap[preSelected.groupVal];        
        if(initialGroupId) {
            updateCategoryList(initialGroupId);
        }
    }
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
        //     h = h / 2.54;
        //     w = w / 2.54;
        //     d = d / 2.54;
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