<?php
// 1. PHP Logic & Data Retrieval
global $inboundingModel;
$images = $data['images'] ?? [];
$item   = $data['item'] ?? [];
$record_id = $data['record_id'] ?? 0;

// Fetch Variations
$variations = $inboundingModel->getVariations($record_id); 

// Group Images by Variation ID
$grouped_images = ['-1' => []];
foreach ($variations as $v) {
    $grouped_images[$v['id']] = [];
}

if (!empty($images)) {
    foreach ($images as $img) {
        $v_id = !empty($img['variation_id']) ? $img['variation_id'] : -1;
        if (!isset($grouped_images[$v_id])) $v_id = -1;
        $grouped_images[$v_id][] = $img;
    }
}

// --- 2. THUMBNAIL GENERATOR FUNCTION ---
// This creates a small version of the image for viewing, but keeps the original safe
function getThumbnail($filePath, $width = 200, $height = 200) {
    $cleanPath = ltrim($filePath, '/');

    // Return placeholder if file missing
    if (empty($cleanPath) || !file_exists($cleanPath)) {
        return 'assets/images/placeholder.png'; // Ensure you have a placeholder image
    }

    // Auto-detect directory (e.g. uploads/itm_raw_img)
    $dirName  = dirname($cleanPath);
    $fileName = basename($cleanPath);
    
    $thumbDir  = $dirName . '/thumbs/'; 
    $thumbPath = $thumbDir . $fileName;

    // Return existing thumb
    if (file_exists($thumbPath)) return $thumbPath;

    // Create directory
    if (!is_dir($thumbDir)) mkdir($thumbDir, 0777, true);

    $info = getimagesize($cleanPath);
    if (!$info) return $cleanPath; // Not an image

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

    // Save with 70% quality (Visual only, original is untouched)
    switch ($mime) {
        case 'image/jpeg': imagejpeg($newImage, $thumbPath, 70); break;
        case 'image/png':  imagepng($newImage, $thumbPath); break;
        case 'image/gif':  imagegif($newImage, $thumbPath); break;
        case 'image/webp': imagewebp($newImage, $thumbPath); break;
    }

    imagedestroy($image);
    imagedestroy($newImage);

    return $thumbPath;
}
?>

<style>
    .scrollbar-thin::-webkit-scrollbar { height: 6px; }
    .scrollbar-thin::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
    .scrollbar-thin::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 4px; }
    
    /* Active Variation Card Style */
    .var-card.active { 
        border-color: #2563eb; 
        background-color: #eff6ff; 
        box-shadow: 0 0 0 1px #2563eb; 
    }
    
    /* Hide non-active containers */
    .image-container { display: none; }
    .image-container.active { display: grid; }

    /* Animation */
    .animate-fade-in { animation: fadeIn 0.3s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="max-w-7xl mx-auto space-y-6 font-['Segoe_UI'] p-2 md:p-6 bg-gray-50 min-h-screen">

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col lg:flex-row gap-6">
        <div class="w-32 h-32 md:w-40 md:h-40 bg-gray-100 rounded-lg border border-gray-200 flex-shrink-0 p-1 flex items-center justify-center">
            <?php 
                // OPTIMIZED MAIN IMAGE
                $mainImgRaw = $item['product_photo'] ?? '';
                $mainImgThumb = !empty($mainImgRaw) ? base_url(getThumbnail($mainImgRaw)) : 'assets/no-img.png';
                $mainImgOrig = !empty($mainImgRaw) ? base_url($mainImgRaw) : '';
            ?>
            <img src="<?php echo $mainImgThumb; ?>" class="max-w-full max-h-full object-contain rounded cursor-zoom-in" onclick="openImagePopup('<?= $mainImgOrig ?>')">
        </div>
        <div class="flex-grow grid grid-cols-2 md:grid-cols-4 gap-y-3 gap-x-6 text-[13px] text-gray-600 content-center">
            <div><span class="block font-bold text-gray-900">Category:</span> <?php echo $item['category'] ?? '-'; ?></div>
            <div><span class="block font-bold text-gray-900">Height:</span> <?php echo $item['height'] ?? '-'; ?> <?php echo $item['dimention_unit'] ?? ''; ?></div>
            <div><span class="block font-bold text-gray-900">Width:</span> <?php echo $item['width'] ?? '-'; ?> <?php echo $item['dimention_unit'] ?? ''; ?></div>
            <div><span class="block font-bold text-gray-900">Weight:</span> <?php echo $item['weight'] ?? '-'; ?> <?php echo $item['weight_unit'] ?? ''; ?></div>
            <div><span class="block font-bold text-gray-900">Material:</span> <?php echo $item['material'] ?? '-'; ?></div>
            <div><span class="block font-bold text-gray-900">Vendor:</span> <?php echo $item['vendor_name'] ?? '-'; ?></div>
            <div><span class="block font-bold text-gray-900">Received By:</span> <?php echo $item['recived_by_name'] ?? '-'; ?></div>
            <div class="col-span-2"><span class="font-bold text-gray-900">Gate Entry:</span> <?php echo !empty($item['gate_entry_date_time']) ? date('d M Y h:i A', strtotime($item['gate_entry_date_time'])) : '-'; ?></div>
        </div>
    </div>

    <div class="flex overflow-x-auto gap-4 pb-2 scrollbar-thin" id="variation-strip">
        
        <div class="var-card active cursor-pointer bg-white border border-gray-300 rounded-lg p-3 flex gap-3 min-w-[220px] items-center shadow-sm transition-all hover:border-gray-400"
             onclick="switchVariation(-1, this)">
            <div class="w-12 h-12 bg-gray-100 rounded border border-gray-200 flex-shrink-0 flex items-center justify-center">
                <i class="fa-solid fa-box-open text-gray-400"></i>
            </div>
            <div class="text-xs text-gray-600">
                <div class="font-bold text-black uppercase mb-0.5">Base / Common</div>
                <div>General Photos</div>
            </div>
        </div>

        <?php if(!empty($variations)): ?>
            <?php foreach($variations as $var): ?>
                <div class="var-card cursor-pointer bg-white border border-gray-300 rounded-lg p-3 flex gap-3 min-w-[220px] items-center shadow-sm transition-all hover:border-gray-400"
                     onclick="switchVariation(<?php echo $var['id']; ?>, this)">
                    <div class="w-12 h-12 bg-gray-100 rounded border border-gray-200 flex-shrink-0 flex items-center justify-center overflow-hidden">
                        <?php if(!empty($var['variation_image'])): 
                             // OPTIMIZED VARIATION ICON
                             $vThumb = base_url(getThumbnail($var['variation_image']));
                        ?>
                            <img src="<?php echo $vThumb; ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <span class="text-[10px] text-gray-400">No IMG</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-xs text-gray-600 flex flex-col gap-0.5">
                        <div class="font-bold text-black">
                            <?= htmlspecialchars($var['color']) ?> - <?= htmlspecialchars($var['size']) ?>
                        </div>
                        <div>Qty: <?= $var['quantity'] ?? 0 ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-300 p-6">
        <form action="<?php echo base_url('?page=inbounding&action=itmrawimgsave&id='.$record_id); ?>" method="POST" enctype="multipart/form-data">
            
            <input type="hidden" name="userid_log" value="<?php echo $_SESSION['user']['id'] ?? ''; ?>">
            <input type="hidden" id="current_variation_id" value="-1">
            <div id="deletedInputsContainer"></div>
            
            <div id="activeFilesContainer" style="display:none;"></div>

            <div class="flex flex-col md:flex-row justify-between items-center mb-6 pb-6 border-b border-gray-200 gap-4">
                <div>
                    <h3 class="font-bold text-lg text-gray-900 flex items-center gap-2">
                        <i class="fa-solid fa-cloud-arrow-up text-blue-600"></i> Upload Raw Photos
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">
                        Select a variation card above, then choose files for it.<br>
                        Currently Active: <span id="active-var-name" class="font-bold text-blue-600">Base / Common</span>
                    </p>
                </div>
                <label class="cursor-pointer bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded inline-flex items-center text-sm transition shadow-md">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Select Files
                    <input type="file" id="fileTrigger" multiple accept="image/*" class="hidden">
                </label>
            </div>

            <div class="image-container active grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4" id="container_-1" data-var-id="-1">
                <?php renderRawImageGrid($grouped_images['-1'] ?? []); ?>
            </div>

            <?php foreach($variations as $var): ?>
                <div class="image-container grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4" id="container_<?php echo $var['id']; ?>" data-var-id="<?php echo $var['id']; ?>">
                    <?php renderRawImageGrid($grouped_images[$var['id']] ?? []); ?>
                </div>
            <?php endforeach; ?>

            <div class="mt-8 flex justify-end gap-3 pt-4 border-t border-gray-100">
                 <a href="<?php echo base_url('?page=inbounding'); ?>" class="px-6 py-2.5 text-sm font-bold text-gray-600 bg-white border border-gray-300 rounded hover:bg-gray-50">Cancel</a>
                <button type="submit" class="bg-black hover:bg-gray-800 text-white font-bold py-2.5 px-8 rounded text-sm shadow-md transition">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php
// Helper Function to Render Raw Image Items
function renderRawImageGrid($imgs) {
    if(empty($imgs)) {
        echo '<div class="col-span-full text-center text-gray-400 text-xs py-10 border-2 border-dashed border-gray-100 rounded">No photos for this variation yet.</div>';
        return;
    }
    foreach($imgs as $img) {
        // OPTIMIZATION 3: Existing Grid Images
        // Path: uploads/itm_raw_img/filename.jpg
        $fullPath = 'uploads/itm_raw_img/' . $img['file_name'];
        $thumbSrc = base_url(getThumbnail($fullPath));
        $origSrc  = base_url($fullPath);
        ?>
        <div class="group relative bg-white border border-gray-200 rounded-lg p-2 shadow-sm existing-photo hover:shadow-md transition">
            <div class="aspect-square bg-gray-100 rounded flex items-center justify-center overflow-hidden relative cursor-zoom-in"
                 onclick="openImagePopup('<?= $origSrc ?>')">
                
                <img src="<?= $thumbSrc ?>" class="w-full h-full object-cover">
                
                <button type="button" onclick="event.stopPropagation(); markForDeletion(this, <?php echo $img['id']; ?>)" 
                        class="absolute top-2 right-2 bg-white/90 text-red-600 p-1.5 rounded-full shadow hover:bg-red-500 hover:text-white transition opacity-0 group-hover:opacity-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        </div>
        <?php
    }
}
?>

<div id="imagePopup" class="fixed inset-0 bg-black bg-opacity-80 hidden flex justify-center items-center z-[100]" onclick="closeImagePopup(event)">
    <div class="bg-white p-2 rounded-md w-auto max-w-[95vw] max-h-[95vh] relative flex flex-col items-center shadow-2xl" onclick="event.stopPropagation();">
        <button onclick="closeImagePopup()" class="absolute -top-3 -right-3 bg-red-600 hover:bg-red-700 text-white w-8 h-8 flex items-center justify-center rounded-full text-sm shadow-md border-2 border-white">✕</button>
        <img id="popupImage" class="max-w-full max-h-[90vh] rounded object-contain" src="" alt="Image Preview">
    </div>
</div>

<script>
    const fileTrigger = document.getElementById('fileTrigger');
    const currentVarInput = document.getElementById('current_variation_id');
    const activeVarNameLabel = document.getElementById('active-var-name');
    const deletedContainer = document.getElementById('deletedInputsContainer');
    const activeFilesContainer = document.getElementById('activeFilesContainer');

    // 1. SWITCH VARIATION LOGIC
    window.switchVariation = function(varId, cardElement) {
        document.querySelectorAll('.var-card').forEach(el => el.classList.remove('active'));
        cardElement.classList.add('active');
        currentVarInput.value = varId;
        
        const color = cardElement.querySelector('.font-bold.text-black')?.innerText || "Base / Common";
        activeVarNameLabel.innerText = color;

        document.querySelectorAll('.image-container').forEach(el => el.classList.remove('active'));
        const targetContainer = document.getElementById('container_' + varId);
        if(targetContainer) {
            targetContainer.classList.add('active');
            const emptyMsg = targetContainer.querySelector('.text-gray-400');
            if(emptyMsg && targetContainer.children.length > 1) emptyMsg.style.display = 'none';
        }
    }

    // 2. HANDLE FILE SELECTION
    // NOTE: We are NOT compressing to ensure ORIGINAL QUALITY.
    fileTrigger.addEventListener('change', function() {
        if (this.files.length === 0) return;

        const activeVarId = currentVarInput.value;
        const activeContainer = document.getElementById('container_' + activeVarId);
        const emptyMsg = activeContainer.querySelector('.text-gray-400');
        if(emptyMsg) emptyMsg.style.display = 'none';

        // Iterate through selected files
        Array.from(this.files).forEach((file) => {
            // Generate a unique ID for this file's group (Preview + Input)
            const uniqueId = 'file-' + Date.now() + '-' + Math.floor(Math.random() * 1000);

            // A. Create the Preview Card
            createPreview(file, uniqueId, activeContainer, activeVarId);

            // B. Create a NEW File Input to hold this specific file for upload
            // We use DataTransfer to attach the file cleanly to a new input element
            const newFileInput = document.createElement('input');
            newFileInput.type = 'file';
            newFileInput.name = 'new_photos[]'; // This goes to PHP $_FILES
            newFileInput.style.display = 'none';
            newFileInput.setAttribute('data-id', uniqueId);

            const dt = new DataTransfer();
            dt.items.add(file);
            newFileInput.files = dt.files;

            // C. Create the Hidden Variation ID Input
            const varInput = document.createElement('input');
            varInput.type = 'hidden';
            varInput.name = 'new_image_variation_id[]'; // This goes to PHP $_POST
            varInput.value = activeVarId;
            varInput.setAttribute('data-id', uniqueId);

            // D. Append both to the hidden container for form submission
            activeFilesContainer.appendChild(newFileInput);
            activeFilesContainer.appendChild(varInput);
        });

        // Reset the trigger so selecting the same file again works
        this.value = '';
    });

    // 3. CREATE PREVIEW (Client Side - No Server Call yet)
    function createPreview(file, uniqueId, container, varId) {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onloadend = function() {
            const div = document.createElement('div');
            div.className = "relative bg-white border border-blue-300 border-dashed rounded-lg p-2 shadow-sm animate-fade-in";
            div.setAttribute('data-preview-id', uniqueId); 

            div.innerHTML = `
                <div class="aspect-square bg-gray-100 rounded flex items-center justify-center overflow-hidden relative">
                    <img src="${reader.result}" class="w-full h-full object-cover opacity-90">
                    <span class="absolute bottom-2 left-2 bg-blue-600 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow">NEW</span>
                    
                    <button type="button" onclick="removeNewFile('${uniqueId}')" 
                            class="absolute top-2 right-2 bg-white text-red-500 p-1 rounded-full shadow hover:bg-red-50 transition">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                `;
            container.appendChild(div);
        }
    }

    // 4. REMOVE NEW FILE
    window.removeNewFile = function(uniqueId) {
        // Remove Preview
        const preview = document.querySelector(`div[data-preview-id="${uniqueId}"]`);
        if(preview) preview.remove();

        // Remove Hidden Inputs (So it is not uploaded)
        const inputs = document.querySelectorAll(`input[data-id="${uniqueId}"]`);
        inputs.forEach(el => el.remove());
    }

    // 5. DELETE EXISTING FILE
    window.markForDeletion = function(btn, dbId) {
        if(!confirm("Delete this raw photo?")) return;
        const parent = btn.closest('.existing-photo');
        parent.style.opacity = '0.4';
        parent.style.pointerEvents = 'none'; 
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'delete_ids[]';
        input.value = dbId;
        deletedContainer.appendChild(input);
    }

    // Popup Logic
    window.openImagePopup = function(imageUrl) {
        if(!imageUrl) return;
        document.getElementById('popupImage').src = imageUrl;
        document.getElementById('imagePopup').classList.remove('hidden');
    }
    window.closeImagePopup = function(event) {
        document.getElementById('imagePopup').classList.add('hidden');
        document.getElementById('popupImage').src = '';
    }
</script>