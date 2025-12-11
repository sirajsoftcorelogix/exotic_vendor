<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.default.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<style>
    .custom-scrollbar::-webkit-scrollbar { height: 14px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #e0e0e0; border: 1px solid #ccc; border-radius: 2px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #666; border: 2px solid #e0e0e0; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #555; }
    .ts-wrapper.single .ts-control {
        background: #fff !important;         /* Force White Background */
        border: 1px solid #ccc !important;   /* Exact border color */
        border-radius: 4px !important;       /* Exact rounding */
        height: 36px !important;             /* Exact Height */
        padding: 0 8px !important;           /* Alignment padding */
        display: flex;
        align-items: center;
        font-size: 13px;
        color: #333;
        box-shadow: none !important;         /* Remove default shadows */
        background-image: none !important;   /* Remove default gradients */
    }
    .ts-wrapper.focus .ts-control {
        border-color: #999 !important;       /* Darker border on click */
        box-shadow: none !important;
    }
    .ts-dropdown {
        border: 1px solid #ccc;
        border-radius: 0 0 4px 4px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
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
</style>
<?php
$record_id = $_GET['id'] ?? '';
?>
<div class="w-full max-w-[1200px] mx-auto p-5 font-['Segoe_UI',Tahoma,Geneva,Verdana,sans-serif] text-[#333]">
    <form action="<?php echo base_url('?page=inbounding&action=updatedesktopform&id='.$record_id); ?>" method="POST" enctype="multipart/form-data">
        <div class="flex items-stretch w-full">
            <div class="shrink-0 w-[150px] bg-[#f4f4f4] border border-[#777] rounded-md p-1 ml-5 relative">
                <img src="<?php echo base_url($data['form2']['product_photo']); ?>" class="w-full h-full object-cover rounded-[3px] block bg-[#ddd]" onclick="openImagePopup('<?= $data['form2']['product_photo'] ?>')">
            </div>
            <fieldset class="grow border border-[#ccc] rounded-[5px] px-5 pt-[15px] pb-5 bg-white ml-2.5 mr-5">
                <legend class="text-sm font-bold text-[#333] px-[5px]">Item Linking</legend>
                <div class="flex gap-[30px] mb-[15px] items-end">
                    <div class="flex-1 flex flex-col">
                        <label class="text-xs font-bold text-[#333] mb-1.5">Variant:</label>
                        <select id="variant_select" name="is_variant" 
                                class="h-[36px] text-[13px] border border-[#ccc] rounded px-2.5 text-[#333] w-full focus:outline-none focus:border-[#999]">
                            <option value="" disabled <?php echo empty($data['form2']['is_variant']) ? 'selected' : ''; ?>>Select...</option>
                            <option value="Y" <?php echo (isset($data['form2']['is_variant']) && $data['form2']['is_variant'] === 'Y') ? 'selected' : ''; ?>>Yes</option>
                            <option value="N" <?php echo (isset($data['form2']['is_variant']) && $data['form2']['is_variant'] === 'N') ? 'selected' : ''; ?>>No</option>
                        </select>
                    </div>
                    <div class="flex-1 flex flex-col">
                        <label class="text-xs font-bold text-[#333] mb-1.5">Parent Item Code:</label>
                        <input type="hidden" id="original_variant_status" value="<?php echo $data['form2']['is_variant'] ?? ''; ?>">
                        <div id="wrapper_select" style="display:none;">
                            <select id="item_code_select" name="Item_code" placeholder="Type to search title...">
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
                    <div class="flex-1 flex flex-col">
                        <label class="text-xs font-bold text-[#333] mb-1.5">Stock Added On</label>
                         <?php if (!empty($data['form2']['stock_added_date']) && $data['form2']['stock_added_date'] != "0000-00-00"): ?>
                            <input type="date" class="h-[36px] text-[13px] border border-[#ccc] rounded px-2.5 text-[#333] w-full focus:outline-none focus:border-[#999]" value="<?php echo date('Y-m-d', strtotime($data['form2']['stock_added_date'])); ?>" name="stock_added_date">
                        <?php else: ?>
                            <input type="date" class="h-[36px] text-[13px] border border-[#ccc] rounded px-2.5 text-[#333] w-full focus:outline-none focus:border-[#999]" value="<?php echo date('Y-m-d'); ?>" name="stock_added_date">
                        <?php endif; ?>
                    </div>
                </div>
            </fieldset>
        </div>
        <div class="mt-[15px] mx-5">
            <fieldset class="border border-[#ccc] rounded-[5px] px-[15px] py-2 pb-3 bg-white w-full">
                <legend class="text-[13px] font-bold text-[#333] px-[5px]">Receipt:</legend>
                <div class="flex gap-[50px]">      
                    <div class="flex flex-col">
                        <span class="text-[11px] font-bold text-[#222] mb-[3px]">Gate Entry Date & Time:</span>
                        <span class="text-xs text-[#444]">
                            <?php echo !empty($data['form2']['gate_entry_date_time']) 
                                ? date('d M Y h:i A', strtotime($data['form2']['gate_entry_date_time'])) 
                                : ''; ?>
                        </span>
                    </div>
                    <div class="flex flex-col mb-4 flex-1">
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
                    <div class="flex flex-col flex-1">
                        <span class="text-[11px] font-bold text-[#222] mb-[3px]">Updated by:</span>
                        <select id="updated_by_select" name="updated_by_user_id" placeholder="Select User...">
                            <option value="">Select User</option>
                            <?php 
                                $dbValue = isset($data['form2']['updated_by_user_id']) ? $data['form2']['updated_by_user_id'] : '';
                                $sessionValue = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';  
                                foreach ($data['user'] as $value1) { 
                                    $isSelected = '';
                                    if (!empty($dbValue)) {
                                        if ($dbValue == $value1['id']) $isSelected = 'selected';
                                    } elseif (!empty($sessionValue)) {
                                        if ($sessionValue == $value1['id']) $isSelected = 'selected';
                                    }
                            ?> 
                            <option value="<?php echo $value1['id']; ?>" <?php echo $isSelected; ?>>
                                <?php echo $value1['name']; ?>
                            </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="flex flex-col flex-1">
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
                        'id'   => $row['id'],
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
        <div class="mt-[15px] mx-5">
            <fieldset class="border border-[#ccc] rounded-[5px] px-[15px] py-4 bg-white">
                <legend class="text-[13px] font-bold text-[#333] px-[5px]">Item Identification</legend>
                <div class="flex flex-col md:flex-row gap-5 items-stretch">
                    <div class="w-full md:w-1/3 flex flex-col gap-4">
                        <div>
                            <label class="block text-xs font-bold text-[#222] mb-1">Material:</label>
                            <select class="w-full h-[36px] border border-[#ccc] rounded px-2 text-[13px]" name="material_code">
                                <option value="">Select Material</option>
                                <?php foreach ($data['material'] as $value2) { 
                                    $isSelected = ($selected_material == $value2['id']) ? 'selected' : '';
                                ?> 
                                    <option <?php echo $isSelected; ?> value="<?php echo $value2['id']; ?>"> <?php echo $value2['material_name']; ?></option>
                                <?php } ?>
                            </select>
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
                        <div>
                            <label class="block text-xs font-bold text-[#222] mb-1">Category:</label>
                            <select id="category_select" name="category_code" placeholder="Select Group First..." disabled autocomplete="off">
                                <option value="">Select Group First...</option>
                            </select>
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
        <div class="mt-[15px] mx-5">
            <fieldset class="border border-[#ccc] rounded-[5px] px-5 py-[15px] pb-5 bg-white">
                <legend class="text-[13px] font-bold text-[#333] px-[5px]">Item Identification</legend>
                <div class="mb-[15px]">
                    <label class="block text-xs font-bold text-[#222] mb-[5px]">Title:</label>
                    <input type="text" class="w-full h-[34px] border border-[#ccc] rounded-[4px] px-2.5 text-[13px] text-[#333] focus:outline-none focus:border-[#999]" name="product_title" value="<?= htmlspecialchars($data['form2']['product_title'] ?? '') ?>">
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#222] mb-[5px]">Keywords:</label>
                    <input type="text" class="w-full h-[34px] border border-[#ccc] rounded-[4px] px-2.5 text-[13px] text-[#333] focus:outline-none focus:border-[#999]" value="<?= htmlspecialchars($data['form2']['key_words'] ?? '') ?>" name= "key_words">
                </div>
            </fieldset>
        </div>
        <div class="mt-[15px] mx-5">
            <fieldset class="border border-[#ccc] rounded-[5px] px-5 py-[15px] bg-white">
                <legend class="text-[13px] font-bold text-[#333] px-[5px]">Invoice Details:</legend>
                <div class="flex gap-5 items-stretch">
                    <?php 
                        $hasImage = !empty($data['form2']['invoice_image']);
                        $imageSrc = $hasImage ? base_url($data['form2']['invoice_image']) : '';
                    ?>
                    <div class="flex-1 border border-[#ccc] rounded-[5px] bg-white p-2.5 flex items-center gap-[15px] cursor-pointer hover:bg-gray-50 transition-colors" 
                         onclick="document.getElementById('invoice_input').click()">

                        <input type="file" id="invoice_input" name="invoice_image" class="hidden" accept="image/*" onchange="previewInvoice(this)">

                        <div id="invoice_preview_box" class="relative w-[65px] h-[75px] bg-[#cc99b5] border border-[#444] rounded-[4px] shrink-0 shadow-sm group <?php echo $hasImage ? '' : 'hidden'; ?>">
                            <img id="invoice_img_tag" src="<?php echo $imageSrc; ?>" class="w-full h-full object-cover rounded-[3px]" name="invoice_image">
                            <div onclick="removeInvoice(event)" class="absolute -top-[6px] -right-[6px] w-4 h-4 bg-[#d32f2f] text-white rounded-full flex items-center justify-center text-[10px] font-bold shadow-sm cursor-pointer z-10 hover:bg-[#b71c1c]">✕</div>
                        </div>

                        <div id="invoice_text_box" class="flex flex-col items-center justify-center grow text-center">
                            <div class="text-2xl text-[#555] mb-[2px]">☁️</div>
                            <h4 class="text-[13px] font-bold text-black m-0">Upload Invoice Image</h4>
                            <p class="text-[10px] text-[#666] m-0">Click to replace or upload</p>
                        </div>
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Invoice Number:</label>
                        <div class="flex flex-col justify-center h-full">
                            <input type="text" class="w-full h-[36px] border border-[#ccc] rounded-[4px] px-2.5 text-[13px] text-[#333] focus:outline-none focus:border-[#999]" name="invoice_no" 
                                   value="<?php echo !empty($data['form2']['invoice_no']) ? $data['form2']['invoice_no'] : ''; ?>">
                        </div>
                    </div>
                </div>
            </fieldset>
        </div>
        <div class="mt-[15px] mx-5">
            <fieldset class="border border-[#ccc] rounded-[5px] px-5 py-[15px] bg-white">
                <legend class="text-[13px] font-bold text-[#333] px-[5px]">Pricing:</legend>
                <div class="flex gap-5 items-start mb-[15px]">
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
            </fieldset>
        </div>
        <div class="mt-[15px] mx-5">
            <fieldset class="border border-[#ccc] rounded-[5px] px-5 py-[15px] bg-white">
                <legend class="text-[13px] font-bold text-[#333] px-[5px]">Unit:</legend>
                <div class="flex gap-5 items-start mb-[15px]">
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Dimention Unit:</label>
                        <div class="relative flex items-center w-full">
                            <select class="w-full h-[32px] border border-[#ccc] rounded-[3px] px-[10px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" name="dimention_unit">
                                <option value="cm">cm</option>
                                <option value="inch" selected="">inch</option>
                            </select>
                        </div>
                    </div>
                     <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Weight Unit:</label>
                        <div class="relative flex items-center w-full">
                            <select class="w-full h-[32px] border border-[#ccc] rounded-[3px] px-[10px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" name="weight_unit">
                                <option value="kg">kg</option>
                                <option value="gm" selected="">gm</option>
                            </select>
                        </div>
                    </div>
                </div>
            </fieldset>
        </div>
        <div class="mt-[15px] mx-5">
            <fieldset class="border border-[#ccc] rounded-[5px] px-5 py-[15px] bg-white">
                <legend class="text-[13px] font-bold text-[#333] px-[5px]">Dimensions:</legend>
                <div class="flex gap-5 items-start mb-[15px]">
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Height:</label>
                        <div class="relative flex items-center w-full">
                            <input type="text" class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[40px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" value="<?= htmlspecialchars($data['form2']['height'] ?? '') ?>" name="height">
                        </div>
                    </div>
                     <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Width:</label>
                        <div class="relative flex items-center w-full">
                            <input type="text" class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[40px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" value="<?= htmlspecialchars($data['form2']['width'] ?? '') ?>" name="width">
                        </div>
                    </div>
                     <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Depth:</label>
                        <div class="relative flex items-center w-full">
                            <input type="text" class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[40px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" value="<?= htmlspecialchars($data['form2']['depth'] ?? '') ?>" name="depth">
                        </div>
                    </div>
                     <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Weight:</label>
                        <div class="relative flex items-center w-full">
                            <input type="text" class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[40px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" value="<?= htmlspecialchars($data['form2']['weight'] ?? '') ?>" name="weight">
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
            </fieldset>
        </div>
        <div class="mt-[15px] mx-5">
            <fieldset class="border border-[#ccc] rounded-[5px] px-5 py-[15px] bg-white">
                <legend class="text-[13px] font-bold text-[#333] px-[5px]">Stock:</legend>
                <div class="flex gap-5 items-start mb-[15px]">
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Quantity:</label>
                        <div class="relative flex items-center w-full">
                            <input type="text" class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[40px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" value="<?= htmlspecialchars($data['form2']['quantity_received'] ?? '') ?>" name="quantity_received">
                            <span class="absolute right-[10px] text-xs text-[#777] pointer-events-none">NOS</span>
                        </div>
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Permanently Available:</label>
                        <select class="w-full h-[32px] border border-[#ccc] rounded-[3px] px-[10px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" name="permanently_available">
                        <?php $selectedVal = $data['form2']['permanently_available'] ?? ''; ?>
                        <option value="N" <?php echo ($selectedVal == 'N') ? 'selected' : ''; ?>>No</option>
                        <option value="Y" <?php echo ($selectedVal == 'Y') ? 'selected' : ''; ?>>Yes</option>
                    </select>
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Warehouse Code:</label>
                        <input type="text" class="w-full h-[32px] border border-[#ccc] rounded-[3px] px-[10px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" value="<?= htmlspecialchars($data['form2']['ware_house_code'] ?? '') ?>" name="ware_house_code">
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Store Location:</label>
                        <input type="text" class="w-full h-[32px] border border-[#ccc] rounded-[3px] px-[10px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" value="<?= htmlspecialchars($data['form2']['store_location'] ?? '') ?>" name="store_location">
                    </div>
                </div>
                <div class="flex gap-5 items-start mb-[15px] mt-[10px]">
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Local Stock:</label>
                         <div class="relative flex items-center w-full">
                            <input type="text" class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[40px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" value="<?= htmlspecialchars($data['form2']['local_stock'] ?? '') ?>" name="local_stock">
                            <span class="absolute right-[10px] text-xs text-[#777] pointer-events-none">NOS</span>
                        </div>
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Lead Time:</label>
                        <div class="relative w-full">
                            <input type="text" name="lead_time_days" value="<?= htmlspecialchars($data['form2']['lead_time_days'] ?? '') ?>" class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[45px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]">
                            <span class="absolute right-[10px] top-1/2 -translate-y-1/2 text-[13px] text-[#777] pointer-events-none">Days</span>
                        </div>
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">In Stock Lead Time:</label>
                        <div class="relative w-full">
                            <input type="text" name="in_stock_leadtime_days" value="<?= htmlspecialchars($data['form2']['in_stock_leadtime_days'] ?? '') ?>" class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[45px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]">
                            <span class="absolute right-[10px] top-1/2 -translate-y-1/2 text-[13px] text-[#777] pointer-events-none">Days</span>
                        </div>
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">US Stock:</label>
                         <select class="w-full h-[32px] border border-[#ccc] rounded-[3px] px-[10px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" name="us_block">
                        <?php $selectedVal = $data['form2']['us_block'] ?? ''; ?>
                        <option value="N" <?php echo ($selectedVal == 'N') ? 'selected' : ''; ?>>No</option>
                        <option value="Y" <?php echo ($selectedVal == 'Y') ? 'selected' : ''; ?>>Yes</option>
                    </select>
                    </div>

                </div>
            </fieldset>
        </div>
        <div class="flex justify-end my-[25px] mx-5 mb-10">
            <button class="bg-[#d97824] text-white border-none rounded-[4px] py-[10px] px-[30px] font-bold text-sm cursor-pointer shadow-md hover:bg-[#c0651a]">Save and Generate Item Code</button>
        </div>
    </form>
</div>
<div id="imagePopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center z-50" onclick="closeImagePopup(event)">
    <div class="bg-white p-4 rounded-md max-w-3xl max-h-3xl relative flex flex-col items-center" onclick="event.stopPropagation();">
        <button onclick="closeImagePopup()" class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-full text-sm">✕</button>
        <img id="popupImage" class="max-w-full max-h-[80vh] rounded" src="" alt="Image Preview">
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
    const apiUrl = '/index.php?page=inbounding&action=getItamcode';
    const variantSelect = document.getElementById('variant_select');
    const wrapperSelect = document.getElementById('wrapper_select');
    const wrapperInput  = document.getElementById('wrapper_input');
    const fixedInput    = document.getElementById('fixed_item_code_input'); 
    const selectElement = document.getElementById('item_code_select');
    const existingCode  = document.getElementById('existing_item_code').value;
    // --- INITIALIZE TOM SELECT FOR PARENT ITEM ---
    let tomSelectInstance = new TomSelect("#item_code_select", {
        valueField: 'item_code',
        labelField: 'title',
        
        // 1. ENABLE SEARCH BY CODE AND TITLE
        searchField: ['item_code', 'title'], 
        
        maxItems: 1,
        create: false,
        preload: true,
        
        // 2. CUSTOM DISPLAY (Fixes Duplication & Styles List)
        render: {
            // Dropdown List Style: "Code - Title"
            option: function(data, escape) {
                return '<div class="py-1 px-2 flex flex-col">' +
                        '<span class="font-bold text-gray-800">' + escape(data.item_code) + '</span>' +
                        '<span class="text-gray-500 text-xs">' + escape(data.title) + '</span>' +
                    '</div>';
            },
            // Selected Item Style: "Code - Title"
            item: function(data, escape) {
                // SMART FIX: If the title is just the code (e.g. "SWL33"), don't repeat it.
                if (data.title === data.item_code) {
                    return '<div>' + escape(data.item_code) + '</div>';
                }
                
                // SMART FIX: If the title already contains the code (from PHP), don't add it again.
                // This prevents "SWL33 - SWL33 - Name"
                if (data.title.indexOf(data.item_code) === 0) {
                     return '<div>' + escape(data.title) + '</div>';
                }

                // Standard format: "Code - Title"
                return '<div>' + escape(data.item_code) + ' - ' + escape(data.title) + '</div>';
            }
        },
        
        load: function(query, callback) {
            fetch(apiUrl)
                .then(response => response.json())
                .then(json => {
                    callback(json);
                })
                .catch(err => {
                    console.error("Error loading items:", err);
                    callback();
                });
        }
    });
    function toggleVariantFields(val) {
        const originalStatus = document.getElementById('original_variant_status').value;
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
    document.querySelector('form').addEventListener('submit', function() {
        if(variantSelect.value === 'N' && fixedInput.value === "Auto-generated on Save") {
            fixedInput.value = ""; // Send empty string so PHP knows to generate
        }
    });
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
            sortField: {
                field: "text",
                direction: "asc"
            },
            onInitialize: function() {
                this.wrapper.classList.add('w-full'); // Ensures full width
            }
        };
        new TomSelect("#vendor_code", commonConfig);
        new TomSelect("#received_by_select", commonConfig);
        new TomSelect("#updated_by_select", commonConfig);
    });
</script>
<script>
    function previewInvoice(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();            
            reader.onload = function(e) {
                document.getElementById('invoice_img_tag').src = e.target.result;
                document.getElementById('invoice_preview_box').classList.remove('hidden');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    function removeInvoice(event) {
        event.stopPropagation(); 
        document.getElementById('invoice_input').value = ""; 
        document.getElementById('invoice_img_tag').src = ""; 
        document.getElementById('invoice_preview_box').classList.add('hidden');
    }
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const categoriesByParent = <?php echo json_encode($categoriesByParent1); ?>;
    const groupMap = <?php echo json_encode($groupMap); ?>; 
    const preSelected = {
        groupVal: "<?php echo $selected_group_val; ?>",
        catId:    "<?php echo $selected_cat_id; ?>",
        sub:      new Set(<?php echo json_encode($selected_sub); ?>.map(String)), 
        subsub:   new Set(<?php echo json_encode($selected_sub_sub); ?>.map(String))
    };
    const subCatContainer = document.getElementById('sub_category_container');
    const subSubCatContainer = document.getElementById('sub_sub_category_container');
    const config = { create: false, sortField: { field: "text", direction: "asc" }, controlInput: null };
    const groupTs = new TomSelect("#group_select", config);
    const categoryTs = new TomSelect("#category_select", config);
    function renderCheckboxList(container, items, selectedSet, inputName, onChangeCallback) {
        container.innerHTML = ''; 
        if (!items || items.length === 0) {
            container.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">No options available</div>';
            return;
        }
        items.forEach(item => {
            const div = document.createElement('div');
            div.className = 'checkbox-item';
            const isChecked = selectedSet.has(String(item.id)) ? 'checked' : '';
            div.innerHTML = `
                <input type="checkbox" id="${inputName}_${item.id}" name="${inputName}[]" value="${item.id}" ${isChecked}>
                <label for="${inputName}_${item.id}">${item.name}</label>
            `;
            const checkbox = div.querySelector('input');
            checkbox.addEventListener('change', () => { if(onChangeCallback) onChangeCallback(); });
            container.appendChild(div);
        });
    }
    function populateDropdown(instance, parentId, valueToSelect = null) {
        instance.clearOptions();
        instance.clear(true);
        if (parentId && categoriesByParent[parentId]) {
            instance.enable();
            categoriesByParent[parentId].forEach(item => {
                instance.addOption({ value: item.id, text: item.name });
            });
            instance.refreshOptions(false);
            if (valueToSelect) instance.setValue(valueToSelect);
        } else {
            instance.disable();
        }
    }
    function updateSubCategoryList(catId) {
        subSubCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select Sub Category...</div>';
        if(!catId || !categoriesByParent[catId]) {
            subCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select Category...</div>';
            return;
        }
        renderCheckboxList(subCatContainer, categoriesByParent[catId], preSelected.sub, 'sub_category_code', handleSubCategoryChange);
        if(preSelected.sub.size > 0) handleSubCategoryChange();
    }
    function handleSubCategoryChange() {
        const checkedSubCats = Array.from(subCatContainer.querySelectorAll('input[type="checkbox"]:checked')).map(cb => cb.value);
        if(checkedSubCats.length === 0) {
            subSubCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select Sub Category...</div>';
            return;
        }
        let allSubSubOptions = [];
        checkedSubCats.forEach(subId => {
            if(categoriesByParent[subId]) allSubSubOptions = allSubSubOptions.concat(categoriesByParent[subId]);
        });
        renderCheckboxList(subSubCatContainer, allSubSubOptions, preSelected.subsub, 'sub_sub_category_code', null);
    }
    groupTs.on('change', function(groupValue) {
        preSelected.sub.clear(); preSelected.subsub.clear();        
        const groupId = groupMap[groupValue]; 
        populateDropdown(categoryTs, groupId, null);
        subCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select Category...</div>';
        subSubCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select Sub Category...</div>';
    });
    categoryTs.on('change', function(catId) {
        if (catId !== preSelected.catId) { preSelected.sub.clear(); preSelected.subsub.clear(); }
        updateSubCategoryList(catId);
    });
    if (preSelected.groupVal) {
        const initialGroupId = groupMap[preSelected.groupVal];        
        if(initialGroupId) {
            populateDropdown(categoryTs, initialGroupId, preSelected.catId);
            if(preSelected.catId) updateSubCategoryList(preSelected.catId);
        }
    }
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