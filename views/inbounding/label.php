<?php
// 1. PHP Logic & Data Fetching
$label_data = $data['form2'] ?? [];
$raw_categories = $data['category'] ?? []; // FETCH CATEGORY LIST from Controller

// --- NEW LOGIC: Resolve Category Name ---
$cat_id = $label_data['category_code'] ?? ''; 
$category_display_name = $cat_id; // Default to ID if name not found

if (!empty($raw_categories) && !empty($cat_id)) {
    foreach ($raw_categories as $cat_item) {
        if (isset($cat_item['category']) && $cat_item['category'] == $cat_id) {
            $category_display_name = $cat_item['display_name'];
            break;
        }
    }
}
// --- END NEW LOGIC ---

if(empty($label_data) && isset($_GET['id'])) {
    is_login();
    require_once 'settings/database/database.php';
    $conn = Database::getConnection();
}

if(!isset($userDetails)) {
    require_once 'models/user/user.php';
    if(!isset($conn)) $conn = Database::getConnection();
    $usersModel = new User($conn);
    $user_id = $label_data['received_by_user_id'] ?? $_SESSION['user']['id'] ?? 0;
    $userDetails = $usersModel->getUserById($user_id);
    unset($usersModel);
}

function safe($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$photoUrl = base_url(safe($label_data['product_photo'] ?? 'assets/images/placeholder.png'));
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<div class="w-full flex items-center justify-center p-0 md:p-6 bg-gray-100 min-h-screen">

    <div class="w-full h-screen md:h-auto md:min-h-[700px] md:max-w-2xl bg-white md:rounded-2xl shadow-2xl overflow-hidden flex flex-col relative border border-gray-200">
        
        <div class="bg-[#d9822b] px-4 py-4 flex items-center justify-between text-white shadow-md z-20 flex-shrink-0">
            <button type="button" id="back-btn" class="p-2 hover:bg-white/20 rounded-full transition">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </button>
            <h1 class="font-semibold text-lg tracking-wide">Print Label (3x2 inch)</h1>
            <button type="button" id="cancel-btn" class="p-2 hover:bg-white/20 rounded-full transition">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-4 md:p-8 bg-gray-100 flex flex-col items-center justify-center">
            
            <div id="printArea" class="bg-white box-border relative flex flex-col justify-between" 
                 style="width: 600px; height: 400px; padding: 20px; border: 4px solid #111827;">
                
                <div class="flex justify-between items-start mb-2 h-[160px]">
                    
                    <div class="w-[140px] h-[140px] border-2 border-gray-200 rounded-lg flex-shrink-0 overflow-hidden bg-white p-1 shadow-sm flex items-center justify-center">
                         <img src="<?php echo $photoUrl; ?>" class="w-full h-full object-contain" crossorigin="anonymous">
                    </div>

                    <div class="flex-1 px-4 flex flex-col justify-center h-full space-y-1.5 text-[15px] font-sans">
                        
                        <div class="mb-1 leading-tight">
                            <div class="font-extrabold text-gray-900 text-[16px]">Gate Entry Date & Time:</div>
                            <div class="text-gray-500 font-semibold text-[15px] mt-0.5">
                                <?php echo safe($label_data['gate_entry_date_time'] ?? '-'); ?>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <span class="font-extrabold text-gray-900 w-24 flex-shrink-0">Received By:</span>
                            <span class="text-gray-600 font-semibold truncate"><?php echo safe($userDetails['name'] ?? 'Unknown'); ?></span>
                        </div>
                        <div class="flex items-center">
                            <span class="font-extrabold text-gray-900 w-24 flex-shrink-0">Category:</span>
                            <span class="text-gray-600 font-semibold truncate"><?php echo safe($category_display_name ?? '-'); ?></span>
                        </div>
                        <div class="flex items-center">
                            <span class="font-extrabold text-gray-900 w-24 flex-shrink-0">Material:</span>
                            <span class="text-gray-600 font-semibold truncate"><?php echo safe($label_data['material_code'] ?? '-'); ?></span>
                        </div>
                         <div class="flex items-center">
                            <span class="font-extrabold text-gray-900 w-24 flex-shrink-0">Quantity:</span>
                            <span class="text-gray-600 font-semibold"><?php echo safe($label_data['quantity_received'] ?? '0'); ?> Nos</span>
                        </div>
                    </div>

                    <div class="h-full flex flex-col justify-start pt-1">
                         <div id="qrcode" class="border border-gray-200 p-1 bg-white rounded"></div>
                    </div>

                </div>

                <div class="grid grid-cols-2 gap-x-8 gap-y-2 py-4 border-t border-b border-gray-200 text-[15px] my-1">
                    
                    <div class="flex justify-between">
                        <span class="font-extrabold text-gray-900">Height:</span> 
                        <span class="text-gray-600 font-bold"><?php echo safe($label_data['height'] ?? '-'); ?> inch</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-extrabold text-gray-900">Width:</span> 
                        <span class="text-gray-600 font-bold"><?php echo safe($label_data['width'] ?? '-'); ?> inch</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="font-extrabold text-gray-900">Depth:</span> 
                        <span class="text-gray-600 font-bold"><?php echo safe($label_data['depth'] ?? '-'); ?> inch</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-extrabold text-gray-900">Weight:</span> 
                        <span class="text-gray-600 font-bold"><?php echo safe($label_data['weight'] ?? '-'); ?> gm</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="font-extrabold text-gray-900">Size:</span> 
                        <span class="text-gray-600 font-bold"><?php echo safe($label_data['size'] ?? '-'); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-extrabold text-gray-900">Color:</span> 
                        <span class="text-gray-600 font-bold"><?php echo safe($label_data['color'] ?? '0'); ?></span>
                    </div>
                
                </div>

                <div class="mt-1 pt-2">
                    <div class="text-[15px] space-y-1">
                        <div class="flex items-center">
                            <span class="font-extrabold text-gray-900 mr-2">Temp Code:</span>
                            <span class="text-gray-600 font-bold"><?php echo safe($label_data['temp_code'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="flex items-center">
                            <span class="font-extrabold text-gray-900 mr-2">Vendor :</span>
                            <span class="text-gray-600 font-bold"><?php echo safe($label_data['vendor_name'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                </div>

            </div>
            
            <p class="text-xs text-gray-400 mt-4">Preview (High Quality for 3x2 inch Print)</p>

        </div>

        <div class="p-5 bg-white border-t border-gray-100 mt-auto z-20">
            <button onclick="generatePDF()" class="w-full bg-[#d9822b] hover:bg-[#bf7326] text-white font-bold text-lg py-3 rounded-xl shadow-lg transform transition active:scale-[0.99] flex justify-center items-center gap-2">
                Print Label
            </button>
        </div>

    </div>
</div>

<script>
    // 1. Generate QR Code
    setTimeout(() => {
        const qrContainer = document.getElementById("qrcode");
        qrContainer.innerHTML = "";
        
        new QRCode(qrContainer, {
            text: "<?php echo $currentUrl; ?>",
            width: 100,    // Bigger QR since it's in the top section now
            height: 100,
            colorDark : "#111827", 
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.M
        });
    }, 100);

    // 2. Back Button
    const recordId = "<?php echo isset($_GET['id']) ? $_GET['id'] : ''; ?>";
    document.getElementById("back-btn").addEventListener("click", function () {
        window.location.href = window.location.origin + "/index.php?page=inbounding&action=form3&id=" + recordId;
    });
    document.getElementById("cancel-btn").addEventListener("click", function () {
        window.location.href = window.location.origin + "/index.php?page=inbounding&action=lsit";
    });

    // 3. PDF Generation Logic (3 inch x 2 inch)
    function generatePDF() {
        const { jsPDF } = window.jspdf;
        const element = document.querySelector("#printArea");

        html2canvas(element, { 
            scale: 2, 
            useCORS: true 
        }).then(canvas => {
            const imgData = canvas.toDataURL("image/png");
            
            // 3 inch x 2 inch in mm
            const pdfWidth = 76.2; 
            const pdfHeight = 50.8; 
            
            const pdf = new jsPDF("l", "mm", [pdfWidth, pdfHeight]);

            pdf.addImage(imgData, "PNG", 0, 0, pdfWidth, pdfHeight);
            
            pdf.save("Label_3x2_<?php echo safe($label_data['temp_code'] ?? 'Item'); ?>.pdf");
            window.open(pdf.output('bloburl'), '_blank');
        });
    }
</script>