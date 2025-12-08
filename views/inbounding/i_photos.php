<?php
// Retrieve variables passed from controller
$images = $data['images'] ?? [];
$record_id = $data['record_id'] ?? 0;
?>



<div class="max-w-5xl mx-auto bg-white rounded-xl shadow-sm border border-gray-200 p-6">

    <div class="flex justify-between items-center mb-6 border-b border-gray-100 pb-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-800">Item Photos</h1>
            <p class="text-sm text-gray-500">Managing photos for Item ID: #<?php echo $record_id; ?></p>
        </div>
        <a href="<?php echo base_url('?page=inbounding&action=list') ?>" 
           class="text-gray-500 hover:text-gray-700 text-sm flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Back to Item
        </a>
    </div>

    <form action="<?php echo base_url('?page=inbounding&action=itmimgsave&id='.$record_id); ?>" method="POST" enctype="multipart/form-data">
        
        <div id="deletedInputsContainer"></div>

        <div id="dropZone" 
             class="relative border-2 border-dashed border-gray-300 rounded-lg p-12 text-center hover:bg-orange-50/30 transition-all cursor-pointer group">
            
            <input type="file" name="new_photos[]" id="fileInput" multiple accept="image/*" 
                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
            
            <div class="space-y-3 pointer-events-none">
                <div class="w-14 h-14 bg-orange-50 text-[#d9822b] rounded-full flex items-center justify-center mx-auto group-hover:scale-110 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                    </svg>
                </div>
                <h3 class="text-gray-700 font-medium">Click to upload or drag & drop</h3>
                <p class="text-xs text-gray-400">Supported formats: JPG, PNG, WEBP</p>
            </div>
        </div>

        <div class="mt-8">
            <h3 class="text-sm font-semibold text-gray-800 mb-4">Gallery Preview</h3>
            
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4" id="previewContainer">
                
                <?php if(!empty($images)): ?>
                    <?php foreach($images as $img): ?>
                        <div class="relative group bg-gray-100 rounded-lg overflow-hidden aspect-square border border-gray-200 existing-photo">
                            <img src="uploads/itm_img/<?php echo $img['file_name']; ?>" class="w-full h-full object-cover">
                            
                            <button type="button" onclick="markForDeletion(this, <?php echo $img['id']; ?>)" 
                                    class="absolute top-2 right-2 bg-white text-red-500 p-1.5 rounded-full shadow-md opacity-0 group-hover:opacity-100 transition-all hover:bg-red-50 hover:scale-110 z-20">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                </svg>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                </div>
        </div>

        <div class="mt-8 pt-5 border-t border-gray-100 flex justify-end gap-3">
            <a href="<?php echo base_url('?page=inbounding'); ?>" class="px-5 py-2.5 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" 
                    class="px-6 py-2.5 text-sm font-medium text-white rounded-lg shadow-sm hover:opacity-90 transition-opacity flex items-center gap-2"
                    style="background-color: #d9822b;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                Save Changes
            </button>
        </div>
    </form>
</div>

<script>
    const fileInput = document.getElementById('fileInput');
    const previewContainer = document.getElementById('previewContainer');
    const dropZone = document.getElementById('dropZone');
    const deletedContainer = document.getElementById('deletedInputsContainer');
    
    // Use DataTransfer to manage file list
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

    // 2. Drag & Drop Visuals
    ['dragenter', 'dragover'].forEach(eName => {
        dropZone.addEventListener(eName, (e) => {
            e.preventDefault();
            dropZone.classList.add('border-[#d9822b]', 'bg-orange-50');
        });
    });

    ['dragleave', 'drop'].forEach(eName => {
        dropZone.addEventListener(eName, (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-[#d9822b]', 'bg-orange-50');
        });
    });

    // 3. Handle Drop Event
    dropZone.addEventListener('drop', (e) => {
        let droppedFiles = e.dataTransfer.files;
        for(let i = 0; i < droppedFiles.length; i++){
            let file = droppedFiles[i];
            dt.items.add(file);
            createPreview(file);
        }
        fileInput.files = dt.files;
    });

    // 4. Create Preview Element
    function createPreview(file) {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onloadend = function() {
            const div = document.createElement('div');
            const tempId = 'new-' + Math.random().toString(36).substr(2, 9);
            div.id = tempId;
            div.className = "relative group bg-gray-100 rounded-lg overflow-hidden aspect-square border border-gray-200 new-photo shadow-sm";

            div.innerHTML = `
                <img src="${reader.result}" class="w-full h-full object-cover opacity-90">
                <div class="absolute top-2 left-2 bg-[#d9822b] text-white text-[10px] px-2 py-0.5 rounded-md shadow-sm">New</div>
                <button type="button" onclick="removeNewFile('${file.name}', '${tempId}')" 
                        class="absolute top-2 right-2 bg-white text-red-500 p-1.5 rounded-full shadow-md opacity-0 group-hover:opacity-100 transition-all hover:bg-red-50 hover:scale-110 z-20">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                        <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                    </svg>
                </button>
            `;
            previewContainer.appendChild(div);
        }
    }

    // 5. Remove New File (Before Upload)
    window.removeNewFile = function(fileName, domId) {
        document.getElementById(domId).remove();
        const newDt = new DataTransfer();
        for(let i = 0; i < dt.files.length; i++) {
            if(dt.files[i].name !== fileName) newDt.items.add(dt.files[i]);
        }
        dt = newDt;
        fileInput.files = dt.files;
    }

    // 6. Mark DB File for Deletion
    window.markForDeletion = function(btn, dbId) {
        if(!confirm("Delete this image? (Changes apply after Save)")) return;
        
        const parent = btn.closest('.existing-photo');
        parent.style.opacity = '0.5';
        setTimeout(() => parent.remove(), 300);

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'delete_ids[]';
        input.value = dbId;
        deletedContainer.appendChild(input);
    }
</script>