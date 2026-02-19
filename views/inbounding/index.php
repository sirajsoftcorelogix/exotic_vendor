<?php
require_once 'settings/database/database.php';
require_once 'models/vendor/vendor.php';
require_once 'models/user/user.php';

// Check if connection exists, if not create it
if (!isset($conn)) {
    $conn = Database::getConnection();
}
$vendorsModel = new Vendor($conn);
$usersModel = new User($conn);

// 1. Processing Logic
if (isset($inbounding_data) && is_array($inbounding_data)) {
    foreach ($inbounding_data as $key => $value) {
        $vendor = $vendorsModel->getVendorById($value['vendor_code']);
        $inbounding_data[$key]['vendor_name'] = $vendor ? $vendor['vendor_name'] : '';

        $userDetails = $usersModel->getUserById($value['received_by_user_id']);
        $inbounding_data[$key]['received_name'] = $userDetails ? $userDetails['name'] : '';

        $updaterDetails = $usersModel->getUserById($value['updated_by_user_id']);
        $inbounding_data[$key]['updated_name'] = $updaterDetails ? $updaterDetails['name'] : '-';
    }
} else {
    $inbounding_data = [];
}

unset($usersModel);
unset($vendorsModel);

// 2. Helper function for calculation
function isFilled($value)
{
    if ($value === null) return false;
    if ($value === "") return false;
    if ($value === 0 || $value === "0") return false;
    if ($value === "0000-00-00" || $value === "0000-00-00 00:00:00") return false;
    return true;
}

// 3. THUMBNAIL HELPER FUNCTION
function getThumbnail($filePath, $width = 150, $height = 150)
{
    $cleanPath = ltrim($filePath, '/');

    // Return placeholder if file missing
    if (empty($cleanPath) || !file_exists($cleanPath)) {
        return '';
    }

    // Auto-detect directory
    $dirName  = dirname($cleanPath);
    $fileName = basename($cleanPath);

    $thumbDir  = $dirName . '/thumbs/';
    $thumbPath = $thumbDir . $fileName;

    // Return existing thumb
    if (file_exists($thumbPath)) return $thumbPath;

    // Create directory
    if (!is_dir($thumbDir)) mkdir($thumbDir, 0777, true);

    $info = getimagesize($cleanPath);
    if (!$info) return $cleanPath; // Not an image or unknown format

    $mime = $info['mime'];
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($cleanPath);
            break;
        case 'image/png':
            $image = imagecreatefrompng($cleanPath);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($cleanPath);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($cleanPath);
            break;
        default:
            return $cleanPath;
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

    // Save Thumbnail
    switch ($mime) {
        case 'image/jpeg':
            imagejpeg($newImage, $thumbPath, 70);
            break;
        case 'image/png':
            imagepng($newImage, $thumbPath);
            break;
        case 'image/gif':
            imagegif($newImage, $thumbPath);
            break;
        case 'image/webp':
            imagewebp($newImage, $thumbPath);
            break;
    }

    imagedestroy($image);
    imagedestroy($newImage);

    return $thumbPath;
}
?>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    @import url('https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@700&display=swap');

    /* --- GAUGE STYLES --- */
    .gauge-wrapper {
        position: relative;
        width: 128px;
        height: 80px;
        display: flex;
        justify-content: center;
    }

    .gauge-arc-wrapper {
        width: 128px;
        height: 64px;
        position: absolute;
        top: 0;
        left: 0;
        overflow: hidden;
        z-index: 10;
    }

    .gauge-arc {
        width: 128px;
        height: 128px;
        border-radius: 50%;
        border: 20px solid #e5e7eb;
        border-bottom-color: transparent;
        border-left-color: transparent;
        border-right-color: transparent;
        box-sizing: border-box;
    }

    .gauge-color-0 {
        border-color: #ef4444;
    }

    .gauge-color-25 {
        border-color: #f97316;
    }

    .gauge-color-50 {
        border-color: #eab308;
    }

    .gauge-color-75 {
        border-color: #84cc16;
    }

    .gauge-color-90 {
        border-color: #22c55e;
    }

    .gauge-color-100 {
        border-color: #15803d;
    }

    .gauge-needle-wrapper {
        position: absolute;
        top: 64px;
        left: 50%;
        width: 0;
        height: 0;
        z-index: 20;
        transition: transform 1s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .gauge-needle {
        width: 0;
        height: 0;
        border-left: 8px solid transparent;
        border-right: 8px solid transparent;
        border-bottom: 64px solid black;
        position: absolute;
        bottom: 0;
        left: -8px;
    }

    .gauge-needle::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        background: black;
        border-radius: 50%;
        top: 56px;
        left: -8px;
    }

    /* --- Accordion Transitions --- */
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

    .accordion-content:not(.open) .accordion-inner {
        border-top-width: 0;
        padding-bottom: 0;
    }

    .accordion-inner {
        min-height: 0;
    }

    /* --- Typography & Buttons --- */
    .grid-label {
        font-family: 'Inter', sans-serif;
        font-weight: 500;
        font-size: 13px;
        line-height: 200%;
        color: rgba(221, 154, 25, 1);
    }

    .grid-value {
        font-family: 'Inter', sans-serif;
        font-weight: 500;
        font-size: 13px;
        line-height: 200%;
        color: rgba(0, 0, 0, 1);
    }

    .timeline-text {
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        line-height: 159%;
    }

    @media (min-width: 1024px) {
        .timeline-text {
            text-align: center;
        }
    }

    @media (max-width: 1023px) {
        .timeline-text {
            text-align: left;
        }
    }

    .header-title {
        font-family: 'Instrument Sans', sans-serif;
        font-weight: 700;
        font-size: 18px;
        line-height: 21.6px;
        color: #000;
    }

    .btn-base {
        font-family: 'Inter', sans-serif;
        font-weight: 500;
        font-size: 14px;
        line-height: 100%;
        text-align: center;
        transition: opacity 0.2s;
        width: 100%;
        display: block;
        padding: 10px 24px;
        border-radius: 9999px;
        white-space: nowrap;
        cursor: pointer;
    }

    .btn-edit {
        background-color: rgba(208, 103, 6, 1);
        color: #fff;
    }

    .btn-upload {
        background-color: #000000;
        color: #ffffff;
    }

    .btn-published {
        background-color: rgba(18, 136, 7, 1);
        color: #fff;
    }

    .btn-base:hover {
        opacity: 0.9;
    }

    .custom-input::placeholder {
        font-size: 13px;
    }
</style>

<div class="w-full max-w-6xl space-y-4 mx-auto">

    <div class="mb-2 px-1">
        <div class="flex flex-col md:flex-row justify-between items-center mb-4 pt-5">
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
                    <a href="<?php echo base_url('?page=inbounding&action=form1'); ?>" class="bg-black text-white px-4 py-2 rounded-full text-xs font-bold hover:bg-gray-800 transition flex items-center gap-1">
                        <i data-lucide="plus" class="w-3 h-3"></i> Add
                    </a>
                </div>
            </form>
        </div>
        <hr class="border-gray-200">
    </div>

    <div class="mb-6 px-1">
        <div class="bg-white border border-gray-200 rounded-[16px] shadow-sm overflow-hidden">

            <button type="button" onclick="toggleFilterPanel()" class="w-full flex justify-between items-center px-6 py-4 bg-gray-50 hover:bg-gray-100 transition text-left">
                <span class="font-bold text-gray-800 text-sm uppercase tracking-wide">Advance Search & Filters</span>
                <i id="filter-icon" data-lucide="chevron-down" class="w-5 h-5 text-gray-500 transition-transform duration-300"></i>
            </button>

            <div id="filter-panel" class="hidden px-6 py-6 border-t border-gray-200">
                <form method="get" id="advanceFilterForm">
                    <input type="hidden" name="page" value="inbounding">
                    <input type="hidden" name="action" value="list">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Pending Status</label>
                            <select name="status_step" class="w-full h-[40px] border border-gray-300 rounded-lg px-3 bg-white focus:outline-none focus:border-orange-500 cursor-pointer">
                                <option value="">All Items</option>
                                <?php
                                $statuses = ['Photoshoot', 'Editing', 'Data Entry', 'Published'];
                                $selStat = $data['filters']['status_step'] ?? '';
                                foreach ($statuses as $st) {
                                    $s = ($selStat == $st) ? 'selected' : '';
                                    echo "<option value='$st' $s>Pending $st</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Vendor</label>
                            <select name="vendor_code" id="filter_vendor" class="w-full h-[40px] border border-gray-300 rounded-lg px-3 bg-white focus:outline-none focus:border-orange-500 cursor-pointer">
                                <option value="">Select Vendor...</option>
                                <?php
                                $selVen = $data['filters']['vendor_code'] ?? '';
                                if (!empty($data['vendor_list'])) {
                                    foreach ($data['vendor_list'] as $v) {
                                        // Correctly uses 'id' and 'vendor_name' from the DB query
                                        $s = ($selVen == $v['id']) ? 'selected' : '';
                                        echo "<option value='{$v['id']}' $s>{$v['vendor_name']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Agent (Received By)</label>
                            <select id="filter_agent" name="agent_id" class="w-full h-[40px] border border-gray-300 rounded-lg px-3 bg-white focus:outline-none focus:border-orange-500 cursor-pointer">
                                <option value="">Select Agent...</option>
                                <?php
                                $selAgent = $data['filters']['received_by_user_id'] ?? '';
                                if (!empty($data['user_list'])) {
                                    foreach ($data['user_list'] as $u) {
                                        $s = ($selAgent == $u['id']) ? 'selected' : '';
                                        echo "<option value='{$u['id']}' $s>{$u['name']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Feeded By</label>
                            <select id="filter_updated_by" name="updated_by" class="w-full h-[40px] border border-gray-300 rounded-lg px-3 bg-white focus:outline-none focus:border-orange-500 cursor-pointer">
                                <option value="">Select User...</option>
                                <?php
                                $selUpd = $data['filters']['updated_by_user_id'] ?? '';
                                if (!empty($data['updated_user_list'])) {
                                    foreach ($data['updated_user_list'] as $u) {
                                        $s = ($selUpd == $u['id']) ? 'selected' : '';
                                        echo "<option value='{$u['id']}' $s>{$u['name']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Group</label>
                            <select id="filter_group" name="group_name" class="w-full h-[40px] border border-gray-300 rounded-lg px-3 bg-white focus:outline-none focus:border-orange-500 cursor-pointer">
                                <option value="">Select Group...</option>
                                <?php
                                $selGroup = $data['filters']['group_name'] ?? '';
                                if (!empty($data['group_list'])) {
                                    foreach ($data['group_list'] as $grp) {
                                        // $grp['id'] is the stored value (category field), $grp['name'] is display_name
                                        $s = ($selGroup == $grp['id']) ? 'selected' : '';
                                        echo "<option value='{$grp['id']}' $s>{$grp['name']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                        <button type="button" onclick="window.location.href='?page=inbounding&action=list'" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-bold hover:bg-gray-300 transition">Reset</button>
                        <button type="submit" class="bg-[#d9822b] text-white px-8 py-2 rounded-lg font-bold hover:bg-[#bf7326] transition shadow-md">Search</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php
    $current_step = $_GET['status_step'] ?? '';
    $tabs = [
        ''           => 'All List',
        'Photoshoot' => 'Pending Photoshoot',
        'Editing'    => 'Pending Editing',
        'Data Entry' => 'Pending Data Entry',
        'Published'  => 'Pending Publish',
        'my_inbound'  => 'My Inbound',
    ];
    ?>

    <div class="bg-white border-b border-gray-200 mb-6 sticky top-0 z-30">
        <div class="max-w-6xl mx-auto px-4 flex justify-between items-center">

           <?php 
$isMyInbound = isset($_GET['my_inbound']) && $_GET['my_inbound'] == 1;
?>

<nav class="-mb-px flex space-x-8 overflow-x-auto no-scrollbar">
<?php foreach ($tabs as $key => $label): 

    if ($key === 'my_inbound') {
        $url = "?page=inbounding&action=list&my_inbound=1";
    } else {
        $url = "?page=inbounding&action=list&status_step=" . urlencode($key);
    }

    $isActive = (
        ($key === 'my_inbound' && $isMyInbound) ||
        ($key !== 'my_inbound' && $current_step == $key && !$isMyInbound)
    );

    $activeClass   = "border-orange-500 text-orange-600 font-bold border-b-4";
    $inactiveClass = "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium border-b-2";
?>
    <a href="<?= $url ?>"
       class="<?= $isActive ? $activeClass : $inactiveClass ?> whitespace-nowrap py-4 px-1 text-sm transition-colors duration-200">
        <?= $label ?>
    </a>
<?php endforeach; ?>
</nav>


            <div class="relative inline-block text-left ml-4">


                <button type="button" onclick="toggleActionMenu()" class="bg-[#856404] hover:bg-[#6d5203] text-white px-5 py-2 rounded-md text-sm font-bold shadow-sm flex items-center gap-2 transition-colors">
                    Actions <i data-lucide="chevron-down" class="w-4 h-4"></i>
                </button>
                <div id="action-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50 transform origin-top-right">
                    <div class="py-1">
                        <button type="button"
                            onclick="openAssignPopupFromActions()"
                            class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                            <i data-lucide="user-plus" class="w-4 h-4 text-blue-600"></i>
                            Assign Selected
                        </button>
                        <button type="button" onclick="exportSelectedData()" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                            <i data-lucide="download" class="w-4 h-4 text-green-600"></i> Export Selected
                        </button>
                        <div class="border-t border-gray-100 my-1"></div>
                        <button type="button" onclick="deleteSelectedData()" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                            <i data-lucide="trash-2" class="w-4 h-4"></i> Delete Selected
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($inbounding_data)): ?>
        <?php foreach ($inbounding_data as $index => $tc): ?>
            <?php
            // Calculation Logic
            $filled = 0;
            $total = count($tc);
            foreach ($tc as $key => $t) {
                if (isset($tc[$key]) && isFilled($tc[$key])) {
                    $filled++;
                }
            }
            $percentage = ($total > 0) ? ($filled / $total) * 100 : 0;
            $percentage = round($percentage);

            $gaugeColorClass = 'gauge-color-0';
            if ($percentage >= 100) {
                $gaugeColorClass = 'gauge-color-100';
            } elseif ($percentage >= 80) {
                $gaugeColorClass = 'gauge-color-90';
            } elseif ($percentage >= 60) {
                $gaugeColorClass = 'gauge-color-75';
            } elseif ($percentage >= 40) {
                $gaugeColorClass = 'gauge-color-50';
            } elseif ($percentage >= 10) {
                $gaugeColorClass = 'gauge-color-25';
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
                            <div class="hidden lg:block mr-2" onclick="event.stopPropagation()">
                                <input type="checkbox" class="row-checkbox w-5 h-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 cursor-pointer"
                                    data-id="<?php echo $tc['id']; ?>" onchange="toggleSelection(<?php echo $tc['id']; ?>)">
                            </div>

                            <div class="flex gap-3 shrink-0 justify-center md:justify-start" onclick="event.stopPropagation()">
                                <?php
                                $prodThumb = !empty($tc['product_photo']) ? base_url(getThumbnail($tc['product_photo'])) : '';
                                $prodFull = !empty($tc['product_photo']) ? base_url($tc['product_photo']) : '';
                                ?>
                                <div class="w-20 h-28 bg-gray-50 rounded-lg shadow-sm border border-gray-200 flex items-center justify-center overflow-hidden cursor-zoom-in hover:opacity-80 transition"
                                    onclick="openImagePopup('<?= $prodFull ?>')">
                                    <?php if (!empty($prodThumb)): ?>
                                        <img src="<?= $prodThumb ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <i data-lucide="image" class="w-8 h-8 text-gray-300"></i>
                                    <?php endif; ?>
                                </div>

                                <?php
                                $invFile = $tc['invoice_image'] ?? '';
                                $isPdf = false;
                                $invThumb = '';
                                $invFull = '';

                                if (!empty($invFile)) {
                                    $invFull = base_url($invFile);
                                    $ext = strtolower(pathinfo($invFile, PATHINFO_EXTENSION));
                                    if ($ext === 'pdf') {
                                        $isPdf = true;
                                    } else {
                                        $invThumb = base_url(getThumbnail($invFile));
                                    }
                                }
                                ?>

                                <?php if ($isPdf): ?>
                                    <div class="relative w-20 h-28 bg-gray-50 rounded-lg shadow-sm border border-gray-200 flex flex-col items-center justify-center cursor-pointer hover:bg-gray-100 transition"
                                        onclick="window.open('<?= $invFull ?>', '_blank')">
                                        <i data-lucide="file-text" class="w-8 h-8 text-red-500 mb-1"></i>
                                        <span class="text-[9px] font-bold text-gray-600">PDF</span>
                                        <div class="absolute top-1 right-1 bg-red-500 text-white text-[8px] px-1 rounded shadow-sm">PDF</div>
                                    </div>
                                <?php else: ?>
                                    <div class="relative w-20 h-28 bg-gray-50 rounded-lg shadow-sm border border-gray-200 flex items-center justify-center overflow-hidden cursor-zoom-in hover:opacity-80 transition"
                                        onclick="openImagePopup('<?= $invFull ?>')">
                                        <?php if (!empty($invThumb)): ?>
                                            <img src="<?= $invThumb ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <i data-lucide="file-text" class="w-8 h-8 text-gray-300"></i>
                                        <?php endif; ?>
                                        <div class="absolute top-1 right-1 bg-orange-400 text-white text-[8px] px-1 rounded shadow-sm">Doc</div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-1 text-sm w-full lg:max-w-xl mx-auto md:mx-0">
                                <div class="flex items-baseline justify-between sm:justify-start"><span class="grid-label w-24 shrink-0">SKU / Code</span><span class="text-slate-400 px-2">:</span><span class="grid-value text-right sm:text-left flex-grow"><?php echo $tc['sku']; ?></span></div>
                                <div class="flex items-baseline justify-between sm:justify-start">
                                    <span class="grid-label w-24 shrink-0">
                                        <?php echo !empty($tc['Item_code']) ? 'Item Code' : 'Temp Code'; ?>
                                    </span>
                                    <span class="text-slate-400 px-2">:</span>
                                    <span class="grid-value text-right sm:text-left flex-grow font-bold text-blue-600">
                                        <?php echo !empty($tc['Item_code']) ? $tc['Item_code'] : $tc['temp_code']; ?>
                                    </span>
                                </div>
                                <div class="flex items-baseline justify-between sm:justify-start"><span class="grid-label w-24 shrink-0">Vendor</span><span class="text-slate-400 px-2">:</span><span class="grid-value text-right sm:text-left flex-grow"><?php echo $tc['vendor_name']; ?></span></div>

                                <div class="flex items-baseline justify-between sm:justify-start"><span class="grid-label w-24 shrink-0">Date</span><span class="text-slate-400 px-2">:</span><span class="grid-value text-right sm:text-left flex-grow"><?php echo !empty($tc['gate_entry_date_time']) ? date('d M Y', strtotime($tc['gate_entry_date_time'])) : '-'; ?></span></div>

                                <div class="flex items-baseline justify-between sm:justify-start"><span class="grid-label w-24 shrink-0">Category</span><span class="text-slate-400 px-2">:</span><span class="grid-value text-right sm:text-left flex-grow"><?php echo $tc['group_name_display']; ?></span></div>
                                <div class="flex items-baseline justify-between sm:justify-start"><span class="grid-label w-24 shrink-0">Received by</span><span class="text-slate-400 px-2">:</span><span class="grid-value text-right sm:text-left flex-grow"><?php echo $tc['received_name']; ?></span></div>
                                <div class="flex items-baseline justify-between sm:justify-start">
                                    <span class="grid-label w-24 shrink-0">Feeded By</span>
                                    <span class="text-slate-400 px-2">:</span>
                                    <span class="grid-value text-right sm:text-left flex-grow text-gray-600">
                                        <?php echo $tc['updated_name']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row lg:flex-row items-center gap-6 justify-center lg:justify-end w-full lg:w-auto mt-4 lg:mt-0 border-t lg:border-t-0 pt-4 lg:pt-0 border-gray-100">

                            <div class="flex flex-col items-center">
                                <div class="gauge-wrapper">
                                    <div class="gauge-arc-wrapper">
                                        <div class="gauge-arc <?= $gaugeColorClass ?>"></div>
                                    </div>

                                    <?php
                                    $rotation = ($percentage * 1.8) - 90;
                                    ?>
                                    <div class="gauge-needle-wrapper" style="transform: rotate(<?= $rotation ?>deg); transform-origin: bottom center;">
                                        <div class="gauge-needle"></div>
                                    </div>
                                </div>
                                <span class="text-sm font-bold text-slate-900 -mt-2"><?= $percentage ?>% Completed</span>
                            </div>

                            <div class="flex flex-col gap-2 w-full sm:w-48 lg:w-40" onclick="event.stopPropagation()">
                                <?php if ($percentage >= 100): ?>
                                    <button class="btn-base btn-published">Published</button>
                                <?php else: ?>
                                    <a href="<?php echo base_url('?page=inbounding&action=desktopform&id=' . $tc['id']); ?>" class="btn-base btn-edit">Edit Information</a>
                                <?php endif; ?>

                                <a href="<?php echo base_url('?page=inbounding&action=i_photos&id=' . $tc['id']); ?>" class="btn-base btn-upload">Edited Photos</a>
                                <a href="<?php echo base_url('?page=inbounding&action=i_raw_photos&id=' . $tc['id']); ?>" class="btn-base btn-upload">Raw Photos</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="accordion-content">
                    <div class="accordion-inner px-6 pb-8 border-t border-dashed border-gray-200 mx-6">
                        <h3 class="text-sm font-bold text-slate-900 mb-6 mt-4 text-center lg:text-left">Product Completion Status:</h3>

                        <div class="relative px-0 lg:px-4 lg:min-w-[700px] overflow-hidden lg:overflow-x-auto">

                            <?php
                            $stepKeys = ['inbound', 'Photoshoot', 'Editing', 'Data Entry', 'Published'];
                            $stepsData = [];

                            foreach ($stepKeys as $k) {
                                $stepsData[$k] = ['active' => false, 'date' => '-', 'user' => ''];
                            }

                            if (!empty($tc['gate_entry_date_time'])) {
                                $stepsData['inbound']['active'] = true;
                                $stepsData['inbound']['date']   = date('d M, h:i A', strtotime($tc['gate_entry_date_time']));
                                $stepsData['inbound']['user']   = $tc['received_name'] ?? '';
                            }

                            if (!empty($tc['stat_logs']) && is_array($tc['stat_logs'])) {
                                foreach ($tc['stat_logs'] as $log) {
                                    $statName = $log['stat'];
                                    if (array_key_exists($statName, $stepsData)) {
                                        $stepsData[$statName]['active'] = true;
                                        $stepsData[$statName]['date']   = date('d M, h:i A', strtotime($log['created_at']));
                                        if (!empty($log['name'])) {
                                            $stepsData[$statName]['user'] = $log['name'];
                                        }
                                    }
                                }
                            }

                            $lastActiveIndex = 0;
                            foreach ($stepKeys as $index => $key) {
                                if ($stepsData[$key]['active']) {
                                    $lastActiveIndex = $index;
                                }
                            }
                            $timelineWidth = min(100, $lastActiveIndex * 25);
                            ?>

                            <div class="absolute left-[9px] top-[10px] bottom-[10px] w-[2px] bg-gray-200 block lg:hidden z-0"></div>
                            <div class="absolute top-[9px] left-[88px] right-[88px] h-[2px] bg-gray-200 hidden lg:block z-0"></div>
                            <div class="absolute top-[9px] left-[88px] h-[2px] bg-[#22c55e] hidden lg:block z-0 transition-all duration-500"
                                style="width: calc(<?= $timelineWidth ?>% - (<?= $timelineWidth > 0 ? 0 : 0 ?>px)); max-width: calc(100% - 176px);">
                            </div>

                            <div class="flex flex-col lg:flex-row justify-between relative z-10 w-full gap-6 lg:gap-0">
                                <?php foreach ($stepKeys as $key):
                                    $info = $stepsData[$key];
                                    $isActive = $info['active'];

                                    $dotBorder = $isActive ? 'border-[#22c55e]' : 'border-gray-300';
                                    $dotBg     = $isActive ? 'bg-[#22c55e]' : 'bg-gray-300';
                                    $textClass = $isActive ? 'text-black' : 'text-gray-400';
                                    $dateClass = $isActive ? 'text-slate-900 font-bold' : 'text-gray-400 font-semibold';
                                ?>
                                    <div class="flex lg:flex-col items-start lg:items-center text-left lg:text-center w-full lg:w-36 gap-4 lg:gap-0">
                                        <div class="w-5 h-5 shrink-0 rounded-full border <?= $dotBorder ?> bg-white flex items-center justify-center z-10 relative lg:mb-4 transition-colors duration-300">
                                            <div class="w-3 h-3 <?= $dotBg ?> rounded-full transition-colors duration-300"></div>
                                        </div>
                                        <div>
                                            <p class="timeline-text <?= $textClass ?>"><?= $key ?></p>
                                            <p class="timeline-text <?= $dateClass ?> lg:mt-1 text-xs">
                                                <?= $info['date'] ?>
                                            </p>
                                            <?php if (!empty($info['user'])): ?>
                                                <p class="text-[10px] text-gray-500 font-medium leading-tight mt-0.5 lg:mt-1 truncate max-w-[120px] mx-auto">
                                                    <span class="hidden lg:inline">by </span><?= htmlspecialchars($info['user']) ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
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
                        <a <?php if ($page_no > 1) { ?> href="?page=inbounding&action=list&page_no=<?= $page_no - 1 ?>&limit=<?= $limit ?>&search_text=<?= $search_query ?>" <?php } else { ?> href="javascript:void(0)" <?php } ?>
                            class="p-2 rounded-full border hover:bg-gray-100 transition <?= $page_no <= 1 ? 'opacity-50 cursor-not-allowed bg-gray-50' : 'bg-white' ?>">
                            <i data-lucide="chevron-left" class="w-4 h-4"></i>
                        </a>

                        <span class="bg-black text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold shadow-md">
                            <?= $page_no ?>
                        </span>

                        <a <?php if ($page_no < $total_pages) { ?> href="?page=inbounding&action=list&page_no=<?= $page_no + 1 ?>&limit=<?= $limit ?>&search_text=<?= $search_query ?>" <?php } else { ?> href="javascript:void(0)" <?php } ?>
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

<!-- ASSIGN POPUP -->
<div id="assignModal">

    <div class="modal-dialog">
        <div class="modal-content">

            <!-- Sliding Container -->
            <div id="modal-slider-assign"
                class="popup-transition fixed top-0 right-0 h-full flex transform translate-x-full z-50"
                style="width: 35%; min-width: 400px;">

                <!-- Close Button -->
                <div class="flex-shrink-0 flex items-start pt-5">
                    <button id="close-assign-popup-btn"
                        class="bg-white text-gray-800 hover:bg-gray-100 transition flex items-center justify-center shadow-lg"
                        style="width: 61px; height: 61px; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Popup Panel -->
                <div class="h-full bg-white shadow-2xl" style="width: 100%;">
                    <div class="h-full w-full overflow-y-auto">
                        <div class="p-8">

                            <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-6 border-b">
                                Assign Inbound Items
                            </h2>

                            <div id="assignMsg" style="margin-top:10px;"></div>

                            <form id="assignForm">

                                <!-- Hidden IDs -->
                                <input type="hidden" name="inbound_ids" id="assign_inbound_ids">

                                <!-- User Dropdown -->
                                <div class="pt-4">
                                    <label class="text-sm font-medium text-gray-700">
                                        Select User <span class="text-red-500">*</span>
                                    </label>

                                    <select id="assign_user_id"
                                        name="assign_user_id"
                                        class="form-input w-full mt-1" required>
                                        <option value="">-- Select User --</option>
                                        <?php foreach ($alluser_list as $user): ?>
                                            <option value="<?= $user['id'] ?>">
                                                <?= $user['name'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Buttons -->
                                <div class="flex justify-center items-center gap-4 pt-6 border-t mt-6">
                                    <button type="button"
                                        id="cancel-assign-btn"
                                        class="action-btn cancel-btn">
                                        Back
                                    </button>

                                    <button type="submit"
                                        class="action-btn save-btn">
                                        Save Assignment
                                    </button>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>


<script>
    function openAssignPopupFromActions() {
        const ids = getSelectedIds();

        if (ids.length === 0) {
            alert("Please select at least one inbound item.");
            return;
        }

        document.getElementById('assign_inbound_ids').value = ids.join(',');

        // Slide popup in
        document.getElementById('modal-slider-assign')
            .classList.remove('translate-x-full');

        document.getElementById('action-menu').classList.add('hidden');
    }
</script>
<script>
    function closeAssignPopup() {
        document.getElementById('modal-slider-assign')
            .classList.add('translate-x-full');
    }



    // Close button
    document.getElementById('close-assign-popup-btn')
        .addEventListener('click', closeAssignPopup);

    // Back button
    document.getElementById('cancel-assign-btn')
        .addEventListener('click', closeAssignPopup);
</script>

<script>
    document.getElementById('assignForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const userId = document.getElementById('assign_user_id').value;
        const ids = document.getElementById('assign_inbound_ids').value;

        if (!userId) {
            alert("Please select a user");
            return;
        }

        fetch('?page=inbounding&action=bulkAssign', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `ids=${ids}&user_id=${userId}`
            })
            .then(res => res.json())
            .then(data => {
                console.log(data);
                if (data.success) {
                    alert('Items assigned successfully');
                    localStorage.removeItem('selected_inbound_ids');
                    location.reload();
                } else {
                    alert(data.message || 'Assignment failed');
                }
            })
            .catch(() => alert('Server error'));
    });
</script>

<script>
    function toggleFilterPanel() {
        const panel = document.getElementById('filter-panel');
        const icon = document.getElementById('filter-icon');

        if (panel.classList.contains('hidden')) {
            panel.classList.remove('hidden');
            icon.style.transform = 'rotate(180deg)';
        } else {
            panel.classList.add('hidden');
            icon.style.transform = 'rotate(0deg)';
        }
    }

    // Initialize TomSelect
    document.addEventListener('DOMContentLoaded', function() {
        const config = {
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            }
        };

        if (document.getElementById('filter_vendor')) new TomSelect("#filter_vendor", config);
        if (document.getElementById('filter_agent')) new TomSelect("#filter_agent", config);
        if (document.getElementById('filter_updated_by')) new TomSelect("#filter_updated_by", config);
        if (document.getElementById('filter_group')) new TomSelect("#filter_group", config);

        // Auto-open filter panel if any filter is applied (optional UX improvement)
        <?php if (!empty($data['filters']['vendor_code']) || !empty($data['filters']['received_by_user_id']) || !empty($data['filters']['updated_by_user_id']) || !empty($data['filters']['group_name']) || !empty($data['filters']['status_step'])): ?>
            toggleFilterPanel();
        <?php endif; ?>
    });
</script>
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
        if (imageUrl) {
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
        if (countSpan) countSpan.innerText = ids.length;
    }

    function clearAllFilters() {
        localStorage.removeItem('selected_inbound_ids');
        const form = document.getElementById('filterForm');
        if (form) form.reset();
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
    // --- DROPDOWN LOGIC ---
    function toggleActionMenu() {
        const menu = document.getElementById('action-menu');
        if (menu.classList.contains('hidden')) {
            menu.classList.remove('hidden');
        } else {
            menu.classList.add('hidden');
        }
    }

    // Close dropdown if clicked outside
    document.addEventListener('click', function(event) {
        const menu = document.getElementById('action-menu');
        const button = event.target.closest('button[onclick="toggleActionMenu()"]');
        const insideMenu = event.target.closest('#action-menu');

        if (!button && !insideMenu && !menu.classList.contains('hidden')) {
            menu.classList.add('hidden');
        }
    });

    // --- DELETE LOGIC ---
    function deleteSelectedData() {
        const ids = getSelectedIds();

        if (ids.length === 0) {
            alert("Please select at least one item to delete.");
            toggleActionMenu(); // Close menu
            return;
        }

        if (!confirm(`Are you sure you want to PERMANENTLY DELETE ${ids.length} selected item(s)?\nThis action cannot be undone.`)) {
            return;
        }

        // Create a temporary form to submit via POST (More secure than GET)
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '?page=inbounding&action=deleteSelected'; // Ensure this matches your Controller route

        // Input for IDs
        const inputIds = document.createElement('input');
        inputIds.type = 'hidden';
        inputIds.name = 'ids';
        inputIds.value = ids.join(','); // Send as comma separated string: "1,2,5"
        form.appendChild(inputIds);

        document.body.appendChild(form);
        form.submit();
    }
    // Toggle the dropdown visibility
    function toggleActionMenu() {
        const menu = document.getElementById('action-menu');
        menu.classList.toggle('hidden');
    }

    // Close dropdown if clicking outside
    window.addEventListener('click', function(e) {
        const btn = document.querySelector('button[onclick="toggleActionMenu()"]');
        const menu = document.getElementById('action-menu');
        if (!btn.contains(e.target) && !menu.contains(e.target)) {
            menu.classList.add('hidden');
        }
    });

    // Logic for Deleting
    function deleteSelectedData() {
        const ids = getSelectedIds(); // Uses your existing selection logic

        if (ids.length === 0) {
            alert("Please select at least one item to delete.");
            return;
        }

        if (!confirm(`Are you sure you want to PERMANENTLY DELETE ${ids.length} selected item(s)?`)) {
            return;
        }

        // Create a hidden form to submit securely via POST
        const form = document.createElement('form');
        form.method = 'POST';
        // Ensure this matches your route case 'deleteSelected'
        form.action = '?page=inbounding&action=deleteSelected';

        const inputIds = document.createElement('input');
        inputIds.type = 'hidden';
        inputIds.name = 'ids';
        inputIds.value = ids.join(',');

        form.appendChild(inputIds);
        document.body.appendChild(form);
        form.submit();
    }
</script>