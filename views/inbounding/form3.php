<?php
// PHP Logic (Unchanged)
    is_login();
    require_once 'settings/database/database.php';
    $conn = Database::getConnection();
    require_once 'models/user/user.php';
    $usersModel = new User($conn);
    $currentuserDetails = $usersModel->getUserById($_SESSION['user']['id']);
    unset($usersModel);

$record_id = $_GET['id'] ?? '';
$form2 = $data['form2'] ?? [];
$raw_categories = $data['category'] ?? []; // FETCH CATEGORY LIST

// --- NEW LOGIC START: Resolve Category Name ---
$cat_id = $form2['group_name'] ?? ''; 
$category_display_name = 'Unknown'; // Default value

if (!empty($raw_categories) && !empty($cat_id)) {
    foreach ($raw_categories as $cat_item) {
        if (isset($cat_item['category']) && $cat_item['category'] == $cat_id) {
            $category_display_name = $cat_item['display_name'];
            break;
        }
    }
}
// --- NEW LOGIC END ---

$photo    = $form2['product_photo'] ?? '';
$temp_code      = $form2['temp_code'] ?? '';
$vendor_name    = $form2['vendor_name'] ?? '';

$gate_entry_date_time = $form2['gate_entry_date_time'] ?? '';
$material_code = $form2['material_code'] ?? '';
$height = $form2['height'] ?? '';
$width = $form2['width'] ?? '';
$depth = $form2['depth'] ?? '';
$weight = $form2['weight'] ?? '';
$color = $form2['color'] ?? '';
$weight_unit = $form2['weight_unit'] ?? '';
$dimention_unit = $form2['dimention_unit'] ?? '';
$quantity_received = $form2['quantity_received'] ?? '';
$size = $form2['size'] ?? '';
$cp = $form2['cp'] ?? '';
$Item_code = $form2['Item_code'] ?? '';

$isEdit = (!empty($gate_entry_date_time) || !empty($material_code) || !empty($height) || !empty($width) || !empty($depth) || !empty($weight) || !empty($color) || !empty($quantity_received) || !empty($Item_code) || !empty($size) || !empty($cp));

$formAction = $isEdit
    ? base_url('?page=inbounding&action=updateform3&id=' . $record_id)
    : base_url('?page=inbounding&action=saveform3');
?>

<div class="w-full flex items-center justify-center p-0 md:p-6 bg-gray-100 min-h-screen">

    <div class="w-full h-screen md:h-auto md:min-h-[700px] md:max-w-5xl bg-white md:rounded-2xl shadow-2xl overflow-hidden flex flex-col relative border border-gray-200">
        
        <div class="bg-[#d9822b] px-4 py-4 flex items-center justify-between text-white shadow-md z-20 flex-shrink-0">
            <button type="button" id="back-btn" class="p-2 hover:bg-white/20 rounded-full transition">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </button>
            <h1 class="font-semibold text-lg tracking-wide">Item Details - Step: 3/4</h1>
            <button type="button" id="cancel-btn" class="p-2 hover:bg-white/20 rounded-full transition">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button> 
        </div>

        <form action="<?php echo $formAction; ?>" method="POST" enctype="multipart/form-data" class="flex flex-col flex-1 overflow-hidden">
            <input type="hidden" name="record_id" value="<?php echo $record_id; ?>">

            <div class="flex-1 overflow-y-auto p-5 md:p-8 bg-gray-50/50">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 h-full">
                    
                    <div class="flex flex-col gap-6">
                        
                        <div class="bg-white border border-gray-200 rounded-xl p-4 flex gap-4 shadow-sm">
                            <div class="w-20 h-20 bg-gray-50 border border-gray-100 rounded-lg flex-shrink-0 overflow-hidden flex items-center justify-center">
                                <?php if(!empty($photo)): ?>
                                    <img src="<?php echo base_url($photo); ?>" class="w-full h-full object-cover" onclick="openImagePopup('<?= $photo ?>')">
                                <?php else: ?>
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <?php endif; ?>
                            </div>
                            <div class="flex flex-col justify-center gap-1">
                                <div><span class="text-xs text-gray-400 font-bold uppercase">Temp Code:</span> <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($temp_code); ?></span></div>
                                
                                <div><span class="text-xs text-gray-400 font-bold uppercase">Category:</span> <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($category_display_name); ?></span></div>
                                
                                <div><span class="text-xs text-gray-400 font-bold uppercase">Vendor:</span> <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($vendor_name); ?></span></div>
                            </div>
                        </div>

                        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex flex-col gap-5">
                            
                            <div>
                                <label class="block text-gray-700 font-bold text-sm mb-2">Gate Entry Date & Time</label>
                                
                                <?php 
                                    // Cleaner Date Logic
                                    $inputValue = (!empty($gate_entry_date_time) && $gate_entry_date_time != "0000-00-00 00:00:00") 
                                        ? date('Y-m-d\TH:i', strtotime($gate_entry_date_time)) 
                                        : date('Y-m-d\TH:i');
                                ?>
                                <input 
                                    type="datetime-local" 
                                    name="gate_entry_date_time" 
                                    value="<?php echo $inputValue; ?>" 
                                    class="w-full bg-white border border-gray-300 text-gray-700 py-3 px-4 rounded-lg focus:ring-2 focus:ring-[#d9822b] outline-none shadow-sm">
                            </div>

                            <div>
                                <label class="block text-gray-700 font-bold text-sm mb-2">Received By</label>
                                <input type="hidden" name="received_by_user_id" value="<?php echo $_SESSION['user']['id']; ?>">
                                <input type="text" name="received_by_name" value="<?php echo htmlspecialchars($currentuserDetails['name']); ?>" readonly class="w-full bg-gray-100 border border-gray-300 text-gray-500 py-3 px-4 rounded-lg cursor-not-allowed font-medium">
                            </div>

                             <div>
                                <label class="block text-gray-700 font-bold text-sm mb-2">Material</label>
                                <div class="relative">
                                    <select name="material_code" class="w-full appearance-none bg-white border border-gray-300 text-gray-700 py-3 px-4 pr-8 rounded-lg focus:ring-2 focus:ring-[#d9822b] outline-none font-medium cursor-pointer shadow-sm">
                                        <option value="">Select Material</option>
                                        <?php foreach ($data['material'] as $value2) { 
                                            $isSelected = ($selected_material == $value2['id']) ? 'selected' : '';
                                        ?> 
                                            <option <?php echo $isSelected; ?> value="<?php echo $value2['id']; ?>"> <?php echo $value2['material_name']; ?></option>
                                        <?php } ?>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="flex flex-col h-full bg-white p-5 rounded-xl border border-gray-200 shadow-sm gap-5">
                        
                        <h3 class="font-bold text-gray-800 text-lg border-b border-gray-100 pb-2">Item Specifications</h3>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Height (inch)</label>
                                <input type="number" step="any" min="0" name="height" value="<?php echo $height; ?>" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-[#d9822b] outline-none text-center font-semibold shadow-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Width (inch)</label>
                                <input type="number" step="any" min="0" name="width" value="<?php echo $width; ?>" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-[#d9822b] outline-none text-center font-semibold shadow-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Depth (inch)</label>
                                <input type="number" step="any" min="0" name="depth" value="<?php echo $depth; ?>" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-[#d9822b] outline-none text-center font-semibold shadow-sm">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Weight (kg)</label>
                                <input type="number" step="any" min="0" name="weight" value="<?php echo $weight; ?>" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-[#d9822b] outline-none font-medium shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Color</label>
                                <input type="text" name="color" value="<?php echo !empty($color) ? $color : 'Black'; ?>" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-[#d9822b] outline-none font-medium shadow-sm">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Quantity</label>
                                <input type="number" min="0" name="quantity_received" value="<?php echo $quantity_received; ?>" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-[#d9822b] outline-none font-medium shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Size</label>
                                <input type="text" name="size" value="<?php echo $size; ?>" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-[#d9822b] outline-none font-medium shadow-sm">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">CP</label>
                                <input type="number" step="any" min="0" name="cp" value="<?php echo $cp; ?>" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-[#d9822b] outline-none font-medium shadow-sm">
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="p-5 bg-white border-t border-gray-100 mt-auto z-20">
                <button type="submit" class="w-full bg-[#d9822b] hover:bg-[#bf7326] text-white font-bold text-lg py-3.5 rounded-xl shadow-lg transform transition active:scale-[0.99] flex justify-center items-center gap-2">
                    <?php echo $isEdit ? "Update & Next" : "Save & Next"; ?>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
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
    var id = <?php echo json_encode($record_id); ?>;
    document.getElementById("back-btn").addEventListener("click", function () {
        window.location.href = window.location.origin + "/index.php?page=inbounding&action=form2&id=" + id;
    });
    document.getElementById("cancel-btn").addEventListener("click", function () {
        window.location.href = window.location.origin + "/index.php?page=inbounding&action=lsit";
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