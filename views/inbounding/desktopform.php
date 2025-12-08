<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.default.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<style>
    /* Custom Scrollbar Styles (Kept separate for exact design match) */
    .custom-scrollbar::-webkit-scrollbar { height: 14px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #e0e0e0; border: 1px solid #ccc; border-radius: 2px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #666; border: 2px solid #e0e0e0; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #555; }
    .ts-control {
        border-radius: 0.25rem;
        padding: 8px 10px;
        font-size: 13px;
        border-color: #ccc;
        height: 36px; /* Your h-[36px] */
        display: flex;
        align-items: center;
    }
    .ts-wrapper.focus .ts-control {
        border-color: #999; /* Your focus color */
        box-shadow: none;
    }
</style>
<?php
$record_id = $_GET['id'] ?? '';
?>
<div class="w-full max-w-[1200px] mx-auto p-5 font-['Segoe_UI',Tahoma,Geneva,Verdana,sans-serif] text-[#333]">
    <form action="<?php echo base_url('?page=inbounding&action=updatedesktopform&id='.$record_id); ?>" method="POST" enctype="multipart/form-data">
        <div class="flex items-stretch w-full">
            <div class="shrink-0 w-[150px] bg-[#f4f4f4] border border-[#777] rounded-md p-1 ml-5 relative">
                <img src="<?php echo base_url($data['form2']['product_photo']); ?>" class="w-full h-full object-cover rounded-[3px] block bg-[#ddd]">
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
                                // Only pre-fill the select option if it is currently a Variant
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
                    <div class="flex flex-col mb-4">
                        <span class="text-[11px] font-bold text-[#222] mb-[3px]">Received by:</span>
                        
                        <select id="received_by_select" name="received_by_user_id" placeholder="Select User...">
                            <option value="">Select User</option>
                            <?php foreach ($data['user'] as $value1) { 
                                // Logic for Received By
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

                            $dbValue = isset($data['form2']['updated_by_user_id']) ? $data['form2']['updated_by_user_id'] : '';
                            $sessionValue = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';
                            
                            foreach ($data['user'] as $value1) { 
                                $isSelected = '';

                                // Logic: Prefer DB value. If DB is empty, use Session value.
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
                    <div class="flex flex-col">
                        <span class="text-[11px] font-bold text-[#222] mb-[3px]">Invoice Number:</span>
                        <input type="text" class="w-full h-[34px] border border-[#ccc] rounded-[4px] px-2.5 text-[13px] text-[#333] focus:outline-none focus:border-[#999]" name="invoice_no" value="<?php echo !empty($data['form2']['invoice_no']) 
                                ? $data['form2']['invoice_no'] : ''; ?>">
                    </div>
                </div>
            </fieldset>
        </div>

        <?php 
            // Extract existing values or set to null/empty string to avoid PHP errors
            $selected_material = $data['form2']['material_code'] ?? '';
            $selected_group    = $data['form2']['group_name'] ?? '';
            $selected_cat      = $data['form2']['category_code'] ?? '';
            $selected_sub      = $data['form2']['sub_category_code'] ?? '';
            $selected_sub_sub  = $data['form2']['sub_sub_category_code'] ?? '';

            // Prepare Category Data
            $categoriesByParent1 = [];
            $rootCategories = [];
            
            if (!empty($data['category'])) {
                foreach ($data['category'] as $row) {
                    if (isset($row['is_active']) && $row['is_active'] != 1) { continue; }
                    
                    $categoriesByParent1[$row['parent_id']][] = [
                        'id'   => $row['id'],
                        'name' => $row['display_name']
                    ];
                    
                    if ($row['parent_id'] == 0) {
                        $rootCategories[] = [
                            'id'   => $row['id'],
                            'name' => $row['display_name']
                        ];
                    }
                }
            }
        ?>
        <div class="mt-[15px] mx-5">
            <fieldset class="border border-[#ccc] rounded-[5px] px-[15px] py-2.5 pb-[15px] bg-white">
                <legend class="text-[13px] font-bold text-[#333] px-[5px]">Item Identification</legend>
                
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">

                    <div>
                        <label class="block text-xs font-bold text-[#222] mb-1">Material:</label>
                        <select class="w-full h-[30px] border border-[#ccc] rounded-[3px] pl-[5px] text-[12px] text-[#333] focus:outline-none focus:border-[#999]" name="material_code">
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
                                // PHP Select Logic for Group
                                $isGroupSelected = ($selected_group == $group['id']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $group['id']; ?>" <?php echo $isGroupSelected; ?>><?php echo $group['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-[#222] mb-1">Category:</label>
                        <select id="category_select" name="category_code" placeholder="Select Group First..." disabled autocomplete="off">
                            <option value="">Select Group First...</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-[#222] mb-1">Sub Category:</label>
                        <select id="sub_category_select" name="sub_category_code" placeholder="Select Category First..." disabled autocomplete="off">
                             <option value="">Select Category First...</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-[#222] mb-1">SubSubCategory:</label>
                        <select id="sub_sub_category_select" name="sub_sub_category_code" placeholder="Select Sub Category First..." disabled autocomplete="off">
                             <option value="">Select Sub Category First...</option>
                        </select>
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

        <div class="flex gap-5 mt-[15px] items-stretch mx-5">
            <?php 
                // 1. Check if an image exists in the database
                $hasImage = !empty($data['form2']['invoice_image']);
                $imageSrc = $hasImage ? base_url($data['form2']['invoice_image']) : '';
            ?>

            <div class="flex-1 border border-[#ccc] rounded-[5px] bg-white p-2.5 flex items-center gap-[15px] cursor-pointer hover:bg-gray-50 transition-colors" 
                 onclick="document.getElementById('invoice_input').click()">

                <input type="file" 
                       id="invoice_input" 
                       name="invoice_image" 
                       class="hidden" 
                       accept="image/*" 
                       onchange="previewInvoice(this)">

                <div id="invoice_preview_box" 
                     class="relative w-[65px] h-[75px] bg-[#cc99b5] border border-[#444] rounded-[4px] shrink-0 shadow-sm group <?php echo $hasImage ? '' : 'hidden'; ?>">
                    
                    <img id="invoice_img_tag" 
                         src="<?php echo $imageSrc; ?>" 
                         class="w-full h-full object-cover rounded-[3px]" name="invoice_image">
                    
                    <div onclick="removeInvoice(event)" 
                         class="absolute -top-[6px] -right-[6px] w-4 h-4 bg-[#d32f2f] text-white rounded-full flex items-center justify-center text-[10px] font-bold shadow-sm cursor-pointer z-10 hover:bg-[#b71c1c]">
                         ✕
                    </div>
                </div>

                <div id="invoice_text_box" 
                     class="flex flex-col items-center justify-center grow text-center">
                    <div class="text-2xl text-[#555] mb-[2px]">☁️</div>
                    <h4 class="text-[13px] font-bold text-black m-0">Upload Invoice Image</h4>
                    <p class="text-[10px] text-[#666] m-0">Click to replace or upload</p>
                </div>

            </div>

            <fieldset class="flex-1 border border-[#ccc] rounded-[5px] px-[15px] py-2.5 bg-white flex flex-col justify-center">
                <legend class="text-[13px] font-bold text-[#333] px-[5px]">Vendor:</legend>
                <div class="relative w-full">
                    <select name="vendor_code" id="vendor_code" class="w-full h-[34px] border border-[#ccc] rounded-[4px] pl-[10px] pr-[100px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]">
                        <option value="">Select Vendor</option>
                        <?php foreach ($data['vendors'] as $key4 => $value4) { 
                            
                            // Logic to check if selected
                            $isSelected = (isset($data['form2']['vendor_code']) && $data['form2']['vendor_code'] == $value4['id']) ? 'selected' : '';
                            
                            // I removed the print_r() and exit; here
                        ?>
                            <option value="<?php echo $value4['id']; ?>" <?php echo $isSelected; ?>>
                                <?php echo htmlspecialchars($value4['vendor_name'] ?? ''); ?>
                            </option>
                        <?php } ?>
                    </select>
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
                            <span class="absolute right-[10px] text-xs text-[#777] pointer-events-none">INR</span>
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
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">In Stock Lead Time:</label>
                        <input type="text" class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[40px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" value="<?= htmlspecialchars($data['form2']['lead_time_days'] ?? '') ?>" name="lead_time_days">
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
    
    // --- CONFIGURATION ---
    const apiUrl = '/index.php?page=inbounding&action=getItamcode'; 
    
    // --- ELEMENTS ---
    const variantSelect = document.getElementById('variant_select');
    const wrapperSelect = document.getElementById('wrapper_select');
    const wrapperInput  = document.getElementById('wrapper_input');
    const fixedInput    = document.getElementById('fixed_item_code_input'); 
    const selectElement = document.getElementById('item_code_select');
    const existingCode  = document.getElementById('existing_item_code').value;

    // --- INITIALIZE TOM SELECT ---
    let tomSelectInstance = new TomSelect("#item_code_select", {
        valueField: 'item_code', 
        labelField: 'title',     
        searchField: 'title',    
        maxItems: 1,
        create: false,
        preload: true,            
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

    // --- LOGIC FUNCTION ---
    function toggleVariantFields(val) {
        const originalStatus = document.getElementById('original_variant_status').value;

        if (val === 'Y') {
            // ... (Existing logic for Yes) ...
            wrapperSelect.style.display = 'block';
            wrapperInput.style.display  = 'none';
            tomSelectInstance.enable(); 
            selectElement.disabled = false; 
            fixedInput.disabled = true;

        } else if (val === 'N') {
            // ... (Existing logic for No) ...
            wrapperSelect.style.display = 'none';
            wrapperInput.style.display  = 'block';
            tomSelectInstance.disable();
            selectElement.disabled = true;
            fixedInput.disabled = false;

            // --- VISUAL FIX ---
            // If the user is switching to 'N', but it WAS 'Y' in the database,
            // clear the input so they don't see the old Parent Code.
            if (originalStatus === 'Y') {
                fixedInput.value = ""; 
                fixedInput.placeholder = "Auto-generated on Save";
            }
        }
    }

    // --- EVENT LISTENER ---
    variantSelect.addEventListener('change', function() {
        toggleVariantFields(this.value);
        
        // If switching to 'N', clear the TomSelect logic visually
        if(this.value === 'N') {
            tomSelectInstance.clear(); 
        }
    });

    // --- SUBMISSION HANDLER ---
    // This cleans up the "Auto-generated" text before sending to PHP
    document.querySelector('form').addEventListener('submit', function() {
        if(variantSelect.value === 'N' && fixedInput.value === "Auto-generated on Save") {
            fixedInput.value = ""; // Send empty string so PHP knows to generate
        }
    });

    // --- RUN ON PAGE LOAD ---
    if(variantSelect.value) {
        toggleVariantFields(variantSelect.value);
    } else {
        // Default state: Hide both or disable inputs
        fixedInput.disabled = true; 
        selectElement.disabled = true;
    }

});
</script>
<script>
    // Initialize "Received By"
    new TomSelect("#received_by_select", {
        create: false,
        sortField: { field: "text", direction: "asc" }
    });

    // Initialize "Updated By"
    new TomSelect("#updated_by_select", {
        create: false,
        sortField: { field: "text", direction: "asc" }
    });

    document.addEventListener('DOMContentLoaded', function() {
        new TomSelect("#vendor_code", {
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            }
        });
    });
</script>
<script>
    function previewInvoice(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            
            reader.onload = function(e) {
                // 1. Update the image source
                document.getElementById('invoice_img_tag').src = e.target.result;
                
                // 2. Show the preview box (if it was hidden)
                document.getElementById('invoice_preview_box').classList.remove('hidden');
                
                // Note: We do NOT hide the text box anymore, so users can click it to replace again.
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }

    function removeInvoice(event) {
        // Stop the click from triggering the file upload dialog
        event.stopPropagation(); 
        
        // Clear input and image
        document.getElementById('invoice_input').value = ""; 
        document.getElementById('invoice_img_tag').src = ""; 
        
        // Hide the preview box (Text box automatically centers itself via Flexbox)
        document.getElementById('invoice_preview_box').classList.add('hidden');
    }
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Data from PHP
    const categoriesByParent = <?php echo json_encode($categoriesByParent1); ?>;
    
    // 2. Pre-selected Values from PHP (Converted to strings for comparison)
    const preSelected = {
        group:  "<?php echo $selected_group; ?>",
        cat:    "<?php echo $selected_cat; ?>",
        sub:    "<?php echo $selected_sub; ?>",
        subsub: "<?php echo $selected_sub_sub; ?>"
    };

    // 3. TomSelect Configuration
    const config = {
        create: false,
        sortField: { field: "text", direction: "asc" },
        // This ensures the styling matches your Tailwind heights better
        controlInput: null 
    };

    // 4. Initialize Instances
    const groupTs      = new TomSelect("#group_select", config);
    const categoryTs   = new TomSelect("#category_select", config);
    const subCatTs     = new TomSelect("#sub_category_select", config);
    const subSubCatTs  = new TomSelect("#sub_sub_category_select", config);

    // --- HELPER: Populate Function ---
    // Handles adding options and setting a value if provided
    function populateAndSelect(instance, parentId, valueToSelect = null) {
        instance.clearOptions();
        instance.clear(true);
        
        if (parentId && categoriesByParent[parentId]) {
            instance.enable();
            categoriesByParent[parentId].forEach(item => {
                instance.addOption({ value: item.id, text: item.name });
            });
            instance.refreshOptions(false);

            // If we have a value to select, set it
            if (valueToSelect) {
                instance.setValue(valueToSelect);
            }
        } else {
            instance.disable();
        }
    }

    // --- EVENT LISTENERS ---

    // Group Changed -> Update Category
    groupTs.on('change', function(groupId) {
        // Reset children
        subCatTs.clear(true); subCatTs.clearOptions(); subCatTs.disable();
        subSubCatTs.clear(true); subSubCatTs.clearOptions(); subSubCatTs.disable();
        
        // Populate Category (No pre-select on manual change)
        populateAndSelect(categoryTs, groupId, null);
    });

    // Category Changed -> Update Sub Category
    categoryTs.on('change', function(catId) {
        // Reset child
        subSubCatTs.clear(true); subSubCatTs.clearOptions(); subSubCatTs.disable();

        // Populate Sub Category
        populateAndSelect(subCatTs, catId, null);
    });

    // Sub Category Changed -> Update Sub Sub Category
    subCatTs.on('change', function(subCatId) {
        // Populate Sub Sub Category
        populateAndSelect(subSubCatTs, subCatId, null);
    });

    // --- INITIALIZATION LOGIC (The Fix for Pre-selection) ---
    
    // If Group is already selected (via PHP Render), trigger the chain
    if (preSelected.group) {
        // 1. Group is set in HTML, wait for TomSelect to sync, then load Category
        // Note: Group value is already set by PHP <option selected>, TomSelect picks it up automatically.
        
        // 2. Load Category Options & Select Category Value
        populateAndSelect(categoryTs, preSelected.group, preSelected.cat);

        // 3. If Category was selected, Load Sub Category Options & Select Sub Value
        if (preSelected.cat) {
            populateAndSelect(subCatTs, preSelected.cat, preSelected.sub);

            // 4. If Sub Category was selected, Load Sub Sub Options & Select Sub Sub Value
            if (preSelected.sub) {
                populateAndSelect(subSubCatTs, preSelected.sub, preSelected.subsub);
            }
        }
    }
});
</script>