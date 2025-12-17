<?php
// Ensure these files are included in your main environment if not already
require_once 'settings/database/database.php';
require_once 'models/vendor/vendor.php';
require_once 'models/user/user.php';

// Check if connection exists, if not create it
if (!isset($conn)) {
    $conn = Database::getConnection();
}
$vendorsModel = new Vendor($conn);
$usersModel = new User($conn);

// 1. Processing Logic (Merges Vendor and User names into the array)
// Ensure $inbounding_data exists to avoid errors
if (isset($inbounding_data) && is_array($inbounding_data)) {
    foreach ($inbounding_data as $key => $value) {
        $vendor = $vendorsModel->getVendorById($value['vendor_code']);
        $inbounding_data[$key]['vendor_name'] = $vendor ? $vendor['vendor_name'] : '';
        
        $userDetails = $usersModel->getUserById($value['received_by_user_id']);
        $inbounding_data[$key]['received_name'] = $userDetails ? $userDetails['name'] : '';
    }
} else {
    $inbounding_data = [];
}

// Clean up models
unset($usersModel);
unset($vendorsModel);

// 2. Helper function for calculation
function isFilled($value) {
    if ($value === null) return false;
    if ($value === "") return false;
    if ($value === 0 || $value === "0") return false;
    if ($value === "0000-00-00" || $value === "0000-00-00 00:00:00") return false;
    return true;
}
?>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    @import url('https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@700&display=swap');

    /* Accordion Transitions */
    .accordion-content {
        display: grid;
        grid-template-rows: 0fr;
        transition: grid-template-rows 0.3s ease-out, visibility 0.3s ease-out, opacity 0.3s ease-out;
        opacity: 0;
        visibility: hidden;
        overflow: hidden;
    }
    .accordion-content.open {
        grid-template-rows: 1fr;
        opacity: 1;
        visibility: visible;
    }
    .accordion-inner { min-height: 0; }

    /* --- GAUGE STYLES --- */
    .gauge-wrapper { position: relative; width: 128px; height: 80px; display: flex; justify-content: center; }
    .gauge-arc-wrapper { width: 128px; height: 64px; position: absolute; top: 0; left: 0; overflow: hidden; z-index: 10; }
    .gauge-arc {
        width: 128px; height: 128px; border-radius: 50%;
        border: 20px solid #e5e7eb;
        border-bottom-color: transparent; border-left-color: transparent; border-right-color: transparent; border-top-color: transparent;
        box-sizing: border-box;
    }

    /* Dynamic Colors */
    .gauge-color-100 { border-color: rgba(18, 136, 7, 1); border-bottom-color: transparent; }
    .gauge-color-75  { border-color: rgba(156, 204, 0, 1); border-bottom-color: transparent; }
    .gauge-color-50  { border-color: rgba(204, 197, 0, 1); border-bottom-color: transparent; }
    .gauge-color-25  { border-color: rgba(204, 0, 0, 1);   border-bottom-color: transparent; }
    .gauge-color-0   { border-color: #e5e7eb; border-bottom-color: transparent; }

    .gauge-needle-wrapper { position: absolute; top: 64px; left: 50%; width: 0; height: 0; z-index: 20; }
    .gauge-needle {
        width: 0; height: 0;
        border-left: 8px solid transparent; border-right: 8px solid transparent; border-bottom: 64px solid black;
        position: absolute; bottom: 0; left: -8px;
        transform-origin: bottom center;
        transition: transform 1s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .gauge-needle::after {
        content: ''; position: absolute; width: 16px; height: 16px; background: black; border-radius: 50%; top: 56px; left: -8px;
    }

    /* Dynamic Rotations */
    .rotate-100 { transform: rotate(90deg); }
    .rotate-75  { transform: rotate(45deg); }
    .rotate-50  { transform: rotate(0deg); }
    .rotate-25  { transform: rotate(-45deg); }
    .rotate-0   { transform: rotate(-90deg); }

    /* Typography & Buttons */
    .grid-label { font-family: 'Inter', sans-serif; font-weight: 500; font-size: 13px; line-height: 200%; color: rgba(221, 154, 25, 1); }
    .grid-value { font-family: 'Inter', sans-serif; font-weight: 500; font-size: 13px; line-height: 200%; color: rgba(0, 0, 0, 1); }

    .timeline-text { font-family: 'Inter', sans-serif; font-size: 13px; line-height: 159%; }
    @media (min-width: 1024px) { .timeline-text { text-align: center; } }
    @media (max-width: 1023px) { .timeline-text { text-align: left; } }

    .timeline-label-completed { font-weight: 400; color: rgba(0, 0, 0, 1); }
    .timeline-date-completed { font-weight: 600; color: rgba(0, 0, 0, 1); }
    .timeline-label-pending { font-weight: 400; color: rgba(186, 186, 186, 1); }
    .timeline-date-pending { font-weight: 600; color: rgba(186, 186, 186, 1); }

    .header-title { font-family: 'Instrument Sans', sans-serif; font-weight: 700; font-size: 18px; line-height: 21.6px; color: #000; }
    .btn-base { font-family: 'Inter', sans-serif; font-weight: 500; font-size: 14px; line-height: 100%; text-align: center; transition: opacity 0.2s; width: 100%; display: block; padding: 10px 24px; border-radius: 9999px; white-space: nowrap; cursor: pointer; }
    
    .btn-edit { background-color: rgba(208, 103, 6, 1); color: #fff; }
    .btn-upload { background-color: #000000; color: #ffffff; }
    .btn-published { background-color: rgba(18, 136, 7, 1); color: #fff; }
    .btn-base:hover { opacity: 0.9; }

    .accordion-content:not(.open) .accordion-inner { border-top-width: 0; padding-bottom: 0; }
    .custom-input::placeholder { font-size: 13px; }
</style>

<div class="w-full max-w-6xl space-y-4 mx-auto">

    <div class="mb-2 px-1">
        <div class="flex flex-col md:flex-row justify-between items-center mb-4">
             <h1 class="header-title mb-2 md:mb-0">Inbound Dashboard</h1>
             
             <form method="get" id="filterForm" class="w-full md:w-auto flex flex-col md:flex-row items-center gap-3">
                <input type="hidden" name="page" value="inbounding"> 
                <input type="hidden" name="action" value="list">
                
                <div class="relative w-full md:w-64">
                    <input type="text" name="search_text" placeholder="Search Item Code, Title..." 
                           class="custom-input w-full border border-gray-300 rounded-full px-4 py-2 text-sm focus:ring-2 focus:ring-orange-400 focus:outline-none transition" 
                           value="<?php echo $data['search'] ?? '' ?>">
                    <button type="submit" class="absolute right-2 top-1.5 text-gray-400 hover:text-gray-600">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </button>
                </div>

                <div class="flex gap-2">
                    <button type="button" onclick="clearAllFilters()" class="bg-red-50 text-red-600 hover:bg-red-100 px-4 py-2 rounded-full text-xs font-bold transition border border-red-200">
                        Clear
                    </button>
                    <button type="button" onclick="exportSelectedData()" class="bg-green-50 text-green-700 hover:bg-green-100 px-4 py-2 rounded-full text-xs font-bold transition border border-green-200 flex items-center gap-1">
                        Export (<span id="count-display">0</span>)
                    </button>
                    <a href="<?php echo base_url('?page=inbounding&action=form1'); ?>" class="bg-black text-white px-4 py-2 rounded-full text-xs font-bold hover:bg-gray-800 transition flex items-center gap-1">
                        <i data-lucide="plus" class="w-3 h-3"></i> Add
                    </a>
                </div>
            </form>
        </div>
        <hr class="border-gray-200">
    </div>

    <?php if (!empty($inbounding_data)): ?>
        <?php foreach ($inbounding_data as $index => $tc): ?>
            <?php
            // Calculation Logic
            $filled = 0;
            $total = count($tc);
            foreach ($tc as $key => $t) {
                if (isset($tc[$key]) && isFilled($tc[$key])) { $filled++; }
            }
            $percentage = ($total > 0) ? ($filled / $total) * 100 : 0;
            $percentage = round($percentage);

            // Determine Gauge Classes based on Percentage
            $gaugeColorClass = 'gauge-color-0';
            $gaugeRotateClass = 'rotate-0';
            $statusText = "Pending";

            if ($percentage >= 100) {
                $gaugeColorClass = 'gauge-color-100';
                $gaugeRotateClass = 'rotate-100';
                $statusText = "Completed";
            } elseif ($percentage >= 75) {
                $gaugeColorClass = 'gauge-color-75';
                $gaugeRotateClass = 'rotate-75';
                $statusText = "Processing";
            } elseif ($percentage >= 50) {
                $gaugeColorClass = 'gauge-color-50';
                $gaugeRotateClass = 'rotate-50';
                $statusText = "Processing";
            } elseif ($percentage > 0) {
                $gaugeColorClass = 'gauge-color-25';
                $gaugeRotateClass = 'rotate-25';
                $statusText = "Started";
            }
            ?>

            <div class="accordion-item bg-white rounded-[16px] border border-[rgba(229,229,229,1)] shadow-sm overflow-visible group transition-all duration-300 hover:shadow-md mb-4" data-open="false">
                
                <div class="px-4 pt-4 pb-3 sm:px-6 sm:pt-6 sm:pb-3 cursor-pointer toggle-btn relative z-20 bg-white rounded-[16px]">
                    <div class="absolute top-4 right-4 sm:top-6 sm:right-6 lg:static lg:hidden" onclick="event.stopPropagation()">
                         <input type="checkbox" class="row-checkbox w-5 h-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 cursor-pointer" 
                                data-id="<?php echo $tc['id']; ?>" onchange="toggleSelection(<?php echo $tc['id']; ?>)">
                    </div>

                    <div class="flex flex-col lg:flex-row gap-6 lg:items-center justify-between">

                        <div class="flex flex-col md:flex-row gap-6 flex-grow">
                            <div class="flex gap-3 shrink-0 justify-center md:justify-start" onclick="event.stopPropagation()">
                                <div class="w-20 h-28 bg-gray-50 rounded-lg shadow-sm border border-gray-200 flex items-center justify-center overflow-hidden cursor-zoom-in hover:opacity-80 transition"
                                     onclick="openImagePopup('<?= base_url($tc['product_photo']) ?>')">
                                    <?php if(!empty($tc['product_photo'])): ?>
                                        <img src="<?= base_url($tc['product_photo']) ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <i data-lucide="image" class="w-8 h-8 text-gray-300"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="relative w-20 h-28 bg-gray-50 rounded-lg shadow-sm border border-gray-200 flex items-center justify-center overflow-hidden cursor-zoom-in hover:opacity-80 transition"
                                     onclick="openImagePopup('<?= base_url($tc['invoice_image']) ?>')">
                                     <?php if(!empty($tc['invoice_image'])): ?>
                                        <img src="<?= base_url($tc['invoice_image']) ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <i data-lucide="file-text" class="w-8 h-8 text-gray-300"></i>
                                    <?php endif; ?>
                                    <div class="absolute top-1 right-1 bg-orange-400 text-white text-[8px] px-1 rounded shadow-sm">Doc</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-1 text-sm w-full lg:max-w-xl mx-auto md:mx-0">
                                <div class="flex items-baseline justify-between sm:justify-start"><span class="grid-label w-24 shrink-0">SKU / Code</span><span class="text-slate-400 px-2">:</span><span class="grid-value text-right sm:text-left flex-grow"><?php echo $tc['sku']; ?></span></div>
                                <div class="flex items-baseline justify-between sm:justify-start"><span class="grid-label w-24 shrink-0">Item Code</span><span class="text-slate-400 px-2">:</span><span class="grid-value text-right sm:text-left flex-grow font-bold text-blue-600"><?php echo $tc['Item_code']; ?></span></div>
                                <div class="flex items-baseline justify-between sm:justify-start"><span class="grid-label w-24 shrink-0">Vendor</span><span class="text-slate-400 px-2">:</span><span class="grid-value text-right sm:text-left flex-grow"><?php echo $tc['vendor_name']; ?></span></div>
                                
                                <div class="flex items-baseline justify-between sm:justify-start"><span class="grid-label w-24 shrink-0">Date</span><span class="text-slate-400 px-2">:</span><span class="grid-value text-right sm:text-left flex-grow"><?php echo !empty($tc['gate_entry_date_time']) ? date('d M Y', strtotime($tc['gate_entry_date_time'])) : '-'; ?></span></div>
                                
                                <div class="flex items-baseline justify-between sm:justify-start"><span class="grid-label w-24 shrink-0">Category</span><span class="text-slate-400 px-2">:</span><span class="grid-value text-right sm:text-left flex-grow"><?php echo $tc['category_code']; ?></span></div>
                                <div class="flex items-baseline justify-between sm:justify-start"><span class="grid-label w-24 shrink-0">Received by</span><span class="text-slate-400 px-2">:</span><span class="grid-value text-right sm:text-left flex-grow"><?php echo $tc['received_name']; ?></span></div>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row lg:flex-row items-center gap-6 justify-center lg:justify-end w-full lg:w-auto mt-4 lg:mt-0 border-t lg:border-t-0 pt-4 lg:pt-0 border-gray-100">
                            
                            <div class="hidden lg:block mr-2" onclick="event.stopPropagation()">
                                 <input type="checkbox" class="row-checkbox w-5 h-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 cursor-pointer" 
                                        data-id="<?php echo $tc['id']; ?>" onchange="toggleSelection(<?php echo $tc['id']; ?>)">
                            </div>

                            <div class="flex flex-col items-center">
                                <div class="gauge-wrapper">
                                    <div class="gauge-arc-wrapper"><div class="gauge-arc <?= $gaugeColorClass ?>"></div></div>
                                    <div class="gauge-needle-wrapper"><div class="gauge-needle <?= $gaugeRotateClass ?>"></div></div>
                                </div>
                                <span class="text-sm font-bold text-slate-900 -mt-2"><?= $percentage ?>% Completed</span>
                            </div>

                            <div class="flex flex-col gap-2 w-full sm:w-48 lg:w-40" onclick="event.stopPropagation()">
                                <?php if($percentage >= 100): ?>
                                    <button class="btn-base btn-published">Published</button>
                                <?php else: ?>
                                    <a href="<?php echo base_url('?page=inbounding&action=desktopform&id=' . $tc['id']); ?>" class="btn-base btn-edit">Edit Information</a>
                                <?php endif; ?>
                                
                                <a href="<?php echo base_url('?page=inbounding&action=i_photos&id=' . $tc['id']); ?>" class="btn-base btn-upload">Photos</a>
                                <a href="<?php echo base_url('?page=inbounding&action=i_raw_photos&id=' . $tc['id']); ?>" class="btn-base btn-upload">Raw Photos</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="accordion-content">
                    <div class="accordion-inner px-6 pb-8 border-t border-dashed border-gray-200 mx-6">
                        <h3 class="text-sm font-bold text-slate-900 mb-6 mt-4 text-center lg:text-left">Product Completion Status:</h3>

                        <div class="relative px-0 lg:px-4 lg:min-w-[700px] overflow-hidden lg:overflow-x-auto">
                            <div class="absolute left-[9px] top-[10px] bottom-[10px] w-[2px] bg-gray-200 block lg:hidden z-0"></div>
                            <div class="absolute top-[9px] left-[88px] right-[88px] h-[2px] bg-gray-200 hidden lg:block z-0"></div>
                            <div class="absolute top-[9px] left-[88px] h-[2px] bg-[#22c55e] hidden lg:block z-0" style="width: <?= $percentage ?>%;"></div>

                            <div class="flex flex-col lg:flex-row justify-between relative z-10 w-full gap-6 lg:gap-0">
                                
                                <?php 
                                    // Helper to determine step status
                                    $s1_active = true; // Inbound (Always active if in list)
                                    $s2_active = $percentage >= 25; // Photoshoot
                                    $s3_active = $percentage >= 50; // Editing
                                    $s4_active = $percentage >= 75; // Data Entry
                                    $s5_active = $percentage >= 95; // Final
                                ?>

                                <div class="flex lg:flex-col items-start lg:items-center text-left lg:text-center w-full lg:w-36 gap-4 lg:gap-0">
                                    <div class="w-5 h-5 shrink-0 rounded-full border <?= $s1_active ? 'border-[#22c55e]' : 'border-gray-300' ?> bg-white flex items-center justify-center z-10 relative lg:mb-4">
                                        <div class="w-3 h-3 <?= $s1_active ? 'bg-[#22c55e]' : 'bg-gray-300' ?> rounded-full"></div>
                                    </div>
                                    <div>
                                        <p class="timeline-text <?= $s1_active ? 'timeline-label-completed' : 'timeline-label-pending' ?>">Inbound</p>
                                        <p class="timeline-text <?= $s1_active ? 'timeline-date-completed' : 'timeline-date-pending' ?> lg:mt-1">
                                            <?= !empty($tc['gate_entry_date_time']) ? date('d M', strtotime($tc['gate_entry_date_time'])) : '-' ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex lg:flex-col items-start lg:items-center text-left lg:text-center w-full lg:w-36 gap-4 lg:gap-0">
                                    <div class="w-5 h-5 shrink-0 rounded-full border <?= $s2_active ? 'border-[#22c55e]' : 'border-gray-300' ?> bg-white flex items-center justify-center z-10 relative lg:mb-4">
                                        <div class="w-3 h-3 <?= $s2_active ? 'bg-[#22c55e]' : 'bg-gray-300' ?> rounded-full"></div>
                                    </div>
                                    <div>
                                        <p class="timeline-text <?= $s2_active ? 'timeline-label-completed' : 'timeline-label-pending' ?>">Photoshoot</p>
                                        <p class="timeline-text <?= $s2_active ? 'timeline-date-completed' : 'timeline-date-pending' ?> lg:mt-1"><?= $s2_active ? 'Done' : 'Pending' ?></p>
                                    </div>
                                </div>

                                <div class="flex lg:flex-col items-start lg:items-center text-left lg:text-center w-full lg:w-36 gap-4 lg:gap-0">
                                    <div class="w-5 h-5 shrink-0 rounded-full border <?= $s3_active ? 'border-[#22c55e]' : 'border-gray-300' ?> bg-white flex items-center justify-center z-10 relative lg:mb-4">
                                        <div class="w-3 h-3 <?= $s3_active ? 'bg-[#22c55e]' : 'bg-gray-300' ?> rounded-full"></div>
                                    </div>
                                    <div>
                                        <p class="timeline-text <?= $s3_active ? 'timeline-label-completed' : 'timeline-label-pending' ?>">Editing</p>
                                        <p class="timeline-text <?= $s3_active ? 'timeline-date-completed' : 'timeline-date-pending' ?> lg:mt-1"><?= $s3_active ? 'Done' : 'Pending' ?></p>
                                    </div>
                                </div>

                                <div class="flex lg:flex-col items-start lg:items-center text-left lg:text-center w-full lg:w-36 gap-4 lg:gap-0">
                                    <div class="w-5 h-5 shrink-0 rounded-full border <?= $s4_active ? 'border-[#22c55e]' : 'border-gray-300' ?> bg-white flex items-center justify-center z-10 relative lg:mb-4">
                                        <div class="w-3 h-3 <?= $s4_active ? 'bg-[#22c55e]' : 'bg-gray-300' ?> rounded-full"></div>
                                    </div>
                                    <div>
                                        <p class="timeline-text <?= $s4_active ? 'timeline-label-completed' : 'timeline-label-pending' ?>">Data Entry</p>
                                        <p class="timeline-text <?= $s4_active ? 'timeline-date-completed' : 'timeline-date-pending' ?> lg:mt-1"><?= $s4_active ? 'Done' : 'Pending' ?></p>
                                    </div>
                                </div>

                                <div class="flex lg:flex-col items-start lg:items-center text-left lg:text-center w-full lg:w-36 gap-4 lg:gap-0">
                                    <div class="w-5 h-5 shrink-0 rounded-full border <?= $s5_active ? 'border-[#22c55e]' : 'border-gray-300' ?> bg-white flex items-center justify-center z-10 relative lg:mb-4">
                                        <div class="w-3 h-3 <?= $s5_active ? 'bg-[#22c55e]' : 'bg-gray-300' ?> rounded-full"></div>
                                    </div>
                                    <div>
                                        <p class="timeline-text <?= $s5_active ? 'timeline-label-completed' : 'timeline-label-pending' ?>">Published</p>
                                        <p class="timeline-text <?= $s5_active ? 'timeline-date-completed' : 'timeline-date-pending' ?> lg:mt-1"><?= $s5_active ? 'Done' : 'Pending' ?></p>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="bg-white rounded-[16px] p-10 text-center text-gray-500 shadow-sm border border-gray-200">
            <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-4 text-gray-300"></i>
            No records found.
        </div>
    <?php endif; ?>

    <?php            
        $page_no = $data["page_no"] ?? 1;
        $limit = $data["limit"] ?? 10;
        $total_records = $data["totalRecords"] ?? 0;
        $total_pages = $limit > 0 ? ceil($total_records / $limit) : 1;
        $search_query = isset($data['search']) ? urlencode($data['search']) : '';
    ?>
    <?php if ($total_records > 0): ?>
        <div class="bg-white rounded-[16px] border border-gray-200 p-4 flex flex-col md:flex-row items-center justify-between gap-4 shadow-sm">
            <div class="text-sm text-gray-500 font-medium">
                Showing <?php echo (($page_no - 1) * $limit) + 1; ?> to <?php echo min($page_no * $limit, $total_records); ?> of <?php echo $total_records; ?> results
            </div>

            <div class="flex items-center gap-4 text-sm text-gray-600">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-gray-700">Rows:</span>
                    <select onchange="window.location.href='?page=inbounding&action=list&page_no=1&search_text=<?= $search_query ?>&limit=' + this.value"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-orange-500 focus:border-orange-500 block p-1.5 cursor-pointer outline-none">
                        <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                        <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="flex items-center gap-2">
                         <a <?php if($page_no > 1) { ?> href="?page=inbounding&action=list&page_no=<?= $page_no-1 ?>&limit=<?= $limit ?>&search_text=<?= $search_query ?>" <?php } else { ?> href="javascript:void(0)" <?php } ?>
                           class="p-2 rounded-full border hover:bg-gray-100 transition <?= $page_no <= 1 ? 'opacity-50 cursor-not-allowed bg-gray-50' : 'bg-white' ?>">
                             <i data-lucide="chevron-left" class="w-4 h-4"></i>
                        </a>
                        
                        <span class="bg-black text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold shadow-md">
                            <?= $page_no ?>
                        </span>
                        
                        <a <?php if($page_no < $total_pages) { ?> href="?page=inbounding&action=list&page_no=<?= $page_no+1 ?>&limit=<?= $limit ?>&search_text=<?= $search_query ?>" <?php } else { ?> href="javascript:void(0)" <?php } ?>
                           class="p-2 rounded-full border hover:bg-gray-100 transition <?= $page_no >= $total_pages ? 'opacity-50 cursor-not-allowed bg-gray-50' : 'bg-white' ?>">
                            <i data-lucide="chevron-right" class="w-4 h-4"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<div id="imagePopup" class="fixed inset-0 bg-black bg-opacity-70 hidden flex justify-center items-center z-50 transition-opacity duration-300" onclick="closeImagePopup(event)">
    <div class="bg-white p-2 rounded-xl max-w-4xl max-h-[90vh] relative flex flex-col items-center shadow-2xl transform transition-transform scale-95" onclick="event.stopPropagation();">
        <button onclick="closeImagePopup()" class="absolute -top-3 -right-3 bg-red-500 hover:bg-red-600 text-white w-8 h-8 flex items-center justify-center rounded-full text-sm shadow-md transition">✕</button>
        <img id="popupImage" class="max-w-full max-h-[85vh] rounded-lg object-contain" src="" alt="Image Preview">
    </div>
</div>

<script>
    // Initialize Lucide Icons
    lucide.createIcons();

    // Accordion Logic
    document.querySelectorAll('.accordion-item').forEach(item => {
        const toggleBtn = item.querySelector('.toggle-btn');
        toggleBtn.addEventListener('click', (e) => {
            // Prevent toggling if clicked on checkbox, images, or buttons
            if (e.target.closest('button') || e.target.closest('input') || e.target.closest('a') || e.target.closest('.cursor-zoom-in')) return;

            const isOpen = item.getAttribute('data-open') === 'true';
            const content = item.querySelector('.accordion-content');

            if (isOpen) {
                item.setAttribute('data-open', 'false');
                content.classList.remove('open');
            } else {
                item.setAttribute('data-open', 'true');
                content.classList.add('open');
            }
        });
    });

    // --- POPUP LOGIC ---
    function openImagePopup(imageUrl) {
        if(imageUrl) {
            const modal = document.getElementById('imagePopup');
            const img = document.getElementById('popupImage');
            const container = modal.querySelector('div');
            
            img.src = imageUrl;
            modal.classList.remove('hidden');
            // Small animation effect
            setTimeout(() => {
                container.classList.remove('scale-95');
                container.classList.add('scale-100');
            }, 10);
        }
    }

    function closeImagePopup() {
        const modal = document.getElementById('imagePopup');
        const container = modal.querySelector('div');
        
        container.classList.remove('scale-100');
        container.classList.add('scale-95');
        
        setTimeout(() => {
            modal.classList.add('hidden');
            document.getElementById('popupImage').src = '';
        }, 200);
    }

    // --- SELECTION & PERSISTENCE LOGIC ---
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

    function clearAllFilters() {
        localStorage.removeItem('selected_inbound_ids');
        const form = document.getElementById('filterForm');
        if(form) form.reset();
        window.location.href = '?page=inbounding&action=list';
    }

    function exportSelectedData() {
        const ids = getSelectedIds();
        if (ids.length === 0) {
            alert("Please select at least one item to export.");
            return;
        }
        const baseUrl = window.location.href.split('?')[0]; 
        const exportUrl = `${baseUrl}?page=inbounding&action=exportSelected&ids=${ids.join(',')}`;
        window.location.href = exportUrl;
    }
</script>