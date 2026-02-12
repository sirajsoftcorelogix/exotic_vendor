<?php
// 1. PHP Logic & Data Retrieval
global $inboundingModel;
$images = $data['images'] ?? [];
$item   = $data['item'] ?? [];
$record_id = $data['record_id'] ?? 0;

// Fetch Variations
$variations = $inboundingModel->getVariations($record_id); 

// Group Images by Variation ID for easy display
$grouped_images = ['-1' => []];
foreach ($variations as $v) {
    $grouped_images[$v['id']] = [];
}
foreach ($images as $img) {
    $v_id = $img['variation_id'] ?? -1;
    $grouped_images[$v_id][] = $img;
}

// --- 2. THUMBNAIL GENERATOR FUNCTION (Smart Version) ---
function getThumbnail($filePath, $width = 150, $height = 150) {
    $cleanPath = ltrim($filePath, '/');

    if (empty($cleanPath) || !file_exists($cleanPath)) {
        return 'assets/images/placeholder.png'; 
    }

    $dirName  = dirname($cleanPath);
    $fileName = basename($cleanPath);
    
    $thumbDir  = $dirName . '/thumbs/'; 
    $thumbPath = $thumbDir . $fileName;

    if (file_exists($thumbPath)) return $thumbPath;

    if (!is_dir($thumbDir)) mkdir($thumbDir, 0777, true);

    $info = getimagesize($cleanPath);
    if (!$info) return $cleanPath;

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

<style>
    .scrollbar-thin::-webkit-scrollbar { height: 6px; }
    .scrollbar-thin::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
    .scrollbar-thin::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 4px; }
    
    /* Active Variation Card Style */
    .var-card.active { 
        border-color: #f97316; 
        background-color: #fff7ed; 
        box-shadow: 0 0 0 1px #f97316; 
    }
    
    /* Hide non-active containers */
    .image-container { display: none; }
    .image-container.active { display: grid; }

    /* Dragging Visuals */
    .draggable-item.dragging { opacity: 0.5; border: 2px dashed #999; }
</style>

<div class="max-w-7xl mx-auto space-y-6 font-['Segoe_UI'] p-2 md:p-6 bg-gray-50 min-h-screen">

    <div class="bg-white rounded-xl shadow-sm border border-gray-300 p-5 flex flex-col lg:flex-row gap-6">
        <div class="w-32 h-32 md:w-40 md:h-40 bg-gray-100 rounded-lg border border-gray-200 flex-shrink-0 p-1 flex items-center justify-center">
            <?php 
                // OPTIMIZATION 1: Main Image Thumbnail
                $mainImgPath = $item['product_photo'] ?? '';
                $mainThumbSrc = (!empty($mainImgPath)) ? base_url(getThumbnail($mainImgPath)) : 'assets/no-img.png';
            ?>
            <img src="<?php echo $mainThumbSrc; ?>" class="max-w-full max-h-full object-contain rounded">
        </div>
        <div class="flex-grow grid grid-cols-2 md:grid-cols-4 gap-y-3 gap-x-6 text-[13px] text-gray-600 content-center">
            <div><span class="block font-bold text-gray-900">Category:</span> <?php echo $item['category'] ?? '-'; ?></div>
            <div><span class="block font-bold text-gray-900">Height:</span> <?php echo $item['height'] ?? '-'; ?> <?php echo $item['dimention_unit'] ?? ''; ?></div>
            <div><span class="block font-bold text-gray-900">Width:</span> <?php echo $item['width'] ?? '-'; ?> <?php echo $item['dimention_unit'] ?? ''; ?></div>
            <div><span class="block font-bold text-gray-900">Weight:</span> <?php echo $item['weight'] ?? '-'; ?> <?php echo $item['weight_unit'] ?? ''; ?></div>
            <div><span class="block font-bold text-gray-900">Material:</span> <?php echo $item['material'] ?? '-'; ?></div>
            <div><span class="block font-bold text-gray-900">Vendor:</span> <?php echo $item['vendor_name'] ?? '-'; ?></div>
            <div><span class="block font-bold text-gray-900">Received By:</span> <?php echo $item['recived_by_name'] ?? '-'; ?></div>
            <div class="col-span-2"><span class="font-bold text-gray-900">Gate Entry:</span> <?php echo $item['gate_entry_date_time'] ?? '-'; ?></div>
        </div>
        <div class="w-full lg:w-64 flex-shrink-0 flex flex-col justify-center">
            <div class="border border-gray-300 rounded-lg p-4 bg-gray-50 text-center">
                <a href="<?php echo base_url('?page=inbounding&action=download_photos&id='.$record_id); ?>" class="bg-black text-white text-xs font-bold py-2.5 w-full rounded flex items-center justify-center gap-2">
                    <i class="fa-solid fa-download"></i> Download All Photos
                </a>
            </div>
        </div>
    </div>

    <div class="flex overflow-x-auto gap-4 pb-2 scrollbar-thin" id="variation-strip">
        
        <div class="var-card active cursor-pointer bg-white border-2 border-gray-300 rounded-lg p-2 flex gap-3 min-w-[240px] items-center shadow-sm transition-all"
             onclick="switchVariation(-1, this)">
            <div class="w-16 h-16 bg-gray-100 rounded border border-gray-200 flex-shrink-0 flex items-center justify-center">
                <img src="<?php echo $mainThumbSrc; ?>" class="max-w-full max-h-full object-contain">
            </div>
            <div class="text-xs text-gray-600">
                <div class="font-bold text-black uppercase mb-1">Base Variation</div>
            <div><span class="font-bold text-gray-800">Color:</span> <?php echo $item['color'] ?? '-'; ?></div>
            <div><span class="font-bold text-gray-800">Size:</span> <?php echo $item['size'] ?? '-'; ?></div>
            </div>
        </div>

        <?php if(!empty($variations)): ?>
            <?php foreach($variations as $var): ?>
                <?php 
                    // OPTIMIZATION 2: Variation Strip Thumbnails
                    $varImgPath = $var['variation_image'] ?? '';
                    $varThumbSrc = (!empty($varImgPath)) ? base_url(getThumbnail($varImgPath)) : 'assets/no-img.png';
                ?>
                <div class="var-card cursor-pointer bg-white border border-gray-300 rounded-lg p-2 flex gap-3 min-w-[240px] items-center shadow-sm transition-all hover:border-gray-400"
                     onclick="switchVariation(<?php echo $var['id']; ?>, this)">
                    <div class="w-16 h-16 bg-gray-100 rounded border border-gray-200 flex-shrink-0 flex items-center justify-center">
                        <img src="<?php echo $varThumbSrc; ?>" class="max-w-full max-h-full object-contain rounded">
                    </div>
                    <div class="text-xs text-gray-600 flex flex-col gap-0.5">
                        <div><span class="font-bold text-gray-800">Cat:</span> <?php echo $item['category'] ?? ''; ?></div>
                        <div><span class="font-bold text-gray-800">Color:</span> <?php echo $var['color'] ?? ''; ?></div>
                        <div><span class="font-bold text-gray-800">Size:</span> <?php echo $var['size'] ?? ''; ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-300 p-6">
        <form action="<?php echo base_url('?page=inbounding&action=itmimgsave&id='.$record_id); ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="userid_log" value="<?php echo $_SESSION['user']['id'] ?? ''; ?>">
            
            <input type="hidden" id="current_variation_id" value="-1">
            <div id="deletedInputsContainer"></div>

            <div class="flex flex-col md:flex-row justify-between items-center mb-6 pb-6 border-b border-gray-200 gap-4">
                <div>
                    <h3 class="font-bold text-lg text-gray-900 flex items-center gap-2">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Upload Edited Photos
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">Select a variation above, then upload photos for it.</p>
                </div>
                <label id="uploadLabel" class="cursor-pointer bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2.5 px-5 rounded inline-flex items-center text-sm transition shadow-sm border border-gray-300">
                    <i class="fa-solid fa-folder-open mr-2"></i> 
                    <span id="uploadText">Choose File</span>
                    <input type="file" name="new_photos[]" id="fileInput" multiple accept=".jpg, .jpeg, image/jpeg" class="hidden">
                </label>
            </div>

            <div class="image-container active grid grid-cols-1 md:grid-cols-3 gap-4" id="container_-1" data-var-id="-1">
                <?php renderImageGrid($grouped_images['-1'] ?? []); ?>
            </div>

            <?php foreach($variations as $var): ?>
                <div class="image-container grid grid-cols-1 md:grid-cols-3 gap-4" id="container_<?php echo $var['id']; ?>" data-var-id="<?php echo $var['id']; ?>">
                    <?php renderImageGrid($grouped_images[$var['id']] ?? []); ?>
                </div>
            <?php endforeach; ?>

            <div class="mt-8 flex justify-end gap-3 pt-4 border-t border-gray-100">
                 <a href="<?php echo base_url('?page=inbounding'); ?>" class="px-6 py-2.5 text-sm font-bold text-gray-600 bg-white border border-gray-300 rounded hover:bg-gray-50">Cancel</a>
                <button type="submit" class="bg-black hover:bg-gray-800 text-white font-bold py-2.5 px-8 rounded text-sm shadow-md transition">Save & Update</button>
            </div>
        </form>
    </div>
</div>

<?php
// Helper Function to Render Grid Items
function renderImageGrid($imgs) {
    if(empty($imgs)) return;
    foreach($imgs as $img) {
        // OPTIMIZATION 3: Existing Grid Images
        // Assuming images are in 'uploads/itm_img/'
        $fullPath = 'uploads/itm_img/' . $img['file_name'];
        $thumbSrc = base_url(getThumbnail($fullPath));
        ?>
        <div class="draggable-item flex border border-gray-300 rounded-md p-2 gap-3 bg-white relative shadow-sm cursor-move" draggable="true">
            <input type="hidden" name="image_ids_ordered[]" value="<?php echo $img['id']; ?>">
            
            <div class="text-gray-400 handle cursor-grab pt-2 pl-1"><i class="fa-solid fa-grip-vertical"></i></div>
            
            <div class="w-24 h-24 bg-gray-100 rounded border border-gray-200 flex-shrink-0 flex items-center justify-center">
                <img src="<?php echo $thumbSrc; ?>" class="max-w-full max-h-full object-contain">
            </div>
            
            <div class="flex-grow flex flex-col justify-center space-y-2 pr-7 relative">
                <label class="text-[10px] font-bold text-gray-500 uppercase">Caption</label>
                <input type="text" name="captions[<?php echo $img['id']; ?>]" 
                       value="<?php echo htmlspecialchars($img['image_caption'] ?? ''); ?>"
                       class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:border-black outline-none">
                
                <button type="button" onclick="markForDeletion(this, <?php echo $img['id']; ?>)" class="absolute top-0 right-0 text-gray-400 hover:text-red-600">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </div>
        <?php
    }
}
?>

<script>
    const fileInput = document.getElementById('fileInput');
    const currentVarInput = document.getElementById('current_variation_id');
    const deletedContainer = document.getElementById('deletedInputsContainer');
    const uploadLabel = document.getElementById('uploadLabel');
    const uploadText = document.getElementById('uploadText');
    let globalFiles = new Map();

    // 1. SWITCH VARIATION LOGIC
    window.switchVariation = function(varId, cardElement) {
        document.querySelectorAll('.var-card').forEach(el => el.classList.remove('active', 'border-orange-500', 'bg-orange-50'));
        cardElement.classList.add('active', 'border-orange-500', 'bg-orange-50');
        currentVarInput.value = varId;
        document.querySelectorAll('.image-container').forEach(el => el.classList.remove('active'));
        const targetContainer = document.getElementById('container_' + varId);
        if(targetContainer) targetContainer.classList.add('active');
    }

    // 2. IMAGE COMPRESSION FUNCTION (Speeds up upload)
    async function compressImage(file, maxWidth = 1500, quality = 0.8) {
        return new Promise((resolve) => {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = function(event) {
                const img = new Image();
                img.src = event.target.result;
                img.onload = function() {
                    const canvas = document.createElement('canvas');
                    let width = img.width;
                    let height = img.height;

                    // Resize logic
                    if (width > maxWidth) {
                        height *= maxWidth / width;
                        width = maxWidth;
                    }

                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);

                    // Convert to Blob (Compressed file)
                    canvas.toBlob((blob) => {
                        const newFile = new File([blob], file.name, {
                            type: 'image/jpeg',
                            lastModified: Date.now()
                        });
                        resolve(newFile);
                    }, 'image/jpeg', quality);
                };
            };
        });
    }

    // 3. HANDLE FILE SELECTION (Async for compression)
    fileInput.addEventListener('change', async function() {
        const activeVarId = currentVarInput.value;
        const activeContainer = document.getElementById('container_' + activeVarId);
        
        // Show loading state
        const originalLabelText = uploadText.innerText;
        uploadText.innerText = "Compressing...";
        uploadLabel.classList.add('opacity-50', 'cursor-not-allowed');

        for (let i = 0; i < this.files.length; i++) {
            let file = this.files[i];

            // Validation
            const fileName = file.name.toLowerCase();
            const isJpgExtension = fileName.endsWith('.jpg') || fileName.endsWith('.jpeg');
            const isJpgMime = file.type === 'image/jpeg' || file.type === 'image/jpg';

            if (!isJpgExtension && !isJpgMime) {
                alert('Only JPG/JPEG images are allowed. Skipped: ' + file.name);
                continue; 
            }

            // COMPRESS BEFORE ADDING
            try {
                const compressedFile = await compressImage(file);
                
                // Use compressed file size for key
                let uniqueKey = compressedFile.name + '-' + compressedFile.size + '-' + activeVarId; 
                
                if(!globalFiles.has(uniqueKey)){
                    globalFiles.set(uniqueKey, compressedFile);
                    createPreview(compressedFile, uniqueKey, activeContainer, activeVarId);
                }
            } catch (e) {
                console.error("Compression failed for " + file.name, e);
                alert("Could not compress " + file.name);
            }
        }
        
        // Reset loading state
        uploadText.innerText = originalLabelText;
        uploadLabel.classList.remove('opacity-50', 'cursor-not-allowed');
        
        updateFileInput();
    });

    // 4. CREATE PREVIEW
    function createPreview(file, uniqueKey, container, varId) {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onloadend = function() {
            const div = document.createElement('div');
            div.className = "draggable-item flex border border-gray-300 rounded-md p-2 gap-3 bg-white relative shadow-sm cursor-move animate-fade-in";
            div.setAttribute('draggable', 'true');
            div.setAttribute('data-key', uniqueKey);

            div.innerHTML = `
                <input type="hidden" name="new_image_variation_id[]" value="${varId}">
                
                <div class="text-gray-400 handle cursor-grab pt-2 pl-1"><i class="fa-solid fa-grip-vertical"></i></div>
                
                <div class="w-24 h-24 bg-gray-100 rounded border border-gray-200 flex-shrink-0 flex items-center justify-center relative">
                    <img src="${reader.result}" class="max-w-full max-h-full object-contain opacity-90">
                    <span class="absolute top-0 right-0 bg-blue-600 text-white text-[9px] px-1 rounded-bl">NEW</span>
                </div>
                
                <div class="flex-grow flex flex-col justify-center space-y-2 pr-7 relative">
                    <label class="text-[10px] font-bold text-gray-500 uppercase">Caption</label>
                    <input type="text" name="new_captions[]" placeholder="Enter caption..." class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:border-black outline-none">
                    
                    <button type="button" onclick="removeNewFile(this, '${uniqueKey}')" class="absolute top-0 right-0 text-gray-400 hover:text-red-600">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            `;
            
            addDragEvents(div);
            container.appendChild(div);
        }
    }

    // 5. HELPERS
    window.removeNewFile = function(btn, key) {
        btn.closest('.draggable-item').remove();
        globalFiles.delete(key);
        updateFileInput();
    }

    function updateFileInput() {
        const newDt = new DataTransfer();
        
        // Instead of just relying on the DOM, let's sync directly from our Map
        // We iterate through the globalFiles Map which is the "Source of Truth"
        globalFiles.forEach((fileObject, key) => {
            newDt.items.add(fileObject);
        });

        // Sync the actual input element with our processed/compressed files
        fileInput.files = newDt.files;
        
        // Debugging: This will show you in the console if files are actually attached
        console.log("Files attached to input:", fileInput.files.length);
    }

    window.markForDeletion = function(btn, dbId) {
        if(!confirm("Delete this image?")) return;
        btn.closest('.draggable-item').remove();
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = 'delete_ids[]'; input.value = dbId;
        deletedContainer.appendChild(input);
    }

    // Drag & Drop
    document.querySelectorAll('.draggable-item').forEach(item => addDragEvents(item));
    function addDragEvents(item) {
        item.addEventListener('dragstart', () => { item.classList.add('dragging'); });
        item.addEventListener('dragend', () => { item.classList.remove('dragging'); });
    }

    document.querySelectorAll('.image-container').forEach(container => {
        container.addEventListener('dragover', e => {
            e.preventDefault();
            const afterElement = getDragAfterElement(container, e.clientY, e.clientX);
            const draggable = document.querySelector('.dragging');
            if (draggable) {
                if (afterElement == null) container.appendChild(draggable);
                else container.insertBefore(draggable, afterElement);
            }
        });
    });

    function getDragAfterElement(container, y, x) {
        const draggableElements = [...container.querySelectorAll('.draggable-item:not(.dragging)')];
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offsetX = x - box.left - box.width / 2;
            const offsetY = y - box.top - box.height / 2;
            const dist = Math.hypot(offsetX, offsetY);
            if (y < box.bottom && x < box.right) {
                 if(closest.element === null) return { element: child, dist: dist };
                 if(dist < closest.dist) return { element: child, dist: dist };
            }
            return closest;
        }, { element: null, dist: Number.POSITIVE_INFINITY }).element;
    }
</script>