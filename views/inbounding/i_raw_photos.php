<?php
// Retrieve variables
$images = $data['images'] ?? [];
$item = $data['item'] ?? [];
$record_id = $data['record_id'] ?? 0;
?>

<div class="max-w-6xl mx-auto bg-white rounded-xl shadow-sm border border-gray-200 p-6 font-['Segoe_UI']">

    <div class="flex flex-col md:flex-row gap-6 pb-6 border-b border-gray-200 mb-6">
        <div class="shrink-0 w-32 h-32 bg-gray-100 rounded-lg border border-gray-200 p-1">
            <img src="<?php echo base_url($item['product_photo'] ?? 'assets/no-img.png'); ?>" class="w-full h-full object-contain rounded">
        </div>

        <div class="flex-grow grid grid-cols-2 md:grid-cols-4 gap-x-8 gap-y-2 text-[13px] text-gray-700">
            <div><span class="font-bold text-gray-900 block">Category:</span><?php echo $item['category'] ?? '-'; ?></div>
            <div><span class="font-bold text-gray-900 block">Height:</span><?php echo $item['height'] ?? '-'; ?> <?php echo $item['dimention_unit'] ?? ''; ?></div>
            <div><span class="font-bold text-gray-900 block">Width:</span><?php echo $item['width'] ?? '-'; ?> <?php echo $item['dimention_unit'] ?? ''; ?></div>
            <div><span class="font-bold text-gray-900 block">Weight:</span><?php echo $item['weight'] ?? '-'; ?> <?php echo $item['weight_unit'] ?? ''; ?></div>
            <div><span class="font-bold text-gray-900 block">Material:</span><?php echo $item['material'] ?? '-'; ?></div>
            <div><span class="font-bold text-gray-900 block">Vendor:</span><?php echo $item['vendor_name'] ?? '-'; ?></div>
            <div><span class="font-bold text-gray-900 block">Depth:</span><?php echo $item['depth'] ?? '-'; ?> <?php echo $item['dimention_unit'] ?? ''; ?></div>
            <div><span class="font-bold text-gray-900 block">Received by:</span><?php echo $item['recived_by_name'] ?? '-'; ?></div>
            <div class="col-span-2 mt-2">
                <span class="font-bold text-gray-900 block">Gate Entry Date & Time:</span>
                <?php echo !empty($item['gate_entry_date_time']) ? date('d M Y h:i A', strtotime($item['gate_entry_date_time'])) : '-'; ?>
            </div>
        </div>
    </div>

    <form action="<?php echo base_url('?page=inbounding&action=itmrawimgsave&id='.$record_id); ?>" method="POST" enctype="multipart/form-data">
        
        <div id="deletedInputsContainer"></div>

        <div class="grid grid-cols-1 md:grid-cols-[1fr,auto] gap-6 items-center border border-gray-300 rounded-lg p-6 mb-8 bg-gray-50 border-dashed">
            <div class="flex flex-col items-center justify-center text-center">
                <div class="mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-400 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="font-bold text-gray-800">Upload Raw Photos</h3>
                <p class="text-xs text-gray-500">Bulk upload supported</p>
            </div>
            
            <div class="flex items-center gap-4 border-l border-gray-200 pl-6 h-full">
                <label class="cursor-pointer bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-flex items-center text-sm transition shadow">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Select Files
                    <input type="file" name="new_photos[]" id="fileInput" multiple accept="image/*" class="hidden">
                </label>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4" id="previewContainer">
            
            <?php if(!empty($images)): ?>
                <?php foreach($images as $img): ?>
                <div class="group relative bg-white border border-gray-200 rounded-lg p-2 shadow-sm existing-photo hover:shadow-md transition">
                    <div class="aspect-square bg-gray-100 rounded flex items-center justify-center overflow-hidden relative">
                        <img src="uploads/itm_raw_img/<?php echo $img['file_name']; ?>" class="w-full h-full object-cover">
                        
                        <button type="button" onclick="markForDeletion(this, <?php echo $img['id']; ?>)" 
                                class="absolute top-2 right-2 bg-white/90 text-red-600 p-1.5 rounded-full shadow hover:bg-red-500 hover:text-white transition opacity-0 group-hover:opacity-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
        </div>

        <div class="mt-8 flex justify-end gap-3 pt-4 border-t border-gray-100">
             <a href="<?php echo base_url('?page=inbounding'); ?>" class="px-5 py-2.5 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" class="bg-black hover:bg-gray-800 text-white font-bold py-2.5 px-6 rounded text-sm shadow-md transition">
                Save Changes
            </button>
        </div>

    </form>
</div>

<script>
    const fileInput = document.getElementById('fileInput');
    const previewContainer = document.getElementById('previewContainer');
    const deletedContainer = document.getElementById('deletedInputsContainer');
    let dt = new DataTransfer();

    // 1. Handle File Selection
    fileInput.addEventListener('change', function() {
        for(let i = 0; i < this.files.length; i++){
            let file = this.files[i];
            dt.items.add(file);
            createPreview(file);
        }
        this.files = dt.files; 
    });

    // 2. Create Preview (Simplified: No captions/order)
    function createPreview(file) {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onloadend = function() {
            const div = document.createElement('div');
            const tempId = 'new-' + Math.random().toString(36).substr(2, 9);
            
            div.id = tempId;
            div.className = "relative bg-gray-50 border border-blue-200 border-dashed rounded-lg p-2 shadow-sm animate-pulse";

            div.innerHTML = `
                <div class="aspect-square bg-gray-200 rounded flex items-center justify-center overflow-hidden relative">
                    <img src="${reader.result}" class="w-full h-full object-cover opacity-80">
                    <span class="absolute bottom-2 left-2 bg-blue-600 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow">NEW</span>
                    
                    <button type="button" onclick="removeNewFile('${file.name}', '${tempId}')" 
                            class="absolute top-2 right-2 bg-white text-red-500 p-1 rounded-full shadow hover:bg-red-50 transition">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            `;
            previewContainer.appendChild(div);
        }
    }

    // 3. Remove New File
    window.removeNewFile = function(fileName, domId) {
        document.getElementById(domId).remove();
        const newDt = new DataTransfer();
        for(let i = 0; i < dt.files.length; i++) {
            if(dt.files[i].name !== fileName) newDt.items.add(dt.files[i]);
        }
        dt = newDt;
        fileInput.files = dt.files;
    }

    // 4. Mark Existing for Deletion
    window.markForDeletion = function(btn, dbId) {
        if(!confirm("Delete this raw photo?")) return;
        
        const parent = btn.closest('.existing-photo');
        
        // Visual feedback: hide the image or turn it red
        parent.style.opacity = '0.4';
        parent.style.borderColor = 'red';
        parent.style.pointerEvents = 'none'; // Prevent clicking again

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'delete_ids[]';
        input.value = dbId;
        deletedContainer.appendChild(input);
    }
</script>