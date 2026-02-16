<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.default.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<style>
    body {
        overflow: hidden; /* Crucial Fix */
    }
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
    /* Fix for Variant Dropdown Width */
    .ts-wrapper {
        width: 100% !important;
    }
    /* Ensure the dropdown arrow and text align correctly */
    .ts-control {
        display: flex !important;
        align-items: center;
    }
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
function renderColorMapField($fieldName, $savedValue, $customClass = "") {
    return '
    <div class="w-full min-w-0 colormap-wrapper" style="display:none;">
        <label class="block text-xs font-bold text-[#555] mb-1">Color Map:</label>
        <select name="' . $fieldName . '" 
                class="colormap-select ' . $customClass . ' w-full h-10 border border-[#ccc] rounded-[3px] px-2 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]"
                data-saved-value="' . htmlspecialchars($savedValue) . '">
            <option value="">Select Color Map</option>
        </select>
    </div>';
}
$is_clothing_initial = false; 
$saved_group_id = $data['form2']['group_name'] ?? '';
if (!empty($data['category']) && !empty($saved_group_id)) {
    foreach ($data['category'] as $cat) {
        if (isset($cat['category']) && $cat['category'] == $saved_group_id) {
            $catName = strtolower($cat['name'] ?? '');
            $catDisplay = strtolower($cat['display_name'] ?? '');
            if (strpos($catName, 'clothing') !== false || strpos($catName, 'textiles') !== false || 
                strpos($catDisplay, 'clothing') !== false || strpos($catDisplay, 'textiles') !== false) {
                $is_clothing_initial = true;
            }
            break;
        }
    }
}
function renderSizeField($fieldName, $currentValue, $isClothing, $options, $customClass = "") {
    $html = '';
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
    else {
        $html .= '<input type="text" name="' . $fieldName . '" value="' . htmlspecialchars($currentValue) . '" class="size-input-field ' . $customClass . ' w-full h-10 border border-[#ccc] rounded-[3px] px-3 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]">';
    }
    return $html;
}
$currentSize = $data['form2']['size'] ?? '';
function getThumbnail($filePath, $width = 150, $height = 150) {
    // 1. Sanitize Path (remove leading slash for file system check)
    $cleanPath = ltrim($filePath, '/');

    // 2. Check if original file exists
    if (empty($cleanPath) || !file_exists($cleanPath)) {
        return 'assets/images/placeholder.png'; // Make sure this placeholder file exists!
    }

    // 3. Determine Directories automatically
    $dirName  = dirname($cleanPath); // Gets "products" or "uploads/itm_img"
    $fileName = basename($cleanPath); // Gets "image.jpg"
    
    $thumbDir  = $dirName . '/thumbs/'; 
    $thumbPath = $thumbDir . $fileName;

    // 4. Return cached thumbnail if it exists
    if (file_exists($thumbPath)) {
        return $thumbPath;
    }

    // 5. Create Thumb Directory if it doesn't exist
    if (!is_dir($thumbDir)) {
        mkdir($thumbDir, 0777, true);
    }

    // 6. Generate Thumbnail
    $info = getimagesize($cleanPath);
    if (!$info) return $cleanPath; // Return original if not an image

    $mime = $info['mime'];
    
    switch ($mime) {
        case 'image/jpeg': $image = imagecreatefromjpeg($cleanPath); break;
        case 'image/png':  $image = imagecreatefrompng($cleanPath); break;
        case 'image/gif':  $image = imagecreatefromgif($cleanPath); break;
        case 'image/webp': $image = imagecreatefromwebp($cleanPath); break;
        default: return $cleanPath;
    }

    $oldW = imagesx($image);
    $oldH = imagesy($image);
    $aspectRatio = $oldW / $oldH;

    if ($width / $height > $aspectRatio) {
        $width = (int) ($height * $aspectRatio);
    } else {
        $height = (int) ($width / $aspectRatio);
    }

    $newImage = imagecreatetruecolor((int)$width, (int)$height);

    if ($mime == 'image/png' || $mime == 'image/webp') {
        imagecolortransparent($newImage, imagecolorallocatealpha($newImage, 0, 0, 0, 127));
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
    }

    imagecopyresampled($newImage, $image, 0, 0, 0, 0, $width, $height, $oldW, $oldH);

    switch ($mime) {
        case 'image/jpeg': imagejpeg($newImage, $thumbPath, 80); break;
        case 'image/png':  imagepng($newImage, $thumbPath); break;
        case 'image/gif':  imagegif($newImage, $thumbPath); break;
        case 'image/webp': imagewebp($newImage, $thumbPath); break;
    }

    imagedestroy($image);
    imagedestroy($newImage);

    return $thumbPath;
}
?>
<div class="w-full max-w-[1200px] mx-auto p-2 md:p-5 font-['Segoe_UI',Tahoma,Geneva,Verdana,sans-serif] text-[#333]">
    <form id="product_form" action="<?php echo base_url('?page=inbounding&action=updatedesktopform&id='.$record_id); ?>" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="save_action" id="hidden_save_action" value="">
        <input type="hidden" name="userid_log" value="<?php echo $_SESSION['user']['id'] ?? ''; ?>">
        <div class="flex flex-col md:flex-row items-stretch w-full gap-4 md:gap-0">
            <div class="shrink-0 w-full md:w-[150px] bg-[#f4f4f4] border border-[#777] rounded-md p-1 md:ml-5 relative h-[200px] md:h-[200px] group">
                <div class="w-full h-full relative flex items-center justify-center bg-white rounded-[3px] overflow-hidden">
                    <?php 
                        $mainPhoto = $data['form2']['product_photo'] ?? ''; 
                        $hasMainPhoto = !empty($mainPhoto);

                        // FIX: Just pass the full path. The function will find the folder.
                        $mainPhotoThumb = $hasMainPhoto ? base_url(getThumbnail($mainPhoto)) : '';
                    ?>
                    <img id="main_photo_preview" 
                         src="<?= $mainPhotoThumb ?>" 
                         onclick="openImagePopup('<?= $hasMainPhoto ? base_url($mainPhoto) : '' ?>')"
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
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 md:gap-[30px] mb-[15px] items-end">
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
                <div>
                    <label class="block text-xs font-bold text-[#222] mb-1 pt-[10px]">Enter Remarks:</label>
                    <textarea 
                        id="feedback" 
                        name="feedback" 
                        autocomplete="off" 
                        class="w-full min-h-[100px] border border-[#ccc] rounded-[4px] px-2.5 py-2 text-[13px] text-[#333] focus:outline-none focus:border-[#999] resize-y"
                    ><?= htmlspecialchars($data['form2']['feedback'] ?? '') ?></textarea>
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
                            <input type="text" class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[40px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" value="<?= htmlspecialchars($data['form2']['gst_rate'] ?? 0) ?>" name="gst_rate">
                            <span class="absolute right-[10px] text-xs text-[#777] pointer-events-none">%</span>
                        </div>
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Dimensions:</label>
                        <div class="relative flex items-center w-full">
                            <input type="text" class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[40px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" value="<?= htmlspecialchars($data['form2']['dimensions'] ?? '') ?>" name="dimensions" placeholder="Dimensions">
                        </div>
                    </div>
                    <?php echo renderColorMapField('colormaps', $data['form2']['colormaps'] ?? ''); ?>
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
                                    $rawPhoto = $var['variation_image'] ?? '';
                                    $hasPhoto = (!empty($rawPhoto) && $rawPhoto !== '0');
                                    
                                    // Pass FULL path to generator
                                    $varThumbSrc = $hasPhoto ? base_url(getThumbnail($rawPhoto)) : '#';
                                ?>
                                <img src="<?= $varThumbSrc ?>" 
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
                            <div class="w-full min-w-0">
                                <label class="block text-xs font-bold text-[#555] mb-1">Price India:</label>
                                <div class="relative w-full">
                                    <input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" 
                                           value="<?= htmlspecialchars($var['price_india'] ?? '') ?>" 
                                           name="variations[<?= $var['id'] ?>][price_india]">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">INR</span>
                                </div>
                            </div>
                            <div class="w-full min-w-0">
                                <label class="block text-xs font-bold text-[#555] mb-1">Price India MRP:</label>
                                <div class="relative w-full">
                                    <input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" 
                                           value="<?= htmlspecialchars($var['price_india_mrp'] ?? '') ?>" 
                                           name="variations[<?= $var['id'] ?>][price_india_mrp]">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">INR</span>
                                </div>
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-bold text-[#222] mb-[5px]">USD Price:</label>
                                <div class="relative flex items-center w-full">
                                    <input type="text" class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[40px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]" 
                                           value="<?= htmlspecialchars($var['usd_price'] ?? '') ?>" 
                                           name="variations[<?= $var['id'] ?>][usd_price]">
                                    <span class="absolute right-[10px] text-xs text-[#777] pointer-events-none">USD</span>
                                </div>
                            </div>
                            <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">HSN Code:</label><div class="relative w-full"><input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" value="<?= htmlspecialchars($var['hsn_code'] ?? '') ?>" name="variations[<?= $var['id'] ?>][hsn_code]"></div></div>
                            <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">GST:</label><div class="relative w-full"><input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" value="<?= htmlspecialchars($var['gst_rate'] ?? 0) ?>" name="variations[<?= $var['id'] ?>][gst_rate]"><span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">%</span></div></div>
                            <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">Dimensions:</label><div class="relative w-full"><input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" value="<?= htmlspecialchars($var['dimensions'] ?? '') ?>" name="variations[<?= $var['id'] ?>][dimensions]"></div></div>
                            <input type="hidden" name="variations[<?= $var['id'] ?>][id]" value="<?= $var['id'] ?>">
                            
                            <?php echo renderColorMapField("variations[{$var['id']}][colormaps]", $var['colormaps'] ?? ''); ?>
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
                <div class="w-full min-w-0">
                    <label class="block text-xs font-bold text-[#555] mb-1">Price India:</label>
                    <div class="relative w-full">
                        <input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" 
                               name="variations[INDEX][price_india]">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">INR</span>
                    </div>
                </div>
                <div class="w-full min-w-0">
                    <label class="block text-xs font-bold text-[#555] mb-1">Price India MRP:</label>
                    <div class="relative w-full">
                        <input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" 
                               name="variations[INDEX][price_india_mrp]">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">INR</span>
                    </div>
                </div>
                <div class="w-full min-w-0">
                    <label class="block text-xs font-bold text-[#555] mb-1">USD Price:</label>
                    <div class="relative w-full">
                        <input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" 
                               name="variations[INDEX][usd_price]">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">USD</span>
                    </div>
                </div>
                <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">HSN Code:</label><div class="relative w-full"><input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" name="variations[INDEX][hsn_code]"></div></div>
                <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">GST:</label><div class="relative w-full"><input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" name="variations[INDEX][gst_rate]"><span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#777] pointer-events-none">%</span></div></div>
                <div class="w-full min-w-0"><label class="block text-xs font-bold text-[#555] mb-1">Dimensions:</label><div class="relative w-full"><input type="text" class="w-full h-10 border border-[#ccc] rounded-[3px] pl-3 pr-10 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]" name="variations[INDEX][dimensions]"></div></div>
                <div class="w-full min-w-0 colormap-wrapper" style="display:none;">
                    <label class="block text-xs font-bold text-[#555] mb-1">Color Map:</label>
                    <select name="variations[INDEX][colormaps]" class="colormap-select w-full h-10 border border-[#ccc] rounded-[3px] px-2 text-[13px] text-[#333] focus:outline-none focus:border-[#d97824]">
                        <option value="">Select Color Map</option>
                    </select>
                </div>
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
    // ... inside your existing renderPhotoCard function ...

    function renderPhotoCard($img, $varId) {
        $fullPath = "uploads/itm_img/" . $img['file_name'];

        // 2. Pass the FULL PATH to the generator
        $thumbSrc = getThumbnail($fullPath); 
        
        // 3. Keep original for the popup
        $originalSrc = $fullPath; 
    ?>
        <div class="draggable-item relative border border-[#ddd] rounded-[4px] p-2 bg-white flex flex-col items-center group cursor-grab active:cursor-grabbing shadow-sm" 
             draggable="true" 
             data-id="<?php echo $img['id']; ?>">
            
            <div class="absolute top-1 right-1 text-gray-400 p-1 bg-white rounded shadow-sm opacity-50 group-hover:opacity-100 transition">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="12" r="1"/><circle cx="9" cy="5" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="19" r="1"/></svg>
            </div>

            <div class="w-full h-32 bg-white flex items-center justify-center overflow-hidden rounded-[2px] border border-[#eee] mb-2" 
                 onclick="openImagePopup('<?php echo $originalSrc; ?>')">
                <img src="<?php echo $thumbSrc; ?>" 
                     loading="lazy" 
                     class="max-w-full max-h-full object-contain cursor-pointer">
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
                    <div class="w-full">
                        <label class="block text-xs font-bold text-[#222] mb-1">Material:</label>
                        <div class="flex gap-2 items-center w-full">
                            <div class="flex-1 w-full min-w-0"> 
                                <select id="material_select" name="material_code" placeholder="Select Material..." autocomplete="off">
                                    <option value="">Select Material</option>
                                    <?php foreach ($data['material'] as $value2) { 
                                        $isSelected = ($selected_material == $value2['id']) ? 'selected' : '';
                                    ?> 
                                        <option <?php echo $isSelected; ?> value="<?php echo $value2['id']; ?>"> <?php echo $value2['material_name']; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <button type="button" onclick="openMaterialModal()" class="h-[36px] w-[36px] shrink-0 bg-[#28a745] hover:bg-[#218838] text-white rounded-[4px] flex items-center justify-center shadow-sm transition" title="Add New Material">
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
                            <div class="p-1 border-b border-gray-200 bg-gray-50">
                                <input type="text" id="main_cat_search" placeholder="Search..." 
                                       class="w-full h-[28px] text-xs border border-gray-300 rounded px-2 focus:outline-none focus:border-[#d97824]">
                            </div>
                            <div id="category_container" class="checkbox-list-container overflow-y-auto p-1 h-full">
                                <div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Group to view options</div>
                            </div>
                        </div>
                    </div>

                    <div class="w-full md:w-1/3 flex flex-col">
                        <label class="block text-xs font-bold text-[#222] mb-1">Sub Category:</label>
                        <div class="border border-[#ccc] rounded-[4px] bg-white flex-grow h-[200px] flex flex-col">
                            <div class="p-1 border-b border-gray-200 bg-gray-50">
                                <input type="text" id="sub_cat_search" placeholder="Search..." 
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
                                <input type="text" id="sub_sub_cat_search" placeholder="Search..." 
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
        <?php 
            // 1. PARSE SAVED DATA (If exists)
            // Assuming you store this string in a column named 'search_category_string'
            // Format: "1155,1145|1323,1450|124,132|-5" (SubSub | Sub | Cat | Group)
            
            $saved_search_string = $data['form2']['search_category_string'] ?? ''; 
            $search_parts = explode('|', $saved_search_string);
            // Map the exploded parts to variables (Default to empty if not found)
            // Order: SubSub | Sub | Cat | Group
            $search_sub_sub_raw = $search_parts[0] ?? '';
            $search_sub_raw     = $search_parts[1] ?? '';
            $search_cat_raw     = $search_parts[2] ?? '';
            $search_group_val   = $search_parts[3] ?? '';
            // Convert comma-strings to arrays for JS
            $search_sel_sub_sub = array_filter(explode(',', $search_sub_sub_raw));
            $search_sel_sub     = array_filter(explode(',', $search_sub_raw));
            $search_sel_cat     = array_filter(explode(',', $search_cat_raw));
        ?>
        <div class="mt-[15px] md:mx-5">
            <fieldset class="border border-[#ccc] rounded-[5px] px-[15px] py-4 bg-gray-50">
                <legend class="text-[13px] font-bold text-[#333] px-[5px]">Search Category (Related Items)</legend>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                    <div>
                        <label class="block text-xs font-bold text-[#222] mb-1">Search Group:</label>
                        <select id="search_group_select" name="search_group" placeholder="Select Group..." autocomplete="off">
                            <option value="">Select Group...</option>
                            <?php foreach($rootCategories as $group): 
                                $isSearchGroupSelected = ($search_group_val == $group['store_value']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $group['store_value']; ?>" <?php echo $isSearchGroupSelected; ?>>
                                    <?php echo $group['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row gap-5 items-stretch">
                    
                    <div class="w-full md:w-1/3 flex flex-col">
                        <label class="block text-xs font-bold text-[#222] mb-1">Search Category:</label>
                        <div class="border border-[#ccc] rounded-[4px] bg-white flex-grow h-[200px] flex flex-col">
                            <div class="p-1 border-b border-gray-200 bg-gray-50">
                                <input type="text" id="search_cat_search" placeholder="Search..." 
                                       class="w-full h-[28px] text-xs border border-gray-300 rounded px-2 focus:outline-none focus:border-[#d97824]">
                            </div>
                            <div id="search_category_container" class="checkbox-list-container overflow-y-auto p-1 h-full">
                                <div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Group...</div>
                            </div>
                        </div>
                    </div>

                    <div class="w-full md:w-1/3 flex flex-col">
                        <label class="block text-xs font-bold text-[#222] mb-1">Search Sub Category:</label>
                        <div class="border border-[#ccc] rounded-[4px] bg-white flex-grow h-[200px] flex flex-col">
                            <div class="p-1 border-b border-gray-200 bg-gray-50">
                                <input type="text" id="search_sub_cat_search" placeholder="Search..." 
                                       class="w-full h-[28px] text-xs border border-gray-300 rounded px-2 focus:outline-none focus:border-[#d97824]">
                            </div>
                            <div id="search_sub_category_container" class="checkbox-list-container overflow-y-auto p-1 flex-grow">
                                <div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Category...</div>
                            </div>
                        </div>
                    </div>

                    <div class="w-full md:w-1/3 flex flex-col">
                        <label class="block text-xs font-bold text-[#222] mb-1">Search SubSubCategory:</label>
                        <div class="border border-[#ccc] rounded-[4px] bg-white flex-grow h-[200px] flex flex-col">
                            <div class="p-1 border-b border-gray-200 bg-gray-50">
                                <input type="text" id="search_sub_sub_cat_search" placeholder="Search..." 
                                       class="w-full h-[28px] text-xs border border-gray-300 rounded px-2 focus:outline-none focus:border-[#d97824]">
                            </div>
                            <div id="search_sub_sub_category_container" class="checkbox-list-container overflow-y-auto p-1 flex-grow">
                                <div class="text-xs text-gray-400 p-2 text-center mt-10">Select Sub Category...</div>
                            </div>
                        </div>
                    </div>

                </div>
            </fieldset>
        </div>
        <div class="mt-[15px] md:mx-5">
            <fieldset class="border border-[#ccc] rounded-[5px] px-[15px] py-4 bg-white">
                <legend class="text-[13px] font-bold text-[#333] px-[5px]">Search Terms</legend>
                <div>
                    <label class="block text-xs font-bold text-[#222] mb-1">Enter Search Terms:</label>
                    <input type="text" name="search_term" 
                           value="<?= htmlspecialchars($data['form2']['search_term'] ?? '') ?>" 
                           placeholder="Type search terms..." 
                           autocomplete="off"
                           class="w-full h-[34px] border border-[#ccc] rounded-[4px] px-2.5 text-[13px] text-[#333] focus:outline-none focus:border-[#999]">
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
                    <textarea 
                        id="keywords_input" 
                        name="key_words"
                        placeholder="Type keyword and press Enter or Comma..."
                        class="w-full min-h-[60px] border border-[#ccc] rounded-[4px] px-2.5 py-2 text-[13px] text-[#333] focus:outline-none focus:border-[#999] resize-y"
                    ><?= htmlspecialchars($data['form2']['key_words'] ?? '') ?></textarea>
                    
                    <div class="text-[10px] text-gray-500 mt-1">
                        Type text and press <strong>Enter</strong> or <strong>Comma (,)</strong> to add a tag.
                    </div>
                </div>
                <div class="mb-[15px]">
                    <label class="block text-xs font-bold text-[#222] mb-[5px]">Snippet Description:</label>
                    <textarea 
                        class="w-full min-h-[80px] border border-[#ccc] rounded-[4px] px-2.5 py-2 text-[13px] text-[#333] focus:outline-none focus:border-[#999] resize-y" 
                        name="snippet_description"><?= htmlspecialchars($data['form2']['snippet_description'] ?? '') ?></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-xs font-bold text-[#222] mb-[5px]">Select Optionals:</label>
                    <div class="border border-[#ccc] rounded-[4px] bg-white h-[200px] flex flex-col">
                        <div class="checkbox-list-container overflow-y-auto p-1 h-full">
                            <?php 
                                $source_data = $data['form2']['optionals_data'] ?? [];
                                if (isset($source_data['optionals']) && is_array($source_data['optionals'])) {
                                    $available_options = $source_data['optionals'];
                                } else {
                                    $available_options = $source_data;
                                }
                                $saved_raw = $data['form2']['optionals'] ?? []; 
                                $saved_values = [];
                                if (is_array($saved_raw)) {
                                    $saved_values = $saved_raw;
                                } elseif (is_string($saved_raw)) {
                                    $saved_values = array_map('trim', explode(',', $saved_raw));
                                }
                                if (!empty($available_options) && is_array($available_options)) {
                                    foreach ($available_options as $key => $val_str) {
                                        if (is_array($val_str)) continue; 
                                        $val_str = (string)$val_str; 
                                        $label = str_replace(['OPTIONALS_', '_'], ['', ' '], $val_str); 
                                        $label = ucwords(strtolower($label));                
                                        $isChecked = in_array($val_str, $saved_values) ? 'checked' : '';
                                        $uniqueId = 'opt_' . md5($val_str); 
                            ?>
                                        <div class="checkbox-item flex items-center p-2 hover:bg-gray-50 border-b border-gray-100 last:border-0">
                                            <input type="checkbox" id="<?= $uniqueId ?>" name="optionals[]" value="<?= htmlspecialchars($val_str) ?>" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 cursor-pointer mr-2" <?= $isChecked ?>>
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
                                   value="<?= htmlspecialchars($data['form2']['backorder_percent'] ?? '20') ?>" 
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
                            <input type="text" name="marketplace" 
                                   value="<?php 
                                        if (is_null($data['form2']['Marketplace'])) {
                                            echo "exoticindia";
                                        } else {
                                            echo htmlspecialchars($data['form2']['Marketplace']);
                                        } 
                                    ?>" 
                                   class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[45px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]">
                        </div>
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-[#222] mb-[5px]">Indian Net Qty.:</label>
                        <div class="relative w-full">
                            <input type="number" name="india_net_qty" 
                                   value="<?= htmlspecialchars($data['form2']['india_net_qty'] ?? '1') ?>" 
                                   class="w-full h-[32px] border border-[#ccc] rounded-[3px] pl-[10px] pr-[45px] text-[13px] text-[#333] focus:outline-none focus:border-[#999]">
                        </div>
                    </div>
                    <div class="flex-1 sm:col-span-2 lg:col-span-2 flex items-start border border-[#eee] rounded bg-gray-50 p-1"> 
    
                        <div class="flex-1 pr-4 border-r border-[#ccc] flex flex-col justify-center h-[52px]"> 
                            <label class="block text-xs font-bold text-[#222] mb-[3px]">US Block:</label>
                            <div class="flex items-center gap-4">
                                <?php $us_val = $data['form2']['us_block'] ?? 'N'; ?>
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
                    <div class="flex-1 pl-4 flex flex-col justify-center min-w-[200px]"> 
                        <label class="block text-xs font-bold text-[#222] mb-[3px]">Image Directory:</label>
                        <input type="text" 
                               id="image_directory_input" 
                               name="image_directory" 
                               readonly
                               value="<?php echo htmlspecialchars($data['form2']['image_directory'] ?? ''); ?>"
                               class="w-full h-[32px] border border-[#ccc] rounded-[3px] px-[10px] text-[13px] text-[#333] bg-gray-100 cursor-not-allowed focus:outline-none"
                               placeholder="Auto-generated...">
                    </div>
                </div>
            </fieldset>
        </div>
        <div class="flex justify-end gap-4 my-[25px] md:mx-5 mb-10">
            <?php if (isset($data['form2']['Item_code']) && !empty($data['form2']['Item_code'])) { ?>
                <button type="button" onclick="openPublishPopup()" class="bg-[#28a745] text-white border-none rounded-[4px] py-[10px] px-[30px] font-bold text-sm cursor-pointer shadow-md hover:bg-[#218838] transition flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    Publish Product
                </button>
            <?php  } ?>

            <button type="button" onclick="validateAndSubmit('draft')" class="bg-[#d97824] text-white border-none rounded-[4px] py-[10px] px-[30px] font-bold text-sm cursor-pointer shadow-md hover:bg-[#db8235] transition">
                Save as Draft
            </button>
            
            <button type="button" onclick="validateAndSubmit('generate')" class="bg-[#d97824] text-white border-none rounded-[4px] py-[10px] px-[30px] font-bold text-sm cursor-pointer shadow-md hover:bg-[#c0651a] transition">
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
    
    // Track the item currently being dragged
    let draggedItem = null;
    // 1. DRAG START (Global Listener)
    document.addEventListener('dragstart', function(e) {
        // Only trigger if the element is one of our draggable items
        const item = e.target.closest('.draggable-item');
        if (!item) return;
        draggedItem = item;
        item.classList.add('dragging'); // Used for styling and logic
        item.style.opacity = '0.5';     // Visual feedback
        
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', ''); // Required for Firefox
    });
    // 2. DRAG END (Cleanup)
    document.addEventListener('dragend', function(e) {
        const item = e.target.closest('.draggable-item');
        if (!item) return;
        item.classList.remove('dragging');
        item.style.opacity = '1';
        draggedItem = null;
    });
    // 3. DRAG OVER (The "Shuffle" Logic)
    document.addEventListener('dragover', function(e) {
        // If we aren't dragging a valid item, ignore
        if (!draggedItem) return;
        // Check if we are over a valid Drop Zone (The Grid)
        const container = e.target.closest('.photo-group-grid');
        if (!container) return;
        e.preventDefault(); // Necessary to allow dropping
        // Calculate where to place the item based on Mouse X position
        const afterElement = getDragAfterElement(container, e.clientX);
        
        // Move the DOM element live (Swapping effect)
        if (afterElement == null) {
            container.appendChild(draggedItem);
        } else {
            container.insertBefore(draggedItem, afterElement);
        }
    });
    // 4. DROP (Save Data)
    document.addEventListener('drop', function(e) {
        e.preventDefault(); // Prevent browser default (opening file)
        
        // We only care if we dropped into a valid grid
        const container = e.target.closest('.photo-group-grid');
        
        if (draggedItem && container) {
            // A. Update the Hidden Variation ID Input
            // This ensures the photo is now assigned to the new color/variation
            const newVarId = container.getAttribute('data-var-id');
            const varInput = draggedItem.querySelector('.variation-input');
            if(varInput) {
                varInput.value = newVarId;
            }
            // B. Recalculate Sort Order for ALL grids
            // (We update all because moving an item changes the order in both Source and Target)
            document.querySelectorAll('.photo-group-grid').forEach(grid => {
                updateOrderInputs(grid);
            });
        }
    });
    // --- HELPER: Calculate Position based on X Axis ---
    function getDragAfterElement(container, x) {
        // Get all items in this grid EXCEPT the one we are dragging
        const draggableElements = [...container.querySelectorAll('.draggable-item:not(.dragging)')];
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            
            // Find the horizontal center of the child
            const boxCenter = box.left + box.width / 2;
            
            // Distance between cursor and center
            const offset = x - boxCenter;
            // We are looking for the element where the cursor is to the LEFT of its center
            // (negative offset) but closest to 0 (smallest negative number)
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }
    // --- HELPER: Update hidden input values (1, 2, 3...) ---
    function updateOrderInputs(container) {
        if(!container) return;
        const currentItems = container.querySelectorAll('.draggable-item');
        currentItems.forEach((item, index) => {
            const input = item.querySelector('.order-input');
            if(input) {
                input.value = index + 1;
            }
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
        const imgDirSelect = document.getElementById('image_directory_select');
            if (imgDirSelect && imgDirSelect.tomselect) {
                if (val === 'Y') {
                    imgDirSelect.tomselect.clear();
                    imgDirSelect.tomselect.disable();
                } else {
                    imgDirSelect.tomselect.enable();
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
        // new TomSelect("#material_select", config);
        // new TomSelect("#variant_select", commonConfig);
        new TomSelect("#material_select", {
            create: false,
            sortField: { field: "text", direction: "asc" },
            onInitialize: function() {
                // This forces the dropdown to take 100% width of the parent div
                this.wrapper.classList.add('w-full'); 
                this.control.classList.add('h-[36px]'); // Matches button height
            }
        });
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
    
    // --- 1. DATA: Safe Import from PHP ---
    const searchDataMap = <?php echo json_encode($categoriesByParent1); ?>;
    
    // --- 2. PRE-SELECTION VALUES ---
    const searchPreSelected = {
        groupVal: "<?php echo $search_group_val; ?>",
        cat:    new Set(<?php echo json_encode(array_values($search_sel_cat)); ?>.map(String)), 
        sub:    new Set(<?php echo json_encode(array_values($search_sel_sub)); ?>.map(String)), 
        subsub: new Set(<?php echo json_encode(array_values($search_sel_sub_sub)); ?>.map(String))
    };

    // --- 3. DOM ELEMENTS ---
    const sGroupSelectEl = document.getElementById("search_group_select");
    const sCatContainer = document.getElementById('search_category_container');
    const sSubCatContainer = document.getElementById('search_sub_category_container');
    const sSubSubCatContainer = document.getElementById('search_sub_sub_category_container');

    // --- 4. INITIALIZE GROUP DROPDOWN (TomSelect) ---
    const config = { create: false, sortField: { field: "text", direction: "asc" }, controlInput: null };
    let sGroupTs = null;

    if(sGroupSelectEl) {
        // Reuse instance if exists, otherwise create
        if (sGroupSelectEl.tomselect) sGroupTs = sGroupSelectEl.tomselect;
        else sGroupTs = new TomSelect(sGroupSelectEl, config);

        sGroupTs.on('change', function(groupValue) {
            // Clear downstream selections on group change
            searchPreSelected.cat.clear(); 
            searchPreSelected.sub.clear(); 
            searchPreSelected.subsub.clear();        
            updateSearchCatList(groupValue);
        });
    }

    // --- HELPER: Create HTML for a Checkbox Item ---
    function createSearchCheckbox(item, inputName, selectedSet, onChangeCallback) {
        const div = document.createElement('div');
        div.className = 'checkbox-item pl-2 flex items-center p-1 hover:bg-gray-50'; // Styling
        
        const valToCheck = String(item.store_val);
        const isChecked = selectedSet.has(valToCheck) ? 'checked' : '';

        div.innerHTML = `
            <input type="checkbox" 
                   id="${inputName}_${item.id}" 
                   name="${inputName}[]" 
                   value="${item.store_val}" 
                   data-parent-path="" 
                   ${isChecked}
                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer mr-2">
            <label for="${inputName}_${item.id}" class="text-[13px] text-[#333] cursor-pointer select-none w-full">${item.name}</label>
        `;
        
        const checkbox = div.querySelector('input');
        checkbox.addEventListener('change', () => { if(onChangeCallback) onChangeCallback(); });
        return div;
    }

    // --- 5. RENDER FUNCTIONS ---

    // A. Render Category List (Column 1)
    function updateSearchCatList(rawGroupValue) {
        // Reset downstream containers
        sSubCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Category...</div>';
        sSubSubCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Sub Category...</div>';

        if(!rawGroupValue) {
             sCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Group...</div>';
             return;
        }

        const lookupKey = String(rawGroupValue).trim(); 
        
        if(!searchDataMap[lookupKey]) {
            sCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">No categories found</div>';
            return;
        }

        const items = searchDataMap[lookupKey];
        sCatContainer.innerHTML = '';
        const seenIds = new Set();

        items.forEach(item => {
            if(!seenIds.has(item.id)){
                seenIds.add(item.id);
                sCatContainer.appendChild(createSearchCheckbox(item, 'search_cat', searchPreSelected.cat, handleSearchCatChange));
            }
        });
        
        // Refresh Search Logic for Col 1
        triggerSearch('search_cat_search');
        
        // If we had pre-selected items, trigger next level load
        if(searchPreSelected.cat.size > 0) handleSearchCatChange();
    }

    // B. Render Sub-Category List (Column 2) - WITH PARENT HEADERS
    function handleSearchCatChange() {
        const checkedInputs = Array.from(sCatContainer.querySelectorAll('input[type="checkbox"]:checked'));
        sSubCatContainer.innerHTML = ''; 
        sSubSubCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Sub Category...</div>';

        if(checkedInputs.length === 0) {
            sSubCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Category...</div>';
            return;
        }

        const groupVal = sGroupTs ? sGroupTs.getValue() : sGroupSelectEl.value;
        const groupKey = String(groupVal).trim(); 
        let hasAnyOptions = false;

        checkedInputs.forEach(input => {
            const catStoreVal = input.value; 
            const parentName = input.nextElementSibling.innerText; // Get Parent Name
            const lookupKey = catStoreVal + "|" + groupKey;

            const children = searchDataMap[lookupKey];
            
            if (children && children.length > 0) {
                hasAnyOptions = true;
                
                // >>> HEADER INJECTION <<<
                const header = document.createElement('div');
                header.className = "text-[11px] font-bold text-[#d97824] bg-gray-50 px-2 py-1 border-b border-t border-gray-200 mt-0 sticky top-0 z-10 group-header";
                header.innerText = parentName; 
                sSubCatContainer.appendChild(header);
                // >>> END HEADER <<<

                children.forEach(item => {
                    const el = createSearchCheckbox(item, 'search_sub', searchPreSelected.sub, handleSearchSubCatChange);
                    el.querySelector('input').setAttribute('data-parent-path', lookupKey);
                    sSubCatContainer.appendChild(el);
                });
            }
        });

        if(!hasAnyOptions) sSubCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">No Sub Categories found</div>';
        
        triggerSearch('search_sub_cat_search');
        if(searchPreSelected.sub.size > 0) handleSearchSubCatChange();
    }

    // C. Render Sub-Sub-Category List (Column 3) - WITH PARENT HEADERS
    function handleSearchSubCatChange() {
        const checkedInputs = Array.from(sSubCatContainer.querySelectorAll('input[type="checkbox"]:checked'));
        sSubSubCatContainer.innerHTML = ''; 
        
        if(checkedInputs.length === 0) {
            sSubSubCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select Sub Category...</div>';
            return;
        }

        let hasAnyOptions = false;

        checkedInputs.forEach(input => {
            const subCatStoreVal = input.value;
            const parentPath = input.getAttribute('data-parent-path'); 
            const parentName = input.nextElementSibling.innerText; // Get Parent Name
            const lookupKey = subCatStoreVal + "|" + parentPath;
            
            const children = searchDataMap[lookupKey];
            
            if (children && children.length > 0) {
                hasAnyOptions = true;
                
                // >>> HEADER INJECTION <<<
                const header = document.createElement('div');
                header.className = "text-[11px] font-bold text-[#d97824] bg-gray-50 px-2 py-1 border-b border-t border-gray-200 mt-0 sticky top-0 z-10 group-header";
                header.innerText = parentName;
                sSubSubCatContainer.appendChild(header);
                // >>> END HEADER <<<

                children.forEach(item => {
                    sSubSubCatContainer.appendChild(createSearchCheckbox(item, 'search_sub_sub', searchPreSelected.subsub, null));
                });
            }
        });

        if(!hasAnyOptions) sSubSubCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">No Sub Sub Categories found</div>';
        triggerSearch('search_sub_sub_cat_search');
    }

    // --- 6. SMART SEARCH LOGIC (Filters + Keeps Headers Visible) ---
    function enableSearchLogic(inputId, containerId) {
        const input = document.getElementById(inputId);
        const container = document.getElementById(containerId);
        
        // Safety Check: Only run if elements exist
        if(input && container) {
            input.addEventListener('keyup', function() {
                const filter = this.value.toLowerCase().trim();
                const allElements = Array.from(container.children);
                
                let currentHeader = null;
                let headerMatches = false;
                let visibleCountInGroup = 0;

                // Reset: If search is empty, show everything
                if(filter === "") {
                    allElements.forEach(el => el.style.display = ''); 
                    return;
                }

                // Iterate over all items in the list
                for (let i = 0; i < allElements.length; i++) {
                    const el = allElements[i];

                    // CASE 1: It's a Header (Parent Name)
                    if (el.classList.contains('group-header')) {
                        // If we are moving to a NEW header, decide if the PREVIOUS header should be shown
                        if (currentHeader) {
                            const showHeader = headerMatches || (visibleCountInGroup > 0);
                            currentHeader.style.display = showHeader ? 'block' : 'none';
                        }
                        
                        // Start tracking this new group
                        currentHeader = el;
                        // Does the Header itself match the search? (e.g. searching "Mens" in "Mens Wear")
                        headerMatches = el.innerText.toLowerCase().includes(filter);
                        visibleCountInGroup = 0;
                        el.style.display = 'none'; // Temporarily hide, will decide at next header/end
                    } 
                    // CASE 2: It's a Checkbox Item
                    else if (el.classList.contains('checkbox-item')) {
                        const label = el.querySelector('label').innerText.toLowerCase();
                        
                        // Show item IF: The header matched OR The item itself matches
                        if (headerMatches || label.includes(filter)) {
                            el.style.display = 'flex';
                            visibleCountInGroup++;
                        } else {
                            el.style.display = 'none';
                        }
                    }
                }

                // Handle the very last group after loop finishes
                if (currentHeader) {
                    const showHeader = headerMatches || (visibleCountInGroup > 0);
                    currentHeader.style.display = showHeader ? 'block' : 'none';
                }
            });
        } else {
            console.warn("Search Logic: Input or Container not found for ID:", inputId);
        }
    }

    function triggerSearch(id) {
        const el = document.getElementById(id);
        if(el && el.value) el.dispatchEvent(new Event('keyup'));
    }

    // --- 7. STARTUP ---
    if (searchPreSelected.groupVal) {
        updateSearchCatList(searchPreSelected.groupVal);
    }

    // Enable Search on all 3 columns
    enableSearchLogic('search_cat_search', 'search_category_container');
    enableSearchLogic('search_sub_cat_search', 'search_sub_category_container');
    enableSearchLogic('search_sub_sub_cat_search', 'search_sub_sub_category_container');
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
         h = h + 4 ;
         w = w + 4;
         d = d + 4;
        // --- STEP A: NORMALIZE TO INCHES ---
        // If user entered CM, convert to Inch first (divide by 2.54)
        // if (dimUnit === 'cm') {
             h = h * 2.54;
             w = w * 2.54;
             d = d * 2.54;
        // }
        // --- STEP B: ADD BUFFER (4 inches) ---
        let h_in = h;
        let w_in = w;
        let d_in = d;
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    // 3. Trigger Controller Function
    function triggerPublishController() {
        // Get the current ID
        const urlParams = new URLSearchParams(window.location.search);
        const recordId = urlParams.get('id');
        // CHECK: Is the library loaded?
        if (typeof Swal === 'undefined') {
            alert("Error: SweetAlert2 library not loaded. Please check Fix 1.");
            return;
        }
        if (!recordId) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Record ID not found in URL.' });
            closePublishPopup();
            return;
        }
        // Visual Feedback
        const confirmBtn = document.querySelector('#publishConfirmPopup button.confirm-btn') || document.querySelector('#publishConfirmPopup button:last-child');
        let originalText = "Yes, Publish";
        
        if (confirmBtn) {
            originalText = confirmBtn.innerText;
            confirmBtn.innerText = "Processing...";
            confirmBtn.disabled = true;
        }
        const targetUrl = `index.php?page=inbounding&action=inbound_product_publish&id=${recordId}`;
        fetch(targetUrl)
        .then(response => {
            if (!response.ok) throw new Error('Network error: ' + response.status);
            return response.text(); 
        })
        .then(text => {
            // Handle Blank = Success
            if (!text || text.trim() === '') {
                return { status: 'success', message: 'Published Successfully!' };
            }
            try {
                return JSON.parse(text);
            } catch (e) {
                if (text.toLowerCase().includes('error')) throw new Error('Server Error: ' + text);
                return { status: 'success', message: 'Published Successfully!' };
            }
        })
        .then(data => {
            if (data.status === 'error') throw new Error(data.message);
            
            closePublishPopup();
            // SUCCESS POPUP
            Swal.fire({
                title: 'Published!',
                text: data.message || "Product has been published.",
                icon: 'success',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Great!'
            }).then((result) => {
                if (result.isConfirmed) window.location.reload();
            });
        })
        .catch(error => {
            closePublishPopup();
            // ERROR POPUP
            Swal.fire({
                icon: 'error',
                title: 'Failed',
                text: error.message,
                confirmButtonColor: '#d33'
            });
        })
        .finally(() => {
            if (confirmBtn) {
                confirmBtn.innerText = originalText;
                confirmBtn.disabled = false;
            }
        });
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
                            'quantity_received', 'cp', 'price_india', 'price_india_mrp', 'usd_price', 'hsn_code', 
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
         h = h + 4;
         w = w + 4;
         d = d + 4;
        let h_in = h / 2.54;
        let w_in = w / 2.54;
        let d_in = d / 2.54;
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
    document.body.addEventListener('input', function(e) {
        if (e.target.matches('.calc-h, .calc-w, .calc-d, .calc-wt') || 
            e.target.id === 'dim_height' || 
            e.target.id === 'dim_width' || 
            e.target.id === 'dim_depth' || 
            e.target.id === 'dim_weight') {
            const card = e.target.closest('.calculation-card') || e.target.closest('.bg-gray-50');
            updateCardPrice(card);
        }
    });
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
                        mainPreview.src = evt.target.result;
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
        document.body.addEventListener('input', function(e) {
            if (e.target.name && e.target.name.includes('price_india') && !e.target.name.includes('mrp')) {
                
                const inrInput = e.target;
                const inrValue = parseFloat(inrInput.value);
                const container = inrInput.closest('.grid') || inrInput.closest('.calculation-card');
                if (container) {
                    const mrpInput = container.querySelector('input[name*="price_india_mrp"]');
                    if (mrpInput) {
                        if (!isNaN(inrValue)) {
                            // Calculate 20% increase
                            const calculatedPrice = inrValue + (inrValue * 0.20);
                            // Update the MRP field
                            mrpInput.value = calculatedPrice.toFixed(2);
                        } else {
                            // If INR is cleared/invalid, clear MRP
                            mrpInput.value = '';
                        }
                    }
                }
            }
        });
    });
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sizeOptionsObj = <?php echo json_encode($sizeOptions); ?>;
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
        const checkedBoxes = document.querySelectorAll('.checkbox-list-container input[type="checkbox"]:checked');
        checkedBoxes.forEach(cb => {
            const label = cb.nextElementSibling ? cb.nextElementSibling.innerText.toLowerCase() : '';
            if (label.includes('clothing') || label.includes('textile')) isClothing = true;
        });
        return isClothing;
    }
    function toggleAllSizeFields() {
        const isClothing = checkIsClothing();
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
    const groupSelect = document.getElementById('group_select');
    if(groupSelect) {
        groupSelect.addEventListener('change', toggleAllSizeFields); // Uses TomSelect change event if native
    }
    if(document.getElementById('group_select').tomselect) {
        document.getElementById('group_select').tomselect.on('change', toggleAllSizeFields);
    }
    // Listen to Checkbox Changes (Delegation for dynamic lists)
    document.body.addEventListener('change', function(e) {
        if (e.target.type === 'checkbox' && e.target.closest('.checkbox-list-container')) {
            toggleAllSizeFields();
        }
    });
    const originalAddVar = window.addNewVariation;
    window.addNewVariation = function() {
        // Run original function
        originalAddVar(); 
        
        // After adding, force a check to update the newly added field
        toggleAllSizeFields();
    };
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- FIX: RE-INJECT DATA FROM PHP SO THIS SCRIPT CAN SEE IT ---
    const categoriesByParent = <?php echo json_encode($categoriesByParent1); ?>;
    
    // 1. Pre-selection Data for Search Section
    const searchPreSelected = {
        groupVal: "<?php echo $search_group_val; ?>",
        cat:    new Set(<?php echo json_encode(array_values($search_sel_cat)); ?>.map(String)), 
        sub:    new Set(<?php echo json_encode(array_values($search_sel_sub)); ?>.map(String)), 
        subsub: new Set(<?php echo json_encode(array_values($search_sel_sub_sub)); ?>.map(String))
    };
    // 2. DOM Elements
    const sGroupSelectEl = document.getElementById("search_group_select");
    const sCatContainer = document.getElementById('search_category_container');
    const sSubCatContainer = document.getElementById('search_sub_category_container');
    const sSubSubCatContainer = document.getElementById('search_sub_sub_category_container');
    // 3. Init TomSelect for Search Group
    const config = { create: false, sortField: { field: "text", direction: "asc" }, controlInput: null };
    let sGroupTs = null;
    if(sGroupSelectEl) {
        sGroupTs = new TomSelect(sGroupSelectEl, config);
        // --- EVENT LISTENER FOR TOM SELECT ---
        sGroupTs.on('change', function(groupValue) {
            searchPreSelected.cat.clear(); 
            searchPreSelected.sub.clear(); 
            searchPreSelected.subsub.clear();       
            updateSearchCatList(groupValue);
        });
    }
    // --- HELPER: Create Checkbox ---
    function createSearchCheckbox(item, inputName, selectedSet, onChangeCallback) {
        const div = document.createElement('div');
        div.className = 'checkbox-item pl-2'; 
        const valToCheck = String(item.store_val);
        const isChecked = selectedSet.has(valToCheck) ? 'checked' : '';
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
    // --- 1. UPDATE SEARCH CATEGORY LIST ---
    function updateSearchCatList(rawGroupValue) {
        sSubCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Category...</div>';
        sSubSubCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Sub Category...</div>';
        if(!rawGroupValue) {
             sCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Group...</div>';
             return;
        }
        const lookupKey = String(rawGroupValue).trim(); 
        
        if(!categoriesByParent[lookupKey]) {
            sCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">No categories found</div>';
            return;
        }
        const items = categoriesByParent[lookupKey];
        sCatContainer.innerHTML = '';
        const seenIds = new Set();
        items.forEach(item => {
            if(!seenIds.has(item.id)){
                seenIds.add(item.id);
                sCatContainer.appendChild(createSearchCheckbox(item, 'search_cat', searchPreSelected.cat, handleSearchCatChange));
            }
        });
        
        if(searchPreSelected.cat.size > 0) handleSearchCatChange();
    }
    // --- 2. UPDATE SEARCH SUB CATEGORY LIST ---
    function handleSearchCatChange() {
        const checkedInputs = Array.from(sCatContainer.querySelectorAll('input[type="checkbox"]:checked'));
        sSubCatContainer.innerHTML = ''; 
        sSubSubCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Sub Category...</div>';
        if(checkedInputs.length === 0) {
            sSubCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Category...</div>';
            return;
        }
        const groupVal = sGroupTs ? sGroupTs.getValue() : sGroupSelectEl.value;
        const groupKey = String(groupVal).trim(); 
        let hasAnyOptions = false;
        checkedInputs.forEach(input => {
            const catStoreVal = input.value; 
            const parentName = input.nextElementSibling.innerText;
            const lookupKey = catStoreVal + "|" + groupKey;
            const children = categoriesByParent[lookupKey];
            if (children && children.length > 0) {
                hasAnyOptions = true;
                const header = document.createElement('div');
                header.className = "text-[11px] font-bold text-[#d97824] bg-gray-50 px-2 py-1 border-b border-t border-gray-200 mt-0 sticky top-0 z-10 group-header";
                header.innerText = parentName; 
                sSubCatContainer.appendChild(header);
                children.forEach(item => {
                    const el = createSearchCheckbox(item, 'search_sub', searchPreSelected.sub, handleSearchSubCatChange);
                    el.querySelector('input').setAttribute('data-parent-path', lookupKey);
                    sSubCatContainer.appendChild(el);
                });
            }
        });
        if(!hasAnyOptions) sSubCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">No Sub Categories found</div>';
        else if(searchPreSelected.sub.size > 0) handleSearchSubCatChange();
    }
    // --- 3. UPDATE SEARCH SUB SUB CATEGORY LIST ---
    function handleSearchSubCatChange() {
        const checkedInputs = Array.from(sSubCatContainer.querySelectorAll('input[type="checkbox"]:checked'));
        sSubSubCatContainer.innerHTML = ''; 
        if(checkedInputs.length === 0) {
            sSubSubCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select Sub Category...</div>';
            return;
        }
        let hasAnyOptions = false;
        checkedInputs.forEach(input => {
            const subCatStoreVal = input.value;
            const parentPath = input.getAttribute('data-parent-path'); 
            const parentName = input.nextElementSibling.innerText;
            const lookupKey = subCatStoreVal + "|" + parentPath;
            
            const children = categoriesByParent[lookupKey];
            if (children && children.length > 0) {
                hasAnyOptions = true;
                const header = document.createElement('div');
                header.className = "text-[11px] font-bold text-[#d97824] bg-gray-50 px-2 py-1 border-b border-t border-gray-200 mt-0 sticky top-0 z-10 group-header";
                header.innerText = parentName;
                sSubSubCatContainer.appendChild(header);
                children.forEach(item => {
                    sSubSubCatContainer.appendChild(createSearchCheckbox(item, 'search_sub_sub', searchPreSelected.subsub, null));
                });
            }
        });
        if(!hasAnyOptions) sSubSubCatContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">No Sub Sub Categories found</div>';
    }
    // --- SEARCH FILTER ---
    function enableSearchFilter(inputId, containerId) {
        const input = document.getElementById(inputId);
        const container = document.getElementById(containerId);
        if(input && container) {
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
    }
    // --- INITIAL LOAD ---
    if (searchPreSelected.groupVal) {
        updateSearchCatList(searchPreSelected.groupVal);
    }
    enableSearchFilter('search_sub_cat_search', 'search_sub_category_container');
    enableSearchFilter('search_sub_sub_cat_search', 'search_sub_sub_category_container');
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // CHANGED: Target the 'keywords_input' instead of 'search_term_input'
    const keywordInput = document.getElementById('keywords_input');
    if (keywordInput) {
        new TomSelect(keywordInput, {
            create: true,               // Allow user to type new text
            createOnBlur: true,         // Create tag if user clicks away
            delimiter: ',',             // Store in DB separated by commas
            persist: false,             // Don't save created tags to the dropdown
            plugins: ['remove_button'], // Add 'x' button
            onInitialize: function() {
                this.wrapper.classList.add('w-full'); 
                this.wrapper.querySelector('.ts-control').classList.add('border', 'border-[#ccc]', 'rounded-[3px]', 'text-[13px]');
            }
        });
    } else {
        console.warn("Keywords Input not found - skipping initialization");
    }
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Get PHP Data into JS
    const colorMapDB = <?php echo json_encode($colorMapData); ?>;
    // 2. Function to determine the key (jewelry/textiles) from the Group Name
    function getColorMapKey() {
        const groupSelect = document.getElementById('group_select');
        let groupText = '';
        // Handle TomSelect or Native Select
        if (groupSelect) {
            if (groupSelect.tomselect) {
                // If TomSelect is initialized, get the text from the selected item
                const val = groupSelect.tomselect.getValue();
                const item = groupSelect.tomselect.getItem(val);
                if(item) groupText = item.innerText.toLowerCase();
            } else if (groupSelect.selectedIndex > -1) {
                // Native fallback
                groupText = groupSelect.options[groupSelect.selectedIndex].text.toLowerCase();
            }
        }
        // Logic to match keys in your array
        if (groupText.includes('textile') || groupText.includes('clothing')) return 'textiles';
        if (groupText.includes('jewelry') || groupText.includes('jewellery')) return 'jewelry';
        return null; // Return null if neither
    }
    // 3. Main Function to Update All Fields
    function updateAllColorMaps() {
        const key = getColorMapKey(); // e.g., 'textiles' or 'jewelry'
        const wrappers = document.querySelectorAll('.colormap-wrapper');
        wrappers.forEach(wrapper => {
            const select = wrapper.querySelector('.colormap-select');
            // A. If no matching category, Hide and Clear
            if (!key || !colorMapDB[key]) {
                wrapper.style.display = 'none';
                select.innerHTML = '<option value="">Select Color Map</option>';
                return;
            }
            // B. If matching category, Show and Populate
            wrapper.style.display = 'block'; // Or 'flex' depending on your grid
            
            // Only repopulate if the options haven't been generated for this key yet
            // (We check a custom attribute to avoid wiping user selection while typing elsewhere)
            if (select.getAttribute('data-loaded-key') !== key) {
                
                // Get the Saved Value (from Database)
                const savedVal = select.getAttribute('data-saved-value') || "";
                
                // Build Options
                let html = '<option value="">Select Color Map</option>';
                const options = colorMapDB[key]; // The array [black, gray, white...]
                
                options.forEach(colorName => {
                    // Check if this option matches the saved database value
                    // We trim and lowercase for loose comparison
                    const isSelected = (String(colorName).trim().toLowerCase() === String(savedVal).trim().toLowerCase()) ? 'selected' : '';
                    html += `<option value="${colorName}" ${isSelected}>${colorName}</option>`;
                });
                select.innerHTML = html;
                select.setAttribute('data-loaded-key', key); // Mark as loaded
            }
        });
    }
    // 4. Listeners
    // Hook into Group Change (TomSelect or Native)
    const groupSelect = document.getElementById('group_select');
    if (groupSelect) {
        // Native Event
        groupSelect.addEventListener('change', updateAllColorMaps);
        // TomSelect Event
        if (groupSelect.tomselect) {
            groupSelect.tomselect.on('change', updateAllColorMaps);
        }
    }
    // Hook into "Add New Variation" to populate the new card immediately
    const originalAddVarForColor = window.addNewVariation;
    window.addNewVariation = function() {
        if(originalAddVarForColor) originalAddVarForColor();
        setTimeout(updateAllColorMaps, 50); // Small delay to ensure DOM is ready
    };
    // 5. Initial Run
    updateAllColorMaps();
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. DATA: Import PHP Data
    const groupingDataMap = <?php echo json_encode($categoriesByParent1); ?>;
    
    // 2. PRE-SELECTION: Get PHP Values
    // Note: Category ID is often comma-separated string in DB, Sub/SubSub are arrays
    const rawCat = "<?php echo $selected_cat_id; ?>";
    const groupingPreSelected = {
        groupVal: "<?php echo $selected_group_val; ?>",
        cat:    new Set(rawCat ? rawCat.split(',').filter(Boolean) : []), 
        sub:    new Set(<?php echo json_encode($selected_sub ? (is_array($selected_sub) ? $selected_sub : explode(',',$selected_sub)) : []); ?>.map(String)), 
        subsub: new Set(<?php echo json_encode($selected_sub_sub ? (is_array($selected_sub_sub) ? $selected_sub_sub : explode(',',$selected_sub_sub)) : []); ?>.map(String))
    };

    // 3. DOM ELEMENTS
    const groupSelectEl = document.getElementById("group_select");
    const catContainer = document.getElementById('category_container');
    const subContainer = document.getElementById('sub_category_container');
    const subSubContainer = document.getElementById('sub_sub_category_container');

    // 4. INIT TOM SELECT FOR GROUP
    const config = { create: false, sortField: { field: "text", direction: "asc" }, controlInput: null };
    let groupTs = null;

    if(groupSelectEl) {
        if(groupSelectEl.tomselect) groupTs = groupSelectEl.tomselect;
        else groupTs = new TomSelect(groupSelectEl, config);

        groupTs.on('change', function(groupValue) {
            // Clear downstream selections on manual change
            groupingPreSelected.cat.clear(); 
            groupingPreSelected.sub.clear(); 
            groupingPreSelected.subsub.clear();        
            updateGroupingCatList(groupValue);
        });
    }

    // --- HELPER: Create Checkbox ---
    function createGroupingCheckbox(item, inputName, selectedSet, onChangeCallback) {
        const div = document.createElement('div');
        div.className = 'checkbox-item pl-2 flex items-center p-1 hover:bg-gray-50';
        
        const valToCheck = String(item.store_val);
        const isChecked = selectedSet.has(valToCheck) ? 'checked' : '';

        div.innerHTML = `
            <input type="checkbox" 
                   id="${inputName}_${item.id}" 
                   name="${inputName}[]" 
                   value="${item.store_val}" 
                   data-parent-path="" 
                   ${isChecked}
                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer mr-2">
            <label for="${inputName}_${item.id}" class="text-[13px] text-[#333] cursor-pointer select-none w-full">${item.name}</label>
        `;
        
        const checkbox = div.querySelector('input');
        checkbox.addEventListener('change', () => { if(onChangeCallback) onChangeCallback(); });
        return div;
    }

    // --- 5. RENDER FUNCTIONS ---

    // A. Render Category (Col 1)
    function updateGroupingCatList(rawGroupValue) {
        subContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Category...</div>';
        subSubContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Sub Category...</div>';

        if(!rawGroupValue) {
             catContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Group...</div>';
             return;
        }

        const lookupKey = String(rawGroupValue).trim(); 
        
        if(!groupingDataMap[lookupKey]) {
            catContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">No categories found</div>';
            return;
        }

        const items = groupingDataMap[lookupKey];
        catContainer.innerHTML = '';
        const seenIds = new Set();

        items.forEach(item => {
            if(!seenIds.has(item.id)){
                seenIds.add(item.id);
                catContainer.appendChild(createGroupingCheckbox(item, 'category_code', groupingPreSelected.cat, handleGroupingCatChange));
            }
        });
        
        // Setup Search
        setupSearch('main_cat_search', 'category_container');
        
        // AUTO-SELECT DOWNSTREAM
        if(groupingPreSelected.cat.size > 0) handleGroupingCatChange();
    }

    // B. Render Sub Category (Col 2)
    function handleGroupingCatChange() {
        const checkedInputs = Array.from(catContainer.querySelectorAll('input[type="checkbox"]:checked'));
        subContainer.innerHTML = ''; 
        subSubContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Sub Category...</div>';

        if(checkedInputs.length === 0) {
            subContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select a Category...</div>';
            return;
        }

        const groupVal = groupTs ? groupTs.getValue() : groupSelectEl.value;
        const groupKey = String(groupVal).trim(); 
        let hasAnyOptions = false;

        checkedInputs.forEach(input => {
            const catStoreVal = input.value; 
            const parentName = input.nextElementSibling.innerText;
            const lookupKey = catStoreVal + "|" + groupKey;

            const children = groupingDataMap[lookupKey];
            
            if (children && children.length > 0) {
                hasAnyOptions = true;
                const header = document.createElement('div');
                header.className = "text-[11px] font-bold text-[#d97824] bg-gray-50 px-2 py-1 border-b border-t border-gray-200 mt-0 sticky top-0 z-10 group-header";
                header.innerText = parentName; 
                subContainer.appendChild(header);

                children.forEach(item => {
                    const el = createGroupingCheckbox(item, 'sub_category_code', groupingPreSelected.sub, handleGroupingSubCatChange);
                    el.querySelector('input').setAttribute('data-parent-path', lookupKey);
                    subContainer.appendChild(el);
                });
            }
        });

        if(!hasAnyOptions) subContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">No Sub Categories found</div>';
        
        setupSearch('sub_cat_search', 'sub_category_container');
        
        // AUTO-SELECT DOWNSTREAM
        if(groupingPreSelected.sub.size > 0) handleGroupingSubCatChange();
    }

    // C. Render Sub Sub Category (Col 3)
    function handleGroupingSubCatChange() {
        const checkedInputs = Array.from(subContainer.querySelectorAll('input[type="checkbox"]:checked'));
        subSubContainer.innerHTML = ''; 
        
        if(checkedInputs.length === 0) {
            subSubContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">Select Sub Category...</div>';
            return;
        }

        let hasAnyOptions = false;

        checkedInputs.forEach(input => {
            const subCatStoreVal = input.value;
            const parentPath = input.getAttribute('data-parent-path'); 
            const parentName = input.nextElementSibling.innerText;
            const lookupKey = subCatStoreVal + "|" + parentPath;
            
            const children = groupingDataMap[lookupKey];
            
            if (children && children.length > 0) {
                hasAnyOptions = true;
                const header = document.createElement('div');
                header.className = "text-[11px] font-bold text-[#d97824] bg-gray-50 px-2 py-1 border-b border-t border-gray-200 mt-0 sticky top-0 z-10 group-header";
                header.innerText = parentName;
                subSubContainer.appendChild(header);

                children.forEach(item => {
                    subSubContainer.appendChild(createGroupingCheckbox(item, 'sub_sub_category_code', groupingPreSelected.subsub, null));
                });
            }
        });

        if(!hasAnyOptions) subSubContainer.innerHTML = '<div class="text-xs text-gray-400 p-2 text-center mt-10">No Sub Sub Categories found</div>';
        setupSearch('sub_sub_cat_search', 'sub_sub_category_container');
    }

    // --- 6. SIMPLE SEARCH LOGIC ---
    function setupSearch(inputId, containerId) {
        const input = document.getElementById(inputId);
        const container = document.getElementById(containerId);
        
        if(input && container) {
            // Remove old listeners to prevent stacking (using cloneNode trick)
            const newInput = input.cloneNode(true);
            input.parentNode.replaceChild(newInput, input);
            
            newInput.addEventListener('keyup', function() {
                const filter = this.value.toLowerCase().trim();
                const allElements = Array.from(container.children);
                let currentHeader = null;
                let headerMatches = false;
                let visibleCountInGroup = 0;

                if(filter === "") {
                    allElements.forEach(el => el.style.display = ''); 
                    return;
                }

                for (let i = 0; i < allElements.length; i++) {
                    const el = allElements[i];
                    if (el.classList.contains('group-header')) {
                        if (currentHeader) currentHeader.style.display = (headerMatches || visibleCountInGroup > 0) ? 'block' : 'none';
                        currentHeader = el;
                        headerMatches = el.innerText.toLowerCase().includes(filter);
                        visibleCountInGroup = 0;
                        el.style.display = 'none'; 
                    } else if (el.classList.contains('checkbox-item')) {
                        const label = el.querySelector('label').innerText.toLowerCase();
                        if (headerMatches || label.includes(filter)) {
                            el.style.display = 'flex';
                            visibleCountInGroup++;
                        } else {
                            el.style.display = 'none';
                        }
                    }
                }
                if (currentHeader) currentHeader.style.display = (headerMatches || visibleCountInGroup > 0) ? 'block' : 'none';
            });
        }
    }

    // --- 7. STARTUP TRIGGER ---
    if (groupingPreSelected.groupVal) {
        // Trigger the cascade
        updateGroupingCatList(groupingPreSelected.groupVal);
    }
});
</script>

<script>
function validateAndSubmit(actionType) {
    let errors = [];
    const form = document.getElementById('product_form');
    
    // Helper to get value cleanly
    const getVal = (name) => {
        const el = form.querySelector(`[name="${name}"]`);
        return el ? el.value.trim() : '';
    };

    // Helper to check if price is strictly greater than 0
    // Returns TRUE if invalid (empty, 0, 0.00, -5, etc.)
    const isInvalidPrice = (val) => {
        const num = parseFloat(val);
        return !val || isNaN(num) || num <= 0;
    };

    // --- 1. GENERAL FIELDS VALIDATION ---
    if (!getVal('added_date')) errors.push("Field 'Added On' is required.");
    if (!getVal('received_by_user_id')) errors.push("Field 'Received By' is required.");
    if (!getVal('updated_by_user_id')) errors.push("Field 'Feeded By' is required.");
    if (!getVal('vendor_code')) errors.push("Field 'Vendor' is required.");
    if (!getVal('material_code')) errors.push("Field 'Material' is required.");
    if (!getVal('group_name')) errors.push("Field 'Group' is required.");
    if (!getVal('search_term')) errors.push("Field 'Search Terms' is required.");
    if (!getVal('key_words')) errors.push("Please enter at least one 'Keyword'.");
    if (!getVal('marketplace')) errors.push("Field 'Marketplace Vendor' is required.");
    const isVariant = getVal('is_variant'); // Get current Variant status (Y or N)

    // Only validate Image Directory if this is NOT a variant (i.e., it is a Parent/Main item)
    

    // Category Check (Checkboxes)
    const catChecked = document.querySelectorAll('input[name="category_code[]"]:checked').length;
    if (catChecked === 0) errors.push("Please select at least one 'Category'.");

    // Lead Time
    const leadTime = parseFloat(getVal('lead_time_days')) || 0;
    if (leadTime < 1) errors.push("'Lead Time' must be at least 1 day.");

    // --- 2. MAIN ITEM VALIDATION ---
    const mainQty = parseFloat(getVal('quantity_received')) || 0;
    if (mainQty < 1) errors.push("Main Item: 'Quantity' must be at least 1.");

    // UPDATED: Check for 0.00
    if (isInvalidPrice(getVal('cp'))) errors.push("Main Item: 'CP' must be greater than 0.");
    if (isInvalidPrice(getVal('price_india'))) errors.push("Main Item: 'Price India' must be greater than 0.");
    if (isInvalidPrice(getVal('price_india_mrp'))) errors.push("Main Item: 'Price India MRP' must be greater than 0.");
    if (isInvalidPrice(getVal('usd_price'))) errors.push("Main Item: 'USD Price' must be greater than 0.");
    
    if (!getVal('hsn_code')) errors.push("Main Item: 'HSN Code' is required.");

    const mainGst = parseFloat(getVal('gst_rate'));
    if (isNaN(mainGst) || mainGst < 0) errors.push("Main Item: 'GST' must be 0 or greater.");

    // Main Gallery Check (ID -1)
    const mainGrid = document.querySelector('.photo-group-grid[data-var-id="-1"]');
    const mainImgCount = mainGrid ? mainGrid.querySelectorAll('.draggable-item').length : 0;
    if (mainImgCount < 1) errors.push("Main Item: Please add at least 1 photo to the Gallery.");


    // --- 3. VARIATIONS VALIDATION ---
    const variations = document.querySelectorAll('.variation-card');
    variations.forEach((card, index) => {
        const cardTitle = `Variation #${index + 1}`;
        
        // Helper specifically for card inputs
        const getCardVal = (partialName) => {
            // Matches name="variations[...][partialName]"
            const input = card.querySelector(`input[name*="[${partialName}]"], select[name*="[${partialName}]"]`);
            return input ? input.value.trim() : '';
        };

        const vQty = parseFloat(getCardVal('quantity')) || 0;
        if (vQty < 1) errors.push(`${cardTitle}: 'Quantity' must be at least 1.`);

        // UPDATED: Check for 0.00 inside variations
        if (isInvalidPrice(getCardVal('cp'))) errors.push(`${cardTitle}: 'CP' must be greater than 0.`);
        if (isInvalidPrice(getCardVal('price_india'))) errors.push(`${cardTitle}: 'Price India' must be greater than 0.`);
        if (isInvalidPrice(getCardVal('price_india_mrp'))) errors.push(`${cardTitle}: 'Price India MRP' must be greater than 0.`);
        if (isInvalidPrice(getCardVal('usd_price'))) errors.push(`${cardTitle}: 'USD Price' must be greater than 0.`);

        if (!getCardVal('hsn_code')) errors.push(`${cardTitle}: 'HSN Code' is required.`);
        
        const vGst = parseFloat(getCardVal('gst_rate'));
        if (isNaN(vGst) || vGst < 0) errors.push(`${cardTitle}: 'GST' must be 0 or greater.`);

        // Variation Gallery Check
        const vGrid = card.querySelector('.photo-group-grid');
        if (vGrid) {
            const vImgCount = vGrid.querySelectorAll('.draggable-item').length;
            if (vImgCount < 1) errors.push(`${cardTitle}: Please add at least 1 photo to Gallery.`);
        }
    });
	
	if (actionType === 'draft') {
		// Skip validation, directly submit
		document.getElementById('hidden_save_action').value = actionType;
		form.submit();
		return; // stop further execution
	}

    // --- 4. RESULT ---
    if (errors.length > 0) {
        // Validation Failed - Show Popup
        let errorHtml = '<div style="text-align: left; max-height: 300px; overflow-y: auto;"><ul style="list-style-type: disc; padding-left: 20px;">';
        errors.forEach(err => {
            errorHtml += `<li style="margin-bottom: 5px; color: #d33;">${err}</li>`;
        });
        errorHtml += '</ul></div>';

        Swal.fire({
            icon: 'error',
            title: 'Validation Failed',
            html: errorHtml,
            confirmButtonColor: '#d97824'
        });
    } else {
        // Validation Passed - Submit
        document.getElementById('hidden_save_action').value = actionType;
        form.submit();
    }
}
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Get Markup Data from PHP
    const markupMap = <?php echo json_encode($data['markup_list'] ?? []); ?>;
    let currentMarkupPercent = 0;

    // 2. Update Markup Percentage on Group Change
    function updateCurrentMarkup() {
        const groupSelect = document.getElementById('group_select');
        let selectedGroupId = '';

        if (groupSelect) {
            if (groupSelect.tomselect) {
                selectedGroupId = groupSelect.tomselect.getValue();
            } else {
                selectedGroupId = groupSelect.value;
            }
        }
        currentMarkupPercent = parseFloat(markupMap[selectedGroupId]) || 0;
        
        // Background Update: Only fill empty fields
        recalculateAllPrices(false); 
    }

    // 3. MAIN LOGIC: Calculate Price
    // isUserAction = true (User typed in CP) | false (Group changed or Page Load)
    function calculatePrice(cpInput, isUserAction) {
        const cp = parseFloat(cpInput.value);
        if (isNaN(cp)) return; 

        // Formula: CP + (CP * Percentage / 100)
        const sellPrice = (cp * (currentMarkupPercent / 100));

        const container = cpInput.closest('.grid') || cpInput.closest('.calculation-card') || cpInput.closest('.variation-card');
        
        if (container) {
            let priceInput = null;
            if (cpInput.name === 'cp') {
                priceInput = container.querySelector('input[name="price_india"]');
            } else {
                priceInput = container.querySelector('input[name*="[price_india]"]:not([name*="mrp"])');
            }

            if (priceInput) {
                // Check if user manually locked THIS specific field in this session
                if (priceInput.getAttribute('data-manual-override') === 'true') {
                    return; // Stop, user wants a custom price here
                }

                const currentVal = parseFloat(priceInput.value || 0);

                // LOGIC DECISION:
                // 1. If Background Action (Group Change): Only update if currently 0 or Empty
                // 2. If User Action (Typing CP): ALWAYS update (unless locked above)
                if (isUserAction || currentVal === 0) {
                    priceInput.value = sellPrice.toFixed(2);
                    
                    // Mark as auto-filled so we know script touched it
                    priceInput.setAttribute('data-auto-filled', 'true'); 
                    
                    // Trigger update for MRP
                    priceInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }
        }
    }

    // 4. Listen for Manual Price Overrides
    // If user types in "Price India" directly, we STOP auto-calculating for that row
    document.body.addEventListener('input', function(e) {
        if (e.target.matches('input[name*="[price_india]"]') || e.target.name === 'price_india') {
            if (e.isTrusted) { // Only if human typed it
                e.target.setAttribute('data-manual-override', 'true');
            }
        }
    });

    // 5. Apply to all rows
    function recalculateAllPrices(isUserAction) {
        const allCpInputs = document.querySelectorAll('input[name="cp"], input[name*="[cp]"]');
        allCpInputs.forEach(input => {
            if(input.value) calculatePrice(input, isUserAction);
        });
    }

    // 6. Init Listeners
    const groupSelect = document.getElementById('group_select');
    if (groupSelect) {
        groupSelect.addEventListener('change', updateCurrentMarkup);
        if(groupSelect.tomselect) groupSelect.tomselect.on('change', updateCurrentMarkup);
    }

    // LISTEN FOR CP CHANGES (User Action = true)
    document.body.addEventListener('input', function(e) {
        if (e.target.matches('input[name="cp"]') || e.target.matches('input[name*="[cp]"]')) {
            calculatePrice(e.target, true); // <--- Pass TRUE here
        }
    });

    // 7. Run once on load (Background action = false)
    updateCurrentMarkup();
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // --- Configuration ---
    const mapConfig = [
        { 
            source: 'category_container', 
            target: 'search_category_container' 
        },
        { 
            source: 'sub_category_container', 
            target: 'search_sub_category_container' 
        },
        { 
            source: 'sub_sub_category_container', 
            target: 'search_sub_sub_category_container' 
        }
    ];

    // --- Helper: Sync Checkboxes from Source to Target ---
    function syncCheckboxes(sourceId, targetId) {
        const sourceContainer = document.getElementById(sourceId);
        const targetContainer = document.getElementById(targetId);
        if(!sourceContainer || !targetContainer) return;

        // 1. Get all checked values from Source
        const checkedValues = Array.from(sourceContainer.querySelectorAll('input[type="checkbox"]:checked'))
                                   .map(cb => cb.value);

        // 2. Reset Target (Uncheck all first to mirror strict sync)
        const targetCheckboxes = targetContainer.querySelectorAll('input[type="checkbox"]');
        targetCheckboxes.forEach(cb => {
            cb.checked = false; 
        });

        // 3. Check matching values in Target and Trigger Change
        let changeTriggered = false;
        checkedValues.forEach(val => {
            const match = targetContainer.querySelector(`input[value="${val}"]`);
            if(match) {
                match.checked = true;
                match.dispatchEvent(new Event('change', { bubbles: true }));
                changeTriggered = true;
            }
        });

        // If nothing was checked, trigger change on first element to clear downstream
        if(!changeTriggered && targetCheckboxes.length > 0) {
             targetCheckboxes[0].dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    // --- Core Function: Master Sync ---
    function performFullSync() {
        // 1. Sync Group (TomSelect)
        const itemGroupSelect = document.getElementById('group_select');
        const searchGroupSelect = document.getElementById('search_group_select');

        if (itemGroupSelect && searchGroupSelect && itemGroupSelect.tomselect && searchGroupSelect.tomselect) {
            const itemVal = itemGroupSelect.tomselect.getValue();
            // Sync Group Value
            searchGroupSelect.tomselect.setValue(itemVal); 
        }

        // 2. Sync Categories (Delayed cascade)
        setTimeout(() => {
            syncCheckboxes('category_container', 'search_category_container');
            setTimeout(() => {
                syncCheckboxes('sub_category_container', 'search_sub_category_container');
                setTimeout(() => {
                    syncCheckboxes('sub_sub_category_container', 'search_sub_sub_category_container');
                }, 50);
            }, 50);
        }, 50);
    }

    // --- Event Listeners (User Interaction Only) ---

    // A. Listen for Item Group Change
    const itemGroupSelect = document.getElementById('group_select');
    if (itemGroupSelect && itemGroupSelect.tomselect) {
        itemGroupSelect.tomselect.on('change', function() {
            // ONLY sync when user manually changes the Group
            performFullSync();
        });
    }

    // B. Listen for Checkbox Changes in Item Grouping
    mapConfig.forEach(config => {
        const container = document.getElementById(config.source);
        if(container) {
            container.addEventListener('change', function(e) {
                // ONLY sync when user manually clicks a checkbox
                if(e.target.matches('input[type="checkbox"]')) {
                    setTimeout(() => {
                        syncCheckboxes(config.source, config.target);
                        
                        // Trigger downstream updates
                        if(config.source === 'category_container') {
                             setTimeout(() => syncCheckboxes('sub_category_container', 'search_sub_category_container'), 50);
                        }
                        if(config.source === 'sub_category_container') {
                             setTimeout(() => syncCheckboxes('sub_sub_category_container', 'search_sub_sub_category_container'), 50);
                        }
                    }, 10);
                }
            });
        }
    });

    // --- REMOVED: The "setTimeout(performFullSync, 200)" block ---
    // This ensures that on page load, your PHP database values are respected.
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const variantSelect = document.getElementById('variant_select');
    const groupSelect = document.getElementById('group_select');
    const imgDirInput = document.getElementById('image_directory_input');

    // Function to Generate the Directory Name
    function updateImageDirectory() {
        // 1. If Variant is YES ('Y'), clear field
        if (variantSelect && variantSelect.value === 'Y') {
            imgDirInput.value = "";
            return;
        }

        // 2. If Variant is NO (or empty), Generate Name
        let groupName = "";

        // Get Group Name (Handle TomSelect vs Native)
        if (groupSelect.tomselect) {
            const val = groupSelect.tomselect.getValue();
            const item = groupSelect.tomselect.getItem(val);
            if(item) groupName = item.innerText;
        } else if (groupSelect.selectedIndex > -1) {
            groupName = groupSelect.options[groupSelect.selectedIndex].text;
        }

        // Only generate if a group is selected
        if (groupName && groupName !== "Select Group..." && groupName.trim() !== "") {
            // Get Date (MM and YY)
            const date = new Date();
            const month = String(date.getMonth() + 1).padStart(2, '0'); // e.g., '01'
            const year = String(date.getFullYear()).slice(-2);          // e.g., '26'

            // Clean Group Name (remove spaces/special chars, lowercase)
            // Example: "Men's Wear" -> "menswear"
            const cleanGroup = groupName.toLowerCase().replace(/[^a-z0-9]/g, '');

            // Set Value: books0126
            imgDirInput.value = cleanGroup + month + year;
        }
    }

    // --- EVENT LISTENERS ---

    // 1. Listen for Variant Change
    if(variantSelect) {
        variantSelect.addEventListener('change', updateImageDirectory);
        if(variantSelect.tomselect) variantSelect.tomselect.on('change', updateImageDirectory);
    }

    // 2. Listen for Group Change
    if(groupSelect) {
        groupSelect.addEventListener('change', updateImageDirectory);
        if(groupSelect.tomselect) groupSelect.tomselect.on('change', updateImageDirectory);
    }

    // 3. Run on Load (in case editing existing data)
    // Only run if the input is currently empty (to avoid overwriting saved data on edit load)
    if(imgDirInput.value === "") {
        setTimeout(updateImageDirectory, 500); // Small delay to let TomSelect load
    }
});
</script>