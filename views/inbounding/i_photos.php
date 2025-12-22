<?php
// Retrieve variables
$images = $data['images'] ?? [];
$item = $data['item'] ?? [];
$record_id = $data['record_id'] ?? 0;
?>

<style>
    /* Initialize counter */
    #sortable-list { counter-reset: img-order; }
    
    /* Increment counter for every draggable item */
    .draggable-item { counter-increment: img-order; }

    /* Inject the number into the badge */
    .order-badge::after { content: "#" counter(img-order); }
    
    /* Dragging Styles */
    .draggable-item.dragging { opacity: 0.5; border: 2px dashed #999; }
</style>

<div class="max-w-6xl mx-auto bg-white rounded-xl shadow-sm border border-gray-200 p-6 font-['Segoe_UI']">
    
    <h2 class="text-xl font-bold text-gray-900 mb-5 ml-[5px]">Edited Photo</h2>
    <div class="flex flex-col md:flex-row gap-6 pb-6 border-b border-gray-200 mb-6">
        <div class="shrink-0 w-32 h-32 bg-gray-100 rounded-lg border border-gray-200 p-1">
            <img src="<?php echo base_url($item['product_photo'] ?? 'assets/no-img.png'); ?>" class="w-full h-full object-contain rounded">
        </div>

        <div class="flex-grow grid grid-cols-2 md:grid-cols-4 gap-x-8 gap-y-2 text-[13px] text-gray-700">
            <div>
                <span class="font-bold text-gray-900 block">Category:</span>
                <?php echo $item['category'] ?? '-'; ?>
            </div>
            <div>
                <span class="font-bold text-gray-900 block">Height:</span>
                <?php echo $item['height'] ?? '-'; ?> <?php echo $item['dimention_unit'] ?? ''; ?>
            </div>
            <div>
                <span class="font-bold text-gray-900 block">Width:</span>
                <?php echo $item['width'] ?? '-'; ?> <?php echo $item['dimention_unit'] ?? ''; ?>
            </div>
            <div>
                <span class="font-bold text-gray-900 block">Weight:</span>
                <?php echo $item['weight'] ?? '-'; ?> <?php echo $item['weight_unit'] ?? ''; ?>
            </div>

            <div>
                <span class="font-bold text-gray-900 block">Material:</span>
                <?php echo $item['material'] ?? '-'; ?>
            </div>
            <div>
                <span class="font-bold text-gray-900 block">Vendor:</span>
                <?php echo $item['vendor_name'] ?? '-'; ?>
            </div>
            <div>
                <span class="font-bold text-gray-900 block">Depth:</span>
                <?php echo $item['depth'] ?? '-'; ?> <?php echo $item['dimention_unit'] ?? ''; ?>
            </div>
            <div>
                <span class="font-bold text-gray-900 block">Received by:</span>
                <?php echo $item['recived_by_name'] ?? '-'; // Ensure 'received_name' is fetched if available ?>
            </div>

            <div class="col-span-2 mt-2">
                <span class="font-bold text-gray-900 block">Gate Entry Date & Time:</span>
                <?php echo !empty($item['gate_entry_date_time']) ? date('d M Y h:i A', strtotime($item['gate_entry_date_time'])) : '-'; ?>
            </div>
        </div>

        <div class="w-full md:w-auto flex flex-col items-end justify-center">
            <div class="border border-gray-200 rounded-lg p-3 bg-gray-50 w-full md:w-[280px] flex items-center justify-between">
                <span class="text-sm font-bold text-gray-700">Download Raw Photos</span>
                <a href="<?php echo base_url('?page=inbounding&action=download_photos&id='.$record_id); ?>" 
                   class="bg-black text-white text-xs font-bold px-4 py-2 rounded flex items-center gap-2 hover:bg-gray-800 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                    Download
                </a>
            </div>
        </div>
    </div>

    <form action="<?php echo base_url('?page=inbounding&action=itmimgsave&id='.$record_id); ?>" method="POST" enctype="multipart/form-data" id="photoForm">
        <input type="hidden" name="userid_log" value="<?php echo $_SESSION['user']['id'] ?? ''; ?>">
        <div id="deletedInputsContainer"></div>

        <div class="grid grid-cols-1 md:grid-cols-[1fr,auto] gap-6 items-center border border-gray-300 rounded-lg p-6 mb-8">
            <div class="flex flex-col items-center justify-center text-center">
                <div class="mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                </div>
                <h3 class="font-bold text-gray-800">Upload Edited Photos</h3>
                <p class="text-xs text-gray-500">Drag or paste a file here, or choose an option below</p>
            </div>
            
            <div class="flex items-center gap-4 border-l border-gray-200 pl-6 h-full">
                <label class="cursor-pointer bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded inline-flex items-center text-sm transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    Choose File
                    <input type="file" name="new_photos[]" id="fileInput" multiple accept="image/*" class="hidden">
                </label>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="sortable-list">
            
            <?php if(!empty($images)): ?>
                <?php foreach($images as $img): ?>
                <div class="draggable-item flex border border-gray-400 rounded-md p-2 gap-3 bg-white existing-photo relative shadow-sm cursor-move" draggable="true">
                    
                    <input type="hidden" name="image_ids_ordered[]" value="<?php echo $img['id']; ?>">
                    <input type="hidden" name="is_new[]" value="0"> 

                    <div class="absolute top-2 left-1 text-gray-400 handle">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M7 2a2 2 0 10-.001 4.001A2 2 0 007 2zm0 6a2 2 0 10-.001 4.001A2 2 0 007 8zm0 6a2 2 0 10-.001 4.001A2 2 0 007 14zm6-8a2 2 0 10-.001-4.001A2 2 0 0013 6zm0 2a2 2 0 10.001 4.001A2 2 0 0013 8zm0 6a2 2 0 10.001 4.001A2 2 0 0013 14z" /></svg>
                    </div>

                    <div class="w-32 h-32 bg-[#e6e2dd] rounded ml-4 flex items-center justify-center shrink-0 border border-gray-300 pointer-events-none">
                        <img src="uploads/itm_img/<?php echo $img['file_name']; ?>" class="max-w-full max-h-full object-contain">
                    </div>

                    <div class="flex-grow flex flex-col justify-center space-y-3 relative pr-8">
                        <div class="flex justify-between items-end">
                            <label class="text-xs font-bold text-gray-700">Image Caption:</label>
                            <span class="order-badge text-[10px] font-mono font-bold text-gray-400 bg-gray-100 border border-gray-200 px-1.5 rounded"></span>
                        </div>
                        <input type="text" name="captions[<?php echo $img['id']; ?>]" 
                               value="<?php echo htmlspecialchars($img['image_caption'] ?? ''); ?>"
                               class="w-full border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:border-gray-500">

                        <button type="button" onclick="markForDeletion(this, <?php echo $img['id']; ?>)" 
                                class="absolute bottom-0 right-0 text-gray-800 hover:text-red-600 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

            </div>

        <div class="mt-8 flex justify-end gap-3">
             <a href="<?php echo base_url('?page=inbounding'); ?>" class="px-5 py-2.5 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</a>
            <button type="submit" class="bg-black hover:bg-gray-800 text-white font-bold py-2.5 px-6 rounded text-sm shadow-md transition">Save & Update</button>
        </div>
    </form>
</div>

<script>
    const fileInput = document.getElementById('fileInput');
    const sortableList = document.getElementById('sortable-list');
    const deletedContainer = document.getElementById('deletedInputsContainer');
    
    // Store all files in a Map for easy retrieval by name
    let globalFiles = new Map();

    // 1. HANDLE FILE SELECTION
    fileInput.addEventListener('change', function() {
        for (let i = 0; i < this.files.length; i++) {
            let file = this.files[i];
            // Avoid duplicates based on name+size
            let uniqueKey = file.name + '-' + file.size;
            if(!globalFiles.has(uniqueKey)){
                globalFiles.set(uniqueKey, file);
                createPreview(file, uniqueKey);
            }
        }
        updateFileInput(); // Sync the input with the map
    });

    // 2. CREATE PREVIEW (For New Files)
    function createPreview(file, uniqueKey) {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onloadend = function() {
            const div = document.createElement('div');
            
            // IMPORTANT: 'draggable-item' class enables the drag logic
            // 'data-key' helps us find the actual file object later
            div.className = "draggable-item flex border border-gray-400 rounded-md p-2 gap-3 bg-gray-50 relative shadow-sm cursor-move";
            div.setAttribute('draggable', 'true');
            div.setAttribute('data-key', uniqueKey); 
            div.setAttribute('data-is-new', 'true');

            div.innerHTML = `
                 <input type="hidden" name="is_new_ordered[]" value="1">

                <div class="absolute top-2 left-1 text-gray-400 handle">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M7 2a2 2 0 10-.001 4.001A2 2 0 007 2zm0 6a2 2 0 10-.001 4.001A2 2 0 007 8zm0 6a2 2 0 10-.001 4.001A2 2 0 007 14zm6-8a2 2 0 10-.001-4.001A2 2 0 0013 6zm0 2a2 2 0 10.001 4.001A2 2 0 0013 8zm0 6a2 2 0 10.001 4.001A2 2 0 0013 14z" /></svg>
                </div>

                <div class="w-32 h-32 bg-[#e6e2dd] rounded ml-4 flex items-center justify-center shrink-0 border border-gray-300 relative pointer-events-none">
                    <img src="${reader.result}" class="max-w-full max-h-full object-contain opacity-90">
                    <span class="absolute top-1 left-1 bg-blue-600 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow">NEW</span>
                </div>

                <div class="flex-grow flex flex-col justify-center space-y-3 relative pr-8">
                    <div class="flex justify-between items-end">
                        <label class="block text-xs font-bold text-gray-700">Image Caption:</label>
                        <span class="order-badge text-[10px] font-mono font-bold text-gray-400 bg-gray-100 border border-gray-200 px-1.5 rounded"></span>
                    </div>
                    
                    <input type="text" name="new_captions[]" placeholder="Enter caption..." class="w-full border border-gray-300 bg-white rounded px-2 py-1 text-sm focus:outline-none focus:border-gray-500">

                    <button type="button" onclick="removeNewFile(this, '${uniqueKey}')" 
                            class="absolute bottom-0 right-0 text-red-500 hover:text-red-700 transition">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                </div>
            `;
            
            // Add Drag Events to the new element
            addDragEvents(div);
            sortableList.appendChild(div);
            
            // Important: Re-sync file input order after adding
            updateFileInput();
        }
    }

    // 3. REMOVE NEW FILE
    window.removeNewFile = function(btn, key) {
        btn.closest('.draggable-item').remove();
        globalFiles.delete(key);
        updateFileInput();
    }

    // 4. SYNC DOM ORDER TO FILE INPUT
    // This is the magic part: It rebuilds the <input type="file"> 
    // based on the visual order of the cards in the grid.
    function updateFileInput() {
        const newDt = new DataTransfer();
        
        // Loop through DOM elements to get the current visual order
        const items = sortableList.querySelectorAll('.draggable-item');
        
        items.forEach(item => {
            // Only care about new items (which have data-key)
            const key = item.getAttribute('data-key');
            if (key && globalFiles.has(key)) {
                newDt.items.add(globalFiles.get(key));
            }
        });
        
        fileInput.files = newDt.files;
    }

    // 5. EXISTING DELETE LOGIC
    window.markForDeletion = function(btn, dbId) {
        if(!confirm("Are you sure you want to delete this image?")) return;
        const parent = btn.closest('.draggable-item');
        parent.style.display = 'none'; 
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'delete_ids[]';
        input.value = dbId;
        deletedContainer.appendChild(input);
        
        // Remove ordering input so it doesn't count
        const orderInput = parent.querySelector('input[name="image_ids_ordered[]"]');
        if(orderInput) orderInput.remove();
    }

    // 6. DRAG AND DROP LOGIC (Unified)
    // Apply to existing items
    document.querySelectorAll('.draggable-item').forEach(item => {
        addDragEvents(item);
    });

    function addDragEvents(item) {
        item.addEventListener('dragstart', () => {
            item.classList.add('dragging');
            item.style.opacity = '0.5';
        });

        item.addEventListener('dragend', () => {
            item.classList.remove('dragging');
            item.style.opacity = '1';
            // CRITICAL: Update the file input order every time a drag finishes
            updateFileInput();
        });
    }

    sortableList.addEventListener('dragover', e => {
        e.preventDefault();
        const afterElement = getDragAfterElement(sortableList, e.clientY, e.clientX);
        const draggable = document.querySelector('.dragging');
        if (afterElement == null) {
            sortableList.appendChild(draggable);
        } else {
            sortableList.insertBefore(draggable, afterElement);
        }
    });

    function getDragAfterElement(container, y, x) {
        const draggableElements = [...container.querySelectorAll('.draggable-item:not(.dragging)')];

        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            // Use a combination of X and Y to find the closest element in the Grid
            const offsetX = x - box.left - box.width / 2;
            const offsetY = y - box.top - box.height / 2;
            
            // Simple logic: We calculate distance to center of box
            const dist = Math.hypot(offsetX, offsetY);
            
            // Check if we are roughly "before" this element visually
            // In a grid, if we are to the left or above, we are "before"
            
            // Simplified "Insert Before" logic for Grid:
            // If the mouse is to the left of the center (offsetX < 0) 
            // AND within the vertical band of the item
            
            // Standard reducer logic: find the element with closest negative offset
            // But for grid, we often just want the closest element generally.
            
            // Let's stick to the standard vertical-list style logic adapted slightly:
            if (y < box.bottom && x < box.right) {
                 if(closest.element === null) return { element: child, dist: dist };
                 if(dist < closest.dist) return { element: child, dist: dist };
            }
            return closest;
        }, { element: null, dist: Number.POSITIVE_INFINITY }).element;
    }
</script>