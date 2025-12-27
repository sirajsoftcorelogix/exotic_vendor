<?php
// 1. PHP Logic & Auth
is_login();
require_once 'settings/database/database.php';
$conn = Database::getConnection();
require_once 'models/user/user.php';
$usersModel = new User($conn);
$currentuserDetails = $usersModel->getUserById($_SESSION['user']['id']);
unset($usersModel);
$record_id = $_GET['id'] ?? '';
$form2 = $data['form2'] ?? []; 
$saved_category_code = $form2['group_name'] ?? ''; 

$raw_categories = $data['category'] ?? []; 
$icon_map = [
    'sculptures'    => 'fa-solid fa-monument',
    'book'          => 'fa-solid fa-book',
    'jewelry'       => 'fa-solid fa-gem',
    'textiles'      => 'fa-solid fa-shirt',
    'paintings'     => 'fa-solid fa-palette',
    'homeandliving' => 'fa-solid fa-couch',
    'default'       => 'fa-solid fa-box-open'
];

// PROCESS CATEGORIES
$display_categories = [];
if (!empty($raw_categories)) {
    foreach ($raw_categories as $cat) {
        if (isset($cat['parent_id']) && $cat['parent_id'] == 0) {
            $iconClass = $icon_map[$cat['name']] ?? $icon_map['default'];
            $display_categories[] = [
                'value' => $cat['category'],    
                'label' => $cat['display_name'], 
                'icon'  => $iconClass
            ];
        }
    }
}

// Prepare Data
$mainVar = [
    'color'    => $form2['color'] ?? '',
    'size'     => $form2['size'] ?? '',
    'quantity' => $form2['quantity_received'] ?? '',
    'cp'       => $form2['cp'] ?? '',
    'photo'    => $form2['product_photo'] ?? '',
    'invoice'  => $form2['invoice_image'] ?? '',
    'height'   => $form2['height'] ?? '',
    'width'    => $form2['width'] ?? '',
    'depth'    => $form2['depth'] ?? '',
    'weight'   => $form2['weight'] ?? ''
];

global $inboundingModel;
$extraVars = $inboundingModel->getVariations($record_id);

$viewVariations = [];
$viewVariations[] = $mainVar; 

foreach ($extraVars as $ex) {
    $viewVariations[] = [
        'id'       => $ex['id'],
        'color'    => $ex['color'],
        'size'     => $ex['size'],
        'quantity' => $ex['quantity'],
        'cp'       => $ex['cp'],
        'photo'    => $ex['variation_image'] ?? '',
        'height'   => $ex['height'] ?? '',
        'width'    => $ex['width'] ?? '',
        'depth'    => $ex['depth'] ?? '',
        'weight'   => $ex['weight'] ?? ''
    ];
}

$temp_code      = $form2['temp_code'] ?? '';
$vendor_name    = $form2['vendor_name'] ?? '';
$gate_entry_date_time = $form2['gate_entry_date_time'] ?? '';
$material_code        = $form2['material_code'] ?? '';

$formAction = base_url('?page=inbounding&action=submitStep3');
?>

<div class="w-full min-h-screen bg-white p-2 md:p-6 font-sans">
    <div class="max-w-[1400px] mx-auto border border-gray-400 rounded-lg overflow-hidden">
        
        <div class="bg-[#ea8c1e] px-4 py-3 flex items-center justify-between text-white relative">
            <button type="button" id="back-btn" class="p-1 hover:bg-white/20 rounded-full transition"><i class="fa-solid fa-arrow-left text-xl"></i></button>
            <h1 class="font-bold text-lg md:text-xl tracking-wide mx-auto">Item Details - Step 3/4</h1>
            <button type="button" id="cancel-btn" class="p-1 hover:bg-white/20 rounded-full transition"><i class="fa-solid fa-xmark text-xl"></i></button> 
        </div>

        <form action="<?php echo $formAction; ?>" method="POST" enctype="multipart/form-data" id="mainForm" class="p-4 space-y-6">
            <input type="hidden" name="record_id" value="<?php echo $record_id; ?>">
            <input type="hidden" name="userid_log" value="<?php echo $_SESSION['user']['id'] ?? ''; ?>">

            <div id="variations-container" class="space-y-6">
                <?php foreach($viewVariations as $index => $var): ?>
                    <div class="variation-card border border-gray-300 rounded-lg p-3 bg-gray-50/50" data-index="<?php echo $index; ?>">
                        
                        <div class="flex justify-between items-center border-b border-gray-300 pb-2 mb-4">
                            <h3 class="font-bold text-black text-sm">
                                <?php echo ($index === 0) ? 'Base Variant' : 'Variant ' . ($index + 1); ?>
                            </h3>
                            <div class="flex gap-3">
                                <button type="button" class="clone-variation-btn text-blue-600 hover:text-blue-800 font-bold text-xs uppercase flex items-center gap-1">
                                    <i class="fa-regular fa-copy"></i> Clone
                                </button>
                                
                                <button type="button" class="remove-variation-btn <?php echo ($index === 0) ? 'hidden' : ''; ?> text-red-500 hover:text-red-700 font-bold text-xs uppercase">
                                    Remove ✕
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-col md:flex-row gap-6">
                            <div class="w-32 md:w-40 flex-shrink-0">
                                <label class="cursor-pointer group relative block w-full aspect-square bg-white border border-gray-400 rounded flex items-center justify-center hover:border-black overflow-hidden">
                                    <?php $hasPhoto = !empty($var['photo']); ?>
                                    <img src="<?php echo $hasPhoto ? base_url($var['photo']) : '#'; ?>" class="preview-img <?php echo $hasPhoto ? '' : 'hidden'; ?> w-full h-full object-cover absolute inset-0 z-10">
                                    
                                    <div class="placeholder-icon <?php echo $hasPhoto ? 'hidden' : ''; ?> flex flex-col items-center justify-center text-gray-500">
                                        <i class="fa-solid fa-camera text-2xl mb-1"></i>
                                    </div>
                                    <input type="file" name="variations[<?php echo $index; ?>][photo]" accept="image/*" class="hidden variation-file-input" capture="camera">
                                    <input type="hidden" name="variations[<?php echo $index; ?>][old_photo]" value="<?php echo $var['photo']; ?>">
                                    <input type="hidden" name="variations[<?php echo $index; ?>][id]" value="<?php echo $var['id'] ?? ''; ?>">
                                </label>
                                <div class="text-[10px] text-center font-bold mt-1 text-gray-500">Upload Photo</div>
                            </div>

                            <div class="flex-1 grid grid-cols-2 md:grid-cols-4 gap-x-4 gap-y-4 items-start">
                                <div>
                                    <label class="block text-xs font-bold text-black mb-1">Color:</label>
                                    <input type="text" name="variations[<?php echo $index; ?>][color]" value="<?php echo $var['color']; ?>" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-black mb-1">Quantity:</label>
                                    <input type="number" min="0" name="variations[<?php echo $index; ?>][quantity]" value="<?php echo $var['quantity']; ?>" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-black mb-1">Size:</label>
                                    <input type="text" name="variations[<?php echo $index; ?>][size]" value="<?php echo $var['size']; ?>" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-black mb-1">Cost Price:</label>
                                    <input type="number" step="any" min="0" name="variations[<?php echo $index; ?>][cp]" value="<?php echo $var['cp']; ?>" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-black mb-1">Height (cm):</label>
                                    <input type="number" step="any" min="0" name="variations[<?php echo $index; ?>][height]" value="<?php echo $var['height'] ?? ''; ?>" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-black mb-1">Depth (cm):</label>
                                    <input type="number" step="any" min="0" name="variations[<?php echo $index; ?>][depth]" value="<?php echo $var['depth'] ?? ''; ?>" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-black mb-1">Width (cm):</label>
                                    <input type="number" step="any" min="0" name="variations[<?php echo $index; ?>][width]" value="<?php echo $var['width'] ?? ''; ?>" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-black mb-1">Weight (kg):</label>
                                    <input type="number" step="any" min="0" name="variations[<?php echo $index; ?>][weight]" value="<?php echo $var['weight'] ?? ''; ?>" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none">
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                 <button type="button" id="add-variation-btn" class="bg-[#ea8c1e] hover:bg-orange-600 text-white font-bold py-2 px-6 rounded shadow text-xs uppercase">
                    Add Variant
                </button>
                <button type="submit" class="bg-[#ea8c1e] hover:bg-orange-600 text-white font-bold py-2 px-6 rounded shadow text-xs uppercase">
                    Save Item
                </button>
            </div>
        </form>
    </div>
</div>

<div id="imagePopup" class="fixed inset-0 bg-black bg-opacity-70 hidden flex justify-center items-center z-50" onclick="closeImagePopup(event)">
    <div class="bg-white p-2 rounded-md max-w-4xl max-h-[90vh] relative flex flex-col items-center" onclick="event.stopPropagation();">
        <button onclick="closeImagePopup()" class="absolute -top-3 -right-3 bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center shadow-lg font-bold">✕</button>
        <img id="popupImage" class="max-w-full max-h-[85vh] rounded" src="" alt="Image Preview">
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('variations-container');
        const addBtn = document.getElementById('add-variation-btn');
        let variationCount = <?php echo count($viewVariations); ?>;

        // 1. ADD NEW VARIATION (Function to generate HTML)
        function createVariationCardHTML(index, count) {
            return `
                <div class="variation-card border border-gray-300 rounded-lg p-3 bg-gray-50/50 animate-fade-in" data-index="${index}">
                    <div class="flex justify-between items-center border-b border-gray-300 pb-2 mb-4">
                        <h3 class="font-bold text-black text-sm">Variant ${count}</h3>
                        <div class="flex gap-3">
                            <button type="button" class="clone-variation-btn text-blue-600 hover:text-blue-800 font-bold text-xs uppercase flex items-center gap-1">
                                <i class="fa-regular fa-copy"></i> Clone
                            </button>
                            <button type="button" class="remove-variation-btn text-red-500 hover:text-red-700 font-bold text-xs uppercase">
                                Remove ✕
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-col md:flex-row gap-6">
                        <div class="w-32 md:w-40 flex-shrink-0">
                            <label class="cursor-pointer group relative block w-full aspect-square bg-white border border-gray-400 rounded flex items-center justify-center hover:border-black overflow-hidden">
                                <img src="#" class="preview-img hidden w-full h-full object-cover absolute inset-0 z-10">
                                <div class="placeholder-icon flex flex-col items-center justify-center text-gray-500">
                                    <i class="fa-solid fa-camera text-2xl mb-1"></i>
                                </div>
                                <input type="file" name="variations[${index}][photo]" accept="image/*" class="hidden variation-file-input" capture="camera">
                                <input type="hidden" name="variations[${index}][old_photo]" value="">
                            </label>
                            <div class="text-[10px] text-center font-bold mt-1 text-gray-500">Upload Photo</div>
                        </div>

                        <div class="flex-1 grid grid-cols-2 md:grid-cols-4 gap-x-4 gap-y-4 items-start">
                            <div><label class="block text-xs font-bold text-black mb-1">Color:</label><input type="text" name="variations[${index}][color]" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none"></div>
                            <div><label class="block text-xs font-bold text-black mb-1">Quantity:</label><input type="number" min="0" name="variations[${index}][quantity]" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none"></div>
                            <div><label class="block text-xs font-bold text-black mb-1">Size:</label><input type="text" name="variations[${index}][size]" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none"></div>
                            <div><label class="block text-xs font-bold text-black mb-1">Cost Price:</label><input type="number" step="any" min="0" name="variations[${index}][cp]" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none"></div>
                            <div><label class="block text-xs font-bold text-black mb-1">Height (cm):</label><input type="number" step="any" min="0" name="variations[${index}][height]" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none"></div>
                            <div><label class="block text-xs font-bold text-black mb-1">Depth (cm):</label><input type="number" step="any" min="0" name="variations[${index}][depth]" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none"></div>
                            <div><label class="block text-xs font-bold text-black mb-1">Width (cm):</label><input type="number" step="any" min="0" name="variations[${index}][width]" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none"></div>
                            <div><label class="block text-xs font-bold text-black mb-1">Weight (kg):</label><input type="number" step="any" min="0" name="variations[${index}][weight]" class="w-full border border-gray-400 rounded px-2 py-1.5 text-sm focus:border-black outline-none"></div>
                        </div>
                    </div>
                </div>
            `;
        }

        addBtn.addEventListener('click', function() {
            variationCount++;
            const html = createVariationCardHTML(variationCount - 1, variationCount);
            container.insertAdjacentHTML('beforeend', html);
        });

        // 2. EVENT DELEGATION (Remove & Clone)
        container.addEventListener('click', function(e) {
            
            // REMOVE Logic
            if (e.target.closest('.remove-variation-btn')) {
                const card = e.target.closest('.variation-card');
                if(card) card.remove();
            }

            // CLONE Logic
            if (e.target.closest('.clone-variation-btn')) {
                const sourceCard = e.target.closest('.variation-card');
                
                // Extract values from Source Card
                // using "contains" selector to find inputs ending with specific names
                const getData = (name) => {
                    const el = sourceCard.querySelector(`[name*="[${name}]"]`);
                    return el ? el.value : '';
                };

                const data = {
                    color: getData('color'),
                    quantity: getData('quantity'),
                    size: getData('size'),
                    cp: getData('cp'),
                    height: getData('height'),
                    width: getData('width'),
                    depth: getData('depth'),
                    weight: getData('weight'),
                    old_photo: getData('old_photo') // Clone the reference to the saved image
                };

                // Create New Card
                variationCount++;
                const html = createVariationCardHTML(variationCount - 1, variationCount);
                container.insertAdjacentHTML('beforeend', html);

                // Populate New Card with Source Data
                const newCard = container.lastElementChild;
                const setData = (name, value) => {
                    const el = newCard.querySelector(`[name*="[${name}]"]`);
                    if(el) el.value = value;
                };

                setData('color', data.color);
                setData('quantity', data.quantity);
                setData('size', data.size);
                setData('cp', data.cp);
                setData('height', data.height);
                setData('width', data.width);
                setData('depth', data.depth);
                setData('weight', data.weight);
                setData('old_photo', data.old_photo); // Set hidden old_photo input

                // Handle Image Preview for Cloned Item
                // If source had a saved image (old_photo), show it in preview
                if(data.old_photo) {
                    const preview = newCard.querySelector('.preview-img');
                    const placeholder = newCard.querySelector('.placeholder-icon');
                    // We assume base_url logic works via PHP rendering, but in JS we need the path
                    // If you have a specific path prefix (like 'uploads/'), add it here. 
                    // Since we can't easily get the PHP base_url() in JS without passing it, 
                    // we will just grab the src from the source image tag if it's visible.
                    
                    const sourceImg = sourceCard.querySelector('.preview-img');
                    if(sourceImg && !sourceImg.classList.contains('hidden')) {
                         preview.src = sourceImg.src;
                         preview.classList.remove('hidden');
                         placeholder.classList.add('hidden');
                    }
                }
            }
        });

        // 3. IMAGE PREVIEW
        container.addEventListener('change', function(e) {
            if (e.target.classList.contains('variation-file-input')) {
                const file = e.target.files[0];
                const label = e.target.closest('label');
                const preview = label.querySelector('.preview-img');
                const placeholder = label.querySelector('.placeholder-icon');

                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        preview.src = evt.target.result;
                        preview.classList.remove('hidden');
                        placeholder.classList.add('hidden');
                    }
                    reader.readAsDataURL(file);
                }
            }
        });
    });

    // Navigation Scripts (Unchanged)
    var id = <?php echo json_encode($record_id); ?>;
    document.getElementById("back-btn").addEventListener("click", function () {
        window.location.href = window.location.origin + "/index.php?page=inbounding&action=form2&id=" + id;
    });
    document.getElementById("cancel-btn").addEventListener("click", function () {
        window.location.href = window.location.origin + "/index.php?page=inbounding&action=list";
    });
    
    // Popup Scripts (Unchanged)
    function openImagePopup(imageUrl) {
        if(!imageUrl) return;
        document.getElementById('popupImage').src = imageUrl;
        document.getElementById('imagePopup').classList.remove('hidden');
    }
    function closeImagePopup(event) {
        document.getElementById('imagePopup').classList.add('hidden');
        document.getElementById('popupImage').src = '';
    } 
</script>

<div id="imagePopup" class="fixed inset-0 bg-black bg-opacity-70 hidden flex justify-center items-center z-50" onclick="closeImagePopup(event)">
    <div class="bg-white p-2 rounded-md max-w-4xl max-h-[90vh] relative flex flex-col items-center" onclick="event.stopPropagation();">
        <button onclick="closeImagePopup()" class="absolute -top-3 -right-3 bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center shadow-lg font-bold">✕</button>
        <img id="popupImage" class="max-w-full max-h-[85vh] rounded" src="" alt="Image Preview">
    </div>
</div>
<script>
    function openImagePopup(imageUrl) {
        if(!imageUrl) return;
        document.getElementById('popupImage').src = imageUrl;
        document.getElementById('imagePopup').classList.remove('hidden');
    }
    function closeImagePopup(event) {
        document.getElementById('imagePopup').classList.add('hidden');
        document.getElementById('popupImage').src = '';
    } 
</script>