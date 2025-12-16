<?php
require_once 'settings/database/database.php';
require_once 'models/vendor/vendor.php';
require_once 'models/user/user.php';
$conn = Database::getConnection();
$vendorsModel = new Vendor($conn);
$usersModel = new User($conn);

foreach ($inbounding_data as $key => $value) {
    $vendor = $vendorsModel->getVendorById($value['vendor_code']);
    $inbounding_data[$key]['vendor_name'] = $vendor ? $vendor['vendor_name'] : '';
    $userDetails = $usersModel->getUserById($value['received_by_user_id']);
    $inbounding_data[$key]['received_name'] = $userDetails ? $userDetails['name'] : '';
}
unset($usersModel);
unset($vendorsModel);
?>

<div class="max-w-7xl mx-auto space-y-6">

    <div class="md:hidden bg-white rounded-xl overflow-hidden shadow-sm mb-4">
        <div class="bg-[#d9822b] px-4 py-3 flex items-center relative">
            <a href="javascript:history.back()" class="text-white absolute left-4 p-1 hover:bg-white/10 rounded-full transition">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </a>
            <h1 class="text-white font-bold text-lg w-full text-center">Inbound Today</h1>
        </div>
        <div class="bg-white p-3 flex justify-end border-b border-gray-100">
            <a href="<?php echo base_url('?page=inbounding&action=form1'); ?>" 
               class="border border-gray-300 text-gray-700 px-3 py-1.5 rounded-md text-sm font-bold flex items-center gap-1 hover:bg-gray-50 transition">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Inbound
            </a>
        </div>
    </div>

    <div class="hidden md:flex flex-wrap items-center justify-between gap-4">
        <div class="bg-white rounded-xl shadow-md p-4 flex flex-wrap items-center justify-between gap-4 flex-grow mt-[10px]">
            <form method="get" id="filterForm" class="w-full flex justify-between items-center">
                <input type="hidden" name="page" value="inbounding"> <input type="hidden" name="action" value="list">
                
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span class="text-gray-600 font-medium">Filters:</span>
                    </div>
                    <div class="relative flex items-left gap-2">
                        <input type="text" name="search_text" placeholder="Search Item Code, Title..." 
                               class="custom-input border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition pl-3" 
                               style="width: 300px; height: 37px; border-radius: 5px;" 
                               value="<?php echo $data['search'] ?? '' ?>">
                    </div>
                    
                    <div class="relative">
                        <input type="submit" value="Search" 
                               class="bg-gray-800 hover:bg-gray-900 text-white font-bold rounded-lg cursor-pointer flex items-center justify-center gap-2"
                               style="width: 100px; height: 37px; border-radius: 5px; font-size: 13px;">
                    </div>

                    <div class="relative">
                        <button type="button" onclick="clearAllFilters()"
                                class="bg-red-500 hover:bg-red-600 text-white font-bold rounded-lg cursor-pointer flex items-center justify-center gap-2"
                                style="width: 100px; height: 37px; border-radius: 5px; font-size: 13px;">
                            Clear
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="flex gap-2 mt-[10px]">
            <button onclick="exportSelectedData()" 
                    class="bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg flex items-center justify-center gap-2"
                    style="width: 120px; height: 40px; font-size: 13px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Export (<span id="count-display">0</span>)
            </button>

            <a href="<?php echo base_url('?page=inbounding&action=form1'); ?>"
               class="bg-gray-800 hover:bg-gray-900 text-white font-bold rounded-lg flex items-center justify-center gap-2"
               style="width: 120px; height: 40px; font-size: 13px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Add
            </a>
        </div>
    </div>

    <div class="overflow-x-auto mt-4">
        <?php if (!empty($inbounding_data)): ?>
            <?php foreach ($inbounding_data as $index => $tc): ?>
                <?php
                // Calculation Logic...
                $filled = 0;
                $total = count($tc);
                foreach ($tc as $key => $t) {
                    if (isset($tc[$key]) && isFilled($tc[$key])) { $filled++; }
                }
                $percentage = ($filled / $total) * 100;
                ?>

                <div class="max-w-7xl mx-auto bg-white rounded-lg shadow-md border border-gray-200 hover:shadow-lg transition-shadow duration-200" style="margin: 0px 10px 10px 10px;">
                    
                    <div class="flex md:hidden p-3 gap-3 items-center relative">
                        <div class="w-20 h-20 rounded-lg flex-shrink-0 bg-gray-50 border border-gray-100 overflow-hidden">
                            <img src="<?php echo base_url($tc['product_photo']); ?>" alt="Product" class="w-full h-full object-cover" onclick="openImagePopup('<?= $tc['product_photo'] ?>')">
                        </div>
                        <div class="flex-1 min-w-0 flex flex-col justify-center space-y-1">
                            <p class="text-sm font-bold text-gray-800 truncate">Category: <span class="font-normal"><?php echo ($tc['category_code']); ?></span></p>
                            <p class="text-xs text-gray-500">Entry: <span class="text-gray-700"><?php echo date('d M Y h:i A', strtotime($tc['gate_entry_date_time'])); ?></span></p>
                            <p class="text-xs font-bold text-gray-800">SKU Code: <span class="text-gray-600 font-medium"><?php echo ($tc['sku']); ?></span></p>
                        </div>
                        <div class="flex-shrink-0">
                            <a href="<?php echo base_url('?page=inbounding&action=form1&id=' . $tc['id']); ?>" class="bg-[#d9822b] text-white text-xs px-4 py-2 rounded-lg">Update</a>
                        </div>
                    </div>

                    <div class="hidden md:flex items-start p-4 gap-4">
                        
                        <div class="flex items-center justify-center h-full pt-10 pl-2">
                            <input type="checkbox" 
                                   class="row-checkbox w-5 h-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 cursor-pointer" 
                                   data-id="<?php echo $tc['id']; ?>"
                                   onchange="toggleSelection(<?php echo $tc['id']; ?>)">
                        </div>

                        <div class="grid grid-cols-[max-content,1fr] gap-x-4 w-full">
                            <div class="flex flex-col gap-4">
                                <div class="flex items-start gap-4">
                                    <div class="w-24 h-24 rounded-md flex-shrink-0 flex items-center justify-center bg-gray-50 overflow-hidden">
                                        <img src="<?php echo base_url($tc['product_photo']); ?>" class="max-w-full max-h-full object-contain cursor-pointer" onclick="openImagePopup('<?= $tc['product_photo'] ?>')">
                                    </div>
                                    <div class="w-24 h-24 rounded-md flex-shrink-0 flex items-center justify-center bg-gray-50 overflow-hidden">
                                        <img src="<?php echo base_url($tc['invoice_image']); ?>" class="max-w-full max-h-full object-contain cursor-pointer" onclick="openImagePopup('<?= $tc['invoice_image'] ?>')">
                                    </div>
                                    
                                    <div class="pt-1 w-full max-w-xs">
                                        <p class="item-code">SKU Code: <?php echo ($tc['sku']); ?></p> 
                                        <p class="item-code font-bold text-blue-600">Item Code: <?php echo ($tc['Item_code']); ?></p>
                                        <p class="quantity">Category : <?php echo ($tc['category_code']); ?></p>
                                        <p class="quantity">Received_by : <?php print_r($tc['received_name']); ?></p>
                                    </div>
                                    
                                    <div class="pt-1 w-full max-w-xs">
                                        <p class="item-code">Title: <?php echo substr($tc['product_title'], 0, 30) . '...'; ?></p>
                                        <p class="quantity">Keywords: <?php echo substr($tc['key_words'], 0, 30) . '...'; ?></p>
                                        <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700 mt-2">
                                            <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                        <span class="text-xs text-gray-500"><?php echo round($percentage) . "%"; ?> Complete</span>
                                    </div>

                                    <div class="pt-1 w-full max-w-xs flex flex-col">
                                        <div class="mb-3 space-y-1">
                                            <p class="item-code font-medium text-gray-700">Vendor: <?php print_r($tc['vendor_name']); ?></p>
                                        </div>
                                        <div class="mt-1 flex gap-2"> 
                                            <a href="<?php echo base_url('?page=inbounding&action=i_photos&id=' . $tc['id']); ?>" class="bg-[#d9822b] hover:bg-[#bf7326] text-white rounded px-3 py-2 text-xs font-bold">Photos</a>
                                            <a href="<?php echo base_url('?page=inbounding&action=i_raw_photos&id=' . $tc['id']); ?>" class="bg-[#d9822b] hover:bg-[#bf7326] text-white rounded px-3 py-2 text-xs font-bold">Raw Photos</a>
                                            <a href="<?php echo base_url('?page=inbounding&action=desktopform&id=' . $tc['id']); ?>" class="bg-[#d9822b] hover:bg-[#bf7326] text-white rounded px-3 py-2 text-xs font-bold">Process</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="p-4 text-center text-gray-500">No records found.</div>
        <?php endif; ?>
    </div>

    <?php         
        $page_no = $data["page_no"] ?? 1;
        $limit = $data["limit"] ?? 10;
        $total_records = $data["totalRecords"] ?? 0;
        $total_pages = $limit > 0 ? ceil($total_records / $limit) : 1;
        
        // Ensure search text persists when changing pages/limits
        $search_query = isset($data['search']) ? urlencode($data['search']) : '';
    ?>
    <?php if ($total_records > 0): ?>
        <div class="bg-white rounded-xl shadow-md p-4 mb-10 flex flex-col md:flex-row items-center justify-between gap-4">
            
            <div class="text-sm text-gray-500 font-medium">
                Showing <?php echo (($page_no - 1) * $limit) + 1; ?> to <?php echo min($page_no * $limit, $total_records); ?> of <?php echo $total_records; ?> results
            </div>

            <div class="flex items-center gap-6 text-sm text-gray-600">
                
                <div class="flex items-center gap-2">
                    <span class="font-bold text-gray-700">Rows:</span>
                    <select onchange="window.location.href='?page=inbounding&action=list&page_no=1&search_text=<?= $search_query ?>&limit=' + this.value"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-1.5 cursor-pointer">
                        <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                        <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="flex items-center gap-3">
                        <span>Page</span>
                        
                        <button class="p-2 rounded-full hover:bg-gray-100 transition <?= $page_no <= 1 ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= $page_no <= 1 ? 'disabled' : '' ?>>
                            <a class="flex items-center justify-center w-full h-full" 
                               <?php if($page_no > 1) { ?> href="?page=inbounding&action=list&page_no=<?= $page_no-1 ?>&limit=<?= $limit ?>&search_text=<?= $search_query ?>" <?php } else { ?> href="javascript:void(0)" <?php } ?>>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                            </a>
                        </button>
                        
                        <span class="bg-black text-white rounded-full h-8 w-8 flex items-center justify-center text-sm font-bold shadow-lg">
                            <?= $page_no ?>
                        </span>
                        
                        <button class="p-2 rounded-full hover:bg-gray-100 transition <?= $page_no >= $total_pages ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= $page_no >= $total_pages ? 'disabled' : '' ?>>
                            <a class="flex items-center justify-center w-full h-full" 
                               <?php if($page_no < $total_pages) { ?> href="?page=inbounding&action=list&page_no=<?= $page_no+1 ?>&limit=<?= $limit ?>&search_text=<?= $search_query ?>" <?php } else { ?> href="javascript:void(0)" <?php } ?>>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </button>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    <?php endif; ?>

</div>

</div>
<div id="imagePopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center z-50" onclick="closeImagePopup(event)">
    <div class="bg-white p-4 rounded-md max-w-3xl max-h-3xl relative flex flex-col items-center" onclick="event.stopPropagation();">
        <button onclick="closeImagePopup()" class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-full text-sm">✕</button>
        <img id="popupImage" class="max-w-full max-h-[80vh] rounded" src="" alt="Image Preview">
    </div>
</div>

<?php
function isFilled($value) {
    if ($value === null) return false;
    if ($value === "") return false;
    if ($value === 0 || $value === "0") return false;
    if ($value === "0000-00-00" || $value === "0000-00-00 00:00:00") return false;
    return true;
}
?>

<script>
    // --- PERSISTENCE & SELECTION LOGIC ---
    document.addEventListener('DOMContentLoaded', function() {
        restoreSelection(); 
        updateCountDisplay(); 
    });

    function getSelectedIds() {
        const stored = localStorage.getItem('selected_inbound_ids');
        return stored ? JSON.parse(stored) : [];
    }

    function toggleSelection(id) {
        id = parseInt(id);
        let ids = getSelectedIds();
        const index = ids.indexOf(id);
        
        if (index > -1) {
            ids.splice(index, 1); 
        } else {
            ids.push(id); 
        }
        
        localStorage.setItem('selected_inbound_ids', JSON.stringify(ids));
        updateCountDisplay();
    }

    function restoreSelection() {
        const ids = getSelectedIds();
        const checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(box => {
            const boxId = parseInt(box.getAttribute('data-id'));
            if (ids.includes(boxId)) {
                box.checked = true;
            }
        });
    }

    function updateCountDisplay() {
        const ids = getSelectedIds();
        const countSpan = document.getElementById('count-display');
        if(countSpan) countSpan.innerText = ids.length;
    }

    // --- NEW: CLEAR ALL FUNCTION ---
    function clearAllFilters() {
        // 1. Clear LocalStorage Selection
        localStorage.removeItem('selected_inbound_ids');
        
        // 2. Reset Search Input Visuals (Optional, strictly not needed as we reload)
        const form = document.getElementById('filterForm');
        if(form) form.reset();
        
        // 3. Reload Page with base URL (removes ?search_text=...)
        // Ensure this URL matches your base list URL
        window.location.href = '?page=inbounding&action=list';
    }

    // --- NEW: EXPORT FUNCTION ---
    function exportSelectedData() {
        const ids = getSelectedIds();
        if (ids.length === 0) {
            alert("Please select at least one item to export.");
            return;
        }

        // Construct URL
        // We use window.location.origin + pathname to ensure we stay in the right directory
        const baseUrl = window.location.href.split('?')[0]; 
        const exportUrl = `${baseUrl}?page=inbounding&action=exportSelected&ids=${ids.join(',')}`;
        
        console.log("Exporting to:", exportUrl); // Debugging
        window.location.href = exportUrl;
    }
</script>

<script>
    function openImagePopup(imageUrl) {
        if(imageUrl) {
            document.getElementById('popupImage').src = imageUrl;
            document.getElementById('imagePopup').classList.remove('hidden');
        }
    }
    function closeImagePopup(event) {
        document.getElementById('imagePopup').classList.add('hidden');
        document.getElementById('popupImage').src = '';
    } 
</script>