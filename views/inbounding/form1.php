<?php
// 1. GET DATA
$form1 = $data['form1'] ?? [];
$raw_categories = $data['category'] ?? []; // This is your big array from DB

// 2. SETUP VARIABLES
$isEdit = !empty($form1);
$id = $form1['id'] ?? '';
// The value currently saved in the database (for editing)
$saved_category_code = $form1['group_name'] ?? ''; 
$photo = $form1['product_photo'] ?? '';

$actionUrl = $isEdit
    ? base_url('?page=inbounding&action=updateform1&id=' . $id)
    : base_url('?page=inbounding&action=saveform1');

// 3. ICON MAPPING (Map DB 'name' to FontAwesome classes)
$icon_map = [
    'sculptures'    => 'fa-solid fa-monument',
    'book'          => 'fa-solid fa-book',
    'jewelry'       => 'fa-solid fa-gem',
    'textiles'      => 'fa-solid fa-shirt',
    'paintings'     => 'fa-solid fa-palette',
    'homeandliving' => 'fa-solid fa-couch',
    'default'       => 'fa-solid fa-box-open' // Fallback icon
];

// 4. PROCESS CATEGORIES (Filter for parent_id == 0)
$display_categories = [];
if (!empty($raw_categories)) {
    foreach ($raw_categories as $cat) {
		print_array($cat);
        // FILTER: Only show if parent_id is 0
        if (isset($cat['parent_id']) && $cat['parent_id'] == 0) {

            // Get Icon (check if name exists in map, else use default)
            $iconClass = $icon_map[$cat['name']] ?? $icon_map['default'];

            $display_categories[] = [
                'value' => $cat['category'],    // This stores -2, -8, etc.
                'label' => $cat['display_name'], // "Statues", "Books", etc.
                'icon'  => $iconClass
            ];
        }
    }
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<div class="w-full flex items-center justify-center p-0 md:p-6 bg-gray-100 min-h-screen">

    <div class="w-full h-screen md:h-auto md:min-h-[600px] md:max-w-5xl bg-white md:rounded-2xl shadow-2xl overflow-hidden flex flex-col relative border border-gray-200">
        
        <div class="bg-[#d9822b] px-4 py-4 flex items-center justify-between text-white shadow-md z-20 flex-shrink-0">
            <button type="button" id="back-btn" class="p-2 hover:bg-white/20 rounded-full transition">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </button>
            <h1 class="font-semibold text-lg tracking-wide">Image Upload - Step: 1/4</h1>
            <div class="w-6"></div> 
        </div>

        <form action="<?php echo $actionUrl; ?>" id="add_role" method="POST" enctype="multipart/form-data" class="flex flex-col flex-1 overflow-hidden">
            
            <div class="flex-1 overflow-y-auto p-5 md:p-8 bg-gray-50/50">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 h-full">
                    
                    <div class="flex flex-col gap-4">
                        <div class="bg-white border border-gray-200 rounded-xl p-4 md:p-6 shadow-sm h-full">
                            <div class="flex items-center gap-2 mb-4 border-b border-gray-100 pb-2">
                                <span class="bg-[#d9822b] w-1 h-5 rounded-full"></span>
                                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Select Category</h3>
                            </div>
                            
                            <div class="grid grid-cols-3 gap-3">
                                <?php if(empty($display_categories)): ?>
                                    <p class="col-span-3 text-center text-gray-400 text-xs">No categories found.</p>
                                <?php else: ?>
                                    <?php foreach ($display_categories as $item) { ?>
                                        <label class="relative cursor-pointer group">
                                            <input type="radio" name="category" value="<?= $item['value'] ?>" class="peer sr-only" <?php if ($saved_category_code == $item['value']) echo 'checked'; ?>>
                                            
                                            <div class="aspect-square flex flex-col items-center justify-center p-2 rounded-xl transition-all duration-200
                                                        bg-gray-900 text-white border-2 border-transparent shadow-sm
                                                        peer-checked:bg-white peer-checked:text-black peer-checked:border-black peer-checked:shadow-md hover:shadow-lg">
                                                
                                                <div class="absolute top-2 left-2 w-4 h-4 rounded-full border-2 border-current flex items-center justify-center">
                                                    <div class="w-2 h-2 rounded-full bg-current opacity-0 peer-checked:opacity-100 transition-opacity"></div>
                                                </div>
                                                
                                                <i class="<?= $item['icon'] ?> text-3xl mb-2 transition-transform duration-200 peer-checked:scale-110"></i>
                                                
                                                <span class="text-[10px] md:text-[11px] font-bold uppercase tracking-wide text-center leading-tight"><?= $item['label'] ?></span>
                                            </div>
                                        </label>
                                    <?php } ?>
                                <?php endif; ?>
                            </div>
                            <div id="category-error" class="text-red-500 text-xs mt-3 text-center font-bold"></div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-4 h-full">
                        <div class="relative border-2 border-dashed border-gray-300 rounded-xl p-6 text-center bg-white group hover:border-[#d9822b] hover:bg-orange-50/10 transition-all duration-300">
                            <span class="absolute -top-3 left-1/2 transform -translate-x-1/2 bg-white px-3 font-bold text-gray-700 text-sm flex items-center gap-2 border border-gray-100 rounded-full shadow-sm">
                                <svg class="w-4 h-4 text-[#d9822b]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                Upload Product Photo
                            </span>
                            
                            <p class="text-xs text-gray-500 mb-4 mt-3">Drag file here or use buttons below</p>

                            <div class="flex gap-3 justify-center">
                                <label class="cursor-pointer bg-gray-50 hover:bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-xs font-bold flex items-center gap-2 transition border border-gray-200 shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    Choose File
                                    <input type="file" id="photo" name="photo" accept="image/*" class="hidden">
                                </label>

                                <label class="cursor-pointer bg-[#d9822b] hover:bg-[#b56b21] text-white px-4 py-2 rounded-lg text-xs font-bold flex items-center gap-2 transition shadow-md shadow-orange-100">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path></svg>
                                    Take Photo
                                    <input type="file" name="photo_capture" accept="image/*" capture="camera" class="hidden" onchange="document.getElementById('photo').files = this.files; document.getElementById('photo').dispatchEvent(new Event('change'));">
                                </label>
                            </div>
                            <div id="photo-error" class="text-red-500 text-xs mt-2 font-medium min-h-[20px]"></div>
                        </div>

                        <div class="flex-grow flex items-center justify-center bg-white rounded-xl border border-gray-200 min-h-[250px] overflow-hidden relative shadow-sm">
                            <?php $displayStyle = ($isEdit && !empty($photo)) ? '' : 'hidden'; ?>
                            <?php $src = ($isEdit && !empty($photo)) ? base_url($photo) : '#'; ?>
                            
                            <img id="preview" src="<?php echo $src; ?>" class="<?php echo $displayStyle; ?> w-full h-full object-contain absolute inset-0 transition-opacity duration-300 p-2" onclick="openImagePopup('<?= $src ?>')">
                            
                            <div id="placeholder-text" class="<?php echo ($displayStyle == '') ? 'hidden' : ''; ?> flex flex-col items-center justify-center text-gray-300">
                                <svg class="w-16 h-16 mb-2 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <span class="text-xs font-medium opacity-60">Preview will appear here</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-5 bg-white border-t border-gray-100 mt-auto z-20">
                <button type="submit" class="w-full bg-[#d9822b] hover:bg-[#b56b21] text-white font-bold text-lg py-3 rounded-xl shadow-lg transform transition active:scale-[0.99] flex justify-center items-center gap-2">
                    <?php echo $isEdit ? "Update & Next" : "Save & Next"; ?><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </button>
            </div>
            
        </form>
    </div>
</div>
<div id="imagePopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center z-50" onclick="closeImagePopup(event)">
    <div class="bg-white p-4 rounded-md max-w-3xl max-h-3xl relative flex flex-col items-center" onclick="event.stopPropagation();">
        <button onclick="closeImagePopup()" class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-full text-sm">✕</button>
        <img id="popupImage" class="max-w-full max-h-[80vh] rounded" src="" alt="Image Preview">
    </div>
</div>
<script>
// Logic remains identical to your previous code
const photoInput = document.getElementById('photo');
const previewImg = document.getElementById('preview');
const placeholder = document.getElementById('placeholder-text');
const photoError = document.getElementById("photo-error");

photoInput.addEventListener('change', function(event) {
    const file = event.target.files[0];
    photoError.innerHTML = "";
    if (file) {
        if (file.size > 51200) {
            photoError.innerHTML = "Error: Image size must be less than 50KB.";
            photoInput.value = ""; 
            previewImg.src = "#";
            previewImg.classList.add('hidden');
            placeholder.classList.remove('hidden');
            return;
        }
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewImg.classList.remove('hidden');
            placeholder.classList.add('hidden');
        }
        reader.readAsDataURL(file);
    }
});

document.getElementById("add_role").addEventListener("submit", function (e) {
    let isValid = true;
    const catError = document.getElementById("category-error");
    catError.innerHTML = "";
    photoError.innerHTML = "";

    const categoryChecked = document.querySelector('input[name="category"]:checked');
    if (!categoryChecked) {
        catError.innerHTML = "Please select a category.";
        isValid = false;
    }

    const isEdit = "<?php echo $isEdit ? '1' : '0'; ?>";
    const hasPhoto = photoInput.files.length > 0;
    
    if (isEdit === "0" && !hasPhoto) {
        photoError.innerHTML = "Please upload a photo to proceed.";
        isValid = false;
    }

    if (!isValid) e.preventDefault();
});

document.querySelectorAll('input[name="category"]').forEach(radio => {
    radio.addEventListener("change", function () {
        document.getElementById("category-error").innerHTML = "";
    });
});

document.getElementById("back-btn").addEventListener("click", function () {
    window.location.href = window.location.origin + "/index.php?page=inbounding&action=list";
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